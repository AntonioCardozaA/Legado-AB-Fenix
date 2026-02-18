<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\Linea;
use App\Models\Componente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exports\AnalisisComponentesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalisisLavadoraController extends Controller
{
    /**
     * LISTADO + FILTROS
     */
    public function index(Request $request)
{
    $query = AnalisisLavadora::with(['linea', 'componente'])
        ->orderBy('fecha_analisis', 'desc')
        ->orderBy('created_at', 'desc');

    // FILTROS
    if ($request->filled('linea_id')) {
        $query->where('linea_id', $request->linea_id);
    }

    if ($request->filled('componente_id')) {
        $query->whereHas('componente', function ($q) use ($request) {
            $q->where('codigo', 'like', '%' . $request->componente_id . '%');
        });
    }

    if ($request->filled('reductor')) {
        $query->where('reductor', 'like', '%' . $request->reductor . '%');
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado);
    }

    if ($request->filled('fecha')) {
        $query->whereMonth('fecha_analisis', substr($request->fecha, 5, 2))
              ->whereYear('fecha_analisis', substr($request->fecha, 0, 4));
    }

    $analisis = $query->get();

    // Obtener línea seleccionada
    $lineaMostrar = 'Todas las líneas';
    $linea = null;

    if ($request->filled('linea_id')) {
        $linea = Linea::find($request->linea_id);

        if ($linea) {
            $lineaMostrar = $linea->nombre;
        }
    }

    // Reductores por línea
    $reductoresMostrar = [];
    if (!$request->filled('linea_id')) {
    return redirect()->route('analisis-lavadora.index', [
        'linea_id' => Linea::where('activo', true)->first()->id
    ]);
} else {
    $reductoresMostrar = $this->getReductoresPorLinea($lineaMostrar);
}

    return view('lavadora/analisis-lavadora.index', [
        'analisis' => $analisis,
        'lineas' => Linea::where('activo', true)->orderBy('nombre')->get(),
        'componentesPorLinea' => $this->getComponentesPorLinea(),
        'todosComponentes' => $this->getTodosComponentes(),
        'reductores' => AnalisisLavadora::select('reductor')
            ->whereNotNull('reductor')
            ->distinct()
            ->orderBy('reductor')
            ->pluck('reductor'),
        'reductoresMostrar' => $reductoresMostrar,
        'lineaMostrar' => $lineaMostrar,
        'filtros' => $request->all(),
    ]);
}



    /**
     * Obtener componentes organizados por línea para la tabla.
     */
    private function getComponentesPorLinea(): array
    {
        return [
            'L-04' => [
                'SERVO_CHICO' => 'Servo Chico',
                'SERVO_GRANDE' => 'Servo Grande',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
                'CATARINAS' => 'Catarinas',
            ],
            'L-05' => [
                'RV200' => 'Reductor RV200',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
                'CATARINAS' => 'Catarinas',
            ],
            'L-06' => [
                'SERVO_CHICO' => 'Servo Chico',
                'SERVO_GRANDE' => 'Servo Grande',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
                'CATARINAS' => 'Catarinas',
            ],
            'L-07' => [
                'SERVO_CHICO' => 'Servo Chico',
                'SERVO_GRANDE' => 'Servo Grande',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
                'CATARINAS' => 'Catarinas',
            ],
            'L-08' => [
                'SERVO_CHICO' => 'Servo Chico',
                'SERVO_GRANDE' => 'Servo Grande',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
                'CATARINAS' => 'Catarinas',
            ],
            'L-09' => [
                'SERVO_CHICO' => 'Servo Chico',
                'SERVO_GRANDE' => 'Servo Grande',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
                'CATARINAS' => 'Catarinas',
            ],
            'L-12' => [
                'RV200_SIN_FIN' => 'Reductor Sin Fin-Corona RV200',
                'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
                'GUI_INF_TANQUE' => 'Guía Inferior ',
                'GUI_INT_TANQUE' => 'Guía Intermedia ',
                'GUI_SUP_TANQUE' => 'Guía Superior ',
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
    }
    
    /**
     * Obtener todos los componentes posibles para el filtro.
     */
    private function getTodosComponentes(): array
    {
        return [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior ',
            'GUI_INT_TANQUE' => 'Guía Intermedia ',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
            'RV200' => 'Reductor RV200',
            'RV200_SIN_FIN' => 'Reductor Sin Fin-Corona RV200'
        ];
    }

    /**
     * Obtener todos los reductores posibles para una línea específica
     */
    private function getReductoresPorLinea(string $lineaNombre): array
    {
        $reductoresPorLinea = [
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

        return $reductoresPorLinea[$lineaNombre] ?? ['Reductor 1', 'Reductor 2', 'Reductor 3'];
    }

    /**
     * SELECCIONAR LÍNEA (LAVADORA)
     */
    public function selectLinea()
    {
        $lineas = Linea::whereIn('nombre', [
            'L-04','L-05','L-06','L-07','L-08','L-09','L-12','L-13'
        ])->get();

        return view('lavadora/analisis-lavadora.select-linea', compact('lineas'));
    }

    /**
     * CREAR ANÁLISIS CON LÍNEA
     */
    public function createWithLinea($lineaId)
    {
        $linea = Linea::findOrFail($lineaId);

        // Obtener componentes disponibles para esta línea
        $componentesPorLinea = $this->getComponentesPorLinea();
        $componentesDisponibles = $componentesPorLinea[$linea->nombre] ?? [];

        // Obtener reductores únicos para esta línea
        $reductores = Componente::where('linea', $linea->nombre)
            ->where('activo', true)
            ->whereNotNull('reductor')
            ->select('reductor')
            ->distinct()
            ->orderBy('reductor')
            ->pluck('reductor');

        return view('lavadora/analisis-lavadora.create', compact(
            'linea',
            'componentesDisponibles',
            'reductores'
        ));
    }

    /**
     * CREAR ANÁLISIS RÁPIDO
     */
    public function createQuick(Request $request)
    {
        // Validar que los parámetros requeridos están presentes
        $request->validate([
            'linea_id'           => 'required|exists:lineas,id',
            'componente_codigo'  => 'required|string',
            'reductor'           => 'required|string',
        ]);

        Log::info('Creando análisis rápido con:', $request->all());

        $linea = Linea::findOrFail($request->linea_id);
        
        // Buscar el componente por código (sin filtrar por línea primero)
        $componente = Componente::where('codigo', $request->componente_codigo)
            ->first();

        // Si no existe el componente en la base de datos
        if (!$componente) {
            Log::info('Componente no encontrado, creando nuevo: ' . $request->componente_codigo);
            
            try {
                $componente = Componente::create([
                    'codigo' => $request->componente_codigo,
                    'nombre' => $this->getNombreComponente($request->componente_codigo),
                    'reductor' => $request->reductor,
                    'ubicacion' => $request->reductor,
                    'linea' => $linea->nombre,
                    'cantidad_total' => 1,
                    'activo' => true,
                ]);
                
                Log::info('Componente creado con ID: ' . $componente->id);
            } catch (\Illuminate\Database\QueryException $e) {
                // Si hay error de duplicado, buscar el componente existente
                if ($e->getCode() == '23000') {
                    Log::warning('Error de duplicado, buscando componente existente...');
                    $componente = Componente::where('codigo', $request->componente_codigo)
                        ->first();
                    
                    if ($componente) {
                        Log::info('Componente encontrado después de error de duplicado: ' . $componente->id);
                    } else {
                        Log::error('No se pudo encontrar el componente después del error de duplicado');
                        return back()->withErrors(['error' => 'Error al crear el componente. Ya existe un componente con este código.']);
                    }
                } else {
                    Log::error('Error al crear componente:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['error' => 'Error al crear el componente: ' . $e->getMessage()]);
                }
            }
        } else {
            Log::info('Componente encontrado con ID: ' . $componente->id);
            
            // Si el componente existe pero no tiene la línea correcta, actualizarlo
            if ($componente->linea !== $linea->nombre) {
                Log::info('Actualizando línea del componente de ' . $componente->linea . ' a ' . $linea->nombre);
                
                // Crear un nuevo componente específico para esta línea
                try {
                    $nuevoComponente = Componente::create([
                        'codigo' => $request->componente_codigo . '_' . str_replace('-', '_', $linea->nombre),
                        'nombre' => $this->getNombreComponente($request->componente_codigo),
                        'reductor' => $request->reductor,
                        'ubicacion' => $request->reductor,
                        'linea' => $linea->nombre,
                        'cantidad_total' => 1,
                        'activo' => true,
                    ]);
                    
                    $componente = $nuevoComponente;
                    Log::info('Nuevo componente creado para línea específica con ID: ' . $componente->id);
                } catch (\Exception $e) {
                    Log::error('Error al crear componente para línea específica:', ['error' => $e->getMessage()]);
                }
            }
        }

        return view('lavadora/analisis-lavadora.create-quick', [
            'linea'          => $linea,
            'componente'     => $componente,
            'reductor'       => $request->reductor,
            'fecha_sugerida' => $request->fecha ?? now()->toDateString(),
            'redirect_to'    => url()->previous(),
        ]);
    }

    /**
     * GUARDAR ANÁLISIS (NORMAL + RÁPIDO)
     */
    public function store(Request $request)
{
    Log::info('Iniciando store', $request->except(['evidencia_fotos']));

    /**
     * ===============================
     * 1️⃣ VALIDACIÓN
     * ===============================
     */
    $validator = Validator::make($request->all(), [
        'linea_id'          => 'required|exists:lineas,id',
        'componente_codigo' => 'nullable|string',
        'componente_id'     => 'nullable|exists:componentes,id',
        'reductor'          => 'required|string|max:255',
        'fecha_analisis'    => 'required|date',
        'numero_orden'      => 'required|string|max:20', // 🔥 YA NO digits:8
        'estado'            => 'required|string|max:255',
        'actividad'         => 'required|string',
        'evidencia_fotos.*' => 'nullable|image|max:2048',
        'redirect_to'       => 'nullable|string',
    ]);

    if ($validator->fails()) {
        Log::error('Errores de validación', $validator->errors()->toArray());
        return back()->withErrors($validator)->withInput();
    }

    // Debe venir al menos un componente
    if (!$request->filled('componente_codigo') && !$request->filled('componente_id')) {
        return back()->withErrors([
            'componente_codigo' => 'Debe especificar un componente'
        ])->withInput();
    }

    /**
     * ===============================
     * 2️⃣ LÍNEA
     * ===============================
     */
    $linea = Linea::findOrFail($request->linea_id);
    Log::info('Línea:', [$linea->nombre]);

    $componente = null;

    /**
     * ===============================
     * 3️⃣ DETERMINAR COMPONENTE
     * ===============================
     */

    // 🔹 CASO A: CREATE RÁPIDO (componente_codigo)
    if ($request->filled('componente_codigo')) {

        $codigoBase = trim($request->componente_codigo);

        // Código específico por línea (ESTÁNDAR)
        $codigoLinea = $codigoBase . '_' . str_replace('-', '_', $linea->nombre);

        Log::info('Buscando/creando componente rápido', [
            'codigo_base' => $codigoBase,
            'codigo_linea' => $codigoLinea
        ]);

        // Buscar nombre base
        $nombreComponente = $this->getNombreComponente($codigoBase);

        // 🔥 CLAVE: firstOrCreate (nunca duplica)
        $componente = Componente::firstOrCreate(
            ['codigo' => $codigoLinea],
            [
                'nombre'          => $nombreComponente,
                'reductor'        => $request->reductor,
                'ubicacion'       => $request->reductor,
                'linea'           => $linea->nombre,
                'cantidad_total'  => 1,
                'activo'          => true,
            ]
        );

        Log::info('Componente usado (rápido)', [
            'id' => $componente->id,
            'codigo' => $componente->codigo
        ]);
    }

    // 🔹 CASO B: CREATE NORMAL (componente_id)
    if ($request->filled('componente_id')) {

        $componente = Componente::findOrFail($request->componente_id);

        if ($componente->linea !== $linea->nombre) {
            return back()->withErrors([
                'componente_id' => 'El componente no pertenece a esta línea'
            ])->withInput();
        }

        Log::info('Componente usado (normal)', [
            'id' => $componente->id,
            'codigo' => $componente->codigo
        ]);
    }

    /**
     * ===============================
     * 4️⃣ CREAR ANÁLISIS
     * ===============================
     */
    try {
        $analisis = AnalisisLavadora::create([
            'linea_id'       => $linea->id,
            'componente_id'  => $componente->id,
            'reductor'       => $request->reductor,
            'fecha_analisis' => $request->fecha_analisis,
            'numero_orden'   => $request->numero_orden,
            'estado'         => $request->estado,
            'actividad'      => $request->actividad,
        ]);

        Log::info('Análisis creado', ['id' => $analisis->id]);

    } catch (\Exception $e) {
        Log::error('Error al crear análisis', [
            'error' => $e->getMessage()
        ]);

        return back()->withErrors([
            'error' => 'Error al guardar el análisis'
        ])->withInput();
    }

    /**
     * ===============================
     * 5️⃣ GUARDAR EVIDENCIAS
     * ===============================
     */
    if ($request->hasFile('evidencia_fotos')) {
        $fotos = [];

        foreach ($request->file('evidencia_fotos') as $foto) {
            $fotos[] = $foto->store('analisis-evidencias', 'public');
        }

        $analisis->update([
            'evidencia_fotos' => json_encode($fotos),
        ]);
    }

    /**
     * ===============================
     * 6️⃣ REDIRECCIÓN INTELIGENTE
     * ===============================
     */
    if ($request->filled('redirect_to')) {
        return redirect($request->redirect_to)
            ->with('success', 'Análisis rápido registrado correctamente.');
    }

    return redirect()
    ->route('analisis-lavadora.index', [
        'linea_id' => $linea->id
    ])
    ->with('success', 'Análisis registrado correctamente.');

}


    /**
     * Helper para obtener el nombre del componente
     */
    private function getNombreComponente($codigo)
    {
        $nombres = [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga',
            'GUI_INT_TANQUE' => 'Guía Int Tanque',
            'GUI_INT_TAQNQUE' => 'Guía Int Tanque',
            'GUI_SUP_TANQUE' => 'Guía Sup Tanque',
            'CATARINAS' => 'Catarinas',
            'RV200' => 'Reductor RV200',
            'RV200_SIN_FIN' => 'Reductor Sin Fin-Corona RV200',
        ];

        return $nombres[$codigo] ?? $codigo;
    }

/**
 * EDITAR ANÁLISIS
 */
public function edit($id)
{
    $analisisComponente = AnalisisLavadora::with(['linea', 'componente'])
        ->findOrFail($id);

    $componentes = Componente::where('linea', $analisisComponente->linea->nombre)
        ->where('activo', true)
        ->orderBy('nombre')
        ->get();

    return view('lavadora/analisis-lavadora.edit', compact(
        'analisisComponente',
        'componentes'
    ));
}


public function update(Request $request, $id)
{
    $analisis = AnalisisLavadora::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'fecha_analisis'    => 'required|date',
        'numero_orden'      => 'required|string|max:20',
        'estado'            => 'required|string|max:255',
        'actividad'         => 'required|string',
        'evidencia_fotos.*' => 'nullable|image|max:2048',
        'eliminar_fotos'    => 'nullable|array',
        'eliminar_fotos.*'  => 'integer',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    /* =====================================================
     | MANEJO DE EVIDENCIAS
     ===================================================== */
    $fotosExistentes = $analisis->evidencia_fotos ?? [];

    // Eliminar fotos marcadas
    if ($request->filled('eliminar_fotos')) {
        foreach ($request->eliminar_fotos as $index) {
            if (isset($fotosExistentes[$index])) {
                Storage::disk('public')->delete($fotosExistentes[$index]);
                unset($fotosExistentes[$index]);
            }
        }
        $fotosExistentes = array_values($fotosExistentes);
    }

    // Agregar nuevas fotos
    if ($request->hasFile('evidencia_fotos')) {
        foreach ($request->file('evidencia_fotos') as $foto) {
            $fotosExistentes[] = $foto->store('analisis-evidencias', 'public');
        }
    }

    /* =====================================================
     | ACTUALIZAR REGISTRO
     ===================================================== */
    $analisis->update([
        'componente_id'   => $analisis->componente_id, // Mantener el mismo
        'reductor'        => $analisis->reductor, // Mantener el mismo
        'fecha_analisis'  => $request->fecha_analisis,
        'numero_orden'    => $request->numero_orden,
        'estado'          => $request->estado,
        'actividad'       => $request->actividad,
        'evidencia_fotos' => $fotosExistentes,
    ]);

    /* =====================================================
     | REDIRECCIÓN - CORREGIDA
     ===================================================== */
    $redirectUrl = $request->input('redirect_to') ?? route('analisis-lavadora.index');
    
    return redirect($redirectUrl)
        ->with('success', 'Análisis actualizado correctamente.');
}

    /**
     * VER
     */
    public function show(AnalisisLavadora $analisislavadora)
    {
        $analisislavadora->load(['linea', 'componente']);
        return view('lavadora/analisis-lavadora.show', compact('analisislavadora'));
    }
    
    /**
     * ELIMINAR
     */
    public function destroy(AnalisisLavadora $analisisComponente)
    {
        $fotos = json_decode($analisisComponente->evidencia_fotos ?? '[]', true) ?? [];
        foreach ($fotos as $foto) {
            Storage::disk('public')->delete($foto);
        }

        $analisisComponente->delete();

        return back()->with('success', 'Análisis eliminado.');
    }

    /**
     * EXPORTAR EXCEL
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new AnalisisLavadoraExport($request),
            'analisis_lavadora.xlsx'
        );
    }

    /**
     * EXPORTAR PDF
     */
    public function exportPdf(Request $request)
    {
        $analisisAgrupados = AnalisisLavadora::with(['linea', 'componente'])
            ->get()
            ->groupBy(fn ($a) => $a->linea->nombre ?? 'Sin línea');

        return Pdf::loadView(
            'analisis-lavadora.export-pdf',
            compact('analisisAgrupados')
        )->setPaper('a4', 'landscape')
         ->download('analisis_lavadora.pdf');
    }

    /**
     * OBTENER COMPONENTES POR LÍNEA (Para AJAX)
     */
    public function getComponentesPorLineaAjax(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id'
        ]);

        $linea = Linea::findOrFail($request->linea_id);
        
        // Obtener componentes según la línea seleccionada
        $componentesPorLinea = $this->getComponentesPorLinea();
        $componentes = $componentesPorLinea[$linea->nombre] ?? [];

        return response()->json($componentes);
    }

    /**
     * OBTENER REDUCTORES POR LÍNEA (Para AJAX)
     */
    public function getReductoresPorLineaPublic(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id'
        ]);

        $linea = Linea::findOrFail($request->linea_id);

        $reductores = Componente::where('linea', $linea->nombre)
            ->where('activo', true)
            ->whereNotNull('reductor')
            ->select('reductor')
            ->distinct()
            ->orderBy('reductor')
            ->pluck('reductor');

        return response()->json($reductores);
    }

    /**
     * ELIMINAR FOTO
     */
    public function deleteFoto(AnalisisComponente $analisisComponente, $fotoIndex)
    {
        $fotos = json_decode($analisisComponente->evidencia_fotos ?? '[]', true) ?? [];
        
        if (isset($fotos[$fotoIndex])) {
            Storage::disk('public')->delete($fotos[$fotoIndex]);
            unset($fotos[$fotoIndex]);
            
            $analisisComponente->update([
                'evidencia_fotos' => json_encode(array_values($fotos))
            ]);
            
            return back()->with('success', 'Foto eliminada correctamente.');
        }
        
        return back()->with('error', 'Foto no encontrada.');
    }
        public function historial(Request $request)
{
    $request->validate([
        'linea_id' => 'required|exists:lineas,id',
        'componente_id' => 'required|string',
        'reductor' => 'required|string',
    ]);

    // Construir la consulta
    $query = AnalisisLavadora::with(['linea', 'componente'])
        ->where('linea_id', $request->linea_id)
        ->where('reductor', $request->reductor)
        ->whereHas('componente', function ($q) use ($request) {
            $q->where('codigo', 'like', '%' . $request->componente_id . '%');
        })
        ->orderByDesc('fecha_analisis')
        ->orderByDesc('created_at');

    // Paginar los resultados (10 por página)
    $analisis = $query->paginate(10)->withQueryString();

    return view('lavadora/analisis-lavadora.historial', compact('analisis'));
}
public function analisis52124 (Request $request)
{
    $analisis = AnalisisLavadora::with(['linea', 'componente'])
        ->where('linea_id', 1) // L-04
        ->where('reductor', 'Reductor 1')
        ->whereHas('componente', function ($q) {
            $q->where('codigo', 'like', '%SERVO_CHICO%');
        })
        ->orderByDesc('fecha_analisis')
        ->orderByDesc('created_at')
        ->get();

    return view('analisis-52-12-4.index', compact('analisis'));
}
public function historicoRevisados(Request $request)
{
    // Obtener todas las líneas activas
    $lineas = Linea::where('activo', true)
        ->orderBy('nombre')
        ->get();
    
    // Separar líneas por tipo (asumimos que líneas con L- son lavadoras, P- son pasteurizadoras)
    $lineasLavadora = $lineas->filter(function($linea) {
        return str_starts_with($linea->nombre, 'L-');
    })->values();
    
    $lineasPasteurizadora = $lineas->filter(function($linea) {
        return str_starts_with($linea->nombre, 'P-');
    })->values();
    
    // Definir los componentes estándar con sus cantidades totales por línea
    $componentesConfig = [
        'SERVO_CHICO' => [
            'nombre' => 'SERVO CHICO',
            'cantidad_total' => 15,
            'icono' => 'servo-chico.png'
        ],
        'SERVO_GRANDE' => [
            'nombre' => 'SERVO GRANDE',
            'cantidad_total' => 15,
            'icono' => 'servo-grande.png'
        ],
        'BUJE_ESPIGA' => [
            'nombre' => 'BUJE BAQUELITA Y ESPIGA',
            'cantidad_total' => 15,
            'icono' => 'buje-espiga.png'
        ],
        'GUI_INF_TANQUE' => [
            'nombre' => 'GUÍA INFERIOR',
            'cantidad_total' => 15,
            'icono' => 'guia-inferior.png'
        ],
        'GUI_INT_TANQUE' => [
            'nombre' => 'GUÍA INTERMEDIA',
            'cantidad_total' => 15,
            'icono' => 'guia-intermedia.png'
        ],
        'GUI_SUP_TANQUE' => [
            'nombre' => 'GUÍA SUPERIOR',
            'cantidad_total' => 15,
            'icono' => 'guia-superior.png'
        ],
        'CATARINAS' => [
            'nombre' => 'CATARINAS',
            'cantidad_total' => 15,
            'icono' => 'catarinas.png'
        ],
        'RV200' => [
            'nombre' => 'REDUCTOR RV200',
            'cantidad_total' => 15,
            'icono' => 'reductor-rv200.png'
        ],
        'RV200_SIN_FIN' => [
            'nombre' => 'REDUCTOR SIN FIN-CORONA',
            'cantidad_total' => 15,
            'icono' => 'reductor-sin-fin.png'
        ],
    ];
    
    // Línea seleccionada (por defecto la primera lavadora)
    $lineaSeleccionadaId = $request->input('linea_id', $lineasLavadora->first()->id ?? null);
    $lineaSeleccionada = $lineas->firstWhere('id', $lineaSeleccionadaId);
    
    // Tipo de máquina seleccionado (lavadora o pasteurizadora)
    $tipoSeleccionado = $request->input('tipo', 'lavadora');
    
    // Obtener componentes según la línea seleccionada
    $componentesPorLinea = $this->getComponentesPorLinea();
    $componentesLinea = $componentesPorLinea[$lineaSeleccionada->nombre] ?? [];
    
    // Calcular cantidad revisada por componente
    $estadisticas = [];
    $totalGeneral = 0;
    $revisadoGeneral = 0;
    
    foreach ($componentesLinea as $codigo => $nombre) {
        // Buscar en la configuración o usar valores por defecto
        $config = $componentesConfig[$codigo] ?? [
            'nombre' => $nombre,
            'cantidad_total' => 15
        ];
        
        // Obtener cantidad total de este componente en la línea
        $cantidadTotal = $config['cantidad_total'];
        
        // Calcular cuántos reductores tienen análisis para este componente
        $reductoresConAnalisis = AnalisisLavadora::where('linea_id', $lineaSeleccionadaId)
            ->whereHas('componente', function($q) use ($codigo) {
                $q->where('codigo', 'like', '%' . $codigo . '%');
            })
            ->distinct('reductor')
            ->count('reductor');
        
        // Limitar al máximo posible
        $cantidadRevisada = min($reductoresConAnalisis, $cantidadTotal);
        
        // Calcular porcentaje
        $porcentaje = $cantidadTotal > 0 ? round(($cantidadRevisada / $cantidadTotal) * 100, 1) : 0;
        
        // Determinar color según porcentaje
        if ($porcentaje >= 80) {
            $color = 'success'; // Verde
        } elseif ($porcentaje >= 50) {
            $color = 'info'; // Azul
        } elseif ($porcentaje >= 20) {
            $color = 'warning'; // Amarillo
        } else {
            $color = 'danger'; // Rojo
        }
        
        $estadisticas[$codigo] = [
            'nombre' => $config['nombre'],
            'cantidad_total' => $cantidadTotal,
            'cantidad_revisada' => $cantidadRevisada,
            'porcentaje' => $porcentaje,
            'color' => $color,
            'reductores_detectados' => $reductoresConAnalisis,
            'icono' => $config['icono'] ?? null
        ];
        
        $totalGeneral += $cantidadTotal;
        $revisadoGeneral += $cantidadRevisada;
    }
    
    // Ordenar por nombre
    uasort($estadisticas, function($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });
    
    // Calcular resumen general
    $resumen = [
        'total_general' => $totalGeneral,
        'revisado_general' => $revisadoGeneral,
        'porcentaje_general' => $totalGeneral > 0 ? round(($revisadoGeneral / $totalGeneral) * 100, 1) : 0
    ];
    
    return view('/historico-revisados.index', compact(
        'lineas',
        'lineasLavadora',
        'lineasPasteurizadora',
        'lineaSeleccionada',
        'tipoSeleccionado',
        'estadisticas',
        'resumen'
    ));
}
}