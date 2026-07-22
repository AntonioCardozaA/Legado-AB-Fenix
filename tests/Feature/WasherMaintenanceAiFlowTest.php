<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Jobs\GenerateWasherActionPlan;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeDocument;
use App\Notifications\WasherAiPlanPendingReviewNotification;
use App\Services\Maintenance\WasherActionPlanGenerator;
use App\Services\Maintenance\WasherMaintenanceOrchestrator;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WasherMaintenanceAiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_orchestrator_dispatches_a_single_job_for_the_same_analysis_detection(): void
    {
        Queue::fake();

        config([
            'maintenance_ai.enabled' => true,
        ]);

        $analysis = $this->createDamagedAnalysis();
        $orchestrator = app(WasherMaintenanceOrchestrator::class);

        $orchestrator->processAnalysis($analysis);
        $orchestrator->processAnalysis($analysis->fresh(['linea', 'componente', 'costEntries']));

        $this->assertDatabaseCount('maintenance_events', 1);
        Queue::assertPushed(GenerateWasherActionPlan::class, 1);
    }

    public function test_generator_creates_a_pending_review_plan_and_notifies_reviewers(): void
    {
        Notification::fake();

        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.provider' => 'openai',
        ]);

        $reviewer = $this->userWithRole(User::ROLE_ADMIN, true);
        $analysis = $this->createDamagedAnalysis();
        $event = MaintenanceEvent::create([
            'linea_id' => $analysis->linea_id,
            'componente_id' => $analysis->componente_id,
            'source_type' => 'analisis_lavadora',
            'source_id' => $analysis->id,
            'event_type' => 'component_damaged',
            'severity' => 'critical',
            'detected_value' => $analysis->estado,
            'limit_value' => null,
            'title' => 'Componente danado en lavadora',
            'description' => 'El componente requiere cambio inmediato.',
            'context_data' => ['event_type' => 'component_damaged'],
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'fingerprint' => sha1('component_damaged|' . $analysis->id),
            'detected_at' => now(),
        ]);

        $this->app->instance(AiProviderInterface::class, new class implements AiProviderInterface
        {
            public function generateStructuredActionPlan(array $payload): array
            {
                return [
                    'data' => [
                        'title' => 'Cambiar servo principal',
                        'priority' => 'critical',
                        'maintenance_type' => 'corrective',
                        'detected_problem' => 'Servo con dano severo y riesgo de paro.',
                        'technical_justification' => 'El componente ya excedio la condicion segura de operacion.',
                        'recommended_actions' => [
                            [
                                'order' => 1,
                                'activity' => 'Desmontar y reemplazar servo',
                                'technical_detail' => 'Aislar energia, desmontar acoplamiento y reemplazar conjunto.',
                            ],
                        ],
                        'suggested_due_date' => '2026-07-20',
                        'risk_if_not_executed' => 'Puede ocurrir paro no programado y dano colateral.',
                        'estimated_cost' => [
                            'minimum' => 1500,
                            'maximum' => 2200,
                            'currency' => 'MXN',
                            'based_on_historical_data' => true,
                        ],
                        'knowledge_sources' => [
                            [
                                'type' => 'revision',
                                'reference' => 'Analisis visual del componente',
                                'document_id' => null,
                                'page' => null,
                                'section' => null,
                            ],
                        ],
                        'confidence' => 0.88,
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

        $plan = app(WasherActionPlanGenerator::class)->generate($event);

        $this->assertSame('ai', $plan->source);
        $this->assertSame('pending_review', $plan->estado);
        $this->assertSame('Cambiar servo principal', $plan->actividad);
        $this->assertSame('corrective', $plan->maintenance_type);
        $this->assertSame(2200.0, $plan->estimated_cost_total);
        $this->assertNull($plan->estimated_hours);
        $this->assertSame($event->id, $plan->maintenance_event_id);
        $this->assertNotEmpty($plan->original_generated_content);

        Notification::assertSentTo($reviewer, WasherAiPlanPendingReviewNotification::class);
    }

    public function test_failed_job_marks_event_as_requires_information(): void
    {
        Notification::fake();

        $reviewer = $this->userWithRole(User::ROLE_ADMIN, true);
        $analysis = $this->createDamagedAnalysis();
        $event = MaintenanceEvent::create([
            'linea_id' => $analysis->linea_id,
            'componente_id' => $analysis->componente_id,
            'source_type' => 'analisis_lavadora',
            'source_id' => $analysis->id,
            'event_type' => 'component_damaged',
            'severity' => 'critical',
            'title' => 'Componente danado en lavadora',
            'description' => 'Falla en prueba',
            'context_data' => [],
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'fingerprint' => sha1('failed|' . $analysis->id),
            'detected_at' => now(),
        ]);

        $job = new GenerateWasherActionPlan($event->id);
        $job->failed(new RuntimeException('Respuesta estructurada invalida'));

        $event->refresh();
        $plan = PlanAccion::query()->where('maintenance_event_id', $event->id)->where('source', 'ai')->first();

        $this->assertSame(MaintenanceEvent::STATUS_REQUIRES_INFORMATION, $event->status);
        $this->assertSame('Respuesta estructurada invalida', $event->context_data['last_error']);
        $this->assertNotNull($plan);
        $this->assertSame('requires_information', $plan->estado);
        $this->assertSame($event->id, $plan->maintenance_event_id);
        $this->assertSame('ai', $plan->source);
        $this->assertNotEmpty($plan->original_generated_content);
        $this->assertSame('Respuesta estructurada invalida', $plan->final_observations);

        Notification::assertSentTo($reviewer, WasherAiPlanPendingReviewNotification::class);
    }

    public function test_sync_dispatch_mode_creates_failure_fallback_without_queue_worker(): void
    {
        Notification::fake();
        Queue::fake();

        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.dispatch_mode' => 'sync',
        ]);

        $reviewer = $this->userWithRole(User::ROLE_ADMIN, true);
        $analysis = $this->createDamagedAnalysis();

        $this->app->instance(AiProviderInterface::class, new class implements AiProviderInterface
        {
            public function generateStructuredActionPlan(array $payload): array
            {
                throw new RuntimeException('Proveedor IA temporalmente no disponible');
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

        try {
            app(WasherMaintenanceOrchestrator::class)->processAnalysis($analysis);
            $this->fail('Se esperaba una excepcion del proveedor IA en modo sync.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proveedor IA temporalmente no disponible', $exception->getMessage());
        }

        $event = MaintenanceEvent::query()->firstOrFail();
        $plan = PlanAccion::query()
            ->where('maintenance_event_id', $event->id)
            ->where('source', 'ai')
            ->first();

        $event->refresh();

        $this->assertNotNull($plan);
        $this->assertSame(MaintenanceEvent::STATUS_REQUIRES_INFORMATION, $event->status);
        $this->assertSame('requires_information', $plan->estado);
        $this->assertSame($event->id, $plan->maintenance_event_id);
        $this->assertStringContainsString('Proveedor IA temporalmente no disponible', (string) $plan->final_observations);

        Queue::assertNothingPushed();
        Notification::assertSentTo($reviewer, WasherAiPlanPendingReviewNotification::class);
    }

    public function test_only_reviewers_can_open_and_approve_ai_plans(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $technician = $this->userWithRole(User::ROLE_TECNICO, true);
        $responsable = User::factory()->create(['activo' => true]);
        $plan = $this->createPendingAiPlan();

        $this->actingAs($technician)
            ->get(route('plan-accion.ai.review', ['planAccion' => $plan->id]))
            ->assertForbidden();

        $response = $this->actingAs($admin)->post(route('plan-accion.ai.approve', ['planAccion' => $plan->id]), [
            'title' => 'Cambiar servo validado',
            'priority' => 'high',
            'maintenance_type' => 'corrective',
            'responsable_id' => $responsable->id,
            'suggested_due_date' => '2026-07-22',
            'detected_problem' => 'Desgaste critico confirmado por revision humana.',
            'technical_justification' => 'Se verifico riesgo de paro y holgura excesiva.',
            'risk_if_not_executed' => 'Paro de linea y dano progresivo.',
            'review_notes' => 'Se ajusto fecha y prioridad despues de revisar historico.',
            'recommended_actions' => [
                [
                    'order' => '1',
                    'activity' => 'Cambiar servo',
                    'technical_detail' => 'Aislar energia y montar servo nuevo.',
                ],
            ],
        ]);

        $response->assertRedirect(route('plan-accion.ai.review', ['planAccion' => $plan->id]));

        $plan->refresh();

        $this->assertSame('approved', $plan->estado);
        $this->assertSame($admin->id, $plan->reviewed_by);
        $this->assertSame($responsable->id, $plan->responsable_id);
        $this->assertSame('Cambiar servo validado', $plan->approved_content['title']);
        $this->assertSame('Se ajusto fecha y prioridad despues de revisar historico.', $plan->final_observations);
        $this->assertSame([
            'order' => 1,
            'activity' => 'Cambiar servo',
            'technical_detail' => 'Aislar energia y montar servo nuevo.',
        ], $plan->approved_content['recommended_actions'][0]);
        $this->assertNull($plan->estimated_hours);
        $this->assertSame(MaintenanceEvent::STATUS_PLAN_GENERATED, $plan->maintenanceEvent->fresh()->status);

        $this->actingAs($admin)
            ->get(route('plan-accion.ai.review', ['planAccion' => $plan->id]))
            ->assertOk()
            ->assertDontSee('Duracion estimada (horas)')
            ->assertDontSee('Costo estimado')
            ->assertDontSee('Fuentes usadas por la IA')
            ->assertDontSee('Informacion faltante');

        $this->actingAs($admin)
            ->getJson(route('plan-accion.show', ['plan_accion' => $plan->id]))
            ->assertOk()
            ->assertJsonPath('structured_content.detected_problem', 'Desgaste critico confirmado por revision humana.')
            ->assertJsonPath('structured_content.recommended_actions.0.activity', 'Cambiar servo')
            ->assertJsonPath('source_label', 'Generado por IA')
            ->assertJsonPath('maintenance_event.title', 'Componente danado');
    }

    public function test_store_knowledge_document_indexes_text_file_uploads(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $linea = $this->washerLine();
        $component = $this->washerComponent();

        $response = $this->actingAs($admin)->post(route('lavadora.knowledge-documents.store'), [
            'title' => 'Procedimiento de lubricacion semanal',
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'document_type' => 'procedimiento',
            'lifecycle_status' => 'vigente',
            'upload' => UploadedFile::fake()->createWithContent('procedimiento.txt', 'Lubricar cadena principal cada semana y revisar tension.'),
            'metadata_notes' => 'Documento de prueba',
        ]);

        $response->assertRedirect(route('lavadora.knowledge-documents.index'));

        $document = WasherKnowledgeDocument::firstOrFail();

        $this->assertSame('indexed', $document->indexing_status);
        $this->assertGreaterThan(0, $document->chunks()->count());
        Storage::disk('local')->assertExists($document->storage_path);
    }

    public function test_store_knowledge_document_indexes_pdf_uploads(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $linea = $this->washerLine();
        $component = $this->washerComponent();

        $response = $this->actingAs($admin)->post(route('lavadora.knowledge-documents.store'), [
            'title' => 'Manual de cambio de cadena',
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'upload' => $this->fakePdfUpload('manual-lavadora.pdf', 'Cambiar cadena principal y verificar tension del motor.'),
            'metadata_notes' => 'Documento PDF de prueba',
        ]);

        $response->assertRedirect(route('lavadora.knowledge-documents.index'));

        $document = WasherKnowledgeDocument::firstOrFail();

        $this->assertSame('indexed', $document->indexing_status);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertStringContainsString('Cambiar cadena principal', (string) $document->extracted_text);
        Storage::disk('local')->assertExists($document->storage_path);
    }

    public function test_store_knowledge_document_indexes_scanned_pdf_uploads_with_ai_ocr_fallback(): void
    {
        Storage::fake('local');

        config([
            'maintenance_ai.enabled' => true,
            'maintenance_ai.knowledge.pdf_ocr_enabled' => true,
        ]);

        $admin = $this->userWithRole(User::ROLE_ADMIN, true);
        $linea = $this->washerLine();
        $component = $this->washerComponent();
        $ocrText = 'Instruccion escaneada cambiar rodamiento y revisar torque del eje.';

        $this->app->instance(AiProviderInterface::class, new class($ocrText) implements AiProviderInterface
        {
            public function __construct(
                private readonly string $ocrText
            ) {
            }

            public function generateStructuredActionPlan(array $payload): array
            {
                throw new RuntimeException('Not used in this test.');
            }

            public function createEmbedding(string $content): array
            {
                return [];
            }

            public function extractDocumentText(array $payload): string
            {
                return $this->ocrText;
            }
        });

        $response = $this->actingAs($admin)->post(route('lavadora.knowledge-documents.store'), [
            'title' => 'Manual escaneado de rodamiento',
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'document_type' => 'manual tecnico',
            'lifecycle_status' => 'vigente',
            'upload' => $this->fakeScannedPdfUpload('manual-escaneado.pdf', $ocrText),
            'metadata_notes' => 'Documento PDF escaneado de prueba',
        ]);

        $response->assertRedirect(route('lavadora.knowledge-documents.index'));

        $document = WasherKnowledgeDocument::firstOrFail();

        $this->assertSame('indexed', $document->indexing_status);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertSame($ocrText, $document->extracted_text);
        Storage::disk('local')->assertExists($document->storage_path);
    }

    public function test_build_washer_knowledge_base_command_generates_and_indexes_pdf(): void
    {
        Storage::fake('local');

        $linea = $this->washerLine();
        $component = $this->washerComponent();

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'reductor' => 'Reductor Principal',
            'lado' => 'bombas',
            'fecha_analisis' => '2026-07-21',
            'numero_orden' => 'OT-200',
            'estado' => AnalisisLavadora::ESTADO_REQUIERE_REVISION,
            'actividad' => 'Revisar alineacion de servo y cadena.',
            'usuario_id' => $this->userWithRole(User::ROLE_ADMIN, true)->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
            'evidencia_fotos' => ['analisis-evidencias/l04-servo-01.jpg'],
        ]);

        $this->artisan('washer:knowledge-base:build')
            ->assertSuccessful();

        $document = WasherKnowledgeDocument::query()->firstOrFail();

        $this->assertSame('Base de conocimiento tecnico de lavadoras', $document->title);
        $this->assertSame('indexed', $document->indexing_status);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertStringContainsString('BASE DE CONOCIMIENTO TECNICO DE LAVADORAS', (string) $document->extracted_text);
        $this->assertStringContainsString('L-04', (string) $document->extracted_text);
        Storage::disk('local')->assertExists('washer-knowledge/base-conocimiento-tecnico-lavadoras.pdf');
    }

    private function createDamagedAnalysis(): AnalisisLavadora
    {
        $linea = $this->washerLine();
        $component = $this->washerComponent();

        return AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'reductor' => 'Reductor Principal',
            'lado' => 'bombas',
            'fecha_analisis' => '2026-07-16',
            'numero_orden' => 'OT-100',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Componente con ruido y holgura excesiva',
            'usuario_id' => $this->userWithRole(User::ROLE_ADMIN, true)->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);
    }

    private function createPendingAiPlan(): PlanAccion
    {
        $linea = $this->washerLine();
        $component = $this->washerComponent();
        $event = MaintenanceEvent::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'source_type' => 'analisis_lavadora',
            'source_id' => 1,
            'event_type' => 'component_damaged',
            'severity' => 'high',
            'title' => 'Componente danado',
            'description' => 'Desgaste critico detectado',
            'context_data' => [],
            'status' => MaintenanceEvent::STATUS_DETECTED,
            'fingerprint' => sha1('pending-plan|' . $component->id),
            'detected_at' => now(),
        ]);

        return PlanAccion::create([
            'linea_id' => $linea->id,
            'actividad' => 'Cambiar servo sugerido por IA',
            'source' => 'ai',
            'maintenance_event_id' => $event->id,
            'tipo_equipo' => User::MODULE_LAVADORA,
            'priority_level' => 'high',
            'maintenance_type' => 'corrective',
            'detected_problem' => 'Desgaste critico detectado',
            'technical_justification' => 'La inspeccion visual indica riesgo alto.',
            'risk_if_not_executed' => 'Paro de linea',
            'missing_information' => [],
            'knowledge_sources' => [
                [
                    'type' => 'manual',
                    'reference' => 'Manual del servo',
                    'document_id' => null,
                    'page' => null,
                    'section' => 'Cambio rapido',
                ],
            ],
            'original_generated_content' => [
                'title' => 'Cambiar servo sugerido por IA',
                'priority' => 'high',
                'maintenance_type' => 'corrective',
                'detected_problem' => 'Desgaste critico detectado',
                'technical_justification' => 'La inspeccion visual indica riesgo alto.',
                'recommended_actions' => [
                    [
                        'order' => 1,
                        'activity' => 'Cambiar servo',
                        'technical_detail' => 'Aislar energia y reemplazar conjunto.',
                    ],
                ],
                'suggested_due_date' => '2026-07-21',
                'risk_if_not_executed' => 'Paro de linea',
                'estimated_cost' => [
                    'minimum' => 1200,
                    'maximum' => 1800,
                    'currency' => 'MXN',
                    'based_on_historical_data' => true,
                ],
                'knowledge_sources' => [
                    [
                        'type' => 'manual',
                        'reference' => 'Manual del servo',
                        'document_id' => null,
                        'page' => null,
                        'section' => 'Cambio rapido',
                    ],
                ],
                'confidence' => 0.84,
                'requires_human_approval' => true,
                'missing_information' => [],
            ],
            'estado' => 'pending_review',
            'fecha_pcm1' => '2026-07-21',
            'confidence_level' => 0.84,
            'generated_at' => now(),
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
            ['codigo' => 'SERVO_CHICO_TEST'],
            [
                'linea' => 'L-04',
                'nombre' => 'Servo Chico Test',
                'reductor' => 'Reductor Principal',
                'ubicacion' => 'Lavadora',
                'cantidad_total' => 1,
                'activo' => true,
                'tipo_equipo' => User::MODULE_LAVADORA,
            ]
        );
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

    private function fakePdfUpload(string $filename, string $content): UploadedFile
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<html><body><p>' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</p></body></html>');
        $dompdf->render();

        return UploadedFile::fake()->createWithContent($filename, $dompdf->output());
    }

    private function fakeScannedPdfUpload(string $filename, string $content): UploadedFile
    {
        $width = 1000;
        $height = 1400;
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 40, 60, $content, $black);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        $dompdf = new Dompdf();
        $dompdf->loadHtml(
            '<html><body style="margin:0;padding:0;"><img style="width:100%;" src="data:image/png;base64,'
            . base64_encode($png)
            . '" alt="scan"></body></html>'
        );
        $dompdf->render();

        return UploadedFile::fake()->createWithContent($filename, $dompdf->output());
    }
}
