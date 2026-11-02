<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Jobs\IndexMaintenanceHistoryRecord;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\MaintenanceHistoryChunk;
use App\Services\Maintenance\MaintenanceHistoryDispatcher;
use App\Services\Maintenance\MaintenanceHistoryIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceHistoryDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function webRequest(): void
    {
        (new \ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);
        $this->app->instance('request', Request::create('/history-dispatch-fixture', 'POST'));
        config(['maintenance_ai.history_index.dispatch_mode' => 'after_response']);
    }

    public function test_after_response_groups_the_final_operation_and_discards_rolled_back_jobs(): void
    {
        Bus::fake([IndexMaintenanceHistoryRecord::class]);
        $this->webRequest();
        $dispatcher = app(MaintenanceHistoryDispatcher::class);
        $dispatcher->dispatch(new IndexMaintenanceHistoryRecord('lavadora', 'analisis_lavadora', 1));
        $dispatcher->dispatch(new IndexMaintenanceHistoryRecord('lavadora', 'analisis_lavadora', 1, true));
        try {
            DB::transaction(function () use ($dispatcher) {
                $dispatcher->dispatch(new IndexMaintenanceHistoryRecord('lavadora', 'analisis_lavadora', 2));
                throw new \RuntimeException('fixture rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('fixture rollback', $exception->getMessage());
        }
        Bus::assertNothingDispatched();
        $this->app->terminate();
        Bus::assertDispatchedSync(IndexMaintenanceHistoryRecord::class, fn ($job) => $job->sourceId === 1 && $job->delete);
        Bus::assertDispatchedSyncTimes(IndexMaintenanceHistoryRecord::class, 1);
    }

    public function test_request_buffers_are_isolated_even_for_the_same_source_id(): void
    {
        Bus::fake([IndexMaintenanceHistoryRecord::class]);
        $this->webRequest();
        app(MaintenanceHistoryDispatcher::class)->dispatch(new IndexMaintenanceHistoryRecord('lavadora', 'analisis_lavadora', 1));
        $this->app->instance('request', Request::create('/another-request', 'POST'));
        app(MaintenanceHistoryDispatcher::class)->dispatch(new IndexMaintenanceHistoryRecord('lavadora', 'analisis_lavadora', 1));
        $this->app->terminate();
        Bus::assertDispatchedSyncTimes(IndexMaintenanceHistoryRecord::class, 2);
    }

    public function test_final_chunks_match_immediate_indexing_with_one_embedding_instead_of_two(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['maintenance_ai.enabled' => true, 'maintenance_ai.history_index.auto_index' => true, 'maintenance_ai.history_index.embeddings_enabled' => true, 'maintenance_ai.history_index.dispatch_mode' => 'queue']);
        $embeddings = 0;
        $this->mock(AiProviderInterface::class, function ($mock) use (&$embeddings) {
            $mock->shouldReceive('createEmbedding')->andReturnUsing(function () use (&$embeddings) { $embeddings++; return [0.1, 0.2]; });
        });
        app(MaintenanceHistoryIndexer::class)->registerModelEvents();
        $linea = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $component = Componente::create(['codigo' => 'HISTORY_DISPATCH_SERVO', 'nombre' => 'Servo', 'activo' => true]);
        $record = AnalisisLavadora::create([
            'linea_id' => $linea->id, 'componente_id' => $component->id, 'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(), 'estado' => AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'Initial revision',
        ]);
        $record->update(['actividad' => 'Final revision with evidence', 'evidencia_fotos' => ['fixture.jpg']]);
        $this->assertSame(2, $embeddings);
        $expected = MaintenanceHistoryChunk::where('source_type', 'analisis_lavadora')->where('source_id', $record->id)->orderBy('chunk_index')->pluck('content_hash')->all();
        $this->assertNotEmpty($expected);
        MaintenanceHistoryChunk::where('source_type', 'analisis_lavadora')->where('source_id', $record->id)->delete();
        $this->webRequest();
        $record->update(['actividad' => 'Initial revision', 'evidencia_fotos' => []]);
        $record->update(['actividad' => 'Final revision with evidence', 'evidencia_fotos' => ['fixture.jpg']]);
        $this->assertSame(2, $embeddings, 'The provider must not run before the response in deferred mode.');
        $this->app->terminate();
        $this->assertSame(3, $embeddings, 'Two saves of the same source need one final embedding.');
        $actual = MaintenanceHistoryChunk::where('source_type', 'analisis_lavadora')->where('source_id', $record->id)->orderBy('chunk_index')->pluck('content_hash')->all();
        $this->assertSame($expected, $actual);
    }
}
