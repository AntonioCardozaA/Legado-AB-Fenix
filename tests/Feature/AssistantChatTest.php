<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Models\AnalisisLavadora;
use App\Models\AssistantMessage;
use App\Models\CadenaCiclo;
use App\Models\Componente;
use App\Models\CostAutomationRule;
use App\Models\CostCatalogItem;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use App\Models\WasherKnowledgeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_chat_generates_elongation_chart_and_excel_artifacts_from_prompt(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'elongaciones',
                        'metric' => 'max_porcentaje',
                        'chart_type' => 'line',
                        'aggregation' => 'monthly',
                        'outputs' => ['image', 'excel'],
                        'lineas' => ['L-05'],
                        'date_range' => [
                            'preset' => 'last_12_months',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Tendencia de elongaciones L-05',
                        'confidence' => 0.94,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        foreach ([3 => [1.12, 1.18], 2 => [1.24, 1.31], 1 => [1.37, 1.42]] as $monthsAgo => [$bombas, $vapor]) {
            Elongacion::create([
                'linea_id' => $linea->id,
                'linea' => 'L-05',
                'bombas_promedio' => 140 + $bombas,
                'bombas_porcentaje' => $bombas,
                'vapor_promedio' => 140 + $vapor,
                'vapor_porcentaje' => $vapor,
                'estado' => $vapor >= 1.3 ? 'alerta' : 'normal',
                'estado_detallado' => $vapor >= 1.3 ? 'comprar' : 'normal',
                'paso_inicial' => 140,
                'hodometro' => 1000 + $monthsAgo,
                'created_at' => now()->subMonths($monthsAgo),
                'updated_at' => now()->subMonths($monthsAgo),
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Graficame la tendencia de elongaciones de la linea 5 y mandamela en imagen y Excel',
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
            ->assertJsonPath('message.metadata.provider', 'gemini')
            ->assertJsonPath('message.metadata.model', 'gemini-3.6-flash')
            ->assertJsonPath('message.metadata.artifact_request', true)
            ->assertJsonCount(2, 'message.metadata.artifacts');

        $this->assertSame('assistant_analytics_intent', $capturingProvider->payloads[0]['schema_name'] ?? null);
        $this->assertStringContainsString('Genere', (string) $response->json('message.content'));
        $this->assertStringContainsString('Excel', (string) $response->json('message.content'));
        $this->assertStringNotContainsString('PNG y SVG y Excel', (string) $response->json('message.content'));

        $serializedArtifacts = $response->json('message.metadata.artifacts');
        $this->assertContains($serializedArtifacts[0]['kind'], ['image', 'svg']);
        $this->assertSame('excel', $serializedArtifacts[1]['kind']);
        $this->assertNotEmpty($serializedArtifacts[0]['url'] ?? null);
        $this->assertNotEmpty($serializedArtifacts[1]['url'] ?? null);

        $assistantMessage = AssistantMessage::query()
            ->where('user_id', $user->id)
            ->where('role', 'assistant')
            ->latest('id')
            ->firstOrFail();
        $storedArtifacts = $assistantMessage->metadata['artifacts'];

        Storage::disk('local')->assertExists($storedArtifacts[0]['path']);
        Storage::disk('local')->assertExists($storedArtifacts[1]['path']);

        $imageResponse = $this->actingAs($user)->get($serializedArtifacts[0]['url']);
        $imageResponse->assertOk();
        if (($serializedArtifacts[0]['kind'] ?? null) === 'image') {
            $this->assertStringContainsString('image/png', (string) $imageResponse->headers->get('content-type'));
            $this->assertStringStartsWith("\x89PNG", Storage::disk('local')->get($storedArtifacts[0]['path']));
        } else {
            $this->assertStringContainsString('image/svg+xml', (string) $imageResponse->headers->get('content-type'));
            $this->assertStringContainsString('<svg', Storage::disk('local')->get($storedArtifacts[0]['path']));
        }

        $this->actingAs($this->authenticatedUser())
            ->get($serializedArtifacts[0]['url'])
            ->assertNotFound();

        $excelResponse = $this->actingAs($user)->get($serializedArtifacts[1]['url'].'?download=1');
        $excelResponse->assertOk();
        $this->assertStringContainsString('attachment', (string) $excelResponse->headers->get('content-disposition'));

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($storedArtifacts[1]['path']));

        $this->assertSame([
            'Dashboard',
            'Tendencia',
            'Alertas',
            'Datos',
        ], $spreadsheet->getSheetNames());
        $dashboard = $spreadsheet->getSheetByName('Dashboard');
        $this->assertNotNull($dashboard);
        $this->assertSame('LEGADO AB FENIX', $dashboard?->getCell('A1')->getValue());
        if (extension_loaded('gd')) {
            $this->assertGreaterThan(0, count($dashboard?->getDrawingCollection() ?? []));
        }
        $this->assertNull($spreadsheet->getSheetByName('Filtros'));
        $this->assertNull($spreadsheet->getSheetByName('Resumen'));

        $explicitSvgResponse = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Graficame la tendencia de elongaciones de la linea 5 en PNG, SVG y Excel',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
                'section' => 'Resumen global',
            ],
        ]);

        $explicitSvgResponse
            ->assertOk()
            ->assertJsonCount(extension_loaded('gd') ? 3 : 2, 'message.metadata.artifacts');

        $explicitArtifacts = $explicitSvgResponse->json('message.metadata.artifacts');
        $this->assertSame(
            extension_loaded('gd') ? ['image', 'svg', 'excel'] : ['svg', 'excel'],
            array_column($explicitArtifacts, 'kind')
        );
    }

    public function test_chat_recovers_typo_prompt_and_incomplete_artifact_intent_json(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        Elongacion::create([
            'linea_id' => $linea->id,
            'linea' => 'L-05',
            'bombas_promedio' => 141.10,
            'bombas_porcentaje' => 1.36,
            'vapor_promedio' => 141.40,
            'vapor_porcentaje' => 1.44,
            'estado' => 'critico',
            'estado_detallado' => 'cambio',
            'paso_inicial' => 140,
            'hodometro' => 1440,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Graficae longaciones de linea 5 en ecxel de ultimos 30 dias',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.artifact_request', true)
            ->assertJsonPath('message.metadata.intent.dataset', 'elongaciones')
            ->assertJsonPath('message.metadata.intent.date_range.preset', 'last_30_days')
            ->assertJsonCount(2, 'message.metadata.artifacts');

        $serializedArtifacts = $response->json('message.metadata.artifacts');
        $this->assertContains($serializedArtifacts[0]['kind'], ['image', 'svg']);
        $this->assertSame('excel', $serializedArtifacts[1]['kind']);
        $this->assertSame('assistant_analytics_intent', $capturingProvider->payloads[0]['schema_name'] ?? null);
    }

    public function test_chat_elongation_trend_uses_current_cycle_unless_history_is_requested(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'elongaciones',
                        'metric' => 'max_porcentaje',
                        'chart_type' => 'line',
                        'aggregation' => 'daily',
                        'outputs' => ['excel'],
                        'lineas' => ['L-05'],
                        'date_range' => [
                            'preset' => 'all',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Tendencia de elongaciones L-05',
                        'confidence' => 0.91,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $cicloAnterior = CadenaCiclo::create([
            'linea_id' => $linea->id,
            'linea' => 'L-05',
            'codigo' => 'L-05-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor anterior',
            'paso_inicial' => 140,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(8),
            'retirada_en' => now()->subMonths(3),
            'activa' => false,
        ]);
        $cicloActual = CadenaCiclo::create([
            'linea_id' => $linea->id,
            'linea' => 'L-05',
            'codigo' => 'L-05-C002',
            'numero_ciclo' => 2,
            'proveedor' => 'Proveedor actual',
            'paso_inicial' => 140,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(2),
            'activa' => true,
        ]);

        foreach ([
            [$cicloAnterior, now()->subMonths(5), 1.55, 1.62],
            [$cicloAnterior, now()->subMonths(4), 1.61, 1.67],
            [$cicloActual, now()->subWeeks(3), 0.31, 0.42],
            [$cicloActual, now()->subWeek(), 0.46, 0.58],
        ] as [$ciclo, $date, $bombas, $vapor]) {
            $elongacion = Elongacion::create([
                'linea_id' => $linea->id,
                'linea' => 'L-05',
                'cadena_ciclo_id' => $ciclo->id,
                'proveedor' => $ciclo->proveedor,
                'bombas_promedio' => 140 + $bombas,
                'bombas_porcentaje' => $bombas,
                'vapor_promedio' => 140 + $vapor,
                'vapor_porcentaje' => $vapor,
                'estado' => $vapor >= 1.46 ? 'critico' : 'normal',
                'estado_detallado' => $vapor >= 1.46 ? 'cambio' : 'normal',
                'paso_inicial' => 140,
                'hodometro' => 1000,
                'hodometro_ciclo' => 100,
            ]);
            $elongacion->forceFill([
                'created_at' => $date,
                'updated_at' => $date,
            ])->saveQuietly();
        }

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Dame Excel de tendencia de elongaciones de la linea 5',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.intent.dataset', 'elongaciones')
            ->assertJsonCount(1, 'message.metadata.artifacts');

        $assistantMessage = AssistantMessage::findOrFail((int) $response->json('message.id'));
        $storedArtifacts = $assistantMessage->metadata['artifacts'];
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($storedArtifacts[0]['path']));
        $datos = $spreadsheet->getSheetByName('Datos');
        $datosText = json_encode($datos?->rangeToArray('A1:'.$datos?->getHighestColumn().$datos?->getHighestRow()), JSON_UNESCAPED_UNICODE);

        $this->assertSame(3, $datos?->getHighestRow());
        $this->assertStringContainsString('L-05-C002', (string) $datosText);
        $this->assertStringNotContainsString('L-05-C001', (string) $datosText);
        $this->assertNull($spreadsheet->getSheetByName('Filtros'));
        $this->assertNull($spreadsheet->getSheetByName('Resumen'));

        $historyResponse = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Dame Excel de tendencia de elongaciones de la linea 5 con todos los ciclos',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $historyResponse
            ->assertOk()
            ->assertJsonPath('message.metadata.intent.dataset', 'elongaciones')
            ->assertJsonCount(1, 'message.metadata.artifacts');

        $historyMessage = AssistantMessage::findOrFail((int) $historyResponse->json('message.id'));
        $historyArtifact = $historyMessage->metadata['artifacts'][0];
        $historySpreadsheet = IOFactory::load(Storage::disk('local')->path($historyArtifact['path']));
        $historyDatos = $historySpreadsheet->getSheetByName('Datos');
        $historyText = json_encode($historyDatos?->rangeToArray('A1:'.$historyDatos?->getHighestColumn().$historyDatos?->getHighestRow()), JSON_UNESCAPED_UNICODE);

        $this->assertSame(5, $historyDatos?->getHighestRow());
        $this->assertStringContainsString('L-05-C001', (string) $historyText);
        $this->assertStringContainsString('L-05-C002', (string) $historyText);
        $this->assertNull($historySpreadsheet->getSheetByName('Filtros'));
        $this->assertNull($historySpreadsheet->getSheetByName('Resumen'));
    }

    public function test_chat_generates_enterprise_comparative_elongation_trend_by_washer(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'elongaciones',
                        'metric' => 'max_porcentaje',
                        'chart_type' => 'line',
                        'aggregation' => 'by_line',
                        'outputs' => ['image', 'excel'],
                        'lineas' => [],
                        'date_range' => [
                            'preset' => 'all',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Tendencia comparativa de elongacion por lavadora',
                        'confidence' => 0.96,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
        $lineas = collect(['L-04', 'L-05', 'L-06', 'L-13'])
            ->mapWithKeys(fn (string $nombre): array => [
                $nombre => Linea::create([
                    'nombre' => $nombre,
                    'tipo' => User::MODULE_LAVADORA,
                    'activo' => true,
                ]),
            ]);

        $cicloL04Anterior = CadenaCiclo::create([
            'linea_id' => $lineas['L-04']->id,
            'linea' => 'L-04',
            'codigo' => 'L-04-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor anterior',
            'paso_inicial' => 173,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(8),
            'retirada_en' => now()->subMonths(3),
            'activa' => false,
        ]);
        $cicloL04Actual = CadenaCiclo::create([
            'linea_id' => $lineas['L-04']->id,
            'linea' => 'L-04',
            'codigo' => 'L-04-C002',
            'numero_ciclo' => 2,
            'proveedor' => 'Proveedor actual',
            'paso_inicial' => 173,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(2),
            'activa' => true,
        ]);
        $cicloL05 = CadenaCiclo::create([
            'linea_id' => $lineas['L-05']->id,
            'linea' => 'L-05',
            'codigo' => 'L-05-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor actual',
            'paso_inicial' => 140,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(5),
            'activa' => true,
        ]);
        $cicloL06 = CadenaCiclo::create([
            'linea_id' => $lineas['L-06']->id,
            'linea' => 'L-06',
            'codigo' => 'L-06-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor actual',
            'paso_inicial' => 173,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(4),
            'activa' => true,
        ]);
        $cicloL13 = CadenaCiclo::create([
            'linea_id' => $lineas['L-13']->id,
            'linea' => 'L-13',
            'codigo' => 'L-13-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor actual',
            'paso_inicial' => 140,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonth(),
            'activa' => true,
        ]);

        foreach ([
            ['L-04', $cicloL04Anterior, now()->subMonths(6), 1.20, 1.25],
            ['L-04', $cicloL04Anterior, now()->subMonths(5), 1.55, 1.60],
            ['L-04', $cicloL04Actual, now()->subMonths(2), 0.60, 0.65],
            ['L-04', $cicloL04Actual, now()->subMonth(), 0.82, 0.90],
            ['L-05', $cicloL05, now()->subMonths(3), 1.22, 1.28],
            ['L-05', $cicloL05, now()->subWeek(), 1.42, 1.48],
            ['L-06', $cicloL06, now()->subWeeks(2), 1.28, 1.34],
            ['L-06', $cicloL06, now()->subDays(3), 1.32, 1.38],
            ['L-13', $cicloL13, now()->subDays(4), 0.75, 0.78],
        ] as [$linea, $ciclo, $date, $bombas, $vapor]) {
            $elongacion = Elongacion::create([
                'linea_id' => $lineas[$linea]->id,
                'linea' => $linea,
                'cadena_ciclo_id' => $ciclo->id,
                'proveedor' => $ciclo->proveedor,
                'bombas_promedio' => 140 + $bombas,
                'bombas_porcentaje' => $bombas,
                'vapor_promedio' => 140 + $vapor,
                'vapor_porcentaje' => $vapor,
                'estado' => $vapor >= 1.46 ? 'critico' : ($vapor >= 1.30 ? 'alerta' : 'normal'),
                'estado_detallado' => $vapor >= 1.46 ? 'cambio' : ($vapor >= 1.30 ? 'comprar' : 'normal'),
                'paso_inicial' => $ciclo->paso_inicial,
                'hodometro' => 1000,
                'hodometro_ciclo' => 100,
            ]);
            $elongacion->forceFill([
                'created_at' => $date,
                'updated_at' => $date,
            ])->saveQuietly();
        }

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Genera una grafica comparativa y profesional de las tendencias de elongacion de todas las lavadoras en SVG y Excel, usando todo el historial disponible. Usa grafica de lineas, una linea por cada lavadora, separa cambios de ciclo y marca el limite critico de 1.46%.',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.intent.dataset', 'elongaciones')
            ->assertJsonPath('message.metadata.intent.report_version', 'elongaciones-comparativo-v3')
            ->assertJsonCount(2, 'message.metadata.artifacts');

        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('Analisis automatico', $content);
        $this->assertStringContainsString('Lavadora con mayor elongacion actual', $content);
        $this->assertStringContainsString('Mayor incremento entre ultimas mediciones', $content);
        $this->assertStringContainsString('Lavadora L-13', $content);

        $assistantMessage = AssistantMessage::findOrFail((int) $response->json('message.id'));
        $storedArtifacts = $assistantMessage->metadata['artifacts'];
        $svgArtifact = collect($storedArtifacts)->firstWhere('kind', 'svg');
        $excelArtifact = collect($storedArtifacts)->firstWhere('kind', 'excel');

        $this->assertIsArray($svgArtifact);
        $this->assertIsArray($excelArtifact);

        $svg = Storage::disk('local')->get($svgArtifact['path']);
        $this->assertStringContainsString('Tendencia comparativa de elongacion por lavadora', $svg);
        $this->assertStringContainsString('Limite critico 1.46%', $svg);
        $this->assertStringContainsString('Lavadora L-04', $svg);
        $this->assertStringContainsString('Analisis automatico', $svg);
        $this->assertGreaterThanOrEqual(4, substr_count($svg, '<polyline'));
        Storage::disk('local')->assertExists($excelArtifact['path']);

        if (! class_exists(\ZipArchive::class)) {
            return;
        }

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($excelArtifact['path']));
        $this->assertNotNull($spreadsheet->getSheetByName('Analisis'));
        $tendencia = $spreadsheet->getSheetByName('Tendencia');
        $analisis = $spreadsheet->getSheetByName('Analisis');
        $tendenciaText = json_encode($tendencia?->rangeToArray('A1:'.$tendencia?->getHighestColumn().$tendencia?->getHighestRow()), JSON_UNESCAPED_UNICODE);
        $analisisText = json_encode($analisis?->rangeToArray('A1:'.$analisis?->getHighestColumn().$analisis?->getHighestRow()), JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('Lavadora L-13', (string) $tendenciaText);
        $this->assertStringContainsString('Sin datos historicos suficientes', (string) $analisisText);
        $this->assertStringContainsString('Lavadora L-06', (string) $analisisText);
    }

    public function test_chat_explains_when_artifact_dataset_has_no_data(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'elongaciones',
                        'metric' => 'max_porcentaje',
                        'chart_type' => 'line',
                        'aggregation' => 'monthly',
                        'outputs' => ['excel'],
                        'lineas' => ['L-05'],
                        'date_range' => [
                            'preset' => 'last_30_days',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Tendencia de elongaciones L-05',
                        'confidence' => 0.8,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
        Linea::create([
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Dame un ecxel de elongaciones de la linea 5 de los ultimos 30 dias',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.empty_artifact_dataset', true);

        $this->assertStringContainsString('No encontre datos', (string) $response->json('message.content'));
        $this->assertSame([], $response->json('message.metadata.artifacts') ?? []);
    }

    public function test_chat_generates_templated_excels_for_supported_artifact_reports(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;
                $decodedPrompt = json_decode((string) ($payload['user_prompt'] ?? ''), true);
                $prompt = Str::lower(Str::ascii((string) data_get($decodedPrompt, 'question', '')));
                $dataset = match (true) {
                    str_contains($prompt, 'costos') => 'costos_lavadora',
                    str_contains($prompt, 'planes') => 'plan_accion',
                    default => 'analisis_lavadora',
                };

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => $dataset,
                        'metric' => $dataset === 'costos_lavadora' ? 'costos' : 'registros',
                        'chart_type' => str_contains($prompt, 'ranking') || str_contains($prompt, 'por linea') ? 'bar' : 'line',
                        'aggregation' => str_contains($prompt, 'por linea') ? 'by_line' : 'monthly',
                        'outputs' => ['excel'],
                        'lineas' => ['L-05'],
                        'date_range' => [
                            'preset' => 'current_year',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Reporte operativo',
                        'confidence' => 0.86,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $analisis = AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'R-14',
            'lado' => 'BOMBAS',
            'fecha_analisis' => now()->toDateString(),
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'CAMBIAR SERVO CHICO',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        LavadoraCostEntry::create([
            'linea_id' => $linea->id,
            'analisis_lavadora_id' => $analisis->id,
            'componente_id' => $componente->id,
            'source_type' => LavadoraCostEntry::SOURCE_MANUAL,
            'source_reference' => 'OC-100',
            'cost_date' => now()->toDateString(),
            'quantity' => 2,
            'unit_cost' => 1500,
            'total_cost' => 3000,
            'component_snapshot' => 'Servo Chico',
            'catalog_name_snapshot' => 'Servo chico refaccion',
            'catalog_sku_snapshot' => 'SKU-100',
            'catalog_category_snapshot' => 'Servo',
            'unidad_medida_snapshot' => 'PZA',
            'sync_key' => 'manual-test-100',
        ]);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'CAMBIAR SERVO CHICO',
            'source' => 'manual',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'priority_level' => 'alta',
            'maintenance_type' => 'correctivo',
            'estado' => 'approved',
            'fecha_pcm1' => now()->subDay()->toDateString(),
            'completado' => false,
        ]);

        foreach ([
            'Dame Excel de analisis de lavadora linea 5 este año',
            'Dame Excel de costos de lavadora por linea este año',
            'Grafica la tendencia mensual de costos de lavadora de la linea 5 en Excel',
            'Dame Excel de planes de accion linea 5 este año',
        ] as $prompt) {
            $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
                'message' => $prompt,
                'page_context' => [
                    'module' => User::MODULE_LAVADORA,
                    'page_title' => 'Chat operativo',
                    'current_path' => '/dashboard/lavadoras',
                ],
            ]);

            $response
                ->assertOk()
                ->assertJsonCount(1, 'message.metadata.artifacts')
                ->assertJsonPath('message.metadata.artifacts.0.kind', 'excel');

            $normalizedPrompt = Str::lower(Str::ascii($prompt));

            if (str_contains($normalizedPrompt, 'costos')) {
                $response->assertJsonPath('message.metadata.intent.chart_type', 'bar');
            }

            $assistantMessage = AssistantMessage::findOrFail((int) $response->json('message.id'));
            $storedArtifacts = $assistantMessage->metadata['artifacts'];
            $spreadsheet = IOFactory::load(Storage::disk('local')->path($storedArtifacts[0]['path']));

            $this->assertContains('Dashboard', $spreadsheet->getSheetNames());
            $this->assertContains('Tendencia', $spreadsheet->getSheetNames());
            $this->assertContains('Datos', $spreadsheet->getSheetNames());
            $this->assertNotContains('Resumen', $spreadsheet->getSheetNames());
            $this->assertNotContains('Filtros', $spreadsheet->getSheetNames());

            if (str_contains($normalizedPrompt, 'costos')) {
                $isByLineCost = str_contains($normalizedPrompt, 'por linea');
                $this->assertNotContains('Alertas', $spreadsheet->getSheetNames());

                $tendencia = $spreadsheet->getSheetByName('Tendencia');
                $this->assertSame($isByLineCost ? 'Linea' : 'Periodo', $tendencia?->getCell('A1')->getValue());
                $this->assertSame('Registros', $tendencia?->getCell('B1')->getValue());
                $this->assertSame('Costo total MXN', $tendencia?->getCell('C1')->getValue());
                $this->assertSame('Componente principal', $tendencia?->getCell('D1')->getValue());
                $this->assertSame('Refaccion principal', $tendencia?->getCell('E1')->getValue());

                $costDashboard = $spreadsheet->getSheetByName('Dashboard');
                $this->assertSame('Cambios por componente/refaccion', $costDashboard?->getCell('K6')->getValue());
                $this->assertSame('Componente', $costDashboard?->getCell('K7')->getValue());
                $this->assertSame('Refaccion', $costDashboard?->getCell('L7')->getValue());
                $this->assertSame('Cambios', $costDashboard?->getCell('M7')->getValue());
                $this->assertSame('Costo total', $costDashboard?->getCell('N7')->getValue());

                $datos = $spreadsheet->getSheetByName('Datos');
                $this->assertSame('Lavadora / Linea', $datos?->getCell('B1')->getValue());
                $this->assertSame('Maquina', $datos?->getCell('C1')->getValue());
                $this->assertSame('Componente', $datos?->getCell('D1')->getValue());
                $this->assertSame('Refaccion', $datos?->getCell('E1')->getValue());
                $this->assertSame('Lavadora L-05', $datos?->getCell('B2')->getValue());
                $this->assertSame('Lavadora', $datos?->getCell('C2')->getValue());
                $this->assertSame('Servo Chico', $datos?->getCell('D2')->getValue());
                $this->assertSame('Servo chico refaccion', $datos?->getCell('E2')->getValue());
            }

            $dashboard = $spreadsheet->getSheetByName('Dashboard');
            $this->assertNotNull($dashboard);
            if (extension_loaded('gd')) {
                $this->assertGreaterThan(0, count($dashboard?->getDrawingCollection() ?? []));
            }
        }
    }

    public function test_chat_graphs_washer_component_states_as_bar_chart(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'analisis_lavadora',
                        'metric' => 'danos',
                        'chart_type' => 'line',
                        'aggregation' => 'monthly',
                        'outputs' => ['image', 'excel'],
                        'lineas' => ['L-05'],
                        'date_range' => [
                            'preset' => 'current_year',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Estado de componentes L-05',
                        'confidence' => 0.9,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        foreach ([
            AnalisisLavadora::ESTADO_REQUIERE_REVISION,
            'Desgaste severo',
            'Desgaste moderado',
            AnalisisLavadora::ESTADO_DANADO,
            AnalisisLavadora::ESTADO_CAMBIADO,
        ] as $index => $estado) {
            AnalisisLavadora::create([
                'linea_id' => $linea->id,
                'componente_id' => $componente->id,
                'reductor' => 'R-'.(10 + $index),
                'lado' => 'BOMBAS',
                'fecha_analisis' => now()->subDays($index)->toDateString(),
                'estado' => $estado,
                'actividad' => 'REVISION DE ESTADO '.$index,
                'usuario_id' => $user->id,
                'tipo_equipo' => User::MODULE_LAVADORA,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Grafica el estado de componentes requiere revision severo moderado danados y cambiados de la linea 5 en imagen y Excel',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.intent.dataset', 'analisis_lavadora')
            ->assertJsonPath('message.metadata.intent.chart_type', 'bar')
            ->assertJsonCount(2, 'message.metadata.artifacts');

        $assistantMessage = AssistantMessage::findOrFail((int) $response->json('message.id'));
        $storedArtifacts = $assistantMessage->metadata['artifacts'];
        $this->assertContains($storedArtifacts[0]['kind'], ['image', 'svg']);
        $this->assertSame('excel', $storedArtifacts[1]['kind']);
        if (($storedArtifacts[0]['kind'] ?? null) === 'image') {
            $this->assertStringStartsWith("\x89PNG", Storage::disk('local')->get($storedArtifacts[0]['path']));
        } else {
            $this->assertStringContainsString('<svg', Storage::disk('local')->get($storedArtifacts[0]['path']));
        }

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($storedArtifacts[1]['path']));
        $tendencia = $spreadsheet->getSheetByName('Tendencia');

        $this->assertSame('Estado operativo', $tendencia?->getCell('A1')->getValue());
        $this->assertSame('Buen estado', $tendencia?->getCell('A2')->getValue());
        $this->assertSame('Requieren revision', $tendencia?->getCell('A3')->getValue());
        $this->assertSame('Severo / Moderado', $tendencia?->getCell('A4')->getValue());
        $this->assertSame('Danados', $tendencia?->getCell('A5')->getValue());
        $this->assertSame('Cambiados', $tendencia?->getCell('A6')->getValue());
        $this->assertNull($spreadsheet->getSheetByName('Filtros'));
        $this->assertNull($spreadsheet->getSheetByName('Resumen'));
    }

    public function test_chat_component_state_chart_distinguishes_current_from_total_history(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'analisis_lavadora',
                        'metric' => 'danos',
                        'chart_type' => 'bar',
                        'aggregation' => 'by_line',
                        'outputs' => ['excel'],
                        'lineas' => ['L-05'],
                        'date_range' => [
                            'preset' => 'all',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Estado de componentes L-05',
                        'confidence' => 0.9,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $servo = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $rodaja = Componente::create([
            'nombre' => 'Rodaja',
            'codigo' => 'RODAJA',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        foreach ([
            [$servo, 'R-10', 'BOMBAS', AnalisisLavadora::ESTADO_DANADO, now()->subDays(20)],
            [$servo, 'R-10', 'BOMBAS', AnalisisLavadora::ESTADO_CAMBIADO, now()->subDays(2)],
            [$rodaja, 'R-11', 'VAPOR', 'Desgaste moderado', now()->subDay()],
        ] as [$component, $reductor, $lado, $estado, $date]) {
            AnalisisLavadora::create([
                'linea_id' => $linea->id,
                'componente_id' => $component->id,
                'reductor' => $reductor,
                'lado' => $lado,
                'fecha_analisis' => $date->toDateString(),
                'estado' => $estado,
                'actividad' => 'REVISION '.$estado,
                'usuario_id' => $user->id,
                'tipo_equipo' => User::MODULE_LAVADORA,
            ]);
        }

        $currentResponse = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Grafica el estado de componentes actuales de la linea 5 en Excel',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $currentResponse
            ->assertOk()
            ->assertJsonPath('message.metadata.intent.chart_type', 'bar')
            ->assertJsonCount(2, 'message.metadata.artifacts');

        $currentMessage = AssistantMessage::findOrFail((int) $currentResponse->json('message.id'));
        $currentSpreadsheet = IOFactory::load(Storage::disk('local')->path($currentMessage->metadata['artifacts'][1]['path']));
        $currentTrend = $currentSpreadsheet->getSheetByName('Tendencia');

        $this->assertSame(0, (int) $currentTrend?->getCell('B5')->getValue());
        $this->assertSame(1, (int) $currentTrend?->getCell('B6')->getValue());
        $this->assertNull($currentSpreadsheet->getSheetByName('Filtros'));
        $this->assertNull($currentSpreadsheet->getSheetByName('Resumen'));

        $totalResponse = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Grafica el estado de componentes en total de la linea 5 en Excel',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $totalResponse
            ->assertOk()
            ->assertJsonPath('message.metadata.intent.chart_type', 'bar')
            ->assertJsonCount(2, 'message.metadata.artifacts');

        $totalMessage = AssistantMessage::findOrFail((int) $totalResponse->json('message.id'));
        $totalSpreadsheet = IOFactory::load(Storage::disk('local')->path($totalMessage->metadata['artifacts'][1]['path']));
        $totalTrend = $totalSpreadsheet->getSheetByName('Tendencia');

        $this->assertSame(1, (int) $totalTrend?->getCell('B5')->getValue());
        $this->assertSame(1, (int) $totalTrend?->getCell('B6')->getValue());
        $this->assertNull($totalSpreadsheet->getSheetByName('Filtros'));
        $this->assertNull($totalSpreadsheet->getSheetByName('Resumen'));
    }

    public function test_chat_explains_when_requested_artifact_dataset_is_not_configured(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'unsupported',
                        'metric' => 'registros',
                        'chart_type' => 'line',
                        'aggregation' => 'monthly',
                        'outputs' => ['excel'],
                        'lineas' => [],
                        'date_range' => [
                            'preset' => 'last_12_months',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Tendencia de donaciones',
                        'confidence' => 0.88,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Grafica la tendencia de donaciones en Excel',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.unsupported_artifact_dataset', true);

        $this->assertSame('assistant_analytics_intent', $capturingProvider->payloads[0]['schema_name'] ?? null);
        $this->assertStringContainsString('datasets operativos configurados', (string) $response->json('message.content'));
        $this->assertSame([], $response->json('message.metadata.artifacts') ?? []);
    }

    public function test_chat_rejects_artifact_requests_for_invalid_washer_lines(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.chat.model' => 'gemini-3.6-flash',
        ]);

        Storage::fake('local');

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'should_generate' => true,
                        'dataset' => 'elongaciones',
                        'metric' => 'max_porcentaje',
                        'chart_type' => 'line',
                        'aggregation' => 'monthly',
                        'outputs' => ['image', 'excel'],
                        'lineas' => ['L-99'],
                        'date_range' => [
                            'preset' => 'last_12_months',
                            'from' => '',
                            'to' => '',
                        ],
                        'title' => 'Tendencia de elongaciones L-99',
                        'confidence' => 0.9,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $payload['model'] ?? 'gemini-3.6-flash',
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

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Graficame elongaciones de la linea 99 en imagen y ecxel',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/dashboard/lavadoras',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.invalid_artifact_filter', true)
            ->assertJsonPath('message.metadata.invalid_lineas.0', 'L-99');

        $this->assertStringContainsString('Lineas validas', (string) $response->json('message.content'));
        $this->assertSame([], $response->json('message.metadata.artifacts') ?? []);
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

    public function test_chat_answers_with_refaction_cost_for_specific_washer_line(): void
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
        $this->seedRefactionCostKnowledge($user);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Cuanto cuesta una catarina para la lavadora 7',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta tecnica',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('SKU 4094364', $content);
        $this->assertStringContainsString('CATARINA DE ACERO COLADO PASO 173', $content);
        $this->assertStringContainsString('$48,000.00 MXN por PZA', $content);
        $this->assertStringContainsString('L-07', $content);
    }

    public function test_chat_answers_with_refaction_variants_when_line_is_not_specified(): void
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
        $this->seedRefactionCostKnowledge($user);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Cuanto cuesta una catarina de lavadora',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta tecnica',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('PASO 125', $content);
        $this->assertStringContainsString('PASO 173', $content);
        $this->assertStringContainsString('PASO 140', $content);
    }

    public function test_chat_prioritizes_specific_guia_inferior_refaction_cost(): void
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
        $guideItems = [
            '4066459' => [
                'nombre' => 'GUIA DE CADENA PORTACANASTILLAS BAJADA DE LOS PRELAVADOS',
                'costo_unitario' => 9300.00,
                'component_code' => 'GUI_SUP_TANQUE',
            ],
            '4066460' => [
                'nombre' => 'GUIA DE CADENA PORTACANASTILLAS BAJADA A TANQUES CAUSTICOS',
                'costo_unitario' => 9000.00,
                'component_code' => 'GUI_INT_TANQUE',
            ],
            '4066462' => [
                'nombre' => 'GUIA DE CADENA PORTACANASTILLAS BAJADA A LA DESCARGA',
                'costo_unitario' => 4490.00,
                'component_code' => 'GUI_INF_TANQUE',
            ],
        ];

        foreach ($guideItems as $sku => $item) {
            $catalogItem = CostCatalogItem::create([
                'sku' => $sku,
                'nombre' => $item['nombre'],
                'categoria' => 'Guia',
                'unidad_medida' => 'Pieza',
                'costo_unitario' => $item['costo_unitario'],
                'activo' => true,
                'aliases' => ['GUIA', 'CADENA'],
            ]);

            CostAutomationRule::create([
                'cost_catalog_item_id' => $catalogItem->id,
                'component_code' => $item['component_code'],
                'trigger_type' => CostAutomationRule::TRIGGER_ESTADO_CAMBIADO,
                'quantity' => 1,
                'priority' => 10,
                'activo' => true,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Que precio tiene una guia inferior?',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta tecnica',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('SKU 4066462', $content);
        $this->assertStringContainsString('GUIA DE CADENA PORTACANASTILLAS BAJADA A LA DESCARGA', $content);
        $this->assertStringContainsString('$4,490.00 MXN por PZA', $content);
        $this->assertStringContainsString('Guia Inferior', $content);
        $this->assertStringNotContainsString('SKU 4066459', $content);
        $this->assertStringNotContainsString('Guia Superior', $content);
    }

    public function test_chat_lists_related_refactions_for_component_and_line(): void
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
        $this->seedRefactionCostKnowledge($user);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Que refacciones lleva la catarina de la lavadora 7',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta tecnica',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('CATARINA DE ACERO COLADO PASO 173', $content);
        $this->assertStringContainsString('DADO TRIBLOCK', $content);
        $this->assertStringContainsString('TORNILLO CAB. HEX.', $content);
    }

    public function test_chat_answers_with_structured_lubrication_lookup_and_related_knowledge_document(): void
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
            'nombre' => 'L-09',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        CostCatalogItem::create([
            'sku' => '4056384',
            'nombre' => 'Aceite Glygoyle_30',
            'categoria' => 'Lubricante',
            'unidad_medida' => 'Litro',
            'costo_unitario' => 465.25,
            'activo' => true,
            'aliases' => ['ACEITE', 'LUBRICANTE', 'GLYGOYLE_30', 'SERVO CHICO'],
        ]);

        $document = WasherKnowledgeDocument::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'title' => 'Manual de lubricacion servo chico L-09',
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'storage_disk' => 'local',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
            'indexing_status' => 'indexed',
            'extracted_text' => 'La linea 9 utiliza Aceite Glygoyle_30 para servos chicos con referencia de 1.5 LT.',
            'indexed_at' => now(),
        ]);

        WasherKnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'La linea 9 utiliza Aceite Glygoyle_30 para servos chicos con referencia de 1.5 LT.',
            'searchable_text' => 'la linea 9 utiliza aceite glygoyle 30 para servos chicos con referencia de 1.5 lt',
            'token_count' => 14,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Que aceite llevan los servos chicos de la linea 9?',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta tecnica',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'platform-insights');

        $this->assertSame([], $capturingProvider->payloads);
        $content = (string) $response->json('message.content');
        $this->assertStringContainsString('Aceite Glygoyle_30', $content);
        $this->assertStringContainsString('SKU 4056384', $content);
        $this->assertStringContainsString('1.5 LT', $content);
        $this->assertStringContainsString('Manual de lubricacion servo chico L-09', $content);
    }

    public function test_chat_includes_uploaded_knowledge_documents_in_ai_context(): void
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
                        'answer' => 'Resumen generado con base en el documento indexado.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.88,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'assistant-doc-model',
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
            'nombre' => 'L-09',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $componente = Componente::create([
            'nombre' => 'Servo Chico',
            'codigo' => 'SERVO_CHICO',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        $document = WasherKnowledgeDocument::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'title' => 'Guia tecnica de lubricacion L-09',
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'storage_disk' => 'local',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
            'indexing_status' => 'indexed',
            'extracted_text' => 'El procedimiento documentado para la linea 9 indica revisar el nivel de aceite del servo chico y confirmar el uso de Glygoyle_30.',
            'indexed_at' => now(),
        ]);

        WasherKnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'El procedimiento documentado para la linea 9 indica revisar el nivel de aceite del servo chico y confirmar el uso de Glygoyle_30.',
            'searchable_text' => 'el procedimiento documentado para la linea 9 indica revisar el nivel de aceite del servo chico y confirmar el uso de glygoyle 30',
            'token_count' => 21,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Segun el documento de conocimiento, que recomienda la guia tecnica de la linea 9 para el servo chico?',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat operativo',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta documental',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'fake');

        $capturedPayload = $capturingProvider->payloads[0] ?? [];
        $userPrompt = (string) ($capturedPayload['user_prompt'] ?? '');

        $this->assertStringContainsString('Guia tecnica de lubricacion L-09', $userPrompt);
        $this->assertStringContainsString('confirmar el uso de Glygoyle_30', $userPrompt);
    }

    public function test_chat_includes_prioritized_technical_context_for_oil_leak_solution(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.technical_context.history_limit_per_bucket' => 3,
            'maintenance_ai.technical_context.document_limit' => 3,
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'Recomendacion contextual para fuga de aceite en reductores de L-13.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.86,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'technical-context-test',
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
        $linea13 = Linea::create([
            'nombre' => 'L-13',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $linea05 = Linea::create([
            'nombre' => 'L-05',
            'tipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $reductor13 = Componente::create([
            'nombre' => 'Reductor RV200',
            'codigo' => 'L13_REDUCTOR_1_RV200',
            'linea' => 'L-13',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $reductor13Secundario = Componente::create([
            'nombre' => 'Reductor RV200',
            'codigo' => 'L13_REDUCTOR_2_RV200',
            'linea' => 'L-13',
            'reductor' => 'Reductor 2',
            'ubicacion' => 'Reductor 2',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);
        $reductor05 = Componente::create([
            'nombre' => 'Reductor RV200',
            'codigo' => 'L05_REDUCTOR_1_RV200',
            'linea' => 'L-05',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea13->id,
            'componente_id' => $reductor13->id,
            'reductor' => 'Reductor 1',
            'lado' => 'salida',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-L13-RETEN',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Fuga de aceite en reductor RV200; se observo aceite en eje de salida.',
            'observaciones_reparacion' => 'Se cambio reten del eje de salida y se libero respiradero.',
            'tipo_intervencion' => 'Cambio de reten',
            'componente_instalado' => 'Reten de salida RV200',
            'fecha_cambio' => '2026-07-11',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea13->id,
            'componente_id' => $reductor13Secundario->id,
            'reductor' => 'Reductor 2',
            'lado' => 'entrada',
            'fecha_analisis' => '2026-07-12',
            'numero_orden' => 'OT-L13-RESP',
            'estado' => AnalisisLavadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Fuga leve de aceite por respiradero bloqueado en reductor.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea05->id,
            'componente_id' => $reductor05->id,
            'reductor' => 'Reductor 1',
            'lado' => 'salida',
            'fecha_analisis' => '2026-07-08',
            'numero_orden' => 'OT-L05-EMP',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Fuga de aceite en carcasa por empaque deteriorado.',
            'usuario_id' => $user->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        $historicalEvent = MaintenanceEvent::create([
            'linea_id' => $linea13->id,
            'componente_id' => $reductor13->id,
            'source_type' => 'analisis_lavadora',
            'source_id' => 999,
            'event_type' => 'component_damaged',
            'severity' => 'critical',
            'title' => 'Fuga de aceite en reductor',
            'description' => 'Antecedente de fuga por reten y respiradero.',
            'status' => MaintenanceEvent::STATUS_RESOLVED,
            'fingerprint' => 'oil-leak-chat-history',
            'detected_at' => '2026-07-10 08:00:00',
        ]);

        PlanAccion::create([
            'linea_id' => $linea13->id,
            'maintenance_event_id' => $historicalEvent->id,
            'actividad' => 'Sustituir reten y limpiar respiradero de reductor RV200',
            'source' => 'manual',
            'tipo_equipo' => User::MODULE_LAVADORA,
            'priority_level' => 'critical',
            'maintenance_type' => 'corrective',
            'detected_problem' => 'Fuga de aceite por reten del eje de salida.',
            'technical_justification' => 'El aceite se acumulaba en la base del reductor durante operacion.',
            'risk_if_not_executed' => 'Perdida de lubricacion y dano interno del reductor.',
            'estado' => 'approved',
            'completado' => true,
            'fecha_ejecucion' => '2026-07-11',
            'execution_result' => 'Despues del cambio de reten y limpieza del respiradero no reaparecio la fuga.',
            'effectiveness' => PlanAccion::EFFECTIVENESS_EFFECTIVE,
        ]);

        $document = WasherKnowledgeDocument::create([
            'linea_id' => $linea13->id,
            'componente_id' => $reductor13->id,
            'title' => 'Manual reductores RV200 L-13',
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'storage_disk' => 'local',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
            'indexing_status' => 'indexed',
            'extracted_text' => 'Para fugas de aceite en reductores RV200 revisar retenes, empaques, nivel de aceite y respiradero.',
            'indexed_at' => now(),
        ]);

        WasherKnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'Para fugas de aceite en reductores RV200 revisar retenes, empaques, nivel de aceite y respiradero.',
            'searchable_text' => 'fugas aceite reductores rv200 retenes empaques nivel aceite respiradero',
            'token_count' => 12,
        ]);

        $response = $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Dame una solucion para la fuga de aceite de los reductores de la Linea 13',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'page_title' => 'Chat tecnico',
                'current_path' => '/asistente/chat',
                'section' => 'Consulta tecnica',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message.metadata.provider', 'fake');

        $this->assertGreaterThanOrEqual(5, (int) $response->json('message.metadata.technical_context_records'));
        $this->assertSame(1, (int) $response->json('message.metadata.technical_context_sources'));

        $prompt = (string) ($capturingProvider->payloads[0]['user_prompt'] ?? '');

        $this->assertStringContainsString('"technical_recommendation_context"', $prompt);
        $this->assertStringContainsString('"same_type_same_washer"', $prompt);
        $this->assertStringContainsString('"same_component_other_washers"', $prompt);
        $this->assertStringContainsString('Se cambio reten del eje de salida', $prompt);
        $this->assertStringContainsString('respiradero bloqueado', $prompt);
        $this->assertStringContainsString('Manual reductores RV200 L-13', $prompt);
        $this->assertStringContainsString('Despues del cambio de reten', $prompt);
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

    private function seedRefactionCostKnowledge(User $user): void
    {
        foreach ([
            ['sku' => '4064265', 'nombre' => 'CATARINA DE ACERO COLADO PASO 125', 'categoria' => 'Catarina', 'unidad_medida' => 'Pieza', 'costo_unitario' => 47410.00, 'aliases' => ['CATARINA', 'SPROCKET']],
            ['sku' => '4094364', 'nombre' => 'CATARINA DE ACERO COLADO PASO 173', 'categoria' => 'Catarina', 'unidad_medida' => 'Pieza', 'costo_unitario' => 48000.00, 'aliases' => ['CATARINA', 'SPROCKET']],
            ['sku' => '4065310', 'nombre' => 'CATARINA DE ACERO COLADO PASO 140', 'categoria' => 'Catarina', 'unidad_medida' => 'Pieza', 'costo_unitario' => 48000.00, 'aliases' => ['CATARINA', 'SPROCKET']],
            ['sku' => '4153062', 'nombre' => 'DADO TRIBLOCK N.P. HHC420 MCA. SIMONAZZI', 'categoria' => 'Tornilleria', 'unidad_medida' => 'Pieza', 'costo_unitario' => 69.18, 'aliases' => ['DADO', 'TRIBLOCK', 'CATARINA']],
            ['sku' => '4073113', 'nombre' => 'TORNILLO CAB. HEX. GALV. 8.8 DE M20X2.5X65 R. CORRIDA.', 'categoria' => 'Tornilleria', 'unidad_medida' => 'Pieza', 'costo_unitario' => 150.00, 'aliases' => ['TORNILLO', 'CATARINA']],
        ] as $item) {
            CostCatalogItem::query()->updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'nombre' => $item['nombre'],
                    'categoria' => $item['categoria'],
                    'unidad_medida' => $item['unidad_medida'],
                    'costo_unitario' => $item['costo_unitario'],
                    'activo' => true,
                    'aliases' => $item['aliases'],
                ]
            );
        }

        $content = implode("\n", [
            '# Base de conocimiento - Costos de refacciones para lavadoras',
            '## SKU 4064265 - CATARINA DE ACERO COLADO PASO 125 - Costo unitario: $47,410.00 MXN por PZA - Lavadoras: L8 - Componentes: Catarinas',
            '## SKU 4094364 - CATARINA DE ACERO COLADO PASO 173 - Costo unitario: $48,000.00 MXN por PZA - Lavadoras: L4, L6, L7 - Componentes: Catarinas',
            '## SKU 4065310 - CATARINA DE ACERO COLADO PASO 140 - Costo unitario: $48,000.00 MXN por PZA - Lavadoras: L5, L9, L12, L13 - Componentes: Catarinas',
            '## SKU 4153062 - DADO TRIBLOCK N.P. HHC420 MCA. SIMONAZZI - Costo unitario: $69.18 MXN por PZA - Lavadoras: Todas - Componentes: Bujes De Baquelita - Espiga De Flecha, Catarinas - Cantidad de referencia: 6 PZA - Costo de referencia: $415.08 MXN',
            '## SKU 4073113 - TORNILLO CAB. HEX. GALV. 8.8 DE M20X2.5X65 R. CORRIDA. - Costo unitario: $150.00 MXN por PZA - Lavadoras: Todas - Componentes: Bujes De Baquelita - Espiga De Flecha, Catarinas - Cantidad de referencia: 6 PZA - Costo de referencia: $900.00 MXN',
        ]);

        $document = WasherKnowledgeDocument::create([
            'title' => 'Costos y refas lav',
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'storage_disk' => 'local',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
            'indexing_status' => 'indexed',
            'extracted_text' => $content,
            'indexed_at' => now(),
        ]);

        WasherKnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 1,
            'content' => $content,
            'searchable_text' => Str::lower(Str::ascii($content)),
            'token_count' => 120,
        ]);
    }
}
