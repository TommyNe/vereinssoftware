<?php

namespace App\Filament\Resources\SepaDebitRuns\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Positionen';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Positionen')
            ->recordTitleAttribute('mandate_reference')
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Mitglied'),

                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de'),

                TextColumn::make('mandate_reference')
                    ->label('Mandat'),

                TextColumn::make('purpose')
                    ->label('Verwendungszweck')
                    ->wrap(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->authorize(fn (): bool => Gate::allows('view', $this->getOwnerRecord()))
                    ->fillForm([])
                    ->schema([
                        TextEntry::make('member.full_name')
                            ->label('Mitglied'),

                        TextEntry::make('amount')
                            ->label('Betrag')
                            ->money('EUR', locale: 'de'),

                        TextEntry::make('mandate_reference')
                            ->label('Mandat'),

                        TextEntry::make('purpose')
                            ->label('Verwendungszweck'),

                        TextEntry::make('iban')
                            ->label('IBAN')
                            ->placeholder('—')
                            ->formatStateUsing(static function (?string $state): string {
                                if ($state === null) {
                                    return '—';
                                }

                                return '•••• •••• •••• '.substr($state, -4);
                            }),
                    ]),
            ]);
    }
}
