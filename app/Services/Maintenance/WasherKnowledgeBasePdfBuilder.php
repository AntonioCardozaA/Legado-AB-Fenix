<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisLavadora;
use App\Models\CadenaCiclo;
use App\Models\Componente;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use App\Models\WasherKnowledgeDocument;
use App\Services\ElongacionChainCostService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WasherKnowledgeBasePdfBuilder
{
    private const WASHER_LINE_IDS = [4, 5, 6, 7, 8, 9, 12, 13];

    private const DEFAULT_TITLE = 'Base de conocimiento tecnico de lavadoras';

    private const DIAGRAM_GROUPS = [
        'L-04' => 'L04-L09',
        'L-05' => 'L05-L12-L13',
        'L-06' => 'L06-L07',
        'L-07' => 'L06-L07',
        'L-08' => 'L08',
        'L-09' => 'L04-L09',
        'L-12' => 'L05-L12-L13',
        'L-13' => 'L05-L12-L13',
    ];

    /**
     * @return array{
     *     title: string,
     *     filename: string,
     *     generated_at: \Illuminate\Support\Carbon,
     *     pdf: string,
     *     text: string,
     *     data: array<string, mixed>
     * }
     */
    public function build(?string $title = null): array
    {
        $generatedAt = now();
        $documentTitle = trim((string) $title) !== ''
            ? trim((string) $title)
            : self::DEFAULT_TITLE;

        $data = $this->buildData($documentTitle, $generatedAt);
        $plainText = $this->buildPlainText($data);
        $pdfOutput = $this->renderPdf($data, $plainText);

        return [
            'title' => $documentTitle,
            'filename' => Str::slug($documentTitle) . '.pdf',
            'generated_at' => $generatedAt,
            'pdf' => $pdfOutput,
            'text' => $plainText,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildData(string $title, CarbonInterface $generatedAt): array
    {
        $lineas = $this->washerLines();
        $analyses = AnalisisLavadora::query()
            ->with(['linea', 'componente'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->get();
        $elongaciones = Elongacion::query()
            ->with(['lineaModel', 'cadenaCiclo'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $cycles = CadenaCiclo::query()
            ->orderBy('linea')
            ->orderByDesc('instalada_en')
            ->orderByDesc('id')
            ->get();
        $plans = $this->washerPlanQuery()
            ->with(['linea', 'maintenanceEvent.componente'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
        $events = $this->washerEventQuery()
            ->with(['linea', 'componente'])
            ->orderByDesc('detected_at')
            ->orderByDesc('id')
            ->get();
        $documents = WasherKnowledgeDocument::query()
            ->with(['linea', 'componente'])
            ->withCount('chunks')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->get();
        $components = Componente::query()
            ->where('activo', true)
            ->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_LAVADORA)
                    ->orWhereNull('tipo_equipo');
            })
            ->orderBy('nombre')
            ->get();
        $costEntries = LavadoraCostEntry::query()
            ->with(['linea', 'componente'])
            ->orderByDesc('cost_date')
            ->orderByDesc('id')
            ->get();

        $evidenceAnalyses = $analyses->filter(fn (AnalisisLavadora $analysis): bool => count($analysis->evidencia_fotos ?? []) > 0)->values();
        $totalPhotos = (int) $evidenceAnalyses->sum(fn (AnalisisLavadora $analysis): int => count($analysis->evidencia_fotos ?? []));
        $chainGroups = $this->chainGroups();

        return [
            'title' => $title,
            'project_name' => (string) config('app.name', 'Legado AB Fenix'),
            'generated_at' => $generatedAt,
            'scope' => [
                'Lavadoras L-04, L-05, L-06, L-07, L-08, L-09, L-12 y L-13.',
                'Contexto operativo del modulo lavadora: analisis, planes, eventos, costos, elongaciones y documentos indexados.',
                'Reglas tecnicas que hoy disparan eventos y sugerencias de mantenimiento asistidas por IA.',
                'Configuracion de cadena por linea, pasos iniciales, umbrales de elongacion y materiales requeridos.',
                'Resumen de componentes, evidencias fotograficas y conocimiento historico disponible en la plataforma.',
            ],
            'overview' => [
                'lineas_activas' => $lineas->count(),
                'analisis_registrados' => $analyses->count(),
                'analisis_con_evidencia' => $evidenceAnalyses->count(),
                'fotos_registradas' => $totalPhotos,
                'componentes_activos' => $components->count(),
                'componentes_observados' => $analyses->pluck('componente_id')->filter()->unique()->count(),
                'eventos_mantenimiento' => $events->count(),
                'planes_accion' => $plans->count(),
                'planes_ia' => $plans->where('source', 'ai')->count(),
                'elongaciones' => $elongaciones->count(),
                'ciclos_cadena' => $cycles->count(),
                'ciclos_activos' => $cycles->where('activa', true)->count(),
                'costos_registrados' => $costEntries->count(),
                'documentos_conocimiento' => $documents->count(),
                'fragmentos_conocimiento' => WasherKnowledgeChunk::query()->count(),
                'primera_revision' => $this->formatDate($analyses->last()?->fecha_analisis),
                'ultima_revision' => $this->formatDate($analyses->first()?->fecha_analisis),
                'ultima_elongacion' => $this->formatDateTime($elongaciones->first()?->created_at),
            ],
            'module_map' => $this->moduleMap(),
            'data_model' => $this->dataModel(),
            'assistant_architecture' => $this->assistantArchitecture(),
            'technical_rules' => [
                'analysis_rules' => [
                    [
                        'condition' => 'Estado "Danado - Requiere cambio"',
                        'event_type' => 'component_damaged',
                        'severity' => 'critical',
                        'effect' => 'Se crea evento critico y se intenta generar plan de accion correctivo.',
                    ],
                    [
                        'condition' => 'Estado "Desgaste severo"',
                        'event_type' => 'component_severe_wear',
                        'severity' => 'high',
                        'effect' => 'Se crea evento alto para plan preventivo/correctivo.',
                    ],
                    [
                        'condition' => 'Estado "Desgaste moderado"',
                        'event_type' => 'component_moderate_wear',
                        'severity' => 'medium',
                        'effect' => 'Se sugiere seguimiento preventivo.',
                    ],
                    [
                        'condition' => 'Estado "Requiere revision"',
                        'event_type' => 'component_requires_revision',
                        'severity' => 'medium',
                        'effect' => 'Se abre evento para inspeccion dirigida o validacion de causa.',
                    ],
                ],
                'elongation_rules' => [
                    'formula' => 'porcentaje = ((promedio - paso_inicial) / paso_inicial) * 100',
                    'warning_threshold' => (float) config('maintenance_ai.rules.elongacion_warning_threshold', Elongacion::LIMITE_COMPRAR),
                    'critical_threshold' => (float) config('maintenance_ai.rules.elongacion_critical_threshold', Elongacion::LIMITE_CAMBIO),
                    'trend_min_delta' => (float) config('maintenance_ai.rules.elongacion_trend_min_delta', 0.05),
                    'rodaja_max_mm' => config('maintenance_ai.rules.rodaja_max_mm'),
                    'status_map' => [
                        'normal' => 'Menor al umbral preventivo.',
                        'comprar' => 'Entre el umbral preventivo y el critico.',
                        'cambio' => 'Igual o mayor al umbral critico.',
                    ],
                ],
                'revision_schedule' => [
                    'interval_months' => max(1, (int) config('elongacion-alerts.interval_months', 2)),
                    'lead_days' => max(0, (int) config('elongacion-alerts.lead_days', 3)),
                    'timezone' => (string) config('elongacion-alerts.timezone', config('app.timezone', 'America/Mexico_City')),
                ],
                'knowledge_rules' => [
                    'plan_context_chars' => (int) config('maintenance_ai.max_context_chars', 18000),
                    'plan_knowledge_chunks' => (int) config('maintenance_ai.max_knowledge_chunks', 6),
                    'chat_history_window' => (int) config('maintenance_ai.chat.history_window', 8),
                    'chat_context_items' => (int) config('maintenance_ai.chat.max_context_items', 5),
                    'knowledge_chunk_size' => (int) config('maintenance_ai.knowledge.chunk_size', 1200),
                    'knowledge_chunk_overlap' => (int) config('maintenance_ai.knowledge.chunk_overlap', 200),
                ],
            ],
            'chain_groups' => $chainGroups,
            'line_profiles' => $lineas->map(function (Linea $linea) use ($analyses, $chainGroups, $cycles, $elongaciones, $events, $plans): array {
                $lineAnalyses = $analyses->where('linea_id', $linea->id)->values();
                $lineElongaciones = $elongaciones->where('linea_id', $linea->id)->values();
                $lineEvents = $events->where('linea_id', $linea->id)->values();
                $linePlans = $plans->where('linea_id', $linea->id)->values();
                $activeCycle = $cycles->first(fn (CadenaCiclo $cycle): bool => $cycle->activa && $cycle->linea === $linea->nombre);
                $lastElongacion = $lineElongaciones->first();
                $chainGroup = collect($chainGroups)->first(fn (array $group): bool => in_array($linea->nombre, $group['lineas'], true));

                return [
                    'linea' => $linea->nombre,
                    'linea_id' => $linea->id,
                    'paso_inicial' => Elongacion::getPasoInicial($linea->nombre),
                    'grupo_cadena' => $chainGroup['chain_type'] ?? 'No definido',
                    'diagrama' => self::DIAGRAM_GROUPS[$linea->nombre] ?? 'General',
                    'analisis_registrados' => $lineAnalyses->count(),
                    'componentes_distintos' => $lineAnalyses->pluck('componente_id')->filter()->unique()->count(),
                    'eventos_registrados' => $lineEvents->count(),
                    'planes_registrados' => $linePlans->count(),
                    'ultima_revision_componentes' => $this->formatDate($lineAnalyses->first()?->fecha_analisis),
                    'ultima_elongacion' => $this->formatDateTime($lastElongacion?->created_at),
                    'estado_elongacion_actual' => $lastElongacion?->estado_detallado ?: $lastElongacion?->estado,
                    'max_elongacion_actual' => $this->maxElongationValue($lastElongacion),
                    'componentes_clave' => $lineAnalyses
                        ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->componente?->nombre ?: 'Sin componente')
                        ->map(fn (Collection $items, string $name): array => ['nombre' => $name, 'total' => $items->count()])
                        ->sortByDesc('total')
                        ->take(5)
                        ->values()
                        ->all(),
                    'ciclo_activo' => $activeCycle ? [
                        'codigo' => $activeCycle->codigo,
                        'numero_ciclo' => $activeCycle->numero_ciclo,
                        'proveedor' => $activeCycle->proveedor,
                        'paso_inicial' => $activeCycle->paso_inicial,
                        'hodometro_inicial' => $activeCycle->hodometro_inicial,
                        'instalada_en' => $this->formatDateTime($activeCycle->instalada_en),
                    ] : null,
                ];
            })->values()->all(),
            'component_catalog' => [
                'families' => $this->keywordFamilies($components),
                'groups' => $components
                    ->groupBy(fn (Componente $component) => $this->fallbackValue($component->grupo, 'Sin grupo'))
                    ->map(fn (Collection $items, string $group): array => ['grupo' => $group, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->take(12)
                    ->values()
                    ->all(),
                'mechanisms' => $components
                    ->groupBy(fn (Componente $component) => $this->fallbackValue($component->mecanismo, 'Sin mecanismo'))
                    ->map(fn (Collection $items, string $mechanism): array => ['mecanismo' => $mechanism, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->take(12)
                    ->values()
                    ->all(),
                'top_analyzed' => $analyses
                    ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->componente?->nombre ?: 'Sin componente')
                    ->map(fn (Collection $items, string $name): array => [
                        'componente' => $name,
                        'codigo' => $items->first()?->componente?->codigo,
                        'total' => $items->count(),
                    ])
                    ->sortByDesc('total')
                    ->take(15)
                    ->values()
                    ->all(),
            ],
            'operational_state' => [
                'analysis_states' => $analyses
                    ->groupBy(fn (AnalisisLavadora $analysis) => $this->fallbackValue($analysis->estado, 'Sin estado'))
                    ->map(fn (Collection $items, string $state): array => ['estado' => $state, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->values()
                    ->all(),
                'event_statuses' => $events
                    ->groupBy(fn (MaintenanceEvent $event) => $this->fallbackValue($event->status, 'Sin estado'))
                    ->map(fn (Collection $items, string $status): array => ['estado' => $status, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->values()
                    ->all(),
                'event_types' => $events
                    ->groupBy(fn (MaintenanceEvent $event) => $this->fallbackValue($event->event_type, 'Sin tipo'))
                    ->map(fn (Collection $items, string $type): array => ['tipo' => $type, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->take(10)
                    ->values()
                    ->all(),
                'plan_statuses' => $plans
                    ->groupBy(fn (PlanAccion $plan) => $this->fallbackValue($plan->estado, 'Sin estado'))
                    ->map(fn (Collection $items, string $status): array => ['estado' => $status, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->values()
                    ->all(),
                'plan_sources' => $plans
                    ->groupBy(fn (PlanAccion $plan) => $this->fallbackValue($plan->source, 'manual'))
                    ->map(fn (Collection $items, string $source): array => ['source' => $source, 'total' => $items->count()])
                    ->sortByDesc('total')
                    ->values()
                    ->all(),
            ],
            'cost_summary' => [
                'entries' => $costEntries->count(),
                'total_amount' => round((float) $costEntries->sum('total_cost'), 2),
                'sources' => $costEntries
                    ->groupBy(fn (LavadoraCostEntry $entry) => $this->fallbackValue(LavadoraCostEntry::sourceLabel($entry->source_type), 'Sin origen'))
                    ->map(fn (Collection $items, string $source): array => [
                        'source' => $source,
                        'entries' => $items->count(),
                        'total_cost' => round((float) $items->sum('total_cost'), 2),
                    ])
                    ->sortByDesc('total_cost')
                    ->values()
                    ->all(),
                'latest' => $costEntries
                    ->take(8)
                    ->map(fn (LavadoraCostEntry $entry): array => [
                        'linea' => $entry->linea?->nombre,
                        'componente' => $entry->componente?->nombre ?: $entry->component_snapshot,
                        'source' => LavadoraCostEntry::sourceLabel($entry->source_type),
                        'total_cost' => round((float) $entry->total_cost, 2),
                        'cost_date' => $this->formatDate($entry->cost_date),
                    ])
                    ->values()
                    ->all(),
            ],
            'evidence_summary' => [
                'analyses_with_photos' => $evidenceAnalyses->count(),
                'photo_count' => $totalPhotos,
                'by_line' => $lineas->map(function (Linea $linea) use ($evidenceAnalyses): array {
                    $lineItems = $evidenceAnalyses->where('linea_id', $linea->id)->values();

                    return [
                        'linea' => $linea->nombre,
                        'analisis' => $lineItems->count(),
                        'fotos' => (int) $lineItems->sum(fn (AnalisisLavadora $analysis): int => count($analysis->evidencia_fotos ?? [])),
                    ];
                })->values()->all(),
                'recent_evidence' => $evidenceAnalyses
                    ->take(10)
                    ->map(fn (AnalisisLavadora $analysis): array => [
                        'analisis_id' => $analysis->id,
                        'linea' => $analysis->linea?->nombre,
                        'componente' => $analysis->componente?->nombre,
                        'estado' => $analysis->estado,
                        'fecha' => $this->formatDate($analysis->fecha_analisis),
                        'actividad' => $this->limitText((string) $analysis->actividad, 180),
                        'photo_count' => count($analysis->evidencia_fotos ?? []),
                        'photos' => collect($analysis->evidencia_fotos ?? [])
                            ->filter(fn ($path): bool => is_string($path) && trim($path) !== '')
                            ->take(3)
                            ->map(fn (string $path): string => basename(str_replace('\\', '/', $path)))
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ],
            'knowledge_inventory' => [
                'documents_total' => $documents->count(),
                'indexed_documents' => $documents->where('indexing_status', 'indexed')->count(),
                'vigent_documents' => $documents->where('lifecycle_status', 'vigente')->count(),
                'total_chunks' => WasherKnowledgeChunk::query()->count(),
                'documents' => $documents
                    ->take(12)
                    ->map(fn (WasherKnowledgeDocument $document): array => [
                        'title' => $document->title,
                        'type' => $document->document_type,
                        'linea' => $document->linea?->nombre,
                        'componente' => $document->componente?->nombre,
                        'version' => $document->version,
                        'status' => $document->lifecycle_status,
                        'indexing_status' => $document->indexing_status,
                        'chunks' => $document->chunks_count,
                        'uploaded_at' => $this->formatDateTime($document->uploaded_at),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildPlainText(array $data): string
    {
        $lines = [
            Str::upper((string) $data['title']),
            'Generado: ' . $this->formatDateTime($data['generated_at']),
            '',
            'ALCANCE',
        ];

        foreach ($data['scope'] as $item) {
            $lines[] = '- ' . $item;
        }

        $overview = $data['overview'];
        $lines[] = '';
        $lines[] = 'RESUMEN OPERATIVO';
        $lines[] = '- Lineas activas: ' . $overview['lineas_activas'];
        $lines[] = '- Analisis registrados: ' . $overview['analisis_registrados'];
        $lines[] = '- Analisis con evidencia: ' . $overview['analisis_con_evidencia'];
        $lines[] = '- Fotos registradas: ' . $overview['fotos_registradas'];
        $lines[] = '- Eventos de mantenimiento: ' . $overview['eventos_mantenimiento'];
        $lines[] = '- Planes de accion: ' . $overview['planes_accion'] . ' (IA: ' . $overview['planes_ia'] . ')';
        $lines[] = '- Elongaciones: ' . $overview['elongaciones'] . ' | Ciclos activos: ' . $overview['ciclos_activos'];
        $lines[] = '- Costos registrados: ' . $overview['costos_registrados'];
        $lines[] = '- Documentos de conocimiento: ' . $overview['documentos_conocimiento']
            . ' | Fragmentos: ' . $overview['fragmentos_conocimiento'];
        $lines[] = '- Primera revision: ' . $overview['primera_revision'] . ' | Ultima revision: ' . $overview['ultima_revision'];
        $lines[] = '- Ultima elongacion: ' . $overview['ultima_elongacion'];

        $lines[] = '';
        $lines[] = 'MAPA DEL MODULO';
        foreach ($data['module_map'] as $module) {
            $lines[] = '- ' . $module['name'] . ': ' . $module['purpose'];
            $lines[] = '  Rutas/entradas: ' . implode(', ', $module['routes']);
            $lines[] = '  Datos principales: ' . implode(', ', $module['sources']);
        }

        $lines[] = '';
        $lines[] = 'MODELO DE DATOS RELEVANTE';
        foreach ($data['data_model'] as $entity) {
            $lines[] = '- ' . $entity['table'] . ': ' . $entity['purpose'];
            $lines[] = '  Relacion clave: ' . $entity['relationship'];
            $lines[] = '  Campos utiles: ' . implode(', ', $entity['fields']);
        }

        $lines[] = '';
        $lines[] = 'ARQUITECTURA DEL ASISTENTE';
        foreach ($data['assistant_architecture'] as $step) {
            $lines[] = '- ' . $step['step'] . ': ' . $step['detail'];
        }

        $rules = $data['technical_rules'];
        $lines[] = '';
        $lines[] = 'REGLAS TECNICAS';
        foreach ($rules['analysis_rules'] as $rule) {
            $lines[] = '- Analisis: ' . $rule['condition']
                . ' -> evento ' . $rule['event_type']
                . ' (' . $rule['severity'] . '). ' . $rule['effect'];
        }
        $lines[] = '- Elongacion: formula ' . $rules['elongation_rules']['formula'];
        $lines[] = '- Umbral preventivo: ' . $rules['elongation_rules']['warning_threshold']
            . ' | Umbral critico: ' . $rules['elongation_rules']['critical_threshold']
            . ' | Delta tendencia: ' . $rules['elongation_rules']['trend_min_delta'];
        $lines[] = '- Rodaja max mm: ' . $this->fallbackValue($rules['elongation_rules']['rodaja_max_mm'], 'No configurado');
        $lines[] = '- Revision: cada ' . $rules['revision_schedule']['interval_months']
            . ' meses, alerta ' . $rules['revision_schedule']['lead_days']
            . ' dias antes, zona horaria ' . $rules['revision_schedule']['timezone'] . '.';
        $lines[] = '- Contexto IA: max chars ' . $rules['knowledge_rules']['plan_context_chars']
            . ', max knowledge chunks ' . $rules['knowledge_rules']['plan_knowledge_chunks']
            . ', history window chat ' . $rules['knowledge_rules']['chat_history_window'] . '.';

        $lines[] = '';
        $lines[] = 'CONFIGURACION DE CADENA';
        foreach ($data['chain_groups'] as $group) {
            $lines[] = '- ' . $group['chain_type'] . ' | lineas: ' . implode(', ', $group['lineas']);
            foreach ($group['items'] as $item) {
                $lines[] = '  * SKU ' . $item['sku'] . ' - ' . $item['nombre']
                    . ' | cantidad: ' . $item['cantidad']
                    . ' | nota: ' . $item['descripcion'];
            }
        }

        $lines[] = '';
        $lines[] = 'PERFIL POR LINEA';
        foreach ($data['line_profiles'] as $profile) {
            $lines[] = '- ' . $profile['linea']
                . ': paso inicial ' . $profile['paso_inicial']
                . ', grupo ' . $profile['grupo_cadena']
                . ', diagrama ' . $profile['diagrama']
                . ', analisis ' . $profile['analisis_registrados']
                . ', componentes ' . $profile['componentes_distintos']
                . ', eventos ' . $profile['eventos_registrados']
                . ', planes ' . $profile['planes_registrados']
                . ', ultima revision ' . $profile['ultima_revision_componentes']
                . ', ultima elongacion ' . $profile['ultima_elongacion']
                . ', estado elongacion ' . $this->fallbackValue($profile['estado_elongacion_actual'], 'Sin dato')
                . ', max elongacion ' . $this->fallbackValue($profile['max_elongacion_actual'], 'Sin dato') . '.';

            if ($profile['ciclo_activo']) {
                $lines[] = '  Ciclo activo: ' . $profile['ciclo_activo']['codigo']
                    . ' | proveedor: ' . $this->fallbackValue($profile['ciclo_activo']['proveedor'], 'Sin proveedor')
                    . ' | instalada: ' . $profile['ciclo_activo']['instalada_en'];
            }

            if ($profile['componentes_clave'] !== []) {
                $lines[] = '  Componentes clave: ' . implode(', ', array_map(
                    fn (array $component): string => $component['nombre'] . ' (' . $component['total'] . ')',
                    $profile['componentes_clave']
                ));
            }
        }

        $lines[] = '';
        $lines[] = 'CATALOGO TECNICO DE COMPONENTES';
        foreach ($data['component_catalog']['families'] as $family) {
            $lines[] = '- Familia ' . $family['family'] . ': ' . $family['total'] . ' componentes activos.';
        }
        foreach ($data['component_catalog']['top_analyzed'] as $component) {
            $lines[] = '- Top componente: ' . $component['componente']
                . ' | codigo: ' . $this->fallbackValue($component['codigo'], 'Sin codigo')
                . ' | revisiones: ' . $component['total'];
        }

        $lines[] = '';
        $lines[] = 'ESTADO OPERATIVO';
        foreach ($data['operational_state']['analysis_states'] as $state) {
            $lines[] = '- Estado de analisis ' . $state['estado'] . ': ' . $state['total'];
        }
        foreach ($data['operational_state']['event_types'] as $eventType) {
            $lines[] = '- Tipo de evento ' . $eventType['tipo'] . ': ' . $eventType['total'];
        }
        foreach ($data['operational_state']['plan_statuses'] as $planStatus) {
            $lines[] = '- Estado de plan ' . $planStatus['estado'] . ': ' . $planStatus['total'];
        }

        $costSummary = $data['cost_summary'];
        $lines[] = '';
        $lines[] = 'COSTOS REGISTRADOS';
        $lines[] = '- Entradas: ' . $costSummary['entries'] . ' | Monto total: ' . number_format((float) $costSummary['total_amount'], 2, '.', ',');
        foreach ($costSummary['sources'] as $source) {
            $lines[] = '- Origen ' . $source['source']
                . ': ' . $source['entries'] . ' entradas, total '
                . number_format((float) $source['total_cost'], 2, '.', ',');
        }

        $evidence = $data['evidence_summary'];
        $lines[] = '';
        $lines[] = 'EVIDENCIA FOTOGRAFICA';
        $lines[] = '- Analisis con fotos: ' . $evidence['analyses_with_photos'] . ' | Fotos: ' . $evidence['photo_count'];
        foreach ($evidence['by_line'] as $line) {
            $lines[] = '- ' . $line['linea'] . ': ' . $line['analisis'] . ' analisis con evidencia y ' . $line['fotos'] . ' fotos.';
        }
        foreach ($evidence['recent_evidence'] as $evidenceItem) {
            $lines[] = '- Evidencia reciente #' . $evidenceItem['analisis_id']
                . ' | ' . $evidenceItem['linea']
                . ' | ' . $this->fallbackValue($evidenceItem['componente'], 'Sin componente')
                . ' | ' . $evidenceItem['fecha']
                . ' | fotos: ' . $evidenceItem['photo_count']
                . ' | archivos: ' . implode(', ', $evidenceItem['photos']);
        }

        $knowledge = $data['knowledge_inventory'];
        $lines[] = '';
        $lines[] = 'INVENTARIO DE CONOCIMIENTO';
        $lines[] = '- Documentos: ' . $knowledge['documents_total']
            . ' | Indexados: ' . $knowledge['indexed_documents']
            . ' | Vigentes: ' . $knowledge['vigent_documents']
            . ' | Chunks: ' . $knowledge['total_chunks'];
        foreach ($knowledge['documents'] as $document) {
            $lines[] = '- Documento: ' . $document['title']
                . ' | tipo: ' . $document['type']
                . ' | linea: ' . $this->fallbackValue($document['linea'], 'General')
                . ' | indexacion: ' . $document['indexing_status']
                . ' | chunks: ' . $document['chunks']
                . ' | cargado: ' . $document['uploaded_at'];
        }

        $text = trim(implode(PHP_EOL, $lines));

        if (mb_strlen($text) <= 28000) {
            return $text;
        }

        return trim(mb_substr($text, 0, 27880) . PHP_EOL . PHP_EOL . '[Contenido truncado de forma segura para indexacion.]');
    }

    /**
     * @return Collection<int, Linea>
     */
    private function washerLines(): Collection
    {
        return Linea::query()
            ->whereIn('id', self::WASHER_LINE_IDS)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<PlanAccion>
     */
    private function washerPlanQuery()
    {
        return PlanAccion::query()
            ->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_LAVADORA)
                    ->orWhere(function ($legacyQuery): void {
                        $legacyQuery->whereNull('tipo_equipo')
                            ->whereIn('linea_id', self::WASHER_LINE_IDS);
                    });
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MaintenanceEvent>
     */
    private function washerEventQuery()
    {
        return MaintenanceEvent::query()
            ->where(function ($query): void {
                $query->whereIn('source_type', ['analisis_lavadora', 'elongacion', 'washer_knowledge_document', 'lavadora_cost_entry'])
                    ->orWhereIn('linea_id', self::WASHER_LINE_IDS);
            });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function moduleMap(): array
    {
        return [
            [
                'name' => 'Dashboard de lavadora',
                'purpose' => 'Consolida estado global, vista operativa, tendencias y accesos a lineas.',
                'routes' => [
                    '/dashboard/lavadoras',
                    '/dashboard/lavadora/operativo',
                    '/lavadora/dashboard',
                ],
                'sources' => ['lineas', 'analisis_componentes', 'maintenance_events', 'plan_accion'],
            ],
            [
                'name' => 'Analisis de componentes',
                'purpose' => 'Captura estado de componente, actividad, orden de trabajo y evidencia fotografica.',
                'routes' => [
                    '/analisis-lavadora',
                    '/analisis-lavadora/crear/{linea}',
                    '/analisis-lavadora/{analisislavadora}',
                ],
                'sources' => ['analisis_componentes', 'componentes', 'lineas'],
            ],
            [
                'name' => 'Elongaciones y ciclos',
                'purpose' => 'Controla mediciones de cadena, vida de ciclo, proveedor y alertas de revision.',
                'routes' => [
                    '/elongaciones',
                    '/analisis/{analisis}/elongacion',
                ],
                'sources' => ['elongaciones', 'cadena_ciclos', 'lavadora_cost_entries'],
            ],
            [
                'name' => 'Planes de accion',
                'purpose' => 'Gestiona planes manuales y sugeridos por IA para hallazgos de lavadora.',
                'routes' => [
                    '/plan-accion/lavadora',
                    '/plan-accion/ai/lavadora',
                ],
                'sources' => ['plan_accion', 'maintenance_events', 'analisis_componentes'],
            ],
            [
                'name' => 'Costos de lavadora',
                'purpose' => 'Relaciona costos manuales y automaticos, incluidos materiales por cambio de cadena.',
                'routes' => [
                    '/lavadora/costos',
                    '/analisis-lavadora/{analisislavadora}/costos',
                ],
                'sources' => ['lavadora_cost_entries', 'cost_catalog_items', 'cost_automation_rules'],
            ],
            [
                'name' => 'Conocimiento y chat operativo',
                'purpose' => 'Indexa documentos, recupera contexto historico y responde preguntas con vision global.',
                'routes' => [
                    '/lavadora/documentos-conocimiento',
                    '/asistente/chat',
                ],
                'sources' => ['washer_knowledge_documents', 'washer_knowledge_chunks', 'assistant_messages'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dataModel(): array
    {
        return [
            [
                'table' => 'lineas',
                'purpose' => 'Catalogo maestro de lineas productivas; en lavadoras concentra L-04 a L-13.',
                'relationship' => 'Una linea tiene analisis, planes, ciclos de cadena y elongaciones.',
                'fields' => ['id', 'nombre', 'tipo', 'activo'],
            ],
            [
                'table' => 'componentes',
                'purpose' => 'Catalogo tecnico de partes inspeccionadas o reemplazables.',
                'relationship' => 'Se enlaza con analisis lavadora y costos por componente.',
                'fields' => ['id', 'codigo', 'nombre', 'grupo', 'mecanismo', 'reductor', 'tipo_equipo'],
            ],
            [
                'table' => 'analisis_componentes',
                'purpose' => 'Bitacora principal de revision de componentes de lavadora.',
                'relationship' => 'Dispara eventos de mantenimiento y aporta evidencia fotografica.',
                'fields' => ['linea_id', 'componente_id', 'estado', 'actividad', 'reductor', 'lado', 'fecha_analisis', 'evidencia_fotos'],
            ],
            [
                'table' => 'maintenance_events',
                'purpose' => 'Normaliza hallazgos tecnicos detectados a partir de analisis y elongaciones.',
                'relationship' => 'Es la entrada oficial para la generacion de planes de accion por IA.',
                'fields' => ['linea_id', 'componente_id', 'source_type', 'event_type', 'severity', 'status', 'title', 'description'],
            ],
            [
                'table' => 'plan_accion',
                'purpose' => 'Registra planes manuales y sugeridos por IA, con revision y aprobacion.',
                'relationship' => 'Se vincula con eventos, responsables y fechas de ejecucion.',
                'fields' => ['maintenance_event_id', 'tipo_equipo', 'actividad', 'detected_problem', 'technical_justification', 'estado', 'source'],
            ],
            [
                'table' => 'cadena_ciclos',
                'purpose' => 'Define ciclo activo de cadena por linea, proveedor y hodometro inicial.',
                'relationship' => 'Una cadena puede tener multiples revisiones de elongacion asociadas.',
                'fields' => ['linea_id', 'linea', 'codigo', 'numero_ciclo', 'proveedor', 'paso_inicial', 'hodometro_inicial', 'activa'],
            ],
            [
                'table' => 'elongaciones',
                'purpose' => 'Guarda mediciones de elongacion en lado bombas y vapor, mas juego de rodaja.',
                'relationship' => 'Dispara eventos preventivos o criticos y puede generar costos de instalacion de cadena.',
                'fields' => ['linea_id', 'cadena_ciclo_id', 'bombas_promedio', 'bombas_porcentaje', 'vapor_promedio', 'vapor_porcentaje', 'estado_detallado'],
            ],
            [
                'table' => 'lavadora_cost_entries',
                'purpose' => 'Bitacora economica por componente, actividad o instalacion de cadena.',
                'relationship' => 'Complementa el contexto de IA con costo historico real.',
                'fields' => ['linea_id', 'componente_id', 'source_type', 'catalog_name_snapshot', 'quantity', 'unit_cost', 'total_cost', 'cost_date'],
            ],
            [
                'table' => 'washer_knowledge_documents',
                'purpose' => 'Repositorio de documentos tecnicos vigentes para lavadoras.',
                'relationship' => 'Cada documento puede partirse en multiples fragmentos indexados.',
                'fields' => ['title', 'document_type', 'version', 'lifecycle_status', 'storage_path', 'indexing_status', 'uploaded_at'],
            ],
            [
                'table' => 'washer_knowledge_chunks',
                'purpose' => 'Fragmentos indexables del conocimiento tecnico utilizado por IA y chat.',
                'relationship' => 'Se recuperan por solapamiento de texto y metadatos del evento.',
                'fields' => ['document_id', 'chunk_index', 'content', 'searchable_text', 'token_count', 'metadata'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function assistantArchitecture(): array
    {
        return [
            [
                'step' => '1. Captura de hallazgo',
                'detail' => 'AnalisisLavadoraController y ElongacionController registran inspecciones, estados, mediciones y evidencias.',
            ],
            [
                'step' => '2. Motor de reglas',
                'detail' => 'WasherMaintenanceRuleEngine convierte estados y umbrales en eventos tecnicos normalizados.',
            ],
            [
                'step' => '3. Orquestacion',
                'detail' => 'WasherMaintenanceOrchestrator persiste MaintenanceEvent y decide si genera plan de IA.',
            ],
            [
                'step' => '4. Contexto para IA',
                'detail' => 'WasherContextBuilder suma hallazgo actual, historico, costos y fragmentos de conocimiento indexado.',
            ],
            [
                'step' => '5. Recuperacion de conocimiento',
                'detail' => 'KnowledgeRetriever cruza chunks vigentes y planes aprobados historicos para enriquecer la respuesta.',
            ],
            [
                'step' => '6. Chat operativo',
                'detail' => 'OperationsAssistantService combina pagina actual, conocimiento indexado y contexto global vivo de toda la plataforma.',
            ],
            [
                'step' => '7. Revision humana',
                'detail' => 'Los planes de IA de lavadora pasan por aprobacion antes de publicarse como plan operativo.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function chainGroups(): array
    {
        return collect(ElongacionChainCostService::requirementGroups())
            ->map(function (array $group, string $key): array {
                return [
                    'key' => $key,
                    'lineas' => array_values($group['lineas'] ?? []),
                    'chain_type' => (string) ($group['chain_type'] ?? 'Cadena'),
                    'items' => collect($group['items'] ?? [])
                        ->map(fn (array $item): array => [
                            'sku' => (string) ($item['sku'] ?? 'Sin SKU'),
                            'nombre' => (string) ($item['nombre'] ?? 'Material'),
                            'cantidad' => round((float) ($item['cantidad'] ?? 0), 2),
                            'descripcion' => (string) ($item['descripcion'] ?? ''),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{family: string, total: int}>
     */
    private function keywordFamilies(Collection $components): array
    {
        $keywords = [
            'Servo' => ['servo'],
            'Cadena' => ['cadena'],
            'Catarina' => ['catarina'],
            'Rodaja' => ['rodaja'],
            'Guia' => ['guia'],
            'Reductor' => ['reductor'],
            'Flecha' => ['flecha', 'espiga'],
            'Buje' => ['buje'],
            'Tanque' => ['tanque'],
        ];

        return collect($keywords)
            ->map(function (array $terms, string $family) use ($components): array {
                $total = $components->filter(function (Componente $component) use ($terms): bool {
                    $haystack = Str::lower(implode(' ', array_filter([
                        $component->nombre,
                        $component->codigo,
                        $component->grupo,
                        $component->mecanismo,
                    ])));

                    foreach ($terms as $term) {
                        if (str_contains($haystack, Str::lower($term))) {
                            return true;
                        }
                    }

                    return false;
                })->count();

                return [
                    'family' => $family,
                    'total' => $total,
                ];
            })
            ->filter(fn (array $item): bool => $item['total'] > 0)
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderPdf(array $data, string $plainText): string
    {
        if (class_exists(Dompdf::class)) {
            $html = view('lavadora.knowledge-documents.pdf.base-conocimiento-lavadoras', [
                'knowledge' => $data,
            ])->render();

            $pdf = new Dompdf();
            $pdf->loadHtml($html);
            $pdf->setPaper('letter');
            $pdf->render();

            return $pdf->output();
        }

        return $this->renderFallbackPdf((string) $data['title'], $plainText, $data['generated_at']);
    }

    private function renderFallbackPdf(string $title, string $plainText, CarbonInterface $generatedAt): string
    {
        $preface = implode(PHP_EOL, [
            $title,
            'Generado: ' . $generatedAt->format('d/m/Y H:i'),
            'Proyecto: ' . (string) config('app.name', 'Legado AB Fenix'),
            str_repeat('=', 70),
            '',
        ]);

        $lines = $this->wrapPdfLines($preface . $plainText, 92);
        $linesPerPage = 56;
        $pages = array_chunk($lines, $linesPerPage);

        if ($pages === []) {
            $pages = [[]];
        }

        $objects = [];
        $pageObjectNumbers = [];
        $contentObjectNumbers = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $nextObject = 4;

        foreach ($pages as $_) {
            $pageObjectNumbers[] = $nextObject++;
            $contentObjectNumbers[] = $nextObject++;
        }

        $kids = implode(' ', array_map(
            static fn (int $objectNumber): string => $objectNumber . ' 0 R',
            $pageObjectNumbers
        ));

        $objects[2] = '<< /Type /Pages /Count ' . count($pageObjectNumbers) . ' /Kids [ ' . $kids . ' ] >>';

        foreach ($pages as $index => $pageLines) {
            $pageObjectNumber = $pageObjectNumbers[$index];
            $contentObjectNumber = $contentObjectNumbers[$index];
            $stream = $this->buildPdfPageStream($pageLines, $index + 1, count($pages));

            $objects[$pageObjectNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] '
                . '/Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObjectNumber . ' 0 R >>';
            $objects[$contentObjectNumber] = '<< /Length ' . strlen($stream) . " >>\nstream\n"
                . $stream . "\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $objectBody) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $objectBody . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = max(array_keys($objects));

        $pdf .= "xref\n0 " . ($objectCount + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index <= $objectCount; $index++) {
            $offset = $offsets[$index] ?? 0;
            $pdf .= sprintf('%010d 00000 n ' . "\n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . ($objectCount + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    /**
     * @return array<int, string>
     */
    private function wrapPdfLines(string $content, int $maxCharsPerLine): array
    {
        $wrapped = [];
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);

        foreach (explode("\n", $normalized) as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                $wrapped[] = '';
                continue;
            }

            foreach (preg_split('/\s+/u', $line) ?: [] as $word) {
                $word = trim((string) $word);

                if ($word === '') {
                    continue;
                }

                if ($wrapped === [] || mb_strlen((string) end($wrapped)) >= $maxCharsPerLine || end($wrapped) === '') {
                    $wrapped[] = $word;
                    continue;
                }

                $candidate = end($wrapped) . ' ' . $word;

                if (mb_strlen($candidate) > $maxCharsPerLine) {
                    $wrapped[] = $word;
                    continue;
                }

                $wrapped[array_key_last($wrapped)] = $candidate;
            }
        }

        return $wrapped;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function buildPdfPageStream(array $lines, int $pageNumber, int $pageCount): string
    {
        $stream = "BT\n/F1 10 Tf\n40 760 Td\n12 TL\n";
        $pageHeader = 'Pagina ' . $pageNumber . ' de ' . $pageCount;
        $allLines = array_merge([$pageHeader, ''], $lines);

        foreach ($allLines as $index => $line) {
            $encoded = $this->escapePdfText($line);

            if ($index === 0) {
                $stream .= '(' . $encoded . ") Tj\n";
                continue;
            }

            $stream .= "T*\n(" . $encoded . ") Tj\n";
        }

        $stream .= 'ET';

        return $stream;
    }

    private function escapePdfText(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        if ($encoded === false) {
            $encoded = $value;
        }

        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $encoded
        );
    }

    private function maxElongationValue(?Elongacion $elongacion): ?float
    {
        if (!$elongacion) {
            return null;
        }

        return round(max((float) $elongacion->bombas_porcentaje, (float) $elongacion->vapor_porcentaje), 2);
    }

    private function formatDate(mixed $value): string
    {
        if (!$value) {
            return 'Sin registro';
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y');
        }

        try {
            return Carbon::parse((string) $value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatDateTime(mixed $value): string
    {
        if (!$value) {
            return 'Sin registro';
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y H:i');
        }

        try {
            return Carbon::parse((string) $value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function fallbackValue(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }

    private function limitText(string $value, int $limit): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $value) ?? $value), $limit, '...');
    }
}
