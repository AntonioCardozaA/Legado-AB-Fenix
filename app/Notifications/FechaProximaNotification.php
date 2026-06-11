<?php

namespace App\Notifications;

use App\Models\PlanAccion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FechaProximaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PlanAccion $plan,
        protected string $pcm,
        protected int $diasRestantes
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->notificationSettings;

        if ($settings && $settings->email_notifications) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $fecha = $this->getFechaPcm();
        $estado = $this->getEstadoNotificacion();

        return (new MailMessage)
            ->subject($estado['asunto'])
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line($estado['mensaje'])
            ->line('**Actividad:** ' . $this->plan->actividad)
            ->line('**Linea:** ' . $this->resolveLineaNombre())
            ->line('**PCM:** ' . strtoupper($this->pcm))
            ->line('**Fecha limite:** ' . $fecha->format('d/m/Y'))
            ->line('**Dias restantes:** ' . max($this->diasRestantes, 0))
            ->action('Ver plan', $this->resolveUrl())
            ->line('Por favor, toma las acciones necesarias.')
            ->salutation('Saludos, Sistema de Gestion');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $fecha = $this->getFechaPcm();
        $estado = $this->getEstadoNotificacion();
        $lineaNombre = $this->resolveLineaNombre();
        $message = sprintf(
            'PLANES DE ACCION POR VENCER: El plan "%s" de la %s vence el %s.',
            $this->plan->actividad,
            $lineaNombre,
            $fecha->format('d/m/Y')
        );

        return [
            'type' => 'plan_accion_due',
            'plan_id' => $this->plan->id,
            'actividad' => $this->plan->actividad,
            'linea' => $lineaNombre,
            'linea_id' => $this->plan->linea_id,
            'pcm' => strtoupper($this->pcm),
            'fecha_limite' => $fecha->format('Y-m-d'),
            'dias_restantes' => max($this->diasRestantes, 0),
            'tipo' => $estado['tipo'],
            'title' => 'Plan de accion por vencer',
            'mensaje' => $message,
            'message' => $message,
            'url' => $this->resolveUrl(),
            'prioridad' => $this->getPrioridad(),
        ];
    }

    protected function getFechaPcm()
    {
        return match ($this->pcm) {
            'pcm1' => $this->plan->fecha_pcm1,
            'pcm2' => $this->plan->fecha_pcm2,
            'pcm3' => $this->plan->fecha_pcm3,
            'pcm4' => $this->plan->fecha_pcm4,
            default => now(),
        };
    }

    /**
     * @return array{asunto: string, mensaje: string, mensaje_corto: string, tipo: string}
     */
    protected function getEstadoNotificacion(): array
    {
        if ($this->diasRestantes < 0) {
            return [
                'asunto' => 'ACTIVIDAD VENCIDA - Plan de Accion',
                'mensaje' => 'La siguiente actividad se encuentra vencida:',
                'mensaje_corto' => "Actividad vencida: {$this->plan->actividad}",
                'tipo' => 'vencida',
            ];
        }

        if ($this->diasRestantes === 0) {
            return [
                'asunto' => 'ACTIVIDAD PARA HOY - Plan de Accion',
                'mensaje' => 'La siguiente actividad debe realizarse hoy:',
                'mensaje_corto' => "Actividad para hoy: {$this->plan->actividad}",
                'tipo' => 'hoy',
            ];
        }

        if ($this->diasRestantes <= 3) {
            return [
                'asunto' => 'ACTIVIDAD PROXIMA - Plan de Accion',
                'mensaje' => "La siguiente actividad vence en {$this->diasRestantes} dias:",
                'mensaje_corto' => "Actividad proxima ({$this->diasRestantes} dias): {$this->plan->actividad}",
                'tipo' => 'proxima',
            ];
        }

        return [
            'asunto' => 'RECORDATORIO - Plan de Accion',
            'mensaje' => "Recordatorio: actividad programada para dentro de {$this->diasRestantes} dias:",
            'mensaje_corto' => "Recordatorio ({$this->diasRestantes} dias): {$this->plan->actividad}",
            'tipo' => 'recordatorio',
        ];
    }

    protected function getPrioridad(): string
    {
        if ($this->diasRestantes <= 1) {
            return 'alta';
        }

        if ($this->diasRestantes <= 3) {
            return 'media';
        }

        return 'baja';
    }

    protected function resolveLineaNombre(): string
    {
        return $this->plan->linea?->nombre ?? 'Linea sin asignar';
    }

    protected function resolveUrl(): string
    {
        return route('plan-accion.edit', [
            'plan_accion' => $this->plan->id,
            'tipo' => $this->plan->tipo_equipo,
        ]);
    }
}
