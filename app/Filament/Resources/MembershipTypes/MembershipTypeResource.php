<?php

namespace App\Filament\Resources\MembershipTypes;

use App\Domain\Membership\Models\MembershipType;
use App\Filament\Resources\MembershipTypes\Pages\ManageMembershipTypes;
use App\Filament\Resources\MembershipTypes\Schemas\MembershipTypeForm;
use App\Filament\Resources\MembershipTypes\Tables\MembershipTypesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class MembershipTypeResource extends Resource
{
    protected static ?string $model =
        MembershipType::class;

    protected static ?string $modelLabel =
        'Mitgliedsart';

    protected static ?string $pluralModelLabel =
        'Mitgliedsarten';

    protected static ?string $navigationLabel =
        'Mitgliedsarten';

    protected static string|UnitEnum|null $navigationGroup =
        'Stammdaten';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-tag';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function form(
        Schema $schema,
    ): Schema {
        return MembershipTypeForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table,
    ): Table {
        return MembershipTypesTable::configure(
            $table
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMembershipTypes::route('/'),
        ];
    }
}
