<?php

namespace App\Services\Maintenance;

use App\Jobs\IndexMaintenanceHistoryRecord;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MaintenanceHistoryDispatcher
{
    private const JOBS = 'maintenance_history.pending_jobs';
    private const SCHEDULED = 'maintenance_history.flush_scheduled';

    public function dispatch(IndexMaintenanceHistoryRecord $job): void
    {
        $job->onQueue((string) config('maintenance_ai.history_index.queue', config('maintenance_ai.queue', 'default')));
        $mode = config('maintenance_ai.history_index.dispatch_mode') ?? config('maintenance_ai.dispatch_mode', 'queue');
        if ($mode === 'after_response' && !app()->runningInConsole()) {
            DB::afterCommit(fn () => $this->buffer($job));
            return;
        }

        dispatch($job)->afterCommit();
    }

    private function buffer(IndexMaintenanceHistoryRecord $job): void
    {
        $request = request();
        $jobs = $request->attributes->get(self::JOBS, []);
        $jobs[implode(':', [$job->module, $job->sourceType, $job->sourceId])] = $job;
        $request->attributes->set(self::JOBS, $jobs);
        if ($request->attributes->get(self::SCHEDULED, false)) return;
        $request->attributes->set(self::SCHEDULED, true);

        app()->terminating(function () use ($request): void {
            $jobs = $request->attributes->get(self::JOBS, []);
            $request->attributes->remove(self::JOBS);
            $request->attributes->remove(self::SCHEDULED);
            foreach ($jobs as $job) {
                try {
                    app(Dispatcher::class)->dispatchSync($job);
                } catch (Throwable $exception) {
                    $job->failed($exception);
                    report($exception);
                }
            }
        });
    }
}
