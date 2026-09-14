<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\JoinMemberDepartment;
use App\Application\Membership\Handlers\JoinMemberDepartmentHandler;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Department;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class JoinDepartmentAction
{
    public static function make(): Action
    {
        return Action::make('joinDepartment')
            ->label('Abteilung hinzufügen')
            ->icon('heroicon-o-plus')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record->status !== MembershipStatus::Left
                    && Gate::allows(
                        'manageDepartments',
                        $record,
                    )
            )

            ->modalHeading(
                'Mitglied einer Abteilung zuordnen'
            )

            ->modalSubmitActionLabel(
                'Abteilung hinzufügen'
            )

            ->schema([
                Select::make('department_id')
                    ->label('Abteilung')
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

                            $existingIds = $record
                                ->departments()
                                ->pluck('departments.id');

                            return Department::query()
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
                                    $existingIds,
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

                DatePicker::make('joined_at')
                    ->label('Eintritt in Abteilung')
                    ->required()
                    ->native(false)
                    ->default(now()),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    JoinMemberDepartmentHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageDepartments',
                        $record,
                    );

                    $handler->handle(
                        new JoinMemberDepartment(
                            memberId: (string) $record->getKey(),

                            departmentId: (string) $data[
                            'department_id'
                            ],

                            joinedAt: CarbonImmutable::parse(
                                $data['joined_at']
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Abteilung hinzugefügt'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
