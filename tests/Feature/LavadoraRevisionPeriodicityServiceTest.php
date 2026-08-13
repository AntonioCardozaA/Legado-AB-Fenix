<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\HistorialRestablecimiento;
use App\Models\Linea;
use App\Services\LavadoraRevisionPeriodicityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LavadoraRevisionPeriodicityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_use_latest_revision_for_each_component_identity(): void
    {
        $linea = $this->crearLinea();
        $guiaIntermedia = $this->crearComponente($linea, 'GUI_INT_TANQUE', 'Reductor 1');
        $guiaSuperior = $this->crearComponente($linea, 'GUI_SUP_TANQUE', 'Reductor 1');

        $this->crearAnalisis($linea, $guiaIntermedia, 'Reductor 1', '2026-01-10', 'OT-OLD-01', 'VAPOR');
        $this->crearAnalisis($linea, $guiaIntermedia, 'Reductor 1', '2026-08-01', 'OT-NEW-01', 'VAPOR');
        $this->crearAnalisis($linea, $guiaSuperior, 'Reductor 1', '2026-01-10', 'OT-OLD-02', 'VAPOR');

        $estadisticas = app(LavadoraRevisionPeriodicityService::class)
            ->estadisticasLinea($linea, Carbon::parse('2026-08-10'));

        $this->assertSame(1, $estadisticas['GUI_INT_TANQUE']['cantidad_revisada']);
        $this->assertSame('programado', $estadisticas['GUI_INT_TANQUE']['estado_periodicidad']);
        $this->assertSame('2026-08-01', $estadisticas['GUI_INT_TANQUE']['ultima_revision']);

        $this->assertSame(0, $estadisticas['GUI_SUP_TANQUE']['cantidad_revisada']);
        $this->assertSame('pendiente', $estadisticas['GUI_SUP_TANQUE']['estado_periodicidad']);
        $this->assertSame('2026-01-10', $estadisticas['GUI_SUP_TANQUE']['ultima_revision']);
    }

    public function test_side_components_count_vapor_and_pasillo_as_independent_components(): void
    {
        $linea = $this->crearLinea('L-04');
        $catarina = $this->crearComponente($linea, 'CATARINAS', 'Reductor 1');

        $this->crearAnalisis($linea, $catarina, 'Reductor 1', '2026-07-01', 'OT-VAP-01', 'VAPOR');
        $this->crearAnalisis($linea, $catarina, 'Reductor 1', '2026-07-02', 'OT-VAP-02', 'VAPOR');
        $this->crearAnalisis($linea, $catarina, 'Reductor 1', '2026-07-03', 'OT-PAS-01', 'PASILLO');

        $estadisticas = app(LavadoraRevisionPeriodicityService::class)
            ->estadisticasLinea($linea, Carbon::parse('2026-08-10'));

        $this->assertSame(26, $estadisticas['CATARINAS']['cantidad_total']);
        $this->assertSame(2, $estadisticas['CATARINAS']['cantidad_revisada']);
        $this->assertSame(13, $estadisticas['CATARINAS']['desglose_lados']['VAPOR']['total']);
        $this->assertSame(13, $estadisticas['CATARINAS']['desglose_lados']['PASILLO']['total']);
        $this->assertSame(1, $estadisticas['CATARINAS']['desglose_lados']['VAPOR']['revisados']);
        $this->assertSame(1, $estadisticas['CATARINAS']['desglose_lados']['PASILLO']['revisados']);
    }

    public function test_reset_only_moves_components_whose_own_latest_revision_is_due(): void
    {
        $linea = $this->crearLinea();
        $guiaIntermedia = $this->crearComponente($linea, 'GUI_INT_TANQUE', 'Reductor 1');
        $guiaSuperior = $this->crearComponente($linea, 'GUI_SUP_TANQUE', 'Reductor 1');

        $oldStillCovered = $this->crearAnalisis($linea, $guiaIntermedia, 'Reductor 1', '2026-01-10', 'OT-OLD-03', 'VAPOR');
        $newStillCovered = $this->crearAnalisis($linea, $guiaIntermedia, 'Reductor 1', '2026-08-01', 'OT-NEW-03', 'VAPOR');
        $oldDue = $this->crearAnalisis($linea, $guiaSuperior, 'Reductor 1', '2026-01-10', 'OT-OLD-04', 'VAPOR');

        $this->artisan('componentes:reset-estadisticas', [
            '--fecha' => '2026-08-10',
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('historial_restablecimientos', [
            'analisis_id' => $oldStillCovered->id,
        ]);
        $this->assertDatabaseMissing('historial_restablecimientos', [
            'analisis_id' => $newStillCovered->id,
        ]);
        $this->assertDatabaseHas('historial_restablecimientos', [
            'analisis_id' => $oldDue->id,
            'motivo' => 'periodicidad_componente',
            'periodo_meses' => 4,
        ]);
        $this->assertDatabaseMissing('configuraciones', [
            'clave' => 'ultimo_reset_estadisticas',
        ]);
    }

    public function test_new_revision_counts_for_component_that_already_has_reset_history(): void
    {
        $linea = $this->crearLinea();
        $componente = $this->crearComponente($linea, 'CATARINAS', 'Reductor 9');

        $historico = $this->crearAnalisis($linea, $componente, 'Reductor 9', '2026-01-10', 'OT-HIST-01', 'VAPOR');
        HistorialRestablecimiento::create([
            'analisis_id' => $historico->id,
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 9',
            'lado' => 'VAPOR',
            'fecha_analisis_original' => '2026-01-10',
            'fecha_restablecimiento' => '2026-05-11 08:00:00',
            'motivo' => 'periodicidad_componente',
            'periodo_meses' => 4,
        ]);

        $this->crearAnalisis($linea, $componente, 'Reductor 9', '2026-08-01', 'OT-NEW-09', 'VAPOR');

        $estadisticas = app(LavadoraRevisionPeriodicityService::class)
            ->estadisticasLinea($linea, Carbon::parse('2026-08-10'));

        $this->assertSame(1, $estadisticas['CATARINAS']['cantidad_revisada']);
        $this->assertSame('programado', $estadisticas['CATARINAS']['estado_periodicidad']);
        $this->assertSame('2026-08-01', $estadisticas['CATARINAS']['ultima_revision']);
        $this->assertSame('11/05/2026 08:00:00', $estadisticas['CATARINAS']['ultimo_reset_formateado']);
    }

    private function crearLinea(string $nombre = 'L-04'): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);
    }

    private function crearComponente(Linea $linea, string $codigoBase, string $reductor): Componente
    {
        return Componente::create([
            'codigo' => str_replace('-', '', $linea->nombre) . '_' . strtolower(str_replace(' ', '_', $reductor)) . '_' . $codigoBase,
            'nombre' => $codigoBase,
            'linea' => $linea->nombre,
            'reductor' => $reductor,
            'ubicacion' => $reductor,
            'cantidad_total' => 1,
            'activo' => true,
        ]);
    }

    private function crearAnalisis(
        Linea $linea,
        Componente $componente,
        string $reductor,
        string $fecha,
        string $orden,
        ?string $lado = null
    ): AnalisisLavadora {
        return AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => $reductor,
            'lado' => $lado,
            'fecha_analisis' => $fecha,
            'numero_orden' => $orden,
            'estado' => AnalisisLavadora::ESTADO_BUENO,
            'actividad' => 'Revision de prueba',
        ]);
    }
}
