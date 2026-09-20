<?php

use App\Domain\Club\Models\Club;
use App\Filament\Pages\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can view profile page and update name', function () {
    $club = Club::factory()->create();
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'user@example.com',
    ]);
    $user->clubs()->attach($club);

    $this->actingAs($user);

    $response = $this->get(route('filament.admin.pages.profile', ['tenant' => $club]));
    $response->assertSuccessful();

    Livewire::test(Profile::class)
        ->set('data.name', 'Updated Name')
        ->set('data.email', 'user@example.com')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Updated Name');
});

test('profile page displays 2fa setup options and actions', function () {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);

    $this->actingAs($user);
    Filament\Facades\Filament::setTenant($club);

    $response = $this->get(route('filament.admin.pages.profile', ['tenant' => $club]));
    $response->assertSuccessful();
    $response->assertSee(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'));

    Livewire::test(Profile::class)
        ->assertSee(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'))
        ->assertSee(__('filament-panels::auth/multi-factor/app/provider.management_schema.actions.messages.disabled'))
        ->assertSee(__('filament-panels::auth/multi-factor/app/actions/set-up.label'));
});

test('profile page displays 2fa disable and regenerate options when 2fa is active', function () {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->saveAppAuthenticationSecret('test-secret');
    $user->saveAppAuthenticationRecoveryCodes(['code1', 'code2']);
    $user->clubs()->attach($club);

    $this->actingAs($user);
    Filament\Facades\Filament::setTenant($club);

    $response = $this->get(route('filament.admin.pages.profile', ['tenant' => $club]));
    $response->assertSuccessful();
    $response->assertSee(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'));

    Livewire::test(Profile::class)
        ->assertSee(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'))
        ->assertSee(__('filament-panels::auth/multi-factor/app/provider.management_schema.actions.messages.enabled'))
        ->assertSee(__('filament-panels::auth/multi-factor/app/actions/disable.label'));
});

test('filament admin default panel and user menu items are configured correctly', function () {
    $panel = Filament\Facades\Filament::getDefaultPanel();

    expect($panel)->not->toBeNull()
        ->and($panel->getId())->toBe('admin');

    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);

    $this->actingAs($user);
    Filament\Facades\Filament::setTenant($club);

    $userMenuItems = $panel->getUserMenuItems();
    expect($userMenuItems)->toHaveKey('profile')
        ->and($userMenuItems['profile']->getUrl())->toBe(route('filament.admin.pages.profile', ['tenant' => $club]));
});
