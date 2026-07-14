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
            ->where('source_type', '!=', LavadoraCostEntry::SOURCE_MANUAL)
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
    public function automaticPreview(AnalisisLavadora $analysis): Collection
    {
        $analysis->loadMissing(['linea', 'componente', 'costRuleExclusions']);

        $componentCode = LavadoraCostSupport::normalizeComponentCode($analysis->componente?->codigo);
        $normalizedActivity = LavadoraCostSupport::normalizeText($analysis->actividad);
        $lineaNombre = $analysis->linea?->nombre;
        $excludedRuleIds = $analysis->costRuleExclusions
            ->pluck('cost_automation_rule_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->matchingRules($analysis, $componentCode, $normalizedActivity, $lineaNombre)
            ->map(function (CostAutomationRule $rule) use ($analysis, $componentCode, $lineaNombre, $excludedRuleIds) {
                $catalogItem = $rule->catalogItem;
                $quantity = LavadoraCostSupport::extractQuantity(
                    $analysis->actividad,
                    $catalogItem?->unidad_medida,
                    (float) ($rule->quantity ?: 1)
                );
                $unitCost = (float) ($catalogItem?->costo_unitario ?: 0);

                return [
                    'rule_id' => $rule->id,
                    'trigger_type' => $rule->trigger_type,
                    'trigger_label' => CostAutomationRule::triggerOptions()[$rule->trigger_type] ?? 'Regla automatica',
                    'trigger_reference' => $rule->trigger_keyword ?: $rule->component_code,
                    'catalog_item_id' => $catalogItem?->id,
                    'catalog_name' => $catalogItem?->nombre ?? 'Concepto sin catalogo',
                    'catalog_sku' => $catalogItem?->sku,
                    'catalog_category' => $catalogItem?->categoria,
                    'unit' => $catalogItem?->unidad_medida,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($quantity * $unitCost, 2),
                    'excluded' => in_array($rule->id, $excludedRuleIds, true),
                    'notes' => $rule->notas,
                    'metadata' => [
                        'linea_nombre' => $lineaNombre,
                        'component_code' => $componentCode,
                        'estado' => $analysis->estado,
                        'actividad' => $analysis->actividad,
                        'rule_id' => $rule->id,
                    ],
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildEntries(AnalisisLavadora $analysis): Collection
    {
        $now = now();
        $componentCode = LavadoraCostSupport::normalizeComponentCode($analysis->componente?->codigo);

        return $this->automaticPreview($analysis)
            ->reject(fn (array $preview) => $preview['excluded'] === true)
            ->map(function (array $preview) use ($analysis, $componentCode, $now) {
                return [
                    'linea_id' => $analysis->linea_id,
                    'analisis_lavadora_id' => $analysis->id,
                    'componente_id' => $analysis->componente_id,
                    'catalog_item_id' => $preview['catalog_item_id'],
                    'source_type' => $preview['trigger_type'],
                    'source_reference' => $preview['trigger_reference'],
                    'cost_date' => $analysis->fecha_analisis?->toDateString() ?? now()->toDateString(),
                    'quantity' => $preview['quantity'],
                    'unit_cost' => $preview['unit_cost'],
                    'total_cost' => $preview['total_cost'],
                    'component_snapshot' => $analysis->componente?->nombre ?? ($componentCode ?: 'Componente no identificado'),
                    'catalog_name_snapshot' => $preview['catalog_name'],
                    'catalog_sku_snapshot' => $preview['catalog_sku'],
                    'catalog_category_snapshot' => $preview['catalog_category'],
                    'unidad_medida_snapshot' => $preview['unit'],
                    'notas' => $preview['notes'],
                    'metadata' => json_encode($preview['metadata']),
                    'sync_key' => sha1(implode('|', [
                        $analysis->id,
                        $preview['rule_id'],
                        $preview['trigger_type'],
                        $preview['trigger_reference'],
                    ])),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values();
    }

    private function matchingRules(
        AnalisisLavadora $analysis,
        string $componentCode,
        string $normalizedActivity,
        ?string $lineaNombre
    ): Collection {
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
            ->values();
    }
}
