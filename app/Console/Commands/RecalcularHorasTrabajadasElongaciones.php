<?php

namespace App\Console\Commands;

use App\Models\CadenaCiclo;
use Illuminate\Console\Command;

class RecalcularHorasTrabajadasElongaciones extends Command
{
    protected $signature = 'elongaciones:recalcular-horas-trabajadas
                            {--linea= : Filtra por línea específica}
                            {--dry-run : Solo calcula y muestra lo que cambiaría sin guardar}';

    protected $description = 'Recalcula hodometro_ciclo en las elongaciones usando la diferencia con la última lectura del mismo ciclo.';

    public function handle(): int
    {
        $linea = $this->option('linea');
        $dryRun = (bool) $this->option('dry-run');

        $query = CadenaCiclo::query()
            ->when($linea, fn ($builder, string $lineaFiltrada) => $builder->where('linea', $lineaFiltrada))
            ->orderBy('linea')
            ->orderBy('numero_ciclo');

        $totalCiclos = $query->count();
        $totalRegistros = 0;
        $totalActualizados = 0;

        $this->info('Iniciando recalculo de horas trabajadas en elongaciones...');

        foreach ($query->cursor() as $ciclo) {
            $registros = $ciclo->elongaciones()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $ultimoHodometroValido = $ciclo->hodometro_inicial !== null && (int) $ciclo->hodometro_inicial > 0
                ? (int) $ciclo->hodometro_inicial
                : null;

            foreach ($registros as $registro) {
                $totalRegistros++;

                if ($registro->hodometro === null || (int) $registro->hodometro <= 0) {
                    continue;
                }

                $nuevoValor = $this->calcularHorasTrabajadas((int) $registro->hodometro, $ultimoHodometroValido);

                if ($registro->hodometro_ciclo !== $nuevoValor) {
                    $totalActualizados++;

                    if (! $dryRun) {
                        $registro->forceFill(['hodometro_ciclo' => $nuevoValor])->saveQuietly();
                    }
                }

                $ultimoHodometroValido = (int) $registro->hodometro;
            }
        }

        $this->info(sprintf(
            'Ciclos revisados: %d | Registros revisados: %d | Cambios a aplicar: %d',
            $totalCiclos,
            $totalRegistros,
            $totalActualizados
        ));

        if ($dryRun) {
            $this->warn('Modo dry-run: no se guardaron cambios.');

            return self::SUCCESS;
        }

        $this->info('Recalculo completado.');

        return self::SUCCESS;
    }

    private function calcularHorasTrabajadas(int $hodometroActual, ?int $baseAnterior): int
    {
        if ($baseAnterior === null) {
            return max($hodometroActual, 0);
        }

        return max($hodometroActual - $baseAnterior, 0);
    }
}
