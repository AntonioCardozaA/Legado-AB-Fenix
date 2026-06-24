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

        $permissions = $this->technicianPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (User::technicianEquivalentRoles() as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $technicianPermissions = [
            User::PERMISSION_ACCESS_LAVADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
            'ver analisis',
            'crear analisis',
            'editar analisis',
        ];

        $programadorPermissions = [
            User::PERMISSION_ACCESS_LAVADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
            'ver analisis',
            'crear analisis',
        ];

        foreach (array_unique(array_merge($technicianPermissions, $programadorPermissions)) as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::where('name', User::ROLE_TECNICO)
            ->where('guard_name', 'web')
            ->first()
            ?->syncPermissions($technicianPermissions);

        Role::where('name', User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO)
            ->where('guard_name', 'web')
            ->first()
            ?->syncPermissions($programadorPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function technicianPermissions(): array
    {
        return [
            User::PERMISSION_ACCESS_LAVADORA,
            'ver analisis',
            'crear analisis',
            'editar analisis',
        ];
    }
};
