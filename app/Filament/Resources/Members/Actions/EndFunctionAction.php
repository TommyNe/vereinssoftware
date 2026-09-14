<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\EndMemberFunction;
use App\Application\Membership\Handlers\EndMemberFunctionHandler;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MemberFunction;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class EndFunctionAction
{
    public static function make(): Action
    {
        return Action::make('endFunction')
            ->label('Funktion beenden')
            ->icon('heroicon-o-x-circle')
            ->color('warning')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record
                    ->activeFunctionAssignments()
                    ->exists()
                    && Gate::allows(
                        'manageFunctions',
                        $record,
                    )
            )

            ->modalHeading(
                'Vereinsfunktion beenden'
            )

            ->modalSubmitActionLabel(
                'Funktion beenden'
            )

            ->schema([
                Select::make(
                    'club_function_id'
                )
                    ->label('Funktion')
                    ->required()
                    ->options(
                        static fn (
                            Member $record,
                        ): array => $record
                            ->activeFunctionAssignments()
                            ->with('clubFunction')
                            ->get()
                            ->mapWithKeys(
                                static fn (
                                    MemberFunction $assignment,
                                ): array => [
                                    (string) $assignment
                                        ->club_function_id => $assignment
                                        ->clubFunction
                                        ->name,
                                ]
                            )
                            ->all()
                    ),

                DatePicker::make('valid_until')
                    ->label('Gültig bis')
                    ->required()
                    ->native(false)
                    ->default(now()),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    EndMemberFunctionHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageFunctions',
                        $record,
                    );

                    $handler->handle(
                        new EndMemberFunction(
                            memberId: (string) $record->getKey(),

                            clubFunctionId: (string) $data[
                            'club_function_id'
                            ],

                            validUntil: CarbonImmutable::parse(
                                $data['valid_until']
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Funktion beendet'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
