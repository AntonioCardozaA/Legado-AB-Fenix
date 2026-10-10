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
        private readonly AssistantWebSearchService $webSearch,
        private readonly OperationsPlatformContextService $platformContext,
        private readonly WasherTechnicalContextRetriever $washerTechnicalContext,
        private readonly PasteurizadoraTechnicalContextRetriever $pasteurizadoraTechnicalContext,
        private readonly AiInteractionLogger $interactionLogger,
        private readonly AssistantResponseGroundingGuard $responseGroundingGuard
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
        $retrievalQuestion = $this->contextualRetrievalQuestion($question, $conversation, $safePageContext);

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

        $platformContext = $this->buildPlatformContext($user, $retrievalQuestion, $safePageContext);

        if (!$this->explicitlyRequestsExternalContext($retrievalQuestion)
            && ($deterministicReply = $this->resolveDeterministicReply($question, $platformContext)) !== null) {
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

        $technicalContext = $this->technicalContextForQuestion($retrievalQuestion, $safePageContext, $user);
        $knowledge = $this->knowledgeSearch->search($retrievalQuestion, $safePageContext, $user);
        $webContext = $this->webSearch->searchIfNeeded($retrievalQuestion, $knowledge, $platformContext, $technicalContext);

        $payload = [
            'system_prompt' => $this->systemPrompt(),
            'user_prompt' => $this->userPrompt($user, $question, $conversation, $safePageContext, $knowledge, $platformContext, $technicalContext, $webContext, $retrievalQuestion),
            'schema_name' => 'operations_assistant_reply',
            'schema' => $this->schema(),
        ];

        $chatModel = trim((string) config('maintenance_ai.chat.model', ''));

        if ($chatModel !== '') {
            $payload['model'] = $chatModel;
        }

        try {
            $response = $this->aiProvider->generateStructuredActionPlan($payload);
        } catch (Throwable $exception) {
            if (($webFallback = $this->webContextFallbackReply($user, $question, $payload, $webContext)) !== null) {
                report($exception);

                return $webFallback;
            }

            if (($diagnosticFallback = $this->diagnosticFallbackReply($user, $question, $payload, $knowledge, $technicalContext, $webContext)) !== null) {
                report($exception);

                return $diagnosticFallback;
            }

            if (($technicalFallback = $this->technicalFallbackReply($user, $question, $payload, $platformContext, $knowledge, $technicalContext, $webContext)) !== null) {
                report($exception);

                return $technicalFallback;
            }

            throw $exception;
        }

        $structured = is_array($response['data'] ?? null) ? $response['data'] : [];
        $grounding = $this->responseGroundingGuard->validate($structured, [
            'platform_context' => $platformContext,
            'technical_context' => $technicalContext,
            'knowledge' => $knowledge,
        ]);
        $structured = $grounding['structured'];
        $groundingReport = $grounding['report'];
        $content = $this->composeMessage($structured);

        $this->interactionLogger->success($user, 'assistant_chat', $response, [
            'input_chars' => mb_strlen($payload['system_prompt'] . $payload['user_prompt']),
            'output_chars' => mb_strlen($content),
            'metadata' => [
                'question_excerpt' => $this->sanitizer->sanitizeText($question, 240),
                'knowledge_count' => count($knowledge),
                'platform_query_matches' => count($platformContext['query_matches'] ?? []),
                'platform_recent_evidence' => count($platformContext['recent_evidence'] ?? []),
                'technical_context_records' => $this->technicalContextRecordCount($technicalContext),
                'technical_context_sources' => (int) data_get($technicalContext, 'coverage.technical_sources_count', 0),
                'web_search_used' => (bool) ($webContext['used'] ?? false),
                'web_search_reason' => $webContext['reason'] ?? null,
                'web_search_provider' => $webContext['provider'] ?? null,
                'web_sources_count' => count((array) ($webContext['sources'] ?? [])),
                'response_grounding' => $groundingReport,
                'unsupported_critical_claims' => $groundingReport['unsupported_claims_count'] ?? 0,
                'unsupported_sources_count' => $groundingReport['unsupported_sources_count'] ?? 0,
                'page_context' => $safePageContext,
            ],
        ]);

        $sources = $this->mergeResponseSources(
            Arr::get($structured, 'sources', []),
            (array) ($webContext['sources'] ?? [])
        );

        return [
            'content' => $content,
            'metadata' => [
                'provider' => Arr::get($response, 'meta.provider'),
                'model' => Arr::get($response, 'meta.model'),
                'confidence' => Arr::get($structured, 'confidence'),
                'sources' => $sources,
                'page_context' => $safePageContext,
                'knowledge_count' => count($knowledge),
                'platform_query_matches' => count($platformContext['query_matches'] ?? []),
                'platform_recent_evidence' => count($platformContext['recent_evidence'] ?? []),
                'technical_context_records' => $this->technicalContextRecordCount($technicalContext),
                'technical_context_sources' => (int) data_get($technicalContext, 'coverage.technical_sources_count', 0),
                'response_grounding' => $groundingReport,
                'web_search' => [
                    'enabled' => (bool) ($webContext['enabled'] ?? false),
                    'used' => (bool) ($webContext['used'] ?? false),
                    'reason' => $webContext['reason'] ?? null,
                    'provider' => $webContext['provider'] ?? null,
                    'sources_count' => count((array) ($webContext['sources'] ?? [])),
                    'error' => $webContext['error'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<string, mixed>  $pageContext
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $platformContext
     * @param  array<string, mixed>  $technicalContext
     * @param  array<string, mixed>  $webContext
     */
    private function userPrompt(User $user, string $question, array $history, array $pageContext, array $knowledge, array $platformContext, array $technicalContext, array $webContext, ?string $retrievalQuestion = null): string
    {
        $retrievalQuestion = $retrievalQuestion !== null && trim($retrievalQuestion) !== $question
            ? $this->sanitizer->sanitizeText($retrievalQuestion, 1600)
            : null;

        $payload = [
            'user' => [
                'name' => $user->name,
                'role' => $user->role_label,
            ],
            'question' => $question,
            'retrieval_context_query' => $retrievalQuestion,
            'page_context' => $pageContext,
            'recent_conversation' => $history,
            'relevant_context' => $knowledge,
            'platform_context' => $platformContext,
            'technical_recommendation_context' => $technicalContext,
            'web_context' => $webContext,
            'instructions' => [
                'Responder en espanol.',
                'Ser concreto, practico y confiable.',
                'Usar solo el contexto dado para afirmar datos especificos del sistema o del mantenimiento.',
                'Tomar como prioridad el bloque platform_context para responder con vision global de la plataforma y no solo de la pagina actual.',
                'Para soluciones tecnicas o diagnosticos, usar technical_recommendation_context como fuente principal de antecedentes, respetando su orden de prioridad.',
                'Diferenciar claramente entre historial de la plataforma, informacion de manuales/base de conocimiento y recomendaciones inferidas.',
                'Para preguntas tecnicas de lavadoras, reductores, cadenas, elongacion, aceites, refacciones o mantenimiento, estructurar la respuesta diferenciando: Dato interno, Recomendacion tecnica y Validacion pendiente.',
                'No inventar antecedentes, reparaciones, resultados, refacciones, costos ni evidencia que no aparezcan en el contexto.',
                'Toda afirmacion critica sobre equipos, refacciones, costos, responsables, fechas, estados, planes o historial debe citar una fuente interna/documento/registro o quedar marcada como inferencia/recomendacion.',
                'Si no hay antecedentes suficientes, indicarlo y apoyarse primero en technical_sources, relevant_context o web_context; si tampoco alcanzan, decir exactamente que dato interno falta validar.',
                'Priorizar module_insights cuando exista, porque resume comparativos, rankings y estados actuales listos para responder.',
                'Si module_insights contiene lubrication_lookup o coincidencias de documentos indexados, usarlos antes de concluir que falta informacion.',
                'Si module_insights contiene refaction_cost_lookup, usarlo como fuente principal para responder costos, SKUs, compatibilidad por linea y refacciones de lavadora.',
                'Si module_insights contiene pasteurizadora, usarlo para responder sobre planes, hallazgos, recomendaciones, estado actual, modulos, niveles, lados y componentes de pasteurizadora.',
                'Cuando relevant_context incluya documentos, priorizar fragmentos con document_id, chunk_index y mayor score_breakdown.',
                'Si la pregunta pide maximos, minimos, ranking o comparativos, usar primero los resumenes comparativos presentes en platform_context.',
                'Si retrieval_context_query existe, entenderlo solo como contexto de recuperacion interna para resolver referencias conversacionales como "esa lavadora", "ese reductor" o "ese componente"; responder siempre la question original.',
                'Usar web_context solo si web_context.used es true y siempre despues de revisar platform_context, technical_recommendation_context y relevant_context.',
                'Cuando web_context exista, tratarlo como complemento externo para informacion vigente, fabricantes, normas, fichas tecnicas o precios actuales; no reemplaza los datos internos.',
                'Si usas web_context, mencionar que es informacion web externa y citar sus fuentes en sources con type web.',
                'Si falta informacion, decirlo claramente sin inventar.',
                'Cuando aplique, entregar pasos accionables punto por punto.',
                'Si la pregunta trata fugas de aceite en reductores industriales, entregar formato de diagnostico con causa probable, evidencia observable y accion recomendada; cubrir sellos/retenes, respiradero, sobrellenado, temperatura, presion interna, desgaste de eje, juntas, carcasa y lubricante incorrecto cuando apliquen.',
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
            'Para lavadoras, reductores, cadenas, elongacion, aceites, refacciones y mantenimiento, consulta primero base interna viva: module_insights, historial tecnico, documentos indexados, costos/refacciones y contexto conversacional inmediato.',
            'Recuerda el contexto inmediato de preguntas anteriores, especialmente rankings, picos historicos, elongacion, lineas, reductores y componentes criticos.',
            'Separa en la respuesta lo observado en historial/base interna, lo encontrado en documentos o web externa y lo que recomiendas como inferencia tecnica.',
            'Si module_insights incluye refaction_cost_lookup, tomalo como referencia estructurada valida para responder refacciones, costos unitarios, SKUs, compatibilidad por linea y consumibles de lavadora.',
            'Si module_insights incluye lubrication_lookup, tomalo como una referencia estructurada valida para responder preguntas de aceite, lubricante, litros, SKU y consumibles de lavadora.',
            'Si module_insights incluye pasteurizadora, usalo para planes de accion, analisis, recomendaciones IA y contexto operativo relacionado con pasteurizadora.',
            'Cuando existan coincidencias de documentos de conocimiento indexados, usalas para complementar o confirmar la respuesta operativa.',
            'Si existe web_context.used, puedes usarlo solo como complemento externo y debes distinguirlo de historial, documentos internos e inferencias.',
            'No uses informacion web para inventar estados internos, historiales, costos registrados, responsables o actividades ejecutadas.',
            'Si citas informacion web, agrega fuentes de type web en el campo sources.',
            'Para fugas de aceite en reductores industriales, responde como diagnostico operativo: causa probable, evidencia observable y accion recomendada; cruza primero manuales/base interna e historial, y usa web_context como referencia externa complementaria.',
            'Si platform_context ya incluye un ranking, panorama o comparativo actual, respondelo directamente sin decir que faltan datos.',
            'No inventes estados de equipos, costos, responsables ni trabajos ejecutados.',
            'Si afirmas datos internos sobre equipos, refacciones, costos, responsables, fechas, estados, planes o historial, deben estar respaldados por contexto interno o fuentes; si no, marcarlos como inferencia/recomendacion y nombrar el dato faltante.',
            'Si el contexto no alcanza para responder con certeza, dilo explicitamente y nombra el dato interno pendiente: linea, componente, reductor, SKU, aceite, fecha de analisis, evidencia, manual/placa o ciclo de cadena, segun aplique.',
            'Evita explicaciones largas. Prioriza claridad y utilidad operativa.',
        ]);
    }

    /**
     * @param  mixed  $structuredSources
     * @param  array<int, mixed>  $webSources
     * @return array<int, array<string, string>>
     */
    private function mergeResponseSources(mixed $structuredSources, array $webSources): array
    {
        $sources = collect(is_array($structuredSources) ? $structuredSources : [])
            ->filter(fn ($source): bool => is_array($source))
            ->map(fn (array $source): array => array_filter([
                'type' => is_scalar($source['type'] ?? null) ? (string) $source['type'] : 'context',
                'reference' => is_scalar($source['reference'] ?? null) ? (string) $source['reference'] : null,
            ], static fn ($value): bool => $value !== null && $value !== ''))
            ->filter(fn (array $source): bool => ($source['reference'] ?? '') !== '')
            ->values();

        collect($webSources)
            ->filter(fn ($source): bool => is_array($source))
            ->each(function (array $source) use ($sources): void {
                $reference = is_scalar($source['reference'] ?? null)
                    ? (string) $source['reference']
                    : (string) ($source['url'] ?? '');

                if ($reference === '') {
                    return;
                }

                $sources->push(array_filter([
                    'type' => 'web',
                    'reference' => $reference,
                    'url' => is_scalar($source['url'] ?? null) ? (string) $source['url'] : null,
                ], static fn ($value): bool => $value !== null && $value !== ''));
            });

        return $sources
            ->unique(fn (array $source): string => ($source['type'] ?? '') . '|' . ($source['reference'] ?? ''))
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $webContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function webContextFallbackReply(User $user, string $question, array $payload, array $webContext): ?array
    {
        if (!((bool) ($webContext['used'] ?? false))) {
            return null;
        }

        $summary = $this->sanitizer->sanitizeText((string) ($webContext['summary'] ?? ''), 1400);

        if ($summary === '') {
            return null;
        }

        $sources = $this->mergeResponseSources([], (array) ($webContext['sources'] ?? []));
        $content = trim(implode("\n\n", array_filter([
            'No pude completar la respuesta con el modelo principal, pero si encontre informacion web externa relevante.',
            "Informacion web externa:\n" . $summary,
            $sources !== []
                ? 'Fuentes: ' . implode(' | ', collect($sources)->take(3)->map(fn (array $source): string => (string) ($source['reference'] ?? $source['url'] ?? 'Fuente web'))->all())
                : null,
            "Siguiente paso:\n- Valida esta informacion contra el componente instalado, placa del equipo o documento interno antes de comprar refacciones o ejecutar cambios.",
        ])));

        $this->interactionLogger->fallback($user, 'assistant_chat', [
            'provider' => 'web-search',
            'model' => $webContext['provider'] ?? null,
            'input_chars' => mb_strlen((string) ($payload['system_prompt'] ?? '') . (string) ($payload['user_prompt'] ?? '')),
            'output_chars' => mb_strlen($content),
            'metadata' => [
                'mode' => 'web_context_after_ai_failure',
                'question_excerpt' => $this->sanitizer->sanitizeText($question, 240),
                'web_search_reason' => $webContext['reason'] ?? null,
                'web_search_provider' => $webContext['provider'] ?? null,
                'web_sources_count' => count((array) ($webContext['sources'] ?? [])),
            ],
        ]);

        return [
            'content' => $content,
            'metadata' => [
                'provider' => 'web-search',
                'model' => $webContext['provider'] ?? null,
                'confidence' => 0.55,
                'sources' => $sources,
                'fallback' => true,
                'web_search' => [
                    'enabled' => true,
                    'used' => true,
                    'reason' => $webContext['reason'] ?? null,
                    'provider' => $webContext['provider'] ?? null,
                    'sources_count' => count((array) ($webContext['sources'] ?? [])),
                    'error' => $webContext['error'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $technicalContext
     * @param  array<string, mixed>  $webContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function diagnosticFallbackReply(User $user, string $question, array $payload, array $knowledge, array $technicalContext, array $webContext): ?array
    {
        $normalized = Str::lower(Str::ascii($question));

        if (!$this->looksLikeLeakDiagnosisQuestion($normalized)) {
            return null;
        }

        $content = implode("\n\n", [
            'Las fugas de aceite en un reductor industrial suelen venir de problemas de sellado, presion interna, nivel incorrecto, temperatura alta, desgaste mecanico o lubricante inadecuado. Usa este diagnostico como base y cruzalo con manuales internos, placa del reductor e historial de la linea.',
            implode("\n", [
                'Diagnostico probable:',
                '| Causa probable | Evidencia observable | Accion recomendada |',
                '| --- | --- | --- |',
                '| Sellos o retenes desgastados, endurecidos, cortados o mal instalados | Aceite alrededor del eje de entrada/salida, goteo despues de operar, labio del reten humedo o con residuo | Cambiar reten/sello con medida y material correctos; revisar orientacion, alojamiento y acabado del eje antes de montar |',
                '| Respiradero obstruido, mal ubicado o ausente | Fuga en varios puntos, aceite expulsado por retenes/tapas, respiradero tapado con polvo/pintura o sin paso de aire | Limpiar o reemplazar respiradero; confirmar posicion de montaje y que el venteo iguale la presion interna |',
                '| Sobrellenado o nivel incorrecto de aceite | Nivel por arriba de mirilla/tapon de nivel, aceite saliendo por respiradero, espuma o calentamiento por batido | Drenar hasta nivel especificado por manual/placa, verificar con equipo detenido y en posicion correcta |',
                '| Temperatura alta o presion interna excesiva | Carcasa muy caliente, olor a aceite degradado, fuga aumenta al calentarse, varios sellos sudan | Revisar carga, ventilacion, aletas/cooler, nivel y viscosidad; corregir causa termica antes de cambiar sellos |',
                '| Desgaste, ranura, rayadura, excentricidad o desalineacion del eje | Marca circular en zona del reten, juego radial, vibracion, fuga recurrente tras cambiar reten | Medir eje y rodamientos; reparar superficie, instalar camisa de reparacion o reemplazar eje/rodamiento segun condicion |',
                '| Juntas, empaques, tapas, mirillas o tapones flojos/deteriorados | Humedad en linea de union, tornilleria floja, aceite en tapa, mirilla o tapon de drenado/llenado | Reapretar con torque correcto si aplica, reemplazar junta/O-ring/sellador y revisar planitud de superficies |',
                '| Carcasa fisurada, porosa o deformada | Fuga localizada en fundicion, grieta visible, fuga que no coincide con sellos ni juntas, antecedente de golpe | Limpiar y hacer inspeccion visual/tintes; reparar o sustituir carcasa y validar alineacion/fijacion |',
                '| Lubricante incorrecto, contaminado o incompatible | Aceite espumoso, cambio de viscosidad/color/olor, mezcla de lubricantes, fuga tras cambio de aceite | Confirmar ISO VG/tipo con manual o placa; drenar, limpiar y rellenar con lubricante especificado; no mezclar aceites incompatibles |',
            ]),
            "Revision inicial recomendada:\n- Limpia el reductor y marca el primer punto donde reaparece aceite.\n- Verifica nivel, tipo de lubricante y posicion de montaje contra manual/placa.\n- Revisa respiradero antes de condenar retenes.\n- Si la fuga sale por eje, inspecciona reten, superficie del eje, juego de rodamientos y alineacion.\n- Documenta evidencia fotografica, temperatura aproximada y condicion del aceite para comparar con historial interno.",
        ]);

        $this->interactionLogger->fallback($user, 'assistant_chat', [
            'provider' => 'diagnostic-fallback',
            'model' => 'local-oil-leak-diagnostic',
            'input_chars' => mb_strlen((string) ($payload['system_prompt'] ?? '') . (string) ($payload['user_prompt'] ?? '')),
            'output_chars' => mb_strlen($content),
            'metadata' => [
                'mode' => 'local_diagnostic_after_ai_failure',
                'question_excerpt' => $this->sanitizer->sanitizeText($question, 240),
                'knowledge_count' => count($knowledge),
                'technical_context_records' => $this->technicalContextRecordCount($technicalContext),
                'web_search_used' => (bool) ($webContext['used'] ?? false),
                'web_search_error' => $webContext['error'] ?? null,
            ],
        ]);

        return [
            'content' => $content,
            'metadata' => [
                'provider' => 'diagnostic-fallback',
                'model' => 'local-oil-leak-diagnostic',
                'confidence' => 0.72,
                'sources' => [],
                'fallback' => true,
                'web_search' => [
                    'enabled' => (bool) ($webContext['enabled'] ?? false),
                    'used' => (bool) ($webContext['used'] ?? false),
                    'reason' => $webContext['reason'] ?? null,
                    'provider' => $webContext['provider'] ?? null,
                    'sources_count' => count((array) ($webContext['sources'] ?? [])),
                    'error' => $webContext['error'] ?? null,
                ],
                'technical_context_records' => $this->technicalContextRecordCount($technicalContext),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $platformContext
     * @param  array<int, array<string, mixed>>  $knowledge
     * @param  array<string, mixed>  $technicalContext
     * @param  array<string, mixed>  $webContext
     * @return array{content: string, metadata: array<string, mixed>}|null
     */
    private function technicalFallbackReply(User $user, string $question, array $payload, array $platformContext, array $knowledge, array $technicalContext, array $webContext): ?array
    {
        $normalized = Str::lower(Str::ascii($question));

        if (!$this->looksLikeWasherTechnicalQuestion($normalized)) {
            return null;
        }

        $internalCount = count((array) ($platformContext['query_matches'] ?? []))
            + count((array) ($platformContext['recent_evidence'] ?? []))
            + count($knowledge)
            + $this->technicalContextRecordCount($technicalContext);
        $recommendations = $this->technicalFallbackRecommendations($normalized);
        $pending = $this->technicalFallbackPendingData($normalized);
        $webSummary = $this->sanitizer->sanitizeText((string) ($webContext['summary'] ?? ''), 700);

        $content = trim(implode("\n\n", array_filter([
            "Dato interno:\n- " . ($internalCount > 0
                ? 'Encontre contexto interno parcial relacionado, pero no una respuesta estructurada suficiente para cerrar la consulta automaticamente.'
                : 'No encontre un dato interno exacto que cierre la consulta con la base actual disponible para el asistente.'),
            $webSummary !== ''
                ? "Informacion web externa:\n- " . $webSummary
                : null,
            "Recomendacion tecnica:\n- " . implode("\n- ", $recommendations),
            "Validacion pendiente:\n- " . implode("\n- ", $pending),
        ])));

        $this->interactionLogger->fallback($user, 'assistant_chat', [
            'provider' => 'technical-fallback',
            'model' => 'local-washer-technical-fallback',
            'input_chars' => mb_strlen((string) ($payload['system_prompt'] ?? '') . (string) ($payload['user_prompt'] ?? '')),
            'output_chars' => mb_strlen($content),
            'metadata' => [
                'mode' => 'local_technical_after_ai_failure',
                'question_excerpt' => $this->sanitizer->sanitizeText($question, 240),
                'internal_context_count' => $internalCount,
                'web_search_used' => (bool) ($webContext['used'] ?? false),
                'web_search_error' => $webContext['error'] ?? null,
            ],
        ]);

        return [
            'content' => $content,
            'metadata' => [
                'provider' => 'technical-fallback',
                'model' => 'local-washer-technical-fallback',
                'confidence' => $internalCount > 0 ? 0.62 : 0.48,
                'sources' => $this->mergeResponseSources(
                    $internalCount > 0 ? [['type' => 'internal_context', 'reference' => 'platform_context/technical_context']] : [],
                    (array) ($webContext['sources'] ?? [])
                ),
                'fallback' => true,
                'technical_context_records' => $this->technicalContextRecordCount($technicalContext),
                'knowledge_count' => count($knowledge),
                'web_search' => [
                    'enabled' => (bool) ($webContext['enabled'] ?? false),
                    'used' => (bool) ($webContext['used'] ?? false),
                    'reason' => $webContext['reason'] ?? null,
                    'provider' => $webContext['provider'] ?? null,
                    'sources_count' => count((array) ($webContext['sources'] ?? [])),
                    'error' => $webContext['error'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $technicalContext
     */
    private function technicalContextRecordCount(array $technicalContext): int
    {
        return (int) data_get($technicalContext, 'coverage.historical_records_count', 0)
            + (int) data_get($technicalContext, 'coverage.technical_sources_count', 0);
    }

    private function shouldUseHybridWebPath(string $question): bool
    {
        if (!(bool) config('maintenance_ai.web_search.enabled', false)
            || (string) config('maintenance_ai.web_search.mode', 'hybrid') !== 'hybrid') {
            return false;
        }

        $normalized = Str::lower(Str::ascii($question));

        return str_contains($normalized, 'web')
            || str_contains($normalized, 'internet')
            || str_contains($normalized, 'google')
            || str_contains($normalized, 'busca en linea')
            || str_contains($normalized, 'buscar en linea')
            || str_contains($normalized, 'fuentes externas')
            || str_contains($normalized, 'actual')
            || str_contains($normalized, 'reciente')
            || str_contains($normalized, 'fabricante')
            || str_contains($normalized, 'ficha tecnica')
            || str_contains($normalized, 'manual oficial')
            || str_contains($normalized, 'catalogo')
            || str_contains($normalized, 'norma')
            || str_contains($normalized, 'estandar')
            || $this->looksLikeLeakDiagnosisQuestion($normalized);
    }

    private function explicitlyRequestsExternalContext(string $question): bool
    {
        $normalized = Str::lower(Str::ascii($question));

        return str_contains($normalized, 'web')
            || str_contains($normalized, 'internet')
            || str_contains($normalized, 'google')
            || str_contains($normalized, 'busca en linea')
            || str_contains($normalized, 'buscar en linea')
            || str_contains($normalized, 'busca afuera')
            || str_contains($normalized, 'fuentes externas')
            || str_contains($normalized, 'fabricante')
            || str_contains($normalized, 'ficha tecnica')
            || str_contains($normalized, 'manual oficial')
            || str_contains($normalized, 'catalogo')
            || str_contains($normalized, 'norma')
            || str_contains($normalized, 'estandar')
            || str_contains($normalized, 'precio actualizado')
            || str_contains($normalized, 'costo actualizado')
            || str_contains($normalized, 'equivalente');
    }

    /**
     * @param  array<int, array<string, string>>  $history
     * @param  array<string, mixed>  $pageContext
     */
    private function contextualRetrievalQuestion(string $question, array $history, array $pageContext): string
    {
        $contextParts = collect($history)
            ->filter(fn (array $entry): bool => in_array($entry['role'] ?? null, ['user', 'assistant'], true))
            ->take(-4)
            ->map(function (array $entry): string {
                $role = ($entry['role'] ?? null) === 'assistant' ? 'assistant' : 'user';
                $limit = $role === 'assistant' ? 320 : 420;

                return $role . ': ' . $this->sanitizer->sanitizeText((string) ($entry['content'] ?? ''), $limit);
            })
            ->filter()
            ->values()
            ->all();

        $pageParts = array_filter([
            $pageContext['linea_nombre'] ?? null,
            $pageContext['entity_label'] ?? null,
            $pageContext['component_name'] ?? null,
            $pageContext['component_code'] ?? null,
            $pageContext['module'] ?? null,
            $pageContext['section'] ?? null,
        ], static fn ($value): bool => is_scalar($value) && trim((string) $value) !== '');

        $retrievalQuestion = trim(implode("\n", array_filter([
            $contextParts !== [] ? 'Contexto conversacional previo del usuario: ' . implode(' | ', $contextParts) : null,
            $pageParts !== [] ? 'Contexto de pagina: ' . implode(' | ', array_map(fn ($value): string => (string) $value, $pageParts)) : null,
            'Pregunta actual: ' . $question,
        ])));

        return $this->sanitizer->sanitizeText($retrievalQuestion !== '' ? $retrievalQuestion : $question, 1800);
    }

    private function looksLikeLeakDiagnosisQuestion(string $normalized): bool
    {
        $mentionsLeak = str_contains($normalized, 'fuga')
            || str_contains($normalized, 'fugas')
            || str_contains($normalized, 'tirando aceite')
            || str_contains($normalized, 'pierde aceite')
            || str_contains($normalized, 'perdida de aceite');

        if (!$mentionsLeak) {
            return false;
        }

        return str_contains($normalized, 'aceite')
            || str_contains($normalized, 'reductor')
            || str_contains($normalized, 'lubric');
    }

    private function looksLikeWasherTechnicalQuestion(string $normalized): bool
    {
        return str_contains($normalized, 'lavadora')
            || str_contains($normalized, 'reductor')
            || str_contains($normalized, 'rv')
            || str_contains($normalized, 'cadena')
            || str_contains($normalized, 'elongacion')
            || str_contains($normalized, 'aceite')
            || str_contains($normalized, 'lubric')
            || str_contains($normalized, 'refaccion')
            || str_contains($normalized, 'refacciones')
            || str_contains($normalized, 'catarina')
            || str_contains($normalized, 'chumacera')
            || str_contains($normalized, 'eje')
            || str_contains($normalized, 'cunero')
            || str_contains($normalized, 'mantenimiento');
    }

    /**
     * @return array<int, string>
     */
    private function technicalFallbackRecommendations(string $normalized): array
    {
        $recommendations = [];

        if (str_contains($normalized, 'elongacion')) {
            $recommendations[] = 'Comparar la ultima medicion de elongacion contra los umbrales configurados y revisar si el valor maximo esta en lado bombas o vapor.';
            $recommendations[] = 'Validar tendencia del ciclo activo contra el pico historico antes de decidir cambio de cadena.';
        }

        if (str_contains($normalized, 'cadena') || str_contains($normalized, 'catarina')) {
            $recommendations[] = 'Inspeccionar cadena, catarinas, tension, alineacion, desgaste de dientes, lubricacion, guardas y juego en chumaceras antes de cambiar una sola pieza.';
        }

        if (str_contains($normalized, 'reductor') || str_contains($normalized, 'rv')) {
            $recommendations[] = 'Revisar nivel y tipo de aceite, temperatura, respiradero, retenes, eje, rodamientos, carcasa y evidencia de fuga o vibracion.';
        }

        if (str_contains($normalized, 'aceite') || str_contains($normalized, 'lubric')) {
            $recommendations[] = 'Confirmar aceite por placa/manual o registro interno de lubricacion; no mezclar viscosidades ni bases incompatibles.';
        }

        if (str_contains($normalized, 'refaccion') || str_contains($normalized, 'refacciones') || str_contains($normalized, 'chumacera') || str_contains($normalized, 'eje') || str_contains($normalized, 'cunero')) {
            $recommendations[] = 'Separar refacciones obligatorias de consumibles y herrajes: pieza principal, cadena/catarina asociada, chumaceras, eje, cuna/cunero, tornilleria, guardas y lubricante.';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Aislar el equipo, confirmar condicion fisica con evidencia, revisar historial de mantenimiento y comparar contra manual o placa del componente.';
        }

        return array_values(array_unique($recommendations));
    }

    /**
     * @return array<int, string>
     */
    private function technicalFallbackPendingData(string $normalized): array
    {
        $pending = [];

        if ($this->extractLineReferences($normalized) === []) {
            $pending[] = 'Lavadora o linea exacta, por ejemplo L-05 o L-13.';
        }

        if (str_contains($normalized, 'reductor') || str_contains($normalized, 'rv')) {
            $pending[] = 'Tipo/codigo del reductor instalado, placa y posicion de montaje.';
        }

        if (str_contains($normalized, 'aceite') || str_contains($normalized, 'lubric')) {
            $pending[] = 'Aceite registrado internamente: nombre, SKU, viscosidad ISO VG, cantidad en litros y documento/manual vigente.';
        }

        if (str_contains($normalized, 'elongacion') || str_contains($normalized, 'cadena')) {
            $pending[] = 'Ultima medicion de elongacion, ciclo de cadena activo, lado critico y pico historico por linea.';
        }

        if (str_contains($normalized, 'refaccion') || str_contains($normalized, 'refacciones') || str_contains($normalized, 'catarina') || str_contains($normalized, 'chumacera')) {
            $pending[] = 'SKU interno, compatibilidad por linea, cantidad requerida, existencia/refaccion disponible y costo actualizado.';
        }

        $pending[] = 'Ultimo analisis con fecha, estado, actividad registrada y evidencia fotografica si existe.';

        return array_values(array_unique($pending));
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
        $highestScope = 'actual';

        if (!is_array($highest)) {
            $highest = is_array($panorama) ? ($panorama['highest_historical'] ?? null) : null;
            $highestScope = 'historico registrado';
        }

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
                . ($highest['lado_critico'] ?? $highest['critical_side'] ?? 'critico')
                . ', segun la ultima medicion registrada el '
                . ($highest['recorded_at'] ?? 'sin fecha')
                . '.',
            array_filter([
                'Alcance usado: ' . $highestScope . '.',
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
        $criticalComponents = collect($highestLine['critical_top_components'] ?? [])
            ->take(4)
            ->map(fn (array $item): string => ($item['componente'] ?? 'Sin componente') . ' (' . (int) ($item['total'] ?? 0) . ')')
            ->all();
        $ranking = collect(data_get($platformContext, 'module_insights.lavadora.current_damage_by_line.top_lines', []))
            ->take(8)
            ->map(function (array $item, int $index): string {
                return ($index + 1) . '. '
                    . ($item['linea'] ?? 'Sin linea')
                    . ': '
                    . (int) ($item['problematic_components'] ?? 0)
                    . ' danados'
                    . ', '
                    . (int) ($item['critical_components'] ?? 0)
                    . ' criticos'
                    . (isset($item['latest_review_date']) ? ', ultima revision ' . $item['latest_review_date'] : '');
            })
            ->all();

        return $this->deterministicResponse(
            'La lavadora con mas componentes actualmente en estado problematico es '
                . ($highestLine['linea'] ?? 'Sin linea')
                . ', con '
                . (int) ($highestLine['problematic_components'] ?? 0)
                . ' componentes comprometidos segun el ultimo analisis disponible por componente/reductor o servo-reductor/lado.',
            array_filter([
                'Componentes criticos dentro de esa lavadora: ' . (int) ($highestLine['critical_components'] ?? 0) . '.',
                $criticalComponents !== [] ? 'Componentes criticos principales: ' . implode(' | ', $criticalComponents) . '.' : null,
                $components !== [] ? 'Componentes mas repetidos: ' . implode(' | ', $components) . '.' : null,
                $ranking !== [] ? 'Ranking actual por lavadora: ' . implode(' || ', $ranking) . '.' : null,
                isset($highestLine['latest_review_date']) ? 'Ultima revision considerada: ' . $highestLine['latest_review_date'] . '.' : null,
            ]),
            [
                'Revisa los registros fuente de la linea con mayor afectacion antes de programar cambios o compras.',
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
        if ($this->asksForTechnicalRecommendation($question)) {
            return null;
        }

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
        if ($this->asksForTechnicalRecommendation($question)) {
            return null;
        }

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

    private function asksForTechnicalRecommendation(string $question): bool
    {
        return str_contains($question, 'solucion')
            || str_contains($question, 'resolver')
            || str_contains($question, 'repar')
            || str_contains($question, 'correg')
            || str_contains($question, 'diagnostic')
            || str_contains($question, 'recomend')
            || str_contains($question, 'intervencion')
            || str_contains($question, 'procedimiento')
            || str_contains($question, 'fuga')
            || str_contains($question, 'fugas')
            || str_contains($question, 'causa')
            || str_contains($question, 'causar')
            || str_contains($question, 'por que')
            || str_contains($question, 'porque')
            || str_contains($question, 'que hago')
            || str_contains($question, 'como atiendo')
            || str_contains($question, 'como reparo');
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
