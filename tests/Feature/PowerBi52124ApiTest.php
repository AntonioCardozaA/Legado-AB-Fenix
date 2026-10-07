<?php

namespace Tests\Feature;

use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PowerBi52124ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.powerbi.api_key' => 'test-powerbi-key']);
    }

    public function test_without_api_key_returns_unauthorized(): void
    {
        $this->getJson('/api/powerbi/52-12-4')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'No autorizado']);
    }

    public function test_wrong_api_key_returns_unauthorized(): void
    {
        $this->withHeader('X-API-KEY', 'wrong-key')
            ->getJson('/api/powerbi/52-12-4')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'No autorizado']);
    }

    public function test_valid_api_key_returns_json_structure_with_numeric_values(): void
    {
        $linea = $this->createImportWithItems('L-04', '2026-09-30');

        $response = $this->withHeader('X-API-KEY', 'test-powerbi-key')
            ->getJson('/api/powerbi/52-12-4');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'lineas' => [
                        [
                            'linea_id' => $linea->id,
                            'linea' => 'L-04',
                        ],
                    ],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'generated_at',
                'filters',
                'data' => [
                    'lineas',
                    'importaciones',
                    'maquinas',
                    'partes',
                    'linea_general',
                    'comparativo_lineas',
                    'tendencia_lavadora',
                    'historico',
                ],
            ]);

        $payload = $response->json();

        $this->assertIsFloat($payload['data']['maquinas'][0]['valor_52']);
        $this->assertIsFloat($payload['data']['maquinas'][0]['valor_12']);
        $this->assertIsFloat($payload['data']['maquinas'][0]['valor_4']);
        $this->assertSame('maquina', $payload['data']['maquinas'][0]['tipo']);
        $this->assertSame('2026-09-30', $payload['data']['maquinas'][0]['fecha']);
    }

    public function test_filters_by_line_and_date(): void
    {
        $linea04 = $this->createImportWithItems('L-04', '2026-09-30');
        $this->createImportWithItems('L-05', '2026-09-30');
        $this->createImportWithItems('L-04', '2026-08-31');

        $response = $this->withHeader('X-API-KEY', 'test-powerbi-key')
            ->getJson("/api/powerbi/52-12-4?linea_id={$linea04->id}&fecha=2026-09-30");

        $response->assertOk();

        $payload = $response->json('data.historico');

        $this->assertNotEmpty($payload);
        $this->assertTrue(collect($payload)->every(
            fn (array $row) => $row['linea_id'] === $linea04->id && $row['fecha'] === '2026-09-30'
        ));
    }

    public function test_invalid_filters_return_validation_error(): void
    {
        $this->withHeader('X-API-KEY', 'test-powerbi-key')
            ->getJson('/api/powerbi/52-12-4?period=99')
            ->assertUnprocessable();
    }

    private function createImportWithItems(string $lineaNombre, string $date): Linea
    {
        $linea = Linea::firstOrCreate(
            ['nombre' => $lineaNombre],
            [
                'descripcion' => 'Linea de prueba',
                'tipo' => 'lavadora',
                'activo' => true,
            ]
        );

        $import = Lef52124Import::create([
            'linea_id' => $linea->id,
            'data_date' => $date,
            'source_filename' => "{$lineaNombre}-{$date}.xlsx",
            'status' => 'success',
            'machines_count' => 1,
            'parts_count' => 1,
            'line_items_count' => 1,
        ]);

        Lef52124Item::create([
            'lef52124_import_id' => $import->id,
            'linea_id' => $linea->id,
            'type' => Lef52124Item::TYPE_MACHINE,
            'item_name' => 'Lavadora',
            'value_52_weeks' => 12.45,
            'value_12_weeks' => 8.32,
            'value_4_weeks' => 5.21,
        ]);

        Lef52124Item::create([
            'lef52124_import_id' => $import->id,
            'linea_id' => $linea->id,
            'type' => Lef52124Item::TYPE_PART,
            'item_name' => 'Guia superior',
            'value_52_weeks' => 6.25,
            'value_12_weeks' => 4.50,
            'value_4_weeks' => 2.10,
        ]);

        Lef52124Item::create([
            'lef52124_import_id' => $import->id,
            'linea_id' => $linea->id,
            'type' => Lef52124Item::TYPE_LINE,
            'item_name' => 'Linea general',
            'value_52_weeks' => 3.33,
            'value_12_weeks' => 2.22,
            'value_4_weeks' => 1.11,
        ]);

        return $linea;
    }
}
