<?php

namespace App\Http\Controllers;

use App\Domain\Club\Models\ClubInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ClubInvitationController
{
    public function show(
        Request $request,
        string $token,
    ): RedirectResponse {
        $invitation = ClubInvitation::query()
            ->where(
                'token_hash',
                hash(
                    'sha256',
                    $token
                )
            )
            ->first();

        abort_if(
            $invitation === null,
            404,
        );

        abort_unless(
            $invitation->isUsable(),
            410,
            'Diese Einladung ist nicht mehr gültig.'
        );

        $request->session()->put(
            'pending_club_invitation_token',
            $token,
        );

        $request->session()->put(
            'pending_club_invitation_email',
            $invitation->email,
        );

        if ($request->user() !== null) {
            return redirect(
                '/admin'
            );
        }

        $userExists = User::query()
            ->where(
                'email',
                $invitation->email
            )
            ->exists();

        return redirect(
            $userExists
                ? '/admin/login'
                : '/admin/register'
        );
    }
}
