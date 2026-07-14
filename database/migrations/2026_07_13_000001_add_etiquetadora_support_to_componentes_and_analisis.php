<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('componentes', 'tipo_equipo')) {
                $table->string('tipo_equipo')->default('lavadora')->after('activo');
            }

            if (!Schema::hasColumn('componentes', 'grupo')) {
                $table->string('grupo')->nullable()->after('ubicacion');
            }

            if (!Schema::hasColumn('componentes', 'mecanismo')) {
                $table->string('mecanismo')->nullable()->after('grupo');
            }

            if (!Schema::hasColumn('componentes', 'cantidad_original')) {
                $table->string('cantidad_original')->nullable()->after('cantidad_total');
            }
        });

        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_componentes', 'tipo_equipo')) {
                $table->string('tipo_equipo')->default('lavadora')->after('usuario_id');
            }

            if (!Schema::hasColumn('analisis_componentes', 'maquina')) {
                $table->string('maquina')->nullable()->after('tipo_equipo');
            }
        });

        DB::table('componentes')
            ->whereNull('tipo_equipo')
            ->update(['tipo_equipo' => 'lavadora']);

        DB::table('analisis_componentes')
            ->whereNull('tipo_equipo')
            ->update(['tipo_equipo' => 'lavadora']);
    }

    public function down(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_componentes', 'maquina')) {
                $table->dropColumn('maquina');
            }

            if (Schema::hasColumn('analisis_componentes', 'tipo_equipo')) {
                $table->dropColumn('tipo_equipo');
            }
        });

        Schema::table('componentes', function (Blueprint $table) {
            foreach (['cantidad_original', 'mecanismo', 'grupo', 'tipo_equipo'] as $column) {
                if (Schema::hasColumn('componentes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
