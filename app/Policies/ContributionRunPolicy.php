<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionRun;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

final readonly class ContributionRunPolicy
{
    public function __construct(private CurrentClub $currentClub) {}

    public function viewAny(
        User $user
    ): bool {
        return $user->can(
            Permission::ContributionRunsView
                ->value
        );
    }

    public function view(
        User $user,
        ContributionRun $run,
    ): bool {
        return
            (string) $run->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::ContributionRunsView
                    ->value
            );
    }

    public function create(
        User $user
    ): bool {
        return $user->can(
            Permission::ContributionRunsCreate
                ->value
        );
    }

    public function update(
        User $user,
        ContributionRun $run,
    ): bool {
        return false;
    }

    public function delete(
        User $user,
        ContributionRun $run,
    ): bool {
        return false;
    }
}
