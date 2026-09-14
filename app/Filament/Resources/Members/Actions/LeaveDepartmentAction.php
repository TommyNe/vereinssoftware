<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\LeaveMemberDepartment;
use App\Application\Membership\Handlers\LeaveMemberDepartmentHandler;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class LeaveDepartmentAction
{
    public static function make(): Action
    {
        return Action::make('leaveDepartment')
            ->label('Abteilung verlassen')
            ->icon('heroicon-o-minus-circle')
            ->color('warning')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record->departments()->exists()
                    && Gate::allows(
                        'manageDepartments',
                        $record,
                    )
            )

            ->modalHeading(
                'Abteilungszuordnung beenden'
            )

            ->modalSubmitActionLabel(
                'Zuordnung beenden'
            )

            ->schema([
                Select::make('department_id')
                    ->label('Abteilung')
                    ->required()
                    ->options(
                        static fn (
                            Member $record,
                        ): array => $record
                            ->departments()
                            ->orderBy('name')
                            ->pluck(
                                'name',
                                'departments.id',
                            )
                            ->all()
                    ),

                DatePicker::make('left_at')
                    ->label('Austrittsdatum')
                    ->required()
                    ->native(false)
                    ->default(now()),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    LeaveMemberDepartmentHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageDepartments',
                        $record,
                    );

                    $handler->handle(
                        new LeaveMemberDepartment(
                            memberId: (string) $record->getKey(),

                            departmentId: (string) $data[
                            'department_id'
                            ],

                            leftAt: CarbonImmutable::parse(
                                $data['left_at']
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Abteilungszuordnung beendet'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
