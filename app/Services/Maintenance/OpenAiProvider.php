<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use RuntimeException;

class OpenAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly HttpFactory $http
    ) {
    }

    public function generateStructuredActionPlan(array $payload): array
    {
        $config = config('maintenance_ai.providers.openai');
        $startedAt = microtime(true);

        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withToken((string) ($config['api_key'] ?? ''))
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/responses', [
                'model' => $payload['model'] ?? ($config['model'] ?? 'gpt-5.6'),
                'input' => [
                    [
                        'role' => 'system',
                        'content' => $payload['system_prompt'],
                    ],
                    [
                        'role' => 'user',
                        'content' => $payload['user_prompt'],
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => $payload['schema_name'] ?? 'washer_action_plan',
                        'schema' => $payload['schema'],
                        'strict' => true,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $json = $response->json();
        $content = $this->extractOutputText($json);

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI did not return structured text output.');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON.');
        }

        return [
            'data' => $decoded,
            'raw' => is_array($json) ? $json : [],
            'meta' => [
                'provider' => 'openai',
                'model' => $payload['model'] ?? ($config['model'] ?? 'gpt-5.6'),
                'usage' => Arr::get($json, 'usage', []),
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ],
        ];
    }

    public function createEmbedding(string $content): array
    {
        $config = config('maintenance_ai.providers.openai');

        if (trim($content) === '') {
            return [];
        }

        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withToken((string) ($config['api_key'] ?? ''))
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/embeddings', [
                'model' => $config['embedding_model'] ?? 'text-embedding-3-small',
                'input' => $content,
                'encoding_format' => 'float',
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $embedding = Arr::get($response->json(), 'data.0.embedding', []);

        return is_array($embedding)
            ? array_map(static fn ($value): float => (float) $value, $embedding)
            : [];
    }

    public function extractDocumentText(array $payload): string
    {
        $config = config('maintenance_ai.providers.openai');
        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withToken((string) ($config['api_key'] ?? ''))
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/responses', [
                'model' => $payload['model']
                    ?? config('maintenance_ai.knowledge.pdf_ocr_model')
                    ?? ($config['model'] ?? 'gpt-5.6'),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => array_values(array_filter([
                            [
                                'type' => 'input_file',
                                'filename' => $payload['filename'],
                                'file_data' => 'data:' . $payload['mime_type'] . ';base64,' . $payload['base64_data'],
                                'detail' => $payload['detail'] ?? config('maintenance_ai.knowledge.pdf_ocr_detail', 'high'),
                            ],
                            [
                                'type' => 'input_text',
                                'text' => $payload['prompt']
                                    ?? 'Extract all readable text from this document in reading order. Return only the extracted text. Preserve headings, bullets, and tables as plain text. Do not summarize or add commentary. If no readable text exists, return an empty string.',
                            ],
                        ])),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $content = $this->extractOutputText($response->json());

        return is_string($content) ? trim($content) : '';
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function extractOutputText(array $json): ?string
    {
        $topLevel = $json['output_text'] ?? null;

        if (is_string($topLevel) && trim($topLevel) !== '') {
            return $topLevel;
        }

        foreach (($json['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                $text = $content['text'] ?? null;

                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }
            }
        }

        return null;
    }
}
