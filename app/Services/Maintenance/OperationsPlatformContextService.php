<?php

namespace App\Services\Maintenance;

use Carbon\CarbonImmutable;
use App\Models\Analisis;
use App\Models\AnalisisEtiquetadora;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\Componente;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Models\Linea;
use App\Models\MaintenanceEvent;
use App\Models\PlanAccion;
use App\Models\User;
use App\Models\WasherKnowledgeChunk;
use App\Models\WasherKnowledgeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperationsPlatformContextService
{
    private const KEY_TABLES = [
        'users',
        'roles',
        'permissions',
        'lineas',
        'componentes',
        'analisis',
        'analisis_componentes',
        'analisis_pasteurizadora',
        'analisis_etiquetadora',
        'plan_accion',
        'maintenance_events',
        'elongaciones',
        'cadena_ciclos',
        'lavadora_cost_entries',
        'washer_knowledge_documents',
        'washer_knowledge_chunks',
        'notifications',
        'assistant_messages',
    ];

    private const BROAD_QUERY_TOKENS = [
        'todo',
        'toda',
        'todas',
        'sistema',
        'plataforma',
        'proyecto',
        'base',
        'datos',
        'database',
        'bd',
        'modulo',
        'modulos',
        'tablas',
        'estructura',
        'global',
        'completo',
        'completa',
        'tiempo',
        'real',
    ];

    private const MODULE_TABLE_MAP = [
        User::MODULE_LAVADORA => [
            'lineas',
            'componentes',
            'analisis_componentes',
            'plan_accion',
            'maintenance_events',
            'lavadora_cost_entries',
            'washer_knowledge_documents',
            'washer_knowledge_chunks',
            'elongaciones',
            'cadena_ciclos',
        ],
        User::MODULE_PASTEURIZADORA => [
            'lineas',
            'analisis_pasteurizadora',
            'historico_revisados',
            'plan_accion',
        ],
        User::MODULE_ETIQUETADORA => [
            'lineas',
            'componentes',
            'analisis_etiquetadora',
        ],
        'general' => [
            'users',
            'roles',
            'permissions',
            'notifications',
            'assistant_messages',
            'sessions',
        ],
    ];

    private ?Collection $tableNameCache = null;

    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer
    ) {
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<string, mixed>
     */
    public function build(User $user, string $question, array $pageContext = []): array
    {
        $moduleHints = $this->requestedModules($user, $question, $pageContext);

        return [
            'generated_at' => now()->toIso8601String(),
            'platform_modules' => $this->moduleCatalog($user),
            'database_overview' => $this->databaseOverview($question, $moduleHints),
            'live_summary' => $this->liveSummary($user),
            'module_insights' => $this->moduleInsights($user, $question, $moduleHints),
            'recent_activity' => $this->recentActivity($user, $moduleHints),
            'query_matches' => $this->queryMatches($user, $question, $moduleHints),
            'recent_evidence' => $this->recentEvidence($user, $moduleHints),
        ];
    }

    /**
     * @param  array<int, string>  $moduleHints
     * @return array<string, mixed>
     */
    private function moduleInsights(User $user, string $question, array $moduleHints): array
    {
        $insights = [];

        if ($user->canAccessModule(User::MODULE_LAVADORA) || $this->shouldIncludeModule(User::MODULE_LAVADORA, $moduleHints)) {
            $latestSnapshots = $this->washerLatestAnalysisSnapshots();
            $problematicSnapshots = $latestSnapshots
                ->filter(fn (AnalisisLavadora $analysis): bool => $this->isProblematicWasherState($analysis->estado))
                ->values();
            $problematicAnalyses = $this->washerProblematicAnalyses();

            $insights['lavadora'] = [
                'elongacion_panorama' => $this->washerElongationPanorama(),
                'damage_periods' => $this->washerDamagePeriods($problematicAnalyses),
                'current_damage_by_line' => $this->washerCurrentDamageByLine($problematicSnapshots),
                'targeted_component_lookup' => $this->washerTargetedComponentLookup($question, $latestSnapshots),
            ];
        }

        return $insights;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function moduleCatalog(User $user): array
    {
        $modules = [
            [
                'module' => 'general',
                'label' => 'Plataforma general',
                'sections' => [
                    'Dashboard',
                    'Notificaciones',
                    'Perfil de usuario',
                ],
            ],
        ];

        if ($user->canAccessModule(User::MODULE_LAVADORA)) {
            $modules[] = [
                'module' => User::MODULE_LAVADORA,
                'label' => 'Lavadora',
                'sections' => [
                    'Dashboard de lavadora',
                    'Analisis de componentes',
                    'Planes de accion',
                    'Costos',
                    'Elongaciones',
                    'Documentos de conocimiento',
                    'Historico y tendencias',
                ],
            ];
        }

        if ($user->canAccessModule(User::MODULE_PASTEURIZADORA)) {
            $modules[] = [
                'module' => User::MODULE_PASTEURIZADORA,
                'label' => 'Pasteurizadora',
                'sections' => [
                    'Dashboard de pasteurizadora',
                    'Analisis mecanica',
                    'Central hidraulica',
                    'Historico revisado',
                    'Planes de accion',
                ],
            ];
        }

        if ($user->canAccessModule(User::MODULE_ETIQUETADORA)) {
            $modules[] = [
                'module' => User::MODULE_ETIQUETADORA,
                'label' => 'Etiquetadora',
                'sections' => [
                    'Dashboard de etiquetadora',
                    'Analisis de etiquetadora',
                    'Historico operativo',
                ],
            ];
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            $modules[] = [
                'module' => 'admin',
                'label' => 'Administracion',
                'sections' => [
                    'Gestion de usuarios',
                    'Control de gastos',
                    'Roles y permisos',
                ],
            ];
        }

        return $modules;
    }

    /**
     * @param  array<int, string>  $moduleHints
     * @return array<string, mixed>
     */
    private function databaseOverview(string $question, array $moduleHints): array
    {
        $databaseName = (string) (DB::connection()->getDatabaseName() ?: config('database.connections.' . config('database.default') . '.database'));
        $tables = $this->loadTableNames();
        $columns = $this->loadColumnsByTable($tables);

        $broad = $this->isBroadSystemQuery($question);
        $tokens = $this->tokenize($question . ' ' . implode(' ', $moduleHints));
        $preferredTables = $this->preferredTables($moduleHints);

        $selectedTables = $tables
            ->map(function (string $tableName) use ($columns, $tokens, $preferredTables, $broad): array {
                $columnNames = collect($columns->get($tableName, []))
                    ->pluck('column_name')
                    ->map(fn ($value) => (string) $value)
                    ->all();
                $haystack = implode(' ', array_merge([$tableName], $columnNames));
                $score = $this->scoreTokens($tokens, $haystack);

                if (in_array($tableName, $preferredTables, true)) {
                    $score += 3;
                }

                if ($broad && in_array($tableName, self::KEY_TABLES, true)) {
                    $score += 5;
                }

                return [
                    'name' => $tableName,
                    'score' => $score,
                    'columns' => $columnNames,
                ];
            })
            ->filter(fn (array $table): bool => $broad || $table['score'] > 0)
            ->sortByDesc('score')
            ->take(max(4, (int) config('maintenance_ai.platform_context.schema_table_limit', 8)))
            ->values()
            ->map(function (array $table): array {
                return [
                    'table' => $table['name'],
                    'rows' => $this->safeTableCount($table['name']),
                    'columns' => array_values(array_slice(
                        $table['columns'],
                        0,
                        max(8, (int) config('maintenance_ai.platform_context.schema_column_limit', 16))
                    )),
                ];
            })
            ->all();

        return [
            'database' => $databaseName,
            'driver' => DB::connection()->getDriverName(),
            'total_tables' => $tables->count(),
            'relevant_tables' => $selectedTables,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function liveSummary(User $user): array
    {
        $summary = [
            'lineas_total' => Linea::query()->count(),
            'componentes_total' => Componente::query()->count(),
            'usuarios_activos' => User::query()->where('activo', true)->count(),
            'planes_accion' => [
                'total' => PlanAccion::query()->count(),
                'completados' => PlanAccion::query()->where('completado', true)->count(),
                'pendientes' => PlanAccion::query()->where('completado', false)->count(),
                'sugeridos_por_ia' => PlanAccion::query()->where('source', 'ai')->count(),
            ],
            'eventos_mantenimiento' => [
                'total' => MaintenanceEvent::query()->count(),
                'abiertos' => MaintenanceEvent::query()
                    ->whereIn('status', [
                        MaintenanceEvent::STATUS_DETECTED,
                        MaintenanceEvent::STATUS_PROCESSING,
                        MaintenanceEvent::STATUS_REQUIRES_INFORMATION,
                    ])
                    ->count(),
                'requieren_informacion' => MaintenanceEvent::query()
                    ->where('status', MaintenanceEvent::STATUS_REQUIRES_INFORMATION)
                    ->count(),
                'plan_generado' => MaintenanceEvent::query()
                    ->where('status', MaintenanceEvent::STATUS_PLAN_GENERATED)
                    ->count(),
                'resueltos' => MaintenanceEvent::query()
                    ->where('status', MaintenanceEvent::STATUS_RESOLVED)
                    ->count(),
            ],
            'notificaciones_usuario' => [
                'total' => DB::table('notifications')->where('notifiable_id', $user->id)->count(),
                'sin_leer' => DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ];

        if ($user->canAccessModule(User::MODULE_LAVADORA)) {
            $elongacionPanorama = $this->washerElongationPanorama();

            $summary['lavadora'] = [
                'analisis' => AnalisisLavadora::query()->count(),
                'analisis_con_evidencia' => $this->countEvidenceRows(AnalisisLavadora::query()->get(['evidencia_fotos']), 'evidencia_fotos'),
                'elongaciones' => Elongacion::query()->count(),
                'costos_registrados' => LavadoraCostEntry::query()->count(),
                'documentos_conocimiento' => WasherKnowledgeDocument::query()->count(),
                'fragmentos_conocimiento' => WasherKnowledgeChunk::query()->count(),
                'elongacion_panorama' => $elongacionPanorama,
            ];
        }

        if ($user->canAccessModule(User::MODULE_PASTEURIZADORA)) {
            $summary['pasteurizadora'] = [
                'analisis' => AnalisisPasteurizadora::query()
                    ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                    ->count(),
                'analisis_con_evidencia' => $this->countEvidenceRows(
                    AnalisisPasteurizadora::query()
                        ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                        ->get(['evidencia_fotos']),
                    'evidencia_fotos'
                ),
            ];
        }

        if ($user->canAccessModule(User::MODULE_ETIQUETADORA)) {
            $summary['etiquetadora'] = [
                'analisis' => AnalisisEtiquetadora::query()->count(),
                'analisis_con_evidencia' => $this->countEvidenceRows(
                    AnalisisEtiquetadora::query()->get(['evidencia_fotos']),
                    'evidencia_fotos'
                ),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<int, string>  $moduleHints
     * @return array<string, mixed>
     */
    private function recentActivity(User $user, array $moduleHints): array
    {
        $limit = max(2, (int) config('maintenance_ai.platform_context.recent_activity_limit', 4));
        $activity = [
            'planes_accion' => PlanAccion::query()
                ->with(['linea', 'responsable', 'maintenanceEvent.componente'])
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->filter(fn (PlanAccion $plan): bool => $this->userCanSeePlanInContext($user, $plan))
                ->values()
                ->map(fn (PlanAccion $plan): array => $this->planSummary($plan))
                ->all(),
            'maintenance_events' => MaintenanceEvent::query()
                ->with(['linea', 'componente'])
                ->orderByDesc('detected_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(fn (MaintenanceEvent $event): array => $this->maintenanceEventSummary($event))
                ->all(),
        ];

        if ($user->canAccessModule(User::MODULE_LAVADORA) || $this->shouldIncludeModule(User::MODULE_LAVADORA, $moduleHints)) {
            $activity['lavadora_analisis'] = $this->currentOrPastWasherAnalysisQuery()
                ->with(['linea', 'componente', 'usuario'])
                ->latest('fecha_analisis')
                ->limit($limit)
                ->get()
                ->map(fn (AnalisisLavadora $analysis): array => $this->lavadoraSummary($analysis))
                ->all();

            $activity['elongaciones'] = $this->currentOrPastElongacionQuery()
                ->with(['lineaModel', 'cadenaCiclo'])
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (Elongacion $elongacion): array => $this->elongacionSummary($elongacion))
                ->all();

            $activity['costos_lavadora'] = LavadoraCostEntry::query()
                ->with(['linea', 'componente', 'catalogItem'])
                ->latest('cost_date')
                ->limit($limit)
                ->get()
                ->map(fn (LavadoraCostEntry $entry): array => $this->costEntrySummary($entry))
                ->all();

            $activity['documentos_conocimiento'] = WasherKnowledgeDocument::query()
                ->with(['linea', 'componente'])
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (WasherKnowledgeDocument $document): array => $this->knowledgeDocumentSummary($document))
                ->all();
        }

        if ($user->canAccessModule(User::MODULE_PASTEURIZADORA) || $this->shouldIncludeModule(User::MODULE_PASTEURIZADORA, $moduleHints)) {
            $activity['pasteurizadora_analisis'] = AnalisisPasteurizadora::query()
                ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                ->with(['linea', 'usuario'])
                ->latest('fecha_analisis')
                ->limit($limit)
                ->get()
                ->map(fn (AnalisisPasteurizadora $analysis): array => $this->pasteurizadoraSummary($analysis))
                ->all();
        }

        if ($user->canAccessModule(User::MODULE_ETIQUETADORA) || $this->shouldIncludeModule(User::MODULE_ETIQUETADORA, $moduleHints)) {
            $activity['etiquetadora_analisis'] = AnalisisEtiquetadora::query()
                ->with(['linea', 'componente', 'usuario'])
                ->latest('fecha_analisis')
                ->limit($limit)
                ->get()
                ->map(fn (AnalisisEtiquetadora $analysis): array => $this->etiquetadoraSummary($analysis))
                ->all();
        }

        if ($this->hasTable('analisis')) {
            $activity['legacy_analisis'] = Analisis::query()
                ->with(['linea', 'componente', 'usuario'])
                ->latest('fecha_analisis')
                ->limit(2)
                ->get()
                ->map(fn (Analisis $analysis): array => $this->legacyAnalysisSummary($analysis))
                ->all();
        }

        return $activity;
    }

    /**
     * @param  array<int, string>  $moduleHints
     * @return array<int, array<string, mixed>>
     */
    private function queryMatches(User $user, string $question, array $moduleHints): array
    {
        $tokens = $this->tokenize($question);

        if ($tokens === []) {
            return [];
        }

        $candidates = collect();

        $candidates = $candidates->concat(
            PlanAccion::query()
                ->with(['linea', 'responsable', 'maintenanceEvent.componente'])
                ->latest('updated_at')
                ->limit(30)
                ->get()
                ->filter(fn (PlanAccion $plan): bool => $this->userCanSeePlanInContext($user, $plan))
                ->map(fn (PlanAccion $plan): array => [
                    'module' => $plan->tipo_equipo ?: 'general',
                    'type' => 'plan_accion',
                    'reference' => 'Plan #' . $plan->id,
                    'date' => optional($plan->updated_at)->toDateString(),
                    'summary' => $this->summarizeText([
                        $plan->actividad,
                        $plan->linea?->nombre,
                        $plan->maintenanceEvent?->componente?->nombre,
                        $plan->detected_problem,
                        $plan->technical_justification,
                    ]),
                    'score' => $this->scoreTokens($tokens, implode(' ', [
                        (string) $plan->actividad,
                        (string) $plan->detected_problem,
                        (string) $plan->technical_justification,
                        (string) $plan->risk_if_not_executed,
                        (string) ($plan->linea?->nombre ?? ''),
                        (string) ($plan->maintenanceEvent?->componente?->nombre ?? ''),
                    ])),
                ])
        );

        $candidates = $candidates->concat(
            MaintenanceEvent::query()
                ->with(['linea', 'componente'])
                ->orderByDesc('detected_at')
                ->orderByDesc('id')
                ->limit(40)
                ->get()
                ->map(fn (MaintenanceEvent $event): array => [
                    'module' => $this->moduleFromMaintenanceEvent($event),
                    'type' => 'maintenance_event',
                    'reference' => 'Evento #' . $event->id,
                    'date' => optional($event->detected_at ?: $event->created_at)->toDateString(),
                    'summary' => $this->summarizeText([
                        $event->title,
                        $event->description,
                        $event->event_type,
                        $event->severity,
                        $event->status,
                        $event->linea?->nombre,
                        $event->componente?->nombre,
                    ]),
                    'score' => $this->scoreTokens($tokens, implode(' ', [
                        (string) $event->title,
                        (string) $event->description,
                        (string) $event->event_type,
                        (string) $event->severity,
                        (string) $event->status,
                        (string) ($event->linea?->nombre ?? ''),
                        (string) ($event->componente?->nombre ?? ''),
                        (string) json_encode($event->context_data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ])),
                ])
        );

        if ($user->canAccessModule(User::MODULE_LAVADORA) || $this->shouldIncludeModule(User::MODULE_LAVADORA, $moduleHints)) {
            $latestSnapshots = $this->washerLatestAnalysisSnapshots();

            $candidates = $candidates->concat(
                $this->currentOrPastWasherAnalysisQuery()
                    ->with(['linea', 'componente', 'usuario'])
                    ->latest('fecha_analisis')
                    ->limit(60)
                    ->get()
                    ->map(fn (AnalisisLavadora $analysis): array => [
                        'module' => User::MODULE_LAVADORA,
                        'type' => 'analisis_lavadora',
                        'reference' => 'Analisis lavadora #' . $analysis->id,
                        'date' => optional($analysis->fecha_analisis)->toDateString(),
                        'summary' => $this->summarizeText([
                            $analysis->linea?->nombre,
                            $analysis->componente?->nombre,
                            $analysis->reductor,
                            $analysis->estado,
                            $analysis->actividad,
                        ]),
                        'score' => $this->scoreTokens($tokens, implode(' ', [
                            (string) ($analysis->linea?->nombre ?? ''),
                            (string) ($analysis->componente?->nombre ?? ''),
                            (string) $analysis->reductor,
                            (string) $analysis->estado,
                            (string) $analysis->actividad,
                            implode(' ', $analysis->evidencia_fotos ?? []),
                        ])),
                    ])
            );

            $candidates = $candidates->concat(
                $latestSnapshots->map(function (AnalisisLavadora $analysis) use ($tokens): array {
                    $summary = $this->currentWasherAnalysisSummary($analysis);

                    return [
                        'module' => User::MODULE_LAVADORA,
                        'type' => 'estado_componente_actual',
                        'reference' => 'Estado actual lavadora #' . $analysis->id,
                        'date' => $summary['fecha_analisis'],
                        'summary' => $this->summarizeText([
                            $summary['linea'],
                            $summary['componente'],
                            $summary['reductor'],
                            $summary['lado'],
                            $summary['estado'],
                            $summary['actividad'],
                        ]),
                        'score' => $this->scoreTokens($tokens, $this->analysisSearchHaystack($analysis)),
                    ];
                })
            );

            $candidates = $candidates->concat(
                $this->currentOrPastElongacionQuery()
                    ->with(['lineaModel', 'cadenaCiclo'])
                    ->latest('created_at')
                    ->limit(40)
                    ->get()
                    ->map(fn (Elongacion $elongacion): array => [
                        'module' => User::MODULE_LAVADORA,
                        'type' => 'elongacion',
                        'reference' => 'Elongacion #' . $elongacion->id,
                        'date' => optional($elongacion->created_at)->toDateString(),
                        'summary' => $this->summarizeText([
                            $elongacion->linea,
                            $elongacion->proveedor,
                            $elongacion->estado,
                            $elongacion->estado_detallado,
                            'Bombas: ' . number_format((float) $elongacion->bombas_porcentaje, 2, '.', '') . '%',
                            'Vapor: ' . number_format((float) $elongacion->vapor_porcentaje, 2, '.', '') . '%',
                            'Maximo: ' . number_format($this->elongacionMaxValue($elongacion), 2, '.', '') . '%',
                            $elongacion->hodometro_formateado,
                        ]),
                        'score' => $this->scoreTokens($tokens, implode(' ', [
                            'elongacion',
                            'cadena',
                            'porcentaje',
                            (string) $elongacion->linea,
                            (string) $elongacion->proveedor,
                            (string) $elongacion->estado,
                            (string) $elongacion->estado_detallado,
                            number_format((float) $elongacion->bombas_porcentaje, 2, '.', ''),
                            number_format((float) $elongacion->vapor_porcentaje, 2, '.', ''),
                            number_format($this->elongacionMaxValue($elongacion), 2, '.', ''),
                            (string) $elongacion->hodometro,
                        ])),
                    ])
            );

            $elongacionPanorama = $this->washerElongationPanorama();
            $topByLine = collect($elongacionPanorama['current_by_line'] ?? []);

            if ($topByLine->isNotEmpty()) {
                $currentTop = $elongacionPanorama['highest_current'] ?? null;
                $historicalTop = $elongacionPanorama['highest_historical'] ?? null;

                $rankingSummary = $topByLine
                    ->take(8)
                    ->map(fn (array $item): string => implode(' ', array_filter([
                        $item['linea'] ?? null,
                        'max',
                        isset($item['max_porcentaje']) ? number_format((float) $item['max_porcentaje'], 2, '.', '') . '%' : null,
                        'bombas',
                        isset($item['bombas_porcentaje']) ? number_format((float) $item['bombas_porcentaje'], 2, '.', '') . '%' : null,
                        'vapor',
                        isset($item['vapor_porcentaje']) ? number_format((float) $item['vapor_porcentaje'], 2, '.', '') . '%' : null,
                        $item['estado_detallado'] ?? null,
                    ])))
                    ->implode(' | ');

                $candidates->push([
                    'module' => User::MODULE_LAVADORA,
                    'type' => 'elongacion_panorama',
                    'reference' => 'Panorama de elongacion por linea',
                    'date' => $currentTop['recorded_at'] ?? null,
                    'summary' => $this->summarizeText([
                        'Mayor elongacion actual: '
                            . ($currentTop['linea'] ?? 'Sin linea')
                            . ' con '
                            . ($currentTop['max_porcentaje'] ?? '0')
                            . '%'
                            . ($currentTop ? ' en ' . ($currentTop['critical_side'] ?? 'maximo') : ''),
                        $rankingSummary,
                        $historicalTop
                            ? 'Pico historico: ' . ($historicalTop['linea'] ?? 'Sin linea') . ' con '
                                . ($historicalTop['max_porcentaje'] ?? '0') . '%'
                            : null,
                    ], 900),
                    'score' => $this->scoreTokens($tokens, implode(' ', array_filter([
                        'elongacion cadena porcentaje ranking comparativo maximo max mayor alto actual historico',
                        $rankingSummary,
                        $currentTop ? ($currentTop['linea'] ?? '') : null,
                        $historicalTop ? ($historicalTop['linea'] ?? '') : null,
                    ]))) + 3,
                ]);
            }

            $targetedLookup = $this->washerTargetedComponentLookup($question, $latestSnapshots);

            $candidates = $candidates->concat(
                collect($targetedLookup['matches'] ?? [])->map(fn (array $match): array => [
                    'module' => User::MODULE_LAVADORA,
                    'type' => 'estado_componente_actual',
                    'reference' => 'Consulta dirigida componente',
                    'date' => $match['fecha_analisis'] ?? null,
                    'summary' => $this->summarizeText([
                        $match['linea'] ?? null,
                        $match['componente'] ?? null,
                        $match['reductor'] ?? null,
                        $match['lado'] ?? null,
                        $match['estado'] ?? null,
                        $match['actividad'] ?? null,
                    ]),
                    'score' => 25,
                ])
            );

            $candidates = $candidates->concat(
                LavadoraCostEntry::query()
                    ->with(['linea', 'componente', 'catalogItem'])
                    ->latest('cost_date')
                    ->limit(30)
                    ->get()
                    ->map(fn (LavadoraCostEntry $entry): array => [
                        'module' => User::MODULE_LAVADORA,
                        'type' => 'costo_lavadora',
                        'reference' => 'Costo #' . $entry->id,
                        'date' => optional($entry->cost_date)->toDateString(),
                        'summary' => $this->summarizeText([
                            $entry->linea?->nombre,
                            $entry->componente?->nombre,
                            $entry->catalogItem?->name ?? $entry->catalog_name_snapshot,
                            $entry->source_reference,
                            'Total: ' . number_format((float) $entry->total_cost, 2),
                        ]),
                        'score' => $this->scoreTokens($tokens, implode(' ', [
                            (string) ($entry->linea?->nombre ?? ''),
                            (string) ($entry->componente?->nombre ?? ''),
                            (string) ($entry->catalogItem?->name ?? $entry->catalog_name_snapshot ?? ''),
                            (string) $entry->source_reference,
                            (string) $entry->notas,
                        ])),
                    ])
            );

            $candidates = $candidates->concat(
                WasherKnowledgeDocument::query()
                    ->with(['linea', 'componente'])
                    ->latest('updated_at')
                    ->limit(20)
                    ->get()
                    ->map(fn (WasherKnowledgeDocument $document): array => [
                        'module' => User::MODULE_LAVADORA,
                        'type' => 'documento_conocimiento',
                        'reference' => 'Documento #' . $document->id,
                        'date' => optional($document->updated_at)->toDateString(),
                        'summary' => $this->summarizeText([
                            $document->title,
                            $document->document_type,
                            $document->linea?->nombre,
                            $document->componente?->nombre,
                            $document->lifecycle_status,
                        ]),
                        'score' => $this->scoreTokens($tokens, implode(' ', [
                            (string) $document->title,
                            (string) $document->document_type,
                            (string) ($document->linea?->nombre ?? ''),
                            (string) ($document->componente?->nombre ?? ''),
                            (string) $document->extracted_text,
                        ])),
                    ])
            );
        }

        if ($user->canAccessModule(User::MODULE_PASTEURIZADORA) || $this->shouldIncludeModule(User::MODULE_PASTEURIZADORA, $moduleHints)) {
            $candidates = $candidates->concat(
                AnalisisPasteurizadora::query()
                    ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                    ->with(['linea', 'usuario'])
                    ->latest('fecha_analisis')
                    ->limit(40)
                    ->get()
                    ->map(fn (AnalisisPasteurizadora $analysis): array => [
                        'module' => User::MODULE_PASTEURIZADORA,
                        'type' => 'analisis_pasteurizadora',
                        'reference' => 'Analisis pasteurizadora #' . $analysis->id,
                        'date' => optional($analysis->fecha_analisis)->toDateString(),
                        'summary' => $this->summarizeText([
                            $analysis->linea?->nombre,
                            $analysis->modulo_nombre,
                            $analysis->componente_nombre,
                            $analysis->lado,
                            $analysis->estado,
                            $analysis->actividad,
                        ]),
                        'score' => $this->scoreTokens($tokens, implode(' ', [
                            (string) ($analysis->linea?->nombre ?? ''),
                            (string) $analysis->modulo,
                            (string) $analysis->componente,
                            (string) $analysis->lado,
                            (string) $analysis->estado,
                            (string) $analysis->actividad,
                            implode(' ', $analysis->evidencia_fotos ?? []),
                        ])),
                    ])
            );
        }

        if ($user->canAccessModule(User::MODULE_ETIQUETADORA) || $this->shouldIncludeModule(User::MODULE_ETIQUETADORA, $moduleHints)) {
            $candidates = $candidates->concat(
                AnalisisEtiquetadora::query()
                    ->with(['linea', 'componente', 'usuario'])
                    ->latest('fecha_analisis')
                    ->limit(30)
                    ->get()
                    ->map(fn (AnalisisEtiquetadora $analysis): array => [
                        'module' => User::MODULE_ETIQUETADORA,
                        'type' => 'analisis_etiquetadora',
                        'reference' => 'Analisis etiquetadora #' . $analysis->id,
                        'date' => optional($analysis->fecha_analisis)->toDateString(),
                        'summary' => $this->summarizeText([
                            $analysis->linea?->nombre,
                            $analysis->componente?->nombre,
                            $analysis->maquina,
                            $analysis->estado,
                            $analysis->actividad,
                        ]),
                        'score' => $this->scoreTokens($tokens, implode(' ', [
                            (string) ($analysis->linea?->nombre ?? ''),
                            (string) ($analysis->componente?->nombre ?? ''),
                            (string) $analysis->maquina,
                            (string) $analysis->estado,
                            (string) $analysis->actividad,
                            implode(' ', $analysis->evidencia_fotos ?? []),
                        ])),
                    ])
            );
        }

        return $candidates
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->sortByDesc('score')
            ->take(max(4, (int) config('maintenance_ai.platform_context.query_match_limit', 8)))
            ->values()
            ->map(fn (array $item): array => [
                'module' => $item['module'],
                'type' => $item['type'],
                'reference' => $item['reference'],
                'date' => $item['date'],
                'summary' => $item['summary'],
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $moduleHints
     * @return array<int, array<string, mixed>>
     */
    private function recentEvidence(User $user, array $moduleHints): array
    {
        $limit = max(2, (int) config('maintenance_ai.platform_context.recent_evidence_limit', 4));
        $entries = collect();

        if ($user->canAccessModule(User::MODULE_LAVADORA) || $this->shouldIncludeModule(User::MODULE_LAVADORA, $moduleHints)) {
            $entries = $entries->concat(
                $this->currentOrPastWasherAnalysisQuery()
                    ->with(['linea', 'componente'])
                    ->latest('fecha_analisis')
                    ->limit(30)
                    ->get()
                    ->filter(fn (AnalisisLavadora $analysis): bool => count($analysis->evidencia_fotos ?? []) > 0)
                    ->take($limit)
                    ->map(fn (AnalisisLavadora $analysis): array => [
                        'module' => User::MODULE_LAVADORA,
                        'reference' => 'Analisis lavadora #' . $analysis->id,
                        'recorded_at' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toIso8601String(),
                        'linea' => $analysis->linea?->nombre,
                        'elemento' => $analysis->componente?->nombre,
                        'estado' => $analysis->estado,
                        'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 240),
                        'photo_count' => count($analysis->evidencia_fotos ?? []),
                        'photos' => $this->normalizePhotoList($analysis->evidencia_fotos ?? []),
                    ])
            );
        }

        if ($user->canAccessModule(User::MODULE_PASTEURIZADORA) || $this->shouldIncludeModule(User::MODULE_PASTEURIZADORA, $moduleHints)) {
            $entries = $entries->concat(
                AnalisisPasteurizadora::query()
                    ->withoutGlobalScope(AnalisisPasteurizadora::DEFAULT_AREA_GLOBAL_SCOPE)
                    ->with(['linea'])
                    ->latest('fecha_analisis')
                    ->limit(30)
                    ->get()
                    ->filter(fn (AnalisisPasteurizadora $analysis): bool => count($analysis->evidencia_fotos ?? []) > 0)
                    ->take($limit)
                    ->map(fn (AnalisisPasteurizadora $analysis): array => [
                        'module' => User::MODULE_PASTEURIZADORA,
                        'reference' => 'Analisis pasteurizadora #' . $analysis->id,
                        'recorded_at' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toIso8601String(),
                        'linea' => $analysis->linea?->nombre,
                        'elemento' => $analysis->componente_nombre,
                        'estado' => $analysis->estado,
                        'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 240),
                        'photo_count' => count($analysis->evidencia_fotos ?? []),
                        'photos' => $this->normalizePhotoList($analysis->evidencia_fotos ?? []),
                    ])
            );
        }

        if ($user->canAccessModule(User::MODULE_ETIQUETADORA) || $this->shouldIncludeModule(User::MODULE_ETIQUETADORA, $moduleHints)) {
            $entries = $entries->concat(
                AnalisisEtiquetadora::query()
                    ->with(['linea', 'componente'])
                    ->latest('fecha_analisis')
                    ->limit(30)
                    ->get()
                    ->filter(fn (AnalisisEtiquetadora $analysis): bool => count($analysis->evidencia_fotos ?? []) > 0)
                    ->take($limit)
                    ->map(fn (AnalisisEtiquetadora $analysis): array => [
                        'module' => User::MODULE_ETIQUETADORA,
                        'reference' => 'Analisis etiquetadora #' . $analysis->id,
                        'recorded_at' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toIso8601String(),
                        'linea' => $analysis->linea?->nombre,
                        'elemento' => $analysis->componente?->nombre,
                        'estado' => $analysis->estado,
                        'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 240),
                        'photo_count' => count($analysis->evidencia_fotos ?? []),
                        'photos' => $this->normalizePhotoList($analysis->evidencia_fotos ?? []),
                    ])
            );
        }

        if ($this->hasTable('analisis')) {
            $entries = $entries->concat(
                Analisis::query()
                    ->with(['linea', 'componente'])
                    ->latest('fecha_analisis')
                    ->limit(20)
                    ->get()
                    ->filter(fn (Analisis $analysis): bool => count($analysis->fotos ?? []) > 0)
                    ->take(2)
                    ->map(fn (Analisis $analysis): array => [
                        'module' => 'analisis_legacy',
                        'reference' => 'Analisis legacy #' . $analysis->id,
                        'recorded_at' => optional($analysis->fecha_analisis ?: $analysis->created_at)->toIso8601String(),
                        'linea' => $analysis->linea?->nombre,
                        'elemento' => $analysis->componente?->nombre,
                        'estado' => null,
                        'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 240),
                        'photo_count' => count($analysis->fotos ?? []),
                        'photos' => $this->normalizePhotoList($analysis->fotos ?? []),
                    ])
            );
        }

        return $entries
            ->sortByDesc('recorded_at')
            ->take($limit)
            ->values()
            ->map(fn (array $entry): array => [
                'module' => $entry['module'],
                'reference' => $entry['reference'],
                'linea' => $entry['linea'],
                'elemento' => $entry['elemento'],
                'estado' => $entry['estado'],
                'actividad' => $entry['actividad'],
                'photo_count' => $entry['photo_count'],
                'photos' => $entry['photos'],
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $moduleHints
     */
    private function preferredTables(array $moduleHints): array
    {
        $tables = self::MODULE_TABLE_MAP['general'];

        foreach ($moduleHints as $module) {
            foreach (self::MODULE_TABLE_MAP[$module] ?? [] as $table) {
                $tables[] = $table;
            }
        }

        foreach (self::KEY_TABLES as $table) {
            $tables[] = $table;
        }

        return array_values(array_unique($tables));
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array<int, string>
     */
    private function requestedModules(User $user, string $question, array $pageContext): array
    {
        $question = Str::lower($question . ' ' . (string) ($pageContext['module'] ?? '') . ' ' . (string) ($pageContext['current_path'] ?? ''));
        $modules = [];

        foreach ([User::MODULE_LAVADORA, User::MODULE_PASTEURIZADORA, User::MODULE_ETIQUETADORA] as $module) {
            if (str_contains($question, $module) && $user->canAccessModule($module)) {
                $modules[] = $module;
            }
        }

        if (str_contains($question, 'elongacion') && $user->canAccessModule(User::MODULE_LAVADORA)) {
            $modules[] = User::MODULE_LAVADORA;
        }

        if ($modules === []) {
            foreach ([User::MODULE_LAVADORA, User::MODULE_PASTEURIZADORA, User::MODULE_ETIQUETADORA] as $module) {
                if ($user->canAccessModule($module)) {
                    $modules[] = $module;
                }
            }
        }

        return array_values(array_unique($modules));
    }

    private function shouldIncludeModule(string $module, array $moduleHints): bool
    {
        return in_array($module, $moduleHints, true);
    }

    private function isBroadSystemQuery(string $question): bool
    {
        $tokens = $this->tokenize($question);

        return count(array_intersect($tokens, self::BROAD_QUERY_TOKENS)) > 0;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(?string $value): array
    {
        $normalized = Str::ascii(Str::lower((string) $value));
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/u', trim($normalized)) ?: [];

        return array_values(array_unique(array_filter($parts, fn ($part): bool => $this->isSearchableToken((string) $part))));
    }

    private function isSearchableToken(string $token): bool
    {
        $token = trim($token);

        if ($token === '') {
            return false;
        }

        if (ctype_digit($token)) {
            return true;
        }

        return strlen($token) > 2;
    }

    /**
     * @return Collection<int, string>
     */
    private function loadTableNames(): Collection
    {
        if ($this->tableNameCache instanceof Collection) {
            return $this->tableNameCache;
        }

        $driver = DB::connection()->getDriverName();

        $rows = match ($driver) {
            'sqlite' => DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"),
            'pgsql' => DB::select("SELECT tablename AS name FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"),
            'sqlsrv' => DB::select("SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"),
            default => DB::select('SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name'),
        };

        return $this->tableNameCache = collect($rows)
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values();
    }

    private function hasTable(string $table): bool
    {
        return $this->loadTableNames()->contains($table);
    }

    /**
     * @param  Collection<int, string>  $tables
     * @return Collection<string, Collection<int, object>>
     */
    private function loadColumnsByTable(Collection $tables): Collection
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return $tables->mapWithKeys(function (string $tableName): array {
                $quoted = str_replace('"', '""', $tableName);
                $columns = collect(DB::select(sprintf('PRAGMA table_info("%s")', $quoted)))
                    ->map(fn ($column): object => (object) [
                        'table_name' => $tableName,
                        'column_name' => (string) ($column->name ?? ''),
                    ]);

                return [$tableName => $columns];
            });
        }

        if ($driver === 'pgsql') {
            return collect(DB::select(
                "SELECT table_name, column_name
                 FROM information_schema.columns
                 WHERE table_schema = 'public'
                 ORDER BY table_name, ordinal_position"
            ))->groupBy('table_name');
        }

        if ($driver === 'sqlsrv') {
            return collect(DB::select(
                "SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
                 FROM INFORMATION_SCHEMA.COLUMNS
                 ORDER BY TABLE_NAME, ORDINAL_POSITION"
            ))->groupBy('table_name');
        }

        return collect(DB::select(
            'SELECT table_name AS table_name, column_name AS column_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             ORDER BY table_name, ordinal_position'
        ))->groupBy('table_name');
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function scoreTokens(array $tokens, string $haystack): int
    {
        if ($tokens === []) {
            return 0;
        }

        return count(array_intersect($tokens, $this->tokenize($haystack)));
    }

    private function safeTableCount(string $tableName): int
    {
        try {
            return (int) DB::table($tableName)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function countEvidenceRows(Collection $rows, string $attribute): int
    {
        return $rows->filter(function ($row) use ($attribute): bool {
            $value = data_get($row, $attribute, []);

            return is_array($value) && count(array_filter($value)) > 0;
        })->count();
    }

    private function userCanSeePlanInContext(User $user, PlanAccion $plan): bool
    {
        $tipo = Str::lower((string) ($plan->tipo_equipo ?: User::MODULE_LAVADORA));

        if (!$user->canViewPlanActionType($tipo)) {
            return false;
        }

        if ($plan->isAiSuggested()
            && $tipo === User::MODULE_LAVADORA
            && $plan->estado !== 'approved'
            && !$user->canReviewWasherAiPlans()) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function summarizeText(array $parts, int $maxLength = 320): string
    {
        return $this->sanitizer->sanitizeText(implode(' | ', array_filter(array_map(
            static fn ($value) => is_scalar($value) ? (string) $value : null,
            $parts
        ))), $maxLength);
    }

    /**
     * @param  array<int, mixed>  $photos
     * @return array<int, array<string, string>>
     */
    private function normalizePhotoList(array $photos): array
    {
        return collect($photos)
            ->filter(fn ($path): bool => is_string($path) && trim($path) !== '')
            ->take(3)
            ->map(function (string $path): array {
                $normalized = trim(str_replace('\\', '/', $path));
                $normalized = preg_replace('#^(public/|app/public/|storage/app/public/|public/storage/|storage/)#', '', $normalized) ?? $normalized;

                return [
                    'path' => $this->sanitizer->sanitizeText($normalized, 220),
                    'url' => $this->sanitizer->sanitizeText(asset('storage/' . ltrim($normalized, '/')), 300),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function planSummary(PlanAccion $plan): array
    {
        return [
            'id' => $plan->id,
            'tipo_equipo' => $plan->tipo_equipo,
            'linea' => $plan->linea?->nombre,
            'actividad' => $this->sanitizer->sanitizeText((string) $plan->actividad, 220),
            'estado' => $plan->estado,
            'prioridad' => $plan->priority_level,
            'responsable' => $plan->responsable?->name,
            'componente' => $plan->maintenanceEvent?->componente?->nombre,
            'fecha_objetivo' => optional($plan->fecha_pcm1)->toDateString(),
            'source' => $plan->source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lavadoraSummary(AnalisisLavadora $analysis): array
    {
        return [
            'id' => $analysis->id,
            'linea' => $analysis->linea?->nombre,
            'componente' => $analysis->componente?->nombre,
            'reductor' => $analysis->reductor,
            'estado' => $analysis->estado,
            'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 220),
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'evidencias' => count($analysis->evidencia_fotos ?? []),
            'usuario' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pasteurizadoraSummary(AnalisisPasteurizadora $analysis): array
    {
        return [
            'id' => $analysis->id,
            'linea' => $analysis->linea?->nombre,
            'modulo' => $analysis->modulo_nombre,
            'componente' => $analysis->componente_nombre,
            'lado' => $analysis->lado,
            'estado' => $analysis->estado,
            'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 220),
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'evidencias' => count($analysis->evidencia_fotos ?? []),
            'usuario' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function etiquetadoraSummary(AnalisisEtiquetadora $analysis): array
    {
        return [
            'id' => $analysis->id,
            'linea' => $analysis->linea?->nombre,
            'componente' => $analysis->componente?->nombre,
            'maquina' => $analysis->maquina,
            'estado' => $analysis->estado,
            'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 220),
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'evidencias' => count($analysis->evidencia_fotos ?? []),
            'usuario' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyAnalysisSummary(Analisis $analysis): array
    {
        return [
            'id' => $analysis->id,
            'linea' => $analysis->linea?->nombre,
            'componente' => $analysis->componente?->nombre,
            'reductor' => $analysis->reductor,
            'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 220),
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'evidencias' => count($analysis->fotos ?? []),
            'usuario' => $analysis->usuario?->name,
        ];
    }

    /**
     * @return Collection<int, AnalisisLavadora>
     */
    private function washerLatestAnalysisSnapshots(): Collection
    {
        return $this->currentOrPastWasherAnalysisQuery()
            ->with(['linea', 'componente', 'usuario'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (AnalisisLavadora $analysis): string => implode('|', [
                (string) $analysis->linea_id,
                (string) $analysis->componente_id,
                Str::lower(trim((string) $analysis->reductor)),
                Str::lower(trim((string) ($analysis->lado ?? ''))),
            ]))
            ->values();
    }

    /**
     * @return Collection<int, AnalisisLavadora>
     */
    private function washerProblematicAnalyses(): Collection
    {
        return $this->currentOrPastWasherAnalysisQuery()
            ->with(['linea', 'componente', 'usuario'])
            ->whereIn('estado', [
                AnalisisLavadora::ESTADO_REQUIERE_REVISION,
                'Desgaste moderado',
                'Desgaste severo',
                AnalisisLavadora::ESTADO_DANADO,
            ])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->get();
    }

    private function isProblematicWasherState(?string $state): bool
    {
        if (!$state) {
            return false;
        }

        if (AnalisisLavadora::esEstadoDanado($state)) {
            return true;
        }

        if (AnalisisLavadora::esEstadoRequiereRevision($state)) {
            return true;
        }

        return AnalisisLavadora::esEstadoDesgaste($state);
    }

    private function isCriticalWasherState(?string $state): bool
    {
        return AnalisisLavadora::esEstadoDanado($state) || $state === 'Desgaste severo';
    }

    /**
     * @param  Collection<int, AnalisisLavadora>  $problematicAnalyses
     * @return array<string, mixed>
     */
    private function washerDamagePeriods(Collection $problematicAnalyses): array
    {
        $today = CarbonImmutable::now(config('app.timezone', 'America/Mexico_City'));
        $weekStart = $today->subDays($today->dayOfWeekIso - 1)->startOfDay();
        $weekEnd = $weekStart->addDays(6)->endOfDay();
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $yearStart = $today->startOfYear();
        $yearEnd = $today->endOfYear();
        $periods = [
            'week' => [
                'label' => 'Semana actual (' . $weekStart->format('d/m/Y') . ' al ' . $weekEnd->format('d/m/Y') . ')',
                'start' => $weekStart,
            ],
            'month' => [
                'label' => 'Mes actual (' . $monthStart->format('d/m/Y') . ' al ' . $monthEnd->format('d/m/Y') . ')',
                'start' => $monthStart,
            ],
            'year' => [
                'label' => 'Ano actual (' . $yearStart->format('d/m/Y') . ' al ' . $yearEnd->format('d/m/Y') . ')',
                'start' => $yearStart,
            ],
            'all_time' => [
                'label' => 'Historico total',
                'start' => null,
            ],
        ];

        return collect($periods)
            ->mapWithKeys(function (array $period, string $key) use ($problematicAnalyses): array {
                $records = $problematicAnalyses
                    ->filter(function (AnalisisLavadora $analysis) use ($period): bool {
                        if ($period['start'] === null) {
                            return true;
                        }

                        if (!$analysis->fecha_analisis) {
                            return false;
                        }

                        return CarbonImmutable::instance($analysis->fecha_analisis)->greaterThanOrEqualTo($period['start']);
                    })
                    ->values();

                return [$key => [
                    'label' => $period['label'],
                    'damage_records' => $records->count(),
                    'state_breakdown' => $records
                        ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->estado ?: 'Sin estado')
                        ->map(fn (Collection $items, string $state): array => ['estado' => $state, 'total' => $items->count()])
                        ->sortByDesc('total')
                        ->values()
                        ->all(),
                    'top_components' => $records
                        ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->componente?->nombre ?: 'Sin componente')
                        ->map(function (Collection $items, string $componentName): array {
                            $codes = $items
                                ->map(fn (AnalisisLavadora $analysis) => $analysis->componente?->codigo)
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();

                            return [
                                'componente' => $componentName,
                                'codigo' => count($codes) === 1 ? $codes[0] : null,
                                'codigos' => $codes,
                                'total' => $items->count(),
                                'lineas' => $items->map(fn (AnalisisLavadora $analysis) => $analysis->linea?->nombre)->filter()->unique()->values()->all(),
                                'ultimo_registro' => optional($items->sortByDesc('fecha_analisis')->first()?->fecha_analisis)->toDateString(),
                            ];
                        })
                        ->sortByDesc('total')
                        ->take(8)
                        ->values()
                        ->all(),
                    'top_lines' => $records
                        ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->linea?->nombre ?: 'Sin linea')
                        ->map(fn (Collection $items, string $line): array => [
                            'linea' => $line,
                            'total' => $items->count(),
                            'criticos' => $items->filter(fn (AnalisisLavadora $analysis): bool => $this->isCriticalWasherState($analysis->estado))->count(),
                            'ultimo_registro' => optional($items->sortByDesc('fecha_analisis')->first()?->fecha_analisis)->toDateString(),
                        ])
                        ->sortByDesc('total')
                        ->take(8)
                        ->values()
                        ->all(),
                ]];
            })
            ->all();
    }

    /**
     * @param  Collection<int, AnalisisLavadora>  $problematicSnapshots
     * @return array<string, mixed>
     */
    private function washerCurrentDamageByLine(Collection $problematicSnapshots): array
    {
        $topLines = $problematicSnapshots
            ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->linea?->nombre ?: 'Sin linea')
            ->map(function (Collection $items, string $line): array {
                return [
                    'linea' => $line,
                    'problematic_components' => $items->count(),
                    'critical_components' => $items->filter(fn (AnalisisLavadora $analysis): bool => $this->isCriticalWasherState($analysis->estado))->count(),
                    'latest_review_date' => optional($items->sortByDesc('fecha_analisis')->first()?->fecha_analisis)->toDateString(),
                    'top_components' => $items
                        ->groupBy(fn (AnalisisLavadora $analysis) => $analysis->componente?->nombre ?: 'Sin componente')
                        ->map(fn (Collection $componentItems, string $name): array => ['componente' => $name, 'total' => $componentItems->count()])
                        ->sortByDesc('total')
                        ->take(5)
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('problematic_components')
            ->values();

        return [
            'label' => 'Estado actual por ultimo analisis de cada componente/reductor/lado.',
            'top_lines' => $topLines->take(8)->all(),
            'highest_line' => $topLines->first(),
        ];
    }

    /**
     * @param  Collection<int, AnalisisLavadora>  $latestSnapshots
     * @return array<string, mixed>
     */
    private function washerTargetedComponentLookup(string $question, Collection $latestSnapshots): array
    {
        $tokens = $this->tokenize($question);
        $references = $this->extractQuestionReferences($question);

        if ($tokens === [] && $references['lineas'] === [] && $references['reductores'] === []) {
            return [
                'query' => $this->sanitizer->sanitizeText($question, 180),
                'matches' => [],
            ];
        }

        $matches = $latestSnapshots
            ->map(function (AnalisisLavadora $analysis) use ($tokens, $references): array {
                $score = $this->scoreTokens($tokens, $this->analysisSearchHaystack($analysis));

                if ($references['lineas'] !== []) {
                    $linea = Str::upper((string) ($analysis->linea?->nombre ?? ''));

                    if (in_array($linea, $references['lineas'], true)) {
                        $score += 6;
                    }
                }

                if ($references['reductores'] !== []) {
                    $reductorTokens = $this->reductorNumericAliases((string) $analysis->reductor);

                    if (count(array_intersect($references['reductores'], $reductorTokens)) > 0) {
                        $score += 6;
                    }
                }

                return [
                    'score' => $score,
                    'data' => $this->currentWasherAnalysisSummary($analysis),
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->sortByDesc('score')
            ->take(6)
            ->values()
            ->map(fn (array $item): array => $item['data'])
            ->all();

        return [
            'query' => $this->sanitizer->sanitizeText($question, 180),
            'matches' => $matches,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentWasherAnalysisSummary(AnalisisLavadora $analysis): array
    {
        return [
            'id' => $analysis->id,
            'linea' => $analysis->linea?->nombre,
            'componente' => $analysis->componente?->nombre,
            'codigo' => $analysis->componente?->codigo,
            'reductor' => $analysis->reductor,
            'lado' => $analysis->lado,
            'estado' => $analysis->estado,
            'actividad' => $this->sanitizer->sanitizeText((string) $analysis->actividad, 220),
            'fecha_analisis' => optional($analysis->fecha_analisis)->toDateString(),
            'evidencias' => count($analysis->evidencia_fotos ?? []),
            'usuario' => $analysis->usuario?->name,
        ];
    }

    private function analysisSearchHaystack(AnalisisLavadora $analysis): string
    {
        $linea = (string) ($analysis->linea?->nombre ?? '');
        $componentName = (string) ($analysis->componente?->nombre ?? '');
        $componentCode = (string) ($analysis->componente?->codigo ?? '');
        $reductor = (string) $analysis->reductor;

        return implode(' ', array_filter([
            'lavadora linea componente reductor estado revision desgaste dano danado cadena servo grande chico',
            $linea,
            $this->lineAliases($linea),
            $componentName,
            $componentCode,
            $reductor,
            $this->reductorAliases($reductor),
            (string) $analysis->lado,
            (string) $analysis->estado,
            (string) $analysis->actividad,
        ]));
    }

    /**
     * @return array{lineas: array<int, string>, reductores: array<int, string>}
     */
    private function extractQuestionReferences(string $question): array
    {
        $normalized = Str::lower(Str::ascii($question));
        $lineas = [];
        $reductores = [];

        if (preg_match_all('/(?:lavadora|linea|l)\s*[-#]?\s*0*(\d{1,2})\b/u', $normalized, $lineMatches)) {
            foreach ($lineMatches[1] as $lineNumber) {
                $lineas[] = 'L-' . str_pad((string) $lineNumber, 2, '0', STR_PAD_LEFT);
            }
        }

        if (preg_match_all('/reductor\s*[-#]?\s*0*(\d{1,2})\b/u', $normalized, $reductorMatches)) {
            foreach ($reductorMatches[1] as $reductorNumber) {
                $reductores[] = ltrim((string) $reductorNumber, '0') !== ''
                    ? ltrim((string) $reductorNumber, '0')
                    : '0';
            }
        }

        return [
            'lineas' => array_values(array_unique($lineas)),
            'reductores' => array_values(array_unique($reductores)),
        ];
    }

    private function lineAliases(string $linea): string
    {
        if ($linea === '') {
            return '';
        }

        if (preg_match('/(\d{1,2})/', $linea, $matches) !== 1) {
            return $linea;
        }

        $number = ltrim($matches[1], '0');
        $number = $number === '' ? '0' : $number;
        $padded = str_pad($number, 2, '0', STR_PAD_LEFT);

        return implode(' ', [
            $linea,
            'lavadora ' . $number,
            'lavadora ' . $padded,
            'linea ' . $number,
            'linea ' . $padded,
            'l ' . $number,
            'l ' . $padded,
        ]);
    }

    private function reductorAliases(string $reductor): string
    {
        $aliases = [$reductor];

        foreach ($this->reductorNumericAliases($reductor) as $number) {
            $aliases[] = 'reductor ' . $number;
        }

        return implode(' ', array_unique(array_filter($aliases)));
    }

    /**
     * @return array<int, string>
     */
    private function reductorNumericAliases(string $reductor): array
    {
        if (preg_match_all('/\d+/u', $reductor, $matches) !== 1) {
            return [];
        }

        return array_values(array_unique(array_map(function (string $value): string {
            $trimmed = ltrim($value, '0');

            return $trimmed !== '' ? $trimmed : '0';
        }, $matches[0])));
    }

    /**
     * @return array<string, mixed>
     */
    private function elongacionSummary(Elongacion $elongacion): array
    {
        return [
            'id' => $elongacion->id,
            'linea' => $elongacion->linea,
            'proveedor' => $elongacion->proveedor,
            'estado' => $elongacion->estado,
            'estado_detallado' => $elongacion->estado_detallado,
            'bombas_porcentaje' => $elongacion->bombas_porcentaje,
            'vapor_porcentaje' => $elongacion->vapor_porcentaje,
            'max_porcentaje' => $this->elongacionMaxValue($elongacion),
            'lado_critico' => $this->elongacionCriticalSide($elongacion),
            'requiere_cambio' => (bool) $elongacion->requiere_cambio,
            'hodometro' => $elongacion->hodometro,
            'recorded_at' => optional($elongacion->created_at)->toIso8601String(),
            'ciclo' => $elongacion->cadenaCiclo?->codigo,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function washerElongationPanorama(): array
    {
        $warningThreshold = (float) config('maintenance_ai.rules.elongacion_warning_threshold', 1.30);
        $criticalThreshold = (float) config('maintenance_ai.rules.elongacion_critical_threshold', 1.46);

        $records = $this->currentOrPastElongacionQuery()
            ->with(['lineaModel', 'cadenaCiclo'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($records->isEmpty()) {
            return [
                'current_by_line' => [],
                'highest_current' => null,
                'highest_historical' => null,
                'warning_threshold' => $warningThreshold,
                'critical_threshold' => $criticalThreshold,
                'lineas_en_alerta' => 0,
                'lineas_en_cambio' => 0,
            ];
        }

        $latestByLine = $records
            ->groupBy(function (Elongacion $elongacion): string {
                return trim((string) ($elongacion->linea ?: $elongacion->lineaModel?->nombre ?: 'Sin linea'));
            })
            ->map(fn (Collection $items) => $items->first())
            ->values()
            ->sortByDesc(fn (Elongacion $elongacion): float => $this->elongacionMaxValue($elongacion))
            ->values();

        $currentByLine = $latestByLine
            ->map(fn (Elongacion $elongacion): array => $this->elongacionComparisonSummary($elongacion))
            ->all();

        $highestHistorical = $records
            ->sortByDesc(fn (Elongacion $elongacion): float => $this->elongacionMaxValue($elongacion))
            ->first();

        return [
            'current_by_line' => $currentByLine,
            'highest_current' => $currentByLine[0] ?? null,
            'highest_historical' => $highestHistorical ? $this->elongacionComparisonSummary($highestHistorical) : null,
            'warning_threshold' => $warningThreshold,
            'critical_threshold' => $criticalThreshold,
            'lineas_en_alerta' => collect($currentByLine)
                ->filter(fn (array $item): bool => (float) ($item['max_porcentaje'] ?? 0) >= $warningThreshold)
                ->count(),
            'lineas_en_cambio' => collect($currentByLine)
                ->filter(fn (array $item): bool => (float) ($item['max_porcentaje'] ?? 0) >= $criticalThreshold)
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function elongacionComparisonSummary(Elongacion $elongacion): array
    {
        $linea = trim((string) ($elongacion->linea ?: $elongacion->lineaModel?->nombre ?: 'Sin linea'));

        return [
            'id' => $elongacion->id,
            'linea' => $linea,
            'bombas_porcentaje' => round((float) $elongacion->bombas_porcentaje, 2),
            'vapor_porcentaje' => round((float) $elongacion->vapor_porcentaje, 2),
            'max_porcentaje' => $this->elongacionMaxValue($elongacion),
            'critical_side' => $this->elongacionCriticalSide($elongacion),
            'estado' => $elongacion->estado,
            'estado_detallado' => $elongacion->estado_detallado,
            'proveedor' => $elongacion->proveedor,
            'ciclo' => $elongacion->cadenaCiclo?->codigo,
            'recorded_at' => optional($elongacion->created_at)->toDateString(),
        ];
    }

    private function elongacionMaxValue(Elongacion $elongacion): float
    {
        return round(max((float) $elongacion->bombas_porcentaje, (float) $elongacion->vapor_porcentaje), 2);
    }

    private function elongacionCriticalSide(Elongacion $elongacion): string
    {
        return (float) $elongacion->bombas_porcentaje >= (float) $elongacion->vapor_porcentaje
            ? 'bombas'
            : 'vapor';
    }

    private function currentBusinessDate(): CarbonImmutable
    {
        return CarbonImmutable::today(config('app.timezone', 'America/Mexico_City'));
    }

    private function currentOrPastWasherAnalysisQuery(): Builder
    {
        return AnalisisLavadora::query()
            ->whereDate('fecha_analisis', '<=', $this->currentBusinessDate()->toDateString());
    }

    private function currentOrPastElongacionQuery(): Builder
    {
        return Elongacion::query()
            ->where('created_at', '<=', $this->currentBusinessDate()->endOfDay());
    }

    /**
     * @return array<string, mixed>
     */
    private function costEntrySummary(LavadoraCostEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'linea' => $entry->linea?->nombre,
            'componente' => $entry->componente?->nombre,
            'concepto' => $entry->catalogItem?->name ?? $entry->catalog_name_snapshot,
            'origen' => LavadoraCostEntry::sourceLabel($entry->source_type),
            'costo_total' => $entry->total_cost,
            'fecha' => optional($entry->cost_date)->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function knowledgeDocumentSummary(WasherKnowledgeDocument $document): array
    {
        return [
            'id' => $document->id,
            'titulo' => $this->sanitizer->sanitizeText((string) $document->title, 180),
            'tipo' => $document->document_type,
            'linea' => $document->linea?->nombre,
            'componente' => $document->componente?->nombre,
            'estado' => $document->lifecycle_status,
            'indexacion' => $document->indexing_status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceEventSummary(MaintenanceEvent $event): array
    {
        return [
            'id' => $event->id,
            'module' => $this->moduleFromMaintenanceEvent($event),
            'linea' => $event->linea?->nombre,
            'componente' => $event->componente?->nombre,
            'titulo' => $this->sanitizer->sanitizeText((string) $event->title, 180),
            'tipo' => $event->event_type,
            'severidad' => $event->severity,
            'estado' => $event->status,
            'detectado_en' => optional($event->detected_at ?: $event->created_at)->toIso8601String(),
        ];
    }

    private function moduleFromMaintenanceEvent(MaintenanceEvent $event): string
    {
        $sourceType = Str::lower((string) $event->source_type);

        return match ($sourceType) {
            'analisis_lavadora', 'elongacion', 'washer_knowledge_document', 'lavadora_cost_entry' => User::MODULE_LAVADORA,
            'analisis_pasteurizadora' => User::MODULE_PASTEURIZADORA,
            'analisis_etiquetadora' => User::MODULE_ETIQUETADORA,
            default => Str::lower((string) ($event->componente?->tipo_equipo ?: 'general')),
        };
    }
}
