<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ElongacionReminderDatabaseNotification extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, array{
     *     linea: string,
     *     linea_id: int|null,
     *     due_at: CarbonInterface,
     *     last_recorded_at: CarbonInterface,
     *     days_remaining: int,
     *     status: string
     * }>  $alerts
     */
    public function __construct(
        private readonly Collection $alerts
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $lines = $this->alerts->pluck('linea')->values()->all();
        $lineLabel = count($lines) === 1 ? 'Linea pendiente' : 'Lineas pendientes';
        $message = sprintf(
            'DEBEN HACERSE NUEVOS REGISTROS DE ELONGACION. %s: %s.',
            $lineLabel,
            implode(', ', $lines)
        );

        return [
            'type' => 'elongacion_reminder',
            'title' => 'Registros de elongacion pendientes',
            'mensaje' => $message,
            'message' => $message,
            'prioridad' => 'alta',
            'url' => route('elongaciones.index'),
            'lineas' => $lines,
            'linea_ids' => $this->alerts->pluck('linea_id')->filter()->values()->all(),
            'detalles' => $this->alerts->map(static function (array $alert): array {
                return [
                    'linea' => $alert['linea'],
                    'linea_id' => $alert['linea_id'],
                    'ultimo_registro' => $alert['last_recorded_at']->format('Y-m-d H:i:s'),
                    'vence_el' => $alert['due_at']->format('Y-m-d'),
                    'dias_restantes' => $alert['days_remaining'],
                    'status' => $alert['status'],
                ];
            })->all(),
        ];
    }
}
