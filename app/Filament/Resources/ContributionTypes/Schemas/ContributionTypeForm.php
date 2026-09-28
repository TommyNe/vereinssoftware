<?php

namespace App\Filament\Resources\ContributionTypes\Schemas;

use App\Domain\Contribution\Enums\ContributionInterval;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContributionTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Bezeichnung')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Beschreibung')
                    ->rows(3),

                Select::make('interval')
                    ->label('Rhythmus')
                    ->required()
                    ->options(
                        collect(
                            ContributionInterval::cases()
                        )
                            ->mapWithKeys(
                                static fn (
                                    ContributionInterval $interval
                                ): array => [
                                    $interval->value => $interval->label(),
                                ]
                            )
                            ->all()
                    ),

                TextInput::make('sort_order')
                    ->label('Sortierung')
                    ->integer()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }
}
