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
            $startedAt
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
            microtime(true)
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
            microtime(true)
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
        float $startedAt
    ): Response {
        try {
            return $this->geminiRequest($config)->post($endpoint, $payload);
        } catch (ConnectionException $exception) {
            Log::warning(
                'Gemini connection failed.',
                GeminiRequestSupport::logContext($flow, $model, $endpoint, $exception, $startedAt)
            );

            throw $exception;
        }
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
            ->retry((int) config('maintenance_ai.max_retries', 2), 500, function ($exception): bool {
                return !($exception instanceof ConnectionException
                    && GeminiRequestSupport::isDnsResolutionError($exception));
            })
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->acceptJson();
    }
}
