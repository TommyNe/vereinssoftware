<?php

namespace App\Filament\Pages;

use App\Filament\Actions\LogoutOtherDevicesAction;
use App\Models\User;
use Auth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

final class Security extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup =
        'Konto';

    protected static ?string $navigationLabel =
        'Sicherheit';

    protected static ?string $title =
        'Sicherheit';

    protected static ?int $navigationSort =
        110;

    protected string $view =
        'filament.pages.security';

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            LogoutOtherDevicesAction::make(),
        ];
    }

    /**
     * @return Collection<int, \stdClass>
     */
    public function getSessions(): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        return DB::table('sessions')
            ->where(
                'user_id',
                $user->getKey()
            )
            ->orderByDesc(
                'last_activity'
            )
            ->get();
    }

    public function isCurrentSession(
        string $sessionId,
    ): bool {
        return hash_equals(
            session()->getId(),
            $sessionId,
        );
    }

    public function revokeSession(
        string $sessionId,
    ): void {
        $user = Auth::user();

        abort_unless(
            $user instanceof User,
            403,
        );

        abort_if(
            hash_equals(
                session()->getId(),
                $sessionId,
            ),
            422,
            'Die aktuelle Sitzung kann hier nicht beendet werden.',
        );

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where(
                'user_id',
                $user->getKey(),
            )
            ->delete();
    }
}
