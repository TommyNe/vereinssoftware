<?php

namespace App\Filament\Resources\SepaDebitRuns\Schemas;

use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaDebitSubmissionStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SepaDebitRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lastschriftlauf')
                    ->columnSpanFull()
                    ->columns(['sm' => 2, 'lg' => 3])
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(
                                static fn (SepaDebitRunStatus $state): string => $state->label(),
                            ),

                        TextEntry::make('collection_date')
                            ->label('Einzugsdatum')
                            ->date('d.m.Y'),

                        TextEntry::make('items_count')
                            ->label('Positionen'),

                        TextEntry::make('total_amount')
                            ->label('Gesamtsumme')
                            ->money('EUR', locale: 'de'),

                        TextEntry::make('errors_count')
                            ->label('Fehler'),
                    ]),
                Section::make('Bankeinreichung')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('submission.status')
                            ->label('Status')
                            ->badge()
                            ->placeholder('Noch nicht eingereicht')
                            ->formatStateUsing(
                                static fn (?SepaDebitSubmissionStatus $state): string => $state?->label()
                                    ?? 'Noch nicht eingereicht',
                            ),
                        TextEntry::make('submission.submission_method')
                            ->label('Einreichungsweg')
                            ->placeholder('—')
                            ->formatStateUsing(
                                static fn (?SepaSubmissionMethod $state): string => $state?->label() ?? '—',
                            ),
                        TextEntry::make('submission.submitted_at')
                            ->label('Eingereicht')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('submission.bank_reference')
                            ->label('Bankreferenz')
                            ->placeholder('—'),
                    ])
                    ->columns(4),
            ]);
    }
}
