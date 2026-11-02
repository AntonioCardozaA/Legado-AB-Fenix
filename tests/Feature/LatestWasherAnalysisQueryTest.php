<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class LatestWasherAnalysisQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_matches_the_original_query_for_aliases_sides_dates_and_equipment_types(): void
    {
        $lineas = [];
        foreach (['L-04', 'L-05'] as $nombre) {
            $linea = Linea::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
            $lineas[] = $linea;
            foreach (['GUI_INF_TANQUE', 'GUIA_INFERIOR', 'SERVO_CHICO', 'OTRO_COMPONENTE'] as $code) {
                $componente = Componente::create([
                    'codigo' => $nombre . '_' . $code, 'nombre' => $code,
                    'linea' => $nombre, 'reductor' => 'Reductor 1', 'cantidad_total' => 1, 'activo' => true,
                ]);
                foreach ([null, '', 'VAPOR', 'PASILLO'] as $lado) {
                    foreach (['2026-07-10', '2026-07-09', '2026-07-10'] as $position => $date) {
                        AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
                            'linea_id' => $linea->id, 'componente_id' => $componente->id,
                            'reductor' => 'Reductor 1', 'lado' => $lado, 'fecha_analisis' => $date,
                            'estado' => $position === 1 ? AnalisisLavadora::ESTADO_DANADO : AnalisisLavadora::ESTADO_BUENO,
                            'actividad' => 'Equivalence fixture',
                        ]));
                    }
                    AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
                        'linea_id' => $linea->id, 'componente_id' => $componente->id,
                        'reductor' => 'Reductor 1', 'lado' => $lado, 'fecha_analisis' => '2026-12-01',
                        'tipo_equipo' => 'etiquetadora', 'estado' => AnalisisLavadora::ESTADO_DANADO,
                        'actividad' => 'Other equipment must not resolve washer history',
                    ]));
                }
            }
        }

        $original = new ReflectionMethod(AnalisisLavadora::class, 'ultimosPorComponenteCorrelacionado');
        $legacyQuery = fn () => $original->invoke(new AnalisisLavadora(), AnalisisLavadora::query());
        $this->assertSame($legacyQuery()->orderBy('id')->pluck('id')->all(), AnalisisLavadora::ultimosPorComponente()->orderBy('id')->pluck('id')->all());

        foreach ($lineas as $linea) {
            $this->assertSame(
                $legacyQuery()->where('linea_id', $linea->id)->orderBy('id')->pluck('id')->all(),
                AnalisisLavadora::ultimosPorComponente([$linea->id])->orderBy('id')->pluck('id')->all()
            );
        }
        $this->assertSame(
            $legacyQuery()->where('estado', AnalisisLavadora::ESTADO_DANADO)->pluck('id')->all(),
            AnalisisLavadora::ultimosPorComponente()->where('estado', AnalisisLavadora::ESTADO_DANADO)->pluck('id')->all()
        );
        $this->assertSame([], AnalisisLavadora::ultimosPorComponente([])->pluck('id')->all());
    }
}
