<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Department;
use App\Models\User;

final readonly class DepartmentPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::DepartmentsManage->value
            );
    }

    public function view(
        User $user,
        Department $department,
    ): bool {
        return $this->belongsToCurrentClub(
            $department
        ) && $user->can(
            Permission::DepartmentsManage->value
        );
    }

    public function create(
        User $user,
    ): bool {
        return $this->currentClub->hasClub()
            && $user->can(
                Permission::DepartmentsManage->value
            );
    }

    public function update(
        User $user,
        Department $department,
    ): bool {
        return $this->belongsToCurrentClub(
            $department
        ) && $user->can(
            Permission::DepartmentsManage->value
        );
    }

    public function delete(
        User $user,
        Department $department,
    ): bool {
        return false;
    }

    public function deleteAny(
        User $user,
    ): bool {
        return false;
    }

    private function belongsToCurrentClub(
        Department $department,
    ): bool {
        return $this->currentClub->hasClub()
            && (string) $department->club_id ===
            $this->currentClub->id();
    }
}
