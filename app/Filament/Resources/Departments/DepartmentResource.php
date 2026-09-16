<?php

namespace App\Filament\Resources\Departments;

use App\Domain\Membership\Models\Department;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Tables\DepartmentsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class DepartmentResource extends Resource
{
    protected static ?string $model =
        Department::class;

    protected static ?string $modelLabel =
        'Abteilung';

    protected static ?string $pluralModelLabel =
        'Abteilungen';

    protected static ?string $navigationLabel =
        'Abteilungen';

    protected static string|UnitEnum|null $navigationGroup =
        'Stammdaten';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-building-office';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function form(
        Schema $schema,
    ): Schema {
        return DepartmentForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table,
    ): Table {
        return DepartmentsTable::configure(
            $table
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDepartments::route('/'),
        ];
    }
}
