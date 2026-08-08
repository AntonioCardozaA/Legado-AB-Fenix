<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CronQueueController extends Controller
{
    public function queue(string $token): JsonResponse
    {
        $configuredToken = trim((string) config('maintenance_ai.cron.queue_token', ''));

        if ($configuredToken === '' || !hash_equals($configuredToken, $token)) {
            abort(404);
        }

        $lock = Cache::lock(
            'cron:maintenance-ai-queue-worker',
            max(30, (int) config('maintenance_ai.cron.queue_lock_seconds', 55))
        );

        if (!$lock->get()) {
            return response()->json([
                'ok' => true,
                'status' => 'skipped',
                'reason' => 'worker_already_running',
            ]);
        }

        try {
            $exitCode = Artisan::call('queue:work', [
                '--queue' => (string) config('maintenance_ai.cron.queue_names', 'maintenance-ai,default'),
                '--tries' => max(1, (int) config('maintenance_ai.cron.queue_tries', 3)),
                '--timeout' => max(10, (int) config('maintenance_ai.cron.queue_timeout', 45)),
                '--max-time' => max(10, (int) config('maintenance_ai.cron.queue_max_time', 50)),
                '--stop-when-empty' => true,
            ]);

            return response()->json([
                'ok' => $exitCode === 0,
                'status' => $exitCode === 0 ? 'processed' : 'failed',
                'exit_code' => $exitCode,
                'output' => trim(Artisan::output()),
            ], $exitCode === 0 ? 200 : 500);
        } finally {
            optional($lock)->release();
        }
    }
}
