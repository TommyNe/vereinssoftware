<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\CreatePaymentFromSettledSepaItem;
use App\Application\Sepa\ExportSepaDebitRun;
use App\Application\Sepa\RecordSepaDebitItemEvent;
use App\Application\Sepa\RefreshSepaDebitRunStatus;
use App\Application\Sepa\SubmitSepaDebitRun;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/** @return array{club: Club, user: User, run: SepaDebitRun} */
function sepaDebitRunAuditFixture(bool $canExport = true): array
{
    test()->travelTo(CarbonImmutable::parse('2026-10-06 12:00:00'));
    Storage::fake('local');
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    app(CurrentClub::class)->set($club);
    setPermissionsTeamId($club->getKey());
    if ($canExport) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsExport->value, 'web'));
    }
    test()->actingAs($user)->withSession(['current_club_id' => $club->getKey()]);
    ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Sportverein Musterstadt',
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
        'is_active' => true,
    ]);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $mandate = SepaMandate::factory()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'bic' => 'COBADEFFXXX',
        'mandate_reference' => 'SV-0001',
    ]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionChargeStatus::Open,
        'amount' => '25.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-10-15',
    ]);
    $run = SepaDebitRun::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Oktoberbeiträge',
        'collection_date' => '2026-10-15',
        'status' => SepaDebitRunStatus::Prepared,
        'items_count' => 1,
        'total_amount' => '25.00',
    ]);
    $run->items()->create([
        'club_id' => $club->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'member_id' => $member->getKey(),
        'sepa_mandate_id' => $mandate->getKey(),
        'amount' => '25.00',
        'purpose' => $charge->description,
        'account_holder' => $mandate->account_holder,
        'iban' => $mandate->iban,
        'bic' => $mandate->bic,
        'mandate_reference' => $mandate->mandate_reference,
        'mandate_signed_at' => $mandate->signed_at,
    ]);

    return compact('club', 'user', 'run');
}

/** @param array{xml_storage_path?: string, xml_generated_at?: string, exported_at?: string} $exportMetadata */
function assertSepaDebitRunAudit(Activity $activity, SepaDebitRun $run, User $user, array $exportMetadata = []): void
{
    expect($activity->log_name)->toBe('security');
    expect($activity->subject->is($run))->toBeTrue();
    expect($activity->causer->is($user))->toBeTrue();
    expect($activity->properties->except(['ip_address', 'user_agent', 'method', 'path'])->all())->toBe([
        'club_id' => $run->club_id,
        'run_id' => $run->getKey(),
        'xml_format' => 'pain.008.001.08',
        ...$exportMetadata,
        'items_count' => 1,
        'total_amount' => '25.00',
        'xml_sha256' => $run->xml_sha256,
    ]);
}

it('audits submission once with only the run identifier', function (): void {
    $fixture = sepaDebitRunAuditFixture();
    $run = app(ExportSepaDebitRun::class)->handle($fixture['run']);
    $service = app(SubmitSepaDebitRun::class);
    $service->handle($run, SepaSubmissionMethod::BankPortal, now()->toImmutable(), 'BANK-123', $fixture['user']);

    expect(fn () => $service->handle($run, SepaSubmissionMethod::BankPortal, now()->toImmutable(), 'BANK-123', $fixture['user']))
        ->toThrow(DomainException::class);

    $activities = Activity::query()->where('event', AuditAction::SepaDebitRunSubmitted->value)->get();
    expect($activities)->toHaveCount(1);
    $activity = $activities->sole();
    expect($activity->subject->is($run))->toBeTrue();
    expect($activity->causer->is($fixture['user']))->toBeTrue();
    expect($activity->properties->except(['ip_address', 'user_agent', 'method', 'path'])->all())->toBe([
        'club_id' => $run->club_id,
        'run_id' => $run->getKey(),
    ]);
});

it('audits item feedback with only identifiers and the normalized reason code', function (SepaDebitItemStatus $current, SepaDebitEventType $type, AuditAction $action): void {
    $fixture = sepaDebitRunAuditFixture();
    $item = $fixture['run']->items()->sole();
    $item->update(['status' => $current, 'end_to_end_id' => 'E2E-123']);
    $service = app(RecordSepaDebitItemEvent::class);

    if ($type === SepaDebitEventType::Returned) {
        app(CreatePaymentFromSettledSepaItem::class)->handle($item, now()->toImmutable(), $fixture['user']);
    }

    $service->handle($item, $type, now()->toImmutable(), ' am04 ', $item->iban, $item->account_holder, 'manual', $fixture['user']);

    $activities = Activity::query()->where('event', $action->value)->get();
    expect($activities)->toHaveCount(1);
    $activity = $activities->sole();
    expect($activity->log_name)->toBe('security');
    expect($activity->subject->is($item))->toBeTrue();
    expect($activity->causer->is($fixture['user']))->toBeTrue();
    expect($activity->properties->except(['ip_address', 'user_agent', 'method', 'path'])->all())->toBe([
        'club_id' => $item->club_id,
        'run_id' => $fixture['run']->getKey(),
        'item_id' => $item->getKey(),
        'end_to_end_id' => 'E2E-123',
        'reason_code' => 'AM04',
    ]);
    expect($activity->attribute_changes->all())->toBeEmpty();
})->with([
    'accepted' => [SepaDebitItemStatus::Submitted, SepaDebitEventType::Accepted, AuditAction::SepaDebitItemAccepted],
    'rejected' => [SepaDebitItemStatus::Submitted, SepaDebitEventType::Rejected, AuditAction::SepaDebitItemRejected],
    'settled' => [SepaDebitItemStatus::Accepted, SepaDebitEventType::Settled, AuditAction::SepaDebitItemSettled],
    'returned' => [SepaDebitItemStatus::Settled, SepaDebitEventType::Returned, AuditAction::SepaDebitItemReturned],
    'refunded' => [SepaDebitItemStatus::Settled, SepaDebitEventType::Refunded, AuditAction::SepaDebitItemRefunded],
]);

it('does not audit item feedback when the transition is invalid or the club differs', function (bool $foreignClub): void {
    $fixture = sepaDebitRunAuditFixture();
    $item = $fixture['run']->items()->sole();
    if ($foreignClub) {
        $item->update(['status' => SepaDebitItemStatus::Submitted]);
        app(CurrentClub::class)->set(Club::factory()->create());
    }

    expect(fn () => app(RecordSepaDebitItemEvent::class)->handle(
        $item, SepaDebitEventType::Accepted, now()->toImmutable(), null, null, null, 'manual', $fixture['user'],
    ))->toThrow(DomainException::class);

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitItemAccepted->value]);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
})->with(['invalid transition' => false, 'foreign club' => true]);

it('audits accepted and rejected run status changes once with only the run identifier', function (SepaDebitItemStatus $itemStatus, AuditAction $action): void {
    $fixture = sepaDebitRunAuditFixture();
    $run = $fixture['run'];
    $run->update(['status' => SepaDebitRunStatus::Submitted]);
    $run->items()->sole()->update(['status' => $itemStatus]);

    app(RefreshSepaDebitRunStatus::class)->handle($run);
    app(RefreshSepaDebitRunStatus::class)->handle($run);

    $activities = Activity::query()->where('event', $action->value)->get();
    expect($activities)->toHaveCount(1);
    $activity = $activities->sole();
    expect($activity->subject->is($run))->toBeTrue();
    expect($activity->causer->is($fixture['user']))->toBeTrue();
    expect($activity->properties->except(['ip_address', 'user_agent', 'method', 'path'])->all())->toBe([
        'club_id' => $run->club_id,
        'run_id' => $run->getKey(),
    ]);
})->with([
    'accepted' => [SepaDebitItemStatus::Accepted, AuditAction::SepaDebitRunAccepted],
    'rejected' => [SepaDebitItemStatus::Rejected, AuditAction::SepaDebitRunRejected],
]);

it('does not audit acceptance or rejection for a run with pending positions', function (): void {
    $fixture = sepaDebitRunAuditFixture();
    $run = $fixture['run'];
    $run->items()->sole()->update(['status' => SepaDebitItemStatus::Submitted]);

    app(RefreshSepaDebitRunStatus::class)->handle($run);

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunAccepted->value]);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunRejected->value]);
});

it('audits a successful export once with only safe run metadata', function (): void {
    $fixture = sepaDebitRunAuditFixture();

    $run = app(ExportSepaDebitRun::class)->handle($fixture['run']);
    app(ExportSepaDebitRun::class)->handle($fixture['run']);

    $activities = Activity::query()->where('event', AuditAction::SepaDebitRunExported->value)->get();
    expect($activities)->toHaveCount(1);
    assertSepaDebitRunAudit($activities->sole(), $run, $fixture['user'], [
        'xml_storage_path' => $run->xml_storage_path,
        'xml_generated_at' => '2026-10-06T12:00:00.000000Z',
        'exported_at' => '2026-10-06T12:00:00.000000Z',
    ]);
    expect($run->status)->toBe(SepaDebitRunStatus::Exported);
    Storage::disk('local')->assertExists($run->xml_storage_path);
});

it('does not audit an export rejected by validation', function (): void {
    $fixture = sepaDebitRunAuditFixture();
    $fixture['run']->update(['errors_count' => 1]);

    expect(fn () => app(ExportSepaDebitRun::class)->handle($fixture['run']))
        ->toThrow(DomainException::class, 'Der SEPA-Lauf enthält ungeklärte Vorbereitungsfehler.');

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunExported->value]);
    expect($fixture['run']->fresh()->status)->toBe(SepaDebitRunStatus::Prepared);
});

it('audits every successful XML download with only safe run metadata', function (): void {
    $fixture = sepaDebitRunAuditFixture();
    $run = app(ExportSepaDebitRun::class)->handle($fixture['run']);
    $xml = Crypt::decryptString(Storage::disk('local')->get($run->xml_storage_path));

    $this->get(route('sepa-debit-runs.download', $run))
        ->assertOk()
        ->assertDownload('sepa-2026-10-15-'.$run->getKey().'.xml')
        ->assertStreamedContent($xml);
    $this->get(route('sepa-debit-runs.download', $run))
        ->assertOk()
        ->assertStreamedContent($xml);

    $activities = Activity::query()->where('event', AuditAction::SepaDebitRunDownloaded->value)->get();
    expect($activities)->toHaveCount(2);
    foreach ($activities as $activity) {
        assertSepaDebitRunAudit($activity, $run, $fixture['user']);
        expect($activity->getProperty('method'))->toBe('GET');
        expect($activity->getProperty('path'))->toBe('sepa-debit-runs/'.$run->getKey().'/download');
    }
});

it('does not audit a download when the stored XML is unavailable or corrupt', function (string $failure): void {
    $fixture = sepaDebitRunAuditFixture();
    $run = app(ExportSepaDebitRun::class)->handle($fixture['run']);
    $disk = Storage::disk('local');
    match ($failure) {
        'missing' => $disk->delete($run->xml_storage_path),
        'checksum' => $disk->put($run->xml_storage_path, Crypt::encryptString('<tampered/>')),
        'encryption' => $disk->put($run->xml_storage_path, 'invalid encrypted content'),
    };

    $response = $this->get(route('sepa-debit-runs.download', $run));

    $response->assertStatus($failure === 'missing' ? 404 : 500);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
})->with(['missing', 'checksum', 'encryption']);

it('does not audit a download without export permission', function (): void {
    $fixture = sepaDebitRunAuditFixture(canExport: false);
    $run = app(ExportSepaDebitRun::class)->handle($fixture['run']);

    $this->get(route('sepa-debit-runs.download', $run))->assertForbidden();

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});

it('does not audit a download of another club run', function (): void {
    $fixture = sepaDebitRunAuditFixture();
    $run = app(ExportSepaDebitRun::class)->handle($fixture['run']);
    $otherClub = Club::factory()->create();
    $fixture['user']->clubs()->attach($otherClub);
    $this->withSession(['current_club_id' => $otherClub->getKey()]);

    $this->get(route('sepa-debit-runs.download', $run))->assertNotFound();

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});

it('does not audit a download of a run that has not been exported', function (): void {
    $fixture = sepaDebitRunAuditFixture();

    $this->get(route('sepa-debit-runs.download', $fixture['run']))->assertNotFound();

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});

it('does not audit an unauthenticated download', function (): void {
    $fixture = sepaDebitRunAuditFixture();
    auth()->logout();

    $this->getJson(route('sepa-debit-runs.download', $fixture['run']))->assertUnauthorized();

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});
