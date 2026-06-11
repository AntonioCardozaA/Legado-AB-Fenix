<?php

namespace App\Services;

use App\Models\NotificationDispatchLog;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\FechaProximaNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function __construct(
        private readonly NotificationRecipientService $notificationRecipientService
    ) {
    }

    /**
     * @return array{
     *     date: string,
     *     plans_evaluated: int,
     *     alerts: array<int, array{
     *         plan_id: int,
     *         actividad: string|null,
     *         linea: string,
     *         linea_id: int|null,
     *         pcm: string,
     *         fecha_limite: string,
     *         dias_restantes: int,
     *         prioridad: string
     *     }>,
     *     recipients: int,
     *     simulated: int,
     *     sent: int,
     *     skipped: int,
     *     dry_run: bool,
     *     total: int,
     *     errores: array<int, string>
     * }
     */
    public function verificarYNotificarActividadesProximas(
        ?CarbonImmutable $referenceTime = null,
        bool $dryRun = false
    ): array {
        $timezone = $this->timezone();
        $now = ($referenceTime ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $today = $now->startOfDay();
        $plans = PlanAccion::query()
            ->with('linea')
            ->orderBy('id')
            ->get()
            ->reject(fn (PlanAccion $plan): bool => $this->isClosedPlan($plan))
            ->values();
        $alerts = $this->buildPlanAlerts($plans, $today);
        $recipients = $this->notificationRecipientService->getInternalRecipients();
        $results = $this->dispatchAlerts($alerts, $recipients, $now, $dryRun);

        return [
            'date' => $today->toDateString(),
            'plans_evaluated' => $plans->count(),
            'alerts' => $this->buildAlertSnapshot($alerts),
            'recipients' => $recipients->count(),
            'simulated' => $results['simulated'],
            'sent' => $results['sent'],
            'skipped' => $results['skipped'],
            'dry_run' => $dryRun,
            'total' => $results['sent'],
            'errores' => $results['errors'],
        ];
    }

    /**
     * @return array{enviadas: int, mensaje: string, pcm_notificados: array<int, array<string, mixed>>, errores: array<int, string>}
     */
    public function enviarNotificacionesManuales($planId, $userId = null): array
    {
        $plan = PlanAccion::query()
            ->with('linea')
            ->findOrFail($planId);

        $timezone = $this->timezone();
        $now = CarbonImmutable::now($timezone);
        $alerts = $this->buildPlanAlerts(collect([$plan]), $now->startOfDay());

        $recipients = $this->notificationRecipientService->getInternalRecipients();

        if ($userId !== null) {
            $targetUser = User::query()->findOrFail($userId);

            $recipients = $recipients
                ->filter(static fn (array $recipient): bool => $recipient['user']->is($targetUser))
                ->values();
        }

        $results = $this->dispatchAlerts($alerts, $recipients, $now, false, true);
        $pcmNotificados = $this->buildAlertSnapshot($alerts);

        return [
            'enviadas' => $results['sent'],
            'mensaje' => "Se enviaron {$results['sent']} notificaciones internas.",
            'pcm_notificados' => $pcmNotificados,
            'errores' => $results['errors'],
        ];
    }

    /**
     * @param  Collection<int, PlanAccion>  $plans
     * @return Collection<int, array{
     *     plan: PlanAccion,
     *     plan_id: int,
     *     actividad: string|null,
     *     linea: string,
     *     linea_id: int|null,
     *     pcm_key: string,
     *     pcm: string,
     *     due_date: CarbonImmutable,
     *     days_remaining: int,
     *     prioridad: string
     * }>
     */
    private function buildPlanAlerts(Collection $plans, CarbonImmutable $today): Collection
    {
        return $plans
            ->flatMap(function (PlanAccion $plan) use ($today): array {
                $alerts = [];

                foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcmKey) {
                    $fecha = $plan->{'fecha_' . $pcmKey};

                    if ($fecha === null) {
                        continue;
                    }

                    $dueDate = CarbonImmutable::instance($fecha)->startOfDay();
                    $daysRemaining = $today->diffInDays($dueDate, false);

                    if ($daysRemaining < 0) {
                        continue;
                    }

                    $alerts[] = [
                        'plan' => $plan,
                        'plan_id' => (int) $plan->id,
                        'actividad' => $plan->actividad,
                        'linea' => $plan->linea?->nombre ?? 'Linea sin asignar',
                        'linea_id' => $plan->linea_id !== null ? (int) $plan->linea_id : null,
                        'pcm_key' => $pcmKey,
                        'pcm' => strtoupper($pcmKey),
                        'due_date' => $dueDate,
                        'days_remaining' => $daysRemaining,
                        'prioridad' => $this->resolvePriority($daysRemaining),
                    ];
                }

                return $alerts;
            })
            ->sortBy([
                ['days_remaining', 'asc'],
                ['linea', 'asc'],
                ['plan_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @param  Collection<int, array{user: User, line_ids: array<int>|null, settings: UserNotificationSetting|null}>  $recipients
     * @return array{sent: int, skipped: int, simulated: int, errors: array<int, string>}
     */
    private function dispatchAlerts(
        Collection $alerts,
        Collection $recipients,
        CarbonImmutable $now,
        bool $dryRun = false,
        bool $force = false
    ): array {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'simulated' => 0,
            'errors' => [],
        ];

        foreach ($recipients as $recipient) {
            /** @var User $user */
            $user = $recipient['user'];
            /** @var UserNotificationSetting|null $settings */
            $settings = $recipient['settings'];
            $leadDays = max(1, (int) ($settings?->days_before_notification ?? 3));
            $userAlerts = $this->notificationRecipientService
                ->filterAlertsForLinePreference($alerts, $recipient['line_ids'])
                ->filter(function (array $alert) use ($leadDays, $settings): bool {
                    if ($alert['days_remaining'] > $leadDays) {
                        return false;
                    }

                    if ($settings && !$settings->shouldNotifyForPCM($alert['pcm_key'])) {
                        return false;
                    }

                    return true;
                })
                ->values();

            if ($userAlerts->isEmpty()) {
                continue;
            }

            foreach ($userAlerts as $alert) {
                if ($dryRun) {
                    $results['simulated']++;
                    continue;
                }

                $log = null;

                if (!$force) {
                    $log = NotificationDispatchLog::query()->firstOrCreate(
                        [
                            'type' => 'plan_accion_due',
                            'notifiable_type' => $user::class,
                            'notifiable_id' => $user->getKey(),
                            'unique_key' => $this->buildPlanUniqueKey($alert),
                        ],
                        [
                            'context' => [
                                'plan_id' => $alert['plan_id'],
                                'pcm' => $alert['pcm'],
                                'linea_id' => $alert['linea_id'],
                                'fecha_limite' => $alert['due_date']->toDateString(),
                            ],
                            'sent_at' => $now,
                        ]
                    );

                    if (!$log->wasRecentlyCreated) {
                        $results['skipped']++;
                        continue;
                    }
                }

                try {
                    Notification::sendNow(
                        $user,
                        new FechaProximaNotification(
                            $alert['plan'],
                            $alert['pcm_key'],
                            $alert['days_remaining']
                        ),
                        ['database']
                    );

                    $results['sent']++;
                } catch (\Throwable $exception) {
                    if ($log !== null) {
                        $log->delete();
                    }

                    $message = sprintf(
                        'No se pudo crear la notificacion interna del plan %d para %s: %s',
                        $alert['plan_id'],
                        $user->email,
                        $exception->getMessage()
                    );

                    $results['errors'][] = $message;

                    Log::error($message, [
                        'plan_id' => $alert['plan_id'],
                        'user_id' => $user->getKey(),
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return array<int, array{
     *     plan_id: int,
     *     actividad: string|null,
     *     linea: string,
     *     linea_id: int|null,
     *     pcm: string,
     *     fecha_limite: string,
     *     dias_restantes: int,
     *     prioridad: string
     * }>
     */
    private function buildAlertSnapshot(Collection $alerts): array
    {
        return $alerts->map(static function (array $alert): array {
            return [
                'plan_id' => $alert['plan_id'],
                'actividad' => $alert['actividad'],
                'linea' => $alert['linea'],
                'linea_id' => $alert['linea_id'],
                'pcm' => $alert['pcm'],
                'fecha_limite' => $alert['due_date']->toDateString(),
                'dias_restantes' => $alert['days_remaining'],
                'prioridad' => $alert['prioridad'],
            ];
        })->all();
    }

    /**
     * @param  array{plan_id: int, pcm_key: string, due_date: CarbonImmutable}  $alert
     */
    private function buildPlanUniqueKey(array $alert): string
    {
        return sprintf(
            'plan-accion:%d:%s:%s',
            $alert['plan_id'],
            $alert['pcm_key'],
            $alert['due_date']->toDateString()
        );
    }

    private function resolvePriority(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining <= 1 => 'alta',
            $daysRemaining <= 3 => 'media',
            default => 'baja',
        };
    }

    private function isClosedPlan(PlanAccion $plan): bool
    {
        if ($plan->completado) {
            return true;
        }

        $estado = trim(mb_strtolower((string) ($plan->estado ?? '')));

        if ($estado === '') {
            return false;
        }

        return in_array($estado, [
            'cerrado',
            'cerrada',
            'closed',
            'completado',
            'completada',
            'finalizado',
            'finalizada',
            'done',
        ], true);
    }

    private function timezone(): string
    {
        return (string) config('elongacion-alerts.timezone', 'America/Mexico_City');
    }
}
