<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

final class ChangePasswordAction
{
    public static function make(): Action
    {
        return Action::make(
            'changePassword'
        )
            ->label(
                'Passwort ändern'
            )
            ->icon(
                'heroicon-o-key'
            )

            ->schema([
                TextInput::make(
                    'current_password'
                )
                    ->label(
                        'Aktuelles Passwort'
                    )
                    ->password()
                    ->revealable()
                    ->required(),

                TextInput::make(
                    'password'
                )
                    ->label(
                        'Neues Passwort'
                    )
                    ->password()
                    ->revealable()
                    ->required()
                    ->rules([
                        Password::min(12)
                            ->letters()
                            ->numbers()
                            ->symbols()
                            ->uncompromised(),
                    ]),

                TextInput::make(
                    'password_confirmation'
                )
                    ->label(
                        'Passwort bestätigen'
                    )
                    ->password()
                    ->revealable()
                    ->required(),
            ])

            ->action(
                static function (
                    array $data
                ): void {
                    validator(
                        $data,
                        [
                            'current_password' => [
                                'required',
                                'current_password',
                            ],

                            'password' => [
                                'required',
                                'confirmed',
                                Password::min(12)
                                    ->letters()
                                    ->numbers()
                                    ->symbols()
                                    ->uncompromised(),
                            ],
                        ]
                    )->validate();

                    $user = Auth::user();

                    abort_unless(
                        $user instanceof User,
                        403,
                    );

                    $currentPassword =
                        (string) $data['current_password'];

                    Auth::logoutOtherDevices(
                        $currentPassword
                    );

                    $user->password =
                        $data['password'];

                    $user->save();

                    Notification::make()
                        ->title(
                            'Passwort geändert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
