<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ALLOWED_PRINCIPAL_LINES = ['L-05', 'L-12', 'L-13'];

    public function up(): void
    {
        $this->renameLegacyFlechaLoca();
        $this->renameServoReductorComponents();
        $this->removeInvalidPrincipalRecords();
    }

    public function down(): void
    {
        $this->renameValue('componentes', 'reductor', 'Flecha Loca', $this->legacyFlechaLoca());
        $this->renameValue('componentes', 'ubicacion', 'Flecha Loca', $this->legacyFlechaLoca());
        $this->renameValue('analisis_componentes', 'reductor', 'Flecha Loca', $this->legacyFlechaLoca());
        $this->renameValue('historial_restablecimientos', 'reductor', 'Flecha Loca', $this->legacyFlechaLoca());
        $this->renameValue('analisis', 'reductor', 'Flecha Loca', $this->legacyFlechaLoca());

        if (Schema::hasTable('componentes') && Schema::hasColumn('componentes', 'codigo')) {
            DB::table('componentes')
                ->where('codigo', 'like', '%flecha_loca%')
                ->update([
                    'codigo' => DB::raw("REPLACE(codigo, 'flecha_loca', 'reductor_loca')"),
                ]);
        }

        $this->renameValue('componentes', 'nombre', 'Servo-Reductor RV200', $this->legacyRv200());
        $this->renameValue('componentes', 'nombre', 'Servo-Reductor Sin Fin-Corona RV200', $this->legacyRv200SinFin());
        $this->renameValue('cost_catalog_items', 'categoria', 'Servo-Reductor', 'Reductor');
    }

    private function renameLegacyFlechaLoca(): void
    {
        $this->renameValue('componentes', 'reductor', $this->legacyFlechaLoca(), 'Flecha Loca');
        $this->renameValue('componentes', 'ubicacion', $this->legacyFlechaLoca(), 'Flecha Loca');
        $this->renameValue('analisis_componentes', 'reductor', $this->legacyFlechaLoca(), 'Flecha Loca');
        $this->renameValue('historial_restablecimientos', 'reductor', $this->legacyFlechaLoca(), 'Flecha Loca');
        $this->renameValue('analisis', 'reductor', $this->legacyFlechaLoca(), 'Flecha Loca');

        if (Schema::hasTable('componentes') && Schema::hasColumn('componentes', 'codigo')) {
            DB::table('componentes')
                ->where('codigo', 'like', '%reductor_loca%')
                ->update([
                    'codigo' => DB::raw("REPLACE(codigo, 'reductor_loca', 'flecha_loca')"),
                ]);
        }
    }

    private function renameServoReductorComponents(): void
    {
        $this->renameValue('componentes', 'nombre', $this->legacyRv200(), 'Servo-Reductor RV200');
        $this->renameValue('componentes', 'nombre', $this->legacyRv200SinFin(), 'Servo-Reductor Sin Fin-Corona RV200');
        $this->renameValue('cost_catalog_items', 'categoria', 'Reductor', 'Servo-Reductor');

        if (Schema::hasTable('cost_catalog_item_histories') && Schema::hasColumn('cost_catalog_item_histories', 'datos_nuevos')) {
            DB::table('cost_catalog_item_histories')
                ->where('datos_nuevos', 'like', '%"categoria":"Reductor"%')
                ->update([
                    'datos_nuevos' => DB::raw("REPLACE(datos_nuevos, '\"categoria\":\"Reductor\"', '\"categoria\":\"Servo-Reductor\"')"),
                ]);
        }
    }

    private function removeInvalidPrincipalRecords(): void
    {
        if (Schema::hasTable('analisis_componentes') && Schema::hasTable('lineas')) {
            $invalidAnalysisIds = DB::table('analisis_componentes as ac')
                ->join('lineas as l', 'l.id', '=', 'ac.linea_id')
                ->where('ac.reductor', 'Reductor Principal')
                ->whereNotIn('l.nombre', self::ALLOWED_PRINCIPAL_LINES)
                ->pluck('ac.id');

            if ($invalidAnalysisIds->isNotEmpty()) {
                DB::table('analisis_componentes')
                    ->whereIn('id', $invalidAnalysisIds)
                    ->delete();
            }
        }

        if (!Schema::hasTable('componentes')) {
            return;
        }

        $invalidComponentIds = DB::table('componentes')
            ->where('reductor', 'Reductor Principal')
            ->whereNotIn('linea', self::ALLOWED_PRINCIPAL_LINES)
            ->pluck('id');

        if ($invalidComponentIds->isEmpty()) {
            return;
        }

        $referencedComponentIds = Schema::hasTable('analisis_componentes')
            ? DB::table('analisis_componentes')
                ->whereIn('componente_id', $invalidComponentIds)
                ->distinct()
                ->pluck('componente_id')
            : collect();

        $componentIdsToDelete = $invalidComponentIds
            ->diff($referencedComponentIds)
            ->values();

        if ($componentIdsToDelete->isNotEmpty()) {
            DB::table('componentes')
                ->whereIn('id', $componentIdsToDelete)
                ->delete();
        }

        if ($referencedComponentIds->isNotEmpty() && Schema::hasColumn('componentes', 'activo')) {
            DB::table('componentes')
                ->whereIn('id', $referencedComponentIds)
                ->update(['activo' => false]);
        }
    }

    private function renameValue(string $table, string $column, string $from, string $to): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->where($column, $from)
            ->update([$column => $to]);
    }

    private function legacyFlechaLoca(): string
    {
        return 'Reductor ' . 'Loca';
    }

    private function legacyRv200(): string
    {
        return 'Reductor ' . 'RV200';
    }

    private function legacyRv200SinFin(): string
    {
        return 'Reductor ' . 'Sin Fin-Corona RV200';
    }
};
