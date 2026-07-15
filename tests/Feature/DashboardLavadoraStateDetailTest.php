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
