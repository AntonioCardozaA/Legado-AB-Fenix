<?php

namespace App\Jobs;

use App\Services\Maintenance\MaintenanceHistoryIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class IndexMaintenanceHistoryRecord implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public readonly string $module,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly bool $delete = false
    ) {
        $this->afterCommit = true;
        $this->tries = max(1, (int) config('maintenance_ai.history_index.max_retries', 2) + 1);
        $this->timeout = max(30, (int) config('maintenance_ai.history_index.job_timeout', 120));
    }

    public function backoff(): array
    {
        return [20, 90];
    }

    public function handle(MaintenanceHistoryIndexer $indexer): void
    {
        if ($this->delete) {
            $indexer->deleteFor($this->module, $this->sourceType, $this->sourceId);

            return;
        }

        $indexer->indexSource($this->sourceType, $this->sourceId);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Failed to index maintenance history record for AI retrieval.', [
            'module' => $this->module,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'delete' => $this->delete,
            'error' => $exception->getMessage(),
        ]);
    }
}
