<?php

namespace App\Application\Club;

use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Role;

class CreateDefaultClubRoles
{
    public function handle(Club $club): void
    {
        setPermissionsTeamId($club->id);

        $administrator = Role::findOrCreate(
            RoleEnum::Administrator->value,
            'web',
        );

        $administrator->syncPermissions(
            array_map(
                static fn (Permission $permission) => $permission->value,
                Permission::cases()
            )
        );

        $member = Role::findOrCreate(
            RoleEnum::Member->value,
            'web',
        );

        $member->syncPermissions([
            Permission::MembersView->value,
        ]);
    }
}
