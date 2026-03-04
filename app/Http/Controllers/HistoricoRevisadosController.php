<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\HistorialRestablecimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HistoricoRevisadosController extends Controller
{
    /**
     * Configuración de componentes por línea de lavadora (exactamente como lo proporcionaste)
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
     * Configuración de cantidades totales por componente
     */
    private $cantidadesTotales = [
        'SERVO_CHICO' => 15,
        'SERVO_GRANDE' => 15,
        'BUJE_ESPIGA' => 15,
        'GUI_INF_TANQUE' => 15,
        'GUI_INT_TANQUE' => 15,
        'GUI_SUP_TANQUE' => 15,
        'CATARINAS' => 15,
        'RV200' => 15,
        'RV200_SIN_FIN' => 15,
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
        
        // Para pasteurizadora, mostrar TODAS las líneas (asumimos que todas tienen pasteurizadora)
        $lineasPasteurizadora = $lineas->values();
        
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
        
        return view('historico-revisados.index', compact(
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
            $cantidadTotal = $this->cantidadesTotales[$codigo] ?? 15;
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
     * Obtener estadísticas para pasteurizadora
     * (Aquí debes poner los componentes reales de pasteurizadora)
     */
    private function getEstadisticasPasteurizadora($linea)
    {
        // Por ahora, datos de ejemplo - reemplazar con configuración real de pasteurizadora
        $componentesPasteurizadora = [
            'Anillas / Pernos de ojo' => 'Anillas / Pernos de ojo',
            'BOMBA_CIRCULACION' => 'Bomba de Circulación',
            'SENSOR_TEMPERATURA' => 'Sensor de Temperatura',
            'VALVULA_CONTROL' => 'Válvula de Control',
            'INTERCAMBIADOR' => 'Intercambiador de Calor',
            'TERMOMETRO' => 'Termómetro',
            'MANOMETRO' => 'Manómetro',
            'FILTRO' => 'Filtro',
        ];
        
        $estadisticas = [];
        
        foreach ($componentesPasteurizadora as $codigo => $nombre) {
            $cantidadTotal = 8; // Ajustar según necesidad
            $revisados = rand(0, $cantidadTotal); // Simulado - reemplazar con consulta real
            $porcentaje = round(($revisados / $cantidadTotal) * 100, 1);
            $color = $this->getColorPorcentaje($porcentaje);
            
            $estadisticas[$codigo] = [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'cantidad_total' => $cantidadTotal,
                'cantidad_revisada' => $revisados,
                'porcentaje' => $porcentaje,
                'color' => $color,
                'reductores_detectados' => $revisados
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