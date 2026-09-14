<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\ChangeMembershipType;
use App\Application\Membership\Handlers\ChangeMembershipTypeHandler;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class ChangeMembershipTypeAction
{
    public static function make(): Action
    {
        return Action::make('changeMembershipType')
            ->label('Mitgliedsart ändern')
            ->icon('heroicon-o-identification')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record->status !==
                    MembershipStatus::Left
                    && Gate::allows(
                        'manageMembership',
                        $record,
                    )
            )

            ->modalHeading(
                'Mitgliedsart ändern'
            )

            ->modalSubmitActionLabel(
                'Mitgliedsart speichern'
            )

            ->fillForm(
                static fn (
                    Member $record,
                ): array => [
                    'membership_type_id' => $record->membership_type_id,
                ]
            )

            ->schema([
                Select::make(
                    'membership_type_id'
                )
                    ->label('Mitgliedsart')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(
                        static function (): array {
                            $club = app(
                                CurrentClub::class
                            );

                            return MembershipType::query()
                                ->where(
                                    'club_id',
                                    $club->id(),
                                )
                                ->where(
                                    'is_active',
                                    true,
                                )
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck(
                                    'name',
                                    'id',
                                )
                                ->all();
                        }
                    ),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    ChangeMembershipTypeHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageMembership',
                        $record,
                    );

                    $handler->handle(
                        new ChangeMembershipType(
                            memberId: (string) $record->getKey(),

                            membershipTypeId: (string) $data[
                            'membership_type_id'
                            ],
                        )
                    );

                    Notification::make()
                        ->title(
                            'Mitgliedsart geändert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
