<?php

namespace App\Console\Commands;

use App\Services\ElongacionStatusNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendElongacionStatusNotifications extends Command
{
    protected $signature = 'elongaciones:notify-status
                            {--linea= : Filtra una linea especifica, por ejemplo L-04}
                            {--dry-run : Muestra el resultado sin crear notificaciones}';

    protected $description = 'Genera notificaciones internas para las ultimas elongaciones en limite de compra o cambio.';

    public function __construct(
        private readonly ElongacionStatusNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Evaluando alertas internas de elongacion por compra/cambio...');

        try {
            $linea = $this->option('linea');
            $dryRun = (bool) $this->option('dry-run');

            if ($linea) {
                $this->line('Filtro por linea: ' . $linea);
            }

            if ($dryRun) {
                $this->warn('Modo simulacion activo: no se crearan notificaciones.');
            }

            $results = $this->notificationService->notifyForLatestRecords($dryRun, $linea ?: null);

            if (!empty($results['records'])) {
                $this->line('Registros detectados:');

                foreach ($results['records'] as $record) {
                    $this->line(sprintf(
                        '- %s | registro #%d | estado: %s | bombas: %.2f%% | vapor: %.2f%%',
                        $record['linea'],
                        $record['elongacion_id'],
                        $record['status_type'],
                        $record['bombas'],
                        $record['vapor']
                    ));
                }
            }

            $this->line(sprintf(
                'Registros: %d | Destinatarios: %d | Simuladas: %d | Enviadas: %d | Omitidas: %d | Fallidas: %d',
                $results['affected_records'],
                $results['recipients'],
                $results['simulated'],
                $results['sent'],
                $results['skipped'],
                $results['failed']
            ));

            if ($dryRun) {
                return self::SUCCESS;
            }

            if ($results['failed'] > 0) {
                Log::warning('El comando de alertas de elongacion termino con fallos.', $results);

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('Error no controlado al generar alertas de elongacion por compra/cambio.', [
                'error' => $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
