<?php

namespace App\Filament\Resources\ContributionRates;

use App\Domain\Contribution\Models\ContributionRate;
use App\Filament\Resources\ContributionRates\Pages\CreateContributionRate;
use App\Filament\Resources\ContributionRates\Pages\EditContributionRate;
use App\Filament\Resources\ContributionRates\Pages\ListContributionRates;
use App\Filament\Resources\ContributionRates\Schemas\ContributionRateForm;
use App\Filament\Resources\ContributionRates\Tables\ContributionRatesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContributionRateResource extends Resource
{
    protected static ?string $model = ContributionRate::class;

    protected static ?string $modelLabel = 'Beitragssatz';

    protected static ?string $pluralModelLabel = 'Beitragssätze';

    protected static ?string $navigationLabel = 'Beitragssätze';

    protected static string|UnitEnum|null $navigationGroup = 'Stammdaten';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ContributionRate';

    public static function form(Schema $schema): Schema
    {
        return ContributionRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContributionRatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContributionRates::route('/'),
            'create' => CreateContributionRate::route('/create'),
            'edit' => EditContributionRate::route('/{record}/edit'),
        ];
    }
}
