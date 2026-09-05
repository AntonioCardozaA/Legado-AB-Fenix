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
use Illuminate\Validation\ValidationException;

class AnalisisEtiquetadoraController extends Controller
{
    private const EVIDENCIA_FOTOS_PATH = 'analisis-evidencias';

    public function index(Request $request)
    {
        $lineas = $this->lineasEtiquetadora();
        $lineaFiltroSolicitada = $request->filled('linea_id') && $request->linea_id !== 'todas';
        $lineaSeleccionada = $lineaFiltroSolicitada
            ? $lineas->firstWhere('id', (int) $request->linea_id)
            : $lineas->first();
        abort_if($lineaFiltroSolicitada && !$lineaSeleccionada, 404);

        $lineaIds = $lineas->pluck('id');
        $lineaNombres = $lineas->pluck('nombre');

        $catalogoQuery = $this->catalogoBase()
            ->whereIn('linea', $lineaNombres);
        $analisisQuery = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->whereIn('linea_id', $lineaIds)
            ->whereHas('componente', fn ($query) => $query
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('activo', true)
                ->whereIn('linea', $lineaNombres))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at');

        if ($lineaSeleccionada) {
            $catalogoQuery->where('linea', $lineaSeleccionada->nombre);
            $analisisQuery
                ->where('linea_id', $lineaSeleccionada->id)
                ->whereHas('componente', fn ($query) => $query->where('linea', $lineaSeleccionada->nombre));
        }

        if ($request->filled('maquina')) {
            $maquina = strtoupper((string) $request->maquina);
            $maquinaLabel = EtiquetadoraCatalog::maquinaLabel($maquina);
            $catalogoQuery->where('reductor', $maquinaLabel);
            $analisisQuery
                ->where('maquina', $maquina)
                ->whereHas('componente', fn ($query) => $query->where('reductor', $maquinaLabel));
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

        $catalogo = $this->catalogoAplicable($catalogoQuery);
        $analisis = $analisisQuery->get()
            ->filter(fn (AnalisisEtiquetadora $registro): bool => $this->analisisPerteneceACatalogoEtiquetadora($registro))
            ->values();

        $ultimos = AnalisisEtiquetadora::ultimosPorComponente()
            ->with(['linea', 'componente', 'usuario'])
            ->whereIn('linea_id', $lineaIds)
            ->whereHas('componente', fn ($query) => $query
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('activo', true)
                ->whereIn('linea', $lineaNombres))
            ->when($lineaSeleccionada, fn ($query) => $query
                ->where('linea_id', $lineaSeleccionada->id)
                ->whereHas('componente', fn ($subQuery) => $subQuery->where('linea', $lineaSeleccionada->nombre)))
            ->when($request->filled('maquina'), function ($query) use ($request): void {
                $maquina = strtoupper((string) $request->maquina);
                $query
                    ->where('maquina', $maquina)
                    ->whereHas('componente', fn ($subQuery) => $subQuery->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina)));
            })
            ->when($request->filled('grupo'), fn ($query) => $query->whereHas('componente', fn ($subQuery) => $subQuery->where('grupo', $request->grupo)))
            ->when($request->filled('componente_id'), fn ($query) => $query->where('componente_id', $request->componente_id))
            ->when($request->filled('componente') && !$request->filled('componente_id'), function ($query) use ($request): void {
                $query->whereHas('componente', function ($subQuery) use ($request): void {
                    $subQuery->where('nombre', 'like', '%' . $request->componente . '%')
                        ->orWhere('codigo', 'like', '%' . $request->componente . '%');
                });
            })
            ->get()
            ->filter(fn (AnalisisEtiquetadora $registro): bool => $this->analisisPerteneceACatalogoEtiquetadora($registro))
            ->keyBy('componente_id');

        $estadisticas = $this->estadisticas($catalogo, $ultimos);
        $matriz = $this->matrizCatalogo($catalogo, $ultimos);
        $tablaLineas = $this->tablaIndustrial($catalogo, $analisis, $lineas, $request->input('maquina'));
        $estadoModalItems = $this->itemsPorEstado($ultimos->values(), $analisis);
        $openAnalysisData = $this->modalPayloadForAnalysisId($request->input('open_analysis_id'));

        return view('etiquetadora.analisis-etiquetadora.index', [
            'lineas' => $lineas,
            'lineaSeleccionada' => $lineaSeleccionada,
            'maquinas' => EtiquetadoraCatalog::maquinas(),
            'grupos' => $this->gruposCatalogo($lineaSeleccionada),
            'todosComponentes' => $this->componentesFiltroCatalogo($lineaSeleccionada),
            'catalogo' => $catalogo,
            'matriz' => $matriz,
            'tablaLineas' => $tablaLineas,
            'ultimos' => $ultimos,
            'analisis' => $analisis,
            'estadisticas' => $estadisticas,
            'estadoModalItems' => $estadoModalItems,
            'openAnalysisData' => $openAnalysisData,
            'canDeleteAnalysis' => $request->user()?->canDeleteEtiquetadoraAnalysis() ?? false,
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
        $componentes = $this->catalogoAplicable(
            $this->catalogoBase()
                ->where('linea', $linea->nombre)
        )
            ->groupBy('reductor');
        $estadoCiclosComponentes = $this->estadoCiclosComponentesParaLinea($linea, $componentes->flatten(1));

        return view('etiquetadora.analisis-etiquetadora.create', [
            'linea' => $linea,
            'componentesPorMaquina' => $componentes,
            'maquinas' => EtiquetadoraCatalog::maquinas(),
            'maquinaSeleccionada' => $maquinaSeleccionada,
            'componenteSeleccionado' => $componenteSeleccionado,
            'estadoCiclosComponentes' => $estadoCiclosComponentes,
            'analisis' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->validationMessages());

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
            $seleccionComponentes = $this->resolverSeleccionComponentesRevisados(
                $request,
                $componente,
                null,
                true
            );

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
                'total_componentes' => $seleccionComponentes['total_componentes'],
                'cantidad_componentes_revisados' => count($seleccionComponentes['componentes_revisados']),
                'componentes_revisados' => $seleccionComponentes['componentes_revisados'],
            ]);

            if ($request->hasFile('evidencia_fotos')) {
                $analisis->update([
                    'evidencia_fotos' => $this->guardarEvidenciasFotograficas($request->file('evidencia_fotos', [])),
                ]);
            }

            return $analisis;
        });

        $resumenPosterior = AnalisisEtiquetadora::getResumenCicloComponente(
            $linea->id,
            $componente->id,
            $analisis->maquina,
            null,
            $analisis->total_componentes ?: (int) ($componente->cantidad_total ?? 1)
        );
        $mensaje = $resumenPosterior['tiene_ciclo_activo']
            ? 'Avance parcial guardado. Puedes continuar con las piezas pendientes desde el listado principal.'
            : 'Analisis de Etiquetadora registrado correctamente. Este ciclo quedo completado.';

        return redirect()
            ->route('analisis-etiquetadora.index', [
                'linea_id' => $linea->id,
                'maquina' => $analisis->maquina,
            ])
            ->with('success', $mensaje);
    }

    public function edit(AnalisisEtiquetadora $analisisetiquetadora)
    {
        $analisisetiquetadora->load(['linea', 'componente', 'usuario']);
        $componentes = $this->catalogoAplicable(
            $this->catalogoBase()
                ->where('linea', $analisisetiquetadora->linea?->nombre)
                ->where('reductor', EtiquetadoraCatalog::maquinaLabel($analisisetiquetadora->maquina))
        )
            ->groupBy('reductor');
        $estadoCiclosComponentes = $this->estadoCiclosComponentesParaLinea(
            $analisisetiquetadora->linea,
            $componentes->flatten(1),
            $analisisetiquetadora->id
        );

        return view('etiquetadora.analisis-etiquetadora.edit', [
            'analisis' => $analisisetiquetadora,
            'linea' => $analisisetiquetadora->linea,
            'componentesPorMaquina' => $componentes,
            'maquinas' => EtiquetadoraCatalog::maquinas(),
            'maquinaSeleccionada' => $analisisetiquetadora->maquina,
            'componenteSeleccionado' => $analisisetiquetadora->componente_id,
            'estadoCiclosComponentes' => $estadoCiclosComponentes,
            'puedeEditarFechaAnalisis' => $this->puedeEditarFechaAnalisis(auth()->user()),
        ]);
    }

    public function update(Request $request, AnalisisEtiquetadora $analisisetiquetadora)
    {
        $rules = $this->rules();
        $rules['fecha_analisis'] = ['required', 'date', 'date_format:Y-m-d'];
        $validator = Validator::make($request->all(), $rules, $this->validationMessages());

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
        $seleccionComponentes = $this->resolverSeleccionComponentesRevisados($request, $componente, $analisisetiquetadora);

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
            'total_componentes' => $seleccionComponentes['total_componentes'],
            'cantidad_componentes_revisados' => count($seleccionComponentes['componentes_revisados']),
            'componentes_revisados' => $seleccionComponentes['componentes_revisados'],
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
            'canDeleteAnalysis' => auth()->user()?->canDeleteEtiquetadoraAnalysis() ?? false,
        ]);
    }

    public function historial(Request $request)
    {
        $maquinaSolicitada = strtoupper(trim((string) $request->input('maquina', '')));
        if ($maquinaSolicitada !== '') {
            $request->merge(['maquina' => $maquinaSolicitada]);
        }

        $lineasEtiquetadora = $this->lineasEtiquetadora();
        $lineaPorDefecto = $lineasEtiquetadora->first();
        $lineaSolicitada = $request->input('linea_id');
        $lineaIds = $lineasEtiquetadora->pluck('id');
        $lineaNombres = $lineasEtiquetadora->pluck('nombre');

        if (($lineaSolicitada === null || $lineaSolicitada === '' || $lineaSolicitada === 'todas') && $lineaPorDefecto) {
            return redirect()->route('analisis-etiquetadora.historial', array_merge(
                $request->except(['linea_id', 'page']),
                ['linea_id' => $lineaPorDefecto->id]
            ));
        }

        $request->validate([
            'linea_id' => 'nullable|exists:lineas,id',
            'componente_id' => 'nullable|exists:componentes,id',
            'maquina' => ['nullable', Rule::in(EtiquetadoraCatalog::maquinas())],
        ]);

        $lineaSeleccionada = $request->filled('linea_id')
            ? $lineasEtiquetadora->firstWhere('id', (int) $request->linea_id)
            : null;

        abort_if($request->filled('linea_id') && !$lineaSeleccionada, 404);

        $catalogoQuery = $this->catalogoBase()
            ->whereIn('linea', $lineaNombres);

        $historicoQuery = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->whereIn('linea_id', $lineaIds)
            ->whereHas('componente', fn ($query) => $query
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('activo', true)
                ->whereIn('linea', $lineaNombres));

        $query = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->whereIn('linea_id', $lineaIds)
            ->whereHas('componente', fn ($query) => $query
                ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
                ->where('activo', true)
                ->whereIn('linea', $lineaNombres))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at');

        if ($lineaSeleccionada) {
            $query
                ->where('linea_id', $request->linea_id)
                ->whereHas('componente', fn ($subQuery) => $subQuery->where('linea', $lineaSeleccionada->nombre));
            $catalogoQuery->where('linea', $lineaSeleccionada->nombre);
            $historicoQuery
                ->where('linea_id', $lineaSeleccionada->id)
                ->whereHas('componente', fn ($subQuery) => $subQuery->where('linea', $lineaSeleccionada->nombre));
        }

        if ($request->filled('componente_id')) {
            $query->where('componente_id', $request->componente_id);
            $catalogoQuery->whereKey($request->componente_id);
            $historicoQuery->where('componente_id', $request->componente_id);
        }

        if ($request->filled('maquina')) {
            $maquina = strtoupper($request->maquina);
            $maquinaLabel = EtiquetadoraCatalog::maquinaLabel($maquina);

            $query
                ->where('maquina', $maquina)
                ->whereHas('componente', fn ($subQuery) => $subQuery->where('reductor', $maquinaLabel));
            $catalogoQuery->where('reductor', $maquinaLabel);
            $historicoQuery
                ->where('maquina', $maquina)
                ->whereHas('componente', fn ($subQuery) => $subQuery->where('reductor', $maquinaLabel));
        }

        $catalogo = $this->catalogoAplicable($catalogoQuery);
        $analisisHistorico = $historicoQuery->get()
            ->filter(fn (AnalisisEtiquetadora $registro): bool => $this->analisisPerteneceACatalogoEtiquetadora($registro))
            ->values();
        $analisis = $query->get()
            ->filter(fn (AnalisisEtiquetadora $registro): bool => $this->analisisPerteneceACatalogoEtiquetadora($registro))
            ->values();

        $historico = $this->historicoRevisadosEtiquetadora(
            $catalogo,
            $analisisHistorico
        );

        return view('etiquetadora.analisis-etiquetadora.historial', [
            'analisis' => $analisis,
            'lineasEtiquetadora' => $lineasEtiquetadora,
            'maquinasEtiquetadora' => EtiquetadoraCatalog::maquinas(),
            'lineaSeleccionada' => $lineaSeleccionada,
            'estadisticasHistorico' => $historico['estadisticas'],
            'resumenHistorico' => $historico['resumen'],
        ]);
    }

    public function historialAnalisis(Request $request)
    {
        $request->merge([
            'maquina' => strtoupper(trim((string) $request->input('maquina', ''))),
        ]);

        $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'componente_id' => 'required|exists:componentes,id',
            'maquina' => ['required', Rule::in(EtiquetadoraCatalog::maquinas())],
        ]);

        $linea = $this->lineasEtiquetadora()
            ->firstWhere('id', (int) $request->input('linea_id'));

        abort_if(!$linea, 404);

        $componente = $this->validarComponenteCatalogo(
            $linea,
            $request->input('componente_id'),
            (string) $request->input('maquina')
        );

        abort_if(!$componente, 404);

        $analisis = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->where('linea_id', $linea->id)
            ->where('componente_id', $componente->id)
            ->where('maquina', $request->input('maquina'))
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('etiquetadora.analisis-etiquetadora.historial-analisis', [
            'analisis' => $analisis,
            'linea' => $linea,
            'componente' => $componente,
            'maquina' => $request->input('maquina'),
            'maquinaLabel' => EtiquetadoraCatalog::maquinaLabel((string) $request->input('maquina')),
        ]);
    }

    public function destroy(Request $request, AnalisisEtiquetadora $analisisetiquetadora)
    {
        abort_unless($request->user()?->canDeleteEtiquetadoraAnalysis(), 403, 'No tienes permiso para eliminar analisis.');

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
            'componentes_revisados' => $analisisetiquetadora->componentes_revisados_lista,
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

        $componentes = $this->catalogoAplicable(
            $this->catalogoBase()
                ->where('linea', $linea->nombre)
                ->when($maquina, fn ($query) => $query->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina)))
        )
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
        $catalogo = $this->catalogoAplicable(
            $this->catalogoBase()
                ->where('linea', $linea->nombre)
                ->when($maquina, fn ($query) => $query->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina)))
        );

        $ultimos = AnalisisEtiquetadora::ultimosPorComponente()
            ->with(['linea', 'componente', 'usuario'])
            ->where('linea_id', $linea->id)
            ->when($maquina, fn ($query) => $query->where('maquina', $maquina))
            ->get()
            ->filter(fn (AnalisisEtiquetadora $registro): bool => $this->analisisPerteneceACatalogoEtiquetadora($registro, $linea->nombre))
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

        if (!$this->analisisPerteneceACatalogoEtiquetadora($registro)) {
            return null;
        }

        $totalComponentes = max(1, (int) ($registro->total_componentes ?: ($registro->componente?->cantidad_total ?? 1)));
        $registrosCiclo = AnalisisEtiquetadora::with(['linea', 'componente', 'usuario'])
            ->where('linea_id', $registro->linea_id)
            ->where('componente_id', $registro->componente_id)
            ->where('maquina', $registro->maquina)
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $resumenCiclo = AnalisisEtiquetadora::buildResumenCicloPiezas($registrosCiclo, $totalComponentes);
        $resumenVisible = $resumenCiclo['resumen_visible'];
        $registroVisible = $this->ultimoAnalisisEtiquetadora($resumenCiclo['registros_visibles']) ?: $registro;

        $imagenes = $registroVisible->evidencia_fotos ?? [];

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

        $canDeleteAnalysis = auth()->user()?->canDeleteEtiquetadoraAnalysis() ?? false;

        return [
            'id' => $registroVisible->id,
            'linea' => $registroVisible->linea->nombre ?? 'Linea no registrada',
            'componente' => $registroVisible->componente->nombre ?? 'Componente no registrado',
            'componente_codigo' => $registroVisible->componente->codigo ?? $registroVisible->componente_id,
            'reductor' => $registroVisible->reductor ?: EtiquetadoraCatalog::maquinaLabel((string) $registroVisible->maquina),
            'maquina' => $registroVisible->maquina,
            'lado' => $registroVisible->lado ?? null,
            'fecha_analisis' => $registroVisible->fecha_analisis ? $registroVisible->fecha_analisis->format('d/m/Y') : '',
            'numero_orden' => $registroVisible->numero_orden,
            'estado' => $registroVisible->estado ?? AnalisisEtiquetadora::ESTADO_BUENO,
            'usuario_nombre' => $registroVisible->usuario?->name ?? 'Usuario no registrado',
            'actividad' => $registroVisible->actividad,
            'imagenes' => $imagenes,
            'componentes_revisados' => $resumenVisible['piezas_revisadas'],
            'componentes_pendientes' => $resumenVisible['piezas_pendientes'],
            'cantidad_componentes_revisados' => $resumenVisible['cantidad_revisada'],
            'total_componentes' => $resumenVisible['total_componentes'],
            'tiene_ciclo_activo' => $resumenCiclo['tiene_ciclo_activo'],
            'color' => $this->analysisCellColor($registroVisible->estado ?? null),
            'created_at' => $registroVisible->created_at ? $registroVisible->created_at->format('d/m/Y H:i') : '',
            'updated_at' => $registroVisible->updated_at ? $registroVisible->updated_at->format('d/m/Y H:i') : '',
            'is_new' => $registroVisible->created_at ? $registroVisible->created_at->gt(now()->subDays(3)) : false,
            'total_historial' => $totalHistorial,
            'edit_url' => route('analisis-etiquetadora.edit', ['analisisetiquetadora' => $registroVisible->id], false),
            'delete_url' => $canDeleteAnalysis ? route('analisis-etiquetadora.destroy', ['analisisetiquetadora' => $registroVisible->id], false) : null,
            'historial_url' => route('analisis-etiquetadora.historial-analisis', [
                'linea_id' => $registroVisible->linea_id,
                'componente_id' => $registroVisible->componente_id,
                'maquina' => $registroVisible->maquina,
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
            ->filter(fn ($catalogoLinea, string $lineaNombre) => $lineasPorNombre->has($lineaNombre))
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
                $estadoCiclos = [];
                $analisisLinea = $linea
                    ? collect($analisisPorLinea->get($linea->id, collect()))
                        ->filter(fn (AnalisisEtiquetadora $registro): bool => $this->analisisPerteneceACatalogoEtiquetadora($registro, $lineaNombre))
                        ->values()
                    : collect();

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
                        $resumenCiclo = AnalisisEtiquetadora::buildResumenCicloPiezas(
                            $celda,
                            max(1, (int) ($componentForMachine->cantidad_total ?? 1))
                        );
                        $estadoCiclos[$maquina][$componentForMachine->id] = $resumenCiclo;
                        $registro = $this->ultimoAnalisisEtiquetadora($resumenCiclo['registros_visibles']);

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
                    'estado_ciclos' => $estadoCiclos,
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

    private function itemsPorEstado($registros, $analisis = null): array
    {
        $items = [
            'total' => [],
            'buen_estado' => [],
            'requiere_revision' => [],
            'desgaste' => [],
            'danado' => [],
            'cambiado' => [],
        ];

        $registrosPorCiclo = collect($analisis ?? $registros)
            ->filter(fn ($registro): bool => $registro instanceof AnalisisEtiquetadora)
            ->groupBy(fn (AnalisisEtiquetadora $registro): string => $this->claveCicloEtiquetadora($registro));

        foreach ($registros as $registro) {
            if (!$registro instanceof AnalisisEtiquetadora) {
                continue;
            }

            $componente = $registro->componente;
            $lineaNombre = $registro->linea->nombre ?? 'Sin linea';
            $maquina = strtoupper((string) ($registro->maquina ?: $this->maquinaDesdeEtiqueta($registro->reductor)));
            $totalComponentes = max(1, (int) ($registro->total_componentes ?: ($componente?->cantidad_total ?? 1)));
            $registrosCiclo = collect($registrosPorCiclo->get($this->claveCicloEtiquetadora($registro), collect()));

            if ($registrosCiclo->isEmpty()) {
                $registrosCiclo = collect([$registro]);
            }

            $resumenCiclo = AnalisisEtiquetadora::buildResumenCicloPiezas(
                $registrosCiclo,
                $totalComponentes
            );
            $resumenVisible = $resumenCiclo['resumen_visible'];
            $imagenes = $registro->evidencia_fotos ?? [];

            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true) ?? [];
            }

            if (!is_array($imagenes)) {
                $imagenes = [];
            }

            $item = [
                'id' => $registro->id,
                'linea' => $lineaNombre,
                'componente' => $componente->nombre ?? 'Sin componente',
                'componente_codigo' => $componente->codigo ?? $registro->componente_id,
                'grupo' => $this->grupoVisibleEtiquetadora($componente?->grupo, $lineaNombre, $componente?->nombre),
                'mecanismo' => filled($componente?->mecanismo) && $componente?->mecanismo !== $componente?->grupo
                    ? $componente?->mecanismo
                    : null,
                'reductor' => $registro->reductor ?: EtiquetadoraCatalog::maquinaLabel($maquina),
                'maquina' => $maquina,
                'maquina_label' => $maquina !== '' ? EtiquetadoraCatalog::maquinaLabel($maquina) : ($registro->reductor ?: 'Sin maquina'),
                'estado' => $registro->estado ?? AnalisisEtiquetadora::ESTADO_BUENO,
                'fecha' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : '-',
                'numero_orden' => $registro->numero_orden ?: null,
                'usuario_nombre' => $registro->usuario?->name ?? 'Usuario no registrado',
                'actividad' => $registro->actividad ?: null,
                'imagenes_count' => count(array_filter($imagenes)),
                'total_componentes' => $resumenVisible['total_componentes'],
                'cantidad_componentes_revisados' => $resumenVisible['cantidad_revisada'],
                'cantidad_componentes_pendientes' => $resumenVisible['cantidad_pendiente'],
                'componentes_revisados' => $resumenVisible['piezas_revisadas'],
                'componentes_pendientes' => $resumenVisible['piezas_pendientes'],
                'tiene_ciclo_activo' => $resumenCiclo['tiene_ciclo_activo'],
                'edit_url' => route('analisis-etiquetadora.edit', ['analisisetiquetadora' => $registro->id], false),
                'historial_url' => route('analisis-etiquetadora.historial-analisis', [
                    'linea_id' => $registro->linea_id,
                    'componente_id' => $registro->componente_id,
                    'maquina' => $maquina,
                ], false),
            ];

            $bucket = $this->estadoBucket($registro->estado);
            $items['total'][] = $item;
            $items[$bucket === 'danado' ? 'danado' : $bucket][] = $item;
        }

        return $items;
    }

    private function claveCicloEtiquetadora(AnalisisEtiquetadora $registro): string
    {
        return implode('|', [
            (string) $registro->linea_id,
            (string) $registro->componente_id,
            strtoupper((string) ($registro->maquina ?: $this->maquinaDesdeEtiqueta($registro->reductor))),
        ]);
    }

    private function grupoVisibleEtiquetadora(?string $grupo, ?string $lineaNombre = null, ?string $componenteNombre = null): ?string
    {
        $grupo = trim((string) $grupo);

        if ($grupo === '') {
            return null;
        }

        if (
            strcasecmp(trim((string) $componenteNombre), 'Platos giratorios') === 0
            && strcasecmp(rtrim($grupo, ':'), 'PRESENTACION POR LINEAS') === 0
        ) {
            return null;
        }

        $lineaEspecifica = EtiquetadoraCatalog::lineaEspecificaDesdeTexto($grupo);

        if ($lineaEspecifica !== null && trim((string) $lineaNombre) === $lineaEspecifica) {
            $grupo = preg_replace('/^\s*(?:LINEA|L)\s*-?\s*0?[0-9]{1,2}\s*[,:\-]?\s*/i', '', $grupo) ?? $grupo;
            $grupo = trim($grupo, ' ,:-');
        }

        return $grupo !== '' ? $grupo : null;
    }

    private function analisisPerteneceACatalogoEtiquetadora(AnalisisEtiquetadora $registro, ?string $lineaNombre = null): bool
    {
        $componente = $registro->componente;
        $lineaRegistro = $lineaNombre ?: $registro->linea?->nombre;

        if (!$componente || blank($lineaRegistro)) {
            return false;
        }

        if ($componente->tipo_equipo !== EtiquetadoraCatalog::TIPO_EQUIPO || !$componente->activo) {
            return false;
        }

        if (!$this->componenteCorrespondeALineaEtiquetadora($componente)) {
            return false;
        }

        if (trim((string) $componente->linea) !== trim((string) $lineaRegistro)) {
            return false;
        }

        $maquinaRegistro = strtoupper((string) ($registro->maquina ?: $this->maquinaDesdeEtiqueta($registro->reductor)));
        $maquinaComponente = $this->maquinaDesdeEtiqueta($componente->reductor);

        return $maquinaRegistro !== '' && $maquinaRegistro === $maquinaComponente;
    }

    private function componentesFiltroCatalogo(?Linea $linea = null)
    {
        return $this->catalogoAplicable(Componente::query()
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->whereIn('linea', EtiquetadoraCatalog::lineas())
            ->when($linea, fn ($query) => $query->where('linea', $linea->nombre))
            ->whereNotNull('nombre'))
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn (string $nombre): array => [$nombre => $nombre]);
    }

    private function catalogoAplicable($query)
    {
        return $query->get()
            ->filter(fn (Componente $componente): bool => $this->componenteCorrespondeALineaEtiquetadora($componente))
            ->values();
    }

    private function componenteCorrespondeALineaEtiquetadora(Componente $componente): bool
    {
        $linea = trim((string) $componente->linea);

        if (!in_array($linea, EtiquetadoraCatalog::lineas(), true)) {
            return false;
        }

        if (!EtiquetadoraCatalog::grupoAplicaALinea(
            $componente->grupo,
            $componente->mecanismo,
            $componente->ubicacion,
            $linea
        )) {
            return false;
        }

        $maquina = $this->maquinaDesdeEtiqueta($componente->reductor);

        return $maquina !== '';
    }

    private function componenteTablaKey(Componente $componente): string
    {
        return sha1(implode('|', [
            trim((string) $componente->linea),
            trim((string) $componente->grupo),
            trim((string) $componente->mecanismo),
            trim((string) $componente->nombre),
        ]));
    }

    private function componenteHistoricoKey(Componente $componente): string
    {
        return sha1(implode('|', [
            trim((string) $componente->linea),
            trim((string) $this->maquinaDesdeEtiqueta($componente->reductor)),
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
            'numero_orden' => ['required', 'string', 'max:8', 'regex:/^\d{1,8}$/'],
            'estado' => ['required', Rule::in(AnalisisEtiquetadora::estados())],
            'actividad' => ['required', 'string'],
            'componentes_revisados' => ['nullable', 'array'],
            'componentes_revisados.*' => ['nullable', 'integer', 'min:1'],
            'evidencia_fotos' => ['nullable', 'array'],
            'evidencia_fotos.*' => $this->evidenciaFotoRules(),
            'eliminar_fotos' => ['nullable', 'array'],
            'eliminar_fotos.*' => ['integer'],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'numero_orden.max' => 'El numero de orden debe tener maximo 8 digitos.',
            'numero_orden.regex' => 'El numero de orden debe contener solo digitos y tener maximo 8 caracteres.',
        ];
    }

    private function resolverSeleccionComponentesRevisados(
        Request $request,
        Componente $componente,
        ?AnalisisEtiquetadora $analisisActual = null,
        bool $lockForUpdate = false
    ): array
    {
        $totalComponentes = max(1, (int) ($componente->cantidad_total ?? 1));
        $componentesDisponibles = AnalisisEtiquetadora::getPiezasDisponiblesParaRegistro(
            $request->input('linea_id'),
            $componente->id,
            $request->input('maquina'),
            $analisisActual?->id,
            $totalComponentes,
            $lockForUpdate
        );

        if ($totalComponentes === 1) {
            if (!in_array(1, $componentesDisponibles, true)) {
                throw ValidationException::withMessages([
                    'componentes_revisados' => 'Todas las piezas de este componente ya fueron revisadas en el ciclo actual.',
                ]);
            }

            return [
                'total_componentes' => $totalComponentes,
                'componentes_revisados' => [1],
            ];
        }

        $componentesSeleccionados = AnalisisEtiquetadora::normalizarComponentesRevisados(
            $request->input('componentes_revisados'),
            $totalComponentes
        );

        if (empty($componentesSeleccionados)) {
            throw ValidationException::withMessages([
                'componentes_revisados' => 'Debe seleccionar al menos una pieza revisada.',
            ]);
        }

        if (empty($componentesDisponibles)) {
            throw ValidationException::withMessages([
                'componentes_revisados' => 'Todas las piezas de este componente ya fueron revisadas en el ciclo actual.',
            ]);
        }

        $seleccionInvalida = array_values(array_diff($componentesSeleccionados, $componentesDisponibles));

        if (!empty($seleccionInvalida)) {
            throw ValidationException::withMessages([
                'componentes_revisados' => 'La seleccion incluye piezas ya revisadas en el ciclo actual o fuera del rango permitido.',
            ]);
        }

        return [
            'total_componentes' => $totalComponentes,
            'componentes_revisados' => $componentesSeleccionados,
        ];
    }

    private function validarComponenteCatalogo(Linea $linea, int|string $componenteId, string $maquina): ?Componente
    {
        return $this->catalogoAplicable(
            $this->catalogoBase()
                ->whereKey($componenteId)
                ->where('linea', $linea->nombre)
                ->where('reductor', EtiquetadoraCatalog::maquinaLabel($maquina))
        )->first();
    }

    private function estadoCiclosComponentesParaLinea(?Linea $linea, $componentes, ?int $excludeId = null): array
    {
        if (!$linea) {
            return [];
        }

        $componentes = collect($componentes)
            ->filter(fn ($componente) => $componente instanceof Componente && filled($componente->id))
            ->values();

        if ($componentes->isEmpty()) {
            return [];
        }

        $registros = AnalisisEtiquetadora::query()
            ->where('linea_id', $linea->id)
            ->whereIn('componente_id', $componentes->pluck('id')->all())
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (AnalisisEtiquetadora $registro) => strtoupper((string) $registro->maquina) . '|' . $registro->componente_id);

        return $componentes
            ->mapWithKeys(function (Componente $componente) use ($registros): array {
                $maquina = $this->maquinaDesdeEtiqueta($componente->reductor);
                $totalComponentes = max(1, (int) ($componente->cantidad_total ?? 1));
                $resumen = AnalisisEtiquetadora::buildResumenCicloPiezas(
                    $registros->get($maquina . '|' . $componente->id, collect()),
                    $totalComponentes
                );
                $piezasDisponibles = $resumen['tiene_ciclo_activo']
                    ? $resumen['resumen_actual']['piezas_pendientes']
                    : range(1, $totalComponentes);
                $piezasBloqueadas = $resumen['tiene_ciclo_activo']
                    ? $resumen['resumen_actual']['piezas_revisadas']
                    : [];

                return [
                    (string) $componente->id => [
                        'total_componentes' => $totalComponentes,
                        'piezas_disponibles' => $piezasDisponibles,
                        'piezas_bloqueadas' => $piezasBloqueadas,
                        'piezas_revisadas' => $resumen['resumen_actual']['piezas_revisadas'],
                        'piezas_pendientes' => $resumen['resumen_actual']['piezas_pendientes'],
                        'cantidad_revisada' => $resumen['resumen_actual']['cantidad_revisada'],
                        'cantidad_pendiente' => $resumen['tiene_ciclo_activo']
                            ? $resumen['resumen_actual']['cantidad_pendiente']
                            : $totalComponentes,
                        'tiene_ciclo_activo' => $resumen['tiene_ciclo_activo'],
                        'tiene_ciclo_completado' => $resumen['tiene_ciclo_completado'],
                    ],
                ];
            })
            ->all();
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

    private function gruposCatalogo(?Linea $linea = null)
    {
        return $this->catalogoAplicable(Componente::query()
            ->where('tipo_equipo', EtiquetadoraCatalog::TIPO_EQUIPO)
            ->where('activo', true)
            ->whereIn('linea', EtiquetadoraCatalog::lineas())
            ->when($linea, fn ($query) => $query->where('linea', $linea->nombre))
            ->whereNotNull('grupo'))
            ->pluck('grupo')
            ->filter()
            ->reject(fn (string $grupo): bool => strcasecmp(rtrim(trim($grupo), ':'), 'PRESENTACION POR LINEAS') === 0)
            ->unique()
            ->sort()
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

    private function historicoRevisadosEtiquetadora($catalogo, $analisis): array
    {
        $analisisPorComponente = collect($analisis)
            ->filter(fn (AnalisisEtiquetadora $registro) => filled($registro->componente_id))
            ->groupBy('componente_id');

        $estadisticas = collect($catalogo)
            ->groupBy(fn (Componente $componente) => $this->componenteHistoricoKey($componente))
            ->map(function ($componentes) use ($analisisPorComponente): array {
                $componentes = collect($componentes);
                /** @var Componente $primerComponente */
                $primerComponente = $componentes->first();

                $detalleComponentes = $componentes
                    ->map(fn (Componente $componente): array => $this->historicoDetalleComponenteEtiquetadora(
                        $componente,
                        collect($analisisPorComponente->get($componente->id, collect()))
                    ))
                    ->values();

                $cantidadTotal = (int) $detalleComponentes->sum('cantidad_total');
                $cantidadRevisada = (int) $detalleComponentes->sum('cantidad_revisada');
                $cantidadPendiente = max($cantidadTotal - $cantidadRevisada, 0);
                $porcentaje = $cantidadTotal > 0 ? round(($cantidadRevisada / $cantidadTotal) * 100, 1) : 0;
                $ultimoRegistro = $this->ultimoAnalisisEtiquetadora(
                    $detalleComponentes->pluck('ultimo_registro')->filter()
                );

                return [
                    'nombre' => $primerComponente->nombre,
                    'grupo' => $primerComponente->grupo,
                    'mecanismo' => $primerComponente->mecanismo,
                    'cantidad_total' => $cantidadTotal,
                    'cantidad_revisada' => $cantidadRevisada,
                    'cantidad_pendiente' => $cantidadPendiente,
                    'porcentaje' => $porcentaje,
                    'color' => $this->colorPorcentajeHistorico($porcentaje),
                    'componentes_total' => $detalleComponentes->count(),
                    'componentes_revisados' => $detalleComponentes->where('cantidad_revisada', '>', 0)->count(),
                    'componentes_completos' => $detalleComponentes
                        ->filter(fn (array $detalle) => $detalle['cantidad_total'] > 0 && $detalle['cantidad_revisada'] >= $detalle['cantidad_total'])
                        ->count(),
                    'componentes_pendientes' => $detalleComponentes
                        ->filter(fn (array $detalle) => $detalle['cantidad_revisada'] < $detalle['cantidad_total'])
                        ->count(),
                    'lineas' => $detalleComponentes->pluck('linea')->filter()->unique()->values(),
                    'maquinas' => $detalleComponentes->pluck('maquina')->filter()->unique()->values(),
                    'ultima_revision' => $ultimoRegistro?->fecha_analisis?->format('d/m/Y'),
                    'usuario_ultima_revision' => $ultimoRegistro?->usuario?->name,
                    'estado_actual' => $ultimoRegistro?->estado,
                    'numero_orden_ultima_revision' => $ultimoRegistro?->numero_orden,
                    'detalle_componentes' => $detalleComponentes->map(fn (array $detalle) => array_diff_key($detalle, ['ultimo_registro' => true]))->values(),
                ];
            })
            ->sortBy(fn (array $grupo) => ($grupo['grupo'] ?? '') . ' ' . ($grupo['nombre'] ?? ''))
            ->values();

        $totalGeneral = (int) $estadisticas->sum('cantidad_total');
        $revisadoGeneral = (int) $estadisticas->sum('cantidad_revisada');
        $pendienteGeneral = max($totalGeneral - $revisadoGeneral, 0);
        $ultimoDetalleGeneral = $estadisticas->pluck('detalle_componentes')
            ->flatten(1)
            ->filter(fn (array $detalle) => filled($detalle['ultima_revision_sortable'] ?? null))
            ->sortByDesc('ultima_revision_sortable')
            ->first();

        return [
            'estadisticas' => $estadisticas,
            'resumen' => [
                'total_general' => $totalGeneral,
                'revisado_general' => $revisadoGeneral,
                'pendiente_general' => $pendienteGeneral,
                'porcentaje_general' => $totalGeneral > 0
                    ? round(($revisadoGeneral / $totalGeneral) * 100, 1)
                    : 0,
                'componentes_total' => (int) $estadisticas->sum('componentes_total'),
                'componentes_revisados' => (int) $estadisticas->sum('componentes_revisados'),
                'componentes_completos' => (int) $estadisticas->sum('componentes_completos'),
                'componentes_pendientes' => (int) $estadisticas->sum('componentes_pendientes'),
                'ultima_revision' => $ultimoDetalleGeneral['ultima_revision'] ?? null,
            ],
        ];
    }

    private function historicoDetalleComponenteEtiquetadora(Componente $componente, $registros): array
    {
        $totalComponentes = max(1, (int) ($componente->cantidad_total ?? 1));
        $registros = collect($registros);
        $resumenCiclo = AnalisisEtiquetadora::buildResumenCicloPiezas($registros, $totalComponentes);
        $resumenVisible = $resumenCiclo['resumen_visible'];
        $piezasRevisadas = collect($resumenVisible['piezas_revisadas']);
        $piezasPendientes = collect($resumenVisible['piezas_pendientes']);
        $ultimoRegistro = $this->ultimoAnalisisEtiquetadora($resumenCiclo['registros_visibles']);

        return [
            'componente_id' => $componente->id,
            'linea' => $componente->linea,
            'maquina' => $this->maquinaDesdeEtiqueta($componente->reductor),
            'cantidad_total' => $totalComponentes,
            'cantidad_revisada' => $piezasRevisadas->count(),
            'cantidad_pendiente' => $piezasPendientes->count(),
            'piezas_revisadas' => $piezasRevisadas->all(),
            'piezas_pendientes' => $piezasPendientes->all(),
            'ultima_revision' => $ultimoRegistro?->fecha_analisis?->format('d/m/Y'),
            'ultima_revision_sortable' => $ultimoRegistro ? $this->analisisEtiquetadoraSortKey($ultimoRegistro) : null,
            'usuario_ultima_revision' => $ultimoRegistro?->usuario?->name,
            'estado_actual' => $ultimoRegistro?->estado,
            'numero_orden_ultima_revision' => $ultimoRegistro?->numero_orden,
            'ultimo_registro_id' => $ultimoRegistro?->id,
            'actividad_ultima_revision' => $ultimoRegistro?->actividad,
            'ultimo_registro' => $ultimoRegistro,
        ];
    }

    private function ultimoAnalisisEtiquetadora($registros): ?AnalisisEtiquetadora
    {
        return collect($registros)
            ->filter(fn ($registro) => $registro instanceof AnalisisEtiquetadora)
            ->sortByDesc(fn (AnalisisEtiquetadora $registro) => $this->analisisEtiquetadoraSortKey($registro))
            ->first();
    }

    private function analisisEtiquetadoraSortKey(AnalisisEtiquetadora $registro): string
    {
        $fechaAnalisis = $registro->fecha_analisis?->format('Ymd') ?? '00000000';
        $createdAt = str_pad((string) ($registro->created_at?->timestamp ?? 0), 12, '0', STR_PAD_LEFT);
        $id = str_pad((string) ($registro->id ?? 0), 10, '0', STR_PAD_LEFT);

        return $fechaAnalisis . '-' . $createdAt . '-' . $id;
    }

    private function colorPorcentajeHistorico(float|int $porcentaje): string
    {
        return match (true) {
            $porcentaje >= 80 => 'success',
            $porcentaje >= 50 => 'info',
            $porcentaje >= 20 => 'warning',
            default => 'danger',
        };
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
