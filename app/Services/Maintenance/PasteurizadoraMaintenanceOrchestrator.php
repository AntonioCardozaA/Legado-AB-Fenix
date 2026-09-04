<?php

namespace App\Services\Maintenance;

use App\Jobs\GeneratePasteurizadoraActionPlan;
use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use Illuminate\Support\Collection;
use Throwable;

class PasteurizadoraMaintenanceOrchestrator
{
    public function __construct(
        private readonly PasteurizadoraMaintenanceRuleEngine $ruleEngine
    ) {
    }

    /**
     * @return Collection<int, MaintenanceEvent>
     */
    public function processAnalysis(AnalisisPasteurizadora $analysis): Collection
    {
        $analysis->loadMissing(['linea', 'usuario']);

        return $this->persist(
            $analysis,
            $this->ruleEngine->forAnalysis($analysis)
        );
    }

    /**
     * @return Collection<int, MaintenanceEvent>
     */
    public function processCentralAnalysis(AnalisisCentralHidraulica $analysis): Collection
    {
        $analysis->loadMissing(['linea', 'usuario', 'configuracion', 'componente']);

        return $this->persistCentral(
            $analysis,
            $this->ruleEngine->forCentralAnalysis($analysis)
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $detections
     * @return Collection<int, MaintenanceEvent>
     */
    private function persist(AnalisisPasteurizadora $source, Collection $detections): Collection
    {
        return $detections->map(function (array $detection) use ($source) {
            $context = $this->eventContext($source, $detection);
            $fingerprint = sha1(implode('|', [
                'analisis_pasteurizadora',
                $source->getKey(),
                $detection['event_type'],
                $source->linea_id,
                $source->area,
                $source->modulo,
                $source->nivel,
                $source->componente,
                $source->lado,
            ]));

            $event = MaintenanceEvent::query()->firstOrNew([
                'fingerprint' => $fingerprint,
            ]);

            $shouldDispatch = !$event->exists;

            $event->fill([
                'linea_id' => $source->linea_id,
                'componente_id' => null,
                'source_type' => 'analisis_pasteurizadora',
                'source_id' => $source->getKey(),
                'event_type' => $detection['event_type'],
                'severity' => $detection['severity'],
                'detected_value' => $detection['detected_value'],
                'limit_value' => $detection['limit_value'],
                'title' => $detection['title'],
                'description' => $detection['description'],
                'context_data' => $context,
            ]);

            if (!$event->exists) {
                $event->status = MaintenanceEvent::STATUS_DETECTED;
                $event->detected_at = now();
            }

            $event->save();

            if ($this->shouldDispatchPlan($event, $shouldDispatch)) {
                $this->dispatchPlan($event);
            }

            return $event;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $detections
     * @return Collection<int, MaintenanceEvent>
     */
    private function persistCentral(AnalisisCentralHidraulica $source, Collection $detections): Collection
    {
        return $detections->map(function (array $detection) use ($source) {
            $context = $this->centralEventContext($source, $detection);
            $fingerprint = sha1(implode('|', [
                'analisis_central_hidraulica',
                $source->getKey(),
                $detection['event_type'],
                $source->linea_id,
                $source->piso,
                $source->componente_id,
                $source->lado,
            ]));

            $event = MaintenanceEvent::query()->firstOrNew([
                'fingerprint' => $fingerprint,
            ]);

            $shouldDispatch = !$event->exists;

            $event->fill([
                'linea_id' => $source->linea_id,
                'componente_id' => null,
                'source_type' => 'analisis_central_hidraulica',
                'source_id' => $source->getKey(),
                'event_type' => $detection['event_type'],
                'severity' => $detection['severity'],
                'detected_value' => $detection['detected_value'],
                'limit_value' => $detection['limit_value'],
                'title' => $detection['title'],
                'description' => $detection['description'],
                'context_data' => $context,
            ]);

            if (!$event->exists) {
                $event->status = MaintenanceEvent::STATUS_DETECTED;
                $event->detected_at = now();
            }

            $event->save();

            if ($this->shouldDispatchPlan($event, $shouldDispatch)) {
                $this->dispatchPlan($event);
            }

            return $event;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $detection
     * @return array<string, mixed>
     */
    private function eventContext(AnalisisPasteurizadora $analysis, array $detection): array
    {
        return array_merge($detection, [
            'area' => $analysis->area,
            'area_label' => PlanAccion::areasPasteurizadoraOpciones()[$analysis->area] ?? ucfirst((string) $analysis->area),
            'linea_nombre' => $analysis->linea?->nombre,
            'component_code' => $analysis->componente,
            'component_name' => $analysis->componente_nombre,
            'modulo' => $analysis->modulo,
            'nivel' => $analysis->nivel,
            'lado' => $analysis->lado,
            'tipo_registro' => $analysis->tipo_registro,
            'numero_orden' => $analysis->numero_orden,
            'actividad' => $analysis->actividad,
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'componentes_revisados' => $analysis->componentes_revisados_lista,
            'cantidad_componentes_revisados' => $analysis->cantidad_componentes_revisados,
            'total_componentes' => $analysis->total_componentes,
            'evidence_count' => count($analysis->evidencia_fotos ?? []),
        ]);
    }

    /**
     * @param  array<string, mixed>  $detection
     * @return array<string, mixed>
     */
    private function centralEventContext(AnalisisCentralHidraulica $analysis, array $detection): array
    {
        return array_merge($detection, [
            'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'area_label' => PlanAccion::areasPasteurizadoraOpciones()[AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA],
            'linea_nombre' => $analysis->linea?->nombre,
            'component_code' => $analysis->componente?->codigo,
            'component_name' => $analysis->componente_nombre,
            'configuracion_id' => $analysis->configuracion_id,
            'componente_id' => $analysis->componente_id,
            'piso' => $analysis->piso,
            'piso_label' => $analysis->piso_label,
            'modulo' => null,
            'nivel' => $analysis->piso,
            'lado' => $analysis->lado,
            'lado_label' => $analysis->lado_label,
            'tipo_registro' => $analysis->tipo_registro,
            'numero_orden' => $analysis->numero_orden,
            'actividad' => $analysis->actividad,
            'observaciones' => $analysis->observaciones,
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'componentes_revisados' => $analysis->componentes_revisados_lista,
            'cantidad_componentes_revisados' => $analysis->cantidad_componentes_revisados,
            'total_componentes' => $analysis->total_componentes,
            'evidence_count' => count($analysis->evidencia_fotos ?? []),
        ]);
    }

    private function shouldDispatchPlan(MaintenanceEvent $event, bool $shouldDispatch): bool
    {
        if (!(bool) config('maintenance_ai.enabled', false) || !$shouldDispatch) {
            return false;
        }

        $existingPlans = $event->planesAccion()
            ->where('source', 'ai')
            ->whereIn('estado', ['pending_review', 'requires_information', 'approved'])
            ->count();

        return $existingPlans < (int) config('maintenance_ai.max_plans_per_event', 1);
    }

    private function dispatchPlan(MaintenanceEvent $event): void
    {
        $queue = (string) config('maintenance_ai.queue', 'default');
        $mode = $this->dispatchMode();

        if ($mode === 'queue') {
            GeneratePasteurizadoraActionPlan::dispatch($event->id)
                ->onQueue($queue);

            return;
        }

        if ($mode === 'after_response' && !app()->runningInConsole()) {
            app()->terminating(function () use ($event): void {
                $this->runPlanGenerationInline($event->id);
            });

            return;
        }

        $this->runPlanGenerationInline($event->id, $mode === 'sync');
    }

    private function dispatchMode(): string
    {
        $mode = strtolower((string) config('maintenance_ai.dispatch_mode', 'queue'));
        $allowed = ['queue', 'sync', 'after_response'];

        if (!in_array($mode, $allowed, true)) {
            return 'queue';
        }

        return $mode;
    }

    private function runPlanGenerationInline(int $maintenanceEventId, bool $rethrow = false): void
    {
        $job = new GeneratePasteurizadoraActionPlan($maintenanceEventId);

        try {
            $job->handle(app(PasteurizadoraActionPlanGenerator::class));
        } catch (Throwable $exception) {
            $job->failed($exception);

            if ($rethrow) {
                throw $exception;
            }
        }
    }
}
