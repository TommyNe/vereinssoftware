<?php

namespace App\Http\Middleware;

use App\Application\Club\ClubInvitationManager;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AcceptPendingClubInvitation
{
    public function __construct(
        private ClubInvitationManager $manager,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $token = $request
            ->session()
            ->get(
                'pending_club_invitation_token'
            );

        if (! is_string($token)) {
            return $next($request);
        }

        $this->manager->accept(
            token: $token,
            user: $user,
        );

        $request->session()->forget([
            'pending_club_invitation_token',
            'pending_club_invitation_email',
        ]);

        return $next($request);
    }
}
