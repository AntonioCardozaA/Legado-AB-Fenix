<?php

namespace App\Services\Maintenance;

use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use App\Models\WasherKnowledgeDocument;
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
        $profile = $this->buildQueryProfile($query, $pageContext);
        $module = $this->normalizeModule($pageContext['module'] ?? null);
        $recordId = isset($pageContext['record_id']) && is_numeric($pageContext['record_id'])
            ? (int) $pageContext['record_id']
            : null;
        $path = Str::lower((string) ($pageContext['current_path'] ?? ''));
        $limit = max(1, (int) config('maintenance_ai.chat.max_context_items', 5));
        $knowledgeDocuments = $this->searchKnowledgeDocuments($profile);
        $preferredDocumentIds = $knowledgeDocuments
            ->pluck('document_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->take(12)
            ->all();

        return $this->searchPlans($profile['tokens'], $module, $recordId, $path)
            ->concat($knowledgeDocuments)
            ->concat($this->searchKnowledgeChunks($profile, $preferredDocumentIds))
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->map(fn (array $item): array => Arr::except($item, ['score', 'document_id']))
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
     * @param  array<string, mixed>  $profile
     * @return Collection<int, array<string, mixed>>
     */
    private function searchKnowledgeDocuments(array $profile): Collection
    {
        $tokens = $profile['tokens'] ?? [];

        if ($tokens === []) {
            return collect();
        }

        return WasherKnowledgeDocument::query()
            ->with(['linea', 'componente'])
            ->where('indexing_status', 'indexed')
            ->where(function ($query): void {
                $query->where('lifecycle_status', 'vigente')
                    ->orWhereNull('lifecycle_status');
            })
            ->get()
            ->map(function (WasherKnowledgeDocument $document) use ($profile, $tokens): array {
                $haystack = implode(' ', array_filter([
                    (string) $document->title,
                    (string) $document->document_type,
                    (string) ($document->linea?->nombre ?? ''),
                    (string) ($document->componente?->nombre ?? ''),
                    (string) $document->extracted_text,
                ]));
                $score = $this->scoreTokens($tokens, $haystack) + $this->knowledgeMetadataBoost(
                    $profile,
                    (string) ($document->linea?->nombre ?? ''),
                    implode(' ', array_filter([
                        (string) ($document->componente?->nombre ?? ''),
                        (string) $document->title,
                        (string) $document->extracted_text,
                    ]))
                );
                $summary = implode(' | ', array_filter([
                    'Documento: ' . $document->title,
                    $document->document_type ? 'Tipo: ' . $document->document_type : null,
                    $document->linea?->nombre ? 'Linea: ' . $document->linea->nombre : null,
                    $document->componente?->nombre ? 'Componente: ' . $document->componente->nombre : null,
                    $this->sanitizer->sanitizeText((string) ($document->extracted_text ?: $document->title), 700),
                ]));

                return [
                    'score' => $score,
                    'document_id' => (int) $document->id,
                    'type' => $this->normalizeDocumentType((string) $document->document_type),
                    'reference' => (string) $document->title,
                    'content' => $this->sanitizer->sanitizeText($summary, 900),
                    'module' => User::MODULE_LAVADORA,
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<int, int>  $preferredDocumentIds
     * @return Collection<int, array<string, mixed>>
     */
    private function searchKnowledgeChunks(array $profile, array $preferredDocumentIds = []): Collection
    {
        $tokens = $profile['tokens'] ?? [];

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
            ->when($preferredDocumentIds !== [], fn ($query) => $query->whereIn('document_id', $preferredDocumentIds))
            ->get();

        return $chunks
            ->map(function (WasherKnowledgeChunk $chunk) use ($profile, $tokens): array {
                $document = $chunk->document;
                $haystack = implode(' ', array_filter([
                    $chunk->searchable_text,
                    $document?->title,
                    $document?->linea?->nombre,
                    $document?->componente?->nombre,
                ]));

                return [
                    'score' => $this->scoreTokens($tokens, $haystack) + $this->knowledgeMetadataBoost(
                        $profile,
                        (string) ($document?->linea?->nombre ?? ''),
                        implode(' ', array_filter([
                            (string) ($document?->componente?->nombre ?? ''),
                            (string) ($document?->title ?? ''),
                            (string) $chunk->searchable_text,
                        ]))
                    ),
                    'document_id' => (int) ($document?->id ?? 0),
                    'type' => $this->normalizeDocumentType((string) ($document?->document_type ?? 'manual')),
                    'reference' => (string) ($document?->title ?? 'Documento tecnico'),
                    'content' => $this->sanitizer->sanitizeText((string) $chunk->content, 900),
                    'module' => User::MODULE_LAVADORA,
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function knowledgeMetadataBoost(array $profile, string $linea, string $componentHaystack): int
    {
        $boost = 0;

        if (($profile['lineas'] ?? []) !== [] && $linea !== '' && in_array(Str::upper($linea), $profile['lineas'], true)) {
            $boost += 4;
        }

        if (($profile['component_terms'] ?? []) !== [] && $this->tokensOverlap(
            $profile['component_terms'],
            $this->tokenize($componentHaystack)
        )) {
            $boost += 4;
        }

        if (($profile['is_lubrication_query'] ?? false) && $this->tokensOverlap(
            ['aceite', 'lubricante', 'lubricacion', 'litro', 'litros'],
            $this->tokenize($componentHaystack)
        )) {
            $boost += 3;
        }

        if (($profile['is_refaction_cost_query'] ?? false) && $this->tokensOverlap(
            ['costo', 'precio', 'sku', 'refaccion', 'refacciones', 'refa', 'consumible', 'material', 'catarina', 'cadena', 'guia', 'buje', 'servo', 'reductor'],
            $this->tokenize($componentHaystack)
        )) {
            $boost += 5;
        }

        return $boost;
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    private function buildQueryProfile(string $query, array $pageContext): array
    {
        $baseText = implode(' ', array_filter([
            $query,
            $pageContext['page_title'] ?? null,
            $pageContext['section'] ?? null,
            $pageContext['entity_label'] ?? null,
        ]));
        $normalized = Str::lower(Str::ascii($baseText));
        $componentTerms = [];

        if (str_contains($normalized, 'servo chico') || str_contains($normalized, 'servos chicos')) {
            $componentTerms = array_merge($componentTerms, ['servo', 'chico', 'servos']);
        }

        if (str_contains($normalized, 'servo grande') || str_contains($normalized, 'servos grandes')) {
            $componentTerms = array_merge($componentTerms, ['servo', 'grande', 'servos']);
        }

        if (str_contains($normalized, 'reductor') || str_contains($normalized, 'reductores') || str_contains($normalized, 'sin fin')) {
            $componentTerms = array_merge($componentTerms, ['reductor', 'reductores', 'rv200', 'sin', 'fin']);
        }

        if (str_contains($normalized, 'red ppal') || str_contains($normalized, 'red principal')) {
            $componentTerms = array_merge($componentTerms, ['red', 'ppal', 'principal']);
        }

        if (str_contains($normalized, 'catarina') || str_contains($normalized, 'sprocket')) {
            $componentTerms = array_merge($componentTerms, ['catarina', 'catarinas', 'sprocket', 'sprockets']);
        }

        if (
            str_contains($normalized, 'cadena')
            || str_contains($normalized, 'candado')
            || str_contains($normalized, 'eslabon')
        ) {
            $componentTerms = array_merge($componentTerms, ['cadena', 'cadenas', 'candado', 'candados', 'eslabon', 'eslabones']);
        }

        if (str_contains($normalized, 'guia')) {
            $componentTerms = array_merge($componentTerms, ['guia', 'guias']);
        }

        if (
            str_contains($normalized, 'buje')
            || str_contains($normalized, 'baquelita')
            || str_contains($normalized, 'espiga')
            || str_contains($normalized, 'casquillo')
        ) {
            $componentTerms = array_merge($componentTerms, ['buje', 'baquelita', 'espiga', 'casquillo']);
        }

        return [
            'tokens' => $this->expandTokens($this->tokenize($baseText), $normalized),
            'lineas' => $this->extractLineReferences($baseText),
            'component_terms' => array_values(array_unique(array_filter($componentTerms))),
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
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function expandTokens(array $tokens, string $normalized): array
    {
        $expanded = $tokens;

        if (
            str_contains($normalized, 'aceite')
            || str_contains($normalized, 'lubric')
            || str_contains($normalized, 'litro')
        ) {
            $expanded = array_merge($expanded, ['aceite', 'lubricante', 'lubricacion', 'litro', 'litros']);
        }

        if (str_contains($normalized, 'servo')) {
            $expanded[] = 'servo';
            $expanded[] = 'servos';
        }

        if (str_contains($normalized, 'reductor')) {
            $expanded[] = 'reductor';
            $expanded[] = 'reductores';
        }

        if (
            str_contains($normalized, 'cuesta')
            || str_contains($normalized, 'costo')
            || str_contains($normalized, 'precio')
            || str_contains($normalized, 'sku')
            || str_contains($normalized, 'refa')
            || str_contains($normalized, 'refaccion')
        ) {
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

        return array_values(array_unique(array_filter($expanded, function ($token): bool {
            $token = trim((string) $token);

            return $token !== '' && (ctype_digit($token) || strlen($token) > 2);
        })));
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
     * @param  array<int, string>  $left
     * @param  array<int, string>  $right
     */
    private function tokensOverlap(array $left, array $right): bool
    {
        return count(array_intersect($left, $right)) > 0;
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
