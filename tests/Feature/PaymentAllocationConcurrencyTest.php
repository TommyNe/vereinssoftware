<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\PaymentManager;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Membership\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

uses(DatabaseTruncation::class);

afterEach(function (): void {
    $this->truncateTablesForAllConnections();
});

it('serializes concurrent allocations so a hundred euro payment cannot fund two hundred euros', function (): void {
    if (DB::getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
        $this->markTestSkipped('Requires a dedicated PostgreSQL test database and the pcntl and posix extensions.');
    }

    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    $payment = Payment::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => 'booked',
        'method' => 'bank_transfer',
        'amount' => '100.00',
        'booking_date' => '2026-10-19',
    ]);
    $firstCharge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '100.00',
        'description' => 'Erste Forderung',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-19',
    ]);
    $secondCharge = $firstCharge->replicate()->fill(['description' => 'Zweite Forderung']);
    $secondCharge->save();

    $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    expect($sockets)->not->toBeFalse();
    DB::purge();
    $childPid = pcntl_fork();
    expect($childPid)->not->toBe(-1);

    if ($childPid === 0) {
        fclose($sockets[0]);
        stream_set_timeout($sockets[1], 10);

        try {
            fwrite($sockets[1], DB::selectOne('select pg_backend_pid() as pid')->pid."\n");
            if (fgets($sockets[1]) !== "allocate\n") {
                throw new RuntimeException('The competing allocation was not started.');
            }

            app(PaymentManager::class)->allocate($payment, $secondCharge, '100.00');
            $result = ['status' => 'allocated'];
        } catch (DomainException $exception) {
            $result = ['status' => 'rejected', 'message' => $exception->getMessage()];
        } catch (Throwable $exception) {
            $result = ['status' => 'error', 'message' => $exception->getMessage()];
        }

        fwrite($sockets[1], json_encode($result, JSON_THROW_ON_ERROR)."\n");
        fclose($sockets[1]);
        exit(0);
    }

    fclose($sockets[1]);
    stream_set_timeout($sockets[0], 10);

    try {
        $backendPid = (int) fgets($sockets[0]);
        expect($backendPid)->toBeGreaterThan(0);
        $parentBackendPid = DB::selectOne('select pg_backend_pid() as pid')->pid;
        DB::beginTransaction();
        app(PaymentManager::class)->allocate($payment, $firstCharge, '100.00');
        fwrite($sockets[0], "allocate\n");

        $blocked = false;
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $blocked = (bool) DB::selectOne('select ? = any(pg_blocking_pids(?)) as blocked', [$parentBackendPid, $backendPid])->blocked;
            if ($blocked) {
                break;
            }
            usleep(10000);
        }

        expect($blocked)->toBeTrue();
        DB::commit();
        $result = json_decode(fgets($sockets[0]), true, flags: JSON_THROW_ON_ERROR);

        expect($result)->toBe([
            'status' => 'rejected',
            'message' => 'Die Zuordnung ist größer als der noch verfügbare Zahlungsbetrag.',
        ]);
        $this->assertDatabaseCount('payment_allocations', 1);
        expect(bccomp((string) $payment->allocations()->sum('amount'), '100.00', 2))->toBe(0);
        $this->assertDatabaseMissing('payment_allocations', ['contribution_charge_id' => $secondCharge->getKey()]);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($sockets[0]);
        if (pcntl_waitpid($childPid, $status, WNOHANG) === 0) {
            posix_kill($childPid, SIGTERM);
            pcntl_waitpid($childPid, $status);
        }
    }
});
