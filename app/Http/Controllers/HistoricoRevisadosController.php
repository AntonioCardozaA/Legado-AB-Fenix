<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\AnalisisLavadora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * Obtener estadísticas para lavadora usando la configuración proporcionada
     */
 /**
 * Obtener estadísticas para lavadora usando la configuración proporcionada
 */
private function getEstadisticasLavadora($linea)
{
    $estadisticas = [];
    
    // Verificar si la línea existe en la configuración
    if (!isset($this->componentesLavadora[$linea->nombre])) {
        return $estadisticas;
    }
    
    $componentesLinea = $this->componentesLavadora[$linea->nombre];
    
    // Crear un array vacío con el orden exacto
    $estadisticasOrdenadas = [];
    
    foreach ($componentesLinea as $codigo => $nombre) {
        // Obtener cantidad total para este componente
        $cantidadTotal = $this->cantidadesTotales[$codigo] ?? 15;
        
        // Calcular cantidad revisada (distintos reductores con análisis)
        $revisados = AnalisisLavadora::where('linea_id', $linea->id)
            ->whereHas('componente', function($q) use ($codigo) {
                $q->where('codigo', 'like', '%' . $codigo . '%');
            })
            ->distinct('reductor')
            ->count('reductor');
        
        // Limitar al máximo posible
        $revisados = min($revisados, $cantidadTotal);
        $porcentaje = $cantidadTotal > 0 ? round(($revisados / $cantidadTotal) * 100, 1) : 0;
        
        // Determinar color según porcentaje
        $color = $this->getColorPorcentaje($porcentaje);
        
        // Asignar al array manteniendo el orden original
        $estadisticasOrdenadas[$codigo] = [
            'nombre' => $nombre,
            'codigo' => $codigo,
            'cantidad_total' => $cantidadTotal,
            'cantidad_revisada' => $revisados,
            'porcentaje' => $porcentaje,
            'color' => $color,
            'reductores_detectados' => $revisados
        ];
    }
    
    return $estadisticasOrdenadas;
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
}