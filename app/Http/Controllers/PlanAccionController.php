<?php

namespace App\Http\Controllers;

use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Linea;
use App\Services\NotificationService; // Agregado para notificaciones
use Illuminate\Http\JsonResponse;

class PlanAccionController extends Controller
{
    private $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
    
    // Propiedad para el servicio de notificaciones
    protected $notificationService;

    // Constructor para inyectar el servicio de notificaciones
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
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

        $query = PlanAccion::with(['linea', 'responsable']);

        if ($lineaId) {
            $query->where('linea_id', $lineaId);
        } elseif ($tipo == 'lavadora') {
            $query->whereIn('linea_id', $this->lineasLavadoraIds);
        }

        $planes = $query->orderBy('created_at', 'desc')->paginate(15);

        $alertas = $this->obtenerAlertasGlobales();
        $estadisticas = $this->obtenerEstadisticas();

        return view('plan-accion.index', compact(
            'lineasTipo',
            'planes',
            'alertas',
            'estadisticas',
            'tipo',
            'lineaId'
        ));
    }

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

        $responsables = User::where('activo', true)->get();

        return view('plan-accion.create', compact(
            'lineas',
            'responsables',
            'tipo',
            'lineaSeleccionada'
        ));
    }

    // Método store modificado para incluir notificaciones
    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
            'responsable_id' => 'nullable|exists:users,id',
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array',
            'notificar_ahora' => 'nullable|boolean' // Nuevo campo para notificar inmediatamente
        ]);

        if (isset($validated['tipo_maquina'])) {
            $validated['tipo_maquina'] = json_encode($validated['tipo_maquina']);
        }

        $plan = PlanAccion::create($validated);

        // Si se solicita notificar inmediatamente
        if ($request->has('notificar_ahora') && $request->notificar_ahora) {
            $this->notificationService->notificarActividadManualmente($plan->id);
        }

        return redirect()->route('plan-accion.index')
            ->with('success', 'Actividad creada correctamente' . 
                   ($request->notificar_ahora ? ' y notificaciones enviadas.' : ''));
    }

    public function show($id)
    {
        $plan = PlanAccion::with(['linea', 'responsable'])->findOrFail($id);
        return response()->json($plan);
    }

    public function edit(Request $request, $id)
    {
        $plan = PlanAccion::findOrFail($id);
        $tipo = $request->get('tipo', 'lavadora');

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

        $responsables = User::where('activo', true)->get();

        $tiposMaquinaSeleccionados = $plan->tipo_maquina
            ? json_decode($plan->tipo_maquina, true)
            : [];

        return view('plan-accion.edit', compact(
            'plan',
            'lavadoras',
            'responsables',
            'tiposMaquinaSeleccionados',
            'tipo'
        ));
    }

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
            'responsable_id' => 'nullable|exists:users,id',
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array'
        ]);

        if (isset($validated['tipo_maquina'])) {
            $validated['tipo_maquina'] = json_encode($validated['tipo_maquina']);
        } else {
            $validated['tipo_maquina'] = null;
        }

        $plan->update($validated);

        return redirect()->route('plan-accion.index')
            ->with('success', 'Actividad actualizada exitosamente');
    }

    public function destroy($id)
    {
        $plan = PlanAccion::findOrFail($id);
        $plan->delete();

        return redirect()->route('plan-accion.index')
            ->with('success', 'Actividad eliminada exitosamente');
    }

    /**
     * Enviar notificaciones manualmente para una actividad específica
     */
    public function enviarNotificaciones($id): JsonResponse
{
    try {
        $plan = PlanAccion::with('linea')->findOrFail($id);
        
        // Verificar si hay fechas próximas
        $fechasProximas = false;
        $pcmConFechas = [];
        
        foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
            $fechaCampo = 'fecha_' . $pcm;
            if ($plan->$fechaCampo) {
                $diasRestantes = now()->diffInDays($plan->$fechaCampo, false);
                if ($diasRestantes <= 7) { // Solo notificar si está próximo (7 días o menos)
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
                'message' => 'No hay fechas próximas para notificar (menos de 7 días)'
            ]);
        }
        
        // Enviar notificaciones usando el servicio
        $notificationService = app(NotificationService::class);
        $resultados = $notificationService->notificarActividadManualmente($id);
        
        // Contar éxitos
        $exitosos = 0;
        $fallidos = 0;
        
        foreach ($resultados as $usuarioId => $resultado) {
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
                
                // Determinar prioridad basada en días restantes
                $prioridad = 'baja';
                if ($diasRestantes <= 1) {
                    $prioridad = 'alta';
                } elseif ($diasRestantes <= 3) {
                    $prioridad = 'media';
                }

                // Verificar si es mañana
                $esManana = $diasRestantes == 1;

                $alertas[] = [
                    'id' => $plan->id,
                    'linea' => optional($plan->linea)->nombre ?? 'Sin línea',
                    'actividad' => $plan->actividad,
                    'pcm' => strtoupper($pcm),
                    'fecha' => Carbon::parse($plan->$fechaCampo)->format('d/m/Y'),
                    'dias_restantes' => $diasRestantes,
                    'es_manana' => $esManana,
                    'prioridad' => $prioridad // ← ESTO ES LO QUE FALTABA
                ];
            }
        }
    }

    // Ordenar alertas por prioridad (alta primero) y días restantes
    usort($alertas, function($a, $b) {
        $prioridades = ['alta' => 1, 'media' => 2, 'baja' => 3];
        if ($prioridades[$a['prioridad']] == $prioridades[$b['prioridad']]) {
            return $a['dias_restantes'] - $b['dias_restantes'];
        }
        return $prioridades[$a['prioridad']] - $prioridades[$b['prioridad']];
    });

    return $alertas;
}

    private function obtenerEstadisticas()
    {
        return [
            'total_actividades' => PlanAccion::count(),
            'proximas_7_dias' => $this->contarActividadesProximas(7),
            'proximas_30_dias' => $this->contarActividadesProximas(30),
        ];
    }

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
}