<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('filament admin login route is accessible and renders password reset link', function () {
    expect(Route::has('filament.admin.auth.login'))->toBeTrue();
    expect(Route::has('filament.admin.auth.password-reset.request'))->toBeTrue();

    $response = $this->get(route('filament.admin.auth.login'));

    $response->assertSuccessful();
    $response->assertSee(route('filament.admin.auth.password-reset.request'));
});

test('filament admin password reset request page is accessible', function () {
    $response = $this->get(route('filament.admin.auth.password-reset.request'));

    $response->assertSuccessful();
});
