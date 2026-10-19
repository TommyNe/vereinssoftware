<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\PaymentBalanceResolver;
use App\Application\Contribution\PaymentManager;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\Payment;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AllocatePaymentAction
{
    public static function make(): Action
    {
        return Action::make('allocatePayment')
            ->label('Zahlung zuordnen')
            ->icon('heroicon-o-link')
            ->visible(static fn (Payment $record): bool => Gate::allows('managePayments', $record->member)
                && $record->status === PaymentStatus::Booked
                && bccomp(app(PaymentBalanceResolver::class)->unallocatedAmount($record), '0.00', 2) > 0)
            ->schema([
                Select::make('contribution_charge_id')
                    ->label('Forderung')
                    ->required()
                    ->searchable()
                    ->options(static function (Payment $record): array {
                        $resolver = app(PaymentBalanceResolver::class);

                        return ContributionCharge::query()
                            ->where('club_id', app(CurrentClub::class)->id())
                            ->where('member_id', $record->member_id)
                            ->where('status', '!=', ContributionChargeStatus::Cancelled->value)
                            ->orderBy('due_date')
                            ->get()
                            ->filter(static fn (ContributionCharge $charge): bool => bccomp($resolver->outstandingAmount($charge), '0.00', 2) > 0)
                            ->pluck('description', 'id')
                            ->all();
                    }),

                TextInput::make('amount')
                    ->label('Zuordnungsbetrag')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->suffix('€'),
            ])
            ->action(static function (array $data, Payment $record, PaymentManager $manager, Action $action): void {
                Gate::authorize('managePayments', $record->member);

                $charge = ContributionCharge::query()
                    ->where('club_id', app(CurrentClub::class)->id())
                    ->where('member_id', $record->member_id)
                    ->where('status', '!=', ContributionChargeStatus::Cancelled->value)
                    ->findOrFail($data['contribution_charge_id']);

                try {
                    $manager->allocate($record, $charge, (string) $data['amount']);
                } catch (DomainException $exception) {
                    $livewire = $action->getLivewire();
                    $statePath = $livewire->getSchema($livewire->getMountedActionSchemaName())->getStatePath();

                    throw ValidationException::withMessages([$statePath.'.amount' => $exception->getMessage()]);
                }

                Notification::make()
                    ->title('Zahlung zugeordnet')
                    ->success()
                    ->send();
            });
    }
}
