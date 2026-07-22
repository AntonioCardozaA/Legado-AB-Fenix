<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use App\Models\User;
use App\Notifications\WasherAiPlanPendingReviewNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Support\Facades\Notification;

class WasherActionPlanReviewNotifier
{
    public function __construct(
        private readonly NotificationRecipientService $notificationRecipientService
    ) {
    }

    public function notify(PlanAccion $plan): int
    {
        $plan->loadMissing(['linea', 'maintenanceEvent.componente']);
        $lineaId = $plan->linea_id !== null ? (int) $plan->linea_id : null;

        $recipients = $this->notificationRecipientService
            ->getInternalRecipients()
            ->filter(function (array $recipient) use ($lineaId): bool {
                /** @var User $user */
                $user = $recipient['user'];

                return $user->canReviewWasherAiPlans()
                    && $this->notificationRecipientService->shouldNotifyForLine($recipient['settings'], $lineaId);
            })
            ->pluck('user')
            ->values();

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new WasherAiPlanPendingReviewNotification($plan));

        return $recipients->count();
    }
}
