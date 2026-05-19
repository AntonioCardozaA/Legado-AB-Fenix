<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_accion')) {
            return;
        }

        if (!Schema::hasColumn('plan_accion', 'tipo_equipo')) {
            Schema::table('plan_accion', function (Blueprint $table) {
                $table->string('tipo_equipo')->nullable()->after('actividad');
            });
        }

        $registros = DB::table('plan_accion')
            ->join('lineas', 'lineas.id', '=', 'plan_accion.linea_id')
            ->select('plan_accion.id', 'lineas.nombre')
            ->get();

        foreach ($registros as $registro) {
            $tipoEquipo = str_starts_with((string) $registro->nombre, 'P-')
                ? 'pasteurizadora'
                : 'lavadora';

            DB::table('plan_accion')
                ->where('id', $registro->id)
                ->update(['tipo_equipo' => $tipoEquipo]);
        }

        DB::table('plan_accion')
            ->whereNull('tipo_equipo')
            ->update(['tipo_equipo' => 'lavadora']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE plan_accion MODIFY tipo_equipo VARCHAR(255) NOT NULL DEFAULT 'lavadora'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('plan_accion') || !Schema::hasColumn('plan_accion', 'tipo_equipo')) {
            return;
        }

        Schema::table('plan_accion', function (Blueprint $table) {
            $table->dropColumn('tipo_equipo');
        });
    }
};
