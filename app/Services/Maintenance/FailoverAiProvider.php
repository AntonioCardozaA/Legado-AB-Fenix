<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class FailoverAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly GeminiProvider $geminiProvider,
        private readonly OpenAiProvider $openAiProvider,
        private readonly NullAiProvider $nullAiProvider
    ) {
    }

    public function generateStructuredActionPlan(array $payload): array
    {
        $lastException = null;

        foreach ($this->providerChain() as $providerName) {
            foreach ($this->generationModelChain($providerName, $payload['model'] ?? null) as $model) {
                $attemptPayload = $payload;
                $attemptPayload['model'] = $model;

                try {
                    return $this->provider($providerName)->generateStructuredActionPlan($attemptPayload);
                } catch (Throwable $exception) {
                    $lastException = $exception;

                    if (!$this->shouldTryNextAttempt($exception)) {
                        throw $exception;
                    }
                }
            }
        }

        throw $lastException ?? new RuntimeException('No AI provider is configured.');
    }

    public function createEmbedding(string $content): array
    {
        $lastException = null;

        foreach ($this->providerChain() as $providerName) {
            try {
                return $this->provider($providerName)->createEmbedding($content);
            } catch (Throwable $exception) {
                $lastException = $exception;

                if (!$this->shouldTryNextAttempt($exception)) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?? new RuntimeException('No AI provider is configured.');
    }

    public function extractDocumentText(array $payload): string
    {
        $lastException = null;

        foreach ($this->providerChain() as $providerName) {
            foreach ($this->generationModelChain($providerName, $payload['model'] ?? null) as $model) {
                $attemptPayload = $payload;
                $attemptPayload['model'] = $model;

                try {
                    return $this->provider($providerName)->extractDocumentText($attemptPayload);
                } catch (Throwable $exception) {
                    $lastException = $exception;

                    if (!$this->shouldTryNextAttempt($exception)) {
                        throw $exception;
                    }
                }
            }
        }

        throw $lastException ?? new RuntimeException('No AI provider is configured.');
    }

    /**
     * @return array<int, string>
     */
    private function providerChain(): array
    {
        $chain = [];
        $primaryProvider = trim((string) config('maintenance_ai.provider', 'openai'));
        $fallbackProvider = trim((string) config('maintenance_ai.fallback.provider', ''));

        foreach ([$primaryProvider, $fallbackProvider] as $providerName) {
            if ($providerName === '' || in_array($providerName, $chain, true)) {
                continue;
            }

            if ($this->hasConfiguredProvider($providerName)) {
                $chain[] = $providerName;
            }
        }

        return $chain === [] ? ['null'] : $chain;
    }

    /**
     * @return array<int, string>
     */
    private function generationModelChain(string $providerName, ?string $requestedModel): array
    {
        $models = [];
        $primaryModel = $requestedModel ?: data_get(config('maintenance_ai'), 'providers.' . $providerName . '.model');

        if (is_string($primaryModel) && trim($primaryModel) !== '') {
            $models[] = $this->normalizeModelName($primaryModel);
        }

        foreach ((array) data_get(config('maintenance_ai'), 'providers.' . $providerName . '.fallback_models', []) as $model) {
            if (!is_string($model) || trim($model) === '') {
                continue;
            }

            $models[] = $this->normalizeModelName($model);
        }

        return array_values(array_unique($models));
    }

    private function hasConfiguredProvider(string $providerName): bool
    {
        if ($providerName === 'null') {
            return true;
        }

        $apiKey = trim((string) data_get(config('maintenance_ai'), 'providers.' . $providerName . '.api_key', ''));
        $baseUrl = trim((string) data_get(config('maintenance_ai'), 'providers.' . $providerName . '.base_url', ''));

        return $apiKey !== '' && $baseUrl !== '';
    }

    private function provider(string $providerName): AiProviderInterface
    {
        return match ($providerName) {
            'gemini' => $this->geminiProvider,
            'openai' => $this->openAiProvider,
            default => $this->nullAiProvider,
        };
    }

    private function shouldTryNextAttempt(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response?->status();
            $transientStatuses = (array) config('maintenance_ai.fallback.transient_statuses', [408, 429, 500, 502, 503, 504]);

            if ($status !== null && in_array($status, $transientStatuses, true)) {
                return true;
            }

            if ($status === 404) {
                return true;
            }
        }

        if ($exception instanceof RuntimeException) {
            $message = strtolower($exception->getMessage());

            return str_contains($message, 'did not return structured text output')
                || str_contains($message, 'returned invalid json');
        }

        return false;
    }

    private function normalizeModelName(string $model): string
    {
        return preg_replace('#^models/#', '', trim($model)) ?: trim($model);
    }
}
