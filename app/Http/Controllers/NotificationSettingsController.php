<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra el formulario de configuración
     */
    public function index()
    {
        $user = Auth::user();
        
        // Obtener o crear configuración del usuario
        $settings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                 'notification_email' => $user->email, 
                'sms_notifications' => false,
                'whatsapp_notifications' => false,
                'telegram_notifications' => false,
                'days_before_notification' => 3,
                'notify_for_pcm1' => true,
                'notify_for_pcm2' => true,
                'notify_for_pcm3' => true,
                'notify_for_pcm4' => true,
                'notify_only_my_lines' => false,
                'lines_to_notify' => [],
                'notify_at_time' => '08:00',
                'preferences' => [
                    'urgent_only' => false,
                    'include_weekends' => true,
                    'summary_daily' => true,
                    'summary_weekly' => false
                ]
            ]
        );

        // Obtener líneas disponibles (las de lavadora)
        $lineas = \App\Models\Linea::whereIn('id', [4,5,6,7,8,9,12,13])->get();

        return view('notificaciones.configuracion', compact('settings', 'lineas'));
    }

    /**
     * Guarda la configuración
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
    'notification_email' => 'nullable|email|max:255',
    'email_notifications' => 'boolean',
    'sms_notifications' => 'boolean',
    'whatsapp_notifications' => 'boolean',
    'telegram_notifications' => 'boolean',
    'phone_number' => 'nullable|string|max:20',
    'whatsapp_number' => 'nullable|string|max:20',
    'telegram_user' => 'nullable|string|max:100',
    'days_before_notification' => 'required|integer|min:1|max:30',
    'notify_for_pcm1' => 'boolean',
    'notify_for_pcm2' => 'boolean',
    'notify_for_pcm3' => 'boolean',
    'notify_for_pcm4' => 'boolean',
    'notify_only_my_lines' => 'boolean',
    'lines_to_notify' => 'nullable|array',
    'lines_to_notify.*' => 'exists:lineas,id',
    'notify_at_time' => 'required|date_format:H:i',
    'urgent_only' => 'boolean',
    'include_weekends' => 'boolean',
    'summary_daily' => 'boolean',
    'summary_weekly' => 'boolean'
]);

        // Preparar preferencias
        $preferences = [
            'urgent_only' => $request->boolean('urgent_only'),
            'include_weekends' => $request->boolean('include_weekends'),
            'summary_daily' => $request->boolean('summary_daily'),
            'summary_weekly' => $request->boolean('summary_weekly')
        ];

        // Actualizar o crear configuración
        $settings = UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'notification_email' => $request->notification_email,
                'sms_notifications' => $request->boolean('sms_notifications'),
                'whatsapp_notifications' => $request->boolean('whatsapp_notifications'),
                'telegram_notifications' => $request->boolean('telegram_notifications'),
                'phone_number' => $request->phone_number,
                'whatsapp_number' => $request->whatsapp_number,
                'telegram_user' => $request->telegram_user,
                'phone_verified' => $settings->phone_verified ?? false,
                'days_before_notification' => $request->days_before_notification,
                'notify_for_pcm1' => $request->boolean('notify_for_pcm1'),
                'notify_for_pcm2' => $request->boolean('notify_for_pcm2'),
                'notify_for_pcm3' => $request->boolean('notify_for_pcm3'),
                'notify_for_pcm4' => $request->boolean('notify_for_pcm4'),
                'notify_only_my_lines' => $request->boolean('notify_only_my_lines'),
                'lines_to_notify' => $request->lines_to_notify ?? [],
                'notify_at_time' => $request->notify_at_time,
                'preferences' => $preferences
            ]
        );

        // Si se actualizó el teléfono, podrías enviar un código de verificación
        if ($request->sms_notifications && $request->phone_number != $settings->phone_number) {
            // Lógica para verificar teléfono
            session()->flash('warning', 'Por favor verifica tu número de teléfono');
        }

        return redirect()->route('notificaciones.configuracion')
            ->with('success', 'Configuración guardada correctamente');
    }

    /**
     * Verificar número de teléfono
     */
    public function verifyPhone(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $settings = Auth::user()->notificationSettings;
        
        // Aquí iría la lógica de verificación
        if ($request->code == session('verification_code')) {
            $settings->update(['phone_verified' => true]);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Código incorrecto']);
    }
}