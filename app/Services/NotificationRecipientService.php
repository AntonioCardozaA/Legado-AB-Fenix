<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Support\Collection;

class NotificationRecipientService
{
    /**
     * @return Collection<int, array{user: User, line_ids: array<int>|null, settings: UserNotificationSetting|null}>
     */
    public function getInternalRecipients(?string $notificationType = null, ?string $recordType = null): Collection
    {
        return User::query()
            ->where('activo', true)
            ->with('notificationSettings')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $this->canReceiveInternalNotifications($user)
                && ($notificationType === null
                    || $this->canReceiveNotificationType($user, $notificationType, $recordType)))
            ->map(function (User $user): array {
                $settings = $user->notificationSettings;

                return [
                    'user' => $user,
                    'line_ids' => $this->resolveLineIds($settings),
                    'settings' => $settings,
                ];
            })
            ->values();
    }

    public function canReceiveInternalNotifications(User $user): bool
    {
        return $user->canUseCustomPermission('ver notificaciones');
    }

    public function canReceiveNotificationType(User $user, string $type, ?string $recordType = null): bool
    {
        $permission = match ($type) {
            'plan_accion', 'plan_accion_due', 'washer_ai_plan_pending_review' => 'ver planes accion',
            'elongacion_reminder', 'elongacion_change_alert', 'elongacion_purchase_alert', 'elongacion_status' => 'ver elongaciones',
            'historico_revisados', 'historico_revisados_alert', 'historico_revisados_due', 'historial_revisados', 'historial_revisados_alert' => 'ver historico revisados',
            'component_alert' => 'ver analisis lavadora',
            'admin_record_created' => $this->permissionForRecordType($recordType),
            default => null,
        };

        return $permission === null || $user->canUseCustomPermission($permission);
    }

    public function filterQueryForUser(User $user, mixed $query): mixed
    {
        if (!$this->canReceiveInternalNotifications($user)) {
            return $query->whereRaw('1 = 0');
        }

        $notificationTypes = [
            'plan_accion', 'plan_accion_due', 'washer_ai_plan_pending_review',
            'elongacion_reminder', 'elongacion_change_alert', 'elongacion_purchase_alert', 'elongacion_status',
            'historico_revisados', 'historico_revisados_alert', 'historico_revisados_due',
            'historial_revisados', 'historial_revisados_alert', 'component_alert',
        ];
        $deniedTypes = collect($notificationTypes)
            ->reject(fn (string $type): bool => $this->canReceiveNotificationType($user, $type))
            ->values()
            ->all();

        if ($deniedTypes !== []) {
            $query->where(function ($query) use ($deniedTypes): void {
                $query->whereNotIn('data->type', $deniedTypes)->orWhereNull('data->type');
            });
        }

        $recordTypes = [
            'analisis', 'analisis_lavadora', 'lavadora', 'analisis_etiquetadora', 'etiquetadora',
            'analisis_pasteurizadora', 'pasteurizadora', 'inspeccion_central_hidraulica',
            'plan_accion', 'registro_elongacion', 'reporte',
        ];
        $deniedRecordTypes = collect($recordTypes)
            ->reject(fn (string $recordType): bool => $this->canReceiveNotificationType($user, 'admin_record_created', $recordType))
            ->values()
            ->all();

        if ($deniedRecordTypes !== []) {
            $query->where(function ($query) use ($deniedRecordTypes): void {
                $query
                    ->where('data->type', '!=', 'admin_record_created')
                    ->orWhereNotIn('data->record_type', $deniedRecordTypes)
                    ->orWhereNull('data->record_type');
            });
        }

        return $query;
    }

    private function permissionForRecordType(?string $recordType): ?string
    {
        return match ($recordType) {
            'analisis', 'analisis_lavadora', 'lavadora' => 'ver analisis lavadora',
            'analisis_etiquetadora', 'etiquetadora' => 'ver analisis etiquetadora',
            'analisis_pasteurizadora', 'pasteurizadora', 'inspeccion_central_hidraulica' => User::PERMISSION_ACCESS_PASTEURIZADORA,
            'plan_accion' => 'ver planes accion',
            'registro_elongacion' => 'ver elongaciones',
            'reporte' => 'ver reportes',
            default => 'ver notificaciones',
        };
    }

    /**
     * @param  Collection<int, array{linea_id: int|null}>  $alerts
     * @param  array<int>|null  $lineIds
     * @return Collection<int, array<string, mixed>>
     */
    public function filterAlertsForLinePreference(Collection $alerts, ?array $lineIds): Collection
    {
        if ($lineIds === null) {
            return $alerts->values();
        }

        return $alerts
            ->filter(static fn (array $alert): bool => $alert['linea_id'] !== null
                && in_array((int) $alert['linea_id'], $lineIds, true))
            ->values();
    }

    public function shouldNotifyForLine(?UserNotificationSetting $settings, ?int $lineaId): bool
    {
        if ($settings === null || !$settings->notify_only_my_lines) {
            return true;
        }

        if ($lineaId === null) {
            return false;
        }

        return in_array($lineaId, $this->resolveLineIds($settings) ?? [], true);
    }

    /**
     * @return array<int>|null
     */
    private function resolveLineIds(?UserNotificationSetting $settings): ?array
    {
        if ($settings === null || !$settings->notify_only_my_lines) {
            return null;
        }

        $lineIds = collect($settings->lines_to_notify ?? [])
            ->map(static fn ($lineId): int => (int) $lineId)
            ->filter(static fn (int $lineId): bool => $lineId > 0)
            ->unique()
            ->values()
            ->all();

        return $lineIds === [] ? [] : $lineIds;
    }
}
