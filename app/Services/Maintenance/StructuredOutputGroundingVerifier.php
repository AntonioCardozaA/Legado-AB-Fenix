<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StructuredOutputGroundingVerifier
{
    /**
     * @param  array<string, mixed>  $structured
     * @param  array<string, mixed>  $context
     * @return array{structured: array<string, mixed>, report: array<string, mixed>}
     */
    public function assessActionPlan(array $structured, array $context): array
    {
        $availableSources = $this->availableSourcesFromContext($context);
        $availableKeys = collect($availableSources)
            ->flatMap(fn (array $source): array => $source['keys'])
            ->unique()
            ->values()
            ->all();

        $citedSources = array_values(array_filter(
            (array) ($structured['knowledge_sources'] ?? []),
            static fn ($source): bool => is_array($source)
        ));

        $validSources = [];
        $invalidSources = [];

        foreach ($citedSources as $source) {
            $sourceKeys = $this->sourceKeys($source);
            $isValid = count(array_intersect($sourceKeys, $availableKeys)) > 0;

            if ($isValid) {
                $validSources[] = $source;
            } else {
                $invalidSources[] = [
                    'reference' => $this->scalarString($source['reference'] ?? null),
                    'type' => $this->scalarString($source['type'] ?? null),
                    'document_id' => $source['document_id'] ?? null,
                    'chunk_id' => $source['chunk_id'] ?? null,
                    'chunk_index' => $source['chunk_index'] ?? null,
                ];
            }
        }

        $citedCount = count($citedSources);
        $validCount = count($validSources);
        $score = $citedCount > 0 ? round($validCount / $citedCount, 4) : 0.0;
        $originalConfidence = is_numeric($structured['confidence'] ?? null) ? (float) $structured['confidence'] : null;
        $adjustedConfidence = $originalConfidence;
        $warnings = [];

        if ($citedCount === 0 && $availableSources !== []) {
            $warnings[] = 'La respuesta no cito fuentes verificables aunque habia contexto disponible.';
            $adjustedConfidence = $this->capConfidence($adjustedConfidence, (float) config('maintenance_ai.grounding.ungrounded_confidence_cap', 0.45));
        } elseif ($validCount === 0 && $citedCount > 0) {
            $warnings[] = 'Ninguna fuente citada coincide con el contexto tecnico, RAG o web entregado.';
            $adjustedConfidence = $this->capConfidence($adjustedConfidence, (float) config('maintenance_ai.grounding.ungrounded_confidence_cap', 0.45));
        } elseif ($score < (float) config('maintenance_ai.grounding.min_score_for_approval', 0.2)) {
            $warnings[] = 'La cobertura de fuentes validas esta por debajo del minimo operativo.';
            $adjustedConfidence = $this->capConfidence($adjustedConfidence, (float) config('maintenance_ai.grounding.weak_grounding_confidence_cap', 0.65));
        }

        if ($adjustedConfidence !== null) {
            $structured['confidence'] = round($adjustedConfidence, 4);
        }

        if ($warnings !== []) {
            $missingInformation = array_values(array_filter((array) ($structured['missing_information'] ?? [])));
            $missingInformation[] = 'Validar evidencia: '.implode(' ', $warnings);
            $structured['missing_information'] = array_values(array_unique($missingInformation));
        }

        $retrievedChunkIds = collect($availableSources)
            ->pluck('chunk_id')
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $retrievedDocumentIds = collect($availableSources)
            ->pluck('document_id')
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        return [
            'structured' => $structured,
            'report' => [
                'required' => true,
                'score' => $score,
                'min_score_for_approval' => (float) config('maintenance_ai.grounding.min_score_for_approval', 0.2),
                'available_source_count' => count($availableSources),
                'cited_source_count' => $citedCount,
                'valid_source_count' => $validCount,
                'invalid_source_count' => count($invalidSources),
                'valid_sources' => $validSources,
                'invalid_sources' => $invalidSources,
                'retrieved_chunk_ids' => $retrievedChunkIds,
                'retrieved_document_ids' => $retrievedDocumentIds,
                'allowed_source_keys' => array_slice($availableKeys, 0, 250),
                'allowed_references' => collect($availableSources)->pluck('reference')->filter()->unique()->values()->all(),
                'coverage_warnings' => $warnings,
                'confidence_original' => $originalConfidence,
                'confidence_adjusted' => $adjustedConfidence,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $structured
     *
     * @throws ValidationException
     */
    public function assertApprovalGrounded(PlanAccion $plan, array $structured): void
    {
        if (! (bool) config('maintenance_ai.grounding.block_approval_without_sources', true)) {
            return;
        }

        $grounding = data_get($plan->source_metadata ?? [], 'grounding');

        if (! is_array($grounding) || ! (bool) ($grounding['required'] ?? false)) {
            return;
        }

        $allowedKeys = array_values(array_filter((array) ($grounding['allowed_source_keys'] ?? []), 'is_string'));
        $citedSources = array_values(array_filter(
            (array) ($structured['knowledge_sources'] ?? []),
            static fn ($source): bool => is_array($source)
        ));

        $validCount = 0;

        foreach ($citedSources as $source) {
            if (count(array_intersect($this->sourceKeys($source), $allowedKeys)) > 0) {
                $validCount++;
            }
        }

        $score = count($citedSources) > 0 ? $validCount / count($citedSources) : 0.0;
        $minScore = (float) ($grounding['min_score_for_approval'] ?? config('maintenance_ai.grounding.min_score_for_approval', 0.2));

        if ($validCount > 0 && $score >= $minScore) {
            return;
        }

        throw ValidationException::withMessages([
            'knowledge_sources' => 'No se puede aprobar: las fuentes citadas no coinciden con la evidencia tecnica/RAG/web registrada para esta sugerencia.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    private function availableSourcesFromContext(array $context): array
    {
        $sources = [];

        foreach ((array) ($context['knowledge'] ?? []) as $item) {
            if (is_array($item)) {
                $sources[] = $this->normalizeAvailableSource($item, 'knowledge');
            }
        }

        foreach ((array) data_get($context, 'web_context.sources', []) as $item) {
            if (is_array($item)) {
                $sources[] = $this->normalizeAvailableSource($item, 'web');
            }
        }

        foreach ($this->technicalSourceCandidates((array) ($context['technical_context'] ?? [])) as $item) {
            $sources[] = $this->normalizeAvailableSource($item, 'technical_context');
        }

        return collect($sources)
            ->filter(fn (array $source): bool => $source['keys'] !== [])
            ->unique(fn (array $source): string => implode('|', $source['keys']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $technicalContext
     * @return array<int, array<string, mixed>>
     */
    private function technicalSourceCandidates(array $technicalContext): array
    {
        $candidates = [];
        $stack = [$technicalContext];

        while ($stack !== []) {
            $item = array_pop($stack);

            if (! is_array($item)) {
                continue;
            }

            if ($this->looksLikeSource($item)) {
                $candidates[] = $item;
            }

            foreach ($item as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        return $candidates;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function looksLikeSource(array $source): bool
    {
        foreach (['reference', 'title', 'source', 'source_type', 'document_id', 'chunk_id', 'event_id', 'plan_id', 'analysis_id', 'analisis_id'] as $key) {
            if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function normalizeAvailableSource(array $source, string $scope): array
    {
        $reference = $this->firstString($source, ['reference', 'title', 'name', 'url', 'source', 'summary']);
        $normalized = array_merge($source, [
            'type' => $source['type'] ?? $source['source_type'] ?? $scope,
            'reference' => $reference,
        ]);

        return [
            'scope' => $scope,
            'reference' => $reference,
            'document_id' => $source['document_id'] ?? $source['id'] ?? null,
            'chunk_id' => $source['chunk_id'] ?? null,
            'chunk_index' => $source['chunk_index'] ?? null,
            'keys' => $this->sourceKeys($normalized),
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<int, string>
     */
    private function sourceKeys(array $source): array
    {
        $keys = [];
        $type = $this->normalizeKey($this->scalarString($source['type'] ?? $source['source_type'] ?? null));
        $reference = $this->normalizeKey($this->scalarString($source['reference'] ?? $source['title'] ?? $source['url'] ?? null));
        $documentId = $source['document_id'] ?? null;
        $chunkId = $source['chunk_id'] ?? null;
        $chunkIndex = $source['chunk_index'] ?? null;
        $sourceId = $source['source_id'] ?? $source['id'] ?? null;

        if ($reference !== '') {
            $keys[] = 'ref:'.$reference;
            $keys[] = 'type_ref:'.$type.':'.$reference;
        }

        if (is_numeric($documentId)) {
            $keys[] = 'doc:'.(int) $documentId;

            if (is_numeric($chunkIndex)) {
                $keys[] = 'doc_chunk_index:'.(int) $documentId.':'.(int) $chunkIndex;
            }
        }

        if (is_numeric($chunkId)) {
            $keys[] = 'chunk:'.(int) $chunkId;
        }

        if (is_numeric($sourceId) && $type !== '') {
            $keys[] = 'typed_id:'.$type.':'.(int) $sourceId;
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $keys
     */
    private function firstString(array $source, array $keys): string
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return Str::limit(trim((string) $value), 255, '');
            }
        }

        return '';
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function normalizeKey(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $value;
    }

    private function capConfidence(?float $confidence, float $cap): ?float
    {
        if ($confidence === null) {
            return null;
        }

        return min($confidence, max(0.0, min(1.0, $cap)));
    }
}
