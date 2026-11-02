<?php

namespace Tests\Feature;

use App\Http\Controllers\AnalisisLavadoraController;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WasherIndexQueryReuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_reuse_does_not_apply_list_filters_to_statistics_or_monitor(): void
    {
        $linea = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        foreach ([AnalisisLavadora::ESTADO_BUENO, AnalisisLavadora::ESTADO_DANADO] as $i => $state) {
            $component = Componente::create(['codigo' => 'INDEX_REUSE_' . $i, 'nombre' => 'Component ' . $i, 'activo' => true]);
            AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
                'linea_id' => $linea->id, 'componente_id' => $component->id,
                'reductor' => 'Reductor 1', 'fecha_analisis' => '2026-07-10', 'estado' => $state, 'actividad' => 'Reuse fixture',
            ]));
        }
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $this->actingAs($user);
        foreach ([[], ['estado' => AnalisisLavadora::ESTADO_DANADO], ['fecha' => '2026-06']] as $filters) {
            $request = Request::create('/analisis-lavadora', 'GET', ['linea_id' => $linea->id] + $filters);
            $request->setUserResolver(fn () => $user);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $data = app(AnalisisLavadoraController::class)->index($request)->getData();
            $rankingQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'ROW_NUMBER()'));
            DB::disableQueryLog();
            $this->assertSame(2, $data['estadisticas']['total']);
            $this->assertCount(2, $data['analisisMonitorDiagrama']);
            $this->assertCount($filters === [] ? 2 : (isset($filters['estado']) ? 1 : 0), $data['analisis']);
            $this->assertCount($filters === [] ? 1 : 3, $rankingQueries);
            $this->assertFalse($data['analisisMonitorDiagrama']->first()->hasAttribute('total_historial'));
        }
    }
}
