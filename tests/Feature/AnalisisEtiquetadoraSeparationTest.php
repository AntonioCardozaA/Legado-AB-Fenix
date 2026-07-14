<?php

namespace Tests\Feature;

use App\Models\AnalisisEtiquetadora;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Support\EtiquetadoraCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisEtiquetadoraSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_etiquetadora_analysis_is_stored_in_its_own_table(): void
    {
        [$linea, $componente] = $this->crearCatalogoEtiquetadora();

        $analisis = AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'maquina' => 'A',
            'fecha_analisis' => '2026-07-14',
            'numero_orden' => 'OT-ETQ-001',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Registro de prueba Etiquetadora',
        ]);

        $this->assertDatabaseHas('analisis_etiquetadora', [
            'id' => $analisis->id,
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'numero_orden' => 'OT-ETQ-001',
            'maquina' => 'A',
        ]);

        $this->assertDatabaseMissing('analisis_componentes', [
            'numero_orden' => 'OT-ETQ-001',
        ]);

        $this->assertFalse(
            AnalisisLavadora::withoutGlobalScopes()
                ->where('numero_orden', 'OT-ETQ-001')
                ->exists()
        );

        $this->assertTrue($linea->analisisEtiquetadora()->whereKey($analisis->id)->exists());
        $this->assertTrue($componente->analisisEtiquetadora()->whereKey($analisis->id)->exists());
    }

    public function test_latest_etiquetadora_query_does_not_read_lavadora_rows(): void
    {
        [$linea, $componente] = $this->crearCatalogoEtiquetadora();

        AnalisisLavadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-20',
            'numero_orden' => 'OT-LAV-FUTURE',
            'estado' => AnalisisLavadora::ESTADO_DANADO,
            'actividad' => 'Registro de lavadora que no debe mezclarse',
        ]);

        AnalisisEtiquetadora::create([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('B'),
            'maquina' => 'B',
            'fecha_analisis' => '2026-07-14',
            'numero_orden' => 'OT-ETQ-LATEST',
            'estado' => AnalisisEtiquetadora::ESTADO_BUENO,
            'actividad' => 'Registro vigente Etiquetadora',
        ]);

        $this->assertSame(
            ['OT-ETQ-LATEST'],
            AnalisisEtiquetadora::ultimosPorComponente()
                ->pluck('numero_orden')
                ->all()
        );
    }

    /**
     * @return array{0: Linea, 1: Componente}
     */
    private function crearCatalogoEtiquetadora(): array
    {
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba Etiquetadora',
            'activo' => true,
        ]);

        $componente = Componente::create([
            'codigo' => 'ETQ_L04_A_PRUEBA',
            'nombre' => 'Componente Etiquetadora',
            'linea' => $linea->nombre,
            'reductor' => EtiquetadoraCatalog::maquinaLabel('A'),
            'ubicacion' => 'Grupo de prueba',
            'grupo' => 'Grupo de prueba',
            'mecanismo' => 'Mecanismo de prueba',
            'cantidad_total' => 1,
            'cantidad_original' => '1*maquina',
            'tipo_equipo' => EtiquetadoraCatalog::TIPO_EQUIPO,
            'activo' => true,
        ]);

        return [$linea, $componente];
    }
}
