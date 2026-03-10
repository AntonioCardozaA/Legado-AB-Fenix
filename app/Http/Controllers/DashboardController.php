<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Componente;
use App\Models\Paro;
use App\Models\AnalisisLavadora;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\AnalisisTendenciaMensualLavadora;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
  public function index()
{
    // ===========================================
    // 1. ESTADÍSTICAS GENERALES (TARJETAS)
    // ===========================================
    
    // Total de análisis (de todos los tipos)
    $totalAnalisis = Analisis::count() + AnalisisLavadora::count();
    
    // Último análisis
    $ultimoAnalisisLavadora = AnalisisLavadora::with('linea')->latest('fecha_analisis')->first();
    $ultimoAnalisisGeneral = Analisis::with('linea')->latest('fecha_analisis')->first();
    $ultimoAnalisis = $ultimoAnalisisLavadora;
    if ($ultimoAnalisisGeneral && (!$ultimoAnalisisLavadora || 
        $ultimoAnalisisGeneral->fecha_analisis > $ultimoAnalisisLavadora->fecha_analisis)) {
        $ultimoAnalisis = $ultimoAnalisisGeneral;
    }
    
    // Fecha para filtro (último mes)
    $fechaInicio = Carbon::now()->subMonth();
    
    // Componentes de lavadora
    try {
        $totalComponentesRevisados = AnalisisLavadora::where('fecha_analisis', '>=', $fechaInicio)->count();
        $componentesBuenos = AnalisisLavadora::where('fecha_analisis', '>=', $fechaInicio)
            ->where(function($q) {
                $q->where('estado', 'like', '%BUENO%')
                  ->orWhere('estado', 'like', '%NORMAL%')
                  ->orWhere('estado', 'like', '%OK%');
            })
            ->count();
        $totalDanados = AnalisisLavadora::where('fecha_analisis', '>=', $fechaInicio)
            ->where(function($q) {
                $q->where('estado', 'like', '%DAÑADO%')
                  ->orWhere('estado', 'like', '%CRÍTICO%')
                  ->orWhere('estado', 'like', '%REEMPLAZADO%');
            })
            ->count();
    } catch (\Exception $e) {
        $totalComponentesRevisados = 0;
        $componentesBuenos = 0;
        $totalDanados = 0;
    }
    
    $porcentajeBuenos = $totalComponentesRevisados > 0 
        ? round(($componentesBuenos / $totalComponentesRevisados) * 100, 1)
        : 0;
    
    // Paros pendientes
    $hoy = Carbon::now();
    $proximos7Dias = Carbon::now()->addDays(7);
    $parosPendientes = PlanAccion::where(function($query) use ($hoy, $proximos7Dias) {
            $query->whereBetween('fecha_pcm1', [$hoy, $proximos7Dias])
                ->orWhereBetween('fecha_pcm2', [$hoy, $proximos7Dias])
                ->orWhereBetween('fecha_pcm3', [$hoy, $proximos7Dias])
                ->orWhereBetween('fecha_pcm4', [$hoy, $proximos7Dias]);
        })
        ->count();

    // ===========================================
    // 2. ANÁLISIS RECIENTES
    // ===========================================
    
    $analisisLavadoraRecientes = AnalisisLavadora::with(['linea', 'componente'])
        ->orderBy('fecha_analisis', 'desc')
        ->limit(5)
        ->get()
        ->map(function($item) {
            return (object)[
                'id' => $item->id,
                'fecha_analisis' => $item->fecha_analisis,
                'linea' => $item->linea,
                'numero_orden' => $item->numero_orden ?? 'N/A',
                'elongacion_promedio' => null,
                'estado' => $item->estado,
                'tipo' => 'lavadora',
                'ruta_show' => route('analisis-lavadora.show', $item->id)
            ];
        });
    
    $analisisGeneralesRecientes = Analisis::with('linea')
        ->orderBy('fecha_analisis', 'desc')
        ->limit(5)
        ->get()
        ->map(function($item) {
            return (object)[
                'id' => $item->id,
                'fecha_analisis' => $item->fecha_analisis,
                'linea' => $item->linea,
                'numero_orden' => $item->numero_orden,
                'elongacion_promedio' => $item->elongacion_promedio,
                'estado' => $this->determinarEstadoElongacion($item->elongacion_promedio),
                'tipo' => 'general',
                'ruta_show' => route('analisis.show', $item->id)
            ];
        });
    
    $analisisRecientes = $analisisLavadoraRecientes
        ->concat($analisisGeneralesRecientes)
        ->sortByDesc('fecha_analisis')
        ->take(10)
        ->values();

    // ===========================================
    // 3. GRÁFICO DE TENDENCIA POR LÍNEA
    // ===========================================
    
    // Obtener líneas activas
    $lineas = Linea::where('activo', true)->orderBy('nombre')->get();
    
    // Obtener últimas 10 fechas con análisis de elongación
    $fechasRecientes = Elongacion::select(DB::raw('DATE(created_at) as fecha'))
        ->distinct()
        ->orderBy('fecha', 'desc')
        ->limit(10)
        ->pluck('fecha')
        ->sort()
        ->values();
    
    $tendenciaLabels = $fechasRecientes->map(function($fecha) {
        return Carbon::parse($fecha)->format('d/m');
    })->toArray();
    
    $tendenciaDatasets = [];
    $colores = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];
    
    foreach ($lineas as $index => $linea) {
        $datos = [];
        foreach ($fechasRecientes as $fecha) {
            $elongacion = Elongacion::where('linea', $linea->nombre)
                ->whereDate('created_at', $fecha)
                ->latest()
                ->first();
            
            $valor = $elongacion 
                ? round(($elongacion->bombas_promedio + $elongacion->vapor_promedio) / 2, 2)
                : null;
            
            $datos[] = $valor;
        }
        
        if (count(array_filter($datos)) > 0) {
            $tendenciaDatasets[] = [
                'label' => $linea->nombre,
                'data' => $datos,
                'borderColor' => $colores[$index % count($colores)],
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'tension' => 0.4,
                'spanGaps' => true,
                'pointRadius' => 3,
                'pointHoverRadius' => 5
            ];
        }
    }

    // ===========================================
    // 4. GRÁFICO DE ESTADO DE COMPONENTES
    // ===========================================
    
    $fechaInicioMes = Carbon::now()->startOfMonth();
    $fechaFinMes = Carbon::now()->endOfMonth();
    
    $componentesData = AnalisisLavadora::with('componente')
        ->whereBetween('fecha_analisis', [$fechaInicioMes, $fechaFinMes])
        ->select('componente_id', DB::raw('count(*) as total'))
        ->groupBy('componente_id')
        ->orderBy('total', 'desc')
        ->limit(5)
        ->get();
    
    $estadoComponentesLabels = [];
    $estadoComponentesData = [];
    
    foreach ($componentesData as $data) {
        if ($data->componente) {
            $estadoComponentesLabels[] = $data->componente->nombre ?? 'Componente';
            $estadoComponentesData[] = $data->total;
        }
    }

    // ===========================================
    // 5. GRÁFICO DE TENDENCIA DE DAÑOS
    // ===========================================
    
    $datosDanosTendencia = AnalisisTendenciaMensualLavadora::with('linea')
        ->orderBy('anio', 'desc')
        ->orderBy('mes', 'desc')
        ->limit(12)
        ->get()
        ->map(function($item) {
            return [
                'periodo' => $item->periodo,
                'total_danos' => $item->total_danos_12semanas ?? 0
            ];
        })
        ->reverse()
        ->values();


    // ===========================================
    // 7. DEBUG - Registrar qué se está enviando
    // ===========================================
    
    \Log::info('Dashboard - Variables enviadas:', [
        'tendenciaLabels' => $tendenciaLabels,
        'tendenciaDatasets_count' => count($tendenciaDatasets),
        'tendenciaDatasets' => $tendenciaDatasets,
        'estadoComponentesLabels' => $estadoComponentesLabels,
        'estadoComponentesData' => $estadoComponentesData,
        'analisisRecientes_count' => $analisisRecientes->count()
    ]);

    return view('dashboard', compact(
        'totalAnalisis',
        'ultimoAnalisis',
        'porcentajeBuenos',
        'totalDanados',
        'parosPendientes',
        'analisisRecientes',
        'tendenciaLabels',
        'tendenciaDatasets',
        'estadoComponentesLabels',
        'estadoComponentesData',
        'lineas' // Asegúrate de pasar las líneas
    ));
}
    
    /**
     * Obtener estadísticas de lavadoras (componentes revisados)
     */
    private function getEstadisticasLavadoras()
    {
        $estadisticas = [];
        
        try {
            $lineasLavadoras = Linea::whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
                ->get();
            
            foreach ($lineasLavadoras as $linea) {
                // Total de componentes en la línea
                $totalComponentes = Componente::where('linea', $linea->nombre)
                    ->where('activo', true)
                    ->count();
                
                // Componentes revisados en el último mes
                $revisados = AnalisisLavadora::join('analisis', 'analisis_componentes.analisis_id', '=', 'analisis.id')
                    ->where('analisis.linea_id', $linea->id)
                    ->where('analisis.fecha_analisis', '>=', Carbon::now()->subMonth())
                    ->distinct('componente_id')
                    ->count('componente_id');
                
                $porcentaje = $totalComponentes > 0 
                    ? round(($revisados / $totalComponentes) * 100, 1) 
                    : 0;
                
                $estadisticas[$linea->nombre] = [
                    'total' => $totalComponentes,
                    'revisados' => $revisados,
                    'porcentaje' => $porcentaje,
                    'color' => $this->getColorPorcentaje($porcentaje)
                ];
            }
        } catch (\Exception $e) {
            // Si hay error, devolver array vacío
        }
        
        return $estadisticas;
    }
    
    /**
     * Obtener color según porcentaje
     */
    private function getColorPorcentaje($porcentaje)
    {
        if ($porcentaje >= 80) return 'success';
        if ($porcentaje >= 50) return 'info';
        if ($porcentaje >= 20) return 'warning';
        return 'danger';
    }
    
    /**
     * Generar color basado en ID
     */
    private function generarColor($id)
    {
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
            '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16',
            '#f97316', '#6366f1', '#14b8a6', '#f43f5e',
            '#0ea5e9', '#22c55e'
        ];
        
        return $colors[$id % count($colors)];
    }
    
    /**
     * Dashboard específico para lavadora
     */
    public function lavadora()
    {
        // Redirigir al índice con filtro de lavadora o mostrar vista específica
        $lineasLavadoras = Linea::whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->pluck('id')
            ->toArray();
        
        // Estadísticas específicas para lavadoras
        $totalAnalisisLavadora = Analisis::whereIn('linea_id', $lineasLavadoras)->count();
        
        $analisisRecientesLavadora = Analisis::with('linea')
            ->whereIn('linea_id', $lineasLavadoras)
            ->orderBy('fecha_analisis', 'desc')
            ->limit(10)
            ->get();
        
        return view('lavadora.dashboard-lavadora', compact(
            'totalAnalisisLavadora',
            'analisisRecientesLavadora'
        ));
    }
    
    /**
     * API endpoint para actualizar gráficos vía AJAX
     */
    public function getTendenciaDanos(Request $request)
    {
        $periodo = $request->get('periodo', '12semanas');
        
        $semanasMap = [
            '4semanas' => 4,
            '12semanas' => 12,
            '52semanas' => 52
        ];
        
        $semanas = $semanasMap[$periodo] ?? 12;
        
        // Obtener datos de tendencia
        $datos = AnalisisTendenciaMensualLavadora::with('linea')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->limit($semanas)
            ->get()
            ->map(function($item) use ($periodo) {
                $campo = 'total_danos_' . str_replace('semanas', '', $periodo);
                return [
                    'periodo' => $item->periodo,
                    'valor' => $item->$campo ?? 0
                ];
            })
            ->reverse()
            ->values();
        
        return response()->json([
            'labels' => $datos->pluck('periodo'),
            'datasets' => [[
                'label' => 'Daños',
                'data' => $datos->pluck('valor'),
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'tension' => 0.4,
                'fill' => true
            ]]
        ]);
    }
}