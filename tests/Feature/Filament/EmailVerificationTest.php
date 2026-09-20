<?php

use App\Domain\Club\Models\Club;
use App\Models\User;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail as LaravelVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('verification.verify route is defined', function () {
    expect(Route::has('verification.verify'))->toBeTrue();
});

test('user can send email verification notification without route not found exception', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo(
        $user,
        VerifyEmail::class,
        function (VerifyEmail $notification) {
            return filled($notification->url);
        }
    );
});

test('laravel default verify email notification resolves verification url using callback', function () {
    $user = User::factory()->unverified()->create();

    $notification = new LaravelVerifyEmail;
    $mailMessage = $notification->toMail($user);

    expect($mailMessage->actionUrl)->not->toBeEmpty();
});

test('user can verify email through verification url', function () {
    $club = Club::factory()->create();
    $user = User::factory()->unverified()->create();
    $user->clubs()->attach($club);

    $this->actingAs($user);

    $verifyUrl = Filament::getVerifyEmailUrl($user);

    $response = $this->get($verifyUrl);

    $response->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
