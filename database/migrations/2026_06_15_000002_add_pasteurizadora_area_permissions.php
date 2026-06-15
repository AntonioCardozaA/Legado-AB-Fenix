<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::where('name', User::ROLE_ADMIN)
            ->first()
            ?->givePermissionTo($permissions);

        Role::whereIn('name', [
            User::ROLE_INGENIERO_MANTENIMIENTO,
            User::ROLE_TECNICO,
        ])->get()->each(function (Role $role) use ($permissions) {
            $role->givePermissionTo($permissions);
        });

        $pasteurizadoraPermission = Permission::where('name', User::PERMISSION_ACCESS_PASTEURIZADORA)
            ->where('guard_name', 'web')
            ->first();

        if ($pasteurizadoraPermission) {
            Role::whereHas('permissions', function ($query) {
                $query->where('name', User::PERMISSION_ACCESS_PASTEURIZADORA)
                    ->where('guard_name', 'web');
            })->get()->each(function (Role $role) use ($permissions) {
                $role->givePermissionTo($permissions);
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', [
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
