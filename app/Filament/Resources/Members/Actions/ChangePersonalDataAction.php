<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\ChangeMemberPersonalData;
use App\Application\Membership\Handlers\ChangeMemberPersonalDataHandler;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class ChangePersonalDataAction
{
    public static function make(): Action
    {
        return Action::make('changePersonalData')
            ->label('Persönliche Daten')
            ->icon('heroicon-o-identification')

            ->visible(
                static fn (
                    Member $record
                ): bool => Gate::allows(
                    'changeData',
                    $record,
                )
            )

            ->modalHeading(
                'Persönliche Daten ändern'
            )

            ->modalSubmitActionLabel(
                'Änderungen speichern'
            )

            ->fillForm(
                static fn (
                    Member $record
                ): array => [
                    'first_name' =>
                        $record->first_name,

                    'last_name' =>
                        $record->last_name,

                    'birth_date' =>
                        $record->birth_date,
                ]
            )

            ->schema([
                TextInput::make('first_name')
                    ->label('Vorname')
                    ->required()
                    ->maxLength(150),

                TextInput::make('last_name')
                    ->label('Nachname')
                    ->required()
                    ->maxLength(150),

                DatePicker::make('birth_date')
                    ->label('Geburtsdatum')
                    ->native(false)
                    ->maxDate(now())
                    ->nullable(),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    ChangeMemberPersonalDataHandler $handler,
                ): void {
                    Gate::authorize(
                        'changeData',
                        $record,
                    );

                    $handler->handle(
                        new ChangeMemberPersonalData(
                            memberId:
                            (string) $record->getKey(),

                            firstName:
                            trim(
                                $data['first_name']
                            ),

                            lastName:
                            trim(
                                $data['last_name']
                            ),

                            birthDate:
                            filled(
                                $data['birth_date']
                                ?? null
                            )
                                ? CarbonImmutable::parse(
                                $data[
                                'birth_date'
                                ]
                            )
                                : null,
                        )
                    );

                    Notification::make()
                        ->title(
                            'Persönliche Daten aktualisiert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
