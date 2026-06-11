<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        $settings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'notification_email' => $user->email,
                'email_notifications' => true,
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
                    'summary_weekly' => false,
                ],
            ]
        );

        $lineas = Linea::whereIn('id', [4, 5, 6, 7, 8, 9, 12, 13])->get();

        return view('notificaciones.configuracion', compact('settings', 'lineas'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $currentSettings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'notification_email' => $user->email,
            ]
        );

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
            'summary_weekly' => 'boolean',
        ]);

        $preferences = [
            'urgent_only' => $request->boolean('urgent_only'),
            'include_weekends' => $request->boolean('include_weekends'),
            'summary_daily' => $request->boolean('summary_daily'),
            'summary_weekly' => $request->boolean('summary_weekly'),
        ];

        $previousPhoneNumber = $currentSettings->phone_number;

        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'notification_email' => $validated['notification_email'] ?? $user->email,
                'email_notifications' => $request->boolean('email_notifications'),
                'sms_notifications' => $request->boolean('sms_notifications'),
                'whatsapp_notifications' => $request->boolean('whatsapp_notifications'),
                'telegram_notifications' => $request->boolean('telegram_notifications'),
                'phone_number' => $validated['phone_number'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'telegram_user' => $validated['telegram_user'] ?? null,
                'phone_verified' => ($validated['phone_number'] ?? null) === $previousPhoneNumber
                    ? $currentSettings->phone_verified
                    : false,
                'days_before_notification' => (int) $validated['days_before_notification'],
                'notify_for_pcm1' => $request->boolean('notify_for_pcm1'),
                'notify_for_pcm2' => $request->boolean('notify_for_pcm2'),
                'notify_for_pcm3' => $request->boolean('notify_for_pcm3'),
                'notify_for_pcm4' => $request->boolean('notify_for_pcm4'),
                'notify_only_my_lines' => $request->boolean('notify_only_my_lines'),
                'lines_to_notify' => $validated['lines_to_notify'] ?? [],
                'notify_at_time' => $validated['notify_at_time'],
                'preferences' => $preferences,
            ]
        );

        if (
            $request->boolean('sms_notifications')
            && ($validated['phone_number'] ?? null) !== null
            && ($validated['phone_number'] ?? null) !== $previousPhoneNumber
        ) {
            session()->flash('warning', 'Por favor verifica tu numero de telefono.');
        }

        return redirect()->route('notificaciones.configuracion')
            ->with('success', 'Configuracion guardada correctamente');
    }

    public function verifyPhone(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $settings = Auth::user()->notificationSettings;

        if ($request->code === session('verification_code')) {
            $settings->update(['phone_verified' => true]);

            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Codigo incorrecto',
        ]);
    }
}
