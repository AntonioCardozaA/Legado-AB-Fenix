<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $adminUserId = $this->resolveAdminUserId();

        if (!$adminUserId) {
            return;
        }

        foreach (['analisis_pasteurizadora', 'analisis_componentes'] as $table) {
            if (!Schema::hasColumn($table, 'usuario_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('usuario_id')
                ->update(['usuario_id' => $adminUserId]);
        }
    }

    public function down(): void
    {
        // Data backfill only. Do not erase authorship on rollback.
    }

    private function resolveAdminUserId(): ?int
    {
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

            if ($adminRoleId) {
                $adminUserId = DB::table('model_has_roles')
                    ->where('role_id', $adminRoleId)
                    ->where('model_type', 'App\\Models\\User')
                    ->value('model_id');

                if ($adminUserId) {
                    return (int) $adminUserId;
                }
            }
        }

        if (Schema::hasTable('users')) {
            $adminUserId = DB::table('users')
                ->where('email', 'admin@legadoabfenix.com')
                ->value('id');

            return $adminUserId ? (int) $adminUserId : null;
        }

        return null;
    }
};
