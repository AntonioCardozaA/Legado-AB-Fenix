<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use App\Models\User;
use App\Notifications\WasherAiPlanPendingReviewNotification;
use App\Services\NotificationRecipientService;
use App\Services\ConfiguredAlertRecipientService;
use Illuminate\Support\Facades\Notification;

class WasherActionPlanReviewNotifier
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

        $recipients = $this->notificationRecipientService
            ->getInternalRecipients('washer_ai_plan_pending_review')
            ->filter(function (array $recipient) use ($lineaId): bool {
                /** @var User $user */
                $user = $recipient['user'];

                return $user->canReviewWasherAiPlans()
                    && $this->notificationRecipientService->shouldNotifyForLine($recipient['settings'], $lineaId);
            })
            ->pluck('user')
            ->values();

        $component = $plan->maintenanceEvent?->componente?->nombre ?? 'Cadena de lavadora';
        $this->configuredAlertRecipientService->dispatch(
            'washer_ai_plan_pending_review',
            sprintf('Nuevo plan sugerido por IA pendiente de revisión para %s - %s.', $plan->linea?->nombre ?? 'lavadora', $component),
            'washer-ai-plan:' . $plan->id,
            ['plan_id' => $plan->id, 'linea_id' => $plan->linea_id]
        );

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new WasherAiPlanPendingReviewNotification($plan));

        return $recipients->count();
    }
}
