<?php

namespace App\Services\Maintenance;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AssistantWebSearchService
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly PromptSafetySanitizer $sanitizer
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $platformContext
     * @param  array<string, mixed>  $technicalContext
     * @return array<string, mixed>
     */
    public function searchIfNeeded(string $question, array $knowledge, array $platformContext, array $technicalContext): array
    {
        if (!(bool) config('maintenance_ai.web_search.enabled', false)) {
            return $this->disabledContext('disabled');
        }

        if ((string) config('maintenance_ai.web_search.mode', 'hybrid') !== 'hybrid') {
            return $this->disabledContext('mode_not_hybrid');
        }

        $decision = $this->shouldSearch($question, $knowledge, $platformContext, $technicalContext);

        if (!($decision['search'] ?? false)) {
            return [
                'enabled' => true,
                'used' => false,
                'reason' => $decision['reason'] ?? 'internal_context_available',
                'provider' => null,
                'query' => $this->sanitizer->sanitizeText($question, 220),
                'summary' => null,
                'sources' => [],
                'error' => null,
            ];
        }

        $lastError = null;

        foreach ($this->providerChain() as $provider) {
            try {
                $result = match ($provider) {
                    'openai' => $this->searchWithOpenAi($question),
                    'gemini' => $this->searchWithGemini($question),
                    default => null,
                };

                if (is_array($result) && trim((string) ($result['summary'] ?? '')) !== '') {
                    return array_merge($result, [
                        'enabled' => true,
                        'used' => true,
                        'reason' => $decision['reason'] ?? 'web_context_needed',
                    ]);
                }
            } catch (ConnectionException $exception) {
                report($exception);
                $lastError = GeminiRequestSupport::publicConnectionMessage($exception);
            } catch (Throwable $exception) {
                report($exception);
                $lastError = $exception->getMessage();
            }
        }

        return [
            'enabled' => true,
            'used' => false,
            'reason' => $decision['reason'] ?? 'web_context_needed',
            'provider' => null,
            'query' => $this->sanitizer->sanitizeText($question, 220),
            'summary' => null,
            'sources' => [],
            'error' => $this->sanitizer->sanitizeText((string) $lastError, 220),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $platformContext
     * @param  array<string, mixed>  $technicalContext
     * @return array{search: bool, reason: string}
     */
    private function shouldSearch(string $question, array $knowledge, array $platformContext, array $technicalContext): array
    {
        $normalized = Str::lower(Str::ascii($question));

        if ($this->explicitlyRequestsWeb($normalized)) {
            return ['search' => true, 'reason' => 'explicit_web_request'];
        }

        if ($this->looksTimeSensitive($normalized)) {
            return ['search' => true, 'reason' => 'time_sensitive_request'];
        }

        if ($this->asksForExternalTechnicalSource($normalized)) {
            return ['search' => true, 'reason' => 'external_technical_source_request'];
        }

        if ($this->looksDiagnosticReferenceRequest($normalized)) {
            return ['search' => true, 'reason' => 'diagnostic_reference_request'];
        }

        if ($this->internalContextCount($knowledge, $platformContext, $technicalContext) < max(1, (int) config('maintenance_ai.web_search.min_internal_items_before_skip', 1))
            && $this->looksTechnicalOrOperational($normalized)) {
            return ['search' => true, 'reason' => 'insufficient_internal_context'];
        }

        return ['search' => false, 'reason' => 'internal_context_available'];
    }

    private function explicitlyRequestsWeb(string $normalized): bool
    {
        return str_contains($normalized, 'web')
            || str_contains($normalized, 'internet')
            || str_contains($normalized, 'google')
            || str_contains($normalized, 'busca en linea')
            || str_contains($normalized, 'buscar en linea')
            || str_contains($normalized, 'busca afuera')
            || str_contains($normalized, 'fuentes externas');
    }

    private function looksTimeSensitive(string $normalized): bool
    {
        return str_contains($normalized, 'actual')
            || str_contains($normalized, 'reciente')
            || str_contains($normalized, 'hoy')
            || str_contains($normalized, 'ultimo')
            || str_contains($normalized, 'ultima')
            || str_contains($normalized, '2026')
            || str_contains($normalized, 'precio actualizado')
            || str_contains($normalized, 'costo actualizado');
    }

    private function asksForExternalTechnicalSource(string $normalized): bool
    {
        return str_contains($normalized, 'fabricante')
            || str_contains($normalized, 'ficha tecnica')
            || str_contains($normalized, 'manual oficial')
            || str_contains($normalized, 'catalogo')
            || str_contains($normalized, 'norma')
            || str_contains($normalized, 'estandar')
            || str_contains($normalized, 'compatibilidad')
            || str_contains($normalized, 'equivalente');
    }

    private function looksDiagnosticReferenceRequest(string $normalized): bool
    {
        $asksCauseOrDiagnosis = str_contains($normalized, 'causa')
            || str_contains($normalized, 'causar')
            || str_contains($normalized, 'diagnostico')
            || str_contains($normalized, 'por que')
            || str_contains($normalized, 'porque')
            || str_contains($normalized, 'que puede provocar')
            || str_contains($normalized, 'que provoca');

        $mentionsLeak = str_contains($normalized, 'fuga')
            || str_contains($normalized, 'fugas')
            || str_contains($normalized, 'tirando aceite')
            || str_contains($normalized, 'pierde aceite')
            || str_contains($normalized, 'perdida de aceite');

        return $mentionsLeak
            && $asksCauseOrDiagnosis
            && (
                str_contains($normalized, 'aceite')
                || str_contains($normalized, 'reductor')
                || str_contains($normalized, 'lubric')
            );
    }

    private function looksTechnicalOrOperational(string $normalized): bool
    {
        return str_contains($normalized, 'reductor')
            || str_contains($normalized, 'servo')
            || str_contains($normalized, 'cadena')
            || str_contains($normalized, 'lavadora')
            || str_contains($normalized, 'pasteur')
            || str_contains($normalized, 'aceite')
            || str_contains($normalized, 'lubric')
            || str_contains($normalized, 'refaccion')
            || str_contains($normalized, 'sku')
            || str_contains($normalized, 'falla')
            || str_contains($normalized, 'diagnostico')
            || str_contains($normalized, 'solucion');
    }

    /**
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $platformContext
     * @param  array<string, mixed>  $technicalContext
     */
    private function internalContextCount(array $knowledge, array $platformContext, array $technicalContext): int
    {
        return count($knowledge)
            + count((array) ($platformContext['query_matches'] ?? []))
            + (int) data_get($technicalContext, 'coverage.historical_records_count', 0)
            + (int) data_get($technicalContext, 'coverage.technical_sources_count', 0);
    }

    /**
     * @return array<int, string>
     */
    private function providerChain(): array
    {
        $providers = [];

        foreach ([
            (string) config('maintenance_ai.web_search.provider', config('maintenance_ai.provider', 'gemini')),
            (string) config('maintenance_ai.web_search.fallback_provider', ''),
        ] as $provider) {
            $provider = Str::lower(trim($provider));

            if (!in_array($provider, ['gemini', 'openai'], true) || in_array($provider, $providers, true)) {
                continue;
            }

            if ($this->hasConfiguredProvider($provider)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    private function hasConfiguredProvider(string $provider): bool
    {
        $apiKey = trim((string) data_get(config('maintenance_ai'), 'providers.' . $provider . '.api_key', ''));
        $baseUrl = trim((string) data_get(config('maintenance_ai'), 'providers.' . $provider . '.base_url', ''));

        return $apiKey !== '' && $baseUrl !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function searchWithOpenAi(string $question): array
    {
        $config = config('maintenance_ai.providers.openai');
        $request = [
            'model' => config('maintenance_ai.web_search.openai_model') ?: ($config['model'] ?? 'gpt-5.6'),
            'tools' => [
                array_filter([
                    'type' => 'web_search',
                    'search_context_size' => (string) config('maintenance_ai.web_search.context_size', 'low'),
                ]),
            ],
            'tool_choice' => 'auto',
            'include' => ['web_search_call.action.sources'],
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'Busca informacion web vigente y responde en espanol. Devuelve datos tecnicos verificables y no inventes. Incluye solo informacion util para complementar una base interna de mantenimiento.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->webQuery($question),
                ],
            ],
        ];

        $response = $this->http
            ->timeout((int) config('maintenance_ai.timeout', 30))
            ->retry((int) config('maintenance_ai.max_retries', 2), 500)
            ->withToken((string) ($config['api_key'] ?? ''))
            ->acceptJson()
            ->post(rtrim((string) ($config['base_url'] ?? ''), '/') . '/responses', $request);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $json = $response->json();

        return [
            'provider' => 'openai',
            'query' => $this->sanitizer->sanitizeText($question, 220),
            'summary' => $this->trimSummary($this->extractOpenAiOutputText(is_array($json) ? $json : [])),
            'sources' => $this->extractOpenAiSources(is_array($json) ? $json : []),
            'raw_meta' => [
                'model' => $request['model'],
                'usage' => Arr::get($json, 'usage', []),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchWithGemini(string $question): array
    {
        $config = (array) config('maintenance_ai.providers.gemini');
        $model = GeminiRequestSupport::normalizeModelName((string) (config('maintenance_ai.web_search.gemini_model') ?: ($config['model'] ?? 'gemini-3.6-flash')));
        $endpoint = GeminiRequestSupport::endpoint($config, 'interactions');
        $startedAt = microtime(true);
        $apiKey = trim((string) ($config['api_key'] ?? ''));

        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        try {
            $response = $this->http
                ->connectTimeout(GeminiRequestSupport::connectionTimeout())
                ->timeout(GeminiRequestSupport::totalTimeout())
                ->retry((int) config('maintenance_ai.max_retries', 2), 500, function ($exception): bool {
                    return !($exception instanceof ConnectionException
                        && GeminiRequestSupport::isDnsResolutionError($exception));
                })
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->acceptJson()
                ->post($endpoint, [
                    'model' => $model,
                    'input' => $this->webQuery($question),
                    'tools' => [
                        ['type' => 'google_search'],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning(
                'Gemini connection failed.',
                GeminiRequestSupport::logContext('assistant_web_search', $model, $endpoint, $exception, $startedAt)
            );

            throw $exception;
        }

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $json = $response->json();

        return [
            'provider' => 'gemini',
            'query' => $this->sanitizer->sanitizeText($question, 220),
            'summary' => $this->trimSummary($this->extractGeminiOutputText(is_array($json) ? $json : [])),
            'sources' => $this->extractGeminiSources(is_array($json) ? $json : []),
            'raw_meta' => [
                'model' => $model,
            ],
        ];
    }

    private function webQuery(string $question): string
    {
        return implode("\n", [
            'Consulta del usuario: ' . $this->sanitizer->sanitizeText($question, 500),
            'Prioridad: informacion tecnica, vigente y verificable.',
            'Si hay datos de fabricante, manual oficial, ficha tecnica, norma o precio actual, priorizalos.',
            'No respondas con datos internos del sistema; eso se agregara aparte por la aplicacion.',
        ]);
    }

    private function trimSummary(?string $summary): string
    {
        return $this->sanitizer->sanitizeText((string) $summary, max(400, (int) config('maintenance_ai.web_search.max_context_chars', 2400)));
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function extractOpenAiOutputText(array $json): ?string
    {
        if (is_string($json['output_text'] ?? null) && trim((string) $json['output_text']) !== '') {
            return (string) $json['output_text'];
        }

        foreach ((array) ($json['output'] ?? []) as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                if (is_string($content['text'] ?? null) && trim((string) $content['text']) !== '') {
                    return (string) $content['text'];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<int, array<string, string>>
     */
    private function extractOpenAiSources(array $json): array
    {
        $sources = [];

        foreach ((array) ($json['output'] ?? []) as $item) {
            $actionSources = Arr::get($item, 'action.sources', []);

            foreach (is_array($actionSources) ? $actionSources : [] as $source) {
                if (!is_array($source)) {
                    continue;
                }

                $url = (string) ($source['url'] ?? '');

                if ($url === '') {
                    continue;
                }

                $sources[] = [
                    'type' => 'web',
                    'reference' => (string) ($source['title'] ?? $url),
                    'url' => $url,
                ];
            }

            foreach ((array) ($item['content'] ?? []) as $content) {
                foreach ((array) ($content['annotations'] ?? []) as $annotation) {
                    $url = (string) ($annotation['url'] ?? '');

                    if ($url === '') {
                        continue;
                    }

                    $sources[] = [
                        'type' => 'web',
                        'reference' => (string) ($annotation['title'] ?? $url),
                        'url' => $url,
                    ];
                }
            }
        }

        return $this->uniqueSources($sources);
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function extractGeminiOutputText(array $json): ?string
    {
        foreach ((array) ($json['steps'] ?? []) as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            foreach ((array) ($step['content'] ?? []) as $content) {
                if (is_string($content['text'] ?? null) && trim((string) $content['text']) !== '') {
                    return (string) $content['text'];
                }
            }
        }

        $text = Arr::get($json, 'candidates.0.content.parts.0.text');

        return is_string($text) ? $text : null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<int, array<string, string>>
     */
    private function extractGeminiSources(array $json): array
    {
        $sources = [];

        foreach ((array) ($json['steps'] ?? []) as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            foreach ((array) ($step['content'] ?? []) as $content) {
                foreach ((array) ($content['annotations'] ?? []) as $annotation) {
                    $url = (string) ($annotation['url'] ?? '');

                    if ($url === '') {
                        continue;
                    }

                    $sources[] = [
                        'type' => 'web',
                        'reference' => (string) ($annotation['title'] ?? $url),
                        'url' => $url,
                    ];
                }
            }
        }

        foreach ((array) Arr::get($json, 'candidates.0.groundingMetadata.groundingChunks', []) as $chunk) {
            $web = is_array($chunk) ? ($chunk['web'] ?? []) : [];
            $url = is_array($web) ? (string) ($web['uri'] ?? '') : '';

            if ($url === '') {
                continue;
            }

            $sources[] = [
                'type' => 'web',
                'reference' => (string) ($web['title'] ?? $url),
                'url' => $url,
            ];
        }

        return $this->uniqueSources($sources);
    }

    /**
     * @param  array<int, array<string, string>>  $sources
     * @return array<int, array<string, string>>
     */
    private function uniqueSources(array $sources): array
    {
        return collect($sources)
            ->filter(fn (array $source): bool => trim((string) ($source['url'] ?? '')) !== '')
            ->unique(fn (array $source): string => (string) $source['url'])
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function disabledContext(string $reason): array
    {
        return [
            'enabled' => false,
            'used' => false,
            'reason' => $reason,
            'provider' => null,
            'query' => null,
            'summary' => null,
            'sources' => [],
            'error' => null,
        ];
    }
}
