<?php

namespace App\Filament\Resources\ClubFunctions;

use App\Domain\Membership\Models\ClubFunction;
use App\Filament\Resources\ClubFunctions\Pages\ManageClubFunctions;
use App\Filament\Resources\ClubFunctions\Schemas\ClubFunctionForm;
use App\Filament\Resources\ClubFunctions\Tables\ClubFunctionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class ClubFunctionResource extends Resource
{
    protected static ?string $model =
        ClubFunction::class;

    protected static ?string $modelLabel =
        'Vereinsfunktion';

    protected static ?string $pluralModelLabel =
        'Vereinsfunktionen';

    protected static ?string $navigationLabel =
        'Vereinsfunktionen';

    protected static string|UnitEnum|null $navigationGroup =
        'Stammdaten';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-briefcase';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function form(
        Schema $schema,
    ): Schema {
        return ClubFunctionForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table,
    ): Table {
        return ClubFunctionsTable::configure(
            $table
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClubFunctions::route('/'),
        ];
    }
}
