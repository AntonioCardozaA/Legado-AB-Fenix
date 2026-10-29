<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use App\Models\User;
use App\Notifications\PasteurizadoraAiPlanPendingReviewNotification;
use App\Services\NotificationRecipientService;
use App\Services\ConfiguredAlertRecipientService;
use Illuminate\Support\Facades\Notification;

class PasteurizadoraActionPlanReviewNotifier
{
    public function __construct(
        private readonly NotificationRecipientService $notificationRecipientService,
        private readonly ConfiguredAlertRecipientService $configuredAlertRecipientService
    ) {
    }

    public function notify(PlanAccion $plan): int
    {
        $plan->loadMissing(['linea', 'maintenanceEvent.componente']);
        $lineaId = $plan->linea_id !== null ? (int) $plan->linea_id : null;
        $area = $plan->area_pasteurizadora;

        $recipients = $this->notificationRecipientService
            ->getInternalRecipients()
            ->filter(function (array $recipient) use ($area, $lineaId): bool {
                /** @var User $user */
                $user = $recipient['user'];

                return $user->canReviewPasteurizadoraAiPlans($area)
                    && $this->notificationRecipientService->shouldNotifyForLine($recipient['settings'], $lineaId);
            })
            ->pluck('user')
            ->values();

        $component = data_get($plan->source_metadata, 'component_name')
            ?? data_get($plan->maintenanceEvent?->context_data, 'component_name')
            ?? 'Componente de pasteurizadora';
        $this->configuredAlertRecipientService->dispatch(
            'pasteurizadora_ai_plan_pending_review',
            sprintf('Nuevo plan sugerido por IA pendiente de revisión para %s - %s.', $plan->linea?->nombre ?? 'pasteurizadora', $component),
            'pasteurizadora-ai-plan:' . $plan->id,
            ['plan_id' => $plan->id, 'linea_id' => $plan->linea_id, 'area' => $area]
        );

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new PasteurizadoraAiPlanPendingReviewNotification($plan));

        return $recipients->count();
    }
}
