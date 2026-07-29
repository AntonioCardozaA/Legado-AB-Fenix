<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\Linea;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardLavadoraElongacionTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_elongacion_trend_uses_only_the_active_cycle_records(): void
    {
        $linea = $this->crearLinea('L-04');
        $cicloAnterior = $this->crearCiclo($linea, 1, false);
        $cicloActual = $this->crearCiclo($linea, 2, true);

        $this->crearElongacion($cicloAnterior, '2026-06-05 08:00:00', 0.65, 0.72);
        $this->crearElongacion($cicloAnterior, '2026-06-18 08:00:00', 0.81, 0.84);
        $this->crearElongacion($cicloActual, '2026-07-02 08:00:00', 0.22, 0.31);
        $this->crearElongacion($cicloActual, '2026-07-16 08:00:00', 0.41, 0.53);

        $resultado = $this->obtenerEvolucionElongaciones(collect([$linea]));
        $serie = collect($resultado['lineas'])->firstWhere('linea_id', $linea->id);

        $this->assertNotNull($serie);
        $this->assertSame(2, $serie['mediciones']);
        $this->assertSame(['02/07/2026', '16/07/2026'], $serie['labels']);
        $this->assertSame([0.22, 0.41], $serie['bombas']);
        $this->assertSame([0.31, 0.53], $serie['vapor']);
        $this->assertSame('02/07/2026', $serie['desde']);
        $this->assertSame('16/07/2026', $serie['hasta']);
    }

    public function test_dashboard_elongacion_trend_falls_back_to_the_latest_cycle_when_no_active_cycle_is_marked(): void
    {
        $linea = $this->crearLinea('L-05');
        $cicloAnterior = $this->crearCiclo($linea, 1, false);
        $cicloMasReciente = $this->crearCiclo($linea, 2, false);

        $this->crearElongacion($cicloAnterior, '2026-05-09 08:00:00', 0.95, 1.02);
        $this->crearElongacion($cicloMasReciente, '2026-07-04 08:00:00', 0.28, 0.37);
        $this->crearElongacion($cicloMasReciente, '2026-07-20 08:00:00', 0.33, 0.45);

        $resultado = $this->obtenerEvolucionElongaciones(collect([$linea]));
        $serie = collect($resultado['lineas'])->firstWhere('linea_id', $linea->id);

        $this->assertNotNull($serie);
        $this->assertSame(2, $serie['mediciones']);
        $this->assertSame(['04/07/2026', '20/07/2026'], $serie['labels']);
        $this->assertSame([0.28, 0.33], $serie['bombas']);
        $this->assertSame([0.37, 0.45], $serie['vapor']);
        $this->assertSame('04/07/2026', $serie['desde']);
        $this->assertSame('20/07/2026', $serie['hasta']);
    }

    private function obtenerEvolucionElongaciones($lineasLavadora): array
    {
        $controller = app(DashboardController::class);
        $method = new ReflectionMethod($controller, 'getEvolucionElongaciones');
        $method->setAccessible(true);

        /** @var array<string, mixed> $resultado */
        $resultado = $method->invoke($controller, $lineasLavadora);

        return $resultado;
    }

    private function crearLinea(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Lavadora de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
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
            'instalada_en' => Carbon::parse('2026-01-01 08:00:00')->addDays($numeroCiclo),
            'activa' => $activa,
        ]);
    }

    private function crearElongacion(CadenaCiclo $ciclo, string $fecha, float $bombasPorcentaje, float $vaporPorcentaje): Elongacion
    {
        $pasoInicial = Elongacion::getPasoInicial($ciclo->linea);
        $elongacion = Elongacion::create([
            'linea_id' => $ciclo->linea_id,
            'linea' => $ciclo->linea,
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => $ciclo->proveedor,
            'seccion' => 'LAVADORA',
            'bombas_promedio' => $pasoInicial,
            'bombas_porcentaje' => $bombasPorcentaje,
            'vapor_promedio' => $pasoInicial,
            'vapor_porcentaje' => $vaporPorcentaje,
            'requiere_cambio' => false,
            'estado' => 'normal',
            'estado_detallado' => 'normal',
            'paso_inicial' => $pasoInicial,
            'hodometro' => 0,
            'hodometro_ciclo' => 0,
        ]);

        $elongacion->forceFill([
            'created_at' => Carbon::parse($fecha),
            'updated_at' => Carbon::parse($fecha),
        ])->saveQuietly();

        return $elongacion;
    }
}
