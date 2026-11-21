<?php

namespace Tests\Feature;

use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\CentralHidraulicaComponente;
use App\Models\CentralHidraulicaConfiguracion;
use App\Models\Lef52124Import;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardPasteurizadoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_dashboard_omits_quick_actions_and_counts_only_active_mechanical_failures(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $lineaActiva = $this->crearLineaPasteurizadora('P-03');
        $lineaResuelta = $this->crearLineaPasteurizadora('P-04');

        $vigente = $this->crearAnalisis($lineaActiva, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Cambio vigente de anillas',
            'numero_orden' => 'OT-ACTIVA',
        ]);

        $this->crearAnalisis($lineaResuelta, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Cambio ya resuelto',
            'numero_orden' => 'OT-RESUELTA',
            'resuelto_por_cambio' => true,
        ]);

        $this->crearAnalisis($lineaActiva, [
            'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Daño de central hidraulica',
            'numero_orden' => 'OT-CENTRAL',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.global.pasteurizadoras'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'https://cdn.jsdelivr.net/npm/chart.js'));
        $response->assertSee(route('pasteurizadora.analisis-pasteurizadora.index'), false);
        $response->assertSee(route('pasteurizadora.analisis-pasteurizadora.plan-accion.index'), false);
        $response->assertDontSee('quick-actions-grid', false);
        $response->assertDontSee(route('pasteurizadora.central-hidraulica.index'), false);
        $response->assertDontSee(route('pasteurizadora.central-hidraulica.historico-revisados'), false);
        $response->assertSee('lefDashboardWasherChart', false);
        $response->assertSee('lefDashboardParetoChart', false);
        $response->assertDontSee('30-14-7');
        $this->assertNull($response->viewData('lefDashboard')['period']);

        $fallas = collect($response->viewData('fallasPorLineaPasteurizadora'))->keyBy('linea');
        $componentes = collect($response->viewData('componentesDanadosPasteurizadora'));
        $planes = collect($response->viewData('planesPendientesPasteurizadora'));

        $this->assertSame(1, $fallas->get('P-03')['total_fallas']);
        $this->assertSame(0, $fallas->get('P-04')['total_fallas']);
        $this->assertSame(1, $componentes->sum('total_danios'));
        $this->assertTrue($planes->contains(fn (AnalisisPasteurizadora $analisis) => $analisis->is($vigente)));
        $this->assertCount(1, $planes);
    }

    public function test_global_dashboard_renders_central_hidraulica_as_independent_view(): void
    {
        $user = $this->userWithRole(User::ROLE_ADMIN);
        $linea = $this->crearLineaPasteurizadora('P-03');
        $config = $this->configuracionCentral('P-03', CentralHidraulicaConfiguracion::PISO_SUPERIOR, 'ELECTROVALVULAS');

        $this->crearAnalisis($linea, [
            'estado' => AnalisisPasteurizadora::ESTADO_DANADO,
            'actividad' => 'Dano mecanico que no debe renderizarse en la vista hidraulica',
            'numero_orden' => 'OT-MECANICA',
        ]);

        AnalisisCentralHidraulica::create([
            'linea_id' => $linea->id,
            'configuracion_id' => $config->id,
            'componente_id' => $config->componente_id,
            'piso' => CentralHidraulicaConfiguracion::PISO_SUPERIOR,
            'lado' => AnalisisCentralHidraulica::LADO_1,
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-HIDRAULICA',
            'estado' => AnalisisCentralHidraulica::ESTADO_DANADO,
            'actividad' => 'Fuga en central hidraulica',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => $config->cantidad,
            'resuelto_por_cambio' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.global.pasteurizadoras', [
            'parte' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
        ]));

        $response->assertOk();
        $response->assertViewHas('dashboardPasteurizadoraParte', AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA);
        $response->assertSee('Central Hidraulica', false);
        $response->assertSee('fallasCentralHidraulicaChart', false);
        $response->assertSee(route('pasteurizadora.central-hidraulica.index'), false);
        $response->assertDontSee('fallasPasteurizadoraChart', false);

        $fallasCentral = collect($response->viewData('fallasPorLineaCentralHidraulica'))->keyBy('linea');
        $resumenCentral = $response->viewData('resumenCentralHidraulicaPasteurizadora');
        $this->assertSame(1, $fallasCentral->get('P-03')['total_fallas']);
        $this->assertSame(1, $resumenCentral['alertas_criticas']);
        $response->assertSee('lefDashboardWasherChart', false);
        $response->assertDontSee('30-14-7');
        $this->assertNull($response->viewData('lefDashboard')['period']);
    }

    public function test_pasteurizadora_lef_excel_upload_feeds_its_module_and_dashboard(): void
    {
        Storage::fake('local');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
        $this->actingAs($this->userWithRole(User::ROLE_ADMIN));
        $pasteurizadora = $this->crearLineaPasteurizadora('P-03');
        $lavadora = Linea::create(['nombre' => 'L-04', 'activo' => true]);
        $path = tempnam(sys_get_temp_dir(), 'legado-lef-');
        $book = new Spreadsheet();

        try {
            $book->getActiveSheet()->setTitle('Máquinas')->fromArray([
                ['Máquina', '1 - 52 SEM', '2 - 12 SEM', '3 - 4 SEM'],
                ['PASTEURIZADOR DE BOTELLAS', 3.25, 1.5, 0.5],
                ['TRANSPORTADOR', 5, 2, 1],
            ]);
            $book->createSheet()->setTitle('Partes')->fromArray([
                ['Parte', '1 - 52 SEM', '2 - 12 SEM', '3 - 4 SEM'],
                ['CADENA', 2, 1, 0],
            ], null, 'A1', true);
            $book->createSheet()->setTitle('Líneas')->fromArray([
                ['Línea', '1 - 52 SEM', '2 - 12 SEM', '3 - 4 SEM'],
                ['LÍNEA COMPLETA', 7, 4, 2],
            ]);
            (new Xlsx($book))->save($path);
            $file = new UploadedFile($path, 'lef.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

            $this->post(route('lef52124.store'), [
                'linea_id' => $lavadora->id,
                'data_date' => '2026-11-10',
                'archivo' => $file,
            ])->assertSessionHasNoErrors()->assertRedirect(route('lef52124.index', ['linea_id' => $lavadora->id]));
            $this->post(route('pasteurizadora.lef52124.store'), [
                'linea_id' => $pasteurizadora->id,
                'data_date' => '2026-10-31',
                'archivo' => $file,
            ])->assertSessionHasNoErrors()->assertRedirect(route('pasteurizadora.lef52124.index', ['linea_id' => $pasteurizadora->id]));
        } finally {
            $book->disconnectWorksheets();
            unlink($path);
        }

        $import = Lef52124Import::where('linea_id', $pasteurizadora->id)->sole();
        $this->get(route('pasteurizadora.lef52124.index'))->assertOk()
            ->assertSee('LEF-52-12-4 | Pasteurizadoras')
            ->assertSee(route('pasteurizadora.lef52124.store'), false)
            ->assertSeeInOrder([
                'aria-label="Equipo para el análisis 52-12-4"',
                'href="'.route('lef52124.index').'"',
                'href="'.route('pasteurizadora.lef52124.index').'"',
                'aria-current="page"',
                'Pasteurizadoras',
            ], false)
            ->assertViewHas('imports', fn ($imports) => $imports->total() === 1 && $imports->first()->is($import));
        $this->get(route('lef52124.index'))->assertOk()->assertSeeInOrder([
            'aria-label="Equipo para el análisis 52-12-4"',
            'href="'.route('lef52124.index').'"',
            'aria-current="page"',
            'Lavadoras',
            'href="'.route('pasteurizadora.lef52124.index').'"',
        ], false);
        $this->getJson(route('pasteurizadora.lef52124.data', ['scope' => 'all']))
            ->assertOk()->assertJsonPath('machines.1.name', 'PASTEURIZADOR DE BOTELLAS');
        $this->getJson(route('pasteurizadora.lef52124.periods', ['scope' => 'all']))
            ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $import->id);
        $this->getJson(route('pasteurizadora.lef52124.trend.washer-machine', ['linea_id' => $pasteurizadora->id]))
            ->assertOk()->assertJsonPath('series.2026.9.value_52_weeks', 3.25);
        $this->getJson(route('pasteurizadora.lef52124.data', ['linea_id' => $lavadora->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('linea_id');
        $this->getJson(route('lef52124.data', ['import_id' => $import->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('import_id');

        $dashboard = $this->get(route('dashboard.global.pasteurizadoras'))->assertOk()
            ->assertSee('Periodo de análisis: Octubre 2026')
            ->assertSee(route('pasteurizadora.lef52124.index'), false)
            ->assertDontSee('30-14-7');
        $payload = $dashboard->viewData('lefDashboard');
        $this->assertSame(['P-03'], array_column($payload['lineas'], 'nombre'));
        $this->assertSame($import->id, $payload['lineas'][0]['import_id']);
        $this->assertSame(3.25, $payload['lineas'][0]['washer']['value_52_weeks']);
        $this->get(route('dashboard.global.lavadoras'))->assertOk()->assertSee('Periodo de análisis: Noviembre 2026');

        config(['excel.temporary_files.local_path' => sys_get_temp_dir().'/legado-lef-pasteur-'.bin2hex(random_bytes(5))]);
        $export = $this->get(route('pasteurizadora.lef52124.export'))->assertOk();
        $exportBook = IOFactory::load($export->baseResponse->getFile()->getPathname());
        try {
            $sheet = $exportBook->getSheetByName('Pasteurizadora 52 semanas');
            $row = collect($sheet->toArray())->first(fn ($row) => $row[0] === 'P-03' && $row[3] === '2026-10-31');
            $this->assertEquals(3.25, $row[4]);
        } finally {
            $exportBook->disconnectWorksheets();
        }
        $this->get(route('lef52124.download', $import))->assertNotFound();
        $this->get(route('pasteurizadora.lef52124.download', $import))->assertOk()->assertDownload('lef.xlsx');
        $this->delete(route('lef52124.destroy', $import))->assertNotFound();
        $this->delete(route('pasteurizadora.lef52124.destroy', $import))->assertRedirect();
        Storage::disk('local')->assertMissing($import->source_path);
        $this->assertModelMissing($import);
    }

    public function test_mechanical_cards_and_modal_include_global_component_numbers_for_every_alert_state(): void
    {
        $linea = $this->crearLineaPasteurizadora('P-06');
        $casos = [
            'critico' => [AnalisisPasteurizadora::ESTADO_DANADO, [76, 80], 'Componentes #76, #80'],
            'severo' => ['Desgaste severo', [77], 'Componente #77'],
            'moderado' => ['Desgaste moderado', [78], 'Componente #78'],
            'revision' => [AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION, [79], 'Componente #79'],
        ];
        foreach ($casos as [$estado, $numeros]) {
            $this->crearAnalisis($linea, [
                'modulo' => 16, 'estado' => $estado,
                'componentes_revisados' => $numeros, 'total_componentes' => 5,
            ]);
        }

        $response = $this->actingAs($this->userWithRole(User::ROLE_ADMIN))
            ->get(route('dashboard.global.pasteurizadoras'));
        $response->assertOk();
        $equipo = collect($response->viewData('estadoPasteurizadoras'))->firstWhere('id', $linea->id);
        foreach ($casos as $clave => [$estado, $numeros, $etiqueta]) {
            $detalle = $equipo['estado']['analisis_por_estado'][$clave][0];
            $this->assertSame($numeros, $detalle['componentes_revisados']);
            $this->assertSame($etiqueta, $detalle['componentes_revisados_label']);
            $tarjeta = collect($equipo['estado']['alert_carousel'])->firstWhere('estado_key', $clave);
            $this->assertSame($etiqueta, $tarjeta['componentes_revisados_label']);
            $response->assertSee('<div class="carousel-slide-meta font-semibold">' . $etiqueta . '</div>', false);
        }
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function crearLineaPasteurizadora(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Pasteurizadora de prueba',
            'activo' => true,
        ]);
    }

    private function crearAnalisis(Linea $linea, array $overrides = []): AnalisisPasteurizadora
    {
        return AnalisisPasteurizadora::create(array_merge([
            'area' => AnalisisPasteurizadora::AREA_MECANICA,
            'linea_id' => $linea->id,
            'modulo' => 1,
            'nivel' => 'SUPERIOR',
            'componente' => 'ANILLAS',
            'lado' => 'VAPOR',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-DASH',
            'estado' => 'Buen estado',
            'actividad' => 'Registro de dashboard',
            'componentes_revisados' => [1],
            'cantidad_componentes_revisados' => 1,
            'total_componentes' => 3,
            'usuario_id' => null,
            'resuelto_por_cambio' => false,
        ], $overrides));
    }

    private function configuracionCentral(string $pasteurizador, string $piso, string $codigo): CentralHidraulicaConfiguracion
    {
        $componente = CentralHidraulicaComponente::where('codigo', $codigo)->firstOrFail();

        return CentralHidraulicaConfiguracion::where('pasteurizador', $pasteurizador)
            ->where('piso', $piso)
            ->where('componente_id', $componente->id)
            ->firstOrFail();
    }
}
