<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WasherActionPlanGenerator
{
    public function __construct(
        private readonly WasherContextBuilder $contextBuilder,
        private readonly WasherActionPlanPromptBuilder $promptBuilder,
        private readonly StructuredActionPlanValidator $validator,
        private readonly AiProviderInterface $aiProvider,
        private readonly WasherActionPlanReviewNotifier $notifier
    ) {
    }

    public function generate(MaintenanceEvent $event): PlanAccion
    {
        $context = $this->contextBuilder->build($event);
        $prompt = $this->promptBuilder->build($context);
        $response = $this->aiProvider->generateStructuredActionPlan($prompt);
        $validated = $this->validator->validate($response['data']);

        /** @var PlanAccion $plan */
        $plan = DB::transaction(function () use ($context, $event, $prompt, $response, $validated) {
            $plan = $this->resolveDraftPlan($event);

            $plan->fill([
                'linea_id' => $event->linea_id,
                'actividad' => $validated['title'],
                'source' => 'ai',
                'maintenance_event_id' => $event->id,
                'tipo_equipo' => 'lavadora',
                'priority_level' => $validated['priority'],
                'maintenance_type' => $validated['maintenance_type'],
                'detected_problem' => $validated['detected_problem'],
                'technical_justification' => $validated['technical_justification'],
                'risk_if_not_executed' => $validated['risk_if_not_executed'],
                'missing_information' => $validated['missing_information'],
                'ai_provider' => $response['meta']['provider'] ?? config('maintenance_ai.provider'),
                'ai_model' => $response['meta']['model'] ?? null,
                'ai_original_response' => $response['raw'],
                'original_generated_content' => $validated,
                'approved_content' => $plan->approved_content,
                'knowledge_sources' => $validated['knowledge_sources'],
                'source_metadata' => [
                    'component_name' => $context['current']['component_name'] ?? null,
                    'linea_nombre' => $context['current']['linea_nombre'] ?? null,
                    'event_type' => $event->event_type,
                ],
                'confidence_level' => $validated['confidence'],
                'prompt_version' => $prompt['prompt_version'],
                'prompt_snapshot' => $prompt['prompt_snapshot'],
                'generated_at' => now(),
                'estado' => 'pending_review',
                'observaciones' => $validated['technical_justification'],
                'fecha_pcm1' => $validated['suggested_due_date'],
                'estimated_cost_total' => $validated['estimated_cost']['maximum'] ?? null,
                'estimated_hours' => null,
            ]);

            $plan->appendReviewHistory([
                'action' => 'generated',
                'performed_at' => now()->toIso8601String(),
                'provider' => $response['meta']['provider'] ?? config('maintenance_ai.provider'),
                'model' => $response['meta']['model'] ?? null,
            ]);

            $plan->save();

            return $plan->fresh(['linea', 'maintenanceEvent.componente']);
        });

        $this->notifier->notify($plan);

        return $plan;
    }

    public function createFailureFallback(MaintenanceEvent $event, Throwable $exception): PlanAccion
    {
        $context = $this->contextBuilder->build($event);
        $structured = $this->fallbackStructuredContent($event, $context, $exception);
        $error = $this->truncate($exception->getMessage(), 500);

        /** @var PlanAccion $plan */
        $plan = DB::transaction(function () use ($context, $event, $structured, $error) {
            $plan = $this->resolveDraftPlan($event);
            $shouldNotify = !$plan->exists;

            $plan->fill([
                'linea_id' => $event->linea_id,
                'actividad' => $structured['title'],
                'source' => 'ai',
                'maintenance_event_id' => $event->id,
                'tipo_equipo' => 'lavadora',
                'priority_level' => $structured['priority'],
                'maintenance_type' => $structured['maintenance_type'],
                'detected_problem' => $structured['detected_problem'],
                'technical_justification' => $structured['technical_justification'],
                'risk_if_not_executed' => $structured['risk_if_not_executed'],
                'missing_information' => $structured['missing_information'],
                'ai_provider' => config('maintenance_ai.provider'),
                'ai_model' => config('maintenance_ai.providers.' . config('maintenance_ai.provider') . '.model'),
                'ai_original_response' => [
                    'error' => $error,
                    'fallback' => true,
                ],
                'original_generated_content' => $structured,
                'approved_content' => $plan->approved_content,
                'knowledge_sources' => $structured['knowledge_sources'],
                'source_metadata' => [
                    'component_name' => $context['current']['component_name'] ?? null,
                    'linea_nombre' => $context['current']['linea_nombre'] ?? null,
                    'event_type' => $event->event_type,
                    'fallback_error' => $error,
                ],
                'confidence_level' => $structured['confidence'],
                'prompt_version' => config('maintenance_ai.prompt_version'),
                'prompt_snapshot' => [
                    'fallback' => true,
                    'reason' => 'ai_generation_failed',
                ],
                'generated_at' => $plan->generated_at ?? now(),
                'estado' => 'requires_information',
                'observaciones' => $structured['technical_justification'],
                'fecha_pcm1' => $structured['suggested_due_date'],
                'estimated_cost_total' => $structured['estimated_cost']['maximum'] ?? null,
                'estimated_hours' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'final_observations' => $error,
            ]);

            $plan->appendReviewHistory([
                'action' => 'generation_failed',
                'performed_at' => now()->toIso8601String(),
                'provider' => config('maintenance_ai.provider'),
                'model' => config('maintenance_ai.providers.' . config('maintenance_ai.provider') . '.model'),
                'error' => $error,
            ]);

            $plan->save();

            if ($shouldNotify) {
                $this->notifier->notify($plan);
            }

            return $plan->fresh(['linea', 'maintenanceEvent.componente']);
        });

        return $plan;
    }

    private function resolveDraftPlan(MaintenanceEvent $event): PlanAccion
    {
        return PlanAccion::query()
            ->where('maintenance_event_id', $event->id)
            ->where('source', 'ai')
            ->whereIn('estado', ['pending_review', 'requires_information'])
            ->latest('id')
            ->first()
            ?? new PlanAccion();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function fallbackStructuredContent(MaintenanceEvent $event, array $context, Throwable $exception): array
    {
        $component = $context['current']['component_name'] ?? 'componente';
        $linea = $context['current']['linea_nombre'] ?? 'lavadora';
        $error = $this->truncate($exception->getMessage(), 400);

        return [
            'title' => 'Revision manual requerida: ' . $component . ' en ' . $linea,
            'priority' => $this->fallbackPriority((string) ($event->severity ?? 'medium')),
            'maintenance_type' => $this->fallbackMaintenanceType($event->event_type),
            'detected_problem' => $event->description ?: ('Evento detectado: ' . $event->title),
            'technical_justification' => 'La sugerencia automatica no pudo completarse. Se requiere revision manual para definir el plan operativo. Error registrado: ' . $error,
            'recommended_actions' => [
                [
                    'order' => 1,
                    'activity' => 'Revisar manualmente el hallazgo y definir plan operativo',
                    'technical_detail' => 'Validar en campo el componente reportado, confirmar causa raiz y completar los pasos tecnicos necesarios.',
                ],
            ],
            'suggested_due_date' => now()->addDay()->toDateString(),
            'risk_if_not_executed' => 'El hallazgo permanece sin plan validado. Puede mantenerse el riesgo operativo hasta que mantenimiento revise el caso manualmente.',
            'estimated_cost' => [
                'minimum' => 0,
                'maximum' => 0,
                'currency' => 'MXN',
                'based_on_historical_data' => false,
            ],
            'knowledge_sources' => collect($context['knowledge'] ?? [])
                ->take(3)
                ->map(fn (array $item): array => [
                    'type' => $item['type'] ?? 'revision',
                    'reference' => $this->truncate((string) ($item['reference'] ?? 'Contexto del evento'), 255),
                    'document_id' => $item['document_id'] ?? null,
                    'page' => $item['page'] ?? null,
                    'section' => $item['section'] ?? null,
                ])
                ->values()
                ->all(),
            'confidence' => 0.15,
            'requires_human_approval' => true,
            'missing_information' => [
                'La IA no pudo generar la sugerencia automaticamente.',
                'Revisar el error tecnico registrado y completar manualmente el plan.',
            ],
        ];
    }

    private function fallbackPriority(string $severity): string
    {
        return match ($severity) {
            'critical' => 'critical',
            'high' => 'high',
            'medium' => 'medium',
            default => 'low',
        };
    }

    private function fallbackMaintenanceType(string $eventType): string
    {
        return match ($eventType) {
            'component_damaged', 'elongation_above_limit' => 'corrective',
            'component_moderate_wear', 'component_severe_wear', 'elongation_near_limit', 'rodaja_out_of_tolerance' => 'preventive',
            'elongation_ascending_trend' => 'predictive',
            default => 'inspection',
        };
    }

    private function truncate(?string $value, int $limit): string
    {
        return Str::limit(trim((string) $value), $limit, '');
    }
}
