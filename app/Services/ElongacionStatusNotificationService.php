<?php

namespace App\Services;

use App\Models\Elongacion;
use App\Models\NotificationDispatchLog;
use App\Notifications\ElongacionStatusDatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ElongacionStatusNotificationService
{
    public function __construct(
        private readonly NotificationRecipientService $notificationRecipientService
    ) {
    }

    public function notifyForRecord(Elongacion $elongacion): int
    {
        $result = $this->dispatchForRecord($elongacion, false);

        return $result['sent'];
    }

    /**
     * @return array{
     *     affected_records: int,
     *     recipients: int,
     *     simulated: int,
     *     sent: int,
     *     skipped: int,
     *     failed: int,
     *     records: array<int, array{
     *         elongacion_id: int,
     *         linea: string,
     *         status_type: string,
     *         bombas: float,
     *         vapor: float
     *     }>
     * }
     */
    public function notifyForLatestRecords(bool $dryRun = false, ?string $linea = null): array
    {
        $records = $this->getLatestStatusRecords($linea);
        $results = [
            'affected_records' => $records->count(),
            'recipients' => 0,
            'simulated' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'records' => $records->map(function (Elongacion $elongacion): array {
                return [
                    'elongacion_id' => (int) $elongacion->id,
                    'linea' => (string) $elongacion->linea,
                    'status_type' => (string) $this->resolveStatusType($elongacion),
                    'bombas' => round((float) $elongacion->bombas_porcentaje, 2),
                    'vapor' => round((float) $elongacion->vapor_porcentaje, 2),
                ];
            })->values()->all(),
        ];

        foreach ($records as $elongacion) {
            $recordResult = $this->dispatchForRecord($elongacion, $dryRun);
            $results['recipients'] += $recordResult['recipients'];
            $results['simulated'] += $recordResult['simulated'];
            $results['sent'] += $recordResult['sent'];
            $results['skipped'] += $recordResult['skipped'];
            $results['failed'] += $recordResult['failed'];
        }

        return $results;
    }

    /**
     * @return Collection<int, Elongacion>
     */
    private function getLatestStatusRecords(?string $linea = null): Collection
    {
        $latestIds = Elongacion::query()
            ->selectRaw('MAX(id) as id')
            ->when($linea, static fn ($query, string $lineaFiltrada) => $query->where('linea', $lineaFiltrada))
            ->groupBy('linea');

        return Elongacion::query()
            ->with('cadenaCiclo')
            ->whereIn('id', $latestIds)
            ->orderBy('linea')
            ->get()
            ->filter(fn (Elongacion $elongacion): bool => $this->resolveStatusType($elongacion) !== null)
            ->values();
    }

    /**
     * @return array{recipients: int, simulated: int, sent: int, skipped: int, failed: int}
     */
    private function dispatchForRecord(Elongacion $elongacion, bool $dryRun): array
    {
        $statusType = $this->resolveStatusType($elongacion);

        if ($statusType === null) {
            return [
                'recipients' => 0,
                'simulated' => 0,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $elongacion->loadMissing('cadenaCiclo');
        $recipients = $this->notificationRecipientService->getInternalRecipients();
        $result = [
            'recipients' => 0,
            'simulated' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($recipients as $recipient) {
            $user = $recipient['user'];

            if (!$this->notificationRecipientService->shouldNotifyForLine(
                $recipient['settings'],
                $elongacion->linea_id !== null ? (int) $elongacion->linea_id : null
            )) {
                continue;
            }

            $result['recipients']++;

            if ($dryRun) {
                $result['simulated']++;
                continue;
            }

            $log = NotificationDispatchLog::query()->firstOrCreate(
                [
                    'type' => 'elongacion_status',
                    'notifiable_type' => $user::class,
                    'notifiable_id' => $user->getKey(),
                    'unique_key' => sprintf('elongacion-status:%d:%s', $elongacion->id, $statusType),
                ],
                [
                    'context' => [
                        'elongacion_id' => $elongacion->id,
                        'linea' => $elongacion->linea,
                        'linea_id' => $elongacion->linea_id,
                        'status_type' => $statusType,
                    ],
                    'sent_at' => now(),
                ]
            );

            if (!$log->wasRecentlyCreated) {
                $result['skipped']++;
                continue;
            }

            try {
                $user->notify(new ElongacionStatusDatabaseNotification($elongacion, $statusType));
                $result['sent']++;
            } catch (\Throwable $exception) {
                $log->delete();
                $result['failed']++;

                Log::error('No se pudo crear la notificacion interna de elongacion.', [
                    'elongacion_id' => $elongacion->id,
                    'user_id' => $user->getKey(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    private function resolveStatusType(Elongacion $elongacion): ?string
    {
        if ($elongacion->requiere_cambio) {
            return 'cambio';
        }

        if ($elongacion->requiere_compra) {
            return 'compra';
        }

        return null;
    }
}
