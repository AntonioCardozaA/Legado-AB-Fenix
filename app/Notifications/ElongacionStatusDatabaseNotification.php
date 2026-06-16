<?php

namespace App\Notifications;

use App\Models\Elongacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ElongacionStatusDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Elongacion $elongacion,
        private readonly string $statusType
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
        $isCritical = $this->statusType === 'cambio';
        $message = $isCritical
            ? 'LA CADENA HA SOBREPASADO LOS LIMITES DE ELONGACION.'
            : 'CONSIDERAR COMPRA DE CADENA PARA SU PROXIMO CAMBIO.';

        return [
            'type' => $isCritical ? 'elongacion_change_alert' : 'elongacion_purchase_alert',
            'title' => $isCritical ? 'Cadena fuera de limite' : 'Cadena en limite de compra',
            'mensaje' => sprintf(
                '%s Linea: %s. Ciclo: %s. Bombas: %.2f%%. Vapor: %.2f%%.',
                $message,
                $this->elongacion->linea,
                $this->elongacion->cadenaCiclo?->codigo ?? 'Sin ciclo',
                (float) $this->elongacion->bombas_porcentaje,
                (float) $this->elongacion->vapor_porcentaje
            ),
            'message' => sprintf(
                '%s Linea: %s. Ciclo: %s. Bombas: %.2f%%. Vapor: %.2f%%.',
                $message,
                $this->elongacion->linea,
                $this->elongacion->cadenaCiclo?->codigo ?? 'Sin ciclo',
                (float) $this->elongacion->bombas_porcentaje,
                (float) $this->elongacion->vapor_porcentaje
            ),
            'prioridad' => $isCritical ? 'alta' : 'media',
            'linea' => $this->elongacion->linea,
            'linea_id' => $this->elongacion->linea_id,
            'cadena_ciclo_id' => $this->elongacion->cadena_ciclo_id,
            'elongacion_id' => $this->elongacion->id,
            'url' => route('elongaciones.show', $this->elongacion),
        ];
    }
}
