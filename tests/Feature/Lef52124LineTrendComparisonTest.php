<?php

namespace Tests\Feature;

use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Lef52124LineTrendComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_washer_trend_keeps_both_years_all_windows_and_missing_months(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_ADMIN));

        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $this->createImport($linea, '2025-01-31', washerValue: 10, lineValue: 100);
        $this->createImport($linea, '2026-01-31', washerValue: 8, lineValue: 120);

        $response = $this->getJson(route('lef52124.trend.washer-machine', ['linea_id' => $linea->id]));

        $response->assertOk()
            ->assertJsonPath('has_data', true)
            ->assertJsonPath('linea', 'L-04')
            ->assertJsonPath('machine_name', 'LAVADORA DE BOTELLA');

        $payload = $response->json();

        $this->assertSame(['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], $payload['months']);
        $this->assertSame([2025, 2026], $payload['years']);
        foreach ([2025 => 10.0, 2026 => 8.0] as $year => $value) {
            $this->assertCount(12, $payload['series'][$year]);
            foreach (['value_52_weeks', 'value_12_weeks', 'value_4_weeks'] as $window) {
                $this->assertEquals($value, $payload['series'][$year][0][$window]);
                $this->assertNull($payload['series'][$year][11][$window]);
            }
        }
        $this->getJson(route('lef52124.trend.washer-machine'))->assertUnprocessable();
        $this->getJson(route('lef52124.trend.washer-machine', ['linea_id' => 999999]))->assertUnprocessable();
    }

    public function test_current_export_downloads_all_lines_workbook_and_requires_authenticated_access(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-31 12:00:00'));
        config(['excel.temporary_files.local_path' => sys_get_temp_dir() . '/legado-lef-current-export-' . bin2hex(random_bytes(5))]);

        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
        $this->createImport($linea, '2025-01-31', washerValue: 10, lineValue: 100);
        $this->createImport($linea, '2026-01-31', washerValue: 8, lineValue: 120);
        $otherLine = Linea::create(['nombre' => 'L-05', 'activo' => true]);
        $this->createImport($otherLine, '2025-01-31', washerValue: 5, lineValue: 500);
        $this->createImport($linea, '2024-12-31', washerValue: 99, lineValue: 999);
        $this->createImport($linea, '2026-11-30', washerValue: 77, lineValue: 777);

        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $response = $this->actingAs($admin)
            ->get(route('lef52124.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=52-12-4-todas-las-lineas-2025-a-2026-10-31.xlsx');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $this->assertSame(['Lavadora 52 semanas', 'Lavadora 12 semanas', 'Lavadora 4 semanas', 'Todas las maquinas'], $workbook->getSheetNames());
            foreach (array_slice($workbook->getAllSheets(), 0, 3) as $sheet) {
                $rows = collect($sheet->toArray());
                $row2025 = $rows->first(fn ($row) => $row[0] === 'L-04' && (int) $row[1] === 2025 && $row[2] === 'Enero');
                $row2026 = $rows->first(fn ($row) => $row[0] === 'L-04' && (int) $row[1] === 2026 && $row[2] === 'Enero');
                $other = $rows->first(fn ($row) => $row[0] === 'L-05' && (int) $row[1] === 2025 && $row[2] === 'Enero');
                $this->assertEquals(10.0, $row2025[4]);
                $this->assertEquals(8.0, $row2026[4]);
                $this->assertEquals(5.0, $other[4]);
                $this->assertFalse($rows->contains(fn ($row) => in_array($row[3] ?? null, ['2024-12-31', '2026-11-30'], true)));
                $missing = $rows->first(fn ($row) => $row[0] === 'L-04' && (int) $row[1] === 2025 && $row[2] === 'Febrero');
                $this->assertNull($missing[4]);
            }
        } finally {
            $workbook->disconnectWorksheets();
        }

        auth()->guard('web')->logout();
        $this->get(route('lef52124.export'))->assertRedirect(route('login'));
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
