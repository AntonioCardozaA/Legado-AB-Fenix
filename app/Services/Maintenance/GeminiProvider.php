<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly HttpFactory $http
    ) {
    }

    public function generateStructuredActionPlan(array $payload): array
    {
        $config = (array) config('maintenance_ai.providers.gemini');
        $model = GeminiRequestSupport::normalizeModelName((string) ($payload['model'] ?? ($config['model'] ?? 'gemini-3.5-flash')));
        $endpoint = GeminiRequestSupport::endpoint($config, 'models/' . $model . ':generateContent');
        $startedAt = microtime(true);
        $fallbackUsed = (bool) ($payload['_fallback_model'] ?? false);

        $response = $this->postJson(
            $config,
            (string) ($payload['flow'] ?? $payload['schema_name'] ?? 'generate_structured_action_plan'),
            $model,
            $endpoint,
            [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $payload['user_prompt'],
                            ],
                        ],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => $payload['system_prompt'],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseJsonSchema' => $payload['schema'],
                ],
            ],
            $startedAt,
            $fallbackUsed
        );

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $json = $response->json();
        $content = Arr::get($json, 'candidates.0.content.parts.0.text');

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Gemini did not return structured text output.');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return [
            'data' => $decoded,
            'raw' => is_array($json) ? $json : [],
            'meta' => [
                'provider' => 'gemini',
                'model' => $model,
                'usage' => Arr::get($json, 'usageMetadata', []),
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ],
        ];
    }

    public function createEmbedding(string $content): array
    {
        $config = (array) config('maintenance_ai.providers.gemini');
        $model = GeminiRequestSupport::normalizeModelName((string) ($config['embedding_model'] ?? 'gemini-embedding-2'));

        if (trim($content) === '') {
            return [];
        }

        $endpoint = GeminiRequestSupport::endpoint($config, 'models/' . $model . ':embedContent');
        $response = $this->postJson(
            $config,
            'create_embedding',
            $model,
            $endpoint,
            [
                'model' => 'models/' . $model,
                'content' => [
                    'parts' => [
                        [
                            'text' => $content,
                        ],
                    ],
                ],
            ],
            microtime(true),
            false
        );

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $embedding = Arr::get($response->json(), 'embedding.values')
            ?? Arr::get($response->json(), 'embeddings.0.values', []);

        return is_array($embedding)
            ? array_map(static fn ($value): float => (float) $value, $embedding)
            : [];
    }

    public function extractDocumentText(array $payload): string
    {
        $config = (array) config('maintenance_ai.providers.gemini');
        $model = GeminiRequestSupport::normalizeModelName((string) ($payload['model'] ?? ($config['model'] ?? 'gemini-3.5-flash')));
        $endpoint = GeminiRequestSupport::endpoint($config, 'models/' . $model . ':generateContent');

        $response = $this->postJson(
            $config,
            'extract_document_text',
            $model,
            $endpoint,
            [
                'contents' => [[
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $payload['mime_type'],
                                'data' => $payload['base64_data'],
                            ],
                        ],
                        [
                            'text' => $payload['prompt']
                                ?? 'Extract all readable text from this PDF in reading order. Return only the extracted text. Preserve headings, bullets, and tables as plain text. Do not summarize or add commentary. If no readable text exists, return an empty string.',
                        ],
                    ],
                ]],
            ],
            microtime(true),
            (bool) ($payload['_fallback_model'] ?? false)
        );

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $text = Arr::get($response->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) ? trim($text) : '';
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function postJson(
        array $config,
        string $flow,
        string $model,
        string $endpoint,
        array $payload,
        float $startedAt,
        bool $fallbackUsed
    ): Response {
        $maxRetries = $this->maxRetries($config);
        $maxAttempts = $maxRetries + 1;
        $requestPayloadChars = $this->payloadChars($payload);

        $this->logLargePromptIfNeeded($flow, $model, $endpoint, $requestPayloadChars, $fallbackUsed);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $attemptStartedAt = microtime(true);

            try {
                $response = $this->geminiRequest($config)->post($endpoint, $payload);
                $shouldRetry = $this->shouldRetryResponse($response);
                $delayMs = $shouldRetry && $attempt < $maxAttempts
                    ? $this->retryDelayMs($config, $response, $attempt)
                    : null;

                $this->logAttempt(
                    $flow,
                    $model,
                    $endpoint,
                    $attempt,
                    $startedAt,
                    $attemptStartedAt,
                    $fallbackUsed,
                    $requestPayloadChars,
                    $response,
                    null,
                    $shouldRetry,
                    $delayMs
                );

                if (!$shouldRetry || $attempt >= $maxAttempts) {
                    return $response;
                }

                $this->sleepForRetry($delayMs);
            } catch (ConnectionException $exception) {
                $shouldRetry = !$this->shouldSkipConnectionRetry($exception);
                $delayMs = $shouldRetry && $attempt < $maxAttempts
                    ? $this->retryDelayMs($config, null, $attempt)
                    : null;

                $this->logAttempt(
                    $flow,
                    $model,
                    $endpoint,
                    $attempt,
                    $startedAt,
                    $attemptStartedAt,
                    $fallbackUsed,
                    $requestPayloadChars,
                    null,
                    $exception,
                    $shouldRetry,
                    $delayMs
                );

                if (!$shouldRetry || $attempt >= $maxAttempts) {
                    throw $exception;
                }

                $this->sleepForRetry($delayMs);
            }
        }

        throw new RuntimeException('Gemini request retry loop ended unexpectedly.');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function maxRetries(array $config): int
    {
        return max(0, is_numeric($config['max_retries'] ?? null)
            ? (int) $config['max_retries']
            : (int) config('maintenance_ai.max_retries', 1));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function geminiRequest(array $config): PendingRequest
    {
        $apiKey = trim((string) ($config['api_key'] ?? ''));

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        return $this->http
            ->connectTimeout(GeminiRequestSupport::connectionTimeout())
            ->timeout(GeminiRequestSupport::totalTimeout())
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->acceptJson();
    }

    private function shouldRetryResponse(Response $response): bool
    {
        return GeminiRequestSupport::isTransientHttpStatus($response->status());
    }

    private function shouldSkipConnectionRetry(ConnectionException $exception): bool
    {
        return GeminiRequestSupport::isDnsResolutionError($exception);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function retryDelayMs(array $config, ?Response $response, int $attempt): int
    {
        $retryAfterMs = $this->retryAfterMs($config, $response);

        if ($retryAfterMs !== null) {
            return $retryAfterMs;
        }

        $backoff = array_values(array_filter(
            (array) ($config['retry_backoff_ms'] ?? [1000, 2000, 4000]),
            static fn ($value): bool => is_numeric($value) && (int) $value >= 0
        ));

        if ($backoff === []) {
            $backoff = [1000, 2000, 4000];
        }

        $index = max(0, $attempt - 1);

        return (int) ($backoff[$index] ?? end($backoff));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function retryAfterMs(array $config, ?Response $response): ?int
    {
        if ($response === null) {
            return null;
        }

        $retryAfter = trim((string) $response->header('Retry-After'));

        if ($retryAfter === '') {
            return null;
        }

        $seconds = is_numeric($retryAfter)
            ? (int) $retryAfter
            : strtotime($retryAfter) - time();

        $maxSeconds = max(1, (int) ($config['retry_after_max_seconds'] ?? 10));

        if ($seconds < 0 || $seconds > $maxSeconds) {
            return null;
        }

        return $seconds * 1000;
    }

    private function sleepForRetry(?int $delayMs): void
    {
        if ($delayMs === null || $delayMs <= 0) {
            return;
        }

        usleep($delayMs * 1000);
    }

    private function logAttempt(
        string $flow,
        string $model,
        string $endpoint,
        int $attempt,
        float $startedAt,
        float $attemptStartedAt,
        bool $fallbackUsed,
        int $requestPayloadChars,
        ?Response $response,
        ?ConnectionException $exception,
        bool $retryable,
        ?int $nextRetryDelayMs
    ): void {
        $status = $response?->status();
        $context = [
            'flow' => $flow,
            'date' => now()->toIso8601String(),
            'provider' => 'gemini',
            'model' => $model,
            'attempt' => $attempt,
            'http_status' => $status,
            'duration_ms' => (int) round((microtime(true) - $attemptStartedAt) * 1000),
            'total_duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'connect_timeout_seconds' => GeminiRequestSupport::connectionTimeout(),
            'request_timeout_seconds' => GeminiRequestSupport::totalTimeout(),
            'fallback_used' => $fallbackUsed,
            'retryable' => $retryable,
            'next_retry_delay_ms' => $nextRetryDelayMs,
            'request_payload_chars' => $requestPayloadChars,
            'gemini_message' => GeminiRequestSupport::responseMessage($response),
        ];

        if ($exception !== null) {
            $context['endpoint'] = $endpoint;
            $context['exception_type'] = get_class($exception);
            $context['technical_message'] = $exception->getMessage();
            $context['timeout_error'] = GeminiRequestSupport::isTimeoutException($exception);
            $context['dns_resolution_error'] = GeminiRequestSupport::isDnsResolutionError($exception);
            $context['exception'] = $exception;

            Log::warning('Gemini connection attempt failed.', $context);

            return;
        }

        if ($response?->failed()) {
            $context['endpoint'] = $endpoint;

            Log::warning('Gemini HTTP attempt failed.', $context);

            return;
        }

        Log::info('Gemini HTTP attempt succeeded.', $context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadChars(array $payload): int
    {
        return mb_strlen((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function logLargePromptIfNeeded(
        string $flow,
        string $model,
        string $endpoint,
        int $requestPayloadChars,
        bool $fallbackUsed
    ): void {
        $threshold = max(1, (int) config('maintenance_ai.providers.gemini.large_prompt_warning_chars', 30000));

        if ($requestPayloadChars < $threshold) {
            return;
        }

        Log::warning('Gemini request payload is large.', [
            'flow' => $flow,
            'date' => now()->toIso8601String(),
            'provider' => 'gemini',
            'model' => $model,
            'endpoint' => $endpoint,
            'request_payload_chars' => $requestPayloadChars,
            'large_prompt_warning_chars' => $threshold,
            'connect_timeout_seconds' => GeminiRequestSupport::connectionTimeout(),
            'request_timeout_seconds' => GeminiRequestSupport::totalTimeout(),
            'fallback_used' => $fallbackUsed,
        ]);
    }
}
