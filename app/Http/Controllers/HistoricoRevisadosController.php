<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\AnalisisPasteurizadora;
use App\Models\HistorialRestablecimiento;
use App\Models\HistoricoRevisados;
use App\Models\User;
use App\Services\LavadoraRevisionPeriodicityService;
use App\Support\LavadoraCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HistoricoRevisadosController extends Controller
{
    public function __construct(
        private readonly LavadoraRevisionPeriodicityService $periodicityService
    ) {
    }

    /**
     * Configuración de componentes por línea de lavadora
     */
    private $componentesLavadora = LavadoraCatalog::COMPONENTES_POR_LINEA;

/**
     * Configuración de cantidades totales POR LÍNEA (según lo especificado)
     */
    private $cantidadesPorLinea = [
        'L-04' => 13,
        'L-05' => 14,
        'L-06' => 15,
        'L-07' => 15,
        'L-08' => 15, // Línea 8 no se mencionó, se deja con 15 como valor por defecto
        'L-09' => 13,
        'L-12' => 14,
        'L-13' => 14,
    ];

    /**
     * Mostrar histórico de revisados con filtros por línea
     */
    public function index(Request $request)
    {
        // Obtener todas las líneas activas
        $lineas = Linea::where('activo', true)
            ->orderBy('nombre')
            ->get();

        // Filtrar líneas que tienen lavadora (las que están en $componentesLavadora)
        $nombresLavadora = array_keys($this->componentesLavadora);
        $lineasLavadora = $lineas->filter(function($linea) use ($nombresLavadora) {
            return in_array($linea->nombre, $nombresLavadora);
        })->values();

        // Para pasteurizadora, mostrar SOLO líneas de pasteurizadora (P-03 a P-14)
        $pasteurizadorasPermitidas = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $lineasPasteurizadora = $lineas->filter(function($linea) use ($pasteurizadorasPermitidas) {
            return in_array($linea->nombre, $pasteurizadorasPermitidas);
        })->values();

        // Tipo seleccionado (por defecto lavadora)
        $tipoSeleccionado = $request->input('tipo', 'lavadora') === 'pasteurizadora'
            ? 'pasteurizadora'
            : 'lavadora';

        if ($tipoSeleccionado === 'pasteurizadora' && !auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)) {
            return redirect()
                ->route('dashboard')
                ->with('pasteurizadora_bloqueada', 'No tienes permiso para acceder al modulo de Pasteurizadora.');
        }

        // Línea seleccionada
        $lineaSeleccionadaId = $request->input('linea_id');
        $lineaSeleccionada = null;

        if ($lineaSeleccionadaId) {
            $lineaSeleccionada = $lineas->firstWhere('id', $lineaSeleccionadaId);
        } elseif ($tipoSeleccionado == 'lavadora' && $lineasLavadora->isNotEmpty()) {
            $lineaSeleccionada = $lineasLavadora->first();
        } elseif ($tipoSeleccionado == 'pasteurizadora' && $lineasPasteurizadora->isNotEmpty()) {
            $lineaSeleccionada = $lineasPasteurizadora->first();
        }

        if (
            $lineaSeleccionada
            && str_starts_with($lineaSeleccionada->nombre, 'P-')
            && !auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)
        ) {
            return redirect()
                ->route('dashboard')
                ->with('pasteurizadora_bloqueada', 'No tienes permiso para acceder al modulo de Pasteurizadora.');
        }

        // Obtener estadísticas según el tipo
        $estadisticas = [];
        $resumen = [
            'total_general' => 0,
            'revisado_general' => 0,
            'porcentaje_general' => 0
        ];

        if ($lineaSeleccionada) {
            if ($tipoSeleccionado == 'lavadora') {
                $estadisticas = $this->getEstadisticasLavadora($lineaSeleccionada);
            } else {
                $estadisticas = $this->getEstadisticasPasteurizadora($lineaSeleccionada);
            }

            // Calcular resumen
            foreach ($estadisticas as $data) {
                $resumen['total_general'] += $data['cantidad_total'];
                $resumen['revisado_general'] += $data['cantidad_revisada'];
            }

            $resumen['porcentaje_general'] = $resumen['total_general'] > 0
                ? round(($resumen['revisado_general'] / $resumen['total_general']) * 100, 1)
                : 0;
        }

        return view('historico-revisados.lavadora.index', compact(
            'lineas',
            'lineasLavadora',
            'lineasPasteurizadora',
            'tipoSeleccionado',
            'lineaSeleccionada',
            'estadisticas',
            'resumen'
        ));
    }
    
    /**
     * Obtener estadísticas para lavadora considerando la periodicidad
     */
    private function getEstadisticasLavadora($linea)
    {
        return $this->periodicityService->estadisticasLinea($linea);
    }

    /**
     * Obtener estadisticas para pasteurizadora desde la tabla historico_revisados
     */
    private function getEstadisticasPasteurizadora($linea)
    {
        $estadisticas = [];

        // Obtener todos los registros de esta línea
        $registros = HistoricoRevisados::forArea(AnalisisPasteurizadora::AREA_MECANICA)
            ->where('linea_id', $linea->id)
            ->get();

        foreach ($registros as $registro) {
            $estadisticas[$registro->componente] = [
                'nombre' => $registro->componente_nombre,
                'codigo' => $registro->componente,
                'cantidad_total' => $registro->cantidad_total,
                'cantidad_revisada' => $registro->cantidad_revisada,
                'porcentaje' => $registro->porcentaje,
                'color' => $registro->color_estado,
                'reductores_detectados' => $registro->cantidad_revisada,
                'ultima_revision' => $registro->ultima_revision_formateada,
                'proximo_vencimiento' => $registro->proximo_vencimiento_formateado,
            ];
        }

        return $estadisticas;
    }
    
    /**
     * Determinar color según porcentaje
     */
    private function getColorPorcentaje($porcentaje)
    {
        if ($porcentaje >= 80) {
            return 'success';
        } elseif ($porcentaje >= 50) {
            return 'info';
        } elseif ($porcentaje >= 20) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Forzar restablecimiento de estadísticas
     */
    public function resetEstadisticas(Request $request)
    {
        try {
            // Verificar permisos (ajusta según tu sistema)
            if (!auth()->user()->hasAnyRole(User::elevatedMaintenanceRoles())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }
            
            // Ejecutar el comando
            $exitCode = \Artisan::call('componentes:reset-estadisticas');
            $output = \Artisan::output();
            
            if ($exitCode === 0) {
                // Log de la acción
                Log::info('Reset de estadísticas realizado por: ' . auth()->user()->name);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Estadísticas restablecidas correctamente',
                    'output' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al restablecer estadísticas',
                    'output' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error en reset de estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado de restablecimiento programado
     */
    public function checkResetStatus(Request $request)
    {
        $linea = null;

        if ($request->filled('linea_id')) {
            $linea = Linea::find($request->integer('linea_id'));
        }

        $componentes = $this->periodicityService->estadosComponentes($linea);
        $ultimoReset = $componentes
            ->pluck('ultimo_reset')
            ->filter()
            ->sortDesc()
            ->first();

        $statsRestablecimientos = [
            'total_restablecidos' => HistorialRestablecimiento::count(),
            'ultimos_30_dias' => HistorialRestablecimiento::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            'por_componente' => HistorialRestablecimiento::select('componente_id', DB::raw('count(*) as total'))
                ->with('componente')
                ->groupBy('componente_id')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'componente' => $item->componente ? $item->componente->nombre : 'N/A',
                        'total' => $item->total
                    ];
                })
        ];

        return response()->json([
            'success' => true,
            'ultimo_reset' => $ultimoReset,
            'componentes' => $componentes,
            'resumen' => [
                'total_componentes' => $componentes->count(),
                'pendientes' => $componentes->where('estado', 'pendiente')->count(),
                'programados' => $componentes->where('estado', 'programado')->count(),
                'restablecidos' => $componentes->where('estado', 'restablecido')->count(),
                'sin_revision' => $componentes->where('estado', 'sin_revision')->count(),
                'ultimo_reset' => $ultimoReset,
            ],
            'estadisticas' => $statsRestablecimientos,
        ]);
    }

    /**
     * Obtener color según días restantes
     */
    private function getColorDiasRestantes($dias)
    {
        if ($dias <= 0) return 'danger';
        if ($dias <= 7) return 'warning';
        if ($dias <= 15) return 'info';
        return 'success';
    }
    
}
