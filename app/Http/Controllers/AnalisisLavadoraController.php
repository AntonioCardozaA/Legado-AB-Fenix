<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\Linea;
use App\Models\Componente;
use App\Models\LavadoraCostEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exports\AnalisisComponentesExport;
use App\Exports\AnalisisLavadoraExport;
use App\Services\AnalysisDeletionLogger;
use App\Services\LavadoraCostSyncService;
use App\Services\Maintenance\WasherMaintenanceOrchestrator;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\WhatsAppService;
use Throwable;

class AnalisisLavadoraController extends Controller
{
    private const EVIDENCIA_FOTOS_DISK = 'public';
    private const EVIDENCIA_FOTOS_PATH = 'analisis-evidencias';
    private const CORRECCION_EVIDENCIAS_PATH = 'analisis-correcciones';

    /**
     * LISTADO + FILTROS
     */
public function index(Request $request)
{
    $query = AnalisisLavadora::ultimosPorComponente()
        ->with(['linea', 'componente', 'usuario', 'usuarioCorreccion'])
        ->ordenVigente();

    // FILTROS
    if ($request->filled('linea_id') && $request->linea_id !== 'todas') {
        $query->where('linea_id', $request->linea_id);
    }

    // Filtro por nombre de línea (desde el componente detail)
    if ($request->filled('linea_nombre')) {
        $query->whereHas('linea', function ($q) use ($request) {
            $q->where('nombre', $request->linea_nombre);
        });
    }

    // Filtro por nombre de componente (desde el componente detail)
    if ($request->filled('componente') && !$request->filled('componente_id')) {
        $query->whereHas('componente', function ($q) use ($request) {
            $q->where('nombre', 'like', '%' . $request->componente . '%');
        });
    }

    if ($request->filled('componente_id')) {
        $this->aplicarFiltroComponenteCodigo($query, $request->componente_id);
    }

    if ($request->filled('reductor')) {
        // Si viene de la búsqueda de detalles, buscar el reductor exacto o con patrón
        if (is_numeric($request->reductor)) {
            $query->where('reductor', $request->reductor);
        } else {
            $query->where('reductor', 'like', '%' . $request->reductor . '%');
        }
    }

    if ($request->filled('estado')) {
        $query->estadoOperativo($request->estado);
    }

    if ($request->filled('fecha')) {
        $query->whereMonth('fecha_analisis', substr($request->fecha, 5, 2))
              ->whereYear('fecha_analisis', substr($request->fecha, 0, 4));
    }

    $analisis = $query->get();
    $this->attachHistorialCountsToCurrentAnalyses($analisis);
    
    // Calcular estadísticas basadas en los últimos registros por componente (estado actual)
    $queryEstadisticas = AnalisisLavadora::ultimosPorComponente()
        ->with(['linea', 'componente', 'usuario', 'usuarioCorreccion']);
    
    // Aplicar los mismos filtros que al listado
    if ($request->filled('linea_id') && $request->linea_id !== 'todas') {
        $queryEstadisticas->where('linea_id', $request->linea_id);
    }
    
    if ($request->filled('linea_nombre')) {
        $queryEstadisticas->whereHas('linea', function ($q) use ($request) {
            $q->where('nombre', $request->linea_nombre);
        });
    }
    
    if ($request->filled('componente') && !$request->filled('componente_id')) {
        $queryEstadisticas->whereHas('componente', function ($q) use ($request) {
            $q->where('nombre', 'like', '%' . $request->componente . '%');
        });
    }
    
    if ($request->filled('componente_id')) {
        $this->aplicarFiltroComponenteCodigo($queryEstadisticas, $request->componente_id);
    }
    
    if ($request->filled('reductor')) {
        if (is_numeric($request->reductor)) {
            $queryEstadisticas->where('reductor', $request->reductor);
        } else {
            $queryEstadisticas->where('reductor', 'like', '%' . $request->reductor . '%');
        }
    }
    
    // Para estadísticas, no filtrar por estado ni fecha, ya que queremos el estado actual
    $analisisParaEstadisticas = $queryEstadisticas->get();
    
    $estadisticas = [
        'total' => $analisisParaEstadisticas->count(),
        'buen_estado' => $analisisParaEstadisticas->filter(fn (AnalisisLavadora $item) => AnalisisLavadora::esEstadoBueno($item->estado_operativo))->count(),
        'requiere_revision' => $analisisParaEstadisticas->filter(fn (AnalisisLavadora $item) => AnalisisLavadora::esEstadoRequiereRevision($item->estado_operativo))->count(),
        'desgaste' => $analisisParaEstadisticas->filter(fn (AnalisisLavadora $item) => AnalisisLavadora::esEstadoDesgaste($item->estado_operativo))->count(),
        'danado_requiere' => $analisisParaEstadisticas->filter(fn (AnalisisLavadora $item) => AnalisisLavadora::esEstadoDanado($item->estado_operativo))->count(),
        'cambiado' => $analisisParaEstadisticas->filter(fn (AnalisisLavadora $item) => AnalisisLavadora::esEstadoCambiado($item->estado_operativo))->count(),
    ];
    $diagramasPorLinea = [
    'L-04' => 'linea4.png',
    'L-05' => 'linea5.png',
    'L-06' => 'linea6.png',
    'L-07' => 'linea7.png',
    'L-08' => 'linea8.png',
    'L-09' => 'linea9.png',
    'L-12' => 'linea12.png',
    'L-13' => 'linea13.png',
    ];
    // Determinar qué líneas mostrar y los reductores
    $lineaMostrar = 'Todas las líneas';
    $reductoresMostrar = [];
    $lineaSeleccionadaParaDiagrama = null;
    
    if ($request->filled('linea_id') && $request->linea_id !== 'todas') {
        $linea = Linea::find($request->linea_id);
        if ($linea) {
            $lineaMostrar = $linea->nombre;
            $reductoresMostrar = $this->getReductoresPorLinea($lineaMostrar);
            $lineaSeleccionadaParaDiagrama = $linea;
        }
    } else {
        // Si es "todas" o no hay línea seleccionada, no filtramos por línea
        $reductoresMostrar = []; // Se usarán los reductores de cada línea en la vista
    }

    $analisisMonitorDiagrama = collect();

    if ($lineaSeleccionadaParaDiagrama) {
        $analisisMonitorDiagrama = AnalisisLavadora::ultimosPorComponente()
            ->with(['linea', 'componente', 'usuario', 'usuarioCorreccion'])
            ->where('linea_id', $lineaSeleccionadaParaDiagrama->id)
            ->ordenVigente()
            ->get();
    }

    $openAnalysisData = $this->modalPayloadForAnalysisId($request->input('open_analysis_id'));

    return view('lavadora/analisis-lavadora.index', [
        'analisis' => $analisis,
        'analisisMonitorDiagrama' => $analisisMonitorDiagrama,
        'lineas' => Linea::where('activo', true)->orderBy('nombre')->get(),
        'diagramasPorLinea' => $diagramasPorLinea,
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
        'estadisticas' => $estadisticas,
        'openAnalysisData' => $openAnalysisData,
        'canDeleteAnalysis' => $request->user()?->canDeleteLavadoraAnalysis() ?? false,
        'canCloseLavadoraDamage' => $request->user()?->canCloseLavadoraDamage() ?? false,
    ]);
}

    private function attachHistorialCountsToCurrentAnalyses($analisis): void
    {
        if ($analisis->isEmpty()) {
            return;
        }

        $identidades = $analisis
            ->map(fn (AnalisisLavadora $item) => [
                'linea_id' => $item->linea_id,
                'codigo_base' => AnalisisLavadora::codigoBaseComponente($item->componente?->codigo),
                'reductor' => $item->reductor,
                'lado' => $item->lado,
                'key' => $this->historialIdentityKey(
                    $item->linea_id,
                    AnalisisLavadora::codigoBaseComponente($item->componente?->codigo),
                    $item->reductor,
                    $item->lado
                ),
            ])
            ->unique('key')
            ->values();

        $identityKeys = $identidades->pluck('key')->flip();

        $historiales = AnalisisLavadora::query()
            ->with('componente:id,codigo')
            ->select('id', 'linea_id', 'componente_id', 'reductor', 'lado')
            ->where(function ($query) use ($identidades): void {
                foreach ($identidades as $identidad) {
                    $query->orWhere(function ($subQuery) use ($identidad): void {
                        $subQuery->where('linea_id', $identidad['linea_id'])
                            ->where('reductor', $identidad['reductor']);

                        if (blank($identidad['lado'])) {
                            $subQuery->where(function ($ladoQuery): void {
                                $ladoQuery->whereNull('lado')->orWhere('lado', '');
                            });
                        } else {
                            $subQuery->where('lado', $identidad['lado']);
                        }
                    });
                }
            })
            ->get()
            ->reduce(function (array $counts, AnalisisLavadora $item) use ($identityKeys): array {
                $key = $this->historialIdentityKey(
                    $item->linea_id,
                    AnalisisLavadora::codigoBaseComponente($item->componente?->codigo),
                    $item->reductor,
                    $item->lado
                );

                if (!isset($identityKeys[$key])) {
                    return $counts;
                }

                $counts[$key] = ($counts[$key] ?? 0) + 1;

                return $counts;
            }, []);

        $analisis->each(function (AnalisisLavadora $item) use ($historiales): void {
            $key = $this->historialIdentityKey(
                $item->linea_id,
                AnalisisLavadora::codigoBaseComponente($item->componente?->codigo),
                $item->reductor,
                $item->lado
            );
            $item->setAttribute('total_historial', $historiales[$key] ?? 1);
        });
    }

    private function historialIdentityKey($lineaId, $codigoBase, $reductor, $lado): string
    {
        return implode('|', [
            $lineaId ?? '',
            $codigoBase ?? '',
            $reductor ?? '',
            $lado ?: '',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function modalPayloadForAnalysisId(mixed $id): ?array
    {
        if (blank($id)) {
            return null;
        }

        $registro = AnalisisLavadora::with(['linea', 'componente', 'usuario', 'usuarioCorreccion'])
            ->find($id);

        if (!$registro) {
            return null;
        }

        $imagenes = $registro->evidencia_fotos ?? ($registro->fotos ?? []);

        if (is_string($imagenes)) {
            $imagenes = json_decode($imagenes, true) ?? [];
        }

        if (!is_array($imagenes)) {
            $imagenes = [];
        }

        $totalHistorial = AnalisisLavadora::query()
            ->where('linea_id', $registro->linea_id)
            ->where('componente_id', $registro->componente_id)
            ->where('reductor', $registro->reductor)
            ->count();

        $canDeleteAnalysis = auth()->user()?->canDeleteLavadoraAnalysis() ?? false;
        $canCloseLavadoraDamage = auth()->user()?->canCloseLavadoraDamage() ?? false;
        $canAccessLavadoraCosts = auth()->user()?->canAccessLavadoraCosts() ?? false;
        $estadoOperativo = $registro->estado_operativo;

        return [
            'id' => $registro->id,
            'linea' => $registro->linea->nombre ?? 'Linea no registrada',
            'componente' => $registro->componente->nombre ?? 'Componente no registrado',
            'componente_codigo' => $registro->componente->codigo ?? $registro->componente_id,
            'reductor' => $registro->reductor,
            'lado' => $registro->lado ?? null,
            'fecha_analisis' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : '',
            'numero_orden' => $registro->numero_orden,
            'estado' => $registro->estado ?? 'Buen estado',
            'estado_operativo' => $estadoOperativo,
            'estado_operativo_label' => $registro->estado_operativo_label,
            'can_show_correction_actions' => $canCloseLavadoraDamage && AnalisisLavadora::requiereCierreAdministrativo($estadoOperativo),
            'usuario_nombre' => $registro->usuario?->name ?? 'Usuario no registrado',
            'actividad' => $registro->actividad,
            'imagenes' => $imagenes,
            'color' => $this->analysisCellColor($estadoOperativo),
            'created_at' => $registro->created_at ? $registro->created_at->format('d/m/Y H:i') : '',
            'updated_at' => $registro->updated_at ? $registro->updated_at->format('d/m/Y H:i') : '',
            'is_new' => $registro->created_at ? $registro->created_at->gt(now()->subDays(3)) : false,
            'total_historial' => $totalHistorial,
            'estado_correccion' => $canCloseLavadoraDamage ? $registro->estado_correccion : null,
            'estado_correccion_label' => $canCloseLavadoraDamage ? $registro->estado_correccion_label : null,
            'fecha_correccion' => $canCloseLavadoraDamage ? $registro->fecha_correccion?->format('Y-m-d\TH:i') : null,
            'fecha_correccion_humana' => $canCloseLavadoraDamage ? $registro->fecha_correccion?->format('d/m/Y H:i') : null,
            'corregido_por_nombre' => $canCloseLavadoraDamage ? $registro->usuarioCorreccion?->name : null,
            'observaciones_reparacion' => $canCloseLavadoraDamage ? $registro->observaciones_reparacion : null,
            'evidencias_reparacion' => $canCloseLavadoraDamage ? $registro->evidencias_reparacion : [],
            'tipo_intervencion' => $canCloseLavadoraDamage ? $registro->tipo_intervencion : null,
            'componente_instalado' => $canCloseLavadoraDamage ? $registro->componente_instalado : null,
            'numero_parte' => $canCloseLavadoraDamage ? $registro->numero_parte : null,
            'proveedor' => $canCloseLavadoraDamage ? $registro->proveedor : null,
            'garantia' => $canCloseLavadoraDamage ? $registro->garantia : null,
            'fecha_cambio' => $canCloseLavadoraDamage ? $registro->fecha_cambio?->format('Y-m-d') : null,
            'costo_refacciones' => $canCloseLavadoraDamage ? $registro->costo_refacciones : null,
            'costo_mano_obra' => $canCloseLavadoraDamage ? $registro->costo_mano_obra : null,
            'costo_servicios_externos' => $canCloseLavadoraDamage ? $registro->costo_servicios_externos : null,
            'costo_total_intervencion' => $canCloseLavadoraDamage ? $registro->costo_total_intervencion : null,
            'tiempo_reparacion_horas' => $canCloseLavadoraDamage ? $registro->tiempo_reparacion_horas : null,
            'responsable_trabajo' => $canCloseLavadoraDamage ? $registro->responsable_trabajo : null,
            'comentarios_costos' => $canCloseLavadoraDamage ? $registro->comentarios_costos : null,
            'can_close_damage' => $canCloseLavadoraDamage,
            'edit_url' => route('analisis-lavadora.edit', ['analisislavadora' => $registro->id], false),
            'costs_url' => $canAccessLavadoraCosts ? route('analisis-lavadora.costos.manage', ['analisislavadora' => $registro->id], false) : null,
            'correction_url' => $canCloseLavadoraDamage ? route('analisis-lavadora.correccion.update', ['analisislavadora' => $registro->id], false) : null,
            'delete_url' => $canDeleteAnalysis ? route('analisis-lavadora.destroy', ['analisislavadora' => $registro->id], false) : null,
            'historial_url' => route('analisis-lavadora.historial', [
                'linea_id' => $registro->linea_id,
                'componente_id' => $registro->componente_id,
                'reductor' => $registro->reductor,
            ], false),
        ];
    }

    private function analysisCellColor(?string $estado): string
    {
        if (AnalisisLavadora::esEstadoCambiado($estado)) {
            return 'cell-changed';
        }

        if (AnalisisLavadora::esEstadoDanado($estado)) {
            return 'cell-danger';
        }

        if (AnalisisLavadora::esEstadoRequiereRevision($estado)) {
            return 'cell-review';
        }

        if (AnalisisLavadora::esEstadoDesgaste($estado)) {
            return 'cell-warning';
        }

        return 'cell-ok';
    }

    /**
     * Aplica el filtro por codigo base aunque el componente tenga prefijos o sufijos.
     */
    private function aplicarFiltroComponenteCodigo($query, ?string $codigoBase): void
    {
        $codigoBase = strtoupper(trim((string) $codigoBase));

        if ($codigoBase === '') {
            return;
        }

        $componenteIds = $this->resolverComponenteIdsPorCodigoBase($codigoBase);

        if ($componenteIds->isNotEmpty()) {
            $query->whereIn('componente_id', $componenteIds->all());

            return;
        }

        $query->whereHas('componente', function ($q) use ($codigoBase) {
            $q->where('codigo', $codigoBase)
                ->orWhere('codigo', 'like', '%_' . $codigoBase);
        });
    }

    private function resolverComponenteIdsPorCodigoBase(string $codigoBase)
    {
        return Componente::query()
            ->select('id', 'codigo')
            ->get()
            ->filter(function (Componente $componente) use ($codigoBase) {
                return $this->normalizarCodigoComponenteLavadora($componente->codigo) === $codigoBase;
            })
            ->pluck('id')
            ->values();
    }

    private function normalizarCodigoComponenteLavadora(?string $codigo): string
    {
        return AnalisisLavadora::codigoBaseComponente($codigo);
    }

    /**
     * Obtener componentes organizados por linea para la tabla.
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
                      'Reductor 18', 'Reductor 19', 'Reductor Loca', 'Reductor Principal'],
            'L-05' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                      'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                      'Reductor 11', 'Reductor 12', 'Reductor Principal', 'Reductor Loca'],
            'L-06' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                      'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22', 'Reductor Principal'],
            'L-07' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                      'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22', 'Reductor Principal'],
            'L-08' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                      'Reductor 18', 'Reductor 19', 'Reductor Loca'],
            'L-09' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                      'Reductor 18', 'Reductor 19', 'Reductor Loca', 'Reductor Principal'],
            'L-12' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                      'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                      'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal'],
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
        $reductores = $this->getReductoresDisponiblesPorLinea($linea);

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

        $analisisRealizados = AnalisisLavadora::with('usuario')
            ->where('linea_id', $linea->id)
            ->where('componente_id', $componente->id)
            ->where('reductor', $request->reductor)
            ->ordenVigente()
            ->limit(5)
            ->get();

        return view('lavadora/analisis-lavadora.create-quick', [
            'linea'          => $linea,
            'componente'     => $componente,
            'reductor'       => $request->reductor,
            'fecha_sugerida' => $request->fecha ?? now()->toDateString(),
            'redirect_to'    => url()->previous(),
            'analisisRealizados' => $analisisRealizados,
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
        'estado'            => 'required|string|in:' . implode(',', AnalisisLavadora::ESTADOS),
        'actividad'         => 'required|string',
        'evidencia_fotos'   => 'nullable|array',
        'evidencia_fotos.*' => $this->evidenciaFotoRules(),
        'redirect_to'       => 'nullable|string',
        'lado'               => 'nullable|string|in:VAPOR,PASILLO',
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

// Convertir L-04 → L04
$lineaFormateada = str_replace('-', '', $linea->nombre);

// Convertir "Reductor 1" → reductor_1
$reductorFormateado = strtolower(str_replace(' ', '_', $request->reductor));

$codigoLinea = $lineaFormateada . '_' . $reductorFormateado . '_' . $codigoBase;

$componente = Componente::firstOrCreate(
    ['codigo' => $codigoLinea],
    [
        'nombre'          => $this->getNombreComponente($codigoBase),
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
            'lado'           => $request->lado ?? null,
            'usuario_id'     => $request->user()?->id,
        ]);
        if ($request->filled('lado')) {
            $data['lado'] = $request->lado;
        }
        Log::info('Análisis creado', ['id' => $analisis->id]);
        // 🚨 ENVIAR WHATSAPP SI ESTÁ DAÑADO
        if ($request->estado === AnalisisLavadora::ESTADO_DANADO) {

            $mensaje = "🚨 *ALERTA DE COMPONENTE DAÑADO* 🚨\n\n"
                . "🔧 Línea: {$linea->nombre}\n"
                . "⚙️ Componente: {$componente->nombre}\n"
                . "📍 Reductor: {$request->reductor}\n"
                . "📅 Fecha: {$request->fecha_analisis}\n"
                . "🧾 Orden: {$request->numero_orden}\n"
                . "📝 Actividad: {$analisis->actividad}\n";

            // Número en formato internacional (México: 521...)
            $numero = "5214981239090"; // numero

            try {
                WhatsAppService::enviarMensaje($numero, $mensaje);
                Log::info('WhatsApp enviado correctamente');
            } catch (\Exception $e) {
                Log::error('Error al enviar WhatsApp', [
                    'error' => $e->getMessage()
                ]);
            }
        }
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
        $fotos = $this->guardarEvidenciasFotograficas($request->file('evidencia_fotos', []));

        $analisis->update([
            'evidencia_fotos' => $fotos,
        ]);
    }

    $analisis = $analisis->fresh(['linea', 'componente', 'costEntries']);

    app(LavadoraCostSyncService::class)->syncForAnalysis($analisis);
    $mensajeIa = $this->procesarMantenimientoAutomaticoSafely(
        $analisis->fresh(['linea', 'componente', 'costEntries'])
    );

    /**
     * ==================
     * 6️⃣ REDIRECCIÓN 
     * ==================
     */
    if ($mensajeIa) {
        session()->flash('warning', $mensajeIa);
    }

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

    private function getReductoresDisponiblesPorLinea(Linea $linea)
    {
        $reductoresBase = collect($this->getReductoresPorLinea($linea->nombre));

        $reductoresDb = Componente::where('linea', $linea->nombre)
            ->where('activo', true)
            ->whereNotNull('reductor')
            ->select('reductor')
            ->distinct()
            ->orderBy('reductor')
            ->pluck('reductor');

        return $reductoresBase
            ->merge($reductoresDb)
            ->filter()
            ->unique()
            ->values();
    }

/**
 * EDITAR ANÁLISIS
 */
public function edit($id)
{
    $analisisComponente = AnalisisLavadora::with(['linea', 'componente', 'usuario', 'cambiosFecha.usuario'])
        ->findOrFail($id);

    $componentes = Componente::where('linea', $analisisComponente->linea->nombre)
        ->where('activo', true)
        ->orderBy('nombre')
        ->get();

    $puedeEditarFechaAnalisis = $this->puedeEditarFechaAnalisis(auth()->user());

    $canDeleteAnalysis = auth()->user()?->canDeleteLavadoraAnalysis() ?? false;

    return view('lavadora/analisis-lavadora.edit', compact(
        'analisisComponente',
        'componentes',
        'puedeEditarFechaAnalisis',
        'canDeleteAnalysis'
    ));
}


public function update(Request $request, $id)
{
    $analisis = AnalisisLavadora::findOrFail($id);
    $fechaAnterior = $analisis->fecha_analisis?->toDateString();

    $validator = Validator::make($request->all(), [
        'fecha_analisis'    => ['required', 'date', 'date_format:Y-m-d'],
        'numero_orden'      => 'required|string|max:20',
        'estado'            => 'required|string|in:' . implode(',', AnalisisLavadora::ESTADOS),
        'actividad'         => 'required|string',
        'evidencia_fotos'   => 'nullable|array',
        'evidencia_fotos.*' => $this->evidenciaFotoRules(),
        'eliminar_fotos'    => 'nullable|array',
        'eliminar_fotos.*'  => 'integer',
    ], [
        'fecha_analisis.required' => 'La fecha del analisis es obligatoria.',
        'fecha_analisis.date' => 'La fecha del analisis no es valida.',
        'fecha_analisis.date_format' => 'La fecha del analisis debe tener el formato AAAA-MM-DD.',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $fechaNueva = Carbon::createFromFormat('Y-m-d', $request->input('fecha_analisis'))->toDateString();
    $fechaFueModificada = $fechaAnterior !== $fechaNueva;
    $puedeEditarFechaAnalisis = $this->puedeEditarFechaAnalisis($request->user());

    if ($fechaFueModificada && !$puedeEditarFechaAnalisis) {
        abort(403, 'No tienes permiso para modificar la fecha del analisis.');
    }

    /* =========================================
       OBTENER FOTOS EXISTENTES
    ========================================= */
    $fotosExistentes = $analisis->evidencia_fotos;

    if (!is_array($fotosExistentes)) {
        $fotosExistentes = json_decode($fotosExistentes, true) ?? [];
    }

    /* =========================================
       ELIMINAR FOTOS MARCADAS
    ========================================= */
    if ($request->filled('eliminar_fotos')) {
        foreach ($request->eliminar_fotos as $index) {

            if (isset($fotosExistentes[$index])) {

                Storage::disk('public')->delete($fotosExistentes[$index]);

                unset($fotosExistentes[$index]);
            }
        }

        $fotosExistentes = array_values($fotosExistentes);
    }

    /* =========================================
       AGREGAR NUEVAS FOTOS
    ========================================= */
    if ($request->hasFile('evidencia_fotos')) {
        $fotosExistentes = array_merge(
            $fotosExistentes,
            $this->guardarEvidenciasFotograficas($request->file('evidencia_fotos', []))
        );
    }

    /* =====================================================
     | ACTUALIZAR REGISTRO
     ===================================================== */
    DB::transaction(function () use ($analisis, $request, $fotosExistentes, $fechaAnterior, $fechaNueva, $fechaFueModificada) {
        $analisis->update([
            'componente_id'   => $analisis->componente_id, // Mantener el mismo
            'reductor'        => $analisis->reductor, // Mantener el mismo
            'fecha_analisis'  => $fechaNueva,
            'numero_orden'    => $request->numero_orden,
            'estado'          => $request->estado,
            'actividad'       => $request->actividad,
            'evidencia_fotos' => $fotosExistentes,
        ]);

        if ($fechaFueModificada) {
            $analisis->cambiosFecha()->create([
                'usuario_id' => $request->user()?->id,
                'fecha_anterior' => $fechaAnterior,
                'fecha_nueva' => $fechaNueva,
                'fecha_cambio' => now(),
            ]);
        }
    });

    $analisis = $analisis->fresh(['linea', 'componente', 'costEntries']);

    app(LavadoraCostSyncService::class)->syncForAnalysis($analisis);
    $mensajeIa = $this->procesarMantenimientoAutomaticoSafely(
        $analisis->fresh(['linea', 'componente', 'costEntries'])
    );

    /* =====================================================
     | REDIRECCIÓN - CORREGIDA
     ===================================================== */
    if ($mensajeIa) {
        session()->flash('warning', $mensajeIa);
    }

    $redirectUrl = $request->input('redirect_to') ?? route('analisis-lavadora.index');
    
    return redirect($redirectUrl)
        ->with('success', 'Análisis actualizado correctamente.');
}

public function updateCorrectionStatus(Request $request, AnalisisLavadora $analisislavadora): RedirectResponse
{
    abort_unless($request->user()?->canCloseLavadoraDamage(), 403, 'No tienes permiso para cerrar danos de lavadora.');

    $validated = $request->validate([
        'estado_correccion' => 'required|string|in:' . implode(',', AnalisisLavadora::ESTADOS_CORRECCION),
    ]);

    $estadoCorreccion = $validated['estado_correccion'];
    $cerrado = $estadoCorreccion !== AnalisisLavadora::CORRECCION_PENDIENTE;
    $now = now();

    $analisislavadora->update([
        'estado_correccion' => $estadoCorreccion,
        'fecha_correccion' => $cerrado ? $now : null,
        'corregido_por' => $cerrado ? $request->user()?->id : null,
        'tipo_intervencion' => match ($estadoCorreccion) {
            AnalisisLavadora::CORRECCION_CORREGIDO => 'Cierre administrativo a buen estado',
            AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO => 'Cierre administrativo por componente cambiado',
            default => null,
        },
        'fecha_cambio' => $estadoCorreccion === AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO
            ? $now->toDateString()
            : null,
        'componente_instalado' => $estadoCorreccion === AnalisisLavadora::CORRECCION_COMPONENTE_CAMBIADO
            ? ($analisislavadora->componente?->nombre ?? 'Componente cambiado')
            : null,
    ]);

    $this->syncCorrectionCostEntry($analisislavadora->fresh(['linea', 'componente', 'usuarioCorreccion']));

    return back()->with('success', 'Estado de correccion actualizado correctamente.');
}

private function puedeEditarFechaAnalisis(?User $user): bool
{
    return $user?->canEditAnalysisDate() ?? false;
}

private function procesarMantenimientoAutomaticoSafely(AnalisisLavadora $analisis): ?string
{
    try {
        app(WasherMaintenanceOrchestrator::class)->processAnalysis($analisis);

        return null;
    } catch (Throwable $exception) {
        Log::warning('El analisis de lavadora se guardo, pero fallo la automatizacion de mantenimiento.', [
            'analisis_id' => $analisis->id,
            'linea_id' => $analisis->linea_id,
            'componente_id' => $analisis->componente_id,
            'error' => $exception->getMessage(),
        ]);

        return 'La sugerencia IA no pudo generarse en este momento; revisa la configuracion SSL/API.';
    }
}

    /**
     * VER
     */
    public function show(AnalisisLavadora $analisislavadora)
    {
        $analisislavadora->load(['linea', 'componente', 'usuario', 'cambiosFecha.usuario']);

        return view('lavadora/analisis-lavadora.show', [
            'analisislavadora' => $analisislavadora,
            'canDeleteAnalysis' => auth()->user()?->canDeleteLavadoraAnalysis() ?? false,
            'canAccessLavadoraCosts' => auth()->user()?->canAccessLavadoraCosts() ?? false,
        ]);
    }
    
    /**
     * ELIMINAR
     */
    public function destroy(Request $request, AnalisisLavadora $analisislavadora)
    {
        abort_unless($request->user()?->canDeleteLavadoraAnalysis(), 403, 'No tienes permiso para eliminar analisis.');

        $analisislavadora->loadMissing(['linea', 'componente']);

        app(AnalysisDeletionLogger::class)->log($request->user(), $analisislavadora, 'lavadora', 'Analisis Lavadora', [
            'componente' => $analisislavadora->componente?->nombre,
            'componente_codigo' => $analisislavadora->componente?->codigo,
            'reductor' => $analisislavadora->reductor,
            'lado' => $analisislavadora->lado,
            'estado' => $analisislavadora->estado,
            'numero_orden' => $analisislavadora->numero_orden,
            'fecha_analisis' => $analisislavadora->fecha_analisis?->toDateString(),
        ]);

        $fotos = $analisislavadora->evidencia_fotos;
        if (!is_array($fotos)) {
            $fotos = json_decode($fotos ?? '[]', true) ?? [];
        }

        foreach ($fotos as $foto) {
            Storage::disk('public')->delete($foto);

            $rutaPublica = public_path('storage/' . $foto);
            if (file_exists($rutaPublica)) {
                @unlink($rutaPublica);
            }
        }

        $analisislavadora->delete();

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

        $reductores = $this->getReductoresDisponiblesPorLinea($linea);

        return response()->json($reductores);
    }

    /**
     * ELIMINAR FOTO
     */
    public function deleteFoto(AnalisisLavadora $analisisComponente, $fotoIndex)
    {
        $fotos = $analisisComponente->evidencia_fotos;

        if (!is_array($fotos)) {
            $fotos = json_decode($fotos ?? '[]', true) ?? [];
        }
        
        if (isset($fotos[$fotoIndex])) {
            Storage::disk('public')->delete($fotos[$fotoIndex]);

            $rutaPublica = public_path('storage/' . $fotos[$fotoIndex]);
            if (file_exists($rutaPublica)) {
                @unlink($rutaPublica);
            }

            unset($fotos[$fotoIndex]);
            
            $analisisComponente->update([
                'evidencia_fotos' => array_values($fotos)
            ]);
            
            return back()->with('success', 'Foto eliminada correctamente.');
        }
        
        return back()->with('error', 'Foto no encontrada.');
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

            /*
             |------------------------------------------------------------
             | Guardado principal visible en producción
             | public/storage/analisis-evidencias
             |------------------------------------------------------------
             */
            $rutaPublica = public_path('storage/' . self::EVIDENCIA_FOTOS_PATH);

            if (!file_exists($rutaPublica)) {
                mkdir($rutaPublica, 0755, true);
            }

            $archivo->move($rutaPublica, $nombreArchivo);

            $rutaGuardar = self::EVIDENCIA_FOTOS_PATH . '/' . $nombreArchivo;
            $rutas[] = $rutaGuardar;

            /*
             |------------------------------------------------------------
             | Copia extra para mantener compatibilidad con Laravel storage
             | storage/app/public/analisis-evidencias
             |------------------------------------------------------------
             */
            try {
                $rutaStorage = storage_path('app/public/' . self::EVIDENCIA_FOTOS_PATH);

                if (!file_exists($rutaStorage)) {
                    mkdir($rutaStorage, 0755, true);
                }

                $origen = public_path('storage/' . $rutaGuardar);
                $destino = $rutaStorage . '/' . $nombreArchivo;

                if (file_exists($origen) && !file_exists($destino)) {
                    copy($origen, $destino);
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo copiar evidencia a storage/app/public', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rutas;
    }

    private function guardarEvidenciasCorreccion(array $archivos): array
    {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if (!$archivo || !$archivo->isValid()) {
                continue;
            }

            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'jpg');
            $nombreArchivo = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $extension;
            $rutaPublica = public_path('storage/' . self::CORRECCION_EVIDENCIAS_PATH);

            if (!file_exists($rutaPublica)) {
                mkdir($rutaPublica, 0755, true);
            }

            $archivo->move($rutaPublica, $nombreArchivo);

            $rutaGuardar = self::CORRECCION_EVIDENCIAS_PATH . '/' . $nombreArchivo;
            $rutas[] = $rutaGuardar;

            try {
                $rutaStorage = storage_path('app/public/' . self::CORRECCION_EVIDENCIAS_PATH);

                if (!file_exists($rutaStorage)) {
                    mkdir($rutaStorage, 0755, true);
                }

                $origen = public_path('storage/' . $rutaGuardar);
                $destino = $rutaStorage . '/' . $nombreArchivo;

                if (file_exists($origen) && !file_exists($destino)) {
                    copy($origen, $destino);
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo copiar evidencia de correccion a storage/app/public', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rutas;
    }

    private function syncCorrectionCostEntry(AnalisisLavadora $analisis): void
    {
        $syncKey = sha1('cierre_dano|' . $analisis->id);

        if (
            $analisis->estado_correccion === AnalisisLavadora::CORRECCION_PENDIENTE
            || round((float) $analisis->costo_total_intervencion, 2) <= 0
        ) {
            LavadoraCostEntry::query()
                ->where('sync_key', $syncKey)
                ->delete();

            return;
        }

        $analisis->loadMissing(['linea', 'componente', 'usuarioCorreccion']);
        $total = round((float) $analisis->costo_total_intervencion, 2);
        $fechaCosto = $analisis->fecha_correccion?->toDateString()
            ?? $analisis->fecha_cambio?->toDateString()
            ?? now()->toDateString();
        $componentName = $analisis->componente?->nombre
            ?? $analisis->componente_instalado
            ?? 'Componente no identificado';
        $componentCode = AnalisisLavadora::codigoBaseComponente($analisis->componente?->codigo);

        LavadoraCostEntry::query()->updateOrCreate(
            ['sync_key' => $syncKey],
            [
                'linea_id' => $analisis->linea_id,
                'analisis_lavadora_id' => $analisis->id,
                'componente_id' => $analisis->componente_id,
                'catalog_item_id' => null,
                'source_type' => LavadoraCostEntry::SOURCE_DAMAGE_CLOSURE,
                'source_reference' => $analisis->estado_correccion,
                'cost_date' => $fechaCosto,
                'quantity' => 1,
                'unit_cost' => $total,
                'total_cost' => $total,
                'component_snapshot' => $componentName,
                'catalog_name_snapshot' => 'Cierre administrativo de dano',
                'catalog_sku_snapshot' => $analisis->numero_parte,
                'catalog_category_snapshot' => $analisis->tipo_intervencion ?: 'Intervencion',
                'unidad_medida_snapshot' => 'Intervencion',
                'notas' => trim(implode("\n", array_filter([
                    $analisis->observaciones_reparacion,
                    $analisis->comentarios_costos,
                ]))) ?: null,
                'metadata' => [
                    'estado_original' => $analisis->estado,
                    'estado_operativo' => $analisis->estado_operativo,
                    'estado_correccion' => $analisis->estado_correccion,
                    'estado_correccion_label' => $analisis->estado_correccion_label,
                    'tipo_intervencion' => $analisis->tipo_intervencion,
                    'componente_instalado' => $analisis->componente_instalado,
                    'numero_parte' => $analisis->numero_parte,
                    'proveedor' => $analisis->proveedor,
                    'garantia' => $analisis->garantia,
                    'fecha_cambio' => $analisis->fecha_cambio?->toDateString(),
                    'fecha_correccion' => $analisis->fecha_correccion?->toDateTimeString(),
                    'costo_refacciones' => round((float) $analisis->costo_refacciones, 2),
                    'costo_mano_obra' => round((float) $analisis->costo_mano_obra, 2),
                    'costo_servicios_externos' => round((float) $analisis->costo_servicios_externos, 2),
                    'tiempo_reparacion_horas' => $analisis->tiempo_reparacion_horas,
                    'responsable_trabajo' => $analisis->responsable_trabajo,
                    'linea_nombre' => $analisis->linea?->nombre,
                    'component_code' => $componentCode,
                    'closed_by' => $analisis->corregido_por,
                    'closed_by_name' => $analisis->usuarioCorreccion?->name,
                ],
            ]
        );
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
            ->where('reductor', $request->reductor);

        $this->aplicarFiltroComponenteCodigo($query, $request->componente_id);

        $query->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // Paginar los resultados (10 por página)
        $analisis = $query->paginate(10)->withQueryString();

        return view('lavadora/analisis-lavadora.historial', compact('analisis'));
    }
public function analisis52124 (Request $request)
{
    return redirect()
        ->route('analisis-tendencia-mensual.lavadora.index', $request->only('linea_id'))
        ->with('info', 'El analisis 52-12-4 se calcula automaticamente desde los analisis registrados.');
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
        // Solo considerar el último registro por componente (último análisis)
        $reductoresConAnalisis = AnalisisLavadora::ultimosPorComponente()
            ->where('linea_id', $lineaSeleccionadaId)
            ->whereHas('componente', function($q) use ($codigo) {
                $q->where('codigo', $codigo);
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
    
    return view('historico-revisados.index', compact(
        'lineas',
        'lineasLavadora',
        'lineasPasteurizadora',
        'lineaSeleccionada',
        'tipoSeleccionado',
        'estadisticas',
        'resumen'
    ));
}
/**
 * Obtener estadísticas de progreso para todas las líneas
 */
public function getEstadisticasProgreso(Request $request)
{
    $lineas = Linea::whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
        ->where('activo', true)
        ->orderBy('nombre')
        ->get();
    
    $componentesPorLinea = $this->getComponentesPorLinea();
    $reductoresPorLineaArray = [
        'L-04' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca', 'Reductor Principal'],
        'L-05' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Principal', 'Reductor Loca'],
        'L-06' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22', 'Reductor Principal'],
        'L-07' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22', 'Reductor Principal'],
        'L-08' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca'],
        'L-09' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca', 'Reductor Principal'],
        'L-12' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal'],
        'L-13' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal']
    ];
    
    $estadisticas = [];
    
    foreach ($lineas as $linea) {
        $componentes = $componentesPorLinea[$linea->nombre] ?? [];
        $reductores = $reductoresPorLineaArray[$linea->nombre] ?? [];
        
        $totalCeldas = count($componentes) * count($reductores);
        $celdasConDatos = 0;
        
        $estados = [
            'buen_estado' => 0,
            'requiere_revision' => 0,
            'desgaste' => 0,
            'danado' => 0,
            'cambiado' => 0,
            'sin_datos' => 0
        ];
        
        foreach ($reductores as $reductor) {
            foreach ($componentes as $codigo => $nombre) {
                // Buscar el último análisis para esta combinación
                $analisis = AnalisisLavadora::ultimosPorComponente()
                    ->where('linea_id', $linea->id)
                    ->where('reductor', $reductor)
                    ->whereHas('componente', function($q) use ($codigo) {
                        $q->where('codigo', 'like', '%' . $codigo . '%');
                    })
                    ->ordenVigente()
                    ->first();
                
                if ($analisis) {
                    $celdasConDatos++;
                    $estado = $analisis->estado_operativo;
                    
                    if (AnalisisLavadora::esEstadoCambiado($estado)) {
                        $estados['cambiado']++;
                    } elseif (AnalisisLavadora::esEstadoDanado($estado)) {
                        $estados['danado']++;
                    } elseif (AnalisisLavadora::esEstadoRequiereRevision($estado)) {
                        $estados['requiere_revision']++;
                    } elseif (AnalisisLavadora::esEstadoDesgaste($estado)) {
                        $estados['desgaste']++;
                    } else {
                        $estados['buen_estado']++;
                    }
                } else {
                    $estados['sin_datos']++;
                }
            }
        }
        $porcentajeProgreso = $totalCeldas > 0 ? round(($celdasConDatos / $totalCeldas) * 100, 1) : 0;
        
        $estadisticas[$linea->nombre] = [
            'id' => $linea->id,
            'nombre' => $linea->nombre,
            'total_celdas' => $totalCeldas,
            'celdas_con_datos' => $celdasConDatos,
            'porcentaje' => $porcentajeProgreso,
            'estados' => $estados,
            'componentes' => array_keys($componentes),
            'reductores' => $reductores
        ];
    }
    
    if ($request->ajax()) {
        return response()->json($estadisticas);
    }
    
    return $estadisticas;
}
}
