<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Exports\AssistantAnalyticsExport;
use App\Exports\AssistantAnalyticsWorkbookExport;
use App\Models\AnalisisLavadora;
use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AssistantAnalyticsArtifactService
{
    /**
     * @var array<int, string>
     */
    private array $chartColors = ['#2563eb', '#f59e0b', '#16a34a', '#dc2626', '#7c3aed'];

    public function __construct(
        private readonly AiProviderInterface $aiProvider,
        private readonly PromptSafetySanitizer $sanitizer
    ) {}

    public function looksLikeArtifactRequest(string $question): bool
    {
        $normalized = $this->normalize($question);

        foreach ([
            'graf',
            'chart',
            'plot',
            'imagen',
            'png',
            'svg',
            'excel',
            'ecxel',
            'exel',
            'excell',
            'xlsx',
            'xlxs',
            'export',
            'descarg',
            'archivo',
            'tabla',
        ] as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    public function tryGenerate(User $user, string $question, array $pageContext = []): ?array
    {
        if (! $this->looksLikeArtifactRequest($question)) {
            return null;
        }

        $intentResult = $this->resolveIntent($question, $pageContext);

        if ($intentResult === null) {
            return null;
        }

        $intent = $intentResult['data'];

        if (! ((bool) ($intent['should_generate'] ?? false))) {
            return null;
        }

        $datasetKey = $this->normalizeDataset((string) ($intent['dataset'] ?? ''), $question);
        $outputs = $this->normalizeOutputs((array) ($intent['outputs'] ?? []), $question);
        $lineas = $this->normalizeLineas((array) ($intent['lineas'] ?? []), $question);
        $dateRange = $this->normalizeDateRange((array) ($intent['date_range'] ?? []), $question);
        $chartType = $this->normalizeChartType((string) ($intent['chart_type'] ?? ''), $question);
        $intent['aggregation'] = $this->normalizeAggregation((string) ($intent['aggregation'] ?? ''), $question, $chartType);
        $invalidLineas = $this->invalidLineReferences((array) ($intent['lineas'] ?? []), $question);

        if ($datasetKey === 'unsupported') {
            return $this->unsupportedReply($question, $intentResult);
        }

        if ($invalidLineas !== []) {
            return $this->invalidLineReply($invalidLineas, $intentResult);
        }

        $dataset = $this->buildDataset($datasetKey, $intent, $lineas, $dateRange, $chartType, $question);

        if ($dataset === null || ($dataset['rows'] ?? []) === []) {
            return $this->emptyReply($datasetKey, $lineas, $dateRange, $intentResult);
        }

        $dataset = $this->withRuntimeFilters($dataset, $question, $datasetKey, $outputs, $lineas, $dateRange, $chartType);

        $artifacts = [];

        if (in_array('image', $outputs, true)) {
            array_push($artifacts, ...$this->storeChartImages($user, $dataset, $chartType));
        }

        if (in_array('excel', $outputs, true)) {
            $artifacts[] = $this->storeExcel($user, $dataset, $chartType);
        }

        if ($artifacts === []) {
            array_push($artifacts, ...$this->storeChartImages($user, $dataset, $chartType));
        }

        return [
            'content' => $this->successContent($dataset, $artifacts),
            'metadata' => [
                'provider' => Arr::get($intentResult, 'meta.provider'),
                'model' => Arr::get($intentResult, 'meta.model'),
                'confidence' => (float) ($intent['confidence'] ?? 0.85),
                'sources' => [
                    [
                        'type' => 'database',
                        'reference' => (string) ($dataset['source_reference'] ?? $dataset['title']),
                    ],
                ],
                'artifacts' => $artifacts,
                'artifact_request' => true,
                'intent' => [
                    'dataset' => $datasetKey,
                    'outputs' => $outputs,
                    'chart_type' => $chartType,
                    'lineas' => $lineas,
                    'date_range' => $this->serializeDateRange($dateRange),
                    'report_version' => (string) ($dataset['report_version'] ?? 'v1'),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array{data: array<string, mixed>, meta: array<string, mixed>}|null
     */
    private function resolveIntent(string $question, array $pageContext): ?array
    {
        try {
            $payload = [
                'system_prompt' => implode("\n", [
                    'Eres un clasificador de solicitudes para el chatbot operativo de LEGADO AB FENIX.',
                    'Tu unica tarea es decidir si el usuario quiere generar una grafica, imagen o archivo Excel usando datos internos.',
                    'Solo puedes elegir datasets permitidos. Nunca inventes tablas ni columnas.',
                    'Si el usuario pide algo fuera de los datasets permitidos, usa dataset unsupported y should_generate true.',
                    'Responde exclusivamente JSON valido que cumpla el esquema.',
                ]),
                'user_prompt' => (string) json_encode([
                    'question' => $this->sanitizer->sanitizeText($question, 1200),
                    'page_context' => $pageContext,
                    'available_datasets' => [
                        'elongaciones' => 'Mediciones de cadena de lavadoras: bombas %, vapor %, maximo %, hodometro y estado por fecha/linea.',
                        'analisis_lavadora' => 'Analisis de componentes de lavadora: estados, danos, desgaste, revisiones y cambios por fecha/linea.',
                        'costos_lavadora' => 'Costos registrados de lavadora: total_cost, unit_cost, quantity, fuente, refaccion y linea por fecha.',
                        'plan_accion' => 'Planes de accion: pendientes, completados, prioridad, tipo de mantenimiento y fechas.',
                    ],
                    'output_rules' => [
                        'image si pide grafica, imagen, png, svg, chart o plot.',
                        'excel si pide excel, xlsx, exportar, descargar, archivo o tabla.',
                        'Trata errores comunes como ecxel, exel, excell o xlxs como Excel.',
                        'Usa ambos outputs cuando pida imagen y Excel.',
                        'Usa line si pide tendencia/evolucion, bar si pide comparativo/ranking/por linea.',
                        'Normaliza lineas como L-04, L-05, L-06, L-07, L-08, L-09, L-12 o L-13.',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'schema_name' => 'assistant_analytics_intent',
                'schema' => $this->intentSchema(),
            ];

            $chatModel = trim((string) config('maintenance_ai.chat.model', ''));

            if ($chatModel !== '') {
                $payload['model'] = $chatModel;
            }

            $response = $this->aiProvider->generateStructuredActionPlan($payload);

            return [
                'data' => is_array($response['data'] ?? null) ? $response['data'] : [],
                'meta' => is_array($response['meta'] ?? null) ? $response['meta'] : [],
            ];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function intentSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'should_generate' => ['type' => 'boolean'],
                'dataset' => [
                    'type' => 'string',
                    'enum' => ['elongaciones', 'analisis_lavadora', 'costos_lavadora', 'plan_accion', 'unsupported'],
                ],
                'metric' => [
                    'type' => 'string',
                    'enum' => [
                        'max_porcentaje',
                        'bombas_porcentaje',
                        'vapor_porcentaje',
                        'registros',
                        'danos',
                        'costos',
                        'planes',
                    ],
                ],
                'chart_type' => [
                    'type' => 'string',
                    'enum' => ['line', 'bar'],
                ],
                'aggregation' => [
                    'type' => 'string',
                    'enum' => ['daily', 'weekly', 'monthly', 'by_line', 'latest'],
                ],
                'outputs' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['image', 'excel'],
                    ],
                    'maxItems' => 2,
                ],
                'lineas' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'maxItems' => 8,
                ],
                'date_range' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'preset' => [
                            'type' => 'string',
                            'enum' => [
                                '',
                                'last_30_days',
                                'last_90_days',
                                'last_6_months',
                                'last_12_months',
                                'current_month',
                                'current_year',
                                'all',
                                'custom',
                            ],
                        ],
                        'from' => ['type' => 'string'],
                        'to' => ['type' => 'string'],
                    ],
                    'required' => ['preset', 'from', 'to'],
                ],
                'title' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
            ],
            'required' => [
                'should_generate',
                'dataset',
                'metric',
                'chart_type',
                'aggregation',
                'outputs',
                'lineas',
                'date_range',
                'title',
                'confidence',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>|null
     */
    private function buildDataset(
        string $datasetKey,
        array $intent,
        array $lineas,
        array $dateRange,
        string $chartType,
        string $question
    ): ?array {
        return match ($datasetKey) {
            'elongaciones' => $this->buildElongacionesDataset($intent, $lineas, $dateRange, $chartType, $question),
            'analisis_lavadora' => $this->buildAnalisisLavadoraDataset($lineas, $dateRange, (string) ($intent['aggregation'] ?? 'monthly'), $question),
            'costos_lavadora' => $this->buildCostosLavadoraDataset($lineas, $dateRange, (string) ($intent['aggregation'] ?? 'monthly'), $question),
            'plan_accion' => $this->buildPlanAccionDataset($lineas, $dateRange, (string) ($intent['aggregation'] ?? 'monthly'), $question),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $intent
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>|null
     */
    private function buildElongacionesDataset(array $intent, array $lineas, array $dateRange, string $chartType, string $question): ?array
    {
        $aggregation = (string) ($intent['aggregation'] ?? 'monthly');
        $includeHistoricalCycles = $this->wantsHistoricalElongacionCycles($question);
        $records = $this->elongacionesQuery($lineas, $dateRange)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $records = $this->scopeElongacionRecordsToCycle($records, $lineas, $includeHistoricalCycles);

        if ($this->wantsCriticalOnly($question)) {
            $criticalThreshold = (float) config('maintenance_ai.rules.elongacion_critical_threshold', Elongacion::LIMITE_CAMBIO);
            $records = $records
                ->filter(fn (Elongacion $record): bool => $this->elongacionMaxValue($record) >= $criticalThreshold)
                ->values();
        }

        if ($records->isEmpty()) {
            return null;
        }

        if ($aggregation === 'by_line' || $aggregation === 'latest' || ($chartType === 'bar' && $lineas === [])) {
            $dataset = $this->buildElongacionesByLineDataset($records, $lineas, $dateRange);
        } elseif (count($lineas) === 1) {
            $dataset = $this->buildElongacionesDetailDataset($records, $lineas[0], $dateRange);
        } else {
            $dataset = $this->buildElongacionesMonthlyDataset($records, $lineas, $dateRange, $aggregation);
        }

        return $this->withElongacionCycleScope($dataset, $includeHistoricalCycles);
    }

    /**
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     */
    private function elongacionesQuery(array $lineas, array $dateRange)
    {
        $lineIds = $this->lineIdsFor($lineas);

        return Elongacion::query()
            ->with('cadenaCiclo')
            ->when($lineas !== [] || $lineIds !== [], function ($query) use ($lineas, $lineIds): void {
                $query->where(function ($subQuery) use ($lineas, $lineIds): void {
                    if ($lineas !== []) {
                        $subQuery->whereIn('linea', $lineas);
                    }

                    if ($lineIds !== []) {
                        $method = $lineas !== [] ? 'orWhereIn' : 'whereIn';
                        $subQuery->{$method}('linea_id', $lineIds);
                    }
                });
            })
            ->when($dateRange['from'], fn ($query, CarbonImmutable $from) => $query->where('created_at', '>=', $from))
            ->when($dateRange['to'], fn ($query, CarbonImmutable $to) => $query->where('created_at', '<=', $to));
    }

    /**
     * @param  Collection<int, Elongacion>  $records
     * @param  array<int, string>  $lineas
     * @return Collection<int, Elongacion>
     */
    private function scopeElongacionRecordsToCycle(Collection $records, array $lineas, bool $includeHistoricalCycles): Collection
    {
        if ($includeHistoricalCycles || $records->isEmpty()) {
            return $records->values();
        }

        $lineNames = collect($lineas !== [] ? $lineas : $records->pluck('linea')->all())
            ->filter()
            ->unique()
            ->values();

        $activeCycles = CadenaCiclo::query()
            ->when($lineNames->isNotEmpty(), fn ($query) => $query->whereIn('linea', $lineNames->all()))
            ->where('activa', true)
            ->orderBy('linea')
            ->orderByDesc('numero_ciclo')
            ->orderByDesc('id')
            ->get()
            ->unique('linea')
            ->keyBy('linea');

        return $records
            ->groupBy(fn (Elongacion $record): string => (string) ($record->linea ?: 'Sin linea'))
            ->flatMap(function (Collection $items, string $linea) use ($activeCycles): Collection {
                $items = $items->values();
                $activeCycle = $activeCycles->get($linea);
                $latestCycleRecord = $items->last(fn (Elongacion $record): bool => ! empty($record->cadena_ciclo_id));
                $currentCycleId = $activeCycle?->id ?? $latestCycleRecord?->cadena_ciclo_id;

                if (! $currentCycleId) {
                    return $items;
                }

                return $items
                    ->filter(fn (Elongacion $record): bool => (int) $record->cadena_ciclo_id === (int) $currentCycleId)
                    ->values();
            })
            ->sortBy(fn (Elongacion $record): string => ($record->created_at?->format('Y-m-d H:i:s') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->values();
    }

    private function wantsHistoricalElongacionCycles(string $question): bool
    {
        $normalized = $this->normalize($question);

        foreach ([
            'todo el historial',
            'todos los ciclos',
            'todas los ciclos',
            'ciclos anteriores',
            'ciclo anterior',
            'otros ciclos',
            'otro ciclo',
            'registros pasados',
            'historico',
            'historial',
            'desde el inicio',
            'desde siempre',
            'incluye ciclos',
            'todos los registros',
            'cualquier ciclo',
        ] as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array<string, mixed>
     */
    private function withElongacionCycleScope(array $dataset, bool $includeHistoricalCycles): array
    {
        $dataset['cycle_scope'] = $includeHistoricalCycles ? 'all_cycles' : 'current_cycle';
        $dataset['cycle_scope_label'] = $includeHistoricalCycles
            ? 'Todos los ciclos solicitados'
            : 'Ciclo actual por lavadora';

        $summaryRows = (array) ($dataset['summary_rows'] ?? []);
        $summaryRows[] = ['Alcance de ciclo', $dataset['cycle_scope_label']];
        $dataset['summary_rows'] = $summaryRows;

        return $dataset;
    }

    /**
     * @param  Collection<int, Elongacion>  $records
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function buildElongacionesByLineDataset(Collection $records, array $lineas, array $dateRange): array
    {
        $latest = $records
            ->sortByDesc(fn (Elongacion $record): string => ($record->created_at?->format('Y-m-d H:i:s') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->unique('linea')
            ->sortBy('linea')
            ->values();

        $rows = $latest->map(function (Elongacion $record): array {
            $max = max((float) $record->bombas_porcentaje, (float) $record->vapor_porcentaje);

            return [
                $record->linea,
                $record->created_at?->format('d/m/Y') ?? '',
                round((float) $record->bombas_porcentaje, 2),
                round((float) $record->vapor_porcentaje, 2),
                round($max, 2),
                (string) ($record->estado_detallado ?: $record->estado ?: $record->estado_general),
                $record->hodometro,
            ];
        })->all();

        $dataset = [
            'type' => 'elongaciones',
            'report_version' => 'elongaciones-v2',
            'title' => 'Elongacion actual por lavadora',
            'subtitle' => $dateRange['label'],
            'source_reference' => 'elongaciones por linea',
            'headings' => ['Linea', 'Fecha', 'Bombas %', 'Vapor %', 'Maximo %', 'Estado', 'Hodometro'],
            'rows' => $rows,
            'series' => [
                [
                    'name' => 'Maximo %',
                    'points' => $latest->map(fn (Elongacion $record): array => [
                        'label' => $record->linea,
                        'value' => round(max((float) $record->bombas_porcentaje, (float) $record->vapor_porcentaje), 2),
                    ])->all(),
                ],
            ],
            'x_label' => 'Lavadora',
            'y_label' => 'Elongacion %',
            'thresholds' => $this->elongacionThresholds(),
        ];

        return $this->withElongacionDetails($dataset, $records, $lineas, $dateRange);
    }

    /**
     * @param  Collection<int, Elongacion>  $records
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function buildElongacionesDetailDataset(Collection $records, string $linea, array $dateRange): array
    {
        $limited = $records->take(-80)->values();

        $dataset = [
            'type' => 'elongaciones',
            'report_version' => 'elongaciones-v2',
            'title' => 'Tendencia de elongacion '.$linea,
            'subtitle' => $dateRange['label'],
            'source_reference' => 'elongaciones '.$linea,
            'headings' => ['Fecha', 'Linea', 'Bombas %', 'Vapor %', 'Maximo %', 'Estado', 'Hodometro'],
            'rows' => $limited->map(function (Elongacion $record): array {
                return [
                    $record->created_at?->format('d/m/Y') ?? '',
                    $record->linea,
                    round((float) $record->bombas_porcentaje, 2),
                    round((float) $record->vapor_porcentaje, 2),
                    round(max((float) $record->bombas_porcentaje, (float) $record->vapor_porcentaje), 2),
                    (string) ($record->estado_detallado ?: $record->estado ?: $record->estado_general),
                    $record->hodometro,
                ];
            })->all(),
            'series' => [
                [
                    'name' => 'Bombas %',
                    'points' => $limited->map(fn (Elongacion $record): array => [
                        'label' => $record->created_at?->format('d/m') ?? (string) $record->id,
                        'value' => round((float) $record->bombas_porcentaje, 2),
                    ])->all(),
                ],
                [
                    'name' => 'Vapor %',
                    'points' => $limited->map(fn (Elongacion $record): array => [
                        'label' => $record->created_at?->format('d/m') ?? (string) $record->id,
                        'value' => round((float) $record->vapor_porcentaje, 2),
                    ])->all(),
                ],
            ],
            'x_label' => 'Fecha',
            'y_label' => 'Elongacion %',
            'thresholds' => $this->elongacionThresholds(),
        ];

        return $this->withElongacionDetails($dataset, $records, [$linea], $dateRange);
    }

    /**
     * @param  Collection<int, Elongacion>  $records
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function buildElongacionesMonthlyDataset(Collection $records, array $lineas, array $dateRange, string $aggregation): array
    {
        $groups = $records->groupBy(fn (Elongacion $record): string => $this->datePeriodKey($record->created_at, $aggregation));
        $rows = [];
        $averagePoints = [];
        $peakPoints = [];

        foreach ($groups as $period => $items) {
            $maxValues = $items->map(fn (Elongacion $record): float => max(
                (float) $record->bombas_porcentaje,
                (float) $record->vapor_porcentaje
            ));

            $label = $this->formatPeriodLabel((string) $period);
            $avgMax = round((float) $maxValues->avg(), 2);
            $peakMax = round((float) $maxValues->max(), 2);

            $rows[] = [
                $label,
                $items->count(),
                round((float) $items->avg(fn (Elongacion $record): float => (float) $record->bombas_porcentaje), 2),
                round((float) $items->avg(fn (Elongacion $record): float => (float) $record->vapor_porcentaje), 2),
                $avgMax,
                $peakMax,
            ];

            $averagePoints[] = ['label' => $label, 'value' => $avgMax];
            $peakPoints[] = ['label' => $label, 'value' => $peakMax];
        }

        $title = match ($aggregation) {
            'daily' => 'Tendencia diaria de elongaciones',
            'weekly' => 'Tendencia semanal de elongaciones',
            default => 'Tendencia mensual de elongaciones',
        };

        $dataset = [
            'type' => 'elongaciones',
            'report_version' => 'elongaciones-v2',
            'title' => $title,
            'subtitle' => $dateRange['label'],
            'source_reference' => 'elongaciones agrupadas por mes',
            'headings' => ['Periodo', 'Registros', 'Promedio bombas %', 'Promedio vapor %', 'Promedio maximo %', 'Pico maximo %'],
            'rows' => $rows,
            'series' => [
                ['name' => 'Promedio maximo %', 'points' => $averagePoints],
                ['name' => 'Pico maximo %', 'points' => $peakPoints],
            ],
            'x_label' => 'Periodo',
            'y_label' => 'Elongacion %',
            'thresholds' => $this->elongacionThresholds(),
        ];

        return $this->withElongacionDetails($dataset, $records, $lineas, $dateRange);
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  Collection<int, Elongacion>  $records
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function withElongacionDetails(array $dataset, Collection $records, array $lineas, array $dateRange): array
    {
        $warningThreshold = (float) config('maintenance_ai.rules.elongacion_warning_threshold', Elongacion::LIMITE_COMPRAR);
        $criticalThreshold = (float) config('maintenance_ai.rules.elongacion_critical_threshold', Elongacion::LIMITE_CAMBIO);
        $sortedRecords = $records
            ->sortBy(fn (Elongacion $record): string => ($record->created_at?->format('Y-m-d H:i:s') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->values();
        $maxValues = $sortedRecords->map(fn (Elongacion $record): float => $this->elongacionMaxValue($record));
        $criticalRecords = $sortedRecords->filter(fn (Elongacion $record): bool => $this->elongacionMaxValue($record) >= $criticalThreshold);
        $warningRecords = $sortedRecords->filter(function (Elongacion $record) use ($warningThreshold, $criticalThreshold): bool {
            $value = $this->elongacionMaxValue($record);

            return $value >= $warningThreshold && $value < $criticalThreshold;
        });
        $latest = $sortedRecords->last();
        $peak = $sortedRecords->sortByDesc(fn (Elongacion $record): float => $this->elongacionMaxValue($record))->first();
        $lineScope = $lineas === []
            ? $sortedRecords->pluck('linea')->filter()->unique()->sort()->values()->implode(', ')
            : implode(', ', $lineas);
        $rawLimit = 2000;
        $rawRecords = $sortedRecords
            ->sortByDesc(fn (Elongacion $record): string => ($record->created_at?->format('Y-m-d H:i:s') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->take($rawLimit)
            ->values();

        $dataset['summary_cards'] = [
            ['label' => 'Registros', 'value' => (string) $sortedRecords->count(), 'tone' => 'neutral'],
            ['label' => 'Promedio max.', 'value' => $this->formatChartNumber(round((float) $maxValues->avg(), 2)).'%', 'tone' => 'normal'],
            ['label' => 'Pico max.', 'value' => $this->formatChartNumber(round((float) $maxValues->max(), 2)).'%', 'tone' => $this->elongacionTone((float) $maxValues->max())],
            ['label' => 'Criticos', 'value' => (string) $criticalRecords->count(), 'tone' => $criticalRecords->isNotEmpty() ? 'critical' : 'normal'],
        ];

        $dataset['summary_rows'] = [
            ['Reporte', (string) ($dataset['title'] ?? 'Elongaciones')],
            ['Periodo', $dateRange['label']],
            ['Lineas', $lineScope !== '' ? $lineScope : 'Sin linea identificada'],
            ['Registros usados', $sortedRecords->count()],
            ['Registros exportados en Datos', $rawRecords->count().($sortedRecords->count() > $rawLimit ? ' de '.$sortedRecords->count().' (limite operativo)' : '')],
            ['Promedio maximo %', round((float) $maxValues->avg(), 2)],
            ['Pico maximo %', round((float) $maxValues->max(), 2)],
            ['Registros en compra/alerta', $warningRecords->count()],
            ['Registros en cambio/critico', $criticalRecords->count()],
            ['Umbral compra %', $warningThreshold],
            ['Umbral cambio %', $criticalThreshold],
            ['Ultima lectura', $latest ? (($latest->linea ?: 'Sin linea').' | '.($latest->created_at?->format('d/m/Y') ?? 'Sin fecha').' | max '.$this->formatChartNumber($this->elongacionMaxValue($latest)).'%') : 'Sin lectura'],
            ['Pico registrado', $peak ? (($peak->linea ?: 'Sin linea').' | '.($peak->created_at?->format('d/m/Y') ?? 'Sin fecha').' | max '.$this->formatChartNumber($this->elongacionMaxValue($peak)).'%') : 'Sin pico'],
        ];

        $dataset['raw_headings'] = [
            'Fecha',
            'Linea',
            'Ciclo',
            'Bombas %',
            'Vapor %',
            'Maximo %',
            'Lado critico',
            'Estado calculado',
            'Estado registrado',
            'Hodometro',
            'Proveedor',
        ];
        $dataset['raw_rows'] = $rawRecords
            ->map(fn (Elongacion $record): array => $this->elongacionRawRow($record))
            ->all();
        $dataset['alert_headings'] = [
            'Fecha',
            'Linea',
            'Lado critico',
            'Bombas %',
            'Vapor %',
            'Maximo %',
            'Nivel',
            'Accion sugerida',
        ];
        $dataset['alert_rows'] = $sortedRecords
            ->filter(fn (Elongacion $record): bool => $this->elongacionMaxValue($record) >= $warningThreshold)
            ->sortByDesc(fn (Elongacion $record): float => $this->elongacionMaxValue($record))
            ->values()
            ->map(fn (Elongacion $record): array => $this->elongacionAlertRow($record))
            ->all();

        return $dataset;
    }

    private function elongacionMaxValue(Elongacion $record): float
    {
        return round(max((float) $record->bombas_porcentaje, (float) $record->vapor_porcentaje), 2);
    }

    private function elongacionCriticalSide(Elongacion $record): string
    {
        return (float) $record->bombas_porcentaje >= (float) $record->vapor_porcentaje
            ? 'Bombas'
            : 'Vapor';
    }

    private function elongacionComputedStatus(float $value): string
    {
        $criticalThreshold = (float) config('maintenance_ai.rules.elongacion_critical_threshold', Elongacion::LIMITE_CAMBIO);
        $warningThreshold = (float) config('maintenance_ai.rules.elongacion_warning_threshold', Elongacion::LIMITE_COMPRAR);

        if ($value >= $criticalThreshold) {
            return 'Cambio / critico';
        }

        if ($value >= $warningThreshold) {
            return 'Comprar / alerta';
        }

        return 'Normal';
    }

    private function elongacionTone(float $value): string
    {
        $criticalThreshold = (float) config('maintenance_ai.rules.elongacion_critical_threshold', Elongacion::LIMITE_CAMBIO);
        $warningThreshold = (float) config('maintenance_ai.rules.elongacion_warning_threshold', Elongacion::LIMITE_COMPRAR);

        if ($value >= $criticalThreshold) {
            return 'critical';
        }

        if ($value >= $warningThreshold) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * @return array<int, mixed>
     */
    private function elongacionRawRow(Elongacion $record): array
    {
        $max = $this->elongacionMaxValue($record);

        return [
            $record->created_at?->format('d/m/Y H:i') ?? '',
            $record->linea,
            $this->elongacionCycleLabel($record),
            round((float) $record->bombas_porcentaje, 2),
            round((float) $record->vapor_porcentaje, 2),
            $max,
            $this->elongacionCriticalSide($record),
            $this->elongacionComputedStatus($max),
            (string) ($record->estado_detallado ?: $record->estado ?: ''),
            $record->hodometro,
            $record->proveedor_actual ?? '',
        ];
    }

    private function elongacionCycleLabel(Elongacion $record): string
    {
        if ($record->cadenaCiclo?->codigo) {
            return (string) $record->cadenaCiclo->codigo;
        }

        if ($record->cadenaCiclo?->numero_ciclo) {
            return 'Ciclo '.$record->cadenaCiclo->numero_ciclo;
        }

        return $record->cadena_ciclo_id ? 'Ciclo #'.$record->cadena_ciclo_id : 'Sin ciclo';
    }

    /**
     * @return array<int, mixed>
     */
    private function elongacionAlertRow(Elongacion $record): array
    {
        $max = $this->elongacionMaxValue($record);
        $status = $this->elongacionComputedStatus($max);

        return [
            $record->created_at?->format('d/m/Y') ?? '',
            $record->linea,
            $this->elongacionCriticalSide($record),
            round((float) $record->bombas_porcentaje, 2),
            round((float) $record->vapor_porcentaje, 2),
            $max,
            $status,
            str_contains(Str::lower($status), 'critico')
                ? 'Programar cambio y validar refacciones disponibles.'
                : 'Preparar compra y aumentar seguimiento de tendencia.',
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  Collection<int, AnalisisLavadora>  $records
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function withAnalisisLavadoraDetails(array $dataset, Collection $records, array $lineas, array $dateRange): array
    {
        $sortedRecords = $records
            ->sortBy(fn (AnalisisLavadora $record): string => ($record->fecha_analisis?->format('Y-m-d') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->values();
        $damagedRecords = $sortedRecords->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoDanado($record->estado_operativo));
        $reviewRecords = $sortedRecords->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoRequiereRevision($record->estado_operativo));
        $wearRecords = $sortedRecords->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoDesgaste($record->estado_operativo));
        $changedRecords = $sortedRecords->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoCambiado($record->estado_operativo));
        $alertRecords = $sortedRecords->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::requiereCierreAdministrativo($record->estado_operativo));
        $rawRecords = $sortedRecords
            ->sortByDesc(fn (AnalisisLavadora $record): string => ($record->fecha_analisis?->format('Y-m-d') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->take(2000)
            ->values();
        $topAlertLine = $alertRecords
            ->groupBy(fn (AnalisisLavadora $record): string => $this->recordLineName($record))
            ->sortByDesc(fn (Collection $items): int => $items->count())
            ->keys()
            ->first();

        $dataset['summary_cards'] = [
            ['label' => 'Registros', 'value' => (string) $sortedRecords->count(), 'tone' => 'neutral'],
            ['label' => 'Danados', 'value' => (string) $damagedRecords->count(), 'tone' => $damagedRecords->isNotEmpty() ? 'critical' : 'normal'],
            ['label' => 'Revision/desgaste', 'value' => (string) ($reviewRecords->count() + $wearRecords->count()), 'tone' => ($reviewRecords->isNotEmpty() || $wearRecords->isNotEmpty()) ? 'warning' : 'normal'],
            ['label' => 'Cambiados', 'value' => (string) $changedRecords->count(), 'tone' => 'normal'],
        ];
        $dataset['summary_rows'] = [
            ['Reporte', (string) ($dataset['title'] ?? 'Analisis de lavadora')],
            ['Periodo', $dateRange['label']],
            ['Lineas', $this->lineScopeFromRecords($sortedRecords, $lineas)],
            ['Registros usados', $sortedRecords->count()],
            ['Registros exportados en Datos', $rawRecords->count()],
            ['Componentes distintos', $sortedRecords->map(fn (AnalisisLavadora $record): string => $this->componentName($record))->filter()->unique()->count()],
            ['Danados / criticos', $damagedRecords->count()],
            ['Requieren revision', $reviewRecords->count()],
            ['Con desgaste', $wearRecords->count()],
            ['Cambiados', $changedRecords->count()],
            ['Lavadora con mas alertas', $topAlertLine ?: 'Sin alertas'],
        ];
        $dataset['raw_headings'] = ['Fecha', 'Linea', 'Componente', 'Reductor', 'Lado', 'Estado operativo', 'Estado original', 'Correccion', 'Actividad', 'Orden'];
        $dataset['raw_rows'] = $rawRecords
            ->map(fn (AnalisisLavadora $record): array => $this->analisisLavadoraRawRow($record))
            ->all();
        $dataset['alert_headings'] = ['Fecha', 'Linea', 'Componente', 'Reductor', 'Lado', 'Nivel', 'Estado', 'Accion sugerida'];
        $dataset['alert_rows'] = $alertRecords
            ->sortByDesc(fn (AnalisisLavadora $record): int => AnalisisLavadora::esEstadoDanado($record->estado_operativo) ? 2 : 1)
            ->values()
            ->map(fn (AnalisisLavadora $record): array => $this->analisisLavadoraAlertRow($record))
            ->all();

        return $dataset;
    }

    /**
     * @return array<int, mixed>
     */
    private function analisisLavadoraRawRow(AnalisisLavadora $record): array
    {
        return [
            $record->fecha_analisis?->format('d/m/Y') ?? '',
            $this->recordLineName($record),
            $this->componentName($record),
            (string) ($record->reductor ?? ''),
            (string) ($record->lado ?? ''),
            (string) ($record->estado_operativo ?? ''),
            (string) ($record->estado ?? ''),
            (string) ($record->estado_correccion ?? ''),
            (string) ($record->actividad ?? ''),
            (string) ($record->numero_orden ?? ''),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function analisisLavadoraAlertRow(AnalisisLavadora $record): array
    {
        $critical = AnalisisLavadora::esEstadoDanado($record->estado_operativo);

        return [
            $record->fecha_analisis?->format('d/m/Y') ?? '',
            $this->recordLineName($record),
            $this->componentName($record),
            (string) ($record->reductor ?? ''),
            (string) ($record->lado ?? ''),
            $critical ? 'Critico' : 'Alerta',
            (string) ($record->estado_operativo ?? ''),
            $critical
                ? 'Programar cambio, validar refaccion y cerrar seguimiento.'
                : 'Revisar condicion, documentar hallazgo y programar correccion.',
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  Collection<int, LavadoraCostEntry>  $records
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function withCostosLavadoraDetails(array $dataset, Collection $records, array $lineas, array $dateRange): array
    {
        $sortedRecords = $records
            ->sortBy(fn (LavadoraCostEntry $record): string => ($record->cost_date?->format('Y-m-d') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->values();
        $totalCost = round((float) $sortedRecords->sum(fn (LavadoraCostEntry $record): float => (float) $record->total_cost), 2);
        $averageCost = round((float) $sortedRecords->avg(fn (LavadoraCostEntry $record): float => (float) $record->total_cost), 2);
        $recordWarningThreshold = max(1, $averageCost * 1.25);
        $recordCriticalThreshold = max($recordWarningThreshold, $averageCost * 2);
        $alertRecords = $sortedRecords
            ->filter(fn (LavadoraCostEntry $record): bool => (float) $record->total_cost >= $recordWarningThreshold)
            ->sortByDesc(fn (LavadoraCostEntry $record): float => (float) $record->total_cost)
            ->values();
        $rawRecords = $sortedRecords
            ->sortByDesc(fn (LavadoraCostEntry $record): string => ($record->cost_date?->format('Y-m-d') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->take(2000)
            ->values();
        $topLine = $sortedRecords
            ->groupBy(fn (LavadoraCostEntry $record): string => $this->recordLineName($record))
            ->map(fn (Collection $items): float => (float) $items->sum(fn (LavadoraCostEntry $record): float => (float) $record->total_cost))
            ->sortDesc()
            ->keys()
            ->first();
        $topItem = $sortedRecords
            ->groupBy(fn (LavadoraCostEntry $record): string => $this->costConcept($record))
            ->map(fn (Collection $items): float => (float) $items->sum(fn (LavadoraCostEntry $record): float => (float) $record->total_cost))
            ->sortDesc()
            ->keys()
            ->first();
        $groupAverage = (float) collect((array) ($dataset['rows'] ?? []))
            ->map(fn (array $row): float => (float) ($row[2] ?? 0))
            ->filter(fn (float $value): bool => $value > 0)
            ->avg();

        if ($groupAverage > 0) {
            $dataset['thresholds'] = [
                ['value' => round($groupAverage * 1.25, 2), 'label' => 'Alerta costo', 'color' => '#d97706'],
                ['value' => round($groupAverage * 2, 2), 'label' => 'Critico costo', 'color' => '#dc2626'],
            ];
        }

        $dataset['summary_cards'] = [
            ['label' => 'Registros', 'value' => (string) $sortedRecords->count(), 'tone' => 'neutral'],
            ['label' => 'Costo total', 'value' => '$'.number_format($totalCost, 2), 'tone' => $totalCost > 0 ? 'warning' : 'normal'],
            ['label' => 'Promedio', 'value' => '$'.number_format($averageCost, 2), 'tone' => 'neutral'],
            ['label' => 'Montos altos', 'value' => (string) $alertRecords->count(), 'tone' => $alertRecords->isNotEmpty() ? 'critical' : 'normal'],
        ];
        $dataset['summary_rows'] = [
            ['Reporte', (string) ($dataset['title'] ?? 'Costos de lavadora')],
            ['Periodo', $dateRange['label']],
            ['Lineas', $this->lineScopeFromRecords($sortedRecords, $lineas)],
            ['Registros usados', $sortedRecords->count()],
            ['Registros exportados en Datos', $rawRecords->count()],
            ['Costo total MXN', $totalCost],
            ['Costo promedio por registro MXN', $averageCost],
            ['Costo maximo por registro MXN', round((float) $sortedRecords->max(fn (LavadoraCostEntry $record): float => (float) $record->total_cost), 2)],
            ['Registros sobre umbral alto', $alertRecords->count()],
            ['Lavadora con mayor costo', $topLine ?: 'Sin linea'],
            ['Concepto con mayor costo', $topItem ?: 'Sin concepto'],
        ];
        $dataset['raw_headings'] = ['Fecha', 'Linea', 'Concepto', 'SKU', 'Categoria', 'Cantidad', 'Costo unitario', 'Costo total', 'Fuente', 'Referencia'];
        $dataset['raw_rows'] = $rawRecords
            ->map(fn (LavadoraCostEntry $record): array => $this->costosLavadoraRawRow($record))
            ->all();
        $dataset['alert_headings'] = ['Fecha', 'Linea', 'Concepto', 'Costo total', 'Promedio base', 'Nivel', 'Accion sugerida'];
        $dataset['alert_rows'] = $alertRecords
            ->map(fn (LavadoraCostEntry $record): array => $this->costosLavadoraAlertRow($record, $averageCost, $recordCriticalThreshold))
            ->all();

        return $dataset;
    }

    /**
     * @return array<int, mixed>
     */
    private function costosLavadoraRawRow(LavadoraCostEntry $record): array
    {
        return [
            $record->cost_date?->format('d/m/Y') ?? '',
            $this->recordLineName($record),
            $this->costConcept($record),
            (string) ($record->catalog_sku_snapshot ?: $record->catalogItem?->sku ?: ''),
            (string) ($record->catalog_category_snapshot ?: $record->catalogItem?->categoria ?: ''),
            round((float) $record->quantity, 2),
            round((float) $record->unit_cost, 2),
            round((float) $record->total_cost, 2),
            LavadoraCostEntry::sourceLabel($record->source_type),
            (string) ($record->source_reference ?? ''),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function costosLavadoraAlertRow(LavadoraCostEntry $record, float $averageCost, float $criticalThreshold): array
    {
        $total = (float) $record->total_cost;

        return [
            $record->cost_date?->format('d/m/Y') ?? '',
            $this->recordLineName($record),
            $this->costConcept($record),
            round($total, 2),
            round($averageCost, 2),
            $total >= $criticalThreshold ? 'Critico' : 'Alerta',
            'Validar causa, presupuesto y recurrencia del concepto.',
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  Collection<int, PlanAccion>  $records
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function withPlanAccionDetails(array $dataset, Collection $records, array $lineas, array $dateRange): array
    {
        $sortedRecords = $records
            ->sortBy(fn (PlanAccion $record): string => ($record->created_at?->format('Y-m-d H:i:s') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->values();
        $completedRecords = $sortedRecords->filter(fn (PlanAccion $record): bool => (bool) $record->completado);
        $pendingRecords = $sortedRecords->reject(fn (PlanAccion $record): bool => (bool) $record->completado);
        $highPriorityRecords = $pendingRecords->filter(fn (PlanAccion $record): bool => $this->isHighPriority($record->priority_level));
        $overdueRecords = $pendingRecords->filter(fn (PlanAccion $record): bool => $this->planAccionIsOverdue($record));
        $attentionRecords = $pendingRecords
            ->filter(fn (PlanAccion $record): bool => $this->isHighPriority($record->priority_level) || $this->planAccionIsOverdue($record))
            ->values();
        $rawRecords = $sortedRecords
            ->sortByDesc(fn (PlanAccion $record): string => ($record->created_at?->format('Y-m-d H:i:s') ?? '').'-'.str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))
            ->take(2000)
            ->values();
        $topPendingLine = $pendingRecords
            ->groupBy(fn (PlanAccion $record): string => $this->recordLineName($record))
            ->sortByDesc(fn (Collection $items): int => $items->count())
            ->keys()
            ->first();

        $dataset['summary_cards'] = [
            ['label' => 'Planes', 'value' => (string) $sortedRecords->count(), 'tone' => 'neutral'],
            ['label' => 'Pendientes', 'value' => (string) $pendingRecords->count(), 'tone' => $pendingRecords->isNotEmpty() ? 'warning' : 'normal'],
            ['label' => 'Vencidos', 'value' => (string) $overdueRecords->count(), 'tone' => $overdueRecords->isNotEmpty() ? 'critical' : 'normal'],
            ['label' => 'Completados', 'value' => (string) $completedRecords->count(), 'tone' => 'normal'],
        ];
        $dataset['summary_rows'] = [
            ['Reporte', (string) ($dataset['title'] ?? 'Planes de accion')],
            ['Periodo', $dateRange['label']],
            ['Lineas', $this->lineScopeFromRecords($sortedRecords, $lineas)],
            ['Registros usados', $sortedRecords->count()],
            ['Registros exportados en Datos', $rawRecords->count()],
            ['Pendientes', $pendingRecords->count()],
            ['Completados', $completedRecords->count()],
            ['Prioridad alta/critica pendiente', $highPriorityRecords->count()],
            ['Vencidos', $overdueRecords->count()],
            ['Lavadora con mas pendientes', $topPendingLine ?: 'Sin pendientes'],
        ];
        $dataset['raw_headings'] = ['Creado', 'Linea', 'Estado', 'Prioridad', 'Tipo', 'Actividad', 'Fecha objetivo', 'Dias vencimiento', 'Completado'];
        $dataset['raw_rows'] = $rawRecords
            ->map(fn (PlanAccion $record): array => $this->planAccionRawRow($record))
            ->all();
        $dataset['alert_headings'] = ['Creado', 'Linea', 'Prioridad', 'Fecha objetivo', 'Nivel', 'Actividad', 'Accion sugerida'];
        $dataset['alert_rows'] = $attentionRecords
            ->sortBy(fn (PlanAccion $record): int => $record->dias_para_vencimiento ?? 9999)
            ->values()
            ->map(fn (PlanAccion $record): array => $this->planAccionAlertRow($record))
            ->all();

        return $dataset;
    }

    /**
     * @return array<int, mixed>
     */
    private function planAccionRawRow(PlanAccion $record): array
    {
        $targetDate = $this->planAccionTargetDate($record);

        return [
            $record->created_at?->format('d/m/Y H:i') ?? '',
            $this->recordLineName($record),
            $this->planAccionStatus($record),
            (string) ($record->priority_level ?? ''),
            (string) ($record->maintenance_type ?? ''),
            (string) ($record->actividad ?? ''),
            $targetDate?->format('d/m/Y') ?? '',
            $record->dias_para_vencimiento,
            (bool) $record->completado ? 'Si' : 'No',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function planAccionAlertRow(PlanAccion $record): array
    {
        $overdue = $this->planAccionIsOverdue($record);
        $targetDate = $this->planAccionTargetDate($record);

        return [
            $record->created_at?->format('d/m/Y') ?? '',
            $this->recordLineName($record),
            (string) ($record->priority_level ?? ''),
            $targetDate?->format('d/m/Y') ?? '',
            $overdue ? 'Critico' : 'Alerta',
            (string) ($record->actividad ?? ''),
            $overdue
                ? 'Reprogramar o ejecutar; el plan esta vencido.'
                : 'Confirmar responsable, recursos y fecha objetivo.',
        ];
    }

    private function recordLineName(mixed $record): string
    {
        $line = $record->linea ?? null;

        if ($line instanceof Linea) {
            return $line->nombre ?: 'Sin linea';
        }

        if (is_object($line) && isset($line->nombre)) {
            return (string) $line->nombre;
        }

        if (is_string($line) && trim($line) !== '') {
            return trim($line);
        }

        return 'Sin linea';
    }

    /**
     * @param  Collection<int, mixed>  $records
     * @param  array<int, string>  $lineas
     */
    private function lineScopeFromRecords(Collection $records, array $lineas): string
    {
        if ($lineas !== []) {
            return implode(', ', $lineas);
        }

        $scope = $records
            ->map(fn (mixed $record): string => $this->recordLineName($record))
            ->reject(fn (string $linea): bool => $linea === 'Sin linea')
            ->unique()
            ->sort()
            ->values()
            ->implode(', ');

        return $scope !== '' ? $scope : 'Todas las lineas';
    }

    private function componentName(mixed $record): string
    {
        $component = $record->componente ?? null;

        if (is_object($component) && isset($component->nombre)) {
            return (string) $component->nombre;
        }

        return (string) ($record->component_snapshot ?? '');
    }

    private function costConcept(LavadoraCostEntry $record): string
    {
        return (string) (
            $record->catalog_name_snapshot
            ?: $record->catalogItem?->nombre
            ?: $record->component_snapshot
            ?: $this->componentName($record)
            ?: 'Sin concepto'
        );
    }

    private function isHighPriority(?string $priority): bool
    {
        $priority = $this->normalize((string) $priority);

        return in_array($priority, ['alta', 'alto', 'critica', 'critico', 'critical', 'high', 'urgente'], true);
    }

    private function planAccionStatus(PlanAccion $record): string
    {
        if ((bool) $record->completado) {
            return 'Completado';
        }

        if ($this->planAccionIsOverdue($record)) {
            return 'Vencido';
        }

        return (string) ($record->estado ?: 'Pendiente');
    }

    private function planAccionIsOverdue(PlanAccion $record): bool
    {
        if ((bool) $record->completado) {
            return false;
        }

        $today = CarbonImmutable::now(config('app.timezone', 'UTC'))->startOfDay();

        foreach ([$record->fecha_pcm1, $record->fecha_pcm2, $record->fecha_pcm3, $record->fecha_pcm4] as $date) {
            if ($date && CarbonImmutable::instance($date)->startOfDay()->lessThan($today)) {
                return true;
            }
        }

        return false;
    }

    private function planAccionTargetDate(PlanAccion $record): ?CarbonImmutable
    {
        return collect([$record->fecha_pcm1, $record->fecha_pcm2, $record->fecha_pcm3, $record->fecha_pcm4])
            ->filter()
            ->map(fn ($date): CarbonImmutable => CarbonImmutable::instance($date)->startOfDay())
            ->sort()
            ->first();
    }

    /**
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>|null
     */
    private function buildAnalisisLavadoraDataset(array $lineas, array $dateRange, string $aggregation, string $question): ?array
    {
        $lineIds = $this->lineIdsFor($lineas);
        $records = AnalisisLavadora::with(['linea:id,nombre', 'componente:id,nombre,codigo'])
            ->when($lineIds !== [], fn ($query) => $query->whereIn('linea_id', $lineIds))
            ->when($dateRange['from'], fn ($query, CarbonImmutable $from) => $query->whereDate('fecha_analisis', '>=', $from->toDateString()))
            ->when($dateRange['to'], fn ($query, CarbonImmutable $to) => $query->whereDate('fecha_analisis', '<=', $to->toDateString()))
            ->orderBy('fecha_analisis')
            ->get();

        if ($this->wantsCriticalOnly($question)) {
            $records = $records
                ->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoDanado($record->estado_operativo))
                ->values();
        }

        if ($records->isEmpty()) {
            return null;
        }

        $rows = [];
        $totalPoints = [];
        $damagedPoints = [];
        $reviewPoints = [];

        $groups = $aggregation === 'by_line'
            ? $records->groupBy(fn (AnalisisLavadora $record): string => $this->recordLineName($record))
            : $records->groupBy(fn (AnalisisLavadora $record): string => $this->datePeriodKey($record->fecha_analisis, $aggregation));

        foreach ($groups as $period => $items) {
            $label = $this->formatPeriodLabel((string) $period);
            $damaged = $items->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoDanado($record->estado_operativo))->count();
            $review = $items->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoRequiereRevision($record->estado_operativo))->count();
            $wear = $items->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoDesgaste($record->estado_operativo))->count();
            $changed = $items->filter(fn (AnalisisLavadora $record): bool => AnalisisLavadora::esEstadoCambiado($record->estado_operativo))->count();

            $rows[] = [$label, $items->count(), $damaged, $review, $wear, $changed];
            $totalPoints[] = ['label' => $label, 'value' => $items->count()];
            $damagedPoints[] = ['label' => $label, 'value' => $damaged];
            $reviewPoints[] = ['label' => $label, 'value' => $review];
        }

        $dataset = [
            'type' => 'analisis_lavadora',
            'report_version' => 'analisis-lavadora-v2',
            'title' => 'Tendencia de analisis de lavadora',
            'subtitle' => $this->scopeSubtitle($lineas, $dateRange),
            'source_reference' => 'analisis_componentes',
            'headings' => ['Periodo', 'Registros', 'Danados', 'Requieren revision', 'Desgaste', 'Cambiados'],
            'rows' => $rows,
            'series' => [
                ['name' => 'Registros', 'points' => $totalPoints],
                ['name' => 'Danados', 'points' => $damagedPoints],
                ['name' => 'Revision', 'points' => $reviewPoints],
            ],
            'x_label' => 'Periodo',
            'y_label' => 'Cantidad',
            'thresholds' => [],
        ];

        if ($aggregation === 'by_line') {
            $dataset['title'] = 'Comparativo de analisis por lavadora';
            $dataset['x_label'] = 'Lavadora';
            $dataset['source_reference'] = 'analisis_componentes por linea';
        }

        return $this->withAnalisisLavadoraDetails($dataset, $records, $lineas, $dateRange);
    }

    /**
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>|null
     */
    private function buildCostosLavadoraDataset(array $lineas, array $dateRange, string $aggregation, string $question): ?array
    {
        $lineIds = $this->lineIdsFor($lineas);
        $records = LavadoraCostEntry::with([
            'linea:id,nombre',
            'componente:id,nombre,codigo',
            'catalogItem:id,nombre,sku,categoria,unidad_medida',
        ])
            ->when($lineIds !== [], fn ($query) => $query->whereIn('linea_id', $lineIds))
            ->when($dateRange['from'], fn ($query, CarbonImmutable $from) => $query->whereDate('cost_date', '>=', $from->toDateString()))
            ->when($dateRange['to'], fn ($query, CarbonImmutable $to) => $query->whereDate('cost_date', '<=', $to->toDateString()))
            ->orderBy('cost_date')
            ->get();

        if ($this->wantsCriticalOnly($question) && $records->isNotEmpty()) {
            $averageCost = (float) $records->avg(fn (LavadoraCostEntry $record): float => (float) $record->total_cost);
            $records = $records
                ->filter(fn (LavadoraCostEntry $record): bool => (float) $record->total_cost >= max(1, $averageCost * 1.25))
                ->values();
        }

        if ($records->isEmpty()) {
            return null;
        }

        $rows = [];
        $points = [];
        $groups = $aggregation === 'by_line'
            ? $records->groupBy(fn (LavadoraCostEntry $record): string => $this->recordLineName($record))
            : $records->groupBy(fn (LavadoraCostEntry $record): string => $this->datePeriodKey($record->cost_date, $aggregation));

        foreach ($groups as $period => $items) {
            $label = $this->formatPeriodLabel((string) $period);
            $total = round((float) $items->sum(fn (LavadoraCostEntry $record): float => (float) $record->total_cost), 2);

            $rows[] = [$label, $items->count(), $total, round((float) $items->avg(fn (LavadoraCostEntry $record): float => (float) $record->total_cost), 2)];
            $points[] = ['label' => $label, 'value' => $total];
        }

        $dataset = [
            'type' => 'costos_lavadora',
            'report_version' => 'costos-lavadora-v2',
            'title' => 'Tendencia de costos de lavadora',
            'subtitle' => $this->scopeSubtitle($lineas, $dateRange),
            'source_reference' => 'lavadora_cost_entries',
            'headings' => ['Periodo', 'Registros', 'Costo total MXN', 'Costo promedio MXN'],
            'rows' => $rows,
            'series' => [
                ['name' => 'Costo total MXN', 'points' => $points],
            ],
            'x_label' => 'Periodo',
            'y_label' => 'MXN',
            'thresholds' => [],
        ];

        if ($aggregation === 'by_line') {
            $dataset['title'] = 'Ranking de costos por lavadora';
            $dataset['headings'][0] = 'Linea';
            $dataset['x_label'] = 'Lavadora';
            $dataset['source_reference'] = 'lavadora_cost_entries por linea';
        }

        return $this->withCostosLavadoraDetails($dataset, $records, $lineas, $dateRange);
    }

    /**
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>|null
     */
    private function buildPlanAccionDataset(array $lineas, array $dateRange, string $aggregation, string $question): ?array
    {
        $lineIds = $this->lineIdsFor($lineas);
        $records = PlanAccion::with('linea:id,nombre')
            ->when($lineIds !== [], fn ($query) => $query->whereIn('linea_id', $lineIds))
            ->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_LAVADORA)
                    ->orWhereNull('tipo_equipo');
            })
            ->when($dateRange['from'], fn ($query, CarbonImmutable $from) => $query->where('created_at', '>=', $from))
            ->when($dateRange['to'], fn ($query, CarbonImmutable $to) => $query->where('created_at', '<=', $to))
            ->orderBy('created_at')
            ->get();

        if ($this->wantsCriticalOnly($question)) {
            $records = $records
                ->filter(fn (PlanAccion $record): bool => ! (bool) $record->completado && ($this->isHighPriority($record->priority_level) || $this->planAccionIsOverdue($record)))
                ->values();
        }

        if ($records->isEmpty()) {
            return null;
        }

        $rows = [];
        $pendingPoints = [];
        $completedPoints = [];
        $totalPoints = [];

        $groups = $aggregation === 'by_line'
            ? $records->groupBy(fn (PlanAccion $record): string => $this->recordLineName($record))
            : $records->groupBy(fn (PlanAccion $record): string => $this->datePeriodKey($record->created_at, $aggregation));

        foreach ($groups as $period => $items) {
            $label = $this->formatPeriodLabel((string) $period);
            $completed = $items->filter(fn (PlanAccion $record): bool => (bool) $record->completado)->count();
            $pending = $items->count() - $completed;
            $critical = $items->filter(fn (PlanAccion $record): bool => $this->isHighPriority($record->priority_level))->count();

            $rows[] = [$label, $items->count(), $pending, $completed, $critical, $items->filter(fn (PlanAccion $record): bool => $this->planAccionIsOverdue($record))->count()];
            $totalPoints[] = ['label' => $label, 'value' => $items->count()];
            $pendingPoints[] = ['label' => $label, 'value' => $pending];
            $completedPoints[] = ['label' => $label, 'value' => $completed];
        }

        $dataset = [
            'type' => 'plan_accion',
            'report_version' => 'plan-accion-v2',
            'title' => 'Tendencia de planes de accion',
            'subtitle' => $this->scopeSubtitle($lineas, $dateRange),
            'source_reference' => 'plan_accion',
            'headings' => ['Periodo', 'Planes', 'Pendientes', 'Completados', 'Prioridad alta/critica', 'Vencidos'],
            'rows' => $rows,
            'series' => [
                ['name' => 'Planes', 'points' => $totalPoints],
                ['name' => 'Pendientes', 'points' => $pendingPoints],
                ['name' => 'Completados', 'points' => $completedPoints],
            ],
            'x_label' => 'Periodo',
            'y_label' => 'Cantidad',
            'thresholds' => [],
        ];

        if ($aggregation === 'by_line') {
            $dataset['title'] = 'Comparativo de planes de accion por lavadora';
            $dataset['headings'][0] = 'Linea';
            $dataset['x_label'] = 'Lavadora';
            $dataset['source_reference'] = 'plan_accion por linea';
        }

        return $this->withPlanAccionDetails($dataset, $records, $lineas, $dateRange);
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array<int, array<string, mixed>>
     */
    private function storeChartImages(User $user, array $dataset, string $chartType): array
    {
        $baseName = $this->fileBaseName((string) $dataset['title']);
        $artifacts = [];

        if (extension_loaded('gd')) {
            $pngFileName = $baseName.'.png';
            $pngPath = $this->artifactPath($user, $pngFileName);

            Storage::disk('local')->put($pngPath, $this->buildPng($dataset, $chartType));
            $artifacts[] = $this->artifactMetadata('image', $pngPath, $pngFileName, 'image/png', 'PNG');
        }

        $svgFileName = $baseName.'.svg';
        $svgPath = $this->artifactPath($user, $svgFileName);

        Storage::disk('local')->put($svgPath, $this->buildSvg($dataset, $chartType));
        $artifacts[] = $this->artifactMetadata('svg', $svgPath, $svgFileName, 'image/svg+xml', 'SVG');

        return $artifacts;
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array<string, mixed>
     */
    private function storeExcel(User $user, array $dataset, string $chartType): array
    {
        $fileName = $this->fileBaseName((string) $dataset['title']).'.xlsx';
        $path = $this->artifactPath($user, $fileName);
        $dashboardImagePath = $this->temporaryDashboardChartPath($dataset, $chartType);

        $workbookSheets = is_array($dataset['workbook_sheets'] ?? null)
            ? (array) $dataset['workbook_sheets']
            : [];

        $dashboard = $workbookSheets !== []
            ? $this->excelDashboard($dataset, $chartType, $dashboardImagePath)
            : [];

        $export = $workbookSheets !== []
            ? new AssistantAnalyticsWorkbookExport($workbookSheets, $dashboard)
            : new AssistantAnalyticsExport(
                (string) $dataset['title'],
                (string) $dataset['subtitle'],
                (array) $dataset['headings'],
                (array) $dataset['rows'],
            );

        try {
            Excel::store($export, $path, 'local');
        } finally {
            $this->deleteTemporaryFile($dashboardImagePath);
        }

        return $this->artifactMetadata(
            'excel',
            $path,
            $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Excel'
        );
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function temporaryDashboardChartPath(array $dataset, string $chartType): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $basePath = tempnam(sys_get_temp_dir(), 'assistant-chart-');

        if ($basePath === false) {
            return null;
        }

        $path = $basePath.'.png';
        @unlink($basePath);

        try {
            file_put_contents($path, $this->buildPng($dataset, $chartType));

            return is_file($path) ? $path : null;
        } catch (Throwable $exception) {
            report($exception);
            $this->deleteTemporaryFile($path);

            return null;
        }
    }

    private function deleteTemporaryFile(?string $path): void
    {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array<string, mixed>
     */
    private function excelDashboard(array $dataset, string $chartType, ?string $dashboardImagePath): array
    {
        return [
            'title' => (string) ($dataset['title'] ?? 'Reporte operativo'),
            'subtitle' => (string) ($dataset['subtitle'] ?? ''),
            'conclusion' => $this->dashboardConclusion($dataset),
            'summary_cards' => array_values((array) ($dataset['summary_cards'] ?? [])),
            'filter_rows' => array_values((array) ($dataset['filter_rows'] ?? [])),
            'chart_type' => $chartType,
            'chart_image_path' => $dashboardImagePath,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function dashboardConclusion(array $dataset): string
    {
        $alerts = count((array) ($dataset['alert_rows'] ?? []));
        $rows = count((array) ($dataset['rows'] ?? []));
        $type = (string) ($dataset['type'] ?? '');

        if ($alerts > 0) {
            $subject = match ($type) {
                'costos_lavadora' => 'registros de costo alto',
                'plan_accion' => 'planes que requieren atencion',
                'analisis_lavadora' => 'componentes en revision o dano',
                default => 'registros en alerta o criticos',
            };

            return "Se generaron {$rows} puntos de tendencia y se detectaron {$alerts} {$subject}. Revisa la hoja Alertas para priorizar acciones.";
        }

        return "Se generaron {$rows} puntos de tendencia sin alertas dentro de los filtros solicitados. Revisa Datos para validar el detalle usado.";
    }

    /**
     * @return array<string, mixed>
     */
    private function artifactMetadata(string $kind, string $path, string $fileName, string $mimeType, string $label): array
    {
        return [
            'kind' => $kind,
            'disk' => 'local',
            'path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'label' => $label,
            'size' => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : null,
        ];
    }

    private function artifactPath(User $user, string $fileName): string
    {
        return implode('/', [
            'assistant-chat-artifacts',
            (string) $user->id,
            now()->format('Y/m/d'),
            $fileName,
        ]);
    }

    private function fileBaseName(string $title): string
    {
        $slug = Str::slug(Str::ascii($title)) ?: 'reporte';

        return now()->format('His').'-'.Str::lower(Str::random(8)).'-'.$slug;
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function buildSvg(array $dataset, string $chartType): string
    {
        $series = array_values(array_filter((array) ($dataset['series'] ?? []), fn ($item): bool => is_array($item)));
        $chartType = $chartType === 'bar' && count($series) === 1 ? 'bar' : 'line';
        $width = 1100;
        $height = 680;
        $left = 88;
        $right = 44;
        $top = 252;
        $bottom = 104;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $values = $this->seriesValues($series);
        $thresholds = array_values(array_filter((array) ($dataset['thresholds'] ?? []), fn ($item): bool => is_array($item)));
        $summaryCards = array_values(array_filter((array) ($dataset['summary_cards'] ?? []), fn ($item): bool => is_array($item)));

        foreach ($thresholds as $threshold) {
            $values[] = (float) ($threshold['value'] ?? 0);
        }

        $maxValue = max($values !== [] ? $values : [1]);
        $minValue = min(0, min($values !== [] ? $values : [0]));

        if ($maxValue <= $minValue) {
            $maxValue = $minValue + 1;
        }

        $maxValue = $this->niceUpperBound($maxValue);
        $labels = $this->labelsFromSeries($series);
        $count = max(1, count($labels));
        $svg = [];

        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" role="img" aria-label="'.$this->svgText((string) $dataset['title']).'">';
        $svg[] = '<rect width="'.$width.'" height="'.$height.'" rx="26" fill="#f8fafc"/>';
        $svg[] = '<rect x="18" y="18" width="'.($width - 36).'" height="'.($height - 36).'" rx="22" fill="#ffffff" stroke="#e2e8f0"/>';
        $svg[] = '<rect x="18" y="18" width="'.($width - 36).'" height="104" rx="22" fill="#0f172a"/>';
        $svg[] = '<rect x="18" y="84" width="'.($width - 36).'" height="38" fill="#0f172a"/>';
        $svg[] = '<text x="46" y="58" font-family="Arial, sans-serif" font-size="27" font-weight="700" fill="#f8fafc">'.$this->svgText((string) $dataset['title']).'</text>';
        $svg[] = '<text x="46" y="86" font-family="Arial, sans-serif" font-size="14" fill="#cbd5e1">'.$this->svgText((string) $dataset['subtitle']).'</text>';
        $svg[] = '<text x="'.($width - 46).'" y="58" text-anchor="end" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="#fbbf24">LEGADO AB FENIX</text>';

        foreach (array_slice($summaryCards, 0, 4) as $index => $card) {
            $cardWidth = 238;
            $cardHeight = 76;
            $x = 46 + (($cardWidth + 18) * $index);
            $y = 142;
            $tone = (string) ($card['tone'] ?? 'neutral');
            $accent = match ($tone) {
                'critical' => '#dc2626',
                'warning' => '#d97706',
                'normal' => '#16a34a',
                default => '#2563eb',
            };

            $svg[] = '<rect x="'.$x.'" y="'.$y.'" width="'.$cardWidth.'" height="'.$cardHeight.'" rx="16" fill="#ffffff" stroke="#e2e8f0"/>';
            $svg[] = '<rect x="'.$x.'" y="'.$y.'" width="6" height="'.$cardHeight.'" rx="3" fill="'.$accent.'"/>';
            $svg[] = '<text x="'.($x + 22).'" y="'.($y + 28).'" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="#64748b">'.$this->svgText((string) ($card['label'] ?? '')).'</text>';
            $svg[] = '<text x="'.($x + 22).'" y="'.($y + 58).'" font-family="Arial, sans-serif" font-size="25" font-weight="800" fill="#0f172a">'.$this->svgText((string) ($card['value'] ?? '0')).'</text>';
        }

        $svg[] = '<rect x="'.$left.'" y="'.$top.'" width="'.$plotWidth.'" height="'.$plotHeight.'" rx="18" fill="#ffffff" stroke="#e2e8f0"/>';

        if (($dataset['type'] ?? null) === 'elongaciones' && $thresholds !== []) {
            $warning = (float) ($thresholds[0]['value'] ?? 0);
            $critical = (float) ($thresholds[1]['value'] ?? $warning);
            $normalY = $top + $plotHeight - (($warning - $minValue) / ($maxValue - $minValue)) * $plotHeight;
            $criticalY = $top + $plotHeight - (($critical - $minValue) / ($maxValue - $minValue)) * $plotHeight;

            $svg[] = '<rect x="'.$left.'" y="'.$top.'" width="'.$plotWidth.'" height="'.round(max(0, $criticalY - $top), 2).'" fill="#fee2e2" opacity="0.45"/>';
            $svg[] = '<rect x="'.$left.'" y="'.round($criticalY, 2).'" width="'.$plotWidth.'" height="'.round(max(0, $normalY - $criticalY), 2).'" fill="#fef3c7" opacity="0.52"/>';
            $svg[] = '<rect x="'.$left.'" y="'.round($normalY, 2).'" width="'.$plotWidth.'" height="'.round(max(0, ($top + $plotHeight) - $normalY), 2).'" fill="#dcfce7" opacity="0.38"/>';
        }

        for ($i = 0; $i <= 5; $i++) {
            $value = $minValue + (($maxValue - $minValue) / 5) * $i;
            $y = $top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight;

            $svg[] = '<line x1="'.$left.'" y1="'.round($y, 2).'" x2="'.($width - $right).'" y2="'.round($y, 2).'" stroke="#cbd5e1" stroke-width="1" opacity="0.75"/>';
            $svg[] = '<text x="'.($left - 12).'" y="'.round($y + 4, 2).'" text-anchor="end" font-family="Arial, sans-serif" font-size="11" fill="#64748b">'.$this->svgText($this->formatChartNumber($value)).'</text>';
        }

        foreach ($thresholds as $threshold) {
            $value = (float) ($threshold['value'] ?? 0);
            $y = $top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight;
            $color = (string) ($threshold['color'] ?? '#64748b');

            $svg[] = '<line x1="'.$left.'" y1="'.round($y, 2).'" x2="'.($width - $right).'" y2="'.round($y, 2).'" stroke="'.$this->svgText($color).'" stroke-width="2" stroke-dasharray="7 7"/>';
            $svg[] = '<text x="'.($width - $right - 12).'" y="'.round($y - 8, 2).'" text-anchor="end" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="'.$this->svgText($color).'">'.$this->svgText((string) ($threshold['label'] ?? $value)).'</text>';
        }

        if ($chartType === 'bar') {
            $svg = array_merge($svg, $this->barSvg($series[0] ?? ['points' => []], $left, $top, $plotWidth, $plotHeight, $minValue, $maxValue, (string) ($dataset['type'] ?? '')));
        } else {
            foreach ($series as $index => $item) {
                $svg = array_merge($svg, $this->lineSvg($item, $index, $left, $top, $plotWidth, $plotHeight, $minValue, $maxValue, (string) ($dataset['type'] ?? '')));
            }
        }

        $labelStep = max(1, (int) ceil($count / 10));

        foreach ($labels as $index => $label) {
            if ($index % $labelStep !== 0 && $index !== $count - 1) {
                continue;
            }

            $x = $count === 1
                ? $left + ($plotWidth / 2)
                : $left + ($plotWidth / ($count - 1)) * $index;

            $svg[] = '<text x="'.round($x, 2).'" y="'.($height - 62).'" text-anchor="middle" font-family="Arial, sans-serif" font-size="11" fill="#475569">'.$this->svgText(Str::limit((string) $label, 12, '')).'</text>';
        }

        $legendX = $left;
        $legendY = $height - 32;

        foreach ($series as $index => $item) {
            $color = $this->chartColors[$index % count($this->chartColors)];
            $name = (string) ($item['name'] ?? 'Serie '.($index + 1));

            $svg[] = '<rect x="'.$legendX.'" y="'.($legendY - 10).'" width="12" height="12" rx="3" fill="'.$color.'"/>';
            $svg[] = '<text x="'.($legendX + 18).'" y="'.$legendY.'" font-family="Arial, sans-serif" font-size="12" fill="#334155">'.$this->svgText($name).'</text>';
            $legendX += 18 + (strlen($name) * 7) + 24;
        }

        $svg[] = '<text x="'.($left + $plotWidth / 2).'" y="'.($height - 78).'" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="#64748b">'.$this->svgText((string) ($dataset['x_label'] ?? '')).'</text>';
        $svg[] = '<text x="22" y="'.($top + $plotHeight / 2).'" text-anchor="middle" transform="rotate(-90 22 '.($top + $plotHeight / 2).')" font-family="Arial, sans-serif" font-size="12" fill="#64748b">'.$this->svgText((string) ($dataset['y_label'] ?? '')).'</text>';
        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function buildPng(array $dataset, string $chartType): string
    {
        $series = array_values(array_filter((array) ($dataset['series'] ?? []), fn ($item): bool => is_array($item)));
        $chartType = $chartType === 'bar' && count($series) === 1 ? 'bar' : 'line';
        $width = 1100;
        $height = 680;
        $left = 88;
        $right = 44;
        $top = 252;
        $bottom = 104;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $values = $this->seriesValues($series);
        $thresholds = array_values(array_filter((array) ($dataset['thresholds'] ?? []), fn ($item): bool => is_array($item)));
        $summaryCards = array_values(array_filter((array) ($dataset['summary_cards'] ?? []), fn ($item): bool => is_array($item)));

        foreach ($thresholds as $threshold) {
            $values[] = (float) ($threshold['value'] ?? 0);
        }

        $maxValue = max($values !== [] ? $values : [1]);
        $minValue = min(0, min($values !== [] ? $values : [0]));

        if ($maxValue <= $minValue) {
            $maxValue = $minValue + 1;
        }

        $maxValue = $this->niceUpperBound($maxValue);
        $labels = $this->labelsFromSeries($series);
        $count = max(1, count($labels));
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = $this->pngColor($image, '#ffffff');
        $page = $this->pngColor($image, '#f8fafc');
        $border = $this->pngColor($image, '#e2e8f0');
        $navy = $this->pngColor($image, '#0f172a');
        $muted = $this->pngColor($image, '#64748b');
        $slate = $this->pngColor($image, '#334155');
        $grid = $this->pngColor($image, '#cbd5e1');
        $gold = $this->pngColor($image, '#fbbf24');

        imagefilledrectangle($image, 0, 0, $width, $height, $page);
        imagefilledrectangle($image, 18, 18, $width - 18, $height - 18, $white);
        imagerectangle($image, 18, 18, $width - 18, $height - 18, $border);
        imagefilledrectangle($image, 18, 18, $width - 18, 122, $navy);

        imagestring($image, 5, 46, 42, $this->pngSafeText((string) $dataset['title'], 86), $white);
        imagestring($image, 3, 46, 78, $this->pngSafeText((string) $dataset['subtitle'], 120), $this->pngColor($image, '#cbd5e1'));
        imagestring($image, 3, $width - 170, 42, 'LEGADO AB FENIX', $gold);

        foreach (array_slice($summaryCards, 0, 4) as $index => $card) {
            $cardWidth = 238;
            $cardHeight = 76;
            $x = 46 + (($cardWidth + 18) * $index);
            $y = 142;
            $accent = $this->pngColor($image, $this->toneHex((string) ($card['tone'] ?? 'neutral')));

            imagefilledrectangle($image, $x, $y, $x + $cardWidth, $y + $cardHeight, $white);
            imagerectangle($image, $x, $y, $x + $cardWidth, $y + $cardHeight, $border);
            imagefilledrectangle($image, $x, $y, $x + 6, $y + $cardHeight, $accent);
            imagestring($image, 3, $x + 22, $y + 18, $this->pngSafeText((string) ($card['label'] ?? ''), 30), $muted);
            imagestring($image, 5, $x + 22, $y + 46, $this->pngSafeText((string) ($card['value'] ?? '0'), 24), $navy);
        }

        imagefilledrectangle($image, $left, $top, $left + $plotWidth, $top + $plotHeight, $white);
        imagerectangle($image, $left, $top, $left + $plotWidth, $top + $plotHeight, $border);

        if (($dataset['type'] ?? null) === 'elongaciones' && count($thresholds) >= 2) {
            $warning = (float) ($thresholds[0]['value'] ?? 0);
            $critical = (float) ($thresholds[1]['value'] ?? $warning);
            $warningY = (int) round($top + $plotHeight - (($warning - $minValue) / ($maxValue - $minValue)) * $plotHeight);
            $criticalY = (int) round($top + $plotHeight - (($critical - $minValue) / ($maxValue - $minValue)) * $plotHeight);

            imagefilledrectangle($image, $left + 1, $top + 1, $left + $plotWidth - 1, max($top + 1, $criticalY), $this->pngColor($image, '#fee2e2'));
            imagefilledrectangle($image, $left + 1, max($top + 1, $criticalY), $left + $plotWidth - 1, max($criticalY, $warningY), $this->pngColor($image, '#fef3c7'));
            imagefilledrectangle($image, $left + 1, max($top + 1, $warningY), $left + $plotWidth - 1, $top + $plotHeight - 1, $this->pngColor($image, '#dcfce7'));
        }

        for ($i = 0; $i <= 5; $i++) {
            $value = $minValue + (($maxValue - $minValue) / 5) * $i;
            $y = (int) round($top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight);

            imageline($image, $left, $y, $width - $right, $y, $grid);
            imagestring($image, 2, $left - 52, $y - 7, $this->formatChartNumber($value), $muted);
        }

        foreach ($thresholds as $threshold) {
            $value = (float) ($threshold['value'] ?? 0);
            $y = (int) round($top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight);
            $color = $this->pngColor($image, (string) ($threshold['color'] ?? '#64748b'));

            imagesetthickness($image, 2);
            $this->pngDashedLine($image, $left, $y, $width - $right, $y, $color);
            imagesetthickness($image, 1);
            imagestring($image, 3, $width - $right - 110, $y - 20, $this->pngSafeText((string) ($threshold['label'] ?? $value), 18), $color);
        }

        if ($chartType === 'bar') {
            $this->drawPngBars($image, $series[0] ?? ['points' => []], $left, $top, $plotWidth, $plotHeight, $minValue, $maxValue, (string) ($dataset['type'] ?? ''));
        } else {
            foreach ($series as $index => $item) {
                $this->drawPngLine($image, $item, $index, $left, $top, $plotWidth, $plotHeight, $minValue, $maxValue, (string) ($dataset['type'] ?? ''));
            }
        }

        $labelStep = max(1, (int) ceil($count / 10));

        foreach ($labels as $index => $label) {
            if ($index % $labelStep !== 0 && $index !== $count - 1) {
                continue;
            }

            $x = $count === 1
                ? $left + ($plotWidth / 2)
                : $left + ($plotWidth / ($count - 1)) * $index;

            imagestring($image, 2, (int) round($x - 22), $height - 66, $this->pngSafeText((string) $label, 12), $slate);
        }

        $legendX = $left;
        $legendY = $height - 36;

        foreach ($series as $index => $item) {
            $color = $this->pngColor($image, $this->chartColors[$index % count($this->chartColors)]);
            $name = $this->pngSafeText((string) ($item['name'] ?? 'Serie '.($index + 1)), 28);

            imagefilledrectangle($image, $legendX, $legendY - 4, $legendX + 12, $legendY + 8, $color);
            imagestring($image, 3, $legendX + 18, $legendY - 6, $name, $slate);
            $legendX += 18 + (strlen($name) * 8) + 22;
        }

        imagestring($image, 3, (int) ($left + ($plotWidth / 2) - 28), $height - 86, $this->pngSafeText((string) ($dataset['x_label'] ?? ''), 30), $muted);
        imagestringup($image, 3, 20, (int) ($top + ($plotHeight / 2) + 42), $this->pngSafeText((string) ($dataset['y_label'] ?? ''), 28), $muted);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    /**
     * @param  resource|\GdImage  $image
     */
    private function drawPngLine($image, array $series, int $index, int $left, int $top, int $plotWidth, int $plotHeight, float $minValue, float $maxValue, string $datasetType): void
    {
        $points = array_values(array_filter((array) ($series['points'] ?? []), fn ($item): bool => is_array($item)));
        $count = max(1, count($points));
        $color = $this->pngColor($image, $this->chartColors[$index % count($this->chartColors)]);
        $previous = null;

        imagesetthickness($image, 3);

        foreach ($points as $pointIndex => $point) {
            $value = (float) ($point['value'] ?? 0);
            $x = (int) round($count === 1 ? $left + ($plotWidth / 2) : $left + ($plotWidth / ($count - 1)) * $pointIndex);
            $y = (int) round($top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight);

            if ($previous !== null) {
                imageline($image, $previous[0], $previous[1], $x, $y, $color);
            }

            $previous = [$x, $y];
        }

        imagesetthickness($image, 1);

        foreach ($points as $pointIndex => $point) {
            $value = (float) ($point['value'] ?? 0);
            $x = (int) round($count === 1 ? $left + ($plotWidth / 2) : $left + ($plotWidth / ($count - 1)) * $pointIndex);
            $y = (int) round($top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight);
            $pointColor = $this->pngColor($image, $this->valueHex((float) $value, $datasetType));

            imagefilledellipse($image, $x, $y, 10, 10, $pointColor);
            imageellipse($image, $x, $y, 12, 12, $this->pngColor($image, '#ffffff'));
        }
    }

    /**
     * @param  resource|\GdImage  $image
     */
    private function drawPngBars($image, array $series, int $left, int $top, int $plotWidth, int $plotHeight, float $minValue, float $maxValue, string $datasetType): void
    {
        $points = array_values(array_filter((array) ($series['points'] ?? []), fn ($item): bool => is_array($item)));
        $count = max(1, count($points));
        $slotWidth = $plotWidth / $count;
        $barWidth = max(18, min(58, $slotWidth * 0.62));

        foreach ($points as $index => $point) {
            $value = (float) ($point['value'] ?? 0);
            $x = (int) round($left + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2));
            $y = (int) round($top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight);
            $barHeight = max(2, $top + $plotHeight - $y);
            $color = $this->pngColor($image, $this->valueHex($value, $datasetType));

            imagefilledrectangle($image, $x, $y, (int) round($x + $barWidth), $y + $barHeight, $color);
            imagestring($image, 2, $x, max($top + 4, $y - 16), $this->formatChartNumber($value), $this->pngColor($image, '#0f172a'));
        }
    }

    /**
     * @param  resource|\GdImage  $image
     */
    private function pngDashedLine($image, int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        $dash = 8;
        $gap = 7;

        for ($x = $x1; $x < $x2; $x += ($dash + $gap)) {
            imageline($image, $x, $y1, min($x + $dash, $x2), $y2, $color);
        }
    }

    /**
     * @param  resource|\GdImage  $image
     */
    private function pngColor($image, string $hex): int
    {
        [$red, $green, $blue] = $this->hexToRgb($hex);

        return imagecolorallocate($image, $red, $green, $blue);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '64748b';
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function pngSafeText(string $value, int $limit): string
    {
        return Str::limit(Str::ascii($value), $limit, '');
    }

    private function toneHex(string $tone): string
    {
        return match ($tone) {
            'critical' => '#dc2626',
            'warning' => '#d97706',
            'normal' => '#16a34a',
            default => '#2563eb',
        };
    }

    private function valueHex(float $value, string $datasetType): string
    {
        if ($datasetType !== 'elongaciones') {
            return '#2563eb';
        }

        return match ($this->elongacionTone($value)) {
            'critical' => '#dc2626',
            'warning' => '#d97706',
            default => '#16a34a',
        };
    }

    /**
     * @param  array<string, mixed>  $series
     * @return array<int, string>
     */
    private function lineSvg(array $series, int $index, int $left, int $top, int $plotWidth, int $plotHeight, float $minValue, float $maxValue, string $datasetType): array
    {
        $points = array_values(array_filter((array) ($series['points'] ?? []), fn ($item): bool => is_array($item)));
        $count = max(1, count($points));
        $color = $this->chartColors[$index % count($this->chartColors)];
        $coordinates = [];
        $svg = [];

        foreach ($points as $pointIndex => $point) {
            $value = (float) ($point['value'] ?? 0);
            $x = $count === 1 ? $left + ($plotWidth / 2) : $left + ($plotWidth / ($count - 1)) * $pointIndex;
            $y = $top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight;
            $coordinates[] = round($x, 2).','.round($y, 2);
        }

        if ($coordinates !== []) {
            $svg[] = '<polyline points="'.implode(' ', $coordinates).'" fill="none" stroke="'.$color.'" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>';
        }

        foreach ($points as $pointIndex => $point) {
            $value = (float) ($point['value'] ?? 0);
            $x = $count === 1 ? $left + ($plotWidth / 2) : $left + ($plotWidth / ($count - 1)) * $pointIndex;
            $y = $top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight;

            $pointColor = $this->valueHex($value, $datasetType);
            $svg[] = '<circle cx="'.round($x, 2).'" cy="'.round($y, 2).'" r="5" fill="'.$pointColor.'" stroke="#ffffff" stroke-width="2"/>';
        }

        return $svg;
    }

    /**
     * @param  array<string, mixed>  $series
     * @return array<int, string>
     */
    private function barSvg(array $series, int $left, int $top, int $plotWidth, int $plotHeight, float $minValue, float $maxValue, string $datasetType): array
    {
        $points = array_values(array_filter((array) ($series['points'] ?? []), fn ($item): bool => is_array($item)));
        $count = max(1, count($points));
        $slotWidth = $plotWidth / $count;
        $barWidth = max(18, min(58, $slotWidth * 0.62));
        $svg = [];

        foreach ($points as $index => $point) {
            $value = (float) ($point['value'] ?? 0);
            $x = $left + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
            $y = $top + $plotHeight - (($value - $minValue) / ($maxValue - $minValue)) * $plotHeight;
            $height = max(2, $top + $plotHeight - $y);

            $svg[] = '<rect x="'.round($x, 2).'" y="'.round($y, 2).'" width="'.round($barWidth, 2).'" height="'.round($height, 2).'" rx="6" fill="'.$this->valueHex($value, $datasetType).'"/>';
            $svg[] = '<text x="'.round($x + $barWidth / 2, 2).'" y="'.round($y - 7, 2).'" text-anchor="middle" font-family="Arial, sans-serif" font-size="11" fill="#0f172a">'.$this->svgText($this->formatChartNumber($value)).'</text>';
        }

        return $svg;
    }

    /**
     * @param  array<int, array<string, mixed>>  $series
     * @return array<int, float>
     */
    private function seriesValues(array $series): array
    {
        $values = [];

        foreach ($series as $item) {
            foreach ((array) ($item['points'] ?? []) as $point) {
                if (is_array($point) && is_numeric($point['value'] ?? null)) {
                    $values[] = (float) $point['value'];
                }
            }
        }

        return $values;
    }

    /**
     * @param  array<int, array<string, mixed>>  $series
     * @return array<int, string>
     */
    private function labelsFromSeries(array $series): array
    {
        $first = $series[0] ?? [];

        return collect((array) ($first['points'] ?? []))
            ->map(fn ($point): string => is_array($point) ? (string) ($point['label'] ?? '') : '')
            ->values()
            ->all();
    }

    private function niceUpperBound(float $value): float
    {
        if ($value <= 2) {
            return ceil($value * 10) / 10;
        }

        if ($value <= 10) {
            return ceil($value);
        }

        if ($value <= 100) {
            return ceil($value / 10) * 10;
        }

        return ceil($value / 1000) * 1000;
    }

    private function formatChartNumber(float $value): string
    {
        if (abs($value) >= 1000) {
            return number_format($value, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function svgText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function elongacionThresholds(): array
    {
        return [
            [
                'value' => (float) config('maintenance_ai.rules.elongacion_warning_threshold', Elongacion::LIMITE_COMPRAR),
                'label' => 'Comprar',
                'color' => '#d97706',
            ],
            [
                'value' => (float) config('maintenance_ai.rules.elongacion_critical_threshold', Elongacion::LIMITE_CAMBIO),
                'label' => 'Cambio',
                'color' => '#dc2626',
            ],
        ];
    }

    private function formatPeriodLabel(string $period): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period) === 1) {
            try {
                return CarbonImmutable::parse($period)->format('d/m/Y');
            } catch (Throwable) {
                return $period;
            }
        }

        if (preg_match('/^(\d{4})-W(\d{2})$/', $period, $matches) === 1) {
            return 'Semana '.$matches[2].' '.$matches[1];
        }

        if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            try {
                return CarbonImmutable::parse($period.'-01')->format('M Y');
            } catch (Throwable) {
                return $period;
            }
        }

        return $period;
    }

    private function datePeriodKey(mixed $date, string $aggregation): string
    {
        if (! $date) {
            return 'Sin fecha';
        }

        try {
            $carbon = $date instanceof \DateTimeInterface
                ? CarbonImmutable::instance($date)
                : CarbonImmutable::parse((string) $date, config('app.timezone', 'UTC'));
        } catch (Throwable) {
            return 'Sin fecha';
        }

        return match ($aggregation) {
            'daily' => $carbon->format('Y-m-d'),
            'weekly' => $carbon->format('o-\WW'),
            default => $carbon->format('Y-m'),
        };
    }

    /**
     * @param  array<int, string>  $lineas
     */
    private function scopeSubtitle(array $lineas, array $dateRange): string
    {
        $scope = $lineas === [] ? 'Todas las lineas' : implode(', ', $lineas);

        return $scope.' | '.$dateRange['label'];
    }

    /**
     * @param  array<int, string>  $lineas
     * @return array<int, int>
     */
    private function lineIdsFor(array $lineas): array
    {
        if ($lineas === []) {
            return [];
        }

        return Linea::query()
            ->whereIn('nombre', $lineas)
            ->pluck('id')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    private function normalizeDataset(string $dataset, string $question): string
    {
        $normalizedQuestion = $this->normalize($question);
        $dataset = $this->normalize($dataset);

        if (in_array($dataset, ['elongaciones', 'analisis_lavadora', 'costos_lavadora', 'plan_accion', 'unsupported'], true)) {
            return $dataset;
        }

        if (str_contains($normalizedQuestion, 'elongacion') || str_contains($normalizedQuestion, 'longacion')) {
            return 'elongaciones';
        }

        if (str_contains($normalizedQuestion, 'costo') || str_contains($normalizedQuestion, 'gasto')) {
            return 'costos_lavadora';
        }

        if (str_contains($normalizedQuestion, 'plan') || str_contains($normalizedQuestion, 'accion')) {
            return 'plan_accion';
        }

        if (
            str_contains($normalizedQuestion, 'analisis')
            || str_contains($normalizedQuestion, 'revision')
            || str_contains($normalizedQuestion, 'dano')
            || str_contains($normalizedQuestion, 'desgaste')
            || str_contains($normalizedQuestion, 'componente')
        ) {
            return 'analisis_lavadora';
        }

        return 'unsupported';
    }

    /**
     * @param  array<int, mixed>  $outputs
     * @return array<int, string>
     */
    private function normalizeOutputs(array $outputs, string $question): array
    {
        $normalized = $this->normalize($question);
        $selected = collect($outputs)
            ->map(fn ($value): string => $this->normalize((string) $value))
            ->filter(fn (string $value): bool => in_array($value, ['image', 'excel'], true))
            ->values()
            ->all();

        if (
            str_contains($normalized, 'excel')
            || str_contains($normalized, 'ecxel')
            || str_contains($normalized, 'exel')
            || str_contains($normalized, 'excell')
            || str_contains($normalized, 'xlsx')
            || str_contains($normalized, 'xlxs')
        ) {
            $selected[] = 'excel';
        }

        if (str_contains($normalized, 'graf') || str_contains($normalized, 'imagen') || str_contains($normalized, 'png') || str_contains($normalized, 'svg')) {
            $selected[] = 'image';
        }

        if ($selected === [] && (str_contains($normalized, 'export') || str_contains($normalized, 'descarg') || str_contains($normalized, 'archivo') || str_contains($normalized, 'tabla'))) {
            $selected[] = 'excel';
        }

        return array_values(array_unique($selected !== [] ? $selected : ['image']));
    }

    /**
     * @param  array<int, mixed>  $lineas
     * @return array<int, string>
     */
    private function normalizeLineas(array $lineas, string $question): array
    {
        $values = [];

        foreach ($lineas as $linea) {
            $normalized = $this->normalizeLineReference((string) $linea);

            if ($normalized !== null) {
                $values[] = $normalized;
            }
        }

        if (preg_match_all('/\b(?:lavadora|linea|l)\s*[-#]?\s*0*(\d{1,2})\b/u', $this->normalize($question), $matches)) {
            foreach ($matches[1] as $lineNumber) {
                $normalized = $this->normalizeLineReference((string) $lineNumber);

                if ($normalized !== null) {
                    $values[] = $normalized;
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function normalizeLineReference(string $linea): ?string
    {
        $normalized = Str::upper(Str::ascii(trim($linea)));

        if (preg_match('/^(?:L-?|LINEA\s*)?0?(\d{1,2})$/', $normalized, $matches) !== 1) {
            return null;
        }

        $lineName = 'L-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);

        return in_array($lineName, $this->validLineNames(), true)
            ? $lineName
            : null;
    }

    /**
     * @param  array<int, mixed>  $intentLineas
     * @return array<int, string>
     */
    private function invalidLineReferences(array $intentLineas, string $question): array
    {
        $candidates = [];

        foreach ($intentLineas as $linea) {
            $candidate = $this->candidateLineReference((string) $linea);

            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        if (preg_match_all('/\b(?:lavadora|linea|l)\s*[-#]?\s*0*(\d{1,2})\b/u', $this->normalize($question), $matches)) {
            foreach ($matches[1] as $lineNumber) {
                $candidate = $this->candidateLineReference((string) $lineNumber);

                if ($candidate !== null) {
                    $candidates[] = $candidate;
                }
            }
        }

        return collect($candidates)
            ->unique()
            ->reject(fn (string $linea): bool => in_array($linea, $this->validLineNames(), true))
            ->values()
            ->all();
    }

    private function candidateLineReference(string $linea): ?string
    {
        $normalized = Str::upper(Str::ascii(trim($linea)));

        if (preg_match('/^(?:L-?|LINEA\s*)?0?(\d{1,2})$/', $normalized, $matches) !== 1) {
            return null;
        }

        return 'L-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, string>
     */
    private function validLineNames(): array
    {
        return ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}
     */
    private function normalizeDateRange(array $dateRange, string $question = ''): array
    {
        $timezone = config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);
        $preset = $this->detectDatePresetFromQuestion($question)
            ?: $this->normalize((string) ($dateRange['preset'] ?? ''));
        $from = $this->parseDate((string) ($dateRange['from'] ?? ''), true);
        $to = $this->parseDate((string) ($dateRange['to'] ?? ''), false);

        if ($from === null || $to === null) {
            [$from, $to] = match ($preset) {
                'last_30_days' => [$now->subDays(30)->startOfDay(), $now->endOfDay()],
                'last_90_days' => [$now->subDays(90)->startOfDay(), $now->endOfDay()],
                'last_6_months' => [$now->subMonthsNoOverflow(6)->startOfDay(), $now->endOfDay()],
                'current_month' => [$now->startOfMonth(), $now->endOfDay()],
                'current_year' => [$now->startOfYear(), $now->endOfDay()],
                'all' => [null, null],
                default => [$now->subMonthsNoOverflow(12)->startOfDay(), $now->endOfDay()],
            };
        }

        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [
            'from' => $from,
            'to' => $to,
            'label' => $this->dateRangeLabel($from, $to),
            'preset' => $preset ?: 'last_12_months',
        ];
    }

    private function detectDatePresetFromQuestion(string $question): ?string
    {
        $normalized = $this->normalize($question);

        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'todo el historial') || str_contains($normalized, 'historico') || str_contains($normalized, 'todos los datos')) {
            return 'all';
        }

        if (preg_match('/ultim(?:o|os|a|as)?\s+30\s+dias?/', $normalized) === 1 || str_contains($normalized, 'ultimo mes')) {
            return 'last_30_days';
        }

        if (preg_match('/ultim(?:o|os|a|as)?\s+90\s+dias?/', $normalized) === 1) {
            return 'last_90_days';
        }

        if (preg_match('/ultim(?:o|os|a|as)?\s+6\s+mes(?:es)?/', $normalized) === 1 || str_contains($normalized, 'ultimo semestre')) {
            return 'last_6_months';
        }

        if (str_contains($normalized, 'este mes') || str_contains($normalized, 'mes actual')) {
            return 'current_month';
        }

        if (str_contains($normalized, 'este ano') || str_contains($normalized, 'este anio') || str_contains($normalized, 'ano actual') || str_contains($normalized, 'anio actual')) {
            return 'current_year';
        }

        return null;
    }

    private function parseDate(string $value, bool $startOfDay): ?CarbonImmutable
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value, config('app.timezone', 'UTC'));

            return $startOfDay ? $date->startOfDay() : $date->endOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function dateRangeLabel(?CarbonImmutable $from, ?CarbonImmutable $to): string
    {
        if ($from === null && $to === null) {
            return 'Todo el historial disponible';
        }

        if ($from && $to) {
            return $from->format('d/m/Y').' - '.$to->format('d/m/Y');
        }

        if ($from) {
            return 'Desde '.$from->format('d/m/Y');
        }

        return 'Hasta '.$to?->format('d/m/Y');
    }

    private function normalizeChartType(string $chartType, string $question): string
    {
        $normalized = $this->normalize($chartType.' '.$question);

        if (str_contains($normalized, 'bar') || str_contains($normalized, 'barra') || str_contains($normalized, 'ranking') || str_contains($normalized, 'por linea')) {
            return 'bar';
        }

        return 'line';
    }

    private function normalizeAggregation(string $aggregation, string $question, string $chartType): string
    {
        $normalized = $this->normalize($aggregation.' '.$question);

        if (
            str_contains($normalized, 'ranking')
            || str_contains($normalized, 'comparativo')
            || str_contains($normalized, 'comparacion')
            || str_contains($normalized, 'por linea')
            || str_contains($normalized, 'por lavadora')
        ) {
            return 'by_line';
        }

        if (str_contains($normalized, 'semanal') || str_contains($normalized, 'por semana')) {
            return 'weekly';
        }

        if (
            str_contains($normalized, 'diario')
            || str_contains($normalized, 'por dia')
            || preg_match('/ultim(?:o|os|a|as)?\s+30\s+dias?/', $normalized) === 1
        ) {
            return 'daily';
        }

        if (
            str_contains($normalized, 'ultimo registro')
            || str_contains($normalized, 'ultima lectura')
            || str_contains($normalized, 'lectura actual')
            || str_contains($normalized, 'elongacion actual')
        ) {
            return 'latest';
        }

        foreach (['daily', 'weekly', 'monthly', 'by_line', 'latest'] as $allowed) {
            if (str_contains($normalized, $allowed)) {
                return $allowed;
            }
        }

        return $chartType === 'bar' ? 'by_line' : 'monthly';
    }

    private function wantsCriticalOnly(string $question): bool
    {
        $normalized = $this->normalize($question);

        return str_contains($normalized, 'solo crit')
            || str_contains($normalized, 'criticos')
            || str_contains($normalized, 'criticas')
            || str_contains($normalized, 'critico')
            || str_contains($normalized, 'critica');
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }

    /**
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, string>
     */
    private function serializeDateRange(array $dateRange): array
    {
        return [
            'from' => $dateRange['from']?->toDateString() ?? '',
            'to' => $dateRange['to']?->toDateString() ?? '',
            'label' => $dateRange['label'],
            'preset' => $dateRange['preset'],
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  array<int, array<string, mixed>>  $artifacts
     */
    private function successContent(array $dataset, array $artifacts): string
    {
        $labels = collect($artifacts)
            ->pluck('label')
            ->filter()
            ->implode(' y ');
        $hasExcel = collect($artifacts)->contains(fn (array $artifact): bool => ($artifact['kind'] ?? null) === 'excel');
        $alertCount = count((array) ($dataset['alert_rows'] ?? []));

        $message = 'Listo. Genere '.($labels !== '' ? $labels : 'el archivo').' para '
            .Str::lower((string) $dataset['title'])
            .' usando '
            .count((array) ($dataset['rows'] ?? []))
            .' filas de datos reales.';

        if ($alertCount > 0) {
            $message .= ' Detecte '.$alertCount.' registros en alerta o criticos.';
        }

        if ($hasExcel) {
            $message .= ' El Excel incluye Dashboard, Resumen, Tendencia, Alertas, Datos y Filtros.';
        }

        return $message.' Puedes abrir la imagen o descargar el Excel desde los adjuntos del mensaje.';
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  array<int, string>  $outputs
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @return array<string, mixed>
     */
    private function withRuntimeFilters(
        array $dataset,
        string $question,
        string $datasetKey,
        array $outputs,
        array $lineas,
        array $dateRange,
        string $chartType
    ): array {
        $filterRows = [
            ['Prompt original', $this->sanitizer->sanitizeText($question, 500)],
            ['Dataset interpretado', $this->readableDatasetName($datasetKey)],
            ['Salidas solicitadas', implode(', ', $outputs)],
            ['Tipo de grafica', $chartType === 'bar' ? 'Barras' : 'Linea'],
            ['Lineas interpretadas', $lineas === [] ? 'Todas las lineas' : implode(', ', $lineas)],
            ['Periodo interpretado', $dateRange['label']],
            ['Fecha de generacion', now()->format('d/m/Y H:i:s')],
            ['Version del reporte', (string) ($dataset['report_version'] ?? 'v1')],
        ];

        if (($dataset['type'] ?? null) === 'elongaciones') {
            $filterRows[] = ['Alcance de ciclo', (string) ($dataset['cycle_scope_label'] ?? 'Ciclo actual por lavadora')];
        }

        $dataset['filter_rows'] = $filterRows;
        $dataset['workbook_sheets'] = $this->analyticsWorkbookSheets($dataset);

        return $dataset;
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>, tone?: string}>
     */
    private function analyticsWorkbookSheets(array $dataset): array
    {
        return [
            [
                'title' => 'Resumen',
                'headings' => ['Metrica', 'Valor'],
                'rows' => (array) ($dataset['summary_rows'] ?? []),
                'tone' => 'default',
            ],
            [
                'title' => 'Tendencia',
                'headings' => (array) ($dataset['headings'] ?? []),
                'rows' => (array) ($dataset['rows'] ?? []),
                'tone' => 'success',
            ],
            [
                'title' => 'Alertas',
                'headings' => (array) ($dataset['alert_headings'] ?? []),
                'rows' => (array) ($dataset['alert_rows'] ?? []),
                'tone' => ! empty($dataset['alert_rows']) ? 'danger' : 'muted',
            ],
            [
                'title' => 'Datos',
                'headings' => (array) ($dataset['raw_headings'] ?? $dataset['headings'] ?? []),
                'rows' => (array) ($dataset['raw_rows'] ?? $dataset['rows'] ?? []),
                'tone' => 'muted',
            ],
            [
                'title' => 'Filtros',
                'headings' => ['Filtro', 'Valor'],
                'rows' => (array) ($dataset['filter_rows'] ?? []),
                'tone' => 'warning',
            ],
        ];
    }

    /**
     * @param  array{data: array<string, mixed>, meta: array<string, mixed>}  $intentResult
     * @return array{content: string, metadata: array<string, mixed>}
     */
    private function unsupportedReply(string $question, array $intentResult): array
    {
        return [
            'content' => 'Puedo generar graficas y Excel desde datasets operativos configurados, pero esa solicitud no coincide con un dataset seguro disponible. Por ahora tengo: elongaciones, analisis de lavadora, costos de lavadora y planes de accion.',
            'metadata' => [
                'provider' => Arr::get($intentResult, 'meta.provider'),
                'model' => Arr::get($intentResult, 'meta.model'),
                'confidence' => (float) Arr::get($intentResult, 'data.confidence', 0.7),
                'artifact_request' => true,
                'unsupported_artifact_dataset' => true,
                'question_excerpt' => $this->sanitizer->sanitizeText($question, 180),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $invalidLineas
     * @param  array{data: array<string, mixed>, meta: array<string, mixed>}  $intentResult
     * @return array{content: string, metadata: array<string, mixed>}
     */
    private function invalidLineReply(array $invalidLineas, array $intentResult): array
    {
        return [
            'content' => 'No genere el reporte porque la solicitud menciona lineas no configuradas: '
                .implode(', ', $invalidLineas)
                .'. Lineas validas: '
                .implode(', ', $this->validLineNames())
                .'.',
            'metadata' => [
                'provider' => Arr::get($intentResult, 'meta.provider'),
                'model' => Arr::get($intentResult, 'meta.model'),
                'confidence' => (float) Arr::get($intentResult, 'data.confidence', 0.7),
                'artifact_request' => true,
                'invalid_artifact_filter' => true,
                'invalid_lineas' => $invalidLineas,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $lineas
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null, label: string, preset: string}  $dateRange
     * @param  array{data: array<string, mixed>, meta: array<string, mixed>}  $intentResult
     * @return array{content: string, metadata: array<string, mixed>}
     */
    private function emptyReply(string $datasetKey, array $lineas, array $dateRange, array $intentResult): array
    {
        $scope = $lineas === [] ? 'todas las lineas' : implode(', ', $lineas);

        return [
            'content' => 'No encontre datos para generar el archivo solicitado en '
                .$this->readableDatasetName($datasetKey)
                .' con el filtro '
                .$scope
                .' y periodo '
                .$dateRange['label']
                .'.',
            'metadata' => [
                'provider' => Arr::get($intentResult, 'meta.provider'),
                'model' => Arr::get($intentResult, 'meta.model'),
                'confidence' => (float) Arr::get($intentResult, 'data.confidence', 0.7),
                'artifact_request' => true,
                'empty_artifact_dataset' => true,
            ],
        ];
    }

    private function readableDatasetName(string $datasetKey): string
    {
        return match ($datasetKey) {
            'elongaciones' => 'elongaciones',
            'analisis_lavadora' => 'analisis de lavadora',
            'costos_lavadora' => 'costos de lavadora',
            'plan_accion' => 'planes de accion',
            default => 'datos operativos',
        };
    }
}
