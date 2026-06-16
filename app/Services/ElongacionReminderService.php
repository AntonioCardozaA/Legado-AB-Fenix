<?php

namespace App\Services;

use App\Models\Elongacion;
use App\Models\NotificationDispatchLog;
use App\Models\ElongacionReminderNotification;
use App\Models\UserNotificationSetting;
use App\Notifications\ElongacionReminderDatabaseNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ElongacionReminderService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly NotificationRecipientService $notificationRecipientService
    ) {
    }

    /**
     * @return array{
     *     date: string,
     *     pending_lines: int,
     *     recipients: int,
     *     simulated: int,
     *     sent: int,
     *     skipped: int,
     *     dry_run: bool,
     *     alerts: array<int, array{
     *         linea: string,
     *         linea_id: int|null,
     *         last_recorded_at: string,
     *         due_at: string,
     *         days_remaining: int,
     *         status: string
     *     }>,
     *     recipient_targets: array<int, array{
     *         recipient: string,
     *         line_count: int,
     *         lines: array<int, string>
     *     }>,
     *     failed: array<int, array{recipient: string, error: string}>
     * }
     */
    public function sendPendingAlerts(?CarbonImmutable $referenceTime = null, bool $dryRun = false): array
    {
        $timezone = $this->timezone();
        $now = ($referenceTime ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $today = $now->startOfDay();
        $pendingAlerts = $this->getPendingAlerts($today);
        $recipients = $this->resolveRecipients();

        $results = [
            'date' => $today->toDateString(),
            'pending_lines' => $pendingAlerts->count(),
            'recipients' => $recipients->count(),
            'simulated' => 0,
            'sent' => 0,
            'skipped' => 0,
            'dry_run' => $dryRun,
            'alerts' => $this->buildSnapshot($pendingAlerts),
            'recipient_targets' => [],
            'failed' => [],
        ];

        if ($pendingAlerts->isEmpty()) {
            Log::info('No hay recordatorios de elongacion pendientes para enviar.', [
                'date' => $results['date'],
                'recipients' => $results['recipients'],
                'dry_run' => $dryRun,
            ]);

            return $results;
        }

        if ($recipients->isEmpty()) {
            Log::warning('No hay destinatarios configurados para recordatorios de elongacion.', [
                'date' => $results['date'],
                'pending_lines' => $results['pending_lines'],
                'dry_run' => $dryRun,
            ]);

            return $results;
        }

        foreach ($recipients as $recipient) {
            $alertsForRecipient = $this->filterAlertsForRecipient($pendingAlerts, $recipient);

            if ($alertsForRecipient->isEmpty()) {
                continue;
            }

            $results['recipient_targets'][] = [
                'recipient' => $recipient['number'],
                'line_count' => $alertsForRecipient->count(),
                'lines' => $alertsForRecipient->pluck('linea')->values()->all(),
            ];

            if ($dryRun) {
                $results['simulated']++;
                continue;
            }

            $notification = ElongacionReminderNotification::query()
                ->whereDate('notification_date', $today->toDateString())
                ->where('recipient', $recipient['number'])
                ->where('channel', 'whatsapp')
                ->first()
                ?? new ElongacionReminderNotification([
                    'notification_date' => $today->toDateString(),
                    'recipient' => $recipient['number'],
                    'channel' => 'whatsapp',
                ]);

            if ($notification->exists && $notification->status === 'sent') {
                $results['skipped']++;
                continue;
            }

            $message = $this->buildMessage($alertsForRecipient);
            $this->markAsProcessing($notification, $today, $now, $recipient, $alertsForRecipient, $message);

            try {
                $response = $this->whatsAppService->sendMessage($recipient['number'], $message);

                if ($response->failed()) {
                    throw new RuntimeException(sprintf(
                        'UltraMsg respondio con HTTP %d: %s',
                        $response->status(),
                        $this->summarizeResponse($response)
                    ));
                }

                $this->markAsSent($notification, $now, $alertsForRecipient, $response);
                $results['sent']++;
            } catch (Throwable $exception) {
                $this->markAsFailed($notification, $now, $exception);
                $results['failed'][] = [
                    'recipient' => $recipient['number'],
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @return array{
     *     date: string,
     *     pending_lines: int,
     *     recipients: int,
     *     simulated: int,
     *     sent: int,
     *     skipped: int,
     *     dry_run: bool,
     *     alerts: array<int, array{
     *         linea: string,
     *         linea_id: int|null,
     *         last_recorded_at: string,
     *         due_at: string,
     *         days_remaining: int,
     *         status: string
     *     }>,
     *     failed: array<int, array{recipient: string, error: string}>
     * }
     */
    public function sendInternalNotifications(?CarbonImmutable $referenceTime = null, bool $dryRun = false): array
    {
        $timezone = $this->timezone();
        $now = ($referenceTime ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $today = $now->startOfDay();
        $pendingAlerts = $this->getPendingAlerts($today);
        $recipients = $this->notificationRecipientService->getInternalRecipients();

        $results = [
            'date' => $today->toDateString(),
            'pending_lines' => $pendingAlerts->count(),
            'recipients' => $recipients->count(),
            'simulated' => 0,
            'sent' => 0,
            'skipped' => 0,
            'dry_run' => $dryRun,
            'alerts' => $this->buildSnapshot($pendingAlerts),
            'failed' => [],
        ];

        if ($pendingAlerts->isEmpty() || $recipients->isEmpty()) {
            return $results;
        }

        foreach ($recipients as $recipient) {
            $alertsForRecipient = $this->notificationRecipientService
                ->filterAlertsForLinePreference($pendingAlerts, $recipient['line_ids']);

            if ($alertsForRecipient->isEmpty()) {
                continue;
            }

            if ($dryRun) {
                $results['simulated']++;
                continue;
            }

            $user = $recipient['user'];
            $uniqueKey = 'elongacion:' . $today->toDateString();

            $log = NotificationDispatchLog::query()->firstOrCreate(
                [
                    'type' => 'elongacion_reminder',
                    'notifiable_type' => $user::class,
                    'notifiable_id' => $user->getKey(),
                    'unique_key' => $uniqueKey,
                ],
                [
                    'context' => [
                        'lineas' => $alertsForRecipient->pluck('linea')->values()->all(),
                        'linea_ids' => $alertsForRecipient->pluck('linea_id')->filter()->values()->all(),
                    ],
                    'sent_at' => $now,
                ]
            );

            if (!$log->wasRecentlyCreated) {
                $results['skipped']++;
                continue;
            }

            try {
                $user->notify(new ElongacionReminderDatabaseNotification($alertsForRecipient));
                $results['sent']++;
            } catch (Throwable $exception) {
                $log->delete();
                $results['failed'][] = [
                    'recipient' => $user->email,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @return Collection<int, array{
     *     linea: string,
     *     linea_id: int|null,
     *     last_recorded_at: CarbonImmutable,
     *     due_at: CarbonImmutable,
     *     alert_starts_at: CarbonImmutable,
     *     days_remaining: int,
     *     status: string
     * }>
     */
    public function getPendingAlerts(?CarbonImmutable $referenceDate = null): Collection
    {
        $timezone = $this->timezone();
        $today = ($referenceDate ?? CarbonImmutable::now($timezone))->setTimezone($timezone)->startOfDay();
        $intervalMonths = $this->intervalMonths();
        $leadDays = $this->leadDays();
        $appTimezone = (string) config('app.timezone', 'UTC');

        $latestPerLine = Elongacion::query()
            ->select('linea', DB::raw('MAX(created_at) as last_recorded_at'))
            ->groupBy('linea');

        $rows = DB::query()
            ->fromSub($latestPerLine, 'latest_elongaciones')
            ->leftJoin('lineas', 'lineas.nombre', '=', 'latest_elongaciones.linea')
            ->select([
                'latest_elongaciones.linea',
                'latest_elongaciones.last_recorded_at',
                'lineas.id as linea_id',
            ])
            ->where(function ($query) {
                $query->whereNull('lineas.id')
                    ->orWhere(function ($innerQuery) {
                        $innerQuery->where('lineas.activo', true)
                            ->where('lineas.tipo', 'lavadora');
                    });
            })
            ->orderBy('latest_elongaciones.linea')
            ->get();

        return collect($rows)
            ->map(function ($row) use ($appTimezone, $intervalMonths, $leadDays, $timezone, $today) {
                $lastRecordedAt = CarbonImmutable::parse((string) $row->last_recorded_at, $appTimezone)
                    ->setTimezone($timezone);
                $dueAt = $lastRecordedAt->addMonthsNoOverflow($intervalMonths)->startOfDay();
                $daysRemaining = $today->diffInDays($dueAt, false);

                if ($daysRemaining > $leadDays) {
                    return null;
                }

                return [
                    'linea' => (string) $row->linea,
                    'linea_id' => $row->linea_id !== null ? (int) $row->linea_id : null,
                    'last_recorded_at' => $lastRecordedAt,
                    'due_at' => $dueAt,
                    'alert_starts_at' => $dueAt->subDays($leadDays),
                    'days_remaining' => $daysRemaining,
                    'status' => $this->resolveStatus($daysRemaining),
                ];
            })
            ->filter()
            ->sortBy([
                ['days_remaining', 'asc'],
                ['linea', 'asc'],
            ])
            ->values();
    }

    /**
     * @param  array{number: string, line_ids: array<int>|null, sources: array<int, string>}  $recipient
     * @param  Collection<int, array{linea: string, linea_id: int|null}>  $pendingAlerts
     * @return Collection<int, array<string, mixed>>
     */
    private function filterAlertsForRecipient(Collection $pendingAlerts, array $recipient): Collection
    {
        if ($recipient['line_ids'] === null) {
            return $pendingAlerts->values();
        }

        $allowedLineIds = $recipient['line_ids'];

        return $pendingAlerts
            ->filter(fn (array $alert): bool => $alert['linea_id'] !== null && in_array($alert['linea_id'], $allowedLineIds, true))
            ->values();
    }

    /**
     * @return Collection<int, array{number: string, line_ids: array<int>|null, sources: array<int, string>}>
     */
    private function resolveRecipients(): Collection
    {
        $recipients = collect();

        foreach ((array) config('elongacion-alerts.whatsapp_recipients', []) as $configuredRecipient) {
            $this->addRecipient($recipients, (string) $configuredRecipient, null, 'config');
        }

        $settings = UserNotificationSetting::query()
            ->where('whatsapp_notifications', true)
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->get();

        foreach ($settings as $setting) {
            $lineIds = null;

            if ($setting->notify_only_my_lines) {
                $lineIds = collect($setting->lines_to_notify ?? [])
                    ->map(static fn ($lineId): int => (int) $lineId)
                    ->filter(static fn (int $lineId): bool => $lineId > 0)
                    ->unique()
                    ->values()
                    ->all();
            }

            $this->addRecipient(
                $recipients,
                (string) $setting->whatsapp_number,
                $lineIds,
                'user_setting'
            );
        }

        return $recipients
            ->groupBy('number')
            ->map(function (Collection $group, string $number): array {
                $hasAllLines = $group->contains(
                    static fn (array $entry): bool => $entry['line_ids'] === null
                );

                return [
                    'number' => $number,
                    'line_ids' => $hasAllLines
                        ? null
                        : $group->pluck('line_ids')->filter()->flatten()->unique()->values()->all(),
                    'sources' => $group->pluck('source')->unique()->values()->all(),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{number: string, line_ids: array<int>|null, source: string}>  $recipients
     * @param  array<int>|null  $lineIds
     */
    private function addRecipient(Collection $recipients, string $number, ?array $lineIds, string $source): void
    {
        try {
            $recipients->push([
                'number' => $this->whatsAppService->normalizeNumber($number),
                'line_ids' => $lineIds,
                'source' => $source,
            ]);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Se omitio un destinatario de WhatsApp invalido.', [
                'source' => $source,
                'number' => $number,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     */
    private function buildMessage(Collection $alerts): string
    {
        $lines = $alerts->map(function (array $alert): string {
            return implode("\n", [
                sprintf('* %s', $alert['linea']),
                '  Ultimo registro: ' . $alert['last_recorded_at']->format('d/m/Y'),
                '  Proxima revision: ' . $alert['due_at']->format('d/m/Y'),
                '  Estado: ' . $this->formatRemainingTime((int) $alert['days_remaining']),
            ]);
        });

        return implode("\n", [
            '⚠️ Recordatorio de elongacion:',
            'Las siguientes lineas/lavadoras requieren nuevo registro de elongacion:',
            '',
            $lines->implode("\n\n"),
            '',
            'Favor de realizar y registrar la revision correspondiente.',
        ]);
    }

    /**
     * @param  array{number: string, line_ids: array<int>|null, sources: array<int, string>}  $recipient
     * @param  Collection<int, array<string, mixed>>  $alerts
     */
    private function markAsProcessing(
        ElongacionReminderNotification $notification,
        CarbonImmutable $today,
        CarbonImmutable $now,
        array $recipient,
        Collection $alerts,
        string $message
    ): void {
        $notification->fill([
            'notification_date' => $today->toDateString(),
            'recipient' => $recipient['number'],
            'channel' => 'whatsapp',
            'status' => 'processing',
            'message' => $message,
            'lines_snapshot' => $this->buildSnapshot($alerts),
            'metadata' => [
                'timezone' => $this->timezone(),
                'interval_months' => $this->intervalMonths(),
                'lead_days' => $this->leadDays(),
                'sources' => $recipient['sources'],
                'processed_at' => $now->toIso8601String(),
            ],
            'sent_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ]);

        $notification->save();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     */
    private function markAsSent(
        ElongacionReminderNotification $notification,
        CarbonImmutable $now,
        Collection $alerts,
        Response $response
    ): void {
        $metadata = $notification->metadata ?? [];
        $metadata['ultramsg_status'] = $response->status();
        $metadata['ultramsg_response'] = $this->extractResponsePayload($response);

        $notification->fill([
            'status' => 'sent',
            'sent_at' => $now,
            'failed_at' => null,
            'error_message' => null,
            'lines_snapshot' => $this->buildSnapshot($alerts),
            'metadata' => $metadata,
        ])->save();

        Log::info('Recordatorio de elongacion enviado por WhatsApp.', [
            'recipient' => $notification->recipient,
            'line_count' => $alerts->count(),
            'notification_date' => $notification->notification_date?->toDateString(),
        ]);
    }

    private function markAsFailed(
        ElongacionReminderNotification $notification,
        CarbonImmutable $now,
        Throwable $exception
    ): void {
        $metadata = $notification->metadata ?? [];
        $metadata['last_failure_at'] = $now->toIso8601String();

        $notification->fill([
            'status' => 'failed',
            'failed_at' => $now,
            'error_message' => $exception->getMessage(),
            'metadata' => $metadata,
        ])->save();

        Log::error('Fallo el envio del recordatorio de elongacion por WhatsApp.', [
            'recipient' => $notification->recipient,
            'notification_date' => $notification->notification_date?->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return array<int, array{
     *     linea: string,
     *     linea_id: int|null,
     *     last_recorded_at: string,
     *     due_at: string,
     *     days_remaining: int,
     *     status: string
     * }>
     */
    private function buildSnapshot(Collection $alerts): array
    {
        return $alerts->map(static function (array $alert): array {
            return [
                'linea' => $alert['linea'],
                'linea_id' => $alert['linea_id'],
                'last_recorded_at' => $alert['last_recorded_at']->toIso8601String(),
                'due_at' => $alert['due_at']->toDateString(),
                'days_remaining' => $alert['days_remaining'],
                'status' => $alert['status'],
            ];
        })->values()->all();
    }

    private function formatRemainingTime(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining > 1 => "faltan {$daysRemaining} dias",
            $daysRemaining === 1 => 'falta 1 dia',
            $daysRemaining === 0 => 'vence hoy',
            $daysRemaining === -1 => 'vencida por 1 dia',
            default => 'vencida por ' . abs($daysRemaining) . ' dias',
        };
    }

    private function resolveStatus(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining > 0 => 'upcoming',
            $daysRemaining === 0 => 'due_today',
            default => 'overdue',
        };
    }

    /**
     * @return array<string, mixed>|string
     */
    private function extractResponsePayload(Response $response): array|string
    {
        $decoded = $response->json();

        return is_array($decoded) ? $decoded : $this->summarizeResponse($response);
    }

    private function summarizeResponse(Response $response): string
    {
        $body = trim($response->body());

        if ($body === '') {
            return 'sin cuerpo de respuesta';
        }

        return mb_substr($body, 0, 500);
    }

    private function timezone(): string
    {
        return (string) config('elongacion-alerts.timezone', 'America/Mexico_City');
    }

    private function intervalMonths(): int
    {
        return max(1, (int) config('elongacion-alerts.interval_months', 2));
    }

    private function leadDays(): int
    {
        return max(0, (int) config('elongacion-alerts.lead_days', 3));
    }
}
