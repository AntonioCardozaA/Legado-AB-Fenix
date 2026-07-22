<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lavadora_cost_entries')) {
            return;
        }

        Schema::table('lavadora_cost_entries', function (Blueprint $table) {
            if (Schema::hasColumn('lavadora_cost_entries', 'analisis_lavadora_id')) {
                $table->foreignId('analisis_lavadora_id')
                    ->nullable()
                    ->change();
            }

            if (!Schema::hasColumn('lavadora_cost_entries', 'elongacion_id')) {
                $table->foreignId('elongacion_id')
                    ->nullable()
                    ->after('analisis_lavadora_id')
                    ->constrained('elongaciones')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('lavadora_cost_entries', 'cadena_ciclo_id')) {
                $table->foreignId('cadena_ciclo_id')
                    ->nullable()
                    ->after('elongacion_id')
                    ->constrained('cadena_ciclos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('lavadora_cost_entries')) {
            return;
        }

        Schema::table('lavadora_cost_entries', function (Blueprint $table) {
            if (Schema::hasColumn('lavadora_cost_entries', 'cadena_ciclo_id')) {
                $table->dropConstrainedForeignId('cadena_ciclo_id');
            }

            if (Schema::hasColumn('lavadora_cost_entries', 'elongacion_id')) {
                $table->dropConstrainedForeignId('elongacion_id');
            }

            if (Schema::hasColumn('lavadora_cost_entries', 'analisis_lavadora_id')) {
                $table->foreignId('analisis_lavadora_id')
                    ->nullable(false)
                    ->change();
            }
        });
    }
};
