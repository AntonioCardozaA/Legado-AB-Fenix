<?php

namespace App\Http\Controllers;

use App\Models\PlanAccion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Linea;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PlanAccionController extends Controller
{
    private $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
    private $lineasPasteurizadoraNombres = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
    
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function planAccion(Request $request)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));
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
        $estadisticas = $this->obtenerEstadisticas();
        $alertas = $this->obtenerAlertasGlobales();
        
        $actividadesPorLinea = PlanAccion::with('linea')
            ->select('linea_id', \DB::raw('count(*) as total'))
            ->groupBy('linea_id')
            ->get();
        
        $actividadesProximas = PlanAccion::with('linea')
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
        
        $planes = PlanAccion::where('linea_id', $lavadora)
            ->with('linea')
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
        
        $plan = PlanAccion::with('linea')->findOrFail($id);

        if (!$request->filled('tipo') && $plan->linea) {
            $tipo = $this->tipoDesdeLinea($plan->linea);
        }

        $lineas = $this->obtenerLineasPorTipo($tipo, true);
        $tiposMaquinaSeleccionados = is_array($plan->tipo_maquina)
            ? $plan->tipo_maquina
            : ($plan->tipo_maquina ? json_decode($plan->tipo_maquina, true) : []);

        $lavadoras = $lineas;

        return view($this->obtenerVistaPorTipo($tipo, 'edit'), compact(
            'plan',
            'lineas',
            'lavadoras',
            'tiposMaquinaSeleccionados',
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
        $plan = PlanAccion::findOrFail($id);

        $validated = $request->validate([
            'linea_id' => ['required', 'exists:lineas,id', Rule::in($this->obtenerLineaIdsPorTipo($tipo))],
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
        ]);

        $validated['tipo_equipo'] = $tipo;
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
        $plan = PlanAccion::findOrFail($id);
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
        $lineaSeleccionada = $request->get('linea_id');
        $lineas = $this->obtenerLineasPorTipo($tipo, true);
        $linea = $lineaSeleccionada ? $lineas->firstWhere('id', (int) $lineaSeleccionada) : null;

        return view($this->obtenerVistaPorTipo($tipo, 'create'), compact(
            'lineas',
            'tipo',
            'lineaSeleccionada',
            'linea'
        ));
    }

    /**
     * POST /plan-accion - Guardar nueva actividad
     */
    public function store(Request $request)
    {
        $tipo = $this->normalizarTipo($request->get('tipo', 'lavadora'));

        $validated = $request->validate([
            'linea_id' => ['required', 'exists:lineas,id', Rule::in($this->obtenerLineaIdsPorTipo($tipo))],
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array',
            'notificar_ahora' => 'nullable|boolean'
        ]);

        $validated['tipo_equipo'] = $tipo;

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
        $plan = PlanAccion::with(['linea'])->findOrFail($id);
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
        $plan = PlanAccion::findOrFail($id);

        $validated = $request->validate([
            'linea_id' => ['required', 'exists:lineas,id', Rule::in($this->obtenerLineaIdsPorTipo($tipo))],
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
        ]);

        $validated['tipo_equipo'] = $tipo;
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
        $plan = PlanAccion::findOrFail($id);
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
                        'linea' => optional($plan->linea)->nombre ?? 'Sin línea',
                        'actividad' => $plan->actividad,
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
        $plan = PlanAccion::findOrFail($id);
        $plan->completado = !$plan->completado;
        $plan->save();

        // Opcional: Aquí podrías agregar lógica para resetear alertas si se desmarca.
        // Por ahora, solo guardamos el cambio.

        return response()->json([
            'completado' => $plan->completado
        ]);
    }

    private function normalizarTipo(?string $tipo): string
    {
        return $tipo === 'pasteurizadora' ? 'pasteurizadora' : 'lavadora';
    }

    private function obtenerVistaPorTipo(string $tipo, string $pantalla): string
    {
        return 'plan-accion.' . $this->normalizarTipo($tipo) . '.' . $pantalla;
    }

    private function obtenerLineasPorTipo(string $tipo, bool $soloActivas = false)
    {
        $query = Linea::query();

        if ($this->normalizarTipo($tipo) === 'lavadora') {
            $query->whereIn('id', $this->lineasLavadoraIds);
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
        $query = PlanAccion::with(['linea'])
            ->where('tipo_equipo', $this->normalizarTipo($tipo));

        return $query->whereIn('linea_id', $this->obtenerLineaIdsPorTipo($tipo));
    }

    private function tipoDesdeLinea(Linea $linea): string
    {
        return in_array($linea->nombre, $this->lineasPasteurizadoraNombres, true)
            ? 'pasteurizadora'
            : 'lavadora';
    }

}
