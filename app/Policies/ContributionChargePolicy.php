<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

readonly class ContributionChargePolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function view(
        User $user,
        ContributionCharge $charge,
    ): bool {
        return
            $this->currentClub->hasClub()
            &&
            (string) $charge->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::ContributionChargesView
                    ->value
            );
    }

    public function create(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::ContributionChargesManage
                    ->value
            );
    }

    public function update(
        User $user,
        ContributionCharge $charge,
    ): bool {
        return
            $this->currentClub->hasClub()
            &&
            (string) $charge->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::ContributionChargesManage
                    ->value
            );
    }

    public function delete(
        User $user,
        ContributionCharge $charge,
    ): bool {
        return false;
    }
}
