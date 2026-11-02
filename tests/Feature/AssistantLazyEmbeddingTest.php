<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Models\PasteurizadoraKnowledgeChunk;
use App\Models\PasteurizadoraKnowledgeDocument;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use App\Models\WasherKnowledgeDocument;
use App\Services\Maintenance\AssistantKnowledgeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssistantLazyEmbeddingTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_or_unauthorized_corpus_does_not_request_an_embedding(): void
    {
        $provider = $this->provider();
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $start = hrtime(true);
        $results = app(AssistantKnowledgeSearchService::class)->search('revision servo', [], $user);
        if (getenv('LEGADO_BENCHMARK_EMBEDDING')) {
            fwrite(STDERR, json_encode(['case' => 'empty_corpus', 'ms' => round((hrtime(true) - $start) / 1e6, 2),
                'provider_calls' => $provider->calls, 'result_sha256' => hash('sha256', json_encode($results))]) . PHP_EOL);
        }
        $this->assertSame([], $results);
        $this->assertSame(0, $provider->calls);
        $this->chunk(WasherKnowledgeDocument::class, WasherKnowledgeChunk::class, 'Restricted procedure', [1.0, 0.0]);
        $restricted = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $permission = Permission::findOrCreate(User::customAccessControlPermissionName(), 'web');
        $restricted->givePermissionTo($permission);
        $restricted->givePermissionTo(Permission::findOrCreate(User::PERMISSION_ACCESS_LAVADORA, 'web'));
        $this->assertFalse($restricted->canAccessModule(User::MODULE_LAVADORA));
        $this->assertSame([], app(AssistantKnowledgeSearchService::class)->search('revision servo', [], $restricted));
        $this->assertSame(0, $provider->calls);
    }

    public function test_both_modules_share_one_vector_and_each_search_observes_new_documents(): void
    {
        $provider = $this->provider();
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $washer = $this->chunk(WasherKnowledgeDocument::class, WasherKnowledgeChunk::class, 'Washer semantic procedure', [1.0, 0.0]);
        $pasteurizer = $this->chunk(PasteurizadoraKnowledgeDocument::class, PasteurizadoraKnowledgeChunk::class, 'Pasteurizer semantic procedure', [1.0, 0.0]);
        $service = app(AssistantKnowledgeSearchService::class);
        $results = $service->search('xyz calibration', [], $user);
        $this->assertSame(1, $provider->calls);
        $this->assertContains($washer->document_id, array_column($results, 'document_id'));
        $this->assertContains($pasteurizer->document_id, array_column($results, 'document_id'));
        $this->assertContains(User::MODULE_LAVADORA, array_column($results, 'module'));
        $this->assertContains(User::MODULE_PASTEURIZADORA, array_column($results, 'module'));
        $this->assertSame([1.0], array_values(array_unique(array_column(array_column($results, 'score_breakdown'), 'semantic'))));
        $washer->document->update(['lifecycle_status' => 'obsoleto']);
        $pasteurizer->document->update(['indexing_status' => 'pending']);
        $this->assertSame([], $service->search('xyz calibration', [], $user));
        $this->assertSame(1, $provider->calls, 'No cross-request cache; no eligible chunks means no additional vector.');
    }

    public function test_provider_failure_keeps_lexical_results_and_does_not_retry_per_module(): void
    {
        $provider = $this->provider();
        $provider->fail = true;
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $this->chunk(WasherKnowledgeDocument::class, WasherKnowledgeChunk::class, 'Revision servo manual', [1.0, 0.0]);
        $this->chunk(PasteurizadoraKnowledgeDocument::class, PasteurizadoraKnowledgeChunk::class, 'Revision servo manual', [1.0, 0.0]);
        $results = app(AssistantKnowledgeSearchService::class)->search('revision servo', [], $user);
        $this->assertNotEmpty($results);
        $this->assertSame(1, $provider->calls);
        $this->assertSame([0.0], array_values(array_unique(array_column(array_column($results, 'score_breakdown'), 'semantic'))));
    }

    public function test_query_without_lexical_tokens_retains_semantic_retrieval(): void
    {
        $provider = $this->provider();
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $this->chunk(WasherKnowledgeDocument::class, WasherKnowledgeChunk::class, 'Semantic procedure', [1.0, 0.0]);
        $results = app(AssistantKnowledgeSearchService::class)->search('???', [], $user);
        $this->assertSame(1, $provider->calls);
        $this->assertNotEmpty($results);
        $this->assertSame(1.0, $results[0]['score_breakdown']['semantic']);
    }

    private function provider(): object
    {
        config(['maintenance_ai.enabled' => true, 'maintenance_ai.knowledge.semantic_query_enabled' => true,
            'maintenance_ai.knowledge.semantic_weight' => 25, 'maintenance_ai.chat.max_context_items' => 5]);
        $provider = new class implements AiProviderInterface {
            public int $calls = 0;
            public bool $fail = false;
            public function createEmbedding(string $content): array
            {
                $this->calls++;
                usleep(100000); // Deterministic local stand-in, not a real provider measurement.
                if ($this->fail) {
                    throw new \RuntimeException('Synthetic provider outage');
                }
                return [1.0, 0.0];
            }
            public function generateStructuredActionPlan(array $payload): array { return ['data' => [], 'raw' => [], 'meta' => []]; }
            public function extractDocumentText(array $payload): string { return ''; }
        };
        $this->app->instance(AiProviderInterface::class, $provider);
        return $provider;
    }

    private function chunk(string $documentClass, string $chunkClass, string $text, array $embedding): object
    {
        $document = $documentClass::create(['title' => $text, 'document_type' => 'manual tecnico', 'lifecycle_status' => 'vigente',
            'storage_disk' => 'local', 'indexing_status' => 'indexed', 'extracted_text' => $text, 'indexed_at' => now()]);
        return $chunkClass::create(['document_id' => $document->id, 'chunk_index' => 1, 'content' => $text,
            'searchable_text' => strtolower($text), 'embedding' => $embedding, 'token_count' => 5]);
    }
}
