<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Carbon\Carbon;

class FechaProximaNotification extends Notification
{
    use Queueable;

    protected $plan;
    protected $pcm;
    protected $diasRestantes;

    public function __construct($plan, $pcm, $diasRestantes)
    {
        $this->plan = $plan;
        $this->pcm = $pcm;
        $this->diasRestantes = $diasRestantes;
    }

    public function via($notifiable)
    {
        // Los canales se determinan en el servicio según preferencias
        // Pero aquí podemos devolver los canales por defecto
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $fecha = $this->getFechaPcm();
        $estado = $this->getEstadoNotificacion();

        return (new MailMessage)
            ->subject($estado['asunto'])
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line($estado['mensaje'])
            ->line('**Actividad:** ' . $this->plan->actividad)
            ->line('**Línea:** ' . ($this->plan->linea->nombre_completo ?? 'No asignada'))
            ->line('**PCM:** ' . strtoupper($this->pcm))
            ->line('**Fecha límite:** ' . $fecha->format('d/m/Y'))
            ->line('**Días restantes:** ' . abs($this->diasRestantes))
            ->action('Ver actividad', url('/plan-accion/' . $this->plan->id . '?tipo=lavadora'))
            ->line('Por favor, toma las acciones necesarias.')
            ->salutation('Saludos, Sistema de Gestión');
    }

    public function toDatabase($notifiable)
    {
        $fecha = $this->getFechaPcm();
        $estado = $this->getEstadoNotificacion();

        return [
            'plan_id' => $this->plan->id,
            'actividad' => $this->plan->actividad,
            'linea' => $this->plan->linea->nombre_completo ?? 'No asignada',
            'linea_id' => $this->plan->linea_id,
            'pcm' => $this->pcm,
            'fecha_limite' => $fecha->format('Y-m-d'),
            'dias_restantes' => abs($this->diasRestantes),
            'tipo' => $estado['tipo'],
            'mensaje' => $estado['mensaje_corto'],
            'url' => url('/plan-accion/' . $this->plan->id . '?tipo=lavadora'),
            'prioridad' => $this->getPrioridad()
        ];
    }

    protected function getFechaPcm()
    {
        switch ($this->pcm) {
            case 'pcm1':
                return $this->plan->fecha_pcm1;
            case 'pcm2':
                return $this->plan->fecha_pcm2;
            case 'pcm3':
                return $this->plan->fecha_pcm3;
            case 'pcm4':
                return $this->plan->fecha_pcm4;
            default:
                return Carbon::now();
        }
    }

    protected function getEstadoNotificacion()
    {
        if ($this->diasRestantes < 0) {
            return [
                'asunto' => '⚠️ ACTIVIDAD VENCIDA - Plan de Acción',
                'mensaje' => 'La siguiente actividad se encuentra VENCIDA:',
                'mensaje_corto' => "Actividad VENCIDA: {$this->plan->actividad}",
                'tipo' => 'vencida'
            ];
        } elseif ($this->diasRestantes == 0) {
            return [
                'asunto' => '🔴 ACTIVIDAD PARA HOY - Plan de Acción',
                'mensaje' => 'La siguiente actividad debe realizarse HOY:',
                'mensaje_corto' => "Actividad para HOY: {$this->plan->actividad}",
                'tipo' => 'hoy'
            ];
        } elseif ($this->diasRestantes <= 3) {
            return [
                'asunto' => '🟡 ACTIVIDAD PRÓXIMA - Plan de Acción',
                'mensaje' => "La siguiente actividad vence en {$this->diasRestantes} días:",
                'mensaje_corto' => "Actividad próxima ({$this->diasRestantes} días): {$this->plan->actividad}",
                'tipo' => 'proxima'
            ];
        } else {
            return [
                'asunto' => '🔔 RECORDATORIO - Plan de Acción',
                'mensaje' => "Recordatorio: Actividad programada para dentro de {$this->diasRestantes} días:",
                'mensaje_corto' => "Recordatorio ({$this->diasRestantes} días): {$this->plan->actividad}",
                'tipo' => 'recordatorio'
            ];
        }
    }

    protected function getPrioridad()
    {
        if ($this->diasRestantes < 0) return 'alta';
        if ($this->diasRestantes <= 1) return 'alta';
        if ($this->diasRestantes <= 3) return 'media';
        return 'baja';
    }
}