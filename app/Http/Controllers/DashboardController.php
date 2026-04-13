<?php

namespace App\Http\Controllers;

use App\Models\AnalisisLavadora;
use App\Models\Linea;
use App\Models\Elongacion;
use App\Models\PlanAccion;
use App\Models\AnalisisTendenciaMensualLavadora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal con todos los módulos integrados.
     */
    public function index(Request $request)
    {
        // 1. Obtener todas las líneas de lavadora activas
        $lineasLavadora = Linea::where('activo', true)
            ->whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->orderBy('nombre')
            ->get();

        // 2. Datos para las tarjetas de resumen
        $resumenGeneral = $this->getResumenGeneral($lineasLavadora);

        // 3. Datos para la tabla de estado de lavadoras y alertas
        $estadoLavadoras = $this->getEstadoLavadoras($lineasLavadora);

        // 4. Datos para el ranking de lavadoras con mayor daño (predictivo)
        $rankingDanos = $this->getRankingDanos($lineasLavadora);

        // 5. Datos para la gráfica de fallas por línea (últimos 12 meses)
        $fallasPorLinea = $this->getFallasPorLinea($lineasLavadora);

        // 6. Datos para la gráfica de componentes más dañados
        $componentesDanados = $this->getComponentesDanados($lineasLavadora);

        // 7. Datos para la gráfica de evolución de elongaciones
        $evolucionElongaciones = $this->getEvolucionElongaciones($lineasLavadora);

        // 8. Datos para el histórico de revisiones (conteo de análisis por componente)
        $historicoRevisiones = $this->getHistoricoRevisiones($lineasLavadora);

        // 9. Datos para el análisis 52-12-4 (últimos registros)
        $analisis52124 = $this->getAnalisis52124($lineasLavadora);
        
        return view('dashboard', compact(
            'lineasLavadora',
            'resumenGeneral',
            'estadoLavadoras',
            'rankingDanos',
            'fallasPorLinea',
            'componentesDanados',
            'evolucionElongaciones',
            'historicoRevisiones',
            'analisis52124'
        ));
    }

    public function lavadora()
    {
        return view('lavadora.dashboard-lavadora'); 
    }

    /**
     * Obtiene el resumen general de todas las lavadoras.
     */
    private function getResumenGeneral($lineasLavadora)
    {
        $totalLavadoras = $lineasLavadora->count();
        $totalAnalisis = AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))->count();
        $totalAlertasCriticas = 0;
        $totalEnRiesgo = 0;
        $totalBuenEstado = 0;
        $totalPendientesAccion = PlanAccion::whereIn('linea_id', $lineasLavadora->pluck('id'))->where('completado', false)->count();

        // Calcular estado de cada lavadora para los resúmenes
        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            if ($estado['nivel'] === 'critico') {
                $totalAlertasCriticas++;
            } elseif ($estado['nivel'] === 'riesgo') {
                $totalEnRiesgo++;
            } else {
                $totalBuenEstado++;
            }
        }

        return [
            'total_lavadoras' => $totalLavadoras,
            'total_analisis' => $totalAnalisis,
            'alertas_criticas' => $totalAlertasCriticas,
            'en_riesgo' => $totalEnRiesgo,
            'buen_estado' => $totalBuenEstado,
            'pendientes_accion' => $totalPendientesAccion,
        ];
    }

    /**
     * Calcula el estado de una lavadora específica.
     */
    private function calcularEstadoLavadora($lineaId)
    {
        // 1. Obtener el último análisis de elongación
        $ultimaElongacion = Elongacion::where('linea', function($query) use ($lineaId) {
                $query->select('nombre')->from('lineas')->where('id', $lineaId);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        // 2. Obtener los últimos análisis de componentes (daños críticos)
        $analisisCriticos = AnalisisLavadora::where('linea_id', $lineaId)
    ->where('estado', 'Dañado - Requiere cambio')
    ->with('componente')
    ->orderBy('fecha_analisis', 'desc')
    ->limit(5)
    ->get()
    ->map(function($a) {

        if ($a->componente) {

            // 🔥 LIMPIAR CODIGO
            $codigo = $a->componente->codigo;

            // Quitar prefijos tipo L05_reductor_1_
            $codigo = preg_replace('/^L\d+_reductor_\d+_/', '', $codigo);

            // Opcional: limpiar más
            $codigo = strtoupper(trim($codigo));

            $a->componente->icono = asset("images/componentes-lavadora/{$codigo}.png");
        }

        return $a;
    });

        // 3. Obtener actividades del plan de acción pendientes
        $accionesPendientes = PlanAccion::where('linea_id', $lineaId)
            ->where('completado', false)
            ->count();

        // 4. Determinar nivel de riesgo
        $nivel = 'bueno';
        $color = 'green';
        $mensaje = 'Funcionando correctamente';

        if ($analisisCriticos->count() > 0) {
            $nivel = 'critico';
            $color = 'red';
            $mensaje = 'Presenta componentes dañados que requieren cambio inmediato.';
        } elseif ($accionesPendientes > 0) {
            $nivel = 'riesgo';
            $color = 'yellow';
            $mensaje = "Tiene {$accionesPendientes} acción(es) pendiente(s) en el plan de acción.";
        } elseif ($ultimaElongacion && ($ultimaElongacion->vapor_porcentaje >= 1.46 || $ultimaElongacion->bombas_porcentaje >= 1.46)) {
            $nivel = 'critico';
            $color = 'red';
            $mensaje = 'Elongación crítica (>1.46%), cambio de cadena requerido.';
        } elseif ($ultimaElongacion && ($ultimaElongacion->vapor_porcentaje >= 1.3 || $ultimaElongacion->bombas_porcentaje >= 1.3)) {
            $nivel = 'riesgo';
            $color = 'yellow';
            $mensaje = 'Elongación en nivel de compra (>1.3%), considerar cambio de cadena.';
        } else {
            // Verificar si hay componentes con desgaste
            $analisisDesgaste = AnalisisLavadora::where('linea_id', $lineaId)
                ->where('estado', 'like', '%Desgaste%')
                ->count();
            if ($analisisDesgaste > 0) {
                $nivel = 'riesgo';
                $color = 'yellow';
                $mensaje = 'Presenta componentes con desgaste, programar mantenimiento.';
            }
        }

        return [
            'nivel' => $nivel,
            'color' => $color,
            'mensaje' => $mensaje,
            'analisis_criticos' => $analisisCriticos,
            'ultima_elongacion' => $ultimaElongacion,
            'acciones_pendientes' => $accionesPendientes,
        ];
    }

    /**
     * Obtiene el estado detallado de todas las lavadoras para la tabla.
     */
    private function getEstadoLavadoras($lineasLavadora)
    {
        $estadoLavadoras = [];
        foreach ($lineasLavadora as $linea) {
            $estado = $this->calcularEstadoLavadora($linea->id);
            $estadoLavadoras[] = [
                'id' => $linea->id,
                'nombre' => $linea->nombre,
                'estado' => $estado,
            ];
        }
        return $estadoLavadoras;
    }

    /**
     * Obtiene el ranking de lavadoras con mayor nivel de daño.
     */
    private function getRankingDanos($lineasLavadora)
    {
        $ranking = [];
        foreach ($lineasLavadora as $linea) {
            // Calcular un puntaje de daño
            $puntajeDanio = 0;
            $analisisCriticos = AnalisisLavadora::where('linea_id', $linea->id)
                ->where('estado', 'Dañado - Requiere cambio')
                ->count();
            $puntajeDanio += $analisisCriticos * 10;

            $analisisDesgaste = AnalisisLavadora::where('linea_id', $linea->id)
                ->where('estado', 'like', '%Desgaste%')
                ->count();
            $puntajeDanio += $analisisDesgaste * 5;

            // Obtener la última elongación
            $ultimaElongacion = Elongacion::where('linea', function($query) use ($linea) {
                $query->select('nombre')->from('lineas')->where('id', $linea->id);
            })->orderBy('created_at', 'desc')->first();

            if ($ultimaElongacion) {
                $puntajeDanio += max($ultimaElongacion->bombas_porcentaje, $ultimaElongacion->vapor_porcentaje) * 2;
            }

            $ranking[] = [
                'linea' => $linea->nombre,
                'puntaje' => round($puntajeDanio, 2),
                'analisis_criticos' => $analisisCriticos,
                'analisis_desgaste' => $analisisDesgaste,
                'ultima_elongacion' => $ultimaElongacion,
            ];
        }

        // Ordenar por puntaje descendente y tomar los primeros 5
        usort($ranking, function($a, $b) {
            return $b['puntaje'] <=> $a['puntaje'];
        });

        return array_slice($ranking, 0, 5);
    }

    /**
     * Obtiene datos de fallas por línea para la gráfica de barras (últimos 12 meses).
     */
    private function getFallasPorLinea($lineasLavadora)
    {
        $fechaLimite = Carbon::now()->subMonths(12);
        $fallas = [];

        foreach ($lineasLavadora as $linea) {
            $totalFallas = AnalisisLavadora::where('linea_id', $linea->id)
                ->where('fecha_analisis', '>=', $fechaLimite)
                ->whereIn('estado', ['Dañado - Requiere cambio', 'Desgaste severo'])
                ->count();

            $fallas[] = [
                'linea' => $linea->nombre,
                'total_fallas' => $totalFallas,
            ];
        }

        // Ordenar por total de fallas descendente
        usort($fallas, function($a, $b) {
            return $b['total_fallas'] <=> $a['total_fallas'];
        });

        return $fallas;
    }

    /**
     * Obtiene los componentes más dañados para la gráfica de pastel.
     */
    private function getComponentesDanados($lineasLavadora)
    {
        $componentes = AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->where('estado', 'Dañado - Requiere cambio')
            ->with('componente')
            ->get();

        $conteoComponentes = [];
        foreach ($componentes as $analisis) {
            $nombreComponente = $analisis->componente ? $analisis->componente->nombre : 'Desconocido';
            if (!isset($conteoComponentes[$nombreComponente])) {
                $conteoComponentes[$nombreComponente] = 0;
            }
            $conteoComponentes[$nombreComponente]++;
        }

        $resultado = [];
        foreach ($conteoComponentes as $nombre => $total) {
            $resultado[] = [
                'componente' => $nombre,
                'total_danios' => $total,
            ];
        }

        // Ordenar por total de daños descendente
        usort($resultado, function($a, $b) {
            return $b['total_danios'] <=> $a['total_danios'];
        });

        return array_slice($resultado, 0, 5); // Top 5 componentes
    }

    /**
     * Obtiene la evolución de elongaciones para la gráfica de líneas.
     */
    private function getEvolucionElongaciones($lineasLavadora)
    {
        $fechaLimite = Carbon::now()->subMonths(6);
        $lineasNombres = $lineasLavadora->pluck('nombre')->toArray();

        $elongaciones = Elongacion::whereIn('linea', $lineasNombres)
            ->where('created_at', '>=', $fechaLimite)
            ->orderBy('created_at', 'asc')
            ->get();

        $datos = [];
        foreach ($elongaciones as $elongacion) {
            $fecha = $elongacion->created_at->format('Y-m-d');
            if (!isset($datos[$fecha])) {
                $datos[$fecha] = [
                    'fecha' => $fecha,
                    'bombas' => 0,
                    'vapor' => 0,
                    'conteo' => 0,
                ];
            }
            $datos[$fecha]['bombas'] += $elongacion->bombas_porcentaje;
            $datos[$fecha]['vapor'] += $elongacion->vapor_porcentaje;
            $datos[$fecha]['conteo']++;
        }

        // Calcular promedios
        $evolucion = [];
        foreach ($datos as $fecha => $data) {
            $evolucion[] = [
                'fecha' => $fecha,
                'bombas' => round($data['bombas'] / $data['conteo'], 2),
                'vapor' => round($data['vapor'] / $data['conteo'], 2),
            ];
        }

        return $evolucion;
    }

    /**
     * Obtiene el histórico de revisiones (conteo de análisis por componente).
     */
    private function getHistoricoRevisiones($lineasLavadora)
    {
        $analisis = AnalisisLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->with('componente')
            ->get();

        $conteoComponentes = [];
        foreach ($analisis as $item) {
            $nombreComponente = $item->componente ? $item->componente->nombre : 'Desconocido';
            if (!isset($conteoComponentes[$nombreComponente])) {
                $conteoComponentes[$nombreComponente] = 0;
            }
            $conteoComponentes[$nombreComponente]++;
        }

        $resultado = [];
        foreach ($conteoComponentes as $nombre => $total) {
            $resultado[] = [
                'componente' => $nombre,
                'total_analisis' => $total,
            ];
        }

        // Ordenar por total de análisis descendente
        usort($resultado, function($a, $b) {
            return $b['total_analisis'] <=> $a['total_analisis'];
        });

        return $resultado;
    }

    /**
     * Obtiene los últimos registros del análisis 52-12-4.
     */
    private function getAnalisis52124($lineasLavadora)
    {
        return AnalisisTendenciaMensualLavadora::whereIn('linea_id', $lineasLavadora->pluck('id'))
            ->with('linea')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $item->periodo = Carbon::create($item->anio, $item->mes, 1)->format('M Y');
                return $item;
            });
    }

    /**
     * API para obtener datos de tendencia de daños (para gráficas dinámicas).
     */
    public function getDanosTendenciaApi(Request $request)
    {
        $lineas = Linea::where('activo', true)
            ->whereIn('nombre', ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'])
            ->pluck('id');

        $datos = AnalisisTendenciaMensualLavadora::whereIn('linea_id', $lineas)
            ->with('linea')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get()
            ->groupBy('linea.nombre');

        $resultado = [];
        foreach ($datos as $lineaNombre => $registros) {
            $resultado[$lineaNombre] = $registros->map(function($item) {
                return [
                    'periodo' => Carbon::create($item->anio, $item->mes, 1)->format('Y-m'),
                    'total_danos_4_semanas' => $item->total_danos_4_semanas,
                    'total_danos_12_semanas' => $item->total_danos_12_semanas,
                    'total_danos_52_semanas' => $item->total_danos_52_semanas,
                ];
            });
        }

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
}