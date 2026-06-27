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

        $permissions = $this->technicianPermissionsFromDatabase()
            ?? $this->defaultTechnicianPermissions();

        $this->syncEngineerPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->syncEngineerPermissions($this->previousEngineerPermissions());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function technicianPermissionsFromDatabase(): ?array
    {
        $technicianRole = Role::query()
            ->where('name', User::ROLE_TECNICO)
            ->where('guard_name', 'web')
            ->first();

        return $technicianRole
            ? $technicianRole->permissions()->pluck('name')->all()
            : null;
    }

    private function syncEngineerPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::firstOrCreate([
            'name' => User::ROLE_INGENIERO_MANTENIMIENTO,
            'guard_name' => 'web',
        ])->syncPermissions($permissions);
    }

    private function defaultTechnicianPermissions(): array
    {
        return [
            User::PERMISSION_ACCESS_LAVADORA,
            'ver analisis',
            'crear analisis',
            'editar analisis',
            User::PERMISSION_EDIT_ANALYSIS_DATE,
        ];
    }

    private function previousEngineerPermissions(): array
    {
        return [
            User::PERMISSION_ACCESS_LAVADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
            'ver analisis',
            'crear analisis',
            'editar analisis',
            'exportar analisis',
            'ver paros',
            'crear paros',
            'editar paros',
            'gestionar planes accion',
            'ver reportes',
            'generar reportes',
            'exportar reportes',
        ];
    }
};
