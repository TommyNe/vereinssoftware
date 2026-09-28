<?php

use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ContributionRates\ContributionRateResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

it('uses the German contribution rate navigation labels', function (): void {
    expect(ContributionRateResource::getModelLabel())->toBe('Beitragssatz')
        ->and(ContributionRateResource::getPluralModelLabel())->toBe('Beitragssätze')
        ->and(ContributionRateResource::getNavigationLabel())->toBe('Beitragssätze')
        ->and(ContributionRateResource::getNavigationGroup())->toBe('Stammdaten');
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
