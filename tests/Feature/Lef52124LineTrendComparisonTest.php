<?php

namespace Tests\Feature;

use App\Exports\Lef52124LineTrendsExport;
use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Lef52124LineTrendComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_trend_cards_use_washer_machine_and_monthly_chart_uses_line_total(): void
    {
        $this->withoutMiddleware();

        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $this->createImport($linea, '2025-01-31', washerValue: 10, lineValue: 100);
        $this->createImport($linea, '2026-01-31', washerValue: 8, lineValue: 120);

        $response = $this->getJson(route('lef52124.trend.lines', ['period' => '4']));

        $response->assertOk()
            ->assertJsonPath('has_data', true)
            ->assertJsonPath('summary.better', 1)
            ->assertJsonPath('summary.worse', 0);

        $payload = $response->json();

        $this->assertSame(['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], $payload['months']);
        $this->assertCount(12, $payload['lines'][0]['series']['2025']);
        $this->assertCount(12, $payload['line_totals'][0]['series']['2025']);
        $this->assertEquals(10.0, $payload['lines'][0]['series']['2025'][0]['value']);
        $this->assertEquals(8.0, $payload['lines'][0]['series']['2026'][0]['value']);
        $this->assertNull($payload['lines'][0]['series']['2025'][11]['value']);
        $this->assertEquals(100.0, $payload['line_totals'][0]['series']['2025'][0]['value']);
        $this->assertEquals(120.0, $payload['line_totals'][0]['series']['2026'][0]['value']);
        $this->assertNull($payload['line_totals'][0]['series']['2025'][11]['value']);
    }

    public function test_only_admin_can_export_filtered_line_trends_to_excel(): void
    {
        $this->withoutMiddleware();

        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $this->createImport($linea, '2025-01-31', washerValue: 10, lineValue: 100);
        $this->createImport($linea, '2026-01-31', washerValue: 8, lineValue: 120);

        $payload = $this->getJson(route('lef52124.trend.lines', ['period' => '4']))->json();
        $washerRows = (new Lef52124LineTrendsExport($payload))->sheets()[0]->array();

        $washer2025 = collect($washerRows)->first(fn (array $row) => ($row[0] ?? null) === 'L-04' && ($row[1] ?? null) === 2025);
        $washer2026 = collect($washerRows)->first(fn (array $row) => ($row[0] ?? null) === 'L-04' && ($row[1] ?? null) === 2026);

        $this->assertSame('10.00', $washer2025[2]);
        $this->assertSame('N/A', $washer2025[13]);
        $this->assertSame('8.00', $washer2026[2]);

        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $this->actingAs($admin)
            ->get(route('lef52124.trend.lines.export-excel', [
                'period' => '12',
                'search' => 'L-04',
                'status' => 'better',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=tendencias-lineas-2025-vs-2026-4-semanas.xlsx');

        $technician = $this->userWithRole(User::ROLE_TECNICO);
        $this->actingAs($technician)
            ->get(route('lef52124.trend.lines.export-excel'))
            ->assertForbidden();
    }

    private function createImport(Linea $linea, string $date, float $washerValue, float $lineValue): void
    {
        $import = Lef52124Import::create([
            'linea_id' => $linea->id,
            'data_date' => $date,
            'source_filename' => 'lef-test.xlsx',
            'status' => 'success',
            'machines_count' => 2,
            'parts_count' => 0,
            'line_items_count' => 1,
        ]);

        Lef52124Item::create([
            'lef52124_import_id' => $import->id,
            'linea_id' => $linea->id,
            'type' => Lef52124Item::TYPE_MACHINE,
            'item_name' => 'LAVADORA DE BOTELLA',
            'value_52_weeks' => $washerValue,
            'value_12_weeks' => $washerValue,
            'value_4_weeks' => $washerValue,
        ]);

        Lef52124Item::create([
            'lef52124_import_id' => $import->id,
            'linea_id' => $linea->id,
            'type' => Lef52124Item::TYPE_MACHINE,
            'item_name' => 'LLENADORA',
            'value_52_weeks' => 999,
            'value_12_weeks' => 999,
            'value_4_weeks' => 999,
        ]);

        Lef52124Item::create([
            'lef52124_import_id' => $import->id,
            'linea_id' => $linea->id,
            'type' => Lef52124Item::TYPE_LINE,
            'item_name' => 'LINEA COMPLETA',
            'value_52_weeks' => $lineValue,
            'value_12_weeks' => $lineValue,
            'value_4_weeks' => $lineValue,
        ]);
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole($role);

        return $user;
    }
}
