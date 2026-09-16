<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

test('filament admin members list page renders and register member action is accessible', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $viewPermission = SpatiePermission::findOrCreate(
        Permission::MembersView->value,
        'web'
    );
    $createPermission = SpatiePermission::findOrCreate(
        Permission::MembersCreate->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo([$viewPermission, $createPermission]);

    $response = $this->actingAs($user)->get(
        MemberResource::getUrl('index', [
            'tenant' => $club,
        ])
    );

    $response->assertSuccessful();
    $response->assertSee('Mitglieder');

    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    Livewire::actingAs($user)
        ->test(ListMembers::class)
        ->assertActionVisible('registerMember')
        ->mountAction('registerMember')
        ->assertHasNoActionErrors();
});

test('user can register a new member via register member action wizard', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $viewPermission = SpatiePermission::findOrCreate(
        Permission::MembersView->value,
        'web'
    );
    $createPermission = SpatiePermission::findOrCreate(
        Permission::MembersCreate->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo([$viewPermission, $createPermission]);

    $this->actingAs($user);
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    Livewire::actingAs($user)
        ->test(ListMembers::class)
        ->callAction('registerMember', data: [
            'member_number' => 'M-12345',
            'joined_at' => '2026-01-01',
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'birth_date' => '1990-05-15',
            'street' => 'Hauptstraße',
            'house_number' => '10',
            'postal_code' => '12345',
            'city' => 'Musterstadt',
            'country_code' => 'DE',
            'email' => 'max@example.com',
            'phone' => '+49123456789',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('members', [
        'club_id' => $club->getKey(),
        'member_number' => 'M-12345',
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max@example.com',
    ]);
});
