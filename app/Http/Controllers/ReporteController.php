<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Linea;
use App\Models\Componente;
use App\Models\AnalisisComponente;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function index()
    {
        $lineas = Linea::withCount('analisis')->get();
        return view('reportes.index', compact('lineas'));
    }
    
    public function elongacion()
    {
        $lineas = Linea::all();
        $datos = [];
        
        foreach ($lineas as $linea) {
            $ultimoAnalisis = Analisis::where('linea_id', $linea->id)
                ->latest()
                ->first();
            
            if ($ultimoAnalisis) {
                $datos[] = [
                    'linea' => $linea->nombre,
                    'elongacion' => $ultimoAnalisis->elongacion_promedio,
                    'fecha' => $ultimoAnalisis->fecha_analisis,
                    'horometro' => $ultimoAnalisis->horometro,
                    'estado' => $this->determinarEstadoElongacion($ultimoAnalisis->elongacion_promedio),
                ];
            }
        }
        
        return view('reportes.elongacion', compact('datos'));
    }
    
    public function componentes()
    {
        $componentes = Componente::all();
        $reporte = [];
        
        foreach ($componentes as $componente) {
            $totalRevisado = AnalisisComponente::where('componente_id', $componente->id)
                ->whereHas('analisis', function($query) {
                    $query->where('fecha_analisis', '>=', Carbon::now()->subMonth());
                })
                ->count();
            
            $estados = AnalisisComponente::where('componente_id', $componente->id)
                ->whereHas('analisis', function($query) {
                    $query->where('fecha_analisis', '>=', Carbon::now()->subMonth());
                })
                ->selectRaw('estado, count(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado')
                ->toArray();
            
            $reporte[] = [
                'componente' => $componente->nombre,
                'total' => $componente->cantidad_total,
                'revisado' => $totalRevisado,
                'porcentaje_revisado' => $componente->cantidad_total > 0 
                    ? round(($totalRevisado / $componente->cantidad_total) * 100, 2)
                    : 0,
                'estados' => $estados,
            ];
        }
        
        return view('reportes.componentes', compact('reporte'));
    }
    
    public function paros()
    {
        $paros = \App\Models\Paro::with(['linea', 'planesAccion'])
            ->where('fecha_inicio', '>=', Carbon::now()->subYear())
            ->orderBy('fecha_inicio', 'desc')
            ->get();
        
        $estadisticas = [
            'total' => $paros->count(),
            'programados' => $paros->where('tipo', 'Programado')->count(),
            'emergencia' => $paros->where('tipo', 'Emergencia')->count(),
            'completados' => $paros->whereHas('planesAccion', function($query) {
                $query->where('estado', 'COMPLETADA');
            })->count(),
        ];
        
        return view('reportes.paros', compact('paros', 'estadisticas'));
    }
    
    private function determinarEstadoElongacion($elongacion)
    {
        if ($elongacion > 178.19) {
            return ['text' => 'CRÍTICO', 'color' => 'text-red-600', 'bg' => 'bg-red-100'];
        } elseif ($elongacion > 176) {
            return ['text' => 'ATENCIÓN', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-100'];
        } else {
            return ['text' => 'NORMAL', 'color' => 'text-green-600', 'bg' => 'bg-green-100'];
        }
    }
}