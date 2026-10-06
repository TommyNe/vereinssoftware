<?php

use App\Http\Controllers\ClubInvitationController;
use App\Http\Controllers\MemberDocumentDownloadController;
use App\Http\Controllers\SepaDebitRunDownloadController;
use App\Http\Middleware\SetCurrentClub;
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

Route::get(
    '/member-documents/{document}/download',
    MemberDocumentDownloadController::class
)
    ->middleware('auth')
    ->name(
        'member-documents.download'
    );

Route::get(
    '/sepa-debit-runs/{run}/download',
    SepaDebitRunDownloadController::class,
)
    ->middleware([
        'auth',
        SetCurrentClub::class,
    ])
    ->name('sepa-debit-runs.download');
