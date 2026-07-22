<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class OperationsAssistantService
{
    public function __construct(
        private readonly AiProviderInterface $aiProvider,
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly AssistantKnowledgeSearchService $knowledgeSearch,
        private readonly OperationsPlatformContextService $platformContext
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<string, mixed>  $pageContext
     * @return array{content: string, metadata: array<string, mixed>}
     */
    public function reply(User $user, string $message, array $history = [], array $pageContext = []): array
    {
        $question = $this->sanitizer->sanitizeText($message, 1600);

        if ($question === '') {
            return [
                'content' => 'No recibi una pregunta valida. Intenta escribirla de nuevo con un poco mas de detalle.',
                'metadata' => ['fallback' => true],
            ];
        }

        $safePageContext = $this->sanitizePageContext($pageContext);
        $conversation = $this->sanitizeHistory($history);
        $platformContext = $this->buildPlatformContext($user, $question, $safePageContext);

        if ($deterministicReply = $this->resolveDeterministicReply($question, $platformContext)) {
            return $deterministicReply;
        }

        if (!(bool) config('maintenance_ai.enabled', false)) {
            return [
                'content' => 'El asistente no esta disponible porque la IA del sistema esta deshabilitada en este momento.',
                'metadata' => ['fallback' => true, 'disabled' => true],
            ];
        }

        $knowledge = $this->knowledgeSearch->search($question, $safePageContext);

        $payload = [
            'system_prompt' => $this->systemPrompt(),
            'user_prompt' => $this->userPrompt($user, $question, $conversation, $safePageContext, $knowledge, $platformContext),
            'schema_name' => 'operations_assistant_reply',
            'schema' => $this->schema(),
        ];

        $chatModel = trim((string) config('maintenance_ai.chat.model', ''));

        if ($chatModel !== '') {
            $payload['model'] = $chatModel;
        }

        $response = $this->aiProvider->generateStructuredActionPlan($payload);
        $structured = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'content' => $this->composeMessage($structured),
            'metadata' => [
                'provider' => Arr::get($response, 'meta.provider'),
                'model' => Arr::get($response, 'meta.model'),
                'confidence' => Arr::get($structured, 'confidence'),
                'sources' => Arr::get($structured, 'sources', []),
                'page_context' => $safePageContext,
                'knowledge_count' => count($knowledge),
                'platform_query_matches' => count($platformContext['query_matches'] ?? []),
                'platform_recent_evidence' => count($platformContext['recent_evidence'] ?? []),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<string, mixed>  $pageContext
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $platformContext
     */
    private function userPrompt(User $user, string $question, array $history, array $pageContext, array $knowledge, array $platformContext): string
    {
        $payload = [
            'user' => [
                'name' => $user->name,
                'role' => $user->role_label,
            ],
            'question' => $question,
            'page_context' => $pageContext,
            'recent_conversation' => $history,
            'relevant_context' => $knowledge,
            'platform_context' => $platformContext,
            'instructions' => [
                'Responder en espanol.',
                'Ser concreto, practico y confiable.',
                'Usar solo el contexto dado para afirmar datos especificos del sistema o del mantenimiento.',
                'Tomar como prioridad el bloque platform_context para responder con vision global de la plataforma y no solo de la pagina actual.',
                'Priorizar module_insights cuando exista, porque resume comparativos, rankings y estados actuales listos para responder.',
                'Si la pregunta pide maximos, minimos, ranking o comparativos, usar primero los resumenes comparativos presentes en platform_context.',
                'Si falta informacion, decirlo claramente sin inventar.',
                'Cuando aplique, entregar pasos accionables punto por punto.',
            ],
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'Actua como un asistente interno de mantenimiento y operacion para el sistema LEGADO AB FENIX.',
            'Debes ayudar con dudas sobre planes de accion, modulos del sistema, lavadoras, pasteurizadoras, etiquetadoras, componentes, documentos tecnicos, evidencias y seguimiento operativo.',
            'Responde en espanol con tono profesional y directo.',
            'El bloque platform_context contiene contexto vivo de toda la plataforma, incluyendo modulos, tablas relevantes, resumen de base de datos, actividad reciente, coincidencias por consulta y evidencias con fotos.',
            'No te limites a la pagina actual si platform_context aporta datos mas amplios y vigentes.',
            'Si existe module_insights, usalo como fuente primaria para rankings, comparativos, tendencias y estado actual de componentes o lineas.',
            'Si platform_context ya incluye un ranking, panorama o comparativo actual, respondelo directamente sin decir que faltan datos.',
            'No inventes estados de equipos, costos, responsables ni trabajos ejecutados.',
            'Si el contexto no alcanza para responder con certeza, dilo explicitamente y sugiere el siguiente dato o modulo a revisar.',
            'Evita explicaciones largas. Prioriza claridad y utilidad operativa.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    private function buildPlatformContext(User $user, string $question, array $pageContext): array
    {
        try {
            return $this->platformContext->build($user, $question, $pageContext);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'generated_at' => now()->toIso8601String(),
                'error' => true,
                'message' => 'No fue posible construir el contexto global de la plataforma en este intento.',
                'page_context' => $pageContext,
                'query_matches' => [],
                'recent_evidence' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'answer' => [
                    'type' => 'string',
                ],
                'key_points' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'maxItems' => 4,
                ],
                'next_steps' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'maxItems' => 3,
                ],
                'sources' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'reference' => ['type' => 'string'],
                        ],
                        'required' => ['type', 'reference'],
                    ],
                    'maxItems' => 4,
                ],
                'confidence' => [
                    'type' => 'number',
                ],
            ],
            'required' => ['answer', 'key_points', 'next_steps', 'sources', 'confidence'],
        ];
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    private function composeMessage(array $structured): string
    {
        $answer = $this->sanitizer->sanitizeText((string) ($structured['answer'] ?? ''), 1200);
        $keyPoints = $this->sanitizeStringList($structured['key_points'] ?? [], 220);
        $nextSteps = $this->sanitizeStringList($structured['next_steps'] ?? [], 220);

        $parts = array_filter([$answer]);

        if ($keyPoints !== []) {
            $parts[] = "Puntos clave:\n- " . implode("\n- ", $keyPoints);
        }

        if ($nextSteps !== []) {
            $parts[] = "Siguiente paso:\n- " . implode("\n- ", $nextSteps);
        }

        return trim(implode("\n\n", $parts)) !== ''
            ? trim(implode("\n\n", $parts))
            : 'No pude construir una respuesta util con el contexto actual. Intenta preguntar de otra forma.';
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function resolveDeterministicReply(string $question, array $platformContext): ?array
    {
        $normalized = Str::lower(Str::ascii($question));

        if (($reply = $this->replyForHighestElongation($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForMostDamagedWasher($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForMostDamagedComponents($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForSpecificWasherComponent($normalized, $platformContext)) !== null) {
            return $reply;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForHighestElongation(string $question, array $platformContext): ?array
    {
        if (!str_contains($question, 'elongacion')) {
            return null;
        }

        if (!(
            str_contains($question, 'mayor')
            || str_contains($question, 'mas alto')
            || str_contains($question, 'mas alta')
            || str_contains($question, 'maximo')
            || str_contains($question, 'maxima')
        )) {
            return null;
        }

        $panorama = data_get($platformContext, 'module_insights.lavadora.elongacion_panorama');
        $highest = is_array($panorama) ? ($panorama['highest_current'] ?? null) : null;

        if (!is_array($highest)) {
            return null;
        }

        $ranking = collect($panorama['current_by_line'] ?? [])
            ->take(3)
            ->map(fn (array $item): string => ($item['linea'] ?? 'Sin linea') . ': ' . number_format((float) ($item['max_porcentaje'] ?? 0), 2, '.', '') . '%')
            ->all();

        return $this->deterministicResponse(
            'La cadena de lavadora con mayor porcentaje de elongacion actual es '
                . ($highest['linea'] ?? 'Sin linea')
                . ' con '
                . number_format((float) ($highest['max_porcentaje'] ?? 0), 2, '.', '')
                . '% en el lado '
                . ($highest['critical_side'] ?? 'critico')
                . ', segun la ultima medicion registrada el '
                . ($highest['recorded_at'] ?? 'sin fecha')
                . '.',
            array_filter([
                'Bombas: ' . number_format((float) ($highest['bombas_porcentaje'] ?? 0), 2, '.', '') . '% | Vapor: ' . number_format((float) ($highest['vapor_porcentaje'] ?? 0), 2, '.', '') . '%.',
                isset($highest['estado_detallado']) ? 'Estado actual: ' . $highest['estado_detallado'] . '.' : null,
                $ranking !== [] ? 'Ranking actual: ' . implode(' | ', $ranking) . '.' : null,
                'Umbrales configurados: preventivo '
                    . number_format((float) ($panorama['warning_threshold'] ?? 0), 2, '.', '')
                    . '% y critico '
                    . number_format((float) ($panorama['critical_threshold'] ?? 0), 2, '.', '')
                    . '%.',
            ]),
            [
                'Si quieres, tambien te doy el pico historico de elongacion y el ranking completo por linea.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'lavadora.elongacion_panorama'],
            ],
            0.98
        );
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForMostDamagedComponents(string $question, array $platformContext): ?array
    {
        if (!str_contains($question, 'component')) {
            return null;
        }

        if (str_contains($question, 'lavadora') && (str_contains($question, 'cual') || str_contains($question, 'que lavadora'))) {
            return null;
        }

        if (!(str_contains($question, 'dan') || str_contains($question, 'desgast') || str_contains($question, 'revision'))) {
            return null;
        }

        $periods = data_get($platformContext, 'module_insights.lavadora.damage_periods');

        if (!is_array($periods) || $periods === []) {
            return null;
        }

        $requestedPeriods = [];

        if (str_contains($question, 'semana')) {
            $requestedPeriods[] = 'week';
        }

        if (str_contains($question, 'mes')) {
            $requestedPeriods[] = 'month';
        }

        if (str_contains($question, 'ano') || str_contains($question, 'anio')) {
            $requestedPeriods[] = 'year';
        }

        if ($requestedPeriods === []) {
            $requestedPeriods = ['week', 'month', 'year'];
        }

        $keyPoints = [];

        foreach (array_values(array_unique($requestedPeriods)) as $periodKey) {
            $period = $periods[$periodKey] ?? null;

            if (!is_array($period)) {
                continue;
            }

            $topComponents = collect($period['top_components'] ?? [])
                ->take(3)
                ->map(fn (array $item): string => ($item['componente'] ?? 'Sin componente') . ' (' . (int) ($item['total'] ?? 0) . ')')
                ->all();

            $keyPoints[] = ($period['label'] ?? ucfirst($periodKey))
                . ': '
                . ($topComponents !== [] ? implode(' | ', $topComponents) : 'sin hallazgos de dano registrados');
        }

        if ($keyPoints === []) {
            return null;
        }

        return $this->deterministicResponse(
            'Ya tengo el comparativo de componentes con mas hallazgos de dano registrados en lavadoras para los periodos consultados.',
            $keyPoints,
            [
                'Si quieres, te lo desgloso por lavadora, por estado exacto o por componente con fechas absolutas.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'lavadora.damage_periods'],
            ],
            0.96
        );
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForMostDamagedWasher(string $question, array $platformContext): ?array
    {
        if (!str_contains($question, 'lavadora')) {
            return null;
        }

        if (!str_contains($question, 'component')) {
            return null;
        }

        if (!(str_contains($question, 'dan') || str_contains($question, 'desgast') || str_contains($question, 'mas'))) {
            return null;
        }

        $highestLine = data_get($platformContext, 'module_insights.lavadora.current_damage_by_line.highest_line');

        if (!is_array($highestLine)) {
            return null;
        }

        $components = collect($highestLine['top_components'] ?? [])
            ->take(4)
            ->map(fn (array $item): string => ($item['componente'] ?? 'Sin componente') . ' (' . (int) ($item['total'] ?? 0) . ')')
            ->all();

        return $this->deterministicResponse(
            'La lavadora con mas componentes actualmente en estado problematico es '
                . ($highestLine['linea'] ?? 'Sin linea')
                . ', con '
                . (int) ($highestLine['problematic_components'] ?? 0)
                . ' componentes comprometidos segun el ultimo analisis disponible por componente/reductor/lado.',
            array_filter([
                'Componentes criticos dentro de esa lavadora: ' . (int) ($highestLine['critical_components'] ?? 0) . '.',
                $components !== [] ? 'Componentes mas repetidos: ' . implode(' | ', $components) . '.' : null,
                isset($highestLine['latest_review_date']) ? 'Ultima revision considerada: ' . $highestLine['latest_review_date'] . '.' : null,
            ]),
            [
                'Si quieres, te doy tambien el ranking actual completo de todas las lavadoras.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'lavadora.current_damage_by_line'],
            ],
            0.97
        );
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForSpecificWasherComponent(string $question, array $platformContext): ?array
    {
        $asksSpecificStatus = str_contains($question, 'como se encuentra')
            || str_contains($question, 'como esta')
            || str_contains($question, 'estado')
            || str_contains($question, 'condicion')
            || str_contains($question, 'ultimo estado')
            || str_contains($question, 'revision actual');

        if (!$asksSpecificStatus) {
            return null;
        }

        $matches = data_get($platformContext, 'module_insights.lavadora.targeted_component_lookup.matches', []);

        if (!is_array($matches) || $matches === []) {
            return null;
        }

        $primary = $matches[0];
        $secondary = collect($matches)->skip(1)->take(3)->map(function (array $item): string {
            return implode(' | ', array_filter([
                $item['linea'] ?? null,
                $item['componente'] ?? null,
                $item['reductor'] ?? null,
                $item['lado'] ?? null,
                $item['estado'] ?? null,
                $item['fecha_analisis'] ?? null,
            ]));
        })->all();

        return $this->deterministicResponse(
            'El ultimo estado encontrado para '
                . ($primary['componente'] ?? 'el componente consultado')
                . ' en '
                . ($primary['linea'] ?? 'la linea indicada')
                . ($primary['reductor'] ? ', ' . $primary['reductor'] : '')
                . ' es "'
                . ($primary['estado'] ?? 'Sin estado')
                . '", con revision del '
                . ($primary['fecha_analisis'] ?? 'sin fecha')
                . '.',
            array_filter([
                $primary['lado'] ? 'Lado: ' . $primary['lado'] . '.' : null,
                $primary['actividad'] ? 'Actividad registrada: ' . $primary['actividad'] . '.' : null,
                isset($primary['evidencias']) ? 'Evidencias registradas: ' . (int) $primary['evidencias'] . '.' : null,
                $secondary !== [] ? 'Coincidencias adicionales: ' . implode(' || ', $secondary) . '.' : null,
            ]),
            [
                'Si quieres, te doy el historial completo de ese componente y no solo el ultimo estado.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'lavadora.targeted_component_lookup'],
            ],
            0.95
        );
    }

    /**
     * @param  array<int, string>  $keyPoints
     * @param  array<int, string>  $nextSteps
     * @param  array<int, array<string, mixed>>  $sources
     * @return array{content: string, metadata: array<string, mixed>}
     */
    private function deterministicResponse(
        string $answer,
        array $keyPoints = [],
        array $nextSteps = [],
        array $sources = [],
        float $confidence = 0.95
    ): array {
        $content = trim($answer);

        if ($keyPoints !== []) {
            $content .= "\n\nPuntos clave:\n- " . implode("\n- ", $keyPoints);
        }

        if ($nextSteps !== []) {
            $content .= "\n\nSiguiente paso:\n- " . implode("\n- ", $nextSteps);
        }

        return [
            'content' => $content,
            'metadata' => [
                'provider' => 'platform-insights',
                'model' => 'deterministic-platform-context',
                'confidence' => $confidence,
                'sources' => $sources,
                'platform_facts' => true,
            ],
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    private function sanitizeStringList(array $items, int $maxLength): array
    {
        return array_values(array_filter(array_map(function ($item) use ($maxLength): ?string {
            if (!is_scalar($item)) {
                return null;
            }

            $sanitized = $this->sanitizer->sanitizeText((string) $item, $maxLength);

            return $sanitized !== '' ? $sanitized : null;
        }, $items)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, array<string, string>>
     */
    private function sanitizeHistory(array $history): array
    {
        $limit = max(1, (int) config('maintenance_ai.chat.history_window', 8));

        return collect($history)
            ->take(-$limit)
            ->map(function (array $entry): ?array {
                $role = Str::lower(trim((string) ($entry['role'] ?? '')));

                if (!in_array($role, ['user', 'assistant'], true)) {
                    return null;
                }

                $content = $this->sanitizer->sanitizeText((string) ($entry['content'] ?? ''), 500);

                if ($content === '') {
                    return null;
                }

                return [
                    'role' => $role,
                    'content' => $content,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    private function sanitizePageContext(array $pageContext): array
    {
        return array_filter([
            'page_title' => $this->sanitizer->sanitizeText((string) ($pageContext['page_title'] ?? ''), 180),
            'current_url' => $this->sanitizer->sanitizeText((string) ($pageContext['current_url'] ?? ''), 300),
            'current_path' => $this->sanitizer->sanitizeText((string) ($pageContext['current_path'] ?? ''), 180),
            'module' => $this->sanitizeModule($pageContext['module'] ?? null),
            'section' => $this->sanitizer->sanitizeText((string) ($pageContext['section'] ?? ''), 180),
            'entity_label' => $this->sanitizer->sanitizeText((string) ($pageContext['entity_label'] ?? ''), 180),
            'record_id' => isset($pageContext['record_id']) && is_numeric($pageContext['record_id'])
                ? (int) $pageContext['record_id']
                : null,
        ], static fn ($value): bool => !($value === null || $value === ''));
    }

    private function sanitizeModule(mixed $module): ?string
    {
        $normalized = Str::lower(trim((string) $module));

        return in_array($normalized, [
            User::MODULE_LAVADORA,
            User::MODULE_ETIQUETADORA,
            User::MODULE_PASTEURIZADORA,
        ], true) ? $normalized : null;
    }
}
