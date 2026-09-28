<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

final readonly class ContributionRatePolicy
{
    public function __construct(private CurrentClub $currentClub) {}

    public function viewAny(User $user): bool
    {
        return $this->currentClub->hasClub()
            && $user->can(Permission::ContributionRatesManage->value);
    }

    public function view(User $user, ContributionRate $rate): bool
    {
        return $this->viewAny($user)
            && (string) $rate->club_id === $this->currentClub->id();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ContributionRate $rate): bool
    {
        return $this->view($user, $rate);
    }

    public function delete(User $user, ContributionRate $rate): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ContributionRate $rate): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContributionRate $rate): bool
    {
        return false;
    }
}
