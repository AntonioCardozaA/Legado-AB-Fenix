<?php

namespace App\Http\Controllers;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

        // Filtrar análisis incompletos o pendientes si no son del día actual
        $hoy = now()->toDateString();
        $analisis = $analisis->filter(function($item) use ($hoy) {
            $fechaAnalisis = $item->fecha_analisis?->toDateString();

            // Si es del hoy, mostrar siempre
            if ($fechaAnalisis === $hoy) {
                return true;
            }

            // Si no es de hoy, solo mostrar si está "Cambiado" o "Buen estado"
            return in_array($item->estado, ['Cambiado', 'Buen estado']);
        });

        $totalAnalisis = $analisis->count();
        $totalDanados = $analisis->where('estado', 'Dañado - Requiere cambio')->count();
        $totalCambiados = $analisis->where('estado', 'Cambiado')->count();

        // Filtrar solo líneas de pasteurizadora (P-03 a P-14)
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineasFiltradas = $lineas->filter(function($linea) use ($pasteurizadorasPermitidas) {
            return in_array($linea->nombre, $pasteurizadorasPermitidas);
        })->values();

        $mostrarTodas = !request('linea_id') || request('linea_id') === 'todas';

        $seguimientoPasteurizadora = $this->buildSeguimientoPasteurizadora($lineasFiltradas);

        return view('pasteurizadora.analisis-pasteurizadora.index', compact(
            'analisis', 'lineasFiltradas', 'totalAnalisis', 'totalDanados', 'totalCambiados',
            'lineaSeleccionada', 'mostrarTodas', 'seguimientoPasteurizadora'
        ));
    }

        public function dashboard()
{
    $analisis = AnalisisPasteurizadora::with('linea')->latest()->take(10)->get();

    $total = AnalisisPasteurizadora::count();
    $danados = AnalisisPasteurizadora::where('estado', 'DaÃ±ado - Requiere cambio')->count();
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
            return redirect()->route('pasteurizadora.analisis-pasteurizadora.select-linea')
                ->with('error', 'Debe seleccionar una línea primero');
        }

        $linea = Linea::findOrFail($lineaId);
        return view('pasteurizadora.analisis-pasteurizadora.create', $this->buildCreateViewData($linea, $request));
    }

    public function createQuick(Request $request)
    {
        $linea = Linea::findOrFail($request->linea_id);

        return view('pasteurizadora.analisis-pasteurizadora.create-quick', $this->buildCreateViewData($linea, $request, true));
    }

    // ============================================================
    // STORE
    // ============================================================

 public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'modulo' => 'required|integer|min:1',
            'nivel' => 'required|in:SUPERIOR,INFERIOR',
            'componente' => 'required|string',
            'lado' => 'required|in:VAPOR,PASILLO',
            'fecha_analisis' => 'required|date',
            'numero_orden' => 'required|string|max:50',
            'estado' => 'required|in:' . implode(',', AnalisisPasteurizadora::ESTADOS),
            'actividad' => 'required|string',
            'evidencia_fotos' => 'nullable|array',
            'evidencia_fotos.*' => 'nullable|image|max:5120',
            'componentes_revisados' => 'nullable',
        ]);

        // Obtener la linea y validar la seleccion de componentes
        $linea = Linea::findOrFail($validated['linea_id']);
        $seleccionComponentes = $this->resolverSeleccionComponentesRevisados($request, $linea, $validated);
        $validated['componente'] = $seleccionComponentes['componente'];

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

        // Crear el registro
        $analisis = AnalisisPasteurizadora::create([
            'linea_id' => $validated['linea_id'],
            'modulo' => $validated['modulo'],
            'nivel' => $validated['nivel'],
            'componente' => $validated['componente'],
            'lado' => $validated['lado'],
            'fecha_analisis' => $validated['fecha_analisis'],
            'numero_orden' => $validated['numero_orden'],
            'estado' => $validated['estado'],
            'actividad' => $validated['actividad'],
            'evidencia_fotos' => $fotosPaths,
            'componentes_revisados' => $seleccionComponentes['componentes_revisados'],
            'cantidad_componentes_revisados' => count($seleccionComponentes['componentes_revisados']),
            'total_componentes' => $seleccionComponentes['total_componentes'],
            'brazos_torsion' => $seleccionComponentes['brazos_torsion'],
            'total_brazos_torsion' => $seleccionComponentes['total_brazos_torsion'],
            'resuelto_por_cambio' => false,
        ]);

        // Marcar como resuelto si es necesario
        if ($validated['estado'] === 'Cambiado') {
            $this->marcarRegistrosAnterioresComoResueltos($analisis);
        }

        $siguienteRevision = AnalisisPasteurizadora::getSiguienteRevisionContexto(
            $validated['linea_id'],
            $validated['modulo'],
            $validated['componente'],
            $validated['nivel'],
            $validated['lado']
        );

        $mensaje = $siguienteRevision
            ? 'Análisis registrado correctamente. Puedes continuar con la siguiente revisión pendiente desde el tablero principal.'
            : 'Análisis registrado correctamente. Este proceso quedó terminado.';

        // Redirigir al índice con mensaje de éxito
        return redirect()
            ->route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $validated['linea_id']])
            ->with('success', $mensaje);
    }

    public function storeQuick(Request $request)
    {
        // Marcar que proviene de create-quick
        $request->merge(['es_quick' => true]);
        return $this->store($request);
    }



    private function marcarRegistrosAnterioresComoResueltos($nuevoAnalisis)
    {
        $registrosAnteriores = AnalisisPasteurizadora::where('linea_id', $nuevoAnalisis->linea_id)
            ->where('modulo', $nuevoAnalisis->modulo)
            ->where('componente', $nuevoAnalisis->componente)
            ->where('estado', 'DaÃ±ado - Requiere cambio')
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
            'nivel' => 'nullable|in:SUPERIOR,INFERIOR',
            'fecha_analisis' => 'nullable|date',
            'numero_orden' => 'nullable|string',
            'actividad' => 'nullable|string',
            'estado' => 'nullable|string|in:' . implode(',', AnalisisPasteurizadora::ESTADOS),
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'componentes_revisados' => 'nullable|array',
        ]);

        $contextoActualizado = [
            'modulo' => $validated['modulo'] ?? $analisis->modulo,
            'componente' => $validated['componente'] ?? $analisis->componente,
            'nivel' => $validated['nivel'] ?? $analisis->nivel,
            'lado' => $validated['lado'] ?? $analisis->lado,
        ];

        $seleccionComponentes = $this->resolverSeleccionComponentesRevisados(
            $request,
            $analisis->linea,
            $contextoActualizado,
            $analisis
        );
        $validated['componente'] = $seleccionComponentes['componente'];

        if (isset($validated['estado']) && $validated['estado'] === 'Cambiado' && $analisis->estado !== 'Cambiado') {
            $tempAnalisis = (object)[
                'linea_id' => $analisis->linea_id,
                'modulo' => $validated['modulo'] ?? $analisis->modulo,
                'componente' => $validated['componente'] ?? $analisis->componente,
                'numero_orden' => $validated['numero_orden'] ?? $analisis->numero_orden,
            ];
            $this->marcarRegistrosAnterioresComoResueltos($tempAnalisis);
        }

        $componentesRevisados = $seleccionComponentes['componentes_revisados'];

        // Agregar componentes revisados a validated solo si hay valores
        if (!empty($componentesRevisados)) {
            $validated['componentes_revisados'] = $componentesRevisados;
            $validated['cantidad_componentes_revisados'] = count($componentesRevisados);
            $validated['total_componentes'] = $seleccionComponentes['total_componentes'];
            $validated['brazos_torsion'] = $seleccionComponentes['brazos_torsion'];
            $validated['total_brazos_torsion'] = $seleccionComponentes['total_brazos_torsion'];
        } elseif (isset($validated['componentes_revisados']) && empty($validated['componentes_revisados'])) {
            // Si enviaron array vacío, no incluir el campo
            unset($validated['componentes_revisados']);
        }

        $analisis->update($validated);

        return redirect()
            ->route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $analisis->linea_id])
            ->with('success', 'Análisis actualizado correctamente.');
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

        $analisis = $query->get();

        // Filtrar análisis incompletos o pendientes si no son del día actual
        $hoy = now()->toDateString();
        $analisis = $analisis->filter(function($item) use ($hoy) {
            $fechaAnalisis = $item->fecha_analisis?->toDateString();

            // Si es del hoy, mostrar siempre
            if ($fechaAnalisis === $hoy) {
                return true;
            }

            // Si no es de hoy, solo mostrar si está "Cambiado" o "Buen estado"
            return in_array($item->estado, ['Cambiado', 'Buen estado']);
        });

        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineas = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->orderBy('nombre')->get();

        return view('pasteurizadora.analisis-pasteurizadora.historial', compact('analisis', 'lineas'));
    }

    // ============================================================
    // PLAN DE ACCIÃ“N
    // ============================================================

    public function planAccion(Request $request)
    {
        $query = AnalisisPasteurizadora::where('estado', 'DaÃ±ado - Requiere cambio')
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
    // HISTÃ“RICO DE REVISADOS
    // ============================================================

    public function historicoRevisados(Request $request)
    {
        // Obtener todas las lÃ­neas de pasteurizadora (P-03 a P-14)
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
                        if (
                            AnalisisPasteurizadora::esBrazoTorsion($codigo)
                            && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                        ) {
                            continue;
                        }

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
                    if (
                        AnalisisPasteurizadora::esBrazoTorsion($codigo)
                        && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($lineaSeleccionada->nombre)
                    ) {
                        continue;
                    }

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

                $revisadas = $analisis->sum('cantidad_componentes_revisados');
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
    // MÃ‰TODOS AJAX
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

    public function getRemainingComponentsAjax(Request $request)
    {
        $lineaId = $request->get('linea_id');
        $modulo = $request->get('modulo');
        $componente = $request->get('componente');
        $lado = $request->get('lado');
        $nivel = $request->get('nivel');

        if (!$lineaId || !$modulo || !$componente) {
            return response()->json([
                'success' => false,
                'message' => 'ParÃ¡metros incompletos'
            ], 400);
        }

        $componentesPendientes = AnalisisPasteurizadora::getCantidadComponentesPendientes($lineaId, $modulo, $componente, $lado, $nivel);
        $cantidadComponentesRevisados = AnalisisPasteurizadora::getCantidadComponentesRevisados($lineaId, $modulo, $componente, $lado, $nivel);

        // Obtener los componentes ya revisados especÃ­ficos de este lado y nivel
        $componentesYaRevisados = AnalisisPasteurizadora::getComponentesYaRevisados($lineaId, $modulo, $componente, $lado, $nivel);

        // Obtener lados pendientes
        $ladosPendientes = AnalisisPasteurizadora::getLadosPendientes($lineaId, $modulo, $componente, $nivel);

        return response()->json([
            'success' => true,
            'componentes_pendientes' => $componentesPendientes,
            'cantidad_componentes_revisados' => $cantidadComponentesRevisados,
            'componentes_ya_revisados' => $componentesYaRevisados,
            'lados_pendientes' => $ladosPendientes,
        ]);
    }

    public function getRevisionContextAjax(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'modulo' => 'required|integer|min:1',
            'componente' => 'required|string',
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'nivel' => 'nullable|in:SUPERIOR,INFERIOR',
        ]);

        $linea = Linea::findOrFail($request->linea_id);
        $contexto = $this->resolveRevisionContext(
            $linea,
            $request->modulo,
            $request->componente,
            $request->lado,
            $request->nivel,
        );

        return response()->json([
            'success' => true,
            'modulo' => $request->modulo,
            'componente' => $contexto['componente'],
            'componente_key' => $contexto['componenteKey'],
            'nombre_componente' => $contexto['nombreComponente'],
            'total_componentes' => $contexto['totalComponentes'],
            'componentes_pendientes' => $contexto['componentesPendientes'],
            'cantidad_componentes_revisados' => $contexto['cantidadComponentesRevisados'],
            'componentes_ya_revisados' => $contexto['componentesYaRevisados'],
            'lados_pendientes' => $contexto['ladosPendientes'],
            'estado_revision' => $contexto['estadoRevision'],
            'nivel' => $contexto['nivel'],
            'lado' => $contexto['lado'],
            'siguiente_revision' => $contexto['siguienteRevision'],
        ]);
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
        // lÃ³gica futura
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
                'message' => count($creadas) . ' lÃ­neas creadas: ' . implode(', ', $creadas)
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
        $request = request();

        return view('pasteurizadora.analisis-pasteurizadora.create', $this->buildCreateViewData($linea, $request));
    }

    private function buildSeguimientoPasteurizadora($lineas): array
    {
        $lineas = collect($lineas)->filter();
        $lineaIds = $lineas->pluck('id')->all();

        if (empty($lineaIds)) {
            return [];
        }

        $registrosPorLinea = AnalisisPasteurizadora::whereIn('linea_id', $lineaIds)
            ->where('resuelto_por_cambio', false)
            ->get()
            ->groupBy('linea_id');

        $seguimiento = [];

        foreach ($lineas as $linea) {
            $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
            $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
            $registrosLinea = $registrosPorLinea->get($linea->id, collect());
            $modulos = [];
            $celdasTotales = 0;
            $celdasCompletadas = 0;

            for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
                $componentesModulo = 0;
                $componentesCompletados = 0;

                foreach ($componentes as $codigo => $config) {
                    if (
                        AnalisisPasteurizadora::esBrazoTorsion($codigo)
                        && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                    ) {
                        continue;
                    }

                    $totalComponentes = (int) ($config['cantidad'] ?? 0);
                    $estadoPorNivel = [];
                    $siguienteRevision = null;
                    $completado = $totalComponentes > 0;

                    foreach (AnalisisPasteurizadora::NIVELES as $nivel) {
                        $ladosPendientes = [];

                        foreach (AnalisisPasteurizadora::LADOS as $lado) {
                            $revisados = $registrosLinea
                                ->where('modulo', $modulo)
                                ->where('componente', $codigo)
                                ->where('nivel', $nivel)
                                ->where('lado', $lado)
                                ->flatMap(function ($registro) use ($totalComponentes) {
                                    $componentesRevisados = AnalisisPasteurizadora::normalizarComponentesRevisados(
                                        $registro->componentes_revisados,
                                        $totalComponentes
                                    );

                                    if (!empty($componentesRevisados)) {
                                        return $componentesRevisados;
                                    }

                                    $cantidad = min((int) ($registro->cantidad_componentes_revisados ?? 0), $totalComponentes);

                                    return $cantidad > 0 ? range(1, $cantidad) : [];
                                })
                                ->unique()
                                ->count();

                            if ($revisados < $totalComponentes) {
                                $ladosPendientes[] = $lado;
                                $completado = false;
                            }
                        }

                        $estadoPorNivel[$nivel] = [
                            'completado' => empty($ladosPendientes),
                            'lados_pendientes' => $ladosPendientes,
                        ];

                        if (!$siguienteRevision && !empty($ladosPendientes)) {
                            $siguienteRevision = [
                                'nivel' => $nivel,
                                'lado' => $ladosPendientes[0],
                            ];
                        }
                    }

                    $componentesModulo++;
                    $celdasTotales++;

                    if ($completado) {
                        $componentesCompletados++;
                        $celdasCompletadas++;
                    }

                    $seguimiento[$linea->id]['celdas'][$modulo][$codigo] = [
                        'completado' => $completado,
                        'estado_por_nivel' => $estadoPorNivel,
                        'siguiente_revision' => $siguienteRevision,
                    ];
                }

                $modulos[$modulo] = [
                    'total' => $componentesModulo,
                    'completados' => $componentesCompletados,
                    'completado' => $componentesModulo > 0 && $componentesCompletados === $componentesModulo,
                ];
            }

            $seguimiento[$linea->id]['resumen'] = [
                'total' => $celdasTotales,
                'completados' => $celdasCompletadas,
                'pendientes' => max(0, $celdasTotales - $celdasCompletadas),
                'porcentaje' => $celdasTotales > 0 ? round(($celdasCompletadas / $celdasTotales) * 100) : 0,
                'completado' => $celdasTotales > 0 && $celdasCompletadas === $celdasTotales,
            ];
            $seguimiento[$linea->id]['modulos'] = $modulos;
        }

        return $seguimiento;
    }

    private function buildCreateViewData(Linea $linea, Request $request, bool $modoQuick = false): array
    {
        return array_merge([
            'linea' => $linea,
            'fechaSugerida' => $request->get('fecha', date('Y-m-d')),
            'modoQuick' => $modoQuick,
        ], $this->resolveRevisionContext(
            $linea,
            $request->get('modulo'),
            $request->get('componente'),
            $request->get('lado'),
            $request->get('nivel'),
        ));
    }

    private function resolveRevisionContext(Linea $linea, $modulo, $componente, $lado, $nivel): array
    {
        $resolved = AnalisisPasteurizadora::resolveComponentePorLinea($linea->nombre, $componente);
        $componenteKey = $resolved['key'] ?? $componente;
        $componentConfig = $resolved['config'] ?? null;
        $siguienteRevision = null;
        $estadoRevision = [];
        $effectiveNivel = $nivel;
        $effectiveLado = $lado;
        $componentesPendientes = 0;
        $cantidadComponentesRevisados = 0;
        $componentesYaRevisados = [];
        $ladosPendientes = [];

        if ($modulo && $componenteKey && $componentConfig) {
            $estadoRevision = AnalisisPasteurizadora::getEstadoRevision($linea->id, $modulo, $componenteKey);
            $siguienteRevision = AnalisisPasteurizadora::getSiguienteRevisionContexto($linea->id, $modulo, $componenteKey, $nivel, $lado);
            $effectiveNivel = $effectiveNivel ?: ($siguienteRevision['nivel'] ?? null);
            $effectiveLado = $effectiveLado ?: ($siguienteRevision['lado'] ?? null);

            $componentesYaRevisados = AnalisisPasteurizadora::getComponentesYaRevisados(
                $linea->id,
                $modulo,
                $componenteKey,
                $effectiveLado,
                $effectiveNivel
            );
            $componentesPendientes = AnalisisPasteurizadora::getCantidadComponentesPendientes(
                $linea->id,
                $modulo,
                $componenteKey,
                $effectiveLado,
                $effectiveNivel
            );
            $cantidadComponentesRevisados = AnalisisPasteurizadora::getCantidadComponentesRevisados(
                $linea->id,
                $modulo,
                $componenteKey,
                $effectiveLado,
                $effectiveNivel
            );
            $ladosPendientes = $effectiveNivel
                ? AnalisisPasteurizadora::getLadosPendientes($linea->id, $modulo, $componenteKey, $effectiveNivel)
                : [];
        }

        return [
            'modulo' => $modulo,
            'componente' => $componenteKey,
            'componenteKey' => $componenteKey,
            'nivel' => $effectiveNivel,
            'lado' => $effectiveLado,
            'totalComponentes' => $componentConfig['cantidad'] ?? 0,
            'componentesPendientes' => $componentesPendientes,
            'cantidadComponentesRevisados' => $cantidadComponentesRevisados,
            'componentesYaRevisados' => $componentesYaRevisados,
            'ladosPendientes' => $ladosPendientes,
            'nombreComponente' => $componentConfig['nombre'] ?? $componente,
            'estadoRevision' => $estadoRevision,
            'siguienteRevision' => $siguienteRevision,
        ];
    }

    private function resolverSeleccionComponentesRevisados(
        Request $request,
        Linea $linea,
        array $contexto,
        ?AnalisisPasteurizadora $analisisActual = null
    ): array {
        $resolved = AnalisisPasteurizadora::resolveComponentePorLinea($linea->nombre, $contexto['componente'] ?? null);

        if (!$resolved) {
            throw ValidationException::withMessages([
                'componente' => 'Seleccione un componente valido para la linea seleccionada.',
            ]);
        }

        $componente = $resolved['key'];
        $totalComponentes = (int) ($resolved['config']['cantidad'] ?? 0);
        $modulo = (int) ($contexto['modulo'] ?? 0);
        $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);

        if ($modulo < 1 || $modulo > $totalModulos) {
            throw ValidationException::withMessages([
                'modulo' => 'Seleccione un modulo valido para la linea seleccionada.',
            ]);
        }

        if (AnalisisPasteurizadora::esBrazoTorsion($componente)) {
            $ultimoModuloConBrazo = AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre);

            if ($modulo < 1 || $modulo > $ultimoModuloConBrazo) {
                throw ValidationException::withMessages([
                    'modulo' => 'El Brazo de Torsion solo aplica del modulo 1 al ' . $ultimoModuloConBrazo . '. El ultimo modulo no tiene brazo.',
                ]);
            }
        }

        $componentesSeleccionados = AnalisisPasteurizadora::normalizarComponentesRevisados(
            $request->input('componentes_revisados'),
            $totalComponentes
        );

        if (AnalisisPasteurizadora::esBrazoTorsion($componente)) {
            $componentesSeleccionados = [1];
        }

        $componentesPendientes = AnalisisPasteurizadora::getComponentesPendientes(
            $linea->id,
            $contexto['modulo'],
            $componente,
            $contexto['lado'] ?? null,
            $contexto['nivel'] ?? null,
            $analisisActual?->id
        );

        if (empty($componentesPendientes)) {
            throw ValidationException::withMessages([
                'componentes_revisados' => 'Todos los componentes de esta seleccion ya fueron revisados.',
            ]);
        }

        if (empty($componentesSeleccionados)) {
            throw ValidationException::withMessages([
                'componentes_revisados' => 'Debe seleccionar al menos un componente revisado.',
            ]);
        }

        $seleccionInvalida = array_values(array_diff($componentesSeleccionados, $componentesPendientes));

        if (!empty($seleccionInvalida)) {
            throw ValidationException::withMessages([
                'componentes_revisados' => 'La seleccion incluye componentes ya revisados o fuera del rango permitido.',
            ]);
        }

        return [
            'componente' => $componente,
            'componentes_revisados' => $componentesSeleccionados,
            'total_componentes' => $totalComponentes,
            'brazos_torsion' => AnalisisPasteurizadora::esBrazoTorsion($componente) ? $componentesSeleccionados : null,
            'total_brazos_torsion' => AnalisisPasteurizadora::esBrazoTorsion($componente)
                ? AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                : null,
        ];
    }
}
