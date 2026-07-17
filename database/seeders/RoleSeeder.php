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
            User::PERMISSION_ACCESS_ETIQUETADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
            'ver analisis',
            'crear analisis',
            'editar analisis',
            User::PERMISSION_DELETE_ANALYSIS,
            User::PERMISSION_CLOSE_LAVADORA_DAMAGE,
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
            User::PERMISSION_EDIT_ANALYSIS_DATE,
            User::PERMISSION_VIEW_LAVADORA_COST_MODULE,
            User::PERMISSION_ACCESS_LAVADORA_COSTS,
            User::PERMISSION_CREATE_LAVADORA_COSTS,
            User::PERMISSION_EDIT_LAVADORA_COSTS,
            User::PERMISSION_DELETE_LAVADORA_COSTS,
            User::PERMISSION_MANAGE_LAVADORA_COSTS,
            User::customAccessControlPermissionName(),
            ...User::configurablePermissionNames(),
        ];

        // Crear permisos con guard web
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $todosLosPermisos = Permission::all();
        $permisosPasteurizadora = [
            User::PERMISSION_ACCESS_PASTEURIZADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
        ];
        $permisosFechaAnalisis = [
            User::PERMISSION_EDIT_ANALYSIS_DATE,
        ];
        $permisosEspecialesUsuario = [
            ...User::configurablePermissionNames(),
        ];
        $permisosGerenteExcluidos = array_merge(
            $permisosPasteurizadora,
            $permisosFechaAnalisis,
            $permisosEspecialesUsuario
        );
        $permisosSinPasteurizadora = Permission::whereNotIn('name', $permisosGerenteExcluidos)->get();
        $permisosSupervisor = Permission::whereNotIn('name', array_merge($permisosPasteurizadora, $permisosEspecialesUsuario))->get();
        $permisosTecnico = [
            User::PERMISSION_ACCESS_LAVADORA,
            User::PERMISSION_ACCESS_ETIQUETADORA,
            'ver analisis', 'crear analisis', 'editar analisis',
            User::PERMISSION_EDIT_ANALYSIS_DATE,
        ];

        // Roles y permisos
        $roles = [
            User::ROLE_ADMIN => $todosLosPermisos,
            User::ROLE_GERENTE_MANTENIMIENTO => $permisosSinPasteurizadora,
            User::ROLE_SUPERVISOR => $permisosSupervisor,
            User::ROLE_INGENIERO_MANTENIMIENTO => $permisosTecnico,
            User::ROLE_TECNICO => $permisosTecnico,
            User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO => $permisosSupervisor,
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
