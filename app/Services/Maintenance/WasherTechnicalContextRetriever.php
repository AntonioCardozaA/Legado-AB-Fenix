<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\MaintenanceHistoryChunk;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WasherTechnicalContextRetriever
{
    private const BUCKET_SAME_COMPONENT_SAME_WASHER = 'same_component_same_washer';
    private const BUCKET_SAME_TYPE_SAME_WASHER = 'same_type_same_washer';
    private const BUCKET_SAME_COMPONENT_OTHER_WASHERS = 'same_component_other_washers';
    private const BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS = 'similar_failure_other_components';

    private const BUCKET_LABELS = [
        self::BUCKET_SAME_COMPONENT_SAME_WASHER => 'Registros anteriores del mismo componente en la misma lavadora',
        self::BUCKET_SAME_TYPE_SAME_WASHER => 'Registros de componentes del mismo tipo dentro de la misma lavadora',
        self::BUCKET_SAME_COMPONENT_OTHER_WASHERS => 'Registros del mismo componente en otras lavadoras o lineas',
        self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS => 'Casos similares encontrados en otros componentes',
    ];

    private const BUCKET_WEIGHTS = [
        self::BUCKET_SAME_COMPONENT_SAME_WASHER => 400,
        self::BUCKET_SAME_TYPE_SAME_WASHER => 300,
        self::BUCKET_SAME_COMPONENT_OTHER_WASHERS => 200,
        self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS => 100,
    ];

    private const DAMAGE_GROUPS = [
        'fuga_aceite' => [
            'fuga',
            'fugas',
            'aceite',
            'lubricante',
            'lubricacion',
            'goteo',
            'derrame',
            'reten',
            'retenes',
            'sello',
            'sellos',
            'empaque',
            'empaques',
            'respiradero',
            'nivel',
            'carcasa',
        ],
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
            'vibrando',
            'alineacion',
            'desalineado',
        ],
        'ruido' => [
            'ruido',
            'sonido',
            'ronquido',
            'chillido',
            'golpeteo',
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
        'temperatura' => [
            'temperatura',
            'caliente',
            'calentamiento',
            'sobrecalentamiento',
        ],
        'tension' => [
            'tension',
            'tensar',
            'destensado',
            'elongacion',
            'estiramiento',
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
        'lavadora',
        'lavadoras',
        'linea',
        'lineas',
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
        if ($user && !$user->canAccessModule(User::MODULE_LAVADORA)) {
            return $this->emptyContext('El usuario no tiene acceso al modulo de lavadora.');
        }

        $profile = $this->buildProfile($question, $pageContext);

        return $this->buildContext($profile, $user);
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    public function forEvent(MaintenanceEvent $event, array $current = []): array
    {
        $event->loadMissing(['linea', 'componente']);

        $sourceAnalysis = $event->source_type === 'analisis_lavadora'
            ? AnalisisLavadora::query()->with(['linea', 'componente'])->find($event->source_id)
            : null;

        $query = implode(' ', array_filter([
            (string) $event->title,
            (string) $event->description,
            (string) $event->event_type,
            (string) $event->severity,
            (string) $event->detected_value,
            (string) ($current['linea_nombre'] ?? $event->linea?->nombre),
            (string) ($current['component_name'] ?? $event->componente?->nombre),
            (string) ($current['component_code'] ?? $event->componente?->codigo),
            (string) ($current['reductor'] ?? $sourceAnalysis?->reductor),
            (string) ($current['lado'] ?? $sourceAnalysis?->lado),
            (string) ($current['estado'] ?? $sourceAnalysis?->estado),
            (string) ($current['observaciones'] ?? $sourceAnalysis?->actividad),
        ]));

        $profile = $this->buildProfile($query, [
            'linea_nombre' => $current['linea_nombre'] ?? $event->linea?->nombre,
            'component_name' => $current['component_name'] ?? $event->componente?->nombre,
            'estado' => $current['estado'] ?? $event->detected_value,
        ]);

        $profile['event'] = [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'severity' => $event->severity,
            'source_type' => $event->source_type,
            'source_id' => $event->source_id,
        ];
        $profile['exclude_analysis_id'] = $sourceAnalysis?->id;
        $profile['exclude_event_id'] = $event->id;

        if ($event->linea_id) {
            $profile['linea_ids'] = array_values(array_unique(array_merge(
                $profile['linea_ids'],
                [(int) $event->linea_id]
            )));
        }

        if ($event->linea?->nombre) {
            $profile['lineas'] = array_values(array_unique(array_merge(
                $profile['lineas'],
                [Str::upper((string) $event->linea->nombre)]
            )));
        }

        if ($event->componente_id) {
            $profile['component_ids'] = array_values(array_unique(array_merge(
                $profile['component_ids'],
                [(int) $event->componente_id]
            )));
            $profile['exact_component_ids'] = [(int) $event->componente_id];
        }

        if ($event->componente) {
            $baseCode = AnalisisLavadora::codigoBaseComponente((string) $event->componente->codigo);
            $profile['component_base_codes'] = array_values(array_unique(array_filter(array_merge(
                $profile['component_base_codes'],
                [$baseCode]
            ))));
            $profile['component_terms'] = array_values(array_unique(array_filter(array_merge(
                $profile['component_terms'],
                $this->ranker->tokenize($event->componente->nombre . ' ' . $event->componente->codigo . ' ' . $baseCode)
            ))));
            $profile['primary_component'] = [
                'id' => $event->componente->id,
                'name' => $event->componente->nombre,
                'code' => $event->componente->codigo,
                'base_code' => $baseCode,
            ];
        }

        if ($sourceAnalysis) {
            $profile['reductores'] = array_values(array_unique(array_filter(array_merge(
                $profile['reductores'],
                [$this->normalizeSubcomponent((string) $sourceAnalysis->reductor)]
            ))));
            $profile['lados'] = array_values(array_unique(array_filter(array_merge(
                $profile['lados'],
                [$this->normalizeSide((string) $sourceAnalysis->lado)]
            ))));
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
            return $this->emptyContext('No se detecto una falla, componente o lavadora especifica para recuperar antecedentes tecnicos.');
        }

        $historicalSources = $this->historicalSources($profile, $user);
        $technicalSources = $this->technicalSources($profile);
        $bucketCounts = collect($historicalSources)
            ->map(fn (array $items): int => count($items))
            ->all();
        $hasHistory = collect($bucketCounts)->sum() > 0;
        $hasTechnicalSources = count($technicalSources) > 0;

        return [
            'available' => true,
            'generated_at' => now()->toIso8601String(),
            'module' => User::MODULE_LAVADORA,
            'detected_context' => $this->detectedContext($profile),
            'search_priority' => array_values(self::BUCKET_LABELS),
            'historical_sources' => $historicalSources,
            'technical_sources' => $technicalSources,
            'coverage' => [
                'historical_records_count' => collect($bucketCounts)->sum(),
                'historical_records_by_priority' => $bucketCounts,
                'technical_sources_count' => count($technicalSources),
                'has_same_component_history' => ($bucketCounts[self::BUCKET_SAME_COMPONENT_SAME_WASHER] ?? 0) > 0,
                'has_same_type_same_washer_history' => ($bucketCounts[self::BUCKET_SAME_TYPE_SAME_WASHER] ?? 0) > 0,
                'has_same_component_other_washers_history' => ($bucketCounts[self::BUCKET_SAME_COMPONENT_OTHER_WASHERS] ?? 0) > 0,
                'has_similar_failure_history' => ($bucketCounts[self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS] ?? 0) > 0,
                'warnings' => $this->coverageWarnings($hasHistory, $hasTechnicalSources, $bucketCounts),
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
        $normalizedQuery = Str::lower(Str::ascii($query));
        $lineas = $this->lineReferences((string) ($rankerProfile['query'] ?? $query));
        $lineaIds = $this->lineIdsForNames($lineas);
        $damage = $this->damageProfile($query);
        $componentCandidates = $this->componentCandidates($query, $rankerProfile, $lineas, $damage['terms']);
        $componentIds = collect($componentCandidates)->pluck('id')->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $baseCodes = collect($componentCandidates)->pluck('base_code')->filter()->unique()->values()->all();
        $componentTerms = collect($rankerProfile['component_terms'] ?? [])
            ->merge(collect($componentCandidates)->pluck('search_terms')->flatten())
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'query' => $this->sanitizer->sanitizeText((string) ($rankerProfile['query'] ?? $query), 1600),
            'tokens' => $rankerProfile['tokens'] ?? $this->ranker->tokenize($query),
            'lineas' => $lineas,
            'linea_ids' => $lineaIds,
            'component_ids' => $componentIds,
            'exact_component_ids' => [],
            'component_base_codes' => $baseCodes,
            'component_terms' => $componentTerms,
            'component_candidates' => $componentCandidates,
            'primary_component' => $componentCandidates[0] ?? null,
            'reductores' => $this->extractSubcomponents($normalizedQuery),
            'lados' => $this->extractSides($normalizedQuery),
            'damage' => $damage,
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
                    'id' => $component['id'] ?? null,
                    'name' => $component['name'] ?? null,
                    'code' => $component['code'] ?? null,
                    'base_code' => $component['base_code'] ?? null,
                    'linea' => $component['linea'] ?? null,
                    'reductor' => $component['reductor'] ?? null,
                ])
                ->values()
                ->all(),
            'primary_component' => $profile['primary_component'] ?? null,
            'subcomponents' => [
                'reductores' => $profile['reductores'] ?? [],
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
            ->concat($this->analysisHistoryItems($profile))
            ->concat($this->maintenanceEventHistoryItems($profile))
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
    private function analysisHistoryItems(array $profile): Collection
    {
        $excludeId = $profile['exclude_analysis_id'] ?? null;

        return $this->currentOrPastWasherAnalysisQuery()
            ->with(['linea', 'componente', 'usuario'])
            ->when($excludeId, fn ($query) => $query->whereKeyNot((int) $excludeId))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->limit($this->candidateLimit())
            ->get()
            ->map(function (AnalisisLavadora $analysis) use ($profile): ?array {
                $bucket = $this->classifyAnalysis($analysis, $profile);

                if (!$bucket) {
                    return null;
                }

                return array_merge($this->analysisSummary($analysis), [
                    'score' => $this->scoreAnalysis($analysis, $profile, $bucket),
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
    private function maintenanceEventHistoryItems(array $profile): Collection
    {
        $excludeId = $profile['exclude_event_id'] ?? null;

        return MaintenanceEvent::query()
            ->with(['linea', 'componente'])
            ->when($excludeId, fn ($query) => $query->whereKeyNot((int) $excludeId))
            ->orderByDesc('detected_at')
            ->orderByDesc('id')
            ->limit($this->candidateLimit())
            ->get()
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
    private function planHistoryItems(array $profile, ?User $user): Collection
    {
        return PlanAccion::query()
            ->with(['linea', 'maintenanceEvent.componente', 'responsable', 'ejecutadoPor'])
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

        $queryEmbedding = $this->ranker->queryEmbedding((string) ($profile['query'] ?? ''));

        return MaintenanceHistoryChunk::query()
            ->with(['linea', 'componente'])
            ->where('module', User::MODULE_LAVADORA)
            ->latest('source_date')
            ->latest('updated_at')
            ->limit($this->historyIndexCandidateLimit())
            ->get()
            ->filter(fn (MaintenanceHistoryChunk $chunk): bool => $this->userCanViewHistoryChunk($user, $chunk))
            ->map(function (MaintenanceHistoryChunk $chunk) use ($profile, $queryEmbedding): ?array {
                $ranking = $this->rankHistoryChunk($chunk, $profile, $queryEmbedding);

                if ($ranking === null || (float) ($ranking['score'] ?? 0) < $this->historyIndexMinScore()) {
                    return null;
                }

                return $this->historyChunkSummary($chunk, $ranking);
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<int, float>  $queryEmbedding
     * @return array<string, mixed>|null
     */
    private function rankHistoryChunk(MaintenanceHistoryChunk $chunk, array $profile, array $queryEmbedding): ?array
    {
        $haystack = $this->historyChunkHaystack($chunk);
        $bucket = $this->classifyHistoryChunk($chunk, $profile, $haystack);
        $lexicalScore = $this->ranker->lexicalScore($profile['tokens'] ?? [], $haystack);
        $semanticScore = $this->semanticScore($queryEmbedding, $chunk->embedding ?? []);
        $metadataScore = $this->historyMetadataScore($chunk, $profile, $haystack);

        if (!$bucket && ($semanticScore >= 0.72 || $lexicalScore >= 2.5)) {
            $bucket = self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS;
        }

        if (!$bucket) {
            return null;
        }

        $score = (float) (self::BUCKET_WEIGHTS[$bucket] ?? 0)
            + ($lexicalScore * (float) config('maintenance_ai.history_index.lexical_weight', 2.0))
            + ($metadataScore * (float) config('maintenance_ai.history_index.metadata_weight', 1.0))
            + ($semanticScore * (float) config('maintenance_ai.history_index.semantic_weight', 18.0))
            + $this->recencyScore(optional($chunk->source_date ?: $chunk->updated_at)->toDateString());

        return [
            'score' => round($score, 4),
            'lexical_score' => round($lexicalScore, 4),
            'metadata_score' => round($metadataScore, 4),
            'semantic_score' => round($semanticScore, 4),
            'priority_bucket' => $bucket,
            'priority_label' => self::BUCKET_LABELS[$bucket],
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyHistoryChunk(MaintenanceHistoryChunk $chunk, array $profile, string $haystack): ?string
    {
        $sameLine = $this->matchesLine((int) $chunk->linea_id, (string) ($chunk->linea?->nombre ?? ''), $profile);
        $sameExactComponent = $this->matchesExactComponent($chunk->componente_id, $profile);
        $sameType = $this->matchesHistoryComponentType($chunk, $profile, $haystack);
        $sameRequestedSubcomponent = $this->matchesHistorySubcomponent($haystack, $profile);
        $similarDamage = $this->matchesHistoryDamage($chunk, $profile, $haystack);

        if ($sameLine && ($sameExactComponent || ($sameType && $sameRequestedSubcomponent))) {
            return self::BUCKET_SAME_COMPONENT_SAME_WASHER;
        }

        if ($sameLine && $sameType) {
            return self::BUCKET_SAME_TYPE_SAME_WASHER;
        }

        if (!$sameLine && $sameType) {
            return self::BUCKET_SAME_COMPONENT_OTHER_WASHERS;
        }

        if ($similarDamage) {
            return self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesHistoryComponentType(MaintenanceHistoryChunk $chunk, array $profile, string $haystack): bool
    {
        if ($chunk->componente && $this->matchesComponentType($chunk->componente, $profile)) {
            return true;
        }

        $componentBaseCodes = $profile['component_base_codes'] ?? [];
        $metadataBaseCode = data_get($chunk->metadata, 'component.base_code');

        if ($metadataBaseCode && in_array((string) $metadataBaseCode, $componentBaseCodes, true)) {
            return true;
        }

        $metadataTerms = (array) data_get($chunk->metadata, 'component_terms', []);
        $haystackTokens = $this->ranker->tokenize($haystack);

        return $this->tokensOverlap($profile['component_terms'] ?? [], array_values(array_unique(array_merge(
            $haystackTokens,
            $metadataTerms
        ))));
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesHistoryDamage(MaintenanceHistoryChunk $chunk, array $profile, string $haystack): bool
    {
        $damageTerms = $profile['damage']['terms'] ?? [];

        if ($damageTerms === []) {
            return false;
        }

        $historyDamageTerms = (array) data_get($chunk->metadata, 'damage_terms', []);
        $chunkDamageTerms = (array) data_get($chunk->metadata, 'extra.damage_terms', []);

        return $this->tokensOverlap($damageTerms, array_values(array_unique(array_merge(
            $this->ranker->tokenize($haystack),
            $historyDamageTerms,
            $chunkDamageTerms
        ))));
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesHistorySubcomponent(string $haystack, array $profile): bool
    {
        $reductores = array_filter($profile['reductores'] ?? []);
        $lados = array_filter($profile['lados'] ?? []);

        if ($reductores === [] && $lados === []) {
            return false;
        }

        $normalized = Str::lower(Str::ascii($haystack));
        $reductorMatches = $reductores === [];

        foreach ($reductores as $reductor) {
            if ($reductor && str_contains($normalized, (string) $reductor)) {
                $reductorMatches = true;
                break;
            }
        }

        $ladoMatches = $lados === [];

        foreach ($lados as $lado) {
            if ($lado && str_contains($normalized, (string) $lado)) {
                $ladoMatches = true;
                break;
            }
        }

        return $reductorMatches && $ladoMatches;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function historyMetadataScore(MaintenanceHistoryChunk $chunk, array $profile, string $haystack): float
    {
        $score = 0.0;
        $haystackTokens = $this->ranker->tokenize($haystack);
        $componentTerms = $profile['component_terms'] ?? [];
        $damageTerms = $profile['damage']['terms'] ?? [];
        $metadataTerms = (array) data_get($chunk->metadata, 'component_terms', []);
        $metadataDamageTerms = (array) data_get($chunk->metadata, 'damage_terms', []);

        if ($this->matchesLine((int) $chunk->linea_id, (string) ($chunk->linea?->nombre ?? ''), $profile)) {
            $score += 5.0;
        }

        if ($this->matchesExactComponent($chunk->componente_id, $profile)) {
            $score += 5.0;
        }

        if ($this->tokensOverlap($componentTerms, array_values(array_unique(array_merge($haystackTokens, $metadataTerms))))) {
            $score += 4.0;
        }

        if ($this->tokensOverlap($damageTerms, array_values(array_unique(array_merge($haystackTokens, $metadataDamageTerms))))) {
            $score += 5.0;
        }

        if ((bool) data_get($chunk->metadata, 'extra.completed', false)
            || (bool) data_get($chunk->metadata, 'extra.has_repair_result', false)) {
            $score += 3.0;
        }

        return $score;
    }

    private function historyChunkHaystack(MaintenanceHistoryChunk $chunk): string
    {
        return implode(' ', array_filter([
            (string) $chunk->title,
            (string) $chunk->content,
            (string) $chunk->searchable_text,
            (string) ($chunk->linea?->nombre ?? ''),
            (string) ($chunk->componente?->nombre ?? ''),
            (string) ($chunk->componente?->codigo ?? ''),
            json_encode($chunk->metadata ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    /**
     * @param  array<string, mixed>  $ranking
     * @return array<string, mixed>
     */
    private function historyChunkSummary(MaintenanceHistoryChunk $chunk, array $ranking): array
    {
        return [
            'source_group' => 'historial_semantico',
            'type' => $this->historyChunkType($chunk->source_type),
            'reference' => $this->historyChunkReference($chunk),
            'date' => optional($chunk->source_date ?: $chunk->updated_at)->toDateString(),
            'linea' => $chunk->linea?->nombre ?? data_get($chunk->metadata, 'linea.nombre'),
            'component' => [
                'id' => $chunk->componente_id ?? data_get($chunk->metadata, 'component.id'),
                'name' => $chunk->componente?->nombre ?? data_get($chunk->metadata, 'component.name'),
                'code' => $chunk->componente?->codigo ?? data_get($chunk->metadata, 'component.code'),
                'base_code' => $chunk->componente
                    ? AnalisisLavadora::codigoBaseComponente((string) $chunk->componente->codigo)
                    : data_get($chunk->metadata, 'component.base_code'),
            ],
            'title' => $this->sanitizer->sanitizeText((string) $chunk->title, 220),
            'summary' => $this->sanitizer->sanitizeText((string) $chunk->content, 900),
            'semantic_index' => [
                'chunk_id' => $chunk->id,
                'source_type' => $chunk->source_type,
                'source_id' => $chunk->source_id,
                'chunk_index' => $chunk->chunk_index,
                'indexed_at' => optional($chunk->indexed_at)->toIso8601String(),
            ],
            'score_breakdown' => [
                'lexical' => $ranking['lexical_score'],
                'metadata' => $ranking['metadata_score'],
                'semantic' => $ranking['semantic_score'],
            ],
            'priority_bucket' => $ranking['priority_bucket'],
            'priority_label' => $ranking['priority_label'],
            'score' => $ranking['score'],
        ];
    }

    private function historyChunkType(string $sourceType): string
    {
        return match ($sourceType) {
            'plan_accion' => 'historical_plan',
            'lavadora_cost_entry' => 'cost_history',
            default => 'revision',
        };
    }

    private function historyChunkReference(MaintenanceHistoryChunk $chunk): string
    {
        return match ($chunk->source_type) {
            'analisis_lavadora' => 'Analisis lavadora #' . $chunk->source_id,
            'maintenance_event' => 'Evento #' . $chunk->source_id,
            'plan_accion' => 'Plan #' . $chunk->source_id,
            'elongacion' => 'Elongacion #' . $chunk->source_id,
            'lavadora_cost_entry' => 'Costo lavadora #' . $chunk->source_id,
            default => $chunk->sourceReference(),
        };
    }

    private function userCanViewHistoryChunk(?User $user, MaintenanceHistoryChunk $chunk): bool
    {
        if ($chunk->source_type !== 'plan_accion') {
            return true;
        }

        $source = (string) data_get($chunk->metadata, 'extra.source', 'manual');
        $estado = (string) data_get($chunk->metadata, 'extra.estado', 'approved');
        $tipo = Str::lower((string) data_get($chunk->metadata, 'extra.tipo_equipo', User::MODULE_LAVADORA));

        if ($source === 'ai' && $estado !== 'approved') {
            return false;
        }

        return !$user || $user->canViewPlanActionType($tipo);
    }

    /**
     * @param  array<int, float>  $queryEmbedding
     * @param  mixed  $chunkEmbedding
     */
    private function semanticScore(array $queryEmbedding, mixed $chunkEmbedding): float
    {
        if ($queryEmbedding === [] || !is_array($chunkEmbedding)) {
            return 0.0;
        }

        $chunkVector = $this->normalizeVector($chunkEmbedding);

        if ($chunkVector === [] || count($queryEmbedding) !== count($chunkVector)) {
            return 0.0;
        }

        $dot = 0.0;
        $queryNorm = 0.0;
        $chunkNorm = 0.0;

        foreach ($queryEmbedding as $index => $value) {
            $chunkValue = $chunkVector[$index] ?? 0.0;
            $dot += $value * $chunkValue;
            $queryNorm += $value * $value;
            $chunkNorm += $chunkValue * $chunkValue;
        }

        if ($queryNorm <= 0.0 || $chunkNorm <= 0.0) {
            return 0.0;
        }

        return max(0.0, $dot / (sqrt($queryNorm) * sqrt($chunkNorm)));
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

    /**
     * @param  array<string, mixed>  $profile
     * @return array<int, array<string, mixed>>
     */
    private function technicalSources(array $profile): array
    {
        $queryEmbedding = $this->ranker->queryEmbedding((string) ($profile['query'] ?? ''));
        $context = [
            'linea_id' => $profile['linea_ids'][0] ?? null,
            'componente_id' => $profile['component_ids'][0] ?? null,
        ];

        return WasherKnowledgeChunk::query()
            ->with('document.linea', 'document.componente')
            ->whereHas('document', function ($query): void {
                $query->where('indexing_status', 'indexed')
                    ->where(function ($documentQuery): void {
                        $documentQuery->where('lifecycle_status', 'vigente')
                            ->orWhereNull('lifecycle_status');
                    });
            })
            ->latest('updated_at')
            ->limit($this->candidateLimit())
            ->get()
            ->map(function (WasherKnowledgeChunk $chunk) use ($profile, $queryEmbedding, $context): array {
                $ranking = $this->ranker->rankChunk($chunk, $profile, $context, $queryEmbedding);
                $ranking['score'] += $this->technicalSourceBoost($chunk, $profile);

                $item = $this->ranker->toKnowledgeItem($chunk, $ranking, 900);
                $item['source_group'] = 'base_conocimiento';

                return $item;
            })
            ->filter(fn (array $item): bool => $this->ranker->shouldKeep($item))
            ->sortByDesc('score')
            ->take($this->technicalSourceLimit())
            ->values()
            ->map(fn (array $item): array => $this->stripScore($item))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function technicalSourceBoost(WasherKnowledgeChunk $chunk, array $profile): float
    {
        $document = $chunk->document;
        $boost = 0.0;
        $lineaIds = $profile['linea_ids'] ?? [];
        $componentIds = $profile['component_ids'] ?? [];
        $componentBaseCodes = $profile['component_base_codes'] ?? [];
        $componentTerms = $profile['component_terms'] ?? [];
        $damageTerms = $profile['damage']['terms'] ?? [];

        if ($lineaIds !== [] && $document?->linea_id && in_array((int) $document->linea_id, $lineaIds, true)) {
            $boost += 5.0;
        }

        if ($componentIds !== [] && $document?->componente_id && in_array((int) $document->componente_id, $componentIds, true)) {
            $boost += 5.0;
        }

        $haystackTokens = $this->ranker->tokenize(implode(' ', array_filter([
            (string) ($document?->title ?? ''),
            (string) ($document?->componente?->nombre ?? ''),
            (string) ($document?->componente?->codigo ?? ''),
            (string) $chunk->content,
            (string) $chunk->searchable_text,
        ])));
        $documentBaseCode = $document?->componente
            ? AnalisisLavadora::codigoBaseComponente((string) $document->componente->codigo)
            : null;

        if ($documentBaseCode && in_array($documentBaseCode, $componentBaseCodes, true)) {
            $boost += 4.0;
        }

        if ($componentTerms !== [] && $this->tokensOverlap($componentTerms, $haystackTokens)) {
            $boost += 3.0;
        }

        if ($damageTerms !== [] && $this->tokensOverlap($damageTerms, $haystackTokens)) {
            $boost += 4.0;
        }

        return $boost;
    }

    /**
     * @param  array<string, mixed>  $rankerProfile
     * @param  array<int, string>  $lineas
     * @param  array<int, string>  $damageTerms
     * @return array<int, array<string, mixed>>
     */
    private function componentCandidates(string $query, array $rankerProfile, array $lineas, array $damageTerms): array
    {
        $tokens = array_values(array_diff(
            $rankerProfile['tokens'] ?? $this->ranker->tokenize($query),
            self::GENERIC_TOKENS,
            $damageTerms,
            $this->numericLineTokens($lineas)
        ));
        $componentTerms = $rankerProfile['component_terms'] ?? [];

        if ($tokens === [] && $componentTerms === []) {
            return [];
        }

        return Componente::query()
            ->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_LAVADORA)
                    ->orWhereNull('tipo_equipo');
            })
            ->where(function ($query): void {
                $query->where('activo', true)
                    ->orWhereNull('activo');
            })
            ->get()
            ->map(function (Componente $component) use ($tokens, $componentTerms, $lineas): array {
                $haystack = $this->componentHaystack($component);
                $haystackTokens = $this->ranker->tokenize($haystack);
                $componentOverlap = $this->tokensOverlap($componentTerms, $haystackTokens);
                $tokenOverlap = count(array_intersect($tokens, $haystackTokens));
                $lineMatch = $lineas !== [] && $component->linea && in_array(Str::upper((string) $component->linea), $lineas, true);
                $score = ($tokenOverlap * 3) + ($componentOverlap ? 8 : 0) + ($lineMatch ? 4 : 0);

                return [
                    'score' => $score,
                    'id' => $component->id,
                    'name' => $component->nombre,
                    'code' => $component->codigo,
                    'base_code' => AnalisisLavadora::codigoBaseComponente((string) $component->codigo),
                    'linea' => $component->linea,
                    'reductor' => $component->reductor ?: $component->ubicacion,
                    'search_terms' => $this->ranker->tokenize($haystack),
                ];
            })
            ->filter(function (array $candidate) use ($componentTerms): bool {
                if (($candidate['score'] ?? 0) <= 0) {
                    return false;
                }

                if ($componentTerms === []) {
                    return true;
                }

                return $this->tokensOverlap($componentTerms, $candidate['search_terms'] ?? []);
            })
            ->sortByDesc('score')
            ->take(12)
            ->values()
            ->map(fn (array $candidate): array => $this->stripScore($candidate))
            ->all();
    }

    private function componentHaystack(Componente $component): string
    {
        return implode(' ', array_filter([
            (string) $component->nombre,
            (string) $component->codigo,
            (string) $component->linea,
            (string) $component->reductor,
            (string) $component->ubicacion,
            (string) $component->grupo,
            (string) $component->mecanismo,
            AnalisisLavadora::codigoBaseComponente((string) $component->codigo),
        ]));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function emptyBuckets(): array
    {
        return collect(array_keys(self::BUCKET_LABELS))
            ->mapWithKeys(fn (string $bucket): array => [$bucket => []])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyAnalysis(AnalisisLavadora $analysis, array $profile): ?string
    {
        $sameLine = $this->matchesLine((int) $analysis->linea_id, (string) ($analysis->linea?->nombre ?? ''), $profile);
        $sameExactComponent = $this->matchesExactComponent($analysis->componente_id, $profile)
            || ($sameLine && $this->matchesComponentType($analysis->componente, $profile) && $this->matchesRequestedSubcomponent($analysis, $profile));
        $sameType = $this->matchesComponentType($analysis->componente, $profile);
        $similarDamage = $this->matchesDamage($this->analysisHaystack($analysis), $profile);

        if ($sameLine && $sameExactComponent) {
            return self::BUCKET_SAME_COMPONENT_SAME_WASHER;
        }

        if ($sameLine && $sameType) {
            return self::BUCKET_SAME_TYPE_SAME_WASHER;
        }

        if (!$sameLine && $sameType) {
            return self::BUCKET_SAME_COMPONENT_OTHER_WASHERS;
        }

        if ($similarDamage) {
            return self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyEvent(MaintenanceEvent $event, array $profile): ?string
    {
        $sameLine = $this->matchesLine((int) $event->linea_id, (string) ($event->linea?->nombre ?? ''), $profile);
        $sameExactComponent = $this->matchesExactComponent($event->componente_id, $profile);
        $sameType = $this->matchesComponentType($event->componente, $profile);
        $similarDamage = $this->matchesDamage($this->eventHaystack($event), $profile);

        if ($sameLine && $sameExactComponent) {
            return self::BUCKET_SAME_COMPONENT_SAME_WASHER;
        }

        if ($sameLine && $sameType) {
            return self::BUCKET_SAME_TYPE_SAME_WASHER;
        }

        if (!$sameLine && $sameType) {
            return self::BUCKET_SAME_COMPONENT_OTHER_WASHERS;
        }

        if ($similarDamage) {
            return self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function classifyPlan(PlanAccion $plan, array $profile): ?string
    {
        $event = $plan->maintenanceEvent;
        $component = $event?->componente;
        $sameLine = $this->matchesLine((int) $plan->linea_id, (string) ($plan->linea?->nombre ?? ''), $profile);
        $sameExactComponent = $this->matchesExactComponent($event?->componente_id, $profile);
        $sameType = $this->matchesComponentType($component, $profile)
            || $this->matchesComponentText($this->planHaystack($plan), $profile);
        $similarDamage = $this->matchesDamage($this->planHaystack($plan), $profile);

        if ($sameLine && $sameExactComponent) {
            return self::BUCKET_SAME_COMPONENT_SAME_WASHER;
        }

        if ($sameLine && $sameType) {
            return self::BUCKET_SAME_TYPE_SAME_WASHER;
        }

        if (!$sameLine && $sameType) {
            return self::BUCKET_SAME_COMPONENT_OTHER_WASHERS;
        }

        if ($similarDamage) {
            return self::BUCKET_SIMILAR_FAILURE_OTHER_COMPONENTS;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function scoreAnalysis(AnalisisLavadora $analysis, array $profile, string $bucket): float
    {
        return $this->scoreGenericSource(
            $this->analysisHaystack($analysis),
            $profile,
            $bucket,
            optional($analysis->fecha_analisis ?: $analysis->created_at)->toDateString()
        ) + ($this->hasRepairEvidence($analysis) ? 10 : 0);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function scoreGenericSource(string $haystack, array $profile, string $bucket, ?string $date): float
    {
        $tokens = $profile['tokens'] ?? [];
        $componentTerms = $profile['component_terms'] ?? [];
        $damageTerms = $profile['damage']['terms'] ?? [];
        $haystackTokens = $this->ranker->tokenize($haystack);

        return (float) (self::BUCKET_WEIGHTS[$bucket] ?? 0)
            + ($this->ranker->lexicalScore($tokens, $haystack) * 3)
            + ($this->tokensOverlap($componentTerms, $haystackTokens) ? 12 : 0)
            + ($this->tokensOverlap($damageTerms, $haystackTokens) ? 16 : 0)
            + $this->recencyScore($date);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesLine(?int $lineaId, string $lineaNombre, array $profile): bool
    {
        $lineaIds = $profile['linea_ids'] ?? [];
        $lineas = $profile['lineas'] ?? [];

        if ($lineaIds === [] && $lineas === []) {
            return false;
        }

        if ($lineaId && in_array((int) $lineaId, $lineaIds, true)) {
            return true;
        }

        return $lineaNombre !== '' && in_array(Str::upper($lineaNombre), $lineas, true);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesExactComponent(?int $componentId, array $profile): bool
    {
        $exactComponentIds = $profile['exact_component_ids'] ?? [];

        if ($exactComponentIds !== []) {
            return $componentId !== null && in_array((int) $componentId, $exactComponentIds, true);
        }

        return $componentId !== null
            && in_array((int) $componentId, $profile['component_ids'] ?? [], true)
            && count($profile['component_ids'] ?? []) === 1;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesComponentType(?Componente $component, array $profile): bool
    {
        if (!$component) {
            return false;
        }

        $componentBaseCodes = $profile['component_base_codes'] ?? [];
        $componentTerms = $profile['component_terms'] ?? [];
        $baseCode = AnalisisLavadora::codigoBaseComponente((string) $component->codigo);

        if ($baseCode !== '' && in_array($baseCode, $componentBaseCodes, true)) {
            return true;
        }

        return $this->tokensOverlap($componentTerms, $this->ranker->tokenize($this->componentHaystack($component)));
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function matchesComponentText(string $haystack, array $profile): bool
    {
        return $this->tokensOverlap($profile['component_terms'] ?? [], $this->ranker->tokenize($haystack));
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
     * @param  array<string, mixed>  $profile
     */
    private function matchesRequestedSubcomponent(AnalisisLavadora $analysis, array $profile): bool
    {
        $reductores = array_filter($profile['reductores'] ?? []);
        $lados = array_filter($profile['lados'] ?? []);

        if ($reductores === [] && $lados === []) {
            return false;
        }

        $reductorMatches = $reductores === []
            || in_array($this->normalizeSubcomponent((string) $analysis->reductor), $reductores, true);
        $ladoMatches = $lados === []
            || in_array($this->normalizeSide((string) $analysis->lado), $lados, true);

        return $reductorMatches && $ladoMatches;
    }

    private function analysisHaystack(AnalisisLavadora $analysis): string
    {
        return implode(' ', array_filter([
            (string) ($analysis->linea?->nombre ?? ''),
            (string) ($analysis->componente?->nombre ?? ''),
            (string) ($analysis->componente?->codigo ?? ''),
            (string) $analysis->reductor,
            (string) $analysis->lado,
            (string) $analysis->estado,
            (string) $analysis->estado_correccion,
            (string) $analysis->actividad,
            (string) $analysis->observaciones_reparacion,
            (string) $analysis->tipo_intervencion,
            (string) $analysis->componente_instalado,
            (string) $analysis->numero_parte,
            (string) $analysis->proveedor,
            (string) $analysis->comentarios_costos,
        ]));
    }

    private function eventHaystack(MaintenanceEvent $event): string
    {
        return implode(' ', array_filter([
            (string) ($event->linea?->nombre ?? ''),
            (string) ($event->componente?->nombre ?? ''),
            (string) ($event->componente?->codigo ?? ''),
            (string) $event->title,
            (string) $event->description,
            (string) $event->event_type,
            (string) $event->severity,
            (string) $event->detected_value,
            (string) json_encode($event->context_data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    private function planHaystack(PlanAccion $plan): string
    {
        return implode(' ', array_filter([
            (string) ($plan->linea?->nombre ?? ''),
            (string) ($plan->maintenanceEvent?->componente?->nombre ?? ''),
            (string) ($plan->maintenanceEvent?->componente?->codigo ?? ''),
            (string) $plan->actividad,
            (string) $plan->detected_problem,
            (string) $plan->technical_justification,
            (string) $plan->risk_if_not_executed,
            (string) $plan->observaciones,
            (string) $plan->execution_result,
            (string) $plan->effectiveness,
            (string) $plan->effectivenessLabel(),
            (string) json_encode($plan->source_metadata ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) json_encode($plan->approved_content ?? $plan->original_generated_content ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisSummary(AnalisisLavadora $analysis): array
    {
        return [
            'source_group' => 'historial_plataforma',
            'type' => 'revision',
            'reference' => 'Analisis lavadora #' . $analysis->id,
            'date' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toDateString(),
            'linea' => $analysis->linea?->nombre,
            'component' => [
                'id' => $analysis->componente_id,
                'name' => $analysis->componente?->nombre,
                'code' => $analysis->componente?->codigo,
                'base_code' => $analysis->componente
                    ? AnalisisLavadora::codigoBaseComponente((string) $analysis->componente->codigo)
                    : null,
            ],
            'subcomponent' => [
                'reductor' => $analysis->reductor,
                'lado' => $analysis->lado,
            ],
            'condition' => [
                'estado' => $analysis->estado,
                'estado_operativo' => $analysis->estado_operativo,
                'estado_correccion' => $analysis->estado_correccion,
            ],
            'technician_observation' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 420),
            'evidence_count' => count($analysis->evidencia_fotos ?? []),
            'repair_result' => $this->repairSummary($analysis),
            'registered_by' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventSummary(MaintenanceEvent $event): array
    {
        return [
            'source_group' => 'historial_plataforma',
            'type' => 'revision',
            'reference' => 'Evento #' . $event->id,
            'date' => optional($event->detected_at ?: $event->created_at)->toDateString(),
            'linea' => $event->linea?->nombre,
            'component' => [
                'id' => $event->componente_id,
                'name' => $event->componente?->nombre,
                'code' => $event->componente?->codigo,
                'base_code' => $event->componente
                    ? AnalisisLavadora::codigoBaseComponente((string) $event->componente->codigo)
                    : null,
            ],
            'event_type' => $event->event_type,
            'severity' => $event->severity,
            'status' => $event->status,
            'title' => $this->sanitizer->sanitizeText((string) $event->title, 220),
            'description' => $this->sanitizer->sanitizeText((string) $event->description, 420),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planSummary(PlanAccion $plan): array
    {
        return [
            'source_group' => 'historial_plataforma',
            'type' => 'historical_plan',
            'reference' => 'Plan #' . $plan->id,
            'date' => optional($plan->fecha_ejecucion ?: $plan->updated_at)->toDateString(),
            'linea' => $plan->linea?->nombre,
            'component' => [
                'id' => $plan->maintenanceEvent?->componente_id,
                'name' => $plan->maintenanceEvent?->componente?->nombre,
                'code' => $plan->maintenanceEvent?->componente?->codigo,
                'base_code' => $plan->maintenanceEvent?->componente
                    ? AnalisisLavadora::codigoBaseComponente((string) $plan->maintenanceEvent->componente->codigo)
                    : null,
            ],
            'actividad' => $this->sanitizer->sanitizeText((string) $plan->actividad, 360),
            'detected_problem' => $this->sanitizer->sanitizeText((string) $plan->detected_problem, 360),
            'technical_justification' => $this->sanitizer->sanitizeText((string) $plan->technical_justification, 420),
            'risk_if_not_executed' => $this->sanitizer->sanitizeText((string) $plan->risk_if_not_executed, 360),
            'source' => $plan->source ?: 'manual',
            'estado' => $plan->estado,
            'priority' => $plan->priority_level,
            'completed' => (bool) $plan->completado,
            'execution_feedback' => [
                'execution_result' => $this->sanitizer->sanitizeText((string) $plan->execution_result, 420),
                'effectiveness' => $plan->effectiveness,
                'effectiveness_label' => $plan->effectivenessLabel(),
                'actual_cost_total' => $plan->actual_cost_total,
                'actual_hours' => $plan->actual_hours,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function repairSummary(AnalisisLavadora $analysis): ?array
    {
        if (!$this->hasRepairEvidence($analysis)) {
            return null;
        }

        return array_filter([
            'estado_correccion' => $analysis->estado_correccion,
            'fecha_correccion' => optional($analysis->fecha_correccion)->toDateString(),
            'tipo_intervencion' => $analysis->tipo_intervencion,
            'observaciones_reparacion' => $this->sanitizer->sanitizeText((string) $analysis->observaciones_reparacion, 420),
            'componente_instalado' => $analysis->componente_instalado,
            'numero_parte' => $analysis->numero_parte,
            'proveedor' => $analysis->proveedor,
            'fecha_cambio' => optional($analysis->fecha_cambio)->toDateString(),
            'costo_total_intervencion' => $analysis->costo_total_intervencion,
            'tiempo_reparacion_horas' => $analysis->tiempo_reparacion_horas,
            'comentarios_costos' => $this->sanitizer->sanitizeText((string) $analysis->comentarios_costos, 260),
            'evidence_count' => count($analysis->evidencias_reparacion ?? []),
        ], static fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    }

    private function hasRepairEvidence(AnalisisLavadora $analysis): bool
    {
        return filled($analysis->observaciones_reparacion)
            || filled($analysis->tipo_intervencion)
            || filled($analysis->componente_instalado)
            || filled($analysis->numero_parte)
            || filled($analysis->fecha_cambio)
            || (float) $analysis->costo_total_intervencion > 0
            || filled($analysis->comentarios_costos)
            || count($analysis->evidencias_reparacion ?? []) > 0;
    }

    private function currentOrPastWasherAnalysisQuery(): Builder
    {
        $today = CarbonImmutable::today(config('app.timezone', 'America/Mexico_City'))->toDateString();

        return AnalisisLavadora::query()
            ->where(function ($query) use ($today): void {
                $query->whereNull('fecha_analisis')
                    ->orWhereDate('fecha_analisis', '<=', $today);
            });
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
            ->get(['id', 'nombre'])
            ->filter(fn (Linea $linea): bool => in_array(Str::upper((string) $linea->nombre), $lineas, true))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function lineReferences(string $text): array
    {
        $normalized = Str::lower(Str::ascii($text));
        $lineas = [];

        if (preg_match_all('/(?:lavadora|linea|l)\s*[-#]?\s*0*(\d{1,2})\b/u', $normalized, $matches)) {
            foreach ($matches[1] as $lineNumber) {
                $lineas[] = 'L-' . str_pad((string) $lineNumber, 2, '0', STR_PAD_LEFT);
            }
        }

        return array_values(array_unique($lineas));
    }

    /**
     * @return array<int, string>
     */
    private function extractSubcomponents(string $normalizedText): array
    {
        $reductores = [];

        if (preg_match_all('/(?:reductor|red)\s*[-#]?\s*0*(\d{1,2})\b/u', $normalizedText, $matches)) {
            foreach ($matches[1] as $number) {
                $reductores[] = 'reductor ' . (int) $number;
            }
        }

        if (str_contains($normalizedText, 'principal')) {
            $reductores[] = 'reductor principal';
        }

        if (str_contains($normalizedText, 'flecha loca')) {
            $reductores[] = 'flecha loca';
        }

        return array_values(array_unique(array_filter($reductores)));
    }

    private function normalizeSubcomponent(string $value): ?string
    {
        $normalized = Str::lower(Str::ascii(trim($value)));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/(?:reductor|red)\s*[-#]?\s*0*(\d{1,2})\b/u', $normalized, $matches)) {
            return 'reductor ' . (int) $matches[1];
        }

        if (str_contains($normalized, 'principal')) {
            return 'reductor principal';
        }

        if (str_contains($normalized, 'flecha loca')) {
            return 'flecha loca';
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function extractSides(string $normalizedText): array
    {
        $sides = [];

        foreach (['bombas', 'vapor', 'izquierdo', 'derecho', 'entrada', 'salida'] as $side) {
            if (str_contains($normalizedText, $side)) {
                $sides[] = $side;
            }
        }

        return array_values(array_unique($sides));
    }

    private function normalizeSide(string $value): ?string
    {
        $normalized = Str::lower(Str::ascii(trim($value)));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array{labels: array<int, string>, terms: array<int, string>}
     */
    private function damageProfile(string $text): array
    {
        $tokens = $this->ranker->tokenize($text);
        $labels = [];
        $terms = [];

        foreach (self::DAMAGE_GROUPS as $label => $groupTerms) {
            if ($this->tokensOverlap($tokens, $groupTerms)) {
                $labels[] = $label;
                $terms = array_merge($terms, $groupTerms);
            }
        }

        $terms = array_merge($terms, array_values(array_intersect($tokens, [
            'averia',
            'bloqueado',
            'bloqueada',
            'contaminado',
            'contaminada',
            'corrosion',
            'deformado',
            'deformada',
            'exceso',
            'fisura',
            'lubricante',
            'obstruido',
            'obstruida',
        ])));

        return [
            'labels' => array_values(array_unique($labels)),
            'terms' => array_values(array_unique(array_filter($terms))),
        ];
    }

    private function extractSeverity(string $normalizedText): ?string
    {
        if (str_contains($normalizedText, 'critico') || str_contains($normalizedText, 'critica')) {
            return 'critical';
        }

        if (str_contains($normalizedText, 'severo') || str_contains($normalizedText, 'requiere cambio') || str_contains($normalizedText, 'danado')) {
            return 'high';
        }

        if (str_contains($normalizedText, 'moderado') || str_contains($normalizedText, 'requiere revision')) {
            return 'medium';
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lineas
     * @return array<int, string>
     */
    private function numericLineTokens(array $lineas): array
    {
        return collect($lineas)
            ->flatMap(function (string $linea): array {
                if (preg_match('/(\d{1,2})/', $linea, $matches) !== 1) {
                    return [];
                }

                $number = ltrim($matches[1], '0');
                $number = $number !== '' ? $number : '0';

                return [$number, str_pad($number, 2, '0', STR_PAD_LEFT)];
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $left
     * @param  array<int, string>  $right
     */
    private function tokensOverlap(array $left, array $right): bool
    {
        return count(array_intersect($left, $right)) > 0;
    }

    private function recencyScore(?string $date): float
    {
        if (!$date) {
            return 0.0;
        }

        try {
            $days = max(0, CarbonImmutable::parse($date)->diffInDays(CarbonImmutable::now(), false));
        } catch (\Throwable) {
            return 0.0;
        }

        if ($days <= 30) {
            return 10.0;
        }

        if ($days <= 180) {
            return 6.0;
        }

        if ($days <= 365) {
            return 3.0;
        }

        return 1.0;
    }

    /**
     * @param  array<string, mixed>  $bucketCounts
     * @return array<int, string>
     */
    private function coverageWarnings(bool $hasHistory, bool $hasTechnicalSources, array $bucketCounts): array
    {
        $warnings = [];

        if (!$hasHistory) {
            $warnings[] = 'No se encontraron antecedentes historicos relevantes en la plataforma para esta consulta.';
        } elseif (($bucketCounts[self::BUCKET_SAME_COMPONENT_SAME_WASHER] ?? 0) === 0) {
            $warnings[] = 'No se encontraron antecedentes del mismo componente exacto en la misma lavadora; se usaron coincidencias de menor prioridad.';
        }

        if (!$hasTechnicalSources) {
            $warnings[] = 'No se encontraron fragmentos relevantes en la base de conocimiento indexada.';
        }

        return $warnings;
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

    private function userCanViewPlan(?User $user, PlanAccion $plan): bool
    {
        if (!$user) {
            return true;
        }

        $tipo = Str::lower((string) ($plan->tipo_equipo ?: User::MODULE_LAVADORA));

        if (!$user->canViewPlanActionType($tipo)) {
            return false;
        }

        return !($plan->source === 'ai'
            && $tipo === User::MODULE_LAVADORA
            && $plan->estado !== 'approved'
            && !$user->canReviewWasherAiPlans());
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function hasTechnicalSignal(array $profile): bool
    {
        return ($profile['lineas'] ?? []) !== []
            || ($profile['linea_ids'] ?? []) !== []
            || ($profile['component_ids'] ?? []) !== []
            || ($profile['component_base_codes'] ?? []) !== []
            || ($profile['component_terms'] ?? []) !== []
            || ($profile['reductores'] ?? []) !== []
            || ($profile['damage']['terms'] ?? []) !== []
            || ($profile['severity'] ?? null) !== null
            || ($profile['event'] ?? null) !== null;
    }

    private function candidateLimit(): int
    {
        return max(20, (int) config('maintenance_ai.technical_context.candidate_limit', 120));
    }

    private function historyLimitPerBucket(): int
    {
        return max(1, (int) config('maintenance_ai.technical_context.history_limit_per_bucket', 3));
    }

    private function totalHistoryLimit(): int
    {
        return max(4, (int) config('maintenance_ai.technical_context.total_history_limit', 12));
    }

    private function technicalSourceLimit(): int
    {
        return max(1, (int) config('maintenance_ai.technical_context.document_limit', 4));
    }

    private function historyIndexCandidateLimit(): int
    {
        return max(20, (int) config('maintenance_ai.history_index.candidate_limit', 180));
    }

    private function historyIndexMinScore(): float
    {
        return max(0.0, (float) config('maintenance_ai.history_index.min_score', 1.0));
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyContext(string $reason): array
    {
        return [
            'available' => false,
            'reason' => $reason,
            'historical_sources' => $this->emptyBuckets(),
            'technical_sources' => [],
            'coverage' => [
                'historical_records_count' => 0,
                'technical_sources_count' => 0,
                'warnings' => [$reason],
            ],
        ];
    }
}
