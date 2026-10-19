<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Filament\Resources\ContributionCharges\ContributionChargeResource;
use App\Filament\Resources\ContributionCharges\Pages\ListContributionCharges;
use App\Filament\Resources\ContributionCharges\Pages\ViewContributionCharge;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

function chargeResourceRecord(Club $club): ContributionCharge
{
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);

    return ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '120.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-14',
    ]);
}

function chargeResourceClub(bool $canView = true): Club
{
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $permissions = [Permission::ContributionChargesManage];
    if ($canView) {
        $permissions[] = Permission::ContributionChargesView;
    }
    foreach ($permissions as $permission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
    }
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    return $club;
}

test('the charge resource only registers list and view routes', function (): void {
    expect(Route::has('filament.admin.resources.contribution-charges.index'))->toBeTrue();
    expect(Route::has('filament.admin.resources.contribution-charges.view'))->toBeTrue();
    expect(Route::has('filament.admin.resources.contribution-charges.create'))->toBeFalse();
    expect(Route::has('filament.admin.resources.contribution-charges.edit'))->toBeFalse();
});

test('the list shows only the current clubs charges and only offers viewing', function (): void {
    $foreign = chargeResourceRecord(Club::factory()->create());
    $club = chargeResourceClub();
    $charge = chargeResourceRecord($club);

    Livewire::test(ListContributionCharges::class)
        ->assertCanSeeTableRecords([$charge])
        ->assertCanNotSeeTableRecords([$foreign])
        ->assertTableColumnStateSet('amount', '120.00', $charge)
        ->assertSee('Jahresbeitrag 2026')
        ->assertSee('Offen')
        ->assertActionExists(TestAction::make('view')->table($charge))
        ->assertActionDoesNotExist(TestAction::make('edit')->table($charge))
        ->assertActionDoesNotExist(TestAction::make('delete')->table($charge))
        ->assertActionDoesNotExist(TestAction::make('delete')->table()->bulk())
        ->assertActionDoesNotExist('create');
});

test('the charge detail shows an infolist without editing or deleting', function (): void {
    $club = chargeResourceClub();
    $charge = chargeResourceRecord($club);

    $this->get(ContributionChargeResource::getUrl('view', ['record' => $charge]))->assertOk();

    Livewire::test(ViewContributionCharge::class, ['record' => $charge->getKey()])
        ->assertSee('Jahresbeitrag 2026')
        ->assertSee($charge->member->member_number)
        ->assertSee('14.10.2026')
        ->assertActionDoesNotExist('edit')
        ->assertActionDoesNotExist('delete');
});

test('charges from other clubs cannot be opened', function (): void {
    $foreign = chargeResourceRecord(Club::factory()->create());
    chargeResourceClub();

    $this->get(ContributionChargeResource::getUrl('view', ['record' => $foreign]))->assertNotFound();
});

test('charge management permission alone does not permit viewing the resource', function (): void {
    $club = chargeResourceClub(canView: false);
    $charge = chargeResourceRecord($club);

    $this->get(ContributionChargeResource::getUrl())->assertForbidden();
    $this->get(ContributionChargeResource::getUrl('view', ['record' => $charge]))->assertForbidden();
});

test('charges can be filtered by status', function (ContributionChargeStatus $status): void {
    $club = chargeResourceClub();
    $charge = chargeResourceRecord($club);
    $charge->update(['status' => $status]);
    $others = collect(ContributionChargeStatus::cases())
        ->reject(static fn (ContributionChargeStatus $other): bool => $other === $status)
        ->map(function (ContributionChargeStatus $other) use ($charge): ContributionCharge {
            $record = $charge->replicate()->fill(['status' => $other]);
            $record->save();

            return $record;
        });

    Livewire::test(ListContributionCharges::class)
        ->filterTable('status', $status->value)
        ->assertCanSeeTableRecords([$charge])
        ->assertCanNotSeeTableRecords($others);
})->with(ContributionChargeStatus::cases());

test('overdue filtering includes only open charges due before today', function (): void {
    $this->travelTo(now()->setDate(2026, 10, 19)->startOfDay());
    $club = chargeResourceClub();
    $overdue = chargeResourceRecord($club);
    $others = collect([
        ['due_date' => '2026-10-19'],
        ['due_date' => '2026-10-20'],
        ['status' => 'paid'],
        ['status' => 'cancelled'],
    ])->map(function (array $attributes) use ($overdue): ContributionCharge {
        $charge = $overdue->replicate()->fill($attributes);
        $charge->save();

        return $charge;
    });

    Livewire::test(ListContributionCharges::class)
        ->filterTable('overdue')
        ->assertCanSeeTableRecords([$overdue])
        ->assertCanNotSeeTableRecords($others);
});

test('monthly due filtering includes the month boundaries and excludes other years', function (): void {
    $this->travelTo(now()->setDate(2026, 10, 19)->startOfDay());
    $club = chargeResourceClub();
    $base = chargeResourceRecord($club);
    $matching = collect(['2026-10-01', '2026-10-31'])->map(function (string $date) use ($base): ContributionCharge {
        $charge = $base->replicate()->fill(['due_date' => $date]);
        $charge->save();

        return $charge;
    });
    $others = collect(['2026-09-30', '2026-11-01', '2025-10-14'])->map(function (string $date) use ($base): ContributionCharge {
        $charge = $base->replicate()->fill(['due_date' => $date]);
        $charge->save();

        return $charge;
    });

    Livewire::test(ListContributionCharges::class)
        ->filterTable('due_this_month')
        ->assertCanSeeTableRecords($matching->push($base))
        ->assertCanNotSeeTableRecords($others);
});

test('period filtering matches overlapping contribution periods with inclusive boundaries', function (array $range, array $visible, array $hidden): void {
    $club = chargeResourceClub();
    $base = chargeResourceRecord($club);
    $base->delete();
    $charges = collect([
        'before' => ['period_from' => '2026-01-01', 'period_until' => '2026-09-30'],
        'start boundary' => ['period_from' => '2026-01-01', 'period_until' => '2026-10-01'],
        'inside' => ['period_from' => '2026-10-10', 'period_until' => '2026-10-20'],
        'end boundary' => ['period_from' => '2026-10-31', 'period_until' => '2026-12-31'],
        'after' => ['period_from' => '2026-11-01', 'period_until' => '2026-12-31'],
        'ongoing' => ['period_from' => '2026-01-01', 'period_until' => null],
    ])->map(function (array $attributes) use ($base): ContributionCharge {
        $charge = $base->replicate()->fill($attributes);
        $charge->save();

        return $charge;
    });

    Livewire::test(ListContributionCharges::class)
        ->filterTable('period', $range)
        ->assertCanSeeTableRecords($charges->only($visible))
        ->assertCanNotSeeTableRecords($charges->only($hidden));
})->with([
    'both boundaries' => [['from' => '2026-10-01', 'until' => '2026-10-31'], ['start boundary', 'inside', 'end boundary', 'ongoing'], ['before', 'after']],
    'from only' => [['from' => '2026-10-01'], ['start boundary', 'inside', 'end boundary', 'after', 'ongoing'], ['before']],
    'until only' => [['until' => '2026-10-31'], ['before', 'start boundary', 'inside', 'end boundary', 'ongoing'], ['after']],
    'no range' => [[], ['before', 'start boundary', 'inside', 'end boundary', 'after', 'ongoing'], []],
]);

test('member and contribution type filters combine and exclude foreign club options', function (): void {
    $foreign = chargeResourceRecord(Club::factory()->create());
    $foreign->contributionType->update(['name' => 'Fremde Beitragsart']);
    $club = chargeResourceClub();
    $charge = chargeResourceRecord($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $otherMember = $charge->replicate()->fill(['member_id' => $member->getKey()]);
    $otherMember->save();
    $type = ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Sonderbeitrag', 'interval' => 'yearly']);
    $otherType = $charge->replicate()->fill(['contribution_type_id' => $type->getKey()]);
    $otherType->save();

    $page = Livewire::test(ListContributionCharges::class);
    $fields = $page->instance()->getTableFiltersForm()->getFlatFields();
    expect($fields['member_id.value']->getOptions())
        ->toHaveKey($charge->member_id)->not->toHaveKey($foreign->member_id);
    expect($fields['contribution_type_id.value']->getOptions())
        ->toHaveKey($type->getKey())->not->toHaveKey($foreign->contribution_type_id);

    $page->filterTable('member_id', (string) $charge->member_id)
        ->assertCanSeeTableRecords([$charge, $otherType])
        ->assertCanNotSeeTableRecords([$otherMember, $foreign])
        ->filterTable('contribution_type_id', (string) $charge->contribution_type_id)
        ->assertCanSeeTableRecords([$charge])
        ->assertCanNotSeeTableRecords([$otherMember, $otherType, $foreign])
        ->filterTable('member_id', (string) $foreign->member_id)
        ->assertCanNotSeeTableRecords([$charge, $otherMember, $otherType, $foreign]);
});
