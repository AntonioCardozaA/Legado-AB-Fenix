<?php
namespace App\Services;

use App\Models\PlanAccion;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\FechaProximaNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Verifica actividades próximas y envía notificaciones según preferencias
     */
    public function verificarYNotificarActividadesProximas()
    {
        $resultados = [
            'total' => 0,
            'errores' => []
        ];

        try {
            $hoy = Carbon::now();
            $planes = PlanAccion::with(['linea', 'responsable'])->get();
            
            foreach ($planes as $plan) {
                foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
                    $campoFecha = 'fecha_' . $pcm;
                    $fecha = $plan->$campoFecha;
                    
                    if (!$fecha) continue;
                    
                    $diasRestantes = $hoy->diffInDays($fecha, false);
                    
                    // Si la fecha ya pasó, no notificar
                    if ($diasRestantes < 0) continue;
                    
                    // Verificar si debemos notificar según preferencias del usuario
                    $this->notificarActividad($plan, $pcm, $diasRestantes, $resultados);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error en NotificationService: ' . $e->getMessage());
            $resultados['errores'][] = $e->getMessage();
        }
        
        return $resultados;
    }
    
    /**
     * Notifica a un usuario específico sobre una actividad
     */
    protected function notificarActividad($plan, $pcm, $diasRestantes, &$resultados)
    {
        try {
            $usuariosANotificar = collect();
            
            // 1. Notificar al responsable si existe
            if ($plan->responsable) {
                $usuariosANotificar->push($plan->responsable);
            }
            
            // 2. Notificar a supervisores o roles específicos
            $supervisores = User::whereHas('roles', function($q) {
                $q->where('name', 'supervisor');
            })->get();
            
            foreach ($supervisores as $supervisor) {
                $usuariosANotificar->push($supervisor);
            }
            
            // 3. Notificar a usuarios con permisos específicos
            $usuariosConPermiso = User::whereHas('permissions', function($q) {
                $q->where('name', 'ver-plan-accion');
            })->get();
            
            foreach ($usuariosConPermiso as $usuario) {
                $usuariosANotificar->push($usuario);
            }
            
            // Eliminar duplicados
            $usuariosANotificar = $usuariosANotificar->unique('id');
            
            foreach ($usuariosANotificar as $usuario) {
                $this->enviarNotificacionSegunPreferencias($usuario, $plan, $pcm, $diasRestantes, $resultados);
            }
            
        } catch (\Exception $e) {
            Log::error("Error notificando actividad {$plan->id}: " . $e->getMessage());
            $resultados['errores'][] = "Error con actividad {$plan->id}: " . $e->getMessage();
        }
    }
    
    /**
     * Envía notificación según las preferencias del usuario
     */
    protected function enviarNotificacionSegunPreferencias($usuario, $plan, $pcm, $diasRestantes, &$resultados)
    {
        try {
            // Obtener o crear configuración del usuario
            $config = UserNotificationSetting::firstOrCreate(
                ['user_id' => $usuario->id],
                [
                    'email_notifications' => true,
                    'sms_notifications' => false,
                    'days_before_notification' => 3,
                    'preferences' => ['pcm' => true, 'actividades' => true]
                ]
            );
            
            // Verificar si debemos notificar según días de anticipación
            if ($diasRestantes > $config->days_before_notification) {
                return; // No notificar si falta más tiempo del configurado
            }
            
            $channels = [];
            
            // Determinar canales según preferencias
            if ($config->email_notifications) {
                $channels[] = 'mail';
            }
            
            if ($config->sms_notifications && $config->phone_number && $config->phone_verified) {
                $channels[] = 'nexmo'; // o el servicio de SMS que uses
            }
            
            // Siempre guardar en base de datos
            $channels[] = 'database';
            
            // Crear y enviar notificación
            $notification = new FechaProximaNotification($plan, $pcm, $diasRestantes);
            
            if (!empty($channels)) {
                $usuario->notify($notification);
                $resultados['total']++;
                
                Log::info("Notificación enviada a {$usuario->email} para {$plan->actividad}");
            }
            
        } catch (\Exception $e) {
            Log::error("Error enviando notificación a {$usuario->email}: " . $e->getMessage());
            $resultados['errores'][] = "Error con usuario {$usuario->id}: " . $e->getMessage();
        }
    }
    
    /**
     * Envía notificaciones manuales desde el botón
     */
    public function enviarNotificacionesManuales($planId, $userId = null)
    {
        $plan = PlanAccion::with(['linea', 'responsable'])->findOrFail($planId);
        $hoy = Carbon::now();
        $resultados = [
            'enviadas' => 0,
            'mensaje' => '',
            'pcm_notificados' => []
        ];
        
        foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
            $campoFecha = 'fecha_' . $pcm;
            $fecha = $plan->$campoFecha;
            
            if ($fecha) {
                $diasRestantes = $hoy->diffInDays($fecha, false);
                
                // Si la fecha ya pasó, mostrar advertencia
                if ($diasRestantes < 0) {
                    $resultados['pcm_notificados'][] = [
                        'pcm' => $pcm,
                        'fecha' => $fecha->format('d/m/Y'),
                        'dias' => $diasRestantes,
                        'estado' => 'vencida'
                    ];
                    continue;
                }
                
                // Si es específico para un usuario
                if ($userId) {
                    $usuario = User::find($userId);
                    if ($usuario) {
                        $this->enviarNotificacionSegunPreferencias($usuario, $plan, $pcm, $diasRestantes, $resultados);
                        
                        $resultados['pcm_notificados'][] = [
                            'pcm' => $pcm,
                            'fecha' => $fecha->format('d/m/Y'),
                            'dias' => $diasRestantes,
                            'estado' => 'notificado'
                        ];
                    }
                } else {
                    // Notificar a todos los relevantes
                    $this->notificarActividad($plan, $pcm, $diasRestantes, $resultados);
                    
                    $resultados['pcm_notificados'][] = [
                        'pcm' => $pcm,
                        'fecha' => $fecha->format('d/m/Y'),
                        'dias' => $diasRestantes,
                        'estado' => 'procesado'
                    ];
                }
            }
        }
        
        $resultados['mensaje'] = "Se enviaron {$resultados['enviadas']} notificaciones";
        
        return $resultados;
    }
}