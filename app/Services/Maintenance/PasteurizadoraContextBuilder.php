<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;

class PasteurizadoraContextBuilder
{
    public function __construct(
        private readonly PasteurizadoraTechnicalContextRetriever $technicalContextRetriever,
        private readonly PromptSafetySanitizer $sanitizer
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(MaintenanceEvent $event): array
    {
        $current = $this->buildCurrentContext($event);
        $history = $this->buildHistory($event, $current);
        $technicalContext = $this->technicalContextRetriever->forEvent($event, $current);
        $knowledge = $this->buildKnowledge($technicalContext);

        return [
            'event' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'title' => $event->title,
                'description' => $event->description,
                'detected_value' => $event->detected_value,
                'limit_value' => $event->limit_value,
            ],
            'current' => $current,
            'history' => $history,
            'technical_context' => $technicalContext,
            'risk' => [
                'severity' => $event->severity,
                'status' => $event->status,
                'summary' => $this->sanitizer->sanitizeText($event->description, 1000),
            ],
            'costs' => [
                'recent_component_costs' => [],
                'totals' => null,
            ],
            'knowledge' => $knowledge,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCurrentContext(MaintenanceEvent $event): array
    {
        $analysis = null;
        $centralAnalysis = null;

        if ($event->source_type === 'analisis_pasteurizadora') {
            $analysis = AnalisisPasteurizadora::query()
                ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                ->with(['linea', 'usuario'])
                ->find($event->source_id);
        }

        if ($event->source_type === 'analisis_central_hidraulica') {
            $centralAnalysis = AnalisisCentralHidraulica::query()
                ->with(['linea', 'usuario', 'configuracion', 'componente'])
                ->find($event->source_id);
        }

        if ($centralAnalysis) {
            return [
                'linea_nombre' => $centralAnalysis->linea?->nombre,
                'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                'area_label' => PlanAccion::areasPasteurizadoraOpciones()[AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA],
                'component_name' => $centralAnalysis->componente_nombre,
                'component_code' => $centralAnalysis->componente?->codigo,
                'estado' => $centralAnalysis->estado,
                'tipo_registro' => $centralAnalysis->tipo_registro,
                'configuracion_id' => $centralAnalysis->configuracion_id,
                'componente_id' => $centralAnalysis->componente_id,
                'piso' => $centralAnalysis->piso,
                'piso_label' => $centralAnalysis->piso_label,
                'modulo' => null,
                'nivel' => $centralAnalysis->piso,
                'lado' => $centralAnalysis->lado,
                'lado_label' => $centralAnalysis->lado_label,
                'componentes_revisados' => $centralAnalysis->componentes_revisados_lista,
                'cantidad_componentes_revisados' => $centralAnalysis->cantidad_componentes_revisados,
                'total_componentes' => $centralAnalysis->total_componentes,
                'observaciones' => $this->sanitizer->sanitizeText($centralAnalysis->actividad, 1500),
                'nota_revision' => $this->sanitizer->sanitizeText($centralAnalysis->observaciones, 1000),
                'fecha_revision' => optional($centralAnalysis->fecha_analisis)->toDateString(),
                'orden' => $centralAnalysis->numero_orden,
                'evidencias' => $centralAnalysis->evidencia_fotos ?? [],
                'registrado_por' => $centralAnalysis->usuario?->name,
            ];
        }

        if (!$analysis) {
            return [
                'linea_nombre' => $event->linea?->nombre ?: data_get($event->context_data, 'linea_nombre'),
                'area' => data_get($event->context_data, 'area'),
                'area_label' => data_get($event->context_data, 'area_label'),
                'component_name' => data_get($event->context_data, 'component_name'),
                'component_code' => data_get($event->context_data, 'component_code'),
                'estado' => $event->detected_value,
                'modulo' => data_get($event->context_data, 'modulo'),
                'nivel' => data_get($event->context_data, 'nivel'),
                'lado' => data_get($event->context_data, 'lado'),
            ];
        }

        return [
            'linea_nombre' => $analysis->linea?->nombre,
            'area' => $analysis->area,
            'area_label' => PlanAccion::areasPasteurizadoraOpciones()[$analysis->area] ?? ucfirst((string) $analysis->area),
            'component_name' => $analysis->componente_nombre,
            'component_code' => $analysis->componente,
            'estado' => $analysis->estado,
            'tipo_registro' => $analysis->tipo_registro,
            'modulo' => $analysis->modulo,
            'nivel' => $analysis->nivel,
            'lado' => $analysis->lado,
            'componentes_revisados' => $analysis->componentes_revisados_lista,
            'cantidad_componentes_revisados' => $analysis->cantidad_componentes_revisados,
            'total_componentes' => $analysis->total_componentes,
            'observaciones' => $this->sanitizer->sanitizeText($analysis->actividad, 1500),
            'fecha_revision' => optional($analysis->fecha_analisis)->toDateString(),
            'orden' => $analysis->numero_orden,
            'evidencias' => $analysis->evidencia_fotos ?? [],
            'registrado_por' => $analysis->usuario?->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function buildHistory(MaintenanceEvent $event, array $current): array
    {
        if (($current['area'] ?? null) === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
            || $event->source_type === 'analisis_central_hidraulica'
        ) {
            return $this->buildCentralHistory($event, $current);
        }

        $analyses = AnalisisPasteurizadora::query()
            ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
            ->with(['linea', 'usuario'])
            ->where('linea_id', $event->linea_id)
            ->when($current['area'] ?? null, fn ($query, $area) => $query->where('area', $area))
            ->when($current['component_code'] ?? null, fn ($query, $component) => $query->where('componente', $component))
            ->when($current['modulo'] ?? null, fn ($query, $modulo) => $query->where('modulo', $modulo))
            ->when($current['nivel'] ?? null, fn ($query, $nivel) => $query->where('nivel', $nivel))
            ->when($current['lado'] ?? null, fn ($query, $lado) => $query->where('lado', $lado))
            ->latest('fecha_analisis')
            ->limit(5)
            ->get()
            ->map(fn (AnalisisPasteurizadora $analysis) => [
                'fecha' => optional($analysis->fecha_analisis)->toDateString(),
                'estado' => $analysis->estado,
                'actividad' => $this->sanitizer->sanitizeText($analysis->actividad, 400),
                'modulo' => $analysis->modulo,
                'nivel' => $analysis->nivel,
                'lado' => $analysis->lado,
                'componentes_revisados' => $analysis->componentes_revisados_lista,
            ])
            ->all();

        $plans = PlanAccion::query()
            ->where('linea_id', $event->linea_id)
            ->where('tipo_equipo', User::MODULE_PASTEURIZADORA)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PlanAccion $plan) => [
                'id' => $plan->id,
                'actividad' => $this->sanitizer->sanitizeText((string) $plan->actividad, 300),
                'estado' => $plan->estado,
                'source' => $plan->source,
                'area_pasteurizadora' => $plan->area_pasteurizadora,
                'completado' => (bool) $plan->completado,
                'fecha_ejecucion' => optional($plan->fecha_ejecucion)->toDateString(),
                'estimated_cost_total' => $plan->estimated_cost_total,
                'actual_cost_total' => $plan->actual_cost_total,
                'estimated_hours' => $plan->estimated_hours,
                'actual_hours' => $plan->actual_hours,
                'execution_result' => $this->sanitizer->sanitizeText((string) $plan->execution_result, 500),
                'effectiveness' => $plan->effectiveness,
                'effectiveness_label' => $plan->effectivenessLabel(),
            ])
            ->all();

        return [
            'recent_analyses' => $analyses,
            'recent_plans' => $plans,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function buildCentralHistory(MaintenanceEvent $event, array $current): array
    {
        $analyses = AnalisisCentralHidraulica::query()
            ->with(['linea', 'usuario', 'configuracion', 'componente'])
            ->where('linea_id', $event->linea_id)
            ->when($current['component_code'] ?? null, function ($query, string $componentCode): void {
                $query->whereHas('componente', fn ($componentQuery) => $componentQuery->where('codigo', $componentCode));
            })
            ->when($current['piso'] ?? $current['nivel'] ?? null, fn ($query, $piso) => $query->where('piso', $piso))
            ->when($current['lado'] ?? null, fn ($query, $lado) => $query->where('lado', $lado))
            ->latest('fecha_analisis')
            ->limit(5)
            ->get()
            ->map(fn (AnalisisCentralHidraulica $analysis) => [
                'fecha' => optional($analysis->fecha_analisis)->toDateString(),
                'estado' => $analysis->estado,
                'actividad' => $this->sanitizer->sanitizeText($analysis->actividad, 400),
                'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                'piso' => $analysis->piso,
                'nivel' => $analysis->piso,
                'lado' => $analysis->lado,
                'componentes_revisados' => $analysis->componentes_revisados_lista,
            ])
            ->all();

        $plans = PlanAccion::query()
            ->where('linea_id', $event->linea_id)
            ->where('tipo_equipo', User::MODULE_PASTEURIZADORA)
            ->where('area_pasteurizadora', AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PlanAccion $plan) => [
                'id' => $plan->id,
                'actividad' => $this->sanitizer->sanitizeText((string) $plan->actividad, 300),
                'estado' => $plan->estado,
                'source' => $plan->source,
                'area_pasteurizadora' => $plan->area_pasteurizadora,
                'completado' => (bool) $plan->completado,
                'fecha_ejecucion' => optional($plan->fecha_ejecucion)->toDateString(),
                'estimated_cost_total' => $plan->estimated_cost_total,
                'actual_cost_total' => $plan->actual_cost_total,
                'estimated_hours' => $plan->estimated_hours,
                'actual_hours' => $plan->actual_hours,
                'execution_result' => $this->sanitizer->sanitizeText((string) $plan->execution_result, 500),
                'effectiveness' => $plan->effectiveness,
                'effectiveness_label' => $plan->effectivenessLabel(),
            ])
            ->all();

        return [
            'recent_analyses' => $analyses,
            'recent_plans' => $plans,
        ];
    }

    /**
     * @param  array<string, mixed>  $technicalContext
     * @return array<int, array<string, mixed>>
     */
    private function buildKnowledge(array $technicalContext): array
    {
        return collect(data_get($technicalContext, 'historical_sources', []))
            ->take(5)
            ->map(fn (array $source): array => [
                'type' => data_get($source, 'type', 'revision'),
                'reference' => data_get($source, 'reference', 'Historial de pasteurizadora'),
                'content' => $this->sanitizer->sanitizeText(
                    json_encode(data_get($source, 'summary', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
                    1000
                ),
                'document_id' => null,
                'chunk_id' => null,
                'chunk_index' => null,
                'page' => null,
                'section' => data_get($source, 'bucket'),
                'linea' => data_get($source, 'summary.linea'),
                'componente' => data_get($source, 'summary.component.name'),
                'score_breakdown' => data_get($source, 'score_breakdown'),
            ])
            ->values()
            ->all();
    }
}
