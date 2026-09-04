<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Jobs\GeneratePasteurizadoraActionPlan;
use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\CentralHidraulicaComponente;
use App\Models\CentralHidraulicaConfiguracion;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;
use App\Notifications\PasteurizadoraAiPlanPendingReviewNotification;
use App\Services\Maintenance\PasteurizadoraActionPlanGenerator;
use App\Services\Maintenance\PasteurizadoraMaintenanceOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasteurizadoraMaintenanceAiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_orchestrator_dispatches_a_single_job_for_the_same_pasteurizadora_detection(): void
    {
        Queue::fake();

        config([
            'maintenance_ai.enabled' => true,
        ]);

        $analysis = $this->createDamagedAnalysis();
        $orchestrator = app(PasteurizadoraMaintenanceOrchestrator::class);

        $orchestrator->processAnalysis($analysis);
        $orchestrator->processAnalysis($analysis->fresh(['linea', 'usuario']));

        $this->assertDatabaseCount('maintenance_events', 1);
        $this->assertDatabaseHas('maintenance_events', [
            'source_type' => 'analisis_pasteurizadora',
            'source_id' => $analysis->id,
            'event_type' => 'component_damaged',
            'severity' => 'critical',
        ]);
        Queue::assertPushed(GeneratePasteurizadoraActionPlan::class, 1);
    }

    public function test_orchestrator_dispatches_ai_plan_for_central_hidraulica_detection_with_context(): void
    {
        Queue::fake();

        config([
            'maintenance_ai.enabled' => true,
        ]);

        $analysis = $this->createDamagedCentralAnalysis();
        $orchestrator = app(PasteurizadoraMaintenanceOrchestrator::class);

        $orchestrator->processCentralAnalysis($analysis);
        $orchestrator->processCentralAnalysis($analysis->fresh(['linea', 'usuario', 'configuracion', 'componente']));

        $this->assertDatabaseCount('maintenance_events', 1);

        $event = MaintenanceEvent::query()->firstOrFail();

        $this->assertSame('analisis_central_hidraulica', $event->source_type);
        $this->assertSame($analysis->id, $event->source_id);
        $this->assertSame('component_damaged', $event->event_type);
        $this->assertSame('critical', $event->severity);
        $this->assertSame(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA, $event->context_data['area']);
        $this->assertSame('ELECTROVALVULAS', $event->context_data['component_code']);
        $this->assertSame(CentralHidraulicaConfiguracion::PISO_SUPERIOR, $event->context_data['piso']);
        $this->assertSame(AnalisisCentralHidraulica::LADO_1, $event->context_data['lado']);
        Queue::assertPushed(GeneratePasteurizadoraActionPlan::class, 1);
    }

    public function test_generator_creates_a_pending_review_pasteurizadora_plan_and_notifies_reviewers(): void
    {
        Notification::fake();

        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.provider' => 'openai',
        ]);

        $reviewer = $this->userWithRole(User::ROLE_ADMIN, true);
        $analysis = $this->createDamagedAnalysis($reviewer);
        $event = $this->createEventForAnalysis($analysis);

        $this->app->instance(AiProviderInterface::class, new class implements AiProviderInterface
        {
            public function generateStructuredActionPlan(array $payload): array
            {
                return [
                    'data' => [
                        'title' => 'Cambiar anillas de pasteurizadora',
                        'priority' => 'critical',
                        'maintenance_type' => 'corrective',
                        'detected_problem' => 'Anillas con dano severo y riesgo de paro.',
                        'technical_justification' => 'El estado reportado requiere cambio y validacion mecanica.',
                        'recommended_actions' => [
                            [
                                'order' => 1,
                                'activity' => 'Inspeccionar y cambiar anillas danadas',
                                'technical_detail' => 'Bloquear energia, verificar modulo y sustituir las anillas comprometidas.',
                            ],
                        ],
                        'suggested_due_date' => '2026-08-20',
                        'risk_if_not_executed' => 'Puede continuar el desgaste y afectar la continuidad de la pasteurizadora.',
                        'estimated_cost' => [
                            'minimum' => 0,
                            'maximum' => 0,
                            'currency' => 'MXN',
                            'based_on_historical_data' => false,
                        ],
                        'knowledge_sources' => [
                            [
                                'type' => 'revision',
                                'reference' => 'Analisis pasteurizadora',
                                'document_id' => null,
                                'chunk_index' => null,
                                'page' => null,
                                'section' => null,
                            ],
                        ],
                        'confidence' => 0.84,
                        'requires_human_approval' => true,
                        'missing_information' => [],
                    ],
                    'raw' => ['provider' => 'fake'],
                    'meta' => [
                        'provider' => 'openai',
                        'model' => 'fake-model',
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
        });

        $plan = app(PasteurizadoraActionPlanGenerator::class)->generate($event);

        $this->assertSame('ai', $plan->source);
        $this->assertSame('pending_review', $plan->estado);
        $this->assertSame('CAMBIAR ANILLAS DE PASTEURIZADORA', $plan->actividad);
        $this->assertSame(User::MODULE_PASTEURIZADORA, $plan->tipo_equipo);
        $this->assertSame(AnalisisPasteurizadora::AREA_MECANICA, $plan->area_pasteurizadora);
        $this->assertSame('ANILLAS', $plan->source_metadata['component_code']);
        $this->assertSame(1, $plan->source_metadata['modulo']);
        $this->assertSame($event->id, $plan->maintenance_event_id);
        $this->assertNotEmpty($plan->original_generated_content);

        Notification::assertSentTo($reviewer, PasteurizadoraAiPlanPendingReviewNotification::class);
    }

    public function test_generator_creates_a_pending_review_central_hidraulica_plan_with_metadata(): void
    {
        Notification::fake();

        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.provider' => 'openai',
        ]);

        $reviewer = $this->userWithRole(User::ROLE_ADMIN, true);
        $analysis = $this->createDamagedCentralAnalysis($reviewer);
        $event = $this->createEventForCentralAnalysis($analysis);
        $structured = $this->structuredActionPlan([
            'title' => 'Corregir electrovalvulas de central hidraulica',
            'detected_problem' => 'Electrovalvulas con dano critico en central hidraulica.',
            'technical_justification' => 'El estado reportado compromete la respuesta hidraulica.',
            'risk_if_not_executed' => 'Puede degradarse el accionamiento hidraulico de la pasteurizadora.',
        ]);

        $this->app->instance(AiProviderInterface::class, new class($structured) implements AiProviderInterface
        {
            public function __construct(private readonly array $structured)
            {
            }

            public function generateStructuredActionPlan(array $payload): array
            {
                return [
                    'data' => $this->structured,
                    'raw' => ['provider' => 'fake'],
                    'meta' => [
                        'provider' => 'openai',
                        'model' => 'fake-model',
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
        });

        $plan = app(PasteurizadoraActionPlanGenerator::class)->generate($event);

        $this->assertSame('ai', $plan->source);
        $this->assertSame('pending_review', $plan->estado);
        $this->assertSame('CORREGIR ELECTROVALVULAS DE CENTRAL HIDRAULICA', $plan->actividad);
        $this->assertSame(User::MODULE_PASTEURIZADORA, $plan->tipo_equipo);
        $this->assertSame(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA, $plan->area_pasteurizadora);
        $this->assertSame('ELECTROVALVULAS', $plan->source_metadata['component_code']);
        $this->assertSame(CentralHidraulicaConfiguracion::PISO_SUPERIOR, $plan->source_metadata['piso']);
        $this->assertSame('Piso Superior', $plan->source_metadata['piso_label']);
        $this->assertSame(AnalisisCentralHidraulica::LADO_1, $plan->source_metadata['lado']);
        $this->assertSame('Lado 1', $plan->source_metadata['lado_label']);
        $this->assertSame($event->id, $plan->maintenance_event_id);

        Notification::assertSentTo($reviewer, PasteurizadoraAiPlanPendingReviewNotification::class);
    }

    public function test_reviewer_can_open_pasteurizadora_ai_queue_index(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $this->createPendingAiPlan($admin);

        $this->actingAs($admin)
            ->get(route('plan-accion.ai.pasteurizadora.index'))
            ->assertOk()
            ->assertSee('Revision de sugerencias IA para pasteurizadoras')
            ->assertSee('CAMBIAR ANILLAS SUGERIDO POR IA');
    }

    public function test_only_pasteurizadora_reviewers_can_open_and_approve_ai_plans(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $technician = $this->userWithRole(User::ROLE_TECNICO, true);
        $this->grantPasteurizadoraAccess($technician);
        $plan = $this->createPendingAiPlan($admin);

        $this->actingAs($technician)
            ->get(route('plan-accion.ai.pasteurizadora.review', ['planAccion' => $plan->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('plan-accion.index', ['tipo' => User::MODULE_PASTEURIZADORA]))
            ->assertOk()
            ->assertDontSee('data-actividad="CAMBIAR ANILLAS SUGERIDO POR IA"', false);

        $response = $this->actingAs($admin)->post(route('plan-accion.ai.pasteurizadora.approve', ['planAccion' => $plan->id]), [
            'title' => 'Cambiar anillas validado',
            'priority' => 'high',
            'maintenance_type' => 'corrective',
            'suggested_due_date' => '2026-08-22',
            'detected_problem' => 'Desgaste critico confirmado por revision humana.',
            'technical_justification' => 'Se verifico riesgo de paro por anillas danadas.',
            'risk_if_not_executed' => 'Paro de pasteurizadora y dano progresivo.',
            'review_notes' => 'Se ajusto fecha y prioridad despues de revisar el historial.',
            'recommended_actions' => [
                [
                    'order' => '1',
                    'activity' => 'Cambiar anillas',
                    'technical_detail' => 'Bloquear energia, desmontar el conjunto y montar anillas nuevas.',
                ],
            ],
        ]);

        $response->assertRedirect(route('plan-accion.ai.pasteurizadora.review', ['planAccion' => $plan->id]));

        $plan->refresh();

        $this->assertSame('approved', $plan->estado);
        $this->assertSame($admin->id, $plan->reviewed_by);
        $this->assertSame(User::MODULE_PASTEURIZADORA, $plan->tipo_equipo);
        $this->assertSame(AnalisisPasteurizadora::AREA_MECANICA, $plan->area_pasteurizadora);
        $this->assertSame('Cambiar anillas validado', $plan->approved_content['title']);
        $this->assertSame(MaintenanceEvent::STATUS_PLAN_GENERATED, $plan->maintenanceEvent->fresh()->status);

        $this->actingAs($admin)
            ->get(route('plan-accion.index', ['tipo' => User::MODULE_PASTEURIZADORA]))
            ->assertOk()
            ->assertSee('CAMBIAR ANILLAS VALIDADO');
    }

    public function test_assistant_prompt_includes_pasteurizadora_ai_plan_context(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.provider' => 'openai',
        ]);

        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $linea = $this->pasteurizadoraLine();

        AnalisisPasteurizadora::create([
            'area' => AnalisisPasteurizadora::AREA_MECANICA,
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-08-05',
            'numero_orden' => '10000001',
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Anillas con desgaste severo en modulo 1 lado vapor.',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'usuario_id' => $admin->id,
        ]);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar anillas y validar movimiento',
            'source' => 'ai',
            'tipo_equipo' => User::MODULE_PASTEURIZADORA,
            'area_pasteurizadora' => AnalisisPasteurizadora::AREA_MECANICA,
            'priority_level' => 'critical',
            'maintenance_type' => 'corrective',
            'detected_problem' => 'Anillas danadas.',
            'technical_justification' => 'El historial de pasteurizadora respalda el cambio.',
            'risk_if_not_executed' => 'Paro por arrastre irregular.',
            'estado' => 'approved',
            'approved_content' => $this->structuredActionPlan(),
            'source_metadata' => [
                'linea_nombre' => 'P-03',
                'component_code' => 'ANILLAS',
                'component_name' => 'Anillas (Ventanas-Cortinas)',
                'area' => AnalisisPasteurizadora::AREA_MECANICA,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'lado' => 'VAPOR',
            ],
            'confidence_level' => 0.87,
            'generated_at' => now(),
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'Usaria el contexto de pasteurizadora para responder.',
                        'key_points' => ['Hay historial de anillas en P-03.'],
                        'next_steps' => ['Validar el registro fuente.'],
                        'sources' => [
                            ['type' => 'technical_context', 'reference' => 'pasteurizadora'],
                        ],
                        'confidence' => 0.8,
                    ],
                    'raw' => ['provider' => 'fake'],
                    'meta' => [
                        'provider' => 'openai',
                        'model' => 'assistant-fake-model',
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

        $this->actingAs($admin)
            ->postJson(route('assistant-chat.store'), [
                'message' => 'Dame una recomendacion para ANILLAS de P-03 modulo 1 lado vapor',
                'page_context' => [
                    'module' => User::MODULE_PASTEURIZADORA,
                    'linea_nombre' => 'P-03',
                    'component_code' => 'ANILLAS',
                    'modulo' => 1,
                    'nivel' => 'SUPERIOR',
                    'lado' => 'VAPOR',
                ],
            ])
            ->assertOk();

        $prompt = (string) ($capturingProvider->payloads[0]['user_prompt'] ?? '');

        $this->assertStringContainsString('"technical_recommendation_context"', $prompt);
        $this->assertStringContainsString('"module":"pasteurizadora"', $prompt);
        $this->assertStringContainsString('same_component_same_pasteurizer_position', $prompt);
        $this->assertStringContainsString('ANILLAS', $prompt);
        $this->assertStringContainsString('P-03', $prompt);
        $this->assertStringContainsString('CAMBIAR ANILLAS Y VALIDAR MOVIMIENTO', $prompt);
    }

    public function test_assistant_prompt_includes_central_hidraulica_pasteurizadora_context(): void
    {
        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.provider' => 'openai',
        ]);

        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $linea = $this->pasteurizadoraLine();
        $analysis = $this->createDamagedCentralAnalysis($admin);

        PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar electrovalvulas central hidraulica',
            'source' => 'ai',
            'tipo_equipo' => User::MODULE_PASTEURIZADORA,
            'area_pasteurizadora' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'priority_level' => 'critical',
            'maintenance_type' => 'corrective',
            'detected_problem' => 'Electrovalvulas danadas.',
            'technical_justification' => 'El historial de central hidraulica respalda el cambio.',
            'risk_if_not_executed' => 'Perdida de respuesta hidraulica.',
            'estado' => 'approved',
            'approved_content' => $this->structuredActionPlan([
                'title' => 'Cambiar electrovalvulas central hidraulica',
            ]),
            'source_metadata' => [
                'linea_nombre' => 'P-03',
                'component_code' => 'ELECTROVALVULAS',
                'component_name' => 'Electrovalvulas',
                'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                'piso' => CentralHidraulicaConfiguracion::PISO_SUPERIOR,
                'piso_label' => 'Piso Superior',
                'lado' => AnalisisCentralHidraulica::LADO_1,
                'lado_label' => 'Lado 1',
            ],
            'confidence_level' => 0.86,
            'generated_at' => now(),
        ]);

        $capturingProvider = new class implements AiProviderInterface
        {
            public array $payloads = [];

            public function generateStructuredActionPlan(array $payload): array
            {
                $this->payloads[] = $payload;

                return [
                    'data' => [
                        'answer' => 'Usaria el contexto de central hidraulica para responder.',
                        'key_points' => ['Hay historial de electrovalvulas en P-03.'],
                        'next_steps' => ['Validar el registro fuente.'],
                        'sources' => [
                            ['type' => 'technical_context', 'reference' => 'pasteurizadora'],
                        ],
                        'confidence' => 0.8,
                    ],
                    'raw' => ['provider' => 'fake'],
                    'meta' => [
                        'provider' => 'openai',
                        'model' => 'assistant-fake-model',
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

        $this->actingAs($admin)
            ->postJson(route('assistant-chat.store'), [
                'message' => 'Dame una recomendacion para ELECTROVALVULAS de P-03 piso superior lado 1',
                'page_context' => [
                    'module' => User::MODULE_PASTEURIZADORA,
                    'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                    'record_id' => $analysis->id,
                    'linea_nombre' => 'P-03',
                    'component_code' => 'ELECTROVALVULAS',
                    'piso' => CentralHidraulicaConfiguracion::PISO_SUPERIOR,
                    'lado' => AnalisisCentralHidraulica::LADO_1,
                    'current_path' => '/pasteurizadora/central-hidraulica/' . $analysis->id,
                ],
            ])
            ->assertOk();

        $prompt = (string) ($capturingProvider->payloads[0]['user_prompt'] ?? '');

        $this->assertStringContainsString('"technical_recommendation_context"', $prompt);
        $this->assertStringContainsString('"module":"pasteurizadora"', $prompt);
        $this->assertStringContainsString('central_hidraulica', $prompt);
        $this->assertStringContainsString('ELECTROVALVULAS', $prompt);
        $this->assertStringContainsString('Piso Superior', $prompt);
        $this->assertStringContainsString('CAMBIAR ELECTROVALVULAS CENTRAL HIDRAULICA', $prompt);
    }

    private function createDamagedAnalysis(?User $user = null): AnalisisPasteurizadora
    {
        $linea = $this->pasteurizadoraLine();
        $user ??= $this->userWithRole(User::ROLE_ADMIN, true);

        return AnalisisPasteurizadora::create([
            'area' => AnalisisPasteurizadora::AREA_MECANICA,
            'tipo_registro' => AnalisisPasteurizadora::TIPO_REGISTRO_NORMAL,
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => '2026-08-10',
            'numero_orden' => '10000002',
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Anillas con ruido y desgaste visible.',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'usuario_id' => $user->id,
            'resuelto_por_cambio' => false,
        ]);
    }

    private function createDamagedCentralAnalysis(?User $user = null): AnalisisCentralHidraulica
    {
        $linea = $this->pasteurizadoraLine();
        $config = $this->centralConfig('P-03', CentralHidraulicaConfiguracion::PISO_SUPERIOR, 'ELECTROVALVULAS');
        $user ??= $this->userWithRole(User::ROLE_ADMIN, true);

        return AnalisisCentralHidraulica::create([
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'componente_id' => $config->componente_id,
            'piso' => CentralHidraulicaConfiguracion::PISO_SUPERIOR,
            'lado' => AnalisisCentralHidraulica::LADO_1,
            'fecha_analisis' => '2026-08-10',
            'numero_orden' => '20000002',
            'estado' => AnalisisCentralHidraulica::ESTADO_DANADO,
            'actividad' => 'Electrovalvulas con fuga y respuesta irregular.',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => $config->cantidad,
            'usuario_id' => $user->id,
            'resuelto_por_cambio' => false,
            'tipo_registro' => AnalisisCentralHidraulica::TIPO_REGISTRO_NORMAL,
        ]);
    }

    private function createEventForAnalysis(AnalisisPasteurizadora $analysis): MaintenanceEvent
    {
        return MaintenanceEvent::create([
            'linea_id' => $analysis->linea_id,
            'componente_id' => null,
            'source_type' => 'analisis_pasteurizadora',
            'source_id' => $analysis->id,
            'event_type' => 'component_damaged',
            'severity' => 'critical',
            'detected_value' => $analysis->estado,
            'limit_value' => null,
            'title' => 'Componente danado en pasteurizadora',
            'description' => 'El componente requiere cambio inmediato.',
            'context_data' => [
                'event_type' => 'component_damaged',
                'area' => $analysis->area,
                'linea_nombre' => $analysis->linea?->nombre,
                'component_code' => $analysis->componente,
                'component_name' => $analysis->componente_nombre,
                'modulo' => $analysis->modulo,
                'nivel' => $analysis->nivel,
                'lado' => $analysis->lado,
            ],
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'fingerprint' => sha1('pasteurizadora-component-damaged|' . $analysis->id),
            'detected_at' => now(),
        ]);
    }

    private function createEventForCentralAnalysis(AnalisisCentralHidraulica $analysis): MaintenanceEvent
    {
        return MaintenanceEvent::create([
            'linea_id' => $analysis->linea_id,
            'componente_id' => null,
            'source_type' => 'analisis_central_hidraulica',
            'source_id' => $analysis->id,
            'event_type' => 'component_damaged',
            'severity' => 'critical',
            'detected_value' => $analysis->estado,
            'limit_value' => null,
            'title' => 'Componente danado en central hidraulica',
            'description' => 'El componente requiere cambio inmediato.',
            'context_data' => [
                'event_type' => 'component_damaged',
                'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                'area_label' => 'Central Hidraulica',
                'linea_nombre' => $analysis->linea?->nombre,
                'component_code' => $analysis->componente?->codigo,
                'component_name' => $analysis->componente_nombre,
                'piso' => $analysis->piso,
                'piso_label' => $analysis->piso_label,
                'nivel' => $analysis->piso,
                'lado' => $analysis->lado,
                'lado_label' => $analysis->lado_label,
            ],
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'fingerprint' => sha1('pasteurizadora-central-component-damaged|' . $analysis->id),
            'detected_at' => now(),
        ]);
    }

    private function createPendingAiPlan(User $user): PlanAccion
    {
        $analysis = $this->createDamagedAnalysis($user);
        $event = $this->createEventForAnalysis($analysis);
        $structured = $this->structuredActionPlan([
            'title' => 'Cambiar anillas sugerido por IA',
        ]);

        return PlanAccion::create([
            'linea_id' => $analysis->linea_id,
            'actividad' => 'Cambiar anillas sugerido por IA',
            'source' => 'ai',
            'maintenance_event_id' => $event->id,
            'tipo_equipo' => User::MODULE_PASTEURIZADORA,
            'area_pasteurizadora' => AnalisisPasteurizadora::AREA_MECANICA,
            'priority_level' => 'high',
            'maintenance_type' => 'corrective',
            'detected_problem' => 'Desgaste critico detectado en anillas.',
            'technical_justification' => 'La inspeccion visual indica riesgo alto.',
            'risk_if_not_executed' => 'Paro de pasteurizadora',
            'missing_information' => [],
            'knowledge_sources' => $structured['knowledge_sources'],
            'original_generated_content' => $structured,
            'source_metadata' => [
                'component_code' => 'ANILLAS',
                'component_name' => 'Anillas (Ventanas-Cortinas)',
                'area' => AnalisisPasteurizadora::AREA_MECANICA,
                'modulo' => 1,
                'nivel' => 'SUPERIOR',
                'lado' => 'VAPOR',
            ],
            'estado' => 'pending_review',
            'fecha_pcm1' => '2026-08-21',
            'confidence_level' => 0.84,
            'generated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function structuredActionPlan(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Cambiar anillas y validar movimiento',
            'priority' => 'high',
            'maintenance_type' => 'corrective',
            'detected_problem' => 'Anillas con desgaste critico.',
            'technical_justification' => 'La condicion reportada requiere correccion mecanica.',
            'recommended_actions' => [
                [
                    'order' => 1,
                    'activity' => 'Cambiar anillas',
                    'technical_detail' => 'Bloquear energia y sustituir piezas afectadas.',
                ],
            ],
            'suggested_due_date' => '2026-08-21',
            'risk_if_not_executed' => 'Riesgo de paro y dano progresivo.',
            'estimated_cost' => [
                'minimum' => 0,
                'maximum' => 0,
                'currency' => 'MXN',
                'based_on_historical_data' => false,
            ],
            'knowledge_sources' => [
                [
                    'type' => 'revision',
                    'reference' => 'Analisis pasteurizadora',
                    'document_id' => null,
                    'chunk_index' => null,
                    'page' => null,
                    'section' => null,
                ],
            ],
            'confidence' => 0.84,
            'requires_human_approval' => true,
            'missing_information' => [],
        ], $overrides);
    }

    private function pasteurizadoraLine(): Linea
    {
        return Linea::firstOrCreate(
            ['nombre' => 'P-03'],
            [
                'descripcion' => 'Pasteurizadora de prueba',
                'tipo' => User::MODULE_PASTEURIZADORA,
                'activo' => true,
            ]
        );
    }

    private function centralConfig(string $pasteurizador, string $piso, string $codigo): CentralHidraulicaConfiguracion
    {
        $componenteId = CentralHidraulicaComponente::query()
            ->where('codigo', $codigo)
            ->value('id');

        return CentralHidraulicaConfiguracion::query()
            ->where('pasteurizador', $pasteurizador)
            ->where('piso', $piso)
            ->where('componente_id', $componenteId)
            ->firstOrFail();
    }

    private function userWithRole(string $role, bool $active = false): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'activo' => $active,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function grantPasteurizadoraAccess(User $user): void
    {
        foreach ([
            User::PERMISSION_ACCESS_PASTEURIZADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }
    }
}
