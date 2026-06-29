<?php

namespace App\Services;

use App\Models\Analisis;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationVisibilityService
{
    public const TYPE_COMPONENT_ALERT = 'component_alert';

    private const TYPE_ADMIN_RECORD_CREATED = 'admin_record_created';
    private const TYPE_ADMIN_ANALYSIS_DELETED = 'admin_analysis_deleted';

    private const ANALYSIS_RECORD_TYPES = [
        'analisis',
        'analisis_general',
        'analisis_lavadora',
        'analisis_pasteurizadora',
        'inspeccion_central_hidraulica',
        'lavadora',
        'pasteurizadora',
    ];

    private const TECHNICAL_OPERATIONAL_TYPES = [
        self::TYPE_COMPONENT_ALERT,
        'elongacion_reminder',
        'elongacion_change_alert',
        'elongacion_purchase_alert',
        'plan_accion_due',
        'historico_revisados',
        'historico_revisados_alert',
        'historico_revisados_due',
        'historial_revisados',
        'historial_revisados_alert',
    ];

    private const TECHNICAL_ADMIN_RECORD_TYPES = [
        'plan_accion',
        'registro_elongacion',
    ];

    public function notificationsFor(User $user): mixed
    {
        return $this->visibleFor($user, $user->notifications());
    }

    public function unreadNotificationsFor(User $user): mixed
    {
        return $this->visibleFor($user, $user->unreadNotifications());
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function availableNotificationsFor(User $user): Collection
    {
        return $this->filterExistingTargets(
            $this->notificationsFor($user)
                ->latest()
                ->get()
        );
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function availableUnreadNotificationsFor(User $user): Collection
    {
        return $this->filterExistingTargets(
            $this->unreadNotificationsFor($user)
                ->latest()
                ->get()
        );
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function availableNotificationItemsFor(User $user, int $limit = 10): Collection
    {
        return $this->availableNotificationsFor($user)
            ->take($limit)
            ->values();
    }

    public function availableNotificationsCountFor(User $user): int
    {
        return $this->availableNotificationsFor($user)->count();
    }

    public function availableUnreadNotificationsCountFor(User $user): int
    {
        return $this->availableUnreadNotificationsFor($user)->count();
    }

    public function visibleFor(User $user, mixed $query): mixed
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return $query;
        }

        if ($user->hasRole(User::ROLE_GERENTE_MANTENIMIENTO)) {
            return $this->withoutAnalysisAuditNotifications($query);
        }

        if ($user->hasAnyRole([
            ...User::technicianEquivalentRoles(),
            ...User::supervisorEquivalentRoles(),
        ])) {
            return $this->onlyTechnicalOperationalNotifications($query);
        }

        return $this->withoutAnalysisAuditNotifications($query);
    }

    private function withoutAnalysisAuditNotifications(mixed $query): mixed
    {
        return $query
            ->where(function ($query): void {
                $query
                    ->where('data->type', '!=', self::TYPE_ADMIN_ANALYSIS_DELETED)
                    ->orWhereNull('data->type');
            })
            ->where(function ($query): void {
                $query
                    ->where('data->type', '!=', self::TYPE_ADMIN_RECORD_CREATED)
                    ->orWhereNull('data->type')
                    ->orWhereNotIn('data->record_type', self::ANALYSIS_RECORD_TYPES)
                    ->orWhereNull('data->record_type');
            });
    }

    private function onlyTechnicalOperationalNotifications(mixed $query): mixed
    {
        return $query->where(function ($query): void {
            $query
                ->whereIn('data->type', self::TECHNICAL_OPERATIONAL_TYPES)
                ->orWhere(function ($query): void {
                    $query
                        ->where('data->type', self::TYPE_ADMIN_RECORD_CREATED)
                        ->whereIn('data->record_type', self::TECHNICAL_ADMIN_RECORD_TYPES);
                });
        });
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return Collection<int, DatabaseNotification>
     */
    private function filterExistingTargets(Collection $notifications): Collection
    {
        return $notifications
            ->filter(fn (DatabaseNotification $notification): bool => $this->notificationTargetExists($notification))
            ->values();
    }

    private function notificationTargetExists(DatabaseNotification $notification): bool
    {
        $data = $notification->data ?? [];

        $planId = $this->planActionIdFromData($data);

        if (filled($planId)) {
            return PlanAccion::query()
                ->withoutGlobalScopes()
                ->whereKey($planId)
                ->exists();
        }

        $target = $this->analysisTargetFromData($data);

        if (!$target) {
            return true;
        }

        /** @var class-string<Model> $class */
        $class = $target['class'];

        return $class::query()
            ->withoutGlobalScopes()
            ->whereKey($target['id'])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function planActionIdFromData(array $data): mixed
    {
        $planId = $data['plan_id'] ?? $data['plan_accion_id'] ?? null;

        if (filled($planId)) {
            return $planId;
        }

        $recordClass = $data['record_class'] ?? null;
        $recordType = $data['record_type'] ?? null;

        if (
            filled($data['record_id'] ?? null)
            && (
                $recordClass === PlanAccion::class
                || $recordType === 'plan_accion'
            )
        ) {
            return $data['record_id'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{class: class-string<Model>, id: mixed}|null
     */
    private function analysisTargetFromData(array $data): ?array
    {
        $explicitClass = $data['record_class'] ?? $data['analysis_model'] ?? null;
        $explicitId = $data['record_id'] ?? $data['analysis_id'] ?? $data['analisis_id'] ?? null;

        if ($explicitClass && filled($explicitId) && $this->isAnalysisClass((string) $explicitClass)) {
            return [
                'class' => (string) $explicitClass,
                'id' => $explicitId,
            ];
        }

        foreach ($this->specificAnalysisIdMap() as $key => $class) {
            if (filled($data[$key] ?? null)) {
                return [
                    'class' => $class,
                    'id' => $data[$key],
                ];
            }
        }

        $class = $this->classFromRecordType($data['record_type'] ?? $data['analysis_type'] ?? null);

        if ($class && filled($explicitId)) {
            return [
                'class' => $class,
                'id' => $explicitId,
            ];
        }

        return null;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private function specificAnalysisIdMap(): array
    {
        return [
            'analisis_general_id' => Analisis::class,
            'analisis_lavadora_id' => AnalisisLavadora::class,
            'analisis_pasteurizadora_id' => AnalisisPasteurizadora::class,
        ];
    }

    /**
     * @return class-string<Model>|null
     */
    private function classFromRecordType(mixed $recordType): ?string
    {
        return match ((string) $recordType) {
            'analisis',
            'analisis_general' => Analisis::class,
            'lavadora',
            'analisis_lavadora' => AnalisisLavadora::class,
            'pasteurizadora',
            'analisis_pasteurizadora',
            'inspeccion_central_hidraulica' => AnalisisPasteurizadora::class,
            default => null,
        };
    }

    private function isAnalysisClass(string $class): bool
    {
        return in_array($class, [
            Analisis::class,
            AnalisisLavadora::class,
            AnalisisPasteurizadora::class,
        ], true);
    }
}
