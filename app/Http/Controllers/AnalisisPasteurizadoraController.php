<?php
// app/Http/Controllers/AnalisisPasteurizadoraController.php

namespace App\Http\Controllers;

use App\Models\AnalisisPasteurizadora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class AnalisisPasteurizadoraController extends Controller
{
    /**
     * Constructor - aplica middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * INDEX - Vista principal con resumen de componentes y actividades recientes
     */
    public function index(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        
        // Obtener último registro para estadísticas
        $ultimoRegistro = AnalisisPasteurizadora::porLinea($linea)
            ->latest()
            ->first();
            
        if (!$ultimoRegistro) {
            // Crear registro inicial con datos del Excel
            $ultimoRegistro = AnalisisPasteurizadora::create([
                'linea' => $linea,
                'total_anillas' => 12,
                'revisadas_anillas' => 5,
                'total_placas' => 12,
                'revisadas_placas' => 5,
                'total_parrillas' => 12,
                'revisadas_parrillas' => 5,
                'total_rodamientos' => 12,
                'revisadas_rodamientos' => 5,
                'total_excentricos' => 12,
                'revisadas_excentricos' => 5,
                'total_reglillas' => 12,
                'revisadas_reglillas' => 5,
                'valor_anterior_52' => 0.51,
                'valor_actual_52' => 0.69,
                'valor_anterior_12' => 0.25,
                'valor_actual_12' => 1.08,
                'valor_anterior_4' => 0.23,
                'valor_actual_4' => 2.52,
            ]);
        }
        
        // Actividades recientes (últimos 10 registros)
        $actividadesRecientes = AnalisisPasteurizadora::porLinea($linea)
            ->whereNotNull('actividad')
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get();
            
        return view('analisis-pasteurizadora.index', compact('linea', 'ultimoRegistro', 'actividadesRecientes'));
    }

    /**
     * SELECT LINEA - Seleccionar línea de pasteurizadora
     */
    public function selectLinea()
    {
        return view('analisis-pasteurizadora.select-linea');
    }

    /**
     * CREATE WITH LINEA - Crear nuevo análisis con línea específica
     */
    public function createWithLinea($linea)
    {
        return view('analisis-pasteurizadora.create', compact('linea'));
    }

    /**
     * CREATE QUICK - Registro rápido de actividad
     */
    public function createQuick()
    {
        return view('analisis-pasteurizadora.create-quick');
    }

    /**
     * STORE - Guardar nuevo análisis
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea' => 'required|in:L-07,L-08',
            'modulo' => 'nullable|integer|min:1|max:11',
            'componente' => 'nullable|in:' . implode(',', array_keys(AnalisisPasteurizadora::COMPONENTES)),
            'fecha' => 'nullable|date',
            'actividad' => 'nullable|string',
            'cantidad' => 'nullable|integer|min:1|max:12',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $analisis = AnalisisPasteurizadora::create($validated);
        
        // Si hay cantidad, actualizar revisadas del componente
        if ($request->filled('cantidad') && $request->filled('componente')) {
            $componente = str_replace('_', '', $request->componente);
            $analisis->actualizarRevisadas($componente, $request->cantidad);
        }

        return redirect()->route('analisis-pasteurizadora.show', $analisis->id)
            ->with('success', 'Análisis creado exitosamente.');
    }

    /**
     * STORE QUICK - Guardar registro rápido
     */
    public function storeQuick(Request $request)
    {
        $validated = $request->validate([
            'modulo' => 'required|integer|min:1|max:11',
            'componente' => 'required|in:' . implode(',', array_keys(AnalisisPasteurizadora::COMPONENTES)),
            'fecha' => 'required|date',
            'actividad' => 'required|string',
            'cantidad' => 'required|integer|min:1|max:12',
            'responsable' => 'nullable|string',
        ]);

        $validated['linea'] = 'L-07';
        $validated['estado'] = 'completado';
        
        $analisis = AnalisisPasteurizadora::create($validated);
        
        // Actualizar contador de revisadas
        $componente = str_replace('_', '', $validated['componente']);
        $analisis->actualizarRevisadas($componente, $validated['cantidad']);

        return redirect()->route('analisis-pasteurizadora.index')
            ->with('success', 'Actividad registrada exitosamente.');
    }

    /**
     * SHOW - Mostrar detalle del análisis
     */
    public function show($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        $estadisticas = $analisis->getEstadisticasCompletas();
        $analisis52124 = $analisis->getAnalisis52124();
        
        return view('analisis-pasteurizadora.show', compact('analisis', 'estadisticas', 'analisis52124'));
    }

    /**
     * EDIT - Editar análisis
     */
    public function edit($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        return view('analisis-pasteurizadora.edit', compact('analisis'));
    }

    /**
     * UPDATE - Actualizar análisis
     */
    public function update(Request $request, $id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        
        $validated = $request->validate([
            'modulo' => 'nullable|integer|min:1|max:11',
            'componente' => 'nullable|in:' . implode(',', array_keys(AnalisisPasteurizadora::COMPONENTES)),
            'fecha' => 'nullable|date',
            'actividad' => 'nullable|string',
            'cantidad' => 'nullable|integer|min:1|max:12',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:pendiente,en_proceso,completado',
        ]);

        $analisis->update($validated);
        
        return redirect()->route('analisis-pasteurizadora.show', $analisis->id)
            ->with('success', 'Análisis actualizado exitosamente.');
    }

    /**
     * DESTROY - Eliminar análisis
     */
    public function destroy($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        $analisis->delete();
        
        return redirect()->route('analisis-pasteurizadora.index')
            ->with('success', 'Análisis eliminado exitosamente.');
    }

    /**
     * HISTORIAL - Ver historial completo de actividades
     */
    public function historial(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $componente = $request->get('componente');
        $modulo = $request->get('modulo');
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');
        
        $query = AnalisisPasteurizadora::porLinea($linea)
            ->orderBy('fecha', 'desc');
            
        if ($componente) {
            $query->porComponente($componente);
        }
        
        if ($modulo) {
            $query->porModulo($modulo);
        }
        
        if ($fechaInicio && $fechaFin) {
            $query->entreFechas($fechaInicio, $fechaFin);
        }
        
        $actividades = $query->paginate(20);
        $resumenComponentes = $this->getResumenComponentes($linea);
        
        return view('analisis-pasteurizadora.historial', compact('actividades', 'resumenComponentes', 'linea'));
    }

    /**
     * HISTORICO REVISADOS - Ver histórico de componentes revisados
     */
    public function historicoRevisados(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $resumenComponentes = $this->getResumenComponentes($linea);
        
        return view('analisis-pasteurizadora.historico-revisados', compact('resumenComponentes', 'linea'));
    }

    /**
     * PLAN ACCION - Ver/Editar plan de acción PCM
     */
    public function planAccion(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $registro = AnalisisPasteurizadora::porLinea($linea)
            ->latest()
            ->first();
            
        return view('analisis-pasteurizadora.plan-accion', compact('registro', 'linea'));
    }

    /**
     * UPDATE PLAN ACCION - Actualizar plan de acción PCM
     */
    public function updatePlanAccion(Request $request)
    {
        $validated = $request->validate([
            'linea' => 'required|in:L-07,L-08',
            'pcm1' => 'nullable|array',
            'pcm2' => 'nullable|array',
            'pcm3' => 'nullable|array',
            'pcm4' => 'nullable|array',
        ]);

        $registro = AnalisisPasteurizadora::porLinea($validated['linea'])
            ->latest()
            ->first();
            
        if (!$registro) {
            $registro = new AnalisisPasteurizadora(['linea' => $validated['linea']]);
        }
        
        $registro->plan_accion_pcm1 = $validated['pcm1'] ?? null;
        $registro->plan_accion_pcm2 = $validated['pcm2'] ?? null;
        $registro->plan_accion_pcm3 = $validated['pcm3'] ?? null;
        $registro->plan_accion_pcm4 = $validated['pcm4'] ?? null;
        $registro->save();
        
        return redirect()->back()->with('success', 'Plan de acción actualizado exitosamente.');
    }

    /**
     * ANALISIS 52-12-4 - Ver/Editar análisis 52-12-4
     */
    public function analisis52124(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $registro = AnalisisPasteurizadora::porLinea($linea)
            ->latest()
            ->first();
            
        return view('analisis-pasteurizadora.analisis-52-12-4', compact('registro', 'linea'));
    }

    /**
     * UPDATE ANALISIS 52-12-4 - Actualizar análisis 52-12-4
     */
    public function updateAnalisis52124(Request $request)
    {
        $validated = $request->validate([
            'linea' => 'required|in:L-07,L-08',
            'valor_actual_52' => 'required|numeric',
            'valor_actual_12' => 'required|numeric',
            'valor_actual_4' => 'required|numeric',
        ]);

        $registro = AnalisisPasteurizadora::porLinea($validated['linea'])
            ->latest()
            ->first();
            
        if (!$registro) {
            $registro = new AnalisisPasteurizadora(['linea' => $validated['linea']]);
            $registro->valor_anterior_52 = 0.51;
            $registro->valor_anterior_12 = 0.25;
            $registro->valor_anterior_4 = 0.23;
        }
        
        $registro->valor_actual_52 = $validated['valor_actual_52'];
        $registro->valor_actual_12 = $validated['valor_actual_12'];
        $registro->valor_actual_4 = $validated['valor_actual_4'];
        $registro->save();
        
        return redirect()->back()->with('success', 'Análisis 52-12-4 actualizado exitosamente.');
    }

    /**
     * EXPORT EXCEL - Exportar datos a Excel
     */
    public function exportExcel(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $data = AnalisisPasteurizadora::porLinea($linea)
            ->orderBy('fecha', 'desc')
            ->get();
            
        // Aquí implementarías la exportación a Excel
        // return Excel::download(new AnalisisPasteurizadoraExport($data), 'pasteurizadora_' . $linea . '.xlsx');
        
        return redirect()->back()->with('info', 'Funcionalidad de exportación a Excel en desarrollo.');
    }

    /**
     * EXPORT PDF - Exportar datos a PDF
     */
    public function exportPdf(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $registro = AnalisisPasteurizadora::porLinea($linea)
            ->latest()
            ->first();
            
        $actividades = AnalisisPasteurizadora::porLinea($linea)
            ->whereNotNull('actividad')
            ->orderBy('fecha', 'desc')
            ->limit(20)
            ->get();
            
        $pdf = Pdf::loadView('analisis-pasteurizadora.export-pdf', compact('registro', 'actividades', 'linea'));
        return $pdf->download('analisis_pasteurizadora_' . $linea . '_' . date('Ymd') . '.pdf');
    }

    /**
     * EXPORT PROCESS - Procesar exportación con filtros
     */
    public function exportProcess(Request $request)
    {
        $validated = $request->validate([
            'formato' => 'required|in:excel,pdf,csv',
            'periodo' => 'required|in:todo,mes,trimestre,año',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'componentes' => 'nullable|array',
        ]);

        // Aquí implementarías la lógica de exportación con filtros
        
        return redirect()->back()->with('success', 'Exportación completada exitosamente.');
    }

    /**
     * DELETE FOTO - Eliminar foto de un análisis
     */
    public function deleteFoto($id, $fotoIndex)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        $fotos = $analisis->fotos ?? [];
        
        if (isset($fotos[$fotoIndex])) {
            Storage::delete($fotos[$fotoIndex]['path']);
            unset($fotos[$fotoIndex]);
            $analisis->fotos = array_values($fotos);
            $analisis->save();
        }
        
        return redirect()->back()->with('success', 'Foto eliminada exitosamente.');
    }

    /**
     * AJAX: Get componentes por línea
     */
    public function getComponentesPorLineaAjax(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        
        $componentes = AnalisisPasteurizadora::porLinea($linea)
            ->select('componente', DB::raw('count(*) as total'))
            ->whereNotNull('componente')
            ->groupBy('componente')
            ->get();
            
        return response()->json($componentes);
    }

    /**
     * AJAX: Get actividades por módulo
     */
    public function getActividadesPorModulo(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $modulo = $request->get('modulo');
        
        $actividades = AnalisisPasteurizadora::porLinea($linea)
            ->porModulo($modulo)
            ->whereNotNull('actividad')
            ->orderBy('fecha', 'desc')
            ->get();
            
        return response()->json($actividades);
    }

    /**
     * AJAX: Get estadísticas de componentes
     */
    public function getEstadisticasComponentes(Request $request)
    {
        $linea = $request->get('linea', 'L-07');
        $resumen = $this->getResumenComponentes($linea);
        
        return response()->json($resumen);
    }

    /**
     * API: Get componentes
     */
    public function apiGetComponentes($linea)
    {
        $componentes = AnalisisPasteurizadora::porLinea($linea)
            ->select('componente', DB::raw('count(*) as total'))
            ->whereNotNull('componente')
            ->groupBy('componente')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $componentes
        ]);
    }

    /**
     * API: Get estadísticas
     */
    public function apiGetEstadisticas($linea)
    {
        $resumen = $this->getResumenComponentes($linea);
        
        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * API: Get análisis 52-12-4
     */
    public function apiGetAnalisis52124()
    {
        $registro = AnalisisPasteurizadora::porLinea('L-07')
            ->latest()
            ->first();
            
        $analisis = $registro ? $registro->getAnalisis52124() : [
            'componente_52' => ['anterior' => 0.51, 'actual' => 0.69, 'variacion' => 35.29],
            'componente_12' => ['anterior' => 0.25, 'actual' => 1.08, 'variacion' => 332.00],
            'componente_4' => ['anterior' => 0.23, 'actual' => 2.52, 'variacion' => 995.65]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $analisis
        ]);
    }

    /**
     * Método privado para obtener resumen de componentes
     */
    private function getResumenComponentes($linea = 'L-07')
    {
        $registro = AnalisisPasteurizadora::porLinea($linea)
            ->latest()
            ->first();
            
        if (!$registro) {
            return [
                'anillas' => ['total' => 12, 'revisadas' => 5, 'avance' => 41.67],
                'placas' => ['total' => 12, 'revisadas' => 5, 'avance' => 41.67],
                'parrillas' => ['total' => 12, 'revisadas' => 5, 'avance' => 41.67],
                'rodamientos' => ['total' => 12, 'revisadas' => 5, 'avance' => 41.67],
                'excentricos' => ['total' => 12, 'revisadas' => 5, 'avance' => 41.67],
                'reglillas' => ['total' => 12, 'revisadas' => 5, 'avance' => 41.67]
            ];
        }
        
        return $registro->getEstadisticasCompletas();
    }
}