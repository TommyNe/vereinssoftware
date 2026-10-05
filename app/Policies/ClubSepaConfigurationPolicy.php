<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Models\User;

final readonly class ClubSepaConfigurationPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {
    }

    public function viewAny(
        User $user,
    ): bool {
        return $user->can(
            Permission::SepaConfigurationView
                ->value
        );
    }

    public function view(
        User $user,
        ClubSepaConfiguration $configuration,
    ): bool {
        return
            (string) $configuration->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::SepaConfigurationView
                    ->value
            );
    }

    public function create(
        User $user,
    ): bool {
        return $user->can(
            Permission::SepaConfigurationManage
                ->value
        );
    }

    public function update(
        User $user,
        ClubSepaConfiguration $configuration,
    ): bool {
        return
            (string) $configuration->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::SepaConfigurationManage
                    ->value
            );
    }

    public function delete(
        User $user,
        ClubSepaConfiguration $configuration,
    ): bool {
        return false;
    }
}
