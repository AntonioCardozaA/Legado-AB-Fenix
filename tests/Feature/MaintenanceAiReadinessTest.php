<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Services\Maintenance\AssistantWebSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceAiReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_reports_missing_documents_and_never_outputs_keys(): void
    {
        config(['maintenance_ai.enabled' => true, 'maintenance_ai.provider' => 'gemini', 'maintenance_ai.providers.gemini.api_key' => 'private-test-secret']);
        $this->assertSame(1, Artisan::call('maintenance:ai:check', ['--json' => true]));
        $output = Artisan::output();
        $report = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($report['ok']);
        $this->assertTrue($report['checks']['table_maintenance_history_chunks']);
        $this->assertFalse($report['checks']['pasteurizadora_documents_ready']);
        $this->assertFalse($report['details']['live_probe_requested']);
        $this->assertStringNotContainsString('private-test-secret', $output);
    }

    public function test_probe_checks_structured_generation_and_embeddings(): void
    {
        Storage::fake('local');
        config(['maintenance_ai.enabled' => true, 'maintenance_ai.provider' => 'gemini', 'maintenance_ai.providers.gemini.api_key' => 'test-secret']);
        $this->mock(AiProviderInterface::class, function ($mock): void {
            $mock->shouldReceive('generateStructuredActionPlan')->once()->withArgs(fn ($payload) => $payload['user_prompt'] === 'Return {"ok":true}.')
                ->andReturn(['data' => ['ok' => true], 'meta' => ['provider' => 'fake', 'model' => 'fake-model']]);
            $mock->shouldReceive('createEmbedding')->once()->andReturn([0.1, 0.2]);
        });
        Artisan::call('maintenance:ai:check', ['--json' => true, '--probe' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($report['checks']['live_structured_response']);
        $this->assertTrue($report['checks']['live_embedding']);
        $this->assertSame(2, $report['details']['embedding_dimensions']);
    }

    public function test_web_probe_uses_only_a_public_synthetic_query(): void
    {
        $this->mock(AssistantWebSearchService::class, function ($mock): void {
            $mock->shouldReceive('searchIfNeeded')->once()
                ->with('Busca en la web el sitio oficial de Laravel y cita la fuente.', [], [], [])
                ->andReturn(['used' => true, 'provider' => 'fake', 'sources' => [['url' => 'https://laravel.com']]]);
        });
        Artisan::call('maintenance:ai:check', ['--json' => true, '--probe-web' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($report['checks']['live_web_search']);
        $this->assertSame(1, $report['details']['web_sources_count']);
    }

    public function test_web_search_uses_an_alternate_model_when_primary_quota_is_exceeded(): void
    {
        config([
            'maintenance_ai.web_search.enabled' => true,
            'maintenance_ai.web_search.mode' => 'hybrid',
            'maintenance_ai.web_search.provider' => 'gemini',
            'maintenance_ai.web_search.fallback_provider' => null,
            'maintenance_ai.web_search.gemini_model' => 'primary-model',
            'maintenance_ai.web_search.gemini_fallback_models' => ['alternate-model'],
            'maintenance_ai.providers.gemini.api_key' => 'test-key',
            'maintenance_ai.max_retries' => 1,
        ]);
        Http::fake(fn ($request) => $request['model'] === 'primary-model'
            ? Http::response(['error' => ['message' => 'Quota exceeded']], 429)
            : Http::response(['steps' => [['type' => 'model_output', 'content' => [[
                'type' => 'text', 'text' => 'Sitio oficial de Laravel.',
                'annotations' => [['type' => 'url_citation', 'url' => 'https://laravel.com', 'title' => 'Laravel']],
            ]]]]]));

        $result = app(AssistantWebSearchService::class)->searchIfNeeded('Busca en la web Laravel.', [], [], []);

        $this->assertTrue($result['used']);
        $this->assertSame('alternate-model', $result['raw_meta']['model']);
        $this->assertSame('https://laravel.com', $result['sources'][0]['url']);
        Http::assertSentCount(2);
    }

    public function test_web_search_does_not_retry_alternate_models_for_invalid_credentials(): void
    {
        config([
            'maintenance_ai.web_search.enabled' => true,
            'maintenance_ai.web_search.mode' => 'hybrid',
            'maintenance_ai.web_search.provider' => 'gemini',
            'maintenance_ai.web_search.fallback_provider' => null,
            'maintenance_ai.web_search.gemini_model' => 'primary-model',
            'maintenance_ai.web_search.gemini_fallback_models' => ['alternate-model'],
            'maintenance_ai.providers.gemini.api_key' => 'test-key',
            'maintenance_ai.max_retries' => 1,
        ]);
        Http::fake(fn () => Http::response(['error' => ['message' => 'Unauthorized']], 401));

        $result = app(AssistantWebSearchService::class)->searchIfNeeded('Busca en la web Laravel.', [], [], []);

        $this->assertFalse($result['used']);
        Http::assertSentCount(1);
    }
}
