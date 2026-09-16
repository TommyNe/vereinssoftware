<?php

use App\Domain\Club\Models\Club;
use App\Domain\Club\Models\ClubInvitation;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Filament\Resources\ClubInvitations\ClubInvitationResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

test('filament admin club-invitations route is defined and resolves url', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    $url = route('filament.admin.resources.club-invitations.index', [
        'tenant' => $club,
    ]);

    expect($url)->toContain('/admin/'.$club->getKey().'/club-invitations');
    expect(ClubInvitationResource::getUrl('index', ['tenant' => $club]))->toBe($url);
});

test('authenticated user with permission can view club invitations page', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $permission = SpatiePermission::findOrCreate(
        Permission::ClubUsersView->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo($permission);

    ClubInvitation::query()->create([
        'club_id' => $club->getKey(),
        'email' => 'invitee@example.com',
        'role' => Role::Administrator->value,
        'token_hash' => hash('sha256', 'test-token'),
        'invited_by' => $user->getKey(),
        'expires_at' => CarbonImmutable::now()->addDays(7),
    ]);

    $response = $this->actingAs($user)->get(
        route('filament.admin.resources.club-invitations.index', [
            'tenant' => $club,
        ])
    );

    $response->assertSuccessful();
    $response->assertSee('invitee@example.com');
});

test('admin dashboard renders sidebar with club invitations navigation item', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $permission = SpatiePermission::findOrCreate(
        Permission::ClubUsersView->value,
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
    $response->assertSee('Einladungen');
});
