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
    public function getInternalRecipients(): Collection
    {
        return User::query()
            ->where('activo', true)
            ->with('notificationSettings')
            ->orderBy('name')
            ->get()
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
