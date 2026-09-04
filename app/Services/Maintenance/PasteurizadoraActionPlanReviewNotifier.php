<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use App\Models\User;
use App\Notifications\PasteurizadoraAiPlanPendingReviewNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Support\Facades\Notification;

class PasteurizadoraActionPlanReviewNotifier
{
    public function __construct(
        private readonly NotificationRecipientService $notificationRecipientService
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

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new PasteurizadoraAiPlanPendingReviewNotification($plan));

        return $recipients->count();
    }
}
