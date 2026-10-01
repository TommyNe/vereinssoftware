<?php

namespace App\Filament\Resources\ContributionRuns\Tables;

use App\Domain\Contribution\Enums\ContributionRunStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ContributionRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contributionType.name')
                    ->label('Beitragsart')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(
                        static fn (ContributionRunStatus $state): string => $state->label(),
                    )
                    ->badge()
                    ->sortable(),

                TextColumn::make('calculation_date')
                    ->label('Berechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('period_from')
                    ->label('Zeitraum von')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('period_until')
                    ->label('Zeitraum bis')
                    ->date('d.m.Y')
                    ->placeholder('offen')
                    ->sortable(),

                TextColumn::make('charges_created')
                    ->label('Forderungen')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('members_exempt')
                    ->label('Beitragsfrei')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('duplicates_skipped')
                    ->label('Übersprungen')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('errors_count')
                    ->label('Fehler')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Summe')
                    ->money('EUR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
