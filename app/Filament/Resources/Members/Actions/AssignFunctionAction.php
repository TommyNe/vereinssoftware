<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\AssignMemberFunction;
use App\Application\Membership\Handlers\AssignMemberFunctionHandler;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\ClubFunction;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class AssignFunctionAction
{
    public static function make(): Action
    {
        return Action::make('assignFunction')
            ->label('Funktion vergeben')
            ->icon('heroicon-o-briefcase')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record->status !== MembershipStatus::Left
                    && Gate::allows(
                        'manageFunctions',
                        $record,
                    )
            )

            ->modalHeading(
                'Vereinsfunktion vergeben'
            )

            ->modalSubmitActionLabel(
                'Funktion vergeben'
            )

            ->schema([
                Select::make(
                    'club_function_id'
                )
                    ->label('Funktion')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(
                        static function (
                            Member $record,
                        ): array {
                            $club = app(
                                CurrentClub::class
                            );

                            $activeIds = $record
                                ->activeFunctionAssignments()
                                ->pluck(
                                    'club_function_id'
                                );

                            return ClubFunction::query()
                                ->where(
                                    'club_id',
                                    $club->id(),
                                )
                                ->where(
                                    'is_active',
                                    true,
                                )
                                ->whereNotIn(
                                    'id',
                                    $activeIds,
                                )
                                ->orderBy(
                                    'sort_order'
                                )
                                ->orderBy('name')
                                ->pluck(
                                    'name',
                                    'id',
                                )
                                ->all();
                        }
                    ),

                DatePicker::make('valid_from')
                    ->label('Gültig ab')
                    ->required()
                    ->native(false)
                    ->default(now()),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    AssignMemberFunctionHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageFunctions',
                        $record,
                    );

                    $handler->handle(
                        new AssignMemberFunction(
                            memberId: (string) $record->getKey(),

                            clubFunctionId: (string) $data[
                            'club_function_id'
                            ],

                            validFrom: CarbonImmutable::parse(
                                $data['valid_from']
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Funktion vergeben'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
