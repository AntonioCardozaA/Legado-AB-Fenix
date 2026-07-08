<?php

namespace App\Services;

use App\Models\AnalisisLavadora;
use App\Models\CostAutomationRule;
use App\Models\LavadoraCostEntry;
use App\Support\LavadoraCostSupport;
use Illuminate\Support\Collection;

class LavadoraCostSyncService
{
    public function syncForAnalysis(AnalisisLavadora $analysis): void
    {
        $analysis->loadMissing(['linea', 'componente']);

        LavadoraCostEntry::query()
            ->where('analisis_lavadora_id', $analysis->id)
            ->delete();

        $entries = $this->buildEntries($analysis);

        if ($entries->isEmpty()) {
            return;
        }

        LavadoraCostEntry::query()->insert($entries->all());
    }

    public function clearForAnalysis(AnalisisLavadora $analysis): void
    {
        LavadoraCostEntry::query()
            ->where('analisis_lavadora_id', $analysis->id)
            ->delete();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildEntries(AnalisisLavadora $analysis): Collection
    {
        $componentCode = LavadoraCostSupport::normalizeComponentCode($analysis->componente?->codigo);
        $normalizedActivity = LavadoraCostSupport::normalizeText($analysis->actividad);
        $lineaNombre = $analysis->linea?->nombre;
        $now = now();

        return CostAutomationRule::query()
            ->with('catalogItem')
            ->active()
            ->whereHas('catalogItem', fn ($query) => $query->where('activo', true))
            ->orderBy('priority')
            ->get()
            ->filter(function (CostAutomationRule $rule) use ($analysis, $componentCode, $normalizedActivity, $lineaNombre) {
                if ($rule->linea_nombre && $lineaNombre !== $rule->linea_nombre) {
                    return false;
                }

                if ($rule->component_code && $componentCode !== LavadoraCostSupport::normalizeComponentCode($rule->component_code)) {
                    return false;
                }

                if ($rule->trigger_type === CostAutomationRule::TRIGGER_ESTADO_CAMBIADO) {
                    return AnalisisLavadora::esEstadoCambiado($analysis->estado);
                }

                if ($rule->trigger_type === CostAutomationRule::TRIGGER_ACTIVIDAD_KEYWORD) {
                    return LavadoraCostSupport::keywordMatches($normalizedActivity, $rule->trigger_keyword);
                }

                return false;
            })
            ->map(function (CostAutomationRule $rule) use ($analysis, $componentCode, $lineaNombre, $now) {
                $catalogItem = $rule->catalogItem;
                $quantity = LavadoraCostSupport::extractQuantity(
                    $analysis->actividad,
                    $catalogItem?->unidad_medida,
                    (float) ($rule->quantity ?: 1)
                );
                $unitCost = (float) ($catalogItem?->costo_unitario ?: 0);

                return [
                    'linea_id' => $analysis->linea_id,
                    'analisis_lavadora_id' => $analysis->id,
                    'componente_id' => $analysis->componente_id,
                    'catalog_item_id' => $catalogItem?->id,
                    'source_type' => $rule->trigger_type,
                    'source_reference' => $rule->trigger_keyword ?: $rule->component_code,
                    'cost_date' => $analysis->fecha_analisis?->toDateString() ?? now()->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($quantity * $unitCost, 2),
                    'component_snapshot' => $analysis->componente?->nombre ?? ($componentCode ?: 'Componente no identificado'),
                    'catalog_name_snapshot' => $catalogItem?->nombre ?? 'Concepto sin catalogo',
                    'catalog_sku_snapshot' => $catalogItem?->sku,
                    'catalog_category_snapshot' => $catalogItem?->categoria,
                    'unidad_medida_snapshot' => $catalogItem?->unidad_medida,
                    'notas' => $rule->notas,
                    'metadata' => json_encode([
                        'linea_nombre' => $lineaNombre,
                        'component_code' => $componentCode,
                        'estado' => $analysis->estado,
                        'actividad' => $analysis->actividad,
                        'rule_id' => $rule->id,
                    ]),
                    'sync_key' => sha1(implode('|', [
                        $analysis->id,
                        $rule->id,
                        $rule->trigger_type,
                        $rule->trigger_keyword,
                        $rule->component_code,
                    ])),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values();
    }
}
