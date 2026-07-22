<?php

namespace App\Services\Maintenance;

use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\WasherKnowledgeChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeRetriever
{
    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function retrieveForEvent(MaintenanceEvent $event, array $filters = []): array
    {
        $queryText = $this->buildQueryText($event, $filters);
        $queryTokens = $this->tokenize($queryText);

        $chunks = WasherKnowledgeChunk::query()
            ->with('document.linea', 'document.componente')
            ->whereHas('document', function ($query) use ($event) {
                $query->where('indexing_status', 'indexed')
                    ->where(function ($documentQuery) {
                        $documentQuery->where('lifecycle_status', 'vigente')
                            ->orWhereNull('lifecycle_status');
                    });
            })
            ->get()
            ->map(function (WasherKnowledgeChunk $chunk) use ($event, $queryTokens) {
                $metadataScore = 0;
                $document = $chunk->document;

                if ((int) $document?->linea_id !== 0 && (int) $document?->linea_id === (int) $event->linea_id) {
                    $metadataScore += 2;
                }

                if ((int) $document?->componente_id !== 0 && (int) $document?->componente_id === (int) $event->componente_id) {
                    $metadataScore += 2;
                }

                $contentTokens = $this->tokenize($chunk->searchable_text);
                $overlap = count(array_intersect($queryTokens, $contentTokens));
                $score = $metadataScore + $overlap;

                return [
                    'score' => $score,
                    'type' => $this->documentTypeToKnowledgeType((string) $document?->document_type),
                    'reference' => (string) $document?->title,
                    'content' => $this->sanitizer->sanitizeText($chunk->content, 1200),
                    'document_id' => $document?->getKey(),
                    'page' => $chunk->metadata['page'] ?? null,
                    'section' => $chunk->metadata['section'] ?? null,
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->sortByDesc('score')
            ->take((int) config('maintenance_ai.max_knowledge_chunks', 6))
            ->values();

        $historicalPlans = PlanAccion::query()
            ->with('maintenanceEvent.componente')
            ->where('source', 'ai')
            ->where('estado', 'approved')
            ->where('linea_id', $event->linea_id)
            ->when(
                $event->componente_id,
                fn ($query) => $query->whereHas('maintenanceEvent', fn ($eventQuery) => $eventQuery->where('componente_id', $event->componente_id))
            )
            ->latest('reviewed_at')
            ->limit(2)
            ->get()
            ->map(function (PlanAccion $plan): array {
                return [
                    'score' => 100,
                    'type' => 'historical_plan',
                    'reference' => 'Plan aprobado #' . $plan->id,
                    'content' => $this->sanitizer->sanitizeText((string) ($plan->actividad ?? ''), 800),
                    'document_id' => null,
                    'page' => null,
                    'section' => null,
                ];
            });

        return $historicalPlans
            ->concat($chunks)
            ->take((int) config('maintenance_ai.max_knowledge_chunks', 6))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildQueryText(MaintenanceEvent $event, array $filters): string
    {
        return implode(' ', array_filter([
            $event->title,
            $event->description,
            $event->event_type,
            $filters['component_name'] ?? null,
            $filters['linea_nombre'] ?? null,
            $filters['estado'] ?? null,
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(?string $value): array
    {
        $normalized = Str::lower((string) $value);
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/u', trim($normalized)) ?: [];

        return array_values(array_unique(array_filter($parts, fn ($part) => strlen($part) > 2)));
    }

    private function documentTypeToKnowledgeType(string $documentType): string
    {
        return match ($documentType) {
            'manual tecnico', 'manual de usuario' => 'manual',
            'procedimiento', 'estandar interno', 'instructivo' => 'procedure',
            'plan anterior' => 'historical_plan',
            'reporte' => 'revision',
            default => 'manual',
        };
    }
}
