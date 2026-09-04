<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\CentralHidraulicaComponente;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\MaintenanceHistoryChunk;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PasteurizadoraTechnicalContextRetriever
{
    private const BUCKET_SAME_COMPONENT_SAME_POSITION = 'same_component_same_pasteurizer_position';
    private const BUCKET_SAME_COMPONENT_SAME_PASTEURIZER = 'same_component_same_pasteurizer';
    private const BUCKET_SAME_COMPONENT_OTHER_PASTEURIZERS = 'same_component_other_pasteurizers';
    private const BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS = 'similar_failure_other_components';

    private const BUCKET_LABELS = [
        self::BUCKET_SAME_COMPONENT_SAME_POSITION => 'Registros anteriores del mismo componente en la misma posicion de la pasteurizadora',
        self::BUCKET_SAME_COMPONENT_SAME_PASTEURIZER => 'Registros del mismo componente en la misma pasteurizadora',
        self::BUCKET_SAME_COMPONENT_OTHER_PASTEURIZERS => 'Registros del mismo componente en otras pasteurizadoras',
        self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS => 'Casos similares encontrados en otros componentes de pasteurizadora',
    ];

    private const BUCKET_WEIGHTS = [
        self::BUCKET_SAME_COMPONENT_SAME_POSITION => 400,
        self::BUCKET_SAME_COMPONENT_SAME_PASTEURIZER => 300,
        self::BUCKET_SAME_COMPONENT_OTHER_PASTEURIZERS => 200,
        self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS => 100,
    ];

    private const DAMAGE_GROUPS = [
        'desgaste' => [
            'desgaste',
            'desgastado',
            'deterioro',
            'deteriorado',
            'danado',
            'dano',
            'critico',
            'severo',
            'requiere',
            'cambio',
        ],
        'holgura_juego' => [
            'holgura',
            'juego',
            'flojo',
            'aflojado',
            'vibracion',
            'alineacion',
            'desalineado',
        ],
        'rotura' => [
            'roto',
            'rota',
            'ruptura',
            'fractura',
            'fracturado',
            'quebrado',
            'quebrada',
        ],
        'friccion_arrastre' => [
            'arrastre',
            'friccion',
            'atorado',
            'trabado',
            'rozamiento',
            'rayado',
        ],
        'fuga_hidraulica' => [
            'fuga',
            'aceite',
            'hidraulico',
            'sello',
            'empaque',
            'reten',
            'goteo',
        ],
    ];

    private const GENERIC_TOKENS = [
        'accion',
        'analisis',
        'componente',
        'componentes',
        'dame',
        'detectado',
        'encontrado',
        'falla',
        'fallas',
        'linea',
        'lineas',
        'pasteurizadora',
        'pasteurizadoras',
        'pasteurizador',
        'plan',
        'problema',
        'recomendacion',
        'recomienda',
        'registrado',
        'revision',
        'revisar',
        'solucion',
        'tecnico',
    ];

    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly HybridKnowledgeRanker $ranker
    ) {
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    public function forQuestion(string $question, array $pageContext = [], ?User $user = null): array
    {
        if ($user && !$user->canAccessModule(User::MODULE_PASTEURIZADORA)) {
            return $this->emptyContext('El usuario no tiene acceso al modulo de pasteurizadora.');
        }

        $profile = $this->buildProfile($question, $pageContext);

        if ($user && ($profile['areas'] ?? []) !== []) {
            $profile['areas'] = array_values(array_filter(
                $profile['areas'],
                fn (string $area): bool => $user->canAccessPasteurizadoraArea($area)
            ));
        }

        return $this->buildContext($profile, $user);
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    public function forEvent(MaintenanceEvent $event, array $current = []): array
    {
        $event->loadMissing(['linea']);

        $sourceAnalysis = $event->source_type === 'analisis_pasteurizadora'
            ? AnalisisPasteurizadora::query()
                ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                ->with(['linea', 'usuario'])
                ->find($event->source_id)
            : null;
        $sourceCentralAnalysis = $event->source_type === 'analisis_central_hidraulica'
            ? AnalisisCentralHidraulica::query()
                ->with(['linea', 'usuario', 'configuracion', 'componente'])
                ->find($event->source_id)
            : null;

        $query = implode(' ', array_filter([
            (string) $event->title,
            (string) $event->description,
            (string) $event->event_type,
            (string) $event->severity,
            (string) $event->detected_value,
            (string) ($current['linea_nombre'] ?? $event->linea?->nombre),
            (string) ($current['component_name'] ?? $sourceAnalysis?->componente_nombre ?? $sourceCentralAnalysis?->componente_nombre),
            (string) ($current['component_code'] ?? $sourceAnalysis?->componente ?? $sourceCentralAnalysis?->componente?->codigo),
            (string) ($current['area'] ?? $sourceAnalysis?->area ?? ($sourceCentralAnalysis ? AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA : null)),
            (string) ($current['modulo'] ?? $sourceAnalysis?->modulo),
            (string) ($current['nivel'] ?? $sourceAnalysis?->nivel ?? $sourceCentralAnalysis?->piso),
            (string) ($current['piso'] ?? $sourceCentralAnalysis?->piso),
            (string) ($current['lado'] ?? $sourceAnalysis?->lado ?? $sourceCentralAnalysis?->lado),
            (string) ($current['estado'] ?? $sourceAnalysis?->estado ?? $sourceCentralAnalysis?->estado),
            (string) ($current['observaciones'] ?? $sourceAnalysis?->actividad ?? $sourceCentralAnalysis?->actividad),
        ]));

        $profile = $this->buildProfile($query, [
            'linea_nombre' => $current['linea_nombre'] ?? $event->linea?->nombre,
            'component_name' => $current['component_name'] ?? $sourceAnalysis?->componente_nombre ?? $sourceCentralAnalysis?->componente_nombre,
            'component_code' => $current['component_code'] ?? $sourceAnalysis?->componente ?? $sourceCentralAnalysis?->componente?->codigo,
            'estado' => $current['estado'] ?? $event->detected_value,
            'area' => $current['area'] ?? $sourceAnalysis?->area ?? ($sourceCentralAnalysis ? AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA : null),
            'modulo' => $current['modulo'] ?? $sourceAnalysis?->modulo,
            'nivel' => $current['nivel'] ?? $sourceAnalysis?->nivel ?? $sourceCentralAnalysis?->piso,
            'piso' => $current['piso'] ?? $sourceCentralAnalysis?->piso,
            'lado' => $current['lado'] ?? $sourceAnalysis?->lado ?? $sourceCentralAnalysis?->lado,
        ]);

        $profile['event'] = [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'severity' => $event->severity,
            'source_type' => $event->source_type,
            'source_id' => $event->source_id,
        ];
        $profile['exclude_analysis_id'] = $sourceAnalysis?->id;
        $profile['exclude_central_analysis_id'] = $sourceCentralAnalysis?->id;
        $profile['exclude_event_id'] = $event->id;

        if ($event->linea_id) {
            $profile['linea_ids'] = array_values(array_unique(array_merge(
                $profile['linea_ids'],
                [(int) $event->linea_id]
            )));
        }

        return $this->buildContext($profile, null);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function buildContext(array $profile, ?User $user): array
    {
        if (!$this->hasTechnicalSignal($profile)) {
            return $this->emptyContext('No se detecto una falla, componente o pasteurizadora especifica para recuperar antecedentes tecnicos.');
        }

        $historicalSources = $this->historicalSources($profile, $user);
        $bucketCounts = collect($historicalSources)
            ->map(fn (array $items): int => count($items))
            ->all();

        return [
            'available' => true,
            'generated_at' => now()->toIso8601String(),
            'module' => User::MODULE_PASTEURIZADORA,
            'detected_context' => $this->detectedContext($profile),
            'search_priority' => array_values(self::BUCKET_LABELS),
            'historical_sources' => $historicalSources,
            'technical_sources' => [],
            'coverage' => [
                'historical_records_count' => collect($bucketCounts)->sum(),
                'historical_records_by_priority' => $bucketCounts,
                'technical_sources_count' => 0,
                'has_same_component_history' => ($bucketCounts[self::BUCKET_SAME_COMPONENT_SAME_POSITION] ?? 0) > 0
                    || ($bucketCounts[self::BUCKET_SAME_COMPONENT_SAME_PASTEURIZER] ?? 0) > 0,
                'has_same_position_history' => ($bucketCounts[self::BUCKET_SAME_COMPONENT_SAME_POSITION] ?? 0) > 0,
                'has_same_component_other_pasteurizers_history' => ($bucketCounts[self::BUCKET_SAME_COMPONENT_OTHER_PASTEURIZERS] ?? 0) > 0,
                'has_similar_failure_history' => ($bucketCounts[self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS] ?? 0) > 0,
                'warnings' => $this->coverageWarnings(collect($bucketCounts)->sum() > 0),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    private function buildProfile(string $query, array $pageContext = []): array
    {
        $rankerProfile = $this->ranker->profile($query, $pageContext);
        $normalizedQuery = Str::lower(Str::ascii($query . ' ' . json_encode($pageContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        $lineas = array_values(array_unique(array_merge(
            $this->lineReferences((string) ($rankerProfile['query'] ?? $query)),
            $this->lineReferences((string) ($pageContext['linea_nombre'] ?? ''))
        )));
        $lineaIds = $this->lineIdsForNames($lineas);
        $componentCandidates = $this->componentCandidates($query, $rankerProfile, $lineas, $pageContext);
        $componentCodes = collect($componentCandidates)->pluck('code')->filter()->unique()->values()->all();
        $componentTerms = collect($rankerProfile['component_terms'] ?? [])
            ->merge(collect($componentCandidates)->pluck('search_terms')->flatten())
            ->merge($this->ranker->tokenize((string) ($pageContext['component_name'] ?? '')))
            ->merge($this->ranker->tokenize((string) ($pageContext['component_code'] ?? '')))
            ->reject(fn (string $token): bool => in_array($token, self::GENERIC_TOKENS, true))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (isset($pageContext['record_id']) && is_numeric($pageContext['record_id'])) {
            $recordId = (int) $pageContext['record_id'];
            $contextArea = AnalisisPasteurizadora::normalizarArea(
                (string) ($pageContext['area'] ?? $pageContext['area_pasteurizadora'] ?? '')
            );
            $currentPath = Str::lower((string) ($pageContext['current_path'] ?? $pageContext['current_url'] ?? ''));
            $preferCentralRecord = $contextArea === AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA
                || str_contains($currentPath, 'central-hidraulica');

            $appendMechanicalRecord = function (AnalisisPasteurizadora $record) use (&$lineas, &$lineaIds, &$componentCodes, &$componentTerms): void {
                $lineas = array_values(array_unique(array_filter(array_merge($lineas, [$record->linea?->nombre]))));
                $lineaIds = array_values(array_unique(array_filter(array_merge($lineaIds, [(int) $record->linea_id]))));
                $componentCodes = array_values(array_unique(array_filter(array_merge($componentCodes, [$record->componente]))));
                $componentTerms = array_values(array_unique(array_merge(
                    $componentTerms,
                    $this->ranker->tokenize($record->componente . ' ' . $record->componente_nombre)
                )));
            };

            $appendCentralRecord = function (AnalisisCentralHidraulica $record) use (&$lineas, &$lineaIds, &$componentCodes, &$componentTerms): void {
                $lineas = array_values(array_unique(array_filter(array_merge($lineas, [$record->linea?->nombre]))));
                $lineaIds = array_values(array_unique(array_filter(array_merge($lineaIds, [(int) $record->linea_id]))));
                $componentCodes = array_values(array_unique(array_filter(array_merge($componentCodes, [$record->componente?->codigo]))));
                $componentTerms = array_values(array_unique(array_merge(
                    $componentTerms,
                    $this->ranker->tokenize(($record->componente?->codigo ?? '') . ' ' . $record->componente_nombre)
                )));
            };

            if ($preferCentralRecord) {
                $centralRecord = AnalisisCentralHidraulica::query()
                    ->with(['linea', 'componente'])
                    ->find($recordId);

                if ($centralRecord) {
                    $appendCentralRecord($centralRecord);
                } else {
                    $record = AnalisisPasteurizadora::query()
                        ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                        ->with('linea')
                        ->find($recordId);

                    if ($record) {
                        $appendMechanicalRecord($record);
                    }
                }
            } else {
                $record = AnalisisPasteurizadora::query()
                    ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                    ->with('linea')
                    ->find($recordId);

                if ($record) {
                    $appendMechanicalRecord($record);
                } else {
                    $centralRecord = AnalisisCentralHidraulica::query()
                        ->with(['linea', 'componente'])
                        ->find($recordId);

                    if ($centralRecord) {
                        $appendCentralRecord($centralRecord);
                    }
                }
            }
        }

        return [
            'query' => $this->sanitizer->sanitizeText((string) ($rankerProfile['query'] ?? $query), 1600),
            'tokens' => $rankerProfile['tokens'] ?? $this->ranker->tokenize($query),
            'lineas' => $lineas,
            'linea_ids' => $lineaIds,
            'component_codes' => $componentCodes,
            'component_terms' => $componentTerms,
            'component_candidates' => $componentCandidates,
            'primary_component' => $componentCandidates[0] ?? null,
            'areas' => $this->areas($normalizedQuery, $pageContext),
            'modulos' => $this->modulos($normalizedQuery, $pageContext),
            'niveles' => $this->niveles($normalizedQuery, $pageContext),
            'lados' => $this->lados($normalizedQuery, $pageContext),
            'damage' => $this->damageProfile($query . ' ' . (string) ($pageContext['estado'] ?? '')),
            'severity' => $this->extractSeverity($normalizedQuery),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function detectedContext(array $profile): array
    {
        return [
            'query' => $profile['query'] ?? '',
            'lineas' => $profile['lineas'] ?? [],
            'component_candidates' => collect($profile['component_candidates'] ?? [])
                ->take(5)
                ->map(fn (array $component): array => [
                    'code' => $component['code'] ?? null,
                    'name' => $component['name'] ?? null,
                    'linea' => $component['linea'] ?? null,
                ])
                ->values()
                ->all(),
            'primary_component' => $profile['primary_component'] ?? null,
            'position' => [
                'areas' => $profile['areas'] ?? [],
                'modulos' => $profile['modulos'] ?? [],
                'niveles' => $profile['niveles'] ?? [],
                'lados' => $profile['lados'] ?? [],
            ],
            'damage_type' => [
                'labels' => $profile['damage']['labels'] ?? [],
                'terms' => $profile['damage']['terms'] ?? [],
            ],
            'severity' => $profile['severity'] ?? null,
            'event' => $profile['event'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function historicalSources(array $profile, ?User $user): array
    {
        $buckets = $this->emptyBuckets();
        $perBucket = $this->historyLimitPerBucket();
        $totalLimit = $this->totalHistoryLimit();

        $items = collect()
            ->concat($this->semanticHistoryItems($profile, $user))
            ->concat($this->analysisHistoryItems($profile, $user))
            ->concat($this->centralAnalysisHistoryItems($profile, $user))
            ->concat($this->maintenanceEventHistoryItems($profile, $user))
            ->concat($this->planHistoryItems($profile, $user))
            ->filter(fn (?array $item): bool => is_array($item) && ($item['priority_bucket'] ?? null) !== null)
            ->sortByDesc('score')
            ->unique(fn (array $item): string => implode('|', [
                $item['type'] ?? 'unknown',
                $item['reference'] ?? 'unknown',
                $item['date'] ?? '',
            ]))
            ->values();

        $used = 0;

        foreach (array_keys(self::BUCKET_LABELS) as $bucket) {
            $bucketItems = $items
                ->filter(fn (array $item): bool => ($item['priority_bucket'] ?? null) === $bucket)
                ->take($perBucket)
                ->map(fn (array $item): array => $this->stripScore($item))
                ->values()
                ->all();

            if ($used + count($bucketItems) > $totalLimit) {
                $bucketItems = array_slice($bucketItems, 0, max(0, $totalLimit - $used));
            }

            $buckets[$bucket] = $bucketItems;
            $used += count($bucketItems);

            if ($used >= $totalLimit) {
                break;
            }
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, array<string, mixed>>
     */
    private function analysisHistoryItems(array $profile, ?User $user): Collection
    {
        $excludeId = $profile['exclude_analysis_id'] ?? null;

        return AnalisisPasteurizadora::query()
            ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
            ->with(['linea', 'usuario'])
            ->when($excludeId, fn ($query) => $query->whereKeyNot((int) $excludeId))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->limit($this->candidateLimit())
            ->get()
            ->filter(fn (AnalisisPasteurizadora $analysis): bool => $this->userCanViewAnalysis($user, $analysis))
            ->map(function (AnalisisPasteurizadora $analysis) use ($profile): ?array {
                $bucket = $this->classifyAnalysis($analysis, $profile);

                if (!$bucket) {
                    return null;
                }

                return array_merge($this->analysisSummary($analysis), [
                    'score' => $this->scoreGenericSource(
                        $this->analysisHaystack($analysis),
                        $profile,
                        $bucket,
                        optional($analysis->fecha_analisis ?: $analysis->created_at)->toDateString()
                    ) + ($this->isProblematicState($analysis->estado) ? 8 : 0),
                    'priority_bucket' => $bucket,
                    'priority_label' => self::BUCKET_LABELS[$bucket],
                ]);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, array<string, mixed>>
     */
    private function maintenanceEventHistoryItems(array $profile, ?User $user): Collection
    {
        $excludeId = $profile['exclude_event_id'] ?? null;

        return MaintenanceEvent::query()
            ->with(['linea'])
            ->whereIn('source_type', ['analisis_pasteurizadora', 'analisis_central_hidraulica'])
            ->when($excludeId, fn ($query) => $query->whereKeyNot((int) $excludeId))
            ->orderByDesc('detected_at')
            ->orderByDesc('id')
            ->limit($this->candidateLimit())
            ->get()
            ->filter(fn (MaintenanceEvent $event): bool => $this->userCanViewEvent($user, $event))
            ->map(function (MaintenanceEvent $event) use ($profile): ?array {
                $bucket = $this->classifyEvent($event, $profile);

                if (!$bucket) {
                    return null;
                }

                return array_merge($this->eventSummary($event), [
                    'score' => $this->scoreGenericSource(
                        $this->eventHaystack($event),
                        $profile,
                        $bucket,
                        optional($event->detected_at ?: $event->created_at)->toDateString()
                    ),
                    'priority_bucket' => $bucket,
                    'priority_label' => self::BUCKET_LABELS[$bucket],
                ]);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, array<string, mixed>>
     */
    private function centralAnalysisHistoryItems(array $profile, ?User $user): Collection
    {
        $excludeId = $profile['exclude_central_analysis_id'] ?? null;

        if (Schema::hasTable('analisis_central_hidraulica') === false) {
            return collect();
        }

        return AnalisisCentralHidraulica::query()
            ->with(['linea', 'usuario', 'configuracion', 'componente'])
            ->when($excludeId, fn ($query) => $query->whereKeyNot((int) $excludeId))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->limit($this->candidateLimit())
            ->get()
            ->filter(fn (AnalisisCentralHidraulica $analysis): bool => $this->userCanViewCentralAnalysis($user, $analysis))
            ->map(function (AnalisisCentralHidraulica $analysis) use ($profile): ?array {
                $bucket = $this->classifyCentralAnalysis($analysis, $profile);

                if (!$bucket) {
                    return null;
                }

                return array_merge($this->centralAnalysisSummary($analysis), [
                    'score' => $this->scoreGenericSource(
                        $this->centralAnalysisHaystack($analysis),
                        $profile,
                        $bucket,
                        optional($analysis->fecha_analisis ?: $analysis->created_at)->toDateString()
                    ) + ($this->isProblematicState($analysis->estado) ? 8 : 0),
                    'priority_bucket' => $bucket,
                    'priority_label' => self::BUCKET_LABELS[$bucket],
                ]);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, array<string, mixed>>
     */
    private function planHistoryItems(array $profile, ?User $user): Collection
    {
        return PlanAccion::query()
            ->with(['linea', 'maintenanceEvent'])
            ->where('tipo_equipo', User::MODULE_PASTEURIZADORA)
            ->where(function ($query): void {
                $query->whereNull('source')
                    ->orWhere('source', 'manual')
                    ->orWhere(function ($aiQuery): void {
                        $aiQuery->where('source', 'ai')
                            ->where('estado', 'approved');
                    });
            })
            ->orderByDesc('updated_at')
            ->limit($this->candidateLimit())
            ->get()
            ->filter(fn (PlanAccion $plan): bool => $this->userCanViewPlan($user, $plan))
            ->map(function (PlanAccion $plan) use ($profile): ?array {
                $bucket = $this->classifyPlan($plan, $profile);

                if (!$bucket) {
                    return null;
                }

                return array_merge($this->planSummary($plan), [
                    'score' => $this->scoreGenericSource(
                        $this->planHaystack($plan),
                        $profile,
                        $bucket,
                        optional($plan->fecha_ejecucion ?: $plan->updated_at)->toDateString()
                    ) + ($plan->completado ? 8 : 0),
                    'priority_bucket' => $bucket,
                    'priority_label' => self::BUCKET_LABELS[$bucket],
                ]);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, array<string, mixed>>
     */
    private function semanticHistoryItems(array $profile, ?User $user): Collection
    {
        if (!Schema::hasTable('maintenance_history_chunks')) {
            return collect();
        }

        return MaintenanceHistoryChunk::query()
            ->with(['linea'])
            ->where('module', User::MODULE_PASTEURIZADORA)
            ->latest('source_date')
            ->latest('updated_at')
            ->limit($this->historyIndexCandidateLimit())
            ->get()
            ->filter(fn (MaintenanceHistoryChunk $chunk): bool => !$user || $user->canAccessModule(User::MODULE_PASTEURIZADORA))
            ->map(function (MaintenanceHistoryChunk $chunk) use ($profile): ?array {
                $haystack = $this->historyChunkHaystack($chunk);
                $bucket = $this->classifyArraySource([
                    'linea_id' => $chunk->linea_id,
                    'linea_nombre' => $chunk->linea?->nombre ?? data_get($chunk->metadata, 'linea.nombre'),
                    'component_code' => data_get($chunk->metadata, 'extra.component_code'),
                    'component_name' => data_get($chunk->metadata, 'extra.component_name') ?? data_get($chunk->metadata, 'component.name'),
                    'area' => data_get($chunk->metadata, 'extra.area'),
                    'modulo' => data_get($chunk->metadata, 'extra.modulo'),
                    'nivel' => data_get($chunk->metadata, 'extra.nivel'),
                    'lado' => data_get($chunk->metadata, 'extra.lado'),
                    'haystack' => $haystack,
                ], $profile);
                $lexicalScore = $this->ranker->lexicalScore($profile['tokens'] ?? [], $haystack);

                if (!$bucket || $lexicalScore < $this->historyIndexMinScore()) {
                    return null;
                }

                return [
                    'source_group' => 'historial_semantico',
                    'type' => 'maintenance_history_chunk',
                    'reference' => 'Historial indexado #' . $chunk->source_id . ' fragmento ' . $chunk->chunk_index,
                    'date' => optional($chunk->source_date ?: $chunk->updated_at)->toDateString(),
                    'linea' => $chunk->linea?->nombre ?? data_get($chunk->metadata, 'linea.nombre'),
                    'component' => [
                        'name' => data_get($chunk->metadata, 'extra.component_name') ?? data_get($chunk->metadata, 'component.name'),
                        'code' => data_get($chunk->metadata, 'extra.component_code') ?? data_get($chunk->metadata, 'component.code'),
                    ],
                    'summary' => $this->sanitizer->sanitizeText((string) $chunk->content, 900),
                    'score' => (self::BUCKET_WEIGHTS[$bucket] ?? 0)
                        + ($lexicalScore * (float) config('maintenance_ai.history_index.lexical_weight', 2.0))
                        + $this->recencyScore(optional($chunk->source_date ?: $chunk->updated_at)->toDateString()),
                    'priority_bucket' => $bucket,
                    'priority_label' => self::BUCKET_LABELS[$bucket],
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyAnalysis(AnalisisPasteurizadora $analysis, array $profile): ?string
    {
        return $this->classifyArraySource([
            'linea_id' => $analysis->linea_id,
            'linea_nombre' => $analysis->linea?->nombre,
            'component_code' => $analysis->componente,
            'component_name' => $analysis->componente_nombre,
            'area' => $analysis->area,
            'modulo' => $analysis->modulo,
            'nivel' => $analysis->nivel,
            'lado' => $analysis->lado,
            'haystack' => $this->analysisHaystack($analysis),
        ], $profile);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyCentralAnalysis(AnalisisCentralHidraulica $analysis, array $profile): ?string
    {
        return $this->classifyArraySource([
            'linea_id' => $analysis->linea_id,
            'linea_nombre' => $analysis->linea?->nombre,
            'component_code' => $analysis->componente?->codigo,
            'component_name' => $analysis->componente_nombre,
            'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'modulo' => null,
            'nivel' => $analysis->piso,
            'lado' => $analysis->lado,
            'haystack' => $this->centralAnalysisHaystack($analysis),
        ], $profile);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyEvent(MaintenanceEvent $event, array $profile): ?string
    {
        return $this->classifyArraySource([
            'linea_id' => $event->linea_id,
            'linea_nombre' => $event->linea?->nombre ?? data_get($event->context_data, 'linea_nombre'),
            'component_code' => data_get($event->context_data, 'component_code'),
            'component_name' => data_get($event->context_data, 'component_name'),
            'area' => data_get($event->context_data, 'area'),
            'modulo' => data_get($event->context_data, 'modulo'),
            'nivel' => data_get($event->context_data, 'nivel'),
            'lado' => data_get($event->context_data, 'lado'),
            'haystack' => $this->eventHaystack($event),
        ], $profile);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyPlan(PlanAccion $plan, array $profile): ?string
    {
        return $this->classifyArraySource([
            'linea_id' => $plan->linea_id,
            'linea_nombre' => $plan->linea?->nombre ?? data_get($plan->source_metadata, 'linea_nombre'),
            'component_code' => data_get($plan->source_metadata, 'component_code') ?? data_get($plan->maintenanceEvent?->context_data, 'component_code'),
            'component_name' => data_get($plan->source_metadata, 'component_name') ?? data_get($plan->maintenanceEvent?->context_data, 'component_name'),
            'area' => $plan->area_pasteurizadora ?? data_get($plan->source_metadata, 'area') ?? data_get($plan->maintenanceEvent?->context_data, 'area'),
            'modulo' => data_get($plan->source_metadata, 'modulo') ?? data_get($plan->maintenanceEvent?->context_data, 'modulo'),
            'nivel' => data_get($plan->source_metadata, 'nivel') ?? data_get($plan->maintenanceEvent?->context_data, 'nivel'),
            'lado' => data_get($plan->source_metadata, 'lado') ?? data_get($plan->maintenanceEvent?->context_data, 'lado'),
            'haystack' => $this->planHaystack($plan),
        ], $profile);
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $profile
     */
    private function classifyArraySource(array $source, array $profile): ?string
    {
        $sameLine = $this->matchesLine($source['linea_id'] ?? null, $source['linea_nombre'] ?? null, $profile);
        $sameComponent = $this->matchesComponent($source['component_code'] ?? null, $source['component_name'] ?? null, $source['haystack'] ?? '', $profile);
        $samePosition = $sameLine && $sameComponent && $this->matchesPosition($source, $profile);
        $similarDamage = $this->matchesDamage((string) ($source['haystack'] ?? ''), $profile);

        if ($samePosition) {
            return self::BUCKET_SAME_COMPONENT_SAME_POSITION;
        }

        if ($sameLine && $sameComponent) {
            return self::BUCKET_SAME_COMPONENT_SAME_PASTEURIZER;
        }

        if (!$sameLine && $sameComponent) {
            return self::BUCKET_SAME_COMPONENT_OTHER_PASTEURIZERS;
        }

        if ($similarDamage) {
            return self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS;
        }

        return null;
    }

    private function analysisHaystack(AnalisisPasteurizadora $analysis): string
    {
        return implode(' ', array_filter([
            $analysis->linea?->nombre,
            $analysis->area,
            $analysis->componente,
            $analysis->componente_nombre,
            $analysis->modulo ? 'modulo ' . $analysis->modulo : null,
            $analysis->nivel,
            $analysis->lado,
            $analysis->estado,
            $analysis->actividad,
            $analysis->observaciones,
            json_encode($analysis->componentes_revisados_lista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    private function centralAnalysisHaystack(AnalisisCentralHidraulica $analysis): string
    {
        return implode(' ', array_filter([
            $analysis->linea?->nombre,
            AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            $analysis->componente?->codigo,
            $analysis->componente_nombre,
            $analysis->piso ? 'piso ' . $analysis->piso : null,
            $analysis->piso,
            $analysis->lado,
            $analysis->lado_label,
            $analysis->estado,
            $analysis->actividad,
            $analysis->observaciones,
            json_encode($analysis->componentes_revisados_lista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    private function eventHaystack(MaintenanceEvent $event): string
    {
        return implode(' ', array_filter([
            $event->linea?->nombre,
            $event->title,
            $event->description,
            $event->event_type,
            $event->severity,
            $event->detected_value,
            json_encode($event->context_data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    private function planHaystack(PlanAccion $plan): string
    {
        return implode(' ', array_filter([
            $plan->linea?->nombre,
            $plan->area_pasteurizadora,
            data_get($plan->source_metadata, 'component_code'),
            data_get($plan->source_metadata, 'component_name'),
            data_get($plan->source_metadata, 'modulo'),
            data_get($plan->source_metadata, 'nivel'),
            data_get($plan->source_metadata, 'lado'),
            $plan->actividad,
            $plan->detected_problem,
            $plan->technical_justification,
            $plan->risk_if_not_executed,
            $plan->execution_result,
            $plan->final_observations,
            json_encode($plan->approved_content ?: $plan->original_generated_content ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($plan->maintenanceEvent?->context_data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    private function historyChunkHaystack(MaintenanceHistoryChunk $chunk): string
    {
        return implode(' ', array_filter([
            (string) $chunk->title,
            (string) $chunk->content,
            (string) $chunk->searchable_text,
            (string) ($chunk->linea?->nombre ?? ''),
            json_encode($chunk->metadata ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisSummary(AnalisisPasteurizadora $analysis): array
    {
        return [
            'source_group' => 'historial_operativo',
            'type' => 'pasteurizadora_analysis',
            'reference' => 'Analisis pasteurizadora #' . $analysis->id,
            'date' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toDateString(),
            'linea' => $analysis->linea?->nombre,
            'area' => $analysis->area,
            'area_label' => PlanAccion::areasPasteurizadoraOpciones()[$analysis->area] ?? ucfirst((string) $analysis->area),
            'component' => [
                'name' => $analysis->componente_nombre,
                'code' => $analysis->componente,
                'modulo' => $analysis->modulo,
                'nivel' => $analysis->nivel,
                'lado' => $analysis->lado,
                'componentes_revisados' => $analysis->componentes_revisados_lista,
                'total_componentes' => $analysis->total_componentes,
            ],
            'condition' => [
                'estado' => $analysis->estado,
                'resolved_by_change' => (bool) $analysis->resuelto_por_cambio,
                'resolution_date' => optional($analysis->fecha_resolucion)->toDateString(),
                'resolution_note' => $this->sanitizer->sanitizeText((string) $analysis->nota_resolucion, 400),
            ],
            'technician_observation' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 700),
            'evidence_count' => count($analysis->evidencia_fotos ?? []),
            'registered_by' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function centralAnalysisSummary(AnalisisCentralHidraulica $analysis): array
    {
        return [
            'source_group' => 'historial_operativo',
            'type' => 'central_hidraulica_analysis',
            'reference' => 'Analisis central hidraulica #' . $analysis->id,
            'date' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toDateString(),
            'linea' => $analysis->linea?->nombre,
            'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            'area_label' => PlanAccion::areasPasteurizadoraOpciones()[AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA],
            'component' => [
                'name' => $analysis->componente_nombre,
                'code' => $analysis->componente?->codigo,
                'piso' => $analysis->piso,
                'nivel' => $analysis->piso,
                'lado' => $analysis->lado,
                'lado_label' => $analysis->lado_label,
                'componentes_revisados' => $analysis->componentes_revisados_lista,
                'total_componentes' => $analysis->total_componentes,
            ],
            'condition' => [
                'estado' => $analysis->estado,
                'resolved_by_change' => (bool) $analysis->resuelto_por_cambio,
                'resolution_date' => optional($analysis->fecha_resolucion)->toDateString(),
                'resolution_note' => $this->sanitizer->sanitizeText((string) $analysis->nota_resolucion, 400),
            ],
            'technician_observation' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 700),
            'notes' => $this->sanitizer->sanitizeText((string) $analysis->observaciones, 500),
            'evidence_count' => count($analysis->evidencia_fotos ?? []),
            'registered_by' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventSummary(MaintenanceEvent $event): array
    {
        return [
            'source_group' => 'evento_mantenimiento',
            'type' => 'maintenance_event',
            'reference' => 'Evento #' . $event->id,
            'date' => optional($event->detected_at ?: $event->created_at)->toDateString(),
            'linea' => $event->linea?->nombre ?? data_get($event->context_data, 'linea_nombre'),
            'area' => data_get($event->context_data, 'area'),
            'component' => [
                'name' => data_get($event->context_data, 'component_name'),
                'code' => data_get($event->context_data, 'component_code'),
                'modulo' => data_get($event->context_data, 'modulo'),
                'piso' => data_get($event->context_data, 'piso'),
                'nivel' => data_get($event->context_data, 'nivel'),
                'lado' => data_get($event->context_data, 'lado'),
                'lado_label' => data_get($event->context_data, 'lado_label'),
            ],
            'condition' => [
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'status' => $event->status,
                'detected_value' => $event->detected_value,
            ],
            'summary' => $this->sanitizer->sanitizeText((string) ($event->description ?: $event->title), 700),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planSummary(PlanAccion $plan): array
    {
        return [
            'source_group' => 'plan_accion',
            'type' => $plan->source === 'ai' ? 'ai_action_plan' : 'action_plan',
            'reference' => 'Plan #' . $plan->id,
            'date' => optional($plan->fecha_ejecucion ?: $plan->updated_at)->toDateString(),
            'linea' => $plan->linea?->nombre,
            'area' => $plan->area_pasteurizadora,
            'component' => [
                'name' => data_get($plan->source_metadata, 'component_name') ?? data_get($plan->maintenanceEvent?->context_data, 'component_name'),
                'code' => data_get($plan->source_metadata, 'component_code') ?? data_get($plan->maintenanceEvent?->context_data, 'component_code'),
                'modulo' => data_get($plan->source_metadata, 'modulo') ?? data_get($plan->maintenanceEvent?->context_data, 'modulo'),
                'piso' => data_get($plan->source_metadata, 'piso') ?? data_get($plan->maintenanceEvent?->context_data, 'piso'),
                'nivel' => data_get($plan->source_metadata, 'nivel') ?? data_get($plan->maintenanceEvent?->context_data, 'nivel'),
                'lado' => data_get($plan->source_metadata, 'lado') ?? data_get($plan->maintenanceEvent?->context_data, 'lado'),
                'lado_label' => data_get($plan->source_metadata, 'lado_label') ?? data_get($plan->maintenanceEvent?->context_data, 'lado_label'),
            ],
            'plan' => [
                'actividad' => $this->sanitizer->sanitizeText((string) $plan->actividad, 500),
                'estado' => $plan->estado,
                'source' => $plan->source,
                'priority_level' => $plan->priority_level,
                'maintenance_type' => $plan->maintenance_type,
                'completed' => (bool) $plan->completado,
                'effectiveness' => $plan->effectiveness,
                'execution_result' => $this->sanitizer->sanitizeText((string) $plan->execution_result, 700),
            ],
            'technical_basis' => [
                'detected_problem' => $this->sanitizer->sanitizeText((string) $plan->detected_problem, 500),
                'technical_justification' => $this->sanitizer->sanitizeText((string) $plan->technical_justification, 700),
                'risk_if_not_executed' => $this->sanitizer->sanitizeText((string) $plan->risk_if_not_executed, 500),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function scoreGenericSource(string $haystack, array $profile, string $bucket, ?string $date): float
    {
        $score = (float) (self::BUCKET_WEIGHTS[$bucket] ?? 0);
        $score += $this->ranker->lexicalScore($profile['tokens'] ?? [], $haystack) * 2.0;
        $score += $this->ranker->lexicalScore($profile['component_terms'] ?? [], $haystack) * 3.0;
        $score += $this->ranker->lexicalScore($profile['damage']['terms'] ?? [], $haystack) * 4.0;
        $score += $this->recencyScore($date);

        return round($score, 4);
    }

    private function recencyScore(?string $date): float
    {
        if (!$date) {
            return 0.0;
        }

        $days = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($date)->startOfDay(), false);
        $age = abs(min(0, $days));

        return max(0.0, 20.0 - min(20.0, $age / 30.0));
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $profile
     */
    private function matchesPosition(array $source, array $profile): bool
    {
        $hasRequestedPosition = ($profile['areas'] ?? []) !== []
            || ($profile['modulos'] ?? []) !== []
            || ($profile['niveles'] ?? []) !== []
            || ($profile['lados'] ?? []) !== [];

        if (!$hasRequestedPosition) {
            return false;
        }

        return $this->matchesValue((string) ($source['area'] ?? ''), $profile['areas'] ?? [])
            && $this->matchesValue((string) ($source['modulo'] ?? ''), $profile['modulos'] ?? [])
            && $this->matchesValue((string) ($source['nivel'] ?? ''), $profile['niveles'] ?? [])
            && $this->matchesValue((string) ($source['lado'] ?? ''), $profile['lados'] ?? []);
    }

    /**
     * @param  array<int, mixed>  $accepted
     */
    private function matchesValue(string $value, array $accepted): bool
    {
        if ($accepted === []) {
            return true;
        }

        $value = Str::lower(Str::ascii(trim($value)));

        return in_array($value, array_map(fn ($item): string => Str::lower(Str::ascii((string) $item)), $accepted), true);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesLine(mixed $lineaId, mixed $lineaNombre, array $profile): bool
    {
        $ids = array_map('intval', $profile['linea_ids'] ?? []);
        $names = array_map(fn ($name): string => Str::upper((string) $name), $profile['lineas'] ?? []);

        if ($ids !== [] && $lineaId && in_array((int) $lineaId, $ids, true)) {
            return true;
        }

        return $lineaNombre && in_array(Str::upper((string) $lineaNombre), $names, true);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesComponent(mixed $code, mixed $name, string $haystack, array $profile): bool
    {
        $codes = array_map(fn ($item): string => Str::upper((string) $item), $profile['component_codes'] ?? []);

        if ($code && in_array(Str::upper((string) $code), $codes, true)) {
            return true;
        }

        $sourceTokens = $this->ranker->tokenize(implode(' ', [
            (string) $code,
            (string) $name,
            $haystack,
        ]));

        return $this->tokensOverlap($profile['component_terms'] ?? [], $sourceTokens);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesDamage(string $haystack, array $profile): bool
    {
        $damageTerms = $profile['damage']['terms'] ?? [];

        return $damageTerms !== [] && $this->tokensOverlap($damageTerms, $this->ranker->tokenize($haystack));
    }

    /**
     * @param  array<int, string>  $needles
     * @param  array<int, string>  $haystack
     */
    private function tokensOverlap(array $needles, array $haystack): bool
    {
        $needles = array_values(array_unique(array_filter($needles)));
        $haystack = array_values(array_unique(array_filter($haystack)));

        return $needles !== [] && array_intersect($needles, $haystack) !== [];
    }

    /**
     * @param  array<string, mixed>  $rankerProfile
     * @param  array<int, string>  $lineas
     * @param  array<string, mixed>  $pageContext
     * @return array<int, array<string, mixed>>
     */
    private function componentCandidates(string $query, array $rankerProfile, array $lineas, array $pageContext): array
    {
        $tokens = array_values(array_unique(array_filter(array_merge(
            $rankerProfile['tokens'] ?? $this->ranker->tokenize($query),
            $this->ranker->tokenize((string) ($pageContext['component_name'] ?? '')),
            $this->ranker->tokenize((string) ($pageContext['component_code'] ?? ''))
        ))));
        $searchLineas = $lineas !== [] ? $lineas : array_keys(AnalisisPasteurizadora::PASTEURIZADORES);
        $candidates = [];

        foreach ($searchLineas as $lineaNombre) {
            foreach (AnalisisPasteurizadora::getComponentesPorLinea($lineaNombre) as $code => $config) {
                $name = (string) ($config['nombre'] ?? $code);
                $candidateTokens = $this->ranker->tokenize($code . ' ' . $name);
                $score = count(array_intersect($tokens, $candidateTokens));

                if (Str::upper((string) ($pageContext['component_code'] ?? '')) === Str::upper((string) $code)) {
                    $score += 5;
                }

                if ($score <= 0) {
                    continue;
                }

                $candidates[] = [
                    'code' => (string) $code,
                    'name' => $name,
                    'linea' => $lineaNombre,
                    'search_terms' => $candidateTokens,
                    'score' => $score,
                ];
            }
        }

        if (Schema::hasTable('central_hidraulica_componentes')) {
            CentralHidraulicaComponente::query()
                ->where('activo', true)
                ->orderBy('orden')
                ->get()
                ->each(function (CentralHidraulicaComponente $component) use (&$candidates, $tokens, $pageContext): void {
                    $code = (string) $component->codigo;
                    $name = $component->nombre_display;
                    $candidateTokens = $this->ranker->tokenize($code . ' ' . $name);
                    $score = count(array_intersect($tokens, $candidateTokens));

                    if (Str::upper((string) ($pageContext['component_code'] ?? '')) === Str::upper($code)) {
                        $score += 5;
                    }

                    if ($score <= 0) {
                        return;
                    }

                    $candidates[] = [
                        'code' => $code,
                        'name' => $name,
                        'linea' => null,
                        'search_terms' => $candidateTokens,
                        'score' => $score,
                    ];
                });
        }

        return collect($candidates)
            ->sortByDesc('score')
            ->unique(fn (array $candidate): string => $candidate['linea'] . '|' . $candidate['code'])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function lineReferences(string $query): array
    {
        $normalized = Str::upper(Str::ascii($query));
        $lineas = [];

        if (preg_match_all('/\bP\s*-\s*0?(\d{1,2})\b/', $normalized, $matches)) {
            foreach ($matches[1] as $number) {
                $lineas[] = sprintf('P-%02d', (int) $number);
            }
        }

        if (preg_match_all('/\b(?:PASTEURIZADORA|PASTEURIZADOR|LINEA)\s*[-#]?\s*0?(\d{1,2})\b/', $normalized, $matches)) {
            foreach ($matches[1] as $number) {
                $lineas[] = sprintf('P-%02d', (int) $number);
            }
        }

        return collect($lineas)
            ->filter(fn (string $linea): bool => array_key_exists($linea, AnalisisPasteurizadora::PASTEURIZADORES))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $lineas
     * @return array<int, int>
     */
    private function lineIdsForNames(array $lineas): array
    {
        if ($lineas === []) {
            return [];
        }

        return Linea::query()
            ->whereIn('nombre', $lineas)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function areas(string $normalizedQuery, array $pageContext): array
    {
        $areas = [];
        $contextArea = $pageContext['area'] ?? $pageContext['area_pasteurizadora'] ?? null;

        if ($contextArea) {
            $areas[] = AnalisisPasteurizadora::normalizarArea((string) $contextArea);
        }

        if (str_contains($normalizedQuery, 'central hidraulica') || str_contains($normalizedQuery, 'hidraulica')) {
            $areas[] = AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA;
        }

        if (str_contains($normalizedQuery, 'mecanica')) {
            $areas[] = AnalisisPasteurizadora::AREA_MECANICA;
        }

        return array_values(array_unique($areas));
    }

    /**
     * @return array<int, string>
     */
    private function modulos(string $normalizedQuery, array $pageContext): array
    {
        $modulos = [];

        if (isset($pageContext['modulo']) && is_numeric($pageContext['modulo'])) {
            $modulos[] = (string) (int) $pageContext['modulo'];
        }

        if (preg_match_all('/\bmodulo\s*[-#]?\s*(\d{1,2})\b/', $normalizedQuery, $matches)) {
            foreach ($matches[1] as $number) {
                $modulos[] = (string) (int) $number;
            }
        }

        return array_values(array_unique($modulos));
    }

    /**
     * @return array<int, string>
     */
    private function niveles(string $normalizedQuery, array $pageContext): array
    {
        $niveles = [];

        if (!empty($pageContext['nivel'])) {
            $niveles[] = Str::lower(Str::ascii((string) $pageContext['nivel']));
        }

        if (!empty($pageContext['piso'])) {
            $niveles[] = Str::lower(Str::ascii((string) $pageContext['piso']));
        }

        foreach (['superior', 'inferior'] as $nivel) {
            if (str_contains($normalizedQuery, $nivel)) {
                $niveles[] = $nivel;
            }
        }

        return array_values(array_unique($niveles));
    }

    /**
     * @return array<int, string>
     */
    private function lados(string $normalizedQuery, array $pageContext): array
    {
        $lados = [];

        if (!empty($pageContext['lado'])) {
            $lados[] = Str::lower(Str::ascii((string) $pageContext['lado']));
        }

        foreach (['vapor', 'pasillo'] as $lado) {
            if (str_contains($normalizedQuery, $lado)) {
                $lados[] = $lado;
            }
        }

        if (preg_match_all('/\blado\s*[-#_]?\s*([12])\b/', $normalizedQuery, $matches)) {
            foreach ($matches[1] as $number) {
                $lados[] = 'lado_' . $number;
            }
        }

        return array_values(array_unique($lados));
    }

    /**
     * @return array{labels: array<int, string>, terms: array<int, string>}
     */
    private function damageProfile(string $query): array
    {
        $tokens = $this->ranker->tokenize($query);
        $labels = [];
        $terms = [];

        foreach (self::DAMAGE_GROUPS as $label => $groupTerms) {
            $matched = array_values(array_intersect($tokens, $groupTerms));

            if ($matched !== []) {
                $labels[] = $label;
                $terms = array_merge($terms, $matched, $groupTerms);
            }
        }

        return [
            'labels' => array_values(array_unique($labels)),
            'terms' => array_values(array_unique($terms)),
        ];
    }

    private function extractSeverity(string $normalizedQuery): ?string
    {
        if (str_contains($normalizedQuery, 'critico') || str_contains($normalizedQuery, 'danado')) {
            return 'critical';
        }

        if (str_contains($normalizedQuery, 'severo') || str_contains($normalizedQuery, 'alto')) {
            return 'high';
        }

        if (str_contains($normalizedQuery, 'moderado') || str_contains($normalizedQuery, 'revision')) {
            return 'medium';
        }

        return null;
    }

    private function isProblematicState(?string $estado): bool
    {
        return AnalisisPasteurizadora::esEstadoSeguimientoReinspeccion($estado);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function hasTechnicalSignal(array $profile): bool
    {
        return ($profile['lineas'] ?? []) !== []
            || ($profile['linea_ids'] ?? []) !== []
            || ($profile['component_codes'] ?? []) !== []
            || ($profile['component_terms'] ?? []) !== []
            || ($profile['damage']['terms'] ?? []) !== [];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function emptyBuckets(): array
    {
        return array_fill_keys(array_keys(self::BUCKET_LABELS), []);
    }

    private function emptyContext(string $reason): array
    {
        return [
            'available' => false,
            'generated_at' => now()->toIso8601String(),
            'module' => User::MODULE_PASTEURIZADORA,
            'reason' => $reason,
            'detected_context' => [],
            'search_priority' => array_values(self::BUCKET_LABELS),
            'historical_sources' => $this->emptyBuckets(),
            'technical_sources' => [],
            'coverage' => [
                'historical_records_count' => 0,
                'historical_records_by_priority' => array_fill_keys(array_keys(self::BUCKET_LABELS), 0),
                'technical_sources_count' => 0,
                'warnings' => [$reason],
            ],
        ];
    }

    private function coverageWarnings(bool $hasHistory): array
    {
        if ($hasHistory) {
            return [];
        }

        return ['No se encontraron antecedentes internos de Pasteurizadora para el contexto detectado.'];
    }

    private function historyLimitPerBucket(): int
    {
        return max(1, (int) config('maintenance_ai.technical_context.history_limit_per_bucket', 3));
    }

    private function totalHistoryLimit(): int
    {
        return max(1, (int) config('maintenance_ai.technical_context.total_history_limit', 10));
    }

    private function candidateLimit(): int
    {
        return max(25, (int) config('maintenance_ai.technical_context.candidate_limit', 60));
    }

    private function historyIndexCandidateLimit(): int
    {
        return max(10, (int) config('maintenance_ai.history_index.candidate_limit', 80));
    }

    private function historyIndexMinScore(): float
    {
        return (float) config('maintenance_ai.history_index.min_score', 2.0);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function stripScore(array $item): array
    {
        unset($item['score']);

        return $item;
    }

    private function userCanViewAnalysis(?User $user, AnalisisPasteurizadora $analysis): bool
    {
        return !$user || $user->canAccessPasteurizadoraArea($analysis->area);
    }

    private function userCanViewCentralAnalysis(?User $user, AnalisisCentralHidraulica $analysis): bool
    {
        return !$user || $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA);
    }

    private function userCanViewEvent(?User $user, MaintenanceEvent $event): bool
    {
        $area = data_get($event->context_data, 'area');

        return !$user || !$area || $user->canAccessPasteurizadoraArea((string) $area);
    }

    private function userCanViewPlan(?User $user, PlanAccion $plan): bool
    {
        if (!$user) {
            return true;
        }

        if (!$user->canViewPlanActionType(User::MODULE_PASTEURIZADORA)) {
            return false;
        }

        if ($plan->area_pasteurizadora && !$user->canAccessPasteurizadoraArea($plan->area_pasteurizadora)) {
            return false;
        }

        return !($plan->source === 'ai'
            && $plan->estado !== 'approved'
            && !$user->canReviewPasteurizadoraAiPlans($plan->area_pasteurizadora));
    }
}
