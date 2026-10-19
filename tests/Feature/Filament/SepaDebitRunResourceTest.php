<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\CreatePaymentFromSettledSepaItem;
use App\Application\Sepa\PrepareSepaDebitRun;
use App\Application\Sepa\RecordSepaDebitItemEvent;
use App\Application\Sepa\RefreshSepaDebitRunStatus;
use App\Application\Sepa\SubmitSepaDebitRun;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaDebitSubmissionStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Filament\Resources\SepaDebitRuns\Pages\ListSepaDebitRuns;
use App\Filament\Resources\SepaDebitRuns\Pages\ViewSepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\RelationManagers\ErrorsRelationManager;
use App\Filament\Resources\SepaDebitRuns\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function sepaDebitRunTableClub(bool $canCreate = false): Club
{
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsView->value, 'web'));
    if ($canCreate) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsCreate->value, 'web'));
    }
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    return $club;
}

/** @param array<string, mixed> $overrides */
function createTableSepaDebitRun(Club $club, array $overrides = []): SepaDebitRun
{
    $run = SepaDebitRun::query()->create(array_replace([
        'club_id' => $club->getKey(),
        'name' => 'Oktoberbeiträge',
        'collection_date' => '2026-10-15',
        'status' => SepaDebitRunStatus::Prepared,
        'items_count' => 12,
        'errors_count' => 2,
        'total_amount' => '300.50',
    ], $overrides));
    $run->forceFill(['created_at' => $overrides['created_at'] ?? '2026-10-05 14:30:00'])->save();

    return $run;
}

it('shows debit runs with formatted dates, status, counts and euro amounts', function (SepaDebitRunStatus $status, string $label): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club, ['status' => $status]);

    Livewire::test(ListSepaDebitRuns::class)
        ->assertCanSeeTableRecords([$run])
        ->assertTableColumnStateSet('name', 'Oktoberbeiträge', $run)
        ->assertTableColumnFormattedStateSet('collection_date', '15.10.2026', $run)
        ->assertTableColumnFormattedStateSet('status', $label, $run)
        ->assertTableColumnStateSet('items_count', 12, $run)
        ->assertTableColumnStateSet('errors_count', 2, $run)
        ->assertTableColumnFormattedStateSet('total_amount', "300,50\u{00A0}€", $run)
        ->assertTableColumnFormattedStateSet('created_at', '05.10.2026 14:30', $run)
        ->assertSee('Bezeichnung')
        ->assertSee('Einzug')
        ->assertSee('Status')
        ->assertSee('Positionen')
        ->assertSee('Fehler')
        ->assertSee('Summe')
        ->assertSee('Erstellt')
        ->assertSee($label);
})->with([
    'draft' => [SepaDebitRunStatus::Draft, 'Entwurf'],
    'prepared' => [SepaDebitRunStatus::Prepared, 'Vorbereitet'],
    'exported' => [SepaDebitRunStatus::Exported, 'Exportiert'],
    'cancelled' => [SepaDebitRunStatus::Cancelled, 'Storniert'],
]);

it('searches debit runs by name', function (): void {
    $club = sepaDebitRunTableClub();
    $matching = createTableSepaDebitRun($club);
    $other = createTableSepaDebitRun($club, ['name' => 'Novemberbeiträge']);

    Livewire::test(ListSepaDebitRuns::class)
        ->searchTable('Oktober')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);
});

it('sorts debit runs by date in both directions', function (string $column): void {
    $club = sepaDebitRunTableClub();
    $earlier = createTableSepaDebitRun($club);
    $later = createTableSepaDebitRun($club, [
        'collection_date' => '2026-11-15',
        'created_at' => '2026-11-05 14:30:00',
    ]);

    Livewire::test(ListSepaDebitRuns::class)
        ->sortTable($column, 'asc')
        ->assertCanSeeTableRecords([$earlier, $later], inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords([$later, $earlier], inOrder: true);
})->with(['collection_date', 'created_at']);

it('does not show debit runs from another club', function (): void {
    createTableSepaDebitRun(Club::factory()->create(), ['name' => 'Fremder SEPA-Lauf']);
    $club = sepaDebitRunTableClub();
    createTableSepaDebitRun($club, ['name' => 'Eigener SEPA-Lauf']);

    $this->get(SepaDebitRunResource::getUrl('index', ['tenant' => $club], panel: 'admin'))
        ->assertOk()
        ->assertSee('Eigener SEPA-Lauf')
        ->assertDontSee('Fremder SEPA-Lauf');
});

function createDebitRunActionConfiguration(Club $club): ClubSepaConfiguration
{
    return ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Testverein e.V.',
        'iban' => 'DE89370400440532013000',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);
}

it('prepares a debit run through the confirmed action', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    $club = sepaDebitRunTableClub(canCreate: true);
    createDebitRunActionConfiguration($club);

    $component = Livewire::test(ListSepaDebitRuns::class)
        ->assertActionVisible('prepareSepaDebitRun')
        ->assertActionHasLabel('prepareSepaDebitRun', 'Lastschriftlauf vorbereiten')
        ->assertActionHasIcon('prepareSepaDebitRun', 'heroicon-o-banknotes')
        ->mountAction('prepareSepaDebitRun')
        ->assertActionMounted('prepareSepaDebitRun');

    $this->assertDatabaseCount('sepa_debit_runs', 0);

    $component
        ->setActionData([
            'name' => 'Oktoberbeiträge',
            'collection_date' => '2026-10-10',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified(Notification::make()
            ->title('SEPA-Lastschriftlauf vorbereitet')
            ->body('0 Positionen, 0.00 €, 0 Fehler.')
            ->success());

    $this->assertDatabaseCount('sepa_debit_runs', 1);
    $this->assertDatabaseHas('sepa_debit_runs', [
        'club_id' => $club->getKey(),
        'name' => 'Oktoberbeiträge',
        'collection_date' => '2026-10-10 00:00:00',
        'status' => SepaDebitRunStatus::Prepared->value,
        'created_by' => auth()->id(),
        'items_count' => 0,
        'errors_count' => 0,
        'total_amount' => '0.00',
        'prepared_at' => '2026-10-05 12:00:00',
    ]);
});

it('prevents users without create permission from preparing a debit run', function (): void {
    sepaDebitRunTableClub();

    $component = Livewire::test(ListSepaDebitRuns::class)
        ->assertActionHidden('prepareSepaDebitRun')
        ->call('mountAction', 'prepareSepaDebitRun')
        ->call('callMountedAction');

    $callback = $component->instance()->getAction('prepareSepaDebitRun')->getActionFunction();
    expect(fn () => $callback([], app(PrepareSepaDebitRun::class)))
        ->toThrow(function (HttpException $exception): void {
            expect($exception->getStatusCode())->toBe(403);
        });

    $this->assertDatabaseCount('sepa_debit_runs', 0);
});

it('rejects invalid preparation form data without creating a run', function (array $data, array $errors): void {
    sepaDebitRunTableClub(canCreate: true);

    Livewire::test(ListSepaDebitRuns::class)
        ->callAction('prepareSepaDebitRun', data: $data)
        ->assertHasActionErrors($errors);

    $this->assertDatabaseCount('sepa_debit_runs', 0);
})->with([
    'required fields' => [[], ['name' => 'required', 'collection_date' => 'required']],
    'name too long' => [['name' => str_repeat('A', 256), 'collection_date' => '2026-10-10'], ['name' => 'max']],
    'invalid date' => [['name' => 'Oktoberbeiträge', 'collection_date' => 'invalid'], ['collection_date' => 'date']],
]);

it('shows an error without creating a run when no active configuration exists', function (): void {
    sepaDebitRunTableClub(canCreate: true);

    Livewire::test(ListSepaDebitRuns::class)
        ->callAction('prepareSepaDebitRun', data: [
            'name' => 'Oktoberbeiträge',
            'collection_date' => '2026-10-10',
        ])
        ->assertNotified(Notification::make()
            ->title('Lastschriftlauf konnte nicht vorbereitet werden')
            ->body('Für diesen Verein ist keine aktive SEPA-Konfiguration vorhanden.')
            ->danger());

    $this->assertDatabaseCount('sepa_debit_runs', 0);
});

it('shows an error without creating a run when the collection date is too early', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    $club = sepaDebitRunTableClub(canCreate: true);
    createDebitRunActionConfiguration($club);

    Livewire::test(ListSepaDebitRuns::class)
        ->callAction('prepareSepaDebitRun', data: [
            'name' => 'Oktoberbeiträge',
            'collection_date' => '2026-10-09',
        ])
        ->assertNotified(Notification::make()
            ->title('Lastschriftlauf konnte nicht vorbereitet werden')
            ->body('Das Einzugsdatum liegt vor dem konfigurierten Mindestvorlauf.')
            ->danger());

    $this->assertDatabaseCount('sepa_debit_runs', 0);
});

it('shows the XML action matching the debit run status for users with export permission', function (
    SepaDebitRunStatus $status,
    string $visibleAction,
    string $hiddenAction,
): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsExport->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => $status]);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertActionVisible($visibleAction)
        ->assertActionHidden($hiddenAction);
})->with([
    'prepared' => [SepaDebitRunStatus::Prepared, 'exportXml', 'downloadXml'],
    'exported' => [SepaDebitRunStatus::Exported, 'downloadXml', 'exportXml'],
]);

it('marks an exported run as submitted through the confirmed action', function (?string $bankReference): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-07 12:00:00'));
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsSubmit->value, 'web'));
    $run = createTableSepaDebitRun($club, [
        'status' => SepaDebitRunStatus::Exported,
        'xml_storage_path' => 'sepa/test.xml.enc',
        'xml_sha256' => str_repeat('a', 64),
    ]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $component = Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertActionVisible('markSubmitted')
        ->assertActionHasLabel('markSubmitted', 'Als eingereicht markieren')
        ->mountAction('markSubmitted')
        ->assertActionMounted('markSubmitted');

    $this->assertDatabaseCount('sepa_debit_submissions', 0);

    $component->setActionData([
        'submission_method' => 'bank_portal',
        'submitted_at' => '2026-10-07 10:30:00',
        'bank_reference' => $bankReference,
    ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified(Notification::make()
            ->title('Lastschriftlauf als eingereicht markiert')
            ->success())
        ->assertActionHidden('markSubmitted');

    $this->assertDatabaseHas('sepa_debit_runs', [
        'id' => $run->getKey(),
        'status' => SepaDebitRunStatus::Submitted->value,
    ]);
    $this->assertDatabaseHas('sepa_debit_submissions', [
        'sepa_debit_run_id' => $run->getKey(),
        'submission_method' => 'bank_portal',
        'submitted_at' => '2026-10-07 10:30:00',
        'bank_reference' => $bankReference,
        'submitted_by' => auth()->id(),
    ]);
    $this->assertDatabaseHas('sepa_debit_items', [
        'id' => $item->getKey(),
        'status' => SepaDebitItemStatus::Submitted->value,
    ]);
    $this->assertDatabaseHas('sepa_debit_item_events', [
        'sepa_debit_item_id' => $item->getKey(),
        'type' => 'submitted',
        'recorded_by' => auth()->id(),
    ]);
})->with(['with bank reference' => ['BANK-123'], 'without bank reference' => [null]]);

it('hides the submission action for runs that have not been exported or are already submitted', function (SepaDebitRunStatus $status): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsSubmit->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => $status]);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertActionHidden('markSubmitted');
})->with([SepaDebitRunStatus::Prepared, SepaDebitRunStatus::Submitted, SepaDebitRunStatus::Cancelled]);

it('rejects invalid submission form data without submitting the run', function (array $data, array $errors): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsSubmit->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Exported]);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->callAction('markSubmitted', data: $data)
        ->assertHasActionErrors($errors);

    $this->assertDatabaseCount('sepa_debit_submissions', 0);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Exported);
})->with([
    'required fields' => [['submission_method' => null, 'submitted_at' => null], ['submission_method' => 'required', 'submitted_at' => 'required']],
    'invalid method' => [['submission_method' => 'invalid', 'submitted_at' => '2026-10-07 12:00:00'], ['submission_method']],
    'invalid date' => [['submission_method' => 'bank_portal', 'submitted_at' => 'invalid'], ['submitted_at' => 'date']],
    'reference too long' => [['submission_method' => 'bank_portal', 'submitted_at' => '2026-10-07 12:00:00', 'bank_reference' => str_repeat('A', 256)], ['bank_reference' => 'max']],
]);

it('prevents users with only export permission from submitting a run', function (): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsExport->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Exported]);
    $component = Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertActionHidden('markSubmitted');
    $callback = $component->instance()->getAction('markSubmitted')->getActionFunction();

    expect(fn () => $callback([], app(SubmitSepaDebitRun::class)))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('sepa_debit_submissions', 0);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
});

it('authorizes submission only for submit users in the current club', function (): void {
    $foreignRun = createTableSepaDebitRun(Club::factory()->create());
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club);

    expect(Gate::allows('submit', $run))->toBeFalse();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsExport->value, 'web'));
    expect(Gate::allows('submit', $run))->toBeFalse();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsSubmit->value, 'web'));
    expect(Gate::allows('submit', $run))->toBeTrue();
    expect(Gate::allows('submit', $foreignRun))->toBeFalse();
});

function createViewSepaDebitItem(
    SepaDebitRun $run,
    string $firstName,
    string $lastName,
    string $amount,
    string $reference,
): SepaDebitItem {
    $member = Member::factory()->create([
        'club_id' => $run->club_id,
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);
    $mandate = SepaMandate::factory()->create([
        'club_id' => $run->club_id,
        'member_id' => $member->getKey(),
        'mandate_reference' => $reference,
    ]);
    $type = ContributionType::query()->firstOrCreate([
        'club_id' => $run->club_id,
        'name' => 'Jahresbeitrag',
    ], [
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $run->club_id,
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionChargeStatus::Open,
        'amount' => $amount,
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-15',
    ]);

    return $run->items()->create([
        'club_id' => $run->club_id,
        'contribution_charge_id' => $charge->getKey(),
        'member_id' => $member->getKey(),
        'sepa_mandate_id' => $mandate->getKey(),
        'amount' => $amount,
        'purpose' => 'Jahresbeitrag 2026',
        'account_holder' => $member->full_name,
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
        'mandate_reference' => $reference,
        'mandate_signed_at' => '2026-10-01',
    ]);
}

it('records bank acceptance and refreshes the run status through the position action', function (bool $hasPendingItem, ?string $bankReference, SepaDebitRunStatus $expectedStatus): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-07 12:00:00'));
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Submitted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Submitted]);
    if ($hasPendingItem) {
        $pendingItem = createViewSepaDebitItem($run, 'Anna', 'Beispiel', '25.00', 'SV-002');
        $pendingItem->update(['status' => SepaDebitItemStatus::Submitted]);
    }

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionVisible(TestAction::make('accept')->table($item))
        ->callAction(TestAction::make('accept')->table($item), data: [
            'occurred_at' => '2026-10-07 10:30:00',
            'bank_reference' => $bankReference,
        ])
        ->assertHasNoActionErrors()
        ->assertActionHidden(TestAction::make('accept')->table($item));

    $this->assertDatabaseHas('sepa_debit_items', [
        'id' => $item->getKey(),
        'status' => SepaDebitItemStatus::Accepted->value,
    ]);
    $this->assertDatabaseHas('sepa_debit_runs', [
        'id' => $run->getKey(),
        'status' => $expectedStatus->value,
    ]);
    $this->assertDatabaseCount('sepa_debit_item_events', 1);
    $this->assertDatabaseHas('sepa_debit_item_events', [
        'club_id' => $club->getKey(),
        'sepa_debit_item_id' => $item->getKey(),
        'type' => 'accepted',
        'occurred_at' => '2026-10-07 10:30:00',
        'reason_code' => null,
        'reason_text' => null,
        'bank_reference' => $bankReference,
        'source' => 'manual',
        'recorded_by' => auth()->id(),
    ]);
})->with([
    'all items accepted without reference' => [false, null, SepaDebitRunStatus::Accepted],
    'remaining submitted item with reference' => [true, 'BANK-123', SepaDebitRunStatus::Submitted],
]);

it('hides bank acceptance for positions that are not submitted', function (SepaDebitItemStatus $status): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => $status]);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('accept')->table($item));
})->with([SepaDebitItemStatus::Prepared, SepaDebitItemStatus::Accepted]);

it('prevents users without feedback permission from recording bank acceptance', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Submitted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Submitted]);
    $component = Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('accept')->table($item));
    $callback = $component->instance()->getTable()->getAction('accept')->getActionFunction();

    expect(fn () => $callback([], $item, app(RecordSepaDebitItemEvent::class), app(RefreshSepaDebitRunStatus::class)))
        ->toThrow(function (HttpException $exception): void {
            expect($exception->getStatusCode())->toBe(403);
        });

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Submitted);
});

it('rejects invalid bank acceptance form data without recording feedback', function (array $data, array $errors): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Submitted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Submitted]);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->callAction(TestAction::make('accept')->table($item), data: $data)
        ->assertHasActionErrors($errors);

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Submitted);
})->with([
    'missing time' => [['occurred_at' => null], ['occurred_at' => 'required']],
    'invalid time' => [['occurred_at' => 'invalid'], ['occurred_at' => 'date']],
    'reference too long' => [['occurred_at' => '2026-10-07 10:30:00', 'bank_reference' => str_repeat('A', 256)], ['bank_reference' => 'max']],
]);

it('records bank rejection while leaving the contribution charge open', function (SepaDebitItemStatus $status, ?string $reasonCode): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Submitted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => $status]);
    $chargeBefore = $item->charge->getAttributes();

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionVisible(TestAction::make('reject')->table($item))
        ->callAction(TestAction::make('reject')->table($item), data: [
            'occurred_at' => '2026-10-07 10:30:00',
            'bank_reference' => 'BANK-123',
            'reason_code' => $reasonCode,
            'reason_text' => 'Konto gesperrt',
        ])
        ->assertHasNoActionErrors()
        ->assertActionHidden(TestAction::make('reject')->table($item));

    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Rejected);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Rejected);
    expect($item->charge->fresh()->status)->toBe(ContributionChargeStatus::Open);
    expect($item->charge->fresh()->getAttributes())->toBe($chargeBefore);
    $this->assertDatabaseCount('sepa_debit_item_events', 1);
    $this->assertDatabaseHas('sepa_debit_item_events', [
        'club_id' => $club->getKey(),
        'sepa_debit_item_id' => $item->getKey(),
        'type' => 'rejected',
        'occurred_at' => '2026-10-07 10:30:00',
        'reason_code' => $reasonCode,
        'reason_text' => 'Konto gesperrt',
        'bank_reference' => 'BANK-123',
        'source' => 'manual',
        'recorded_by' => auth()->id(),
    ]);
})->with([
    'submitted with code' => [SepaDebitItemStatus::Submitted, 'AC06'],
    'accepted without code' => [SepaDebitItemStatus::Accepted, null],
]);

it('rejects invalid bank rejection form data without changing the position or charge', function (array $data, array $errors): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Submitted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Submitted]);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->callAction(TestAction::make('reject')->table($item), data: array_replace([
            'occurred_at' => '2026-10-07 10:30:00',
            'reason_text' => 'Konto gesperrt',
        ], $data))
        ->assertHasActionErrors($errors);

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Submitted);
    expect($item->charge->status)->toBe(ContributionChargeStatus::Open);
})->with([
    'missing reason' => [['reason_text' => null], ['reason_text' => 'required']],
    'code too long' => [['reason_code' => str_repeat('A', 21)], ['reason_code' => 'max']],
    'reason too long' => [['reason_text' => str_repeat('A', 501)], ['reason_text' => 'max']],
    'missing time' => [['occurred_at' => null], ['occurred_at' => 'required']],
    'invalid time' => [['occurred_at' => 'invalid'], ['occurred_at' => 'date']],
    'reference too long' => [['bank_reference' => str_repeat('A', 256)], ['bank_reference' => 'max']],
]);

it('hides bank rejection for positions outside submitted and accepted statuses', function (SepaDebitItemStatus $status): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => $status]);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('reject')->table($item));
})->with([
    SepaDebitItemStatus::Prepared,
    SepaDebitItemStatus::Rejected,
    SepaDebitItemStatus::Settled,
    SepaDebitItemStatus::Returned,
    SepaDebitItemStatus::Refunded,
    SepaDebitItemStatus::Cancelled,
]);

it('prevents unauthorized users from recording bank rejection', function (string $missingPermission): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    auth()->user()->revokePermissionTo($missingPermission);
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Submitted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Submitted]);

    $component = Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('reject')->table($item));
    $callback = $component->instance()->getTable()->getAction('reject')->getActionFunction();

    expect(fn () => $callback([], $item, app(RecordSepaDebitItemEvent::class), app(RefreshSepaDebitRunStatus::class)))
        ->toThrow($missingPermission === Permission::SepaDebitItemsFeedbackManage->value
            ? HttpException::class
            : AuthorizationException::class);

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Submitted);
    expect($item->charge->status)->toBe(ContributionChargeStatus::Open);
})->with([
    'feedback permission' => Permission::SepaDebitItemsFeedbackManage->value,
    'view permission' => Permission::SepaDebitRunsView->value,
]);

it('records a returned debit and reverses its payment while reopening the charge', function (?string $reasonCode, ?string $bankReference): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club, ['status' => SepaDebitRunStatus::Accepted]);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Settled]);
    $payment = app(CreatePaymentFromSettledSepaItem::class)->handle($item, now()->toImmutable(), auth()->user());

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionVisible(TestAction::make('return')->table($item))
        ->callAction(TestAction::make('return')->table($item), data: [
            'occurred_at' => '2026-10-20 10:30:00',
            'reason_code' => $reasonCode,
            'reason_text' => 'Keine ausreichende Deckung',
            'bank_reference' => $bankReference,
        ])
        ->assertHasNoActionErrors()
        ->assertActionHidden(TestAction::make('return')->table($item));

    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Returned);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Reversed);
    expect($item->charge->fresh()->status)->toBe(ContributionChargeStatus::Open);
    expect($item->charge->fresh()->paid_at)->toBeNull();
    $this->assertDatabaseCount('sepa_debit_item_events', 1);
    $this->assertDatabaseHas('sepa_debit_item_events', [
        'club_id' => $club->getKey(),
        'sepa_debit_item_id' => $item->getKey(),
        'type' => 'returned',
        'occurred_at' => '2026-10-20 10:30:00',
        'reason_code' => $reasonCode,
        'reason_text' => 'Keine ausreichende Deckung',
        'bank_reference' => $bankReference,
        'source' => 'manual',
        'recorded_by' => auth()->id(),
    ]);
})->with([
    'with bank details' => ['AM04', 'BANK-123'],
    'without optional bank details' => [null, null],
]);

it('rejects invalid returned debit form data without recording an event', function (array $data, array $errors): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Settled]);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->callAction(TestAction::make('return')->table($item), data: array_replace([
            'occurred_at' => '2026-10-20 10:30:00',
            'reason_text' => 'Keine ausreichende Deckung',
        ], $data))
        ->assertHasActionErrors($errors);

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Settled);
})->with([
    'required fields' => [['occurred_at' => null, 'reason_text' => null], ['occurred_at' => 'required', 'reason_text' => 'required']],
    'invalid date' => [['occurred_at' => 'invalid'], ['occurred_at' => 'date']],
    'code too long' => [['reason_code' => str_repeat('A', 21)], ['reason_code' => 'max']],
    'reason too long' => [['reason_text' => str_repeat('A', 501)], ['reason_text' => 'max']],
    'reference too long' => [['bank_reference' => str_repeat('A', 256)], ['bank_reference' => 'max']],
]);

it('prevents returned debit events for positions that are not settled', function (SepaDebitItemStatus $status): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => $status]);

    $component = Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('return')->table($item));
    $callback = $component->instance()->getTable()->getAction('return')->getActionFunction();

    expect(fn () => $callback([
        'occurred_at' => '2026-10-20 10:30:00',
        'reason_text' => 'Keine ausreichende Deckung',
    ], $item, app(RecordSepaDebitItemEvent::class), app(RefreshSepaDebitRunStatus::class)))
        ->toThrow(DomainException::class);

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe($status);
})->with([
    SepaDebitItemStatus::Prepared,
    SepaDebitItemStatus::Submitted,
    SepaDebitItemStatus::Accepted,
    SepaDebitItemStatus::Rejected,
    SepaDebitItemStatus::Returned,
    SepaDebitItemStatus::Refunded,
    SepaDebitItemStatus::Cancelled,
]);

it('prevents unauthorized users from recording a returned debit', function (string $missingPermission): void {
    $club = sepaDebitRunTableClub();
    auth()->user()->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitItemsFeedbackManage->value, 'web'));
    auth()->user()->revokePermissionTo($missingPermission);
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '25.00', 'SV-001');
    $item->update(['status' => SepaDebitItemStatus::Settled]);

    $component = Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('return')->table($item));
    $callback = $component->instance()->getTable()->getAction('return')->getActionFunction();

    expect(fn () => $callback([], $item, app(RecordSepaDebitItemEvent::class), app(RefreshSepaDebitRunStatus::class)))
        ->toThrow($missingPermission === Permission::SepaDebitItemsFeedbackManage->value
            ? HttpException::class
            : AuthorizationException::class);

    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Settled);
})->with([
    'feedback permission' => Permission::SepaDebitItemsFeedbackManage->value,
    'view permission' => Permission::SepaDebitRunsView->value,
]);

it('shows bank submission details in the run infolist', function (SepaDebitSubmissionStatus $status, SepaSubmissionMethod $method, string $statusLabel, string $methodLabel, ?string $bankReference): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club);
    $run->submission()->create([
        'club_id' => $club->getKey(),
        'status' => $status,
        'submission_method' => $method,
        'submitted_at' => '2026-10-07 10:30:00',
        'bank_reference' => $bankReference,
    ]);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertSee('Bankeinreichung')
        ->assertSee('Einreichungsweg')
        ->assertSee($statusLabel)
        ->assertSee($methodLabel)
        ->assertSee('07.10.2026 10:30')
        ->assertSee('Bankreferenz')
        ->assertSee($bankReference ?? '—')
        ->assertDontSee('Noch nicht eingereicht');
})->with([
    'submitted via portal' => [SepaDebitSubmissionStatus::Submitted, SepaSubmissionMethod::BankPortal, 'Eingereicht', 'Bankportal', 'BANK-123'],
    'accepted via online banking' => [SepaDebitSubmissionStatus::Accepted, SepaSubmissionMethod::OnlineBanking, 'Angenommen', 'Onlinebanking', null],
    'partially accepted via EBICS' => [SepaDebitSubmissionStatus::PartiallyAccepted, SepaSubmissionMethod::Ebics, 'Teilweise angenommen', 'EBICS', 'EBICS-123'],
    'rejected via API' => [SepaDebitSubmissionStatus::Rejected, SepaSubmissionMethod::Api, 'Abgelehnt', 'Bank-API', 'API-123'],
    'other submission method' => [SepaDebitSubmissionStatus::Submitted, SepaSubmissionMethod::Other, 'Eingereicht', 'Sonstige', 'OTHER-123'],
]);

it('shows placeholders in the bank submission section when the run has not been submitted', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertSee('Bankeinreichung')
        ->assertSee('Noch nicht eingereicht')
        ->assertSee('Einreichungsweg')
        ->assertSee('Bankreferenz')
        ->assertSee('—');
});

it('shows the run name and summary with German date and currency formatting', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club, [
        'name' => 'SEPA-Lastschrift Oktober 2026',
        'items_count' => 126,
        'errors_count' => 3,
        'total_amount' => '14320.00',
    ]);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertSee('SEPA-Lastschrift Oktober 2026')
        ->assertSee('Vorbereitet')
        ->assertSee('Einzugsdatum')
        ->assertSee('15.10.2026')
        ->assertSee('Positionen')
        ->assertSee('126')
        ->assertSee('Gesamtsumme')
        ->assertSee("14.320,00\u{00A0}€")
        ->assertSee('Fehler')
        ->assertSee('3');
});

it('shows only the run positions with member names and without full bank details', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club, ['items_count' => 2, 'errors_count' => 0, 'total_amount' => '180.00']);
    $max = createViewSepaDebitItem($run, 'Max', 'Mustermann', '120.00', 'SV-34242');
    $anna = createViewSepaDebitItem($run, 'Anna', 'Beispiel', '60.00', 'SV-34243');
    $otherRun = createTableSepaDebitRun($club, ['name' => 'Weiterer Lauf']);
    $otherItem = createViewSepaDebitItem($otherRun, 'Anderes', 'Mitglied', '30.00', 'SV-34244');

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->assertSeeLivewire(ItemsRelationManager::class)
        ->assertDontSee('DE89370400440532013000');

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertCanSeeTableRecords([$max, $anna])
        ->assertCanNotSeeTableRecords([$otherItem])
        ->assertTableColumnFormattedStateSet('amount', "120,00\u{00A0}€", $max)
        ->assertTableColumnFormattedStateSet('amount', "60,00\u{00A0}€", $anna)
        ->assertTableColumnDoesNotExist('iban')
        ->assertTableColumnDoesNotExist('bic')
        ->assertSee('Max Mustermann')
        ->assertSee('Anna Beispiel')
        ->assertSee('SV-34242')
        ->assertSee('SV-34243')
        ->assertSee('Jahresbeitrag 2026')
        ->assertDontSee('DE89370400440532013000')
        ->assertDontSee('COBADEFFXXX');
});

it('shows a masked IBAN in the position details without exposing bank data in Livewire state', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '120.00', 'SV-34242');

    $component = Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->mountAction(TestAction::make('view')->table($item))
        ->assertActionMounted(TestAction::make('view')->table($item))
        ->assertSee('Max Mustermann')
        ->assertDontSee('DE89370400440532013000')
        ->assertDontSee('COBADEFFXXX');

    expect($component->instance()->mountedActionShouldOpenModal())->toBeTrue();
    expect($component->instance()->getSchema('mountedActionSchema0')->toHtml())
        ->toContain('IBAN')
        ->toContain('•••• •••• •••• 3000')
        ->not->toContain('DE89370400440532013000');

    $ibanEntry = collect($component->instance()->getSchema('mountedActionSchema0')->getComponents())
        ->first(static fn (TextEntry $entry): bool => $entry->getName() === 'iban');
    expect($ibanEntry->formatState(null))->toBe('—');

    expect(json_encode($component->get('mountedActions'), JSON_THROW_ON_ERROR))
        ->not->toContain('DE89370400440532013000')
        ->not->toContain('COBADEFFXXX');
    expect($item->fresh()->iban)->toBe('DE89370400440532013000');
});

it('prevents users without view permission from opening masked position details', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club);
    $item = createViewSepaDebitItem($run, 'Max', 'Mustermann', '120.00', 'SV-34242');
    auth()->user()->revokePermissionTo(Permission::SepaDebitRunsView->value);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertActionHidden(TestAction::make('view')->table($item))
        ->mountAction(TestAction::make('view')->table($item))
        ->assertForbidden()
        ->assertDontSee('•••• •••• •••• 3000')
        ->assertDontSee('DE89370400440532013000');
});

it('returns 403 when a user without view permission requests the detail page', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club);
    auth()->user()->revokePermissionTo(Permission::SepaDebitRunsView->value);

    $this->get(SepaDebitRunResource::getUrl('view', ['tenant' => $club, 'record' => $run->getKey()], panel: 'admin'))
        ->assertForbidden();
});

it('returns 404 when a user requests the detail page of another club', function (): void {
    $foreignRun = createTableSepaDebitRun(Club::factory()->create());
    $club = sepaDebitRunTableClub();

    $this->get(SepaDebitRunResource::getUrl('view', ['tenant' => $club, 'record' => $foreignRun->getKey()], panel: 'admin'))
        ->assertNotFound();
});

it('shows debit run errors separately with member numbers and messages', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club, ['errors_count' => 2]);
    $member4711 = Member::factory()->create(['club_id' => $club->getKey(), 'member_number' => '4711']);
    $member4715 = Member::factory()->create(['club_id' => $club->getKey(), 'member_number' => '4715']);
    $missingMandate = $run->errors()->create([
        'member_id' => $member4711->getKey(),
        'message' => 'Kein aktives SEPA-Mandat.',
    ]);
    $futureMandate = $run->errors()->create([
        'member_id' => $member4715->getKey(),
        'message' => 'SEPA-Mandat am Einzugstag noch nicht gültig.',
    ]);
    $otherRun = createTableSepaDebitRun($club, ['name' => 'Weiterer Lauf']);
    $otherError = $otherRun->errors()->create(['message' => 'Fehler eines anderen Laufs.']);

    Livewire::test(ViewSepaDebitRun::class, ['record' => $run->getKey()])
        ->set('activeRelationManager', '1')
        ->assertSee('Mitglied 4711')
        ->assertSee('Kein aktives SEPA-Mandat.')
        ->assertSee('Mitglied 4715')
        ->assertSee('SEPA-Mandat am Einzugstag noch nicht gültig.');

    Livewire::test(ErrorsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertCanSeeTableRecords([$missingMandate, $futureMandate])
        ->assertCanNotSeeTableRecords([$otherError])
        ->assertSee('Fehler')
        ->assertSee('Mitglied 4711')
        ->assertSee('Kein aktives SEPA-Mandat.')
        ->assertSee('Mitglied 4715')
        ->assertSee('SEPA-Mandat am Einzugstag noch nicht gültig.')
        ->assertDontSee('Fehler eines anderen Laufs.');
});

it('shows errors whose member is no longer available', function (): void {
    $club = sepaDebitRunTableClub();
    $run = createTableSepaDebitRun($club, ['errors_count' => 1]);
    $error = $run->errors()->create([
        'member_id' => null,
        'message' => 'Kein aktives SEPA-Mandat.',
    ]);

    Livewire::test(ErrorsRelationManager::class, ['ownerRecord' => $run, 'pageClass' => ViewSepaDebitRun::class])
        ->assertCanSeeTableRecords([$error])
        ->assertSee('—')
        ->assertSee('Kein aktives SEPA-Mandat.');
});

it('hides the error list when the user lacks view permission or the run belongs to another club', function (): void {
    $foreignRun = createTableSepaDebitRun(Club::factory()->create());
    $club = sepaDebitRunTableClub();
    $ownRun = createTableSepaDebitRun($club);

    expect(ErrorsRelationManager::canViewForRecord($ownRun, ViewSepaDebitRun::class))->toBeTrue();
    expect(ErrorsRelationManager::canViewForRecord($foreignRun, ViewSepaDebitRun::class))->toBeFalse();

    auth()->user()->revokePermissionTo(Permission::SepaDebitRunsView->value);

    expect(ErrorsRelationManager::canViewForRecord($ownRun, ViewSepaDebitRun::class))->toBeFalse();
});
