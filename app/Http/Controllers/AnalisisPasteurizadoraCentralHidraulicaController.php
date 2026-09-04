<?php

namespace App\Http\Controllers;

use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use App\Models\CentralHidraulicaComponente;
use App\Models\CentralHidraulicaConfiguracion;
use App\Models\Linea;
use App\Services\ImageEvidenceOptimizer;
use App\Services\Maintenance\PasteurizadoraMaintenanceOrchestrator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnalisisPasteurizadoraCentralHidraulicaController extends Controller
{
    private const EVIDENCIAS_DIR = 'analisis-pasteurizadora-central-hidraulica';
    private const EVIDENCIAS_MAX_TOTAL_KILOBYTES = 5120;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!$request->user()?->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA)) {
                abort(403, 'No tienes permiso para acceder a Central Hidraulica.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $lineas = $this->getLineasPasteurizadora();
        $lineaId = $request->get('linea_id', 'todas');
        $lineaSeleccionada = $lineaId !== 'todas' ? Linea::find($lineaId) : null;

        $query = $this->filteredAnalisisQuery($request)
            ->where('resuelto_por_cambio', false)
            ->latest('fecha_analisis')
            ->latest('created_at');

        $analisis = $query->get();
        $totalAnalisis = $analisis->count();
        $totalDanados = $analisis->filter(fn (AnalisisCentralHidraulica $item) => AnalisisCentralHidraulica::esEstadoDanado($item->estado))->count();
        $totalCambiados = $analisis->where('estado', AnalisisCentralHidraulica::ESTADO_CAMBIADO)->count();
        $totalRequiereRevision = $analisis->where('estado', AnalisisCentralHidraulica::ESTADO_REQUIERE_REVISION)->count();
        $seguimientoCentral = $this->buildSeguimientoCentral($lineaSeleccionada ? collect([$lineaSeleccionada]) : $lineas);
        $mostrarTodas = !$lineaSeleccionada;

        return view('pasteurizadora.central-hidraulica.index', $this->sharedViewData(compact(
            'analisis',
            'lineas',
            'lineaSeleccionada',
            'mostrarTodas',
            'totalAnalisis',
            'totalDanados',
            'totalCambiados',
            'totalRequiereRevision',
            'seguimientoCentral'
        )));
    }

    public function selectLinea()
    {
        $lineas = $this->getLineasPasteurizadora();

        return view('pasteurizadora.central-hidraulica.select-linea', $this->sharedViewData(compact('lineas')));
    }

    public function create(Request $request)
    {
        $linea = $request->filled('linea_id') ? Linea::find($request->linea_id) : null;

        if (!$linea) {
            return redirect()->route($this->routeName('select-linea'));
        }

        return $this->renderCreateView($linea, $request, false);
    }

    public function createWithLinea($linea)
    {
        $linea = $this->resolverLineaPasteurizadora($linea);

        if (!$linea) {
            abort(404, 'Pasteurizadora no encontrada.');
        }

        return $this->renderCreateView($linea, request(), false);
    }

    public function createQuick(Request $request)
    {
        $linea = $request->filled('linea_id') ? Linea::find($request->linea_id) : null;

        if (!$linea) {
            return redirect()->route($this->routeName('select-linea'), ['modo' => 'rapido']);
        }

        return $this->renderCreateView($linea, $request, true);
    }

    public function store(Request $request)
    {
        $analisis = $this->guardarAnalisis($request, AnalisisCentralHidraulica::TIPO_REGISTRO_NORMAL);
        $mensajeIa = $this->procesarMantenimientoAutomaticoSafely($analisis);

        $response = redirect()
            ->route($this->routeName('index'), ['linea_id' => $analisis->linea_id])
            ->with('success', 'Analisis de central hidraulica registrado correctamente.');

        return $mensajeIa ? $response->with('warning', $mensajeIa) : $response;
    }

    public function storeQuick(Request $request)
    {
        $analisis = $this->guardarAnalisis($request, AnalisisCentralHidraulica::TIPO_REGISTRO_QUICK);
        $mensajeIa = $this->procesarMantenimientoAutomaticoSafely($analisis);

        $response = redirect()
            ->route($this->routeName('index'), ['linea_id' => $analisis->linea_id])
            ->with('success', 'Revision rapida de central hidraulica registrada correctamente.');

        return $mensajeIa ? $response->with('warning', $mensajeIa) : $response;
    }

    public function show($id)
    {
        $analisis = AnalisisCentralHidraulica::with(['linea', 'configuracion', 'componente', 'usuario'])
            ->findOrFail($id);
        $historial = $this->historialContexto($analisis)->latest('fecha_analisis')->latest('created_at')->get();

        return view('pasteurizadora.central-hidraulica.show', $this->sharedViewData(compact('analisis', 'historial')));
    }

    public function edit($id)
    {
        $analisis = AnalisisCentralHidraulica::with(['linea', 'configuracion', 'componente'])->findOrFail($id);
        $linea = $analisis->linea;
        $configuraciones = $this->configuracionesParaLinea($linea);
        $componentesDisponibles = $this->componentesDisponiblesPara($analisis->configuracion, $analisis->lado, $analisis->id);

        return view('pasteurizadora.central-hidraulica.edit', $this->sharedViewData(compact(
            'analisis',
            'linea',
            'configuraciones',
            'componentesDisponibles'
        )));
    }

    public function update(Request $request, $id)
    {
        $analisis = AnalisisCentralHidraulica::findOrFail($id);
        $payload = $this->validateAnalisisPayload($request, $analisis);
        $payload['evidencia_fotos'] = array_values(array_merge(
            $analisis->evidencia_fotos ?? [],
            $this->guardarEvidencias($request)
        ));

        $analisis->update($payload);
        $this->resolverPendientesPorCambio($analisis);
        $mensajeIa = $this->procesarMantenimientoAutomaticoSafely($analisis->fresh(['linea', 'configuracion', 'componente', 'usuario']));

        $response = redirect()
            ->route($this->routeName('show'), $analisis->id)
            ->with('success', 'Analisis de central hidraulica actualizado correctamente.');

        return $mensajeIa ? $response->with('warning', $mensajeIa) : $response;
    }

    public function destroy(Request $request, $id)
    {
        $analisis = AnalisisCentralHidraulica::findOrFail($id);
        $lineaId = $analisis->linea_id;
        $analisis->delete();

        return redirect()
            ->route($this->routeName('index'), ['linea_id' => $lineaId])
            ->with('success', 'Analisis de central hidraulica eliminado correctamente.');
    }

    public function historial(Request $request)
    {
        $lineas = $this->getLineasPasteurizadora();
        $analisis = $this->filteredAnalisisQuery($request)
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->get();

        return view('pasteurizadora.central-hidraulica.historial', $this->sharedViewData(compact('analisis', 'lineas')));
    }

    public function historicoRevisados(Request $request)
    {
        $lineas = $this->getLineasPasteurizadora();
        $lineaSeleccionada = $request->filled('linea_id')
            ? $lineas->firstWhere('id', (int) $request->linea_id)
            : $lineas->first();
        $lineaSeleccionada ??= $lineas->first();
        $resumenCentral = $this->buildSeguimientoCentral($lineaSeleccionada ? collect([$lineaSeleccionada]) : collect());

        return view('historico-revisados.central-hidraulica.index', $this->sharedViewData(compact(
            'lineas',
            'lineaSeleccionada',
            'resumenCentral'
        )));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $analisis = $this->filteredAnalisisQuery($request)
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->get();

        return response()->streamDownload(function () use ($analisis): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Pasteurizador',
                'Piso',
                'Lado',
                'Componente',
                'Cantidad revisada',
                'Total configurado',
                'Estado',
                'Orden',
                'Fecha',
                'Actividad',
                'Observaciones',
            ]);

            foreach ($analisis as $item) {
                fputcsv($handle, [
                    $item->linea?->nombre,
                    $item->piso_label,
                    $item->lado ? $item->lado_label : '',
                    $item->componente_nombre,
                    $item->cantidad_componentes_revisados,
                    $item->total_componentes,
                    $item->estado,
                    $item->numero_orden,
                    $item->fecha_analisis?->format('Y-m-d'),
                    $item->actividad,
                    $item->observaciones,
                ]);
            }

            fclose($handle);
        }, 'central-hidraulica-pasteurizadora.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $analisis = $this->filteredAnalisisQuery($request)
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->get();

        return Pdf::loadHTML($this->renderPdfHtml($analisis))
            ->setPaper('letter', 'landscape')
            ->download('central-hidraulica-pasteurizadora.pdf');
    }

    public function exportProcess(Request $request)
    {
        return $request->input('formato') === 'pdf'
            ? $this->exportPdf($request)
            : $this->exportExcel($request);
    }

    public function getComponentesPorLineaAjax(Request $request)
    {
        $linea = Linea::find($request->input('linea_id'));

        if (!$linea) {
            return response()->json(['componentes' => []]);
        }

        return response()->json([
            'componentes' => $this->configuracionesParaLinea($linea, $request->input('piso'))
                ->map(fn (CentralHidraulicaConfiguracion $config) => $this->configuracionPayload($config))
                ->values(),
        ]);
    }

    public function getRemainingComponentsAjax(Request $request)
    {
        $config = CentralHidraulicaConfiguracion::find($request->input('configuracion_id'));

        return response()->json([
            'componentes_disponibles' => $config
                ? $this->componentesDisponiblesPara($config, $request->input('lado'))
                : [],
        ]);
    }

    public function getRevisionContextAjax(Request $request)
    {
        $config = CentralHidraulicaConfiguracion::with('componente')->find($request->input('configuracion_id'));

        if (!$config) {
            return response()->json(['message' => 'Configuracion no encontrada.'], 404);
        }

        return response()->json([
            'configuracion' => $this->configuracionPayload($config),
            'componentes_disponibles' => $this->componentesDisponiblesPara($config, $request->input('lado')),
        ]);
    }

    public function getPiezasDisponiblesAjax(Request $request)
    {
        return $this->getRemainingComponentsAjax($request);
    }

    public function getActividadesPorContexto(Request $request)
    {
        $configId = $request->input('configuracion_id');

        return response()->json([
            'actividades' => AnalisisCentralHidraulica::query()
                ->when($configId, fn ($query) => $query->where('configuracion_id', $configId))
                ->whereNotNull('actividad')
                ->latest('created_at')
                ->limit(8)
                ->pluck('actividad')
                ->unique()
                ->values(),
        ]);
    }

    public function getEstadisticasComponentesAjax(Request $request)
    {
        $linea = Linea::find($request->input('linea_id'));

        if (!$linea) {
            return response()->json(['estadisticas' => []]);
        }

        return response()->json([
            'estadisticas' => $this->buildSeguimientoCentral(collect([$linea]))[$linea->id] ?? [],
        ]);
    }

    public function deleteFoto($id, $fotoIndex)
    {
        $analisis = AnalisisCentralHidraulica::findOrFail($id);
        $fotos = array_values($analisis->evidencia_fotos ?? []);

        if (!isset($fotos[$fotoIndex])) {
            abort(404);
        }

        $this->eliminarEvidencia($fotos[$fotoIndex]);
        unset($fotos[$fotoIndex]);
        $analisis->update(['evidencia_fotos' => array_values($fotos)]);

        return back()->with('success', 'Evidencia eliminada correctamente.');
    }

    private function renderCreateView(Linea $linea, Request $request, bool $modoQuick)
    {
        if (!$this->lineaTieneConfiguracion($linea)) {
            abort(404, 'Esta pasteurizadora no tiene configuracion de central hidraulica.');
        }

        $configuraciones = $this->configuracionesParaLinea($linea);
        $view = $modoQuick
            ? 'pasteurizadora.central-hidraulica.create-quick'
            : 'pasteurizadora.central-hidraulica.create';

        return view($view, $this->sharedViewData([
            'linea' => $linea,
            'configuraciones' => $configuraciones,
            'modoQuick' => $modoQuick,
            'fechaSugerida' => $request->get('fecha', now()->toDateString()),
        ]));
    }

    private function guardarAnalisis(Request $request, string $tipoRegistro): AnalisisCentralHidraulica
    {
        $payload = $this->validateAnalisisPayload($request);
        $payload['tipo_registro'] = $tipoRegistro;
        $payload['usuario_id'] = $request->user()?->id;
        $payload['responsable'] = $request->user()?->name;
        $payload['evidencia_fotos'] = $this->guardarEvidencias($request);

        $analisis = DB::transaction(function () use ($payload) {
            $analisis = AnalisisCentralHidraulica::create($payload);
            $this->resolverPendientesPorCambio($analisis);

            return $analisis;
        });

        return $analisis->load(['linea', 'configuracion', 'componente']);
    }

    private function validateAnalisisPayload(Request $request, ?AnalisisCentralHidraulica $analisisActual = null): array
    {
        $validated = $request->validate([
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
            'configuracion_id' => ['required', 'integer', 'exists:central_hidraulica_configuraciones,id'],
            'lado' => ['nullable', 'string', Rule::in(array_keys(AnalisisCentralHidraulica::LADOS))],
            'fecha_analisis' => ['required', 'date'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'numero_orden' => ['nullable', 'digits_between:1,8'],
            'estado' => ['required', 'string', Rule::in(AnalisisCentralHidraulica::ESTADOS)],
            'actividad' => ['required', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'cantidad_componentes_revisados' => ['nullable', 'integer', 'min:0'],
            'componentes_revisados' => ['nullable'],
            'evidencia_fotos' => ['nullable', 'array'],
            'evidencia_fotos.*' => $this->evidenciaFotoRules(),
        ]);

        $this->validarPesoTotalEvidencias($request);

        $linea = Linea::findOrFail($validated['linea_id']);
        $configuracion = CentralHidraulicaConfiguracion::with('componente')->findOrFail($validated['configuracion_id']);

        if ($configuracion->pasteurizador !== $linea->nombre) {
            throw ValidationException::withMessages([
                'configuracion_id' => 'El componente seleccionado no pertenece a la pasteurizadora indicada.',
            ]);
        }

        $lado = AnalisisCentralHidraulica::normalizarLado($validated['lado'] ?? null);

        if ($configuracion->lado_requerido && !$lado) {
            throw ValidationException::withMessages([
                'lado' => 'Seleccione el lado para este componente.',
            ]);
        }

        if (!$configuracion->lado_requerido) {
            $lado = null;
        }

        $total = $configuracion->cantidad;
        $componentesSeleccionados = [];
        $cantidadRevisada = (int) ($validated['cantidad_componentes_revisados'] ?? 0);
        $esContabilizable = $configuracion->es_contabilizable;
        $usaChecklist = $esContabilizable && $total !== null && $total > 1 && $total <= 24;

        if ($usaChecklist) {
            $componentesSeleccionados = AnalisisCentralHidraulica::normalizarComponentesRevisados(
                $request->input('componentes_revisados'),
                $total
            );

            if (empty($componentesSeleccionados)) {
                throw ValidationException::withMessages([
                    'componentes_revisados' => 'Seleccione al menos una pieza revisada.',
                ]);
            }

            $disponibles = $this->componentesDisponiblesPara($configuracion, $lado, $analisisActual?->id, $linea->id);
            $invalidos = array_values(array_diff($componentesSeleccionados, $disponibles));

            if (!empty($invalidos)) {
                throw ValidationException::withMessages([
                    'componentes_revisados' => 'La seleccion incluye piezas ya revisadas en el ciclo actual.',
                ]);
            }

            $cantidadRevisada = count($componentesSeleccionados);
        } elseif (!$esContabilizable) {
            $cantidadRevisada = (int) ($total ?: CentralHidraulicaConfiguracion::ACEITE_LITROS_DEFAULT);
        } elseif ($total !== null) {
            if ($cantidadRevisada < 1) {
                throw ValidationException::withMessages([
                    'cantidad_componentes_revisados' => 'Capture la cantidad revisada.',
                ]);
            }

            if ($cantidadRevisada > $total) {
                throw ValidationException::withMessages([
                    'cantidad_componentes_revisados' => 'La cantidad revisada no puede superar el total configurado.',
                ]);
            }
        }

        return [
            'linea_id' => $linea->id,
            'configuracion_id' => $configuracion->id,
            'componente_id' => $configuracion->componente_id,
            'piso' => $configuracion->piso,
            'lado' => $lado,
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'fecha_analisis' => $validated['fecha_analisis'],
            'numero_orden' => blank($validated['numero_orden'] ?? null) ? null : $validated['numero_orden'],
            'estado' => $validated['estado'],
            'actividad' => $validated['actividad'],
            'observaciones' => $validated['observaciones'] ?? null,
            'cantidad_componentes_revisados' => $cantidadRevisada,
            'total_componentes' => $total,
            'componentes_revisados' => $usaChecklist ? $componentesSeleccionados : null,
        ];
    }

    private function guardarEvidencias(Request $request): array
    {
        $fotos = [];

        foreach ($request->file('evidencia_fotos', []) as $foto) {
            if (!$foto || !$foto->isValid()) {
                continue;
            }

            $fotos[] = app(ImageEvidenceOptimizer::class)->store($foto, self::EVIDENCIAS_DIR);
        }

        return $fotos;
    }

    private function evidenciaFotoRules(): array
    {
        return [
            'nullable',
            'file',
            'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/x-ms-bmp',
            'extensions:jpg,jpeg,png,gif,webp,bmp',
            'max:' . ImageEvidenceOptimizer::UPLOAD_MAX_KILOBYTES,
        ];
    }

    private function validarPesoTotalEvidencias(Request $request): void
    {
        $totalKilobytes = collect($request->file('evidencia_fotos', []))
            ->filter(fn ($foto) => $foto && $foto->isValid())
            ->sum(fn ($foto) => (int) ceil($foto->getSize() / 1024));

        if ($totalKilobytes > self::EVIDENCIAS_MAX_TOTAL_KILOBYTES) {
            throw ValidationException::withMessages([
                'evidencia_fotos' => 'El total de imagenes no puede superar 5 MB.',
            ]);
        }
    }

    private function eliminarEvidencia(?string $foto): void
    {
        if (!$foto) {
            return;
        }

        $path = public_path('storage/' . ltrim(str_replace('\\', '/', $foto), '/'));

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function resolverPendientesPorCambio(AnalisisCentralHidraulica $nuevoAnalisis): void
    {
        if (!AnalisisCentralHidraulica::esEstadoCambiado($nuevoAnalisis->estado)) {
            return;
        }

        AnalisisCentralHidraulica::query()
            ->where('id', '!=', $nuevoAnalisis->id)
            ->where('linea_id', $nuevoAnalisis->linea_id)
            ->where('configuracion_id', $nuevoAnalisis->configuracion_id)
            ->where('lado', $nuevoAnalisis->lado)
            ->whereIn('estado', AnalisisPasteurizadora::estadosDanado())
            ->where('resuelto_por_cambio', false)
            ->get()
            ->each(fn (AnalisisCentralHidraulica $pendiente) => $pendiente->marcarComoResuelto($nuevoAnalisis));
    }

    private function filteredAnalisisQuery(Request $request)
    {
        $estadoGrupo = $request->filled('estado') ? null : $request->input('estado_grupo');

        return AnalisisCentralHidraulica::with(['linea', 'configuracion', 'componente', 'usuario'])
            ->when($request->filled('linea_id') && $request->linea_id !== 'todas', fn ($query) => $query->where('linea_id', $request->linea_id))
            ->when($request->filled('piso'), fn ($query) => $query->where('piso', CentralHidraulicaConfiguracion::normalizarPiso($request->piso)))
            ->when($request->filled('lado'), fn ($query) => $query->where('lado', AnalisisCentralHidraulica::normalizarLado($request->lado)))
            ->when($request->filled('componente_id'), fn ($query) => $query->where('componente_id', $request->componente_id))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->when($estadoGrupo === 'desgaste', fn ($query) => $query->whereIn('estado', AnalisisCentralHidraulica::ESTADOS_DESGASTE))
            ->when($estadoGrupo === 'danado', fn ($query) => $query->whereIn('estado', AnalisisPasteurizadora::estadosDanado()))
            ->when($request->filled('fecha'), function ($query) use ($request) {
                $query->whereYear('fecha_analisis', substr($request->fecha, 0, 4))
                    ->whereMonth('fecha_analisis', substr($request->fecha, 5, 2));
            });
    }

    private function historialContexto(AnalisisCentralHidraulica $analisis)
    {
        return AnalisisCentralHidraulica::with(['linea', 'configuracion', 'componente', 'usuario'])
            ->where('linea_id', $analisis->linea_id)
            ->where('configuracion_id', $analisis->configuracion_id)
            ->where('lado', $analisis->lado);
    }

    private function buildSeguimientoCentral($lineas): array
    {
        $lineas = collect($lineas)->filter();
        $resultado = [];

        foreach ($lineas as $linea) {
            $configuraciones = $this->configuracionesParaLinea($linea);
            $resumenLinea = [
                'linea' => $linea,
                'pisos' => [],
                'totales' => [
                    'componentes' => 0,
                    'revisados' => 0,
                    'pendientes' => 0,
                    'porcentaje' => 0,
                ],
            ];

            foreach (CentralHidraulicaConfiguracion::PISOS as $piso => $pisoLabel) {
                $componentesPiso = [];
                $configsPiso = $configuraciones
                    ->where('piso', $piso)
                    ->sortBy(fn (CentralHidraulicaConfiguracion $config) => sprintf(
                        '%d-%03d-%06d',
                        $config->lado_requerido ? 0 : 1,
                        (int) $config->orden,
                        (int) $config->id
                    ))
                    ->values();

                foreach ($configsPiso as $config) {
                    $contextos = $config->lado_requerido ? array_keys(AnalisisCentralHidraulica::LADOS) : [null];
                    $contextosData = [];

                    foreach ($contextos as $lado) {
                        $contextosData[] = $this->resumenContexto($linea, $config, $lado);
                    }

                    $componentesPiso[] = [
                        'configuracion' => $config,
                        'contextos' => $contextosData,
                    ];

                    if ($config->es_contabilizable) {
                        foreach ($contextosData as $contexto) {
                            if ($contexto['total'] !== null) {
                                $resumenLinea['totales']['componentes'] += $contexto['total'];
                                $resumenLinea['totales']['revisados'] += min($contexto['revisado'], $contexto['total']);
                            }
                        }
                    }
                }

                $resumenLinea['pisos'][$piso] = [
                    'key' => $piso,
                    'label' => $pisoLabel,
                    'componentes' => $componentesPiso,
                ];
            }

            $total = $resumenLinea['totales']['componentes'];
            $revisados = $resumenLinea['totales']['revisados'];
            $resumenLinea['totales']['pendientes'] = max(0, $total - $revisados);
            $resumenLinea['totales']['porcentaje'] = $total > 0 ? (int) round(($revisados / $total) * 100) : 0;
            $resultado[$linea->id] = $resumenLinea;
        }

        return $resultado;
    }

    private function resumenContexto(Linea $linea, CentralHidraulicaConfiguracion $config, ?string $lado): array
    {
        $query = AnalisisCentralHidraulica::with('usuario')
            ->where('linea_id', $linea->id)
            ->where('configuracion_id', $config->id)
            ->where('lado', $lado)
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id');

        $registros = $query->get();
        $ultimo = $registros->last();
        $total = $config->cantidad;
        $revisado = 0;
        $porcentaje = null;
        $pendientes = [];

        if ($total !== null && $total > 0 && $total <= 24) {
            $resumen = AnalisisCentralHidraulica::buildResumenCiclo($registros, $total)['resumen_visible'];
            $revisado = $resumen['cantidad_revisada'];
            $porcentaje = $resumen['porcentaje'];
            $pendientes = $resumen['pendientes'];
        } elseif ($total !== null && $total > 0) {
            $revisado = min((int) ($ultimo?->cantidad_componentes_revisados ?? 0), $total);
            $porcentaje = (int) round(($revisado / $total) * 100);
        } else {
            $revisado = (int) ($ultimo?->cantidad_componentes_revisados ?? 0);
        }

        return [
            'lado' => $lado,
            'lado_label' => $lado ? AnalisisCentralHidraulica::ladoLabel($lado) : null,
            'total' => $total,
            'revisado' => $revisado,
            'porcentaje' => $porcentaje,
            'pendientes' => $pendientes,
            'ultimo' => $ultimo,
            'estado' => $ultimo?->estado,
            'registros_count' => $registros->count(),
            'contabilizable' => $config->es_contabilizable,
            'es_revision_aceite' => $config->es_revision_aceite,
        ];
    }

    private function componentesDisponiblesPara(
        CentralHidraulicaConfiguracion $config,
        ?string $lado,
        ?int $excludeId = null,
        ?int $lineaId = null
    ): array {
        if (!$lineaId) {
            $linea = Linea::where('nombre', $config->pasteurizador)->first();
            $lineaId = $linea?->id;
        }

        if (!$lineaId || !$config->es_contabilizable || !$config->cantidad || $config->cantidad > 24) {
            return [];
        }

        return AnalisisCentralHidraulica::getPiezasDisponiblesParaRegistro(
            $lineaId,
            $config->id,
            $config->lado_requerido ? $lado : null,
            $excludeId,
            $config->cantidad
        );
    }

    private function configuracionesParaLinea(Linea $linea, ?string $piso = null)
    {
        return CentralHidraulicaConfiguracion::with('componente')
            ->activas()
            ->paraPasteurizador($linea->nombre)
            ->paraPiso($piso)
            ->get()
            ->sortBy(fn (CentralHidraulicaConfiguracion $config) => $this->pisoOrden($config->piso) . '-' . str_pad((string) $config->orden, 3, '0', STR_PAD_LEFT))
            ->values();
    }

    private function lineaTieneConfiguracion(Linea $linea): bool
    {
        return CentralHidraulicaConfiguracion::query()
            ->paraPasteurizador($linea->nombre)
            ->exists();
    }

    private function getLineasPasteurizadora()
    {
        $nombresConfigurados = CentralHidraulicaConfiguracion::query()
            ->distinct()
            ->pluck('pasteurizador')
            ->all();

        return Linea::whereIn('nombre', $nombresConfigurados)
            ->get()
            ->sortBy(fn (Linea $linea) => array_search($linea->nombre, CentralHidraulicaConfiguracion::PASTEURIZADORES_EXCEL, true))
            ->values();
    }

    private function resolverLineaPasteurizadora($linea): ?Linea
    {
        return $linea instanceof Linea ? $linea : Linea::find($linea);
    }

    private function configuracionPayload(CentralHidraulicaConfiguracion $config): array
    {
        return [
            'id' => $config->id,
            'pasteurizador' => $config->pasteurizador,
            'piso' => $config->piso,
            'piso_label' => $config->piso_label,
            'componente_id' => $config->componente_id,
            'codigo' => $config->componente?->codigo,
            'nombre' => $config->componente_nombre,
            'cantidad' => $config->cantidad,
            'unidad' => $config->unidad,
            'cantidad_label' => $config->cantidad_label,
            'lado_requerido' => $config->lado_requerido,
            'detalle_excel' => $config->detalle_excel,
            'contabilizable' => $config->es_contabilizable,
            'es_revision_aceite' => $config->es_revision_aceite,
            'tipo_elemento_label' => $config->tipo_elemento_label,
        ];
    }

    private function sharedViewData(array $data = []): array
    {
        return array_merge($data, [
            'analisisRoutePrefix' => 'pasteurizadora.central-hidraulica',
            'analisisRoute' => fn ($name, $params = []) => route($this->routeName($name), $params),
            'analisisTitulo' => 'Pasteurizador - Central Hidraulica',
            'pisosCentral' => CentralHidraulicaConfiguracion::PISOS,
            'ladosCentral' => AnalisisCentralHidraulica::LADOS,
            'estadoOpciones' => AnalisisCentralHidraulica::getEstadoOpciones(),
            'componentesCentral' => CentralHidraulicaComponente::activos()->orderBy('orden')->get(),
            'canDeleteAnalysis' => auth()->user()?->canDeletePasteurizadoraAnalysis() ?? false,
        ]);
    }

    private function procesarMantenimientoAutomaticoSafely(?AnalisisCentralHidraulica $analisis): ?string
    {
        if (!$analisis) {
            return null;
        }

        try {
            app(PasteurizadoraMaintenanceOrchestrator::class)->processCentralAnalysis($analisis);

            return null;
        } catch (Throwable $exception) {
            Log::warning('No se pudo procesar mantenimiento automatico IA de central hidraulica.', [
                'analisis_id' => $analisis->id,
                'linea_id' => $analisis->linea_id,
                'configuracion_id' => $analisis->configuracion_id,
                'exception' => $exception->getMessage(),
            ]);

            return 'La sugerencia IA no pudo generarse en este momento; revisa la configuracion SSL/API.';
        }
    }

    private function routeName(string $name): string
    {
        return 'pasteurizadora.central-hidraulica.' . $name;
    }

    private function pisoOrden(?string $piso): string
    {
        return CentralHidraulicaConfiguracion::normalizarPiso($piso) === CentralHidraulicaConfiguracion::PISO_SUPERIOR
            ? '1'
            : '2';
    }

    private function renderPdfHtml($analisis): string
    {
        $rows = collect($analisis)->map(function (AnalisisCentralHidraulica $item): string {
            return '<tr>'
                . '<td>' . e($item->linea?->nombre) . '</td>'
                . '<td>' . e($item->piso_label) . '</td>'
                . '<td>' . e($item->lado ? $item->lado_label : '') . '</td>'
                . '<td>' . e($item->componente_nombre) . '</td>'
                . '<td>' . e((string) $item->cantidad_componentes_revisados) . '</td>'
                . '<td>' . e((string) $item->total_componentes) . '</td>'
                . '<td>' . e($item->estado) . '</td>'
                . '<td>' . e($item->fecha_analisis?->format('d/m/Y')) . '</td>'
                . '</tr>';
        })->implode('');

        return '<html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111827;}'
            . 'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #d1d5db;padding:6px;text-align:left;}'
            . 'th{background:#eff6ff;color:#1e3a8a;}h1{font-size:18px;margin-bottom:12px;}'
            . '</style></head><body>'
            . '<h1>Pasteurizador - Central Hidraulica</h1>'
            . '<table><thead><tr><th>Pasteurizador</th><th>Piso</th><th>Lado</th><th>Componente</th><th>Revisado</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table></body></html>';
    }
}
