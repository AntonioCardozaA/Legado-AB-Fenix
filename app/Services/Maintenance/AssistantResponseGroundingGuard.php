<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AssistantResponseGroundingGuard
{
    private const CRITICAL_TERMS = [
        'actividad',
        'aprobado',
        'aprobada',
        'asignado',
        'asignada',
        'costo',
        'costos',
        'estado',
        'fecha',
        'historial',
        'linea',
        'lavadora',
        'mantenimiento',
        'mxn',
        'orden',
        'pasteurizadora',
        'plan',
        'precio',
        'prioridad',
        'refaccion',
        'refacciones',
        'responsable',
        'sku',
        'tecnico',
        'trabajo',
    ];

    private const INFERENCE_MARKERS = [
        'como inferencia',
        'conviene',
        'debe validarse',
        'es recomendable',
        'inferencia',
        'podria',
        'probable',
        'recomendacion',
        'recomiendo',
        'revisar',
        'se recomienda',
        'sugiero',
        'validacion pendiente',
        'validar',
    ];

    /**
     * @param  array<string, mixed>  $structured
     * @param  array<string, mixed>  $context
     * @return array{structured: array<string, mixed>, report: array<string, mixed>}
     */
    public function validate(array $structured, array $context): array
    {
        $evidence = $this->evidenceProfile($context);
        $sections = [
            'answer' => [(string) ($structured['answer'] ?? '')],
            'key_points' => (array) ($structured['key_points'] ?? []),
            'next_steps' => (array) ($structured['next_steps'] ?? []),
        ];

        $unsupported = [];
        $inferences = [];
        $criticalClaims = 0;

        foreach ($sections as $field => $items) {
            foreach ($items as $index => $item) {
                if (! is_scalar($item)) {
                    continue;
                }

                foreach ($this->sentences((string) $item) as $sentence) {
                    if (! $this->isCriticalClaim($sentence)) {
                        continue;
                    }

                    $criticalClaims++;

                    if ($this->isMarkedAsInference($sentence)) {
                        $inferences[] = [
                            'field' => $field,
                            'index' => $index,
                            'claim' => $sentence,
                        ];

                        continue;
                    }

                    if (! $this->isSupported($sentence, $evidence)) {
                        $unsupported[] = [
                            'field' => $field,
                            'index' => $index,
                            'claim' => $sentence,
                            'missing_data' => $this->missingDataFor($sentence),
                        ];
                    }
                }
            }
        }

        $sourceReport = $this->sourceReport((array) ($structured['sources'] ?? []), $evidence);
        $unsupportedSources = $sourceReport['unsupported_sources'];

        if ($unsupported !== [] || $unsupportedSources !== []) {
            $structured = $this->applyMissingEvidenceNotice($structured, $unsupported, $unsupportedSources);
            $structured['confidence'] = $this->capConfidence(
                is_numeric($structured['confidence'] ?? null) ? (float) $structured['confidence'] : null,
                (float) config('maintenance_ai.chat.ungrounded_confidence_cap', 0.45)
            );
        }

        return [
            'structured' => $structured,
            'report' => [
                'critical_claims' => $criticalClaims,
                'unsupported_claims_count' => count($unsupported),
                'unsupported_claims' => $unsupported,
                'inference_claims_count' => count($inferences),
                'inference_claims' => $inferences,
                'grounded_sources_count' => count($sourceReport['grounded_sources']),
                'unsupported_sources_count' => count($unsupportedSources),
                'unsupported_sources' => $unsupportedSources,
                'evidence_sources_count' => count($evidence['sources']),
                'missing_data' => collect($unsupported)
                    ->pluck('missing_data')
                    ->merge(collect($unsupportedSources)->map(fn (array $source): string => 'fuente interna/documento/registro para '.$source['reference']))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{text: string, normalized_text: string, sources: array<int, array<string, string>>}
     */
    private function evidenceProfile(array $context): array
    {
        $items = [];
        $sources = [];

        foreach (['platform_context', 'technical_context', 'knowledge'] as $key) {
            $this->collectEvidence($context[$key] ?? [], $items, $sources);
        }

        $text = implode(' ', array_slice($items, 0, 5000));

        return [
            'text' => $text,
            'normalized_text' => $this->normalize($text),
            'sources' => collect($sources)
                ->filter(fn (array $source): bool => $source['reference'] !== '')
                ->unique(fn (array $source): string => $source['type'].'|'.$this->normalize($source['reference']))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  mixed  $value
     * @param  array<int, string>  $items
     * @param  array<int, array<string, string>>  $sources
     */
    private function collectEvidence(mixed $value, array &$items, array &$sources): void
    {
        if (is_scalar($value)) {
            $text = trim((string) $value);

            if ($text !== '') {
                $items[] = Str::limit($text, 800, '');
            }

            return;
        }

        if (! is_array($value)) {
            return;
        }

        $reference = $this->firstScalar($value, ['reference', 'title', 'name', 'url', 'source_reference']);
        $type = $this->firstScalar($value, ['type', 'source_type', 'document_type']) ?: 'internal';

        if ($reference !== '') {
            $sources[] = [
                'type' => $type,
                'reference' => $reference,
            ];
        }

        foreach ($value as $child) {
            $this->collectEvidence($child, $items, $sources);
        }
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $keys
     */
    private function firstScalar(array $source, array $keys): string
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return Str::limit(trim((string) $value), 255, '');
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+|\R+|(?:\s+-\s+)/u', trim($text)) ?: [];

        return array_values(array_filter(array_map(
            fn (string $part): string => trim($part),
            $parts
        ), fn (string $part): bool => $part !== ''));
    }

    private function isCriticalClaim(string $sentence): bool
    {
        $normalized = $this->normalize($sentence);

        foreach (self::CRITICAL_TERMS as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return preg_match('/\b(?:l|p)\s*-?\s*0?\d{1,2}\b/u', $normalized) === 1
            || preg_match('/\b\d{4}-\d{2}-\d{2}\b/u', $normalized) === 1
            || preg_match('/\$\s*\d/u', $sentence) === 1;
    }

    private function isMarkedAsInference(string $sentence): bool
    {
        $normalized = $this->normalize($sentence);

        foreach (self::INFERENCE_MARKERS as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{text: string, normalized_text: string, sources: array<int, array<string, string>>}  $evidence
     */
    private function isSupported(string $sentence, array $evidence): bool
    {
        $normalizedSentence = $this->normalize($sentence);
        $normalizedEvidence = $evidence['normalized_text'];

        if ($normalizedEvidence === '') {
            return false;
        }

        foreach ($this->criticalIdentifiers($normalizedSentence) as $identifier) {
            if (! str_contains($normalizedEvidence, $identifier)) {
                return false;
            }
        }

        $tokens = $this->claimTokens($normalizedSentence);

        if ($tokens === []) {
            return str_contains($normalizedEvidence, $normalizedSentence);
        }

        $matches = collect($tokens)
            ->filter(fn (string $token): bool => str_contains($normalizedEvidence, $token))
            ->count();

        return $matches >= min(3, count($tokens))
            && ($matches / max(1, count($tokens))) >= 0.45;
    }

    /**
     * @return array<int, string>
     */
    private function criticalIdentifiers(string $normalizedSentence): array
    {
        $identifiers = [];

        if (preg_match_all('/\b(?:l|p)\s*-?\s*0?\d{1,2}\b/u', $normalizedSentence, $matches)) {
            foreach ($matches[0] as $match) {
                $identifiers[] = preg_replace('/\s+/', '', $match) ?? $match;
                $identifiers[] = str_replace('-', '', preg_replace('/\s+/', '', $match) ?? $match);
            }
        }

        if (preg_match_all('/\b(?:sku|oc|plan|evento|orden)\s*#?\s*[a-z0-9-]+\b/u', $normalizedSentence, $matches)) {
            foreach ($matches[0] as $match) {
                $identifiers[] = trim($match);
            }
        }

        if (preg_match_all('/\b\d{4}-\d{2}-\d{2}\b/u', $normalizedSentence, $matches)) {
            $identifiers = array_merge($identifiers, $matches[0]);
        }

        return array_values(array_unique(array_filter($identifiers)));
    }

    /**
     * @return array<int, string>
     */
    private function claimTokens(string $normalizedSentence): array
    {
        $parts = preg_split('/[^a-z0-9-]+/u', $normalizedSentence) ?: [];
        $stop = ['para', 'con', 'por', 'del', 'los', 'las', 'una', 'uno', 'que', 'este', 'esta', 'debe', 'deben'];

        return array_values(array_unique(array_filter($parts, function (string $part) use ($stop): bool {
            return strlen($part) > 2 && ! in_array($part, $stop, true);
        })));
    }

    /**
     * @param  array<int, mixed>  $sources
     * @param  array{text: string, normalized_text: string, sources: array<int, array<string, string>>}  $evidence
     * @return array{grounded_sources: array<int, array<string, string>>, unsupported_sources: array<int, array<string, string>>}
     */
    private function sourceReport(array $sources, array $evidence): array
    {
        $grounded = [];
        $unsupported = [];

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            $reference = $this->firstScalar($source, ['reference', 'url']);
            $type = $this->firstScalar($source, ['type']) ?: 'context';

            if ($reference === '') {
                continue;
            }

            if (in_array($this->normalize($type), ['web', 'external', 'web_search'], true)) {
                $grounded[] = ['type' => $type, 'reference' => $reference];

                continue;
            }

            $normalizedReference = $this->normalize($reference);
            $found = str_contains($evidence['normalized_text'], $normalizedReference)
                || collect($evidence['sources'])->contains(fn (array $candidate): bool => $this->normalize($candidate['reference']) === $normalizedReference);

            if ($found) {
                $grounded[] = ['type' => $type, 'reference' => $reference];
            } else {
                $unsupported[] = ['type' => $type, 'reference' => $reference];
            }
        }

        return [
            'grounded_sources' => $grounded,
            'unsupported_sources' => $unsupported,
        ];
    }

    /**
     * @param  array<string, mixed>  $structured
     * @param  array<int, array<string, mixed>>  $unsupported
     * @param  array<int, array<string, string>>  $unsupportedSources
     * @return array<string, mixed>
     */
    private function applyMissingEvidenceNotice(array $structured, array $unsupported, array $unsupportedSources): array
    {
        $missing = collect($unsupported)
            ->pluck('missing_data')
            ->merge(collect($unsupportedSources)->map(fn (array $source): string => 'fuente interna/documento/registro para '.$source['reference']))
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        $structured['answer'] = trim((string) ($structured['answer'] ?? ''));

        if ($missing !== []) {
            $structured['answer'] .= "\n\nDato faltante: no encontre evidencia interna suficiente para confirmar ".implode('; ', $missing).'.';
        }

        $keyPoints = array_values(array_filter((array) ($structured['key_points'] ?? []), 'is_scalar'));
        $keyPoints[] = 'Las afirmaciones sin respaldo interno deben tratarse como inferencia o quedar pendientes de validacion.';
        $structured['key_points'] = array_values(array_unique(array_map('strval', $keyPoints)));

        $nextSteps = array_values(array_filter((array) ($structured['next_steps'] ?? []), 'is_scalar'));
        $nextSteps[] = 'Validar el dato faltante en base de datos, documento indexado o registro operativo antes de ejecutarlo.';
        $structured['next_steps'] = array_values(array_unique(array_map('strval', $nextSteps)));

        return $structured;
    }

    private function missingDataFor(string $sentence): string
    {
        $normalized = $this->normalize($sentence);
        $missing = [];

        foreach ([
            'responsable' => ['responsable', 'tecnico', 'asignado', 'asignada'],
            'fecha' => ['fecha', 'hoy', 'ayer', 'manana'],
            'costo/SKU/refaccion' => ['costo', 'precio', 'mxn', 'sku', 'refaccion'],
            'estado/plan' => ['estado', 'plan', 'aprobado', 'aprobada', 'actividad'],
            'equipo/componente' => ['lavadora', 'pasteurizadora', 'linea', 'reductor', 'componente'],
            'historial' => ['historial', 'historico', 'evento', 'orden'],
        ] as $label => $terms) {
            foreach ($terms as $term) {
                if (str_contains($normalized, $term)) {
                    $missing[] = $label;
                    break;
                }
            }
        }

        return $missing === []
            ? 'evidencia interna de la afirmacion critica'
            : implode(', ', array_unique($missing));
    }

    private function capConfidence(?float $confidence, float $cap): ?float
    {
        if ($confidence === null) {
            return null;
        }

        return round(min($confidence, max(0.0, min(1.0, $cap))), 4);
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return str_replace(['l -', 'p -'], ['l-', 'p-'], $value);
    }
}
