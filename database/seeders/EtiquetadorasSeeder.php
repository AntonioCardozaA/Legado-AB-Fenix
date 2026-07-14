<?php

namespace Database\Seeders;

use App\Support\EtiquetadoraCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EtiquetadorasSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (EtiquetadoraCatalog::lineas() as $lineaNombre) {
            DB::table('lineas')->updateOrInsert(
                ['nombre' => $lineaNombre],
                [
                    'descripcion' => 'Linea de envasado ' . substr($lineaNombre, -2),
                    'activo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $catalogRows = EtiquetadoraCatalog::expandedComponentRows();
        $catalogCodes = [];

        foreach ($catalogRows as $row) {
            $catalogCodes[] = $row['codigo'];

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

        DB::table('componentes')
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->whereNotIn('codigo', $catalogCodes)
            ->update([
                'activo' => false,
                'updated_at' => $now,
            ]);
    }
}
