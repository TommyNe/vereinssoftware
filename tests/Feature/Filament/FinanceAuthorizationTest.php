<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\PaymentManager;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Filament\Pages\FinanceOverview;
use App\Filament\Resources\Members\Actions\AllocatePaymentAction;
use App\Filament\Resources\Members\Actions\CreatePaymentAction;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\PaymentResource;
use App\Livewire\UnallocatedPaymentsTable;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/**
 * @param  array<int, Permission>  $permissions
 * @return array{club: Club, user: User}
 */
function financeAuthorizationUser(array $permissions): array
{
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    foreach ($permissions as $permission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
    }
    test()->actingAs($user);
    app(CurrentClub::class)->set($club);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);

    return compact('club', 'user');
}

/** @return array{member: Member, payment: Payment, charge: ContributionCharge} */
function financeAuthorizationPayment(Club $club): array
{
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $payment = Payment::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => 'booked',
        'method' => 'bank_transfer',
        'amount' => '100.00',
        'booking_date' => '2026-10-19',
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
        'amount' => '100.00',
        'description' => 'Jahresbeitrag',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-19',
    ]);

    return compact('member', 'payment', 'charge');
}

it('shows finance navigation and permits direct page access only with the matching view permission', function (array $permissions, bool $canViewOverview, bool $canViewPayments): void {
    financeAuthorizationUser($permissions);
    $labels = collect(Filament::getNavigation())
        ->filter(static fn (NavigationGroup $group): bool => $group->getLabel() === 'Finanzen')
        ->flatMap(static fn (NavigationGroup $group): iterable => $group->getItems())
        ->map(static fn (NavigationItem $item): string => $item->getLabel())
        ->all();

    expect(in_array('Übersicht', $labels, true))->toBe($canViewOverview);
    expect(in_array('Zahlungen', $labels, true))->toBe($canViewPayments);
    $overviewResponse = $this->get(FinanceOverview::getUrl());
    $paymentResponse = $this->get(PaymentResource::getUrl());

    if ($canViewOverview) {
        $overviewResponse->assertOk();
    } else {
        $overviewResponse->assertForbidden();
    }
    if ($canViewPayments) {
        $paymentResponse->assertOk();
    } else {
        $paymentResponse->assertForbidden();
    }
})->with([
    'no finance access' => [[], false, false],
    'payment viewer without overview permission' => [[Permission::PaymentsView], false, true],
    'overview viewer without payment permission' => [[Permission::FinanceOverviewView], true, false],
]);

it('prevents payment viewers from creating payments through pages or forged action calls', function (): void {
    financeAuthorizationUser([Permission::PaymentsView]);

    $this->get(PaymentResource::getUrl('create'))->assertForbidden();
    Livewire::test(CreatePayment::class)->assertForbidden();
    Livewire::test(ListPayments::class)
        ->assertActionHidden('create')
        ->call('mountAction', 'create')
        ->call('callMountedAction')
        ->assertNotNotified();

    $this->assertDatabaseCount('payments', 0);
});

it('rechecks view permissions on subsequent Livewire requests', function (string $pageClass, Permission $permission, string $method): void {
    $fixture = financeAuthorizationUser([$permission]);
    $page = Livewire::test($pageClass);
    $fixture['user']->revokePermissionTo($permission->value);

    $page->call($method)->assertForbidden();
})->with([
    'overview data request after permission revocation' => [FinanceOverview::class, Permission::FinanceOverviewView, 'getOverview'],
    'payment list refresh after permission revocation' => [ListPayments::class, Permission::PaymentsView, '$refresh'],
]);

it('rejects a forged create submission after payment management permission is revoked', function (): void {
    $fixture = financeAuthorizationUser([Permission::PaymentsView, Permission::PaymentsManage]);
    $member = Member::factory()->create(['club_id' => $fixture['club']->getKey()]);
    $page = Livewire::test(CreatePayment::class)->set('data', [
        'member_id' => (string) $member->getKey(),
        'amount' => '100.00',
        'method' => 'bank_transfer',
        'booking_date' => '2026-10-19',
    ]);
    $fixture['user']->revokePermissionTo(Permission::PaymentsManage->value);

    $page->call('create')->assertForbidden();

    $this->assertDatabaseCount('payments', 0);
});

it('keeps the member payment creation callback protected even if its UI is bypassed', function (): void {
    $fixture = financeAuthorizationUser([Permission::PaymentsView, Permission::MembersView]);
    $member = Member::factory()->create(['club_id' => $fixture['club']->getKey()]);
    $callback = CreatePaymentAction::make()->getActionFunction();

    expect(fn () => $callback([
        'amount' => '100.00',
        'method' => 'bank_transfer',
        'booking_date' => '2026-10-19',
    ], $member, app(PaymentManager::class)))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('payments', 0);
});

it('prevents payment viewers from allocating through forged table action calls', function (): void {
    $fixture = financeAuthorizationUser([Permission::PaymentsView]);
    $records = financeAuthorizationPayment($fixture['club']);

    $page = Livewire::test(UnallocatedPaymentsTable::class)
        ->assertActionHidden(TestAction::make('allocate')->table($records['payment']))
        ->call('mountAction', 'allocate', [], ['table' => true, 'recordKey' => $records['payment']->getKey()])
        ->call('callMountedAction')
        ->assertNotNotified();
    $callback = $page->instance()->getTable()->getAction('allocate')->getActionFunction();

    expect(fn () => $callback([
        'contribution_charge_id' => $records['charge']->getKey(),
        'amount' => '100.00',
    ], $records['payment'], app(PaymentManager::class)))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('payment_allocations', 0);
    expect($records['charge']->refresh()->status->value)->toBe('open');
});

it('rejects allocation submissions when management permission is revoked while the modal is open', function (): void {
    $fixture = financeAuthorizationUser([Permission::PaymentsView, Permission::PaymentsManage]);
    $records = financeAuthorizationPayment($fixture['club']);
    $page = Livewire::test(UnallocatedPaymentsTable::class)
        ->mountAction(TestAction::make('allocate')->table($records['payment']))
        ->fillForm([
            'contribution_charge_id' => $records['charge']->getKey(),
            'amount' => '100.00',
        ]);
    $fixture['user']->revokePermissionTo(Permission::PaymentsManage->value);

    $page->call('callMountedAction')->assertNotNotified();

    $this->assertDatabaseCount('payment_allocations', 0);
});

it('keeps member allocation protected when an unauthorized user forges the action name', function (): void {
    $fixture = financeAuthorizationUser([Permission::PaymentsView, Permission::MembersView]);
    $records = financeAuthorizationPayment($fixture['club']);

    Livewire::test(ViewMember::class, ['record' => $records['member']->getKey()])
        ->assertActionDoesNotExist(TestAction::make('allocatePayment')->schemaComponent('payments.0.allocation'))
        ->call('mountAction', 'allocatePayment', [], ['schemaComponent' => 'infolist.payments.0.allocation'])
        ->call('callMountedAction')
        ->assertNotNotified();
    $action = AllocatePaymentAction::make();
    $callback = $action->getActionFunction();

    expect(fn () => $callback([
        'contribution_charge_id' => $records['charge']->getKey(),
        'amount' => '100.00',
    ], $records['payment'], app(PaymentManager::class), $action))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('payment_allocations', 0);
});
