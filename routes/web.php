<?php

use App\Http\Controllers\ClubInvitationController;
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
