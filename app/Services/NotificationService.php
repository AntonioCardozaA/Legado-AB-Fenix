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

            $planes = PlanAccion::with(['linea','responsable'])->get();

            foreach ($planes as $plan) {

                foreach (['pcm1','pcm2','pcm3','pcm4'] as $pcm) {

                    $campoFecha = 'fecha_'.$pcm;
                    $fecha = $plan->$campoFecha;

                    if (!$fecha) continue;

                    $diasRestantes = $hoy->diffInDays($fecha,false);

                    if ($diasRestantes < 0) continue;

                    $this->notificarActividad($plan,$pcm,$diasRestantes,$resultados);
                }
            }

        } catch (\Exception $e) {

            Log::error('Error en NotificationService: '.$e->getMessage());
            $resultados['errores'][] = $e->getMessage();

        }

        return $resultados;
    }

    /**
     * Notifica usuarios relevantes
     */
    protected function notificarActividad($plan,$pcm,$diasRestantes,&$resultados)
    {

        try {

            $usuariosANotificar = collect();

            // Responsable
            if ($plan->responsable) {
                $usuariosANotificar->push($plan->responsable);
            }

            // Supervisores
            $supervisores = User::whereHas('roles',function($q){
                $q->where('name','supervisor');
            })->get();

            foreach ($supervisores as $supervisor){
                $usuariosANotificar->push($supervisor);
            }

            // Usuarios con permiso
            $usuariosConPermiso = User::whereHas('permissions',function($q){
                $q->where('name','ver-plan-accion');
            })->get();

            foreach ($usuariosConPermiso as $usuario){
                $usuariosANotificar->push($usuario);
            }

            // eliminar duplicados
            $usuariosANotificar = $usuariosANotificar->unique('id');

            foreach ($usuariosANotificar as $usuario){

                $this->enviarNotificacionSegunPreferencias(
                    $usuario,
                    $plan,
                    $pcm,
                    $diasRestantes,
                    $resultados
                );

            }

        } catch (\Exception $e){

            Log::error("Error notificando actividad {$plan->id}: ".$e->getMessage());

            $resultados['errores'][] =
                "Error con actividad {$plan->id}: ".$e->getMessage();
        }

    }


    /**
     * Envía notificación según preferencias del usuario
     */
    protected function enviarNotificacionSegunPreferencias($usuario,$plan,$pcm,$diasRestantes,&$resultados)
    {

        try {

            // Obtener configuración
            $config = UserNotificationSetting::firstOrCreate(

                ['user_id'=>$usuario->id],

                [
                    'email_notifications'=>true,
                    'sms_notifications'=>false,
                    'days_before_notification'=>3,
                    'preferences'=>[
                        'pcm'=>true,
                        'actividades'=>true
                    ]
                ]

            );


            if ($diasRestantes > $config->days_before_notification){
                return;
            }


            /*
            ======================================
            VALIDACIONES
            ======================================
            */

            if (!$config->shouldNotifyForPCM($pcm)) {
                return;
            }

            if (!$config->shouldNotifyForLine($plan->linea_id)) {
                return;
            }


            /*
            ======================================
            EMAIL PERSONALIZADO
            ======================================
            */

            // Determinar email a usar
            $emailToUse = $config->notification_email ?? $usuario->email;


            /*
            ======================================
            CANALES ACTIVOS
            ======================================
            */

            $channels = $config->getActiveChannels();


            /*
            ======================================
            CREAR NOTIFICACIÓN
            ======================================
            */

            $notification = new FechaProximaNotification(
                $plan,
                $pcm,
                $diasRestantes,
                $emailToUse
            );


            if (!empty($channels)){

                Notification::sendNow(
                    $usuario,
                    $notification,
                    $channels
                );

                $resultados['total']++;

                Log::info(
                    "Notificación enviada a {$emailToUse} para {$plan->actividad}"
                );

            }

        } catch (\Exception $e){

            Log::error(
                "Error enviando notificación a {$usuario->email}: ".$e->getMessage()
            );

            $resultados['errores'][] =
                "Error con usuario {$usuario->id}: ".$e->getMessage();

        }

    }



    /**
     * Envío manual de notificaciones
     */
    public function enviarNotificacionesManuales($planId,$userId=null)
    {

        $plan = PlanAccion::with(['linea','responsable'])->findOrFail($planId);

        $hoy = Carbon::now();

        $resultados = [

            'enviadas'=>0,
            'mensaje'=>'',
            'pcm_notificados'=>[]

        ];

        foreach (['pcm1','pcm2','pcm3','pcm4'] as $pcm){

            $campoFecha = 'fecha_'.$pcm;
            $fecha = $plan->$campoFecha;

            if (!$fecha) continue;

            $diasRestantes = $hoy->diffInDays($fecha,false);

            if ($diasRestantes < 0){

                $resultados['pcm_notificados'][] = [

                    'pcm'=>$pcm,
                    'fecha'=>$fecha->format('d/m/Y'),
                    'dias'=>$diasRestantes,
                    'estado'=>'vencida'

                ];

                continue;
            }

            if ($userId){

                $usuario = User::find($userId);

                if ($usuario){

                    $this->enviarNotificacionSegunPreferencias(
                        $usuario,
                        $plan,
                        $pcm,
                        $diasRestantes,
                        $resultados
                    );

                    $resultados['pcm_notificados'][]=[

                        'pcm'=>$pcm,
                        'fecha'=>$fecha->format('d/m/Y'),
                        'dias'=>$diasRestantes,
                        'estado'=>'notificado'

                    ];
                }

            }else{

                $this->notificarActividad(
                    $plan,
                    $pcm,
                    $diasRestantes,
                    $resultados
                );

                $resultados['pcm_notificados'][]=[

                    'pcm'=>$pcm,
                    'fecha'=>$fecha->format('d/m/Y'),
                    'dias'=>$diasRestantes,
                    'estado'=>'procesado'

                ];

            }

        }

        $resultados['mensaje'] =
            "Se enviaron {$resultados['enviadas']} notificaciones";

        return $resultados;

    }

}