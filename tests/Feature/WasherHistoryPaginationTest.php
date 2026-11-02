<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WasherHistoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $code, array $sides): array
    {
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $this->actingAs($user);
        $line = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $component = Componente::create(['codigo' => $code, 'nombre' => 'History fixture', 'linea' => 'L-04', 'activo' => true]);
        foreach ($sides as $i => $side) {
            DB::table('analisis_componentes')->insert([
                'linea_id' => $line->id, 'componente_id' => $component->id, 'usuario_id' => $user->id,
                'reductor' => 'Reductor 1', 'lado' => $side, 'fecha_analisis' => '2026-07-10',
                'created_at' => '2026-07-10 10:00:00', 'updated_at' => '2026-07-10 10:00:00',
                'estado' => AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'Fixture ' . $i,
                'evidencia_fotos' => $i % 7 === 0 ? '["fixture.jpg"]' : '[]',
            ]);
        }

        return ['linea_id' => $line->id, 'componente_id' => $code, 'reductor' => 'Reductor 1'];
    }

    public function test_all_side_history_is_reachable_without_changing_totals_order_or_filters(): void
    {
        $params = $this->fixture('GUI_INF_TANQUE', array_merge(...array_fill(0, 55, ['VAPOR', 'PASILLO', null])));
        $seen = [];
        for ($page = 1; $page <= 3; $page++) {
            $response = $this->get(route('analisis-lavadora.historial', $params + [
                'vapor_page' => $page, 'pasillo_page' => $page, 'sin_lado_page' => $page,
            ]))->assertOk()->assertViewHas('totalRegistros', 165)->assertViewHas('conImagenes', 24);
            $sections = $response->viewData('historialSecciones');
            $this->assertSame(['VAPOR', 'PASILLO', 'SIN_LADO'], $sections->pluck('key')->all());
            foreach ($sections as $section) {
                $this->assertSame(55, $section['total']);
                $this->assertCount($page === 3 ? 5 : 25, $section['registros']);
                $ids = $section['registros']->pluck('id')->all();
                $sorted = $ids;
                rsort($sorted);
                $this->assertSame($sorted, $ids, 'Tied dates preserve descending ID order.');
                $seen[$section['key']] = array_merge($seen[$section['key']] ?? [], $ids);
                parse_str(parse_url($section['paginador']->url(2), PHP_URL_QUERY), $query);
                $this->assertSame((string) $params['linea_id'], $query['linea_id']);
                $this->assertSame('Reductor 1', $query['reductor']);
            }
        }
        foreach ($seen as $side => $ids) {
            $expected = AnalisisLavadora::orderByDesc('id')->get()->filter(fn ($item) => ($side === 'SIN_LADO' ? '' : $side) === strtoupper(trim((string) $item->lado)))->pluck('id')->all();
            $this->assertSame($expected, $ids, 'No missing or repeated history across pages.');
        }
        $this->assertDatabaseCount('analisis_componentes', 165);
    }

    public function test_legacy_side_spellings_evidence_and_general_history_keep_original_rules(): void
    {
        $params = $this->fixture('GUI_INF_TANQUE', [' vapor ', 'VAPOR', 'pasillo', null, '', '  ', 'VÁPOR']);
        DB::table('analisis_componentes')->where('id', 1)->update(['evidencia_fotos' => 'legacy.jpg']);
        DB::table('analisis_componentes')->where('id', 2)->update(['evidencia_fotos' => '"legacy-scalar.jpg"']);
        $response = $this->get(route('analisis-lavadora.historial', $params))->assertOk()
            ->assertViewHas('totalRegistros', 7)->assertViewHas('conImagenes', 2);
        $this->assertSame([2, 1, 3], $response->viewData('historialSecciones')->pluck('total')->all());
        $this->assertCount(6, $response->viewData('analisis'));

        $params = $this->fixture('SERVO_CHICO', array_fill(0, 60, null));
        $response = $this->get(route('analisis-lavadora.historial', $params))->assertOk()->assertViewHas('totalRegistros', 60);
        $this->assertCount(50, $response->viewData('analisis'));
        $first = $response->viewData('analisis')->pluck('id')->all();
        $response = $this->get(route('analisis-lavadora.historial', $params + ['page' => 2]))->assertOk();
        $second = $response->viewData('analisis')->pluck('id')->all();
        $this->assertCount(10, $second);
        $this->assertCount(60, array_unique(array_merge($first, $second)));
    }
}
