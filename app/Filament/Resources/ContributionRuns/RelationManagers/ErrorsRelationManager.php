<?php

namespace App\Filament\Resources\ContributionRuns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'errors';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Fehler')
            ->columns([
                TextColumn::make('member.first_name')
                    ->label('Vorname'),

                TextColumn::make('member.last_name')
                    ->label('Nachname'),

                TextColumn::make('message')
                    ->label('Fehlermeldung')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Erfasst am')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->paginated(false);
    }
}
