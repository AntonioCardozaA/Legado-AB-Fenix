<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('analysis_type');
            $table->string('analysis_model');
            $table->string('analysis_table');
            $table->unsignedBigInteger('deleted_record_id');
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
            $table->string('linea_nombre')->nullable();
            $table->string('tipo_analisis')->nullable();
            $table->timestamp('deleted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['analysis_type', 'deleted_record_id']);
            $table->index('deleted_at');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => User::PERMISSION_DELETE_ANALYSIS,
            'guard_name' => 'web',
        ]);

        Role::where('name', User::ROLE_ADMIN)
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permission);

        Role::where('name', '!=', User::ROLE_ADMIN)
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::whereIn('name', [
            User::ROLE_GERENTE_MANTENIMIENTO,
            ...User::supervisorEquivalentRoles(),
        ])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role): void {
                $permission = Permission::where('name', User::PERMISSION_DELETE_ANALYSIS)
                    ->where('guard_name', 'web')
                    ->first();

                if ($permission) {
                    $role->givePermissionTo($permission);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('analysis_deletion_logs');
    }
};
