<?php

namespace App\Filament\Resources\ContributionRuns\Schemas;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContributionRunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('contributionTypes')
                    ->schema([
                        Select::make(
                            'contribution_type_id'
                        )
                            ->label('Beitragsart')
                            ->required()
                            ->options(
                                static fn (): array => ContributionType::query()
                                    ->where(
                                        'club_id',
                                        app(
                                            CurrentClub::class
                                        )->id()
                                    )
                                    ->where(
                                        'is_active',
                                        true
                                    )
                                    ->orderBy(
                                        'sort_order'
                                    )
                                    ->pluck(
                                        'name',
                                        'id'
                                    )
                                    ->all()
                            ),

                        DatePicker::make(
                            'calculation_date'
                        )
                            ->label(
                                'Berechnungsdatum'
                            )
                            ->required()
                            ->default(
                                today()
                            ),

                        DatePicker::make(
                            'period_from'
                        )
                            ->label(
                                'Zeitraum von'
                            )
                            ->required(),

                        DatePicker::make(
                            'period_until'
                        )
                            ->label(
                                'Zeitraum bis'
                            )
                            ->afterOrEqual(
                                'period_from'
                            ),

                        DatePicker::make(
                            'due_date'
                        )
                            ->label(
                                'Fällig am'
                            )
                            ->required(),

                        TextInput::make(
                            'description'
                        )
                            ->label(
                                'Beschreibung'
                            )
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
