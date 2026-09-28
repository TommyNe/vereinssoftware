<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

class ContributionTypePolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user
    ): bool {
        return $this->currentClub->hasClub() && $user->can(
            Permission::ContributionTypesManage
                ->value
        );
    }

    public function view(
        User $user,
        ContributionType $type,
    ): bool {
        return $this->currentClub->hasClub() &&
            (string) $type->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::ContributionTypesManage
                    ->value
            );
    }

    public function create(
        User $user
    ): bool {
        return $this->currentClub->hasClub() && $user->can(
            Permission::ContributionTypesManage
                ->value
        );
    }

    public function update(
        User $user,
        ContributionType $type,
    ): bool {
        return $this->view(
            $user,
            $type
        );
    }

    public function delete(
        User $user,
        ContributionType $type,
    ): bool {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
