<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public static function enviarMensaje($numero, $mensaje)
    {
        $instance = env('ULTRAMSG_INSTANCE');
        $token = env('ULTRAMSG_TOKEN');

        $url = "https://api.ultramsg.com/$instance/messages/chat";

        return Http::asForm()->post($url, [
            'token' => $token,
            'to' => $numero, // Ejemplo: 521XXXXXXXXXX
            'body' => $mensaje
        ]);
    }
}