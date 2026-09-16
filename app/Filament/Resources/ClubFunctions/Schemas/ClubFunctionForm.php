<?php

namespace App\Filament\Resources\ClubFunctions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ClubFunctionForm
{
    public static function configure(
        Schema $schema,
    ): Schema {
        return $schema
            ->components([
                Section::make(
                    'Vereinsfunktion'
                )
                    ->schema([
                        TextInput::make('name')
                            ->label('Bezeichnung')
                            ->required()
                            ->maxLength(150),

                        Textarea::make(
                            'description'
                        )
                            ->label('Beschreibung')
                            ->maxLength(1000)
                            ->rows(4),

                        TextInput::make(
                            'sort_order'
                        )
                            ->label('Sortierung')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
