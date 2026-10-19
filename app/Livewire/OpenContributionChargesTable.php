<?php

namespace App\Livewire;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\PaymentBalanceResolver;
use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Filament\Resources\Members\MemberResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class OpenContributionChargesTable extends TableWidget
{
    protected int|string|array $columnSpan =
        'full';

    public function table(
        Table $table,
    ): Table {
        return $table
            ->query(
                $this->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make(
                    'member.member_number'
                )
                    ->label('Mitgliedsnummer')
                    ->searchable()
                    ->url(
                        static fn (
                            ContributionCharge $record
                        ): string => MemberResource::getUrl(
                            'view',
                            [
                                'record' => $record->member_id,
                            ]
                        )
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'member.last_name'
                )
                    ->label('Mitglied')
                    ->formatStateUsing(
                        static fn (
                            ContributionCharge $record
                        ): string => trim(
                            $record->member->first_name
                            .' '
                            .$record->member->last_name
                        )
                    )
                    ->searchable([
                        'member.first_name',
                        'member.last_name',
                    ]),

                Tables\Columns\TextColumn::make(
                    'description'
                )
                    ->label('Forderung')
                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'amount'
                )
                    ->label('Betrag')
                    ->money('EUR'),

                Tables\Columns\TextColumn::make(
                    'paid_amount'
                )
                    ->label('Bezahlt')
                    ->state(
                        static function (
                            ContributionCharge $record
                        ): string {
                            return app(
                                PaymentBalanceResolver::class
                            )->allocatedAmount(
                                $record
                            );
                        }
                    )
                    ->money('EUR'),

                Tables\Columns\TextColumn::make(
                    'outstanding_amount'
                )
                    ->label('Offen')
                    ->state(
                        static function (
                            ContributionCharge $record
                        ): string {
                            return app(
                                PaymentBalanceResolver::class
                            )->outstandingAmount(
                                $record
                            );
                        }
                    )
                    ->money('EUR'),

                Tables\Columns\TextColumn::make(
                    'due_state'
                )
                    ->label('Fälligkeit')
                    ->badge()
                    ->state(
                        static fn (
                            ContributionCharge $record
                        ): string => $record->due_date->isPast()
                            ? 'overdue'
                            : 'current'
                    )
                    ->formatStateUsing(
                        static fn (
                            string $state
                        ): string => match ($state) {
                            'overdue' => 'Überfällig',

                            default => 'Offen',
                        }
                    )
                    ->color(
                        static fn (
                            string $state
                        ): string => $state === 'overdue'
                            ? 'danger'
                            : 'gray'
                    ),

                Tables\Columns\TextColumn::make(
                    'payment_state'
                )
                    ->label('Status')
                    ->badge()
                    ->state(
                        static function (
                            ContributionCharge $record
                        ): ChargePaymentState {
                            return app(
                                PaymentBalanceResolver::class
                            )->state(
                                $record
                            );
                        }
                    )
                    ->formatStateUsing(
                        static fn (
                            ChargePaymentState $state
                        ): string => $state->label()
                    )
                    ->color(
                        static fn (
                            ChargePaymentState $state
                        ): string => match ($state) {
                            ChargePaymentState::Open => 'danger',

                            ChargePaymentState::PartiallyPaid => 'warning',

                            ChargePaymentState::Paid => 'success',
                        }
                    ),
            ])
            ->filters([
                Tables\Filters\Filter::make(
                    'overdue'
                )
                    ->label(
                        'Nur überfällige'
                    )
                    ->query(
                        static fn (
                            Builder $query
                        ): Builder => $query->whereDate(
                            'due_date',
                            '<',
                            today()
                        )
                    ),

                Tables\Filters\Filter::make(
                    'due_this_month'
                )
                    ->label(
                        'Diesen Monat fällig'
                    )
                    ->query(
                        static fn (
                            Builder $query
                        ): Builder => $query
                            ->whereYear(
                                'due_date',
                                today()->year
                            )
                            ->whereMonth(
                                'due_date',
                                today()->month
                            )
                    ),
            ])
            ->defaultSort(
                'due_date'
            );
    }

    private function getQuery(): Builder
    {
        return ContributionCharge::query()
            ->where(
                'club_id',
                app(CurrentClub::class)->id()
            )
            ->openForPayment()
            ->with([
                'member',
                'paymentAllocations.payment',
            ]);
    }
}
