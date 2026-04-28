<?php

namespace Tests\Feature;

use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElongacionCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_without_filters_shows_only_latest_record_per_line(): void
    {
        $this->crearLinea('L-04');
        $this->crearLinea('L-05');

        $cicloL04 = $this->crearCiclo('L-04', 1, 'Proveedor A');
        $cicloL05 = $this->crearCiclo('L-05', 1, 'Proveedor B');

        $primeroL04 = $this->crearElongacion($cicloL04, [
            'linea' => 'L-04',
            'hodometro' => 100,
            'created_at' => now()->subDays(2),
        ]);
        $ultimoL04 = $this->crearElongacion($cicloL04, [
            'linea' => 'L-04',
            'hodometro' => 200,
            'created_at' => now()->subDay(),
        ]);
        $ultimoL05 = $this->crearElongacion($cicloL05, [
            'linea' => 'L-05',
            'hodometro' => 300,
            'created_at' => now(),
        ]);

        $response = $this->get(route('elongaciones.index'));

        $response->assertOk();

        $paginator = $response->viewData('elongaciones');
        $ids = $paginator->getCollection()->pluck('id');

        $this->assertCount(2, $ids);
        $this->assertFalse($ids->contains($primeroL04->id));
        $this->assertTrue($ids->contains($ultimoL04->id));
        $this->assertTrue($ids->contains($ultimoL05->id));
    }

    public function test_store_creates_new_cycle_and_closes_previous_active_cycle(): void
    {
        $linea = $this->crearLinea('L-04');
        $cicloActivo = $this->crearCiclo('L-04', 1, 'Proveedor anterior', [
            'linea_id' => $linea->id,
            'hodometro_inicial' => 500,
            'instalada_en' => now()->subDays(10),
        ]);

        $response = $this->post(route('elongaciones.store'), $this->payloadBase('L-04', [
            'linea' => 'L-04',
            'nueva_cadena' => 1,
            'proveedor' => 'Proveedor nuevo',
            'hodometro_inicial' => 1200,
            'hodometro' => 1200,
            'fecha_instalacion' => now()->toDateString(),
        ]));

        $response->assertRedirect(route('elongaciones.index', ['linea' => 'L-04']));

        $this->assertDatabaseHas('cadena_ciclos', [
            'linea' => 'L-04',
            'numero_ciclo' => 2,
            'proveedor' => 'Proveedor nuevo',
            'activa' => 1,
        ]);

        $this->assertDatabaseHas('cadena_ciclos', [
            'id' => $cicloActivo->id,
            'activa' => 0,
        ]);

        $nuevoCiclo = CadenaCiclo::where('linea', 'L-04')->where('numero_ciclo', 2)->firstOrFail();
        $elongacion = Elongacion::latest('id')->firstOrFail();

        $this->assertSame($nuevoCiclo->id, $elongacion->cadena_ciclo_id);
        $this->assertSame('Proveedor nuevo', $elongacion->proveedor);
        $this->assertSame(0, $elongacion->hodometro_ciclo);
    }

    public function test_store_without_new_cycle_uses_active_cycle(): void
    {
        $linea = $this->crearLinea('L-05');
        $cicloActivo = $this->crearCiclo('L-05', 1, 'Proveedor continuidad', [
            'linea_id' => $linea->id,
            'hodometro_inicial' => 1000,
        ]);

        $response = $this->post(route('elongaciones.store'), $this->payloadBase('L-05', [
            'linea' => 'L-05',
            'hodometro' => 1300,
        ]));

        $response->assertRedirect(route('elongaciones.index', ['linea' => 'L-05']));

        $elongacion = Elongacion::latest('id')->firstOrFail();

        $this->assertSame($cicloActivo->id, $elongacion->cadena_ciclo_id);
        $this->assertSame('Proveedor continuidad', $elongacion->proveedor);
        $this->assertSame(300, $elongacion->hodometro_ciclo);
    }

    private function crearLinea(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Linea de prueba',
            'activo' => true,
        ]);
    }

    private function crearCiclo(string $linea, int $numero, string $proveedor, array $overrides = []): CadenaCiclo
    {
        return CadenaCiclo::create(array_merge([
            'linea' => $linea,
            'codigo' => sprintf('%s-C%03d', $linea, $numero),
            'numero_ciclo' => $numero,
            'proveedor' => $proveedor,
            'paso_inicial' => Elongacion::getPasoInicial($linea),
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subDays(5),
            'activa' => true,
        ], $overrides));
    }

    private function crearElongacion(CadenaCiclo $ciclo, array $overrides = []): Elongacion
    {
        $pasoInicial = Elongacion::getPasoInicial($ciclo->linea);
        $timestamps = array_intersect_key($overrides, array_flip(['created_at', 'updated_at']));
        $data = array_diff_key($overrides, $timestamps);

        $elongacion = Elongacion::create(array_merge([
            'linea' => $ciclo->linea,
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => $ciclo->proveedor,
            'seccion' => 'LAVADORA',
            'bombas_promedio' => $pasoInicial,
            'bombas_porcentaje' => 0,
            'vapor_promedio' => $pasoInicial,
            'vapor_porcentaje' => 0,
            'requiere_cambio' => false,
            'estado' => 'normal',
            'estado_detallado' => 'normal',
            'paso_inicial' => $pasoInicial,
            'hodometro' => 0,
            'hodometro_ciclo' => 0,
        ], $data));

        if ($timestamps) {
            $elongacion->forceFill($timestamps)->saveQuietly();
        }

        return $elongacion;
    }

    private function payloadBase(string $linea = 'L-04', array $overrides = []): array
    {
        $pasoInicial = Elongacion::getPasoInicial($linea);

        $payload = [
            'linea' => $linea,
            'hodometro' => 0,
            'juego_rodaja_bombas' => 0.10,
            'juego_rodaja_vapor' => 0.20,
        ];

        for ($i = 1; $i <= 10; $i++) {
            $payload["bombas_{$i}"] = $pasoInicial;
            $payload["vapor_{$i}"] = $pasoInicial;
        }

        return array_merge($payload, $overrides);
    }
}
