<?php

namespace App\Http\Controllers;

use App\Models\AiInteractionLog;
use App\Models\PlanAccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiObservabilityController extends Controller
{
    private const METADATA_SAMPLE_LIMIT = 1000;
    private const TIMELINE_SAMPLE_LIMIT = 5000;
    private const LATENCY_SAMPLE_LIMIT = 10000;

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->canViewAiObservability(),
            403,
            'No tienes permiso para acceder a Observabilidad IA.'
        );

        $filters = $this->filtersFromRequest($request);
        $baseQuery = $this->logsQuery($filters);

        $metrics = $this->interactionMetrics($baseQuery);
        $rag = $this->ragInsights($baseQuery);
        $plans = $this->planMetrics($filters);
        $timeline = $this->timeline($baseQuery);

        $recentFailures = (clone $baseQuery)
            ->with('user')
            ->where(function (Builder $query): void {
                $query->where('status', 'failed')
                    ->orWhereNotNull('error_message');
            })
            ->latest()
            ->take(8)
            ->get();

        $recentInteractions = (clone $baseQuery)
            ->with('user')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.ai-observability.index', [
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'actionOptions' => $this->actionOptions(),
            'providerOptions' => $this->providerOptions(),
            'metrics' => $metrics,
            'rag' => $rag,
            'plans' => $plans,
            'timeline' => $timeline,
            'healthSignals' => $this->healthSignals($metrics, $rag, $plans),
            'recentFailures' => $recentFailures,
            'recentInteractions' => $recentInteractions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:success,fallback,failed'],
            'provider' => ['nullable', 'string', 'max:50'],
            'action_type' => ['nullable', 'string', 'max:80'],
        ]);

        $to = filled($validated['to'] ?? null)
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        $from = filled($validated['from'] ?? null)
            ? Carbon::parse($validated['from'])->startOfDay()
            : $to->copy()->subDays(29)->startOfDay();

        return [
            'from' => $from,
            'to' => $to,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'status' => trim((string) ($validated['status'] ?? '')),
            'provider' => trim((string) ($validated['provider'] ?? '')),
            'action_type' => trim((string) ($validated['action_type'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logsQuery(array $filters): Builder
    {
        return AiInteractionLog::query()
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['provider'] !== '', fn (Builder $query) => $query->where('provider', $filters['provider']))
            ->when($filters['action_type'] !== '', fn (Builder $query) => $query->where('action_type', $filters['action_type']));
    }

    /**
     * @return array<string, mixed>
     */
    private function interactionMetrics(Builder $baseQuery): array
    {
        $statusBreakdown = $this->countsBy($baseQuery, 'status', 6);
        $statusTotals = $statusBreakdown
            ->mapWithKeys(fn (array $row): array => [$row['key'] => $row['total']])
            ->all();

        $total = array_sum($statusTotals);
        $success = (int) ($statusTotals['success'] ?? 0);
        $fallback = (int) ($statusTotals['fallback'] ?? 0);
        $failed = (int) ($statusTotals['failed'] ?? 0);

        $averageLatency = (clone $baseQuery)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        $latencies = (clone $baseQuery)
            ->whereNotNull('response_time_ms')
            ->latest()
            ->take(self::LATENCY_SAMPLE_LIMIT)
            ->pluck('response_time_ms')
            ->map(fn ($value): int => (int) $value);

        return [
            'total' => $total,
            'success' => $success,
            'fallback' => $fallback,
            'failed' => $failed,
            'success_rate' => $this->rate($success, $total),
            'fallback_rate' => $this->rate($fallback, $total),
            'failure_rate' => $this->rate($failed, $total),
            'avg_latency_ms' => $averageLatency !== null ? (int) round((float) $averageLatency) : null,
            'p95_latency_ms' => $this->percentile($latencies, 0.95),
            'total_tokens' => (int) (clone $baseQuery)->sum('total_tokens'),
            'prompt_tokens' => (int) (clone $baseQuery)->sum('prompt_tokens'),
            'completion_tokens' => (int) (clone $baseQuery)->sum('completion_tokens'),
            'input_chars' => (int) (clone $baseQuery)->sum('input_chars'),
            'output_chars' => (int) (clone $baseQuery)->sum('output_chars'),
            'provider_breakdown' => $this->countsBy($baseQuery, 'provider', 8),
            'action_breakdown' => $this->countsBy($baseQuery, 'action_type', 8),
            'status_breakdown' => $statusBreakdown,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ragInsights(Builder $baseQuery): array
    {
        $logs = (clone $baseQuery)
            ->latest()
            ->take(self::METADATA_SAMPLE_LIMIT)
            ->get(['action_type', 'metadata', 'created_at']);

        $knowledgeCounts = $logs->map(fn (AiInteractionLog $log): int => $this->metadataInteger($log, [
            'knowledge_count',
            'knowledge_sources_count',
            'sources_count',
        ]));

        $withKnowledge = $knowledgeCounts->filter(fn (int $count): bool => $count > 0)->count();
        $chatLogs = $logs->where('action_type', 'assistant_chat');
        $chatWithKnowledge = $chatLogs
            ->filter(fn (AiInteractionLog $log): bool => $this->metadataInteger($log, ['knowledge_count', 'sources_count']) > 0)
            ->count();

        $questions = $chatLogs
            ->map(fn (AiInteractionLog $log): string => trim((string) data_get($log->metadata ?? [], 'question_excerpt', '')))
            ->filter()
            ->map(fn (string $question): array => [
                'key' => Str::lower($question),
                'question' => Str::limit($question, 140),
            ])
            ->groupBy('key')
            ->map(fn (Collection $items): array => [
                'question' => $items->first()['question'],
                'total' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->take(6);

        $modules = $chatLogs
            ->map(fn (AiInteractionLog $log): string => trim((string) data_get($log->metadata ?? [], 'page_context.module', '')))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn (int $total, string $module): array => [
                'module' => $module,
                'total' => $total,
            ])
            ->values();

        return [
            'sample_size' => $logs->count(),
            'with_knowledge' => $withKnowledge,
            'knowledge_rate' => $this->rate($withKnowledge, $logs->count()),
            'avg_knowledge_sources' => $knowledgeCounts->isNotEmpty() ? round((float) $knowledgeCounts->avg(), 1) : 0.0,
            'chat_total' => $chatLogs->count(),
            'chat_with_knowledge' => $chatWithKnowledge,
            'chat_knowledge_rate' => $this->rate($chatWithKnowledge, $chatLogs->count()),
            'platform_matches_total' => $logs->sum(fn (AiInteractionLog $log): int => $this->metadataInteger($log, ['platform_query_matches'])),
            'top_questions' => $questions,
            'module_breakdown' => $modules,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function planMetrics(array $filters): array
    {
        $query = PlanAccion::query()
            ->aiSuggested()
            ->where(function (Builder $dateQuery) use ($filters): void {
                $dateQuery->whereBetween('generated_at', [$filters['from'], $filters['to']])
                    ->orWhere(function (Builder $fallbackDateQuery) use ($filters): void {
                        $fallbackDateQuery
                            ->whereNull('generated_at')
                            ->whereBetween('created_at', [$filters['from'], $filters['to']]);
                    });
            })
            ->when($filters['provider'] !== '', fn (Builder $planQuery) => $planQuery->where('ai_provider', $filters['provider']));

        $total = (clone $query)->count();
        $feedback = (clone $query)
            ->where(function (Builder $feedbackQuery): void {
                $feedbackQuery
                    ->whereNotNull('execution_result')
                    ->orWhereNotNull('effectiveness')
                    ->orWhereNotNull('actual_cost_total')
                    ->orWhereNotNull('actual_hours');
            })
            ->count();

        $evaluated = (clone $query)
            ->whereIn('effectiveness', [
                PlanAccion::EFFECTIVENESS_EFFECTIVE,
                PlanAccion::EFFECTIVENESS_PARTIALLY_EFFECTIVE,
                PlanAccion::EFFECTIVENESS_INEFFECTIVE,
            ])
            ->count();

        $effective = (clone $query)
            ->where('effectiveness', PlanAccion::EFFECTIVENESS_EFFECTIVE)
            ->count();

        $averageConfidence = (clone $query)
            ->whereNotNull('confidence_level')
            ->avg('confidence_level');

        return [
            'total' => $total,
            'review_queue' => (clone $query)->whereIn('estado', ['pending_review', 'requires_information'])->count(),
            'reviewed' => (clone $query)->whereNotNull('reviewed_at')->count(),
            'completed' => (clone $query)->where('completado', true)->count(),
            'feedback' => $feedback,
            'feedback_rate' => $this->rate($feedback, $total),
            'evaluated' => $evaluated,
            'effective' => $effective,
            'effective_rate' => $this->rate($effective, $evaluated),
            'avg_confidence' => $averageConfidence !== null ? round((float) $averageConfidence * 100, 1) : null,
            'actual_cost_total' => (float) (clone $query)->sum('actual_cost_total'),
            'actual_hours_total' => (float) (clone $query)->sum('actual_hours'),
            'status_breakdown' => $this->countsBy($query, 'estado', 6),
            'effectiveness_breakdown' => $this->effectivenessBreakdown($query),
        ];
    }

    private function timeline(Builder $baseQuery): Collection
    {
        $rows = (clone $baseQuery)
            ->latest()
            ->take(self::TIMELINE_SAMPLE_LIMIT)
            ->get(['created_at', 'status', 'total_tokens']);

        $days = $rows
            ->groupBy(fn (AiInteractionLog $log): string => optional($log->created_at)->format('Y-m-d') ?? 'sin-fecha')
            ->map(fn (Collection $items, string $date): array => [
                'date' => $date,
                'label' => $date === 'sin-fecha' ? 'Sin fecha' : Carbon::parse($date)->format('d/m'),
                'total' => $items->count(),
                'failed' => $items->where('status', 'failed')->count(),
                'fallback' => $items->where('status', 'fallback')->count(),
                'tokens' => (int) $items->sum('total_tokens'),
            ])
            ->sortKeys()
            ->values();

        $days = $days->slice(max(0, $days->count() - 14))->values();
        $maxTotal = max(1, (int) $days->max('total'));

        return $days->map(fn (array $day): array => array_merge($day, [
            'percent' => round(($day['total'] / $maxTotal) * 100, 1),
        ]));
    }

    private function countsBy(Builder $baseQuery, string $column, int $limit): Collection
    {
        return (clone $baseQuery)
            ->select($column)
            ->selectRaw('COUNT(*) as total')
            ->groupBy($column)
            ->orderByRaw('COUNT(*) DESC')
            ->take($limit)
            ->get()
            ->map(fn ($row): array => [
                'key' => (string) ($row->{$column} ?? ''),
                'label' => $this->labelFor($column, $row->{$column} ?? null),
                'total' => (int) $row->total,
            ]);
    }

    private function effectivenessBreakdown(Builder $query): Collection
    {
        $labels = PlanAccion::effectivenessOptions();

        return $this->countsBy($query, 'effectiveness', 6)
            ->map(fn (array $row): array => array_merge($row, [
                'label' => $labels[$row['key']] ?? $row['label'],
            ]));
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            'success' => 'Exitosas',
            'fallback' => 'Fallback',
            'failed' => 'Fallidas',
        ];
    }

    private function actionOptions(): Collection
    {
        return AiInteractionLog::query()
            ->select('action_type')
            ->whereNotNull('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type')
            ->map(fn (string $actionType): array => [
                'value' => $actionType,
                'label' => $this->labelFor('action_type', $actionType),
            ]);
    }

    private function providerOptions(): Collection
    {
        return AiInteractionLog::query()
            ->select('provider')
            ->whereNotNull('provider')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider')
            ->filter()
            ->values();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function healthSignals(array $metrics, array $rag, array $plans): array
    {
        $signals = [];

        if ($metrics['total'] === 0) {
            $signals[] = [
                'level' => 'info',
                'title' => 'Sin trafico IA',
                'detail' => 'No hay interacciones en el periodo filtrado.',
            ];

            return $signals;
        }

        if ($metrics['failure_rate'] >= 5) {
            $signals[] = [
                'level' => 'critical',
                'title' => 'Fallas por arriba del 5%',
                'detail' => 'Revisa errores recientes y proveedor activo.',
            ];
        } elseif ($metrics['failed'] > 0) {
            $signals[] = [
                'level' => 'warning',
                'title' => 'Fallas aisladas',
                'detail' => 'Hay errores registrados en este periodo.',
            ];
        }

        if (($metrics['p95_latency_ms'] ?? 0) >= 15000) {
            $signals[] = [
                'level' => 'warning',
                'title' => 'Latencia alta',
                'detail' => 'El percentil 95 supera 15 segundos.',
            ];
        }

        if ($rag['chat_total'] > 0 && $rag['chat_knowledge_rate'] < 30) {
            $signals[] = [
                'level' => 'warning',
                'title' => 'Baja cobertura RAG',
                'detail' => 'Pocas respuestas del chatbot usan documentos o fuentes.',
            ];
        }

        if ($plans['review_queue'] > 0) {
            $signals[] = [
                'level' => 'warning',
                'title' => 'Planes IA pendientes',
                'detail' => 'Hay sugerencias esperando revision operativa.',
            ];
        }

        if ($plans['total'] > 0 && $plans['feedback_rate'] < 50) {
            $signals[] = [
                'level' => 'info',
                'title' => 'Falta feedback de ejecucion',
                'detail' => 'Completar costo, horas y efectividad mejora el aprendizaje.',
            ];
        }

        if ($signals === []) {
            $signals[] = [
                'level' => 'healthy',
                'title' => 'Operacion estable',
                'detail' => 'Sin alertas de salud IA en el periodo.',
            ];
        }

        return $signals;
    }

    private function labelFor(string $column, mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ($value === '') {
            return 'Sin dato';
        }

        if ($column === 'status') {
            return match ($value) {
                'success' => 'Exitosa',
                'fallback' => 'Fallback',
                'failed' => 'Fallida',
                default => $this->humanize($value),
            };
        }

        if ($column === 'action_type') {
            return match ($value) {
                'assistant_chat' => 'Chatbot operativo',
                'washer_action_plan_generation' => 'Plan IA lavadora',
                'washer_action_plan_fallback' => 'Fallback plan lavadora',
                default => $this->humanize($value),
            };
        }

        return $this->humanize($value);
    }

    private function humanize(string $value): string
    {
        return Str::of($value)
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }

    private function metadataInteger(AiInteractionLog $log, array $paths): int
    {
        $metadata = $log->metadata ?? [];

        foreach ($paths as $path) {
            $value = data_get($metadata, $path);

            if (is_numeric($value)) {
                return max(0, (int) $value);
            }
        }

        return 0;
    }

    private function percentile(Collection $values, float $percentile): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $index = (int) ceil($sorted->count() * $percentile) - 1;
        $index = max(0, min($index, $sorted->count() - 1));

        return (int) $sorted->get($index);
    }

    private function rate(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }
}
