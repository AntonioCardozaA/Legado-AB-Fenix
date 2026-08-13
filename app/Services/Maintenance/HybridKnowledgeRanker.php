<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Models\WasherKnowledgeChunk;
use Illuminate\Support\Str;
use Throwable;

class HybridKnowledgeRanker
{
    private const STOP_WORDS = [
        'con',
        'del',
        'desde',
        'donde',
        'ella',
        'ellos',
        'esta',
        'este',
        'esto',
        'estos',
        'las',
        'los',
        'para',
        'por',
        'que',
        'sin',
        'una',
        'uno',
    ];

    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly AiProviderInterface $aiProvider
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function profile(string $query, array $context = []): array
    {
        $baseText = implode(' ', array_filter([
            $query,
            $context['page_title'] ?? null,
            $context['section'] ?? null,
            $context['entity_label'] ?? null,
            $context['component_name'] ?? null,
            $context['linea_nombre'] ?? null,
            $context['estado'] ?? null,
        ]));
        $normalized = Str::lower(Str::ascii($baseText));

        return [
            'query' => $this->sanitizer->sanitizeText($baseText, 1600),
            'tokens' => $this->expandTokens($this->tokenize($baseText), $normalized),
            'lineas' => $this->extractLineReferences($baseText),
            'component_terms' => $this->extractComponentTerms($normalized),
            'is_lubrication_query' => str_contains($normalized, 'aceite')
                || str_contains($normalized, 'lubric')
                || str_contains($normalized, 'litro')
                || str_contains($normalized, 'fluido'),
            'is_refaction_cost_query' => str_contains($normalized, 'cuesta')
                || str_contains($normalized, 'costo')
                || str_contains($normalized, 'precio')
                || str_contains($normalized, 'sku')
                || str_contains($normalized, 'refa')
                || str_contains($normalized, 'refaccion')
                || str_contains($normalized, 'material')
                || str_contains($normalized, 'consumible'),
        ];
    }

    /**
     * @return array<int, float>
     */
    public function queryEmbedding(string $query): array
    {
        if (!(bool) config('maintenance_ai.enabled', false)
            || !(bool) config('maintenance_ai.knowledge.semantic_query_enabled', true)) {
            return [];
        }

        try {
            return $this->normalizeVector($this->aiProvider->createEmbedding(
                $this->sanitizer->sanitizeText($query, 1600)
            ));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $context
     * @param  array<int, float>  $queryEmbedding
     * @return array<string, mixed>
     */
    public function rankChunk(
        WasherKnowledgeChunk $chunk,
        array $profile,
        array $context = [],
        array $queryEmbedding = []
    ): array {
        $document = $chunk->document;
        $haystack = implode(' ', array_filter([
            (string) $chunk->searchable_text,
            (string) $chunk->content,
            (string) ($document?->title ?? ''),
            (string) ($document?->document_type ?? ''),
            (string) ($document?->linea?->nombre ?? ''),
            (string) ($document?->componente?->nombre ?? ''),
        ]));
        $tokens = $profile['tokens'] ?? [];
        $matchedTerms = array_values(array_intersect($tokens, $this->tokenize($haystack)));
        $lexicalScore = $this->lexicalScore($tokens, $haystack);
        $metadataScore = $this->metadataScore($chunk, $profile, $context);
        $semanticScore = $this->semanticScore($queryEmbedding, $chunk->embedding ?? []);

        $score = ($lexicalScore * (float) config('maintenance_ai.knowledge.lexical_weight', 2.0))
            + ($metadataScore * (float) config('maintenance_ai.knowledge.metadata_weight', 1.0))
            + ($semanticScore * (float) config('maintenance_ai.knowledge.semantic_weight', 18.0));

        return [
            'score' => round($score, 4),
            'lexical_score' => round($lexicalScore, 4),
            'metadata_score' => round($metadataScore, 4),
            'semantic_score' => round($semanticScore, 4),
            'matched_terms' => $matchedTerms,
        ];
    }

    /**
     * @param  array<string, mixed>  $ranking
     */
    public function shouldKeep(array $ranking): bool
    {
        return (float) ($ranking['score'] ?? 0) >= (float) config('maintenance_ai.knowledge.min_score', 1.0);
    }

    /**
     * @param  array<string, mixed>  $ranking
     * @return array<string, mixed>
     */
    public function toKnowledgeItem(WasherKnowledgeChunk $chunk, array $ranking, int $contentLimit = 1200): array
    {
        $document = $chunk->document;

        return [
            'score' => $ranking['score'],
            'type' => $this->documentTypeToKnowledgeType((string) ($document?->document_type ?? 'manual')),
            'reference' => trim((string) ($document?->title ?? 'Documento tecnico')) . ' · fragmento ' . $chunk->chunk_index,
            'content' => $this->sanitizer->sanitizeText((string) $chunk->content, $contentLimit),
            'document_id' => $document?->getKey(),
            'chunk_id' => $chunk->getKey(),
            'chunk_index' => $chunk->chunk_index,
            'page' => $chunk->metadata['page'] ?? null,
            'section' => $chunk->metadata['section'] ?? null,
            'linea' => $document?->linea?->nombre,
            'componente' => $document?->componente?->nombre,
            'document_type' => $document?->document_type,
            'version' => $document?->version,
            'effective_at' => optional($document?->effective_at)->toDateString(),
            'score_breakdown' => [
                'lexical' => $ranking['lexical_score'],
                'metadata' => $ranking['metadata_score'],
                'semantic' => $ranking['semantic_score'],
                'matched_terms' => $ranking['matched_terms'],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $tokens
     */
    public function lexicalScore(array $tokens, string $haystack): float
    {
        if ($tokens === []) {
            return 0.0;
        }

        $haystackTokens = $this->tokenize($haystack);
        $overlap = count(array_intersect($tokens, $haystackTokens));
        $coverage = $overlap / max(1, count($tokens));

        return $overlap + $coverage;
    }

    /**
     * @return array<int, string>
     */
    public function tokenize(?string $value): array
    {
        $normalized = Str::ascii(Str::lower((string) $value));
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/u', trim($normalized)) ?: [];

        return array_values(array_unique(array_filter($parts, function ($part): bool {
            $part = trim((string) $part);

            if ($part === '' || in_array($part, self::STOP_WORDS, true)) {
                return false;
            }

            if (ctype_digit($part)) {
                return true;
            }

            return strlen($part) > 2;
        })));
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function expandTokens(array $tokens, string $normalized): array
    {
        $expanded = $tokens;

        if (str_contains($normalized, 'aceite') || str_contains($normalized, 'lubric') || str_contains($normalized, 'litro')) {
            $expanded = array_merge($expanded, ['aceite', 'lubricante', 'lubricacion', 'litro', 'litros']);
        }

        if (str_contains($normalized, 'servo')) {
            $expanded = array_merge($expanded, ['servo', 'servos']);
        }

        if (str_contains($normalized, 'reductor')) {
            $expanded = array_merge($expanded, ['reductor', 'reductores']);
        }

        if (str_contains($normalized, 'cuesta')
            || str_contains($normalized, 'costo')
            || str_contains($normalized, 'precio')
            || str_contains($normalized, 'sku')
            || str_contains($normalized, 'refa')
            || str_contains($normalized, 'refaccion')) {
            $expanded = array_merge($expanded, [
                'costo',
                'costos',
                'precio',
                'precios',
                'sku',
                'refa',
                'refas',
                'refaccion',
                'refacciones',
                'material',
                'materiales',
                'consumible',
                'consumibles',
                'repuesto',
                'repuestos',
            ]);
        }

        if (str_contains($normalized, 'catarina') || str_contains($normalized, 'sprocket')) {
            $expanded = array_merge($expanded, ['catarina', 'catarinas', 'sprocket', 'sprockets']);
        }

        if (str_contains($normalized, 'guia')) {
            $expanded = array_merge($expanded, ['guia', 'guias']);
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    /**
     * @return array<int, string>
     */
    private function extractLineReferences(string $text): array
    {
        $normalized = Str::lower(Str::ascii($text));
        $lineas = [];

        if (preg_match_all('/(?:lavadora|linea|l)\s*[-#]?\s*0*(\d{1,2})\b/u', $normalized, $lineMatches)) {
            foreach ($lineMatches[1] as $lineNumber) {
                $lineas[] = 'L-' . str_pad((string) $lineNumber, 2, '0', STR_PAD_LEFT);
            }
        }

        return array_values(array_unique($lineas));
    }

    /**
     * @return array<int, string>
     */
    private function extractComponentTerms(string $normalized): array
    {
        $terms = [];

        if (str_contains($normalized, 'servo chico') || str_contains($normalized, 'servos chicos')) {
            $terms = array_merge($terms, ['servo', 'chico', 'servos']);
        }

        if (str_contains($normalized, 'servo grande') || str_contains($normalized, 'servos grandes')) {
            $terms = array_merge($terms, ['servo', 'grande', 'servos']);
        }

        if (str_contains($normalized, 'reductor') || str_contains($normalized, 'reductores') || str_contains($normalized, 'sin fin')) {
            $terms = array_merge($terms, ['reductor', 'reductores', 'rv250', 'rv200', 'sin', 'fin', 'corona']);
        }

        if (str_contains($normalized, 'red ppal') || str_contains($normalized, 'red principal')) {
            $terms = array_merge($terms, ['red', 'ppal', 'principal']);
        }

        if (str_contains($normalized, 'catarina') || str_contains($normalized, 'sprocket')) {
            $terms = array_merge($terms, ['catarina', 'catarinas', 'sprocket', 'sprockets']);
        }

        if (str_contains($normalized, 'cadena') || str_contains($normalized, 'candado') || str_contains($normalized, 'eslabon')) {
            $terms = array_merge($terms, ['cadena', 'cadenas', 'candado', 'candados', 'eslabon', 'eslabones']);
        }

        if (str_contains($normalized, 'guia')) {
            $terms = array_merge($terms, ['guia', 'guias']);
        }

        if (str_contains($normalized, 'buje')
            || str_contains($normalized, 'baquelita')
            || str_contains($normalized, 'espiga')
            || str_contains($normalized, 'casquillo')) {
            $terms = array_merge($terms, ['buje', 'baquelita', 'espiga', 'casquillo']);
        }

        return array_values(array_unique(array_filter($terms)));
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $context
     */
    private function metadataScore(WasherKnowledgeChunk $chunk, array $profile, array $context): float
    {
        $score = 0.0;
        $document = $chunk->document;
        $linea = Str::upper((string) ($document?->linea?->nombre ?? ''));
        $componentHaystack = implode(' ', array_filter([
            (string) ($document?->componente?->nombre ?? ''),
            (string) ($document?->title ?? ''),
            (string) $chunk->searchable_text,
        ]));
        $componentTokens = $this->tokenize($componentHaystack);

        if (($context['linea_id'] ?? null) && (int) $document?->linea_id === (int) $context['linea_id']) {
            $score += 4.0;
        }

        if (($context['componente_id'] ?? null) && (int) $document?->componente_id === (int) $context['componente_id']) {
            $score += 4.0;
        }

        if (($profile['lineas'] ?? []) !== [] && $linea !== '' && in_array($linea, $profile['lineas'], true)) {
            $score += 3.0;
        }

        if (($profile['component_terms'] ?? []) !== [] && count(array_intersect($profile['component_terms'], $componentTokens)) > 0) {
            $score += 3.0;
        }

        if (($profile['is_lubrication_query'] ?? false)
            && count(array_intersect(['aceite', 'lubricante', 'lubricacion', 'litro', 'litros'], $componentTokens)) > 0) {
            $score += 2.5;
        }

        if (($profile['is_refaction_cost_query'] ?? false)
            && count(array_intersect([
                'costo',
                'precio',
                'sku',
                'refaccion',
                'refacciones',
                'refa',
                'consumible',
                'material',
                'catarina',
                'cadena',
                'guia',
                'buje',
                'servo',
                'reductor',
            ], $componentTokens)) > 0) {
            $score += 3.0;
        }

        if ($document?->isCurrent()) {
            $score += 0.5;
        }

        return $score;
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

    private function documentTypeToKnowledgeType(string $documentType): string
    {
        return match (Str::lower(trim($documentType))) {
            'manual tecnico', 'manual de usuario' => 'manual',
            'procedimiento', 'estandar interno', 'instructivo' => 'procedure',
            'plan anterior' => 'historical_plan',
            'reporte' => 'revision',
            'costos', 'catalogo costos' => 'cost_history',
            default => 'manual',
        };
    }
}
