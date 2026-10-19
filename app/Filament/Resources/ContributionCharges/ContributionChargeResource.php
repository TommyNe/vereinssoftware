<?php

namespace App\Filament\Resources\ContributionCharges;

use App\Domain\Contribution\Models\ContributionCharge;
use App\Filament\Resources\ContributionCharges\Pages\ListContributionCharges;
use App\Filament\Resources\ContributionCharges\Pages\ViewContributionCharge;
use App\Filament\Resources\ContributionCharges\Schemas\ContributionChargeInfolist;
use App\Filament\Resources\ContributionCharges\Tables\ContributionChargesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ContributionChargeResource extends Resource
{
    protected static ?string $model = ContributionCharge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?string $navigationLabel = 'Forderungen';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Forderung';

    protected static ?string $pluralModelLabel = 'Forderungen';

    protected static ?string $recordTitleAttribute = 'description';

    public static function infolist(Schema $schema): Schema
    {
        return ContributionChargeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContributionChargesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['member', 'contributionType']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContributionCharges::route('/'),
            'view' => ViewContributionCharge::route('/{record}'),
        ];
    }
}
