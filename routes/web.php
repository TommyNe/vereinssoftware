<?php

use App\Http\Controllers\ClubInvitationController;
use Filament\Auth\Http\Controllers\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get(
    '/invitations/{token}',
    [
        ClubInvitationController::class,
        'show',
    ]
)->name(
    'club-invitations.show'
);

Route::get(
    '/email/verify/{id}/{hash}',
    EmailVerificationController::class
)->middleware([
    'signed',
    'throttle:6,1',
])->name(
    'verification.verify'
);
