<?php

namespace Tests\Feature;

use App\Models\CadenaCiclo;
use App\Models\CostCatalogItem;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ElongacionChainInstallationCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_chain_installation_cost_flow_covers_supported_groups_and_rollback_rules(): void
    {
        $cases = [
            'L-05' => [
                'expected_total' => 1605102.82,
                'items' => [
                    '4051563' => ['quantity' => 280.00, 'unit_cost' => 5634.03, 'total_cost' => 1577528.40],
                    '4050797' => ['quantity' => 2.00, 'unit_cost' => 3586.16, 'total_cost' => 7172.32],
                    '4050924' => ['quantity' => 2.00, 'unit_cost' => 10201.05, 'total_cost' => 20402.10],
                ],
            ],
            'L-04' => [
                'expected_total' => 1624457.60,
                'items' => [
                    '4071701' => ['quantity' => 346.00, 'unit_cost' => 4672.73, 'total_cost' => 1616764.58],
                    '4050646' => ['quantity' => 2.00, 'unit_cost' => 2500.76, 'total_cost' => 5001.52],
                    '4050647' => ['quantity' => 2.00, 'unit_cost' => 1345.75, 'total_cost' => 2691.50],
                ],
            ],
            'L-08' => [
                'expected_total' => 2258901.18,
                'items' => [
                    '4065483' => ['quantity' => 250.00, 'unit_cost' => 8971.85, 'total_cost' => 2242962.50],
                    '4039789' => ['quantity' => 2.00, 'unit_cost' => 3131.46, 'total_cost' => 6262.92],
                    '4064202' => ['quantity' => 2.00, 'unit_cost' => 4837.88, 'total_cost' => 9675.76],
                ],
            ],
        ];

        foreach ($cases as $lineaCodigo => $expected) {
            $linea = $this->crearLinea($lineaCodigo);
            $cicloActivo = $this->crearCiclo($lineaCodigo, 1, 'Proveedor anterior', [
                'linea_id' => $linea->id,
                'hodometro_inicial' => 400,
                'instalada_en' => '2026-07-10',
            ]);

            $response = $this->postElongacion($this->payloadBase($lineaCodigo, [
                'linea' => $lineaCodigo,
                'nueva_cadena' => 1,
                'proveedor' => 'Proveedor nuevo ' . $lineaCodigo,
                'hodometro_inicial' => 1250,
                'hodometro' => 1250,
                'fecha_instalacion' => '2026-07-15',
            ]));

            $response->assertRedirect(route('elongaciones.index', ['linea' => $lineaCodigo]));

            $nuevoCiclo = CadenaCiclo::query()
                ->where('linea', $lineaCodigo)
                ->where('numero_ciclo', 2)
                ->firstOrFail();

            $elongacion = Elongacion::query()
                ->where('cadena_ciclo_id', $nuevoCiclo->id)
                ->latest('id')
                ->firstOrFail();

            $entries = LavadoraCostEntry::query()
                ->where('elongacion_id', $elongacion->id)
                ->where('source_type', LavadoraCostEntry::SOURCE_CHAIN_INSTALLATION)
                ->orderBy('catalog_sku_snapshot')
                ->get();

            $this->assertSame(0, (int) $cicloActivo->fresh()->activa, "El ciclo previo de {$lineaCodigo} debe quedar inactivo.");
            $this->assertCount(3, $entries, "La linea {$lineaCodigo} debe generar tres partidas de costo.");
            $this->assertEqualsWithDelta($expected['expected_total'], (float) $entries->sum('total_cost'), 0.01, "Total inesperado para {$lineaCodigo}.");

            foreach ($expected['items'] as $sku => $itemExpected) {
                $entry = $entries->firstWhere('catalog_sku_snapshot', $sku);

                $this->assertNotNull($entry, "No se encontro la partida SKU {$sku} para {$lineaCodigo}.");
                $this->assertSame($linea->id, $entry->linea_id);
                $this->assertSame($elongacion->id, $entry->elongacion_id);
                $this->assertSame($nuevoCiclo->id, $entry->cadena_ciclo_id);
                $this->assertSame($nuevoCiclo->codigo, $entry->source_reference);
                $this->assertSame('2026-07-15', $entry->cost_date?->toDateString());
                $this->assertEqualsWithDelta($itemExpected['quantity'], (float) $entry->quantity, 0.01);
                $this->assertEqualsWithDelta($itemExpected['unit_cost'], (float) $entry->unit_cost, 0.01);
                $this->assertEqualsWithDelta($itemExpected['total_cost'], (float) $entry->total_cost, 0.01);
            }
        }

        $lineaContinuidad = $this->crearLinea('L-07');
        $cicloContinuidad = $this->crearCiclo('L-07', 1, 'Proveedor continuidad', [
            'linea_id' => $lineaContinuidad->id,
            'hodometro_inicial' => 1000,
        ]);

        $responseContinuidad = $this->postElongacion($this->payloadBase('L-07', [
            'linea' => 'L-07',
            'hodometro' => 1300,
        ]));

        $responseContinuidad->assertRedirect(route('elongaciones.index', ['linea' => 'L-07']));

        $elongacionContinuidad = Elongacion::query()
            ->where('cadena_ciclo_id', $cicloContinuidad->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($cicloContinuidad->id, $elongacionContinuidad->cadena_ciclo_id);
        $this->assertSame(0, LavadoraCostEntry::query()->where('elongacion_id', $elongacionContinuidad->id)->count());

        $lineaConFaltante = $this->crearLinea('L-09');
        $cicloConFaltante = $this->crearCiclo('L-09', 1, 'Proveedor actual', [
            'linea_id' => $lineaConFaltante->id,
            'hodometro_inicial' => 500,
        ]);

        $elongacionesAntesFaltante = Elongacion::query()->count();
        $ciclosAntesFaltante = CadenaCiclo::query()->count();
        $costosAntesFaltante = LavadoraCostEntry::query()->count();

        CostCatalogItem::query()->where('sku', '4050797')->delete();

        $responseFaltante = $this->postElongacion($this->payloadBase('L-09', [
            'linea' => 'L-09',
            'nueva_cadena' => 1,
            'proveedor' => 'Proveedor nuevo',
            'hodometro_inicial' => 1200,
            'hodometro' => 1200,
            'fecha_instalacion' => '2026-07-15',
        ]), route('elongaciones.create'));

        $responseFaltante->assertRedirect(route('elongaciones.create'));
        $responseFaltante->assertSessionHasErrors('nueva_cadena_costs');

        $this->assertSame($elongacionesAntesFaltante, Elongacion::query()->count());
        $this->assertSame($ciclosAntesFaltante, CadenaCiclo::query()->count());
        $this->assertSame($costosAntesFaltante, LavadoraCostEntry::query()->count());
        $this->assertDatabaseHas('cadena_ciclos', [
            'id' => $cicloConFaltante->id,
            'activa' => 1,
        ]);

        $lineaConPrecioInvalido = $this->crearLinea('L-06');
        $cicloConPrecioInvalido = $this->crearCiclo('L-06', 1, 'Proveedor actual', [
            'linea_id' => $lineaConPrecioInvalido->id,
            'hodometro_inicial' => 500,
        ]);

        $elongacionesAntesPrecio = Elongacion::query()->count();
        $ciclosAntesPrecio = CadenaCiclo::query()->count();
        $costosAntesPrecio = LavadoraCostEntry::query()->count();

        CostCatalogItem::query()
            ->where('sku', '4071701')
            ->update(['costo_unitario' => 0]);

        $responsePrecioInvalido = $this->postElongacion($this->payloadBase('L-06', [
            'linea' => 'L-06',
            'nueva_cadena' => 1,
            'proveedor' => 'Proveedor nuevo',
            'hodometro_inicial' => 1200,
            'hodometro' => 1200,
            'fecha_instalacion' => '2026-07-15',
        ]), route('elongaciones.create'));

        $responsePrecioInvalido->assertRedirect(route('elongaciones.create'));
        $responsePrecioInvalido->assertSessionHasErrors('nueva_cadena_costs');

        $this->assertSame($elongacionesAntesPrecio, Elongacion::query()->count());
        $this->assertSame($ciclosAntesPrecio, CadenaCiclo::query()->count());
        $this->assertSame($costosAntesPrecio, LavadoraCostEntry::query()->count());
        $this->assertDatabaseHas('cadena_ciclos', [
            'id' => $cicloConPrecioInvalido->id,
            'activa' => 1,
        ]);
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

    private function postElongacion(array $payload, ?string $from = null): TestResponse
    {
        $formToken = 'test-form-token-' . bin2hex(random_bytes(8));
        $requestBuilder = $from ? $this->from($from) : $this;

        return $requestBuilder->withSession([
            'elongaciones.create.form_token' => $formToken,
        ])->post(route('elongaciones.store'), array_merge($payload, [
            'form_token' => $formToken,
        ]));
    }
}
