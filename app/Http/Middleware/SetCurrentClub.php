<?php

namespace App\Http\Middleware;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetCurrentClub
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        /*
         * Filament-Tenant verwenden,
         * sobald Filament ihn aufgelöst hat.
         */
        $tenant = Filament::getTenant();

        if ($tenant instanceof Club) {
            $this->activateClub(
                user: $user,
                club: $tenant,
            );

            return $next($request);
        }

        /*
         * Außerhalb von Filament:
         * bisheriger API-/Session-Flow.
         */
        $clubId = $request
            ->session()
            ->get('current_club_id');

        if ($clubId !== null) {
            $club = $user
                ->clubs()
                ->whereKey($clubId)
                ->first();

            if ($club instanceof Club) {
                $this->activateClub(
                    user: $user,
                    club: $club,
                );

                return $next($request);
            }

            $request
                ->session()
                ->forget(
                    'current_club_id'
                );
        }

        /*
         * Fallback auf den ersten Club.
         */
        $club = $user
            ->clubs()
            ->first();

        if (! $club instanceof Club) {
            /*
             * Kein Club ist beim Onboarding erlaubt.
             */
            return $next($request);
        }

        $request
            ->session()
            ->put(
                'current_club_id',
                $club->getKey()
            );

        $this->activateClub(
            user: $user,
            club: $club,
        );

        return $next($request);
    }

    private function activateClub(
        User $user,
        Club $club,
    ): void {
        $this->currentClub->set(
            $club
        );

        setPermissionsTeamId(
            $club->getKey()
        );

        $user
            ->unsetRelation('roles')
            ->unsetRelation('permissions');
    }
}
