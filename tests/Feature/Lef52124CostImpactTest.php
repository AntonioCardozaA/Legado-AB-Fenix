<?php

namespace Tests\Feature;

use App\Models\Lef52124CostEntry;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Lef52124CostImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_impact_uses_only_assigned_excel_cost_rows(): void
    {
        $this->withoutMiddleware();

        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        Lef52124CostEntry::create($this->costAttributes($linea->id, 100, 'assigned'));
        Lef52124CostEntry::create($this->costAttributes(null, 900, 'unassigned'));

        $payload = $this->getJson(route('lef52124.impact'))->assertOk()->json();

        $this->assertEquals(100.0, $payload['costs']['kpis']['total_2025']);
        $this->assertSame(1, $payload['costs']['source_summary']['excel_imports']);
        $this->assertArrayNotHasKey('cost_module', $payload['costs']['source_summary']);
        $this->assertSame('L-04', $payload['costs']['by_line'][0]['linea']);
        $this->assertEquals(100.0, $payload['costs']['by_line'][0]['cost_2025']);
    }

    private function costAttributes(?int $lineaId, float $amount, string $key): array
    {
        return [
            'linea_id' => $lineaId,
            'year' => 2025,
            'month' => 1,
            'accounting_date' => '2025-01-15',
            'order_number' => 'ORD-'.$key,
            'object_description' => 'Costo lavadora',
            'quantity' => 1,
            'unit' => 'PZA',
            'amount' => $amount,
            'source_filename' => 'Libro1.xlsx',
            'source_sheet' => '2025',
            'source_row' => $key === 'assigned' ? 2 : 3,
            'sync_key' => 'sync-'.$key,
        ];
    }
}
