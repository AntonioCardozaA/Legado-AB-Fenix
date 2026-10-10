<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiProviderCircuitBreaker
{
    public function isOpen(string $provider, ?string $model, string $flow): bool
    {
        if (! (bool) config('maintenance_ai.circuit_breaker.enabled', true)) {
            return false;
        }

        return Cache::has($this->openKey($provider, $model, $flow));
    }

    public function recordSuccess(string $provider, ?string $model, string $flow): void
    {
        Cache::forget($this->failureKey($provider, $model, $flow, 'availability'));
        Cache::forget($this->failureKey($provider, $model, $flow, 'quality'));
        Cache::forget($this->openKey($provider, $model, $flow));
    }

    public function recordFailure(string $provider, ?string $model, string $flow, string $category): void
    {
        if (! (bool) config('maintenance_ai.circuit_breaker.enabled', true)) {
            return;
        }

        $category = in_array($category, ['quality', 'availability'], true) ? $category : 'availability';
        $key = $this->failureKey($provider, $model, $flow, $category);
        $count = (int) Cache::get($key, 0) + 1;
        $windowSeconds = max(30, (int) config('maintenance_ai.circuit_breaker.failure_window_seconds', 300));

        Cache::put($key, $count, now()->addSeconds($windowSeconds));

        $threshold = $category === 'quality'
            ? (int) config('maintenance_ai.circuit_breaker.quality_failure_threshold', 2)
            : (int) config('maintenance_ai.circuit_breaker.availability_failure_threshold', 3);

        if ($count >= max(1, $threshold)) {
            Cache::put(
                $this->openKey($provider, $model, $flow),
                [
                    'provider' => $provider,
                    'model' => $model,
                    'flow' => $flow,
                    'category' => $category,
                    'opened_at' => now()->toIso8601String(),
                    'failure_count' => $count,
                ],
                now()->addSeconds(max(30, (int) config('maintenance_ai.circuit_breaker.open_seconds', 120)))
            );
        }
    }

    public function state(string $provider, ?string $model, string $flow): array
    {
        return [
            'open' => $this->isOpen($provider, $model, $flow),
            'availability_failures' => (int) Cache::get($this->failureKey($provider, $model, $flow, 'availability'), 0),
            'quality_failures' => (int) Cache::get($this->failureKey($provider, $model, $flow, 'quality'), 0),
        ];
    }

    private function openKey(string $provider, ?string $model, string $flow): string
    {
        return 'ai:circuit:open:'.$this->slug($provider, $model, $flow);
    }

    private function failureKey(string $provider, ?string $model, string $flow, string $category): string
    {
        return 'ai:circuit:failures:'.$category.':'.$this->slug($provider, $model, $flow);
    }

    private function slug(string $provider, ?string $model, string $flow): string
    {
        return Str::slug($flow.'-'.$provider.'-'.($model ?: 'default'), '-');
    }
}
