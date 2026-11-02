<?php

namespace App\Support;

use App\Jobs\GeneratePasteurizadoraActionPlan;
use App\Jobs\GenerateWasherActionPlan;
use App\Jobs\IndexMaintenanceHistoryRecord;
use App\Jobs\SendLavadoraDamageWhatsApp;
use App\Jobs\SendElongacionWhatsApp;

final class QueueExecutionBudget
{
    public static function jobTimeouts(): array
    {
        // Constructors only read configuration; no jobs are dispatched here.
        return [
            'washer_plan' => (new GenerateWasherActionPlan(0))->timeout,
            'pasteurizer_plan' => (new GeneratePasteurizadoraActionPlan(0))->timeout,
            'history_index' => (new IndexMaintenanceHistoryRecord('lavadora', 'analisis_lavadora', 0))->timeout,
            'whatsapp' => (new SendLavadoraDamageWhatsApp('', ''))->timeout,
            'elongation_whatsapp' => (new SendElongacionWhatsApp('', ''))->timeout,
        ];
    }

    public static function workerTimeout(): int
    {
        return max(10, (int) config('maintenance_ai.cron.queue_timeout', 45), ...array_values(self::jobTimeouts()));
    }

    public static function cronLockSeconds(): int
    {
        // max-time is checked between jobs; one last job may consume its full timeout.
        return max(
            30,
            (int) config('maintenance_ai.cron.queue_lock_seconds', 55),
            max(10, (int) config('maintenance_ai.cron.queue_max_time', 50)) + self::workerTimeout() + 30
        );
    }

    public static function minimumRetryAfter(int $workerTimeout): int
    {
        return max($workerTimeout, ...array_values(self::jobTimeouts())) + 30;
    }
}
