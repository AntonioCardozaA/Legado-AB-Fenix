<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\Componente;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\Paro;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteLavadoraGeneralExcel;
use Carbon\Carbon;
use App\Models\Elongacion;
use App\Models\AnalisisTendenciaMensualLavadora;
use App\Models\AnalisisTendenciaMensualPasteurizadora;
use App\Models\HistorialRestablecimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReporteController extends Controller
{
    // Líneas de lavadoras
    protected $lavadoras = ['L-04','L-05','L-06','L-07','L-08','L-09','L-12','L-13'];

    // Líneas pasteurizadoras
    protected $pasteurizadoras = ['P-03','P-04','P-05','P-06','P-07','P-08','P-09','P-10','P-11','P-12','P-13','P-14'];

    // Componentes por línea
    protected $componentesPorLinea = [
        'L-04' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-05' => [
            'RV200' => 'Reductor RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-06' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-07' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-08' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-09' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-12' => [
            'RV200_SIN_FIN' => 'Reductor Sin Fin-Corona RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-13' => [
            'RV200' => 'Reductor RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
    ];

    // Reductores por línea
    protected $reductoresPorLinea = [
        'L-04' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor Loca'],
        'L-05' => ['Reductor 1','Reductor 2','Reductor 3','Reductor 4','Reductor 5','Reductor 6','Reductor 7','Reductor 8','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor Principal','Reductor Loca'],
        'L-06' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor 20','Reductor 21','Reductor 22'],
        'L-07' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor 20','Reductor 21','Reductor 22'],
        'L-08' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor Loca'],
        'L-09' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor Loca'],
        'L-12' => ['Reductor 1','Reductor 2','Reductor 3','Reductor 4','Reductor 5','Reductor 6','Reductor 7','Reductor 8','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor Loca'],
        'L-13' => ['Reductor 1','Reductor 2','Reductor 3','Reductor 4','Reductor 5','Reductor 6','Reductor 7','Reductor 8','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor Loca','Reductor Principal']
    ];

    public function index(Request $request)
    {
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $fechaInicio = $request->get('fecha_inicio') 
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();
            
        $fechaFin = $request->get('fecha_fin') 
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        $lineaId = $request->get('linea_id', $request->get('lineaId'));
        $lineasFiltro = Linea::whereIn('nombre', $this->getLineasPorTipo($tipoEquipo))
            ->orderBy('nombre')
            ->get();

        if ($lineaId) {
            $lineaSeleccionada = $lineasFiltro->firstWhere('id', (int) $lineaId);
            $lineaId = $lineaSeleccionada?->id;
        }

        $cacheKey = "reporte_index_v4_{$tipoEquipo}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}";
        
        $reporteGeneral = Cache::remember($cacheKey, 1800, function() use ($tipoEquipo, $fechaInicio, $fechaFin) {
            return $this->generarReporteIndexOptimizado($tipoEquipo, $fechaInicio, $fechaFin);
        });

        $lineas = $lineaId
            ? $lineasFiltro->where('id', (int) $lineaId)->values()
            : $lineasFiltro;
        $canAccessPasteurizadora = auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA) ?? false;

        return view('reportes.index', compact(
            'lineas', 
            'tipoEquipo', 
            'fechaInicio', 
            'fechaFin', 
            'reporteGeneral',
            'canAccessPasteurizadora',
            'lineaId',
            'lineasFiltro'
        ));
    }

    public function pasteurizadora(Request $request)
    {
        $this->ensureCanAccessTipoEquipo('pasteurizadoras');

        return redirect()->route('reportes.index', array_merge(
            $request->query(),
            ['tipo' => 'pasteurizadoras']
        ));
    }

    private function generarReporteIndexOptimizado($tipoEquipo, $fechaInicio, $fechaFin)
    {
        if ($tipoEquipo === 'pasteurizadoras') {
            return $this->generarReporteIndexPasteurizadora($fechaInicio, $fechaFin);
        }

        $nombresLineas = $this->getLineasPorTipo($tipoEquipo);
        $lineas = Linea::whereIn('nombre', $nombresLineas)->get();
        $lineaIds = $lineas->pluck('id');
        $reporteGeneral = [];

        // 1. Estadísticas de análisis por línea (UNA consulta)
        $estadisticasAnalisis = DB::table('analisis_componentes')
            ->whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->select(
                'linea_id',
                DB::raw('COUNT(*) as total_analisis'),
                DB::raw('COUNT(DISTINCT componente_id) as componentes_revisados'),
                DB::raw('SUM(CASE WHEN estado = "Dañado - Requiere cambio" THEN 1 ELSE 0 END) as componentes_criticos'),
                DB::raw('SUM(CASE WHEN estado IN ("Desgaste moderado", "Desgaste severo") THEN 1 ELSE 0 END) as componentes_severos_moderados'),
                DB::raw('SUM(CASE WHEN estado = "Requiere revisión" THEN 1 ELSE 0 END) as componentes_revision')
            )
            ->groupBy('linea_id')
            ->get()
            ->keyBy('linea_id');

        // 2. Elongaciones por línea (UNA consulta)
        $elongaciones = Elongacion::whereIn('linea', $nombresLineas)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->select('linea', 'bombas_porcentaje', 'vapor_porcentaje')
            ->get()
            ->groupBy('linea');

        // 3. Análisis de tendencia (UNA consulta)
        $tendencias = AnalisisTendenciaMensualLavadora::whereIn('linea_id', $lineaIds)
            ->where(function($query) use ($fechaInicio, $fechaFin) {
                $query->whereYear('created_at', $fechaInicio->year)
                      ->orWhereYear('created_at', $fechaFin->year);
            })
            ->select('linea_id', 'total_danos_4_semanas')
            ->get()
            ->groupBy('linea_id');

        // 4. Historial de revisiones (UNA consulta)
        $historicos = HistorialRestablecimiento::whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_restablecimiento', [$fechaInicio, $fechaFin])
            ->select('linea_id', 'fecha_restablecimiento')
            ->orderBy('fecha_restablecimiento', 'desc')
            ->get()
            ->groupBy('linea_id');

        // 5. Últimas revisiones por línea
        $ultimasRevisiones = HistorialRestablecimiento::whereIn('linea_id', $lineaIds)
            ->select('linea_id', 'fecha_restablecimiento')
            ->orderBy('fecha_restablecimiento', 'desc')
            ->get()
            ->unique('linea_id')
            ->keyBy('linea_id');

        $analisisIndex = DB::table('analisis_componentes')
            ->whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->get(['linea_id', 'componente_id', 'estado'])
            ->groupBy('linea_id');

        // Construir reporte para cada línea - VERSIÓN CORREGIDA (ELIMINADAS LAS LÍNEAS SUELTAS)
        foreach ($lineas as $linea) {
            $estadisticas = $estadisticasAnalisis->get($linea->id);

            $elongacionesLinea = $elongaciones->get($linea->nombre, collect([]));
            $tendenciasLinea = $tendencias->get($linea->id, collect([]));
            $historicosLinea = $historicos->get($linea->id, collect([]));
            $ultimaRevision = $ultimasRevisiones->get($linea->id);
            $registrosLinea = $analisisIndex->get($linea->id, collect([]));

            $promedioBombas = $elongacionesLinea->avg('bombas_porcentaje') ?: 0;
            $promedioVapor = $elongacionesLinea->avg('vapor_porcentaje') ?: 0;

            $maxElongacion = max($promedioBombas, $promedioVapor);

            // ⚠️ IMPORTANTE: Todo debe estar DENTRO de este array
            $reporteGeneral[$linea->id] = [
                'total_analisis' => $registrosLinea->count(),
                'componentes_revisados' => $registrosLinea->pluck('componente_id')->filter()->unique()->count(),
                'total_componentes' => count($this->componentesPorLinea[$linea->nombre] ?? []),
                'acciones_pendientes' => $registrosLinea->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado))->count(),
                'elongacion_max' => $maxElongacion,
                'promedio_bombas' => $promedioBombas,
                'promedio_vapor' => $promedioVapor,
                'analisis_tendencia_count' => $tendenciasLinea->count(),
                'total_danos_4' => $tendenciasLinea->sum('total_danos_4_semanas'),
                'historicos' => $historicosLinea->count(),
                'ultima_revision' => $ultimaRevision
                    ? Carbon::parse($ultimaRevision->fecha_restablecimiento)->format('d/m/Y')
                    : null,
                'componentes_criticos' => $registrosLinea->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado))->count(),
                'componentes_severos_moderados' => $registrosLinea->filter(fn ($registro) => $this->esEstadoDesgasteReporte($registro->estado))->count(),
                'componentes_revision' => $registrosLinea->filter(fn ($registro) => $this->esEstadoRevisionReporte($registro->estado))->count(),
                'reductores_count' => count($this->reductoresPorLinea[$linea->nombre] ?? []),
                'estado_general' => $this->determinarEstadoGeneral($linea->id, $fechaInicio, $fechaFin)
            ];
        }

        return $reporteGeneral;
    }

    public function show(Request $request)
    {
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $fechaInicio = Carbon::parse($request->get('fecha_inicio', Carbon::now()->subMonth()))->startOfDay();
        $fechaFin = Carbon::parse($request->get('fecha_fin', Carbon::now()))->endOfDay();

        // 👇 obtener lineaId desde GET
        $lineaId = $request->get('lineaId', $request->get('linea_id'));

        if ($lineaId) {
            return $this->mostrarReporteLinea($lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        }

        $cacheKey = "reporte_general_v4_{$tipoEquipo}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}";

        $reporte = Cache::remember($cacheKey, 1800, function() use ($tipoEquipo, $fechaInicio, $fechaFin) {
            return $this->generarReporteGeneralOptimizado($tipoEquipo, $fechaInicio, $fechaFin);
        });

        return view('reportes.show', compact('reporte', 'tipoEquipo', 'fechaInicio', 'fechaFin', 'lineaId'));
    }

    private function mostrarReporteLinea($lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
    {
        $linea = Linea::findOrFail($lineaId);
        $this->ensureCanAccessLinea($linea);

        $cacheKey = "reporte_linea_v4_{$tipoEquipo}_{$lineaId}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}";
        
        $reporte = Cache::remember($cacheKey, 3600, function() use ($linea, $tipoEquipo, $fechaInicio, $fechaFin) {
            return $this->getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo);
        });
        
        return view('reportes.show', compact('reporte', 'tipoEquipo', 'fechaInicio', 'fechaFin', 'lineaId'));
    }

  private function generarReporteGeneralOptimizado($tipoEquipo, $fechaInicio, $fechaFin)
{
    if ($tipoEquipo === 'pasteurizadoras') {
        return $this->generarReporteGeneralPasteurizadora($fechaInicio, $fechaFin);
    }

    $lineas = Linea::whereIn('nombre', $this->getLineasPorTipo($tipoEquipo))
        ->orderBy('nombre')
        ->get();

    return [
        'lineas' => $lineas
            ->map(fn ($linea) => $this->getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo))
            ->all(),
    ];

    $nombresLineas = $this->getLineasPorTipo($tipoEquipo);
    $lineas = Linea::whereIn('nombre', $nombresLineas)->get();
    $lineaIds = $lineas->pluck('id');

    /* =========================
       TODOS LOS ANALISIS CON DETALLES COMPLETOS
    ========================= */
    $todosAnalisis = DB::table('analisis_componentes')
        ->join('componentes', 'analisis_componentes.componente_id', '=', 'componentes.id')
        ->join('lineas', 'analisis_componentes.linea_id', '=', 'lineas.id')
        ->select(
            'analisis_componentes.id',
            'analisis_componentes.linea_id',
            'analisis_componentes.estado',
            'analisis_componentes.fecha_analisis',
            'analisis_componentes.reductor',
            'analisis_componentes.actividad',
            'analisis_componentes.numero_orden',
            'analisis_componentes.lado',
            'analisis_componentes.evidencia_fotos',
            'analisis_componentes.created_at',
            'componentes.nombre as componente_nombre',
            'componentes.codigo as componente_codigo',
            'lineas.nombre as linea_nombre'
        )
        ->whereIn('analisis_componentes.linea_id', $lineaIds)
        ->whereBetween('analisis_componentes.fecha_analisis', [$fechaInicio, $fechaFin])
        ->orderBy('analisis_componentes.fecha_analisis', 'desc')
        ->get()
        ->groupBy('linea_id');

    /* =========================
       ELONGACIONES
    ========================= */
    $elongaciones = Elongacion::whereIn('linea', $nombresLineas)
        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('linea');

    /* =========================
       COMPONENTES POR LÍNEA (del array definido)
    ========================= */
    $componentesPorLineaArray = [
        'L-04' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-05' => [
            'RV200' => 'Reductor RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-06' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-07' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-08' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-09' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-12' => [
            'RV200_SIN_FIN' => 'Reductor Sin Fin-Corona RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-13' => [
            'RV200' => 'Reductor RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
    ];

    // Reductores por línea
    $reductoresPorLineaArray = [
        'L-04' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca'],
        'L-05' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Principal', 'Reductor Loca'],
        'L-06' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22'],
        'L-07' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22'],
        'L-08' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca'],
        'L-09' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca'],
        'L-12' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Loca'],
        'L-13' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal']
    ];

    /* =========================
       REPORTE FINAL CON ANÁLISIS DETALLADOS
    ========================= */
    $reportesLineas = [];

    foreach ($lineas as $linea) {
        $analisisLinea = $todosAnalisis->get($linea->id, collect());
        
        // Obtener componentes específicos para esta línea
        $componentesLinea = collect();
        if (isset($componentesPorLineaArray[$linea->nombre])) {
            foreach ($componentesPorLineaArray[$linea->nombre] as $codigo => $nombre) {
                $componentesLinea->push((object)[
                    'id' => $codigo,
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                ]);
            }
        }

        // Obtener reductores específicos para esta línea
        $reductoresLinea = collect();
        if (isset($reductoresPorLineaArray[$linea->nombre])) {
            $reductoresLinea = collect($reductoresPorLineaArray[$linea->nombre]);
        }

        // Procesar análisis y agruparlos por reductor y componente
        $analisisAgrupados = [];
        foreach ($analisisLinea as $item) {
            $reductor = $item->reductor;
            $componenteCodigo = $item->componente_codigo;
            
            if (!isset($analisisAgrupados[$reductor][$componenteCodigo])) {
                $analisisAgrupados[$reductor][$componenteCodigo] = [];
            }
            
            // Procesar imágenes
            $imagenes = $item->evidencia_fotos;
            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true) ?? [];
            } elseif (is_array($imagenes)) {
                $imagenes = $imagenes;
            } else {
                $imagenes = [];
            }
            
            $analisisAgrupados[$reductor][$componenteCodigo][] = [
                'id' => $item->id,
                'fecha_analisis' => Carbon::parse($item->fecha_analisis)->format('Y-m-d'),
                'fecha_analisis_formateada' => Carbon::parse($item->fecha_analisis)->format('d/m/Y'),
                'estado' => $item->estado,
                'reductor' => $item->reductor,
                'actividad' => $item->actividad,
                'numero_orden' => $item->numero_orden,
                'lado' => $item->lado,
                'imagenes' => $imagenes,
                'componente' => [
                    'nombre' => $item->componente_nombre,
                    'codigo' => $item->componente_codigo
                ],
                'created_at' => $item->created_at,
                'edit_url' => route('analisis-lavadora.edit', ['analisislavadora' => $item->id]),
                'is_new' => $item->created_at && Carbon::parse($item->created_at)->gt(now()->subDays(3))
            ];
        }

        $elongacionesLinea = $elongaciones->get($linea->nombre, collect());

        $reportesLineas[] = [
            'linea' => $linea,
            'resumen' => [
                'total_analisis' => $analisisLinea->count(),
                'componentes_revisados' => $analisisLinea->pluck('componente_codigo')->unique()->count(),
                'componentes_criticos' => $analisisLinea->where('estado', 'Dañado - Requiere cambio')->count(),
                'componentes_severos_moderados' => $analisisLinea->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])->count(),
                'componentes_revision' => $analisisLinea->where('estado', 'Requiere revisión')->count(),
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo)
            ],
            'componentes_lista' => $componentesLinea,
            'reductores_lista' => $reductoresLinea,
            'analisis_agrupados' => $analisisAgrupados,
            'elongaciones' => $elongacionesLinea,
        ];
    }

    return ['lineas' => $reportesLineas];
}

    private function getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo)
    {
        if ($tipoEquipo === 'pasteurizadoras') {
            return $this->getReporteDetalladoPasteurizadora($linea, $fechaInicio, $fechaFin);
        }

        // 1. Obtener análisis del período (optimizado)
        $componentesLista = $this->getComponentesListaLavadora($linea);
        $reductoresLista = collect($this->reductoresPorLinea[$linea->nombre] ?? []);

        $analisis = AnalisisLavadora::where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->with(['componente:id,nombre,codigo'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get();

        $elongaciones = Elongacion::where('linea', $linea->nombre)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderByDesc('created_at')
            ->get();

        $analisisTendencia = AnalisisTendenciaMensualLavadora::where('linea_id', $linea->id)
            ->where(function($query) use ($fechaInicio, $fechaFin) {
                $query->where('anio', $fechaInicio->year)
                      ->orWhere('anio', $fechaFin->year);
            })
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $componentes = $this->procesarComponentesLavadora($analisis, $linea);
        $reductores = $this->procesarReductoresLavadora($analisis, $elongaciones);
        $analisisAgrupados = $this->agruparAnalisisLavadora($analisis);

        return [
            'tipo_equipo' => 'lavadoras',
            'linea' => $linea,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'resumen' => [
                'total_analisis' => $analisis->count(),
                'componentes_revisados' => $analisis->pluck('componente.codigo')->filter()->unique()->count(),
                'total_componentes' => $componentesLista->count(),
                'componentes_criticos' => $analisis->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado))->count(),
                'componentes_severos_moderados' => $analisis->filter(fn ($registro) => $this->esEstadoDesgasteReporte($registro->estado))->count(),
                'componentes_revision' => $analisis->filter(fn ($registro) => $this->esEstadoRevisionReporte($registro->estado))->count(),
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo),
                'estado_general' => $this->determinarEstadoGeneralDesdeRegistros($analisis),
            ],
            'analisis' => $analisis,
            'elongaciones' => $elongaciones,
            'analisis_tendencia' => $analisisTendencia,
            'componentes' => $componentes,
            'reductores' => $reductores,
            'componentes_lista' => $componentesLista,
            'reductores_lista' => $reductoresLista,
            'analisis_agrupados' => $analisisAgrupados,
            'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo),
        ];

        $analisis = AnalisisLavadora::where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->with('componente:id,nombre,codigo')
            ->orderBy('fecha_analisis', 'desc')
            ->get()
            ->map(function($a){
                return [
                    'id' => $a->id,
                    'fecha_analisis' => $a->fecha_analisis,
                    'estado' => $a->estado,
                    'reductor' => $a->reductor,
                    'actividad' => $a->actividad,
                    'componente' => $a->componente ? [
                        'nombre' => $a->componente->nombre,
                        'codigo' => $a->componente->codigo
                    ] : null
                ];
            });

        // 2. Obtener estadísticas en una sola consulta
        $estadisticas = DB::table('analisis_componentes')
            ->where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->selectRaw('
                COUNT(*) as total_analisis,
                COUNT(DISTINCT componente_id) as componentes_revisados,
                SUM(CASE WHEN estado = "Dañado - Requiere cambio" THEN 1 ELSE 0 END) as componentes_criticos,
                SUM(CASE WHEN estado IN ("Desgaste moderado", "Desgaste severo") THEN 1 ELSE 0 END) as componentes_severos_moderados,
                SUM(CASE WHEN estado = "Requiere revisión" THEN 1 ELSE 0 END) as componentes_revision
            ')
            ->first();

        // 3. Elongaciones (optimizado con índices)
        $elongaciones = Elongacion::where('linea', $linea->nombre)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderBy('created_at', 'desc')
            ->select(['created_at', 'hodometro', 'bombas_porcentaje', 'vapor_porcentaje'])
            ->get();

        // 4. Análisis de tendencia (optimizado)
        $analisisTendencia = AnalisisTendenciaMensualLavadora::where('linea_id', $linea->id)
            ->where(function($query) use ($fechaInicio, $fechaFin) {
                $query->where('anio', $fechaInicio->year)
                      ->orWhere('anio', $fechaFin->year);
            })
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->select(['anio', 'mes', 'total_danos_52_semanas', 'total_danos_12_semanas', 'total_danos_4_semanas'])
            ->get();

        // 5. Procesar componentes
        $componentes = $this->procesarComponentesLavadora($analisis, $linea);
        
        // 6. Procesar reductores
        $reductores = $this->procesarReductoresLavadora($analisis, $elongaciones);

        return [
            'linea' => $linea,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'resumen' => [
                'total_analisis' => $estadisticas->total_analisis ?? 0,
                'componentes_revisados' => $estadisticas->componentes_revisados ?? 0,
                'componentes_criticos' => $estadisticas->componentes_criticos ?? 0,
                'componentes_severos_moderados' => $estadisticas->componentes_severos_moderados ?? 0,
                'componentes_revision' => $estadisticas->componentes_revision ?? 0,
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo)
            ],
            'analisis' => $analisis,
            'elongaciones' => $elongaciones,
            'analisis_tendencia' => $analisisTendencia,
            'componentes' => $componentes,
            'reductores' => $reductores
        ];
    }

    private function generarReporteIndexPasteurizadora($fechaInicio, $fechaFin)
    {
        $lineas = Linea::whereIn('nombre', $this->pasteurizadoras)
            ->orderBy('nombre')
            ->get();
        $lineaIds = $lineas->pluck('id');

        $registros = AnalisisPasteurizadora::query()
            ->whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->get([
                'id',
                'linea_id',
                'modulo',
                'nivel',
                'componente',
                'lado',
                'fecha_analisis',
                'estado',
                'cantidad_componentes_revisados',
                'total_componentes',
                'resuelto_por_cambio',
                'created_at',
            ])
            ->groupBy('linea_id');

        $tendencias = AnalisisTendenciaMensualPasteurizadora::whereIn('linea_id', $lineaIds)
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->where('anio', $fechaInicio->year)
                    ->orWhere('anio', $fechaFin->year);
            })
            ->get(['linea_id', 'total_danos_4_semanas'])
            ->groupBy('linea_id');

        $ultimasRevisiones = AnalisisPasteurizadora::query()
            ->whereIn('linea_id', $lineaIds)
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get(['linea_id', 'fecha_analisis'])
            ->unique('linea_id')
            ->keyBy('linea_id');

        $reporteGeneral = [];

        foreach ($lineas as $linea) {
            $registrosLinea = $registros->get($linea->id, collect());
            $tendenciasLinea = $tendencias->get($linea->id, collect());
            $componentesDefinidos = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
            $avance = $this->calcularAvanceModulosPasteurizadora($linea, $registrosLinea);
            $ultimaRevision = $ultimasRevisiones->get($linea->id);

            $reporteGeneral[$linea->id] = [
                'total_analisis' => $registrosLinea->count(),
                'componentes_revisados' => $registrosLinea->pluck('componente')->filter()->unique()->count(),
                'total_componentes' => count($componentesDefinidos),
                'componentes_criticos' => $registrosLinea
                    ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
                    ->count(),
                'componentes_severos_moderados' => $registrosLinea
                    ->filter(fn ($registro) => AnalisisPasteurizadora::esEstadoDesgaste($registro->estado))
                    ->count(),
                'componentes_revision' => $registrosLinea
                    ->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)
                    ->count(),
                'acciones_pendientes' => $registrosLinea
                    ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
                    ->count(),
                'analisis_tendencia_count' => $tendenciasLinea->count(),
                'total_danos_4' => $tendenciasLinea->sum('total_danos_4_semanas'),
                'historicos' => $registrosLinea->count(),
                'ultima_revision' => $ultimaRevision
                    ? Carbon::parse($ultimaRevision->fecha_analisis)->format('d/m/Y')
                    : null,
                'reductores_count' => 0,
                'modulos_configurados' => $avance['total_modulos'],
                'modulos_con_analisis' => $avance['modulos_con_analisis'],
                'avance_historico_porcentaje' => $avance['porcentaje'],
                'niveles_count' => count(AnalisisPasteurizadora::NIVELES),
                'lados_count' => count(AnalisisPasteurizadora::LADOS),
                'estado_general' => $this->determinarEstadoGeneralPasteurizadora($registrosLinea),
            ];
        }

        return $reporteGeneral;
    }

    private function generarReporteGeneralPasteurizadora($fechaInicio, $fechaFin)
    {
        $lineas = Linea::whereIn('nombre', $this->pasteurizadoras)
            ->orderBy('nombre')
            ->get();

        return [
            'lineas' => $lineas
                ->map(fn ($linea) => $this->getReporteDetalladoPasteurizadora($linea, $fechaInicio, $fechaFin))
                ->all(),
        ];
    }

    private function getReporteDetalladoPasteurizadora($linea, $fechaInicio, $fechaFin)
    {
        $analisis = AnalisisPasteurizadora::query()
            ->with(['usuario:id,name', 'linea:id,nombre'])
            ->where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get();

        $analisisTendencia = AnalisisTendenciaMensualPasteurizadora::where('linea_id', $linea->id)
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->where('anio', $fechaInicio->year)
                    ->orWhere('anio', $fechaFin->year);
            })
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $componentes = $this->procesarComponentesPasteurizadora($linea, $analisis);
        $avance = $this->calcularAvanceModulosPasteurizadora($linea, $analisis);
        $componentesDefinidos = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $criticos = $analisis
            ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
            ->count();

        return [
            'tipo_equipo' => 'pasteurizadoras',
            'linea' => $linea,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'resumen' => [
                'total_analisis' => $analisis->count(),
                'componentes_revisados' => $analisis->pluck('componente')->filter()->unique()->count(),
                'total_componentes' => count($componentesDefinidos),
                'componentes_criticos' => $criticos,
                'componentes_severos_moderados' => $analisis
                    ->filter(fn ($registro) => AnalisisPasteurizadora::esEstadoDesgaste($registro->estado))
                    ->count(),
                'componentes_revision' => $analisis
                    ->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)
                    ->count(),
                'piezas_revisadas' => $analisis->sum(fn ($registro) => (int) ($registro->cantidad_componentes_revisados ?? 0)),
                'total_modulos' => $avance['total_modulos'],
                'modulos_con_analisis' => $avance['modulos_con_analisis'],
                'avance_historico_porcentaje' => $avance['porcentaje'],
                'componentes_definidos' => $this->getComponentesDefinidos('pasteurizadoras'),
                'estado_general' => $this->determinarEstadoGeneralPasteurizadora($analisis),
            ],
            'analisis' => $analisis,
            'analisis_tendencia' => $analisisTendencia,
            'componentes' => $componentes,
            'modulos' => $avance['modulos'],
        ];
    }

    private function procesarComponentesPasteurizadora($linea, $analisis)
    {
        $componentesLinea = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
        $resultado = collect();

        foreach ($componentesLinea as $codigo => $config) {
            $analisisComponente = $analisis
                ->where('componente', $codigo)
                ->sortByDesc('fecha_analisis')
                ->values();
            $ultimoAnalisis = $analisisComponente->first();
            $modulosAplicables = AnalisisPasteurizadora::esBrazoTorsion($codigo)
                ? AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                : $totalModulos;
            $cantidadPorSeleccion = (int) ($config['cantidad'] ?? 0);
            $totalConfigurado = $cantidadPorSeleccion
                * max(1, $modulosAplicables)
                * max(1, count(AnalisisPasteurizadora::NIVELES))
                * max(1, count(AnalisisPasteurizadora::LADOS));
            $revisadas = $analisisComponente->sum(fn ($registro) => (int) ($registro->cantidad_componentes_revisados ?? 0));

            $resultado->push([
                'codigo' => $codigo,
                'nombre' => $config['nombre'] ?? $codigo,
                'cantidad' => $cantidadPorSeleccion,
                'modulos_aplicables' => $modulosAplicables,
                'total_configurado' => $totalConfigurado,
                'cantidad_revisada' => $revisadas,
                'porcentaje' => $totalConfigurado > 0 ? min(100, round(($revisadas / $totalConfigurado) * 100, 1)) : 0,
                'total_analisis' => $analisisComponente->count(),
                'ultimo_analisis' => $ultimoAnalisis,
                'ultimo_estado' => $ultimoAnalisis?->estado,
            ]);
        }

        return $resultado;
    }

    private function calcularAvanceModulosPasteurizadora($linea, $registros)
    {
        $registros = collect($registros);
        $componentesLinea = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
        $modulos = collect();
        $modulosConAnalisis = 0;
        $totalComponentesModulo = 0;
        $componentesRevisadosModulo = 0;

        for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
            $componentesAplicables = collect($componentesLinea)
                ->reject(function ($config, $codigo) use ($linea, $modulo) {
                    return AnalisisPasteurizadora::esBrazoTorsion($codigo)
                        && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre);
                });
            $registrosModulo = $registros->where('modulo', $modulo)->values();
            $componentesConAnalisis = $registrosModulo->pluck('componente')->filter()->unique()->count();
            $totalComponentes = $componentesAplicables->count();
            $componentesRevisados = min($componentesConAnalisis, $totalComponentes);

            if ($registrosModulo->isNotEmpty()) {
                $modulosConAnalisis++;
            }

            $totalComponentesModulo += $totalComponentes;
            $componentesRevisadosModulo += $componentesRevisados;

            $modulos->push([
                'numero' => $modulo,
                'total_componentes' => $totalComponentes,
                'componentes_revisados' => $componentesRevisados,
                'porcentaje' => $totalComponentes > 0 ? round(($componentesRevisados / $totalComponentes) * 100, 1) : 0,
                'total_analisis' => $registrosModulo->count(),
                'criticos' => $registrosModulo
                    ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
                    ->count(),
                'ultima_revision' => optional($registrosModulo->sortByDesc('fecha_analisis')->first())->fecha_analisis,
                'niveles' => collect(AnalisisPasteurizadora::NIVELES)
                    ->mapWithKeys(fn ($nivel) => [$nivel => $registrosModulo->where('nivel', $nivel)->count()])
                    ->all(),
                'lados' => collect(AnalisisPasteurizadora::LADOS)
                    ->mapWithKeys(fn ($lado) => [$lado => $registrosModulo->where('lado', $lado)->count()])
                    ->all(),
            ]);
        }

        return [
            'modulos' => $modulos,
            'total_modulos' => $totalModulos,
            'modulos_con_analisis' => $modulosConAnalisis,
            'porcentaje' => $totalComponentesModulo > 0
                ? round(($componentesRevisadosModulo / $totalComponentesModulo) * 100, 1)
                : 0,
        ];
    }

    private function determinarEstadoGeneralPasteurizadora($registros)
    {
        $registros = collect($registros);

        $criticos = $registros
            ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
            ->count();
        $desgaste = $registros
            ->filter(fn ($registro) => AnalisisPasteurizadora::esEstadoDesgaste($registro->estado))
            ->count();
        $revision = $registros
            ->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)
            ->count();

        if ($criticos > 0) {
            return ['texto' => 'CRITICO', 'color' => 'red'];
        }

        if ($desgaste > 0) {
            return ['texto' => 'SEVERO / MODERADO', 'color' => 'orange'];
        }

        if ($revision > 0) {
            return ['texto' => 'REQUIERE REVISION', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    private function getComponentesListaLavadora($linea)
    {
        return collect($this->componentesPorLinea[$linea->nombre] ?? [])
            ->map(function ($nombre, $codigo) {
                return (object) [
                    'id' => $codigo,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                ];
            })
            ->values();
    }

    private function agruparAnalisisLavadora($analisis)
    {
        $agrupados = [];

        foreach (collect($analisis) as $item) {
            $reductor = $item->reductor ?: 'Sin reductor';
            $componenteCodigo = $item->componente?->codigo ?: 'SIN_COMPONENTE';

            if (!isset($agrupados[$reductor][$componenteCodigo])) {
                $agrupados[$reductor][$componenteCodigo] = [];
            }

            $imagenes = $item->evidencia_fotos;

            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true) ?? [];
            } elseif (!is_array($imagenes)) {
                $imagenes = [];
            }

            $agrupados[$reductor][$componenteCodigo][] = [
                'id' => $item->id,
                'fecha_analisis' => optional($item->fecha_analisis)->format('Y-m-d'),
                'fecha_analisis_formateada' => optional($item->fecha_analisis)->format('d/m/Y'),
                'estado' => $item->estado,
                'reductor' => $item->reductor,
                'actividad' => $item->actividad,
                'numero_orden' => $item->numero_orden,
                'lado' => $item->lado,
                'imagenes' => $imagenes,
                'componente' => [
                    'nombre' => $item->componente?->nombre,
                    'codigo' => $item->componente?->codigo,
                ],
                'created_at' => $item->created_at,
                'edit_url' => route('analisis-lavadora.edit', ['analisislavadora' => $item->id]),
                'is_new' => $item->created_at && $item->created_at->gt(now()->subDays(3)),
            ];
        }

        return $agrupados;
    }

    private function normalizarEstadoReporte($estado): string
    {
        $estado = strtolower((string) $estado);

        return strtr($estado, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);
    }

    private function esEstadoDanadoReporte($estado): bool
    {
        return str_contains($this->normalizarEstadoReporte($estado), 'requiere cambio');
    }

    private function esEstadoDesgasteReporte($estado): bool
    {
        return str_contains($this->normalizarEstadoReporte($estado), 'desgaste');
    }

    private function esEstadoRevisionReporte($estado): bool
    {
        $estado = $this->normalizarEstadoReporte($estado);

        return str_contains($estado, 'revision') || str_contains($estado, 'revisi');
    }

    private function determinarEstadoGeneralDesdeRegistros($registros)
    {
        $registros = collect($registros);

        if ($registros->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado ?? null))->isNotEmpty()) {
            return ['texto' => 'CRITICO', 'color' => 'red'];
        }

        if ($registros->filter(fn ($registro) => $this->esEstadoDesgasteReporte($registro->estado ?? null))->isNotEmpty()) {
            return ['texto' => 'SEVERO / MODERADO', 'color' => 'orange'];
        }

        if ($registros->filter(fn ($registro) => $this->esEstadoRevisionReporte($registro->estado ?? null))->isNotEmpty()) {
            return ['texto' => 'REQUIERE REVISION', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    private function procesarComponentesLavadora($analisis, $linea)
    {
        $componentesLinea = $this->componentesPorLinea[$linea->nombre] ?? [];
        $resultado = [];
        
        foreach ($componentesLinea as $codigo => $nombre) {
            // CORRECCIÓN: Verificar si $analisis es una colección y si el item tiene la estructura correcta
            $analisisComponente = collect($analisis)->filter(function($item) use ($codigo) {
                return $item->componente
                    && (
                        $item->componente->codigo === $codigo
                        || str_ends_with((string) $item->componente->codigo, '_' . $codigo)
                    );
            });
            
            $ultimoAnalisis = $analisisComponente->first();
            $totalAnalisis = $analisisComponente->count();
            $promedioElongacion = 0;
            
            $resultado[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'total_analisis' => $totalAnalisis,
                'promedio_elongacion' => $promedioElongacion,
                'ultimo_analisis' => $ultimoAnalisis,
                'ultimo_estado' => $ultimoAnalisis?->estado
            ];
        }
        
        return $resultado;
    }

    private function procesarReductoresLavadora($analisis, $elongaciones)
    {
        // CORRECCIÓN: Asegurar que $analisis sea una colección
        $analisis = collect($analisis);
        
        // CORRECCIÓN: Filtrar items que tengan reductor antes de agrupar
        $analisisConReductor = $analisis->filter(function($item) {
            return !empty($item->reductor);
        });
        
        $reductoresAgrupados = $analisisConReductor->groupBy('reductor');
        $resultado = [];
        
        foreach ($reductoresAgrupados as $nombre => $analisisReductor) {
            $ultimoAnalisis = $analisisReductor->first();
            $ultimaElongacion = $elongaciones->isNotEmpty() ? $elongaciones->first() : null;
            
            $resultado[] = [
                'nombre' => $nombre,
                'total_analisis' => $analisisReductor->count(),
                'ultima_fecha' => $ultimoAnalisis?->fecha_analisis,
                'ultima_elongacion' => $ultimaElongacion ? ($ultimaElongacion->bombas_porcentaje ?? 0) : 0
            ];
        }
        
        return $resultado;
    }

    private function getComponentesDefinidos($tipoEquipo)
    {
        if ($tipoEquipo === 'lavadoras') {
            $todos = [];
            foreach ($this->componentesPorLinea as $componentes) {
                $todos = array_merge($todos, array_values($componentes));
            }
            return $todos;
        }
        $todos = [];
        foreach ($this->pasteurizadoras as $lineaNombre) {
            foreach (AnalisisPasteurizadora::getComponentesPorLinea($lineaNombre) as $componente) {
                $todos[] = $componente['nombre'] ?? null;
            }
        }

        return collect($todos)->filter()->unique()->values()->all();
    }

    private function getLineasPorTipo($tipo)
    {
        return $this->normalizarTipoEquipo($tipo) === 'lavadoras' ? $this->lavadoras : $this->pasteurizadoras;
    }

    private function normalizarTipoEquipo(?string $tipo): string
    {
        return $tipo === 'pasteurizadoras' ? 'pasteurizadoras' : 'lavadoras';
    }

    private function ensureCanAccessTipoEquipo(string $tipoEquipo): void
    {
        if (
            $this->normalizarTipoEquipo($tipoEquipo) === 'pasteurizadoras'
            && !auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)
        ) {
            abort(403, 'No tienes permiso para acceder al modulo de Pasteurizadora.');
        }
    }

    private function ensureCanAccessLinea(Linea $linea): void
    {
        if (
            str_starts_with($linea->nombre, 'P-')
            && !auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)
        ) {
            abort(403, 'No tienes permiso para acceder al modulo de Pasteurizadora.');
        }
    }

    private function determinarEstadoGeneral($lineaId, $fechaInicio, $fechaFin)
    {
        // CORRECCIÓN: Usar el mismo DB::table que en el resto del controlador
        $registros = DB::table('analisis_componentes')
            ->where('linea_id', $lineaId)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->get(['estado']);

        return $this->determinarEstadoGeneralDesdeRegistros($registros);

        $consulta = DB::table('analisis_componentes')
            ->where('linea_id', $lineaId)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin]);

        $criticos = (clone $consulta)
            ->where('estado', 'Dañado - Requiere cambio')
            ->count();

        $severosModerados = (clone $consulta)
            ->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])
            ->count();

        $requiereRevision = (clone $consulta)
            ->where('estado', 'Requiere revisión')
            ->count();

        if ($criticos > 0) {
            return ['texto' => 'CRÍTICO', 'color' => 'red'];
        }

        if ($severosModerados > 0) {
            return ['texto' => 'SEVERO / MODERADO', 'color' => 'orange'];
        }

        if ($requiereRevision > 0) {
            return ['texto' => 'REQUIERE REVISIÓN', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    public function exportar(Request $request)
    {
        $formato = $request->get('export_format', 'pdf');
        $lineaId = $request->get('lineaId', $request->get('linea_id'));
        $tipo = $request->get('export_tipo', $lineaId ? 'linea' : 'completo');
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $fechaInicio = Carbon::parse($request->get('fecha_inicio', Carbon::now()->subMonth()))->startOfDay();
        $fechaFin = Carbon::parse($request->get('fecha_fin', Carbon::now()))->endOfDay();

        if ($formato === 'pdf') {
            return $this->exportarPDF($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        } else {
            return $this->exportarExcel($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        }
    }

    private function exportarPDF($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
{
    if ($tipo === 'linea' && $lineaId) {

        $linea = Linea::findOrFail($lineaId);
        $this->ensureCanAccessLinea($linea);

        $reporteLinea = $this->getReporteDetalladoLinea(
            $linea,
            $fechaInicio,
            $fechaFin,
            $tipoEquipo
        );
        $reporte = ['lineas' => [$reporteLinea]];

        $pdf = Pdf::loadView(
            'reportes.pdf.general-lavadoras',
            compact('reporte', 'fechaInicio', 'fechaFin', 'tipoEquipo')
        );

        return $pdf->download(
            "reporte_{$linea->nombre}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.pdf"
        );

    } else {

        $reporte = $this->generarReporteGeneralOptimizado(
            $tipoEquipo,
            $fechaInicio,
            $fechaFin
        );

        $pdf = Pdf::loadView(
            'reportes.pdf.general-lavadoras',
            compact('reporte', 'fechaInicio', 'fechaFin', 'tipoEquipo')
        );

        return $pdf->download(
            "reporte_general_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.pdf"
        );
    }
}

    private function exportarExcel($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
{
    if ($tipo === 'linea' && $lineaId) {

        $linea = Linea::findOrFail($lineaId);
        $this->ensureCanAccessLinea($linea);

        $reporte = $this->getReporteDetalladoLinea(
            $linea,
            $fechaInicio,
            $fechaFin,
            $tipoEquipo
        );

        return Excel::download(
            new ReporteLavadoraGeneralExcel($reporte, $fechaInicio, $fechaFin),
            "reporte_{$linea->nombre}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.xlsx"
        );

    } else {

        $reporte = $this->generarReporteGeneralOptimizado(
            $tipoEquipo,
            $fechaInicio,
            $fechaFin
        );

        return Excel::download(
            new ReporteLavadoraGeneralExcel($reporte, $fechaInicio, $fechaFin),
            "reporte_general_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.xlsx"
        );
    }
}
}
