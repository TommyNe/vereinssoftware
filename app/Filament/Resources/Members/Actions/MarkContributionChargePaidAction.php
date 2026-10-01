<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Contribution\MarkContributionChargePaid;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class MarkContributionChargePaidAction
{
    public static function make(): Action
    {
        return Action::make('markContributionChargePaid')
            ->label('Forderung bezahlen')
            ->icon('heroicon-o-check-circle')
            ->color('success')
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
            ->modalHeading('Forderung als bezahlt markieren')
            ->modalSubmitActionLabel('Als bezahlt markieren')
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
                DatePicker::make('paid_at')
                    ->label('Bezahlt am')
                    ->required()
                    ->default(now())
                    ->native(false),
            ])
            ->action(
                static function (
                    array $data,
                    Member $record,
                    MarkContributionChargePaid $marker,
                ): void {
                    $charge = $record
                        ->contributionCharges()
                        ->whereKey($data['charge_id'])
                        ->where(
                            'status',
                            ContributionChargeStatus::Open->value,
                        )
                        ->firstOrFail();

                    Gate::authorize('update', $charge);

                    $marker->handle(
                        $charge,
                        CarbonImmutable::parse($data['paid_at']),
                    );

                    Notification::make()
                        ->title('Forderung als bezahlt markiert')
                        ->success()
                        ->send();
                },
            );
    }
}
