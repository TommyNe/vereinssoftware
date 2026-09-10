<?php

use App\Http\Controllers\Api\ClubContextController;
use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'current.club',
])->group(function (): void {
    Route::post(
        '/members',
        [MemberController::class, 'store']
    );

    Route::post(
        '/club-context',
        [ClubContextController::class, 'switch']
    );

    Route::patch(
        '/members/{member}/address',
        [
            MemberController::class,
            'changeAddress',
        ]
    );

    Route::patch(
        '/members/{member}/contact-data',
        [
            MemberController::class,
            'changeContactData',
        ]
    );

    Route::patch(
        '/members/{member}/personal-data',
        [
            MemberController::class,
            'changePersonalData',
        ]
    );
});
