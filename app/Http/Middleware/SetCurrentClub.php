<?php

namespace App\Http\Middleware;

use App\Application\Club\CurrentClub;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetCurrentClub
{
    public function __construct(
        private CurrentClub $currentClub
    ){
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }
        $clubId = session('current_club_id');
        if ($clubId === null) {
            $club = $user->clubs()->first();

            if ($club === null) {
                abort(403, 'Benutzer gehört keinem Verein an.');
            }

            $clubId = $club->id;
            session(['current_club_id' => $clubId]);
        }

        $club = $user
            ->clubs()
            ->whereKey($clubId)
            ->first();

        if ($club === null) {
            abort(403, 'Kein Zugriff auf den ausgewählten Verein.');
        }

        $this->currentClub->set($club);

        setPermissionsTeamId($club->id);

        $user
            ->unsetRelation('roles')
            ->unsetRelation('permissions');

        return $next($request);
    }
}
