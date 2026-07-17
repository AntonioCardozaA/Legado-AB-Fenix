<?php

namespace App\Services;

use App\Models\AnalisisLavadora;
use App\Models\LavadoraBudget;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Support\LavadoraCostSupport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LavadoraCostAnalyticsService
{
    public function dashboardData(array $filters = []): array
    {
        [$from, $to, $preset, $rangeLabel] = $this->resolveRange($filters);
        $budgetYear = (int) ($filters['budget_year'] ?? now()->year);
        $selectedLinea = !empty($filters['linea_id'])
            ? Linea::query()->find($filters['linea_id'])
            : null;

        $entryQuery = LavadoraCostEntry::query()
            ->with('linea');

        $this->applyDateRange($entryQuery, $from, $to);

        if ($selectedLinea) {
            $entryQuery->where('linea_id', $selectedLinea->id);
        }

        $entries = $entryQuery
            ->orderBy('cost_date')
            ->orderBy('created_at')
            ->get();

        $byComponent = $entries
            ->groupBy('component_snapshot')
            ->map(fn (Collection $group) => round($group->sum('total_cost'), 2))
            ->sortDesc()
            ->map(fn (float $total, string $label) => ['label' => $label, 'total' => $total])
            ->values();

        $byLavadora = $entries
            ->groupBy(fn (LavadoraCostEntry $entry) => $entry->linea?->nombre ?? ($entry->metadata['linea_nombre'] ?? 'Sin linea'))
            ->map(fn (Collection $group) => round($group->sum('total_cost'), 2))
            ->sortDesc()
            ->map(fn (float $total, string $label) => ['label' => $label, 'total' => $total])
            ->values();

        $replacements = $entries
            ->filter(fn (LavadoraCostEntry $entry) => $entry->source_type === 'estado_cambiado'
                || (($entry->metadata['estado_correccion'] ?? null) === AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO))
            ->groupBy('component_snapshot')
            ->map(fn (Collection $group) => $group->pluck('analisis_lavadora_id')->unique()->count())
            ->sortDesc()
            ->map(fn (int $total, string $label) => ['label' => $label, 'total' => $total])
            ->values();

        $history = $entries
            ->sortByDesc(fn (LavadoraCostEntry $entry) => sprintf(
                '%s-%010d',
                optional($entry->cost_date)->toDateString() ?? '',
                $entry->id
            ))
            ->take(15)
            ->map(function (LavadoraCostEntry $entry) {
                return [
                    'fecha' => optional($entry->cost_date)->format('d/m/Y') ?? '-',
                    'lavadora' => $entry->linea?->nombre ?? '-',
                    'componente' => $entry->component_snapshot,
                    'concepto' => $entry->catalog_name_snapshot,
                    'tipo' => LavadoraCostEntry::sourceLabel($entry->source_type),
                    'cantidad' => $entry->quantity,
                    'unidad' => $entry->unidad_medida_snapshot ?? '-',
                    'total' => $entry->total_cost,
                ];
            })
            ->values();

        $trend = $this->buildTrendSeries($entries, $from, $to);
        $lineBudgets = $this->buildBudgetRows($budgetYear);
        $selectedLineaDetails = $selectedLinea
            ? $this->buildSelectedLineaDetails($selectedLinea, $from, $to)
            : null;
        $correctionSummary = $this->buildCorrectionSummary($selectedLinea?->id, $from, $to);
        $interventionHistory = $this->buildInterventionHistory($selectedLinea?->id, $from, $to);

        $monthComparison = $this->buildComparison(
            $selectedLinea?->id,
            now()->copy()->startOfMonth(),
            now()->copy()->endOfMonth(),
            now()->copy()->subMonth()->startOfMonth(),
            now()->copy()->subMonth()->endOfMonth()
        );

        $yearComparison = $this->buildComparison(
            $selectedLinea?->id,
            now()->copy()->startOfYear(),
            now()->copy()->endOfYear(),
            now()->copy()->subYear()->startOfYear(),
            now()->copy()->subYear()->endOfYear()
        );

        return [
            'filters' => [
                'preset' => $preset,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'range_label' => $rangeLabel,
                'linea_id' => $selectedLinea?->id,
                'budget_year' => $budgetYear,
            ],
            'summary' => [
                'range_total' => round($entries->sum('total_cost'), 2),
                'month_total' => $this->sumForRange($selectedLinea?->id, now()->copy()->startOfMonth(), now()->copy()->endOfMonth()),
                'year_total' => $this->sumForRange($selectedLinea?->id, now()->copy()->startOfYear(), now()->copy()->endOfYear()),
                'top_component' => $byComponent->first(),
                'top_replacement' => $replacements->first(),
                'top_lavadora' => $byLavadora->first(),
                'month_comparison' => $monthComparison,
                'year_comparison' => $yearComparison,
            ],
            'by_component' => $byComponent,
            'by_lavadora' => $byLavadora,
            'top_replacements' => $replacements,
            'correction_summary' => $correctionSummary,
            'trend' => $trend,
            'budgets' => $lineBudgets,
            'history' => $history,
            'intervention_history' => $interventionHistory,
            'selected_linea' => $selectedLineaDetails,
        ];
    }

    private function resolveRange(array $filters): array
    {
        $preset = $filters['preset'] ?? 'anual';
        $today = now();

        return match ($preset) {
            'mensual' => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
                'mensual',
                'Mes actual',
            ],
            'trimestral' => [
                $today->copy()->startOfQuarter(),
                $today->copy()->endOfQuarter(),
                'trimestral',
                'Trimestre actual',
            ],
            'semestral' => [
                $today->copy()->month <= 6
                    ? $today->copy()->startOfYear()
                    : $today->copy()->month(7)->startOfMonth(),
                $today->copy()->month <= 6
                    ? $today->copy()->month(6)->endOfMonth()
                    : $today->copy()->endOfYear(),
                'semestral',
                'Semestre actual',
            ],
            'custom' => $this->resolveCustomRange($filters),
            default => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
                'anual',
                'Año actual',
            ],
        };
    }

    private function resolveCustomRange(array $filters): array
    {
        try {
            $from = !empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : now()->copy()->startOfYear();
            $to = !empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : now()->copy()->endOfDay();
        } catch (\Throwable) {
            $from = now()->copy()->startOfYear();
            $to = now()->copy()->endOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to, 'custom', 'Rango personalizado'];
    }

    private function applyDateRange(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('cost_date', [$from->toDateString(), $to->toDateString()]);
    }

    private function sumForRange(?int $lineaId, Carbon $from, Carbon $to): float
    {
        $query = LavadoraCostEntry::query();
        $this->applyDateRange($query, $from, $to);

        if ($lineaId) {
            $query->where('linea_id', $lineaId);
        }

        return round((float) $query->sum('total_cost'), 2);
    }

    private function buildComparison(?int $lineaId, Carbon $currentFrom, Carbon $currentTo, Carbon $previousFrom, Carbon $previousTo): array
    {
        $current = $this->sumForRange($lineaId, $currentFrom, $currentTo);
        $previous = $this->sumForRange($lineaId, $previousFrom, $previousTo);
        $delta = round($current - $previous, 2);

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'trend' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable'),
        ];
    }

    private function buildBudgetRows(int $year): Collection
    {
        $lineas = Linea::query()
            ->whereIn('nombre', LavadoraCostSupport::LAVADORA_LINEAS)
            ->orderBy('nombre')
            ->get();

        $budgets = LavadoraBudget::query()
            ->with('updatedBy')
            ->where('year', $year)
            ->get()
            ->keyBy('linea_id');

        $spendByLinea = LavadoraCostEntry::query()
            ->selectRaw('linea_id, SUM(total_cost) as total')
            ->whereYear('cost_date', $year)
            ->groupBy('linea_id')
            ->pluck('total', 'linea_id');

        return $lineas->map(function (Linea $linea) use ($budgets, $spendByLinea, $year) {
            $budget = $budgets->get($linea->id);
            $spent = round((float) ($spendByLinea[$linea->id] ?? 0), 2);
            $assigned = round((float) ($budget?->annual_budget ?? 0), 2);
            $remaining = round($assigned - $spent, 2);
            $usage = $assigned > 0 ? round(min(($spent / $assigned) * 100, 999), 1) : null;

            return [
                'linea_id' => $linea->id,
                'linea' => $linea->nombre,
                'year' => $year,
                'assigned' => $assigned,
                'spent' => $spent,
                'remaining' => $remaining,
                'usage_percent' => $usage,
                'observaciones' => $budget?->observaciones,
                'updated_by' => $budget?->updatedBy?->name,
            ];
        });
    }

    private function buildSelectedLineaDetails(Linea $linea, Carbon $from, Carbon $to): array
    {
        $entries = LavadoraCostEntry::query()
            ->where('linea_id', $linea->id)
            ->whereBetween('cost_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('cost_date')
            ->get();

        $history = $entries
            ->sortByDesc('cost_date')
            ->take(10)
            ->map(fn (LavadoraCostEntry $entry) => [
                'fecha' => optional($entry->cost_date)->format('d/m/Y') ?? '-',
                'componente' => $entry->component_snapshot,
                'concepto' => $entry->catalog_name_snapshot,
                'tipo' => LavadoraCostEntry::sourceLabel($entry->source_type),
                'total' => $entry->total_cost,
            ])
            ->values();

        $topComponents = $entries
            ->groupBy('component_snapshot')
            ->map(fn (Collection $group) => round($group->sum('total_cost'), 2))
            ->sortDesc()
            ->map(fn (float $total, string $label) => ['label' => $label, 'total' => $total])
            ->values();

        $topReplacements = $entries
            ->filter(fn (LavadoraCostEntry $entry) => $entry->source_type === 'estado_cambiado'
                || (($entry->metadata['estado_correccion'] ?? null) === AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO))
            ->groupBy('component_snapshot')
            ->map(fn (Collection $group) => $group->pluck('analisis_lavadora_id')->unique()->count())
            ->sortDesc()
            ->map(fn (int $total, string $label) => ['label' => $label, 'total' => $total])
            ->values();

        return [
            'linea_id' => $linea->id,
            'linea' => $linea->nombre,
            'total' => round($entries->sum('total_cost'), 2),
            'trend' => $this->buildTrendSeries($entries, $from, $to),
            'top_components' => $topComponents,
            'top_replacements' => $topReplacements,
            'history' => $history,
        ];
    }

    private function buildCorrectionSummary(?int $lineaId, Carbon $from, Carbon $to): array
    {
        $pendingQuery = AnalisisLavadora::ultimosPorComponente()
            ->with('componente')
            ->where('estado', AnalisisLavadora::ESTADO_DANADO)
            ->where(function ($query): void {
                $query->whereNull('estado_correccion')
                    ->orWhere('estado_correccion', AnalisisLavadora::CORRECCION_PENDIENTE);
            });

        if ($lineaId) {
            $pendingQuery->where('linea_id', $lineaId);
        }

        $closedQuery = AnalisisLavadora::query()
            ->whereIn('estado_correccion', [
                AnalisisLavadora::CORRECCION_CORREGIDO,
                AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO,
            ])
            ->whereBetween('fecha_correccion', [$from->toDateTimeString(), $to->toDateTimeString()]);

        if ($lineaId) {
            $closedQuery->where('linea_id', $lineaId);
        }

        $closed = $closedQuery->get();
        $pending = $pendingQuery->get();

        return [
            'pending' => $pending->count(),
            'corrected' => $closed->where('estado_correccion', AnalisisLavadora::CORRECCION_CORREGIDO)->count(),
            'replaced' => $closed->where('estado_correccion', AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO)->count(),
            'average_repair_hours' => $closed->whereNotNull('tiempo_reparacion_horas')->avg('tiempo_reparacion_horas'),
            'total_intervention_cost' => round((float) $closed->sum('costo_total_intervencion'), 2),
            'top_pending_components' => $pending
                ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->componente?->nombre ?? 'Sin componente')
                ->map(fn (Collection $items, string $label) => ['label' => $label, 'total' => $items->count()])
                ->sortByDesc('total')
                ->values()
                ->take(5),
        ];
    }

    private function buildInterventionHistory(?int $lineaId, Carbon $from, Carbon $to): Collection
    {
        $query = AnalisisLavadora::query()
            ->with(['linea:id,nombre', 'componente:id,nombre,codigo', 'usuarioCorreccion:id,name'])
            ->whereIn('estado_correccion', [
                AnalisisLavadora::CORRECCION_CORREGIDO,
                AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO,
            ])
            ->whereBetween('fecha_correccion', [$from->toDateTimeString(), $to->toDateTimeString()]);

        if ($lineaId) {
            $query->where('linea_id', $lineaId);
        }

        return $query
            ->orderByDesc('fecha_correccion')
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(fn (AnalisisLavadora $analysis) => [
                'fecha' => $analysis->fecha_correccion?->format('d/m/Y H:i') ?? '-',
                'lavadora' => $analysis->linea?->nombre ?? '-',
                'componente' => $analysis->componente?->nombre ?? 'Sin componente',
                'estado' => $analysis->estado_correccion_label,
                'tipo_intervencion' => $analysis->tipo_intervencion ?: '-',
                'responsable' => $analysis->responsable_trabajo ?: ($analysis->usuarioCorreccion?->name ?? '-'),
                'horas' => $analysis->tiempo_reparacion_horas,
                'total' => $analysis->costo_total_intervencion,
            ]);
    }

    private function buildTrendSeries(Collection $entries, Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to);
        $daily = $days <= 45;
        $cursor = $daily ? $from->copy()->startOfDay() : $from->copy()->startOfMonth();
        $limit = $daily ? $to->copy()->endOfDay() : $to->copy()->startOfMonth();
        $totals = [];

        while ($cursor->lte($limit)) {
            $key = $daily ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $totals[$key] = 0;
            $cursor = $daily ? $cursor->addDay() : $cursor->addMonth();
        }

        foreach ($entries as $entry) {
            if (!$entry->cost_date) {
                continue;
            }

            $key = $daily
                ? $entry->cost_date->format('Y-m-d')
                : $entry->cost_date->copy()->startOfMonth()->format('Y-m');

            $totals[$key] = round(($totals[$key] ?? 0) + (float) $entry->total_cost, 2);
        }

        return [
            'labels' => collect(array_keys($totals))
                ->map(function (string $key) use ($daily) {
                    return $daily
                        ? Carbon::parse($key)->format('d/m')
                        : Carbon::parse($key . '-01')->translatedFormat('M Y');
                })
                ->values(),
            'values' => array_values($totals),
        ];
    }
}
