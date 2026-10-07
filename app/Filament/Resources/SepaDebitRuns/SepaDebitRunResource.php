<?php

namespace App\Filament\Resources\SepaDebitRuns;

use App\Domain\Sepa\Models\SepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\Pages\CreateSepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\Pages\EditSepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\Pages\ListSepaDebitRuns;
use App\Filament\Resources\SepaDebitRuns\Pages\ViewSepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\RelationManagers\ErrorsRelationManager;
use App\Filament\Resources\SepaDebitRuns\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\SepaDebitRuns\Schemas\SepaDebitRunForm;
use App\Filament\Resources\SepaDebitRuns\Schemas\SepaDebitRunInfolist;
use App\Filament\Resources\SepaDebitRuns\Tables\SepaDebitRunsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SepaDebitRunResource extends Resource
{
    protected static ?string $model = SepaDebitRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'SEPA-Lastschriftlauf';

    protected static ?string $pluralModelLabel = 'SEPA-Lastschriftläufe';

    protected static ?string $navigationLabel = 'SEPA-Lastschriftläufe';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SepaDebitRunForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SepaDebitRunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SepaDebitRunsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            ErrorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSepaDebitRuns::route('/'),
            'create' => CreateSepaDebitRun::route('/create'),
            'view' => ViewSepaDebitRun::route('/{record}'),
            'edit' => EditSepaDebitRun::route('/{record}/edit'),
        ];
    }
}
