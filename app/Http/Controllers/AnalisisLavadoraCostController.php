<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\CostAutomationRule;
use App\Models\CostCatalogItem;
use App\Models\LavadoraCostEntry;
use App\Models\LavadoraCostRuleExclusion;
use App\Services\LavadoraCostSyncService;
use App\Support\LavadoraCostSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnalisisLavadoraCostController extends Controller
{
    public function manage(Request $request, AnalisisLavadora $analisislavadora, LavadoraCostSyncService $costSync): View
    {
        abort_unless(
            $request->user()?->canAccessLavadoraCosts(),
            403,
            'No tienes permiso para acceder al modulo de Costos.'
        );

        $analisislavadora->load(['linea', 'componente', 'usuario']);

        $costEntries = LavadoraCostEntry::query()
            ->with('catalogItem')
            ->where('analisis_lavadora_id', $analisislavadora->id)
            ->orderByRaw(
                "CASE WHEN source_type = ? THEN 1 ELSE 0 END",
                [LavadoraCostEntry::SOURCE_MANUAL]
            )
            ->orderBy('created_at')
            ->get();

        $automaticSuggestions = $costSync->automaticPreview($analisislavadora);
        $automaticEntryCount = $costEntries->filter(fn (LavadoraCostEntry $entry) => $entry->isAutomatic())->count();
        $activeAutomaticSuggestionsCount = $automaticSuggestions
            ->reject(fn (array $suggestion) => $suggestion['excluded'] === true)
            ->count();
        $missingAutomaticEntriesCount = max($activeAutomaticSuggestionsCount - $automaticEntryCount, 0);
        $canSyncAutomaticCosts = $activeAutomaticSuggestionsCount > 0 || $automaticEntryCount > 0;

        $catalogItems = CostCatalogItem::query()
            ->active()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        return view('lavadora.analisis-lavadora.costos', [
            'analisislavadora' => $analisislavadora,
            'costEntries' => $costEntries,
            'automaticSuggestions' => $automaticSuggestions,
            'catalogItems' => $catalogItems,
            'automaticEntryCount' => $automaticEntryCount,
            'activeAutomaticSuggestionsCount' => $activeAutomaticSuggestionsCount,
            'missingAutomaticEntriesCount' => $missingAutomaticEntriesCount,
            'canSyncAutomaticCosts' => $canSyncAutomaticCosts,
            'canCreateLavadoraCosts' => $request->user()?->canCreateLavadoraCosts() ?? false,
            'canEditLavadoraCosts' => $request->user()?->canEditLavadoraCosts() ?? false,
            'canDeleteLavadoraCosts' => $request->user()?->canDeleteLavadoraCosts() ?? false,
        ]);
    }

    public function syncAutomaticCosts(
        Request $request,
        AnalisisLavadora $analisislavadora,
        LavadoraCostSyncService $costSync
    ): RedirectResponse {
        abort_unless(
            $request->user()?->canEditLavadoraCosts(),
            403,
            'No tienes permiso para editar costos de Lavadora.'
        );

        $analisislavadora->loadMissing(['linea', 'componente', 'costRuleExclusions']);

        $existingAutomaticEntries = LavadoraCostEntry::query()
            ->where('analisis_lavadora_id', $analisislavadora->id)
            ->where('source_type', '!=', LavadoraCostEntry::SOURCE_MANUAL)
            ->count();

        $expectedAutomaticEntries = $costSync->automaticPreview($analisislavadora)
            ->reject(fn (array $preview) => $preview['excluded'] === true)
            ->count();

        $costSync->syncForAnalysis($analisislavadora->fresh(['linea', 'componente', 'costRuleExclusions']));

        $syncedAutomaticEntries = LavadoraCostEntry::query()
            ->where('analisis_lavadora_id', $analisislavadora->id)
            ->where('source_type', '!=', LavadoraCostEntry::SOURCE_MANUAL)
            ->count();

        if ($syncedAutomaticEntries === 0) {
            return back()->with('success', 'No se detectaron costos automaticos aplicables para este analisis.');
        }

        if ($existingAutomaticEntries === 0 && $expectedAutomaticEntries > 0) {
            return back()->with('success', 'Se aplicaron ' . $syncedAutomaticEntries . ' costo(s) automatico(s) al analisis.');
        }

        return back()->with('success', 'Se sincronizaron ' . $syncedAutomaticEntries . ' costo(s) automatico(s) del analisis.');
    }

    public function storeManual(Request $request, AnalisisLavadora $analisislavadora): RedirectResponse
    {
        abort_unless(
            $request->user()?->canCreateLavadoraCosts(),
            403,
            'No tienes permiso para crear costos de Lavadora.'
        );

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.selected' => 'nullable|boolean',
            'items.*.catalog_item_id' => 'nullable|integer',
            'items.*.quantity' => 'nullable|numeric',
            'items.*.notas' => 'nullable|string|max:1000',
        ]);

        $selectedItems = collect($validated['items'] ?? [])
            ->filter(fn (array $item) => (bool) ($item['selected'] ?? false))
            ->values();

        if ($selectedItems->isEmpty()) {
            return back()->with('error', 'Selecciona al menos un gasto manual para registrar.');
        }

        $selectedItems->each(function (array $item): void {
            Validator::make($item, [
                'catalog_item_id' => 'required|exists:cost_catalog_items,id',
                'quantity' => 'required|numeric|min:0.01|max:9999',
                'notas' => 'nullable|string|max:1000',
            ])->validate();
        });

        $catalogItems = CostCatalogItem::query()
            ->active()
            ->whereIn('id', $selectedItems->pluck('catalog_item_id')->all())
            ->get()
            ->keyBy('id');

        if ($catalogItems->count() !== $selectedItems->pluck('catalog_item_id')->unique()->count()) {
            return back()->with('error', 'Uno o mas conceptos ya no estan activos en el catalogo de costos.');
        }

        $analisislavadora->loadMissing(['linea', 'componente']);

        $componentCode = LavadoraCostSupport::normalizeComponentCode($analisislavadora->componente?->codigo);
        $lineaNombre = $analisislavadora->linea?->nombre;
        $now = now();

        $rows = $selectedItems->map(function (array $item) use ($analisislavadora, $catalogItems, $componentCode, $lineaNombre, $now) {
            /** @var CostCatalogItem $catalogItem */
            $catalogItem = $catalogItems->get((int) $item['catalog_item_id']);
            $quantity = round((float) $item['quantity'], 2);
            $unitCost = round((float) $catalogItem->costo_unitario, 2);

            return [
                'linea_id' => $analisislavadora->linea_id,
                'analisis_lavadora_id' => $analisislavadora->id,
                'componente_id' => $analisislavadora->componente_id,
                'catalog_item_id' => $catalogItem->id,
                'source_type' => LavadoraCostEntry::SOURCE_MANUAL,
                'source_reference' => 'manual',
                'cost_date' => $analisislavadora->fecha_analisis?->toDateString() ?? $now->toDateString(),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'component_snapshot' => $analisislavadora->componente?->nombre ?? ($componentCode ?: 'Componente no identificado'),
                'catalog_name_snapshot' => $catalogItem->nombre,
                'catalog_sku_snapshot' => $catalogItem->sku,
                'catalog_category_snapshot' => $catalogItem->categoria,
                'unidad_medida_snapshot' => $catalogItem->unidad_medida,
                'notas' => trim((string) ($item['notas'] ?? '')) ?: null,
                'metadata' => json_encode([
                    'manual' => true,
                    'linea_nombre' => $lineaNombre,
                    'component_code' => $componentCode,
                    'estado' => $analisislavadora->estado,
                    'actividad' => $analisislavadora->actividad,
                    'added_by' => auth()->id(),
                    'added_at' => $now->toDateTimeString(),
                ]),
                'sync_key' => sha1(implode('|', [
                    'manual',
                    $analisislavadora->id,
                    $catalogItem->id,
                    Str::uuid()->toString(),
                ])),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        DB::transaction(function () use ($rows): void {
            LavadoraCostEntry::query()->insert($rows->all());
        });

        return back()->with('success', 'Se registraron ' . $rows->count() . ' gasto(s) manual(es).');
    }

    public function destroyManual(AnalisisLavadora $analisislavadora, LavadoraCostEntry $costEntry): RedirectResponse
    {
        abort_unless(
            auth()->user()?->canDeleteLavadoraCosts(),
            403,
            'No tienes permiso para eliminar costos de Lavadora.'
        );

        abort_unless($costEntry->analisis_lavadora_id === $analisislavadora->id, 404);

        if (!$costEntry->isManual()) {
            return back()->with('error', 'Solo los gastos manuales pueden eliminarse desde esta vista.');
        }

        $costEntry->delete();

        return back()->with('success', 'Gasto manual eliminado correctamente.');
    }

    public function disableAutomaticRule(
        Request $request,
        AnalisisLavadora $analisislavadora,
        CostAutomationRule $rule,
        LavadoraCostSyncService $costSync
    ): RedirectResponse {
        abort_unless(
            $request->user()?->canEditLavadoraCosts(),
            403,
            'No tienes permiso para editar costos de Lavadora.'
        );

        $validated = $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        if (!$this->ruleAppliesToAnalysis($costSync, $analisislavadora, $rule)) {
            return back()->with('error', 'La regla seleccionada ya no aplica a este analisis.');
        }

        LavadoraCostRuleExclusion::query()->firstOrCreate(
            [
                'analisis_lavadora_id' => $analisislavadora->id,
                'cost_automation_rule_id' => $rule->id,
            ],
            [
                'motivo' => trim((string) ($validated['motivo'] ?? '')) ?: null,
                'created_by' => auth()->id(),
            ]
        );

        $costSync->syncForAnalysis($analisislavadora->fresh(['linea', 'componente', 'costRuleExclusions']));

        return back()->with('success', 'La regla automatica se desactivo solo para este analisis.');
    }

    public function enableAutomaticRule(
        Request $request,
        AnalisisLavadora $analisislavadora,
        CostAutomationRule $rule,
        LavadoraCostSyncService $costSync
    ): RedirectResponse {
        abort_unless(
            $request->user()?->canEditLavadoraCosts(),
            403,
            'No tienes permiso para editar costos de Lavadora.'
        );

        LavadoraCostRuleExclusion::query()
            ->where('analisis_lavadora_id', $analisislavadora->id)
            ->where('cost_automation_rule_id', $rule->id)
            ->delete();

        $costSync->syncForAnalysis($analisislavadora->fresh(['linea', 'componente', 'costRuleExclusions']));

        return back()->with('success', 'La regla automatica volvio a habilitarse para este analisis.');
    }

    private function ruleAppliesToAnalysis(
        LavadoraCostSyncService $costSync,
        AnalisisLavadora $analisislavadora,
        CostAutomationRule $rule
    ): bool {
        return $costSync->automaticPreview($analisislavadora)
            ->contains(fn (array $preview) => (int) $preview['rule_id'] === $rule->id);
    }
}
