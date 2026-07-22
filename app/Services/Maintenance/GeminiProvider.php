<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use RuntimeException;

class GeminiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly HttpFactory $http
    ) {
    }

    public function generateStructuredActionPlan(array $payload): array
    {
        $config = config('maintenance_ai.providers.gemini');
        $model = $payload['model'] ?? ($config['model'] ?? 'gemini-3.5-flash');
        $startedAt = microtime(true);

        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withHeaders([
                'x-goog-api-key' => (string) ($config['api_key'] ?? ''),
            ])
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/models/' . $model . ':generateContent', [
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
            ]);

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
        $config = config('maintenance_ai.providers.gemini');
        $model = $config['embedding_model'] ?? 'gemini-embedding-2';

        if (trim($content) === '') {
            return [];
        }

        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withHeaders([
                'x-goog-api-key' => (string) ($config['api_key'] ?? ''),
            ])
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/models/' . $model . ':embedContent', [
                'model' => 'models/' . $model,
                'content' => [
                    'parts' => [
                        [
                            'text' => $content,
                        ],
                    ],
                ],
            ]);

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
        $config = config('maintenance_ai.providers.gemini');
        $model = $payload['model'] ?? ($config['model'] ?? 'gemini-3.5-flash');

        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withHeaders([
                'x-goog-api-key' => (string) ($config['api_key'] ?? ''),
            ])
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/models/' . $model . ':generateContent', [
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
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $text = Arr::get($response->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) ? trim($text) : '';
    }
}
