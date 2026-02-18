<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Linea;

class PlanAccionController extends Controller
{
    public function index(Request $request)
    {
        // Obtener parámetros de filtro
        $tipo = $request->get('tipo', 'lavadora'); // Por defecto mostrar lavadoras
        $lineaId = $request->get('linea_id');
        
        // Definir IDs específicos para lavadoras (líneas 4,5,6,7,8,9,12,13)
        $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
        
        // Obtener líneas según el tipo seleccionado
        if ($tipo == 'lavadora') {
            // Solo mostrar las líneas de lavadora específicas
            $lineasTipo = Linea::whereIn('id', $lineasLavadoraIds)
                                ->orderBy('nombre')
                                ->get();
        } else {
            // Mostrar todas las líneas para pasteurizadora
            $lineasTipo = Linea::orderBy('nombre')->get();
        }
        
        // Construir query de planes de acción
        $query = PlanAccion::with(['linea', 'responsable']);
        
        // Aplicar filtros según tipo y línea
        if ($lineaId) {
            // Si se seleccionó una línea específica
            $query->where('linea_id', $lineaId);
        } else {
            // Si no hay línea específica, filtrar por el tipo seleccionado
            if ($tipo == 'lavadora') {
                $query->whereIn('linea_id', $lineasLavadoraIds);
            } else {
                // Para pasteurizadora, mostrar todas las líneas
                // No aplicamos filtro adicional
            }
        }
        
        // Ordenar por fecha de creación
        $planes = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Alertas globales
        $alertas = $this->obtenerAlertasGlobales();
        
        // Estadísticas actualizadas
        $estadisticas = $this->obtenerEstadisticas($lineasLavadoraIds);
        
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
        
        // Definir IDs específicos para lavadoras
        $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
        
        if ($tipo == 'lavadora') {
            // Solo mostrar líneas de lavadora
            $lineas = Linea::whereIn('id', $lineasLavadoraIds)
                            ->where('activo', true)
                            ->orderBy('nombre')
                            ->get();
        } else {
            // Mostrar todas las líneas para pasteurizadora
            $lineas = Linea::where('activo', true)
                            ->orderBy('nombre')
                            ->get();
        }

        $responsables = User::where('activo', true)->get();
        
        return view('plan-accion.create', compact('lineas', 'responsables', 'tipo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'actividad' => 'required|string|max:1000',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
            'estado' => 'required|in:pendiente,en_proceso,completada,atrasada',
            'responsable_id' => 'nullable|exists:users,id',
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array'
        ]);

        // Convertir el array a JSON para guardar
        if (isset($validated['tipo_maquina'])) {
            $validated['tipo_maquina'] = json_encode($validated['tipo_maquina']);
        }

        PlanAccion::create($validated);

        // Obtener el tipo de la línea para redireccionar
        $linea = Linea::find($validated['linea_id']);
        $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
        $tipo = in_array($linea->id, $lineasLavadoraIds) ? 'lavadora' : 'pasteurizadora';

        return redirect()->route('plan-accion.index', ['tipo' => $tipo])
            ->with('success', 'Actividad creada correctamente');
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
        
        // Definir IDs específicos para lavadoras
        $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
        
        if ($tipo == 'lavadora') {
            // Solo mostrar líneas de lavadora
            $lavadoras = Linea::whereIn('id', $lineasLavadoraIds)
                                ->where('activo', true)
                                ->orderBy('nombre')
                                ->get();
        } else {
            // Mostrar todas las líneas para pasteurizadora
            $lavadoras = Linea::where('activo', true)
                                ->orderBy('nombre')
                                ->get();
        }
        
        $responsables = User::where('activo', true)->get();
        
        // Decodificar los tipos de máquina para mostrarlos en el formulario
        $tiposMaquinaSeleccionados = $plan->tipo_maquina ? json_decode($plan->tipo_maquina, true) : [];
        
        return view('plan-accion.edit', compact('plan', 'lavadoras', 'responsables', 'tiposMaquinaSeleccionados', 'tipo'));
    }

    public function update(Request $request, $id)
    {
        $plan = PlanAccion::findOrFail($id);
        
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'actividad' => 'required|string|max:500',
            'fecha_pcm1' => 'nullable|date',
            'fecha_pcm2' => 'nullable|date',
            'fecha_pcm3' => 'nullable|date',
            'fecha_pcm4' => 'nullable|date',
            'estado' => 'required|in:pendiente,en_proceso,completada,atrasada',
            'responsable_id' => 'nullable|exists:users,id',
            'observaciones' => 'nullable|string',
            'tipo_maquina' => 'nullable|array'
        ]);

        // Convertir el array a JSON para guardar
        if (isset($validated['tipo_maquina'])) {
            $validated['tipo_maquina'] = json_encode($validated['tipo_maquina']);
        } else {
            $validated['tipo_maquina'] = null;
        }

        $plan->update($validated);
        
        // Actualizar estado automáticamente
        $plan->actualizarEstado();

        // Obtener el tipo de la línea para redireccionar
        $linea = Linea::find($validated['linea_id']);
        $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
        $tipo = in_array($linea->id, $lineasLavadoraIds) ? 'lavadora' : 'pasteurizadora';

        return redirect()->route('plan-accion.index', ['tipo' => $tipo])
                         ->with('success', 'Actividad actualizada exitosamente');
    }

    public function destroy(Request $request, $id)
    {
        $plan = PlanAccion::findOrFail($id);
        $tipo = $request->get('tipo', 'lavadora');
        
        $plan->delete();

        return redirect()->route('plan-accion.index', ['tipo' => $tipo])
                         ->with('success', 'Actividad eliminada exitosamente');
    }

    public function dashboard()
    {
        $lavadoras = Linea::orderBy('nombre')->get();
        $lavadoras = Linea::withCount(['planesAccion' => function($query) {
            $query->where('estado', '!=', 'completada');
        }])->get();
        
        $actividadesProximas = PlanAccion::with('linea')
            ->whereHas('linea', function($q) {
                $q->where('activo', true);
            })
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->where(function($query) {
                $query->whereDate('fecha_pcm1', '>=', now())
                      ->orWhereDate('fecha_pcm2', '>=', now())
                      ->orWhereDate('fecha_pcm3', '>=', now())
                      ->orWhereDate('fecha_pcm4', '>=', now());
            })
            ->orderByRaw('LEAST(
                COALESCE(fecha_pcm1, "9999-12-31"),
                COALESCE(fecha_pcm2, "9999-12-31"),
                COALESCE(fecha_pcm3, "9999-12-31"),
                COALESCE(fecha_pcm4, "9999-12-31")
            ) ASC')
            ->limit(10)
            ->get();
        
        $alertas = $this->obtenerAlertasGlobales();
        
        return view('plan-accion.dashboard', compact('lavadoras', 'actividadesProximas', 'alertas'));
    }

    private function obtenerAlertasGlobales()
    {
        $alertas = [];
        $hoy = Carbon::now();
        $manana = Carbon::now()->addDay();
        $proximosDias = Carbon::now()->addDays(7);
        
        $actividadesProximas = PlanAccion::with('linea')
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->where(function($query) use ($hoy, $proximosDias) {
                $query->whereBetween('fecha_pcm1', [$hoy, $proximosDias])
                      ->orWhereBetween('fecha_pcm2', [$hoy, $proximosDias])
                      ->orWhereBetween('fecha_pcm3', [$hoy, $proximosDias])
                      ->orWhereBetween('fecha_pcm4', [$hoy, $proximosDias]);
            })
            ->get();
        
        foreach ($actividadesProximas as $plan) {
            foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
                $fechaCampo = 'fecha_' . $pcm;
                if ($plan->$fechaCampo && $plan->$fechaCampo >= $hoy && $plan->$fechaCampo <= $proximosDias) {
                    $diasRestantes = $hoy->diffInDays($plan->$fechaCampo, false);
                    
                    // Determinar prioridad
                    $prioridad = $diasRestantes <= 3 ? 'alta' : ($diasRestantes <= 5 ? 'media' : 'baja');
                    
                    $alertas[] = [
                        'id' => $plan->id,
                        'linea_id' => $plan->linea_id,
                        'linea' => optional($plan->linea)->nombre ?? 'Sin línea',
                        'actividad' => $plan->actividad,
                        'pcm' => strtoupper($pcm),
                        'fecha' => $plan->$fechaCampo->format('d/m/Y'),
                        'dias_restantes' => $diasRestantes,
                        'prioridad' => $prioridad,
                        'es_manana' => $plan->$fechaCampo->format('Y-m-d') == $manana->format('Y-m-d')
                    ];
                }
            }
        }
        
        // Ordenar por prioridad y días restantes
        usort($alertas, function($a, $b) {
            $prioridad = ['alta' => 1, 'media' => 2, 'baja' => 3];
            if ($prioridad[$a['prioridad']] != $prioridad[$b['prioridad']]) {
                return $prioridad[$a['prioridad']] - $prioridad[$b['prioridad']];
            }
            return $a['dias_restantes'] - $b['dias_restantes'];
        });
        
        return $alertas;
    }

    private function obtenerEstadisticas($lineasLavadoraIds = [])
    {
        // Total de lavadoras (solo las líneas específicas)
        $totalLavadoras = Linea::whereIn('id', $lineasLavadoraIds)->count();
        
        // Total de pasteurizadoras (todas las líneas)
        $totalPasteurizadoras = Linea::count();
        
        // Actividades pendientes (sin filtrar por tipo)
        $actividadesPendientes = PlanAccion::whereIn('estado', ['pendiente', 'en_proceso'])->count();
        
        // Actividades completadas
        $actividadesCompletadas = PlanAccion::where('estado', 'completada')->count();
        
        // Actividades atrasadas
        $actividadesAtrasadas = PlanAccion::where('estado', 'atrasada')->count();
        
        return [
            'total_lavadoras' => $totalLavadoras,
            'total_pasteurizadoras' => $totalPasteurizadoras,
            'total_actividades' => PlanAccion::count(),
            'actividades_pendientes' => $actividadesPendientes,
            'actividades_completadas' => $actividadesCompletadas,
            'actividades_atrasadas' => $actividadesAtrasadas,
            'proximas_7_dias' => $this->contarActividadesProximas(7),
            'proximas_30_dias' => $this->contarActividadesProximas(30)
        ];
    }

    private function contarActividadesProximas($dias)
    {
        $fechaLimite = Carbon::now()->addDays($dias);
        
        return PlanAccion::whereIn('estado', ['pendiente', 'en_proceso'])
            ->where(function($query) use ($fechaLimite) {
                $query->whereBetween('fecha_pcm1', [now(), $fechaLimite])
                      ->orWhereBetween('fecha_pcm2', [now(), $fechaLimite])
                      ->orWhereBetween('fecha_pcm3', [now(), $fechaLimite])
                      ->orWhereBetween('fecha_pcm4', [now(), $fechaLimite]);
            })
            ->count();
    }

    private function verificarNotificacionesIniciales($plan)
    {
        // Lógica para enviar notificaciones si es necesario
    }
}