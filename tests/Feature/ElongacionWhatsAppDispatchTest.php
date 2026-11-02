<?php

namespace Tests\Feature;

use App\Http\Controllers\ElongacionController;
use App\Jobs\SendElongacionWhatsApp;
use App\Models\CadenaCiclo;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ElongacionWhatsAppDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function send(Request $request, CadenaCiclo $cycle, float $pump = 1.46123, float $steam = 1.30996): void
    {
        (new \ReflectionMethod(app(ElongacionController::class), 'enviarNotificacionWhatsApp'))
            ->invoke(app(ElongacionController::class), $request, $pump, $steam, $cycle);
    }

    public function test_after_response_keeps_the_exact_sync_message_and_captures_values_before_later_changes(): void
    {
        config(['services.ultramsg.instance' => 'fixture', 'services.ultramsg.token' => 'fixture', 'services.ultramsg.url' => 'https://fixture.invalid']);
        Http::fake(fn () => Http::response(['sent' => true]));
        $request = Request::create('/fixture', 'POST', ['linea' => 'L-04']);
        $cycle = new CadenaCiclo(['codigo' => 'FIXTURE-C01', 'proveedor' => 'Original provider']);
        config(['elongacion-alerts.whatsapp_dispatch_mode' => 'sync']);
        $this->send($request, $cycle);
        Http::assertSentCount(1);
        $expected = Http::recorded()[0][0]->data();

        Http::fake(fn () => Http::response(['sent' => true]));
        config(['elongacion-alerts.whatsapp_dispatch_mode' => 'after_response']);
        (new \ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);
        $this->send($request, $cycle);
        Http::assertNothingSent();
        $request->merge(['linea' => 'L-05']);
        $cycle->codigo = 'LATER-CYCLE';
        $cycle->proveedor = 'Later provider';
        $this->app->terminate();
        Http::assertSentCount(1);
        $this->assertSame($expected, Http::recorded()[0][0]->data());
        $this->app->terminate();
        Http::assertSentCount(1);
    }

    public function test_opt_in_queue_contains_serializable_event_data_and_normal_values_send_nothing(): void
    {
        Bus::fake([SendElongacionWhatsApp::class]);
        config(['elongacion-alerts.whatsapp_dispatch_mode' => 'queue']);
        $request = Request::create('/fixture', 'POST', ['linea' => 'L-04']);
        $cycle = new CadenaCiclo(['codigo' => 'FIXTURE-C01', 'proveedor' => 'Captured provider']);
        $this->send($request, $cycle, 0.2, 0.3);
        Bus::assertNothingDispatched();
        $this->send($request, $cycle);
        $cycle->proveedor = 'Later provider';
        Bus::assertDispatched(SendElongacionWhatsApp::class, function ($job) {
            $copy = unserialize(serialize($job));
            $this->assertSame(1, $copy->tries);
            $this->assertSame(45, $copy->timeout);
            $this->assertTrue($copy->afterCommit);
            $this->assertSame(1.46123, $copy->context['bombas']);
            $this->assertStringContainsString('Captured provider', $copy->message);
            $this->assertStringNotContainsString('Later provider', $copy->message);
            return true;
        });
        Bus::assertDispatchedTimes(SendElongacionWhatsApp::class, 1);
    }

    public function test_provider_exception_keeps_existing_non_fatal_behavior(): void
    {
        $service = \Mockery::mock(WhatsAppService::class);
        $service->shouldReceive('sendMessage')->once()->andThrow(new \RuntimeException('Fixture provider failure'));
        (new SendElongacionWhatsApp('fixture', 'fixture'))->handle($service);
        $this->addToAssertionCount(1);
    }
}
