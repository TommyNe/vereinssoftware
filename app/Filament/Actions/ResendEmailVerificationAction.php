<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

final class ResendEmailVerificationAction
{
    public static function make(): Action
    {
        return Action::make(
            'resendVerification'
        )
            ->label(
                'Bestätigungsmail erneut senden'
            )
            ->icon(
                'heroicon-o-envelope'
            )

            ->visible(
                static function (): bool {
                    $user =
                        Auth::user();

                    return $user instanceof User
                        && ! $user
                            ->hasVerifiedEmail();
                }
            )

            ->action(
                static function (): void {
                    $user =
                        Auth::user();

                    abort_unless(
                        $user instanceof User,
                        403,
                    );

                    $user
                        ->sendEmailVerificationNotification();

                    Notification::make()
                        ->title(
                            'Bestätigungsmail versendet'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
