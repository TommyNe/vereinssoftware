<?php

namespace App\Filament\Resources\ContributionTypes\Tables;

use App\Domain\Contribution\Enums\ContributionInterval;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ContributionTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('name')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('interval')
                    ->label('Rhythmus')
                    ->formatStateUsing(
                        static fn (ContributionInterval $state): string => $state->label(),
                    )
                    ->badge()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Sortierung')
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
            ->defaultSort('sort_order')
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
