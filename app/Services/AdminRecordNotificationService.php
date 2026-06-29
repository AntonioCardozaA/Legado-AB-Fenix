<?php

namespace App\Services;

use App\Models\Analisis;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\AnalysisDeletionLog;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Notifications\AdminRegistroCreadoNotification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Throwable;

class AdminRecordNotificationService
{
    /**
     * @var array<int, class-string<Model>>
     */
    private array $watchedModels = [
        Analisis::class,
        AnalisisLavadora::class,
        AnalisisPasteurizadora::class,
        Elongacion::class,
        PlanAccion::class,
    ];

    public function registerModelEvents(): void
    {
        foreach ($this->watchedModels as $modelClass) {
            $modelClass::created(function (Model $record): void {
                $this->notifyCreated($record);
            });
        }
    }

    public function notifyCreated(Model $record): int
    {
        try {
            $payload = $this->buildPayloadForRecord($record);

            return $this->sendToAdmins($payload);
        } catch (Throwable $exception) {
            Log::warning('No se pudo crear la notificacion administrativa del registro.', [
                'record_type' => $record::class,
                'record_id' => $record->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    public function notifyAnalysisDeletedBySupervisor(User $actor, AnalysisDeletionLog $deletionLog, ?Model $record = null): int
    {
        if (!$actor->hasAnyRole(User::supervisorEquivalentRoles())) {
            return 0;
        }

        try {
            $payload = $this->buildPayloadForDeletion($actor, $deletionLog, $record);

            return $this->sendToAdmins($payload);
        } catch (Throwable $exception) {
            Log::warning('No se pudo crear la notificacion administrativa de eliminacion de analisis.', [
                'user_id' => $actor->id,
                'analysis_type' => $deletionLog->analysis_type,
                'deleted_record_id' => $deletionLog->deleted_record_id,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    public function notifyReportGenerated(
        ?User $actor,
        string $tipoEquipo,
        ?Linea $linea,
        CarbonInterface $fechaInicio,
        CarbonInterface $fechaFin,
        string $formato,
        string $url
    ): int {
        try {
            $lineaNombre = $linea?->nombre ?? 'Reporte general';
            $createdAt = now();
            $createdAtDisplay = $this->formatDateTime($createdAt);
            $recordLabel = 'Reporte generado';
            $actorName = $actor?->name ?? 'Usuario no identificado';
            $tipoEquipoLabel = str($tipoEquipo)->replace('_', ' ')->title()->toString();
            $detail = sprintf(
                'Equipo: %s. Periodo: %s - %s. Formato: %s.',
                $tipoEquipoLabel,
                $fechaInicio->format('d/m/Y'),
                $fechaFin->format('d/m/Y'),
                strtoupper($formato)
            );

            return $this->sendToAdmins([
                'type' => 'admin_record_created',
                'record_type' => 'reporte',
                'record_label' => $recordLabel,
                'record_id' => null,
                'record_class' => null,
                'title' => 'Nuevo reporte generado',
                'mensaje' => $this->message($actorName, $recordLabel, $lineaNombre, $createdAtDisplay, $detail),
                'message' => $this->message($actorName, $recordLabel, $lineaNombre, $createdAtDisplay, $detail),
                'actor_id' => $actor?->id,
                'actor_name' => $actorName,
                'linea' => $lineaNombre,
                'linea_id' => $linea?->id,
                'created_at' => $createdAt->toIso8601String(),
                'created_at_display' => $createdAtDisplay,
                'detail' => $detail,
                'url' => $url,
                'prioridad' => 'media',
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo crear la notificacion administrativa del reporte.', [
                'tipo_equipo' => $tipoEquipo,
                'linea_id' => $linea?->id,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendToAdmins(array $payload): int
    {
        $admins = User::query()
            ->where('activo', true)
            ->whereHas('roles', fn ($query) => $query->where('name', User::ROLE_ADMIN))
            ->get();

        if ($admins->isEmpty()) {
            return 0;
        }

        Notification::send($admins, new AdminRegistroCreadoNotification($payload));

        return $admins->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayloadForRecord(Model $record): array
    {
        $createdAt = $record->created_at ?? now();
        $createdAtDisplay = $this->formatDateTime($createdAt);
        $actor = $this->resolveActor($record);
        $actorName = $actor?->name ?? 'Usuario no identificado';
        $linea = $this->resolveLinea($record);
        $recordLabel = $this->resolveRecordLabel($record);
        $detail = $this->resolveDetail($record);

        return [
            'type' => 'admin_record_created',
            'record_type' => $this->resolveRecordType($record),
            'record_label' => $recordLabel,
            'record_id' => $record->getKey(),
            'record_class' => $record::class,
            'title' => 'Nuevo registro: ' . $recordLabel,
            'mensaje' => $this->message($actorName, $recordLabel, $linea['nombre'], $createdAtDisplay, $detail),
            'message' => $this->message($actorName, $recordLabel, $linea['nombre'], $createdAtDisplay, $detail),
            'actor_id' => $actor?->id,
            'actor_name' => $actorName,
            'linea' => $linea['nombre'],
            'linea_id' => $linea['id'],
            'created_at' => $createdAt instanceof CarbonInterface ? $createdAt->toIso8601String() : now()->toIso8601String(),
            'created_at_display' => $createdAtDisplay,
            'detail' => $detail,
            'url' => $this->resolveUrl($record),
            'prioridad' => 'media',
            'area_pasteurizadora' => $record instanceof AnalisisPasteurizadora ? $record->area : null,
            'area_pasteurizadora_label' => $record instanceof AnalisisPasteurizadora ? $this->areaPasteurizadoraLabel($record->area) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayloadForDeletion(User $actor, AnalysisDeletionLog $deletionLog, ?Model $record = null): array
    {
        $deletedAt = $deletionLog->deleted_at ?? now();
        $deletedAtDisplay = $this->formatDateTime($deletedAt);
        $recordLabel = $deletionLog->tipo_analisis ?: $this->analysisDeletionTypeLabel($deletionLog->analysis_type);
        $lineaNombre = $deletionLog->linea_nombre ?: 'Linea no asignada';
        $detail = $this->resolveDeletionDetail($deletionLog);
        $areaPasteurizadora = $this->resolveDeletionPasteurizadoraArea($deletionLog, $record);
        $message = $this->deletionMessage($actor->name, $recordLabel, $lineaNombre, $deletedAtDisplay, $detail);

        return [
            'type' => 'admin_analysis_deleted',
            'record_type' => $deletionLog->analysis_type,
            'record_label' => $recordLabel,
            'record_id' => $deletionLog->deleted_record_id,
            'record_class' => $deletionLog->analysis_model,
            'title' => 'Analisis eliminado por supervisor',
            'mensaje' => $message,
            'message' => $message,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'linea' => $lineaNombre,
            'linea_id' => $deletionLog->linea_id,
            'deleted_at' => $deletedAt instanceof CarbonInterface ? $deletedAt->toIso8601String() : now()->toIso8601String(),
            'deleted_at_display' => $deletedAtDisplay,
            'created_at' => $deletedAt instanceof CarbonInterface ? $deletedAt->toIso8601String() : now()->toIso8601String(),
            'created_at_display' => $deletedAtDisplay,
            'detail' => $detail,
            'url' => $this->resolveDeletionUrl($deletionLog, $record),
            'prioridad' => 'alta',
            'area_pasteurizadora' => $areaPasteurizadora,
            'area_pasteurizadora_label' => $areaPasteurizadora ? $this->areaPasteurizadoraLabel($areaPasteurizadora) : null,
        ];
    }

    private function resolveActor(Model $record): ?User
    {
        if ($record instanceof PlanAccion) {
            $record->loadMissing('registradoPor');

            return $record->registradoPor ?: auth()->user();
        }

        if ($record instanceof Analisis || $record instanceof AnalisisLavadora || $record instanceof AnalisisPasteurizadora) {
            $record->loadMissing('usuario');

            return $record->usuario ?: auth()->user();
        }

        return auth()->user();
    }

    /**
     * @return array{nombre: string, id: int|null}
     */
    private function resolveLinea(Model $record): array
    {
        if ($record instanceof Elongacion) {
            $record->loadMissing('lineaModel');

            return [
                'nombre' => $record->linea ?: ($record->lineaModel?->nombre ?? 'Linea no asignada'),
                'id' => $record->linea_id,
            ];
        }

        if (method_exists($record, 'linea')) {
            $record->loadMissing('linea');
            $linea = $record->getRelation('linea');

            return [
                'nombre' => $linea?->nombre ?? 'Linea no asignada',
                'id' => $record->linea_id ?? $linea?->id,
            ];
        }

        return [
            'nombre' => 'Linea no asignada',
            'id' => null,
        ];
    }

    private function resolveRecordType(Model $record): string
    {
        return match (true) {
            $record instanceof Analisis => 'analisis_general',
            $record instanceof AnalisisLavadora => 'analisis_lavadora',
            $record instanceof AnalisisPasteurizadora => $record->area === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                ? 'inspeccion_central_hidraulica'
                : 'analisis_pasteurizadora',
            $record instanceof Elongacion => 'registro_elongacion',
            $record instanceof PlanAccion => 'plan_accion',
            default => 'registro',
        };
    }

    private function resolveRecordLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Analisis => 'Analisis general',
            $record instanceof AnalisisLavadora => 'Analisis de lavadora',
            $record instanceof AnalisisPasteurizadora => $record->area === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                ? 'Inspeccion de central hidraulica'
                : 'Analisis de pasteurizadora',
            $record instanceof Elongacion => 'Registro de elongacion',
            $record instanceof PlanAccion => 'Plan de accion',
            default => 'Registro',
        };
    }

    private function resolveDetail(Model $record): ?string
    {
        if ($record instanceof AnalisisLavadora) {
            $record->loadMissing('componente');

            return $this->joinDetails([
                'Componente: ' . ($record->componente?->nombre ?? 'Sin componente'),
                $record->reductor ? 'Reductor: ' . $record->reductor : null,
                $record->lado ? 'Lado: ' . $record->lado : null,
                $record->estado ? 'Estado: ' . $record->estado : null,
                $record->numero_orden ? 'Orden: ' . $record->numero_orden : null,
            ]);
        }

        if ($record instanceof AnalisisPasteurizadora) {
            return $this->joinDetails([
                'Area: ' . $this->areaPasteurizadoraLabel($record->area),
                $record->modulo ? 'Modulo: ' . $record->modulo : null,
                $record->componente ? 'Componente: ' . $record->componente : null,
                $record->nivel ? 'Nivel: ' . $record->nivel : null,
                $record->lado ? 'Lado: ' . $record->lado : null,
                $record->estado ? 'Estado: ' . $record->estado : null,
                $record->numero_orden ? 'Orden: ' . $record->numero_orden : null,
            ]);
        }

        if ($record instanceof Analisis) {
            $record->loadMissing('componente');

            return $this->joinDetails([
                'Componente: ' . ($record->componente?->nombre ?? 'Sin componente'),
                $record->reductor ? 'Reductor: ' . $record->reductor : null,
                $record->numero_orden ? 'Orden: ' . $record->numero_orden : null,
            ]);
        }

        if ($record instanceof Elongacion) {
            return $this->joinDetails([
                $record->cadenaCiclo?->codigo ? 'Ciclo: ' . $record->cadenaCiclo->codigo : null,
                'Bombas: ' . number_format((float) $record->bombas_porcentaje, 2) . '%',
                'Vapor: ' . number_format((float) $record->vapor_porcentaje, 2) . '%',
                $record->estado_detallado ? 'Estado: ' . $record->estado_detallado : null,
            ]);
        }

        if ($record instanceof PlanAccion) {
            return $this->joinDetails([
                $record->tipo_equipo ? 'Equipo: ' . ucfirst($record->tipo_equipo) : null,
                $record->area_pasteurizadora ? 'Area: ' . $record->area_pasteurizadora_label : null,
                $record->actividad ? 'Actividad: ' . $record->actividad : null,
            ]);
        }

        return null;
    }

    private function resolveDeletionDetail(AnalysisDeletionLog $deletionLog): ?string
    {
        $metadata = $deletionLog->metadata ?? [];
        $area = $metadata['area'] ?? null;

        return $this->joinDetails([
            $area ? 'Area: ' . $this->areaPasteurizadoraLabel((string) $area) : null,
            $this->metadataText($metadata, 'modulo', 'Modulo'),
            $this->metadataText($metadata, 'componente', 'Componente'),
            $this->metadataText($metadata, 'categoria', 'Categoria'),
            $this->metadataText($metadata, 'numero_r', 'Numero R'),
            $this->metadataText($metadata, 'reductor', 'Reductor'),
            $this->metadataText($metadata, 'nivel', 'Nivel'),
            $this->metadataText($metadata, 'lado', 'Lado'),
            $this->metadataText($metadata, 'estado', 'Estado'),
            $this->metadataText($metadata, 'numero_orden', 'Orden'),
            $this->metadataText($metadata, 'fecha_analisis', 'Fecha analisis'),
        ]);
    }

    private function resolveUrl(Model $record): ?string
    {
        return match (true) {
            $record instanceof Analisis => $this->routeIfExists('analisis.show', ['analisis' => $record->getKey()]),
            $record instanceof AnalisisLavadora => $this->routeIfExists('analisis-lavadora.show', ['analisislavadora' => $record->getKey()]),
            $record instanceof AnalisisPasteurizadora => $this->routeIfExists(
                $record->area === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                    ? 'pasteurizadora.central-hidraulica.show'
                    : 'pasteurizadora.analisis-pasteurizadora.show',
                ['analisispasteurizadora' => $record->getKey()]
            ),
            $record instanceof Elongacion => $this->routeIfExists('elongaciones.show', ['elongacion' => $record->getKey()]),
            $record instanceof PlanAccion => $this->routeIfExists('plan-accion.edit', [
                'plan_accion' => $record->getKey(),
                'tipo' => $record->tipo_equipo ?: 'lavadora',
            ]),
            default => null,
        };
    }

    private function resolveDeletionUrl(AnalysisDeletionLog $deletionLog, ?Model $record = null): ?string
    {
        $parameters = $deletionLog->linea_id ? ['linea_id' => $deletionLog->linea_id] : [];

        if ($record instanceof AnalisisPasteurizadora) {
            return $this->routeIfExists(
                $record->area === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                    ? 'pasteurizadora.central-hidraulica.index'
                    : 'pasteurizadora.analisis-pasteurizadora.index',
                $parameters
            );
        }

        $metadata = $deletionLog->metadata ?? [];

        return match ($deletionLog->analysis_type) {
            'lavadora' => $this->routeIfExists('analisis-lavadora.index', $parameters),
            'pasteurizadora' => $this->routeIfExists(
                ($metadata['area'] ?? null) === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                    ? 'pasteurizadora.central-hidraulica.index'
                    : 'pasteurizadora.analisis-pasteurizadora.index',
                $parameters
            ),
            'analisis' => $this->routeIfExists('analisis.index', $parameters),
            default => null,
        };
    }

    private function resolveDeletionPasteurizadoraArea(AnalysisDeletionLog $deletionLog, ?Model $record = null): ?string
    {
        if ($record instanceof AnalisisPasteurizadora) {
            return $record->area;
        }

        $metadata = $deletionLog->metadata ?? [];

        return $deletionLog->analysis_type === 'pasteurizadora'
            ? ($metadata['area'] ?? null)
            : null;
    }

    private function analysisDeletionTypeLabel(string $analysisType): string
    {
        return match ($analysisType) {
            'analisis' => 'Analisis general',
            'lavadora' => 'Analisis Lavadora',
            'pasteurizadora' => 'Analisis de pasteurizadora',
            default => 'Analisis',
        };
    }

    private function routeIfExists(string $name, array $parameters = []): ?string
    {
        if (!Route::has($name)) {
            return null;
        }

        try {
            return route($name, $parameters);
        } catch (Throwable) {
            return null;
        }
    }

    private function areaPasteurizadoraLabel(?string $area): ?string
    {
        if (!$area) {
            return null;
        }

        return PlanAccion::areasPasteurizadoraOpciones()[$area] ?? $area;
    }

    /**
     * @param  array<int, string|null>  $parts
     */
    private function joinDetails(array $parts): ?string
    {
        $details = collect($parts)
            ->filter(fn ($part) => filled($part))
            ->values()
            ->all();

        return $details ? implode('. ', $details) . '.' : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function metadataText(array $metadata, string $key, string $label): ?string
    {
        $value = $metadata[$key] ?? null;

        return filled($value) ? $label . ': ' . $value : null;
    }

    private function message(string $actorName, string $recordLabel, string $linea, string $createdAtDisplay, ?string $detail): string
    {
        $message = sprintf(
            '%s creo %s en %s el %s.',
            $actorName,
            $recordLabel,
            $linea,
            $createdAtDisplay
        );

        return $detail ? $message . ' ' . $detail : $message;
    }

    private function deletionMessage(string $actorName, string $recordLabel, string $linea, string $deletedAtDisplay, ?string $detail): string
    {
        $message = sprintf(
            '%s elimino %s en %s el %s.',
            $actorName,
            $recordLabel,
            $linea,
            $deletedAtDisplay
        );

        return $detail ? $message . ' ' . $detail : $message;
    }

    private function formatDateTime(mixed $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date
                ->copy()
                ->timezone(config('app.timezone', 'UTC'))
                ->format('d/m/Y H:i');
        }

        return now()->format('d/m/Y H:i');
    }
}
