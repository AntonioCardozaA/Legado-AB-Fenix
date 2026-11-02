<?php

namespace Tests\Feature;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasteurizadoraHistoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $this->actingAs($user);
        $line = Linea::firstOrCreate(['nombre' => 'P-06'], ['activo' => true]);
        return [
            'linea_id' => $line->id, 'usuario_id' => $user->id, 'area' => 'mecanica',
            'modulo' => 2, 'componente' => 'ANILLAS', 'lado' => 'VAPOR', 'nivel' => 'SUPERIOR',
            'fecha_analisis' => '2026-07-10', 'tipo_registro' => 'normal',
            'estado' => AnalisisPasteurizadora::ESTADO_BUENO, 'actividad' => 'History fixture',
            'cantidad_componentes_revisados' => 1, 'total_componentes' => 5,
            'componentes_revisados' => '[6]', 'evidencia_fotos' => '["fixture.jpg"]',
            'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
        ];
    }

    public function test_every_group_page_retains_complete_totals_numbers_filters_and_all_records(): void
    {
        $row = $this->fixture();
        $expected = [];
        for ($i = 0; $i < 165; $i++) {
            $side = ['VAPOR', 'PASILLO', null][$i % 3];
            $id = DB::table('analisis_pasteurizadora')->insertGetId(array_replace($row, [
                'lado' => $side, 'componentes_revisados' => json_encode([6 + $i % 5]),
            ]));
            $expected[$side ?? 'SIN_LADO'][] = $id;
        }
        $params = [
            'linea_id' => $row['linea_id'], 'modulo' => 2, 'componente' => 'ANILLAS',
            'estado' => $row['estado'], 'fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-07-31', 'resueltos' => 'no',
        ];
        $pageNames = [];
        $seen = [];
        for ($page = 1; $page <= 3; $page++) {
            $pageParams = array_fill_keys($pageNames, $page);
            $response = $this->get(route('pasteurizadora.analisis-pasteurizadora.historial', $params + $pageParams))
                ->assertOk()->assertViewHas('totalAnalisis', 165)->assertSee('55 registros');
            $this->assertCount($page === 3 ? 15 : 75, $response->viewData('analisis'));
            $sections = $response->viewData('historialSecciones');
            $this->assertSame(['VAPOR', 'PASILLO', 'SIN_LADO'], $sections->pluck('key')->all());
            foreach ($sections as $section) {
                $this->assertSame(55, $section['total']);
                $this->assertCount(1, $section['grupos']);
                $group = $section['grupos']->first();
                $this->assertSame(55, $group['total_registros']);
                $this->assertSame(5, $group['total_componentes']);
                $numbers = array_values($group['todas_componentes_revisadas']);
                sort($numbers);
                $this->assertSame([6, 7, 8, 9, 10], $numbers);
                $this->assertSame('2026-07-10', $group['fecha_inicio']->toDateString());
                $this->assertSame('2026-07-10', $group['fecha_fin']->toDateString());
                $this->assertCount($page === 3 ? 5 : 25, $group['registros']);
                $seen[$section['key']] = array_merge($seen[$section['key']] ?? [], $group['registros']->pluck('id')->all());
                $pageNames[] = $group['paginador']->getPageName();
                parse_str(parse_url($group['paginador']->url(2), PHP_URL_QUERY), $query);
                foreach ($params as $key => $value) $this->assertSame((string) $value, $query[$key]);
            }
            $pageNames = array_values(array_unique($pageNames));
        }
        foreach ($expected as $side => $ids) $this->assertSame(array_reverse($ids), $seen[$side]);
        $this->assertDatabaseCount('analisis_pasteurizadora', 165);
        $this->assertSame('["fixture.jpg"]', DB::table('analisis_pasteurizadora')->value('evidencia_fotos'));
    }

    public function test_original_visibility_area_and_legacy_number_fallback_are_preserved(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 10:00:00'));
        $row = $this->fixture();
        $cases = [
            ['tipo_registro' => 'normal', 'estado' => AnalisisPasteurizadora::ESTADO_DANADO, 'visible' => true],
            ['tipo_registro' => 'quick', 'estado' => AnalisisPasteurizadora::ESTADO_DANADO, 'visible' => false],
            ['tipo_registro' => 'quick', 'estado' => 'Desgaste severo', 'visible' => false],
            ['tipo_registro' => 'quick', 'estado' => AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION, 'visible' => true],
            ['tipo_registro' => 'quick', 'estado' => AnalisisPasteurizadora::ESTADO_CAMBIADO, 'visible' => true],
            ['tipo_registro' => 'quick', 'estado' => AnalisisPasteurizadora::ESTADO_DANADO, 'fecha_analisis' => '2026-10-30', 'visible' => true],
            ['tipo_registro' => 'legacy', 'estado' => AnalisisPasteurizadora::ESTADO_DANADO, 'visible' => false],
            ['tipo_registro' => '', 'estado' => AnalisisPasteurizadora::ESTADO_BUENO, 'visible' => true],
            ['area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA, 'visible' => false],
        ];
        $expected = [];
        foreach ($cases as $case) {
            $visible = $case['visible'];
            unset($case['visible']);
            $id = DB::table('analisis_pasteurizadora')->insertGetId(array_replace($row, $case, [
                'componentes_revisados' => null, 'cantidad_componentes_revisados' => 2,
            ]));
            if ($visible) $expected[] = $id;
        }
        $response = $this->get(route('pasteurizadora.analisis-pasteurizadora.historial', ['linea_id' => $row['linea_id']]))->assertOk();
        $this->assertSame($expected, $response->viewData('analisis')->pluck('id')->sort()->values()->all());
        $group = $response->viewData('historialSecciones')->firstWhere('key', 'VAPOR')['grupos']->first();
        $numbers = array_values($group['todas_componentes_revisadas']);
        sort($numbers);
        $this->assertSame([6, 7], $numbers);
        $this->assertSame(count($expected), $group['total_registros']);
        $this->assertSame('2026-07-10', $group['fecha_inicio']->toDateString());
        $this->assertSame('2026-10-30', $group['fecha_fin']->toDateString());
    }

    public function test_empty_out_of_range_and_denied_requests_do_not_write_records(): void
    {
        $row = $this->fixture();
        $this->get(route('pasteurizadora.analisis-pasteurizadora.historial'))->assertOk()->assertViewHas('totalAnalisis', 0);
        DB::table('analisis_pasteurizadora')->insert($row);
        $first = $this->get(route('pasteurizadora.analisis-pasteurizadora.historial'))->assertOk();
        $group = $first->viewData('historialSecciones')->firstWhere('key', 'VAPOR')['grupos']->first();
        $response = $this->get(route('pasteurizadora.analisis-pasteurizadora.historial', [
            $group['paginador']->getPageName() => 99,
        ]))->assertOk()->assertViewHas('totalAnalisis', 1)->assertSee('1 registro');
        $this->assertCount(0, $response->viewData('analisis'));
        $this->actingAs(User::factory()->withRole(User::ROLE_TECNICO)->create())
            ->get(route('pasteurizadora.analisis-pasteurizadora.historial'))->assertRedirect(route('dashboard'))
            ->assertSessionHas('pasteurizadora_bloqueada');
        auth()->guard('web')->logout();
        $this->get(route('pasteurizadora.analisis-pasteurizadora.historial'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('analisis_pasteurizadora', 1);
    }
}
