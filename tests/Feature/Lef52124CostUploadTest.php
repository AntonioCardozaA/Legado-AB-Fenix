<?php

namespace Tests\Feature;

use App\Models\Lef52124CostEntry;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class Lef52124CostUploadTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) if (is_file($path)) unlink($path);
        parent::tearDown();
    }

    private function workbook(bool $valid = true): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'legado-lef-cost-');
        $this->temporaryFiles[] = $path;
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('2025');
        $sheet->fromArray($valid ? [
            ['Orden', 'Denominacion del objeto', 'Cantidad', 'Valor', 'Fecha contable'],
            ['00012345', 'Equipo sin linea identificable', 2, 125.50, '2025-01-15'],
        ] : [['Encabezado incorrecto'], ['Sin costos']]);
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        return new UploadedFile($path, 'cost-fixture.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_existing_upload_imports_through_service_and_repeated_file_does_not_duplicate_costs(): void
    {
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $line = Linea::create(['nombre' => 'L-04', 'activo' => true]);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->actingAs($user)->post(route('lef52124.costs.import'), [
                'archivo_costos' => $this->workbook(), 'fallback_linea_id' => $line->id,
            ])->assertSessionHasNoErrors()->assertRedirect(route('lef52124.index', ['linea_id' => $line->id]));
            $this->assertDatabaseCount('lef52124_cost_entries', 1);
        }
        $entry = Lef52124CostEntry::firstOrFail();
        $this->assertSame($line->id, $entry->linea_id);
        $this->assertEquals(125.50, $entry->amount);
        $this->assertSame('00012345', $entry->order_number);
        $this->assertSame($user->id, $entry->imported_by);
        $this->getJson(route('lef52124.costs.data', ['linea_id' => $line->id]))->assertOk()
            ->assertJsonPath('summary.records', 1);
    }

    public function test_only_admin_can_use_existing_upload(): void
    {
        $technician = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $this->actingAs($technician)->post(route('lef52124.costs.import'), [
            'archivo_costos' => $this->workbook(),
        ])->assertForbidden();
        $this->assertDatabaseCount('lef52124_cost_entries', 0);
        auth()->guard('web')->logout();
        $this->post(route('lef52124.costs.import'))->assertRedirect(route('login'));
    }

    public function test_invalid_input_and_workbook_return_validation_errors_without_writes(): void
    {
        $this->actingAs(User::factory()->withRole(User::ROLE_ADMIN)->create());
        $back = route('lef52124.index');
        $this->from($back)->post(route('lef52124.costs.import'))->assertSessionHasErrors('archivo_costos');
        $this->from($back)->post(route('lef52124.costs.import'), [
            'archivo_costos' => $this->workbook(), 'fallback_linea_id' => 999999,
        ])->assertSessionHasErrors('fallback_linea_id');
        $this->from($back)->post(route('lef52124.costs.import'), [
            'archivo_costos' => $this->workbook(false),
        ])->assertRedirect($back)->assertSessionHasErrors('archivo_costos');
        $this->assertDatabaseCount('lef52124_cost_entries', 0);
    }
}
