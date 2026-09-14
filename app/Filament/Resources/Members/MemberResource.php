<?php

namespace App\Filament\Resources\Members;

use App\Domain\Membership\Models\Member;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Filament\Resources\Members\Schemas\MemberInfolist;
use App\Filament\Resources\Members\Tables\MembersTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $modelLabel = 'Mitglied';

    protected static ?string $pluralModelLabel = 'Mitglieder';

    protected static ?string $navigationLabel = 'Mitglieder';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute =
        'full_name';

    public static function table(
        Table $table,
    ): Table {
        return MembersTable::configure($table);
    }

    public static function infolist(
        Schema $schema,
    ): Schema {
        return MemberInfolist::configure($schema);
    }

    /**
     * @return Builder<Member>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Member> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->forCurrentClub()
            ->with([
                'membershipType',
                'departments',
                'activeFunctionAssignments.clubFunction',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'view' => ViewMember::route('/{record}'),
        ];
    }
}
