<?php

namespace App\Http\Controllers;

use App\Models\PlanAccion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Linea;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PlanAccionController extends Controller
{
    private $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
    private $lineasPasteurizadoraNombres = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
    private $lineasEtiquetadoraNombres = ['L-04', 'L-05', 'L-06', 'L-10', 'L-12', 'L-13'];
    
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function planAccion(Request $request)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanViewTipo($tipo);

        $lineaId = $request->get('linea_id');
        $lineasTipo = $this->obtenerLineasPorTipo($tipo);
        $query = $this->crearQueryPlanesPorTipo($tipo);

        if ($lineaId && in_array((int) $lineaId, $this->obtenerLineaIdsPorTipo($tipo), true)) {
            $query->where('linea_id', $lineaId);
        }

        $planes = $query->orderBy('created_at', 'desc')->paginate(15);

        $alertas = $this->obtenerAlertasGlobales($tipo);
        $estadisticas = $this->obtenerEstadisticas($tipo);

        return view($this->obtenerVistaPorTipo($tipo, 'index'), compact(
            'lineasTipo',
            'planes',
            'alertas',
            'estadisticas',
            'tipo',
            'lineaId'
        ));
    }

   
    public function dashboard()
    {
        $lineaIdsPermitidas = $this->obtenerLineaIdsPermitidas();
        $estadisticas = $this->obtenerEstadisticas();
        $alertas = $this->obtenerAlertasGlobales();
        
        $actividadesPorLinea = PlanAccion::with('linea')
            ->select('linea_id', \DB::raw('count(*) as total'))
            ->whereIn('linea_id', $lineaIdsPermitidas)
            ->groupBy('linea_id')
            ->get();
        
        $actividadesProximas = PlanAccion::with($this->relacionesTrazabilidad())
            ->whereIn('linea_id', $lineaIdsPermitidas)
            ->where(function ($query) {
                $now = Carbon::now();
                $query->whereBetween('fecha_pcm1', [$now, $now->copy()->addDays(7)])
                    ->orWhereBetween('fecha_pcm2', [$now, $now->copy()->addDays(7)])
                    ->orWhereBetween('fecha_pcm3', [$now, $now->copy()->addDays(7)])
                    ->orWhereBetween('fecha_pcm4', [$now, $now->copy()->addDays(7)]);
            })
            ->orderBy('fecha_pcm1')
            ->limit(10)
            ->get();
        
        return view('plan-accion.dashboard', compact(
            'estadisticas', 
            'alertas', 
            'actividadesPorLinea', 
            'actividadesProximas'
        ));
    }

   
    public function porLavadora($lavadora)
    {
        $linea = Linea::findOrFail($lavadora);
        $this->ensureCanViewTipo($this->tipoDesdeLinea($linea));
        
        $planes = PlanAccion::where('linea_id', $lavadora)
            ->with($this->relacionesTrazabilidad())
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        $estadisticas = [
            'total' => $planes->total(),
            'proximas' => PlanAccion::where('linea_id', $lavadora)
                ->where(function ($query) {
                    $now = Carbon::now();
                    $query->whereBetween('fecha_pcm1', [$now, $now->copy()->addDays(7)])
                        ->orWhereBetween('fecha_pcm2', [$now, $now->copy()->addDays(7)])
                        ->orWhereBetween('fecha_pcm3', [$now, $now->copy()->addDays(7)])
                        ->orWhereBetween('fecha_pcm4', [$now, $now->copy()->addDays(7)]);
                })
                ->count()
        ];
        
        return view('plan-accion.por-lavadora', compact('planes', 'linea', 'estadisticas'));
    }

 
    public function editarPlanAccion(Request $request)
    {
        $id = $request->input('id');
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        
        $plan = PlanAccion::with($this->relacionesTrazabilidad())->findOrFail($id);
        $this->ensureCanAccessPlan($plan);

        if (!$request->filled('tipo') && $plan->linea) {
            $tipo = $this->tipoDesdeLinea($plan->linea);
        }

        $this->ensureCanAccessTipo($tipo);

        $lineas = $this->obtenerLineasPorTipo($tipo, true);
        $tiposMaquinaSeleccionados = is_array($plan->tipo_maquina)
            ? $plan->tipo_maquina
            : ($plan->tipo_maquina ? json_decode($plan->tipo_maquina, true) : []);
        $areasPasteurizadora = PlanAccion::areasPasteurizadoraOpciones();
        $usuariosResponsables = $this->obtenerUsuariosResponsables();

        $lavadoras = $lineas;

        return view($this->obtenerVistaPorTipo($tipo, 'edit'), compact(
            'plan',
            'lineas',
            'lavadoras',
            'tiposMaquinaSeleccionados',
            'areasPasteurizadora',
            'usuariosResponsables',
            'tipo'
        ));
    }

    /**
     * POST /plan-accion/lavadora/update - Actualizar plan acción
     */
    public function updatePlanAccion(Request $request)
    {
        $id = $request->input('id');
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanAccessTipo($tipo);

        $plan = PlanAccion::findOrFail($id);
        $this->ensureCanAccessPlan($plan);

        $validated = $this->prepararDatosValidados(
            $request->validate($this->reglasValidacion($tipo)),
            $tipo
        );
        $plan->update($validated);

        return redirect()->route('plan-accion.index', [
            'tipo' => $tipo,
            'linea_id' => $validated['linea_id'],
        ])
            ->with('success', 'Actividad actualizada exitosamente');
    }

    /**
     * POST /plan-accion/lavadora/destroy - Eliminar plan acción
     */
    public function destroyPlanAccion(Request $request)
    {
        $id = $request->input('id');
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanAccessTipo($tipo);

        $plan = PlanAccion::findOrFail($id);
        $this->ensureCanAccessPlan($plan);
        $lineaId = $plan->linea_id;
        $plan->delete();

        return redirect()->route('plan-accion.index', [
            'tipo' => $tipo,
            'linea_id' => $lineaId,
        ])
            ->with('success', 'Actividad eliminada exitosamente');
    }

    /**
     * POST /plan-accion/{id}/notificar - Notificar actividad
     */
    public function notificar($id)
    {
        try {
            $plan = PlanAccion::with('linea')->findOrFail($id);
            $this->ensureCanAccessPlan($plan);

            $resultados = $this->notificationService->enviarNotificacionesManuales($id);
            
            return response()->json([
                'success' => true,
                'message' => $resultados['mensaje'],
                'data' => $resultados
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function notificacionesPendientes(Request $request): JsonResponse
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanViewTipo($tipo);

        $alertas = $this->obtenerAlertasGlobales($tipo);

        return response()->json([
            'total' => count($alertas),
            'notificaciones' => $alertas,
        ]);
    }

    public function marcarNotificacionLeida($id): JsonResponse
    {
        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * ===========================================================
     * MÉTODOS DEL RESOURCE (RESTful)
     * ===========================================================
     */

    /**
     * GET /plan-accion - Listar todos los planes
     */
    public function index(Request $request)
    {
        // Redirige al método específico de lavadoras por defecto
        return $this->planAccion($request);
    }

    /**
     * GET /plan-accion/create - Mostrar formulario de creación
     */
    public function create(Request $request)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanAccessTipo($tipo);

        $lineaSeleccionada = $request->get('linea_id');
        $lineas = $this->obtenerLineasPorTipo($tipo, true);
        $linea = $lineaSeleccionada ? $lineas->firstWhere('id', (int) $lineaSeleccionada) : null;
        $areasPasteurizadora = PlanAccion::areasPasteurizadoraOpciones();
        $usuariosResponsables = $this->obtenerUsuariosResponsables();

        return view($this->obtenerVistaPorTipo($tipo, 'create'), compact(
            'lineas',
            'tipo',
            'lineaSeleccionada',
            'linea',
            'areasPasteurizadora',
            'usuariosResponsables'
        ));
    }

    /**
     * POST /plan-accion - Guardar nueva actividad
     */
    public function store(Request $request)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanAccessTipo($tipo);

        $validated = $request->validate(array_merge($this->reglasValidacion($tipo), [
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array',
            'notificar_ahora' => 'nullable|boolean'
        ]));

        $validated = $this->prepararDatosValidados($validated, $tipo);
        $usuarioActualId = auth()->id();
        $validated['registrado_por_id'] = $usuarioActualId;
        $validated['responsable_id'] = $validated['responsable_id'] ?? $usuarioActualId;

        if (isset($validated['tipo_maquina'])) {
            $validated['tipo_maquina'] = json_encode($validated['tipo_maquina']);
        }

        $plan = PlanAccion::create($validated);

        if ($request->has('notificar_ahora') && $request->notificar_ahora) {
            $this->notificationService->enviarNotificacionesManuales($plan->id);
        }

        return redirect()->route('plan-accion.index', [
            'tipo' => $tipo,
            'linea_id' => $validated['linea_id'],
        ])
            ->with('success', 'Actividad creada correctamente' . 
                   ($request->notificar_ahora ? ' y notificaciones enviadas.' : ''));
    }

    /**
     * GET /plan-accion/{id} - Mostrar detalles de una actividad
     */
    public function show($id)
    {
        $plan = PlanAccion::with($this->relacionesTrazabilidad())->findOrFail($id);
        $this->ensureCanViewPlan($plan);

        return response()->json($plan);
    }

    /**
     * GET /plan-accion/{id}/edit - Editar actividad
     */
    public function edit(Request $request, $id)
    {
        // Reutiliza el método editarPlanAccion pero con parámetro en URL
        $request->merge(['id' => $id]);
        return $this->editarPlanAccion($request);
    }

    /**
     * PUT/PATCH /plan-accion/{id} - Actualizar actividad
     */
    public function update(Request $request, $id)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanAccessTipo($tipo);

        $plan = PlanAccion::findOrFail($id);
        $this->ensureCanAccessPlan($plan);

        $validated = $this->prepararDatosValidados(
            $request->validate($this->reglasValidacion($tipo)),
            $tipo
        );
        $plan->update($validated);

        return redirect()->route('plan-accion.index', [
            'tipo' => $tipo,
            'linea_id' => $validated['linea_id'],
        ])
            ->with('success', 'Actividad actualizada exitosamente');
    }

    /**
     * DELETE /plan-accion/{id} - Eliminar actividad
     */
    public function destroy(Request $request, $id)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
        $this->ensureCanAccessTipo($tipo);

        $plan = PlanAccion::findOrFail($id);
        $this->ensureCanAccessPlan($plan);
        $lineaId = $plan->linea_id;
        $plan->delete();

        return redirect()->route('plan-accion.index', [
            'tipo' => $tipo,
            'linea_id' => $lineaId,
        ])
            ->with('success', 'Actividad eliminada exitosamente');
    }

    /**
     * POST /plan-accion/{id}/enviar-notificaciones - Enviar notificaciones
     */
    public function enviarNotificaciones($id): JsonResponse
    {
        try {
            $plan = PlanAccion::with('linea')->findOrFail($id);
            $this->ensureCanAccessPlan($plan);
            
            $fechasProximas = false;
            $pcmConFechas = [];
            
            foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
                $fechaCampo = 'fecha_' . $pcm;
                if ($plan->$fechaCampo) {
                    $diasRestantes = (int) now()->startOfDay()->diffInDays(Carbon::parse($plan->$fechaCampo)->startOfDay(), false);
                    if ($diasRestantes <= 7 && $diasRestantes >= 0) {
                        $fechasProximas = true;
                        $pcmConFechas[] = [
                            'pcm' => $pcm,
                            'fecha' => $plan->$fechaCampo->format('d/m/Y'),
                            'dias' => $diasRestantes
                        ];
                    }
                }
            }
            
            if (!$fechasProximas) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay fechas próximas para notificar (próximos 7 días)'
                ]);
            }
            
            $resultados = $this->notificationService->enviarNotificacionesManuales($id);
            
            $exitosos = 0;
            $fallidos = 0;
            
            foreach ($resultados as $resultado) {
                if ($resultado === 'success') {
                    $exitosos++;
                } else {
                    $fallidos++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Notificaciones enviadas: {$exitosos} exitosas, {$fallidos} fallidas",
                'data' => [
                    'enviados' => $exitosos,
                    'fallidos' => $fallidos,
                    'pcm_notificados' => $pcmConFechas,
                    'detalles' => $resultados
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error enviando notificaciones: ' . $e->getMessage(), [
                'plan_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ===========================================================
     * MÉTODOS PRIVADOS (utilidades compartidas)
     * ===========================================================
     */

    /**
     * Obtener alertas globales
     */
     private function obtenerAlertasGlobales($tipo = 'lavadora')
    {
        $alertas = [];
        $hoy = Carbon::now()->startOfDay();
        $proximosDias = $hoy->copy()->addDays(7);
        $hoyFecha = $hoy->toDateString();
        $proximosDiasFecha = $proximosDias->toDateString();

        $actividadesProximas = $this->crearQueryPlanesPorTipo($this->normalizarTipo($tipo))
            ->where('completado', false)
            ->where(function ($query) use ($hoyFecha, $proximosDiasFecha) {
                foreach (['fecha_pcm1', 'fecha_pcm2', 'fecha_pcm3', 'fecha_pcm4'] as $campoFecha) {
                    $query->orWhere(function ($subQuery) use ($campoFecha, $hoyFecha, $proximosDiasFecha) {
                        $subQuery->whereDate($campoFecha, '>=', $hoyFecha)
                            ->whereDate($campoFecha, '<=', $proximosDiasFecha);
                    });
                }
            })
            ->get();

        foreach ($actividadesProximas as $plan) {
            foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
                $fechaCampo = 'fecha_' . $pcm;

                if ($plan->$fechaCampo) {
                    $fechaPcm = Carbon::parse($plan->$fechaCampo)->startOfDay();

                    if (!$fechaPcm->betweenIncluded($hoy, $proximosDias)) {
                        continue;
                    }

                    $diasRestantes = (int) $hoy->diffInDays($fechaPcm, false);
                    
                    $prioridad = 'baja';
                    if ($diasRestantes <= 1) {
                        $prioridad = 'alta';
                    } elseif ($diasRestantes <= 3) {
                        $prioridad = 'media';
                    }

                    $esManana = $diasRestantes == 1;

                    $alertas[] = [
                        'id' => $plan->id,
                        'linea_id' => $plan->linea_id,
                        'linea' => optional($plan->linea)->nombre ?? 'Sin línea',
                        'actividad' => $plan->actividad,
                        'tipo_equipo' => $this->tipoDesdePlan($plan),
                        'area_pasteurizadora' => $plan->area_pasteurizadora,
                        'area_pasteurizadora_label' => $this->resolveAreaPasteurizadoraLabel($plan),
                        'pcm' => strtoupper($pcm),
                        'fecha' => $fechaPcm->format('d/m/Y'),
                        'dias_restantes' => $diasRestantes,
                        'es_manana' => $esManana,
                        'prioridad' => $prioridad
                    ];
                }
            }
        }

        usort($alertas, function($a, $b) {
            $prioridades = ['alta' => 1, 'media' => 2, 'baja' => 3];
            if ($prioridades[$a['prioridad']] == $prioridades[$b['prioridad']]) {
                return $a['dias_restantes'] - $b['dias_restantes'];
            }
            return $prioridades[$a['prioridad']] - $prioridades[$b['prioridad']];
        });

        return $alertas;
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticas($tipo = 'lavadora')
    {
        $tipo = $this->normalizarTipo($tipo);

        return [
            'total_actividades' => $this->crearQueryPlanesPorTipo($tipo)->count(),
            'proximas_7_dias' => $this->contarActividadesProximas(7, $tipo),
            'proximas_30_dias' => $this->contarActividadesProximas(30, $tipo),
        ];
    }

    /**
     * Contar actividades próximas en X días
     */
     private function contarActividadesProximas($dias, $tipo = 'lavadora')
    {
        $hoy = Carbon::now()->startOfDay();
        $fechaLimite = $hoy->copy()->addDays($dias);

        return $this->crearQueryPlanesPorTipo($this->normalizarTipo($tipo))
            ->where('completado', false) 
            ->where(function ($query) use ($hoy, $fechaLimite) {
                foreach (['fecha_pcm1', 'fecha_pcm2', 'fecha_pcm3', 'fecha_pcm4'] as $campoFecha) {
                    $query->orWhere(function ($subQuery) use ($campoFecha, $hoy, $fechaLimite) {
                        $subQuery->whereDate($campoFecha, '>=', $hoy->toDateString())
                            ->whereDate($campoFecha, '<=', $fechaLimite->toDateString());
                    });
                }
            })->count();
    }

      public function checklist($id)
    {
        $plan = PlanAccion::with($this->relacionesTrazabilidad())->findOrFail($id);
        $this->ensureCanAccessPlan($plan);

        $plan->completado = !$plan->completado;

        if ($plan->completado) {
            $plan->ejecutado_por_id = auth()->id();
            $plan->fecha_ejecucion = now();

            if (!$plan->responsable_id) {
                $plan->responsable_id = auth()->id();
            }
        } else {
            $plan->ejecutado_por_id = null;
            $plan->fecha_ejecucion = null;
        }

        $plan->save();
        $plan->load(['ejecutadoPor', 'responsable']);

        // Opcional: Aquí podrías agregar lógica para resetear alertas si se desmarca.
        // Por ahora, solo guardamos el cambio.

        return response()->json([
            'completado' => $plan->completado,
            'ejecutado_por' => $plan->ejecutadoPor,
            'responsable' => $plan->responsable,
            'fecha_ejecucion' => optional($plan->fecha_ejecucion)->toISOString(),
        ]);
    }

    private function normalizarTipo(?string $tipo): string
    {
        return $this->normalizarTipoValido($tipo) ?? User::MODULE_LAVADORA;
    }

    private function normalizarTipoValido(?string $tipo): ?string
    {
        $tipo = strtolower(trim((string) $tipo));

        if (str_contains($tipo, User::MODULE_PASTEURIZADORA)) {
            return User::MODULE_PASTEURIZADORA;
        }

        if (str_contains($tipo, User::MODULE_ETIQUETADORA)) {
            return User::MODULE_ETIQUETADORA;
        }

        if (str_contains($tipo, User::MODULE_LAVADORA)) {
            return User::MODULE_LAVADORA;
        }

        return null;
    }

    private function reglasValidacion(string $tipo): array
    {
        $tipo = $this->normalizarTipo($tipo);

        $reglas = [
            'linea_id' => ['required', 'exists:lineas,id', Rule::in($this->obtenerLineaIdsPorTipo($tipo))],
            'actividad' => 'required|string|max:1000',
            'responsable_id' => ['nullable', 'exists:users,id'],
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
        ];

        if ($tipo === 'pasteurizadora') {
            $reglas['area_pasteurizadora'] = [
                'required',
                Rule::in(array_keys(PlanAccion::areasPasteurizadoraOpciones())),
            ];
        }

        return $reglas;
    }

    private function prepararDatosValidados(array $validated, string $tipo): array
    {
        $tipo = $this->normalizarTipo($tipo);
        $validated['tipo_equipo'] = $tipo;

        if ($tipo === 'pasteurizadora') {
            $validated['area_pasteurizadora'] = PlanAccion::normalizarAreaPasteurizadora(
                $validated['area_pasteurizadora'] ?? null
            );
        } else {
            unset($validated['area_pasteurizadora']);
        }

        return $validated;
    }

    private function resolveAreaPasteurizadoraLabel(PlanAccion $plan): ?string
    {
        if ($plan->tipo_equipo !== 'pasteurizadora' || !$plan->area_pasteurizadora) {
            return null;
        }

        return $plan->area_pasteurizadora_label;
    }

    private function ensureCanAccessTipo(string $tipo): void
    {
        $tipo = $this->normalizarTipo($tipo);

        if (!auth()->user()?->canAccessModule($tipo)) {
            abort(403, 'No tienes permiso para acceder al modulo solicitado.');
        }
    }

    private function ensureCanViewTipo(string $tipo): void
    {
        if (!auth()->user()?->canViewPlanActionType($this->normalizarTipo($tipo))) {
            abort(403, 'No tienes permiso para visualizar Plan de Accion.');
        }
    }

    private function ensureCanAccessPlan(PlanAccion $plan): void
    {
        $tipo = $this->tipoDesdePlan($plan);

        $this->ensureCanAccessTipo($tipo);
    }

    private function ensureCanViewPlan(PlanAccion $plan): void
    {
        $tipo = $this->tipoDesdePlan($plan);

        $this->ensureCanViewTipo($tipo);
    }

    private function tipoDesdePlan(PlanAccion $plan): string
    {
        $tipoEquipo = $this->normalizarTipoValido($plan->tipo_equipo);

        if ($tipoEquipo) {
            return $tipoEquipo;
        }

        return $plan->linea
            ? $this->tipoDesdeLinea($plan->linea)
            : User::MODULE_LAVADORA;
    }

    private function obtenerLineaIdsPermitidas(): array
    {
        $lineaIds = $this->obtenerLineaIdsPorTipo('lavadora');

        if (auth()->user()?->canViewPlanActionType(User::MODULE_PASTEURIZADORA)) {
            $lineaIds = array_merge($lineaIds, $this->obtenerLineaIdsPorTipo('pasteurizadora'));
        }

        if (auth()->user()?->canViewPlanActionType(User::MODULE_ETIQUETADORA)) {
            $lineaIds = array_merge($lineaIds, $this->obtenerLineaIdsPorTipo('etiquetadora'));
        }

        return array_values(array_unique($lineaIds));
    }

    private function obtenerVistaPorTipo(string $tipo, string $pantalla): string
    {
        return 'plan-accion.' . $this->normalizarTipo($tipo) . '.' . $pantalla;
    }

    private function obtenerLineasPorTipo(string $tipo, bool $soloActivas = false)
    {
        $query = Linea::query();

        $tipo = $this->normalizarTipo($tipo);

        if ($tipo === 'lavadora') {
            $query->whereIn('id', $this->lineasLavadoraIds);
        } elseif ($tipo === 'etiquetadora') {
            $query->whereIn('nombre', $this->lineasEtiquetadoraNombres);
        } else {
            $query->whereIn('nombre', $this->lineasPasteurizadoraNombres);
        }

        if ($soloActivas) {
            $query->where('activo', true);
        }

        return $query->orderBy('nombre')->get();
    }

    private function obtenerLineaIdsPorTipo(string $tipo, bool $soloActivas = false): array
    {
        return $this->obtenerLineasPorTipo($tipo, $soloActivas)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function crearQueryPlanesPorTipo(string $tipo)
    {
        $tipo = $this->normalizarTipo($tipo);
        $query = PlanAccion::with($this->relacionesTrazabilidad());

        $query->whereIn('linea_id', $this->obtenerLineaIdsPorTipo($tipo));

        if ($tipo === User::MODULE_LAVADORA) {
            $query->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_LAVADORA)
                    ->orWhereNull('tipo_equipo');
            });
        } elseif ($tipo === User::MODULE_PASTEURIZADORA) {
            $query->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_PASTEURIZADORA)
                    ->orWhereNull('tipo_equipo');
            });
        } else {
            $query->where('tipo_equipo', $tipo);
        }

        return $query;
    }

    private function relacionesTrazabilidad(): array
    {
        return ['linea', 'responsable', 'registradoPor', 'ejecutadoPor'];
    }

    private function obtenerUsuariosResponsables()
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function tipoDesdeLinea(Linea $linea): string
    {
        if (in_array($linea->nombre, $this->lineasPasteurizadoraNombres, true)) {
            return User::MODULE_PASTEURIZADORA;
        }

        return $this->normalizarTipoValido($linea->tipo ?? null) ?? User::MODULE_LAVADORA;
    }

}
