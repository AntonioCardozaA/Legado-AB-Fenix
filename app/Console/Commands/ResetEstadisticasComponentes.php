<?php

namespace App\Console\Commands;

use App\Services\LavadoraRevisionPeriodicityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ResetEstadisticasComponentes extends Command
{
    protected $signature = 'componentes:reset-estadisticas
                            {--fecha= : Fecha especifica para evaluar el reset (YYYY-MM-DD)}
                            {--simular : Solo simular sin guardar cambios}';

    protected $description = 'Restablece estadisticas de componentes por ultima revision independiente de cada componente';

    public function handle(LavadoraRevisionPeriodicityService $periodicityService): int
    {
        $fechaReferencia = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->endOfDay()
            : Carbon::now()->endOfDay();

        $simular = (bool) $this->option('simular');

        $this->info('Iniciando reset independiente por componente...');
        $this->info('Fecha de referencia: ' . $fechaReferencia->format('d/m/Y H:i:s'));

        if ($simular) {
            $this->warn('Modo simulacion: no se guardaran restablecimientos.');
        }

        $stats = $periodicityService->resetPendientes($fechaReferencia, $simular);

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Total de analisis', $stats['total_analisis']],
                ['Componentes evaluados', $stats['componentes_evaluados']],
                ['Componentes vigentes', $stats['componentes_vigentes']],
                ['Componentes a restablecer', $stats['componentes_a_restablecer']],
                ['Analisis a restablecer', $stats['analisis_a_restablecer']],
                ['Analisis ya restablecidos', $stats['analisis_ya_restablecidos']],
                ['Componentes afectados', implode(', ', $stats['componentes_afectados']) ?: 'Ninguno'],
                ['Lineas afectadas', implode(', ', $stats['lineas_afectadas']) ?: 'Ninguna'],
            ]
        );

        if (!empty($stats['detalles'])) {
            $this->newLine();
            $this->info('Detalle por componente vencido:');

            $this->table(
                ['Linea', 'Componente', 'Reductor', 'Lado', 'Ultima revision', 'Vence', 'Analisis', 'Periodo'],
                collect($stats['detalles'])
                    ->take(30)
                    ->map(fn (array $detalle): array => [
                        $detalle['linea'],
                        $detalle['componente'],
                        $detalle['reductor'] ?: '-',
                        $detalle['lado'] ?: '-',
                        $detalle['ultima_revision'],
                        $detalle['proximo_vencimiento'],
                        $detalle['analisis'],
                        $detalle['periodo'],
                    ])
                    ->all()
            );

            if (count($stats['detalles']) > 30) {
                $this->info('... y ' . (count($stats['detalles']) - 30) . ' componentes mas.');
            }
        }

        if ($stats['analisis_a_restablecer'] > 0) {
            $this->info($simular
                ? 'Simulacion completada.'
                : 'Reset independiente por componente completado.');
        } else {
            $this->info('No hay componentes vencidos para restablecer.');
        }

        return Command::SUCCESS;
    }
}
