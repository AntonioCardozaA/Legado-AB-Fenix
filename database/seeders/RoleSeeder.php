<?php

namespace Database\Seeders;

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

        // Roles y permisos
        $roles = [
            'admin' => Permission::all(),
            'ingeniero_mantenimiento' => [
                'ver analisis', 'crear analisis', 'editar analisis', 'exportar analisis',
                'ver paros', 'crear paros', 'editar paros', 'gestionar planes accion',
                'ver reportes', 'generar reportes', 'exportar reportes',
            ],
            'supervisor' => [
                'ver analisis', 'crear analisis', 'exportar analisis',
                'ver paros', 'gestionar planes accion',
                'ver reportes', 'generar reportes',
            ],
            'tecnico' => [
                'ver analisis', 'crear analisis',
                'ver paros',
                'ver reportes',
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
