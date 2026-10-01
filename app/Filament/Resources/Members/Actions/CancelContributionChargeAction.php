<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Contribution\CancelContributionCharge;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class CancelContributionChargeAction
{
    public static function make(): Action
    {
        return Action::make('cancelContributionCharge')
            ->label('Forderung stornieren')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(
                static function (Member $record): bool {
                    $charge = $record->contributionCharges()
                        ->where(
                            'status',
                            ContributionChargeStatus::Open->value,
                        )
                        ->first();

                    return $charge !== null
                        && Gate::allows('update', $charge);
                }
            )
            ->modalHeading('Forderung stornieren')
            ->modalSubmitActionLabel('Forderung stornieren')
            ->schema([
                Select::make('charge_id')
                    ->label('Forderung')
                    ->required()
                    ->options(
                        static fn (Member $record): array => $record
                            ->contributionCharges()
                            ->where(
                                'status',
                                ContributionChargeStatus::Open->value,
                            )
                            ->orderBy('due_date')
                            ->get()
                            ->mapWithKeys(
                                static fn (
                                    ContributionCharge $charge,
                                ): array => [
                                    $charge->getKey() => sprintf(
                                        '%s – %s € (fällig %s)',
                                        $charge->description,
                                        $charge->amount,
                                        $charge->due_date->format('d.m.Y'),
                                    ),
                                ],
                            )
                            ->all(),
                    )
                    ->searchable()
                    ->preload(),
                Textarea::make('reason')
                    ->label('Stornogrund')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(
                static function (
                    array $data,
                    Member $record,
                    CancelContributionCharge $canceller,
                ): void {
                    $user = Auth::user();

                    abort_unless($user instanceof User, 403);

                    $charge = $record
                        ->contributionCharges()
                        ->whereKey($data['charge_id'])
                        ->where(
                            'status',
                            ContributionChargeStatus::Open->value,
                        )
                        ->firstOrFail();

                    Gate::authorize('update', $charge);

                    $canceller->handle(
                        charge: $charge,
                        reason: (string) $data['reason'],
                        cancelledBy: $user,
                    );

                    Notification::make()
                        ->title('Forderung storniert')
                        ->success()
                        ->send();
                },
            );
    }
}
