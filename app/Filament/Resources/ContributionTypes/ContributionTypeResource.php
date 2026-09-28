<?php

namespace App\Filament\Resources\ContributionTypes;

use App\Domain\Contribution\Models\ContributionType;
use App\Filament\Resources\ContributionTypes\Pages\CreateContributionType;
use App\Filament\Resources\ContributionTypes\Pages\EditContributionType;
use App\Filament\Resources\ContributionTypes\Pages\ListContributionTypes;
use App\Filament\Resources\ContributionTypes\Schemas\ContributionTypeForm;
use App\Filament\Resources\ContributionTypes\Tables\ContributionTypesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContributionTypeResource extends Resource
{
    protected static ?string $model = ContributionType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Beitragsart';

    public static function form(Schema $schema): Schema
    {
        return ContributionTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContributionTypesTable::configure($table);
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
            'index' => ListContributionTypes::route('/'),
            'create' => CreateContributionType::route('/create'),
            'edit' => EditContributionType::route('/{record}/edit'),
        ];
    }
}
