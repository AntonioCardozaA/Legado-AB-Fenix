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
        private readonly AssistantAnalyticsArtifactService $analyticsArtifacts,
        private readonly AssistantKnowledgeSearchService $knowledgeSearch,
        private readonly OperationsPlatformContextService $platformContext,
        private readonly WasherTechnicalContextRetriever $washerTechnicalContext,
        private readonly PasteurizadoraTechnicalContextRetriever $pasteurizadoraTechnicalContext,
        private readonly AiInteractionLogger $interactionLogger
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<string, mixed>  $pageContext
     * @return array{content: string, metadata: array<string, mixed>}
     */
    public function reply(User $user, string $message, array $history = [], array $pageContext = []): array
    {
        $question = $this->sanitizer->sanitizeText($message, 4000);

        if ($question === '') {
            return [
                'content' => 'No recibi una pregunta valida. Intenta escribirla de nuevo con un poco mas de detalle.',
                'metadata' => ['fallback' => true],
            ];
        }

        $safePageContext = $this->sanitizePageContext($pageContext);
        $conversation = $this->sanitizeHistory($history);

        if ($this->analyticsArtifacts->looksLikeArtifactRequest($question)) {
            if (!(bool) config('maintenance_ai.enabled', false)) {
                $this->interactionLogger->fallback($user, 'assistant_chat', [
                    'input_chars' => mb_strlen($question),
                    'metadata' => [
                        'mode' => 'analytics_artifact_disabled',
                    ],
                ]);

                return [
                    'content' => 'El asistente no esta disponible porque la IA del sistema esta deshabilitada en este momento.',
                    'metadata' => ['fallback' => true, 'disabled' => true],
                ];
            }

            if ($artifactReply = $this->analyticsArtifacts->tryGenerate($user, $question, $safePageContext)) {
                $this->interactionLogger->success($user, 'assistant_chat', [
                    'meta' => [
                        'provider' => data_get($artifactReply, 'metadata.provider'),
                        'model' => data_get($artifactReply, 'metadata.model'),
                    ],
                ], [
                    'input_chars' => mb_strlen($question),
                    'output_chars' => mb_strlen((string) $artifactReply['content']),
                    'metadata' => [
                        'mode' => 'analytics_artifact',
                        'artifacts_count' => count((array) data_get($artifactReply, 'metadata.artifacts', [])),
                        'dataset' => data_get($artifactReply, 'metadata.intent.dataset'),
                        'outputs' => data_get($artifactReply, 'metadata.intent.outputs', []),
                        'page_context' => $safePageContext,
                    ],
                ]);

                return $artifactReply;
            }
        }

        $platformContext = $this->buildPlatformContext($user, $question, $safePageContext);

        if ($deterministicReply = $this->resolveDeterministicReply($question, $platformContext)) {
            $this->interactionLogger->fallback($user, 'assistant_chat', [
                'provider' => data_get($deterministicReply, 'metadata.provider'),
                'model' => data_get($deterministicReply, 'metadata.model'),
                'input_chars' => mb_strlen($question),
                'output_chars' => mb_strlen((string) $deterministicReply['content']),
                'metadata' => [
                    'mode' => 'deterministic_platform_context',
                    'confidence' => data_get($deterministicReply, 'metadata.confidence'),
                    'sources_count' => count((array) data_get($deterministicReply, 'metadata.sources', [])),
                    'platform_query_matches' => count($platformContext['query_matches'] ?? []),
                    'platform_recent_evidence' => count($platformContext['recent_evidence'] ?? []),
                ],
            ]);

            return $deterministicReply;
        }

        if (!(bool) config('maintenance_ai.enabled', false)) {
            $this->interactionLogger->fallback($user, 'assistant_chat', [
                'input_chars' => mb_strlen($question),
                'metadata' => [
                    'mode' => 'disabled',
                ],
            ]);

            return [
                'content' => 'El asistente no esta disponible porque la IA del sistema esta deshabilitada en este momento.',
                'metadata' => ['fallback' => true, 'disabled' => true],
            ];
        }

        $technicalContext = $this->technicalContextForQuestion($question, $safePageContext, $user);
        $knowledge = $this->knowledgeSearch->search($question, $safePageContext, $user);

        $payload = [
            'system_prompt' => $this->systemPrompt(),
            'user_prompt' => $this->userPrompt($user, $question, $conversation, $safePageContext, $knowledge, $platformContext, $technicalContext),
            'schema_name' => 'operations_assistant_reply',
            'schema' => $this->schema(),
        ];

        $chatModel = trim((string) config('maintenance_ai.chat.model', ''));

        if ($chatModel !== '') {
            $payload['model'] = $chatModel;
        }

        $response = $this->aiProvider->generateStructuredActionPlan($payload);
        $structured = is_array($response['data'] ?? null) ? $response['data'] : [];
        $content = $this->composeMessage($structured);

        $this->interactionLogger->success($user, 'assistant_chat', $response, [
            'input_chars' => mb_strlen($payload['system_prompt'] . $payload['user_prompt']),
            'output_chars' => mb_strlen($content),
            'metadata' => [
                'question_excerpt' => $this->sanitizer->sanitizeText($question, 240),
                'knowledge_count' => count($knowledge),
                'platform_query_matches' => count($platformContext['query_matches'] ?? []),
                'platform_recent_evidence' => count($platformContext['recent_evidence'] ?? []),
                'technical_context_records' => (int) data_get($technicalContext, 'coverage.historical_records_count', 0),
                'technical_context_sources' => (int) data_get($technicalContext, 'coverage.technical_sources_count', 0),
                'page_context' => $safePageContext,
            ],
        ]);

        return [
            'content' => $content,
            'metadata' => [
                'provider' => Arr::get($response, 'meta.provider'),
                'model' => Arr::get($response, 'meta.model'),
                'confidence' => Arr::get($structured, 'confidence'),
                'sources' => Arr::get($structured, 'sources', []),
                'page_context' => $safePageContext,
                'knowledge_count' => count($knowledge),
                'platform_query_matches' => count($platformContext['query_matches'] ?? []),
                'platform_recent_evidence' => count($platformContext['recent_evidence'] ?? []),
                'technical_context_records' => (int) data_get($technicalContext, 'coverage.historical_records_count', 0),
                'technical_context_sources' => (int) data_get($technicalContext, 'coverage.technical_sources_count', 0),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<string, mixed>  $pageContext
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $platformContext
     * @param  array<string, mixed>  $technicalContext
     */
    private function userPrompt(User $user, string $question, array $history, array $pageContext, array $knowledge, array $platformContext, array $technicalContext): string
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
            'technical_recommendation_context' => $technicalContext,
            'instructions' => [
                'Responder en espanol.',
                'Ser concreto, practico y confiable.',
                'Usar solo el contexto dado para afirmar datos especificos del sistema o del mantenimiento.',
                'Tomar como prioridad el bloque platform_context para responder con vision global de la plataforma y no solo de la pagina actual.',
                'Para soluciones tecnicas o diagnosticos, usar technical_recommendation_context como fuente principal de antecedentes, respetando su orden de prioridad.',
                'Diferenciar claramente entre historial de la plataforma, informacion de manuales/base de conocimiento y recomendaciones inferidas.',
                'No inventar antecedentes, reparaciones, resultados, refacciones, costos ni evidencia que no aparezcan en el contexto.',
                'Si no hay antecedentes suficientes, indicarlo y apoyarse primero en technical_sources o relevant_context; si tampoco alcanzan, decir que falta evidencia interna.',
                'Priorizar module_insights cuando exista, porque resume comparativos, rankings y estados actuales listos para responder.',
                'Si module_insights contiene lubrication_lookup o coincidencias de documentos indexados, usarlos antes de concluir que falta informacion.',
                'Si module_insights contiene refaction_cost_lookup, usarlo como fuente principal para responder costos, SKUs, compatibilidad por linea y refacciones de lavadora.',
                'Si module_insights contiene pasteurizadora, usarlo para responder sobre planes, hallazgos, recomendaciones, estado actual, modulos, niveles, lados y componentes de pasteurizadora.',
                'Cuando relevant_context incluya documentos, priorizar fragmentos con document_id, chunk_index y mayor score_breakdown.',
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
            'Si la pregunta pide una solucion tecnica, diagnostico o plan de intervencion, usa technical_recommendation_context antes que conocimiento general.',
            'Respeta el orden de antecedentes del modulo consultado: mismo componente y equipo/posicion, mismo tipo en el mismo equipo, mismo componente en otros equipos, fallas similares, manuales/base de conocimiento.',
            'Separa en la respuesta lo observado en historial, lo encontrado en documentos y lo que recomiendas como inferencia tecnica.',
            'Si module_insights incluye refaction_cost_lookup, tomalo como referencia estructurada valida para responder refacciones, costos unitarios, SKUs, compatibilidad por linea y consumibles de lavadora.',
            'Si module_insights incluye lubrication_lookup, tomalo como una referencia estructurada valida para responder preguntas de aceite, lubricante, litros, SKU y consumibles de lavadora.',
            'Si module_insights incluye pasteurizadora, usalo para planes de accion, analisis, recomendaciones IA y contexto operativo relacionado con pasteurizadora.',
            'Cuando existan coincidencias de documentos de conocimiento indexados, usalas para complementar o confirmar la respuesta operativa.',
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
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    private function technicalContextForQuestion(string $question, array $pageContext, User $user): array
    {
        $normalized = Str::lower(Str::ascii($question));

        if (($pageContext['module'] ?? null) === User::MODULE_PASTEURIZADORA
            || $this->targetsPasteurizadora($normalized, ['page_context' => $pageContext])
        ) {
            return $this->pasteurizadoraTechnicalContext->forQuestion($question, $pageContext, $user);
        }

        return $this->washerTechnicalContext->forQuestion($question, $pageContext, $user);
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

        if ($this->targetsPasteurizadora($normalized, $platformContext)) {
            if (($reply = $this->replyForMostDamagedPasteurizadoraComponents($normalized, $platformContext)) !== null) {
                return $reply;
            }

            if (($reply = $this->replyForMostDamagedPasteurizadora($normalized, $platformContext)) !== null) {
                return $reply;
            }

            if (($reply = $this->replyForSpecificPasteurizadoraComponent($normalized, $platformContext)) !== null) {
                return $reply;
            }

            return null;
        }

        if (($reply = $this->replyForHighestElongation($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForMostDamagedWasher($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForMostDamagedComponents($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForWasherRefactionCosts($normalized, $platformContext)) !== null) {
            return $reply;
        }

        if (($reply = $this->replyForWasherLubrication($normalized, $platformContext)) !== null) {
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
    private function replyForMostDamagedPasteurizadoraComponents(string $question, array $platformContext): ?array
    {
        if (!str_contains($question, 'component')) {
            return null;
        }

        if (str_contains($question, 'cual') || str_contains($question, 'que pasteur')) {
            return null;
        }

        if (!(str_contains($question, 'dan') || str_contains($question, 'desgast') || str_contains($question, 'revision'))) {
            return null;
        }

        $periods = data_get($platformContext, 'module_insights.pasteurizadora.damage_periods');

        if (!is_array($periods) || $periods === []) {
            return null;
        }

        $period = $periods['actual'] ?? $periods['ultimos_30_dias'] ?? reset($periods);

        if (!is_array($period)) {
            return null;
        }

        $components = collect($period['top_components'] ?? [])
            ->take(5)
            ->map(fn (array $item): string => ($item['componente'] ?? 'Sin componente') . ' (' . (int) ($item['total'] ?? 0) . ')')
            ->all();

        if ($components === []) {
            return null;
        }

        return $this->deterministicResponse(
            'Los componentes de Pasteurizadora con mas hallazgos problematicos en '
                . ($period['label'] ?? 'el periodo consultado')
                . ' son: '
                . implode(' | ', $components)
                . '.',
            array_filter([
                'Total de hallazgos considerados: ' . (int) ($period['total'] ?? 0) . '.',
                'Estados incluidos: danado, desgaste moderado/severo y requiere revision.',
                !empty($period['top_lines']) ? 'Lineas con mas hallazgos: ' . implode(' | ', collect($period['top_lines'])->take(3)->map(fn (array $item): string => ($item['linea'] ?? 'Sin linea') . ' (' . (int) ($item['total'] ?? 0) . ')')->all()) . '.' : null,
            ]),
            [
                'Abre el modulo de Pasteurizadora para revisar los registros fuente antes de ejecutar cambios.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'pasteurizadora.damage_periods'],
            ],
            0.96
        );
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForMostDamagedPasteurizadora(string $question, array $platformContext): ?array
    {
        if (!(str_contains($question, 'pasteur') || preg_match('/\bp\s*[-#]?\s*0?\d{1,2}\b/u', $question))) {
            return null;
        }

        if (!(str_contains($question, 'dan') || str_contains($question, 'desgast') || str_contains($question, 'revision') || str_contains($question, 'mas'))) {
            return null;
        }

        $highestLine = data_get($platformContext, 'module_insights.pasteurizadora.current_damage_by_line.highest_line');

        if (!is_array($highestLine)) {
            return null;
        }

        $components = collect($highestLine['top_components'] ?? [])
            ->take(4)
            ->map(fn (array $item): string => ($item['componente'] ?? 'Sin componente') . ' (' . (int) ($item['total'] ?? 0) . ')')
            ->all();

        return $this->deterministicResponse(
            'La pasteurizadora con mas componentes actualmente en estado problematico es '
                . ($highestLine['linea'] ?? 'Sin linea')
                . ', con '
                . (int) ($highestLine['problematic_components'] ?? 0)
                . ' hallazgos activos segun el ultimo analisis disponible por componente, modulo, nivel y lado.',
            array_filter([
                'Hallazgos criticos dentro de esa pasteurizadora: ' . (int) ($highestLine['critical_components'] ?? 0) . '.',
                $components !== [] ? 'Componentes mas repetidos: ' . implode(' | ', $components) . '.' : null,
                isset($highestLine['latest_review_date']) ? 'Ultima revision considerada: ' . $highestLine['latest_review_date'] . '.' : null,
            ]),
            [
                'Revisa las sugerencias IA pendientes de Pasteurizadora si necesitas convertir estos hallazgos en plan operativo.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'pasteurizadora.current_damage_by_line'],
            ],
            0.97
        );
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForSpecificPasteurizadoraComponent(string $question, array $platformContext): ?array
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

        $matches = data_get($platformContext, 'module_insights.pasteurizadora.targeted_component_lookup.matches', []);

        if (!is_array($matches) || $matches === []) {
            return null;
        }

        $primary = $matches[0];
        $secondary = collect($matches)->skip(1)->take(3)->map(function (array $item): string {
            return implode(' | ', array_filter([
                $item['linea'] ?? null,
                $item['componente'] ?? null,
                isset($item['modulo']) ? 'Modulo ' . $item['modulo'] : null,
                $item['nivel'] ?? null,
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
                . (isset($primary['modulo']) ? ', modulo ' . $primary['modulo'] : '')
                . (!empty($primary['nivel']) ? ', nivel ' . $primary['nivel'] : '')
                . (!empty($primary['lado']) ? ', lado ' . $primary['lado'] : '')
                . ' es "'
                . ($primary['estado'] ?? 'Sin estado')
                . '", con revision del '
                . ($primary['fecha_analisis'] ?? 'sin fecha')
                . '.',
            array_filter([
                $primary['actividad'] ? 'Actividad registrada: ' . $primary['actividad'] . '.' : null,
                isset($primary['evidencias']) ? 'Evidencias registradas: ' . (int) $primary['evidencias'] . '.' : null,
                $secondary !== [] ? 'Coincidencias adicionales: ' . implode(' || ', $secondary) . '.' : null,
            ]),
            [
                'Valida el registro fuente antes de ejecutar una accion de mantenimiento.',
            ],
            [
                ['type' => 'module_insights', 'reference' => 'pasteurizadora.targeted_component_lookup'],
            ],
            0.95
        );
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
                . ' componentes comprometidos segun el ultimo analisis disponible por componente/reductor o servo-reductor/lado.',
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
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForWasherRefactionCosts(string $question, array $platformContext): ?array
    {
        if (
            str_contains($question, 'aceite')
            || str_contains($question, 'lubric')
            || str_contains($question, 'fluido')
        ) {
            return null;
        }

        $hasRefactionIntent = str_contains($question, 'cuesta')
            || str_contains($question, 'costo')
            || str_contains($question, 'precio')
            || str_contains($question, 'sku')
            || str_contains($question, 'vale')
            || str_contains($question, 'valor')
            || str_contains($question, 'refa')
            || str_contains($question, 'refaccion')
            || str_contains($question, 'refacciones')
            || str_contains($question, 'repuesto')
            || str_contains($question, 'material')
            || str_contains($question, 'consumible')
            || str_contains($question, 'compatible')
            || str_contains($question, 'aplica')
            || str_contains($question, 'lleva')
            || str_contains($question, 'usa');

        if (!$hasRefactionIntent) {
            return null;
        }

        if (!(
            str_contains($question, 'cuesta')
            || str_contains($question, 'costo')
            || str_contains($question, 'precio')
            || str_contains($question, 'sku')
            || str_contains($question, 'refa')
            || str_contains($question, 'refaccion')
            || str_contains($question, 'refacciones')
            || str_contains($question, 'repuesto')
            || str_contains($question, 'material')
            || str_contains($question, 'consumible')
            || str_contains($question, 'compatible')
            || str_contains($question, 'aplica')
            || str_contains($question, 'lleva')
            || str_contains($question, 'usa')
            || str_contains($question, 'catarina')
            || str_contains($question, 'cadena')
            || str_contains($question, 'guia')
            || str_contains($question, 'buje')
            || str_contains($question, 'servo')
            || str_contains($question, 'reductor')
        )) {
            return null;
        }

        $lookup = data_get($platformContext, 'module_insights.lavadora.refaction_cost_lookup');
        $matches = is_array($lookup) ? ($lookup['matches'] ?? []) : [];

        if (!is_array($matches) || $matches === []) {
            return null;
        }

        $knowledgeMatches = is_array($lookup) && is_array($lookup['knowledge_matches'] ?? null)
            ? $lookup['knowledge_matches']
            : [];
        $requestedLineas = $this->extractLineReferences($question);
        $asksCost = str_contains($question, 'cuesta')
            || str_contains($question, 'costo')
            || str_contains($question, 'precio')
            || str_contains($question, 'vale')
            || str_contains($question, 'valor');
        $asksSku = str_contains($question, 'sku')
            || str_contains($question, 'numero de parte')
            || str_contains($question, 'n parte')
            || str_contains($question, 'np');
        $asksList = str_contains($question, 'que refa')
            || str_contains($question, 'que refaccion')
            || str_contains($question, 'que refacciones')
            || str_contains($question, 'cuales refacciones')
            || str_contains($question, 'que piezas')
            || str_contains($question, 'que materiales')
            || str_contains($question, 'que consumibles')
            || str_contains($question, 'que lleva')
            || str_contains($question, 'que usa')
            || str_contains($question, 'compatible')
            || str_contains($question, 'aplica');

        $primary = $matches[0];
        $componentes = collect($primary['componentes'] ?? [])->filter()->values()->all();
        $lineas = $requestedLineas !== []
            ? $requestedLineas
            : collect($primary['lineas'] ?? [])->filter()->values()->all();
        $producto = (string) ($primary['producto'] ?? 'Refaccion');
        $sku = (string) ($primary['sku'] ?? 'sin SKU');
        $categoria = (string) ($primary['categoria'] ?? 'Refaccion');
        $unidad = (string) ($primary['unidad_medida'] ?? 'PZA');
        $unitCost = isset($primary['costo_unitario']) && (float) $primary['costo_unitario'] > 0
            ? '$' . number_format((float) $primary['costo_unitario'], 2, '.', ',') . ' MXN por ' . $unidad
            : null;
        $quantity = isset($primary['cantidad_referencia']) && $primary['cantidad_referencia'] !== null
            ? $this->formatNumber((float) $primary['cantidad_referencia']) . ' ' . ((string) ($primary['unidad_referencia'] ?? $unidad))
            : null;
        $referenceCost = isset($primary['costo_referencia']) && $primary['costo_referencia'] !== null
            ? '$' . number_format((float) $primary['costo_referencia'], 2, '.', ',') . ' MXN'
            : null;
        $scope = $this->formatRefactionScope($componentes, $lineas);
        $extraMatches = collect($matches)
            ->skip(1)
            ->take(4)
            ->map(fn (array $item): string => $this->formatRefactionMatchSummary($item))
            ->filter()
            ->all();
        $primarySummary = $this->formatRefactionMatchSummary($primary);

        $answer = $asksCost
            ? 'La refaccion de referencia para ' . $scope . ' es ' . $producto . ' (SKU ' . $sku . ')'
                . ($unitCost ? ', con costo unitario de ' . $unitCost : '.')
            : 'La referencia principal encontrada para ' . $scope . ' es ' . $producto . ' (SKU ' . $sku . ').';

        if ($requestedLineas === [] && $asksCost && count($matches) > 1) {
            $answer = 'Encontre varias referencias de costo para ' . ($componentes !== [] ? implode(', ', $componentes) : 'la refaccion consultada') . ' segun la linea o el paso de la lavadora.';
        } elseif ($asksSku) {
            $answer = 'El SKU de referencia para ' . $scope . ' es ' . $sku . ', correspondiente a ' . $producto . '.';
        } elseif ($asksList && count($matches) > 1) {
            $answer = 'Estas son las refacciones compatibles encontradas para ' . $scope . '.';
        }

        $sources = [
            ['type' => 'refaction_cost_lookup', 'reference' => 'SKU ' . $sku],
        ];

        foreach (collect($knowledgeMatches)->take(2) as $knowledgeMatch) {
            if (!is_array($knowledgeMatch)) {
                continue;
            }

            $sources[] = [
                'type' => 'knowledge_document',
                'reference' => (string) ($knowledgeMatch['reference'] ?? 'Documento tecnico'),
            ];
        }

        return $this->deterministicResponse(
            $answer,
            array_filter([
                (count($matches) > 1 || $asksList) && $primarySummary !== ''
                    ? 'Referencia principal: ' . $primarySummary . '.'
                    : null,
                $unitCost ? 'Costo unitario: ' . $unitCost . '.' : null,
                $categoria !== '' ? 'Categoria: ' . $categoria . '.' : null,
                $lineas !== [] ? 'Compatibilidad registrada: ' . $this->formatLineScope($lineas) . '.' : null,
                $quantity ? 'Cantidad de referencia: ' . $quantity . '.' : null,
                $referenceCost ? 'Costo de referencia: ' . $referenceCost . '.' : null,
                isset($primary['observaciones']) && $primary['observaciones']
                    ? 'Observaciones: ' . (string) $primary['observaciones'] . '.'
                    : null,
                $extraMatches !== [] ? 'Coincidencias adicionales: ' . implode(' || ', $extraMatches) . '.' : null,
                $knowledgeMatches !== [] ? 'Documento relacionado: ' . (string) ($knowledgeMatches[0]['reference'] ?? 'Documento tecnico') . '.' : null,
            ]),
            [
                $requestedLineas === [] && count($matches) > 1
                    ? 'Si me dices la linea exacta, te dejo un solo SKU y costo de referencia.'
                    : 'Si quieres, tambien te listo las refacciones relacionadas, consumibles o costos auxiliares del mismo conjunto.',
            ],
            $sources,
            0.98
        );
    }

    /**
     * @param  array<string, mixed>  $platformContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function replyForWasherLubrication(string $question, array $platformContext): ?array
    {
        if (!(
            str_contains($question, 'aceite')
            || str_contains($question, 'lubric')
            || str_contains($question, 'litro')
            || str_contains($question, 'fluido')
        )) {
            return null;
        }

        $lookup = data_get($platformContext, 'module_insights.lavadora.lubrication_lookup');
        $matches = is_array($lookup) ? ($lookup['matches'] ?? []) : [];

        if (!is_array($matches) || $matches === []) {
            return null;
        }

        $knowledgeMatches = is_array($lookup) && is_array($lookup['knowledge_matches'] ?? null)
            ? $lookup['knowledge_matches']
            : [];
        $primary = $matches[0];
        $product = (string) ($primary['producto'] ?? 'Lubricante');
        $sku = (string) ($primary['sku'] ?? 'sin SKU');
        $type = (string) ($primary['tipo'] ?? 'Aceite lubricante industrial');
        $requestedLineas = $this->extractLineReferences($question);
        $lineas = $requestedLineas !== []
            ? $requestedLineas
            : collect($primary['lineas'] ?? [])->filter()->values()->all();
        $componentes = collect($primary['componentes'] ?? [])->filter()->values()->all();
        $quantity = isset($primary['cantidad_referencia']) && $primary['cantidad_referencia'] !== null
            ? $this->formatNumber((float) $primary['cantidad_referencia']) . ' ' . ((string) ($primary['unidad_referencia'] ?? 'LT'))
            : null;
        $unitCost = isset($primary['costo_unitario']) && (float) $primary['costo_unitario'] > 0
            ? '$' . number_format((float) $primary['costo_unitario'], 2, '.', ',') . ' MXN por ' . ((string) ($primary['unidad_referencia'] ?? 'LT'))
            : null;
        $referenceCost = isset($primary['costo_referencia']) && $primary['costo_referencia'] !== null
            ? '$' . number_format((float) $primary['costo_referencia'], 2, '.', ',') . ' MXN'
            : null;
        $asksQuantity = str_contains($question, 'cuanto')
            || str_contains($question, 'cuantos')
            || str_contains($question, 'cantidad')
            || str_contains($question, 'litro')
            || str_contains($question, 'capacidad');

        $answer = $asksQuantity && $quantity
            ? 'La referencia registrada para '
                . $this->formatComponentPhrase($componentes, $lineas)
                . ' es '
                . $quantity
                . ' de '
                . $product
                . ' (SKU ' . $sku . ').'
            : 'Para '
                . $this->formatComponentPhrase($componentes, $lineas)
                . ' el lubricante de referencia es '
                . $product
                . ' (SKU ' . $sku . ').';

        $extraMatches = collect($matches)
            ->skip(1)
            ->take(3)
            ->map(function (array $item): string {
                return implode(' | ', array_filter([
                    $item['producto'] ?? null,
                    isset($item['sku']) ? 'SKU ' . $item['sku'] : null,
                    !empty($item['lineas']) ? implode(', ', $item['lineas']) : null,
                    !empty($item['componentes']) ? implode(', ', $item['componentes']) : null,
                ]));
            })
            ->all();

        $sources = [
            ['type' => 'lubrication_lookup', 'reference' => 'SKU ' . $sku],
        ];

        foreach (collect($knowledgeMatches)->take(2) as $knowledgeMatch) {
            if (!is_array($knowledgeMatch)) {
                continue;
            }

            $sources[] = [
                'type' => 'knowledge_document',
                'reference' => (string) ($knowledgeMatch['reference'] ?? 'Documento tecnico'),
            ];
        }

        return $this->deterministicResponse(
            $answer,
            array_filter([
                'Tipo: ' . $type . '.',
                $componentes !== [] ? 'Componentes relacionados: ' . implode(', ', $componentes) . '.' : null,
                $unitCost ? 'Costo unitario: ' . $unitCost . '.' : null,
                $quantity ? 'Cantidad de referencia: ' . $quantity . '.' : null,
                $referenceCost ? 'Costo de referencia: ' . $referenceCost . '.' : null,
                $extraMatches !== [] ? 'Coincidencias adicionales: ' . implode(' || ', $extraMatches) . '.' : null,
                $knowledgeMatches !== [] ? 'Documento relacionado: ' . (string) ($knowledgeMatches[0]['reference'] ?? 'Documento tecnico') . '.' : null,
            ]),
            [
                'Si quieres, tambien te digo el costo estimado, la cantidad de litros o los otros aceites relacionados por linea.',
            ],
            $sources,
            0.98
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
     * @param  array<int, mixed>  $componentes
     * @param  array<int, mixed>  $lineas
     */
    private function formatComponentPhrase(array $componentes, array $lineas): string
    {
        $componentLabel = $componentes !== []
            ? implode(', ', array_map(fn ($value) => (string) $value, $componentes))
            : 'el componente consultado';
        $lineLabel = $lineas !== []
            ? 'en ' . implode(', ', array_map(fn ($value) => (string) $value, $lineas))
            : 'en las lineas registradas';

        return $componentLabel . ' ' . $lineLabel;
    }

    /**
     * @param  array<int, mixed>  $componentes
     * @param  array<int, mixed>  $lineas
     */
    private function formatRefactionScope(array $componentes, array $lineas): string
    {
        $componentLabel = $componentes !== []
            ? implode(', ', array_map(fn ($value) => (string) $value, $componentes))
            : 'la refaccion consultada';

        if ($lineas === []) {
            return $componentLabel . ' en las lavadoras registradas';
        }

        if (in_array('TODAS', array_map(fn ($value) => Str::upper((string) $value), $lineas), true)) {
            return $componentLabel . ' en todas las lavadoras';
        }

        return $componentLabel . ' en ' . implode(', ', array_map(fn ($value) => (string) $value, $lineas));
    }

    /**
     * @param  array<int, mixed>  $lineas
     */
    private function formatLineScope(array $lineas): string
    {
        $normalized = array_values(array_map(fn ($value) => Str::upper((string) $value), $lineas));

        if (in_array('TODAS', $normalized, true)) {
            return 'todas las lavadoras';
        }

        return implode(', ', array_map(fn ($value) => (string) $value, $lineas));
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function formatRefactionMatchSummary(array $match): string
    {
        $parts = array_filter([
            $match['producto'] ?? null,
            isset($match['sku']) ? 'SKU ' . $match['sku'] : null,
            isset($match['costo_unitario']) && (float) $match['costo_unitario'] > 0
                ? '$' . number_format((float) $match['costo_unitario'], 2, '.', ',') . ' MXN/' . ((string) ($match['unidad_medida'] ?? 'PZA'))
                : null,
            !empty($match['lineas']) ? $this->formatLineScope((array) $match['lineas']) : null,
        ]);

        return implode(' | ', $parts);
    }

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $platformContext
     */
    private function targetsPasteurizadora(string $normalizedQuestion, array $platformContext = []): bool
    {
        if (data_get($platformContext, 'page_context.module') === User::MODULE_PASTEURIZADORA) {
            return true;
        }

        return str_contains($normalizedQuestion, 'pasteur')
            || preg_match('/\bp\s*[-#]?\s*0?\d{1,2}\b/u', $normalizedQuestion) === 1;
    }

    /**
     * @return array<int, string>
     */
    private function extractLineReferences(string $question): array
    {
        $lineas = [];

        if (preg_match_all('/(?:lavadora|linea|l)\s*[-#]?\s*0*(\d{1,2})\b/u', $question, $matches)) {
            foreach ($matches[1] as $lineNumber) {
                $lineas[] = 'L-' . str_pad((string) $lineNumber, 2, '0', STR_PAD_LEFT);
            }
        }

        return array_values(array_unique($lineas));
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
            'linea_nombre' => $this->sanitizer->sanitizeText((string) ($pageContext['linea_nombre'] ?? ''), 80),
            'area' => $this->sanitizer->sanitizeText((string) ($pageContext['area'] ?? $pageContext['area_pasteurizadora'] ?? ''), 80),
            'component_name' => $this->sanitizer->sanitizeText((string) ($pageContext['component_name'] ?? ''), 160),
            'component_code' => $this->sanitizer->sanitizeText((string) ($pageContext['component_code'] ?? ''), 80),
            'configuracion_id' => isset($pageContext['configuracion_id']) && is_numeric($pageContext['configuracion_id'])
                ? (int) $pageContext['configuracion_id']
                : null,
            'modulo' => isset($pageContext['modulo']) && is_numeric($pageContext['modulo'])
                ? (int) $pageContext['modulo']
                : null,
            'nivel' => $this->sanitizer->sanitizeText((string) ($pageContext['nivel'] ?? ''), 40),
            'piso' => $this->sanitizer->sanitizeText((string) ($pageContext['piso'] ?? ''), 40),
            'lado' => $this->sanitizer->sanitizeText((string) ($pageContext['lado'] ?? ''), 40),
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
