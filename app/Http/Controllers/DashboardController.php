<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\AnalisisEtiquetadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\Elongacion;
use App\Models\PlanAccion;
use App\Models\AnalisisPasteurizadora;
use App\Models\User;
use App\Services\TendenciaDanosService;
use App\Services\LavadoraCostAnalyticsService;
use App\Services\NotificationVisibilityService;
use App\Support\EtiquetadoraCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private const LAVADORA_TREND_COMPONENT_STATES = [
        'danado - requiere cambio',
        'dano - requiere cambio',
        'desgaste severo',
        'desgaste moderado',
    ];
    private const LAVADORA_DAMAGE_STATE_KEYS = [
        'danado - requiere cambio',
        'dano - requiere cambio',
        'desgaste severo',
        'desgaste moderado',
    ];
    private const LAVADORA_CRITICAL_STATE_KEYS = ['danado - requiere cambio', 'dano - requiere cambio'];
    private const LAVADORA_SEVERE_STATE_KEYS = ['desgaste severo', 'desgaste moderado'];
    private const LAVADORA_REVIEW_STATE_KEYS = ['requiere revision'];

    private const LAVADORA_NOMBRES = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];
    private const ETIQUETADORA_NOMBRES = ['L-04', 'L-05', 'L-06', 'L-10', 'L-12', 'L-13'];
    private const LAVADORA_DAMAGE_STATES = ['Dañado - Requiere cambio', 'Desgaste severo', 'Desgaste moderado'];
    private const LAVADORA_SEVERE_STATES = ['Desgaste severo', 'Desgaste moderado'];
    private const LAVADORA_REVIEW_STATES = ['Requiere revisión'];

    /**
     * ===========================================================
     * RUTAS DASHBOARD GLOBAL (MÓDULOS)
     * ===========================================================
     */
    
    /**
     * RUTA: GET /dashboard
     * NOMBRE: dashboard
     * VISTA: dashboard-modulos.blade.php
     * DESCRIPCIÓN: Muestra la vista principal de selección de módulos
     */
    public function index()
    {
        $user = auth()->user();

        if ($user?->usesTechnicianAccessProfile()) {
            return redirect()->route('tecnico.dashboard');
        }

        $pasteurizadoraComingSoon = $user?->shouldShowPasteurizadoraComingSoon() ?? false;

        // Configuración de módulos disponibles (escalable para futuro)
        $modulos = [
            [
                'id' => 'lavadora',
                'nombre' => 'Lavadoras',
                'descripcion' => '',
                'icono' => 'fa-industry',
                'imagen_personalizada' => true,
                'icono_imagen' => 'images/icono-maquina-cover.png',
                'color' => 'blue',
                'ruta' => route('dashboard.global.lavadoras'),
                'estadisticas' => $this->getLavadoraStats(),
                'activo' => true
            ],
            [
                'id' => 'etiquetadora',
                'nombre' => 'Etiquetadoras',
                'descripcion' => '',
                'icono' => 'fa-tags',
                'imagen_personalizada' => true,
                'icono_imagen' => 'images/Etiquetas/Modelo especial.png',
                'color' => 'blue',
                'ruta' => route('dashboard.global.etiquetadoras'),
                'estadisticas' => $this->getEtiquetadoraStats(),
                'activo' => $user?->canAccessModule(User::MODULE_ETIQUETADORA) ?? true,
            ],
        ];

        if ($user?->shouldSeePasteurizadoraShortcut()) {
            $modulos[] = [
                'id' => 'pasteurizadora',
                'nombre' => 'Pasteurizadoras',
                'descripcion' => '',
                'icono' => 'fa-temperature-high',
                'imagen_personalizada' => true,
                'icono_imagen' => 'images/icono-pas-cover.png',
                'color' => 'orange',
                'ruta' => route('dashboard.global.pasteurizadoras'),
                'estadisticas' => $this->getPasteurizadoraStats(),
                'activo' => true,
                'bloqueado' => $pasteurizadoraComingSoon,
                'mensaje_bloqueo' => 'Estamos trabajando en ello, estara disponible muy pronto.',
            ];
        }

        return view('dashboard-modulos', compact('modulos'));
    }

    public function tecnico()
    {
        $resumen = [
            'lavadora' => [
                'total' => AnalisisLavadora::count(),
                'hoy' => AnalisisLavadora::whereDate('created_at', today())->count(),
            ],
            'pasteurizadora' => [
                'total' => AnalisisPasteurizadora::count(),
                'hoy' => AnalisisPasteurizadora::whereDate('created_at', today())->count(),
            ],
        ];

        return view('tecnico.dashboard', compact('resumen'));
    }

    /**
     * Resuelve el rango de fechas aplicado a los modulos de tendencia.
     */
    private function resolveLavadoraTrendDateRange(Request $request, string $fromKey, string $toKey): array
    {
        $defaultRange = $this->getLavadoraTrendDefaultDateRange();
        $from = $this->parseLavadoraTrendDate($request->query($fromKey))
            ?: (($defaultRange['from'] ?? null) instanceof Carbon ? $defaultRange['from']->copy() : null);
        $to = $this->parseLavadoraTrendDate($request->query($toKey))
            ?: (($defaultRange['to'] ?? null) instanceof Carbon ? $defaultRange['to']->copy() : null);

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from?->copy()->startOfDay(),
            'to' => $to?->copy()->endOfDay(),
            'from_input' => $from?->toDateString(),
            'to_input' => $to?->toDateString(),
        ];
    }

    /**
     * Rango por defecto para las tarjetas de tendencia.
     */
    private function getLavadoraTrendDefaultDateRange(): array
    {
        return [
            'from' => null,
            'to' => now()->copy()->endOfDay(),
        ];
    }

    /**
     * Convierte una fecha del request a Carbon sin romper el dashboard.
     */
    private function parseLavadoraTrendDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Filtra el historico de analisis solo para los modulos de tendencia.
     */
    private function filterLavadoraTrendAnalisisByDateRange(Collection $analisisHistoricos, ?array $dateRange = null): Collection
    {
        if (!$this->hasLavadoraTrendDateRange($dateRange)) {
            return $analisisHistoricos;
        }

        return $analisisHistoricos
            ->filter(function (AnalisisLavadora $item) use ($dateRange) {
                return $this->isLavadoraTrendDateInRange($item->fecha_analisis?->copy()->startOfDay(), $dateRange);
            })
            ->values();
    }

    /**
     * Indica si existe al menos un limite de fecha para tendencia.
     */
    private function hasLavadoraTrendDateRange(?array $dateRange): bool
    {
        return $dateRange
            && (($dateRange['from'] ?? null) instanceof Carbon || ($dateRange['to'] ?? null) instanceof Carbon);
    }

    /**
     * Valida si una fecha cae dentro del rango seleccionado.
     */
    private function isLavadoraTrendDateInRange(?Carbon $fecha, ?array $dateRange): bool
    {
        if (!$fecha) {
            return false;
        }

        if (!$this->hasLavadoraTrendDateRange($dateRange)) {
            return true;
        }

        if (($dateRange['from'] ?? null) instanceof Carbon && $fecha->lt($dateRange['from'])) {
            return false;
        }

        if (($dateRange['to'] ?? null) instanceof Carbon && $fecha->gt($dateRange['to'])) {
            return false;
        }

        return true;
    }

    /**
     * ===========================================================
     * DASHBOARDS GLOBALES (VISTAS PRINCIPALES)
     * ===========================================================
     */

    /**
     * RUTA: GET /dashboard/global/lavadoras
     * NOMBRE: dashboard.global.lavadoras
     * VISTA: dashboard_lavadora.blade.php
     * DESCRIPCIÓN: Dashboard global de lavadoras
     */
    public function lavadoraGlobal(Request $request)
    {
        $lineasLavadora = Linea::where('activo', true)
            ->whereIn('nombre', self::LAVADORA_NOMBRES)
            ->orderBy('nombre')
            ->get();

        $todasLasLineasLavadora = Linea::whereIn('nombre', self::LAVADORA_NOMBRES)
            ->orderBy('nombre')
            ->get();

        $trend52124Range = $this->resolveLavadoraTrendDateRange($request, 'trend_52124_desde', 'trend_52124_hasta');
        $trend30147Range = $this->resolveLavadoraTrendDateRange($request, 'trend_30147_desde', 'trend_30147_hasta');

        $analisisActuales = $this->getAnalisisActualesLavadoras($lineasLavadora);
        $analisisHistoricos = $this->getAnalisisHistoricosLavadoras($lineasLavadora);

        $resumenGeneral = $this->getResumenGeneral($lineasLavadora);
        $estadoLavadoras = $this->getEstadoLavadoras($lineasLavadora);
        $fallasPorLinea = $this->getFallasPorLinea($lineasLavadora, $analisisActuales);
        $planesAccionDashboard = $this->getPlanesAccionDashboard($lineasLavadora);
        $componentesDanados = [];
        $rankingDanos = $this->getRankingDanosPorLavadora($todasLasLineasLavadora);
        $evolucionElongaciones = $this->getEvolucionElongaciones($lineasLavadora);
        $historicoRevisiones = $this->getHistoricoRevisiones($lineasLavadora, $analisisHistoricos);
        $lavadoraCostSummary = app(LavadoraCostAnalyticsService::class)->dashboardData([
            'preset' => 'anual',
            'budget_year' => now()->year,
        ]);
        $tendenciaDanos = app(TendenciaDanosService::class);
        $analisis52124 = $tendenciaDanos->construirDashboard(
            $lineasLavadora,
            TendenciaDanosService::TIPO_LAVADORAS,
            $tendenciaDanos->ventanas52124(),
            $trend52124Range
        );
        $analisis30147 = $tendenciaDanos->construirDashboard(
            $lineasLavadora,
            TendenciaDanosService::TIPO_LAVADORAS,
            $tendenciaDanos->ventanas30147(),
            $trend30147Range
        );
        $trendFilters = [
            'tendencia' => [
                'from_input' => $trend52124Range['from_input'],
                'to_input' => $trend52124Range['to_input'],
                'from_param' => 'trend_52124_desde',
                'to_param' => 'trend_52124_hasta',
            ],
            'tendencia30147' => [
                'from_input' => $trend30147Range['from_input'],
                'to_input' => $trend30147Range['to_input'],
                'from_param' => 'trend_30147_desde',
                'to_param' => 'trend_30147_hasta',
            ],
        ];

        return view('dashboard_lavadora', compact(
            'lineasLavadora',
            'resumenGeneral',
            'estadoLavadoras',
            'rankingDanos',
            'fallasPorLinea',
            'componentesDanados',
            'planesAccionDashboard',
            'evolucionElongaciones',
            'historicoRevisiones',
            'lavadoraCostSummary',
            'analisis52124',
            'analisis30147',
            'trendFilters'
        ));
    }

    /**
     * RUTA: GET /dashboard/global/pasteurizadoras
     * NOMBRE: dashboard.global.pasteurizadoras
     * VISTA: dashboard_pasteurizadora.blade.php
     * DESCRIPCIÓN: Dashboard global de pasteurizadoras
     */
   public function pasteurizadoraGlobal(Request $request)
{
    if (!auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)) {
        return redirect()
            ->route('lavadora.dashboard')
            ->with('pasteurizadora_bloqueada', 'Estamos trabajando en ello, estará disponible muy pronto.');
    }

    $pasteurizadoras = Linea::whereIn('nombre', [
        'P-03','P-04','P-05','P-06','P-07','P-08','P-09','P-10','P-11','P-12','P-13','P-14'
    ])->get();

    $trend52124Range = $this->resolveLavadoraTrendDateRange($request, 'trend_52124_desde', 'trend_52124_hasta');
    $trend30147Range = $this->resolveLavadoraTrendDateRange($request, 'trend_30147_desde', 'trend_30147_hasta');

    $analisis = AnalisisPasteurizadora::with('linea')
        ->where('resuelto_por_cambio', false)
        ->get();

    $analisisHistorico = AnalisisPasteurizadora::with('linea')
        ->whereIn('linea_id', $pasteurizadoras->pluck('id'))
        ->get();

    $estadoPasteurizadoras = collect($this->getEstadoPasteurizadoras($pasteurizadoras, $analisis));
    $planesPendientesPasteurizadora = $this->getPlanesPendientesPasteurizadora($pasteurizadoras, $analisis);

    $resumenPasteurizadora = [
        'total_pasteurizadoras' => $pasteurizadoras->count(),
        'total_analisis' => $analisisHistorico->count(),
        'alertas_criticas' => $estadoPasteurizadoras->where('estado.nivel', 'critico')->count(),
        'en_riesgo' => $estadoPasteurizadoras->where('estado.nivel', 'riesgo')->count(),
        'requiere_revision' => $estadoPasteurizadoras->where('estado.nivel', 'operativo')->count(),
        'buen_estado' => $estadoPasteurizadoras->where('estado.nivel', 'bueno')->count(),
        'pendientes_accion' => $planesPendientesPasteurizadora->count(),
        'ultima_actualizacion' => now()->format('d/m/Y H:i'),
    ];

    $fallasPorLineaPasteurizadora = $this->getFallasPorLineaPasteurizadora($pasteurizadoras, $analisisHistorico);
    $componentesDanadosPasteurizadora = $this->getComponentesDanadosPasteurizadora($analisisHistorico);
    $historicoRevisionesPasteurizadora = $this->getHistoricoRevisionesPasteurizadora($analisisHistorico);
    $tendenciaDanos = app(TendenciaDanosService::class);
    $analisis52124Pasteurizadora = $tendenciaDanos->construirDashboard(
        $pasteurizadoras,
        TendenciaDanosService::TIPO_PASTEURIZADORAS,
        $tendenciaDanos->ventanas52124(),
        $trend52124Range
    );
    $analisis30147Pasteurizadora = $tendenciaDanos->construirDashboard(
        $pasteurizadoras,
        TendenciaDanosService::TIPO_PASTEURIZADORAS,
        $tendenciaDanos->ventanas30147(),
        $trend30147Range
    );
    $trendFilters = [
        'tendencia' => [
            'from_input' => $trend52124Range['from_input'],
            'to_input' => $trend52124Range['to_input'],
            'from_param' => 'trend_52124_desde',
            'to_param' => 'trend_52124_hasta',
        ],
        'tendencia30147' => [
            'from_input' => $trend30147Range['from_input'],
            'to_input' => $trend30147Range['to_input'],
            'from_param' => 'trend_30147_desde',
            'to_param' => 'trend_30147_hasta',
        ],
    ];
    $ultimosAnalisisPasteurizadora = $analisisHistorico->sortByDesc('fecha_analisis')->take(8)->values();

    return view('dashboard_pasteurizadora', compact(
        'resumenPasteurizadora',
        'estadoPasteurizadoras',
        'fallasPorLineaPasteurizadora',
        'componentesDanadosPasteurizadora',
        'historicoRevisionesPasteurizadora',
        'analisis52124Pasteurizadora',
        'analisis30147Pasteurizadora',
        'trendFilters',
        'planesPendientesPasteurizadora',
        'ultimosAnalisisPasteurizadora'
    ));
}

    /**
     * ===========================================================
     * DASHBOARDS OPERATIVOS (CON DATOS)
     * ===========================================================
     */

    /**
     * RUTA: GET /dashboard/operativo/lavadora
     * NOMBRE: dashboard.operativo.lavadora
     * VISTA: lavadora/dashboard-lavadora.blade.php
     * DESCRIPCIÓN: Dashboard operativo de lavadoras con todas las métricas
     */
    public function lavadoraOperativo(Request $request)
    {
        // 1. Obtener todas las líneas de lavadora activas
        $lineasLavadora = Linea::where('activo', true)
            ->whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->orderBy('nombre')
            ->get();

        // 2. Datos para las tarjetas de resumen
        $resumenGeneral = $this->getResumenGeneral($lineasLavadora);

        // 3. Datos para la tabla de estado de lavadoras y alertas
        $estadoLavadoras = $this->getEstadoLavadoras($lineasLavadora);

        // 4. Datos para el ranking de lavadoras con mayor daño (predictivo)
        $rankingDanos = $this->getRankingDanos($lineasLavadora);

        // 5. Datos para la gráfica de fallas por línea (últimos 12 meses)
        $fallasPorLinea = $this->getFallasPorLinea($lineasLavadora);

        // 6. Datos para la gráfica de componentes más dañados
        $componentesDanados = $this->getComponentesDanados($lineasLavadora);

        // 7. Datos para la gráfica de evolución de elongaciones
        $evolucionElongaciones = $this->getEvolucionElongaciones($lineasLavadora);

        // 8. Datos para el histórico de revisiones (conteo de análisis por componente)
        $historicoRevisiones = $this->getHistoricoRevisiones($lineasLavadora);

        // 9. Datos para el análisis 52-12-4 (últimos registros)
        $tendenciaDanos = app(TendenciaDanosService::class);
        $analisis52124 = $tendenciaDanos->construirDashboard(
            $lineasLavadora,
            TendenciaDanosService::TIPO_LAVADORAS,
            $tendenciaDanos->ventanas52124()
        );
        $analisis30147 = $tendenciaDanos->construirDashboard(
            $lineasLavadora,
            TendenciaDanosService::TIPO_LAVADORAS,
            $tendenciaDanos->ventanas30147()
        );

        return view('lavadora.dashboard-lavadora', compact(
            'lineasLavadora',
            'resumenGeneral',
            'estadoLavadoras',
            'rankingDanos',
            'fallasPorLinea',
            'componentesDanados',
            'evolucionElongaciones',
            'historicoRevisiones',
            'analisis52124',
            'analisis30147'
        ));
    }

    /**
     * RUTA: GET /dashboard/operativo/pasteurizadora
     * NOMBRE: dashboard.operativo.pasteurizadora
     * VISTA: pasteurizadora/dashboard.blade.php
     * DESCRIPCIÓN: Dashboard operativo de pasteurizadoras
     */
public function pasteurizadoraOperativo(Request $request)
{
    if (!auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)) {
        return redirect()
            ->route('lavadora.dashboard')
            ->with('pasteurizadora_bloqueada', 'Próximamente estamos trabajando en ello, estará disponible muy pronto.');
    }

    $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
    $pasteurizadoras = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->get();

    $analisisPasteurizadora = AnalisisPasteurizadora::with('linea')
        ->where('resuelto_por_cambio', false)
        ->get();

    $estadoPasteurizadoras = $this->getEstadoPasteurizadoras($pasteurizadoras, $analisisPasteurizadora);
    $estadoPasteurizadorasResumen = collect($estadoPasteurizadoras);

    $resumenPasteurizadora = [
        'total_pasteurizadoras' => $pasteurizadoras->count(),
        'total_analisis' => $analisisPasteurizadora->count(),
        'alertas_criticas' => $estadoPasteurizadorasResumen->where('estado.nivel', 'critico')->count(),
        'en_riesgo' => $estadoPasteurizadorasResumen->where('estado.nivel', 'riesgo')->count(),
        'requiere_revision' => $estadoPasteurizadorasResumen->where('estado.nivel', 'operativo')->count(),
        'buen_estado' => $estadoPasteurizadorasResumen->where('estado.nivel', 'bueno')->count(),
        'pendientes_accion' => $estadoPasteurizadorasResumen->sum('estado.acciones_pendientes'),
        'ultima_actualizacion' => now()->format('d/m/Y H:i')
    ];

    return view('pasteurizadora.dashboard', compact(
        'resumenPasteurizadora',
        'estadoPasteurizadoras'
    ));
}

    public function etiquetadoraGlobal(Request $request)
    {
        abort_unless(auth()->user()?->canAccessModule(User::MODULE_ETIQUETADORA), 403, 'No tienes permiso para acceder al modulo de Etiquetadora.');

        $todasLasLineas = $this->getLineasEtiquetadora();
        $filtrosEtiquetadora = $this->resolveEtiquetadoraDashboardFilters($request, $todasLasLineas);
        $lineas = $filtrosEtiquetadora['linea']
            ? collect([$filtrosEtiquetadora['linea']])
            : $todasLasLineas;
        $catalogo = $this->getCatalogoEtiquetadora($filtrosEtiquetadora, $lineas);
        $ultimos = $this->getUltimosAnalisisEtiquetadora($lineas, $filtrosEtiquetadora);
        $analisisPeriodo = $this->getAnalisisPeriodoEtiquetadora($lineas, $filtrosEtiquetadora);
        $estadoEtiquetadoras = $this->getEstadoEtiquetadoras($lineas, $catalogo, $ultimos);

        return view('dashboard_etiquetadora', [
            'todasLasLineasEtiquetadora' => $todasLasLineas,
            'lineasEtiquetadora' => $lineas,
            'maquinasEtiquetadora' => collect(EtiquetadoraCatalog::maquinas()),
            'filtrosEtiquetadora' => $filtrosEtiquetadora,
            'dashboardLinksEtiquetadora' => $this->getDashboardLinksEtiquetadora($filtrosEtiquetadora),
            'resumenEtiquetadora' => $this->getResumenEtiquetadoraGlobal($lineas, $catalogo, $ultimos, $analisisPeriodo, $filtrosEtiquetadora),
            'estadoEtiquetadoras' => $estadoEtiquetadoras,
            'fallasPorLineaEtiquetadora' => $this->getFallasPorLineaEtiquetadora($lineas, $catalogo, $ultimos),
            'componentesDanadosEtiquetadora' => $this->getComponentesDanadosEtiquetadora($ultimos),
            'rankingEtiquetadoras' => $this->getRankingEtiquetadoras($estadoEtiquetadoras),
            'planesPendientesEtiquetadora' => $this->getPlanesPendientesEtiquetadora($lineas, $filtrosEtiquetadora),
            'historicoRevisionesEtiquetadora' => $this->getHistoricoRevisionesEtiquetadora($lineas, $filtrosEtiquetadora),
            'ultimosAnalisisEtiquetadora' => $this->getUltimosRegistrosEtiquetadora($lineas, $filtrosEtiquetadora),
            'evidenciasEtiquetadora' => $this->getEvidenciasEtiquetadoraSummary($analisisPeriodo),
            'notificacionesEtiquetadora' => $this->getNotificacionesEtiquetadoraSummary($request->user()),
            'catalogoEtiquetadoraResumen' => $this->getCatalogoEtiquetadoraResumen($catalogo),
        ]);
    }

    public function etiquetadora(Request $request)
    {
        abort_unless(auth()->user()?->canAccessModule(User::MODULE_ETIQUETADORA), 403, 'No tienes permiso para acceder al modulo de Etiquetadora.');

        return view('etiquetadora.dashboard');
    }

    /**
     * ===========================================================
     * MÉTODO DE COMPATIBILIDAD (BACKWARD COMPATIBILITY)
     * Mantiene las rutas anteriores para no romper código existente
     * ===========================================================
     */

    /**
     * DEPRECADO: Usar lavadoraOperativo() en su lugar
     * Mantenido por compatibilidad con rutas existentes
     */
    public function lavadora(Request $request)
    {
        return $this->lavadoraOperativo($request);
    }

    /**
     * DEPRECADO: Usar pasteurizadoraOperativo() en su lugar
     * Mantenido por compatibilidad con rutas existentes
     */
   public function pasteurizadora(Request $request)
{
    return $this->pasteurizadoraOperativo($request);
}

    /**
     * ===========================================================
     * RUTA: GET /api/danos-tendencia
     * NOMBRE: api.danos-tendencia
     * DESCRIPCIÓN: API para obtener datos de tendencia de daños (gráficas dinámicas)
     * ===========================================================
     */
    public function getDanosTendenciaApi(Request $request)
    {
        $lineas = Linea::where('activo', true)
            ->whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->orderBy('nombre')
            ->get();

        $tendenciaDanos = app(TendenciaDanosService::class);
        $datos = $lineas->mapWithKeys(function (Linea $linea) use ($tendenciaDanos) {
            return [
                $linea->nombre => $tendenciaDanos
                    ->construirFilasMensuales($linea, TendenciaDanosService::TIPO_LAVADORAS)
                    ->sortBy(fn ($item) => sprintf('%04d%02d', $item->anio, $item->mes))
                    ->values(),
            ];
        });

        $resultado = [];
        foreach ($datos as $lineaNombre => $registros) {
            $resultado[$lineaNombre] = $registros->map(function($item) {
                return [
                    'periodo' => Carbon::create($item->anio, $item->mes, 1)->format('Y-m'),
                    'total_danos_4_semanas' => $item->total_danos_4_semanas,
                    'total_danos_12_semanas' => $item->total_danos_12_semanas,
                    'total_danos_52_semanas' => $item->total_danos_52_semanas,
                ];
            });
        }

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }

    /*
    | ======================================================================
    | MÉTODOS PRIVADOS - LÓGICA DE NEGOCIO
    | ======================================================================
    */

    /**
     * Obtiene estadísticas resumidas para el módulo de lavadoras.
     */
    private function getLavadoraStats()
    {
        $lineasLavadora = Linea::where('activo', true)
            ->whereIn('nombre', self::LAVADORA_NOMBRES)
            ->orderBy('nombre')
            ->get();

        $alertasCriticas = 0;
        $enRiesgo = 0;
        $requiereRevision = 0;
        $buenEstado = 0;

        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            if ($estado['nivel'] === 'critico') {
                $alertasCriticas++;
            } elseif ($estado['nivel'] === 'riesgo') {
                $enRiesgo++;
            } elseif ($estado['nivel'] === 'operativo') {
                $requiereRevision++;
            } else {
                $buenEstado++;
            }
        }

        return [
            'total_equipos' => $lineasLavadora->count(),
            'alertas_criticas' => $alertasCriticas,
            'en_riesgo' => $enRiesgo,
            'requiere_revision' => $requiereRevision,
            'buen_estado' => $buenEstado,
            'ultima_actualizacion' => now()->format('d/m/Y H:i')
        ];
    }

    /**
     * Obtiene estadísticas resumidas para el módulo de pasteurizadoras.
     */
    private function getPasteurizadoraStats()
    {
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $pasteurizadoras = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->get();
        $analisis = AnalisisPasteurizadora::with('linea')
            ->where('resuelto_por_cambio', false)
            ->get();
        $estadoPasteurizadoras = collect($this->getEstadoPasteurizadoras($pasteurizadoras, $analisis));

        return [
            'total_equipos' => $pasteurizadoras->count(),
            'alertas_criticas' => $estadoPasteurizadoras->where('estado.nivel', 'critico')->count(),
            'en_riesgo' => $estadoPasteurizadoras->where('estado.nivel', 'riesgo')->count(),
            'requiere_revision' => $estadoPasteurizadoras->where('estado.nivel', 'operativo')->count(),
            'buen_estado' => $estadoPasteurizadoras->where('estado.nivel', 'bueno')->count(),
            'ultima_actualizacion' => now()->format('d/m/Y H:i')
        ];
    }

    private function getEtiquetadoraStats(): array
    {
        $lineas = $this->getLineasEtiquetadora();
        $catalogo = $this->getCatalogoEtiquetadora([], $lineas);
        $ultimos = $this->getUltimosAnalisisEtiquetadora($lineas);
        $estadoEtiquetadoras = $this->getEstadoEtiquetadoras($lineas, $catalogo, $ultimos);

        return [
            'total_equipos' => $this->countEtiquetadoraEquiposFromCatalogo($catalogo),
            'alertas_criticas' => $estadoEtiquetadoras->where('estado.nivel', 'critico')->count(),
            'en_riesgo' => $estadoEtiquetadoras->where('estado.nivel', 'riesgo')->count(),
            'requiere_revision' => $estadoEtiquetadoras->where('estado.nivel', 'operativo')->count(),
            'buen_estado' => $estadoEtiquetadoras->where('estado.nivel', 'bueno')->count(),
            'ultima_actualizacion' => now()->format('d/m/Y H:i'),
        ];
    }

    private function getEtiquetadoraModuleStats(): array
    {
        $lineas = $this->getLineasEtiquetadora();
        $catalogo = $this->getCatalogoEtiquetadora([], $lineas);
        $ultimos = $this->getUltimosAnalisisEtiquetadora($lineas);

        return [
            'componentes_catalogo' => $catalogo->count(),
            'analisis_total' => AnalisisEtiquetadora::whereIn('linea_id', $lineas->pluck('id'))->count(),
            'criticos' => $ultimos->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDanado($analisis->estado))->count(),
            'avance' => $catalogo->count() > 0 ? round(($ultimos->pluck('componente_id')->filter()->unique()->count() / $catalogo->count()) * 100, 1) : 0,
        ];
    }

    private function getLineasEtiquetadora(): Collection
    {
        return Linea::where('activo', true)
            ->whereIn('nombre', EtiquetadoraCatalog::lineas())
            ->orderBy('nombre')
            ->get();
    }

    private function resolveEtiquetadoraDashboardFilters(Request $request, Collection $lineas): array
    {
        $linea = null;
        $lineaId = $request->query('linea_id');

        if (filled($lineaId) && $lineaId !== 'todas') {
            $linea = $lineas->firstWhere('id', (int) $lineaId);
        }

        $maquina = strtoupper(trim((string) $request->query('maquina', '')));
        if (!in_array($maquina, EtiquetadoraCatalog::maquinas(), true)) {
            $maquina = null;
        }

        $fechaDesde = $this->parseLavadoraTrendDate($request->query('fecha_desde'))?->startOfDay();
        $fechaHasta = $this->parseLavadoraTrendDate($request->query('fecha_hasta'))?->endOfDay();

        if ($fechaDesde && $fechaHasta && $fechaDesde->gt($fechaHasta)) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta->copy()->startOfDay(), $fechaDesde->copy()->endOfDay()];
        }

        $query = [];

        if ($linea) {
            $query['linea_id'] = $linea->id;
        }

        if ($maquina) {
            $query['maquina'] = $maquina;
        }

        if ($fechaDesde) {
            $query['fecha_desde'] = $fechaDesde->toDateString();
        }

        if ($fechaHasta) {
            $query['fecha_hasta'] = $fechaHasta->toDateString();
        }

        return [
            'linea' => $linea,
            'linea_id' => $linea?->id,
            'maquina' => $maquina,
            'maquina_label' => $maquina ? EtiquetadoraCatalog::maquinaLabel($maquina) : null,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'fecha_desde_input' => $fechaDesde?->toDateString(),
            'fecha_hasta_input' => $fechaHasta?->toDateString(),
            'query' => $query,
            'has_filters' => $linea !== null || $maquina !== null || $fechaDesde !== null || $fechaHasta !== null,
        ];
    }

    private function getDashboardLinksEtiquetadora(array $filters): array
    {
        $lineaId = $filters['linea_id'] ?? null;
        $maquina = $filters['maquina'] ?? null;
        $fechaDesde = ($filters['fecha_desde'] ?? null) instanceof Carbon ? $filters['fecha_desde'] : null;
        $fechaHasta = ($filters['fecha_hasta'] ?? null) instanceof Carbon ? $filters['fecha_hasta'] : null;

        $analisisParams = ['linea_id' => $lineaId ?: 'todas'];
        $historialParams = [];
        $planParams = ['tipo' => User::MODULE_ETIQUETADORA];
        $reportParams = ['tipo' => 'etiquetadoras'];

        if ($lineaId) {
            $historialParams['linea_id'] = $lineaId;
            $planParams['linea_id'] = $lineaId;
            $reportParams['linea_id'] = $lineaId;
        }

        if ($maquina) {
            $analisisParams['maquina'] = $maquina;
            $historialParams['maquina'] = $maquina;
        }

        $fechaMes = $this->monthFilterFromEtiquetadoraRange($fechaDesde, $fechaHasta);
        if ($fechaMes) {
            $analisisParams['fecha'] = $fechaMes;
        }

        if ($fechaDesde) {
            $reportParams['fecha_inicio'] = $fechaDesde->toDateString();
        }

        if ($fechaHasta) {
            $reportParams['fecha_fin'] = $fechaHasta->toDateString();
        }

        return [
            'analisis' => route('analisis-etiquetadora.index', $analisisParams),
            'historico' => route('analisis-etiquetadora.historial', $historialParams),
            'componentes' => route('analisis-etiquetadora.index', $analisisParams),
            'catalogo' => route('analisis-etiquetadora.index', $analisisParams),
            'evidencias' => route('analisis-etiquetadora.historial', $historialParams),
            'reportes' => route('reportes.index', $reportParams),
            'plan_accion' => route('plan-accion.index', $planParams),
            'plan_accion_create' => route('plan-accion.create', $planParams),
            'notificaciones' => route('notifications.index'),
            'notificaciones_configuracion' => route('notificaciones.configuracion'),
            'nuevo_analisis' => route('analisis-etiquetadora.select-linea'),
            'costos' => null,
        ];
    }

    private function monthFilterFromEtiquetadoraRange(?Carbon $fechaDesde, ?Carbon $fechaHasta): ?string
    {
        if (!$fechaDesde || !$fechaHasta) {
            return null;
        }

        return $fechaDesde->isSameMonth($fechaHasta)
            ? $fechaDesde->format('Y-m')
            : null;
    }

    private function getCatalogoEtiquetadora(array $filters = [], ?Collection $lineas = null): Collection
    {
        $lineas = $lineas ?: $this->getLineasEtiquetadora();

        return Componente::query()
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->whereIn('linea', $lineas->pluck('nombre'))
            ->when($filters['maquina'] ?? null, fn ($query, $maquina) => $query->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina)))
            ->orderBy('linea')
            ->orderBy('reductor')
            ->orderBy('grupo')
            ->orderBy('nombre')
            ->get();
    }

    private function getUltimosAnalisisEtiquetadora(Collection $lineas, array $filters = []): Collection
    {
        if ($lineas->isEmpty()) {
            return collect();
        }

        $lineaIds = $lineas->pluck('id')->filter()->values();
        $baseTable = 'analisis_etiquetadora';

        return AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->whereHas('componente', fn ($query) => $query
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('activo', true))
            ->whereIn($baseTable . '.linea_id', $lineaIds)
            ->tap(fn ($query) => $this->applyEtiquetadoraAnalysisFilters($query, $filters, $baseTable))
            ->whereNotExists(function ($query) use ($lineaIds, $filters, $baseTable): void {
                $query->selectRaw('1')
                    ->from('analisis_etiquetadora as mas_reciente')
                    ->whereIn('mas_reciente.linea_id', $lineaIds)
                    ->whereColumn('mas_reciente.linea_id', $baseTable . '.linea_id')
                    ->whereColumn('mas_reciente.componente_id', $baseTable . '.componente_id')
                    ->whereRaw("COALESCE(mas_reciente.maquina, '') = COALESCE({$baseTable}.maquina, '')")
                    ->where(function ($subQuery) use ($baseTable): void {
                        $subQuery->whereColumn('mas_reciente.fecha_analisis', '>', $baseTable . '.fecha_analisis')
                            ->orWhere(function ($tieBreaker) use ($baseTable): void {
                                $tieBreaker->whereColumn('mas_reciente.fecha_analisis', '=', $baseTable . '.fecha_analisis')
                                    ->whereColumn('mas_reciente.id', '>', $baseTable . '.id');
                            });
                    });

                $this->applyEtiquetadoraAnalysisFilters($query, $filters, 'mas_reciente');
            })
            ->orderByDesc($baseTable . '.fecha_analisis')
            ->orderByDesc($baseTable . '.id')
            ->get();
    }

    private function getAnalisisPeriodoEtiquetadora(Collection $lineas, array $filters = []): Collection
    {
        if ($lineas->isEmpty()) {
            return collect();
        }

        return AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->whereHas('componente', fn ($query) => $query
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('activo', true))
            ->whereIn('analisis_etiquetadora.linea_id', $lineas->pluck('id'))
            ->tap(fn ($query) => $this->applyEtiquetadoraAnalysisFilters($query, $filters, 'analisis_etiquetadora'))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private function applyEtiquetadoraAnalysisFilters($query, array $filters, string $table): void
    {
        if (!empty($filters['maquina'])) {
            $query->where($table . '.maquina', $filters['maquina']);
        }

        if (($filters['fecha_desde'] ?? null) instanceof Carbon) {
            $query->whereDate($table . '.fecha_analisis', '>=', $filters['fecha_desde']->toDateString());
        }

        if (($filters['fecha_hasta'] ?? null) instanceof Carbon) {
            $query->whereDate($table . '.fecha_analisis', '<=', $filters['fecha_hasta']->toDateString());
        }
    }

    private function getResumenEtiquetadoraGlobal(
        Collection $lineas,
        Collection $catalogo,
        Collection $ultimos,
        Collection $analisisPeriodo,
        array $filters = []
    ): array {
        $lineaIds = $lineas->pluck('id');
        $pendientesAccion = PlanAccion::whereIn('linea_id', $lineaIds)
            ->where('tipo_equipo', User::MODULE_ETIQUETADORA)
            ->where('completado', false)
            ->count();

        $catalogoTotal = $catalogo->count();
        $totalEquipos = $this->countEtiquetadoraEquiposFromCatalogo($catalogo);
        $equiposConAnalisis = $ultimos
            ->filter(fn (AnalisisEtiquetadora $analisis): bool => filled($analisis->linea_id) && filled($analisis->maquina))
            ->map(fn (AnalisisEtiquetadora $analisis): string => (string) $analisis->linea_id . '|' . strtoupper((string) $analisis->maquina))
            ->unique()
            ->count();
        $componentesRevisados = $ultimos->pluck('componente_id')->filter()->unique()->count();
        $totalUnidades = $catalogo->sum(fn (Componente $componente) => (int) ($componente->cantidad_total ?? 0));
        $unidadesRevisadas = $ultimos->sum(function (AnalisisEtiquetadora $analisis): int {
            $total = (int) ($analisis->total_componentes ?: ($analisis->componente?->cantidad_total ?? 0));

            return count($analisis->piezasRevisadasParaTotal($total ?: null));
        });
        $evidencias = $this->countEtiquetadoraEvidence($analisisPeriodo);

        return [
            'total_etiquetadoras' => $totalEquipos,
            'componentes_catalogo' => $catalogoTotal,
            'total_unidades' => $totalUnidades,
            'unidades_revisadas' => $unidadesRevisadas,
            'total_analisis' => $analisisPeriodo->count(),
            'alertas_criticas' => $ultimos->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDanado($analisis->estado))->count(),
            'en_riesgo' => $ultimos->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDesgaste($analisis->estado))->count(),
            'requiere_revision' => $ultimos->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoRequiereRevision($analisis->estado))->count(),
            'buen_estado' => $ultimos->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoBueno($analisis->estado))->count(),
            'cambiados' => $ultimos->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoCambiado($analisis->estado))->count(),
            'pendientes_accion' => $pendientesAccion,
            'equipos_con_analisis' => $equiposConAnalisis,
            'equipos_sin_analisis' => max($totalEquipos - $equiposConAnalisis, 0),
            'avance' => $catalogoTotal > 0 ? round(($componentesRevisados / $catalogoTotal) * 100, 1) : 0,
            'avance_unidades' => $totalUnidades > 0 ? round(($unidadesRevisadas / $totalUnidades) * 100, 1) : 0,
            'registros_con_evidencia' => $evidencias['registros_con_evidencia'],
            'fotos_evidencia' => $evidencias['total_fotos'],
            'filtros_activos' => $filters['has_filters'] ?? false,
            'ultima_actualizacion' => now()->format('d/m/Y H:i'),
        ];
    }

    private function getEstadoEtiquetadoras(Collection $lineas, Collection $catalogo, Collection $ultimos): Collection
    {
        $pendientesPorLinea = PlanAccion::whereIn('linea_id', $lineas->pluck('id'))
            ->where('tipo_equipo', User::MODULE_ETIQUETADORA)
            ->where('completado', false)
            ->get()
            ->groupBy('linea_id');

        return $lineas->flatMap(function (Linea $linea) use ($catalogo, $ultimos, $pendientesPorLinea): array {
            $maquinas = $catalogo
                ->where('linea', $linea->nombre)
                ->pluck('reductor')
                ->filter()
                ->unique()
                ->map(fn (string $maquinaLabel): string => $this->maquinaDesdeEtiquetaLabel($maquinaLabel))
                ->filter()
                ->values();

            return $maquinas
                ->map(function (string $maquina) use ($linea, $catalogo, $ultimos, $pendientesPorLinea): array {
                    $maquinaLabel = EtiquetadoraCatalog::maquinaLabel($maquina);
                    $catalogoMaquina = $catalogo
                        ->where('linea', $linea->nombre)
                        ->where('reductor', $maquinaLabel);
                    $analisisMaquina = $ultimos
                        ->where('linea_id', $linea->id)
                        ->where('maquina', $maquina);

                    $criticos = $analisisMaquina->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDanado($analisis->estado))->count();
                    $desgaste = $analisisMaquina->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDesgaste($analisis->estado))->count();
                    $requiereRevision = $analisisMaquina->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoRequiereRevision($analisis->estado))->count();
                    $buenEstado = $analisisMaquina->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoBueno($analisis->estado))->count();
                    $totalComponentes = $catalogoMaquina->count();
                    $revisados = $analisisMaquina->pluck('componente_id')->filter()->unique()->count();
                    $avance = $totalComponentes > 0 ? round(($revisados / $totalComponentes) * 100, 1) : 0;
                    $pendientesAccion = ($pendientesPorLinea->get($linea->id) ?? collect())->count();

                    $nivel = $this->resolverNivelEtiquetadora($criticos, $desgaste, $requiereRevision, $pendientesAccion, $avance, $totalComponentes, $analisisMaquina->isEmpty());
                    $alertas = $analisisMaquina
                        ->filter(fn ($analisis) => !AnalisisEtiquetadora::esEstadoBueno($analisis->estado) && !AnalisisEtiquetadora::esEstadoCambiado($analisis->estado))
                        ->sortByDesc('fecha_analisis')
                        ->take(5)
                        ->values()
                        ->map(fn ($analisis): array => [
                            'title' => $analisis->componente?->nombre ?? 'Componente sin nombre',
                            'subtitle' => $analisis->estado,
                            'detail' => $analisis->actividad,
                            'meta' => $analisis->numero_orden,
                            'fecha' => optional($analisis->fecha_analisis)->format('d/m/Y'),
                            'grupo' => $analisis->componente?->grupo,
                            'mecanismo' => $analisis->componente?->mecanismo,
                            'url' => route('analisis-etiquetadora.show', $analisis),
                        ])
                        ->all();

                    return [
                        'id' => $linea->id . '-' . $maquina,
                        'linea_id' => $linea->id,
                        'linea' => $linea->nombre,
                        'maquina' => $maquina,
                        'nombre' => $linea->nombre . ' | Maquina ' . $maquina,
                        'estado' => [
                            'nivel' => $nivel,
                            'mensaje' => $this->mensajeEstadoEtiquetadora($nivel, $criticos, $desgaste, $requiereRevision, $pendientesAccion, $avance),
                            'criticos' => $criticos,
                            'desgaste' => $desgaste,
                            'requiere_revision' => $requiereRevision,
                            'buen_estado' => $buenEstado,
                            'acciones_pendientes' => $pendientesAccion,
                            'alert_carousel' => $alertas,
                            'progreso_revision' => [
                                'porcentaje' => $avance,
                                'revisados' => $revisados,
                                'pendientes' => max($totalComponentes - $revisados, 0),
                                'total' => $totalComponentes,
                                'unidades' => $catalogoMaquina->sum(fn (Componente $componente) => (int) ($componente->cantidad_total ?? 0)),
                            ],
                        ],
                        'puntaje' => ($criticos * 5) + ($desgaste * 3) + ($requiereRevision * 2) + $pendientesAccion,
                    ];
                })
                ->all();
        })->values();
    }

    private function resolverNivelEtiquetadora(
        int $criticos,
        int $desgaste,
        int $requiereRevision,
        int $pendientesAccion,
        float $avance,
        int $totalComponentes,
        bool $sinAnalisis = false
    ): string {
        if ($sinAnalisis) {
            return 'sin_datos';
        }

        if ($criticos > 0) {
            return 'critico';
        }

        if ($desgaste > 0) {
            return 'riesgo';
        }

        if ($requiereRevision > 0 || $pendientesAccion > 0 || ($totalComponentes > 0 && $avance < 100)) {
            return 'operativo';
        }

        return 'bueno';
    }

    private function mensajeEstadoEtiquetadora(string $nivel, int $criticos, int $desgaste, int $requiereRevision, int $pendientesAccion, float $avance): string
    {
        return match ($nivel) {
            'critico' => $criticos . ' componente(s) critico(s) requieren atencion.',
            'riesgo' => $desgaste . ' componente(s) con desgaste severo o moderado.',
            'operativo' => $requiereRevision > 0
                ? $requiereRevision . ' componente(s) requieren revision; avance ' . number_format($avance, 1) . '%.'
                : 'Revision incompleta; avance ' . number_format($avance, 1) . '%.',
            'sin_datos' => 'Sin analisis registrados para esta etiquetadora.',
            default => 'Sin alertas activas. Avance de revision ' . number_format($avance, 1) . '%.',
        } . ($pendientesAccion > 0 ? ' Planes pendientes: ' . $pendientesAccion . '.' : '');
    }

    private function getFallasPorLineaEtiquetadora(Collection $lineas, Collection $catalogo, Collection $ultimos): Collection
    {
        return $lineas->map(function (Linea $linea) use ($catalogo, $ultimos): array {
            $analisisLinea = $ultimos->where('linea_id', $linea->id);
            $criticos = $analisisLinea->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDanado($analisis->estado))->count();
            $desgaste = $analisisLinea->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDesgaste($analisis->estado))->count();
            $requiereRevision = $analisisLinea->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoRequiereRevision($analisis->estado))->count();
            $totalComponentes = $catalogo->where('linea', $linea->nombre)->count();
            $revisados = $analisisLinea->pluck('componente_id')->filter()->unique()->count();

            return [
                'linea' => $linea->nombre,
                'criticos' => $criticos,
                'desgaste' => $desgaste,
                'requiere_revision' => $requiereRevision,
                'total_fallas' => $criticos + $desgaste + $requiereRevision,
                'revisados' => $revisados,
                'total_componentes' => $totalComponentes,
                'avance' => $totalComponentes > 0 ? round(($revisados / $totalComponentes) * 100, 1) : 0,
            ];
        })->values();
    }

    private function getComponentesDanadosEtiquetadora(Collection $ultimos): Collection
    {
        return $ultimos
            ->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDanado($analisis->estado) || AnalisisEtiquetadora::esEstadoDesgaste($analisis->estado) || AnalisisEtiquetadora::esEstadoRequiereRevision($analisis->estado))
            ->groupBy(fn ($analisis) => implode('|', [
                $analisis->componente?->grupo ?? 'Sin grupo',
                $analisis->componente?->nombre ?? 'Sin componente',
            ]))
            ->map(function (Collection $items, string $componente): array {
                $primerRegistro = $items->first();

                return [
                    'componente' => $primerRegistro?->componente?->nombre ?? 'Sin componente',
                    'grupo' => $primerRegistro?->componente?->grupo,
                    'total' => $items->count(),
                    'criticos' => $items->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDanado($analisis->estado))->count(),
                    'desgaste' => $items->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoDesgaste($analisis->estado))->count(),
                    'requiere_revision' => $items->filter(fn ($analisis) => AnalisisEtiquetadora::esEstadoRequiereRevision($analisis->estado))->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    private function getRankingEtiquetadoras(Collection $estadoEtiquetadoras): Collection
    {
        return $estadoEtiquetadoras
            ->sortByDesc('puntaje')
            ->take(8)
            ->values()
            ->map(function (array $item, int $index): array {
                $estado = $item['estado'];
                $total = (int) ($estado['progreso_revision']['total'] ?? 0);
                $danos = (int) ($estado['criticos'] + $estado['desgaste'] + $estado['requiere_revision']);

                return [
                    'posicion' => $index + 1,
                    'nombre' => $item['nombre'],
                    'linea' => $item['linea'],
                    'maquina' => $item['maquina'],
                    'nivel' => $estado['nivel'],
                    'total_danos' => $danos,
                    'criticos' => $estado['criticos'],
                    'desgaste' => $estado['desgaste'],
                    'requiere_revision' => $estado['requiere_revision'],
                    'total_componentes' => $total,
                    'porcentaje_impacto' => $total > 0 ? round(($danos / $total) * 100, 1) : 0,
                    'prioridad_label' => $this->etiquetadoraPrioridadLabel($estado['nivel']),
                ];
            });
    }

    private function etiquetadoraPrioridadLabel(string $nivel): string
    {
        return match ($nivel) {
            'critico' => 'Critico',
            'riesgo' => 'Severo / Moderado',
            'operativo' => 'Requiere revision',
            'sin_datos' => 'Sin analisis',
            default => 'Estable',
        };
    }

    private function getPlanesPendientesEtiquetadora(Collection $lineas, array $filters = []): Collection
    {
        return PlanAccion::with(['linea', 'responsable'])
            ->whereIn('linea_id', $lineas->pluck('id'))
            ->where('tipo_equipo', User::MODULE_ETIQUETADORA)
            ->where('completado', false)
            ->orderBy('fecha_pcm1')
            ->orderBy('fecha_pcm2')
            ->take(10)
            ->get();
    }

    private function getHistoricoRevisionesEtiquetadora(Collection $lineas, array $filters = []): Collection
    {
        return $this->getAnalisisPeriodoEtiquetadora($lineas, $filters)
            ->groupBy(fn ($analisis) => implode('|', [
                $analisis->componente?->grupo ?? 'Sin grupo',
                $analisis->componente?->nombre ?? 'Sin componente',
            ]))
            ->map(function (Collection $items, string $componente): array {
                $ultimo = $items->sortByDesc('fecha_analisis')->first();

                return [
                    'componente' => $ultimo?->componente?->nombre ?? 'Sin componente',
                    'grupo' => $ultimo?->componente?->grupo,
                    'ultimo_analisis' => optional($ultimo?->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha',
                    'total_analisis' => $items->count(),
                ];
            })
            ->sortByDesc('total_analisis')
            ->take(10)
            ->values();
    }

    private function getUltimosRegistrosEtiquetadora(Collection $lineas, array $filters = []): Collection
    {
        return $this->getAnalisisPeriodoEtiquetadora($lineas, $filters)
            ->take(8)
            ->values();
    }

    private function getEvidenciasEtiquetadoraSummary(Collection $analisisPeriodo): array
    {
        $conEvidencia = $analisisPeriodo
            ->filter(fn (AnalisisEtiquetadora $analisis) => collect($analisis->evidencia_fotos ?? [])->filter()->isNotEmpty());

        return [
            ...$this->countEtiquetadoraEvidence($analisisPeriodo),
            'ultimas' => $conEvidencia
                ->sortByDesc(fn (AnalisisEtiquetadora $analisis) => $this->analisisEtiquetadoraSortKey($analisis))
                ->take(6)
                ->values(),
        ];
    }

    private function getCatalogoEtiquetadoraResumen(Collection $catalogo): array
    {
        return [
            'componentes' => $catalogo->count(),
            'equipos' => $this->countEtiquetadoraEquiposFromCatalogo($catalogo),
            'grupos' => $catalogo->pluck('grupo')->filter()->unique()->count(),
            'maquinas' => $catalogo->pluck('reductor')->filter()->unique()->count(),
            'unidades' => $catalogo->sum(fn (Componente $componente) => (int) ($componente->cantidad_total ?? 0)),
        ];
    }

    private function countEtiquetadoraEvidence(Collection $analisis): array
    {
        $registrosConEvidencia = 0;
        $totalFotos = 0;

        foreach ($analisis as $registro) {
            $fotos = collect($registro->evidencia_fotos ?? [])->filter();

            if ($fotos->isNotEmpty()) {
                $registrosConEvidencia++;
                $totalFotos += $fotos->count();
            }
        }

        return [
            'registros_con_evidencia' => $registrosConEvidencia,
            'total_fotos' => $totalFotos,
        ];
    }

    private function getNotificacionesEtiquetadoraSummary(?User $user): array
    {
        if (!$user) {
            return [
                'total' => 0,
                'no_leidas' => 0,
                'ultimas' => collect(),
            ];
        }

        $service = app(NotificationVisibilityService::class);
        $notificaciones = $service->availableNotificationsFor($user)
            ->filter(fn ($notification) => $this->isEtiquetadoraNotification($notification->data ?? []))
            ->values();
        $noLeidas = $service->availableUnreadNotificationsFor($user)
            ->filter(fn ($notification) => $this->isEtiquetadoraNotification($notification->data ?? []))
            ->values();

        return [
            'total' => $notificaciones->count(),
            'no_leidas' => $noLeidas->count(),
            'ultimas' => $notificaciones->take(5)->values(),
        ];
    }

    private function isEtiquetadoraNotification(array $data): bool
    {
        $tipoEquipo = strtolower((string) ($data['tipo_equipo'] ?? $data['tipo'] ?? ''));
        $recordType = strtolower((string) ($data['record_type'] ?? $data['analysis_type'] ?? ''));
        $recordLabel = strtolower((string) ($data['record_label'] ?? $data['title'] ?? $data['message'] ?? ''));

        return str_contains($tipoEquipo, User::MODULE_ETIQUETADORA)
            || in_array($recordType, [User::MODULE_ETIQUETADORA, 'analisis_etiquetadora'], true)
            || str_contains($recordLabel, User::MODULE_ETIQUETADORA);
    }

    private function countEtiquetadoraEquiposFromCatalogo(Collection $catalogo): int
    {
        return $catalogo
            ->map(fn (Componente $componente): string => trim((string) $componente->linea) . '|' . trim((string) $componente->reductor))
            ->filter(fn (string $key): bool => $key !== '|')
            ->unique()
            ->count();
    }

    private function maquinaDesdeEtiquetaLabel(?string $etiqueta): string
    {
        $valor = strtoupper(trim((string) $etiqueta));

        foreach (EtiquetadoraCatalog::maquinas() as $maquina) {
            if ($valor === strtoupper(EtiquetadoraCatalog::maquinaLabel($maquina)) || str_ends_with($valor, strtoupper($maquina))) {
                return $maquina;
            }
        }

        return '';
    }

    private function analisisEtiquetadoraSortKey(AnalisisEtiquetadora $registro): string
    {
        $fechaAnalisis = $registro->fecha_analisis?->format('Ymd') ?? '00000000';
        $createdAt = str_pad((string) ($registro->created_at?->timestamp ?? 0), 12, '0', STR_PAD_LEFT);
        $id = str_pad((string) ($registro->id ?? 0), 10, '0', STR_PAD_LEFT);

        return $fechaAnalisis . '-' . $createdAt . '-' . $id;
    }

    /**
     * Obtiene el resumen general de todas las lavadoras.
     */
    private function getResumenGeneral($lineasLavadora)
    {
        $totalLavadoras = $lineasLavadora->count();
        $totalAnalisis = AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))->count();
        $totalAlertasCriticas = 0;
        $totalEnRiesgo = 0;
        $totalRequiereRevision = 0;
        $totalBuenEstado = 0;
        $totalPendientesAccion = PlanAccion::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->where(function ($query) {
                $query->where('tipo_equipo', 'lavadora')
                    ->orWhereNull('tipo_equipo');
            })
            ->where('completado', false)
            ->count();

        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            if ($estado['nivel'] === 'critico') {
                $totalAlertasCriticas++;
            } elseif ($estado['nivel'] === 'riesgo') {
                $totalEnRiesgo++;
            } elseif ($estado['nivel'] === 'operativo') {
                $totalRequiereRevision++;
            } else {
                $totalBuenEstado++;
            }
        }

        return [
            'total_lavadoras' => $totalLavadoras,
            'total_analisis' => $totalAnalisis,
            'alertas_criticas' => $totalAlertasCriticas,
            'en_riesgo' => $totalEnRiesgo,
            'requiere_revision' => $totalRequiereRevision,
            'buen_estado' => $totalBuenEstado,
            'pendientes_accion' => $totalPendientesAccion,
        ];
    }

    /**
     * Obtiene el estado detallado de todas las lavadoras.
     */
    private function getEstadoLavadoras($lineasLavadora)
    {
        $estadoLavadoras = [];
        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            $estadoLavadoras[] = [
                'id' => $linea->id,
                'nombre' => $linea->nombre,
                'estado' => $estado,
            ];
        }
        return $estadoLavadoras;
    }

    /**
     * Calcula el estado de una lavadora específica.
     */
    private function calcularEstadoLavadora($lineaId)
    {
        $lineaNombre = Linea::whereKey($lineaId)->value('nombre');

        $ultimaElongacion = Elongacion::where('linea', $lineaNombre)
            ->orderBy('created_at', 'desc')
            ->first();

        $analisisPorEstado = $this->getLavadoraDashboardAnalysesByState($lineaId);
        $analisisCriticos = $analisisPorEstado['critico'];
        $analisisSeveros = $analisisPorEstado['severo'];
        $analisisModerados = $analisisPorEstado['moderado'];
        $analisisRevision = $analisisPorEstado['revision'];

        $accionesPendientes = PlanAccion::where('linea_id', $lineaId)
            ->where(function ($query) {
                $query->where('tipo_equipo', 'lavadora')
                    ->orWhereNull('tipo_equipo');
            })
            ->where('completado', false)
            ->count();

        $analisisDesgaste = count($analisisSeveros) + count($analisisModerados);

        $conteoAlertas = [
            'critico' => count($analisisCriticos),
            'severo' => count($analisisSeveros),
            'moderado' => count($analisisModerados),
            'revision' => count($analisisRevision),
        ];

        $elongacionCritica = $ultimaElongacion
            && ($ultimaElongacion->vapor_porcentaje >= 1.46 || $ultimaElongacion->bombas_porcentaje >= 1.46);
        $elongacionCompra = $ultimaElongacion
            && ($ultimaElongacion->vapor_porcentaje >= 1.3 || $ultimaElongacion->bombas_porcentaje >= 1.3);

        $nivel = 'bueno';
        $color = 'green';
        $mensaje = 'Funcionando correctamente';

        if (count($analisisCriticos) > 0) {
            $nivel = 'critico';
            $color = 'red';
            $mensaje = 'Presenta componentes dañados que requieren cambio inmediato.';
        } elseif ($elongacionCritica) {
            $nivel = 'critico';
            $color = 'red';
            $mensaje = 'Elongación crítica (>1.46%), cambio de cadena requerido.';
        } elseif ($analisisDesgaste > 0) {
            $nivel = 'riesgo';
            $color = 'orange';
            $mensaje = 'Presenta componentes con daños severos o moderados, programar mantenimiento.';
        } elseif ($accionesPendientes > 0) {
            $nivel = 'riesgo';
            $color = 'orange';
            $mensaje = "Tiene {$accionesPendientes} acción(es) pendiente(s) en el plan de acción.";
        } elseif ($elongacionCompra) {
            $nivel = 'riesgo';
            $color = 'orange';
            $mensaje = 'Elongación en nivel de compra (>1.3%), considerar compra de cadena';
        } elseif (count($analisisRevision) > 0) {
            $nivel = 'operativo';
            $color = 'yellow';
            $mensaje = 'Presenta anomalías operativas que requieren revisión.';
        }

        return [
            'nivel' => $nivel,
            'color' => $color,
            'mensaje' => $mensaje,
            'analisis_por_estado' => $analisisPorEstado,
            'ultima_elongacion' => $ultimaElongacion,
            'acciones_pendientes' => $accionesPendientes,
            'analisis_desgaste' => $analisisDesgaste,
            'requiere_revision' => count($analisisRevision),
            'conteo_alertas' => $conteoAlertas,
            'total_alertas_componentes' => array_sum($conteoAlertas),
            'alert_carousel' => $this->buildLavadoraAlertCarousel(
                $analisisPorEstado,
                $nivel
            ),
        ];
    }

    /**
     * Construye los items del carrusel para la tarjeta de estado.
     */
    private function buildLavadoraAlertCarousel(array $analisisPorEstado, string $nivel)
    {
        $items = [];

        foreach (['critico', 'severo', 'moderado', 'revision'] as $estadoKey) {
            foreach ($analisisPorEstado[$estadoKey] ?? [] as $analisis) {
                $subtitleParts = [];

                if (!empty($analisis['reductor'])) {
                    $subtitleParts[] = "{$analisis['reductor']}";
                }

                if (!empty($analisis['lado'])) {
                    $subtitleParts[] = $analisis['lado'];
                }

                $items[] = [
                    'type' => 'componente',
                    'title' => $analisis['componente']['nombre'] ?? 'Componente con alerta',
                    'subtitle' => count($subtitleParts) ? implode(' · ', $subtitleParts) : ($analisis['componente']['codigo'] ?? 'Sin ubicación'),
                    'image' => $analisis['componente']['icono'] ?? asset('images/componentes-lavadora/default.png'),
                    'detail' => $analisis['actividad'] ?? 'Problema detectado en el componente.',
                    'reductor' => $analisis['reductor'] ?? null,
                    'meta' => $analisis['componente']['codigo'] ?? null,
                    'fecha' => $analisis['fecha_formateada'] ?? null,
                    'estado_label' => $analisis['estado_label'] ?? null,
                    'estado_key' => $analisis['estado_key'] ?? null,
                ];
            }
        }

        if (!empty($items)) {
            return $items;
        }

        if ($nivel === 'bueno') {
            return [[
                'type' => 'info',
                'title' => 'Sin alertas activas',
                'subtitle' => 'La lavadora está en buen estado',
                'description' => 'No hay componentes dañados ni alertas críticas en este momento.',
                'icon' => 'fa-check-circle',
            ]];
        }

        return [];
    }

    /**
     * Agrupa los análisis vigentes de la lavadora por severidad para el dashboard.
     */
    private function getLavadoraDashboardAnalysesByState(int $lineaId): array
    {
        $analisisLinea = AnalisisLavadora::ultimosPorComponente()
            ->where('linea_id', $lineaId)
            ->with('componente')
            ->orderBy('fecha_analisis', 'desc')
            ->get()
            ->map(fn (AnalisisLavadora $analisis) => $this->attachLavadoraComponentIcon($analisis))
            ->values();

        return [
            'critico' => $this->formatLavadoraDashboardAnalyses(
                $analisisLinea->filter(fn (AnalisisLavadora $analisis) => $this->isLavadoraCriticalState($analisis->estado)),
                'Requiere cambio',
                'critico'
            ),
            'severo' => $this->formatLavadoraDashboardAnalyses(
                $analisisLinea->filter(fn (AnalisisLavadora $analisis) => $this->normalizeLavadoraState($analisis->estado) === 'desgaste severo'),
                'Daño severo',
                'severo'
            ),
            'moderado' => $this->formatLavadoraDashboardAnalyses(
                $analisisLinea->filter(fn (AnalisisLavadora $analisis) => $this->normalizeLavadoraState($analisis->estado) === 'desgaste moderado'),
                'Daño moderado',
                'moderado'
            ),
            'revision' => $this->formatLavadoraDashboardAnalyses(
                $analisisLinea->filter(fn (AnalisisLavadora $analisis) => $this->isLavadoraReviewState($analisis->estado)),
                'Requiere revisión',
                'revision'
            ),
        ];
    }

    /**
     * Prepara un bloque de análisis para consumo directo en las tarjetas y el modal.
     */
    private function formatLavadoraDashboardAnalyses(Collection $analisis, string $estadoLabel, string $estadoKey): array
    {
        return $analisis
            ->values()
            ->map(function (AnalisisLavadora $item) use ($estadoLabel, $estadoKey) {
                return [
                    'id' => $item->id,
                    'estado' => $item->estado,
                    'estado_label' => $estadoLabel,
                    'estado_key' => $estadoKey,
                    'actividad' => $item->actividad,
                    'reductor' => $item->reductor,
                    'lado' => $item->lado,
                    'fecha_analisis' => $item->fecha_analisis?->format('Y-m-d'),
                    'fecha_formateada' => $item->fecha_analisis?->format('d/m/Y'),
                    'componente' => [
                        'id' => $item->componente?->id,
                        'nombre' => $item->componente?->nombre,
                        'codigo' => $item->componente?->codigo,
                        'icono' => $item->componente?->icono ?? $this->getLavadoraComponentIcon($item->componente?->codigo),
                    ],
                ];
            })
            ->all();
    }

    /**
     * Obtiene los ultimos analisis vigentes por componente/lado para lavadoras.
     */
    private function getAnalisisActualesLavadoras($lineasLavadora): Collection
    {
        if ($lineasLavadora->isEmpty()) {
            return collect();
        }

        return AnalisisLavadora::ultimosPorComponente()
            ->whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->with(['linea:id,nombre', 'componente:id,nombre,codigo', 'usuario:id,name'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Obtiene el historico completo de analisis de lavadoras.
     */
    private function getAnalisisHistoricosLavadoras($lineasLavadora): Collection
    {
        if ($lineasLavadora->isEmpty()) {
            return collect();
        }

        return AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->with(['linea:id,nombre', 'componente:id,nombre,codigo', 'usuario:id,name'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Obtiene los planes de accion del modulo de lavadoras.
     */
    private function getPlanesAccionLavadoras($lineasLavadora): Collection
    {
        if ($lineasLavadora->isEmpty()) {
            return collect();
        }

        return PlanAccion::with('linea:id,nombre')
            ->whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->where(function ($query) {
                $query->where('tipo_equipo', 'lavadora')
                    ->orWhereNull('tipo_equipo');
            })
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Resumen y detalle para la seccion de planes de accion.
     */
    private function getPlanesAccionDashboard($lineasLavadora): array
    {
        $planes = $this->getPlanesAccionLavadoras($lineasLavadora);
        $planesNormalizados = $planes
            ->map(function (PlanAccion $plan) {
                $prioridad = $this->resolvePlanPriority($plan);

                return [
                    'id' => $plan->id,
                    'linea_id' => $plan->linea_id,
                    'linea' => optional($plan->linea)->nombre ?? 'Sin linea',
                    'actividad' => $plan->actividad,
                    'completado' => (bool) $plan->completado,
                    'prioridad' => $prioridad['level'],
                    'prioridad_label' => $prioridad['label'],
                    'prioridad_sort' => $prioridad['sort'],
                    'proxima_fecha' => $prioridad['date']?->format('Y-m-d'),
                    'proxima_fecha_humana' => $prioridad['date']?->format('d/m/Y'),
                    'dias_restantes' => $prioridad['days'],
                    'created_at' => optional($plan->created_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        $planesActivos = $planesNormalizados->where('completado', false)->values();
        $conteoPrioridad = [
            'alta' => $planesActivos->where('prioridad', 'alta')->count(),
            'media' => $planesActivos->where('prioridad', 'media')->count(),
            'baja' => $planesActivos->where('prioridad', 'baja')->count(),
            'sin_fecha' => $planesActivos->where('prioridad', 'sin_fecha')->count(),
        ];
        $planesPendientes = $planesActivos->whereIn('prioridad', ['alta', 'media', 'sin_fecha'])->count();
        $planesVencidos = $planesActivos
            ->filter(fn ($item) => $item['dias_restantes'] !== null && $item['dias_restantes'] < 0)
            ->count();
        $planesProximos7Dias = $planesActivos
            ->filter(fn ($item) => $item['dias_restantes'] !== null && $item['dias_restantes'] >= 0 && $item['dias_restantes'] <= 7)
            ->count();

        $estadoGeneral = [
            'nivel' => 'estable',
            'label' => 'Controlado',
            'mensaje' => 'No hay planes abiertos con riesgo inmediato.',
        ];

        if ($conteoPrioridad['alta'] > 0) {
            $estadoGeneral = [
                'nivel' => 'critico',
                'label' => 'Atencion inmediata',
                'mensaje' => 'Existen planes vencidos o por vencer con prioridad alta.',
            ];
        } elseif ($conteoPrioridad['media'] > 0 || $planesPendientes > 0) {
            $estadoGeneral = [
                'nivel' => 'riesgo',
                'label' => 'Seguimiento cercano',
                'mensaje' => 'Hay actividades pendientes que requieren seguimiento en corto plazo.',
            ];
        } elseif ($planesActivos->count() > 0) {
            $estadoGeneral = [
                'nivel' => 'estable',
                'label' => 'En seguimiento',
                'mensaje' => 'Las actividades abiertas se encuentran programadas y sin urgencia alta.',
            ];
        } elseif ($planesNormalizados->count() > 0) {
            $estadoGeneral = [
                'nivel' => 'estable',
                'label' => 'Sin pendientes',
                'mensaje' => 'Todos los planes registrados se encuentran completados.',
            ];
        }

        $porLinea = $lineasLavadora
            ->map(function ($linea) use ($planesNormalizados) {
                $planesLinea = $planesNormalizados->where('linea_id', $linea->id);
                $abiertos = $planesLinea->where('completado', false)->count();
                $completados = $planesLinea->where('completado', true)->count();
                $altaPrioridad = $planesLinea
                    ->where('completado', false)
                    ->where('prioridad', 'alta')
                    ->count();

                return [
                    'linea' => $linea->nombre,
                    'linea_id' => $linea->id,
                    'total' => $planesLinea->count(),
                    'abiertos' => $abiertos,
                    'completados' => $completados,
                    'alta_prioridad' => $altaPrioridad,
                    'porcentaje_cierre' => $planesLinea->count() > 0
                        ? round(($completados / $planesLinea->count()) * 100)
                        : 0,
                ];
            })
            ->sortByDesc(fn ($item) => ($item['alta_prioridad'] * 100) + ($item['abiertos'] * 10) + $item['total'])
            ->values()
            ->all();

        $topPlanes = $planesActivos
            ->sortBy(fn ($item) => ($item['prioridad_sort'] * 1000000) + (int) str_replace('-', '', $item['proxima_fecha'] ?? '9999-12-31'))
            ->take(8)
            ->values()
            ->all();

        return [
            'resumen' => [
                'total' => $planesNormalizados->count(),
                'activos' => $planesActivos->count(),
                'pendientes' => $planesPendientes,
                'programados' => max($planesActivos->count() - $planesPendientes, 0),
                'completados' => $planesNormalizados->where('completado', true)->count(),
                'prioridad_alta' => $conteoPrioridad['alta'],
                'prioridad_media' => $conteoPrioridad['media'],
                'prioridad_baja' => $conteoPrioridad['baja'],
                'sin_fecha' => $conteoPrioridad['sin_fecha'],
                'vencidos' => $planesVencidos,
                'proximos_7_dias' => $planesProximos7Dias,
                'lineas_comprometidas' => collect($porLinea)->filter(fn ($item) => $item['abiertos'] > 0)->count(),
                'avance' => $planesNormalizados->count() > 0
                    ? round(($planesNormalizados->where('completado', true)->count() / $planesNormalizados->count()) * 100)
                    : 0,
            ],
            'estado_general' => $estadoGeneral,
            'por_linea' => $porLinea,
            'planes' => $topPlanes,
        ];
    }

    /**
     * Obtiene el ranking de lavadoras con mayor nivel de daño.
     */
    private function getRankingDanos($lineasLavadora, ?Collection $analisisActuales = null): array
    {
        $analisisActuales = $analisisActuales ?: $this->getAnalisisActualesLavadoras($lineasLavadora);

        return $analisisActuales
            ->filter(fn ($analisis) => $this->isLavadoraDamageState($analisis->estado))
            ->map(function ($analisis) {
                $meta = $this->getLavadoraSeverityMeta($analisis->estado);
                $diasDesdeRevision = $analisis->fecha_analisis
                    ? $analisis->fecha_analisis->copy()->startOfDay()->diffInDays(now()->copy()->startOfDay())
                    : null;

                return [
                    'id' => $analisis->id,
                    'linea' => optional($analisis->linea)->nombre ?? 'Sin linea',
                    'componente' => optional($analisis->componente)->nombre ?? 'Componente desconocido',
                    'codigo' => optional($analisis->componente)->codigo,
                    'reductor' => $analisis->reductor,
                    'lado' => $analisis->lado,
                    'estado' => $analisis->estado,
                    'fecha_analisis' => $analisis->fecha_analisis?->format('Y-m-d'),
                    'fecha_analisis_humana' => $analisis->fecha_analisis?->format('d/m/Y'),
                    'dias_desde_revision' => $diasDesdeRevision,
                    'prioridad' => $meta['level'],
                    'prioridad_label' => $meta['label'],
                    'color' => $meta['color'],
                    'puntaje' => round($meta['score'] + min((int) ($diasDesdeRevision ?? 0), 90) / 30, 2),
                    'icono' => $this->getLavadoraComponentIcon(optional($analisis->componente)->codigo),
                    'requiere_cambio' => $this->isLavadoraCriticalState($analisis->estado),
                ];
            })
            ->sortByDesc(fn ($item) => ($item['puntaje'] * 100) + (($item['dias_desde_revision'] ?? 0) / 10))
            ->values()
            ->take(10)
            ->all();
    }

    /**
     * Obtiene el ranking de dano agrupado por lavadora.
     */
    private function getRankingDanosPorLavadora($lineasLavadora, ?Collection $analisisActuales = null): array
    {
        $analisisActuales = $analisisActuales ?: $this->getAnalisisActualesLavadoras($lineasLavadora);
        $agrupados = $analisisActuales->groupBy('linea_id');

        return $lineasLavadora
            ->map(function ($linea) use ($agrupados) {
                $analisisLinea = $agrupados->get($linea->id, collect());
                $criticas = $analisisLinea->filter(fn ($item) => $this->isLavadoraCriticalState($item->estado))->count();
                $severos = $analisisLinea->filter(fn ($item) => $this->normalizeLavadoraState($item->estado) === 'desgaste severo')->count();
                $moderados = $analisisLinea->filter(fn ($item) => $this->normalizeLavadoraState($item->estado) === 'desgaste moderado')->count();
                $totalDanos = $criticas + $severos + $moderados;
                $ultimaRevision = $analisisLinea
                    ->filter(fn ($item) => $item->fecha_analisis)
                    ->sortByDesc(fn ($item) => $item->fecha_analisis->format('Y-m-d') . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT))
                    ->first();
                $diasDesdeRevision = $ultimaRevision?->fecha_analisis
                    ? $ultimaRevision->fecha_analisis->copy()->startOfDay()->diffInDays(now()->copy()->startOfDay())
                    : null;

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'criticas' => $criticas,
                    'severos' => $severos,
                    'moderados' => $moderados,
                    'severas_moderadas' => $severos + $moderados,
                    'total_danos' => $totalDanos,
                    'total_componentes' => $analisisLinea->count(),
                    'porcentaje_impacto' => $analisisLinea->count() > 0
                        ? round(($totalDanos / $analisisLinea->count()) * 100, 1)
                        : 0,
                    'fecha_analisis' => $ultimaRevision?->fecha_analisis?->format('Y-m-d'),
                    'fecha_analisis_humana' => $ultimaRevision?->fecha_analisis?->format('d/m/Y'),
                    'dias_desde_revision' => $diasDesdeRevision,
                    'prioridad' => $criticas > 0 ? 'critico' : 'severo',
                    'prioridad_label' => $criticas > 0 ? 'Crítico' : 'Severo / Moderado',
                    'puntaje' => $totalDanos,
                    'requiere_cambio' => $criticas > 0,
                ];
            })
            ->sortByDesc(fn ($item) => ($item['total_danos'] * 1000) + ($item['criticas'] * 100) + ($item['severos'] * 10) + $item['moderados'] + (($item['dias_desde_revision'] ?? 0) / 100))
            ->values()
            ->all();
    }

    /**
     * Obtiene datos de fallas por línea para la gráfica de barras.
     */
    private function getFallasPorLinea($lineasLavadora, ?Collection $analisisActuales = null): array
    {
        $analisisActuales = $analisisActuales ?: $this->getAnalisisActualesLavadoras($lineasLavadora);
        $agrupados = $analisisActuales->groupBy('linea_id');

        return $lineasLavadora
            ->map(function ($linea) use ($agrupados) {
                $componentes = $agrupados->get($linea->id, collect());
                $criticas = $componentes->filter(fn ($item) => $this->isLavadoraCriticalState($item->estado))->count();
                $severasModeradas = $componentes->filter(fn ($item) => $this->isLavadoraSevereState($item->estado))->count();
                $requiereRevision = $componentes->filter(fn ($item) => $this->isLavadoraReviewState($item->estado))->count();
                $estables = $componentes->filter(fn ($item) => in_array($this->normalizeLavadoraState($item->estado), ['buen estado', 'cambiado'], true))->count();
                $impactados = $criticas + $severasModeradas + $requiereRevision;
                $ultimaRevision = $componentes
                    ->filter(fn ($item) => $item->fecha_analisis)
                    ->sortByDesc(fn ($item) => $item->fecha_analisis->format('Y-m-d') . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT))
                    ->first();

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'criticas' => $criticas,
                    'requiere_revision' => $requiereRevision,
                    'severas_moderadas' => $severasModeradas,
                    'estables' => $estables,
                    'total_componentes' => $componentes->count(),
                    'impactados' => $impactados,
                    'total_fallas' => $impactados,
                    'porcentaje_impacto' => $componentes->count() > 0
                        ? round(($impactados / $componentes->count()) * 100, 1)
                        : 0,
                    'ultima_revision' => $ultimaRevision?->fecha_analisis?->format('Y-m-d'),
                    'ultima_revision_humana' => $ultimaRevision?->fecha_analisis?->format('d/m/Y'),
                    'estado' => $criticas > 0
                        ? 'critico'
                        : ($severasModeradas > 0
                            ? 'riesgo'
                            : ($requiereRevision > 0
                                ? 'operativo'
                                : ($componentes->isNotEmpty() ? 'estable' : 'sin_datos'))),
                    'sin_datos' => $componentes->isEmpty(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Obtiene los componentes más dañados para la gráfica de pastel.
     */
    private function getComponentesDanados($lineasLavadora)
    {
        return $this->getAnalisisActualesLavadoras($lineasLavadora)
            ->where('estado', 'Dañado - Requiere cambio')
            ->groupBy(fn ($analisis) => optional($analisis->componente)->nombre ?? 'Desconocido')
            ->map(fn ($items, $componente) => [
                'componente' => $componente,
                'total_danios' => $items->count(),
            ])
            ->sortByDesc('total_danios')
            ->values()
            ->take(5)
            ->all();
    }

    /**
     * Obtiene la evolución de elongaciones para la gráfica de líneas.
     */
    private function getEvolucionElongaciones($lineasLavadora): array
    {
        $mediciones = Elongacion::whereIn('linea', $lineasLavadora->pluck('nombre'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('linea');

        $seriesPorLinea = $lineasLavadora
            ->map(function ($linea) use ($mediciones) {
                $items = $mediciones->get($linea->nombre, collect())->values();

                if ($items->isEmpty()) {
                    return [
                        'linea_id' => $linea->id,
                        'linea' => $linea->nombre,
                        'labels' => [],
                        'bombas' => [],
                        'vapor' => [],
                        'promedio' => [],
                        'threshold_compra' => 1.30,
                        'threshold_cambio' => 1.46,
                        'mediciones' => 0,
                        'desde' => null,
                        'hasta' => null,
                        'actual_max' => null,
                        'sin_datos' => true,
                    ];
                }

                $labels = $items->map(fn ($item) => optional($item->created_at)->format('d/m/Y'))->all();
                $bombas = $items->map(fn ($item) => round((float) $item->bombas_porcentaje, 2))->all();
                $vapor = $items->map(fn ($item) => round((float) $item->vapor_porcentaje, 2))->all();
                $promedio = $items->map(fn ($item) => round((((float) $item->bombas_porcentaje) + ((float) $item->vapor_porcentaje)) / 2, 2))->all();
                $ultimo = $items->last();

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'labels' => $labels,
                    'bombas' => $bombas,
                    'vapor' => $vapor,
                    'promedio' => $promedio,
                    'threshold_compra' => 1.30,
                    'threshold_cambio' => 1.46,
                    'mediciones' => $items->count(),
                    'desde' => optional($items->first()->created_at)->format('d/m/Y'),
                    'hasta' => $ultimo && $ultimo->created_at ? $ultimo->created_at->format('d/m/Y') : now()->format('d/m/Y'),
                    'actual_max' => $ultimo ? round(max((float) $ultimo->bombas_porcentaje, (float) $ultimo->vapor_porcentaje), 2) : null,
                    'sin_datos' => false,
                ];
            })
            ->values();

        $defaultLineaItem = $seriesPorLinea->firstWhere('sin_datos', false);

        return [
            'default_linea_id' => $defaultLineaItem['linea_id'] ?? $lineasLavadora->first()?->id,
            'lineas' => $seriesPorLinea->all(),
        ];
    }

    /**
     * Obtiene el histórico de revisiones (conteo de análisis por componente).
     */
    private function getHistoricoRevisiones($lineasLavadora, ?Collection $analisisHistoricos = null): array
    {
        $analisisHistoricos = $analisisHistoricos ?: $this->getAnalisisHistoricosLavadoras($lineasLavadora);
        $analisisOrdenados = $analisisHistoricos
            ->sortByDesc(fn ($item) => $item->fecha_analisis?->format('Y-m-d') . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT))
            ->values();
        $meses = collect(range(11, 0))
            ->map(fn ($offset) => now()->copy()->startOfMonth()->subMonths($offset))
            ->values();
        $formatearRegistro = function ($item): array {
            $meta = $this->getLavadoraSeverityMeta($item->estado);

            return [
                'id' => $item->id,
                'linea_id' => $item->linea_id,
                'linea' => optional($item->linea)->nombre ?? 'Sin linea',
                'componente' => optional($item->componente)->nombre ?? 'Componente desconocido',
                'reductor' => $item->reductor,
                'lado' => $item->lado,
                'estado' => $item->estado,
                'estado_color' => $meta['color'],
                'fecha' => $item->fecha_analisis?->format('Y-m-d'),
                'fecha_humana' => $item->fecha_analisis?->format('d/m/Y'),
                'actividad' => $item->actividad,
                'usuario' => optional($item->usuario)->name,
            ];
        };

        $series = [
            'Todas' => $meses
                ->map(function (Carbon $mes) use ($analisisOrdenados) {
                    return $analisisOrdenados->filter(function ($item) use ($mes) {
                        return $item->fecha_analisis
                            && $item->fecha_analisis->isSameMonth($mes)
                            && $item->fecha_analisis->year === $mes->year;
                    })->count();
                })
                ->all(),
        ];

        foreach ($lineasLavadora as $linea) {
            $series[$linea->nombre] = $meses
                ->map(function (Carbon $mes) use ($analisisOrdenados, $linea) {
                    return $analisisOrdenados->filter(function ($item) use ($mes, $linea) {
                        return $item->linea_id === $linea->id
                            && $item->fecha_analisis
                            && $item->fecha_analisis->isSameMonth($mes)
                            && $item->fecha_analisis->year === $mes->year;
                    })->count();
                })
                ->all();
        }

        $registrosPorAlcance = [
            'Todas' => $analisisOrdenados
                ->take(5)
                ->map($formatearRegistro)
                ->values()
                ->all(),
        ];

        foreach ($lineasLavadora as $linea) {
            $registrosPorAlcance[$linea->nombre] = $analisisOrdenados
                ->where('linea_id', $linea->id)
                ->take(5)
                ->map($formatearRegistro)
                ->values()
                ->all();
        }

        $registros = collect($registrosPorAlcance['Todas'] ?? [])
            ->values()
            ->all();

        return [
            'labels' => $meses->map(fn (Carbon $mes) => $mes->format('m/Y'))->all(),
            'series' => $series,
            'registros' => $registros,
            'registros_por_alcance' => $registrosPorAlcance,
            'resumen' => [
                'total' => $analisisOrdenados->count(),
                'ultimos_30_dias' => $analisisOrdenados->filter(fn ($item) => $item->fecha_analisis && $item->fecha_analisis->gte(now()->copy()->subDays(30)))->count(),
                'componentes_unicos' => $analisisOrdenados->map(fn ($item) => optional($item->componente)->nombre)->filter()->unique()->count(),
                'ultima_revision' => optional($analisisOrdenados->first()?->fecha_analisis)->format('d/m/Y'),
            ],
        ];
    }

    /**
     * Obtiene los últimos registros del análisis 52-12-4.
     */
    private function getAnalisis52124($lineasLavadora, ?Collection $analisisHistoricos = null, ?array $dateRange = null): array
    {
        return $this->buildLavadoraDamageTrendAnalysis(
            $lineasLavadora,
            [
                ['key' => 'semanas_52', 'label' => '52 semanas', 'type' => 'weeks', 'size' => 52],
                ['key' => 'semanas_12', 'label' => '12 semanas', 'type' => 'weeks', 'size' => 12],
                ['key' => 'semanas_4', 'label' => '4 semanas', 'type' => 'weeks', 'size' => 4],
            ],
            $analisisHistoricos,
            $dateRange
        );
    }

    /**
     * Construye el anÃ¡lisis 30-14-7 con la misma fuente real del 52-12-4.
     */
    private function getAnalisis30147($lineasLavadora, ?Collection $analisisHistoricos = null, ?array $dateRange = null): array
    {
        return $this->buildLavadoraDamageTrendAnalysis(
            $lineasLavadora,
            [
                ['key' => 'dias_30', 'label' => '30 dias', 'type' => 'days', 'size' => 30],
                ['key' => 'dias_14', 'label' => '14 dias', 'type' => 'days', 'size' => 14],
                ['key' => 'dias_7', 'label' => '7 dias', 'type' => 'days', 'size' => 7],
            ],
            $analisisHistoricos,
            $dateRange
        );
    }

    /**
     * Consolida los eventos que alimentan los modulos de tendencia del dashboard.
     */
    private function buildLavadoraDamageTrendAnalysis($lineasLavadora, array $windows, ?Collection $analisisHistoricos = null, ?array $dateRange = null): array
    {
        if ($lineasLavadora->isEmpty()) {
            return [
                'default_linea_id' => null,
                'lineas' => [],
                'criterios' => $this->getLavadoraTrendCriteria(),
            ];
        }

        $sourceRange = $dateRange ? $this->resolveLavadoraTrendSourceRange($dateRange, $windows) : null;
        $trendAnalisisHistoricos = $analisisHistoricos
            ? $this->filterLavadoraTrendAnalisisByDateRange($analisisHistoricos, $sourceRange)
            : null;

        $allEvents = $this->getLavadoraTrendEvents($lineasLavadora, $trendAnalisisHistoricos, $sourceRange)
            ->values();
        $eventsByLine = $allEvents->groupBy('linea_id');
        $timeline = $this->buildLavadoraTrendTimelineCuts($allEvents, $dateRange);
        $timelineLabels = $timeline->pluck('label')->all();

        $seriesPorLinea = $lineasLavadora
            ->map(function ($linea) use ($eventsByLine, $windows, $timeline, $timelineLabels) {
                $eventos = $eventsByLine
                    ->get($linea->id, collect())
                    ->sortBy(function (array $item) {
                        return sprintf(
                            '%s-%010d',
                            $item['occurred_at']->format('YmdHis'),
                            (int) ($item['id'] ?? 0)
                        );
                    })
                    ->values();

                if ($eventos->isEmpty()) {
                    $emptySeries = collect($windows)
                        ->map(function (array $window) use ($timelineLabels) {
                            return [
                                'key' => $window['key'],
                                'label' => $window['label'],
                                'data' => array_fill(0, count($timelineLabels), 0),
                            ];
                        });

                    return [
                        'linea_id' => $linea->id,
                        'linea' => $linea->nombre,
                        'labels' => $timelineLabels,
                        'series' => $emptySeries->all(),
                        'resumen' => [
                            'ultimo_corte' => $timelineLabels ? end($timelineLabels) : null,
                            'ultima_falla' => null,
                            'ultima_fuente' => null,
                            'total_fallas' => 0,
                        ],
                        'sin_datos' => true,
                    ] + $emptySeries->mapWithKeys(fn (array $serie) => [$serie['key'] => $serie['data']])->all();
                }

                $series = collect($windows)
                    ->map(function (array $window) use ($eventos, $timeline) {
                        return [
                            'key' => $window['key'],
                            'label' => $window['label'],
                            'data' => $timeline
                                ->map(fn (array $cut) => $this->countLavadoraTrendEventsForWindowAtCut($eventos, $window, $cut['cut_at']))
                                ->all(),
                        ];
                    })
                    ->values();

                $ultimoEvento = $eventos->last();
                $ultimoCorte = $timelineLabels ? end($timelineLabels) : null;
                $resumenSeries = $series
                    ->mapWithKeys(function (array $serie) {
                        return [$serie['key'] => (int) collect($serie['data'])->last()];
                    })
                    ->all();

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'labels' => $timelineLabels,
                    'series' => $series->all(),
                    'resumen' => [
                        'ultimo_corte' => $ultimoCorte,
                        'ultima_falla' => $ultimoEvento['fecha_humana'] ?? null,
                        'ultima_fuente' => $ultimoEvento['type_label'] ?? null,
                        'total_fallas' => $eventos->count(),
                    ] + $resumenSeries,
                    'sin_datos' => false,
                ] + $series->mapWithKeys(fn (array $serie) => [$serie['key'] => $serie['data']])->all();
            })
            ->values();

        $defaultLineaItem = $seriesPorLinea->firstWhere('sin_datos', false);

        return [
            'default_linea_id' => $defaultLineaItem['linea_id'] ?? $lineasLavadora->first()?->id,
            'lineas' => $seriesPorLinea->all(),
            'criterios' => $this->getLavadoraTrendCriteria(),
        ];
    }

    /**
     * Amplia el rango visible para calcular correctamente las ventanas historicas.
     */
    private function resolveLavadoraTrendSourceRange(array $displayRange, array $windows): array
    {
        $from = ($displayRange['from'] ?? null) instanceof Carbon ? $displayRange['from']->copy() : null;
        $to = ($displayRange['to'] ?? null) instanceof Carbon ? $displayRange['to']->copy() : null;

        if (!$from && !$to) {
            return $displayRange;
        }

        $reference = ($from ?: $to)->copy()->endOfDay();
        $sourceStart = collect($windows)
            ->map(function (array $window) use ($reference) {
                return $this->resolveLavadoraTrendCurrentRange($window, $reference->copy())['current_start'];
            })
            ->filter(fn ($date) => $date instanceof Carbon)
            ->sortBy(fn (Carbon $date) => $date->getTimestamp())
            ->first();

        return [
            'from' => $sourceStart ? $sourceStart->copy()->startOfDay() : $from,
            'to' => $to?->copy()->endOfDay(),
        ];
    }

    /**
     * Genera los cortes mensuales para dibujar la tendencia por fecha.
     */
    private function buildLavadoraTrendTimelineCuts(Collection $eventos, ?array $dateRange = null): Collection
    {
        $eventosOrdenados = $eventos
            ->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())
            ->values();

        $firstEvent = $eventosOrdenados->first()['occurred_at'] ?? null;
        $lastEvent = $eventosOrdenados->last()['occurred_at'] ?? null;
        $start = ($dateRange['from'] ?? null) instanceof Carbon ? $dateRange['from']->copy() : ($firstEvent ? $firstEvent->copy() : null);
        $end = ($dateRange['to'] ?? null) instanceof Carbon ? $dateRange['to']->copy() : ($lastEvent ? $lastEvent->copy() : null);

        if (!$start || !$end || $start->gt($end)) {
            return collect();
        }

        $cursor = $start->copy()->startOfMonth();
        $lastMonth = $end->copy()->startOfMonth();
        $cuts = collect();

        while ($cursor->lte($lastMonth)) {
            $cutAt = $cursor->copy()->endOfMonth()->endOfDay();

            if ($cursor->isSameMonth($end)) {
                $cutAt = $end->copy()->endOfDay();
            }

            $cuts->push([
                'label' => $this->formatLavadoraTrendTimelineLabel($cursor),
                'cut_at' => $cutAt,
            ]);

            $cursor->addMonthNoOverflow();
        }

        return $cuts;
    }

    /**
     * Cuenta los eventos contenidos en una ventana usando un corte puntual.
     */
    private function countLavadoraTrendEventsForWindowAtCut(Collection $eventos, array $window, Carbon $cutAt): int
    {
        $range = $this->resolveLavadoraTrendCurrentRange($window, $cutAt);

        return $eventos
            ->filter(fn (array $item) => $item['occurred_at']->between($range['current_start'], $range['current_end'], true))
            ->count();
    }

    /**
     * Resuelve el rango actual de una ventana a partir de una fecha de corte.
     */
    private function resolveLavadoraTrendCurrentRange(array $window, Carbon $reference): array
    {
        $cutAt = $reference->copy()->endOfDay();
        $size = max((int) ($window['size'] ?? 1), 1);

        switch ($window['type']) {
            case 'days':
                $currentStart = $cutAt->copy()->subDays($size - 1)->startOfDay();
                break;

            case 'weeks':
                $currentStart = $cutAt->copy()->subWeeks($size)->addDay()->startOfDay();
                break;

            case 'quarters':
                $currentStart = $cutAt->copy()->startOfQuarter()->subQuarters($size - 1)->startOfDay();
                break;

            case 'months':
            default:
                $currentStart = $cutAt->copy()->subMonthsNoOverflow($size)->addDay()->startOfDay();
                break;
        }

        return [
            'current_start' => $currentStart,
            'current_end' => $cutAt,
        ];
    }

    /**
     * Formatea la etiqueta mensual visible en la grafica.
     */
    private function formatLavadoraTrendTimelineLabel(Carbon $month): string
    {
        $months = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        return ($months[(int) $month->format('n')] ?? $month->format('m')) . ' ' . $month->format('Y');
    }

    /**
     * Obtiene el flujo real de eventos que deben contarse como falla.
     */
    private function getLavadoraTrendEvents($lineasLavadora, ?Collection $analisisHistoricos = null, ?array $dateRange = null): Collection
    {
        if ($lineasLavadora->isEmpty()) {
            return collect();
        }

        $analisisHistoricos = $analisisHistoricos ?: $this->getAnalisisHistoricosLavadoras($lineasLavadora);
        $lineaIds = $lineasLavadora->pluck('id');
        $lineaIdsByName = $lineasLavadora->pluck('id', 'nombre');
        $lineaNamesById = $lineasLavadora->pluck('nombre', 'id');

        $eventosComponente = $analisisHistoricos
            ->filter(function ($item) use ($dateRange) {
                return $item->fecha_analisis
                    && $this->isLavadoraTrendDateInRange($item->fecha_analisis->copy()->startOfDay(), $dateRange)
                    && $this->isLavadoraTrendDamageState($item->estado);
            })
            ->map(function (AnalisisLavadora $item) {
                return [
                    'id' => $item->id,
                    'source' => 'componente',
                    'type_label' => $item->estado,
                    'linea_id' => $item->linea_id,
                    'linea' => optional($item->linea)->nombre,
                    'occurred_at' => $item->fecha_analisis->copy()->startOfDay(),
                    'fecha_humana' => $item->fecha_analisis->format('d/m/Y'),
                ];
            });

        $eventosElongacion = Elongacion::query()
            ->where(function ($query) use ($lineaIds, $lineasLavadora) {
                $query->whereIn('linea_id', $lineaIds)
                    ->orWhereIn('linea', $lineasLavadora->pluck('nombre'));
            })
            ->where(function ($query) {
                $query->where('bombas_porcentaje', '>', Elongacion::LIMITE_CAMBIO)
                    ->orWhere('vapor_porcentaje', '>', Elongacion::LIMITE_CAMBIO);
            })
            ->when(($dateRange['from'] ?? null) instanceof Carbon, function ($query) use ($dateRange) {
                $query->where('created_at', '>=', $dateRange['from']);
            })
            ->when(($dateRange['to'] ?? null) instanceof Carbon, function ($query) use ($dateRange) {
                $query->where('created_at', '<=', $dateRange['to']);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (Elongacion $item) use ($lineaIdsByName, $lineaNamesById) {
                $lineaId = $item->linea_id ?: $lineaIdsByName->get($item->linea);
                $lineaNombre = $item->linea ?: $lineaNamesById->get($lineaId);
                $fecha = $item->created_at?->copy()->startOfDay();

                return [
                    'id' => $item->id,
                    'source' => 'elongacion',
                    'type_label' => $this->getLavadoraTrendElongationLabel(),
                    'linea_id' => $lineaId,
                    'linea' => $lineaNombre,
                    'occurred_at' => $fecha,
                    'fecha_humana' => $fecha?->format('d/m/Y'),
                ];
            })
            ->filter(fn (array $item) => $item['linea_id'] && $item['occurred_at']);

        return $eventosComponente
            ->concat($eventosElongacion)
            ->sortBy(function (array $item) {
                return sprintf(
                    '%s-%s-%010d',
                    $item['linea_id'],
                    $item['occurred_at']->format('YmdHis'),
                    (int) ($item['id'] ?? 0)
                );
            })
            ->values();
    }

    /**
     * Resume una ventana actual contra su periodo anterior equivalente.
     */
    private function buildLavadoraTrendWindowSummary(Collection $eventos, array $window, ?array $dateRange = null): array
    {
        $ranges = $this->resolveLavadoraTrendWindowRanges($window, $dateRange);
        $currentEvents = $eventos
            ->filter(fn (array $item) => $item['occurred_at']->between($ranges['current_start'], $ranges['current_end'], true))
            ->values();
        $previousEvents = $eventos
            ->filter(fn (array $item) => $item['occurred_at']->between($ranges['previous_start'], $ranges['previous_end'], true))
            ->values();

        $current = $currentEvents->count();
        $previous = $previousEvents->count();
        $delta = $current - $previous;
        $trend = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable');

        return [
            'key' => $window['key'],
            'label' => $window['label'],
            'current' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'trend' => $trend,
            'tone' => $this->resolveLavadoraTrendTone($trend, $current),
            'current_range' => $this->formatLavadoraTrendRange($ranges['current_start'], $ranges['current_end']),
            'previous_range' => $this->formatLavadoraTrendRange($ranges['previous_start'], $ranges['previous_end']),
            'current_componentes' => $currentEvents->where('source', 'componente')->count(),
            'current_elongaciones' => $currentEvents->where('source', 'elongacion')->count(),
            'previous_componentes' => $previousEvents->where('source', 'componente')->count(),
            'previous_elongaciones' => $previousEvents->where('source', 'elongacion')->count(),
        ];
    }

    /**
     * Resuelve las fechas de corte para una ventana de tendencia.
     */
    private function resolveLavadoraTrendWindowRanges(array $window, ?array $dateRange = null): array
    {
        $reference = (($dateRange['to'] ?? null) instanceof Carbon ? $dateRange['to']->copy() : now()->copy())->endOfDay();
        $size = max((int) ($window['size'] ?? 1), 1);

        switch ($window['type']) {
            case 'days':
                $currentStart = $reference->copy()->subDays($size - 1)->startOfDay();
                $currentEnd = $reference->copy();
                $previousStart = $currentStart->copy()->subDays($size);
                $previousEnd = $currentEnd->copy()->subDays($size);
                break;

            case 'weeks':
                $currentStart = $reference->copy()->subWeeks($size)->addDay()->startOfDay();
                $currentEnd = $reference->copy();
                $previousStart = $currentStart->copy()->subWeeks($size);
                $previousEnd = $currentEnd->copy()->subWeeks($size);
                break;

            case 'quarters':
                $currentStart = $reference->copy()->startOfQuarter()->subQuarters($size - 1)->startOfDay();
                $currentEnd = $reference->copy();
                $previousStart = $currentStart->copy()->subYear()->startOfDay();
                $previousEnd = $currentEnd->copy()->subYear();
                break;

            case 'months':
            default:
                $currentStart = $reference->copy()->subMonthsNoOverflow($size)->addDay()->startOfDay();
                $currentEnd = $reference->copy();
                $previousStart = $currentStart->copy()->subMonthsNoOverflow($size);
                $previousEnd = $currentEnd->copy()->subMonthsNoOverflow($size);
                break;
        }

        return [
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * Determina el estado general de la tendencia por linea.
     */
    private function resolveLavadoraTrendStatus(Collection $ventanas): array
    {
        if ($ventanas->isEmpty() || $ventanas->every(fn (array $item) => (int) $item['current'] === 0)) {
            return [
                'label' => 'Sin fallas',
                'tone' => 'success',
                'up' => 0,
                'down' => 0,
                'stable' => $ventanas->count(),
            ];
        }

        $up = $ventanas->where('trend', 'up')->count();
        $down = $ventanas->where('trend', 'down')->count();
        $stable = $ventanas->where('trend', 'stable')->count();

        if ($up > $down) {
            return ['label' => 'Acelerando', 'tone' => 'danger', 'up' => $up, 'down' => $down, 'stable' => $stable];
        }

        if ($down > $up) {
            return ['label' => 'En descenso', 'tone' => 'success', 'up' => $up, 'down' => $down, 'stable' => $stable];
        }

        if ($up === 0 && $down === 0) {
            return ['label' => 'Estable', 'tone' => 'info', 'up' => $up, 'down' => $down, 'stable' => $stable];
        }

        return ['label' => 'Mixto', 'tone' => 'warning', 'up' => $up, 'down' => $down, 'stable' => $stable];
    }

    /**
     * Define el tono visual del indicador de tendencia.
     */
    private function resolveLavadoraTrendTone(string $trend, int $current): string
    {
        return match ($trend) {
            'up' => 'danger',
            'down' => 'success',
            default => $current > 0 ? 'warning' : 'info',
        };
    }

    /**
     * Criterios visibles para ambos modulos de tendencia.
     */
    private function getLavadoraTrendCriteria(): array
    {
        return [
            'DaÃ±ado - Requiere cambio',
            'Desgaste severo',
            'Desgaste moderado',
            $this->getLavadoraTrendElongationLabel(),
        ];
    }

    /**
     * Etiqueta visible para eventos de elongacion fuera de limite.
     */
    private function getLavadoraTrendElongationLabel(): string
    {
        return sprintf('Elongación fuera de límite (> %.2f%%)', Elongacion::LIMITE_CAMBIO);
    }

    /**
     * Normaliza y valida los estados que representan una falla para tendencia.
     */
    private function isLavadoraTrendDamageState(?string $estado): bool
    {
        $normalizado = $this->normalizeLavadoraState($estado);

        return in_array($normalizado, self::LAVADORA_TREND_COMPONENT_STATES, true);
    }

    /**
     * Normaliza el texto de estado para comparaciones robustas.
     */
    private function normalizeLavadoraState(?string $estado): string
    {
        return Str::of((string) $estado)->ascii()->lower()->squish()->value();
    }

    /**
     * Determina si el estado representa un dano relevante para dashboard.
     */
    private function isLavadoraDamageState(?string $estado): bool
    {
        return in_array($this->normalizeLavadoraState($estado), self::LAVADORA_DAMAGE_STATE_KEYS, true);
    }

    /**
     * Determina si el estado es critico y requiere cambio.
     */
    private function isLavadoraCriticalState(?string $estado): bool
    {
        return in_array($this->normalizeLavadoraState($estado), self::LAVADORA_CRITICAL_STATE_KEYS, true);
    }

    /**
     * Determina si el estado corresponde a desgaste severo o moderado.
     */
    private function isLavadoraSevereState(?string $estado): bool
    {
        return in_array($this->normalizeLavadoraState($estado), self::LAVADORA_SEVERE_STATE_KEYS, true);
    }

    /**
     * Determina si el estado requiere revision operativa.
     */
    private function isLavadoraReviewState(?string $estado): bool
    {
        return in_array($this->normalizeLavadoraState($estado), self::LAVADORA_REVIEW_STATE_KEYS, true);
    }

    /**
     * Formatea un rango de fechas corto para UI.
     */
    private function formatLavadoraTrendRange(Carbon $start, Carbon $end): string
    {
        return $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
    }

    /**
     * Asigna el icono visual del componente de lavadora.
     */
    private function attachLavadoraComponentIcon(AnalisisLavadora $analisis): AnalisisLavadora
    {
        if ($analisis->componente) {
            $analisis->componente->icono = $this->getLavadoraComponentIcon($analisis->componente->codigo);
        }

        return $analisis;
    }

    /**
     * Devuelve la ruta del icono del componente.
     */
    private function getLavadoraComponentIcon(?string $codigo): string
    {
        if (!$codigo) {
            return asset('images/componentes-lavadora/default.png');
        }

        $codigoNormalizado = preg_replace('/^L\d+_reductor_\d+_/', '', $codigo);
        $codigoNormalizado = strtoupper(trim((string) $codigoNormalizado));

        return asset("images/componentes-lavadora/{$codigoNormalizado}.png");
    }

    /**
     * Metadatos visuales y de prioridad por severidad.
     */
    private function getLavadoraSeverityMeta(?string $estado): array
    {
        return match ($this->normalizeLavadoraState($estado)) {
            'danado - requiere cambio',
            'dano - requiere cambio' => [
                'level' => 'critico',
                'label' => 'Critico',
                'color' => '#ef4444',
                'score' => 100,
            ],
            'requiere revision' => [
                'level' => 'revision',
                'label' => 'Requiere revision',
                'color' => '#f59e0b',
                'score' => 20,
            ],
            'desgaste severo' => [
                'level' => 'severo',
                'label' => 'Severo / Moderado',
                'color' => '#f97316',
                'score' => 70,
            ],
            'desgaste moderado' => [
                'level' => 'severo',
                'label' => 'Severo / Moderado',
                'color' => '#f97316',
                'score' => 45,
            ],
            'cambiado' => [
                'level' => 'cambiado',
                'label' => 'Cambiado',
                'color' => '#10b981',
                'score' => 5,
            ],
            default => [
                'level' => 'estable',
                'label' => 'Estable',
                'color' => '#10b981',
                'score' => 0,
            ],
        };
    }

    /**
     * Normaliza la prioridad de un plan de accion.
     */
    private function resolvePlanPriority(PlanAccion $plan): array
    {
        if ($plan->completado) {
            return [
                'level' => 'completado',
                'label' => 'Completado',
                'sort' => 9,
                'date' => null,
                'days' => null,
            ];
        }

        $fechas = collect([
            $plan->fecha_pcm1,
            $plan->fecha_pcm2,
            $plan->fecha_pcm3,
            $plan->fecha_pcm4,
        ])->filter();

        if ($fechas->isEmpty()) {
            return [
                'level' => 'sin_fecha',
                'label' => 'Sin fecha',
                'sort' => 4,
                'date' => null,
                'days' => null,
            ];
        }

        /** @var Carbon $proximaFecha */
        $proximaFecha = $fechas->sort()->first();
        $dias = now()->startOfDay()->diffInDays($proximaFecha->copy()->startOfDay(), false);

        if ($dias < 0) {
            return [
                'level' => 'alta',
                'label' => 'Vencido',
                'sort' => 1,
                'date' => $proximaFecha,
                'days' => $dias,
            ];
        }

        if ($dias <= 3) {
            return [
                'level' => 'alta',
                'label' => 'Alta',
                'sort' => 1,
                'date' => $proximaFecha,
                'days' => $dias,
            ];
        }

        if ($dias <= 10) {
            return [
                'level' => 'media',
                'label' => 'Media',
                'sort' => 2,
                'date' => $proximaFecha,
                'days' => $dias,
            ];
        }

        return [
            'level' => 'baja',
            'label' => 'Baja',
            'sort' => 3,
            'date' => $proximaFecha,
            'days' => $dias,
        ];
    }

    /**
     * Obtiene fallas reales por línea para el dashboard global de pasteurizadoras.
     */
    private function getFallasPorLineaPasteurizadora($pasteurizadoras, $analisisPasteurizadora)
    {
        $fechaLimite = Carbon::now()->subMonths(12);

        return $pasteurizadoras->map(function ($linea) use ($analisisPasteurizadora, $fechaLimite) {
            $analisisLinea = $analisisPasteurizadora
                ->where('linea_id', $linea->id)
                ->filter(fn($item) => $item->fecha_analisis && $item->fecha_analisis->gte($fechaLimite));
            $criticos = $analisisLinea->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count();
            $desgaste = $analisisLinea->whereIn('estado', AnalisisPasteurizadora::ESTADOS_DESGASTE)->count();
            $requiereRevision = $analisisLinea->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count();

            return [
                'linea' => $linea->nombre,
                'total_fallas' => $criticos + $desgaste + $requiereRevision,
                'criticos' => $criticos,
                'desgaste' => $desgaste,
                'requiere_revision' => $requiereRevision,
            ];
        })->sortByDesc('total_fallas')->values();
    }

    /**
     * Obtiene componentes con daño o desgaste para la gráfica de pasteurizadoras.
     */
    private function getComponentesDanadosPasteurizadora($analisisPasteurizadora)
    {
        return $analisisPasteurizadora
            ->filter(function ($item) {
                return in_array($item->estado, AnalisisPasteurizadora::estadosDanado(), true)
                    || in_array($item->estado, ['Desgaste moderado', 'Desgaste severo'], true);
            })
            ->groupBy(fn($item) => $item->componente_nombre ?? $item->componente ?? 'Sin componente')
            ->map(function ($items, $componente) {
                return [
                    'componente' => $componente,
                    'total_danios' => $items->count(),
                ];
            })
            ->sortByDesc('total_danios')
            ->take(8)
            ->values();
    }

    /**
     * Obtiene conteo histórico de análisis por componente de pasteurizadoras.
     */
    private function getHistoricoRevisionesPasteurizadora($analisisPasteurizadora)
    {
        return $analisisPasteurizadora
            ->groupBy(fn($item) => $item->componente_nombre ?? $item->componente ?? 'Sin componente')
            ->map(function ($items, $componente) {
                return [
                    'componente' => $componente,
                    'total_analisis' => $items->count(),
                    'ultimo_analisis' => optional($items->sortByDesc('fecha_analisis')->first()?->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha',
                ];
            })
            ->sortByDesc('total_analisis')
            ->take(10)
            ->values();
    }

    /**
     * Obtiene los últimos registros 52-12-4 conectados a líneas de pasteurizadora.
     */
    private function getAnalisis52124Pasteurizadora($pasteurizadoras)
    {
        $tendenciaDanos = app(TendenciaDanosService::class);

        return $tendenciaDanos->construirDashboard(
            $pasteurizadoras,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            $tendenciaDanos->ventanas52124()
        );
    }

    /**
     * Obtiene planes de acción activos conectados a pasteurizadoras.
     */
    private function getPlanesPendientesPasteurizadora($pasteurizadoras, $analisisPasteurizadora = null)
    {
        $analisisPasteurizadora = $analisisPasteurizadora ?: AnalisisPasteurizadora::with('linea')
            ->whereIn('linea_id', $pasteurizadoras->pluck('id'))
            ->where('resuelto_por_cambio', false)
            ->get();

        return $analisisPasteurizadora
            ->whereIn('estado', AnalisisPasteurizadora::estadosDanado())
            ->sortByDesc('fecha_analisis')
            ->values();
    }

    /**
     * Obtiene el estado detallado de todas las pasteurizadoras.
     */
    private function getEstadoPasteurizadoras($pasteurizadoras, $analisisPasteurizadora)
    {
        $estadoPasteurizadoras = [];

        foreach ($pasteurizadoras as $pasteurizadora) {
            $analisisLinea = $analisisPasteurizadora->where('linea_id', $pasteurizadora->id);
            $criticos = $analisisLinea->whereIn('estado', AnalisisPasteurizadora::estadosDanado());

            $componentesLista = AnalisisPasteurizadora::getComponentesPorLinea($pasteurizadora->nombre);
            $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($pasteurizadora->nombre);

            $totalComponentesConfigurados = 0;
            $cantidadComponentesRevisados = 0;
            $componentesRevisados = 0;
            $totalComponentes = 0;

            foreach ($componentesLista as $codigo => $compData) {
                for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
                    if (
                        AnalisisPasteurizadora::esBrazoTorsion($codigo)
                        && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($pasteurizadora->nombre)
                    ) {
                        continue;
                    }

                    $totalPorComponente = (int) ($compData['cantidad'] ?? 0);
                    $registrosComponente = $analisisLinea
                        ->where('modulo', $modulo)
                        ->where('componente', $codigo)
                        ->values();

                    $resumenCiclo = AnalisisPasteurizadora::buildResumenCicloComponenteFromCollection(
                        $registrosComponente,
                        $totalPorComponente
                    );
                    $estadoVisible = $resumenCiclo['estado_visible'] ?? [];
                    $resumenVisible = $resumenCiclo['resumen_visible'] ?? [];

                    $totalComponentes++;
                    $totalComponentesConfigurados += $totalPorComponente * count(AnalisisPasteurizadora::NIVELES) * count(AnalisisPasteurizadora::LADOS);

                    foreach (AnalisisPasteurizadora::NIVELES as $nivelRevision) {
                        foreach (AnalisisPasteurizadora::LADOS as $ladoRevision) {
                            $cantidadComponentesRevisados += min(
                                count($estadoVisible[$nivelRevision][$ladoRevision] ?? []),
                                $totalPorComponente
                            );
                        }
                    }

                    if (($resumenVisible['completado'] ?? false) === true) {
                        $componentesRevisados++;
                    }
                }
            }

            $cantidadComponentesRevisados = min($cantidadComponentesRevisados, $totalComponentesConfigurados);
            $porcentajeRevision = $totalComponentesConfigurados > 0 ? round(($cantidadComponentesRevisados / $totalComponentesConfigurados) * 100) : 0;

            $criticosCount = $criticos->count();
            $desgasteCount = $analisisLinea->whereIn('estado', AnalisisPasteurizadora::ESTADOS_DESGASTE)->count();
            $revisionOperativa = $analisisLinea->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->values();
            $revisionOperativaCount = $revisionOperativa->count();

            if ($criticosCount > 0) {
                $nivel = 'critico';
                $mensaje = "⚠️ {$criticosCount} componente(s) requieren cambio urgente";
            } elseif ($desgasteCount > 0) {
                $nivel = 'riesgo';
                $mensaje = "⚠️ {$desgasteCount} componente(s) presentan condición severa o moderada";
            } elseif ($revisionOperativaCount > 0) {
                $nivel = 'operativo';
                $mensaje = "🔧 {$revisionOperativaCount} componente(s) requieren revisión";
            } else {
                $nivel = 'bueno';
                $mensaje = "✅ Todos los componentes en buen estado";
            }

            $ultimoAnalisis = $analisisLinea->sortByDesc('fecha_analisis')->first();

            $estadoPasteurizadoras[] = [
                'id' => $pasteurizadora->id,
                'nombre' => $pasteurizadora->nombre,
                'estado' => [
                    'nivel' => $nivel,
                    'mensaje' => $mensaje,
                    'analisis_criticos' => $criticos->values()->map(function($item) {
                        return [
                            'id' => $item->id,
                            'modulo' => $item->modulo,
                            'componente_nombre' => $item->componente_nombre,
                            'lado' => $item->lado,
                            'numero_orden' => $item->numero_orden,
                            'actividad' => $item->actividad,
                            'fecha_analisis' => $item->fecha_analisis,
                            'fecha_formateada' => $item->fecha_formateada
                        ];
                    })->toArray(),
                    'analisis_revision' => $revisionOperativa->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'modulo' => $item->modulo,
                            'componente_nombre' => $item->componente_nombre,
                            'lado' => $item->lado,
                            'numero_orden' => $item->numero_orden,
                            'actividad' => $item->actividad,
                            'fecha_analisis' => $item->fecha_analisis,
                            'fecha_formateada' => $item->fecha_formateada,
                        ];
                    })->toArray(),
                    'acciones_pendientes' => $criticosCount,
                    'requiere_revision' => $revisionOperativaCount,
                    'ultimo_analisis' => $ultimoAnalisis ? [
                        'fecha' => $ultimoAnalisis->fecha_formateada,
                        'modulos' => $totalModulos
                    ] : null,
                    'progreso_revision' => [
                        'porcentaje' => $porcentajeRevision,
                        'revisados' => $cantidadComponentesRevisados,
                        'total' => $totalComponentesConfigurados,
                        'componentes_revisados' => $componentesRevisados,
                        'total_componentes' => $totalComponentes
                    ],
                    'alert_carousel' => $this->buildPasteurizadoraAlertCarousel(
                        $criticos->values(),
                        $revisionOperativa,
                        $desgasteCount,
                        $criticosCount,
                        $porcentajeRevision,
                        $ultimoAnalisis,
                        $nivel
                    )
                ]
            ];
        }

        return $estadoPasteurizadoras;
    }

    /**
     * Construye los items del carrusel para tarjetas de pasteurizadoras.
     */
    private function buildPasteurizadoraAlertCarousel($criticos, $revisionOperativa, int $desgasteCount, int $accionesPendientes, int $porcentajeRevision, $ultimoAnalisis, string $nivel)
    {
        $items = [];

        foreach ($criticos as $analisis) {
            $codigo = strtoupper((string) $analisis->componente);
            $items[] = [
                'type' => 'componente',
                'title' => $analisis->componente_nombre ?? 'Componente crítico',
                'subtitle' => "Módulo {$analisis->modulo}" . ($analisis->lado ? " · {$analisis->lado}" : ''),
                'image' => asset("images/componentes-pasteurizadora/{$codigo}.png"),
                'fallback_image' => asset('images/icono-pasteurizadora.png'),
                'detail' => $analisis->actividad ?? 'Componente requiere cambio.',
                'meta' => $analisis->numero_orden,
                'fecha' => $analisis->fecha_formateada,
            ];
        }

        if ($accionesPendientes > 0) {
            $items[] = [
                'type' => 'alert',
                'title' => 'Acciones pendientes',
                'subtitle' => "{$accionesPendientes} componente(s) requieren cambio",
                'description' => 'Revisa el plan de acción de pasteurizadora para cerrar las actividades.',
                'icon' => 'fa-tasks',
            ];
        }

        if ($desgasteCount > 0) {
            $items[] = [
                'type' => 'alert',
                'title' => 'Severidad detectada',
                'subtitle' => "{$desgasteCount} componente(s) con condición severa o moderada",
                'description' => 'Existen componentes con severidad moderada o severa que deben monitorearse.',
                'icon' => 'fa-exclamation-triangle',
            ];
        }

        foreach ($revisionOperativa as $analisis) {
            $codigo = strtoupper((string) $analisis->componente);
            $items[] = [
                'type' => 'componente',
                'title' => $analisis->componente_nombre ?? 'Componente en revisión',
                'subtitle' => "Módulo {$analisis->modulo}" . ($analisis->lado ? " · {$analisis->lado}" : ''),
                'image' => asset("images/componentes-pasteurizadora/{$codigo}.png"),
                'fallback_image' => asset('images/icono-pasteurizadora.png'),
                'detail' => $analisis->actividad ?? 'Se detectó una anomalía operativa que debe revisarse.',
                'meta' => $analisis->numero_orden,
                'fecha' => $analisis->fecha_formateada,
                'icon' => 'fa-tools',
            ];
        }

        $items[] = [
            'type' => 'info',
            'title' => 'Avance de revisión',
            'subtitle' => "{$porcentajeRevision}% completado",
            'description' => $ultimoAnalisis ? "Último análisis: {$ultimoAnalisis->fecha_formateada}" : 'Sin análisis registrado todavía.',
            'icon' => 'fa-chart-line',
        ];

        if ($nivel === 'bueno' && count($items) === 1) {
            $items[] = [
                'type' => 'info',
                'title' => 'Sin alertas activas',
                'subtitle' => 'Pasteurizadora en buen estado',
                'description' => 'No hay componentes críticos ni desgaste activo en este momento.',
                'icon' => 'fa-check-circle',
            ];
        }

        return $items;
    }
}
