<?php

namespace App\Filament\Resources\ClubUsers;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ClubUsers\Pages\ManageClubUsers;
use App\Filament\Resources\ClubUsers\Tables\ClubUsersTable;
use App\Models\User;
use Auth;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class ClubUserResource extends Resource
{
    protected static ?string $model =
        User::class;

    /*
     * User ist global und gehört mehreren Clubs.
     * Deshalb verwenden wir NICHT Filaments
     * automatisches Tenant-Scoping.
     */
    protected static bool $isScopedToTenant =
        false;

    protected static ?string $modelLabel =
        'Benutzer';

    protected static ?string $pluralModelLabel =
        'Benutzer';

    protected static ?string $navigationLabel =
        'Benutzer';

    protected static string|UnitEnum|null $navigationGroup =
        'Administration';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-users';

    protected static ?int $navigationSort = 10;

    public static function table(
        Table $table,
    ): Table {
        return ClubUsersTable::configure(
            $table
        );
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        $club = app(
            CurrentClub::class
        );

        if (! $club->hasClub()) {
            return User::query()
                ->whereRaw('1 = 0');
        }

        return User::query()
            ->whereHas(
                'clubs',
                static fn (
                    Builder $query
                ): Builder => $query->whereKey(
                    $club->id()
                )
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClubUsers::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can(
            Permission::ClubUsersView->value
        );
    }
}
