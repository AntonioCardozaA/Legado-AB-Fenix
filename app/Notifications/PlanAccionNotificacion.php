<?php
// app/Notifications/PlanAccionNotificacion.php
namespace App\Notifications;

use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PlanAccionNotificacion extends Notification implements ShouldQueue
{
    use Queueable;

    protected $plan;
    protected $tipo;
    protected $pcm;
    protected $diasRestantes;

    public function __construct(PlanAccion $plan, $tipo, $pcm, $diasRestantes)
    {
        $this->plan = $plan;
        $this->tipo = $tipo;
        $this->pcm = $pcm;
        $this->diasRestantes = $diasRestantes;
    }

    public function via($notifiable)
    {
        $channels = ['database']; // Siempre guardar en BD
        
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

    public function toMail($notifiable)
    {
        $prioridad = $this->getPrioridad();
        $color = $prioridad === 'alta' ? '#ef4444' : ($prioridad === 'media' ? '#f59e0b' : '#3b82f6');
        
        return (new MailMessage)
            ->subject("🔔 Alerta: {$this->tipo} - {$this->plan->actividad}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tienes una actividad próxima a vencer:")
            ->line("**Actividad:** {$this->plan->actividad}")
            ->line("**Línea:** {$this->plan->linea?->nombre}")
            ->line("**PCM:** {$this->pcm}")
            ->line("**Días restantes:** {$this->diasRestantes}")
            ->line("**Fecha límite:** " . now()->parse($this->plan->{'fecha_' . strtolower($this->pcm)})->format('d/m/Y'))
            ->action('Ver actividad', url('/plan-accion/' . $this->plan->id))
            ->line('Por favor, toma las acciones necesarias.')
            ->salutation('Saludos, Sistema de Gestión');
    }

    public function toDatabase($notifiable)
    {
        $prioridad = $this->getPrioridad();
        
        return [
            'plan_id' => $this->plan->id,
            'actividad' => $this->plan->actividad,
            'linea_nombre' => $this->plan->linea?->nombre,
            'pcm' => $this->pcm,
            'dias_restantes' => $this->diasRestantes,
            'fecha_limite' => $this->plan->{'fecha_' . strtolower($this->pcm)}->format('d/m/Y'),
            'tipo' => $this->tipo,
            'prioridad' => $prioridad,
            'mensaje' => $this->getMensaje(),
            'url' => url('/plan-accion/' . $this->plan->id)
        ];
    }

    public function toTwilio($notifiable)
    {
        $prioridad = $this->getPrioridad();
        $mensaje = $this->getMensajeSMS();
        
        return (new TwilioSmsMessage())
            ->content($mensaje);
    }

    private function getPrioridad()
    {
        if ($this->diasRestantes <= 1) return 'alta';
        if ($this->diasRestantes <= 3) return 'media';
        return 'baja';
    }

    private function getMensaje()
    {
        $prioridad = $this->getPrioridad();
        $emoji = $prioridad === 'alta' ? '🔴' : ($prioridad === 'media' ? '🟡' : '🔵');
        
        return "{$emoji} {$this->tipo}: {$this->plan->actividad} - {$this->pcm} vence en {$this->diasRestantes} días";
    }

    private function getMensajeSMS()
    {
        // SMS más corto por limitaciones de caracteres
        $fecha = $this->plan->{'fecha_' . strtolower($this->pcm)}->format('d/m');
        return "ALERTA: {$this->plan->actividad} ({$this->pcm}) vence el {$fecha}. Por favor revise el sistema.";
    }
}