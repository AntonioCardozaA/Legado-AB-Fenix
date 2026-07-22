<?php

namespace App\Services;

use App\Models\Analisis;
use App\Models\AnalisisEtiquetadora;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

class NotificationRedirectService
{
    /**
     * @return array{url: string|null, message: string|null}
     */
    public function resolve(User $user, DatabaseNotification $notification): array
    {
        $planActionTarget = $this->planActionTargetFromNotification($user, $notification->data ?? []);

        if ($planActionTarget !== null) {
            return $planActionTarget;
        }

        $fallbackUrl = $this->fallbackUrl($notification);
        $target = $this->analysisTargetFromNotification($notification);

        if ($target === null) {
            return [
                'url' => $fallbackUrl,
                'message' => null,
            ];
        }

        if (!$this->shouldResolveAnalysisTarget($user) && filled($fallbackUrl)) {
            return [
                'url' => $fallbackUrl,
                'message' => null,
            ];
        }

        $record = $this->findRecord($target['class'], $target['id']);

        if (!$record) {
            return [
                'url' => null,
                'message' => 'El analisis relacionado ya no esta disponible.',
            ];
        }

        if (!$this->canAccessRecord($user, $record)) {
            return [
                'url' => null,
                'message' => 'No cuentas con autorizacion para visualizar este contenido.',
            ];
        }

        return [
            'url' => $this->shouldResolveAnalysisTarget($user)
                ? $this->urlForRecord($record)
                : ($this->directUrlForRecord($record) ?? $this->urlForRecord($record)),
            'message' => null,
        ];
    }

    private function shouldResolveAnalysisTarget(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_GERENTE_MANTENIMIENTO,
            User::ROLE_TECNICO,
            User::ROLE_SUPERVISOR,
        ]);
    }

    /**
     * @return array{class: class-string<Model>, id: int|string}|null
     */
    private function analysisTargetFromNotification(DatabaseNotification $notification): ?array
    {
        $data = $notification->data ?? [];
        $explicitClass = $data['record_class'] ?? $data['analysis_model'] ?? null;
        $explicitId = $data['record_id'] ?? $data['analysis_id'] ?? $data['analisis_id'] ?? null;

        if ($explicitClass && $explicitId && $this->isAnalysisClass((string) $explicitClass)) {
            return [
                'class' => (string) $explicitClass,
                'id' => $explicitId,
            ];
        }

        foreach ($this->specificIdMap() as $key => $class) {
            if (!empty($data[$key])) {
                return [
                    'class' => $class,
                    'id' => $data[$key],
                ];
            }
        }

        $class = $this->classFromRecordType($data['record_type'] ?? $data['analysis_type'] ?? null);

        if ($class && $explicitId) {
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
    private function specificIdMap(): array
    {
        return [
            'analisis_general_id' => Analisis::class,
            'analisis_etiquetadora_id' => AnalisisEtiquetadora::class,
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
            'etiquetadora',
            'analisis_etiquetadora' => AnalisisEtiquetadora::class,
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
            AnalisisEtiquetadora::class,
            AnalisisLavadora::class,
            AnalisisPasteurizadora::class,
        ], true);
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function findRecord(string $class, int|string $id): ?Model
    {
        return $class::query()
            ->withoutGlobalScopes()
            ->find($id);
    }

    private function canAccessRecord(User $user, Model $record): bool
    {
        if ($record instanceof AnalisisLavadora) {
            return $user->canAccessModule(User::MODULE_LAVADORA);
        }

        if ($record instanceof AnalisisEtiquetadora) {
            return $user->canAccessModule(User::MODULE_ETIQUETADORA);
        }

        if ($record instanceof AnalisisPasteurizadora) {
            return $user->canAccessPasteurizadoraArea(
                AnalisisPasteurizadora::normalizarArea($record->area)
            );
        }

        if ($record instanceof Analisis) {
            return !$user->usesTechnicianAccessProfile();
        }

        return false;
    }

    private function urlForRecord(Model $record): ?string
    {
        if ($record instanceof Analisis) {
            return $this->routeIfExists('analisis.index', [
                'open_analysis_id' => $record->getKey(),
            ]);
        }

        if ($record instanceof AnalisisLavadora) {
            return $this->routeIfExists('analisis-lavadora.index', array_filter([
                'linea_id' => $record->linea_id,
                'open_analysis_id' => $record->getKey(),
            ], fn ($value) => filled($value)));
        }

        if ($record instanceof AnalisisEtiquetadora) {
            return $this->routeIfExists('analisis-etiquetadora.index', array_filter([
                'linea_id' => $record->linea_id,
                'maquina' => $record->maquina,
                'open_analysis_id' => $record->getKey(),
            ], fn ($value) => filled($value)));
        }

        if ($record instanceof AnalisisPasteurizadora) {
            $area = AnalisisPasteurizadora::normalizarArea($record->area);
            $route = $area === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                ? 'pasteurizadora.central-hidraulica.index'
                : 'pasteurizadora.analisis-pasteurizadora.index';

            return $this->routeIfExists($route, array_filter([
                'linea_id' => $record->linea_id,
                'modulo' => $record->modulo,
                'componente' => $record->componente,
                'open_analysis_id' => $record->getKey(),
            ], fn ($value) => filled($value)));
        }

        return null;
    }

    private function directUrlForRecord(Model $record): ?string
    {
        if ($record instanceof Analisis) {
            return $this->routeIfExists('analisis.show', [
                'analisis' => $record->getKey(),
            ]);
        }

        if ($record instanceof AnalisisLavadora) {
            return $this->routeIfExists('analisis-lavadora.show', [
                'analisislavadora' => $record->getKey(),
            ]);
        }

        if ($record instanceof AnalisisEtiquetadora) {
            return $this->routeIfExists('analisis-etiquetadora.show', [
                'analisisetiquetadora' => $record->getKey(),
            ]);
        }

        if ($record instanceof AnalisisPasteurizadora) {
            $route = $record->area === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                ? 'pasteurizadora.central-hidraulica.show'
                : 'pasteurizadora.analisis-pasteurizadora.show';

            return $this->routeIfExists($route, [
                'analisispasteurizadora' => $record->getKey(),
            ]);
        }

        return null;
    }

    private function fallbackUrl(DatabaseNotification $notification): ?string
    {
        $data = $notification->data ?? [];
        $url = $data['url'] ?? null;

        if (blank($url)) {
            return null;
        }

        $normalizedUrl = $this->normalizeInternalUrl((string) $url);

        return $this->isAuthenticationUrl($normalizedUrl) ? null : $normalizedUrl;
    }

    private function routeIfExists(string $name, array $parameters = []): ?string
    {
        if (!Route::has($name)) {
            return null;
        }

        return route($name, $parameters, false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * @param  array<string, mixed>  $data
     * @return array{url: string|null, message: string|null}|null
     */
    private function planActionTargetFromNotification(User $user, array $data): ?array
    {
        $planId = $this->planActionIdFromNotification($data);

        if (blank($planId)) {
            return null;
        }

        $plan = PlanAccion::query()
            ->with('linea')
            ->find($planId);

        if (!$plan) {
            return [
                'url' => null,
                'message' => 'El plan de accion relacionado ya no esta disponible.',
            ];
        }

        $tipoEquipo = $this->resolvePlanActionEquipmentType($data, $planId, $plan);

        if (!$this->canAccessPlanAction($user, $plan, $tipoEquipo)) {
            return [
                'url' => null,
                'message' => 'No cuentas con autorizacion para visualizar este contenido.',
            ];
        }

        if (($data['type'] ?? null) === 'washer_ai_plan_pending_review') {
            if (!$user->canReviewWasherAiPlans()) {
                return [
                    'url' => null,
                    'message' => 'No cuentas con autorizacion para revisar sugerencias generadas por IA.',
                ];
            }

            return [
                'url' => $this->routeIfExists('plan-accion.ai.review', [
                    'planAccion' => $planId,
                ]),
                'message' => null,
            ];
        }

        $parameters = array_filter([
            'tipo' => $tipoEquipo,
            'linea_id' => $plan->linea_id ?? $data['linea_id'] ?? null,
            'open_plan_id' => $planId,
        ], fn ($value) => filled($value));

        return [
            'url' => $this->routeIfExists('plan-accion.index', $parameters),
            'message' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function planActionIdFromNotification(array $data): mixed
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

    private function canAccessPlanAction(User $user, PlanAccion $plan, ?string $tipoEquipo): bool
    {
        $tipoEquipo = $tipoEquipo ?: $this->equipmentTypeFromPlan($plan);

        return $tipoEquipo !== null && $user->canViewPlanActionType($tipoEquipo);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePlanActionEquipmentType(array $data, int|string $planId, ?PlanAccion $plan = null): ?string
    {
        $plan ??= PlanAccion::query()
            ->with('linea')
            ->find($planId);

        $planType = $this->equipmentTypeFromPlan($plan);

        if ($planType) {
            return $planType;
        }

        $explicitType = $this->validEquipmentType($data['tipo_equipo'] ?? null);

        if ($explicitType) {
            return $explicitType;
        }

        if (!empty($data['area_pasteurizadora'])) {
            return User::MODULE_PASTEURIZADORA;
        }

        $urlType = $this->equipmentTypeFromUrl($data['url'] ?? null);

        if ($urlType) {
            return $urlType;
        }

        return null;
    }

    private function equipmentTypeFromUrl(mixed $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $query = parse_url((string) $url, PHP_URL_QUERY);

        if (!$query) {
            return null;
        }

        parse_str($query, $parameters);

        return $this->validEquipmentType($parameters['tipo'] ?? null);
    }

    private function validEquipmentType(mixed $type): ?string
    {
        $type = strtolower(trim((string) $type));

        if (str_contains($type, User::MODULE_PASTEURIZADORA)) {
            return User::MODULE_PASTEURIZADORA;
        }

        if (str_contains($type, User::MODULE_LAVADORA)) {
            return User::MODULE_LAVADORA;
        }

        if (str_contains($type, User::MODULE_ETIQUETADORA)) {
            return User::MODULE_ETIQUETADORA;
        }

        return null;
    }

    private function equipmentTypeFromPlan(?PlanAccion $plan): ?string
    {
        if (!$plan) {
            return null;
        }

        $plan->loadMissing('linea');

        return $this->equipmentTypeFromLineName($plan->linea?->nombre)
            ?? $this->validEquipmentType($plan->linea?->tipo)
            ?? $this->validEquipmentType($plan->tipo_equipo);
    }

    private function equipmentTypeFromLineName(?string $lineName): ?string
    {
        $lineName = strtoupper(trim((string) $lineName));

        if (str_starts_with($lineName, 'P-')) {
            return User::MODULE_PASTEURIZADORA;
        }

        if (str_starts_with($lineName, 'L-')) {
            return User::MODULE_LAVADORA;
        }

        return null;
    }

    private function normalizeInternalUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return $url;
        }

        $internalHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()?->getHost(),
            'localhost',
            '127.0.0.1',
        ]);

        if (!in_array(strtolower($host), array_map('strtolower', $internalHosts), true)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? $path . '?' . $query : $path;
    }

    private function isAuthenticationUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';
        $loginPath = Route::has('login')
            ? rtrim(route('login', [], false), '/')
            : '/login';

        return $path === ($loginPath ?: '/login');
    }
}
