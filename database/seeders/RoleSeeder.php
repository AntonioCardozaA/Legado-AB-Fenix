<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Limpiar cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Lista de permisos
        $permisos = [
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

        // Crear permisos con guard web
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $todosLosPermisos = Permission::all();
        $permisosSinPasteurizadora = Permission::where('name', '!=', User::PERMISSION_ACCESS_PASTEURIZADORA)->get();

        // Roles y permisos
        $roles = [
            User::ROLE_ADMIN => $todosLosPermisos,
            User::ROLE_GERENTE_MANTENIMIENTO => $permisosSinPasteurizadora,
            User::ROLE_SUPERVISOR => $permisosSinPasteurizadora,
            User::ROLE_INGENIERO_MANTENIMIENTO => [
                User::PERMISSION_ACCESS_LAVADORA,
                'ver analisis', 'crear analisis', 'editar analisis', 'exportar analisis',
                'ver paros', 'crear paros', 'editar paros', 'gestionar planes accion',
                'ver reportes', 'generar reportes', 'exportar reportes',
            ],
            User::ROLE_TECNICO => [
                User::PERMISSION_ACCESS_LAVADORA,
                'ver analisis', 'crear analisis', 'editar analisis',
            ],
        ];

        foreach ($roles as $rol => $perms) {
            $role = Role::firstOrCreate([
                'name' => $rol,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($perms);
        }
    }
}
