<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use App\Models\WasherKnowledgeDocument;
use App\Services\Maintenance\KnowledgeRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HybridKnowledgeRetrievalTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_retrieval_prefers_semantic_embedding_match(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.knowledge.semantic_query_enabled' => true,
            'maintenance_ai.knowledge.semantic_weight' => 25,
            'maintenance_ai.knowledge.lexical_weight' => 1,
            'maintenance_ai.knowledge.metadata_weight' => 0,
            'maintenance_ai.max_knowledge_chunks' => 2,
        ]);

        $this->app->instance(AiProviderInterface::class, new class implements AiProviderInterface
        {
            public function generateStructuredActionPlan(array $payload): array
            {
                return ['data' => [], 'raw' => [], 'meta' => []];
            }

            public function createEmbedding(string $content): array
            {
                return [1.0, 0.0];
            }

            public function extractDocumentText(array $payload): string
            {
                return '';
            }
        });

        $linea = $this->washerLine();
        $component = $this->washerComponent();

        $semanticDocument = $this->document('Manual semantico servo', $linea, $component);
        WasherKnowledgeChunk::create([
            'document_id' => $semanticDocument->id,
            'chunk_index' => 1,
            'content' => 'Procedimiento ZK-77: aislar energia, calibrar acoplamiento y validar giro libre.',
            'searchable_text' => 'procedimiento zk 77 aislar energia calibrar acoplamiento validar giro libre',
            'token_count' => 10,
            'embedding' => [1.0, 0.0],
        ]);

        $lexicalDocument = $this->document('Manual lexical generico', $linea, $component);
        WasherKnowledgeChunk::create([
            'document_id' => $lexicalDocument->id,
            'chunk_index' => 1,
            'content' => 'La vibracion del servo puede requerir revision visual general sin procedimiento especifico.',
            'searchable_text' => 'la vibracion del servo puede requerir revision visual general sin procedimiento especifico',
            'token_count' => 11,
            'embedding' => [0.0, 1.0],
        ]);

        $event = MaintenanceEvent::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'source_type' => 'analisis_lavadora',
            'source_id' => 100,
            'event_type' => 'damage_detected',
            'severity' => 'high',
            'title' => 'Vibracion oscilante en servo chico',
            'description' => 'El patron coincide con el procedimiento semantico aunque no comparta palabras exactas.',
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'fingerprint' => 'hybrid-test',
            'detected_at' => now(),
        ]);

        $results = app(KnowledgeRetriever::class)->retrieveForEvent($event, [
            'component_name' => 'Servo Chico',
            'linea_nombre' => 'L-04',
        ]);

        $this->assertSame($semanticDocument->id, $results[0]['document_id']);
        $this->assertSame(1, $results[0]['chunk_index']);
        $this->assertSame(1.0, $results[0]['score_breakdown']['semantic']);
    }

    public function test_chat_context_includes_traceable_hybrid_chunk_metadata(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.knowledge.semantic_query_enabled' => true,
        ]);

        $provider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'Respuesta basada en fragmento trazable.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.9,
                    ],
                    'raw' => [],
                    'meta' => ['provider' => 'fake', 'model' => 'hybrid-test'],
                ];
            }

            public function createEmbedding(string $content): array
            {
                return [1.0, 0.0];
            }

            public function extractDocumentText(array $payload): string
            {
                return '';
            }
        };

        $this->app->instance(AiProviderInterface::class, $provider);

        $user = $this->authenticatedUser();
        $linea = $this->washerLine();
        $component = $this->washerComponent();
        $document = $this->document('Guia trazable servo chico', $linea, $component);

        WasherKnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 3,
            'content' => 'Fragmento tecnico trazable para ajustar servo chico con torque validado.',
            'searchable_text' => 'fragmento tecnico trazable ajustar servo chico torque validado',
            'token_count' => 9,
            'metadata' => ['section' => 'Torque final'],
            'embedding' => [1.0, 0.0],
        ]);

        $this->actingAs($user)
            ->postJson(route('assistant-chat.store'), [
                'message' => 'Que dice la guia trazable sobre ajuste del servo chico?',
                'page_context' => [
                    'module' => User::MODULE_LAVADORA,
                    'page_title' => 'Chat operativo',
                    'current_path' => '/asistente/chat',
                    'section' => 'Consulta documental',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'fake');

        $prompt = (string) ($provider->payloads[0]['user_prompt'] ?? '');

        $this->assertStringContainsString('"document_id":' . $document->id, $prompt);
        $this->assertStringContainsString('"chunk_index":3', $prompt);
        $this->assertStringContainsString('score_breakdown', $prompt);
    }

    public function test_chat_context_does_not_include_washer_knowledge_without_module_access(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.knowledge.semantic_query_enabled' => true,
        ]);

        $provider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'Respuesta sin documentos restringidos.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.7,
                    ],
                    'raw' => [],
                    'meta' => ['provider' => 'fake', 'model' => 'permission-test'],
                ];
            }

            public function createEmbedding(string $content): array
            {
                return [1.0, 0.0];
            }

            public function extractDocumentText(array $payload): string
            {
                return '';
            }
        };

        $this->app->instance(AiProviderInterface::class, $provider);

        $user = $this->authenticatedUserWithoutWasherAccess();
        $linea = $this->washerLine();
        $component = $this->washerComponent();
        $document = $this->document('Manual restringido de Lavadora', $linea, $component);

        WasherKnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'Contenido sensible de lavadora para servo chico.',
            'searchable_text' => 'contenido sensible lavadora servo chico',
            'token_count' => 6,
            'embedding' => [1.0, 0.0],
        ]);

        $this->actingAs($user)
            ->postJson(route('assistant-chat.store'), [
                'message' => 'Que dice el manual restringido de lavadora?',
                'page_context' => [
                    'module' => User::MODULE_ETIQUETADORA,
                    'page_title' => 'Chat operativo',
                    'current_path' => '/asistente/chat',
                    'section' => 'Consulta documental',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'fake');

        $prompt = (string) ($provider->payloads[0]['user_prompt'] ?? '');

        $this->assertStringNotContainsString('Manual restringido de Lavadora', $prompt);
        $this->assertStringNotContainsString('Contenido sensible de lavadora', $prompt);
    }

    private function document(string $title, Linea $linea, Componente $component): WasherKnowledgeDocument
    {
        return WasherKnowledgeDocument::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'title' => $title,
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'storage_disk' => 'local',
            'indexing_status' => 'indexed',
            'extracted_text' => $title,
            'indexed_at' => now(),
        ]);
    }

    private function washerLine(): Linea
    {
        return Linea::firstOrCreate(
            ['id' => 4],
            [
                'nombre' => 'L-04',
                'descripcion' => 'Linea de prueba',
                'tipo' => User::MODULE_LAVADORA,
                'activo' => true,
            ]
        );
    }

    private function washerComponent(): Componente
    {
        return Componente::firstOrCreate(
            ['codigo' => 'SERVO_CHICO'],
            [
                'nombre' => 'Servo Chico',
                'tipo_equipo' => User::MODULE_LAVADORA,
                'activo' => true,
            ]
        );
    }

    private function authenticatedUser(): User
    {
        Role::firstOrCreate([
            'name' => User::ROLE_TECNICO,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole(User::ROLE_TECNICO);

        return $user;
    }

    private function authenticatedUserWithoutWasherAccess(): User
    {
        $user = $this->authenticatedUser();

        foreach ([User::customAccessControlPermissionName(), User::PERMISSION_ACCESS_LAVADORA] as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo(User::customAccessControlPermissionName());
        $user->givePermissionTo(User::PERMISSION_ACCESS_LAVADORA);

        return $user;
    }
}
