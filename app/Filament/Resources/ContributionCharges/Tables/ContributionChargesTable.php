<?php

namespace App\Filament\Resources\ContributionCharges\Tables;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Membership\Models\Member;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContributionChargesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.member_number')
                    ->label('Mitgliedsnummer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('member.last_name')
                    ->label('Mitglied')
                    ->formatStateUsing(static fn (ContributionCharge $record): string => trim($record->member->first_name.' '.$record->member->last_name))
                    ->searchable(['member.first_name', 'member.last_name'])
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Forderung')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Fällig am')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(static fn (ContributionChargeStatus $state): string => $state->label())
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ContributionChargeStatus::Open->value => 'Offen',
                        ContributionChargeStatus::Paid->value => 'Bezahlt',
                        ContributionChargeStatus::Cancelled->value => 'Storniert',
                    ]),

                Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(static fn (Builder $query): Builder => $query
                        ->where('status', ContributionChargeStatus::Open->value)
                        ->whereDate('due_date', '<', today())),

                Filter::make('due_this_month')
                    ->label('Diesen Monat fällig')
                    ->query(static fn (Builder $query): Builder => $query
                        ->whereYear('due_date', today()->year)
                        ->whereMonth('due_date', today()->month)),

                SelectFilter::make('member_id')
                    ->label('Mitglied')
                    ->relationship('member', 'last_name', static fn (Builder $query): Builder => $query
                        ->where('club_id', app(CurrentClub::class)->id()))
                    ->getOptionLabelFromRecordUsing(static fn (Member $record): string => trim($record->member_number.' – '.$record->first_name.' '.$record->last_name))
                    ->searchable(['member_number', 'first_name', 'last_name'])
                    ->preload(),

                SelectFilter::make('contribution_type_id')
                    ->label('Beitragsart')
                    ->relationship('contributionType', 'name', static fn (Builder $query): Builder => $query
                        ->where('club_id', app(CurrentClub::class)->id()))
                    ->searchable()
                    ->preload(),

                Filter::make('period')
                    ->label('Zeitraum')
                    ->schema([
                        DatePicker::make('from')->label('Zeitraum von'),
                        DatePicker::make('until')->label('Zeitraum bis'),
                    ])
                    ->query(static fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), static fn (Builder $query): Builder => $query
                            ->where(static fn (Builder $query): Builder => $query
                                ->whereNull('period_until')
                                ->orWhereDate('period_until', '>=', $data['from'])))
                        ->when(filled($data['until'] ?? null), static fn (Builder $query): Builder => $query
                            ->whereDate('period_from', '<=', $data['until']))),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('due_date');
    }
}
