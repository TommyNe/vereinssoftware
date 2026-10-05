<?php

namespace App\Filament\Resources\SepaDebitRuns\Tables;

use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SepaDebitRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Bezeichnung')
                    ->searchable(),

                TextColumn::make('collection_date')
                    ->label('Einzug')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        static fn (SepaDebitRunStatus $state): string => $state->label(),
                    ),

                TextColumn::make('items_count')
                    ->label('Positionen'),

                TextColumn::make('errors_count')
                    ->label('Fehler'),

                TextColumn::make('total_amount')
                    ->label('Summe')
                    ->money('EUR'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
