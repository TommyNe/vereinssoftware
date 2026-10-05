<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\CancelSepaDebitRun;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCancellationRun(Club $club, SepaDebitRunStatus $status = SepaDebitRunStatus::Prepared): SepaDebitRun
{
    return SepaDebitRun::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Oktoberbeiträge',
        'collection_date' => '2026-10-15',
        'status' => $status,
    ]);
}

function createCancellationItem(SepaDebitRun $run, SepaDebitItemStatus $status = SepaDebitItemStatus::Prepared): SepaDebitItem
{
    $member = Member::factory()->create(['club_id' => $run->club_id]);
    $mandate = SepaMandate::factory()->create([
        'club_id' => $run->club_id,
        'member_id' => $member->getKey(),
    ]);
    $type = ContributionType::query()->firstOrCreate([
        'club_id' => $run->club_id,
        'name' => 'Jahresbeitrag',
    ], ['interval' => 'yearly', 'is_active' => true]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $run->club_id,
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionChargeStatus::Open,
        'amount' => '25.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-10-15',
    ]);

    return $run->items()->create([
        'club_id' => $run->club_id,
        'contribution_charge_id' => $charge->getKey(),
        'member_id' => $member->getKey(),
        'sepa_mandate_id' => $mandate->getKey(),
        'status' => $status,
        'amount' => $charge->amount,
        'purpose' => $charge->description,
        'account_holder' => $mandate->account_holder,
        'iban' => $mandate->iban,
        'bic' => $mandate->bic,
        'mandate_reference' => $mandate->mandate_reference,
        'mandate_signed_at' => $mandate->signed_at,
    ]);
}

it('cancels only prepared items belonging to the cancelled run', function (SepaDebitRunStatus $status): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $user = User::factory()->create();
    $run = createCancellationRun($club, $status);
    $prepared = createCancellationItem($run);
    $cancelled = createCancellationItem($run, SepaDebitItemStatus::Cancelled);
    $cancelled->forceFill(['updated_at' => '2026-10-01 12:00:00'])->save();
    $cancelledAttributes = $cancelled->fresh()->getRawOriginal();
    $otherRun = createCancellationRun($club);
    $otherItem = createCancellationItem($otherRun);

    app(CancelSepaDebitRun::class)->handle($run, '  Doppelte Vorbereitung  ', $user);

    $freshRun = $run->fresh();
    expect($freshRun->status)->toBe(SepaDebitRunStatus::Cancelled)
        ->and($freshRun->cancellation_reason)->toBe('Doppelte Vorbereitung')
        ->and($freshRun->cancelled_by)->toBe($user->getKey())
        ->and($freshRun->cancelled_at->equalTo(now()))->toBeTrue()
        ->and($prepared->fresh()->status)->toBe(SepaDebitItemStatus::Cancelled)
        ->and($cancelled->fresh()->getRawOriginal())->toBe($cancelledAttributes)
        ->and($otherItem->fresh()->status)->toBe(SepaDebitItemStatus::Prepared)
        ->and($otherRun->fresh()->status)->toBe(SepaDebitRunStatus::Prepared)
        ->and($prepared->charge->status)->toBe(ContributionChargeStatus::Open);
    $this->assertDatabaseCount('sepa_debit_items', 3);
})->with([SepaDebitRunStatus::Draft, SepaDebitRunStatus::Prepared]);

it('leaves the run and its items unchanged when cancellation is rejected', function (
    SepaDebitRunStatus $status,
    string $reason,
    string $message,
): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $run = createCancellationRun($club, $status);
    $item = createCancellationItem($run);
    $runAttributes = $run->fresh()->getRawOriginal();
    $itemAttributes = $item->fresh()->getRawOriginal();
    $user = User::factory()->create();

    expect(fn () => app(CancelSepaDebitRun::class)->handle($run, $reason, $user))
        ->toThrow(DomainException::class, $message);

    expect($run->fresh()->getRawOriginal())->toBe($runAttributes)
        ->and($item->fresh()->getRawOriginal())->toBe($itemAttributes);
})->with([
    'exported run' => [SepaDebitRunStatus::Exported, 'Fehler', 'Ein bereits exportierter Lastschriftlauf kann nicht auf diesem Weg storniert werden.'],
    'cancelled run' => [SepaDebitRunStatus::Cancelled, 'Fehler', 'Der Lastschriftlauf ist bereits storniert.'],
    'empty reason' => [SepaDebitRunStatus::Prepared, '', 'Eine Begründung ist erforderlich.'],
    'blank reason' => [SepaDebitRunStatus::Prepared, '   ', 'Eine Begründung ist erforderlich.'],
]);

it('cannot cancel a foreign club run or its items', function (): void {
    $run = createCancellationRun(Club::factory()->create());
    $item = createCancellationItem($run);
    $runAttributes = $run->fresh()->getRawOriginal();
    $itemAttributes = $item->fresh()->getRawOriginal();
    app(CurrentClub::class)->set(Club::factory()->create());
    $user = User::factory()->create();

    expect(fn () => app(CancelSepaDebitRun::class)->handle($run, 'Fehler', $user))
        ->toThrow(DomainException::class, 'Der Lastschriftlauf gehört nicht zum aktuellen Verein.');

    expect($run->fresh()->getRawOriginal())->toBe($runAttributes)
        ->and($item->fresh()->getRawOriginal())->toBe($itemAttributes);
});

it('rolls back item cancellation if updating the run fails', function (): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $run = createCancellationRun($club);
    $item = createCancellationItem($run);
    $runAttributes = $run->fresh()->getRawOriginal();
    $itemAttributes = $item->fresh()->getRawOriginal();
    $missingUser = User::factory()->make(['id' => 999999]);

    expect(fn () => app(CancelSepaDebitRun::class)->handle($run, 'Fehler', $missingUser))
        ->toThrow(QueryException::class);

    expect($run->fresh()->getRawOriginal())->toBe($runAttributes)
        ->and($item->fresh()->getRawOriginal())->toBe($itemAttributes);
});
