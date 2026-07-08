<?php

use App\Support\LavadoraCostSupport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $data = require database_path('data/lavadora_cost_catalog.php');
        $now = now();
        $observacion = 'Inicializado desde COSTO DE MATERIALES FENIX.xlsx';

        $catalogRows = collect($data['catalog_items'] ?? [])
            ->map(function (array $item) use ($now, $observacion) {
                return [
                    'sku' => $item['sku'] ?? null,
                    'nombre' => $item['nombre'],
                    'categoria' => LavadoraCostSupport::inferCategory($item['nombre']),
                    'unidad_medida' => $item['unidad_medida'] ?? 'Pieza',
                    'costo_unitario' => $item['costo_unitario'] ?? 0,
                    'activo' => true,
                    'fecha_actualizacion' => $now->toDateString(),
                    'observaciones' => $observacion,
                    'aliases' => json_encode(LavadoraCostSupport::inferAliases($item['nombre'])),
                    'metadata' => json_encode([
                        'source' => 'COSTO DE MATERIALES FENIX.xlsx',
                        'source_rows' => $item['source_rows'] ?? [],
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        DB::table('cost_catalog_items')->insert($catalogRows);

        $catalogItems = DB::table('cost_catalog_items')
            ->select('id', 'sku', 'nombre', 'categoria', 'unidad_medida', 'costo_unitario')
            ->get();

        $histories = $catalogItems
            ->map(function ($item) use ($now) {
                return [
                    'cost_catalog_item_id' => $item->id,
                    'tipo_cambio' => 'importado',
                    'datos_anteriores' => null,
                    'datos_nuevos' => json_encode([
                        'nombre' => $item->nombre,
                        'categoria' => $item->categoria,
                        'unidad_medida' => $item->unidad_medida,
                        'costo_unitario' => (float) $item->costo_unitario,
                    ]),
                    'costo_anterior' => null,
                    'costo_nuevo' => $item->costo_unitario,
                    'fecha_cambio' => $now,
                    'usuario_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        DB::table('cost_catalog_item_histories')->insert($histories);

        $catalogIdsBySku = $catalogItems
            ->filter(fn ($item) => !blank($item->sku))
            ->mapWithKeys(fn ($item) => [$item->sku => $item->id]);

        $ruleRows = collect($data['automation_rules'] ?? [])
            ->map(function (array $rule) use ($catalogIdsBySku, $now) {
                $catalogItemId = $catalogIdsBySku[$rule['catalog_sku'] ?? ''] ?? null;

                if (!$catalogItemId) {
                    return null;
                }

                return [
                    'cost_catalog_item_id' => $catalogItemId,
                    'linea_nombre' => $rule['linea_nombre'] ?? null,
                    'component_code' => $rule['component_code'] ?? null,
                    'trigger_type' => $rule['trigger_type'],
                    'trigger_keyword' => $rule['trigger_keyword'] ?? null,
                    'quantity' => $rule['quantity'] ?? 1,
                    'priority' => $rule['priority'] ?? 100,
                    'activo' => true,
                    'notas' => $rule['notas'] ?? null,
                    'metadata' => json_encode([
                        'source' => 'seed',
                        'catalog_sku' => $rule['catalog_sku'] ?? null,
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($ruleRows !== []) {
            DB::table('cost_automation_rules')->insert($ruleRows);
        }
    }

    public function down(): void
    {
        DB::table('cost_automation_rules')->delete();
        DB::table('cost_catalog_item_histories')->delete();
        DB::table('cost_catalog_items')->delete();
    }
};
