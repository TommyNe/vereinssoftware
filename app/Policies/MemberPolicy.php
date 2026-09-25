<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Models\User;

final readonly class MemberPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user,
    ): bool {
        if (! $this->currentClub->hasClub()) {
            return false;
        }

        return $user->can(
            Permission::MembersView->value
        );
    }

    public function view(
        User $user,
        Member $member,
    ): bool {
        if (! $this->currentClub->hasClub()) {
            return false;
        }

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

    public function create(
        User $user,
    ): bool {
        return false;
    }

    public function update(
        User $user,
        Member $member,
    ): bool {
        return false;
    }

    public function delete(
        User $user,
        Member $member,
    ): bool {
        return false;
    }

    public function deleteAny(
        User $user,
    ): bool {
        return false;
    }

    public function restore(
        User $user,
        Member $member,
    ): bool {
        return false;
    }

    public function forceDelete(
        User $user,
        Member $member,
    ): bool {
        return false;
    }

    public function changeData(
        User $user,
        Member $member,
    ): bool {
        if (! $this->currentClub->hasClub()) {
            return false;
        }

        if (
            (string) $member->club_id !==
            $this->currentClub->id()
        ) {
            return false;
        }

        return $user->can(
            Permission::MembersUpdate->value
        );
    }

    public function manageDepartments(
        User $user,
        Member $member,
    ): bool {
        return $this->belongsToCurrentClub($member)
            && $user->can(
                Permission::MembersDepartmentsManage->value
            );
    }

    public function manageFunctions(
        User $user,
        Member $member,
    ): bool {
        return $this->belongsToCurrentClub($member)
            && $user->can(
                Permission::MembersFunctionsManage->value
            );
    }

    public function manageMembership(
        User $user,
        Member $member,
    ): bool {
        return $this->belongsToCurrentClub($member)
            && $user->can(
                Permission::MembersMembershipManage->value
            );
    }

    private function belongsToCurrentClub(
        Member $member,
    ): bool {
        if (! $this->currentClub->hasClub()) {
            return false;
        }

        return (string) $member->club_id ===
            $this->currentClub->id();
    }

    public function viewDocuments(
        User $user,
        Member $member,
    ): bool {
        return $this->belongsToCurrentClub(
                $member
            ) && $user->can(
                Permission::MembersDocumentsView->value
            );
    }

    public function manageDocuments(
        User $user,
        Member $member,
    ): bool {
        return $this->belongsToCurrentClub(
                $member
            ) && $user->can(
                Permission::MembersDocumentsManage->value
            );
    }
}
