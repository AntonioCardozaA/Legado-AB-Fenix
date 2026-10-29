<?php

namespace App\Services;

use App\Models\AlertRecipient;
use App\Models\NotificationDispatchLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfiguredAlertRecipientService
{
    public function recipientsFor(string $alertType): Collection
    {
        return AlertRecipient::query()
            ->where('active', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (AlertRecipient $recipient): bool => $recipient->receivesAlertType($alertType))
            ->values();
    }

    /**
     * Sends one alert to the administrative phone list. The dispatch log makes
     * scheduled commands safe to run more than once for the same event.
     *
     * @return array{recipients: int, sent: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function dispatch(
        string $alertType,
        string $message,
        string $uniqueKey,
        array $context = []
    ): array {
        $result = [
            'recipients' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($this->recipientsFor($alertType) as $recipient) {
            $result['recipients']++;
            $log = NotificationDispatchLog::query()->firstOrCreate(
                [
                    'type' => 'external:' . $alertType,
                    'notifiable_type' => AlertRecipient::class,
                    'notifiable_id' => $recipient->getKey(),
                    'unique_key' => $uniqueKey,
                ],
                [
                    'context' => array_merge($context, [
                        'phone_number' => $recipient->phone_number,
                        'channel' => $recipient->channel,
                    ]),
                    'sent_at' => now(),
                ]
            );

            if (!$log->wasRecentlyCreated) {
                $result['skipped']++;
                continue;
            }

            try {
                $this->send($recipient, $message);
                $result['sent']++;
            } catch (Throwable $exception) {
                $log->delete();
                $result['failed']++;
                $result['errors'][] = $exception->getMessage();

                Log::error('No se pudo enviar una alerta al destinatario configurado.', [
                    'alert_type' => $alertType,
                    'recipient_id' => $recipient->getKey(),
                    'phone_number' => $recipient->phone_number,
                    'channel' => $recipient->channel,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    private function send(AlertRecipient $recipient, string $message): void
    {
        if ($recipient->channel === AlertRecipient::CHANNEL_SMS) {
            app(TwilioService::class)->sendSMS($this->formatSmsNumber($recipient->phone_number), $message);

            return;
        }

        $response = app(WhatsAppService::class)->sendMessage($recipient->phone_number, $message);

        if ($response->failed()) {
            throw new \RuntimeException('El proveedor de WhatsApp respondió con HTTP ' . $response->status());
        }
    }

    private function formatSmsNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return str_starts_with($number, '+') ? '+' . $digits : '+' . $digits;
    }
}
