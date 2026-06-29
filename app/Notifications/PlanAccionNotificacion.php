<?php

namespace App\Notifications;

use App\Models\PlanAccion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PlanAccionNotificacion extends Notification implements ShouldQueue
{
    use Queueable;

    protected PlanAccion $plan;
    protected string $tipo;
    protected string $pcm;
    protected int $diasRestantes;

    public function __construct(PlanAccion $plan, $tipo, $pcm, $diasRestantes)
    {
        $this->plan = $plan;
        $this->tipo = (string) $tipo;
        $this->pcm = (string) $pcm;
        $this->diasRestantes = (int) $diasRestantes;
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->notificationSettings;

        if ($settings) {
            if ($settings->email_notifications) {
                $channels[] = 'mail';
            }

            if ($settings->sms_notifications && $settings->phone_number) {
                $channels[] = TwilioChannel::class;
            }
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $areaPasteurizadora = $this->getAreaPasteurizadoraLabel();

        $message = (new MailMessage)
            ->subject("Alerta: {$this->tipo} - {$this->plan->actividad}")
            ->greeting("Hola {$notifiable->name},")
            ->line('Tienes una actividad proxima a vencer:')
            ->line("**Actividad:** {$this->plan->actividad}")
            ->line("**Linea:** {$this->plan->linea?->nombre}");

        if ($areaPasteurizadora) {
            $message->line("**Parte de Pasteurizadora:** {$areaPasteurizadora}");
        }

        return $message
            ->line("**PCM:** {$this->pcm}")
            ->line("**Dias restantes:** {$this->diasRestantes}")
            ->line('**Fecha limite:** ' . $this->fechaLimite()->format('d/m/Y'))
            ->action('Ver actividad', $this->resolvePlanActionUrl(true))
            ->line('Por favor, toma las acciones necesarias.')
            ->salutation('Saludos, Sistema de Gestion');
    }

    public function toDatabase($notifiable): array
    {
        $prioridad = $this->getPrioridad();

        return [
            'plan_id' => $this->plan->id,
            'actividad' => $this->plan->actividad,
            'linea_id' => $this->plan->linea_id,
            'linea_nombre' => $this->plan->linea?->nombre,
            'tipo_equipo' => $this->plan->tipo_equipo,
            'area_pasteurizadora' => $this->plan->area_pasteurizadora,
            'area_pasteurizadora_label' => $this->getAreaPasteurizadoraLabel(),
            'pcm' => $this->pcm,
            'dias_restantes' => $this->diasRestantes,
            'fecha_limite' => $this->fechaLimite()->format('d/m/Y'),
            'tipo' => $this->tipo,
            'prioridad' => $prioridad,
            'mensaje' => $this->getMensaje(),
            'url' => $this->resolvePlanActionUrl(),
        ];
    }

    public function toTwilio($notifiable): TwilioSmsMessage
    {
        return (new TwilioSmsMessage())
            ->content($this->getMensajeSMS());
    }

    private function getPrioridad(): string
    {
        if ($this->diasRestantes <= 1) {
            return 'alta';
        }

        if ($this->diasRestantes <= 3) {
            return 'media';
        }

        return 'baja';
    }

    private function getMensaje(): string
    {
        $areaPasteurizadora = $this->getAreaPasteurizadoraLabel();
        $parte = $areaPasteurizadora ? " - Parte: {$areaPasteurizadora}" : '';

        return "{$this->tipo}: {$this->plan->actividad}{$parte} - {$this->pcm} vence en {$this->diasRestantes} dias";
    }

    private function getMensajeSMS(): string
    {
        $fecha = $this->fechaLimite()->format('d/m');
        $areaPasteurizadora = $this->getAreaPasteurizadoraLabel();
        $parte = $areaPasteurizadora ? " {$areaPasteurizadora}" : '';

        return "ALERTA: {$this->plan->actividad}{$parte} ({$this->pcm}) vence el {$fecha}. Por favor revise el sistema.";
    }

    private function getAreaPasteurizadoraLabel(): ?string
    {
        if ($this->plan->tipo_equipo !== 'pasteurizadora' || !$this->plan->area_pasteurizadora) {
            return null;
        }

        return $this->plan->area_pasteurizadora_label;
    }

    private function fechaLimite()
    {
        return $this->plan->{'fecha_' . strtolower($this->pcm)} ?? now();
    }

    private function resolvePlanActionUrl(bool $absolute = false): string
    {
        return route('plan-accion.index', [
            'tipo' => $this->plan->tipo_equipo ?: 'lavadora',
            'linea_id' => $this->plan->linea_id,
            'open_plan_id' => $this->plan->id,
        ], $absolute);
    }
}
