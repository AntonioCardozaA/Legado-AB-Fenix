<?php

namespace App\Http\Controllers;

use App\Models\AnalisisEtiquetadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Services\AnalysisDeletionLogger;
use App\Support\EtiquetadoraCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AnalisisEtiquetadoraController extends Controller
{
    private const EVIDENCIA_FOTOS_PATH = 'analisis-evidencias';

    public function index(Request $request)
    {
        $lineas = $this->lineasEtiquetadora();
        $lineaSeleccionada = $request->filled('linea_id') && $request->linea_id !== 'todas'
            ? $lineas->firstWhere('id', (int) $request->linea_id)
            : null;

        $catalogoQuery = $this->catalogoBase();
        $analisisQuery = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at');

        if ($lineaSeleccionada) {
            $catalogoQuery->where('linea', $lineaSeleccionada->nombre);
            $analisisQuery->where('linea_id', $lineaSeleccionada->id);
        } else {
            $analisisQuery->whereIn('linea_id', $lineas->pluck('id'));
        }

        if ($request->filled('maquina')) {
            $maquinaLabel = EtiquetadoraCatalog::maquinaLabel($request->maquina);
            $catalogoQuery->where('reductor', $maquinaLabel);
            $analisisQuery->where('maquina', strtoupper($request->maquina));
        }

        if ($request->filled('grupo')) {
            $catalogoQuery->where('grupo', $request->grupo);
            $analisisQuery->whereHas('componente', fn ($query) => $query->where('grupo', $request->grupo));
        }

        if ($request->filled('componente_id')) {
            $catalogoQuery->whereKey($request->componente_id);
            $analisisQuery->where('componente_id', $request->componente_id);
        }

        if ($request->filled('estado')) {
            $analisisQuery->where('estado', $request->estado);
        }

        if ($request->filled('fecha')) {
            $analisisQuery->whereMonth('fecha_analisis', substr($request->fecha, 5, 2))
                ->whereYear('fecha_analisis', substr($request->fecha, 0, 4));
        }

        if ($request->filled('componente') && !$request->filled('componente_id')) {
            $catalogoQuery->where('nombre', 'like', '%' . $request->componente . '%');
            $analisisQuery->whereHas('componente', function ($query) use ($request): void {
                $query->where('nombre', 'like', '%' . $request->componente . '%')
                    ->orWhere('codigo', 'like', '%' . $request->componente . '%');
            });
        }

        $catalogo = $catalogoQuery->get();
        $analisis = $analisisQuery->get();

        $ultimos = AnalisisEtiquetadora::ultimosPorComponente()
            ->with(['linea', 'componente', 'usuario'])
            ->whereIn('linea_id', $lineas->pluck('id'))
            ->when($lineaSeleccionada, fn ($query) => $query->where('linea_id', $lineaSeleccionada->id))
            ->when($request->filled('maquina'), fn ($query) => $query->where('maquina', strtoupper($request->maquina)))
            ->when($request->filled('grupo'), fn ($query) => $query->whereHas('componente', fn ($subQuery) => $subQuery->where('grupo', $request->grupo)))
            ->when($request->filled('componente_id'), fn ($query) => $query->where('componente_id', $request->componente_id))
            ->when($request->filled('componente') && !$request->filled('componente_id'), function ($query) use ($request): void {
                $query->whereHas('componente', function ($subQuery) use ($request): void {
                    $subQuery->where('nombre', 'like', '%' . $request->componente . '%')
                        ->orWhere('codigo', 'like', '%' . $request->componente . '%');
                });
            })
            ->get()
            ->keyBy('componente_id');

        $estadisticas = $this->estadisticas($catalogo, $ultimos);
        $matriz = $this->matrizCatalogo($catalogo, $ultimos);
        $tablaLineas = $this->tablaIndustrial($catalogo, $analisis, $lineas, $request->input('maquina'));
        $estadoModalItems = $this->itemsPorEstado($ultimos->values());
        $openAnalysisData = $this->modalPayloadForAnalysisId($request->input('open_analysis_id'));

        return view('etiquetadora.analisis-etiquetadora.index', [
            'lineas' => $lineas,
            'lineaSeleccionada' => $lineaSeleccionada,
            'maquinas' => EtiquetadoraCatalog::maquinas(),
            'grupos' => $this->gruposCatalogo(),
            'todosComponentes' => $this->componentesFiltroCatalogo(),
            'catalogo' => $catalogo,
            'matriz' => $matriz,
            'tablaLineas' => $tablaLineas,
            'ultimos' => $ultimos,
            'analisis' => $analisis,
            'estadisticas' => $estadisticas,
            'estadoModalItems' => $estadoModalItems,
            'openAnalysisData' => $openAnalysisData,
            'canDeleteAnalysis' => $request->user()?->canDeleteAnalysis() ?? false,
            'filtros' => $request->all(),
        ]);
    }

    public function selectLinea()
    {
        $lineas = $this->lineasEtiquetadora();

        return view('etiquetadora.analisis-etiquetadora.select-linea', compact('lineas'));
    }

    public function createWithLinea(Request $request, int $linea)
    {
        $linea = Linea::findOrFail($linea);
        abort_unless(in_array($linea->nombre, EtiquetadoraCatalog::lineas(), true), 404);

        $maquinaSeleccionada = strtoupper((string) $request->query('maquina', ''));
        $componenteSeleccionado = $request->query('componente_id');
        $componentes = $this->catalogoBase()
            ->where('linea', $linea->nombre)
            ->get()
            ->groupBy('reductor');

        return view('etiquetadora.analisis-etiquetadora.create', [
            'linea' => $linea,
            'componentesPorMaquina' => $componentes,
            'maquinas' => EtiquetadoraCatalog::maquinas(),
            'maquinaSeleccionada' => $maquinaSeleccionada,
            'componenteSeleccionado' => $componenteSeleccionado,
            'analisis' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $linea = Linea::findOrFail($request->linea_id);
        $componente = $this->validarComponenteCatalogo($linea, $request->componente_id, $request->maquina);

        if (!$componente) {
            return back()
                ->withErrors(['componente_id' => 'El componente no pertenece a la linea y maquina seleccionadas.'])
                ->withInput();
        }

        $analisis = DB::transaction(function () use ($request, $linea, $componente) {
            $analisis = AnalisisEtiquetadora::create([
                'linea_id' => $linea->id,
                'componente_id' => $componente->id,
                'reductor' => EtiquetadoraCatalog::maquinaLabel($request->maquina),
                'maquina' => strtoupper($request->maquina),
                'fecha_analisis' => $request->fecha_analisis,
                'numero_orden' => $request->numero_orden,
                'estado' => $request->estado,
                'actividad' => $request->actividad,
                'usuario_id' => $request->user()?->id,
                'evidencia_fotos' => [],
            ]);

            if ($request->hasFile('evidencia_fotos')) {
                $analisis->update([
                    'evidencia_fotos' => $this->guardarEvidenciasFotograficas($request->file('evidencia_fotos', [])),
                ]);
            }

            return $analisis;
        });

        return redirect()
            ->route('analisis-etiquetadora.index', [
                'linea_id' => $linea->id,
                'maquina' => $analisis->maquina,
            ])
            ->with('success', 'Analisis de Etiquetadora registrado correctamente.');
    }

    public function edit(AnalisisEtiquetadora $analisisetiquetadora)
    {
        $analisisetiquetadora->load(['linea', 'componente', 'usuario']);
        $componentes = $this->catalogoBase()
            ->where('linea', $analisisetiquetadora->linea?->nombre)
            ->where('reductor', EtiquetadoraCatalog::maquinaLabel($analisisetiquetadora->maquina))
            ->get()
            ->groupBy('reductor');

        return view('etiquetadora.analisis-etiquetadora.edit', [
            'analisis' => $analisisetiquetadora,
            'linea' => $analisisetiquetadora->linea,
            'componentesPorMaquina' => $componentes,
            'maquinas' => EtiquetadoraCatalog::maquinas(),
            'maquinaSeleccionada' => $analisisetiquetadora->maquina,
            'componenteSeleccionado' => $analisisetiquetadora->componente_id,
            'puedeEditarFechaAnalisis' => $this->puedeEditarFechaAnalisis(auth()->user()),
        ]);
    }

    public function update(Request $request, AnalisisEtiquetadora $analisisetiquetadora)
    {
        $rules = $this->rules();
        $rules['fecha_analisis'] = ['required', 'date', 'date_format:Y-m-d'];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $linea = Linea::findOrFail($request->linea_id);
        $componente = $this->validarComponenteCatalogo($linea, $request->componente_id, $request->maquina);

        if (!$componente) {
            return back()
                ->withErrors(['componente_id' => 'El componente no pertenece a la linea y maquina seleccionadas.'])
                ->withInput();
        }

        $fechaAnterior = $analisisetiquetadora->fecha_analisis?->toDateString();
        $fechaNueva = Carbon::createFromFormat('Y-m-d', $request->input('fecha_analisis'))->toDateString();

        if ($fechaAnterior !== $fechaNueva && !$this->puedeEditarFechaAnalisis($request->user())) {
            abort(403, 'No tienes permiso para modificar la fecha del analisis.');
        }

        $fotos = $analisisetiquetadora->evidencia_fotos ?? [];

        if (!is_array($fotos)) {
            $fotos = json_decode($fotos ?? '[]', true) ?? [];
        }

        if ($request->filled('eliminar_fotos')) {
            foreach ($request->eliminar_fotos as $index) {
                if (isset($fotos[$index])) {
                    Storage::disk('public')->delete($fotos[$index]);
                    $rutaPublica = public_path('storage/' . $fotos[$index]);
                    if (file_exists($rutaPublica)) {
                        @unlink($rutaPublica);
                    }
                    unset($fotos[$index]);
                }
            }
            $fotos = array_values($fotos);
        }

        if ($request->hasFile('evidencia_fotos')) {
            $fotos = array_merge(
                $fotos,
                $this->guardarEvidenciasFotograficas($request->file('evidencia_fotos', []))
            );
        }

        $analisisetiquetadora->update([
            'linea_id' => $linea->id,
            'componente_id' => $componente->id,
            'reductor' => EtiquetadoraCatalog::maquinaLabel($request->maquina),
            'maquina' => strtoupper($request->maquina),
            'fecha_analisis' => $fechaNueva,
            'numero_orden' => $request->numero_orden,
            'estado' => $request->estado,
            'actividad' => $request->actividad,
            'evidencia_fotos' => $fotos,
        ]);

        return redirect()
            ->route('analisis-etiquetadora.show', $analisisetiquetadora)
            ->with('success', 'Analisis de Etiquetadora actualizado correctamente.');
    }

    public function show(AnalisisEtiquetadora $analisisetiquetadora)
    {
        $analisisetiquetadora->load(['linea', 'componente', 'usuario']);

        return view('etiquetadora.analisis-etiquetadora.show', [
            'analisis' => $analisisetiquetadora,
        ]);
    }

    public function historial(Request $request)
    {
        $request->validate([
            'linea_id' => 'nullable|exists:lineas,id',
            'componente_id' => 'nullable|exists:componentes,id',
            'maquina' => ['nullable', Rule::in(EtiquetadoraCatalog::maquinas())],
        ]);

        $query = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at');

        if ($request->filled('linea_id')) {
            $query->where('linea_id', $request->linea_id);
        }

        if ($request->filled('componente_id')) {
            $query->where('componente_id', $request->componente_id);
        }

        if ($request->filled('maquina')) {
            $query->where('maquina', strtoupper($request->maquina));
        }

        return view('etiquetadora.analisis-etiquetadora.historial', [
            'analisis' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function destroy(Request $request, AnalisisEtiquetadora $analisisetiquetadora)
    {
        abort_unless($request->user()?->canDeleteAnalysis(), 403, 'No tienes permiso para eliminar analisis.');

        $analisisetiquetadora->loadMissing(['linea', 'componente']);

        app(AnalysisDeletionLogger::class)->log($request->user(), $analisisetiquetadora, 'etiquetadora', 'Analisis Etiquetadora', [
            'componente' => $analisisetiquetadora->componente?->nombre,
            'componente_codigo' => $analisisetiquetadora->componente?->codigo,
            'grupo' => $analisisetiquetadora->componente?->grupo,
            'mecanismo' => $analisisetiquetadora->componente?->mecanismo,
            'maquina' => $analisisetiquetadora->maquina,
            'estado' => $analisisetiquetadora->estado,
            'numero_orden' => $analisisetiquetadora->numero_orden,
            'fecha_analisis' => $analisisetiquetadora->fecha_analisis?->toDateString(),
        ]);

        foreach (($analisisetiquetadora->evidencia_fotos ?? []) as $foto) {
            Storage::disk('public')->delete($foto);
            $rutaPublica = public_path('storage/' . $foto);
            if (file_exists($rutaPublica)) {
                @unlink($rutaPublica);
            }
        }

        $lineaId = $analisisetiquetadora->linea_id;
        $maquina = $analisisetiquetadora->maquina;
        $analisisetiquetadora->delete();

        return redirect()
            ->route('analisis-etiquetadora.index', ['linea_id' => $lineaId, 'maquina' => $maquina])
            ->with('success', 'Analisis de Etiquetadora eliminado.');
    }

    public function deleteFoto(AnalisisEtiquetadora $analisisetiquetadora, int $fotoIndex)
    {
        $fotos = $analisisetiquetadora->evidencia_fotos ?? [];

        if (isset($fotos[$fotoIndex])) {
            Storage::disk('public')->delete($fotos[$fotoIndex]);
            $rutaPublica = public_path('storage/' . $fotos[$fotoIndex]);
            if (file_exists($rutaPublica)) {
                @unlink($rutaPublica);
            }

            unset($fotos[$fotoIndex]);
            $analisisetiquetadora->update(['evidencia_fotos' => array_values($fotos)]);

            return back()->with('success', 'Foto eliminada correctamente.');
        }

        return back()->with('error', 'Foto no encontrada.');
    }

    public function apiGetComponentes(Request $request, Linea $linea)
    {
        abort_unless(in_array($linea->nombre, EtiquetadoraCatalog::lineas(), true), 404);

        $maquina = $request->filled('maquina') ? strtoupper((string) $request->maquina) : null;

        $componentes = $this->catalogoBase()
            ->where('linea', $linea->nombre)
            ->when($maquina, fn ($query) => $query->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina)))
            ->get()
            ->map(fn (Componente $componente) => [
                'id' => $componente->id,
                'codigo' => $componente->codigo,
                'nombre' => $componente->nombre,
                'grupo' => $componente->grupo,
                'mecanismo' => $componente->mecanismo,
                'maquina' => $componente->reductor,
                'cantidad' => $componente->cantidad_total,
                'cantidad_original' => $componente->cantidad_original,
            ])
            ->values();

        return response()->json($componentes);
    }

    public function apiGetEstadisticas(Request $request, Linea $linea)
    {
        abort_unless(in_array($linea->nombre, EtiquetadoraCatalog::lineas(), true), 404);

        $maquina = $request->filled('maquina') ? strtoupper((string) $request->maquina) : null;
        $catalogo = $this->catalogoBase()
            ->where('linea', $linea->nombre)
            ->when($maquina, fn ($query) => $query->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina)))
            ->get();

        $ultimos = AnalisisEtiquetadora::ultimosPorComponente()
            ->where('linea_id', $linea->id)
            ->when($maquina, fn ($query) => $query->where('maquina', $maquina))
            ->get()
            ->keyBy('componente_id');

        return response()->json([
            'linea' => $linea->nombre,
            'maquina' => $maquina,
            'estadisticas' => $this->estadisticas($catalogo, $ultimos),
        ]);
    }

    private function modalPayloadForAnalysisId(mixed $id): ?array
    {
        if (blank($id)) {
            return null;
        }

        $registro = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->find($id);

        if (!$registro) {
            return null;
        }

        $imagenes = $registro->evidencia_fotos ?? [];

        if (is_string($imagenes)) {
            $imagenes = json_decode($imagenes, true) ?? [];
        }

        if (!is_array($imagenes)) {
            $imagenes = [];
        }

        $totalHistorial = AnalisisEtiquetadora::query()
            ->where('linea_id', $registro->linea_id)
            ->where('componente_id', $registro->componente_id)
            ->where('maquina', $registro->maquina)
            ->count();

        $canDeleteAnalysis = auth()->user()?->canDeleteAnalysis() ?? false;

        return [
            'id' => $registro->id,
            'linea' => $registro->linea->nombre ?? 'Linea no registrada',
            'componente' => $registro->componente->nombre ?? 'Componente no registrado',
            'componente_codigo' => $registro->componente->codigo ?? $registro->componente_id,
            'reductor' => $registro->reductor ?: EtiquetadoraCatalog::maquinaLabel((string) $registro->maquina),
            'maquina' => $registro->maquina,
            'lado' => $registro->lado ?? null,
            'fecha_analisis' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : '',
            'numero_orden' => $registro->numero_orden,
            'estado' => $registro->estado ?? AnalisisEtiquetadora::ESTADO_BUENO,
            'usuario_nombre' => $registro->usuario?->name ?? 'Usuario no registrado',
            'actividad' => $registro->actividad,
            'imagenes' => $imagenes,
            'color' => $this->analysisCellColor($registro->estado ?? null),
            'created_at' => $registro->created_at ? $registro->created_at->format('d/m/Y H:i') : '',
            'updated_at' => $registro->updated_at ? $registro->updated_at->format('d/m/Y H:i') : '',
            'is_new' => $registro->created_at ? $registro->created_at->gt(now()->subDays(3)) : false,
            'total_historial' => $totalHistorial,
            'edit_url' => route('analisis-etiquetadora.edit', ['analisisetiquetadora' => $registro->id], false),
            'delete_url' => $canDeleteAnalysis ? route('analisis-etiquetadora.destroy', ['analisisetiquetadora' => $registro->id], false) : null,
            'historial_url' => route('analisis-etiquetadora.historial', [
                'linea_id' => $registro->linea_id,
                'componente_id' => $registro->componente_id,
                'maquina' => $registro->maquina,
            ], false),
        ];
    }

    private function tablaIndustrial($catalogo, $analisis, $lineas, mixed $maquinaFiltro): array
    {
        $maquinas = collect(EtiquetadoraCatalog::maquinas())
            ->map(fn ($maquina) => strtoupper((string) $maquina))
            ->when(filled($maquinaFiltro), fn ($items) => $items->filter(fn ($maquina) => $maquina === strtoupper((string) $maquinaFiltro)))
            ->values();

        $lineasPorNombre = $lineas->keyBy('nombre');
        $analisisPorLinea = $analisis->groupBy('linea_id');

        return $catalogo
            ->groupBy('linea')
            ->map(function ($catalogoLinea, string $lineaNombre) use ($lineasPorNombre, $analisisPorLinea, $maquinas): array {
                $linea = $lineasPorNombre->get($lineaNombre);

                $componentes = $catalogoLinea
                    ->groupBy(fn (Componente $componente) => $this->componenteTablaKey($componente))
                    ->map(function ($items, string $key): array {
                        /** @var Componente $first */
                        $first = $items->first();
                        $porMaquina = [];

                        foreach ($items as $componente) {
                            $maquina = $this->maquinaDesdeEtiqueta($componente->reductor);

                            if ($maquina !== '') {
                                $porMaquina[$maquina] = $componente;
                            }
                        }

                        return [
                            'key' => $key,
                            'nombre' => $first->nombre,
                            'codigo' => $first->codigo,
                            'grupo' => $first->grupo,
                            'mecanismo' => $first->mecanismo,
                            'cantidad_total' => $first->cantidad_total,
                            'cantidad_original' => $first->cantidad_original,
                            'por_maquina' => $porMaquina,
                        ];
                    })
                    ->sortBy(fn (array $componente) => ($componente['grupo'] ?? '') . ' ' . ($componente['nombre'] ?? ''))
                    ->values();

                $registros = [];
                $analisisLinea = $linea ? collect($analisisPorLinea->get($linea->id, collect())) : collect();

                foreach ($analisisLinea as $registro) {
                    if (!$registro->componente) {
                        continue;
                    }

                    $maquina = strtoupper((string) ($registro->maquina ?: $this->maquinaDesdeEtiqueta($registro->reductor)));

                    $registros[$maquina][$registro->componente_id] ??= collect();
                    $registros[$maquina][$registro->componente_id]->push($registro);
                }

                $conteosComponentes = [];
                $conteosMaquinas = [];
                $resumenEstados = [
                    'buen_estado' => 0,
                    'requiere_revision' => 0,
                    'desgaste' => 0,
                    'danado' => 0,
                    'cambiado' => 0,
                    'sin_datos' => 0,
                ];

                foreach ($componentes as $componente) {
                    $maquinasConComponente = collect($componente['por_maquina'] ?? [])
                        ->keys()
                        ->filter(fn ($maquina) => $maquinas->contains($maquina))
                        ->count();

                    $conteosComponentes[$componente['key']] = [
                        'ok' => 0,
                        'review' => 0,
                        'warning' => 0,
                        'danger' => 0,
                        'changed' => 0,
                        'empty' => $maquinasConComponente,
                    ];
                }

                $totalCeldas = 0;

                foreach ($maquinas as $maquina) {
                    $conteosMaquinas[$maquina] = [
                        'total' => 0,
                        'total_posibles' => 0,
                        'ok' => 0,
                        'review' => 0,
                        'warning' => 0,
                        'danger' => 0,
                        'changed' => 0,
                    ];

                    foreach ($componentes as $componente) {
                        $componentForMachine = $componente['por_maquina'][$maquina] ?? null;

                        if (!$componentForMachine) {
                            continue;
                        }

                        $totalCeldas++;
                        $conteosMaquinas[$maquina]['total_posibles']++;

                        $celda = collect($registros[$maquina][$componentForMachine->id] ?? []);
                        $registro = $celda->first();

                        if (!$registro) {
                            $resumenEstados['sin_datos']++;
                            continue;
                        }

                        $bucket = $this->estadoBucket($registro->estado);
                        $conteosMaquinas[$maquina]['total']++;

                        match ($bucket) {
                            'cambiado' => $conteosMaquinas[$maquina]['changed']++,
                            'danado' => $conteosMaquinas[$maquina]['danger']++,
                            'requiere_revision' => $conteosMaquinas[$maquina]['review']++,
                            'desgaste' => $conteosMaquinas[$maquina]['warning']++,
                            default => $conteosMaquinas[$maquina]['ok']++,
                        };

                        match ($bucket) {
                            'cambiado' => $conteosComponentes[$componente['key']]['changed']++,
                            'danado' => $conteosComponentes[$componente['key']]['danger']++,
                            'requiere_revision' => $conteosComponentes[$componente['key']]['review']++,
                            'desgaste' => $conteosComponentes[$componente['key']]['warning']++,
                            default => $conteosComponentes[$componente['key']]['ok']++,
                        };

                        $conteosComponentes[$componente['key']]['empty']--;
                        $resumenEstados[$bucket === 'danado' ? 'danado' : $bucket]++;
                    }
                }

                $celdasConDatos = max($totalCeldas - $resumenEstados['sin_datos'], 0);

                return [
                    'linea' => $linea,
                    'linea_nombre' => $lineaNombre,
                    'componentes' => $componentes,
                    'maquinas' => $maquinas,
                    'registros' => $registros,
                    'conteos_componentes' => $conteosComponentes,
                    'conteos_maquinas' => $conteosMaquinas,
                    'resumen_estados' => $resumenEstados,
                    'total_celdas' => $totalCeldas,
                    'celdas_con_datos' => $celdasConDatos,
                    'analisis_count' => $analisisLinea->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function itemsPorEstado($registros): array
    {
        $items = [
            'total' => [],
            'buen_estado' => [],
            'requiere_revision' => [],
            'desgaste' => [],
            'danado' => [],
            'cambiado' => [],
        ];

        foreach ($registros as $registro) {
            $item = [
                'id' => $registro->id,
                'linea' => $registro->linea->nombre ?? 'Sin linea',
                'componente' => $registro->componente->nombre ?? 'Sin componente',
                'reductor' => $registro->reductor ?: EtiquetadoraCatalog::maquinaLabel((string) $registro->maquina),
                'maquina' => $registro->maquina,
                'estado' => $registro->estado,
                'fecha' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : '-',
            ];

            $bucket = $this->estadoBucket($registro->estado);
            $items['total'][] = $item;
            $items[$bucket === 'danado' ? 'danado' : $bucket][] = $item;
        }

        return $items;
    }

    private function componentesFiltroCatalogo()
    {
        return Componente::query()
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->whereNotNull('nombre')
            ->select('nombre')
            ->distinct()
            ->orderBy('nombre')
            ->pluck('nombre', 'nombre');
    }

    private function componenteTablaKey(Componente $componente): string
    {
        return sha1(implode('|', [
            trim((string) $componente->grupo),
            trim((string) $componente->mecanismo),
            trim((string) $componente->nombre),
        ]));
    }

    private function maquinaDesdeEtiqueta(?string $etiqueta): string
    {
        $valor = strtoupper(trim((string) $etiqueta));

        foreach (EtiquetadoraCatalog::maquinas() as $maquina) {
            $maquina = strtoupper((string) $maquina);

            if ($valor === $maquina || str_ends_with($valor, $maquina)) {
                return $maquina;
            }
        }

        return '';
    }

    private function estadoBucket(?string $estado): string
    {
        if (AnalisisEtiquetadora::esEstadoCambiado($estado)) {
            return 'cambiado';
        }

        if (AnalisisEtiquetadora::esEstadoDanado($estado)) {
            return 'danado';
        }

        if (AnalisisEtiquetadora::esEstadoRequiereRevision($estado)) {
            return 'requiere_revision';
        }

        if (AnalisisEtiquetadora::esEstadoDesgaste($estado)) {
            return 'desgaste';
        }

        return 'buen_estado';
    }

    private function analysisCellColor(?string $estado): string
    {
        return match ($this->estadoBucket($estado)) {
            'cambiado' => 'cell-changed',
            'danado' => 'cell-danger',
            'requiere_revision' => 'cell-review',
            'desgaste' => 'cell-warning',
            default => 'cell-ok',
        };
    }

    private function rules(): array
    {
        return [
            'linea_id' => ['required', 'exists:lineas,id'],
            'componente_id' => ['required', 'exists:componentes,id'],
            'maquina' => ['required', Rule::in(EtiquetadoraCatalog::maquinas())],
            'fecha_analisis' => ['required', 'date'],
            'numero_orden' => ['required', 'string', 'max:20'],
            'estado' => ['required', Rule::in(AnalisisEtiquetadora::estados())],
            'actividad' => ['required', 'string'],
            'evidencia_fotos' => ['nullable', 'array'],
            'evidencia_fotos.*' => $this->evidenciaFotoRules(),
            'eliminar_fotos' => ['nullable', 'array'],
            'eliminar_fotos.*' => ['integer'],
        ];
    }

    private function validarComponenteCatalogo(Linea $linea, int|string $componenteId, string $maquina): ?Componente
    {
        return $this->catalogoBase()
            ->whereKey($componenteId)
            ->where('linea', $linea->nombre)
            ->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina))
            ->first();
    }

    private function catalogoBase()
    {
        return Componente::query()
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->orderBy('linea')
            ->orderBy('reductor')
            ->orderBy('grupo')
            ->orderBy('nombre');
    }

    private function lineasEtiquetadora()
    {
        return Linea::whereIn('nombre', EtiquetadoraCatalog::lineas())
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    private function gruposCatalogo()
    {
        return Componente::query()
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->whereNotNull('grupo')
            ->select('grupo')
            ->distinct()
            ->orderBy('grupo')
            ->pluck('grupo')
            ->filter()
            ->values();
    }

    private function matrizCatalogo($catalogo, $ultimos)
    {
        return $catalogo
            ->groupBy(['linea', 'reductor'])
            ->map(function ($porMaquina) use ($ultimos) {
                return $porMaquina->map(function ($componentes) use ($ultimos) {
                    return $componentes
                        ->groupBy('grupo')
                        ->map(function ($items) use ($ultimos) {
                            return $items->map(function (Componente $componente) use ($ultimos) {
                                $componente->ultimo_analisis = $ultimos->get($componente->id);

                                return $componente;
                            });
                        });
                });
            });
    }

    private function estadisticas($catalogo, $ultimos): array
    {
        $total = $catalogo->count();
        $revisados = $catalogo->filter(fn (Componente $componente) => $ultimos->has($componente->id))->count();
        $registros = $ultimos->values();
        $danados = $registros->where('estado', AnalisisEtiquetadora::ESTADO_DANADO)->count();

        return [
            'total' => $registros->count(),
            'total_componentes' => $total,
            'revisados' => $revisados,
            'pendientes' => max($total - $revisados, 0),
            'avance' => $total > 0 ? round(($revisados / $total) * 100, 1) : 0,
            'buen_estado' => $registros->where('estado', AnalisisEtiquetadora::ESTADO_BUENO)->count(),
            'requiere_revision' => $registros->where('estado', AnalisisEtiquetadora::ESTADO_REQUIERE_REVISION)->count(),
            'desgaste' => $registros->whereIn('estado', AnalisisEtiquetadora::ESTADOS_DESGASTE)->count(),
            'danados' => $danados,
            'danado_requiere' => $danados,
            'cambiados' => $registros->where('estado', AnalisisEtiquetadora::ESTADO_CAMBIADO)->count(),
            'cambiado' => $registros->where('estado', AnalisisEtiquetadora::ESTADO_CAMBIADO)->count(),
        ];
    }

    private function evidenciaFotoRules(): array
    {
        return [
            'nullable',
            'file',
            'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/x-ms-bmp',
            'extensions:jpg,jpeg,png,gif,webp,bmp',
            'max:12288',
        ];
    }

    private function guardarEvidenciasFotograficas(array $archivos): array
    {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if (!$archivo || !$archivo->isValid()) {
                continue;
            }

            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'jpg');
            $nombreArchivo = now()->format('Ymd_His') . '_' . uniqid() . '.' . $extension;
            $rutaPublica = public_path('storage/' . self::EVIDENCIA_FOTOS_PATH);

            if (!file_exists($rutaPublica)) {
                mkdir($rutaPublica, 0755, true);
            }

            $archivo->move($rutaPublica, $nombreArchivo);
            $rutaGuardar = self::EVIDENCIA_FOTOS_PATH . '/' . $nombreArchivo;
            $rutas[] = $rutaGuardar;

            $rutaStorage = storage_path('app/public/' . self::EVIDENCIA_FOTOS_PATH);

            if (!file_exists($rutaStorage)) {
                mkdir($rutaStorage, 0755, true);
            }

            $origen = public_path('storage/' . $rutaGuardar);
            $destino = $rutaStorage . '/' . $nombreArchivo;

            if (file_exists($origen) && !file_exists($destino)) {
                copy($origen, $destino);
            }
        }

        return $rutas;
    }

    private function puedeEditarFechaAnalisis(?User $user): bool
    {
        return $user?->canEditAnalysisDate() ?? false;
    }
}
