<?php

namespace App\Http\Controllers;


use App\Models\Analisis;
use App\Models\Linea;
use App\Models\Componente;
use App\Models\Categoria;
use App\Models\NumeroR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exports\AnalisisLavadoraExcel;
use Maatwebsite\Excel\Facades\Excel;

class AnalisisController extends Controller
{
    /**
     * LISTADO PRINCIPAL CON VISTA MATRIZ
     */
public function index(Request $request)
{
    // Obtener datos para filtros
    $lineas = Linea::orderBy('nombre')->get();
    $componentes = Componente::with('categoria')->orderBy('nombre')->get();
    $categorias = Categoria::orderBy('nombre')->get();

    // Construir consulta base - IMPORTANTE: usar AnalisisGeneral
    $query = Analisis::with(['linea', 'componente.categoria'])
        ->orderBy('linea_id')
        ->orderBy('reductor')
        ->orderBy('componente_id');

    // Aplicar filtros SI existen valores
    if ($request->filled('linea_id')) {
        $query->where('linea_id', $request->linea_id);
    }

    if ($request->filled('componente_id')) {
        $query->where('componente_id', $request->componente_id);
    }

    if ($request->filled('categoria_id')) {
        $query->whereHas('componente', function ($q) use ($request) {
            $q->where('categoria_id', $request->categoria_id);
        });
    }

    if ($request->filled('reductor')) {
        $query->where('reductor', 'like', '%' . $request->reductor . '%');
    }

    if ($request->filled('fecha')) {
        $query->whereYear('fecha_analisis', substr($request->fecha, 0, 4))
              ->whereMonth('fecha_analisis', substr($request->fecha, 5, 2));
    }

    // Obtener datos agrupados
    $analisis = $query->get();
    
    // Agrupar por lavadora
    $analisisAgrupados = $analisis->groupBy(function ($item) {
        return $item->linea ? $item->linea->nombre : 'Sin lavadora';
    });

    return view('analisis.index', compact(
        'analisisAgrupados',
        'lineas',
        'componentes',
        'categorias'
    ));
}

    /**
     * PASO 1: Seleccionar línea
     */
    public function seleccionarLinea()
    {
        $lineas = Linea::where('activo', true)->get();
        return view('analisis.seleccionar-linea', compact('lineas'));
    }

    /**
     * PASO 2: Seleccionar componente
     */
    public function seleccionarComponente($lineaId)
    {
        $linea = Linea::findOrFail($lineaId);
        $componentes = Componente::where('activo', true)->get();
        return view('analisis.seleccionar-componente', compact('linea', 'componentes'));
    }

    /**
     * PASO 3: Crear análisis
     */
    public function crear($lineaId, $componenteId)
    {
        $linea = Linea::findOrFail($lineaId);
        $componente = Componente::findOrFail($componenteId);
        $categorias = Categoria::where('activo', true)->get();

        return view('analisis.create', compact(
            'linea',
            'componente',
            'categorias'
        ));
    }

    /**
     * AJAX: Números R por categoría
     */
    public function getNumerosR($categoriaId)
    {
        return response()->json(
            NumeroR::where('categoria_id', $categoriaId)
                ->where('activo', true)
                ->orderBy('numero')
                ->get()
        );
    }

    /**
     * GUARDAR NUEVO ANÁLISIS
     */
    public function store(Request $request)
    {
        // Validación completa
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'componente_id' => 'required|exists:componentes,id',
            'fecha_analisis' => 'required|date',
            'numero_orden' => 'required|string|max:50',
            'actividad' => 'required|string',
            'categoria_id' => 'required|exists:categorias,id',
            'numero_r_id' => 'required|exists:numeros_r,id',
            'reductor' => 'required|string|max:20',
            'horometro' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string',
            'fotos.*' => 'nullable|image|max:5120', // 5MB máximo por foto
        ]);

        // Procesar fotos si existen
        $fotosPaths = [];
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('analisis-fotos', 'public');
                $fotosPaths[] = $path;
            }
        }

        // Crear análisis
        $analisis = Analisis::create([
            'linea_id' => $validated['linea_id'],
            'componente_id' => $validated['componente_id'],
            'fecha_analisis' => $validated['fecha_analisis'],
            'numero_orden' => $validated['numero_orden'],
            'actividad' => $validated['actividad'],
            'categoria_id' => $validated['categoria_id'],
            'numero_r_id' => $validated['numero_r_id'],
            'reductor' => $validated['reductor'],
            'horometro' => $validated['horometro'],
            'observaciones' => $validated['observaciones'],
            'fotos' => $fotosPaths,
            'usuario_id' => auth()->id(),
        ]);

        return redirect()->route('analisis.show', $analisis)
            ->with('success', 'Análisis creado exitosamente.');
    }

    /**
     * VER ANÁLISIS DETALLADO
     */
    public function show($id)
    {
        $analisis = Analisis::with(['linea', 'componente', 'categoria', 'numeroR', 'usuario'])
            ->findOrFail($id);

        return view('analisis.show', compact('analisis'));
    }

    /**
     * EDITAR ANÁLISIS
     */
    public function edit($id)
    {
        $analisis = Analisis::findOrFail($id);
        $lineas = Linea::where('activo', true)->get();
        $componentes = Componente::where('activo', true)->get();
        $categorias = Categoria::where('activo', true)->get();
        $numerosR = NumeroR::where('categoria_id', $analisis->categoria_id)->get();

        return view('analisis.edit', compact(
            'analisis',
            'lineas',
            'componentes',
            'categorias',
            'numerosR'
        ));
    }

    /**
     * ACTUALIZAR ANÁLISIS
     */
    public function update(Request $request, $id)
    {
        $analisis = Analisis::findOrFail($id);
        
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'componente_id' => 'required|exists:componentes,id',
            'fecha_analisis' => 'required|date',
            'numero_orden' => 'required|string|max:50',
            'actividad' => 'required|string',
            'categoria_id' => 'required|exists:categorias,id',
            'numero_r_id' => 'required|exists:numeros_r,id',
            'reductor' => 'required|string|max:20',
            'horometro' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string',
            'fotos.*' => 'nullable|image|max:5120',
        ]);

        // Procesar nuevas fotos
        $nuevasFotos = $analisis->fotos ?? [];
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('analisis-fotos', 'public');
                $nuevasFotos[] = $path;
            }
        }

        // Actualizar análisis
        $analisis->update([
            'linea_id' => $validated['linea_id'],
            'componente_id' => $validated['componente_id'],
            'fecha_analisis' => $validated['fecha_analisis'],
            'numero_orden' => $validated['numero_orden'],
            'actividad' => $validated['actividad'],
            'categoria_id' => $validated['categoria_id'],
            'numero_r_id' => $validated['numero_r_id'],
            'reductor' => $validated['reductor'],
            'horometro' => $validated['horometro'],
            'observaciones' => $validated['observaciones'],
            'fotos' => $nuevasFotos,
        ]);

        return redirect()->route('analisis.show', $analisis)
            ->with('success', 'Análisis actualizado exitosamente.');
    }

    /**
     * ELIMINAR ANÁLISIS
     */
    public function destroy($id)
    {
        $analisis = Analisis::findOrFail($id);
        
        // Eliminar fotos almacenadas
        if (!empty($analisis->fotos) && is_array($analisis->fotos)) {
            foreach ($analisis->fotos as $foto) {
                if (Storage::disk('public')->exists($foto)) {
                    Storage::disk('public')->delete($foto);
                }
            }
        }

        $analisis->delete();

        return redirect()->route('analisis.index')
            ->with('success', 'Análisis eliminado exitosamente.');
    }

    /**
     * ELIMINAR FOTO ESPECÍFICA
     */
    public function eliminarFoto(Request $request, $id)
    {
        $analisis = Analisis::findOrFail($id);
        $fotoIndex = $request->input('foto_index');
        
        if (isset($analisis->fotos[$fotoIndex])) {
            $fotoPath = $analisis->fotos[$fotoIndex];
            
            // Eliminar del storage
            if (Storage::disk('public')->exists($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }
            
            // Eliminar del array
            $fotos = $analisis->fotos;
            unset($fotos[$fotoIndex]);
            $analisis->fotos = array_values($fotos); // Reindexar
            $analisis->save();
            
            return back()->with('success', 'Foto eliminada exitosamente.');
        }
        
        return back()->with('error', 'Foto no encontrada.');
    }

    /**
     * PDF INDIVIDUAL
     */
    public function exportPdf($id)
    {
        $analisis = Analisis::with(['linea', 'componente', 'categoria', 'numeroR'])
            ->findOrFail($id);

        return view('analisis.pdf', compact('analisis'));
    }

    /**
     * ESTADÍSTICAS
     */
    public function estadisticas()
    {
        $data = [
            'totalAnalisis' => Analisis::count(),
            'analisisPorLinea' => Analisis::with('linea')
                ->selectRaw('linea_id, COUNT(*) as total')
                ->groupBy('linea_id')
                ->get()
                ->map(function($item) {
                    return [
                        'linea' => $item->linea->nombre ?? 'Sin lavadora',
                        'total' => $item->total,
                    ];
                }),
            'analisisPorComponente' => Analisis::with('componente')
                ->selectRaw('componente_id, COUNT(*) as total')
                ->groupBy('componente_id')
                ->get()
                ->map(function($item) {
                    return [
                        'componente' => $item->componente->nombre ?? 'Sin componente',
                        'total' => $item->total,
                    ];
                }),
            'analisisPorCategoria' => Analisis::with('categoria')
                ->selectRaw('categoria_id, COUNT(*) as total')
                ->groupBy('categoria_id')
                ->get()
                ->map(function($item) {
                    return [
                        'categoria' => $item->categoria->nombre ?? 'Sin categoría',
                        'total' => $item->total,
                    ];
                }),
        ];

        return view('analisis.estadisticas', $data);
    }

    /**
     * EXPORTAR EXCEL CON FILTROS
     */
    public function exportarExcel(Request $request)
    {
        $lavadora = $request->query('lavadora', 'TODAS');
        $filtros = $request->query();
        
        return Excel::download(
            new AnalisisLavadoraExcel($lavadora, $filtros),
            "ANALISIS_LAVADORA_{$lavadora}_" . date('Ymd_His') . ".xlsx"
        );
    }

    /**
     * EXPORTAR TODAS LAS LAVADORAS
     */
    public function exportarTodas()
    {
        return Excel::download(
            new AnalisisLavadoraExcel('TODAS'),
            'ANALISIS_TODAS_LAVADORAS_' . date('Ymd_His') . ".xlsx"
        );
    }

    /**
     * ANÁLISIS POR LÍNEA ESPECÍFICA
     */
    public function porLinea($lineaId)
    {
        $linea = Linea::findOrFail($lineaId);
        $analisis = Analisis::where('linea_id', $lineaId)
            ->with(['componente', 'categoria', 'numeroR', 'usuario'])
            ->orderBy('fecha_analisis', 'desc')
            ->paginate(20);

        return view('analisis.por-linea', compact('linea', 'analisis'));
    }

    /**
     * AJAX: Componentes por línea
     */
    public function getComponentes($lineaId)
    {
        $componentes = Analisis::where('linea_id', $lineaId)
            ->with('componente')
            ->whereNotNull('componente_id')
            ->select('componente_id')
            ->distinct()
            ->get()
            ->pluck('componente')
            ->filter()
            ->unique('id')
            ->values();

        return response()->json($componentes);
    }

    /**
     * AJAX: Reductores por componente
     */
    public function getReductores($componenteId)
    {
        $reductores = Analisis::where('componente_id', $componenteId)
            ->whereNotNull('reductor')
            ->select('reductor')
            ->distinct()
            ->orderBy('reductor')
            ->get()
            ->pluck('reductor');

        return response()->json($reductores);
    }
}