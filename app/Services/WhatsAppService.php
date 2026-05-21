<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class WhatsAppService
{
    public static function enviarMensaje(string $numero, string $mensaje): Response
    {
        return app(self::class)->sendMessage($numero, $mensaje);
    }

    public function sendMessage(string $number, string $message): Response
    {
        $config = $this->resolveConfiguration();
        $normalizedNumber = $this->normalizeNumber($number);
        $url = sprintf(
            '%s/%s/messages/chat',
            rtrim($config['url'], '/'),
            $config['instance']
        );

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 500, throw: false)
            ->post($url, [
                'token' => $config['token'],
                'to' => $normalizedNumber,
                'body' => $message,
            ]);

        if ($response->failed()) {
            Log::error('UltraMsg devolvio una respuesta no exitosa.', [
                'recipient' => $normalizedNumber,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response;
    }

    public function normalizeNumber(string $number): string
    {
        $normalized = preg_replace('/\D+/', '', $number) ?? '';

        if ($normalized === '') {
            throw new InvalidArgumentException('El numero de WhatsApp no es valido.');
        }

        /** @var string $defaultCountryCode */
        $defaultCountryCode = trim((string) config('services.ultramsg.default_country_code', ''));

        if ($defaultCountryCode !== '' && strlen($normalized) === 10) {
            $normalized = $defaultCountryCode . $normalized;
        }

        return $normalized;
    }

    /**
     * @return array{instance: string, token: string, url: string}
     */
    private function resolveConfiguration(): array
    {
        /** @var string $instance */
        $instance = trim((string) config('services.ultramsg.instance', ''));
        /** @var string $token */
        $token = trim((string) config('services.ultramsg.token', ''));
        /** @var string $url */
        $url = trim((string) config('services.ultramsg.url', 'https://api.ultramsg.com'));

        if ($instance === '' || $token === '') {
            throw new RuntimeException('La configuracion de UltraMsg esta incompleta.');
        }

        return [
            'instance' => $instance,
            'token' => $token,
            'url' => $url,
        ];
    }
}
