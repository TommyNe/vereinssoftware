<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ContributionTypes\ContributionTypeResource;
use App\Filament\Resources\ContributionTypes\Pages\CreateContributionType;
use App\Filament\Resources\ContributionTypes\Pages\EditContributionType;
use App\Filament\Resources\ContributionTypes\Pages\ListContributionTypes;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

function contributionAdministrator(): Club
{
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::ContributionTypesManage->value, 'web'));
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    return $club;
}

it('lists contribution types without allowing bulk deletion', function (): void {
    contributionAdministrator();

    Livewire::test(ListContributionTypes::class)
        ->assertSuccessful()
        ->assertTableBulkActionHidden('delete');
});

it('creates a contribution type for the current club', function (): void {
    $club = contributionAdministrator();

    Livewire::test(CreateContributionType::class)
        ->fillForm(['name' => 'Jahresbeitrag', 'interval' => 'yearly', 'is_active' => true, 'sort_order' => 0])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('contribution_types', ['name' => 'Jahresbeitrag', 'club_id' => $club->getKey(), 'interval' => 'yearly']);
    $this->assertDatabaseCount('contribution_types', 1);
});

it('returns 404 when editing a contribution type from another club', function (): void {
    $type = ContributionType::query()->create(['club_id' => Club::factory()->create()->getKey(), 'name' => 'Fremder Beitrag', 'interval' => 'yearly']);
    $club = contributionAdministrator();

    $this->get(ContributionTypeResource::getUrl('edit', ['tenant' => $club, 'record' => $type]))->assertNotFound();

    $this->assertDatabaseHas('contribution_types', ['id' => $type->getKey(), 'name' => 'Fremder Beitrag']);
});

it('preserves the contribution type and its historical rates when deactivated', function (): void {
    $club = contributionAdministrator();
    $type = ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Jahresbeitrag', 'interval' => 'yearly']);
    $rate = ContributionRate::query()->create([
        'club_id' => $club->getKey(), 'contribution_type_id' => $type->getKey(),
        'amount' => '30.00', 'valid_from' => '2025-01-01', 'valid_until' => '2025-12-31',
    ]);

    Livewire::test(EditContributionType::class, ['record' => $type->getKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('contribution_types', ['id' => $type->getKey(), 'is_active' => false]);
    $this->assertModelExists($rate);
    expect($rate->fresh()->contributionType->getKey())->toBe($type->getKey());
});
