<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Jobs\IndexMaintenanceHistoryRecord;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\MaintenanceEvent;
use App\Models\MaintenanceHistoryChunk;
use App\Models\PlanAccion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MaintenanceHistoryIndexer
{
    public const SOURCE_ANALISIS_LAVADORA = 'analisis_lavadora';
    public const SOURCE_MAINTENANCE_EVENT = 'maintenance_event';
    public const SOURCE_PLAN_ACCION = 'plan_accion';
    public const SOURCE_ELONGACION = 'elongacion';
    public const SOURCE_LAVADORA_COST_ENTRY = 'lavadora_cost_entry';

    private const SOURCE_MODEL_MAP = [
        self::SOURCE_ANALISIS_LAVADORA => AnalisisLavadora::class,
        self::SOURCE_MAINTENANCE_EVENT => MaintenanceEvent::class,
        self::SOURCE_PLAN_ACCION => PlanAccion::class,
        self::SOURCE_ELONGACION => Elongacion::class,
        self::SOURCE_LAVADORA_COST_ENTRY => LavadoraCostEntry::class,
    ];

    private const DAMAGE_TERMS = [
        'aceite',
        'alineacion',
        'averia',
        'bloqueado',
        'calentamiento',
        'cambio',
        'critico',
        'danado',
        'desgaste',
        'deterioro',
        'empaque',
        'exceso',
        'fisura',
        'fuga',
        'goteo',
        'holgura',
        'juego',
        'lubricacion',
        'lubricante',
        'obstruido',
        'reten',
        'rotura',
        'ruido',
        'sello',
        'severo',
        'temperatura',
        'tension',
        'vibracion',
    ];

    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly AiProviderInterface $aiProvider
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function indexAll(
        string $module = User::MODULE_LAVADORA,
        ?string $sourceType = null,
        bool $fresh = false
    ): array {
        if (!$this->historyTableExists()) {
            return [
                'indexed_chunks' => 0,
                'deleted_chunks' => 0,
                'sources' => [],
                'skipped' => 'La tabla maintenance_history_chunks no existe. Ejecuta las migraciones primero.',
            ];
        }

        $sourceTypes = $this->sourceTypesForModule($module);

        if ($sourceType !== null) {
            $sourceTypes = array_values(array_intersect($sourceTypes, [$sourceType]));
        }

        $deleted = 0;

        if ($fresh) {
            $deleteQuery = MaintenanceHistoryChunk::query()->where('module', $module);

            if ($sourceType !== null) {
                $deleteQuery->where('source_type', $sourceType);
            }

            $deleted = (int) $deleteQuery->delete();
        }

        $summary = [
            'indexed_chunks' => 0,
            'deleted_chunks' => $deleted,
            'sources' => [],
        ];

        foreach ($sourceTypes as $currentSourceType) {
            $sourceSummary = [
                'records' => 0,
                'indexed_chunks' => 0,
                'errors' => 0,
            ];

            $this->queryForSource($currentSourceType)
                ->chunkById(100, function ($records) use (&$sourceSummary, &$summary): void {
                    foreach ($records as $record) {
                        $sourceSummary['records']++;

                        try {
                            $indexed = $this->indexRecord($record);
                            $sourceSummary['indexed_chunks'] += $indexed;
                            $summary['indexed_chunks'] += $indexed;
                        } catch (Throwable) {
                            $sourceSummary['errors']++;
                        }
                    }
                });

            $summary['sources'][$currentSourceType] = $sourceSummary;
        }

        return $summary;
    }

    public function indexRecord(Model $record): int
    {
        if (!$this->historyTableExists()) {
            return 0;
        }

        $payload = $this->payloadForRecord($record);

        if ($payload === null) {
            return 0;
        }

        return $this->indexPayload($payload);
    }

    public function indexSource(string $sourceType, int $sourceId): int
    {
        $record = $this->findSourceRecord($sourceType, $sourceId);

        if (!$record) {
            $this->deleteFor(User::MODULE_LAVADORA, $sourceType, $sourceId);

            return 0;
        }

        return $this->indexRecord($record);
    }

    public function deleteFor(string $module, string $sourceType, int $sourceId): int
    {
        if (!$this->historyTableExists()) {
            return 0;
        }

        return (int) MaintenanceHistoryChunk::query()
            ->where('module', $module)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public function registerModelEvents(): void
    {
        if (!(bool) config('maintenance_ai.history_index.auto_index', false)) {
            return;
        }

        foreach (self::SOURCE_MODEL_MAP as $sourceType => $modelClass) {
            $modelClass::saved(function (Model $model) use ($sourceType): void {
                $this->dispatchIndex($sourceType, (int) $model->getKey());
            });

            $modelClass::deleted(function (Model $model) use ($sourceType): void {
                $this->dispatchDelete($sourceType, (int) $model->getKey());
            });
        }
    }

    /**
     * @return array<int, string>
     */
    public function sourceTypesForModule(string $module): array
    {
        return $module === User::MODULE_LAVADORA
            ? array_keys(self::SOURCE_MODEL_MAP)
            : [];
    }

    private function dispatchIndex(string $sourceType, int $sourceId): void
    {
        $dispatch = IndexMaintenanceHistoryRecord::dispatch(
            User::MODULE_LAVADORA,
            $sourceType,
            $sourceId
        )->onQueue((string) config('maintenance_ai.history_index.queue', config('maintenance_ai.queue', 'default')));

        if (method_exists($dispatch, 'afterCommit')) {
            $dispatch->afterCommit();
        }
    }

    private function dispatchDelete(string $sourceType, int $sourceId): void
    {
        $dispatch = IndexMaintenanceHistoryRecord::dispatch(
            User::MODULE_LAVADORA,
            $sourceType,
            $sourceId,
            true
        )->onQueue((string) config('maintenance_ai.history_index.queue', config('maintenance_ai.queue', 'default')));

        if (method_exists($dispatch, 'afterCommit')) {
            $dispatch->afterCommit();
        }
    }

    private function historyTableExists(): bool
    {
        return Schema::hasTable('maintenance_history_chunks');
    }

    private function queryForSource(string $sourceType): Builder
    {
        return match ($sourceType) {
            self::SOURCE_ANALISIS_LAVADORA => AnalisisLavadora::query()
                ->with(['linea', 'componente', 'usuario'])
                ->orderBy('id'),
            self::SOURCE_MAINTENANCE_EVENT => MaintenanceEvent::query()
                ->with(['linea', 'componente'])
                ->orderBy('id'),
            self::SOURCE_PLAN_ACCION => PlanAccion::query()
                ->with(['linea', 'maintenanceEvent.componente', 'responsable', 'ejecutadoPor'])
                ->orderBy('id'),
            self::SOURCE_ELONGACION => Elongacion::query()
                ->with(['lineaModel', 'cadenaCiclo'])
                ->orderBy('id'),
            self::SOURCE_LAVADORA_COST_ENTRY => LavadoraCostEntry::query()
                ->with(['linea', 'componente', 'catalogItem', 'analisisLavadora.componente', 'elongacion'])
                ->orderBy('id'),
            default => throw new \InvalidArgumentException('Tipo de fuente historica no soportado: ' . $sourceType),
        };
    }

    private function findSourceRecord(string $sourceType, int $sourceId): ?Model
    {
        return match ($sourceType) {
            self::SOURCE_ANALISIS_LAVADORA => AnalisisLavadora::query()
                ->with(['linea', 'componente', 'usuario'])
                ->find($sourceId),
            self::SOURCE_MAINTENANCE_EVENT => MaintenanceEvent::query()
                ->with(['linea', 'componente'])
                ->find($sourceId),
            self::SOURCE_PLAN_ACCION => PlanAccion::query()
                ->with(['linea', 'maintenanceEvent.componente', 'responsable', 'ejecutadoPor'])
                ->find($sourceId),
            self::SOURCE_ELONGACION => Elongacion::query()
                ->with(['lineaModel', 'cadenaCiclo'])
                ->find($sourceId),
            self::SOURCE_LAVADORA_COST_ENTRY => LavadoraCostEntry::query()
                ->with(['linea', 'componente', 'catalogItem', 'analisisLavadora.componente', 'elongacion'])
                ->find($sourceId),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payloadForRecord(Model $record): ?array
    {
        return match (true) {
            $record instanceof AnalisisLavadora => $this->analysisPayload($record),
            $record instanceof MaintenanceEvent => $this->eventPayload($record),
            $record instanceof PlanAccion => $this->planPayload($record),
            $record instanceof Elongacion => $this->elongacionPayload($record),
            $record instanceof LavadoraCostEntry => $this->costEntryPayload($record),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function indexPayload(array $payload): int
    {
        $content = $this->sanitizer->sanitizeText((string) ($payload['content'] ?? ''), 80000);

        if ($content === '') {
            return $this->deleteFor(
                (string) $payload['module'],
                (string) $payload['source_type'],
                (int) $payload['source_id']
            );
        }

        $chunks = $this->chunkText($content);
        $metadata = $payload['metadata'] ?? [];
        $indexed = 0;

        foreach ($chunks as $index => $chunk) {
            $chunkIndex = $index + 1;
            $chunkMetadata = array_merge($metadata, [
                'chunk_count' => count($chunks),
                'damage_terms' => $this->damageTermsFor($chunk['content']),
            ]);
            $contentHash = hash('sha256', $chunk['content'] . '|' . json_encode($chunkMetadata));
            $attributes = [
                'module' => (string) $payload['module'],
                'source_type' => (string) $payload['source_type'],
                'source_id' => (int) $payload['source_id'],
                'chunk_index' => $chunkIndex,
            ];
            $existing = MaintenanceHistoryChunk::query()->where($attributes)->first();
            $embeddingModel = $this->embeddingModelName();
            $embedding = $this->shouldReuseEmbedding($existing, $contentHash, $embeddingModel)
                ? $existing?->embedding
                : $this->embeddingFor($chunk['content']);

            MaintenanceHistoryChunk::query()->updateOrCreate($attributes, [
                'linea_id' => $payload['linea_id'] ?? null,
                'componente_id' => $payload['componente_id'] ?? null,
                'source_date' => $payload['source_date'] ?? null,
                'title' => $payload['title'] ?? null,
                'content' => $chunk['content'],
                'searchable_text' => $chunk['searchable_text'],
                'token_count' => $chunk['token_count'],
                'metadata' => $chunkMetadata,
                'embedding' => $embedding,
                'embedding_model' => $embedding ? $embeddingModel : null,
                'content_hash' => $contentHash,
                'indexed_at' => now(),
            ]);

            $indexed++;
        }

        MaintenanceHistoryChunk::query()
            ->where('module', (string) $payload['module'])
            ->where('source_type', (string) $payload['source_type'])
            ->where('source_id', (int) $payload['source_id'])
            ->where('chunk_index', '>', count($chunks))
            ->delete();

        return $indexed;
    }

    /**
     * @return array<int, array{content: string, searchable_text: string, token_count: int}>
     */
    private function chunkText(string $content): array
    {
        $chunkSize = max(500, (int) config('maintenance_ai.history_index.chunk_size', 1800));
        $overlap = min(max(0, (int) config('maintenance_ai.history_index.chunk_overlap', 200)), $chunkSize - 100);

        if (mb_strlen($content) <= $chunkSize) {
            return [[
                'content' => $content,
                'searchable_text' => $this->searchableText($content),
                'token_count' => $this->tokenCount($content),
            ]];
        }

        $chunks = [];
        $position = 0;
        $length = mb_strlen($content);

        while ($position < $length) {
            $sliceLength = min($chunkSize, $length - $position);
            $part = mb_substr($content, $position, $sliceLength);
            $isLastSlice = $position + $sliceLength >= $length;

            if (!$isLastSlice) {
                $lastSpace = mb_strrpos($part, ' ');

                if ($lastSpace !== false && $lastSpace > (int) ($chunkSize * 0.6)) {
                    $part = mb_substr($part, 0, $lastSpace);
                }
            }

            $part = trim($part);
            $partLength = mb_strlen($part);

            if ($part !== '') {
                $chunks[] = [
                    'content' => $part,
                    'searchable_text' => $this->searchableText($part),
                    'token_count' => $this->tokenCount($part),
                ];
            }

            if ($isLastSlice) {
                break;
            }

            $position += max(1, $partLength - $overlap);
        }

        return $chunks !== [] ? $chunks : [[
            'content' => $content,
            'searchable_text' => $this->searchableText($content),
            'token_count' => $this->tokenCount($content),
        ]];
    }

    private function searchableText(string $content): string
    {
        $normalized = strtolower(Str::ascii($content));
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    private function tokenCount(string $content): int
    {
        $parts = preg_split('/\s+/u', trim($this->searchableText($content))) ?: [];

        return count(array_filter($parts));
    }

    /**
     * @return array<int, float>|null
     */
    private function embeddingFor(string $content): ?array
    {
        if (!(bool) config('maintenance_ai.enabled', false)) {
            return null;
        }

        try {
            $embedding = $this->normalizeVector($this->aiProvider->createEmbedding(
                $this->sanitizer->sanitizeText($content, 1800)
            ));

            return $embedding !== [] ? $embedding : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function shouldReuseEmbedding(
        ?MaintenanceHistoryChunk $existing,
        string $contentHash,
        ?string $embeddingModel
    ): bool {
        return $existing !== null
            && $existing->content_hash === $contentHash
            && $existing->embedding !== null
            && $existing->embedding_model === $embeddingModel;
    }

    /**
     * @param  mixed  $vector
     * @return array<int, float>
     */
    private function normalizeVector(mixed $vector): array
    {
        if (!is_array($vector)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($value): ?float => is_numeric($value) ? (float) $value : null,
            $vector
        ), static fn (?float $value): bool => $value !== null));
    }

    private function embeddingModelName(): ?string
    {
        if (!(bool) config('maintenance_ai.enabled', false)) {
            return null;
        }

        $provider = (string) config('maintenance_ai.provider', 'openai');

        return (string) data_get(
            config('maintenance_ai.providers', []),
            $provider . '.embedding_model',
            config('maintenance_ai.providers.openai.embedding_model')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisPayload(AnalisisLavadora $analysis): array
    {
        $analysis->loadMissing(['linea', 'componente', 'usuario']);
        $component = $analysis->componente;
        $lineaNombre = $analysis->linea?->nombre;
        $sourceDate = $analysis->fecha_analisis ?: $analysis->created_at;
        $title = $this->titleFor('Analisis lavadora', $analysis->id, $lineaNombre, $component);
        $content = $this->sections([
            'Origen' => 'Analisis de lavadora #' . $analysis->id,
            'Linea' => $lineaNombre,
            'Componente' => $this->componentLabel($component),
            'Subcomponente' => $this->subcomponentLabel($analysis->reductor, $analysis->lado),
            'Fecha del analisis' => $this->dateString($sourceDate),
            'Numero de orden' => $analysis->numero_orden,
            'Estado detectado' => $analysis->estado,
            'Estado operativo' => $analysis->estado_operativo,
            'Estado de correccion' => $analysis->estado_correccion,
            'Observacion del tecnico' => $analysis->actividad,
            'Tipo de intervencion' => $analysis->tipo_intervencion,
            'Resultado u observaciones de reparacion' => $analysis->observaciones_reparacion,
            'Componente instalado' => $analysis->componente_instalado,
            'Numero de parte' => $analysis->numero_parte,
            'Proveedor' => $analysis->proveedor,
            'Fecha de cambio' => $this->dateString($analysis->fecha_cambio),
            'Costo total intervencion' => $analysis->costo_total_intervencion,
            'Tiempo de reparacion horas' => $analysis->tiempo_reparacion_horas,
            'Comentarios de costos' => $analysis->comentarios_costos,
            'Evidencias de falla' => count($analysis->evidencia_fotos ?? []),
            'Evidencias de reparacion' => count($analysis->evidencias_reparacion ?? []),
            'Registrado por' => $analysis->usuario?->name,
        ]);

        return $this->payload(
            self::SOURCE_ANALISIS_LAVADORA,
            (int) $analysis->id,
            $analysis->linea_id,
            $analysis->componente_id,
            $sourceDate,
            $title,
            $content,
            $this->metadata($analysis, $lineaNombre, $component, [
                'estado' => $analysis->estado,
                'estado_correccion' => $analysis->estado_correccion,
                'reductor' => $analysis->reductor,
                'lado' => $analysis->lado,
                'has_repair_result' => filled($analysis->observaciones_reparacion)
                    || filled($analysis->tipo_intervencion)
                    || filled($analysis->componente_instalado)
                    || (float) $analysis->costo_total_intervencion > 0,
            ])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(MaintenanceEvent $event): array
    {
        $event->loadMissing(['linea', 'componente']);
        $component = $event->componente;
        $lineaNombre = $event->linea?->nombre;
        $sourceDate = $event->detected_at ?: $event->created_at;
        $title = $event->title ?: $this->titleFor('Evento mantenimiento', $event->id, $lineaNombre, $component);
        $content = $this->sections([
            'Origen' => 'Evento de mantenimiento #' . $event->id,
            'Linea' => $lineaNombre,
            'Componente' => $this->componentLabel($component),
            'Fecha de deteccion' => $this->dateString($sourceDate),
            'Tipo de evento' => $event->event_type,
            'Severidad' => $event->severity,
            'Estado' => $event->status,
            'Titulo' => $event->title,
            'Descripcion' => $event->description,
            'Valor detectado' => $event->detected_value,
            'Limite' => $event->limit_value,
            'Datos de contexto' => $this->jsonText($event->context_data),
            'Origen del registro' => trim((string) $event->source_type . ' #' . $event->source_id),
        ]);

        return $this->payload(
            self::SOURCE_MAINTENANCE_EVENT,
            (int) $event->id,
            $event->linea_id,
            $event->componente_id,
            $sourceDate,
            $title,
            $content,
            $this->metadata($event, $lineaNombre, $component, [
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'status' => $event->status,
                'origin_source_type' => $event->source_type,
                'origin_source_id' => $event->source_id,
            ])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(PlanAccion $plan): array
    {
        $plan->loadMissing(['linea', 'maintenanceEvent.componente', 'responsable', 'ejecutadoPor']);
        $event = $plan->maintenanceEvent;
        $component = $event?->componente;
        $lineaNombre = $plan->linea?->nombre;
        $sourceDate = $plan->fecha_ejecucion ?: $plan->generated_at ?: $plan->updated_at;
        $title = $this->titleFor('Plan accion', $plan->id, $lineaNombre, $component);
        $generatedContent = $plan->approved_content ?: $plan->original_generated_content;
        $content = $this->sections([
            'Origen' => 'Plan de accion #' . $plan->id,
            'Linea' => $lineaNombre,
            'Componente' => $this->componentLabel($component),
            'Evento asociado' => $event ? 'Evento #' . $event->id . ' - ' . $event->title : null,
            'Actividad recomendada o ejecutada' => $plan->actividad,
            'Problema detectado' => $plan->detected_problem,
            'Justificacion tecnica' => $plan->technical_justification,
            'Riesgo si no se ejecuta' => $plan->risk_if_not_executed,
            'Prioridad' => $plan->priority_level,
            'Tipo de mantenimiento' => $plan->maintenance_type,
            'Estado del plan' => $plan->estado,
            'Fuente' => $plan->source ?: 'manual',
            'Completado' => $plan->completado ? 'si' : 'no',
            'Fecha de ejecucion' => $this->dateString($plan->fecha_ejecucion),
            'Resultado de ejecucion' => $plan->execution_result,
            'Efectividad' => $plan->effectiveness,
            'Observaciones finales' => $plan->final_observations,
            'Costo estimado' => $plan->estimated_cost_total,
            'Costo real' => $plan->actual_cost_total,
            'Horas estimadas' => $plan->estimated_hours,
            'Horas reales' => $plan->actual_hours,
            'Responsable' => $plan->responsable?->name,
            'Ejecutado por' => $plan->ejecutadoPor?->name,
            'Informacion generada o aprobada' => $this->jsonText($generatedContent, 1800),
            'Fuentes usadas originalmente' => $this->jsonText($plan->knowledge_sources, 1000),
        ]);

        return $this->payload(
            self::SOURCE_PLAN_ACCION,
            (int) $plan->id,
            $plan->linea_id,
            $event?->componente_id,
            $sourceDate,
            $title,
            $content,
            $this->metadata($plan, $lineaNombre, $component, [
                'event_id' => $event?->id,
                'event_type' => $event?->event_type,
                'event_severity' => $event?->severity,
                'source' => $plan->source ?: 'manual',
                'estado' => $plan->estado,
                'tipo_equipo' => $plan->tipo_equipo ?: User::MODULE_LAVADORA,
                'priority_level' => $plan->priority_level,
                'maintenance_type' => $plan->maintenance_type,
                'completed' => (bool) $plan->completado,
                'effectiveness' => $plan->effectiveness,
            ])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function elongacionPayload(Elongacion $elongacion): array
    {
        $elongacion->loadMissing(['lineaModel', 'cadenaCiclo']);
        $lineaNombre = $elongacion->lineaModel?->nombre ?: $elongacion->linea;
        $sourceDate = $elongacion->created_at;
        $title = 'Elongacion cadena lavadora #' . $elongacion->id . ($lineaNombre ? ' - ' . $lineaNombre : '');
        $content = $this->sections([
            'Origen' => 'Revision de elongacion #' . $elongacion->id,
            'Linea' => $lineaNombre,
            'Componente principal' => 'Cadena de lavadora, rodajas, bujes, guias y tensores asociados',
            'Proveedor' => $elongacion->proveedor,
            'Seccion' => $elongacion->seccion,
            'Estado' => $elongacion->estado,
            'Estado detallado' => $elongacion->estado_detallado,
            'Requiere cambio' => $elongacion->requiere_cambio ? 'si' : 'no',
            'Porcentaje lado bombas' => $elongacion->bombas_porcentaje,
            'Promedio lado bombas' => $elongacion->bombas_promedio,
            'Porcentaje lado vapor' => $elongacion->vapor_porcentaje,
            'Promedio lado vapor' => $elongacion->vapor_promedio,
            'Hodometro total' => $elongacion->hodometro,
            'Hodometro ciclo' => $elongacion->hodometro_ciclo,
            'Juego rodaja bombas' => $elongacion->juego_rodaja_bombas,
            'Juego rodaja vapor' => $elongacion->juego_rodaja_vapor,
            'Ciclo de cadena' => $elongacion->cadenaCiclo?->nombre ?? $elongacion->cadena_ciclo_id,
        ]);

        return $this->payload(
            self::SOURCE_ELONGACION,
            (int) $elongacion->id,
            $elongacion->linea_id,
            null,
            $sourceDate,
            $title,
            $content,
            $this->metadata($elongacion, $lineaNombre, null, [
                'component_terms' => ['cadena', 'rodaja', 'buje', 'guia', 'tensor', 'elongacion'],
                'estado' => $elongacion->estado,
                'estado_detallado' => $elongacion->estado_detallado,
                'requiere_cambio' => (bool) $elongacion->requiere_cambio,
                'bombas_porcentaje' => $elongacion->bombas_porcentaje,
                'vapor_porcentaje' => $elongacion->vapor_porcentaje,
            ])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function costEntryPayload(LavadoraCostEntry $entry): array
    {
        $entry->loadMissing(['linea', 'componente', 'catalogItem', 'analisisLavadora.componente', 'elongacion']);
        $component = $entry->componente ?: $entry->analisisLavadora?->componente;
        $lineaNombre = $entry->linea?->nombre;
        $sourceDate = $entry->cost_date ?: $entry->created_at;
        $title = $this->titleFor('Costo lavadora', $entry->id, $lineaNombre, $component);
        $content = $this->sections([
            'Origen' => 'Costo historico de lavadora #' . $entry->id,
            'Linea' => $lineaNombre,
            'Componente' => $this->componentLabel($component),
            'Fecha de costo' => $this->dateString($sourceDate),
            'Tipo de origen' => LavadoraCostEntry::sourceLabel($entry->source_type),
            'Referencia de origen' => $entry->source_reference,
            'Material o refaccion' => $entry->catalog_name_snapshot ?: $entry->catalogItem?->nombre,
            'SKU' => $entry->catalog_sku_snapshot ?: $entry->catalogItem?->sku,
            'Categoria' => $entry->catalog_category_snapshot,
            'Unidad' => $entry->unidad_medida_snapshot,
            'Cantidad' => $entry->quantity,
            'Costo unitario' => $entry->unit_cost,
            'Costo total' => $entry->total_cost,
            'Componente snapshot' => $entry->component_snapshot,
            'Notas' => $entry->notas,
            'Metadata' => $this->jsonText($entry->metadata, 900),
        ]);

        return $this->payload(
            self::SOURCE_LAVADORA_COST_ENTRY,
            (int) $entry->id,
            $entry->linea_id,
            $component?->id,
            $sourceDate,
            $title,
            $content,
            $this->metadata($entry, $lineaNombre, $component, [
                'source_type' => $entry->source_type,
                'source_reference' => $entry->source_reference,
                'catalog_name' => $entry->catalog_name_snapshot ?: $entry->catalogItem?->nombre,
                'catalog_sku' => $entry->catalog_sku_snapshot ?: $entry->catalogItem?->sku,
                'total_cost' => $entry->total_cost,
            ])
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function payload(
        string $sourceType,
        int $sourceId,
        mixed $lineaId,
        mixed $componenteId,
        mixed $sourceDate,
        string $title,
        string $content,
        array $metadata
    ): array {
        return [
            'module' => User::MODULE_LAVADORA,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'linea_id' => $lineaId ? (int) $lineaId : null,
            'componente_id' => $componenteId ? (int) $componenteId : null,
            'source_date' => $sourceDate,
            'title' => $this->sanitizer->sanitizeText($title, 255),
            'content' => $content,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function metadata(Model $record, ?string $lineaNombre, ?Componente $component, array $extra = []): array
    {
        $componentMetadata = $this->componentMetadata($component);
        $componentText = implode(' ', array_filter([
            $componentMetadata['name'] ?? null,
            $componentMetadata['code'] ?? null,
            $componentMetadata['base_code'] ?? null,
            $componentMetadata['grupo'] ?? null,
            $componentMetadata['mecanismo'] ?? null,
            $componentMetadata['reductor'] ?? null,
            $componentMetadata['ubicacion'] ?? null,
            implode(' ', (array) ($extra['component_terms'] ?? [])),
        ]));

        return array_filter([
            'record_class' => $record::class,
            'linea' => [
                'id' => $record->getAttribute('linea_id'),
                'nombre' => $lineaNombre,
            ],
            'component' => $componentMetadata,
            'component_terms' => array_values(array_unique(array_filter(array_merge(
                $this->tokenize($componentText),
                (array) ($extra['component_terms'] ?? [])
            )))),
            'extra' => $extra,
        ], static fn ($value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function componentMetadata(?Componente $component): ?array
    {
        if (!$component) {
            return null;
        }

        return array_filter([
            'id' => $component->id,
            'name' => $component->nombre,
            'code' => $component->codigo,
            'base_code' => AnalisisLavadora::codigoBaseComponente((string) $component->codigo),
            'linea' => $component->linea,
            'reductor' => $component->reductor,
            'ubicacion' => $component->ubicacion,
            'grupo' => $component->grupo,
            'mecanismo' => $component->mecanismo,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private function sections(array $sections): string
    {
        $lines = [];

        foreach ($sections as $label => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'si' : 'no';
            }

            if (is_array($value)) {
                $value = $this->jsonText($value);
            }

            $lines[] = $label . ': ' . $this->sanitizer->sanitizeText((string) $value, 2500);
        }

        return implode("\n", $lines);
    }

    private function componentLabel(?Componente $component): ?string
    {
        if (!$component) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $component->nombre,
            '(' . $component->codigo . ')',
            $component->reductor ?: $component->ubicacion,
        ])));
    }

    private function subcomponentLabel(mixed $reductor, mixed $lado): ?string
    {
        $parts = array_filter([
            $reductor ? 'Reductor/ubicacion: ' . $reductor : null,
            $lado ? 'Lado: ' . $lado : null,
        ]);

        return $parts !== [] ? implode(' | ', $parts) : null;
    }

    private function titleFor(string $prefix, int $id, ?string $lineaNombre, ?Componente $component): string
    {
        return trim(implode(' - ', array_filter([
            $prefix . ' #' . $id,
            $lineaNombre,
            $component?->nombre,
            $component?->codigo,
        ])));
    }

    private function jsonText(mixed $value, int $limit = 1200): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $encoded = is_string($value)
            ? $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded ? $this->sanitizer->sanitizeText($encoded, $limit) : null;
    }

    private function dateString(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return $value ? (string) $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function damageTermsFor(string $content): array
    {
        $tokens = $this->tokenize($content);

        return array_values(array_intersect($tokens, self::DAMAGE_TERMS));
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $value): array
    {
        $normalized = $this->searchableText($value);
        $parts = preg_split('/\s+/u', trim($normalized)) ?: [];

        return array_values(array_unique(array_filter($parts, static function ($part): bool {
            $part = trim((string) $part);

            return $part !== '' && (ctype_digit($part) || strlen($part) > 2);
        })));
    }
}
