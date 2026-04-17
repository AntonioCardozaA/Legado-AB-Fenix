<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public static function enviarMensaje(string $numero, string $mensaje): Response
    {
        /** @var string $instance */
        $instance = (string) config('services.ultramsg.instance', '');
        /** @var string $token */
        $token = (string) config('services.ultramsg.token', '');

        $url = "https://api.ultramsg.com/$instance/messages/chat";

        return Http::asForm()->post($url, [
            'token' => $token,
            'to' => $numero, // Ejemplo: 521XXXXXXXXXX
            'body' => $mensaje
        ]);
    }
}