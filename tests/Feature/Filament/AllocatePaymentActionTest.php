<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Contribution\Models\PaymentAllocation;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

function allocationActionCharge(Member $member, string $description = 'Jahresbeitrag'): ContributionCharge
{
    $type = ContributionType::query()->firstOrCreate([
        'club_id' => $member->club_id,
        'name' => $description,
        'interval' => 'yearly',
    ]);

    return ContributionCharge::query()->create([
        'club_id' => $member->club_id,
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '120.00',
        'description' => $description,
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-08',
    ]);
}

/** @return array{member: Member, user: User, payment: Payment, charge: ContributionCharge} */
function allocationActionFixture(bool $canManage = true): array
{
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $permissions = [Permission::MembersView];
    if ($canManage) {
        $permissions[] = Permission::PaymentsManage;
    }
    foreach ($permissions as $permission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
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

    return compact('member', 'user', 'payment') + ['charge' => allocationActionCharge($member)];
}

test('allocating a payment stores the allocation and refreshes the charge balance', function (): void {
    $fixture = allocationActionFixture();

    Livewire::actingAs($fixture['user'])->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction(TestAction::make('allocatePayment')->schemaComponent('payments.0.allocation'), data: [
            'contribution_charge_id' => $fixture['charge']->getKey(),
            'amount' => '50.00',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Zahlung zugeordnet')
        ->assertSee('Teilweise bezahlt');

    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $fixture['payment']->getKey(),
        'contribution_charge_id' => $fixture['charge']->getKey(),
        'amount' => '50.00',
    ]);
});

test('the charge selection includes only outstanding non cancelled charges of the current club and member', function (): void {
    $fixture = allocationActionFixture();
    $cancelled = allocationActionCharge($fixture['member'], 'Storniert');
    $cancelled->update(['status' => 'cancelled']);
    $paid = allocationActionCharge($fixture['member'], 'Bezahlt');
    PaymentAllocation::query()->create([
        'club_id' => $fixture['member']->club_id,
        'payment_id' => $fixture['payment']->getKey(),
        'contribution_charge_id' => $paid->getKey(),
        'amount' => '120.00',
    ]);
    allocationActionCharge(Member::factory()->create(['club_id' => $fixture['member']->club_id]), 'Anderes Mitglied');
    allocationActionCharge(Member::factory()->create(['club_id' => Club::factory()->create()->getKey()]), 'Anderer Verein');

    Livewire::actingAs($fixture['user'])->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->mountAction(TestAction::make('allocatePayment')->schemaComponent('payments.0.allocation'))
        ->assertSchemaComponentExists('contribution_charge_id', checkComponentUsing: static fn (Select $component): bool => $component->getOptions() === [
            $fixture['charge']->getKey() => 'Jahresbeitrag',
        ]);
});

test('a member viewer without payment management permission cannot allocate a payment', function (): void {
    $fixture = allocationActionFixture(canManage: false);

    Livewire::actingAs($fixture['user'])->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->assertActionDoesNotExist(TestAction::make('allocatePayment')->schemaComponent('payments.0.allocation'));

    $this->assertDatabaseCount('payment_allocations', 0);
});

test('invalid allocation amounts show form errors without storing an allocation', function (mixed $amount): void {
    $fixture = allocationActionFixture();

    Livewire::actingAs($fixture['user'])->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction(TestAction::make('allocatePayment')->schemaComponent('payments.0.allocation'), data: [
            'contribution_charge_id' => $fixture['charge']->getKey(),
            'amount' => $amount,
        ])
        ->assertHasActionErrors(['amount']);

    $this->assertDatabaseCount('payment_allocations', 0);
})->with([null, '0.00', '-1.00', 'invalid', '130.00', '210.00']);

test('a forged charge selection cannot allocate to another member', function (): void {
    $fixture = allocationActionFixture();
    $foreign = allocationActionCharge(Member::factory()->create(['club_id' => $fixture['member']->club_id]));

    Livewire::actingAs($fixture['user'])->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction(TestAction::make('allocatePayment')->schemaComponent('payments.0.allocation'), data: [
            'contribution_charge_id' => $foreign->getKey(),
            'amount' => '50.00',
        ])
        ->assertHasActionErrors(['contribution_charge_id']);

    $this->assertDatabaseCount('payment_allocations', 0);
});
