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

        DB::transaction(function (): void {
            $now = now();
            $hasLegacyRows = DB::table('componentes')
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('nombre', 'Tulipa y goma')
                ->exists();

            if (!$hasLegacyRows) {
                return;
            }

            DB::table('componentes')
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('nombre', 'Tulipa y goma')
                ->update([
                    'nombre' => 'Tulipa',
                    'updated_at' => $now,
                ]);

            foreach ($this->splitCatalogRows() as $row) {
                DB::table('componentes')->updateOrInsert(
                    ['codigo' => $row['codigo']],
                    [
                        'nombre' => $row['nombre'],
                        'linea' => $row['linea'],
                        'reductor' => $row['maquina_label'],
                        'ubicacion' => $row['grupo'],
                        'grupo' => $row['grupo'],
                        'mecanismo' => $row['mecanismo'],
                        'cantidad_total' => $row['cantidad_total'],
                        'cantidad_original' => $row['cantidad_original'],
                        'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
                        'activo' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('componentes')) {
            return;
        }

        DB::transaction(function (): void {
            $now = now();
            $rows = collect($this->splitCatalogRows());

            DB::table('componentes')
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->whereIn('codigo', $rows->where('nombre', 'Goma')->pluck('codigo')->all())
                ->update([
                    'activo' => false,
                    'updated_at' => $now,
                ]);

            DB::table('componentes')
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->whereIn('codigo', $rows->where('nombre', 'Tulipa')->pluck('codigo')->all())
                ->update([
                    'nombre' => 'Tulipa y goma',
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function splitCatalogRows(): array
    {
        return collect(EtiquetadoraCatalog::expandedComponentRows())
            ->filter(fn (array $row): bool => in_array($row['nombre'], ['Tulipa', 'Goma'], true))
            ->values()
            ->all();
    }
};
