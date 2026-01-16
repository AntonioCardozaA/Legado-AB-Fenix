<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Limpiar cache de permisos y roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        // Crear permisos si no existen
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear roles y asignar permisos
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
            $role = Role::firstOrCreate(['name' => $rol]);
            $role->syncPermissions($perms); // Sincroniza permisos y evita duplicados
        }
    }
}
