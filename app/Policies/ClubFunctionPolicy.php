<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\ClubFunction;
use App\Models\User;

final readonly class ClubFunctionPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::ClubFunctionsManage->value
            );
    }

    public function view(
        User $user,
        ClubFunction $function,
    ): bool {
        return $this->belongsToCurrentClub(
            $function
        ) && $user->can(
            Permission::ClubFunctionsManage->value
        );
    }

    public function create(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::ClubFunctionsManage->value
            );
    }

    public function update(
        User $user,
        ClubFunction $function,
    ): bool {
        return $this->belongsToCurrentClub(
            $function
        ) && $user->can(
            Permission::ClubFunctionsManage->value
        );
    }

    public function delete(
        User $user,
        ClubFunction $function,
    ): bool {
        return false;
    }

    public function deleteAny(
        User $user,
    ): bool {
        return false;
    }

    private function belongsToCurrentClub(
        ClubFunction $function,
    ): bool {
        return $this->currentClub->hasClub()
            && (string) $function->club_id ===
            $this->currentClub->id();
    }
}
