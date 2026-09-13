<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\ChangeMemberAddress;
use App\Application\Membership\Handlers\ChangeMemberAddressHandler;
use App\Domain\Membership\Models\Member;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Gate;

final class ChangeAddressAction
{
    public static function make(): Action
    {
        return Action::make('changeAddress')
            ->label('Adresse')
            ->icon('heroicon-o-map-pin')

            ->visible(
                static fn (
                    Member $record
                ): bool => Gate::allows(
                    'changeData',
                    $record,
                )
            )

            ->modalHeading(
                'Adresse ändern'
            )

            ->modalSubmitActionLabel(
                'Änderungen speichern'
            )

            ->fillForm(
                static fn (
                    Member $record
                ): array => [
                    'street' =>
                        $record->street,

                    'house_number' =>
                        $record->house_number,

                    'postal_code' =>
                        $record->postal_code,

                    'city' =>
                        $record->city,

                    'country_code' =>
                        $record->country_code
                        ?? 'DE',
                ]
            )

            ->schema([
                Section::make('Anschrift')
                    ->columns(2)
                    ->schema([
                        TextInput::make('street')
                            ->label('Straße')
                            ->required()
                            ->maxLength(150),

                        TextInput::make(
                            'house_number'
                        )
                            ->label('Hausnummer')
                            ->required()
                            ->maxLength(20),

                        TextInput::make(
                            'postal_code'
                        )
                            ->label('PLZ')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('city')
                            ->label('Ort')
                            ->required()
                            ->maxLength(150),

                        TextInput::make(
                            'country_code'
                        )
                            ->label('Ländercode')
                            ->required()
                            ->length(2)
                            ->default('DE')
                            ->formatStateUsing(
                                static fn (
                                    ?string $state
                                ): ?string =>
                                $state !== null
                                    ? strtoupper(
                                    $state
                                )
                                    : null
                            ),
                    ]),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    ChangeMemberAddressHandler $handler,
                ): void {
                    Gate::authorize(
                        'changeData',
                        $record,
                    );

                    $handler->handle(
                        new ChangeMemberAddress(
                            memberId:
                            (string) $record->getKey(),

                            street:
                            trim(
                                $data['street']
                            ),

                            houseNumber:
                            trim(
                                $data[
                                'house_number'
                                ]
                            ),

                            postalCode:
                            trim(
                                $data[
                                'postal_code'
                                ]
                            ),

                            city:
                            trim(
                                $data['city']
                            ),

                            countryCode:
                            strtoupper(
                                trim(
                                    $data[
                                    'country_code'
                                    ]
                                )
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Adresse aktualisiert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
