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
            User::PERMISSION_ACCESS_LAVADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA,
            'ver analisis',
            'crear analisis',
            'editar analisis',
            'eliminar analisis',
            'exportar analisis',
            'ver paros',
            'crear paros',
            'editar paros',
            'eliminar paros',
            'gestionar planes accion',
            'ver reportes',
            'generar reportes',
            'exportar reportes',
            'gestionar lineas',
            'gestionar componentes',
            'gestionar usuarios',
            'gestionar configuracion',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $allPermissions = Permission::all();
        $permissionsExceptPasteurizadora = Permission::where('name', '!=', User::PERMISSION_ACCESS_PASTEURIZADORA)->get();

        Role::firstOrCreate(['name' => User::ROLE_ADMIN, 'guard_name' => 'web'])
            ->syncPermissions($allPermissions);

        Role::firstOrCreate(['name' => User::ROLE_GERENTE_MANTENIMIENTO, 'guard_name' => 'web'])
            ->syncPermissions($permissionsExceptPasteurizadora);

        Role::firstOrCreate(['name' => User::ROLE_SUPERVISOR, 'guard_name' => 'web'])
            ->syncPermissions($permissionsExceptPasteurizadora);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', User::ROLE_GERENTE_MANTENIMIENTO)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
