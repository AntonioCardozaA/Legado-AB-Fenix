<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWasherHistorySeriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_counts_match_the_previous_rules_without_dropping_old_history_or_mixing_lines(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 10:00:00'));
        $lineas = collect();
        foreach (['L-04', 'L-05'] as $name) {
            $linea = Linea::firstOrCreate(['nombre' => $name], ['activo' => true]);
            $lineas->push($linea);
            $component = Componente::create(['codigo' => $name . '_HISTORY_SERIES', 'nombre' => 'History fixture', 'activo' => true]);
            foreach (['2024-02-29', '2025-10-31', '2025-11-01', '2026-06-30', '2026-07-01', '2026-10-30'] as $date) {
                AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
                    'linea_id' => $linea->id, 'componente_id' => $component->id, 'reductor' => 'Reductor 1',
                    'fecha_analisis' => $date, 'estado' => AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'History series fixture',
                ]));
            }
        }
        $controller = app(DashboardController::class);
        $history = (new \ReflectionMethod($controller, 'getAnalisisHistoricosLavadoras'))->invoke($controller, $lineas);
        $history->last()->setAttribute('fecha_analisis', null);
        $expected = ['Todas' => []];
        foreach (range(11, 0) as $offset) {
            $month = now()->startOfMonth()->subMonths($offset);
            $matchesMonth = fn ($item) => $item->fecha_analisis && $item->fecha_analisis->isSameMonth($month) && $item->fecha_analisis->year === $month->year;
            $expected['Todas'][] = $history->filter($matchesMonth)->count();
            foreach ($lineas as $linea) $expected[$linea->nombre][] = $history->filter(fn ($item) => $item->linea_id === $linea->id && $matchesMonth($item))->count();
        }
        $result = (new \ReflectionMethod($controller, 'getHistoricoRevisiones'))->invoke($controller, $lineas, $history);
        $this->assertSame($expected, $result['series']);
        $this->assertCount(12, $result['labels']);
        $this->assertSame('11/2025', $result['labels'][0]);
        $this->assertSame('10/2026', $result['labels'][11]);
        $this->assertCount(12, $history, 'The optimization must keep the complete source history.');
        $this->assertNotEmpty($result['registros']);
    }
}
