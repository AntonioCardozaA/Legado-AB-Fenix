<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([...User::configurablePermissionNames(), User::customAccessControlPermissionName()] as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        Role::where('name', User::ROLE_ADMIN)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(User::configurablePermissionNames()));
    }

    public function down(): void
    {
        $costPermissionNames = User::lavadoraCostPermissionNames();

        Role::query()
            ->whereHas('permissions', fn ($query) => $query->whereIn('name', $costPermissionNames))
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($costPermissionNames));

        Permission::query()
            ->whereIn('name', $costPermissionNames)
            ->delete();
    }
};
