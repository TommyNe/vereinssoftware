<?php

namespace App\Filament\Resources\ContributionRuns;

use App\Domain\Contribution\Models\ContributionRun;
use App\Filament\Resources\ContributionRuns\Pages\CreateContributionRun;
use App\Filament\Resources\ContributionRuns\Pages\EditContributionRun;
use App\Filament\Resources\ContributionRuns\Pages\ListContributionRuns;
use App\Filament\Resources\ContributionRuns\Pages\ViewContributionRun;
use App\Filament\Resources\ContributionRuns\RelationManagers\ErrorsRelationManager;
use App\Filament\Resources\ContributionRuns\Schemas\ContributionRunForm;
use App\Filament\Resources\ContributionRuns\Tables\ContributionRunsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContributionRunResource extends Resource
{
    protected static ?string $model = ContributionRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'ContributionRun';

    protected static ?string $modelLabel = 'Beitragslauf';

    protected static ?string $pluralModelLabel = 'Beitragsläufe';

    protected static ?string $navigationLabel = 'Beitragsläufe';

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
            ErrorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContributionRuns::route('/'),
            'create' => CreateContributionRun::route('/create'),
            'view' => ViewContributionRun::route('/{record}'),
            'edit' => EditContributionRun::route('/{record}/edit'),
        ];
    }
}
