<?php
// app/Http/Controllers/AnalisisTendenciaMensualLavadoraController.php

namespace App\Http\Controllers;

use App\Models\AnalisisTendenciaMensualLavadora;
use App\Models\Linea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalisisTendenciaMensualLavadoraController extends Controller
{
    /**
     * Mostrar vista principal con la tabla de tendencias mes a mes
     */
    public function index(Request $request)
    {
        $lineas = Linea::whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->orderBy('nombre')
            ->get();

        $lineaSeleccionada = $request->get('linea_id', $lineas->first()?->id);
        
        // Obtener todos los meses con datos para esta línea
        $analisis = collect();
        $meses = [];
        
        if ($lineaSeleccionada) {
            $analisis = AnalisisTendenciaMensualLavadora::where('linea_id', $lineaSeleccionada)
                ->orderBy('anio', 'desc')
                ->orderBy('mes', 'desc')
                ->get();
            
            // Generar array de meses para la tabla
            foreach ($analisis as $item) {
                $meses[$item->anio][$item->mes] = $item;
            }
        }

        return view('analisis-tendencia-mensual-lavadora.index', compact(
            'lineas', 
            'lineaSeleccionada',
            'analisis',
            'meses'
        ));
    }

    /**
     * Mostrar formulario para crear nuevo análisis mensual
     */
    public function create(Request $request)
    {
        $lineas = Linea::whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->orderBy('nombre')
            ->get();

        $lineaSeleccionada = $request->get('linea_id');
        
        // Mes actual por defecto
        $anioActual = now()->year;
        $mesActual = now()->month;
        
        // Verificar si ya existe registro para este mes
        $existeRegistro = false;
        if ($lineaSeleccionada) {
            $existeRegistro = AnalisisTendenciaMensualLavadora::where('linea_id', $lineaSeleccionada)
                ->where('anio', $anioActual)
                ->where('mes', $mesActual)
                ->exists();
        }

        return view('analisis-tendencia-mensual-lavadora.create', compact(
            'lineas', 
            'lineaSeleccionada',
            'anioActual',
            'mesActual',
            'existeRegistro'
        ));
    }

    /**
     * Guardar nuevo análisis mensual
     */
    public function store(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'anio' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'total_danos_52_semanas' => 'required|numeric|min:0', // Cambiado a numeric
            'total_danos_12_semanas' => 'required|numeric|min:0', // Cambiado a numeric
            'total_danos_4_semanas' => 'required|numeric|min:0',  // Cambiado a numeric
            'observaciones' => 'nullable|string'
        ]);

        // Verificar que no exista un registro para el mismo mes
        $existe = AnalisisTendenciaMensualLavadora::where('linea_id', $request->linea_id)
            ->where('anio', $request->anio)
            ->where('mes', $request->mes)
            ->exists();

        if ($existe) {
            return back()
                ->withInput()
                ->with('error', 'Ya existe un análisis para este mes y línea');
        }

        DB::beginTransaction();
        try {
            // Calcular fechas de corte
            $fechaReferencia = Carbon::create($request->anio, $request->mes, 1)->endOfMonth();
            
            $tendencia = AnalisisTendenciaMensualLavadora::create([
                'linea_id' => $request->linea_id,
                'anio' => $request->anio,
                'mes' => $request->mes,
                'total_danos_52_semanas' => $request->total_danos_52_semanas,
                'total_danos_12_semanas' => $request->total_danos_12_semanas,
                'total_danos_4_semanas' => $request->total_danos_4_semanas,
                'fecha_corte_52' => $fechaReferencia->copy()->subWeeks(52),
                'fecha_corte_12' => $fechaReferencia->copy()->subWeeks(12),
                'fecha_corte_4' => $fechaReferencia->copy()->subWeeks(4),
                'observaciones' => $request->observaciones
            ]);

            DB::commit();

            return redirect()
                ->route('analisis-tendencia-mensual-lavadora.index', ['linea_id' => $request->linea_id])
                ->with('success', 'Análisis mensual guardado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle de un análisis y su comparación con meses anteriores
     */
    public function show(AnalisisTendenciaMensualLavadora $analisis)
    {
        $analisis->load('linea');
        
        // Obtener últimos 12 meses para la gráfica
        $historial = AnalisisTendenciaMensualLavadora::where('linea_id', $analisis->linea_id)
            ->where(function($query) use ($analisis) {
                $query->where('anio', '<', $analisis->anio)
                    ->orWhere(function($q) use ($analisis) {
                        $q->where('anio', $analisis->anio)
                          ->where('mes', '<=', $analisis->mes);
                    });
            })
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get()
            ->reverse(); // Para orden cronológico

        return view('analisis-tendencia-mensual-lavadora.show', compact('analisis', 'historial'));
    }

    /**
     * API para obtener datos de tendencia (para gráficas)
     */
    public function getTendenciaApi(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id'
        ]);

        $datos = AnalisisTendenciaMensualLavadora::where('linea_id', $request->linea_id)
            ->orderBy('anio')
            ->orderBy('mes')
            ->get()
            ->map(function($item) {
                $variacion52 = $item->variacion_52_semanas;
                $variacion12 = $item->variacion_12_semanas;
                $variacion4 = $item->variacion_4_semanas;
                
                return [
                    'periodo' => $item->periodo,
                    'anio' => $item->anio,
                    'mes' => $item->mes,
                    'semanas_52' => $item->total_danos_52_semanas,
                    'semanas_12' => $item->total_danos_12_semanas,
                    'semanas_4' => $item->total_danos_4_semanas,
                    'variacion_52' => $variacion52 ? [
                        'valor' => $variacion52['porcentaje'],
                        'tendencia' => $variacion52['tendencia']
                    ] : null,
                    'variacion_12' => $variacion12 ? [
                        'valor' => $variacion12['porcentaje'],
                        'tendencia' => $variacion12['tendencia']
                    ] : null,
                    'variacion_4' => $variacion4 ? [
                        'valor' => $variacion4['porcentaje'],
                        'tendencia' => $variacion4['tendencia']
                    ] : null
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $datos
        ]);
    }
}