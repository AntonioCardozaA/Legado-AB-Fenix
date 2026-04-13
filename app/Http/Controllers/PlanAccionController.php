<?php

namespace App\Http\Controllers;

use App\Models\PlanAccion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Linea;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class PlanAccionController extends Controller
{
    private $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
    
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * ===========================================================
     * MÉTODOS PARA RUTAS ESPECÍFICAS (definidas en web.php)
     * ===========================================================
     */

    /**
     * GET /plan-accion/lavadora - Índice de lavadoras
     */
    public function planAccion(Request $request)
    {
        $tipo = $request->get('tipo', 'lavadora');
        $lineaId = $request->get('linea_id');

        if ($tipo == 'lavadora') {
            $lineasTipo = Linea::whereIn('id', $this->lineasLavadoraIds)
                ->orderBy('nombre')
                ->get();
        } else {
            $lineasTipo = Linea::orderBy('nombre')->get();
        }

        $query = PlanAccion::with(['linea']);

        if ($lineaId) {
            $query->where('linea_id', $lineaId);
        } elseif ($tipo == 'lavadora') {
            $query->whereIn('linea_id', $this->lineasLavadoraIds);
        }

        $planes = $query->orderBy('created_at', 'desc')->paginate(15);

        $alertas = $this->obtenerAlertasGlobales();
        $estadisticas = $this->obtenerEstadisticas();

        return view('plan-accion.lavadora.index', compact(
            'lineasTipo',
            'planes',
            'alertas',
            'estadisticas',
            'tipo',
            'lineaId'
        ));
    }

    /**
     * GET /plan-accion/dashboard - Dashboard de plan de acción
     */
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

    /**
     * GET /plan-accion/por-lavadora/{lavadora} - Filtrar por lavadora específica
     */
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

    /**
     * POST /plan-accion/lavadora/edit - Editar plan acción (por POST)
     */
    public function editarPlanAccion(Request $request)
    {
        $id = $request->input('id');
        $tipo = $request->get('tipo', 'lavadora');
        
        $plan = PlanAccion::findOrFail($id);

        if ($tipo == 'lavadora') {
            $lavadoras = Linea::whereIn('id', $this->lineasLavadoraIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
        } else {
            $lavadoras = Linea::where('activo', true)
                ->orderBy('nombre')
                ->get();
        }

        $tiposMaquinaSeleccionados = $plan->tipo_maquina
            ? json_decode($plan->tipo_maquina, true)
            : [];

        return view('plan-accion.lavadora.edit', compact(
            'plan',
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
        $plan = PlanAccion::findOrFail($id);

        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
        ]);

        $plan->update($validated);

        return redirect()->route('plan-accion.lavadora.index')
            ->with('success', 'Actividad actualizada exitosamente');
    }

    /**
     * POST /plan-accion/lavadora/destroy - Eliminar plan acción
     */
    public function destroyPlanAccion(Request $request)
    {
        $id = $request->input('id');
        $plan = PlanAccion::findOrFail($id);
        $plan->delete();

        return redirect()->route('plan-accion.lavadora.index')
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
        $tipo = $request->get('tipo', 'lavadora');
        $lineaSeleccionada = $request->get('linea_id');

        if ($tipo == 'lavadora') {
            $lineas = Linea::whereIn('id', $this->lineasLavadoraIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
        } else {
            $lineas = Linea::where('activo', true)
                ->orderBy('nombre')
                ->get();
        }

        return view('plan-accion.lavadora.create', compact(
            'lineas',
            'tipo',
            'lineaSeleccionada'
        ));
    }

    /**
     * POST /plan-accion - Guardar nueva actividad
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array',
            'notificar_ahora' => 'nullable|boolean'
        ]);

        if (isset($validated['tipo_maquina'])) {
            $validated['tipo_maquina'] = json_encode($validated['tipo_maquina']);
        }

        $plan = PlanAccion::create($validated);

        if ($request->has('notificar_ahora') && $request->notificar_ahora) {
            $this->notificationService->notificarActividadManualmente($plan->id);
        }

        return redirect()->route('plan-accion.lavadora.index')
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
        $plan = PlanAccion::findOrFail($id);

        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
        ]);

        $plan->update($validated);

        return redirect()->route('plan-accion.lavadora.index')
            ->with('success', 'Actividad actualizada exitosamente');
    }

    /**
     * DELETE /plan-accion/{id} - Eliminar actividad
     */
    public function destroy($id)
    {
        $plan = PlanAccion::findOrFail($id);
        $plan->delete();

        return redirect()->route('plan-accion.lavadora.index')
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
                    $diasRestantes = now()->diffInDays($plan->$fechaCampo, false);
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
            
            $resultados = $this->notificationService->notificarActividadManualmente($id);
            
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
    private function obtenerAlertasGlobales()
    {
        $alertas = [];
        $hoy = Carbon::now();
        $proximosDias = Carbon::now()->addDays(7);

        $actividadesProximas = PlanAccion::with('linea')
            ->where(function ($query) use ($hoy, $proximosDias) {
                $query->whereBetween('fecha_pcm1', [$hoy, $proximosDias])
                    ->orWhereBetween('fecha_pcm2', [$hoy, $proximosDias])
                    ->orWhereBetween('fecha_pcm3', [$hoy, $proximosDias])
                    ->orWhereBetween('fecha_pcm4', [$hoy, $proximosDias]);
            })
            ->get();

        foreach ($actividadesProximas as $plan) {
            foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
                $fechaCampo = 'fecha_' . $pcm;

                if ($plan->$fechaCampo &&
                    $plan->$fechaCampo >= $hoy &&
                    $plan->$fechaCampo <= $proximosDias) {

                    $diasRestantes = $hoy->diffInDays($plan->$fechaCampo, false);
                    
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
                        'fecha' => Carbon::parse($plan->$fechaCampo)->format('d/m/Y'),
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
    private function obtenerEstadisticas()
    {
        return [
            'total_actividades' => PlanAccion::count(),
            'proximas_7_dias' => $this->contarActividadesProximas(7),
            'proximas_30_dias' => $this->contarActividadesProximas(30),
        ];
    }

    /**
     * Contar actividades próximas en X días
     */
    private function contarActividadesProximas($dias)
    {
        $fechaLimite = Carbon::now()->addDays($dias);

        return PlanAccion::where(function ($query) use ($fechaLimite) {
            $query->whereBetween('fecha_pcm1', [now(), $fechaLimite])
                ->orWhereBetween('fecha_pcm2', [now(), $fechaLimite])
                ->orWhereBetween('fecha_pcm3', [now(), $fechaLimite])
                ->orWhereBetween('fecha_pcm4', [now(), $fechaLimite]);
        })->count();
    }

    public function checklist($id)
    {
        $plan = PlanAccion::findOrFail($id);

        $plan->completado = !$plan->completado;
        $plan->save();

        return response()->json([
            'completado' => $plan->completado
        ]);
    }

}