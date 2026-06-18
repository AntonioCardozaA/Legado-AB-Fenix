<?php

namespace App\Http\Controllers;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use App\Exports\AnalisisPasteurizadoraExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class AnalisisPasteurizadoraController extends Controller
{
    private const EVIDENCIAS_PASTEURIZADORA_DIR = 'analisis-pasteurizadora';
    private const PASTEURIZADORAS_PERMITIDAS = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];

    protected string $areaAnalisis = AnalisisPasteurizadora::AREA_MECANICA;
    protected string $areaLabel = 'Mecánica';
    protected string $routeNamePrefix = 'pasteurizadora.analisis-pasteurizadora';
    protected string $baseUrl = '/pasteurizadora/analisis-pasteurizadora';
    protected string $viewPathPrefix = 'pasteurizadora.analisis-pasteurizadora';
    protected string $historicoViewPath = 'historico-revisados.pasteurizadora.index';
    protected string $dashboardRouteName = 'pasteurizadora.dashboard';
    protected string $tituloAnalisis = 'Análisis de Pasteurizadoras';
    protected string $tituloHistorial = 'Historial de Análisis - Pasteurizadora';
    protected string $tituloHistoricoRevisados = 'Histórico de Revisados - Pasteurizadora';
    protected string $evidenciasDir = self::EVIDENCIAS_PASTEURIZADORA_DIR;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!$request->user()?->canAccessPasteurizadoraArea($this->currentArea())) {
                abort(403, 'No tienes permiso para acceder a esta seccion de Pasteurizadora.');
            }

            return $next($request);
        });
    }

    protected function currentArea(): string
    {
        return AnalisisPasteurizadora::normalizarArea($this->areaAnalisis);
    }

    protected function analisisQuery()
    {
        return AnalisisPasteurizadora::queryForArea($this->currentArea());
    }

    protected function routeName(string $name): string
    {
        return $this->routeNamePrefix . '.' . $name;
    }

    protected function viewName(string $name): string
    {
        return $this->viewPathPrefix . '.' . $name;
    }

    protected function sharedViewData(array $data = []): array
    {
        return array_merge($data, [
            'analisisArea' => $this->currentArea(),
            'analisisAreaLabel' => $this->areaLabel,
            'analisisRoutePrefix' => $this->routeNamePrefix,
            'analisisBaseUrl' => $this->baseUrl,
            'analisisDashboardRoute' => $this->dashboardRouteName,
            'analisisTitulo' => $this->tituloAnalisis,
            'historialTitulo' => $this->tituloHistorial,
            'historicoRevisadosTitulo' => $this->tituloHistoricoRevisados,
        ]);
    }

    protected function renderView(string $name, array $data = [])
    {
        return view($this->viewName($name), $this->sharedViewData($data));
    }

    protected function renderExternalView(string $view, array $data = [])
    {
        return view($view, $this->sharedViewData($data));
    }

    private function getRevisionesAgrupadasHistorico($lineas): array
    {
        if ($lineas->isEmpty()) {
            return [];
        }

        // Usar el método optimizado del modelo
        return AnalisisPasteurizadora::getComponentesRevisadosAgrupadosParaHistorico($lineas->pluck('id')->all(), $this->currentArea());
    }

    private function buildHistoricoLinea(Linea $linea, array $revisionesAgrupadas, &$componentesModulos, array &$estadisticas): array
    {
        $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
        $modulos = [];
        $totalLinea = 0;
        $totalRevisadoLinea = 0;

        for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
            $niveles = [];
            $moduleTotal = 0;
            $moduleRevisado = 0;
            $moduleSides = [];

            foreach (AnalisisPasteurizadora::NIVELES as $nivel) {
                $nivelTotal = 0;
                $nivelRevisado = 0;
                $ladosNivel = [];

                foreach (AnalisisPasteurizadora::LADOS as $lado) {
                    $componentesLado = [];
                    $ladoTotal = 0;
                    $ladoRevisado = 0;

                    foreach ($componentes as $codigo => $compData) {
                        if (
                            AnalisisPasteurizadora::esBrazoTorsion($codigo)
                            && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                        ) {
                            continue;
                        }

                        $total = (int) ($compData['cantidad'] ?? 0);
                        $revisadas = min(
                            $revisionesAgrupadas[$this->buildHistoricoKey($linea->id, $codigo, $modulo, $nivel, $lado)] ?? 0,
                            $total
                        );
                        $porcentaje = $total > 0 ? round(($revisadas / $total) * 100) : 0;
                        $color = $this->getColorClassByPercentage($porcentaje);

                        $estadisticas[$linea->id][$codigo][$modulo][$nivel][$lado] = [
                            'total' => $total,
                            'revisadas' => $revisadas,
                            'porcentaje' => $porcentaje,
                            'color' => $color,
                        ];

                        $componentesModulos->push([
                            'linea_id' => $linea->id,
                            'linea_nombre' => $linea->nombre,
                            'codigo' => $codigo,
                            'nombre' => $compData['nombre'],
                            'modulo' => $modulo,
                            'nivel' => $nivel,
                            'lado' => $lado,
                            'cantidad_total' => $total,
                        ]);

                        $componentesLado[] = [
                            'codigo' => $codigo,
                            'nombre' => $compData['nombre'],
                            'total' => $total,
                            'revisadas' => $revisadas,
                            'porcentaje' => $porcentaje,
                            'color' => $color,
                        ];

                        $ladoTotal += $total;
                        $ladoRevisado += $revisadas;
                    }

                    $ladoPorcentaje = $ladoTotal > 0 ? round(($ladoRevisado / $ladoTotal) * 100) : 0;

                    $ladosNivel[] = [
                        'key' => $lado,
                        'label' => $lado === 'VAPOR' ? 'Lado vapor' : 'Lado pasillo',
                        'total' => $ladoTotal,
                        'revisado' => $ladoRevisado,
                        'porcentaje' => $ladoPorcentaje,
                        'color' => $this->getColorClassByPercentage($ladoPorcentaje),
                        'componentes' => $componentesLado,
                    ];

                    $nivelTotal += $ladoTotal;
                    $nivelRevisado += $ladoRevisado;
                    $moduleSides[$lado] = [
                        'total' => ($moduleSides[$lado]['total'] ?? 0) + $ladoTotal,
                        'revisado' => ($moduleSides[$lado]['revisado'] ?? 0) + $ladoRevisado,
                    ];
                }

                $nivelPorcentaje = $nivelTotal > 0 ? round(($nivelRevisado / $nivelTotal) * 100) : 0;

                $niveles[] = [
                    'key' => $nivel,
                    'label' => $nivel === 'SUPERIOR' ? 'Nivel superior' : 'Nivel inferior',
                    'total' => $nivelTotal,
                    'revisado' => $nivelRevisado,
                    'porcentaje' => $nivelPorcentaje,
                    'color' => $this->getColorClassByPercentage($nivelPorcentaje),
                    'lados' => $ladosNivel,
                ];

                $moduleTotal += $nivelTotal;
                $moduleRevisado += $nivelRevisado;
            }

            $modulePorcentaje = $moduleTotal > 0 ? round(($moduleRevisado / $moduleTotal) * 100) : 0;
            $moduleSides = collect($moduleSides)
                ->map(function ($sideData, $lado) {
                    $porcentaje = $sideData['total'] > 0 ? round(($sideData['revisado'] / $sideData['total']) * 100) : 0;

                    return [
                        'key' => $lado,
                        'label' => $lado === 'VAPOR' ? 'Lado vapor' : 'Lado pasillo',
                        'total' => $sideData['total'],
                        'revisado' => $sideData['revisado'],
                        'porcentaje' => $porcentaje,
                        'color' => $this->getColorClassByPercentage($porcentaje),
                    ];
                })
                ->values()
                ->all();

            $modulos[] = [
                'numero' => $modulo,
                'total' => $moduleTotal,
                'revisado' => $moduleRevisado,
                'porcentaje' => $modulePorcentaje,
                'color' => $this->getColorClassByPercentage($modulePorcentaje),
                'lados' => $moduleSides,
                'niveles' => $niveles,
            ];

            $totalLinea += $moduleTotal;
            $totalRevisadoLinea += $moduleRevisado;
        }

        return [
            'linea_id' => $linea->id,
            'linea_nombre' => $linea->nombre,
            'totales' => [
                'total' => $totalLinea,
                'revisado' => $totalRevisadoLinea,
            ],
            'modulos' => $modulos,
        ];
    }

    private function buildHistoricoKey($lineaId, $componente, $modulo, $nivel, $lado): string
    {
        return implode('|', [
            $lineaId,
            strtoupper((string) $componente),
            (int) $modulo,
            strtoupper(trim((string) $nivel)),
            strtoupper(trim((string) $lado)),
        ]);
    }

    private function getColorClassByPercentage(int $porcentaje): string
    {
        if ($porcentaje >= 80) {
            return 'success';
        }

        if ($porcentaje >= 50) {
            return 'info';
        }

        if ($porcentaje >= 20) {
            return 'warning';
        }

        return 'danger';
    }

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request)
    {
        $lineas = Linea::all();

        $lineaId = $request->get('linea_id', 'todas');
        $lineaSeleccionada = $lineaId !== 'todas' ? Linea::find($lineaId) : null;

        $query = $this->analisisQuery()
            ->with(['linea', 'usuario'])
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

            // Si no es de hoy, solo mostrar si está "Cambiado", "Buen estado" o en revisión operativa
            return in_array($item->estado, [
                AnalisisPasteurizadora::ESTADO_CAMBIADO,
                AnalisisPasteurizadora::ESTADO_BUENO,
                AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION,
            ], true);
        });

        $totalAnalisis = $analisis->count();
        $totalDanados = $analisis->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count();
        $totalCambiados = $analisis->where('estado', AnalisisPasteurizadora::ESTADO_CAMBIADO)->count();
        $totalRequiereRevision = $analisis->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count();

        // Filtrar solo líneas de pasteurizadora (P-03 a P-14)
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineasFiltradas = $lineas->filter(function($linea) use ($pasteurizadorasPermitidas) {
            return in_array($linea->nombre, $pasteurizadorasPermitidas);
        })->values();

        $mostrarTodas = !request('linea_id') || request('linea_id') === 'todas';

        $seguimientoPasteurizadora = $this->buildSeguimientoPasteurizadora($lineasFiltradas);

        return $this->renderView('index', compact(
            'analisis', 'lineasFiltradas', 'totalAnalisis', 'totalDanados', 'totalCambiados',
            'totalRequiereRevision', 'lineaSeleccionada', 'mostrarTodas', 'seguimientoPasteurizadora'
        ));
    }

        public function dashboard()
{
    $analisis = $this->analisisQuery()->with('linea')->latest()->take(10)->get();

    $total = $this->analisisQuery()->count();
    $danados = $this->analisisQuery()->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count();
    $cambiados = $this->analisisQuery()->where('estado', AnalisisPasteurizadora::ESTADO_CAMBIADO)->count();
    $requiereRevision = $this->analisisQuery()->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count();

    return view('pasteurizadora.dashboard', $this->sharedViewData(compact(
        'analisis',
        'total',
        'danados',
        'cambiados',
        'requiereRevision'
    )));
}
    // ============================================================
    // CREATE
    // ============================================================

    public function create(Request $request)
    {
        $lineaId = $request->get('linea_id');

        if (!$lineaId) {
            return redirect()->route($this->routeName('select-linea'))
                ->with('error', 'Debe seleccionar una línea primero');
        }

        $linea = Linea::findOrFail($lineaId);
        return $this->renderView('create', $this->buildCreateViewData($linea, $request));
    }

    public function createQuick(Request $request)
    {
        $linea = Linea::findOrFail($request->linea_id);

        return $this->renderView('create-quick', $this->buildCreateViewData($linea, $request, true));
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
            'numero_orden' => 'nullable|string|max:50',
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
                    $fotosPaths[] = $this->guardarEvidenciaPasteurizadora($foto);
                }
            }
        }

        // Crear el registro
        $analisis = AnalisisPasteurizadora::create([
            'area' => $this->currentArea(),
            'linea_id' => $validated['linea_id'],
            'modulo' => $validated['modulo'],
            'nivel' => $validated['nivel'],
            'componente' => $validated['componente'],
            'lado' => $validated['lado'],
            'fecha_analisis' => $validated['fecha_analisis'],
            'numero_orden' => $validated['numero_orden'] ?? null,
            'estado' => $validated['estado'],
            'actividad' => $validated['actividad'],
            'evidencia_fotos' => $fotosPaths,
            'componentes_revisados' => $seleccionComponentes['componentes_revisados'],
            'cantidad_componentes_revisados' => count($seleccionComponentes['componentes_revisados']),
            'total_componentes' => $seleccionComponentes['total_componentes'],
            'brazos_torsion' => $seleccionComponentes['brazos_torsion'],
            'total_brazos_torsion' => $seleccionComponentes['total_brazos_torsion'],
            'usuario_id' => $request->user()?->id,
            'resuelto_por_cambio' => false,
        ]);

        // Marcar como resuelto si es necesario
        if ($validated['estado'] === AnalisisPasteurizadora::ESTADO_CAMBIADO) {
            $this->marcarRegistrosAnterioresComoResueltos($analisis);
        }

        $siguienteRevision = AnalisisPasteurizadora::getSiguienteRevisionContexto(
            $validated['linea_id'],
            $validated['modulo'],
            $validated['componente'],
            $validated['nivel'],
            $validated['lado'],
            $this->currentArea()
        );

        $mensaje = $siguienteRevision
            ? 'Análisis registrado correctamente. Puedes continuar con la siguiente revisión pendiente desde el tablero principal.'
            : 'Análisis registrado correctamente. Este proceso quedó terminado.';

        // Redirigir al índice con mensaje de éxito
        return redirect()
            ->route($this->routeName('index'), ['linea_id' => $validated['linea_id']])
            ->with('success', $mensaje);
    }

    public function storeQuick(Request $request)
    {
        // Marcar que proviene de create-quick
        $request->merge(['es_quick' => true]);
        return $this->store($request);
    }

    private function guardarEvidenciaPasteurizadora($foto): string
    {
        $directorioRelativo = $this->evidenciasDir;
        $directorioPublico = public_path('storage/' . $directorioRelativo);

        if (!is_dir($directorioPublico)) {
            mkdir($directorioPublico, 0755, true);
        }

        $nombreArchivo = $foto->hashName();
        $foto->move($directorioPublico, $nombreArchivo);

        return $directorioRelativo . '/' . $nombreArchivo;
    }

    private function eliminarEvidenciaPasteurizadora(?string $foto): void
    {
        if (!$foto) {
            return;
        }

        $ruta = public_path('storage/' . ltrim(str_replace('\\', '/', $foto), '/'));

        if (is_file($ruta)) {
            unlink($ruta);
        }
    }



    private function marcarRegistrosAnterioresComoResueltos($nuevoAnalisis)
    {
        $registrosAnteriores = $this->analisisQuery()
            ->where('linea_id', $nuevoAnalisis->linea_id)
            ->where('modulo', $nuevoAnalisis->modulo)
            ->where('componente', $nuevoAnalisis->componente)
            ->whereIn('estado', AnalisisPasteurizadora::estadosDanado())
            ->where('resuelto_por_cambio', false)
            ->when($nuevoAnalisis->id ?? null, fn ($query) => $query->where('id', '!=', $nuevoAnalisis->id))
            ->get();

        foreach ($registrosAnteriores as $registro) {
            $numeroOrden = $nuevoAnalisis->numero_orden ?: 'sin numero de orden';

            $registro->update([
                'resuelto_por_cambio' => true,
                'fecha_resolucion' => now(),
                'nota_resolucion' => "Resuelto por cambio en orden #{$numeroOrden}"
            ]);
        }
    }
    // ============================================================
    // SHOW, EDIT, UPDATE, DELETE
    // ============================================================

    public function show($id)
    {
        $analisis = $this->analisisQuery()->with(['linea', 'usuario'])->findOrFail($id);
        return $this->renderView('show', compact('analisis'));
    }

    public function edit($id)
    {
        $analisis = $this->analisisQuery()->with('usuario')->findOrFail($id);
        $lineas = Linea::all();
        return $this->renderView('edit', compact('analisis', 'lineas'));
    }

    public function update(Request $request, $id)
    {
        $analisis = $this->analisisQuery()->findOrFail($id);

        $validated = $request->validate([
            'modulo' => 'nullable|integer|min:1',
            'componente' => 'nullable|string',
            'nivel' => 'nullable|in:SUPERIOR,INFERIOR',
            'fecha_analisis' => 'nullable|date',
            'numero_orden' => 'nullable|string|max:50',
            'actividad' => 'nullable|string',
            'estado' => 'nullable|string|in:' . implode(',', AnalisisPasteurizadora::ESTADOS),
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'observaciones' => 'nullable|string',
            'componentes_revisados' => 'nullable|array',
            'evidencia_fotos' => 'nullable|array',
            'evidencia_fotos.*' => 'nullable|image|max:5120',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'integer|min:0',
        ]);

        unset($validated['eliminar_fotos'], $validated['responsable']);

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

        if (
            isset($validated['estado'])
            && $validated['estado'] === AnalisisPasteurizadora::ESTADO_CAMBIADO
            && $analisis->estado !== AnalisisPasteurizadora::ESTADO_CAMBIADO
        ) {
            $tempAnalisis = (object)[
                'linea_id' => $analisis->linea_id,
                'modulo' => $validated['modulo'] ?? $analisis->modulo,
                'componente' => $validated['componente'] ?? $analisis->componente,
                'numero_orden' => array_key_exists('numero_orden', $validated) ? $validated['numero_orden'] : $analisis->numero_orden,
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

        $fotosExistentes = $analisis->evidencia_fotos ?? [];
        if (!is_array($fotosExistentes)) {
            $fotosExistentes = json_decode($fotosExistentes, true) ?? [];
        }

        if ($request->filled('eliminar_fotos')) {
            foreach ($request->input('eliminar_fotos', []) as $index) {
                if (isset($fotosExistentes[$index])) {
                    $this->eliminarEvidenciaPasteurizadora($fotosExistentes[$index]);
                    unset($fotosExistentes[$index]);
                }
            }

            $fotosExistentes = array_values($fotosExistentes);
        }

        if ($request->hasFile('evidencia_fotos')) {
            foreach ($request->file('evidencia_fotos', []) as $foto) {
                if ($foto && $foto->isValid()) {
                    $fotosExistentes[] = $this->guardarEvidenciaPasteurizadora($foto);
                }
            }
        }

        $validated['evidencia_fotos'] = $fotosExistentes;

        $analisis->update($validated);

        return redirect()
            ->route($this->routeName('index'), ['linea_id' => $analisis->linea_id])
            ->with('success', 'Análisis actualizado correctamente.');
    }

    public function destroy($id)
    {
        $analisis = $this->analisisQuery()->findOrFail($id);

        if ($analisis->evidencia_fotos) {
            foreach ($analisis->evidencia_fotos as $foto) {
                $this->eliminarEvidenciaPasteurizadora($foto);
            }
        }

        $analisis->delete();

        return redirect()->route($this->routeName('index'))
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

    return $this->renderView('select-linea', compact('lineas'));
}

    public function historial(Request $request)
    {
        $query = $this->analisisQuery()
            ->with(['linea', 'usuario'])
            ->orderBy('fecha_analisis', 'desc');

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
            return in_array($item->estado, [
                AnalisisPasteurizadora::ESTADO_CAMBIADO,
                AnalisisPasteurizadora::ESTADO_BUENO,
                AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION,
            ], true);
        });

        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineas = Linea::whereIn('nombre', $pasteurizadorasPermitidas)->orderBy('nombre')->get();

        return $this->renderView('historial', compact('analisis', 'lineas'));
    }

    // ============================================================
    // PLAN DE ACCIÃ“N
    // ============================================================

    public function planAccion(Request $request)
    {
        return redirect()->route('plan-accion.index', array_filter([
            'tipo' => 'pasteurizadora',
            'linea_id' => $request->get('linea_id'),
        ]));
    }

    public function createPlanAccion(Request $request)
    {
        return redirect()->route('plan-accion.create', array_filter([
            'tipo' => 'pasteurizadora',
            'linea_id' => $request->get('linea_id'),
        ]));
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

        $modulosHistorico = collect();
        $estadisticas = [];
        $totalGeneral = 0;
        $totalRevisado = 0;

        $lineasHistorico = $mostrarTodas
            ? $lineasPasteurizadora
            : collect($lineaSeleccionada ? [$lineaSeleccionada] : []);

        $revisionesAgrupadas = $this->getRevisionesAgrupadasHistorico($lineasHistorico);

        foreach ($lineasHistorico as $linea) {
            $historicoLinea = $this->buildHistoricoLinea(
                $linea,
                $revisionesAgrupadas,
                $componentesModulos,
                $estadisticas
            );

            $modulosHistorico->push($historicoLinea);
            $totalGeneral += $historicoLinea['totales']['total'];
            $totalRevisado += $historicoLinea['totales']['revisado'];
        }

        // Calcular estadísticas
        if ($lineasHistorico->isNotEmpty()) {
            $estadisticas['resumen'] = [
                'total_general' => $totalGeneral,
                'total_revisado' => $totalRevisado,
                'porcentaje_general' => $totalGeneral > 0 ? round(($totalRevisado / $totalGeneral) * 100) : 0,
            ];
        }

        return $this->renderExternalView($this->historicoViewPath, compact(
            'lineas',
            'lineasPasteurizadora',
            'lineaSeleccionada',
            'componentesModulos',
            'modulosHistorico',
            'estadisticas',
            'mostrarTodas'
        ));
    }

    public function exportExcel(Request $request)
    {
        $analisis = $this->getAnalisisPasteurizadoraParaExportar($request);
        $filename = 'analisis_pasteurizadora_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AnalisisPasteurizadoraExport($analisis), $filename);
    }

    public function exportPdf(Request $request)
    {
        $analisis = $this->getAnalisisPasteurizadoraParaExportar($request);
        $linea = $request->filled('linea_id') && $request->linea_id !== 'todas'
            ? Linea::find($request->linea_id)
            : null;
        $fechaInicio = $request->filled('fecha_inicio') ? $request->fecha_inicio : null;
        $fechaFin = $request->filled('fecha_fin') ? $request->fecha_fin : null;
        $tituloDocumento = $linea
            ? 'Reporte de Pasteurizadora ' . $linea->nombre
            : 'Reporte General de Pasteurizadoras';
        $filename = $linea
            ? 'reporte_pasteurizadora_' . $linea->nombre . '_' . now()->format('Ymd_His') . '.pdf'
            : 'reporte_pasteurizadoras_' . now()->format('Ymd_His') . '.pdf';

        return Pdf::loadView('pasteurizadora.pdf.analisis', $this->sharedViewData(compact(
            'analisis',
            'linea',
            'fechaInicio',
            'fechaFin',
            'tituloDocumento'
        )))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function exportProcess(Request $request)
    {
        $formato = $request->get('formato', $request->get('export_format', 'excel'));

        return $formato === 'pdf'
            ? $this->exportPdf($request)
            : $this->exportExcel($request);
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

        $componentesPendientes = AnalisisPasteurizadora::getCantidadComponentesPendientes($lineaId, $modulo, $componente, $lado, $nivel, null, $this->currentArea());
        $cantidadComponentesRevisados = AnalisisPasteurizadora::getCantidadComponentesRevisados($lineaId, $modulo, $componente, $lado, $nivel, null, $this->currentArea());

        // Obtener los componentes ya revisados especÃ­ficos de este lado y nivel
        $componentesYaRevisados = AnalisisPasteurizadora::getComponentesYaRevisados($lineaId, $modulo, $componente, $lado, $nivel, null, $this->currentArea());

        // Obtener lados pendientes
        $ladosPendientes = AnalisisPasteurizadora::getLadosPendientes($lineaId, $modulo, $componente, $nivel, $this->currentArea());

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

    public function getPiezasDisponiblesAjax(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'modulo' => 'required|integer|min:1',
            'componente' => 'required|string',
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'nivel' => 'nullable|in:SUPERIOR,INFERIOR',
        ]);

        $linea = Linea::findOrFail($request->linea_id);
        $resolved = AnalisisPasteurizadora::resolveComponentePorLinea($linea->nombre, $request->componente);

        if (!$resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Componente no valido para la pasteurizadora seleccionada.',
            ], 422);
        }

        $componentesDisponibles = AnalisisPasteurizadora::getComponentesPendientes(
            $linea->id,
            $request->modulo,
            $resolved['key'],
            $request->lado,
            $request->nivel,
            null,
            $this->currentArea()
        );

        return response()->json([
            'success' => true,
            'piezas_disponibles' => $componentesDisponibles,
            'componentes_disponibles' => $componentesDisponibles,
            'total_disponibles' => count($componentesDisponibles),
        ]);
    }

    public function getActividadesPorModulo(Request $request)
    {
        $query = $this->analisisQuery()
            ->when($request->filled('linea_id'), fn ($query) => $query->where('linea_id', $request->linea_id))
            ->when($request->filled('modulo'), fn ($query) => $query->where('modulo', $request->modulo))
            ->when($request->filled('componente'), fn ($query) => $query->where('componente', $request->componente))
            ->whereNotNull('actividad')
            ->latest('fecha_analisis')
            ->latest('created_at');

        return response()->json([
            'success' => true,
            'actividades' => $query->limit(30)->pluck('actividad')->filter()->unique()->values(),
        ]);
    }

    public function getEstadisticasComponentesAjax(Request $request)
    {
        $query = $this->analisisQuery()
            ->when($request->filled('linea_id'), fn ($query) => $query->where('linea_id', $request->linea_id))
            ->when($request->filled('modulo'), fn ($query) => $query->where('modulo', $request->modulo))
            ->when($request->filled('componente'), fn ($query) => $query->where('componente', $request->componente))
            ->when($request->filled('nivel'), fn ($query) => $query->where('nivel', $request->nivel))
            ->when($request->filled('lado'), fn ($query) => $query->where('lado', $request->lado))
            ->where('resuelto_por_cambio', false);

        $registros = $query->get();

        return response()->json([
            'success' => true,
            'total' => $registros->count(),
            'buen_estado' => $registros->where('estado', AnalisisPasteurizadora::ESTADO_BUENO)->count(),
            'requiere_revision' => $registros->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count(),
            'desgaste' => $registros->whereIn('estado', AnalisisPasteurizadora::ESTADOS_DESGASTE)->count(),
            'danado' => $registros->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count(),
            'cambiado' => $registros->where('estado', AnalisisPasteurizadora::ESTADO_CAMBIADO)->count(),
        ]);
    }

    public function deleteFoto($id, $fotoIndex)
    {
        $analisis = $this->analisisQuery()->findOrFail($id);

        if (isset($analisis->evidencia_fotos[$fotoIndex])) {
            $this->eliminarEvidenciaPasteurizadora($analisis->evidencia_fotos[$fotoIndex]);
            $fotos = $analisis->evidencia_fotos;
            unset($fotos[$fotoIndex]);
            $analisis->evidencia_fotos = array_values($fotos);
            $analisis->save();

            return redirect()->back()->with('success', 'Foto eliminada correctamente');
        }

        return redirect()->back()->with('error', 'No se pudo eliminar la foto');
    }
    public function analisis52124(Request $request)
    {
        $lineas = $this->getLineasPasteurizadora();
        $lineaIds = $lineas->pluck('id');

        $analisis = AnalisisPasteurizadora::with('linea')
            ->whereIn('linea_id', $lineaIds)
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->limit(100)
            ->get();

        return view('analisis-tendencia-mensual.pasteurizadora.index', compact('lineas', 'analisis'));
    }
    public function getEstadisticasTendencia52124(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'nullable|exists:lineas,id',
            'componente' => 'nullable|string',
            'periodo' => 'nullable|in:52,12,4',
        ]);

        $periodo = $validated['periodo'] ?? '52';
        $columna = "valor_actual_{$periodo}";
        $lineaIds = $this->getLineasPasteurizadora()->pluck('id');

        $registros = AnalisisPasteurizadora::with('linea')
            ->whereIn('linea_id', $lineaIds)
            ->when(!empty($validated['linea_id']), fn ($query) => $query->where('linea_id', $validated['linea_id']))
            ->when(!empty($validated['componente']), fn ($query) => $query->where('componente', $validated['componente']))
            ->whereNotNull($columna)
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->get();

        $valores = $registros->pluck($columna)->map(fn ($valor) => round((float) $valor, 2))->values();
        $ultimo = $valores->last();
        $anterior = $valores->count() > 1 ? $valores[$valores->count() - 2] : null;
        $variacion = $ultimo !== null && $anterior !== null ? round($ultimo - $anterior, 2) : null;
        $mejorRegistro = $registros->sortByDesc($columna)->first();

        return response()->json([
            'labels' => $registros->map(fn ($item) => $item->fecha_analisis?->format('d/m/Y') ?? 'Sin fecha')->values(),
            'valores' => $valores,
            'promedio' => $valores->isNotEmpty() ? number_format($valores->avg(), 2) : null,
            'tendencia' => $this->formatearTendencia52124($variacion),
            'mejor_valor' => $mejorRegistro ? number_format((float) $mejorRegistro->{$columna}, 2) : null,
            'mejor_fecha' => $mejorRegistro?->fecha_analisis?->format('d/m/Y'),
        ]);
    }

    public function showAnalisis52124Json($id)
    {
        $analisis = AnalisisPasteurizadora::with('linea')->findOrFail($id);

        return response()->json([
            'id' => $analisis->id,
            'valor_actual_52' => $analisis->valor_actual_52,
            'valor_actual_12' => $analisis->valor_actual_12,
            'valor_actual_4' => $analisis->valor_actual_4,
        ]);
    }

    public function updateAnalisis52124(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:analisis_pasteurizadora,id',
            'valor_52' => 'nullable|numeric',
            'valor_12' => 'nullable|numeric',
            'valor_4' => 'nullable|numeric',
        ]);

        $analisis = AnalisisPasteurizadora::findOrFail($validated['id']);

        $analisis->update([
            'valor_anterior_52' => $analisis->valor_actual_52,
            'valor_actual_52' => $validated['valor_52'] ?? null,
            'valor_anterior_12' => $analisis->valor_actual_12,
            'valor_actual_12' => $validated['valor_12'] ?? null,
            'valor_anterior_4' => $analisis->valor_actual_4,
            'valor_actual_4' => $validated['valor_4'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Datos actualizados correctamente');
    }

    public function crearLineasPasteurizadora(Request $request)
    {
        try {
            $lineasExistentes = Linea::pluck('nombre')->toArray();
            $lineasNecesarias = self::PASTEURIZADORAS_PERMITIDAS;
            $creadas = [];

            foreach ($lineasNecesarias as $nombre) {
                if (!in_array($nombre, $lineasExistentes)) {
                    $linea = Linea::create([
                        'nombre' => $nombre,
                        'descripcion' => 'Pasteurizadora ' . $nombre,
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

    public function apiGetComponentes($linea)
    {
        $linea = $this->resolverLineaPasteurizadora($linea);

        if (!$linea) {
            return response()->json(['success' => false, 'message' => 'Pasteurizadora no encontrada.'], 404);
        }

        return response()->json([
            'success' => true,
            'linea' => $linea->nombre,
            'componentes' => AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre),
            'modulos' => AnalisisPasteurizadora::getModulosPorLinea($linea->nombre),
        ]);
    }

    public function apiGetEstadisticas($linea)
    {
        $linea = $this->resolverLineaPasteurizadora($linea);

        if (!$linea) {
            return response()->json(['success' => false, 'message' => 'Pasteurizadora no encontrada.'], 404);
        }

        $registros = AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('resuelto_por_cambio', false)
            ->get();

        return response()->json([
            'success' => true,
            'linea' => $linea->nombre,
            'total' => $registros->count(),
            'buen_estado' => $registros->where('estado', AnalisisPasteurizadora::ESTADO_BUENO)->count(),
            'requiere_revision' => $registros->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count(),
            'desgaste' => $registros->whereIn('estado', AnalisisPasteurizadora::ESTADOS_DESGASTE)->count(),
            'danado' => $registros->whereIn('estado', AnalisisPasteurizadora::estadosDanado())->count(),
            'cambiado' => $registros->where('estado', AnalisisPasteurizadora::ESTADO_CAMBIADO)->count(),
        ]);
    }

    public function apiGetAnalisis52124(Request $request)
    {
        $lineaIds = $this->getLineasPasteurizadora()->pluck('id');

        $analisis = AnalisisPasteurizadora::with('linea')
            ->whereIn('linea_id', $lineaIds)
            ->where(function ($query) {
                $query->whereNotNull('valor_actual_52')
                    ->orWhereNotNull('valor_actual_12')
                    ->orWhereNotNull('valor_actual_4');
            })
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->limit((int) $request->get('limit', 25))
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? null,
                'fecha' => $item->fecha_analisis?->format('Y-m-d'),
                'modulo' => $item->modulo,
                'componente' => $item->componente,
                'valor_actual_52' => $item->valor_actual_52,
                'valor_actual_12' => $item->valor_actual_12,
                'valor_actual_4' => $item->valor_actual_4,
            ]);

        return response()->json(['success' => true, 'data' => $analisis]);
    }

    public function updatePlanAccion(Request $request)
    {
        return app(PlanAccionController::class)->updatePlanAccion($request->merge(['tipo' => 'pasteurizadora']));
    }

    public function createWithLinea($linea)
    {
        $linea = Linea::findOrFail($linea);
        $request = request();

        return $this->renderView('create', $this->buildCreateViewData($linea, $request));
    }

    private function getLineasPasteurizadora()
    {
        return Linea::whereIn('nombre', self::PASTEURIZADORAS_PERMITIDAS)
            ->orderBy('nombre')
            ->get();
    }

    private function resolverLineaPasteurizadora($linea): ?Linea
    {
        return Linea::whereIn('nombre', self::PASTEURIZADORAS_PERMITIDAS)
            ->where(function ($query) use ($linea) {
                $query->where('id', $linea)
                    ->orWhere('nombre', $linea);
            })
            ->first();
    }

    private function getAnalisisPasteurizadoraParaExportar(Request $request)
    {
        $lineaIds = $this->getLineasPasteurizadora()->pluck('id');

        return $this->analisisQuery()
            ->with(['linea', 'usuario'])
            ->whereIn('linea_id', $lineaIds)
            ->when($request->filled('linea_id') && $request->linea_id !== 'todas', fn ($query) => $query->where('linea_id', $request->linea_id))
            ->when($request->filled('modulo'), fn ($query) => $query->where('modulo', $request->modulo))
            ->when($request->filled('componente'), fn ($query) => $query->where('componente', $request->componente))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', AnalisisPasteurizadora::normalizarEstado($request->estado)))
            ->when($request->filled('fecha_inicio'), fn ($query) => $query->whereDate('fecha_analisis', '>=', $request->fecha_inicio))
            ->when($request->filled('fecha_fin'), fn ($query) => $query->whereDate('fecha_analisis', '<=', $request->fecha_fin))
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->get();
    }

    private function renderAnalisisPasteurizadoraPdf($analisis): string
    {
        $rows = $analisis->map(function ($item) {
            return '<tr>'
                . '<td>' . e($item->fecha_analisis?->format('d/m/Y')) . '</td>'
                . '<td>' . e($item->linea->nombre ?? '') . '</td>'
                . '<td>' . e($item->modulo) . '</td>'
                . '<td>' . e($item->nivel) . '</td>'
                . '<td>' . e($item->lado) . '</td>'
                . '<td>' . e($item->componente_nombre) . '</td>'
                . '<td>' . e($item->estado) . '</td>'
                . '<td>' . e($item->numero_orden) . '</td>'
                . '<td>' . e($item->actividad) . '</td>'
                . '</tr>';
        })->implode('');

        return '<!doctype html><html><head><meta charset="utf-8"><title>Analisis Pasteurizadora</title></head><body>'
            . '<h1>Analisis Pasteurizadora</h1>'
            . '<table width="100%" border="1" cellspacing="0" cellpadding="4">'
            . '<thead><tr><th>Fecha</th><th>Pasteurizadora</th><th>Modulo</th><th>Nivel</th><th>Lado</th><th>Componente</th><th>Estado</th><th>Orden</th><th>Actividad</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table></body></html>';
    }

    private function formatearTendencia52124(?float $variacion): ?string
    {
        if ($variacion === null) {
            return null;
        }

        if ($variacion > 0) {
            return '<span class="trend-up">+' . number_format($variacion, 2) . '</span>';
        }

        if ($variacion < 0) {
            return '<span class="trend-down">' . number_format($variacion, 2) . '</span>';
        }

        return '<span class="trend-neutral">0.00</span>';
    }

    private function buildSeguimientoPasteurizadora($lineas): array
    {
        $lineas = collect($lineas)->filter();
        $lineaIds = $lineas->pluck('id')->all();

        if (empty($lineaIds)) {
            return [];
        }

        $registrosPorLinea = $this->analisisQuery()
            ->with('usuario')
            ->whereIn('linea_id', $lineaIds)
            ->where('resuelto_por_cambio', false)
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id')
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
                    $registrosComponente = $registrosLinea
                        ->where('modulo', $modulo)
                        ->where('componente', $codigo)
                        ->values();
                    $resumenCiclo = AnalisisPasteurizadora::buildResumenCicloComponenteFromCollection(
                        $registrosComponente,
                        $totalComponentes
                    );
                    $estadoVisible = $resumenCiclo['resumen_visible'];
                    $completado = (bool) ($estadoVisible['completado'] ?? false);
                    $estadoPorNivel = $estadoVisible['estado_por_nivel'] ?? [];
                    $siguienteRevision = $estadoVisible['siguiente_revision']
                        ?? [
                            'nivel' => AnalisisPasteurizadora::NIVELES[0] ?? null,
                            'lado' => AnalisisPasteurizadora::LADOS[0] ?? null,
                        ];

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
                        'registros_visibles' => $resumenCiclo['registros_visibles'],
                        'registros_actuales' => $resumenCiclo['registros_actuales'],
                        'tiene_ciclo_activo' => $resumenCiclo['tiene_ciclo_activo'],
                        'tiene_ciclo_completado' => $resumenCiclo['tiene_ciclo_completado'],
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
        $registrosYaRealizados = collect();
        $ladosPendientes = [];

        if ($modulo && $componenteKey && $componentConfig) {
            $estadoRevision = AnalisisPasteurizadora::getEstadoRevision($linea->id, $modulo, $componenteKey, null, $this->currentArea());
            $siguienteRevision = AnalisisPasteurizadora::getSiguienteRevisionContexto($linea->id, $modulo, $componenteKey, $nivel, $lado, $this->currentArea());
            $effectiveNivel = $effectiveNivel ?: ($siguienteRevision['nivel'] ?? null);
            $effectiveLado = $effectiveLado ?: ($siguienteRevision['lado'] ?? null);

            $componentesYaRevisados = AnalisisPasteurizadora::getComponentesYaRevisados(
                $linea->id,
                $modulo,
                $componenteKey,
                $effectiveLado,
                $effectiveNivel,
                null,
                $this->currentArea()
            );
            $registrosYaRealizados = $this->analisisQuery()
                ->with('usuario')
                ->where('linea_id', $linea->id)
                ->where('modulo', $modulo)
                ->where('componente', $componenteKey)
                ->when($effectiveLado, fn ($query) => $query->where('lado', $effectiveLado))
                ->when($effectiveNivel, fn ($query) => $query->where('nivel', $effectiveNivel))
                ->latest('fecha_analisis')
                ->latest('created_at')
                ->get();
            $componentesPendientes = AnalisisPasteurizadora::getCantidadComponentesPendientes(
                $linea->id,
                $modulo,
                $componenteKey,
                $effectiveLado,
                $effectiveNivel,
                null,
                $this->currentArea()
            );
            $cantidadComponentesRevisados = AnalisisPasteurizadora::getCantidadComponentesRevisados(
                $linea->id,
                $modulo,
                $componenteKey,
                $effectiveLado,
                $effectiveNivel,
                null,
                $this->currentArea()
            );
            $ladosPendientes = $effectiveNivel
                ? AnalisisPasteurizadora::getLadosPendientes($linea->id, $modulo, $componenteKey, $effectiveNivel, $this->currentArea())
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
            'registrosYaRealizados' => $registrosYaRealizados,
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
            $analisisActual?->id,
            $this->currentArea()
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
