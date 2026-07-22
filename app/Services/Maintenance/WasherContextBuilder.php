<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisLavadora;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;

class WasherContextBuilder
{
    public function __construct(
        private readonly KnowledgeRetriever $knowledgeRetriever,
        private readonly PromptSafetySanitizer $sanitizer
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(MaintenanceEvent $event): array
    {
        $current = $this->buildCurrentContext($event);
        $history = $this->buildHistory($event);
        $costs = $this->buildCosts($event);
        $knowledge = $this->knowledgeRetriever->retrieveForEvent($event, [
            'component_name' => $current['component_name'] ?? null,
            'linea_nombre' => $current['linea_nombre'] ?? null,
            'estado' => $current['estado'] ?? null,
        ]);

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
            'risk' => [
                'severity' => $event->severity,
                'status' => $event->status,
                'summary' => $this->sanitizer->sanitizeText($event->description, 1000),
            ],
            'costs' => $costs,
            'knowledge' => $knowledge,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCurrentContext(MaintenanceEvent $event): array
    {
        if ($event->source_type === 'analisis_lavadora') {
            $analysis = AnalisisLavadora::query()
                ->with(['linea', 'componente'])
                ->find($event->source_id);

            return [
                'linea_nombre' => $analysis?->linea?->nombre,
                'component_name' => $analysis?->componente?->nombre,
                'component_code' => $analysis?->componente?->codigo,
                'estado' => $analysis?->estado,
                'reductor' => $analysis?->reductor,
                'lado' => $analysis?->lado,
                'observaciones' => $this->sanitizer->sanitizeText($analysis?->actividad, 1500),
                'fecha_revision' => optional($analysis?->fecha_analisis)->toDateString(),
                'orden' => $analysis?->numero_orden,
                'evidencias' => $analysis?->evidencia_fotos ?? [],
            ];
        }

        $elongacion = Elongacion::query()
            ->with(['lineaModel', 'cadenaCiclo'])
            ->find($event->source_id);

        return [
            'linea_nombre' => $elongacion?->linea ?: $elongacion?->lineaModel?->nombre,
            'component_name' => 'Cadena de lavadora',
            'estado' => $elongacion?->estado_detallado,
            'bombas_porcentaje' => $elongacion?->bombas_porcentaje,
            'vapor_porcentaje' => $elongacion?->vapor_porcentaje,
            'juego_rodaja_bombas' => $elongacion?->juego_rodaja_bombas,
            'juego_rodaja_vapor' => $elongacion?->juego_rodaja_vapor,
            'hodometro' => $elongacion?->hodometro,
            'hodometro_ciclo' => $elongacion?->hodometro_ciclo,
            'fecha_revision' => optional($elongacion?->created_at)->toDateString(),
            'revision_due_at' => optional($elongacion?->revision_due_at)->toDateString(),
            'revision_status' => $elongacion?->revision_status,
            'ciclo' => $elongacion?->cadenaCiclo?->codigo,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHistory(MaintenanceEvent $event): array
    {
        $analyses = AnalisisLavadora::query()
            ->with(['linea', 'componente'])
            ->where('linea_id', $event->linea_id)
            ->when($event->componente_id, fn ($query) => $query->where('componente_id', $event->componente_id))
            ->latest('fecha_analisis')
            ->limit(5)
            ->get()
            ->map(fn (AnalisisLavadora $analysis) => [
                'fecha' => optional($analysis->fecha_analisis)->toDateString(),
                'estado' => $analysis->estado,
                'actividad' => $this->sanitizer->sanitizeText($analysis->actividad, 400),
            ])
            ->all();

        $elongaciones = Elongacion::query()
            ->where('linea_id', $event->linea_id)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Elongacion $item) => [
                'fecha' => optional($item->created_at)->toDateString(),
                'bombas_porcentaje' => (float) $item->bombas_porcentaje,
                'vapor_porcentaje' => (float) $item->vapor_porcentaje,
                'revision_status' => $item->revision_status,
            ])
            ->all();

        $plans = PlanAccion::query()
            ->where('linea_id', $event->linea_id)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PlanAccion $plan) => [
                'id' => $plan->id,
                'actividad' => $this->sanitizer->sanitizeText((string) $plan->actividad, 300),
                'estado' => $plan->estado,
                'source' => $plan->source,
            ])
            ->all();

        return [
            'recent_analyses' => $analyses,
            'recent_elongaciones' => $elongaciones,
            'recent_plans' => $plans,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCosts(MaintenanceEvent $event): array
    {
        if (!$event->componente_id) {
            return [
                'recent_component_costs' => [],
                'totals' => null,
            ];
        }

        $entries = LavadoraCostEntry::query()
            ->where('linea_id', $event->linea_id)
            ->where('componente_id', $event->componente_id)
            ->latest('cost_date')
            ->limit(10)
            ->get();

        return [
            'recent_component_costs' => $entries->map(fn (LavadoraCostEntry $entry) => [
                'date' => optional($entry->cost_date)->toDateString(),
                'total_cost' => $entry->total_cost,
                'source_type' => $entry->source_type,
                'component_snapshot' => $entry->component_snapshot,
            ])->all(),
            'totals' => [
                'count' => $entries->count(),
                'sum' => round((float) $entries->sum('total_cost'), 2),
                'avg' => round((float) $entries->avg('total_cost'), 2),
            ],
        ];
    }
}
