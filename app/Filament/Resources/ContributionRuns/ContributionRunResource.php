<?php

namespace App\Filament\Resources\ContributionRuns;

use App\Filament\Resources\ContributionRuns\Pages\CreateContributionRun;
use App\Filament\Resources\ContributionRuns\Pages\EditContributionRun;
use App\Filament\Resources\ContributionRuns\Pages\ListContributionRuns;
use App\Filament\Resources\ContributionRuns\Schemas\ContributionRunForm;
use App\Filament\Resources\ContributionRuns\Tables\ContributionRunsTable;
use App\Models\ContributionRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContributionRunResource extends Resource
{
    protected static ?string $model = ContributionRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ContributionRun';

    public static function form(Schema $schema): Schema
    {
        return ContributionRunForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContributionRunsTable::configure($table);
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
            'index' => ListContributionRuns::route('/'),
            'create' => CreateContributionRun::route('/create'),
            'edit' => EditContributionRun::route('/{record}/edit'),
        ];
    }
}
