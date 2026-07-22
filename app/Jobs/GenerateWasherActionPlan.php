<?php

namespace App\Jobs;

use App\Models\MaintenanceEvent;
use App\Services\Maintenance\WasherActionPlanGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateWasherActionPlan implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public readonly int $maintenanceEventId
    ) {
        $this->tries = max(1, (int) config('maintenance_ai.max_retries', 2) + 1);
        $this->timeout = $this->resolveTimeout();
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WasherActionPlanGenerator $generator): void
    {
        if (!(bool) config('maintenance_ai.enabled', false)) {
            return;
        }

        $event = MaintenanceEvent::query()->find($this->maintenanceEventId);

        if (!$event) {
            return;
        }

        $event->update(['status' => MaintenanceEvent::STATUS_PROCESSING]);

        $generator->generate($event);

        $event->update(['status' => MaintenanceEvent::STATUS_PLAN_GENERATED]);
    }

    public function failed(Throwable $exception): void
    {
        $event = MaintenanceEvent::query()->find($this->maintenanceEventId);

        if ($event) {
            $context = $event->context_data ?? [];
            $context['last_error'] = mb_substr($exception->getMessage(), 0, 500);
            $context['failed_at'] = now()->toIso8601String();

            $event->update([
                'status' => MaintenanceEvent::STATUS_REQUIRES_INFORMATION,
                'context_data' => $context,
            ]);

            app(WasherActionPlanGenerator::class)->createFailureFallback($event, $exception);
        }

        Log::warning('Failed to generate washer AI action plan.', [
            'maintenance_event_id' => $this->maintenanceEventId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveTimeout(): int
    {
        $configured = config('maintenance_ai.job_timeout');

        if (is_numeric($configured)) {
            return max(10, (int) $configured);
        }

        $requestTimeout = max(10, (int) config('maintenance_ai.timeout', 30));
        $httpAttempts = max(1, (int) config('maintenance_ai.max_retries', 2) + 1);
        $providerAttempts = $this->providerAttemptCount();

        return max(10, ($requestTimeout * $httpAttempts * $providerAttempts) + 15);
    }

    private function providerAttemptCount(): int
    {
        $provider = trim((string) config('maintenance_ai.provider', 'openai'));
        $attempts = 1 + count((array) data_get(config('maintenance_ai'), 'providers.' . $provider . '.fallback_models', []));
        $fallbackProvider = trim((string) config('maintenance_ai.fallback.provider', ''));

        if ($fallbackProvider !== '' && $fallbackProvider !== $provider) {
            $attempts += 1 + count((array) data_get(config('maintenance_ai'), 'providers.' . $fallbackProvider . '.fallback_models', []));
        }

        return max(1, $attempts);
    }
}
