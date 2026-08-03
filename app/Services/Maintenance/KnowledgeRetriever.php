<?php

namespace App\Services\Maintenance;

use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\WasherKnowledgeChunk;

class KnowledgeRetriever
{
    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly HybridKnowledgeRanker $ranker
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function retrieveForEvent(MaintenanceEvent $event, array $filters = []): array
    {
        $queryText = $this->buildQueryText($event, $filters);
        $profile = $this->ranker->profile($queryText, $filters);
        $queryEmbedding = $this->ranker->queryEmbedding($queryText);
        $candidateLimit = max(
            (int) config('maintenance_ai.max_knowledge_chunks', 6),
            (int) config('maintenance_ai.knowledge.candidate_limit', 80)
        );

        $chunks = WasherKnowledgeChunk::query()
            ->with('document.linea', 'document.componente')
            ->whereHas('document', function ($query) use ($event) {
                $query->where('indexing_status', 'indexed')
                    ->where(function ($documentQuery) {
                        $documentQuery->where('lifecycle_status', 'vigente')
                            ->orWhereNull('lifecycle_status');
                    });
            })
            ->latest('updated_at')
            ->limit($candidateLimit)
            ->get()
            ->map(function (WasherKnowledgeChunk $chunk) use ($event, $filters, $profile, $queryEmbedding): array {
                $ranking = $this->ranker->rankChunk($chunk, $profile, array_merge($filters, [
                    'linea_id' => $event->linea_id,
                    'componente_id' => $event->componente_id,
                ]), $queryEmbedding);
                $item = $this->ranker->toKnowledgeItem($chunk, $ranking, 1200);

                return $item;
            })
            ->filter(fn (array $item): bool => $this->ranker->shouldKeep($item))
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
                    'chunk_id' => null,
                    'chunk_index' => null,
                    'page' => null,
                    'section' => null,
                    'score_breakdown' => [
                        'lexical' => 0,
                        'metadata' => 0,
                        'semantic' => 0,
                        'matched_terms' => [],
                    ],
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

}
