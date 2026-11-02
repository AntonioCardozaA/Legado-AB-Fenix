<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Services\NotificationVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class NotificationTargetBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_batches_match_individual_checks_preserving_order_and_plan_precedence(): void
    {
        $linea = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $component = Componente::create(['codigo' => 'BATCH_SERVO', 'nombre' => 'Servo', 'activo' => true]);
        $analysis = AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
            'linea_id' => $linea->id, 'componente_id' => $component->id, 'reductor' => 'Reductor 1', 'actividad' => 'Batch fixture',
            'fecha_analisis' => '2026-07-10', 'estado' => AnalisisLavadora::ESTADO_BUENO,
        ]));
        $plan = PlanAccion::create(['linea_id' => $linea->id, 'actividad' => 'Batch fixture', 'tipo_equipo' => 'lavadora', 'completado' => false]);
        $items = collect();
        for ($i = 0; $i < 60; $i++) {
            $items->push($this->item(['record_class' => AnalisisLavadora::class, 'record_id' => $analysis->id]));
            $items->push($this->item(['analisis_pasteurizadora_id' => $analysis->id]));
            $items->push($this->item(['plan_id' => $plan->id, 'record_class' => AnalisisLavadora::class, 'record_id' => 999999]));
            $items->push($this->item(['plan_id' => 999999, 'record_class' => AnalisisLavadora::class, 'record_id' => $analysis->id]));
            $items->push($this->item(['record_type' => 'lavadora', 'analysis_id' => 999999]));
        }
        $items->push($this->item(['type' => 'elongacion_reminder', 'detalles' => []]));
        $items->push($this->item(['type' => 'elongacion_change_alert', 'elongacion_id' => null]));
        $items->push($this->item(['message' => 'Without target']));
        // Noncanonical IDs retain the database-specific behavior of the individual query.
        $items->push($this->item(['analisis_lavadora_id' => '00' . $analysis->id]));
        $items->push($this->item(['record_class' => 'UnrecognizedClass', 'record_id' => 999999]));

        $service = app(NotificationVisibilityService::class);
        $individual = new ReflectionMethod($service, 'notificationTargetExists');
        $expected = $items->filter(fn ($item) => $individual->invoke($service, $item))->values()->pluck('id')->all();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $actual = (new ReflectionMethod($service, 'filterExistingTargets'))->invoke($service, $items)->pluck('id')->all();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame($expected, $actual);
        $this->assertCount(4, $queries, 'Three model batches plus one legacy identifier check, regardless of repeated targets.');
    }

    public function test_batching_keeps_user_and_role_visibility(): void
    {
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $other = User::factory()->withRole(User::ROLE_TECNICO)->create();
        foreach ([$user, $other] as $recipient) {
            foreach (['component_alert', 'admin_analysis_deleted'] as $type) {
                $recipient->notifications()->save($this->item(['type' => $type]));
            }
        }
        $result = app(NotificationVisibilityService::class)->availableNotificationsFor($user);
        $this->assertCount(1, $result);
        $this->assertSame($user->id, $result->first()->notifiable_id);
        $this->assertSame('component_alert', $result->first()->data['type']);
    }

    private function item(array $data): DatabaseNotification
    {
        return new DatabaseNotification(['id' => (string) Str::uuid(), 'type' => 'batch-fixture', 'data' => $data]);
    }
}
