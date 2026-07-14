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

        $permission = Permission::firstOrCreate([
            'name' => User::PERMISSION_ACCESS_ETIQUETADORA,
            'guard_name' => 'web',
        ]);

        Role::whereIn('name', [
            User::ROLE_ADMIN,
            User::ROLE_GERENTE_MANTENIMIENTO,
            ...User::technicianEquivalentRoles(),
            ...User::supervisorEquivalentRoles(),
        ])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('name', User::PERMISSION_ACCESS_ETIQUETADORA)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
