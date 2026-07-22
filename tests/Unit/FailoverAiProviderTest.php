<?php

namespace Tests\Unit;

use App\Services\Maintenance\FailoverAiProvider;
use App\Services\Maintenance\GeminiProvider;
use App\Services\Maintenance\NullAiProvider;
use App\Services\Maintenance\OpenAiProvider;
use Illuminate\Http\Client\ConnectionException;
use Tests\TestCase;

class FailoverAiProviderTest extends TestCase
{
    public function test_it_tries_fallback_models_when_primary_model_fails_transiently(): void
    {
        config([
            'maintenance_ai.provider' => 'gemini',
            'maintenance_ai.fallback.provider' => null,
            'maintenance_ai.providers.gemini.api_key' => 'test-key',
            'maintenance_ai.providers.gemini.base_url' => 'https://example.test',
            'maintenance_ai.providers.gemini.model' => 'gemini-3.6-flash',
            'maintenance_ai.providers.gemini.fallback_models' => [
                'gemini-3.5-flash-lite',
                'gemini-3.5-flash',
            ],
        ]);

        $gemini = new class extends GeminiProvider
        {
            public array $attemptedModels = [];

            public function __construct()
            {
            }

            public function generateStructuredActionPlan(array $payload): array
            {
                $model = (string) ($payload['model'] ?? '');
                $this->attemptedModels[] = $model;

                if ($model === 'gemini-3.6-flash') {
                    throw new ConnectionException('temporary timeout');
                }

                return [
                    'data' => ['ok' => true],
                    'raw' => [],
                    'meta' => ['provider' => 'gemini', 'model' => $model],
                ];
            }

            public function createEmbedding(string $content): array
            {
                return [];
            }

            public function extractDocumentText(array $payload): string
            {
                return '';
            }
        };

        $openai = new class extends OpenAiProvider
        {
            public function __construct()
            {
            }

            public function generateStructuredActionPlan(array $payload): array
            {
                throw new ConnectionException('should not be called');
            }

            public function createEmbedding(string $content): array
            {
                return [];
            }

            public function extractDocumentText(array $payload): string
            {
                return '';
            }
        };

        $provider = new FailoverAiProvider($gemini, $openai, new NullAiProvider());

        $result = $provider->generateStructuredActionPlan([
            'system_prompt' => 'test',
            'user_prompt' => 'test',
            'schema' => ['type' => 'object'],
        ]);

        $this->assertSame(['gemini-3.6-flash', 'gemini-3.5-flash-lite'], $gemini->attemptedModels);
        $this->assertTrue($result['data']['ok']);
        $this->assertSame('gemini-3.5-flash-lite', $result['meta']['model']);
    }
}
