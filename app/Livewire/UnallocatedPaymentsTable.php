<?php

namespace App\Livewire;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\PaymentManager;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class UnallocatedPaymentsTable extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make(
                    'booking_date'
                )
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'member.member_number'
                )
                    ->label(
                        'Mitgliedsnummer'
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'member.last_name'
                )
                    ->label('Mitglied')
                    ->formatStateUsing(
                        static fn (
                            Payment $record
                        ): string => trim(
                            $record->member->first_name
                            .' '
                            .$record->member->last_name
                        )
                    ),

                Tables\Columns\TextColumn::make(
                    'amount'
                )
                    ->label(
                        'Zahlung'
                    )
                    ->money('EUR'),

                Tables\Columns\TextColumn::make(
                    'allocated_total'
                )
                    ->label(
                        'Zugeordnet'
                    )
                    ->money('EUR'),

                Tables\Columns\TextColumn::make(
                    'unallocated'
                )
                    ->label(
                        'Rest'
                    )
                    ->state(
                        static function (
                            Payment $record
                        ): string {
                            return bcsub(
                                (string) $record->amount,
                                (string) (
                                    $record->allocated_total
                                    ?? '0.00'
                                ),
                                2
                            );
                        }
                    )
                    ->money('EUR'),

                Tables\Columns\TextColumn::make(
                    'method'
                )
                    ->label(
                        'Zahlungsart'
                    )
                    ->formatStateUsing(
                        static fn (
                            PaymentMethod $state
                        ): string => $state->label()
                    ),

                Tables\Columns\TextColumn::make(
                    'reference'
                )
                    ->label('Referenz')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('allocate')
                    ->label('Zuordnen')
                    ->authorize(static fn (Payment $record): bool => Gate::allows('managePayments', $record->member))
                    ->icon(
                        'heroicon-o-link'
                    )
                    ->schema([
                        Select::make(
                            'contribution_charge_id'
                        )
                            ->label(
                                'Forderung'
                            )
                            ->required()
                            ->options(
                                function (
                                    Payment $record
                                ): array {
                                    return ContributionCharge::query()
                                        ->where(
                                            'club_id',
                                            $record->club_id
                                        )
                                        ->where(
                                            'member_id',
                                            $record->member_id
                                        )
                                        ->where(
                                            'status',
                                            ContributionChargeStatus::Open->value
                                        )
                                        ->orderBy(
                                            'due_date'
                                        )
                                        ->get()
                                        ->mapWithKeys(
                                            static function (
                                                ContributionCharge $charge
                                            ): array {
                                                return [
                                                    (string) $charge->getKey() => sprintf(
                                                        '%s – %s € – fällig %s',
                                                        $charge->description,
                                                        $charge->amount,
                                                        $charge->due_date
                                                            ->format(
                                                                'd.m.Y'
                                                            ),
                                                    ),
                                                ];
                                            }
                                        )
                                        ->all();
                                }
                            ),

                        TextInput::make('amount')
                            ->label(
                                'Zuordnungsbetrag'
                            )
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->suffix('€'),
                    ])
                    ->action(static function (array $data, Payment $record, PaymentManager $manager): void {
                        Gate::authorize('managePayments', $record->member);

                        $charge = ContributionCharge::query()
                            ->whereKey($data['contribution_charge_id'])
                            ->where('club_id', $record->club_id)
                            ->where('member_id', $record->member_id)
                            ->firstOrFail();

                        $manager->allocate(
                            payment: $record,
                            charge: $charge,
                            amount: (string) $data['amount'],
                        );

                        Notification::make()
                            ->title('Zahlung zugeordnet')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    private function getQuery(): Builder
    {
        return Payment::query()
            ->where(
                'club_id',
                app(CurrentClub::class)
                    ->id()
            )
            ->where(
                'status',
                PaymentStatus::Booked->value
            )
            ->with('member')
            ->withSum(
                'allocations as allocated_total',
                'amount'
            );
    }
}
