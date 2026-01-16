// app/Http/Controllers/ApiController.php
<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Linea;
use App\Models\AnalisisComponente;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function dashboard()
    {
        // Datos para gráficos del dashboard
        $lineas = Linea::all();
        
        // Datos de elongación por línea (últimos 30 días)
        $datosElongacion = [];
        foreach ($lineas as $linea) {
            $ultimoAnalisis = Analisis::where('linea_id', $linea->id)
                ->where('fecha_analisis', '>=', Carbon::now()->subDays(30))
                ->orderBy('fecha_analisis', 'desc')
                ->first();
            
            $datosElongacion[] = [
                'linea' => $linea->nombre,
                'elongacion' => $ultimoAnalisis ? $ultimoAnalisis->elongacion_promedio : 0,
                'fecha' => $ultimoAnalisis ? $ultimoAnalisis->fecha_analisis->format('d/m/Y') : 'N/A',
            ];
        }
        
        // Datos de daños por componente
        $danosComponentes = AnalisisComponente::whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])
            ->whereHas('analisis', function($query) {
                $query->where('fecha_analisis', '>=', Carbon::now()->subDays(30));
            })
            ->with('componente')
            ->selectRaw('componente_id, count(*) as total')
            ->groupBy('componente_id')
            ->get()
            ->map(function($item) {
                return [
                    'componente' => $item->componente->nombre,
                    'total' => $item->total,
                ];
            });
        
        return response()->json([
            'elongacion' => $datosElongacion,
            'danos_componentes' => $danosComponentes,
        ]);
    }
    
    public function tendenciaLinea(Linea $linea)
    {
        $analisis = Analisis::where('linea_id', $linea->id)
            ->where('fecha_analisis', '>=', Carbon::now()->subMonths(6))
            ->orderBy('fecha_analisis')
            ->get();
        
        $labels = $analisis->pluck('fecha_analisis')->map(function($fecha) {
            return $fecha->format('d/m/Y');
        });
        
        $datos = $analisis->pluck('elongacion_promedio');
        
        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Elongación (mm)',
                    'data' => $datos,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ]
            ]
        ]);
    }
    
    public function danosTendencia(Request $request)
    {
        $periodo = $request->get('periodo', '4semanas');
        
        switch ($periodo) {
            case '12semanas':
                $semanas = 12;
                break;
            case '52semanas':
                $semanas = 52;
                break;
            default:
                $semanas = 4;
        }
        
        $labels = [];
        $datos = [];
        
        for ($i = $semanas; $i >= 0; $i--) {
            $fechaInicio = Carbon::now()->subWeeks($i)->startOfWeek();
            $fechaFin = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $labels[] = "Sem " . $fechaInicio->format('W');
            
            $danosSemana = AnalisisComponente::whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])
                ->whereHas('analisis', function($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin]);
                })
                ->count();
            
            $datos[] = $danosSemana;
        }
        
        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Daños',
                    'data' => $datos,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ]
            ]
        ]);
    }
}