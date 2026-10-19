<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Filament\Pages\FinanceOverview;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

test('the finance overview renders and survives a Livewire refresh with outstanding charges', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::FinanceOverviewView->value, 'web'));
    $this->actingAs($user);
    app(CurrentClub::class)->set($club);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '120.00',
        'description' => 'Jahresbeitrag',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-14',
    ]);

    $this->get(FinanceOverview::getUrl())
        ->assertOk()
        ->assertSee('Finanzübersicht');

    Livewire::test(FinanceOverview::class)
        ->assertSee('Finanzübersicht')
        ->call('$refresh')
        ->assertSee('Finanzübersicht');
});
