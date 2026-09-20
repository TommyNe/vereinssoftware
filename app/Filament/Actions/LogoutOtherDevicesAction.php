<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

final class LogoutOtherDevicesAction
{
    public static function make(): Action
    {
        return Action::make('logoutOtherDevices')
            ->label('Andere Geräte abmelden')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(
                'Andere Geräte abmelden'
            )
            ->modalDescription(
                'Alle anderen aktiven Sitzungen werden beendet. '
                .'Dieses Gerät bleibt angemeldet.'
            )
            ->schema([
                TextInput::make('password')
                    ->label(
                        'Aktuelles Passwort'
                    )
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->action(
                static function (
                    array $data,
                ): void {
                    Auth::logoutOtherDevices(
                        (string) $data['password']
                    );

                    Notification::make()
                        ->title(
                            'Andere Geräte wurden abgemeldet'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
