<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\CancelSepaDebitRun;
use App\Application\Sepa\PrepareSepaDebitRun;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRunError;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('prepares only due charges and records preparation errors', function (
    string $dueDate,
    bool $hasMandate,
    int $expectedItems,
    string $expectedAmount,
    int $expectedErrors = 0,
    ?string $expectedMessage = null,
    ?string $validFrom = null,
    bool $corruptBankData = false,
): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    $club = Club::factory()->create();
    $user = User::factory()->create();
    app(CurrentClub::class)->set($club);
    ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Testverein e.V.',
        'iban' => 'DE89370400440532013000',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    if ($hasMandate) {
        $mandate = SepaMandate::factory()->create([
            'club_id' => $club->getKey(),
            'member_id' => $member->getKey(),
            'valid_from' => $validFrom,
        ]);
        if ($corruptBankData) {
            DB::table('sepa_mandates')->where('id', $mandate->getKey())->update(['iban' => 'invalid-ciphertext']);
        }
    }
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
        'amount' => '25.50',
        'description' => 'Mitgliedsbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => $dueDate,
    ]);

    $run = app(PrepareSepaDebitRun::class)->handle(
        name: 'Oktoberbeiträge',
        collectionDate: CarbonImmutable::parse('2026-10-15'),
        createdBy: $user,
    );

    $this->assertDatabaseCount('sepa_debit_items', $expectedItems);
    $this->assertDatabaseCount('sepa_debit_run_errors', $expectedErrors);
    $this->assertDatabaseHas('sepa_debit_runs', [
        'id' => $run->getKey(),
        'status' => SepaDebitRunStatus::Prepared->value,
        'items_count' => $expectedItems,
        'errors_count' => $expectedErrors,
        'total_amount' => $expectedAmount,
    ]);
    expect($run->total_amount)->toBe($expectedAmount);
    expect($run->items->map(static fn (SepaDebitItem $item): SepaDebitItemStatus => $item->status)->all())
        ->toBe(array_fill(0, $expectedItems, SepaDebitItemStatus::Prepared));
    expect($run->errors->map(static fn (SepaDebitRunError $error): array => [
        'member_id' => $error->member_id,
        'contribution_charge_id' => $error->contribution_charge_id,
        'message' => $error->message,
    ])->all())->toBe($expectedMessage === null ? [] : [[
        'member_id' => (string) $member->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'message' => $expectedMessage,
    ]]);
    expect($charge->fresh()->status)->toBe(ContributionChargeStatus::Open);
})->with([
    'due before collection' => ['2026-10-14', true, 1, '25.50'],
    'due on collection day' => ['2026-10-15', true, 1, '25.50'],
    'due after collection' => ['2026-10-16', true, 0, '0.00'],
    'future charge without a mandate is not an error' => ['2026-10-16', false, 0, '0.00'],
    'due charge without active mandate' => ['2026-10-15', false, 0, '0.00', 1, 'Kein aktives SEPA-Mandat.'],
    'mandate not yet valid on collection day' => ['2026-10-15', true, 0, '0.00', 1, 'SEPA-Mandat am Einzugstag noch nicht gültig.', '2026-10-16'],
    'technical errors expose no internal details' => ['2026-10-15', true, 0, '0.00', 1, 'Die Forderung konnte nicht für den SEPA-Lauf vorbereitet werden.', null, true],
]);

/** @return array{club: Club, user: User, charge: ContributionCharge, mandate: SepaMandate} */
function createRepeatPreparationCharge(string $creditorIdentifier = 'DE98ZZZ09999999999'): array
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $user = User::factory()->create();
    ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => $creditorIdentifier,
        'account_holder' => 'Testverein e.V.',
        'iban' => 'DE89370400440532013000',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $mandate = SepaMandate::factory()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
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
        'amount' => '25.50',
        'description' => 'Mitgliedsbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-10-15',
    ]);

    return ['club' => $club, 'user' => $user, 'charge' => $charge, 'mandate' => $mandate];
}

it('skips charges already included in a prepared item without recording an error', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user, 'charge' => $charge] = createRepeatPreparationCharge();
    $service = app(PrepareSepaDebitRun::class);
    $original = $service->handle('Erster Lauf', CarbonImmutable::parse('2026-10-15'), $user);
    $originalItem = $original->items()->sole();

    $repeat = $service->handle('Zweiter Lauf', CarbonImmutable::parse('2026-10-15'), $user);

    expect($repeat->items_count)->toBe(0);
    expect($repeat->errors_count)->toBe(0);
    expect($repeat->total_amount)->toBe('0.00');
    $this->assertDatabaseCount('sepa_debit_items', 1);
    $this->assertDatabaseCount('sepa_debit_run_errors', 0);
    $this->assertDatabaseHas('sepa_debit_items', [
        'id' => $originalItem->getKey(),
        'sepa_debit_run_id' => $original->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'status' => 'prepared',
    ]);
});

it('can prepare the same charge again after multiple run cancellations while preserving history', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user, 'charge' => $charge] = createRepeatPreparationCharge();
    $service = app(PrepareSepaDebitRun::class);
    $first = $service->handle('Erster Lauf', CarbonImmutable::parse('2026-10-15'), $user);
    $firstItem = $first->items()->sole();
    app(CancelSepaDebitRun::class)->handle($first, 'Erste Korrektur', $user);
    $second = $service->handle('Zweiter Lauf', CarbonImmutable::parse('2026-10-15'), $user);
    $secondItem = $second->items()->sole();
    app(CancelSepaDebitRun::class)->handle($second, 'Zweite Korrektur', $user);

    $third = $service->handle('Dritter Lauf', CarbonImmutable::parse('2026-10-15'), $user);

    expect($third->items_count)->toBe(1);
    expect($third->errors_count)->toBe(0);
    expect($third->total_amount)->toBe('25.50');
    $this->assertDatabaseCount('sepa_debit_items', 3);
    $this->assertDatabaseCount('sepa_debit_run_errors', 0);
    foreach ([$firstItem, $secondItem] as $cancelledItem) {
        $this->assertDatabaseHas('sepa_debit_items', [
            'id' => $cancelledItem->getKey(),
            'sepa_debit_run_id' => $cancelledItem->sepa_debit_run_id,
            'contribution_charge_id' => $charge->getKey(),
            'status' => 'cancelled',
        ]);
    }
    $this->assertDatabaseHas('sepa_debit_items', [
        'sepa_debit_run_id' => $third->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'status' => 'prepared',
    ]);
    expect($charge->fresh()->status)->toBe(ContributionChargeStatus::Open);
});

it('rejects a second prepared item for the same charge at the database boundary', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user] = createRepeatPreparationCharge();
    $run = app(PrepareSepaDebitRun::class)->handle('Erster Lauf', CarbonImmutable::parse('2026-10-15'), $user);
    $item = $run->items()->sole();

    expect(fn () => DB::transaction(fn (): bool => $item->replicate()->save()))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('sepa_debit_items', 1);
    $this->assertModelExists($item);
});

it('requires an active configuration of the current club before creating any run', function (bool $configurationExists): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['club' => $club, 'user' => $user] = createRepeatPreparationCharge();
    $configuration = ClubSepaConfiguration::query()->where('club_id', $club->getKey())->sole();
    if ($configurationExists) {
        $configuration->update(['is_active' => false]);
    } else {
        $configuration->delete();
    }
    $foreignConfiguration = $configuration->replicate();
    $foreignConfiguration->club_id = Club::factory()->create()->getKey();
    $foreignConfiguration->creditor_identifier = 'DE98ZZZ09999999998';
    $foreignConfiguration->is_active = true;
    $foreignConfiguration->save();

    expect(fn () => app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user))
        ->toThrow(DomainException::class, 'Für diesen Verein ist keine aktive SEPA-Konfiguration vorhanden.');

    $this->assertDatabaseCount('sepa_debit_runs', 0);
    $this->assertDatabaseCount('sepa_debit_items', 0);
    $this->assertDatabaseCount('sepa_debit_run_errors', 0);
})->with(['missing configuration' => false, 'disabled configuration' => true]);

it('rejects a collection date before the configured minimum lead time without creating a run', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['club' => $club, 'user' => $user] = createRepeatPreparationCharge();
    ClubSepaConfiguration::query()->where('club_id', $club->getKey())->update(['default_lead_days' => 10]);

    expect(fn () => app(PrepareSepaDebitRun::class)->handle('Zu früh', CarbonImmutable::parse('2026-10-14'), $user))
        ->toThrow(DomainException::class, 'Das Einzugsdatum liegt vor dem konfigurierten Mindestvorlauf.');

    $this->assertDatabaseCount('sepa_debit_runs', 0);
    $this->assertDatabaseCount('sepa_debit_items', 0);
    $this->assertDatabaseCount('sepa_debit_run_errors', 0);
});

it('uses the current club and accepts exactly the configured minimum lead time', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['club' => $club, 'user' => $user] = createRepeatPreparationCharge();
    ClubSepaConfiguration::query()->where('club_id', $club->getKey())->update(['default_lead_days' => 10]);

    $run = app(PrepareSepaDebitRun::class)->handle('  Oktober  ', CarbonImmutable::parse('2026-10-15'), $user);

    $this->assertDatabaseHas('sepa_debit_runs', [
        'id' => $run->getKey(),
        'club_id' => $club->getKey(),
        'created_by' => $user->getKey(),
        'name' => 'Oktober',
        'status' => 'prepared',
        'items_count' => 1,
        'errors_count' => 0,
        'total_amount' => '25.50',
    ]);
    expect($run->collection_date->format('Y-m-d'))->toBe('2026-10-15');
    expect($run->prepared_at->equalTo(now()))->toBeTrue();
});

it('includes only open charges with a positive amount', function (ContributionChargeStatus $status, string $amount, int $expectedItems): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user, 'charge' => $charge] = createRepeatPreparationCharge();
    $charge->update(['status' => $status, 'amount' => $amount]);

    $run = app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user);

    expect($run->items_count)->toBe($expectedItems);
    expect($run->total_amount)->toBe($expectedItems === 1 ? '25.50' : '0.00');
    expect($run->errors_count)->toBe(0);
    $this->assertDatabaseCount('sepa_debit_items', $expectedItems);
    $this->assertDatabaseCount('sepa_debit_run_errors', 0);
    $this->assertDatabaseHas('contribution_charges', ['id' => $charge->getKey(), 'status' => $status->value]);
})->with([
    'positive open charge' => [ContributionChargeStatus::Open, '25.50', 1],
    'zero amount' => [ContributionChargeStatus::Open, '0.00', 0],
    'negative amount' => [ContributionChargeStatus::Open, '-25.50', 0],
    'paid charge' => [ContributionChargeStatus::Paid, '25.50', 0],
    'cancelled charge' => [ContributionChargeStatus::Cancelled, '25.50', 0],
]);

it('does not prepare charges with a revoked or expired mandate', function (SepaMandateStatus $status): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user, 'charge' => $charge, 'mandate' => $mandate] = createRepeatPreparationCharge();
    $mandate->update(['status' => $status]);

    $run = app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user);

    expect($run->items_count)->toBe(0);
    expect($run->total_amount)->toBe('0.00');
    expect($run->errors_count)->toBe(1);
    $this->assertDatabaseCount('sepa_debit_items', 0);
    $this->assertDatabaseHas('sepa_debit_run_errors', [
        'sepa_debit_run_id' => $run->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'message' => 'Kein aktives SEPA-Mandat.',
    ]);
})->with(['revoked' => SepaMandateStatus::Revoked, 'expired' => SepaMandateStatus::Expired]);

it('copies immutable snapshots and stores bank details encrypted', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user, 'charge' => $charge, 'mandate' => $mandate] = createRepeatPreparationCharge();
    $mandate->update([
        'account_holder' => 'Max Mustermann',
        'mandate_reference' => 'SV-34242',
        'signed_at' => '2026-09-20',
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
    ]);
    expect($charge->member->activeSepaMandate)->toBeInstanceOf(SepaMandate::class);

    $run = app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user);

    $item = $run->items()->sole();
    expect($item->amount)->toBe('25.50');
    expect($item->account_holder)->toBe('Max Mustermann');
    expect($item->mandate_reference)->toBe('SV-34242');
    expect($item->mandate_signed_at->format('Y-m-d'))->toBe('2026-09-20');
    expect($item->purpose)->toBe('Mitgliedsbeitrag 2026');
    expect($item->iban)->toBe('DE89370400440532013000');
    expect($item->bic)->toBe('COBADEFFXXX');
    $raw = DB::table('sepa_debit_items')->where('id', $item->getKey())->first(['iban', 'bic']);
    expect($raw->iban)->toBeString()->not->toContain('DE89370400440532013000');
    expect($raw->bic)->toBeString()->not->toContain('COBADEFFXXX');

    $charge->update(['amount' => '99.99']);
    $mandate->update(['account_holder' => 'Geänderter Name', 'mandate_reference' => 'SV-99999', 'signed_at' => '2026-10-02', 'iban' => 'DE12500105170648489890']);

    $snapshot = $item->fresh();
    expect($snapshot->amount)->toBe('25.50');
    expect($snapshot->account_holder)->toBe('Max Mustermann');
    expect($snapshot->mandate_reference)->toBe('SV-34242');
    expect($snapshot->mandate_signed_at->format('Y-m-d'))->toBe('2026-09-20');
    expect($snapshot->iban)->toBe('DE89370400440532013000');
});

it('never processes charges from another club', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['club' => $club, 'user' => $user, 'charge' => $ownCharge] = createRepeatPreparationCharge();
    ['charge' => $foreignCharge] = createRepeatPreparationCharge('DE98ZZZ09999999998');
    $foreignAttributes = $foreignCharge->fresh()->getRawOriginal();
    app(CurrentClub::class)->set($club);

    $run = app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user);

    expect($run->items_count)->toBe(1);
    expect($run->total_amount)->toBe('25.50');
    expect($run->errors_count)->toBe(0);
    $this->assertDatabaseCount('sepa_debit_items', 1);
    $this->assertDatabaseHas('sepa_debit_items', ['club_id' => $club->getKey(), 'contribution_charge_id' => $ownCharge->getKey()]);
    $this->assertDatabaseMissing('sepa_debit_items', ['contribution_charge_id' => $foreignCharge->getKey()]);
    expect($foreignCharge->fresh()->getRawOriginal())->toBe($foreignAttributes);
});

it('never uses a mandate belonging to another club even if linked to its own member', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['user' => $user, 'charge' => $charge, 'mandate' => $mandate] = createRepeatPreparationCharge();
    $mandate->update(['club_id' => Club::factory()->create()->getKey()]);
    $foreignAttributes = $mandate->fresh()->getRawOriginal();

    $run = app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user);

    expect($run->items_count)->toBe(0);
    expect($run->total_amount)->toBe('0.00');
    expect($run->errors_count)->toBe(1);
    $this->assertDatabaseCount('sepa_debit_items', 0);
    $this->assertDatabaseHas('sepa_debit_run_errors', [
        'sepa_debit_run_id' => $run->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'message' => 'Das SEPA-Mandat gehört nicht zum aktuellen Verein.',
    ]);
    expect($mandate->fresh()->getRawOriginal())->toBe($foreignAttributes);
});

it('counts successful items and errors separately and sums only successfully prepared amounts', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    ['club' => $club, 'user' => $user, 'charge' => $firstCharge] = createRepeatPreparationCharge();
    $firstCharge->update(['amount' => '10.10']);
    $type = ContributionType::query()->where('club_id', $club->getKey())->sole();
    $charges = collect();
    foreach (['20.20', '99.99', '88.88'] as $position => $amount) {
        $member = Member::factory()->create(['club_id' => $club->getKey(), 'member_number' => (string) (100000 + $position)]);
        if ($position === 0) {
            SepaMandate::factory()->create(['club_id' => $club->getKey(), 'member_id' => $member->getKey()]);
        }
        $charges->push(ContributionCharge::query()->create([
            'club_id' => $club->getKey(),
            'member_id' => $member->getKey(),
            'contribution_type_id' => $type->getKey(),
            'status' => ContributionChargeStatus::Open,
            'amount' => $amount,
            'description' => 'Mitgliedsbeitrag 2026',
            'period_from' => '2026-01-01',
            'period_until' => '2026-12-31',
            'due_date' => '2026-10-15',
        ]));
    }

    $run = app(PrepareSepaDebitRun::class)->handle('Oktober', CarbonImmutable::parse('2026-10-15'), $user);

    $this->assertDatabaseHas('sepa_debit_runs', ['id' => $run->getKey(), 'items_count' => 2, 'errors_count' => 2, 'total_amount' => '30.30']);
    $this->assertDatabaseCount('sepa_debit_items', 2);
    $this->assertDatabaseCount('sepa_debit_run_errors', 2);
    expect($run->items()->pluck('contribution_charge_id')->sort()->values()->all())
        ->toBe(collect([$firstCharge->getKey(), $charges[0]->getKey()])->sort()->values()->all());
    expect($run->errors()->pluck('contribution_charge_id')->sort()->values()->all())
        ->toBe(collect([$charges[1]->getKey(), $charges[2]->getKey()])->sort()->values()->all());
});
