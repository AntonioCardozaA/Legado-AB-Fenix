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
            'name' => User::PERMISSION_CAPTURE_PASTEURIZADORA_EXCENTRICOS,
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => User::ROLE_CAPTURISTA_EXCENTRICOS,
            'guard_name' => 'web',
        ])->syncPermissions([$permission]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', User::ROLE_CAPTURISTA_EXCENTRICOS)
            ->where('guard_name', 'web')
            ->delete();

        Permission::where('name', User::PERMISSION_CAPTURE_PASTEURIZADORA_EXCENTRICOS)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
