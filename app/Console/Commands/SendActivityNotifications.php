<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendActivityNotifications extends Command
{
    protected $signature = 'notifications:send-activities
                            {--date= : Fecha/hora de referencia para simular el calculo}
                            {--dry-run : Muestra el resultado sin crear notificaciones}';

    protected $description = 'Genera notificaciones internas para planes de accion proximos a vencer.';

    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Evaluando planes de accion proximos a vencer...');

        try {
            $referenceTime = $this->resolveReferenceTime();
            $dryRun = (bool) $this->option('dry-run');

            if ($referenceTime) {
                $this->line('Fecha de referencia: ' . $referenceTime->format('Y-m-d H:i:s'));
            }

            if ($dryRun) {
                $this->warn('Modo simulacion activo: no se crearan notificaciones.');
            }

            $results = $this->notificationService
                ->verificarYNotificarActividadesProximas($referenceTime, $dryRun);

            if (!empty($results['alerts'])) {
                $this->line('Planes detectados:');

                foreach ($results['alerts'] as $alert) {
                    $this->line(sprintf(
                        '- Plan %d | %s | %s | %s | vence %s | faltan %d dias',
                        $alert['plan_id'],
                        $alert['linea'],
                        $alert['pcm'],
                        $alert['actividad'] ?? 'Sin actividad',
                        CarbonImmutable::parse($alert['fecha_limite'])->format('d/m/Y'),
                        $alert['dias_restantes']
                    ));
                }
            }

            $this->line(sprintf(
                'Planes evaluados: %d | Destinatarios: %d | Simuladas: %d | Enviadas: %d | Omitidas: %d | Errores: %d',
                $results['plans_evaluated'],
                $results['recipients'],
                $results['simulated'],
                $results['sent'],
                $results['skipped'],
                count($results['errores'])
            ));

            if ($dryRun) {
                return self::SUCCESS;
            }

            if (!empty($results['errores'])) {
                Log::warning('El comando de planes de accion termino con errores.', $results);

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('Error no controlado al generar notificaciones de planes de accion.', [
                'error' => $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveReferenceTime(): ?CarbonImmutable
    {
        $dateOption = $this->option('date');

        if (!$dateOption) {
            return null;
        }

        return CarbonImmutable::parse(
            (string) $dateOption,
            config('elongacion-alerts.timezone', 'America/Mexico_City')
        );
    }
}
