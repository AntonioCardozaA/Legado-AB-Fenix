<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\Linea;
use App\Models\Elongacion;
use App\Models\PlanAccion;
use App\Models\AnalisisTendenciaMensualLavadora;
use App\Models\AnalisisPasteurizadora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
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
        if (auth()->user()?->hasRole('tecnico')
            && !auth()->user()?->hasAnyRole(['admin', 'ingeniero_mantenimiento', 'supervisor'])) {
            return redirect()->route('tecnico.dashboard');
        }

        // Configuración de módulos disponibles (escalable para futuro)
        $modulos = [
            [
                'id' => 'lavadora',
                'nombre' => 'Lavadoras',
                'descripcion' => '',
                'icono' => 'fa-industry',
                'imagen_personalizada' => true,
                'icono_imagen' => 'images/icono-maquina.png',
                'color' => 'blue',
                'ruta' => route('dashboard.global.lavadoras'),
                'estadisticas' => $this->getLavadoraStats(),
                'activo' => true
            ],
            [
                'id' => 'pasteurizadora',
                'nombre' => 'Pasteurizadoras',
                'descripcion' => '',
                'icono' => 'fa-temperature-high',
                'imagen_personalizada' => true,
                'icono_imagen' => 'images/icono_pas.png',
                'color' => 'orange',
                'ruta' => route('dashboard.global.pasteurizadoras'),
                'estadisticas' => $this->getPasteurizadoraStats(),
                'activo' => true
            ]
        ];

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
    public function lavadoraGlobal()
{
    $lineasLavadora = Linea::where('activo', true)
        ->whereIn('nombre', ['L-04','L-05','L-06','L-07','L-08','L-09','L-12','L-13'])
        ->orderBy('nombre')
        ->get();

    $resumenGeneral = $this->getResumenGeneral($lineasLavadora);
    $estadoLavadoras = $this->getEstadoLavadoras($lineasLavadora);
    $rankingDanos = $this->getRankingDanos($lineasLavadora);
    $fallasPorLinea = $this->getFallasPorLinea($lineasLavadora);
    $componentesDanados = $this->getComponentesDanados($lineasLavadora);
    $evolucionElongaciones = $this->getEvolucionElongaciones($lineasLavadora);
    $historicoRevisiones = $this->getHistoricoRevisiones($lineasLavadora);
    $analisis52124 = $this->getAnalisis52124($lineasLavadora);

    return view('dashboard_lavadora', compact(
        'lineasLavadora',
        'resumenGeneral',
        'estadoLavadoras',
        'rankingDanos',
        'fallasPorLinea',
        'componentesDanados',
        'evolucionElongaciones',
        'historicoRevisiones',
        'analisis52124'
    ));
}

    /**
     * RUTA: GET /dashboard/global/pasteurizadoras
     * NOMBRE: dashboard.global.pasteurizadoras
     * VISTA: dashboard_pasteurizadora.blade.php
     * DESCRIPCIÓN: Dashboard global de pasteurizadoras
     */
   public function pasteurizadoraGlobal()
{
    if (auth()->user()?->hasRole('tecnico')
        && !auth()->user()?->hasAnyRole(['admin', 'ingeniero_mantenimiento', 'supervisor'])) {
        return redirect()
            ->route('lavadora.dashboard')
            ->with('pasteurizadora_bloqueada', 'Estamos trabajando en ello, estará disponible muy pronto.');
    }

    $pasteurizadoras = Linea::whereIn('nombre', [
        'P-03','P-04','P-05','P-06','P-07','P-08','P-09','P-10','P-11','P-12','P-13','P-14'
    ])->get();

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
        'buen_estado' => $estadoPasteurizadoras->where('estado.nivel', 'bueno')->count(),
        'pendientes_accion' => $planesPendientesPasteurizadora->count(),
        'ultima_actualizacion' => now()->format('d/m/Y H:i'),
    ];

    $fallasPorLineaPasteurizadora = $this->getFallasPorLineaPasteurizadora($pasteurizadoras, $analisisHistorico);
    $componentesDanadosPasteurizadora = $this->getComponentesDanadosPasteurizadora($analisisHistorico);
    $historicoRevisionesPasteurizadora = $this->getHistoricoRevisionesPasteurizadora($analisisHistorico);
    $analisis52124Pasteurizadora = $this->getAnalisis52124Pasteurizadora($pasteurizadoras);
    $ultimosAnalisisPasteurizadora = $analisisHistorico->sortByDesc('fecha_analisis')->take(8)->values();

    return view('dashboard_pasteurizadora', compact(
        'resumenPasteurizadora',
        'estadoPasteurizadoras',
        'fallasPorLineaPasteurizadora',
        'componentesDanadosPasteurizadora',
        'historicoRevisionesPasteurizadora',
        'analisis52124Pasteurizadora',
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
        $analisis52124 = $this->getAnalisis52124($lineasLavadora);

        return view('lavadora.dashboard-lavadora', compact(
            'lineasLavadora',
            'resumenGeneral',
            'estadoLavadoras',
            'rankingDanos',
            'fallasPorLinea',
            'componentesDanados',
            'evolucionElongaciones',
            'historicoRevisiones',
            'analisis52124'
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
    if (auth()->user()?->hasRole('tecnico')
        && !auth()->user()?->hasAnyRole(['admin', 'ingeniero_mantenimiento', 'supervisor'])) {
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
        'buen_estado' => $estadoPasteurizadorasResumen->where('estado.nivel', 'bueno')->count(),
        'pendientes_accion' => $estadoPasteurizadorasResumen->sum('estado.acciones_pendientes'),
        'ultima_actualizacion' => now()->format('d/m/Y H:i')
    ];

    return view('pasteurizadora.dashboard', compact(
        'resumenPasteurizadora',
        'estadoPasteurizadoras'
    ));
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
            ->pluck('id');

        $datos = AnalisisTendenciaMensualLavadora::whereIn('linea_id', $lineas)
            ->with('linea')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get()
            ->groupBy('linea.nombre');

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
            ->whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->orderBy('nombre')
            ->get();

        $alertasCriticas = 0;
        $enRiesgo = 0;
        $buenEstado = 0;

        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            if ($estado['nivel'] === 'critico') {
                $alertasCriticas++;
            } elseif ($estado['nivel'] === 'riesgo') {
                $enRiesgo++;
            } else {
                $buenEstado++;
            }
        }

        return [
            'total_equipos' => $lineasLavadora->count(),
            'alertas_criticas' => $alertasCriticas,
            'en_riesgo' => $enRiesgo,
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

        return [
            'total_equipos' => $pasteurizadoras->count(),
            'alertas_criticas' => $analisis->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count(),
            'en_riesgo' => $analisis->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])->count(),
            'buen_estado' => $analisis->where('estado', 'Buen estado')->count(),
            'ultima_actualizacion' => now()->format('d/m/Y H:i')
        ];
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
        $totalBuenEstado = 0;
        $totalPendientesAccion = PlanAccion::whereIn('linea_id', $lineasLavadora->pluck('id'))->where('completado', false)->count();

        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            if ($estado['nivel'] === 'critico') {
                $totalAlertasCriticas++;
            } elseif ($estado['nivel'] === 'riesgo') {
                $totalEnRiesgo++;
            } else {
                $totalBuenEstado++;
            }
        }

        return [
            'total_lavadoras' => $totalLavadoras,
            'total_analisis' => $totalAnalisis,
            'alertas_criticas' => $totalAlertasCriticas,
            'en_riesgo' => $totalEnRiesgo,
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
        $ultimaElongacion = Elongacion::where('linea', function($query) use ($lineaId) {
                $query->select('nombre')->from('lineas')->where('id', $lineaId);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $analisisCriticos = AnalisisLavadora::ultimosPorComponente()
            ->where('linea_id', $lineaId)
            ->where('estado', 'Dañado - Requiere cambio')
            ->with('componente')
            ->orderBy('fecha_analisis', 'desc')
            ->limit(5)
            ->get()
            ->map(function($a) {
                if ($a->componente) {
                    $codigo = $a->componente->codigo;
                    $codigo = preg_replace('/^L\d+_reductor_\d+_/', '', $codigo);
                    $codigo = strtoupper(trim($codigo));
                    $a->componente->icono = asset("images/componentes-lavadora/{$codigo}.png");
                }
                return $a;
            })
            ->toArray();

        $accionesPendientes = PlanAccion::where('linea_id', $lineaId)
            ->where('completado', false)
            ->count();

        $analisisDesgaste = AnalisisLavadora::ultimosPorComponente()
            ->where('linea_id', $lineaId)
            ->where('estado', 'like', '%Desgaste%')
            ->count();

        $nivel = 'bueno';
        $color = 'green';
        $mensaje = 'Funcionando correctamente';

        if (count($analisisCriticos) > 0) {
            $nivel = 'critico';
            $color = 'red';
            $mensaje = 'Presenta componentes dañados que requieren cambio inmediato.';
        } elseif ($accionesPendientes > 0) {
            $nivel = 'riesgo';
            $color = 'yellow';
            $mensaje = "Tiene {$accionesPendientes} acción(es) pendiente(s) en el plan de acción.";
        } elseif ($ultimaElongacion && ($ultimaElongacion->vapor_porcentaje >= 1.46 || $ultimaElongacion->bombas_porcentaje >= 1.46)) {
            $nivel = 'critico';
            $color = 'red';
            $mensaje = 'Elongación crítica (>1.46%), cambio de cadena requerido.';
        } elseif ($ultimaElongacion && ($ultimaElongacion->vapor_porcentaje >= 1.3 || $ultimaElongacion->bombas_porcentaje >= 1.3)) {
            $nivel = 'riesgo';
            $color = 'yellow';
            $mensaje = 'Elongación en nivel de compra (>1.3%), considerar cambio de cadena.';
        } elseif ($analisisDesgaste > 0) {
            $nivel = 'riesgo';
            $color = 'yellow';
            $mensaje = 'Presenta componentes con desgaste, programar mantenimiento.';
        }

        return [
            'nivel' => $nivel,
            'color' => $color,
            'mensaje' => $mensaje,
            'analisis_criticos' => $analisisCriticos,
            'ultima_elongacion' => $ultimaElongacion,
            'acciones_pendientes' => $accionesPendientes,
            'analisis_desgaste' => $analisisDesgaste,
            'alert_carousel' => $this->buildLavadoraAlertCarousel($analisisCriticos, $accionesPendientes, $ultimaElongacion, $analisisDesgaste, $nivel),
        ];
    }

    /**
     * Construye los items del carrusel para la tarjeta de estado.
     */
    private function buildLavadoraAlertCarousel(array $analisisCriticos, int $accionesPendientes, $ultimaElongacion, int $analisisDesgaste, string $nivel)
    {
        $items = [];

        if (count($analisisCriticos) > 0) {
            foreach ($analisisCriticos as $analisis) {
                $subtitleParts = [];
                if (!empty($analisis['modulo'])) {
                    $subtitleParts[] = "Módulo {$analisis['modulo']}";
                }
                if (!empty($analisis['lado'])) {
                    $subtitleParts[] = $analisis['lado'];
                }

                $items[] = [
                    'type' => 'componente',
                    'title' => $analisis['componente']['nombre'] ?? 'Componente dañado',
                    'subtitle' => count($subtitleParts) ? implode(' · ', $subtitleParts) : 'Componente dañado',
                    'image' => $analisis['componente']['icono'] ?? asset('images/componentes-lavadora/default.png'),
                    'detail' => $analisis['actividad'] ?? 'Problema detectado en el componente.',
                    'reductor' => $analisis['reductor'] ?? null,
                    'fecha' => isset($analisis['fecha_analisis']) ? Carbon::parse($analisis['fecha_analisis'])->format('d/m/Y') : null,
                ];
            }
        }

        if ($accionesPendientes > 0) {
            $items[] = [
                'type' => 'alert',
                'title' => 'Acciones pendientes',
                'subtitle' => "{$accionesPendientes} tarea(s) sin cerrar",
                'description' => 'Revisa el plan de acción para validar y cerrar las actividades pendientes.',
                'icon' => 'fa-tasks',
            ];
        }

        if ($ultimaElongacion && ($ultimaElongacion->vapor_porcentaje >= 1.46 || $ultimaElongacion->bombas_porcentaje >= 1.46)) {
            $items[] = [
                'type' => 'alert',
                'title' => 'Elongación crítica',
                'subtitle' => 'Cambio de cadena requerido',
                'description' => "Bombas: {$ultimaElongacion->bombas_porcentaje}% · Vapor: {$ultimaElongacion->vapor_porcentaje}%",
                'icon' => 'fa-exclamation-triangle',
            ];
        } elseif ($ultimaElongacion && ($ultimaElongacion->vapor_porcentaje >= 1.3 || $ultimaElongacion->bombas_porcentaje >= 1.3)) {
            $items[] = [
                'type' => 'alert',
                'title' => 'Elongación en riesgo',
                'subtitle' => 'Monitorear posible cambio',
                'description' => "Bombas: {$ultimaElongacion->bombas_porcentaje}% · Vapor: {$ultimaElongacion->vapor_porcentaje}%",
                'icon' => 'fa-chart-line',
            ];
        }

        if ($analisisDesgaste > 0) {
            $items[] = [
                'type' => 'alert',
                'title' => 'Desgaste detectado',
                'subtitle' => "{$analisisDesgaste} elementos con desgaste o alguna anomalía",
                'description' => 'Existen componentes con signos de desgaste o anomalías que deben revisarse pronto.',
                'icon' => 'fa-cog',
            ];
        }

        if (empty($items)) {
            $items[] = [
                'type' => 'info',
                'title' => 'Sin alertas activas',
                'subtitle' => 'La lavadora está en buen estado',
                'description' => 'No hay componentes dañados ni alertas críticas en este momento.',
                'icon' => 'fa-check-circle',
            ];
        }

        return $items;
    }

    /**
     * Obtiene el ranking de lavadoras con mayor nivel de daño.
     */
    private function getRankingDanos($lineasLavadora)
    {
        $ranking = [];
        foreach ($lineasLavadora as $linea) {
            $puntajeDanio = 0;
            $analisisCriticos = AnalisisLavadora::where('linea_id', $linea->id)
                ->where('estado', 'Dañado - Requiere cambio')
                ->count();
            $puntajeDanio += $analisisCriticos * 10;

            $analisisDesgaste = AnalisisLavadora::where('linea_id', $linea->id)
                ->where('estado', 'like', '%Desgaste%')
                ->count();
            $puntajeDanio += $analisisDesgaste * 5;

            $ultimaElongacion = Elongacion::where('linea', function($query) use ($linea) {
                $query->select('nombre')->from('lineas')->where('id', $linea->id);
            })->orderBy('created_at', 'desc')->first();

            if ($ultimaElongacion) {
                $puntajeDanio += max($ultimaElongacion->bombas_porcentaje, $ultimaElongacion->vapor_porcentaje) * 2;
            }

            $ranking[] = [
                'linea' => $linea->nombre,
                'puntaje' => round($puntajeDanio, 2),
                'analisis_criticos' => $analisisCriticos,
                'analisis_desgaste' => $analisisDesgaste,
                'ultima_elongacion' => $ultimaElongacion,
            ];
        }

        usort($ranking, function($a, $b) {
            return $b['puntaje'] <=> $a['puntaje'];
        });

        return array_slice($ranking, 0, 5);
    }

    /**
     * Obtiene datos de fallas por línea para la gráfica de barras.
     */
    private function getFallasPorLinea($lineasLavadora)
    {
        $fechaLimite = Carbon::now()->subMonths(12);
        $fallas = [];

        foreach ($lineasLavadora as $linea) {
            $totalFallas = AnalisisLavadora::where('linea_id', $linea->id)
                ->where('fecha_analisis', '>=', $fechaLimite)
                ->whereIn('estado', ['Dañado - Requiere cambio', 'Desgaste severo'])
                ->count();

            $fallas[] = [
                'linea' => $linea->nombre,
                'total_fallas' => $totalFallas,
            ];
        }

        usort($fallas, function($a, $b) {
            return $b['total_fallas'] <=> $a['total_fallas'];
        });

        return $fallas;
    }

    /**
     * Obtiene los componentes más dañados para la gráfica de pastel.
     */
    private function getComponentesDanados($lineasLavadora)
    {
        $componentes = AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->where('estado', 'Dañado - Requiere cambio')
            ->with('componente')
            ->get();

        $conteoComponentes = [];
        foreach ($componentes as $analisis) {
            $nombreComponente = $analisis->componente ? $analisis->componente->nombre : 'Desconocido';
            if (!isset($conteoComponentes[$nombreComponente])) {
                $conteoComponentes[$nombreComponente] = 0;
            }
            $conteoComponentes[$nombreComponente]++;
        }

        $resultado = [];
        foreach ($conteoComponentes as $nombre => $total) {
            $resultado[] = [
                'componente' => $nombre,
                'total_danios' => $total,
            ];
        }

        usort($resultado, function($a, $b) {
            return $b['total_danios'] <=> $a['total_danios'];
        });

        return array_slice($resultado, 0, 5);
    }

    /**
     * Obtiene la evolución de elongaciones para la gráfica de líneas.
     */
    private function getEvolucionElongaciones($lineasLavadora)
    {
        $fechaLimite = Carbon::now()->subMonths(6);
        $lineasNombres = $lineasLavadora->pluck('nombre')->toArray();

        $elongaciones = Elongacion::whereIn('linea', $lineasNombres)
            ->where('created_at', '>=', $fechaLimite)
            ->orderBy('created_at', 'asc')
            ->get();

        $datos = [];
        foreach ($elongaciones as $elongacion) {
            $fecha = $elongacion->created_at->format('Y-m-d');
            if (!isset($datos[$fecha])) {
                $datos[$fecha] = [
                    'fecha' => $fecha,
                    'bombas' => 0,
                    'vapor' => 0,
                    'conteo' => 0,
                ];
            }
            $datos[$fecha]['bombas'] += $elongacion->bombas_porcentaje;
            $datos[$fecha]['vapor'] += $elongacion->vapor_porcentaje;
            $datos[$fecha]['conteo']++;
        }

        $evolucion = [];
        foreach ($datos as $fecha => $data) {
            $evolucion[] = [
                'fecha' => $fecha,
                'bombas' => round($data['bombas'] / $data['conteo'], 2),
                'vapor' => round($data['vapor'] / $data['conteo'], 2),
            ];
        }

        return $evolucion;
    }

    /**
     * Obtiene el histórico de revisiones (conteo de análisis por componente).
     */
    private function getHistoricoRevisiones($lineasLavadora)
    {
        $analisis = AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->with('componente')
            ->get();

        $conteoComponentes = [];
        foreach ($analisis as $item) {
            $nombreComponente = $item->componente ? $item->componente->nombre : 'Desconocido';
            if (!isset($conteoComponentes[$nombreComponente])) {
                $conteoComponentes[$nombreComponente] = 0;
            }
            $conteoComponentes[$nombreComponente]++;
        }

        $resultado = [];
        foreach ($conteoComponentes as $nombre => $total) {
            $resultado[] = [
                'componente' => $nombre,
                'total_analisis' => $total,
            ];
        }

        usort($resultado, function($a, $b) {
            return $b['total_analisis'] <=> $a['total_analisis'];
        });

        return $resultado;
    }

    /**
     * Obtiene los últimos registros del análisis 52-12-4.
     */
    private function getAnalisis52124($lineasLavadora)
    {
        return AnalisisTendenciaMensualLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->with('linea')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $item->periodo = Carbon::create($item->anio, $item->mes, 1)->format('M Y');
                return $item;
            });
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

            return [
                'linea' => $linea->nombre,
                'total_fallas' => $analisisLinea->filter(function ($item) {
                    return in_array($item->estado, AnalisisPasteurizadora::estadosDanado(), true)
                        || $item->estado === 'Desgaste severo';
                })->count(),
                'criticos' => $analisisLinea->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count(),
                'desgaste' => $analisisLinea->where('estado', 'Desgaste severo')->count(),
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
        return AnalisisPasteurizadora::whereIn('linea_id', $pasteurizadoras->pluck('id'))
            ->with('linea')
            ->where(function ($query) {
                $query->whereNotNull('valor_actual_52')
                    ->orWhereNotNull('valor_actual_12')
                    ->orWhereNotNull('valor_actual_4');
            })
            ->orderBy('fecha_analisis', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();
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
            $desgasteCount = $analisisLinea->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])->count();

            if ($criticosCount > 0) {
                $nivel = 'critico';
                $mensaje = "⚠️ {$criticosCount} componente(s) requieren cambio urgente";
            } elseif ($desgasteCount > 0) {
                $nivel = 'riesgo';
                $mensaje = "⚠️ {$desgasteCount} componente(s) presentan desgaste";
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
                    'acciones_pendientes' => $criticosCount,
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
    private function buildPasteurizadoraAlertCarousel($criticos, int $desgasteCount, int $accionesPendientes, int $porcentajeRevision, $ultimoAnalisis, string $nivel)
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
                'title' => 'Desgaste detectado',
                'subtitle' => "{$desgasteCount} componente(s) con desgaste",
                'description' => 'Existen componentes con desgaste moderado o severo que deben monitorearse.',
                'icon' => 'fa-exclamation-triangle',
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
