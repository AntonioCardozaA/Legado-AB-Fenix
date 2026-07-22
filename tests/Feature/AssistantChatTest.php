<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Models\AnalisisLavadora;
use App\Models\AssistantMessage;
use App\Models\Componente;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_send_and_fetch_chat_messages(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'Este modulo concentra el seguimiento del plan y sus riesgos principales.',
                        'key_points' => [
                            'Se debe revisar el responsable y la prioridad del plan.',
                            'Conviene validar el riesgo antes de ejecutar la actividad.',
                        ],
                        'next_steps' => [
                            'Confirma fecha objetivo y disponibilidad del equipo.',
                        ],
                        'sources' => [
                            [
                                'type' => 'operational_plan',
                                'reference' => 'Plan #15',
                            ],
                        ],
                        'confidence' => 0.84,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'assistant-test-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $event = MaintenanceEvent::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'source_type' => 'analisis_lavadora',
            'source_id' => 1,
            'event_type' => 'component_requires_review',
            'severity' => 'high',
            'title' => 'Servo chico con revision pendiente',
            'description' => 'Se detecto desgaste y se requiere inspeccion dirigida.',
            'context_data' => ['hallazgo' => 'desgaste'],
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'detected_at' => now()->subHour(),
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'R-14',
            'lado' => 'BOMBAS',
            'fecha_analisis' => now()->toDateString(),
            'estado' => AnalisisLavadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Revisar holgura del servo chico',
            'usuario_id' => $user->id,
            'evidencia_fotos' => ['evidencias/servo-chico-01.jpg'],
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'maintenance_event_id' => $event->id,
            'actividad' => 'Inspeccionar servo chico y validar ajuste',
            'source' => 'manual',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'priority_level' => 'media',
            'maintenance_type' => 'inspeccion',
            'detected_problem' => 'Existe juego mecanico en el servo chico.',
            'technical_justification' => 'El hallazgo afecta la estabilidad del arrastre.',
            'risk_if_not_executed' => 'Puede crecer el desgaste y provocar paro.',
            'estado' => 'approved',
            'fecha_pcm1' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Dame contexto global del modulo de accion sobre el servo chico en L-04 con fotos y eventos',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Plan de accion',
                'current_path' => '/plan-accion/lavadora',
                'section' => 'Listado de planes',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user_message.role', 'user')
            ->assertJsonPath('message.role', 'assistant')
            ->assertJsonPath('message.metadata.provider', 'fake');

        $capturedPayload = $capturingProvider->payloads[0] ?? [];
        $userPrompt = (string) ($capturedPayload['user_prompt'] ?? '');

        $this->assertStringContainsString('"platform_context"', $userPrompt);
        $this->assertStringContainsString('"database_overview"', $userPrompt);
        $this->assertStringContainsString('"maintenance_events"', $userPrompt);
        $this->assertStringContainsString('analisis_componentes', $userPrompt);
        $this->assertStringContainsString('plan_accion', $userPrompt);
        $this->assertStringContainsString('evidencias/servo-chico-01.jpg', $userPrompt);
        $this->assertStringContainsString('Servo Chico', $userPrompt);
        $this->assertStringContainsString('L-04', $userPrompt);

        $this->assertDatabaseCount('assistant_messages', 2);
        $this->assertDatabaseHas('assistant_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'Dame contexto global del modulo de accion sobre el servo chico en L-04 con fotos y eventos',
        ]);

        $this->actingAs($user)
            ->getJson(route('assistant-chat.index'))
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.1.role', 'assistant');
    }

    public function test_authenticated_user_can_clear_chat_history(): void
    {
        $user = $this->authenticatedUser();

        AssistantMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'Mensaje temporal',
        ]);

        AssistantMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => 'Respuesta temporal',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('assistant-chat.destroy'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseCount('assistant_messages', 0);
    }

    public function test_widget_is_rendered_on_authenticated_layout_pages(): void
    {
        $user = $this->authenticatedUser();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Abrir chat')
            ->assertSee('assistant-chat-widget', false);
    }

    public function test_chat_answers_with_live_elongation_ranking_for_comparative_questions(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'La lavadora con mayor elongacion actual es L-05.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.91,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'assistant-elongation-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea04 = Linea::create([
            'nombre' => 'L-04',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $linea05 = Linea::create([
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        Elongacion::create([
            'linea_id' => $linea04->id,
            'linea' => 'L-04',
            'bombas_promedio' => 175.10,
            'bombas_porcentaje' => 1.21,
            'vapor_promedio' => 175.90,
            'vapor_porcentaje' => 1.39,
            'estado' => 'alerta',
            'estado_detallado' => 'comprar',
            'paso_inicial' => 173,
            'hodometro' => 1200,
            'hodometro_ciclo' => 400,
            'juego_rodaja_bombas' => 0.30,
            'juego_rodaja_vapor' => 0.28,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Elongacion::create([
            'linea_id' => $linea05->id,
            'linea' => 'L-05',
            'bombas_promedio' => 142.20,
            'bombas_porcentaje' => 1.57,
            'vapor_promedio' => 142.27,
            'vapor_porcentaje' => 1.62,
            'estado' => 'critico',
            'estado_detallado' => 'cambio',
            'paso_inicial' => 140,
            'hodometro' => 1430,
            'hodometro_ciclo' => 630,
            'juego_rodaja_bombas' => 0.42,
            'juego_rodaja_vapor' => 0.45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Cual es la lavadora con porcentaje de elongacion mas alto en la cadena',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.role', 'assistant')
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $this->assertStringContainsString('L-05', (string) $response->json('message.content'));
        $this->assertStringContainsString('1.62%', (string) $response->json('message.content'));
    }

    public function test_chat_uses_lavadora_context_when_legacy_analysis_table_is_missing(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'No deberia usarse el proveedor para esta consulta.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.5,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'unused-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea06 = Linea::create([
            'nombre' => 'L-06',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        Elongacion::create([
            'linea_id' => $linea06->id,
            'linea' => 'L-06',
            'bombas_promedio' => 173.00,
            'bombas_porcentaje' => 1.31,
            'vapor_promedio' => 173.00,
            'vapor_porcentaje' => 1.31,
            'estado' => 'alerta',
            'estado_detallado' => 'comprar',
            'paso_inicial' => 173,
            'hodometro' => 1200,
            'hodometro_ciclo' => 400,
            'juego_rodaja_bombas' => 0.30,
            'juego_rodaja_vapor' => 0.28,
            'created_at' => '2026-07-21 12:00:00',
            'updated_at' => '2026-07-21 12:00:00',
        ]);

        Schema::dropIfExists('analisis');

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Cual es la lavadora con porcentaje de elongacion mas alto en la cadena',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('L-06', $content);
        $this->assertStringContainsString('1.31%', $content);
    }

    public function test_chat_ignores_future_dated_records_for_current_component_status(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'No deberia usarse el proveedor para esta consulta.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.5,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'unused-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea07 = Linea::create([
            'nombre' => 'L-07',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        $servo = Componente::create([
            'nombre' => 'Servo Grande',
            'codigo' => 'SERVO_GRANDE',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea07->id,
            'componente_id' => $servo->id,
            'reductor' => 'Reductor 12',
            'lado' => null,
            'fecha_analisis' => '2026-07-20',
            'estado' => 'Buen estado',
            'actividad' => 'Cambio de aceite y revision interna, encontrandose en buen estado.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea07->id,
            'componente_id' => $servo->id,
            'reductor' => 'Reductor 12',
            'lado' => null,
            'fecha_analisis' => '2026-12-12',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Registro futuro que no debe tratarse como estado actual.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Como se encuentra el servo grande del reductor 12 de la lavadora 7',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('Buen estado', $content);
        $this->assertStringContainsString('2026-07-20', $content);
        $this->assertStringNotContainsString('2026-12-12', $content);
    }

    public function test_chat_answers_with_targeted_component_status_from_latest_snapshot(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'No deberia usarse el proveedor para esta consulta.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.5,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'unused-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea = Linea::create([
            'nombre' => 'L-07',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $component = Componente::create([
            'nombre' => 'Servo Grande',
            'codigo' => 'SERVO_GRANDE',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'reductor' => 'Reductor 12',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-07-21',
            'estado' => 'Desgaste severo',
            'actividad' => 'Se detecta juego excesivo y desgaste en el acoplamiento del servo grande.',
            'usuario_id' => $user->id,
            'evidencia_fotos' => ['evidencias/servo-grande-r12.jpg'],
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Como se encuentra el servo grande del reductor 12 de la lavadora 7',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('Servo Grande', $content);
        $this->assertStringContainsString('L-07', $content);
        $this->assertStringContainsString('Reductor 12', $content);
        $this->assertStringContainsString('Desgaste severo', $content);
    }

    public function test_chat_answers_with_most_damaged_components_by_period(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'No deberia usarse el proveedor para esta consulta.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.5,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'unused-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $servo = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $cadena = Componente::create([
            'nombre' => 'Cadena Principal',
            'codigo' => 'CADENA_PRINCIPAL',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $servo->id,
            'reductor' => 'Reductor 10',
            'lado' => 'BOMBAS',
            'fecha_analisis' => '2026-07-20',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Cambio urgente por dano en servo chico.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $servo->id,
            'reductor' => 'Reductor 11',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-07-21',
            'estado' => 'Desgaste severo',
            'actividad' => 'Se detecta desgaste severo en servo chico.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $cadena->id,
            'reductor' => 'Reductor 05',
            'lado' => 'BOMBAS',
            'fecha_analisis' => '2026-07-18',
            'estado' => 'Desgaste moderado',
            'actividad' => 'Cadena con alargamiento visible.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Que componentes presentaron mas danos este mes',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('Mes actual', $content);
        $this->assertStringContainsString('Servo Chico', $content);
        $this->assertStringContainsString('Cadena Principal', $content);
    }

    public function test_chat_answers_with_washer_having_most_problematic_components(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'No deberia usarse el proveedor para esta consulta.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.5,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'unused-model',
                    ],
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

        $this->app->instance(AiProviderInterface::class, $capturingProvider);

        $user = $this->authenticatedUser();
        $linea04 = Linea::create([
            'nombre' => 'L-04',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $linea07 = Linea::create([
            'nombre' => 'L-07',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        $servo = Componente::create([
            'nombre' => 'Servo Grande',
            'codigo' => 'SERVO_GRANDE',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $cadena = Componente::create([
            'nombre' => 'Cadena Principal',
            'codigo' => 'CADENA_PRINCIPAL',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $catarina = Componente::create([
            'nombre' => 'Catarina',
            'codigo' => 'CATARINA',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea04->id,
            'componente_id' => $servo->id,
            'reductor' => 'Reductor 01',
            'lado' => 'BOMBAS',
            'fecha_analisis' => '2026-07-21',
            'estado' => 'Desgaste moderado',
            'actividad' => 'Desgaste en servo.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea07->id,
            'componente_id' => $servo->id,
            'reductor' => 'Reductor 12',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-07-21',
            'estado' => 'Desgaste severo',
            'actividad' => 'Desgaste severo en servo grande.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea07->id,
            'componente_id' => $cadena->id,
            'reductor' => 'Reductor 05',
            'lado' => 'BOMBAS',
            'fecha_analisis' => '2026-07-21',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Cadena danada.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea07->id,
            'componente_id' => $catarina->id,
            'reductor' => 'Reductor 09',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-07-21',
            'estado' => AnalisisLavadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Se requiere revision.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Cual lavadora tiene componentes mas danados actualmente',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('L-07', $content);
        $this->assertStringContainsString('3 componentes', $content);
    }

    private function authenticatedUser(): User
    {
        Role::firstOrCreate([
            'name' => User::ROLE_TECNICO,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'activo' => true,
        ]);

        $user->assignRole(User::ROLE_TECNICO);

        return $user;
    }
}
