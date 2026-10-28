<?php

namespace Tests\Unit;

use App\Models\AnalisisPasteurizadora;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AnalisisPasteurizadoraIndicadoresTest extends TestCase
{
    public function test_un_buen_estado_en_otra_ubicacion_no_resuelve_la_anomalia(): void
    {
        $indicadores = AnalisisPasteurizadora::getIndicadoresActivos(new Collection([
            $this->registro(1, 'Desgaste severo', 'SUPERIOR', 'VAPOR', '2026-10-01'),
            $this->registro(2, AnalisisPasteurizadora::ESTADO_BUENO, 'SUPERIOR', 'PASILLO', '2026-10-02'),
        ]), 'P-03', 'EXCENTRICOS');

        $this->assertCount(1, $indicadores);
        $this->assertSame('Desgaste severo', $indicadores[0]['estado']);
        $this->assertSame('SUPERIOR', $indicadores[0]['nivel']);
        $this->assertSame('VAPOR', $indicadores[0]['lado']);
    }

    public function test_buen_estado_en_la_misma_ubicacion_resuelve_solo_esa_anomalia(): void
    {
        $indicadores = AnalisisPasteurizadora::getIndicadoresActivos(new Collection([
            $this->registro(1, 'Desgaste moderado', 'SUPERIOR', 'VAPOR', '2026-10-01'),
            $this->registro(1, AnalisisPasteurizadora::ESTADO_BUENO, 'SUPERIOR', 'VAPOR', '2026-10-02'),
            $this->registro(2, AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION, 'INFERIOR', 'PASILLO', '2026-10-03'),
        ]), 'P-03', 'EXCENTRICOS');

        $this->assertCount(1, $indicadores);
        $this->assertSame(AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION, $indicadores[0]['estado']);
        $this->assertSame(2, $indicadores[0]['numero_componente']);
    }

    public function test_la_anomalia_mas_reciente_reemplaza_el_color_de_la_misma_ubicacion(): void
    {
        $indicadores = AnalisisPasteurizadora::getIndicadoresActivos(new Collection([
            $this->registro(1, 'Desgaste moderado', 'SUPERIOR', 'VAPOR', '2026-10-01'),
            $this->registro(1, 'Dañado - Requiere cambio', 'SUPERIOR', 'VAPOR', '2026-10-02'),
        ]), 'P-03', 'EXCENTRICOS');

        $this->assertCount(1, $indicadores);
        $this->assertSame(AnalisisPasteurizadora::ESTADO_DANADO, $indicadores[0]['estado']);
    }

    private function registro(int $numero, string $estado, string $nivel, string $lado, string $fecha): AnalisisPasteurizadora
    {
        $registro = new AnalisisPasteurizadora([
            'id' => $numero,
            'componente' => 'EXCENTRICOS',
            'modulo' => 1,
            'nivel' => $nivel,
            'lado' => $lado,
            'estado' => $estado,
            'fecha_analisis' => $fecha,
            'componentes_revisados' => [$numero],
        ]);
        $registro->created_at = Carbon::parse($fecha)->startOfDay();

        return $registro;
    }
}
