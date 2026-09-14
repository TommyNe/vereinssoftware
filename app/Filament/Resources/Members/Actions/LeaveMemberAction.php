<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\LeaveMember;
use App\Application\Membership\Handlers\LeaveMemberHandler;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class LeaveMemberAction
{
    public static function make(): Action
    {
        return Action::make('leaveMember')
            ->label('Austritt erfassen')
            ->icon('heroicon-o-arrow-right-start-on-rectangle')
            ->color('danger')

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
                'Austritt erfassen'
            )

            ->modalDescription(
                'Der Austritt beendet die aktuelle Mitgliedschaft. '
                .'Der Vorgang bleibt dauerhaft in der Ereignishistorie erhalten.'
            )

            ->modalSubmitActionLabel(
                'Austritt bestätigen'
            )

            ->schema([
                DatePicker::make('left_at')
                    ->label('Austrittsdatum')
                    ->required()
                    ->native(false)
                    ->default(now()),

                Textarea::make('reason')
                    ->label('Begründung')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    LeaveMemberHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageMembership',
                        $record,
                    );

                    $handler->handle(
                        new LeaveMember(
                            memberId: (string) $record->getKey(),

                            leftAt: CarbonImmutable::parse(
                                $data['left_at']
                            ),

                            reason: trim(
                                $data['reason']
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Austritt erfasst'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
