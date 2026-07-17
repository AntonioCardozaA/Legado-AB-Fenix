<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
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
}
