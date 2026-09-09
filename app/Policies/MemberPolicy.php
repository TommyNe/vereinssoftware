<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct(
        private CurrentClub $currentClub,
    ){}

    public function view(
        User $user,
        Member $member,
    ): bool
    {
        if(
            $member->club_id !== $this->currentClub->id()
        ) {
            return false;
        }

        return $user->can(
            Permission::MembersView->value
        );
    }

    public function update(
        User $user,
        Member $member,
    ): bool {
        if (
            $member->club_id !== $this->currentClub->id()
        ) {
            return false;
        }

        return $user->can(
            Permission::MembersUpdate->value
        );
    }

    public function create(User $user): bool
    {
        return $user->can(
            Permission::MembersCreate->value
        );
    }

}
