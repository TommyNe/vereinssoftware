<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\MembershipType;
use App\Models\User;

final readonly class MembershipTypePolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::MembershipTypesManage->value
            );
    }

    public function view(
        User $user,
        MembershipType $type,
    ): bool {
        return $this->belongsToCurrentClub($type)
            && $user->can(
                Permission::MembershipTypesManage->value
            );
    }

    public function create(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::MembershipTypesManage->value
            );
    }

    public function update(
        User $user,
        MembershipType $type,
    ): bool {
        return $this->belongsToCurrentClub($type)
            && $user->can(
                Permission::MembershipTypesManage->value
            );
    }

    public function delete(
        User $user,
        MembershipType $type,
    ): bool {
        return false;
    }

    public function deleteAny(
        User $user,
    ): bool {
        return false;
    }

    private function belongsToCurrentClub(
        MembershipType $type,
    ): bool {
        return $this->currentClub->hasClub()
            && (string) $type->club_id ===
            $this->currentClub->id();
    }
}
