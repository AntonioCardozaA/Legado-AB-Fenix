<?php

namespace Tests\Feature;

use App\Models\Lef52124CostEntry;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Lef52124CostImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_cost_endpoint_preserves_global_and_line_filtered_totals(): void
    {
        $this->actingAs(User::factory()->withRole(User::ROLE_ADMIN)->create());

        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        Lef52124CostEntry::create($this->costAttributes($linea->id, 100, 'assigned'));
        Lef52124CostEntry::create($this->costAttributes(null, 900, 'unassigned'));

        $global = $this->getJson(route('lef52124.costs.data'))->assertOk()->json();
        $this->assertEquals(1000.0, $global['summary']['2025']);
        $this->assertEquals(0.0, $global['summary']['2026']);
        $this->assertSame(2, $global['summary']['records']);
        $this->assertCount(12, $global['monthly']);
        $this->assertEquals(1000.0, $global['monthly'][0]['2025']);
        $this->assertSame(['Sin linea asignada', 'L-04'], array_column($global['by_line'], 'linea'));

        $payload = $this->getJson(route('lef52124.costs.data', ['linea_id' => $linea->id]))->assertOk()->json();
        $this->assertSame('L-04', $payload['linea']);
        $this->assertEquals(100.0, $payload['summary']['2025']);
        $this->assertSame(1, $payload['summary']['records']);
        $this->assertCount(1, $payload['by_line']);
        $this->assertSame('L-04', $payload['by_line'][0]['linea']);
        $this->assertEquals(100.0, $payload['by_line'][0]['2025']);
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
