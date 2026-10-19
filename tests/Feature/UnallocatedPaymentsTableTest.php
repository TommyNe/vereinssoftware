<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Livewire\UnallocatedPaymentsTable;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/** @return array{payment: Payment, charge: ContributionCharge} */
function unallocatedPaymentFixture(bool $canManage = true): array
{
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    if ($canManage) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::PaymentsManage->value, 'web'));
    }
    test()->actingAs($user);
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    $payment = Payment::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => 'booked',
        'method' => 'bank_transfer',
        'amount' => '200.00',
        'booking_date' => '2026-10-08',
    ]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '120.00',
        'description' => 'Jahresbeitrag',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-08',
    ]);

    return compact('payment', 'charge');
}

test('the table allocates a payment and shows a success notification', function (): void {
    $fixture = unallocatedPaymentFixture();

    Livewire::test(UnallocatedPaymentsTable::class)
        ->callAction(TestAction::make('allocate')->table($fixture['payment']), data: [
            'contribution_charge_id' => $fixture['charge']->getKey(),
            'amount' => '50.00',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Zahlung zugeordnet')
        ->assertTableColumnStateSet('unallocated', '150.00', $fixture['payment']);

    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $fixture['payment']->getKey(),
        'contribution_charge_id' => $fixture['charge']->getKey(),
        'amount' => '50.00',
    ]);
});

test('the charge selection excludes other members clubs and non open charges', function (): void {
    $fixture = unallocatedPaymentFixture();
    foreach (['cancelled', 'paid'] as $status) {
        $fixture['charge']->replicate()->fill(['status' => $status])->save();
    }
    $otherMember = Member::factory()->create(['club_id' => $fixture['payment']->club_id]);
    $fixture['charge']->replicate()->fill(['member_id' => $otherMember->getKey()])->save();
    $otherClub = Club::factory()->create();
    $foreignMember = Member::factory()->create(['club_id' => $otherClub->getKey()]);
    $fixture['charge']->replicate()->fill([
        'club_id' => $otherClub->getKey(),
        'member_id' => $foreignMember->getKey(),
    ])->save();

    Livewire::test(UnallocatedPaymentsTable::class)
        ->mountAction(TestAction::make('allocate')->table($fixture['payment']))
        ->assertSchemaComponentExists('contribution_charge_id', checkComponentUsing: static fn (Select $component): bool => $component->getOptions() === [
            $fixture['charge']->getKey() => 'Jahresbeitrag – 120.00 € – fällig 08.10.2026',
        ]);
});

test('invalid allocation input does not store an allocation', function (array $data, array $errors): void {
    $fixture = unallocatedPaymentFixture();

    Livewire::test(UnallocatedPaymentsTable::class)
        ->callAction(TestAction::make('allocate')->table($fixture['payment']), data: $data + [
            'contribution_charge_id' => $fixture['charge']->getKey(),
            'amount' => '50.00',
        ])
        ->assertHasActionErrors($errors)
        ->assertNotNotified();

    $this->assertDatabaseCount('payment_allocations', 0);
})->with([
    'required fields' => [['contribution_charge_id' => null, 'amount' => null], ['contribution_charge_id' => 'required', 'amount' => 'required']],
    'zero amount' => [['amount' => '0.00'], ['amount' => 'min']],
    'non numeric amount' => [['amount' => 'invalid'], ['amount' => 'numeric']],
    'unknown charge' => [['contribution_charge_id' => 'unknown'], ['contribution_charge_id']],
]);

test('the action requires payment management permission', function (): void {
    $fixture = unallocatedPaymentFixture(canManage: false);

    Livewire::test(UnallocatedPaymentsTable::class)
        ->assertActionHidden(TestAction::make('allocate')->table($fixture['payment']));

    $this->assertDatabaseCount('payment_allocations', 0);
});

test('a forged charge selection cannot allocate to another member or club', function (bool $differentClub): void {
    $fixture = unallocatedPaymentFixture();
    $clubId = $differentClub ? Club::factory()->create()->getKey() : $fixture['payment']->club_id;
    $member = Member::factory()->create(['club_id' => $clubId]);
    $charge = $fixture['charge']->replicate()->fill([
        'club_id' => $clubId,
        'member_id' => $member->getKey(),
    ]);
    $charge->save();

    Livewire::test(UnallocatedPaymentsTable::class)
        ->callAction(TestAction::make('allocate')->table($fixture['payment']), data: [
            'contribution_charge_id' => $charge->getKey(),
            'amount' => '50.00',
        ])
        ->assertHasActionErrors(['contribution_charge_id'])
        ->assertNotNotified();

    $this->assertDatabaseCount('payment_allocations', 0);
})->with(['other member' => false, 'other club' => true]);

test('the table only shows booked payments from the current club', function (): void {
    $fixture = unallocatedPaymentFixture();
    $reversed = $fixture['payment']->replicate()->fill(['status' => 'reversed']);
    $reversed->save();
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $foreign = $fixture['payment']->replicate()->fill([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
    ]);
    $foreign->save();

    Livewire::test(UnallocatedPaymentsTable::class)
        ->assertCanSeeTableRecords([$fixture['payment']])
        ->assertCanNotSeeTableRecords([$reversed, $foreign]);
});

test('the payment manager rejects allocations exceeding the remaining balances', function (string $amount, string $message): void {
    $fixture = unallocatedPaymentFixture();

    expect(fn () => Livewire::test(UnallocatedPaymentsTable::class)
        ->callAction(TestAction::make('allocate')->table($fixture['payment']), data: [
            'contribution_charge_id' => $fixture['charge']->getKey(),
            'amount' => $amount,
        ]))->toThrow(DomainException::class, $message);

    $this->assertDatabaseCount('payment_allocations', 0);
})->with([
    'charge balance' => ['130.00', 'Die Zuordnung ist größer als der offene Forderungsbetrag.'],
    'payment balance' => ['210.00', 'Die Zuordnung ist größer als der noch verfügbare Zahlungsbetrag.'],
]);
