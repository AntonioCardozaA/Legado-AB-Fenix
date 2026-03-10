<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\Componente;
use App\Models\AnalisisLavadora;
use App\Models\Paro;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteLavadoraGeneralExcel;
use Carbon\Carbon;
use App\Models\Elongacion;
use App\Models\AnalisisTendenciaMensualLavadora;
use App\Models\HistorialRestablecimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReporteController extends Controller
{
    // Líneas de lavadoras
    protected $lavadoras = ['L-04','L-05','L-06','L-07','L-08','L-09','L-12','L-13'];

    // Líneas pasteurizadoras
    protected $pasteurizadoras = ['P-01','P-02','P-03','P-04','P-05','P-06','P-07','P-08','P-09','P-10','P-11','P-12','P-13','P-14'];

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
        $tipoEquipo = $request->get('tipo','lavadoras');
        $fechaInicio = $request->get('fecha_inicio') 
            ? Carbon::parse($request->fecha_inicio) 
            : Carbon::now()->subMonth();
            
        $fechaFin = $request->get('fecha_fin') 
            ? Carbon::parse($request->fecha_fin) 
            : Carbon::now();

        $cacheKey = "reporte_index_{$tipoEquipo}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}";
        
        $reporteGeneral = Cache::remember($cacheKey, 1800, function() use ($tipoEquipo, $fechaInicio, $fechaFin) {
            return $this->generarReporteIndexOptimizado($tipoEquipo, $fechaInicio, $fechaFin);
        });

        $lineas = Linea::whereIn('nombre', $this->getLineasPorTipo($tipoEquipo))->get();

        return view('reportes.index', compact(
            'lineas', 
            'tipoEquipo', 
            'fechaInicio', 
            'fechaFin', 
            'reporteGeneral'
        ));
    }

    private function generarReporteIndexOptimizado($tipoEquipo, $fechaInicio, $fechaFin)
    {
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
                DB::raw('SUM(CASE WHEN estado IN ("Dañado - Requiere cambio", "Desgaste severo") THEN 1 ELSE 0 END) as componentes_criticos')
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

        // Construir reporte para cada línea - VERSIÓN CORREGIDA (ELIMINADAS LAS LÍNEAS SUELTAS)
        foreach ($lineas as $linea) {
            $estadisticas = $estadisticasAnalisis->get($linea->id);

            $elongacionesLinea = $elongaciones->get($linea->nombre, collect([]));
            $tendenciasLinea = $tendencias->get($linea->id, collect([]));
            $historicosLinea = $historicos->get($linea->id, collect([]));
            $ultimaRevision = $ultimasRevisiones->get($linea->id);

            $promedioBombas = $elongacionesLinea->avg('bombas_porcentaje') ?: 0;
            $promedioVapor = $elongacionesLinea->avg('vapor_porcentaje') ?: 0;

            $maxElongacion = max($promedioBombas, $promedioVapor);

            // ⚠️ IMPORTANTE: Todo debe estar DENTRO de este array
            $reporteGeneral[$linea->id] = [
                'total_analisis' => $estadisticas->total_analisis ?? 0,
                'componentes_revisados' => $estadisticas->componentes_revisados ?? 0,
                'total_componentes' => count($this->componentesPorLinea[$linea->nombre] ?? []),
                'acciones_pendientes' => $estadisticas->componentes_criticos ?? 0,
                'elongacion_max' => $maxElongacion,
                'promedio_bombas' => $promedioBombas,
                'promedio_vapor' => $promedioVapor,
                'analisis_tendencia_count' => $tendenciasLinea->count(),
                'total_danos_4' => $tendenciasLinea->sum('total_danos_4_semanas'),
                'historicos' => $historicosLinea->count(),
                'ultima_revision' => $ultimaRevision
                    ? Carbon::parse($ultimaRevision->fecha_restablecimiento)->format('d/m/Y')
                    : null,
                'componentes_criticos' => $estadisticas->componentes_criticos ?? 0,
                'reductores_count' => count($this->reductoresPorLinea[$linea->nombre] ?? []),
                'estado_general' => $this->determinarEstadoGeneral($linea->id, $fechaInicio, $fechaFin)
            ];
        }

        return $reporteGeneral;
    }

    public function show(Request $request)
    {
        $tipoEquipo = $request->get('tipo', 'lavadoras');
        $fechaInicio = Carbon::parse($request->get('fecha_inicio', Carbon::now()->subMonth()));
        $fechaFin = Carbon::parse($request->get('fecha_fin', Carbon::now()));

        // 👇 obtener lineaId desde GET
        $lineaId = $request->get('lineaId');

        if ($lineaId) {
            return $this->mostrarReporteLinea($lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        }

        $cacheKey = "reporte_general_{$tipoEquipo}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}";

        $reporte = Cache::remember($cacheKey, 1800, function() use ($tipoEquipo, $fechaInicio, $fechaFin) {
            return $this->generarReporteGeneralOptimizado($tipoEquipo, $fechaInicio, $fechaFin);
        });

        return view('reportes.show', compact('reporte', 'tipoEquipo', 'fechaInicio', 'fechaFin', 'lineaId'));
    }

    private function mostrarReporteLinea($lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
    {
        $cacheKey = "reporte_linea_{$lineaId}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}";
        
        $reporte = Cache::remember($cacheKey, 3600, function() use ($lineaId, $tipoEquipo, $fechaInicio, $fechaFin) {
            $linea = Linea::findOrFail($lineaId);
            return $this->getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo);
        });
        
        return view('reportes.show', compact('reporte', 'tipoEquipo', 'fechaInicio', 'fechaFin', 'lineaId'));
    }

  private function generarReporteGeneralOptimizado($tipoEquipo, $fechaInicio, $fechaFin)
{
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
                'componentes_criticos' => $analisisLinea->whereIn('estado', ['Dañado - Requiere cambio', 'Desgaste severo'])->count(),
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
        // 1. Obtener análisis del período (optimizado)
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
                SUM(CASE WHEN estado = "Dañado - Requiere cambio" THEN 1 ELSE 0 END) as componentes_criticos
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
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo)
            ],
            'analisis' => $analisis,
            'elongaciones' => $elongaciones,
            'analisis_tendencia' => $analisisTendencia,
            'componentes' => $componentes,
            'reductores' => $reductores
        ];
    }

    private function procesarComponentesLavadora($analisis, $linea)
    {
        $componentesLinea = $this->componentesPorLinea[$linea->nombre] ?? [];
        $resultado = [];
        
        foreach ($componentesLinea as $codigo => $nombre) {
            // CORRECCIÓN: Verificar si $analisis es una colección y si el item tiene la estructura correcta
            $analisisComponente = collect($analisis)->filter(function($item) use ($codigo) {
                return isset($item['componente']) && 
                       is_array($item['componente']) && 
                       isset($item['componente']['codigo']) && 
                       $item['componente']['codigo'] == $codigo;
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
                'ultimo_estado' => $ultimoAnalisis ? ($ultimoAnalisis['estado'] ?? null) : null
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
            return isset($item['reductor']) && !empty($item['reductor']);
        });
        
        $reductoresAgrupados = $analisisConReductor->groupBy('reductor');
        $resultado = [];
        
        foreach ($reductoresAgrupados as $nombre => $analisisReductor) {
            $ultimoAnalisis = $analisisReductor->first();
            $ultimaElongacion = $elongaciones->isNotEmpty() ? $elongaciones->first() : null;
            
            $resultado[] = [
                'nombre' => $nombre,
                'total_analisis' => $analisisReductor->count(),
                'ultima_fecha' => $ultimoAnalisis ? ($ultimoAnalisis['fecha_analisis'] ?? null) : null,
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
        return []; // Para pasteurizadoras, implementar después
    }

    private function getLineasPorTipo($tipo)
    {
        return $tipo === 'lavadoras' ? $this->lavadoras : $this->pasteurizadoras;
    }

    private function determinarEstadoGeneral($lineaId, $fechaInicio, $fechaFin)
    {
        // CORRECCIÓN: Usar el mismo DB::table que en el resto del controlador
        $criticos = DB::table('analisis_componentes')
            ->where('linea_id', $lineaId)
            ->whereIn('estado', ['Dañado - Requiere cambio', 'Desgaste severo'])
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->count();

        if ($criticos > 3) {
            return ['texto' => 'CRÍTICO', 'color' => 'red'];
        } elseif ($criticos > 0) {
            return ['texto' => 'ALERTA', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    public function exportar(Request $request)
    {
        $formato = $request->get('export_format', 'pdf');
        $tipo = $request->get('export_tipo', 'completo');
        $lineaId = $request->get('lineaId');
        $tipoEquipo = $request->get('tipo', 'lavadoras');
        $fechaInicio = Carbon::parse($request->get('fecha_inicio', Carbon::now()->subMonth()));
        $fechaFin = Carbon::parse($request->get('fecha_fin', Carbon::now()));

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

        $reporte = $this->getReporteDetalladoLinea(
            $linea,
            $fechaInicio,
            $fechaFin,
            $tipoEquipo
        );

        $pdf = Pdf::loadView(
            'reportes.pdf.linea-lavadora',
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