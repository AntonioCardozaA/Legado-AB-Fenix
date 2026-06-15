<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analisis_pasteurizadora') && !Schema::hasColumn('analisis_pasteurizadora', 'area')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->string('area')->default('mecanica')->after('id')->index();
            });

            DB::table('analisis_pasteurizadora')
                ->whereNull('area')
                ->update(['area' => 'mecanica']);
        }

        if (Schema::hasTable('historico_revisados') && !Schema::hasColumn('historico_revisados', 'area')) {
            Schema::table('historico_revisados', function (Blueprint $table) {
                $table->string('area')->default('mecanica')->after('id')->index();
                $table->dropUnique(['linea_id', 'componente']);
            });

            DB::table('historico_revisados')
                ->whereNull('area')
                ->update(['area' => 'mecanica']);

            Schema::table('historico_revisados', function (Blueprint $table) {
                $table->unique(['linea_id', 'componente', 'area'], 'historico_revisados_linea_componente_area_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('historico_revisados') && Schema::hasColumn('historico_revisados', 'area')) {
            Schema::table('historico_revisados', function (Blueprint $table) {
                $table->dropUnique('historico_revisados_linea_componente_area_unique');
                $table->dropIndex(['area']);
                $table->unique(['linea_id', 'componente']);
                $table->dropColumn('area');
            });
        }

        if (Schema::hasTable('analisis_pasteurizadora') && Schema::hasColumn('analisis_pasteurizadora', 'area')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->dropIndex(['area']);
                $table->dropColumn('area');
            });
        }
    }
};
