<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('analisis_pasteurizadora', 'total_piezas')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->renameColumn('total_piezas', 'total_componentes');
            });
        }

        if (Schema::hasColumn('analisis_pasteurizadora', 'revisadas_piezas')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->renameColumn('revisadas_piezas', 'cantidad_componentes_revisados');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('analisis_pasteurizadora', 'total_componentes')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->renameColumn('total_componentes', 'total_piezas');
            });
        }

        if (Schema::hasColumn('analisis_pasteurizadora', 'cantidad_componentes_revisados')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->renameColumn('cantidad_componentes_revisados', 'revisadas_piezas');
            });
        }
    }
};
