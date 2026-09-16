<?php

namespace App\Filament\Resources\ClubInvitations;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\ClubInvitation;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ClubInvitations\Pages\ManageClubInvitations;
use App\Filament\Resources\ClubInvitations\Tables\ClubInvitationsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

final class ClubInvitationResource extends Resource
{
    protected static ?string $model =
        ClubInvitation::class;

    protected static bool $isScopedToTenant =
        false;

    protected static ?string $modelLabel =
        'Einladung';

    protected static ?string $pluralModelLabel =
        'Einladungen';

    protected static ?string $navigationLabel =
        'Einladungen';

    protected static string|UnitEnum|null $navigationGroup =
        'Administration';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-envelope';

    protected static ?int $navigationSort = 20;

    public static function table(
        Table $table,
    ): Table {
        return ClubInvitationsTable::configure(
            $table
        );
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->can(
                Permission::ClubUsersView->value
            );
    }

    /**
     * @return Builder<ClubInvitation>
     */
    public static function getEloquentQuery(): Builder
    {
        $club = app(
            CurrentClub::class
        );

        if (! $club->hasClub()) {
            return ClubInvitation::query()
                ->whereRaw('1 = 0');
        }

        return ClubInvitation::query()
            ->where(
                'club_id',
                $club->id()
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClubInvitations::route('/'),
        ];
    }
}
