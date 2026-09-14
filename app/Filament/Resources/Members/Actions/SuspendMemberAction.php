<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\Commands\SuspendMember;
use App\Application\Membership\Handlers\SuspendMemberHandler;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class SuspendMemberAction
{
    public static function make(): Action
    {
        return Action::make('suspendMember')
            ->label('Mitglied sperren')
            ->icon('heroicon-o-no-symbol')
            ->color('warning')

            ->visible(
                static fn (
                    Member $record,
                ): bool => $record->status ===
                    MembershipStatus::Active
                    && Gate::allows(
                        'manageMembership',
                        $record,
                    )
            )

            ->modalHeading(
                'Mitglied sperren'
            )

            ->modalDescription(
                'Das Mitglied wird als gesperrt markiert. '
                .'Dieser Vorgang wird im Event-Verlauf protokolliert.'
            )

            ->modalSubmitActionLabel(
                'Mitglied sperren'
            )

            ->schema([
                DatePicker::make('suspended_at')
                    ->label('Sperrdatum')
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
                    SuspendMemberHandler $handler,
                ): void {
                    Gate::authorize(
                        'manageMembership',
                        $record,
                    );

                    $handler->handle(
                        new SuspendMember(
                            memberId: (string) $record->getKey(),

                            suspendedAt: CarbonImmutable::parse(
                                $data['suspended_at']
                            ),

                            reason: trim(
                                $data['reason']
                            ),
                        )
                    );

                    Notification::make()
                        ->title(
                            'Mitglied gesperrt'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
