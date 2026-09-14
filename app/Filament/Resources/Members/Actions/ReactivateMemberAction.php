<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\ReactivateMember;
use App\Application\Membership\Handlers\ReactivateMemberHandler;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class ReactivateMemberAction
{
    public static function make(): Action
    {
        return Action::make('reactivateMember')
            ->label('Mitglied reaktivieren')
            ->icon('heroicon-o-check-circle')
            ->color('success')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record->status ===
                    MembershipStatus::Suspended
                    && Gate::allows(
                        'manageMembership',
                        $record,
                    )
            )

            ->modalHeading(
                'Mitglied reaktivieren'
            )

            ->modalDescription(
                'Die Sperrung des Mitglieds wird aufgehoben.'
            )

            ->modalSubmitActionLabel(
                'Reaktivieren'
            )

            ->schema([
                DatePicker::make(
                    'reactivated_at'
                )
                    ->label(
                        'Reaktivierungsdatum'
                    )
                    ->required()
                    ->native(false)
                    ->default(now()),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    ReactivateMemberHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageMembership',
                        $record,
                    );

                    $handler->handle(
                        new ReactivateMember(
                            memberId: (string) $record->getKey(),

                            reactivatedAt: CarbonImmutable::parse(
                                $data[
                                'reactivated_at'
                                ]
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Mitglied reaktiviert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
