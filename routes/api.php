<?php

use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;

Route::post('/members', [
    MemberController::class,
    'store',
]);
