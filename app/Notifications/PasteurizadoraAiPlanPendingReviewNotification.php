<?php

namespace App\Notifications;

use App\Models\PlanAccion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PasteurizadoraAiPlanPendingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PlanAccion $plan
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $component = data_get($this->plan->source_metadata, 'component_name')
            ?? data_get($this->plan->maintenanceEvent?->context_data, 'component_name')
            ?? 'Componente de pasteurizadora';

        return [
            'type' => 'pasteurizadora_ai_plan_pending_review',
            'plan_id' => $this->plan->id,
            'maintenance_event_id' => $this->plan->maintenance_event_id,
            'linea_id' => $this->plan->linea_id,
            'linea_nombre' => $this->plan->linea?->nombre,
            'component_name' => $component,
            'area' => $this->plan->area_pasteurizadora,
            'area_label' => $this->plan->area_pasteurizadora_label,
            'priority' => $this->plan->priority_level,
            'message' => sprintf(
                'Nuevo plan sugerido por IA pendiente de revision para %s - %s.',
                $this->plan->linea?->nombre ?? 'pasteurizadora',
                $component
            ),
            'url' => route('plan-accion.ai.pasteurizadora.review', ['planAccion' => $this->plan->id]),
        ];
    }
}
