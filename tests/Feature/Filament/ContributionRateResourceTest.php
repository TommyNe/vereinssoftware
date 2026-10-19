<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ContributionRates\ContributionRateResource;
use App\Filament\Resources\ContributionRates\Pages\ListContributionRates;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

it('uses the German contribution rate navigation labels', function (): void {
    expect(ContributionRateResource::getModelLabel())->toBe('Beitragssatz')
        ->and(ContributionRateResource::getPluralModelLabel())->toBe('Beitragssätze')
        ->and(ContributionRateResource::getNavigationLabel())->toBe('Beitragssätze')
        ->and(ContributionRateResource::getNavigationGroup())->toBe('Finanzen');
});

it('authorizes the contribution rates page through its policy', function (bool $canManage): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    if ($canManage) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::ContributionRatesManage->value, 'web'));
    }

    $response = $this->actingAs($user)->get(ContributionRateResource::getUrl('index', ['tenant' => $club]));

    if ($canManage) {
        $response->assertSuccessful();
    } else {
        $response->assertForbidden();
    }
})->with(['authorized' => true, 'missing permission' => false]);

it('shows contribution rate values in the table', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(
        SpatiePermission::findOrCreate(
            Permission::ContributionRatesManage->value,
            'web',
        ),
    );
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    $rate = ContributionRate::query()->create([
        'club_id' => $club->getKey(),
        'contribution_type_id' => $type->getKey(),
        'amount' => '30.00',
        'valid_from' => '2026-01-01',
        'valid_until' => '2026-12-31',
        'is_active' => true,
    ]);

    Livewire::test(ListContributionRates::class)
        ->assertCanSeeTableRecords([$rate])
        ->assertCanRenderTableColumn('contributionType.name')
        ->assertCanRenderTableColumn('amount')
        ->assertCanRenderTableColumn('valid_from')
        ->assertSee('Jahresbeitrag')
        ->assertSee("30,00\u{00A0}€")
        ->assertSee('01.01.2026');
});
