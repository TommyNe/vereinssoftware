<?php

namespace App\Filament\Resources\ContributionRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ContributionRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contributionType.name')
                    ->label('Beitragsart')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('membershipType.name')
                    ->label('Mitgliedsart')
                    ->placeholder('Alle Mitgliedsarten')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('valid_from')
                    ->label('Gültig ab')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->placeholder('offen')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->defaultSort('valid_from', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
