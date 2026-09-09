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
});
