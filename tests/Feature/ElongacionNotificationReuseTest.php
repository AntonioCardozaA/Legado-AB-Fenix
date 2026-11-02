<?php

namespace Tests\Feature;

use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Services\NotificationVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class ElongacionNotificationReuseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_repeated_alerts_match_original_cycle_and_due_rules_with_fewer_queries(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        config(['elongacion-alerts.timezone' => 'America/Mexico_City']);
        $line = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $closed = $this->cycle($line, false, 1);
        $superseded = $this->cycle($line, true, 2);
        $current = $this->cycle($line, true, 3);
        $oldCycle = $this->record($closed, '2026-06-01 10:00:00');
        $oldActive = $this->record($superseded, '2026-07-01 10:00:00');
        $tied = $this->record($current, '2026-08-31 10:00:00');
        $latest = $this->record($current, '2026-08-31 10:00:00');
        $payloads = [];
        foreach ([$oldCycle->id, $oldActive->id, $tied->id, $latest->id, 999999, null, '00' . $latest->id] as $id) {
            foreach (['elongacion_change_alert', 'elongacion_purchase_alert', 'elongacion_reminder'] as $type) {
                $payloads[] = ['type' => $type, 'elongacion_id' => $id, 'detalles' => [['elongacion_id' => $id]]];
            }
        }
        foreach ([[], [['linea' => ' L-04 ']], [['linea' => 'L-04', 'ultimo_registro' => '2026-08-31 10:00:00']],
            [['linea' => 'L-04', 'ultimo_registro' => '2026-08-30 10:00:00']], [['linea' => '']],
            [['elongacion_id' => $latest->id], ['elongacion_id' => 999999]], [['linea' => 'UNKNOWN']]] as $details) {
            $payloads[] = ['type' => 'elongacion_reminder', 'detalles' => $details];
        }
        $items = collect();
        for ($repeat = 0; $repeat < 8; $repeat++) {
            foreach ($payloads as $data) {
                $items->push($this->item($data + ['message' => 'Unique text ' . $repeat]));
            }
        }
        $service = app(NotificationVisibilityService::class);
        $original = new ReflectionMethod($service, 'notificationTargetExists');
        $optimized = new ReflectionMethod($service, 'filterExistingTargets');
        // Warm both paths before measuring the same ordered notifications and fixed clock.
        $original->invoke($service, $items->first());
        $optimized->invoke($service, $items);
        $before = $this->measure(fn () => $items->filter(fn ($item) => $original->invoke($service, $item))->values()->pluck('id')->all());
        $after = $this->measure(fn () => $optimized->invoke($service, $items)->pluck('id')->all());
        $this->assertSame($before['ids'], $after['ids']);
        $this->assertNotEmpty($after['ids']);
        $this->assertLessThan($items->count(), count($after['ids']));
        if (getenv('LEGADO_BENCHMARK_NOTIFICATIONS')) {
            fwrite(STDERR, json_encode(['notifications' => $items->count(), 'before' => array_diff_key($before, ['ids' => true]),
                'after' => array_diff_key($after, ['ids' => true]), 'same_ordered_ids' => $before['ids'] === $after['ids']], JSON_PRETTY_PRINT) . PHP_EOL);
        }
        $this->assertLessThan($before['queries'] / 4, $after['queries']);
    }

    public function test_each_read_observes_new_records_cycles_clock_and_alert_configuration(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        $line = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $cycle = $this->cycle($line, true, 1);
        $record = $this->record($cycle, '2026-08-31 10:00:00');
        $items = collect([$this->item(['type' => 'elongacion_reminder', 'detalles' => [['elongacion_id' => $record->id]]])]);
        $service = app(NotificationVisibilityService::class);
        $filter = new ReflectionMethod($service, 'filterExistingTargets');
        $this->assertCount(1, $filter->invoke($service, $items));
        Carbon::setTestNow('2026-09-01 12:00:00');
        $this->assertCount(0, $filter->invoke($service, $items));
        config(['elongacion-alerts.interval_months' => 1, 'elongacion-alerts.lead_days' => 40]);
        $this->assertCount(1, $filter->invoke($service, $items));
        $this->record($cycle, '2026-09-01 10:00:00');
        $this->assertCount(0, $filter->invoke($service, $items));
        $change = collect([$this->item(['type' => 'elongacion_change_alert', 'elongacion_id' => $record->id])]);
        $this->assertCount(0, $filter->invoke($service, $change));
        $latest = $this->record($cycle, null);
        $nullDate = collect([$this->item(['type' => 'elongacion_change_alert', 'elongacion_id' => $latest->id])]);
        $this->assertCount(1, $filter->invoke($service, $nullDate));
        $this->cycle($line, true, 2);
        $this->assertCount(0, $filter->invoke($service, $nullDate));
    }

    private function measure(callable $operation): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = hrtime(true);
        try {
            $ids = $operation();
            return ['ids' => $ids, 'queries' => count(DB::getQueryLog()), 'ms' => round((hrtime(true) - $start) / 1e6, 2)];
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    private function cycle(Linea $line, bool $active, int $number): CadenaCiclo
    {
        return CadenaCiclo::create(['linea_id' => $line->id, 'linea' => $line->nombre, 'codigo' => 'REUSE-' . $number,
            'numero_ciclo' => $number, 'proveedor' => 'Fixture', 'activa' => $active, 'paso_inicial' => 173, 'instalada_en' => '2026-01-01']);
    }

    private function record(CadenaCiclo $cycle, ?string $date): Elongacion
    {
        return Elongacion::withoutEvents(fn () => Elongacion::forceCreate(['linea_id' => $cycle->linea_id, 'linea' => $cycle->linea,
            'cadena_ciclo_id' => $cycle->id, 'created_at' => $date, 'updated_at' => $date]));
    }

    private function item(array $data): DatabaseNotification
    {
        return new DatabaseNotification(['id' => (string) Str::uuid(), 'type' => 'reuse-fixture', 'data' => $data]);
    }
}
