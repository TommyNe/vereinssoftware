<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\ClubInvitation;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

final readonly class ClubInvitationPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::ClubUsersView->value
            );
    }

    public function view(
        User $user,
        ClubInvitation $invitation,
    ): bool {
        return $this->belongsToCurrentClub(
            $invitation
        ) && $user->can(
            Permission::ClubUsersView->value
        );
    }

    public function resend(
        User $user,
        ClubInvitation $invitation,
    ): bool {
        return $this->belongsToCurrentClub(
            $invitation
        ) && $user->can(
            Permission::ClubUsersManage->value
        );
    }

    public function revoke(
        User $user,
        ClubInvitation $invitation,
    ): bool {
        return $this->belongsToCurrentClub(
            $invitation
        ) && $user->can(
            Permission::ClubUsersManage->value
        );
    }

    public function create(
        User $user,
    ): bool {
        return false;
    }

    public function update(
        User $user,
        ClubInvitation $invitation,
    ): bool {
        return false;
    }

    public function delete(
        User $user,
        ClubInvitation $invitation,
    ): bool {
        return false;
    }

    private function belongsToCurrentClub(
        ClubInvitation $invitation,
    ): bool {
        return $this->currentClub->hasClub()
            && (string) $invitation->club_id
            === $this->currentClub->id();
    }
}
