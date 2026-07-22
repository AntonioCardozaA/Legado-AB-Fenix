<?php

namespace App\Notifications;

use App\Models\PlanAccion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WasherAiPlanPendingReviewNotification extends Notification implements ShouldQueue
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
        $component = $this->plan->maintenanceEvent?->componente?->nombre ?? 'Cadena de lavadora';

        return [
            'type' => 'washer_ai_plan_pending_review',
            'plan_id' => $this->plan->id,
            'maintenance_event_id' => $this->plan->maintenance_event_id,
            'linea_id' => $this->plan->linea_id,
            'linea_nombre' => $this->plan->linea?->nombre,
            'component_name' => $component,
            'priority' => $this->plan->priority_level,
            'message' => sprintf(
                'Nuevo plan sugerido por IA pendiente de revision para %s - %s.',
                $this->plan->linea?->nombre ?? 'lavadora',
                $component
            ),
            'url' => route('plan-accion.ai.review', ['planAccion' => $this->plan->id]),
        ];
    }
}
