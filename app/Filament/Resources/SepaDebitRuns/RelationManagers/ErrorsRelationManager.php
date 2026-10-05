<?php

namespace App\Filament\Resources\SepaDebitRuns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class ErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'errors';

    protected static ?string $title = 'Fehler';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Fehler')
            ->recordTitleAttribute('message')
            ->columns([
                TextColumn::make('member.member_number')
                    ->label('Mitglied')
                    ->placeholder('—')
                    ->formatStateUsing(static fn (string $state): string => 'Mitglied '.$state),

                TextColumn::make('message')
                    ->label('Fehlermeldung')
                    ->wrap(),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('Keine Fehler');
    }
}
