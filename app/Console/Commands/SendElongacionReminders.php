<?php

namespace App\Console\Commands;

use App\Services\ElongacionReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendElongacionReminders extends Command
{
    protected $signature = 'elongaciones:send-reminders
                            {--date= : Fecha/hora de referencia para simular el calculo}
                            {--dry-run : Muestra el resultado sin enviar mensajes}';

    protected $description = 'Envia recordatorios de elongacion por WhatsApp para lineas proximas a vencer.';

    public function __construct(
        private readonly ElongacionReminderService $reminderService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Evaluando recordatorios de elongacion...');

        try {
            $referenceTime = $this->resolveReferenceTime();
            $dryRun = (bool) $this->option('dry-run');

            if ($referenceTime) {
                $this->line('Fecha de referencia: ' . $referenceTime->format('Y-m-d H:i:s'));
            }

            if ($dryRun) {
                $this->warn('Modo simulacion activo: no se enviaran mensajes.');
            }

            $results = $this->reminderService->sendPendingAlerts($referenceTime, $dryRun);
            $internalResults = $this->reminderService->sendInternalNotifications($referenceTime, $dryRun);

            if (!empty($results['alerts'])) {
                $this->line('Lineas pendientes:');

                foreach ($results['alerts'] as $alert) {
                    $this->line(sprintf(
                        '- %s | ultimo: %s | vence: %s | estado: %s',
                        $alert['linea'],
                        CarbonImmutable::parse($alert['last_recorded_at'])->format('d/m/Y H:i'),
                        CarbonImmutable::parse($alert['due_at'])->format('d/m/Y'),
                        $this->formatRemainingTime((int) $alert['days_remaining'])
                    ));
                }
            }

            if (!empty($results['recipient_targets'])) {
                $this->line('Destinatarios alcanzados:');

                foreach ($results['recipient_targets'] as $target) {
                    $this->line(sprintf(
                        '- %s | lineas: %s',
                        $target['recipient'],
                        implode(', ', $target['lines'])
                    ));
                }
            }

            $this->line(sprintf(
                'WhatsApp -> Pendientes: %d | Destinatarios: %d | Simulados: %d | Enviados: %d | Omitidos: %d | Fallidos: %d',
                $results['pending_lines'],
                $results['recipients'],
                $results['simulated'],
                $results['sent'],
                $results['skipped'],
                count($results['failed'])
            ));

            $this->line(sprintf(
                'Internas -> Pendientes: %d | Destinatarios: %d | Simulados: %d | Enviadas: %d | Omitidas: %d | Fallidas: %d',
                $internalResults['pending_lines'],
                $internalResults['recipients'],
                $internalResults['simulated'],
                $internalResults['sent'],
                $internalResults['skipped'],
                count($internalResults['failed'])
            ));

            if ($dryRun) {
                return self::SUCCESS;
            }

            if (!empty($results['failed']) || !empty($internalResults['failed'])) {
                Log::warning('El comando de recordatorios de elongacion termino con fallos.', [
                    'whatsapp' => $results,
                    'database' => $internalResults,
                ]);
                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('Error no controlado al enviar recordatorios de elongacion.', [
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

    private function formatRemainingTime(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining > 1 => "faltan {$daysRemaining} dias",
            $daysRemaining === 1 => 'falta 1 dia',
            $daysRemaining === 0 => 'vence hoy',
            $daysRemaining === -1 => 'vencida por 1 dia',
            default => 'vencida por ' . abs($daysRemaining) . ' dias',
        };
    }
}
