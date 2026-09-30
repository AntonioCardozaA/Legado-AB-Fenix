<?php

namespace Tests\Unit;

use App\Services\Maintenance\GeminiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_it_normalizes_gemini_base_url_and_model_before_posting(): void
    {
        config([
            'maintenance_ai.connect_timeout' => 10,
            'maintenance_ai.timeout' => 60,
            'maintenance_ai.max_retries' => 0,
            'maintenance_ai.providers.gemini.api_key' => 'test-key',
            'maintenance_ai.providers.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'maintenance_ai.providers.gemini.model' => 'models/gemini-3.6-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode(['answer' => 'ok'])],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 1],
            ]),
        ]);

        $result = app(GeminiProvider::class)->generateStructuredActionPlan([
            'system_prompt' => 'system',
            'user_prompt' => 'user',
            'schema' => ['type' => 'object'],
        ]);

        $this->assertSame('ok', $result['data']['answer']);
        $this->assertSame('gemini-3.6-flash', $result['meta']['model']);

        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent');
    }

    public function test_it_does_not_retry_dns_resolution_errors(): void
    {
        config([
            'maintenance_ai.connect_timeout' => 10,
            'maintenance_ai.timeout' => 60,
            'maintenance_ai.max_retries' => 3,
            'maintenance_ai.providers.gemini.api_key' => 'test-key',
            'maintenance_ai.providers.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'maintenance_ai.providers.gemini.model' => 'gemini-3.6-flash',
        ]);

        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 6: Could not resolve host: generativelanguage.googleapis.com');
        });

        try {
            app(GeminiProvider::class)->generateStructuredActionPlan([
                'system_prompt' => 'system',
                'user_prompt' => 'user',
                'schema' => ['type' => 'object'],
            ]);

            $this->fail('Expected a Gemini DNS connection exception.');
        } catch (ConnectionException) {
            $this->assertSame(1, $attempts);
        }
    }
}
