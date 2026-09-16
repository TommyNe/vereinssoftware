<?php

namespace App\Filament\Resources\ClubFunctions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ClubFunctionsTable
{
    public static function configure(
        Table $table,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Funktion')
                    ->searchable()
                    ->sortable(),

                TextColumn::make(
                    'description'
                )
                    ->label('Beschreibung')
                    ->limit(60)
                    ->toggleable(),

                TextColumn::make(
                    'sort_order'
                )
                    ->label('Sortierung')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
            ])

            ->filters([
                TernaryFilter::make(
                    'is_active'
                )
                    ->label('Aktiv'),
            ])

            ->defaultSort(
                'sort_order'
            )

            ->recordActions([
                EditAction::make(),
            ]);
    }
}
