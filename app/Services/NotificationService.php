<?php
// app/Services/NotificationService.php
namespace App\Services;

use App\Models\PlanAccion;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\PlanAccionNotificacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Verificar y enviar notificaciones para actividades próximas
     */
    public function verificarYNotificarActividadesProximas()
    {
        $hoy = Carbon::now();
        $notificacionesEnviadas = [
            'total' => 0,
            'por_usuario' => [],
            'errores' => []
        ];

        // Obtener usuarios con configuración de notificaciones
        $usuarios = User::whereHas('notificationSettings', function($query) {
            $query->where('email_notifications', true)
                  ->orWhere('sms_notifications', true);
        })->with('notificationSettings')->get();

        foreach ($usuarios as $usuario) {
            $diasAnticipacion = $usuario->notificationSettings->days_before_notification ?? 3;
            $actividades = $this->getActividadesProximasParaUsuario($usuario, $diasAnticipacion);
            
            foreach ($actividades as $actividad) {
                try {
                    $this->enviarNotificacionActividad($usuario, $actividad);
                    $notificacionesEnviadas['total']++;
                    $notificacionesEnviadas['por_usuario'][$usuario->id][] = $actividad->id;
                } catch (\Exception $e) {
                    Log::error("Error enviando notificación a usuario {$usuario->id}: " . $e->getMessage());
                    $notificacionesEnviadas['errores'][] = [
                        'usuario_id' => $usuario->id,
                        'actividad_id' => $actividad->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return $notificacionesEnviadas;
    }

    /**
     * Obtener actividades próximas relevantes para un usuario
     */
    private function getActividadesProximasParaUsuario(User $usuario, $diasAnticipacion)
    {
        $fechaLimite = Carbon::now()->addDays($diasAnticipacion);
        
        return PlanAccion::with('linea')
            ->where(function($query) use ($fechaLimite) {
                $query->whereBetween('fecha_pcm1', [now(), $fechaLimite])
                      ->orWhereBetween('fecha_pcm2', [now(), $fechaLimite])
                      ->orWhereBetween('fecha_pcm3', [now(), $fechaLimite])
                      ->orWhereBetween('fecha_pcm4', [now(), $fechaLimite]);
            })
            // Opcional: filtrar por responsable o línea asignada
            ->where(function($query) use ($usuario) {
                $query->where('responsable_id', $usuario->id)
                      ->orWhereNull('responsable_id');
            })
            ->get();
    }

    /**
     * Enviar notificación individual para una actividad
     */
    private function enviarNotificacionActividad(User $usuario, PlanAccion $actividad)
    {
        $pcmCampos = ['pcm1', 'pcm2', 'pcm3', 'pcm4'];
        
        foreach ($pcmCampos as $pcm) {
            $fechaCampo = 'fecha_' . $pcm;
            
            if ($actividad->$fechaCampo && $actividad->$fechaCampo >= now()) {
                $diasRestantes = now()->diffInDays($actividad->$fechaCampo, false);
                
                if ($diasRestantes <= $usuario->notificationSettings->days_before_notification) {
                    $tipo = $actividad->linea ? "Línea {$actividad->linea->nombre}" : 'Actividad';
                    
                    // Enviar notificación
                    $usuario->notify(new PlanAccionNotificacion(
                        $actividad,
                        $tipo,
                        strtoupper($pcm),
                        $diasRestantes
                    ));
                    
                    // Registrar en log
                    Log::info("Notificación enviada a usuario {$usuario->id} para actividad {$actividad->id}");
                }
            }
        }
    }

    /**
     * Enviar notificación manual para una actividad específica
     */
    public function notificarActividadManualmente($actividadId, $userId = null)
{
    $actividad = PlanAccion::with('linea')->findOrFail($actividadId);
    
    // Construir query de usuarios
    $query = User::query();
    
    if ($userId) {
        $query->where('id', $userId);
    } else {
        // Solo usuarios con notificaciones activadas
        $query->whereHas('notificationSettings', function($q) {
            $q->where('email_notifications', true)
              ->orWhere('sms_notifications', true);
        })->with('notificationSettings');
    }
    
    $usuarios = $query->get();
    $resultados = [];
    
    // Si no hay usuarios, intentar con el responsable de la actividad
    if ($usuarios->isEmpty() && $actividad->responsable_id) {
        $usuarios = User::where('id', $actividad->responsable_id)
            ->with('notificationSettings')
            ->get();
    }
    
    foreach ($usuarios as $usuario) {
        try {
            // Verificar configuración del usuario
            $settings = $usuario->notificationSettings;
            
            // Determinar qué PCMs notificar
            $pcmANotificar = [];
            foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
                $fechaCampo = 'fecha_' . $pcm;
                if ($actividad->$fechaCampo) {
                    $diasRestantes = now()->diffInDays($actividad->$fechaCampo, false);
                    // Solo notificar si la fecha es futura
                    if ($diasRestantes >= 0) {
                        $pcmANotificar[] = [
                            'pcm' => $pcm,
                            'dias' => $diasRestantes,
                            'fecha' => $actividad->$fechaCampo
                        ];
                    }
                }
            }
            
            // Enviar notificación para cada PCM
            foreach ($pcmANotificar as $pcmInfo) {
                $tipo = $actividad->linea ? "Línea {$actividad->linea->nombre}" : 'Actividad';
                
                $usuario->notify(new \App\Notifications\PlanAccionNotificacion(
                    $actividad,
                    $tipo,
                    strtoupper($pcmInfo['pcm']),
                    $pcmInfo['dias']
                ));
            }
            
            $resultados[$usuario->id] = 'success';
            
            \Log::info("Notificación manual enviada a usuario {$usuario->id} para actividad {$actividadId}");
            
        } catch (\Exception $e) {
            $resultados[$usuario->id] = 'error: ' . $e->getMessage();
            \Log::error("Error enviando notificación manual a usuario {$usuario->id}: " . $e->getMessage());
        }
    }
    
    return $resultados;
}
}