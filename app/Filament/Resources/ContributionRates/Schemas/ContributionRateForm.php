<?php

namespace App\Filament\Resources\ContributionRates\Schemas;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\MembershipType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContributionRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make(
                    'contribution_type_id'
                )
                    ->label('Beitragsart')
                    ->required()
                    ->options(
                        ContributionType::query()
                            ->where(
                                'club_id',
                                app(CurrentClub::class)->id()
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck(
                                'name',
                                'id'
                            )
                    ),

                Select::make(
                    'membership_type_id'
                )
                    ->label('Mitgliedsart')
                    ->placeholder(
                        'Alle Mitgliedsarten'
                    )
                    ->options(
                        MembershipType::query()
                            ->where(
                                'club_id',
                                app(CurrentClub::class)->id()
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('name')
                            ->pluck(
                                'name',
                                'id'
                            )
                    ),

                TextInput::make('amount')
                    ->label('Betrag')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('€'),

                DatePicker::make('valid_from')
                    ->label('Gültig ab')
                    ->required(),

                DatePicker::make('valid_until')
                    ->label('Gültig bis')
                    ->afterOrEqual(
                        'valid_from'
                    ),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }
}
