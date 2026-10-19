<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class FailoverAiProvider implements AiProviderInterface
{
    private readonly AiProviderCircuitBreaker $circuitBreaker;

    public function __construct(
        private readonly GeminiProvider $geminiProvider,
        private readonly OpenAiProvider $openAiProvider,
        private readonly NullAiProvider $nullAiProvider,
        ?AiProviderCircuitBreaker $circuitBreaker = null
    ) {
        $this->circuitBreaker = $circuitBreaker ?? app(AiProviderCircuitBreaker::class);
    }

    public function generateStructuredActionPlan(array $payload): array
    {
        $lastException = null;

        foreach ($this->providerChain() as $providerIndex => $providerName) {
            foreach ($this->generationModelChain($providerName, $payload['model'] ?? null) as $index => $model) {
                if ($this->circuitBreaker->isOpen($providerName, $model, 'generation')) {
                    $lastException = new RuntimeException("AI circuit breaker is open for {$providerName}/{$model}.");

                    continue;
                }

                $attemptPayload = $payload;
                $attemptPayload['model'] = $model;
                $attemptPayload['_fallback_model'] = $providerIndex > 0 || $index > 0;

                try {
                    $response = $this->provider($providerName)->generateStructuredActionPlan($attemptPayload);
                    $this->circuitBreaker->recordSuccess($providerName, $model, 'generation');
                    $response['meta'] = array_merge((array) ($response['meta'] ?? []), [
                        'fallback_used' => $providerIndex > 0 || $index > 0,
                        'fallback_provider_index' => $providerIndex,
                        'fallback_model_index' => $index,
                    ]);

                    return $response;
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $this->circuitBreaker->recordFailure($providerName, $model, 'generation', $this->failureCategory($exception));

                    if (!$this->shouldTryNextAttempt($exception)) {
                        throw $exception;
                    }

                    if ($this->shouldSkipRemainingModels($exception)) {
                        break;
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
            $model = data_get(config('maintenance_ai'), 'providers.' . $providerName . '.embedding_model');

            if ($this->circuitBreaker->isOpen($providerName, is_string($model) ? $model : null, 'embedding')) {
                $lastException = new RuntimeException("AI circuit breaker is open for {$providerName}/embedding.");

                continue;
            }

            try {
                $embedding = $this->provider($providerName)->createEmbedding($content);
                $this->circuitBreaker->recordSuccess($providerName, is_string($model) ? $model : null, 'embedding');

                return $embedding;
            } catch (Throwable $exception) {
                $lastException = $exception;
                $this->circuitBreaker->recordFailure($providerName, is_string($model) ? $model : null, 'embedding', $this->failureCategory($exception));

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

        foreach ($this->providerChain() as $providerIndex => $providerName) {
            foreach ($this->generationModelChain($providerName, $payload['model'] ?? null) as $index => $model) {
                if ($this->circuitBreaker->isOpen($providerName, $model, 'document_extraction')) {
                    $lastException = new RuntimeException("AI circuit breaker is open for {$providerName}/{$model}.");

                    continue;
                }

                $attemptPayload = $payload;
                $attemptPayload['model'] = $model;
                $attemptPayload['_fallback_model'] = $providerIndex > 0 || $index > 0;

                try {
                    $text = $this->provider($providerName)->extractDocumentText($attemptPayload);
                    $this->circuitBreaker->recordSuccess($providerName, $model, 'document_extraction');

                    return $text;
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $this->circuitBreaker->recordFailure($providerName, $model, 'document_extraction', $this->failureCategory($exception));

                    if (!$this->shouldTryNextAttempt($exception)) {
                        throw $exception;
                    }

                    if ($this->shouldSkipRemainingModels($exception)) {
                        break;
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
            $transientStatuses = (array) config('maintenance_ai.fallback.transient_statuses', GeminiRequestSupport::TRANSIENT_HTTP_STATUSES);

            if ($status !== null && in_array($status, $transientStatuses, true)) {
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

    private function failureCategory(Throwable $exception): string
    {
        if ($exception instanceof RuntimeException) {
            $message = strtolower($exception->getMessage());

            if (str_contains($message, 'did not return structured text output')
                || str_contains($message, 'returned invalid json')
                || str_contains($message, 'invalid json')) {
                return 'quality';
            }
        }

        return 'availability';
    }

    private function shouldSkipRemainingModels(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            && GeminiRequestSupport::isDnsResolutionError($exception);
    }

    private function normalizeModelName(string $model): string
    {
        return preg_replace('#^models/#', '', trim($model)) ?: trim($model);
    }
}
