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
    ) {}

    public function view(
        User $user,
        Member $member,
    ): bool {
        if (
            $member->club_id !==
            $this->currentClub->id()
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

        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function manageDepartments(
        User $user,
        Member $member,
    ): bool {
        if (
            $member->club_id !==
            $this->currentClub->id()
        ) {
            return false;
        }

        return $user->can(
            Permission::MembersDepartmentsManage->value
        );
    }

    public function manageFunctions(
        User $user,
        Member $member,
    ): bool {
        if (
            $member->club_id !==
            $this->currentClub->id()
        ) {
            return false;
        }

        return $user->can(
            Permission::MembersFunctionsManage->value
        );
    }

    public function viewAny(User $user): bool
    {
        return $user->can(
            Permission::MembersView->value
        );
    }

    public function deleteAny(
        User $user,
    ): bool {
        return false;
    }
}
