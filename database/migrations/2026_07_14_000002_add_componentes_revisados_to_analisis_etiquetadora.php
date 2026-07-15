<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analisis_etiquetadora')) {
            return;
        }

        Schema::table('analisis_etiquetadora', function (Blueprint $table): void {
            if (!Schema::hasColumn('analisis_etiquetadora', 'total_componentes')) {
                $table->integer('total_componentes')->nullable()->after('evidencia_fotos');
            }

            if (!Schema::hasColumn('analisis_etiquetadora', 'cantidad_componentes_revisados')) {
                $table->integer('cantidad_componentes_revisados')->nullable()->after('total_componentes');
            }

            if (!Schema::hasColumn('analisis_etiquetadora', 'componentes_revisados')) {
                $table->json('componentes_revisados')->nullable()->after('cantidad_componentes_revisados');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('analisis_etiquetadora')) {
            return;
        }

        Schema::table('analisis_etiquetadora', function (Blueprint $table): void {
            foreach (['componentes_revisados', 'cantidad_componentes_revisados', 'total_componentes'] as $column) {
                if (Schema::hasColumn('analisis_etiquetadora', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
