<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_accion')) {
            return;
        }

        Schema::table('plan_accion', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_accion', 'responsable_id')) {
                $table->foreignId('responsable_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('plan_accion', 'registrado_por_id')) {
                $table->foreignId('registrado_por_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('plan_accion', 'ejecutado_por_id')) {
                $table->foreignId('ejecutado_por_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('plan_accion', 'fecha_ejecucion')) {
                $table->timestamp('fecha_ejecucion')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('plan_accion')) {
            return;
        }

        Schema::table('plan_accion', function (Blueprint $table) {
            if (Schema::hasColumn('plan_accion', 'ejecutado_por_id')) {
                $table->dropConstrainedForeignId('ejecutado_por_id');
            }

            if (Schema::hasColumn('plan_accion', 'registrado_por_id')) {
                $table->dropConstrainedForeignId('registrado_por_id');
            }

            if (Schema::hasColumn('plan_accion', 'fecha_ejecucion')) {
                $table->dropColumn('fecha_ejecucion');
            }
        });
    }
};
