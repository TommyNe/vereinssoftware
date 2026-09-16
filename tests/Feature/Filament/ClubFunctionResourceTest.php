<?php

use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\ClubFunction;
use App\Filament\Resources\ClubFunctions\ClubFunctionResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

test('filament admin club-functions route is defined and resolves url', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    $url = route('filament.admin.resources.club-functions.index', [
        'tenant' => $club,
    ]);

    expect($url)->toContain('/admin/'.$club->getKey().'/club-functions');
    expect(ClubFunctionResource::getUrl('index', ['tenant' => $club]))->toBe($url);
});

test('authenticated user with permission can view club functions page', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $permission = SpatiePermission::findOrCreate(
        Permission::ClubFunctionsManage->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo($permission);

    ClubFunction::query()->create([
        'club_id' => $club->getKey(),
        'name' => '1. Vorsitzender',
    ]);

    $response = $this->actingAs($user)->get(
        route('filament.admin.resources.club-functions.index', [
            'tenant' => $club,
        ])
    );

    $response->assertSuccessful();
    $response->assertSee('1. Vorsitzender');
});

test('admin dashboard renders sidebar with club functions navigation item', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $permission = SpatiePermission::findOrCreate(
        Permission::ClubFunctionsManage->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo($permission);

    $response = $this->actingAs($user)->get(
        route('filament.admin.pages.dashboard', [
            'tenant' => $club,
        ])
    );

    $response->assertSuccessful();
    $response->assertSee('Vereinsfunktionen');
});
