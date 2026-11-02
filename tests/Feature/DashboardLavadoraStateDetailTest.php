<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\AnalisisLavadora;
use App\Models\CadenaCiclo;
use App\Models\Componente;
use App\Models\Elongacion;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardLavadoraStateDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_lavadora_state_detail_groups_alerts_by_status_and_prioritizes_critical_first(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $this->crearAnalisis($linea, 'GUIA_CRIT', 'Guia critica', 'Reductor 1', 'Dañado - Requiere cambio', '2026-07-10');
        $this->crearAnalisis($linea, 'GUIA_SEV', 'Guia severa', 'Reductor 2', 'Desgaste severo', '2026-07-09');
        $this->crearAnalisis($linea, 'GUIA_MOD', 'Guia moderada', 'Reductor 3', 'Desgaste moderado', '2026-07-08');
        $this->crearAnalisis($linea, 'GUIA_REV', 'Guia revision', 'Reductor 4', 'Requiere revisión', '2026-07-07');

        $controller = app(DashboardController::class);
        $method = new ReflectionMethod($controller, 'calcularEstadoLavadora');
        $method->setAccessible(true);

        /** @var array<string, mixed> $estado */
        $estado = $method->invoke($controller, $linea->id);

        $this->assertSame('critico', $estado['nivel']);
        $this->assertSame([
            'critico' => 1,
            'severo' => 1,
            'moderado' => 1,
            'revision' => 1,
        ], $estado['conteo_alertas']);

        $this->assertCount(1, $estado['analisis_por_estado']['critico']);
        $this->assertCount(1, $estado['analisis_por_estado']['severo']);
        $this->assertCount(1, $estado['analisis_por_estado']['moderado']);
        $this->assertCount(1, $estado['analisis_por_estado']['revision']);

        $this->assertSame(
            ['critico', 'severo', 'moderado', 'revision'],
            array_column($estado['alert_carousel'], 'estado_key')
        );
        $this->assertSame(
            ['Requiere cambio', 'Daño severo', 'Daño moderado', 'Requiere revisión'],
            array_column($estado['alert_carousel'], 'estado_label')
        );
    }

    public function test_lavadora_state_uses_only_latest_analysis_for_component_status(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $componenteHistorico = Componente::create([
            'linea' => $linea->nombre,
            'nombre' => 'Guia inferior',
            'codigo' => 'L04_reductor_1_GUI_INT_TANQUE',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        $componenteVigente = Componente::create([
            'linea' => $linea->nombre,
            'nombre' => 'Guia inferior',
            'codigo' => 'GUI_INT_TANQUE_L_04',
            'reductor' => 'Reductor 1',
            'ubicacion' => 'Reductor 1',
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteHistorico->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-LAV-OLD',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Registro historico danado',
        ]);

        $vigente = AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componenteVigente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-10',
            'numero_orden' => 'OT-LAV-NEW',
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Registro vigente bueno',
        ]);

        $this->assertSame(
            [$vigente->id],
            AnalisisLavadora::ultimosPorComponente()->pluck('id')->all()
        );

        $controller = app(DashboardController::class);
        $method = new ReflectionMethod($controller, 'calcularEstadoLavadora');
        $method->setAccessible(true);

        /** @var array<string, mixed> $estado */
        $estado = $method->invoke($controller, $linea->id);

        $this->assertSame('bueno', $estado['nivel']);
        $this->assertSame([
            'critico' => 0,
            'severo' => 0,
            'moderado' => 0,
            'revision' => 0,
        ], $estado['conteo_alertas']);
        $this->assertSame(0, $estado['total_alertas_componentes']);
    }

    public function test_lavadora_state_uses_only_active_cycle_elongacion(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $cicloCerrado = $this->crearCiclo($linea, 1, false);
        $cicloActivo = $this->crearCiclo($linea, 2, true);

        $this->crearElongacion($cicloCerrado, 1.50, 1.48, '2026-06-01 08:00:00');
        $elongacionActiva = $this->crearElongacion($cicloActivo, 0.20, 0.25, '2026-07-01 08:00:00');

        $controller = app(DashboardController::class);
        $method = new ReflectionMethod($controller, 'calcularEstadoLavadora');
        $method->setAccessible(true);

        /** @var array<string, mixed> $estado */
        $estado = $method->invoke($controller, $linea->id);

        $this->assertSame('bueno', $estado['nivel']);
        $this->assertSame($elongacionActiva->id, $estado['ultima_elongacion']?->id);
    }

    public function test_summary_reuses_the_card_states_without_recalculating_them(): void
    {
        $linea = Linea::create([
            'nombre' => 'L-04', 'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora', 'activo' => true,
        ]);
        $this->crearAnalisis($linea, 'SUMMARY_CRITICAL', 'Componente critico', 'Reductor 1', AnalisisLavadora::ESTADO_DANADO, '2026-07-10');
        $controller = app(DashboardController::class);
        $lineas = collect([$linea]);
        $states = (new ReflectionMethod($controller, 'getEstadoLavadoras'))->invoke($controller, $lineas);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $summary = (new ReflectionMethod($controller, 'getResumenGeneral'))->invoke($controller, $lineas, $states);
        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(1, $summary['alertas_criticas']);
        $this->assertSame(1, $summary['total_lavadoras']);
        $this->assertSame(1, $summary['total_analisis']);
        $this->assertCount(2, $queries, 'Only aggregate counts should run after the card states have been loaded.');
    }

    public function test_batch_cards_match_individual_rules_for_mixed_lines_cycles_plans_and_ties(): void
    {
        $lines = collect(['L-04', 'L-05', 'L-06', 'L-07'])->map(fn ($name) => Linea::create(['nombre' => $name, 'activo' => $name !== 'L-07']));
        $this->crearAnalisis($lines[0], 'BATCH_DAMAGE', 'Damaged fixture', 'Reductor 1', AnalisisLavadora::ESTADO_DANADO, '2026-07-10');
        $this->crearAnalisis($lines[1], 'BATCH_REVIEW', 'Review fixture', 'Reductor 2', AnalisisLavadora::ESTADO_REQUIERE_REVISION, '2026-07-10');
        $old = $this->crearCiclo($lines[0], 1, true);
        $current = $this->crearCiclo($lines[0], 2, true);
        $this->crearElongacion($old, 1.8, 1.8, '2026-08-01 10:00:00');
        $this->crearElongacion($current, 0.2, 0.2, '2026-07-10 10:00:00');
        $latest = $this->crearElongacion($current, 0.3, 0.3, '2026-07-10 10:00:00');
        foreach ([[null, false], ['lavadora', false], ['pasteurizadora', false], ['lavadora', true]] as [$type, $done]) {
            \App\Models\PlanAccion::withoutEvents(fn () => \App\Models\PlanAccion::create([
                'linea_id' => $lines[1]->id, 'actividad' => 'Plan fixture', 'tipo_equipo' => $type, 'completado' => $done,
            ]));
        }
        $controller = app(DashboardController::class);
        $individual = new ReflectionMethod($controller, 'calcularEstadoLavadora');
        $expected = $lines->map(fn ($line) => ['id' => $line->id, 'nombre' => $line->nombre, 'estado' => $individual->invoke($controller, $line->id)])->all();
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $actual = (new ReflectionMethod($controller, 'getEstadoLavadoras'))->invoke($controller, $lines);
        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();
        $this->assertSame(json_encode($expected), json_encode($actual));
        $this->assertCount(6, $queries);
        $this->assertSame($latest->id, $actual[0]['estado']['ultima_elongacion']->id);
        $this->assertSame(2, $actual[1]['estado']['acciones_pendientes']);
        $this->assertNull($actual[2]['estado']['ultima_elongacion']);
        $this->assertSame('bueno', $actual[3]['estado']['nivel']);
    }

    private function crearAnalisis(
        Linea $linea,
        string $codigo,
        string $nombre,
        string $reductor,
        string $estado,
        string $fecha
    ): void {
        $componente = Componente::create([
            'linea' => $linea->nombre,
            'nombre' => $nombre,
            'codigo' => $codigo,
            'reductor' => $reductor,
            'ubicacion' => $reductor,
            'cantidad_total' => 1,
            'activo' => true,
        ]);

        AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => $reductor,
            'fecha_analisis' => $fecha,
            'numero_orden' => 'OT-' . $codigo,
            'estado' => $estado,
            'actividad' => 'Registro de prueba ' . $codigo,
        ]));
    }

    private function crearCiclo(Linea $linea, int $numeroCiclo, bool $activa): CadenaCiclo
    {
        return CadenaCiclo::create([
            'linea_id' => $linea->id,
            'linea' => $linea->nombre,
            'codigo' => sprintf('%s-C%03d', $linea->nombre, $numeroCiclo),
            'numero_ciclo' => $numeroCiclo,
            'proveedor' => 'Proveedor test',
            'paso_inicial' => Elongacion::getPasoInicial($linea->nombre),
            'hodometro_inicial' => 0,
            'instalada_en' => '2026-01-01 08:00:00',
            'retirada_en' => $activa ? null : '2026-06-15 08:00:00',
            'activa' => $activa,
        ]);
    }

    private function crearElongacion(CadenaCiclo $ciclo, float $bombas, float $vapor, string $createdAt): Elongacion
    {
        $pasoInicial = Elongacion::getPasoInicial($ciclo->linea);
        $elongacion = Elongacion::create([
            'linea_id' => $ciclo->linea_id,
            'linea' => $ciclo->linea,
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => $ciclo->proveedor,
            'seccion' => 'LAVADORA',
            'bombas_promedio' => $pasoInicial,
            'bombas_porcentaje' => $bombas,
            'vapor_promedio' => $pasoInicial,
            'vapor_porcentaje' => $vapor,
            'requiere_cambio' => $bombas >= Elongacion::LIMITE_CAMBIO || $vapor >= Elongacion::LIMITE_CAMBIO,
            'estado' => ($bombas >= Elongacion::LIMITE_CAMBIO || $vapor >= Elongacion::LIMITE_CAMBIO) ? 'critico' : 'normal',
            'estado_detallado' => ($bombas >= Elongacion::LIMITE_CAMBIO || $vapor >= Elongacion::LIMITE_CAMBIO) ? 'cambio' : 'normal',
            'paso_inicial' => $pasoInicial,
            'hodometro' => 0,
            'hodometro_ciclo' => 0,
        ]);

        $elongacion->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $elongacion->fresh();
    }
}
