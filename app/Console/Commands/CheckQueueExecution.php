<?php

namespace App\Console\Commands;

use App\Support\QueueExecutionBudget;
use Illuminate\Console\Command;

class CheckQueueExecution extends Command
{
    protected $signature = 'maintenance:queue:check {--worker-timeout=240 : Timeout of the supervised worker} {--json}';
    protected $description = 'Checks queue timing and process support without connecting to a queue or dispatching jobs.';

    public function handle(): int
    {
        $connection = (string) config('queue.default');
        $driver = (string) config('queue.connections.' . $connection . '.driver', '');
        $retryAfter = config('queue.connections.' . $connection . '.retry_after');
        $minimumRetry = QueueExecutionBudget::minimumRetryAfter(max(1, (int) $this->option('worker-timeout')));
        $checks = [
            'durable_async_connection' => in_array($driver, ['database', 'redis', 'beanstalkd', 'sqs'], true),
            'retry_after_verified' => is_numeric($retryAfter) && (int) $retryAfter >= $minimumRetry,
            'worker_timeout_signals_available' => function_exists('pcntl_alarm') && function_exists('pcntl_signal'),
        ];
        $report = [
            'ok' => !in_array(false, $checks, true), 'checks' => $checks,
            'details' => [
                'connection' => $connection, 'driver' => $driver, 'sapi' => PHP_SAPI,
                'job_timeouts' => QueueExecutionBudget::jobTimeouts(),
                'worker_timeout' => (int) $this->option('worker-timeout'),
                'retry_after' => $retryAfter, 'minimum_retry_after' => $minimumRetry,
                'cron_worker_timeout' => QueueExecutionBudget::workerTimeout(),
                'cron_lock_seconds' => QueueExecutionBudget::cronLockSeconds(),
                'note' => $driver === 'sqs'
                    ? 'Verify visibility timeout in SQS; this command cannot verify it remotely.'
                    : 'Verify the actual supervised worker and web process limits separately; no configuration was changed.',
            ],
        ];
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $report['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
