<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendElongacionWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // The transport retains its existing retry policy. Do not add job-level duplicate sends.
    public int $tries = 1;
    public int $timeout = 45;

    public function __construct(
        public readonly string $number,
        public readonly string $message,
        public readonly array $context = [],
    ) {
        $this->afterCommit = true;
    }

    public function handle(WhatsAppService $service): void
    {
        try {
            $service->sendMessage($this->number, $this->message);
            Log::info('WhatsApp elongacion enviado', $this->context);
        } catch (\Exception $exception) {
            Log::error('Error WhatsApp elongacion: ' . $exception->getMessage());
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Error WhatsApp elongacion: ' . $exception->getMessage(), $this->context);
    }
}
