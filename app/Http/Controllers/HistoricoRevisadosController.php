<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\HistorialRestablecimiento;
use App\Models\HistoricoRevisados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
cli_set_process_titlecls
class HistoricoRevisadosController extends Controller
{
    /**
     * Configuración de componentes por línea de lavadora
     */
    private $componentesLavadora = [
        'L-04' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-05' => [
            'RV200' => 'Reductor RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-06' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-07' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-08' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-09' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-12' => [
            'RV200_SIN_FIN' => 'Reductor Sin Fin-Corona RV200',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guía Inferior',
            'GUI_INT_TANQUE' => 'Guía Intermedia',
            'GUI_SUP_TANQUE' => 'Guía Superior',
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
        'L-12' => 13,
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
        $tipoSeleccionado = $request->input('tipo', 'lavadora');

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
        $estadisticas = [];
        
        if (!isset($this->componentesLavadora[$linea->nombre])) {
            return $estadisticas;
        }
        
        $componentesLinea = $this->componentesLavadora[$linea->nombre];
        
        // Obtener la cantidad total para esta línea específica
        $cantidadTotalLinea = $this->cantidadesPorLinea[$linea->nombre] ?? 15; // Por defecto 15 si no está definida
        
        // Configuración de periodicidad (en meses)
        $periodicidad = [
            'CATARINAS' => 4,
            'GUI_INF_TANQUE' => 4,
            'GUI_INT_TANQUE' => 4,
            'GUI_SUP_TANQUE' => 4,
            'SERVO_CHICO' => 12,
            'SERVO_GRANDE' => 12,
            'BUJE_ESPIGA' => 12,
            'RV200' => 12,
            'RV200_SIN_FIN' => 12,
        ];
        
        $fechaActual = Carbon::now();
        
        foreach ($componentesLinea as $codigo => $nombre) {
            // Usar la cantidad total de la línea para todos los componentes
            $cantidadTotal = $cantidadTotalLinea;
            $mesesPeriodo = $periodicidad[$codigo] ?? 12; // Por defecto anual
            
            // Fecha límite para considerar análisis vigentes
            $fechaLimite = $fechaActual->copy()->subMonths($mesesPeriodo);
            
            // Obtener IDs de componentes que coincidan con el código
            $componenteIds = Componente::where('codigo', 'like', '%' . $codigo . '%')
                ->where('activo', true)
                ->pluck('id')
                ->toArray();
            
            // Calcular cantidad revisada solo de análisis en el periodo vigente
            // y que no hayan sido restablecidos
            $revisados = AnalisisLavadora::where('linea_id', $linea->id)
                ->whereIn('componente_id', $componenteIds)
                ->where('created_at', '>=', $fechaLimite)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('historial_restablecimientos')
                          ->whereRaw('analisis_id = analisis_componentes.id');
                })
                ->distinct('reductor')
                ->count('reductor');
            
            $revisados = min($revisados, $cantidadTotal);
            $porcentaje = $cantidadTotal > 0 ? round(($revisados / $cantidadTotal) * 100, 1) : 0;
            $color = $this->getColorPorcentaje($porcentaje);
            
            // Calcular próximos vencimientos
            $proximoVencimiento = $this->calcularProximoVencimiento($linea->id, $componenteIds, $mesesPeriodo);
            
            $estadisticas[$codigo] = [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'cantidad_total' => $cantidadTotal,
                'cantidad_revisada' => $revisados,
                'porcentaje' => $porcentaje,
                'color' => $color,
                'reductores_detectados' => $revisados,
                'periodo_meses' => $mesesPeriodo,
                'fecha_inicio_periodo' => $fechaLimite->format('Y-m-d'),
                'fecha_fin_periodo' => $fechaActual->format('Y-m-d'),
                'proximo_vencimiento' => $proximoVencimiento,
            ];
        }
        
        return $estadisticas;
    }

    /**
     * Calcular próximo vencimiento para un componente
     */
    private function calcularProximoVencimiento($lineaId, $componenteIds, $periodoMeses)
    {
        $ultimoAnalisis = AnalisisLavadora::where('linea_id', $lineaId)
            ->whereIn('componente_id', $componenteIds)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('historial_restablecimientos')
                      ->whereRaw('analisis_id = analisis_componentes.id');
            })
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($ultimoAnalisis) {
            return Carbon::parse($ultimoAnalisis->created_at)
                ->addMonths($periodoMeses)
                ->format('Y-m-d');
        }
        
        return null;
    }
    
    /**
     * Obtener estadísticas para pasteurizadora desde la tabla histórico_revisados
     */
    private function getEstadisticasPasteurizadora($linea)
    {
        $estadisticas = [];

        // Obtener todos los registros de esta línea
        $registros = HistoricoRevisados::where('linea_id', $linea->id)->get();

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
            if (!auth()->user()->hasRole(['admin', 'ingeniero_mantenimiento'])) {
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
    public function checkResetStatus()
    {
        $ultimoReset = DB::table('configuraciones')
            ->where('clave', 'ultimo_reset_estadisticas')
            ->first();
        
        $proximosResets = [];
        $fechaActual = Carbon::now();
        
        // Calcular próximos resets para cada periodicidad
        $periodicidades = [
            '4_meses' => 4,
            'anual' => 12
        ];
        
        foreach ($periodicidades as $nombre => $meses) {
            $ultimoResetPeriodo = $ultimoReset 
                ? Carbon::parse($ultimoReset->valor) 
                : $fechaActual->copy()->subMonths($meses);
            
            $proximoReset = $ultimoResetPeriodo->copy()->addMonths($meses);
            
            $diasRestantes = $fechaActual->diffInDays($proximoReset, false);
            
            $proximosResets[$nombre] = [
                'fecha' => $proximoReset->format('d/m/Y'),
                'dias_restantes' => $diasRestantes,
                'estado' => $diasRestantes <= 0 ? 'pendiente' : 'programado',
                'color' => $this->getColorDiasRestantes($diasRestantes)
            ];
        }
        
        // Estadísticas de restablecimientos
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
            'ultimo_reset' => $ultimoReset ? Carbon::parse($ultimoReset->valor)->format('d/m/Y H:i:s') : null,
            'proximos_resets' => $proximosResets,
            'estadisticas' => $statsRestablecimientos
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