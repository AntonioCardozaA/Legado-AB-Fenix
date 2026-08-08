<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendLavadoraDamageWhatsApp implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 45;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $number,
        public readonly string $message,
        public readonly array $context = []
    ) {
        $this->afterCommit = true;
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $response = $whatsAppService->sendMessage($this->number, $this->message);

        if ($response->failed()) {
            throw new RuntimeException('UltraMsg respondio con HTTP ' . $response->status());
        }

        Log::info('WhatsApp automatico de lavadora enviado correctamente.', $this->context);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('No se pudo enviar el WhatsApp automatico de lavadora.', array_merge($this->context, [
            'error' => $exception->getMessage(),
        ]));
    }
}
