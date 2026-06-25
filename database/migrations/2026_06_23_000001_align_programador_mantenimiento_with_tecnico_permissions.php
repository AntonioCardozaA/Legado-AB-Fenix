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

        $permissions = $this->supervisorPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::where('name', User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO)
            ->where('guard_name', 'web')
            ->first()
            ?->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->supervisorPermissions();

        Role::where('name', User::ROLE_PROGRAMADOR_DE_MANTENIMIENTO)
            ->where('guard_name', 'web')
            ->first()
            ?->syncPermissions([
                User::PERMISSION_ACCESS_LAVADORA,
                'ver analisis',
                'crear analisis',
                'editar analisis',
                User::PERMISSION_EDIT_ANALYSIS_DATE,
            ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function supervisorPermissions(): array
    {
        return [
            User::PERMISSION_ACCESS_LAVADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
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
            User::PERMISSION_EDIT_ANALYSIS_DATE,
        ];
    }
};
