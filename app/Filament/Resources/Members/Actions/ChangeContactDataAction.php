<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\ChangeMemberContactData;
use App\Application\Membership\Handlers\ChangeMemberContactDataHandler;
use App\Domain\Membership\Models\Member;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class ChangeContactDataAction
{
    public static function make(): Action
    {
        return Action::make('changeContactData')
            ->label('Kontaktdaten')
            ->icon('heroicon-o-phone')

            ->visible(
                static fn (
                    Member $record
                ): bool => Gate::allows(
                    'changeData',
                    $record,
                )
            )

            ->modalHeading(
                'Kontaktdaten ändern'
            )

            ->modalSubmitActionLabel(
                'Änderungen speichern'
            )

            ->fillForm(
                static fn (
                    Member $record
                ): array => [
                    'email' =>
                        $record->email,

                    'phone' =>
                        $record->phone,

                    'mobile' =>
                        $record->mobile,
                ]
            )

            ->schema([
                TextInput::make('email')
                    ->label('E-Mail')
                    ->email()
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->maxLength(50)
                    ->nullable(),

                TextInput::make('mobile')
                    ->label('Mobil')
                    ->tel()
                    ->maxLength(50)
                    ->nullable(),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    ChangeMemberContactDataHandler $handler,
                ): void {
                    Gate::authorize(
                        'changeData',
                        $record,
                    );

                    $handler->handle(
                        new ChangeMemberContactData(
                            memberId:
                            (string) $record->getKey(),

                            email:
                            self::nullableString(
                                $data['email']
                                ?? null
                            ),

                            phone:
                            self::nullableString(
                                $data['phone']
                                ?? null
                            ),

                            mobile:
                            self::nullableString(
                                $data['mobile']
                                ?? null
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Kontaktdaten aktualisiert'
                        )
                        ->success()
                        ->send();
                }
            );
    }

    private static function nullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
