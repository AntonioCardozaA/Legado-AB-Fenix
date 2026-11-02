<?php

namespace Tests\Feature;

use App\Http\Controllers\CronQueueController;
use App\Support\QueueExecutionBudget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueExecutionBudgetTest extends TestCase
{
    public function test_cron_lease_covers_its_window_and_a_final_job_and_is_released(): void
    {
        config([
            'maintenance_ai.job_timeout' => 120, 'maintenance_ai.history_index.job_timeout' => 120,
            'maintenance_ai.cron.queue_timeout' => 45, 'maintenance_ai.cron.queue_max_time' => 50,
            'maintenance_ai.cron.queue_lock_seconds' => 55, 'maintenance_ai.cron.queue_token' => 'fixture-token',
        ]);
        $lock = \Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $lock->shouldReceive('get')->once()->andReturnTrue();
        $lock->shouldReceive('release')->once();
        Cache::shouldReceive('lock')->once()->with('cron:maintenance-ai-queue-worker', 200)->andReturn($lock);
        Artisan::shouldReceive('call')->once()->withArgs(fn ($command, $options) => $command === 'queue:work' && $options['--timeout'] === 120 && $options['--max-time'] === 50)->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('fixture');
        $response = app(CronQueueController::class)->queue('fixture-token');
        $this->assertSame(200, $response->status());
        $this->assertSame(270, QueueExecutionBudget::minimumRetryAfter(240));
    }

    public function test_budget_tracks_provider_and_history_timeouts_and_preserves_a_larger_configured_lease(): void
    {
        config(['maintenance_ai.job_timeout' => 400, 'maintenance_ai.history_index.job_timeout' => 180, 'maintenance_ai.cron.queue_max_time' => 50, 'maintenance_ai.cron.queue_lock_seconds' => 1000]);
        $this->assertSame(400, QueueExecutionBudget::workerTimeout());
        $this->assertSame(430, QueueExecutionBudget::minimumRetryAfter(240));
        $this->assertSame(1000, QueueExecutionBudget::cronLockSeconds());
    }
}
