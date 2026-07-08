<?php

namespace App\Http\Controllers;

use App\Models\CostAutomationRule;
use App\Models\CostCatalogItem;
use App\Models\CostCatalogItemHistory;
use App\Models\LavadoraBudget;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Support\LavadoraCostSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControlGastosController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'categoria' => 'nullable|string|max:100',
            'activo' => 'nullable|in:todos,activos,inactivos',
            'sort' => 'nullable|in:nombre,categoria,costo_unitario,fecha_actualizacion,created_at',
            'direction' => 'nullable|in:asc,desc',
            'budget_year' => 'nullable|integer|min:2024|max:2100',
        ]);

        $sort = $filters['sort'] ?? 'categoria';
        $direction = $filters['direction'] ?? ($sort === 'costo_unitario' ? 'desc' : 'asc');
        $budgetYear = (int) ($filters['budget_year'] ?? now()->year);

        $items = CostCatalogItem::query()
            ->with('updatedBy')
            ->when(!empty($filters['q']), function ($query) use ($filters) {
                $term = '%' . trim($filters['q']) . '%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('sku', 'like', $term)
                        ->orWhere('nombre', 'like', $term)
                        ->orWhere('categoria', 'like', $term);
                });
            })
            ->when(($filters['activo'] ?? 'todos') === 'activos', fn ($query) => $query->where('activo', true))
            ->when(($filters['activo'] ?? 'todos') === 'inactivos', fn ($query) => $query->where('activo', false))
            ->when(!empty($filters['categoria']), fn ($query) => $query->where('categoria', $filters['categoria']))
            ->orderBy($sort, $direction)
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $rules = CostAutomationRule::query()
            ->with('catalogItem')
            ->orderBy('priority')
            ->orderBy('component_code')
            ->get();

        $history = CostCatalogItemHistory::query()
            ->with(['catalogItem', 'usuario'])
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id')
            ->take(20)
            ->get();

        $lineas = Linea::query()
            ->whereIn('nombre', LavadoraCostSupport::LAVADORA_LINEAS)
            ->orderBy('nombre')
            ->get();

        $budgets = LavadoraBudget::query()
            ->with(['linea', 'updatedBy'])
            ->where('year', $budgetYear)
            ->get()
            ->keyBy('linea_id');

        $spendByLinea = LavadoraCostEntry::query()
            ->selectRaw('linea_id, SUM(total_cost) as total')
            ->whereYear('cost_date', $budgetYear)
            ->groupBy('linea_id')
            ->pluck('total', 'linea_id');

        $budgetRows = $lineas->map(function (Linea $linea) use ($budgets, $spendByLinea, $budgetYear) {
            $budget = $budgets->get($linea->id);
            $assigned = round((float) ($budget?->annual_budget ?? 0), 2);
            $spent = round((float) ($spendByLinea[$linea->id] ?? 0), 2);

            return [
                'linea' => $linea,
                'budget' => $budget,
                'year' => $budgetYear,
                'assigned' => $assigned,
                'spent' => $spent,
                'remaining' => round($assigned - $spent, 2),
                'usage_percent' => $assigned > 0 ? round(($spent / $assigned) * 100, 1) : null,
            ];
        });

        return view('admin.control-gastos.index', [
            'items' => $items,
            'rules' => $rules,
            'history' => $history,
            'budgetRows' => $budgetRows,
            'catalogOptions' => CostCatalogItem::query()->orderBy('categoria')->orderBy('nombre')->get(),
            'categories' => CostCatalogItem::query()->select('categoria')->distinct()->whereNotNull('categoria')->orderBy('categoria')->pluck('categoria'),
            'componentCodes' => LavadoraCostSupport::COMPONENT_CODES,
            'triggerOptions' => CostAutomationRule::triggerOptions(),
            'budgetYears' => range(now()->year - 1, now()->year + 2),
            'filters' => $filters,
            'metrics' => [
                'catalog_total' => CostCatalogItem::query()->count(),
                'catalog_active' => CostCatalogItem::query()->where('activo', true)->count(),
                'rules_total' => $rules->count(),
                'budgets_configured' => $budgetRows->where('assigned', '>', 0)->count(),
            ],
        ]);
    }

    public function storeCatalogItem(Request $request): RedirectResponse
    {
        $payload = $this->catalogItemPayload($request);
        $item = CostCatalogItem::query()->create($payload);

        $this->recordHistory($item, 'creado', null);

        return back()->with('success', 'Concepto de costo creado correctamente.');
    }

    public function updateCatalogItem(Request $request, CostCatalogItem $item): RedirectResponse
    {
        $before = $item->only([
            'sku',
            'nombre',
            'categoria',
            'unidad_medida',
            'costo_unitario',
            'activo',
            'observaciones',
            'aliases',
        ]);

        $item->update($this->catalogItemPayload($request, $item));
        $this->recordHistory($item->fresh(), 'actualizado', $before);

        return back()->with('success', 'Concepto de costo actualizado.');
    }

    public function toggleCatalogItem(CostCatalogItem $item): RedirectResponse
    {
        $before = $item->only([
            'sku',
            'nombre',
            'categoria',
            'unidad_medida',
            'costo_unitario',
            'activo',
            'observaciones',
            'aliases',
        ]);

        $item->update([
            'activo' => !$item->activo,
            'fecha_actualizacion' => today(),
            'actualizado_por' => auth()->id(),
        ]);

        $this->recordHistory($item->fresh(), $item->activo ? 'activado' : 'desactivado', $before);

        return back()->with('success', 'Estado del concepto actualizado.');
    }

    public function destroyCatalogItem(CostCatalogItem $item): RedirectResponse
    {
        if ($item->automationRules()->exists() || $item->costEntries()->exists()) {
            return back()->with('error', 'El concepto ya tiene reglas o gastos asociados. Desactivalo en lugar de eliminarlo.');
        }

        $before = $item->only([
            'sku',
            'nombre',
            'categoria',
            'unidad_medida',
            'costo_unitario',
            'activo',
            'observaciones',
            'aliases',
        ]);

        $this->recordHistory($item, 'eliminado', $before);
        $item->delete();

        return back()->with('success', 'Concepto de costo eliminado.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        CostAutomationRule::query()->create($this->rulePayload($request));

        return back()->with('success', 'Regla de automatizacion creada.');
    }

    public function updateRule(Request $request, CostAutomationRule $rule): RedirectResponse
    {
        $rule->update($this->rulePayload($request, $rule));

        return back()->with('success', 'Regla de automatizacion actualizada.');
    }

    public function destroyRule(CostAutomationRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('success', 'Regla de automatizacion eliminada.');
    }

    public function upsertBudget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'year' => 'required|integer|min:2024|max:2100',
            'annual_budget' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        LavadoraBudget::query()->updateOrCreate(
            [
                'linea_id' => $validated['linea_id'],
                'year' => $validated['year'],
            ],
            [
                'annual_budget' => $validated['annual_budget'],
                'observaciones' => $validated['observaciones'] ?? null,
                'updated_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Presupuesto anual guardado correctamente.');
    }

    private function catalogItemPayload(Request $request, ?CostCatalogItem $item = null): array
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:255',
            'nombre' => 'required|string|max:2000',
            'categoria' => 'nullable|string|max:255',
            'unidad_medida' => 'required|string|max:255',
            'costo_unitario' => 'required|numeric|min:0',
            'activo' => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:1000',
            'aliases_input' => 'nullable|string|max:1000',
        ]);

        $categoria = trim((string) ($validated['categoria'] ?? ''));

        return [
            'sku' => $validated['sku'] ?? null,
            'nombre' => trim($validated['nombre']),
            'categoria' => $categoria !== '' ? $categoria : LavadoraCostSupport::inferCategory($validated['nombre']),
            'unidad_medida' => trim($validated['unidad_medida']),
            'costo_unitario' => $validated['costo_unitario'],
            'activo' => $request->has('activo') ? (bool) $validated['activo'] : ($item?->activo ?? true),
            'fecha_actualizacion' => today(),
            'actualizado_por' => auth()->id(),
            'observaciones' => $validated['observaciones'] ?? null,
            'aliases' => LavadoraCostSupport::formatAliases($validated['aliases_input'] ?? ''),
        ];
    }

    private function rulePayload(Request $request, ?CostAutomationRule $rule = null): array
    {
        $validated = $request->validate([
            'cost_catalog_item_id' => 'required|exists:cost_catalog_items,id',
            'linea_nombre' => 'nullable|string|max:255',
            'component_code' => 'nullable|string|max:255',
            'trigger_type' => 'required|in:' . implode(',', array_keys(CostAutomationRule::triggerOptions())),
            'trigger_keyword' => 'nullable|string|max:255|required_if:trigger_type,' . CostAutomationRule::TRIGGER_ACTIVIDAD_KEYWORD,
            'quantity' => 'required|numeric|min:0.01|max:9999',
            'priority' => 'nullable|integer|min:1|max:9999',
            'activo' => 'nullable|boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        return [
            'cost_catalog_item_id' => $validated['cost_catalog_item_id'],
            'linea_nombre' => $validated['linea_nombre'] ?: null,
            'component_code' => $validated['component_code']
                ? LavadoraCostSupport::normalizeComponentCode($validated['component_code'])
                : null,
            'trigger_type' => $validated['trigger_type'],
            'trigger_keyword' => $validated['trigger_keyword'] ?: null,
            'quantity' => $validated['quantity'],
            'priority' => $validated['priority'] ?? $rule?->priority ?? 100,
            'activo' => array_key_exists('activo', $validated) ? (bool) $validated['activo'] : true,
            'notas' => $validated['notas'] ?? null,
        ];
    }

    private function recordHistory(CostCatalogItem $item, string $type, ?array $before): void
    {
        CostCatalogItemHistory::query()->create([
            'cost_catalog_item_id' => $item->id,
            'tipo_cambio' => $type,
            'datos_anteriores' => $before,
            'datos_nuevos' => $item->only([
                'sku',
                'nombre',
                'categoria',
                'unidad_medida',
                'costo_unitario',
                'activo',
                'observaciones',
                'aliases',
            ]),
            'costo_anterior' => $before['costo_unitario'] ?? null,
            'costo_nuevo' => $item->costo_unitario,
            'fecha_cambio' => now(),
            'usuario_id' => auth()->id(),
        ]);
    }
}
