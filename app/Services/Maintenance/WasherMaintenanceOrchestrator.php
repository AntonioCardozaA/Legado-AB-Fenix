<?php

namespace App\Services\Maintenance;

use App\Jobs\GenerateWasherActionPlan;
use App\Models\AnalisisLavadora;
use App\Models\Elongacion;
use App\Models\MaintenanceEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

class WasherMaintenanceOrchestrator
{
    public function __construct(
        private readonly WasherMaintenanceRuleEngine $ruleEngine
    ) {
    }

    /**
     * @return Collection<int, MaintenanceEvent>
     */
    public function processAnalysis(AnalisisLavadora $analysis): Collection
    {
        $analysis->loadMissing(['linea', 'componente', 'costEntries']);

        return $this->persist(
            $analysis,
            'analisis_lavadora',
            $analysis->linea_id,
            $analysis->componente_id,
            $this->ruleEngine->forAnalysis($analysis)
        );
    }

    /**
     * @return Collection<int, MaintenanceEvent>
     */
    public function processElongacion(Elongacion $elongacion): Collection
    {
        return $this->persist(
            $elongacion,
            'elongacion',
            $elongacion->linea_id,
            null,
            $this->ruleEngine->forElongacion($elongacion)
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $detections
     * @return Collection<int, MaintenanceEvent>
     */
    private function persist(
        Model $source,
        string $sourceType,
        ?int $lineaId,
        ?int $componenteId,
        Collection $detections
    ): Collection {
        return $detections->map(function (array $detection) use ($componenteId, $lineaId, $source, $sourceType) {
            $fingerprint = sha1(implode('|', [
                $sourceType,
                $source->getKey(),
                $detection['event_type'],
                $lineaId,
                $componenteId,
            ]));

            $event = MaintenanceEvent::query()->firstOrNew([
                'fingerprint' => $fingerprint,
            ]);

            $shouldDispatch = !$event->exists;

            $event->fill([
                'linea_id' => $lineaId,
                'componente_id' => $componenteId,
                'source_type' => $sourceType,
                'source_id' => $source->getKey(),
                'event_type' => $detection['event_type'],
                'severity' => $detection['severity'],
                'detected_value' => $detection['detected_value'],
                'limit_value' => $detection['limit_value'],
                'title' => $detection['title'],
                'description' => $detection['description'],
                'context_data' => $detection,
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
            GenerateWasherActionPlan::dispatch($event->id)
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
        $job = new GenerateWasherActionPlan($maintenanceEventId);

        try {
            $job->handle(app(WasherActionPlanGenerator::class));
        } catch (Throwable $exception) {
            $job->failed($exception);

            if ($rethrow) {
                throw $exception;
            }
        }
    }
}
