<?php

namespace App\Http\Controllers;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalisisPasteurizadoraController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================================================
    // INDEX
    // ============================================================
    
    public function index(Request $request)
    {
        $lineas = Linea::all();
        
        $lineaId = $request->get('linea_id', 'todas');
        $lineaSeleccionada = $lineaId !== 'todas' ? Linea::find($lineaId) : null;
        
        $query = AnalisisPasteurizadora::with('linea')
            ->where('resuelto_por_cambio', false);
        
        if ($lineaId !== 'todas' && $lineaId) {
            $query->where('linea_id', $lineaId);
        }
        
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }
        
        if ($request->filled('componente')) {
            $query->where('componente', $request->componente);
        }
        
        if ($request->filled('fecha')) {
            $query->whereYear('fecha_analisis', substr($request->fecha, 0, 4))
                  ->whereMonth('fecha_analisis', substr($request->fecha, 5, 2));
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        $analisis = $query->latest('fecha_analisis')->get();
        
        $totalAnalisis = $analisis->count();
        $totalDanados = $analisis->where('estado', 'Dañado - Requiere cambio')->count();
        $totalCambiados = $analisis->where('estado', 'Cambiado')->count();
        
        // Filtrar solo líneas de pasteurizadora (P-03 a P-14)
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineasFiltradas = $lineas->filter(function($linea) use ($pasteurizadorasPermitidas) {
            return in_array($linea->nombre, $pasteurizadorasPermitidas);
        })->values();
        
        $mostrarTodas = !request('linea_id') || request('linea_id') === 'todas';
        
        return view('pasteurizadora.analisis-pasteurizadora.index', compact(
            'analisis', 'lineasFiltradas', 'totalAnalisis', 'totalDanados', 'totalCambiados',
            'lineaSeleccionada', 'mostrarTodas'
        ));
    }

        public function dashboard()
{
    $analisis = AnalisisPasteurizadora::with('linea')->latest()->take(10)->get();

    $total = AnalisisPasteurizadora::count();
    $danados = AnalisisPasteurizadora::where('estado', 'Dañado - Requiere cambio')->count();
    $cambiados = AnalisisPasteurizadora::where('estado', 'Cambiado')->count();

    return view('pasteurizadora.dashboard', compact(
        'analisis',
        'total',
        'danados',
        'cambiados'
    ));
}
    // ============================================================
    // CREATE
    // ============================================================
    
    public function create(Request $request)
    {
        $lineaId = $request->get('linea_id');
        
        if (!$lineaId) {
            return redirect()->route('analisis-pasteurizadora.select-linea')
                ->with('error', 'Debe seleccionar una línea primero');
        }
        
        $linea = Linea::findOrFail($lineaId);
        $fechaSugerida = date('Y-m-d');
        
        return view('pasteurizadora.analisis-pasteurizadora.create', compact('linea', 'fechaSugerida'));
    }

   public function createQuick(Request $request)
{
    $linea = Linea::findOrFail($request->linea_id);
    $modulo = $request->modulo;
    $componente = $request->componente;
    $fecha = $request->fecha;
    
    // Calcular total de piezas para este componente
    $totalPiezas = 0;
    $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
    
    // Buscar el componente (puede venir con mayúsculas o minúsculas)
    $componenteKey = strtoupper($componente);
    if (isset($componentes[$componenteKey])) {
        $totalPiezas = $componentes[$componenteKey]['cantidad'];
    } else {
        // Buscar por coincidencia
        foreach ($componentes as $key => $comp) {
            if (strtoupper($key) === $componenteKey) {
                $totalPiezas = $comp['cantidad'];
                break;
            }
        }
    }
    
    // Log para debug
    \Log::info('createQuick - Componente:', [
        'componente_recibido' => $componente,
        'componente_key' => $componenteKey,
        'total_piezas' => $totalPiezas,
        'componentes_disponibles' => array_keys($componentes)
    ]);
    
    return view('pasteurizadora.analisis-pasteurizadora.create-quick', compact(
        'linea', 'modulo', 'componente', 'fecha', 'totalPiezas'
    ));
}

    // ============================================================
    // STORE
    // ============================================================
    
 public function store(Request $request)
{
    $validated = $request->validate([
        'linea_id' => 'required|exists:lineas,id',
        'modulo' => 'required|integer|min:1',
        'nivel' => 'nullable|in:SUPERIOR,INFERIOR',
        'componente' => 'required|string',
        'lado' => 'nullable|in:VAPOR,PASILLO',
        'fecha_analisis' => 'required|date',
        'numero_orden' => 'required|string|max:50',
        'estado' => 'required|in:' . implode(',', AnalisisPasteurizadora::ESTADOS),
        'actividad' => 'required|string',
        'evidencia_fotos' => 'nullable|array',
        'evidencia_fotos.*' => 'nullable|image|max:5120',
        'componentes_revisados' => 'nullable',
    ]);
    
    // Obtener la línea y calcular total de piezas
    $linea = Linea::findOrFail($validated['linea_id']);
    $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
    $totalPiezas = $componentes[$validated['componente']]['cantidad'] ?? 0;
    
    // Procesar componentes_revisados
    $componentesRevisados = [];
    
    if ($request->filled('componentes_revisados')) {
        $input = $request->input('componentes_revisados');
        
        // Si es string JSON (viene del create-quick)
        if (is_string($input) && !empty($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $componentesRevisados = $decoded;
            }
        } 
        // Si es array (viene del create normal)
        elseif (is_array($input)) {
            $componentesRevisados = $input;
        }
        
        // Filtrar y limpiar valores
        $componentesRevisados = array_values(array_filter(
            array_map('intval', $componentesRevisados),
            fn($val) => $val > 0 && $val <= $totalPiezas
        ));
    }
    
    // Procesar imágenes
    $fotosPaths = [];
    if ($request->hasFile('evidencia_fotos')) {
        foreach ($request->file('evidencia_fotos') as $foto) {
            if ($foto) {
                $path = $foto->store('analisis-pasteurizadora', 'public');
                $fotosPaths[] = $path;
            }
        }
    }
    
    // Crear el registro - ⚠️ NO usar total_piezas para almacenar componentes_revisados
    $analisis = AnalisisPasteurizadora::create([
        'linea_id' => $validated['linea_id'],
        'modulo' => $validated['modulo'],
        'nivel' => $validated['nivel'] ?? null,
        'componente' => $validated['componente'],
        'lado' => $validated['lado'] ?? null,
        'fecha_analisis' => $validated['fecha_analisis'],
        'numero_orden' => $validated['numero_orden'],
        'estado' => $validated['estado'],
        'actividad' => $validated['actividad'],
        'evidencia_fotos' => $fotosPaths,
        'componentes_revisados' => $componentesRevisados, // [1, 2, 3]
        'revisadas_piezas' => count($componentesRevisados), // Esto es importante
        'total_piezas' => $totalPiezas,
        'resuelto_por_cambio' => false,
    ]);
    
    // Marcar como resuelto si es necesario
    if ($validated['estado'] === 'Cambiado') {
        $this->marcarRegistrosAnterioresComoResueltos($analisis);
    }
    
    return redirect()
        ->route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $validated['linea_id']])
        ->with('success', 'Análisis registrado correctamente.');
}

    public function storeQuick(Request $request)
    {
        return $this->store($request);
    }

    private function marcarRegistrosAnterioresComoResueltos($nuevoAnalisis)
    {
        $registrosAnteriores = AnalisisPasteurizadora::where('linea_id', $nuevoAnalisis->linea_id)
            ->where('modulo', $nuevoAnalisis->modulo)
            ->where('componente', $nuevoAnalisis->componente)
            ->where('estado', 'Dañado - Requiere cambio')
            ->where('resuelto_por_cambio', false)
            ->where('id', '!=', $nuevoAnalisis->id)
            ->get();
        
        foreach ($registrosAnteriores as $registro) {
            $registro->update([
                'resuelto_por_cambio' => true,
                'fecha_resolucion' => now(),
                'nota_resolucion' => "Resuelto por cambio en orden #{$nuevoAnalisis->numero_orden}"
            ]);
        }
    }

    // ============================================================
    // SHOW, EDIT, UPDATE, DELETE
    // ============================================================
    
    public function show($id)
    {
        $analisis = AnalisisPasteurizadora::with('linea')->findOrFail($id);
        return view('pasteurizadora.analisis-pasteurizadora.show', compact('analisis'));
    }

    public function edit($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        $lineas = Linea::all();
        return view('pasteurizadora.analisis-pasteurizadora.edit', compact('analisis', 'lineas'));
    }

    public function update(Request $request, $id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        
        $validated = $request->validate([
            'modulo' => 'nullable|integer|min:1',
            'componente' => 'nullable|string',
            'fecha_analisis' => 'nullable|date',
            'numero_orden' => 'nullable|string',
            'actividad' => 'nullable|string',
            'estado' => 'nullable|string|in:' . implode(',', AnalisisPasteurizadora::ESTADOS),
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'revisadas_piezas' => 'nullable|integer|min:0',
            'componentes_revisados' => 'nullable|array',
        ]);
        
        // Procesar componentes revisados
        $componentesRevisados = [];
        if (!empty($validated['componentes_revisados'])) {
            $lineaNombre = $analisis->linea->nombre;
            $componentes = AnalisisPasteurizadora::getComponentesPorLinea($lineaNombre);
            $totalPiezas = $componentes[$analisis->componente]['cantidad'] ?? 0;
            
            // Validar y convertir a array
            $componentesRevisados = array_filter(
                $validated['componentes_revisados'],
                fn($val) => is_numeric($val) && intval($val) > 0 && intval($val) <= $totalPiezas
            );
            $componentesRevisados = array_values(array_map('intval', $componentesRevisados));
        }
        
        if (isset($validated['estado']) && $validated['estado'] === 'Cambiado' && $analisis->estado !== 'Cambiado') {
            $tempAnalisis = (object)[
                'linea_id' => $analisis->linea_id,
                'modulo' => $validated['modulo'] ?? $analisis->modulo,
                'componente' => $validated['componente'] ?? $analisis->componente,
                'numero_orden' => $validated['numero_orden'] ?? $analisis->numero_orden,
            ];
            $this->marcarRegistrosAnterioresComoResueltos($tempAnalisis);
        }
        
        // Agregar componentes revisados a validated solo si hay valores
        if (!empty($componentesRevisados)) {
            $validated['componentes_revisados'] = $componentesRevisados;
            $validated['revisadas_piezas'] = count($componentesRevisados);
        } elseif (isset($validated['componentes_revisados']) && empty($validated['componentes_revisados'])) {
            // Si enviaron array vacío, no incluir el campo
            unset($validated['componentes_revisados']);
        }
        
        $analisis->update($validated);
        
        return redirect()->route('pasteurizadora.analisis-pasteurizadora.show', $analisis->id)
            ->with('success', 'Análisis actualizado correctamente');
    }

    public function destroy($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        
        if ($analisis->evidencia_fotos) {
            foreach ($analisis->evidencia_fotos as $foto) {
                Storage::disk('public')->delete($foto);
            }
        }
        
        $analisis->delete();
        
        return redirect()->route('pasteurizadora.analisis-pasteurizadora.index')
            ->with('success', 'Registro eliminado correctamente');
    }

    // ============================================================
    // VISTAS AUXILIARES
    // ============================================================
    
   public function selectLinea()
{
    $nombres = ['P-03','P-04','P-05','P-06','P-07','P-08','P-09','P-10','P-11','P-12','P-13','P-14'];

    foreach ($nombres as $nombre) {
        \App\Models\Linea::firstOrCreate(['nombre' => $nombre]);
    }

    $lineas = \App\Models\Linea::whereIn('nombre', $nombres)->get()->keyBy('nombre');

    return view('pasteurizadora.analisis-pasteurizadora.select-linea', compact('lineas'));
}

    public function historial(Request $request)
    {
        $query = AnalisisPasteurizadora::with('linea')->orderBy('fecha_analisis', 'desc');
        
        if ($request->filled('linea_id')) {
            $query->where('linea_id', $request->linea_id);
        }
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }
        if ($request->filled('componente')) {
            $query->where('componente', $request->componente);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('resueltos')) {
            $query->where('resuelto_por_cambio', $request->resueltos === 'si');
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_analisis', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_analisis', '<=', $request->fecha_fin);
        }
        
        $analisis = $query->paginate(10)->withQueryString();
        
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineas = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->orderBy('nombre')->get();
        
        return view('pasteurizadora.analisis-pasteurizadora.historial', compact('analisis', 'lineas'));
    }

    // ============================================================
    // PLAN DE ACCIÓN
    // ============================================================
    
    public function planAccion(Request $request)
    {
        $query = AnalisisPasteurizadora::where('estado', 'Dañado - Requiere cambio')
            ->where('resuelto_por_cambio', false)
            ->with('linea');
        
        if ($request->filled('linea_id')) {
            $query->where('linea_id', $request->linea_id);
        }
        
        $planes = $query->get();
        
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineas = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->orderBy('nombre')->get();
        
        $lineaSeleccionada = $request->linea_id ?? null;
        
        return view('plan-accion.pasteurizadora.index', compact('planes', 'lineas', 'lineaSeleccionada'));
    }

    public function createPlanAccion(Request $request)
    {
        $lineas = Linea::all();
        $lineaSeleccionada = $request->get('linea_id');
        
        return view('plan-accion.pasteurizadora.create', compact('lineas', 'lineaSeleccionada'));
    }

    // ============================================================
    // HISTÓRICO DE REVISADOS
    // ============================================================

    public function historicoRevisados(Request $request)
    {
        // Obtener todas las líneas de pasteurizadora (P-03 a P-14)
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineasPasteurizadora = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->get();
        $lineas = $lineasPasteurizadora;

        $lineaSeleccionada = null;
        $mostrarTodas = false;
        $lineaId = $request->get('linea_id');

        if ($lineaId === 'all') {
            $mostrarTodas = true;
        } elseif ($lineaId) {
            $lineaSeleccionada = Linea::find($lineaId);
        } elseif ($lineasPasteurizadora->isNotEmpty()) {
            $lineaSeleccionada = $lineasPasteurizadora->first();
        }

        // Obtener todos los componentes para mostrar
        $componentesModulos = collect();

        if ($mostrarTodas) {
            $niveles = ['SUPERIOR', 'INFERIOR'];
            foreach ($lineasPasteurizadora as $linea) {
                $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
                $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);

                for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
                    foreach ($componentes as $codigo => $compData) {
                        foreach ($niveles as $nivel) {
                            $componentesModulos->push([
                                'linea_id' => $linea->id,
                                'linea_nombre' => $linea->nombre,
                                'codigo' => $codigo,
                                'nombre' => $compData['nombre'],
                                'modulo' => $modulo,
                                'nivel' => $nivel,
                                'cantidad_total' => $compData['cantidad']
                            ]);
                        }
                    }
                }
            }
        } elseif ($lineaSeleccionada) {
            $componentes = AnalisisPasteurizadora::getComponentesPorLinea($lineaSeleccionada->nombre);
            $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($lineaSeleccionada->nombre);
            $niveles = ['SUPERIOR', 'INFERIOR'];

            for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
                foreach ($componentes as $codigo => $compData) {
                    foreach ($niveles as $nivel) {
                        $componentesModulos->push([
                            'linea_id' => $lineaSeleccionada->id,
                            'linea_nombre' => $lineaSeleccionada->nombre,
                            'codigo' => $codigo,
                            'nombre' => $compData['nombre'],
                            'modulo' => $modulo,
                            'nivel' => $nivel,
                            'cantidad_total' => $compData['cantidad']
                        ]);
                    }
                }
            }
        }

        // Calcular estadísticas
        $estadisticas = [];
        $totalGeneral = 0;
        $totalRevisado = 0;

        if ($lineaSeleccionada || $mostrarTodas) {
            foreach ($componentesModulos as $item) {
                $lineaIdParaAnalisis = $item['linea_id'];
                $analisis = AnalisisPasteurizadora::where('linea_id', $lineaIdParaAnalisis)
                    ->where('componente', $item['codigo'])
                    ->where('modulo', $item['modulo'])
                    ->where('nivel', $item['nivel'])
                    ->get();

                $total = $item['cantidad_total'];

                $revisadas = $analisis->sum('revisadas_piezas');
                $revisadas = min($revisadas, $total);
                $porcentaje = $total > 0 ? round(($revisadas / $total) * 100) : 0;

                $color = $porcentaje >= 80 ? 'success' : ($porcentaje >= 50 ? 'info' : ($porcentaje >= 20 ? 'warning' : 'danger'));

                $estadisticas[$item['linea_id']][$item['codigo']][$item['modulo']][$item['nivel']] = [
                    'total' => $total,
                    'revisadas' => $revisadas,
                    'porcentaje' => $porcentaje,
                    'color' => $color,
                ];

                $totalGeneral += $total;
                $totalRevisado += $revisadas;
            }

            $estadisticas['resumen'] = [
                'total_general' => $totalGeneral,
                'total_revisado' => $totalRevisado,
                'porcentaje_general' => $totalGeneral > 0 ? round(($totalRevisado / $totalGeneral) * 100) : 0
            ];
        }

        return view('historico-revisados.pasteurizadora.index', compact(
            'lineas', 'lineasPasteurizadora', 'lineaSeleccionada', 'componentesModulos', 'estadisticas', 'mostrarTodas'
        ));
    }

    // ============================================================
    // MÉTODOS AJAX
    // ============================================================
    
    public function getComponentesPorLineaAjax(Request $request)
    {
        $lineaId = $request->get('linea_id');
        $linea = Linea::find($lineaId);
        
        if (!$linea) {
            return response()->json([]);
        }
        
        $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        
        return response()->json($componentes);
    }

    public function deleteFoto($id, $fotoIndex)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        
        if (isset($analisis->evidencia_fotos[$fotoIndex])) {
            Storage::disk('public')->delete($analisis->evidencia_fotos[$fotoIndex]);
            $fotos = $analisis->evidencia_fotos;
            unset($fotos[$fotoIndex]);
            $analisis->evidencia_fotos = array_values($fotos);
            $analisis->save();
            
            return redirect()->back()->with('success', 'Foto eliminada correctamente');
        }
        
        return redirect()->back()->with('error', 'No se pudo eliminar la foto');
    }
    public function analisis52124()
    {
        return view('analisis-tendencia-mensual.pasteurizadora.index');
    }
    public function updateAnalisis52124(Request $request)
    {
        // lógica futura
        return back()->with('success', 'Datos actualizados correctamente');
    }

    public function crearLineasPasteurizadora(Request $request)
    {
        try {
            $lineasExistentes = Linea::pluck('nombre')->toArray();
            $lineasNecesarias = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
            $creadas = [];
            
            foreach ($lineasNecesarias as $nombre) {
                if (!in_array($nombre, $lineasExistentes)) {
                    $linea = Linea::create([
                        'nombre' => $nombre,
                        'nombre_completo' => 'Pasteurizadora ' . $nombre,
                        'activo' => true
                    ]);
                    $creadas[] = $nombre;
                }
            }
            
            return response()->json([
                'success' => true,
                'creadas' => $creadas,
                'message' => count($creadas) . ' líneas creadas: ' . implode(', ', $creadas)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function createWithLinea($linea)
{
    $linea = Linea::findOrFail($linea);

    $fechaSugerida = date('Y-m-d');

    return view('pasteurizadora.analisis-pasteurizadora.create', compact('linea', 'fechaSugerida'));
}
}