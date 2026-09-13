<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $administrator = Role::query()
            ->firstOrCreate([
                'name' => RoleEnum::Administrator->value,
                'guard_name' => 'web',
                'club_id' => null,
            ]);

        $administrator->syncPermissions(
            array_map(
                static fn (
                    Permission $permission
                ): string => $permission->value,
                Permission::cases(),
            )
        );
    }
}
