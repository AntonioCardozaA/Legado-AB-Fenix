<?php

namespace Tests\Unit;

use App\Models\AnalisisLavadora;
use App\Models\CadenaCiclo;
use App\Models\Componente;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Models\User;
use App\Services\Maintenance\WasherMaintenanceRuleEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WasherMaintenanceRuleEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_analysis_rules_detect_damage_and_high_cost(): void
    {
        config([
            'maintenance_ai.rules.high_cost_threshold' => 1000,
        ]);

        $linea = $this->washerLine();
        $component = $this->washerComponent();

        $analysis = AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $component->id,
            'reductor' => 'Reductor Principal',
            'lado' => 'bombas',
            'fecha_analisis' => '2026-07-16',
            'numero_orden' => 'OT-220',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Dano confirmado',
            'tipo_equipo' => User::MODULE_LAVADORA,
        ]);

        LavadoraCostEntry::create([
            'linea_id' => $linea->id,
            'analisis_lavadora_id' => $analysis->id,
            'componente_id' => $component->id,
            'source_type' => 'manual',
            'source_reference' => 'PRUEBA',
            'cost_date' => '2026-07-16',
            'quantity' => 1,
            'unit_cost' => 1200,
            'total_cost' => 1200,
            'component_snapshot' => $component->nombre,
            'catalog_name_snapshot' => 'Servo principal',
            'catalog_sku_snapshot' => 'SERVO-001',
            'catalog_category_snapshot' => 'Refaccion',
            'unidad_medida_snapshot' => 'Pieza',
            'sync_key' => 'analysis-rule-test-1',
        ]);

        $events = app(WasherMaintenanceRuleEngine::class)->forAnalysis($analysis->fresh(['costEntries']));
        $types = $events->pluck('event_type')->all();

        $this->assertContains('component_damaged', $types);
        $this->assertContains('high_component_cost', $types);
    }

    public function test_elongacion_rules_detect_warning_revision_trend_and_rodaja(): void
    {
        Carbon::setTestNow('2026-07-16 09:00:00');

        config([
            'maintenance_ai.rules.elongacion_warning_threshold' => 1.30,
            'maintenance_ai.rules.elongacion_critical_threshold' => 1.46,
            'maintenance_ai.rules.elongacion_trend_min_delta' => 0.05,
            'maintenance_ai.rules.rodaja_max_mm' => 2.00,
            'elongacion-alerts.interval_months' => 2,
            'elongacion-alerts.lead_days' => 3,
            'elongacion-alerts.timezone' => 'America/Mexico_City',
        ]);

        $linea = $this->washerLine();
        $ciclo = CadenaCiclo::create([
            'linea_id' => $linea->id,
            'linea' => $linea->nombre,
            'codigo' => 'L-04-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor test',
            'paso_inicial' => 173,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subMonths(4),
            'activa' => true,
        ]);

        $this->createElongacionRecord($linea, $ciclo, '2026-05-01 08:00:00', 1.20, 1.10, 1.50);
        $this->createElongacionRecord($linea, $ciclo, '2026-05-05 08:00:00', 1.35, 1.28, 1.90);
        $current = $this->createElongacionRecord($linea, $ciclo, '2026-05-10 08:00:00', 1.40, 1.32, 2.30);

        $events = app(WasherMaintenanceRuleEngine::class)->forElongacion($current);
        $types = $events->pluck('event_type')->all();

        $this->assertContains('elongation_near_limit', $types);
        $this->assertContains('elongation_revision_due', $types);
        $this->assertContains('elongation_ascending_trend', $types);
        $this->assertContains('rodaja_out_of_tolerance', $types);
    }

    private function createElongacionRecord(Linea $linea, CadenaCiclo $ciclo, string $createdAt, float $bombas, float $vapor, float $rodaja): Elongacion
    {
        $elongacion = Elongacion::create([
            'linea_id' => $linea->id,
            'linea' => $linea->nombre,
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => 'Proveedor test',
            'seccion' => 'LAVADORA',
            'bombas_promedio' => 173,
            'bombas_porcentaje' => $bombas,
            'vapor_promedio' => 173,
            'vapor_porcentaje' => $vapor,
            'requiere_cambio' => false,
            'estado' => 'alerta',
            'estado_detallado' => 'comprar',
            'paso_inicial' => 173,
            'hodometro' => 100,
            'hodometro_ciclo' => 50,
            'juego_rodaja_bombas' => $rodaja,
            'juego_rodaja_vapor' => $rodaja,
        ]);

        $elongacion->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $elongacion->fresh();
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
            ['codigo' => 'SERVO_RULE_TEST'],
            [
                'linea' => 'L-04',
                'nombre' => 'Servo Rule Test',
                'reductor' => 'Reductor Principal',
                'ubicacion' => 'Lavadora',
                'cantidad_total' => 1,
                'activo' => true,
                'tipo_equipo' => User::MODULE_LAVADORA,
            ]
        );
    }
}
