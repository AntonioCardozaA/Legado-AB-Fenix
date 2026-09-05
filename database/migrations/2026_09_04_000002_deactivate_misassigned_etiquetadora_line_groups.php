<?php

use App\Support\EtiquetadoraCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('componentes')) {
            return;
        }

        $invalidIds = DB::table('componentes')
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->whereIn('linea', EtiquetadoraCatalog::lineas())
            ->get(['id', 'linea', 'grupo', 'mecanismo', 'ubicacion'])
            ->filter(fn ($row): bool => !EtiquetadoraCatalog::grupoAplicaALinea(
                $row->grupo,
                $row->mecanismo,
                $row->ubicacion,
                $row->linea
            ))
            ->pluck('id');

        if ($invalidIds->isEmpty()) {
            return;
        }

        DB::table('componentes')
            ->whereIn('id', $invalidIds->all())
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data cleanup only. Do not reactivate rows that contradict their own line-specific group.
    }
};
