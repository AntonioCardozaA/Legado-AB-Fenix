<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Componente;
use App\Models\Paro;
use App\Models\AnalisisComponente;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Estadísticas generales
        $totalAnalisis = Analisis::count();
        $ultimoAnalisis = Analisis::with('linea')->latest()->first();
        
        // Fecha para filtro (último mes)
        $fechaInicio = Carbon::now()->subMonth();
        
        // PRIMERO: Verifica si el modelo y relación existen
        try {
            // Intenta usar join en lugar de whereHas para evitar errores
            $totalComponentesRevisados = AnalisisComponente::join('analisis', 'analisis_componentes.analisis_id', '=', 'analisis.id')
                ->where('analisis.fecha_analisis', '>=', $fechaInicio)
                ->count();
                
            $componentesBuenos = AnalisisComponente::join('analisis', 'analisis_componentes.analisis_id', '=', 'analisis.id')
                ->where('analisis.fecha_analisis', '>=', $fechaInicio)
                ->where('analisis_componentes.estado', 'BUENO')
                ->count();
                
            $totalDanados = AnalisisComponente::join('analisis', 'analisis_componentes.analisis_id', '=', 'analisis.id')
                ->where('analisis.fecha_analisis', '>=', $fechaInicio)
                ->whereIn('analisis_componentes.estado', ['DAÑADO', 'REEMPLAZADO'])
                ->count();
                
        } catch (\Exception $e) {
            // Si hay error, usa valores por defecto
            $totalComponentesRevisados = 0;
            $componentesBuenos = 0;
            $totalDanados = 0;
        }
        
        // Calcular porcentaje
        $porcentajeBuenos = $totalComponentesRevisados > 0 
            ? round(($componentesBuenos / $totalComponentesRevisados) * 100, 2)
            : 0;
        
        // Paros pendientes
        $parosPendientes = Paro::where('fecha_inicio', '>', Carbon::now())
            ->orWhereHas('planesAccion', function($query) {
                $query->where('estado', 'PENDIENTE');
            })->count();
        
        // Análisis recientes
        $analisisRecientes = Analisis::with('linea')
            ->orderBy('fecha_analisis', 'desc')
            ->limit(10)
            ->get();
        
        // Datos para gráficos - Simplificado para evitar errores
        $tendenciaLabels = [];
        $tendenciaDatasets = [];
        
        // Solo generar gráficos si hay datos
        $lineas = \App\Models\Linea::all();
        if ($lineas->count() > 0) {
            for ($i = 7; $i >= 0; $i--) {
                $fecha = Carbon::now()->subWeeks($i);
                $tendenciaLabels[] = $fecha->format('W/Y');
            }
            
            foreach ($lineas as $linea) {
                $datosLinea = [];
                for ($i = 7; $i >= 0; $i--) {
                    $fecha = Carbon::now()->subWeeks($i);
                    
                    $analisisSemana = Analisis::where('linea_id', $linea->id)
                        ->whereBetween('fecha_analisis', [
                            $fecha->startOfWeek(),
                            $fecha->endOfWeek()
                        ])
                        ->latest()
                        ->first();
                    
                    $datosLinea[] = $analisisSemana ? $analisisSemana->elongacion_promedio : 0;
                }
                
                $tendenciaDatasets[] = [
                    'label' => $linea->nombre,
                    'data' => $datosLinea,
                    'borderColor' => $this->generarColor($linea->id),
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'tension' => 0.4
                ];
            }
        }
        
        return view('dashboard', compact(
            'totalAnalisis',
            'ultimoAnalisis',
            'porcentajeBuenos',
            'totalDanados',
            'parosPendientes',
            'analisisRecientes',
            'tendenciaLabels',
            'tendenciaDatasets'
        ));
    }
    
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
}