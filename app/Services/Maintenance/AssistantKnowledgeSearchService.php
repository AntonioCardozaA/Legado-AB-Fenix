<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssistantKnowledgeSearchService
{
    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer
    ) {
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, array $pageContext = []): array
    {
        $tokens = $this->tokenize(implode(' ', array_filter([
            $query,
            $pageContext['page_title'] ?? null,
            $pageContext['section'] ?? null,
            $pageContext['entity_label'] ?? null,
        ])));

        $module = $this->normalizeModule($pageContext['module'] ?? null);
        $recordId = isset($pageContext['record_id']) && is_numeric($pageContext['record_id'])
            ? (int) $pageContext['record_id']
            : null;
        $path = Str::lower((string) ($pageContext['current_path'] ?? ''));
        $limit = max(1, (int) config('maintenance_ai.chat.max_context_items', 5));

        return $this->searchPlans($tokens, $module, $recordId, $path)
            ->concat($this->searchKnowledgeChunks($tokens))
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->map(fn (array $item): array => Arr::except($item, ['score']))
            ->all();
    }

    /**
     * @param  array<int, string>  $tokens
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPlans(array $tokens, ?string $module, ?int $recordId, string $path): Collection
    {
        $plans = PlanAccion::query()
            ->with(['linea', 'maintenanceEvent.componente'])
            ->where(function ($query): void {
                $query->whereNull('source')
                    ->orWhere('source', 'manual')
                    ->orWhere(function ($aiQuery): void {
                        $aiQuery->where('source', 'ai')
                            ->where('estado', 'approved');
                    });
            })
            ->when($module, fn ($query) => $query->where('tipo_equipo', $module))
            ->latest('updated_at')
            ->limit(25)
            ->get();

        return $plans
            ->map(function (PlanAccion $plan) use ($tokens, $recordId, $path): array {
                $haystack = implode(' ', array_filter([
                    $plan->actividad,
                    $plan->detected_problem,
                    $plan->technical_justification,
                    $plan->risk_if_not_executed,
                    $plan->linea?->nombre,
                    $plan->maintenanceEvent?->componente?->nombre,
                    $plan->maintenanceEvent?->title,
                ]));

                $score = $this->scoreTokens($tokens, $haystack);

                if ($recordId && str_contains($path, 'plan-accion') && (int) $plan->getKey() === $recordId) {
                    $score += 20;
                }

                $summary = implode(' | ', array_filter([
                    $plan->actividad ? 'Actividad: ' . $plan->actividad : null,
                    $plan->linea?->nombre ? 'Linea: ' . $plan->linea->nombre : null,
                    $plan->maintenanceEvent?->componente?->nombre ? 'Componente: ' . $plan->maintenanceEvent->componente->nombre : null,
                    $plan->detected_problem ? 'Problema: ' . $plan->detected_problem : null,
                    $plan->technical_justification ? 'Justificacion: ' . $plan->technical_justification : null,
                    $plan->risk_if_not_executed ? 'Riesgo: ' . $plan->risk_if_not_executed : null,
                ]));

                return [
                    'score' => $score,
                    'type' => 'operational_plan',
                    'reference' => 'Plan #' . $plan->id,
                    'content' => $this->sanitizer->sanitizeText($summary, 900),
                    'module' => $plan->tipo_equipo,
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return Collection<int, array<string, mixed>>
     */
    private function searchKnowledgeChunks(array $tokens): Collection
    {
        if ($tokens === []) {
            return collect();
        }

        $chunks = WasherKnowledgeChunk::query()
            ->with('document.linea', 'document.componente')
            ->whereHas('document', function ($query): void {
                $query->where('indexing_status', 'indexed')
                    ->where(function ($documentQuery): void {
                        $documentQuery->where('lifecycle_status', 'vigente')
                            ->orWhereNull('lifecycle_status');
                    });
            })
            ->latest('id')
            ->limit(120)
            ->get();

        return $chunks
            ->map(function (WasherKnowledgeChunk $chunk) use ($tokens): array {
                $document = $chunk->document;
                $haystack = implode(' ', array_filter([
                    $chunk->searchable_text,
                    $document?->title,
                    $document?->linea?->nombre,
                    $document?->componente?->nombre,
                ]));

                return [
                    'score' => $this->scoreTokens($tokens, $haystack),
                    'type' => $this->normalizeDocumentType((string) ($document?->document_type ?? 'manual')),
                    'reference' => (string) ($document?->title ?? 'Documento tecnico'),
                    'content' => $this->sanitizer->sanitizeText((string) $chunk->content, 900),
                    'module' => User::MODULE_LAVADORA,
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function scoreTokens(array $tokens, string $haystack): int
    {
        if ($tokens === []) {
            return 0;
        }

        $normalized = $this->tokenize($haystack);
        $overlap = count(array_intersect($tokens, $normalized));

        return $overlap;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(?string $value): array
    {
        $normalized = Str::ascii(Str::lower((string) $value));
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/u', trim($normalized)) ?: [];

        return array_values(array_unique(array_filter($parts, function ($part): bool {
            $part = trim((string) $part);

            if ($part === '') {
                return false;
            }

            if (ctype_digit($part)) {
                return true;
            }

            return strlen($part) > 2;
        })));
    }

    private function normalizeModule(?string $module): ?string
    {
        $normalized = Str::lower(trim((string) $module));

        return in_array($normalized, [
            User::MODULE_LAVADORA,
            User::MODULE_ETIQUETADORA,
            User::MODULE_PASTEURIZADORA,
        ], true) ? $normalized : null;
    }

    private function normalizeDocumentType(string $type): string
    {
        $normalized = Str::lower(trim($type));

        return match ($normalized) {
            'procedimiento', 'estandar interno', 'instructivo' => 'procedure',
            'plan anterior' => 'historical_plan',
            'reporte' => 'revision',
            default => 'manual',
        };
    }
}
