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
         * 1. Filament-Tenant hat Priorität.
         *
         * Innerhalb eines tenantfähigen Filament-Panels liefert
         * Filament::getTenant() den aktuell ausgewählten Club.
         */
        $filamentTenant = Filament::getTenant();

        if ($filamentTenant instanceof Club) {
            $this->activateClub(
                user: $user,
                club: $filamentTenant,
            );

            return $next($request);
        }

        /*
         * 2. Außerhalb von Filament verwenden wir weiterhin
         * den bisherigen Session-basierten Club-Kontext.
         */
        $clubId = $request
            ->session()
            ->get('current_club_id');

        /*
         * Benutzer ohne Verein sind erlaubt.
         *
         * Das ist wichtig für den neuen Filament-Onboarding-Flow:
         * Login -> Verein anlegen.
         */
        if ($clubId === null) {
            $club = $user
                ->clubs()
                ->first();

            if (! $club instanceof Club) {
                return $next($request);
            }

            $clubId = (string) $club->getKey();

            $request
                ->session()
                ->put(
                    'current_club_id',
                    $clubId,
                );
        }

        /*
         * Sicherheitsprüfung:
         *
         * Niemals einfach Club::find($clubId) verwenden.
         *
         * Der Club muss tatsächlich dem angemeldeten
         * Benutzer zugeordnet sein.
         */
        $club = $user
            ->clubs()
            ->whereKey($clubId)
            ->first();

        if (! $club instanceof Club) {
            /*
             * Ungültiger/veralteter Club in der Session.
             */
            $request
                ->session()
                ->forget('current_club_id');

            return $next($request);
        }

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
        /*
         * Unsere bestehende Application-Schicht.
         */
        $this->currentClub->set($club);

        /*
         * Spatie Permission Teams:
         * Der Club ist unser Team.
         */
        setPermissionsTeamId(
            $club->getKey()
        );

        /*
         * Sehr wichtig nach einem Team-Wechsel.
         *
         * Sonst könnten Rollen/Permissions aus dem vorherigen
         * Club noch im geladenen Eloquent Model vorhanden sein.
         */
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
}
