<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionRunError;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

final readonly class ContributionRunErrorPolicy
{
    public function __construct(private CurrentClub $currentClub) {}

    public function viewAny(User $user): bool
    {
        return $this->currentClub->hasClub()
            && $user->can(Permission::ContributionRunsView->value);
    }

    public function view(User $user, ContributionRunError $error): bool
    {
        return $this->viewAny($user)
            && (string) $error->contributionRun->club_id === $this->currentClub->id();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContributionRunError $error): bool
    {
        return false;
    }

    public function delete(User $user, ContributionRunError $error): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ContributionRunError $error): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContributionRunError $error): bool
    {
        return false;
    }
}
