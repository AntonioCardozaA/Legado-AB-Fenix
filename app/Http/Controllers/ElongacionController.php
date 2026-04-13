<?php

namespace App\Http\Controllers;

use App\Models\Elongacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

class ElongacionController extends Controller
{
    // Definir constantes de límites
    const LIMITE_COMPRA = 1.3;    // Límite para considerar compra
    const LIMITE_CAMBIO = 1.46;   // Límite para cambio de cadena (ROJO)

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Elongacion::query();
        
        // Filtrar por línea
        if ($request->has('linea') && !empty($request->linea)) {
            $query->porLinea($request->linea);
        }
        
        // Filtrar por estado
        if ($request->has('estado') && !empty($request->estado)) {
            $query->porEstado($request->estado);
        }
        
        // Ordenar por fecha descendente y paginar
        $elongaciones = $query->orderBy('created_at', 'desc')
                              ->paginate(15)
                              ->withQueryString();
        
        return view('elongaciones.index', compact('elongaciones'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $lineaSeleccionada = $request->get('linea', 'L-07');
        
        // Obtener última lectura para la línea seleccionada
        $ultimaLectura = Elongacion::where('linea', $lineaSeleccionada)
                                   ->orderBy('created_at', 'desc')
                                   ->first();
        
        return view('elongaciones.create', compact('lineaSeleccionada', 'ultimaLectura'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validación
            $request->validate([
                'linea' => 'required|in:L-04,L-05,L-06,L-07,L-08,L-09,L-12,L-13',
                'hodometro' => 'nullable|integer|min:0',
                'bombas_1' => 'nullable|numeric|min:0|max:200',
                'bombas_2' => 'nullable|numeric|min:0|max:200',
                'bombas_3' => 'nullable|numeric|min:0|max:200',
                'bombas_4' => 'nullable|numeric|min:0|max:200',
                'bombas_5' => 'nullable|numeric|min:0|max:200',
                'bombas_6' => 'nullable|numeric|min:0|max:200',
                'bombas_7' => 'nullable|numeric|min:0|max:200',
                'bombas_8' => 'nullable|numeric|min:0|max:200',
                'bombas_9' => 'nullable|numeric|min:0|max:200',
                'bombas_10' => 'nullable|numeric|min:0|max:200',
                'vapor_1' => 'nullable|numeric|min:0|max:200',
                'vapor_2' => 'nullable|numeric|min:0|max:200',
                'vapor_3' => 'nullable|numeric|min:0|max:200',
                'vapor_4' => 'nullable|numeric|min:0|max:200',
                'vapor_5' => 'nullable|numeric|min:0|max:200',
                'vapor_6' => 'nullable|numeric|min:0|max:200',
                'vapor_7' => 'nullable|numeric|min:0|max:200',
                'vapor_8' => 'nullable|numeric|min:0|max:200',
                'vapor_9' => 'nullable|numeric|min:0|max:200',
                'vapor_10' => 'nullable|numeric|min:0|max:200',
                'juego_rodaja_bombas' => 'nullable|numeric|min:0',
                'juego_rodaja_vapor' => 'nullable|numeric|min:0',
            ]);

            // Definir pasos iniciales por línea
            $pasosIniciales = [
                'L-04' => 173,
                'L-05' => 140,
                'L-06' => 173,
                'L-07' => 173,
                'L-08' => 125,
                'L-09' => 140,
                'L-12' => 140,
                'L-13' => 140,
            ];
            
            $pasoInicial = $pasosIniciales[$request->linea] ?? 173;

            // Obtener mediciones lado bombas
            $bombasMediciones = [];
            for ($i = 1; $i <= 10; $i++) {
                $bombasMediciones[] = $request->input("bombas_{$i}");
            }
            
            // Calcular promedio lado bombas
            $bombasPromedio = Elongacion::calcularPromedio($bombasMediciones);
            $bombasPorcentaje = Elongacion::calcularPorcentaje($bombasPromedio, $pasoInicial);
            
            // Obtener mediciones lado vapor
            $vaporMediciones = [];
            for ($i = 1; $i <= 10; $i++) {
                $vaporMediciones[] = $request->input("vapor_{$i}");
            }
            
            // Calcular promedio lado vapor
            $vaporPromedio = Elongacion::calcularPromedio($vaporMediciones);
            $vaporPorcentaje = Elongacion::calcularPorcentaje($vaporPromedio, $pasoInicial);

            // LÍMITES DEFINIDOS: Compra 1.3% - Cambio 1.46%
            $limiteCompra = self::LIMITE_COMPRA;
            $limiteCambio = self::LIMITE_CAMBIO;

            // Determinar estado basado en los límites
            $estado = 'normal';
            $estadoDetallado = 'normal';
            
            // Evaluar lado bombas (el más crítico prevalece)
            if ($bombasPorcentaje >= $limiteCambio) {
                $estado = 'critico';
                $estadoDetallado = 'cambio';
            } elseif ($bombasPorcentaje >= $limiteCompra) {
                if ($estado != 'critico') $estado = 'alerta';
                $estadoDetallado = 'comprar';
            }
            
            // Evaluar lado vapor
            if ($vaporPorcentaje >= $limiteCambio) {
                $estado = 'critico';
                $estadoDetallado = 'cambio';
            } elseif ($vaporPorcentaje >= $limiteCompra) {
                if ($estado != 'critico') $estado = 'alerta';
                $estadoDetallado = 'comprar';
            }

            // Preparar datos para crear
            $data = [
                'linea' => $request->linea,
                'seccion' => 'LAVADORA',
                'hodometro' => $request->hodometro,
                'juego_rodaja_bombas' => $request->juego_rodaja_bombas,
                'juego_rodaja_vapor' => $request->juego_rodaja_vapor,
                'bombas_promedio' => $bombasPromedio,
                'bombas_porcentaje' => $bombasPorcentaje,
                'vapor_promedio' => $vaporPromedio,
                'vapor_porcentaje' => $vaporPorcentaje,
                'estado' => $estado,
                'estado_detallado' => $estadoDetallado,
                'paso_inicial' => $pasoInicial,
            ];

            // Agregar mediciones individuales
            for ($i = 1; $i <= 10; $i++) {
                $data["bombas_{$i}"] = $request->input("bombas_{$i}");
                $data["vapor_{$i}"] = $request->input("vapor_{$i}");
            }

            // Crear registro
            $elongacion = Elongacion::create($data);
            
            // 🚨 NOTIFICACIÓN WHATSAPP POR LÍMITES DE ELONGACIÓN
            $this->enviarNotificacionWhatsApp($request, $bombasPorcentaje, $vaporPorcentaje);

            // Mensaje de éxito
            $mensaje = 'Registro guardado exitosamente';
            
            if ($bombasPorcentaje >= $limiteCambio || $vaporPorcentaje >= $limiteCambio) {
                $mensaje .= ' - 🚨 ¡ATENCIÓN! LÍMITE DE CAMBIO ALCANZADO (' . $limiteCambio . '%) - CAMBIO REQUERIDO';
            } elseif ($bombasPorcentaje >= $limiteCompra || $vaporPorcentaje >= $limiteCompra) {
                $mensaje .= ' - ⚠️ NOTA: Un lado superó ' . $limiteCompra . '%, considerar compra de cadena';
            }

            return redirect()->route('elongaciones.index', ['linea' => $request->linea])
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            Log::error('Error al guardar elongación: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error al guardar el registro: ' . $e->getMessage());
        }
    }

    /**
     * Enviar notificación por WhatsApp
     */
    private function enviarNotificacionWhatsApp($request, $bombasPorcentaje, $vaporPorcentaje)
    {
        $limiteCompra = self::LIMITE_COMPRA;
        $limiteCambio = self::LIMITE_CAMBIO;
        
        $numero = "5214981096696"; // tu número o grupo
        $mensaje = null;

        // Detectar lados afectados
        $lados = [];

        if ($bombasPorcentaje >= $limiteCompra) {
            $lados[] = "🔵 Bombas (" . round($bombasPorcentaje, 2) . "%)";
        }

        if ($vaporPorcentaje >= $limiteCompra) {
            $lados[] = "🟢 Vapor (" . round($vaporPorcentaje, 2) . "%)";
        }

        $ladosTexto = !empty($lados) ? implode(' y ', $lados) : 'Sin afectación';

        // 🔴 CRÍTICO (CAMBIO) - SUPERÓ 1.46%
        if ($bombasPorcentaje >= $limiteCambio || $vaporPorcentaje >= $limiteCambio) {
            $mensaje = "🔴 *ALERTA CRÍTICA - CAMBIO DE CADENA* 🔴\n\n"
                . "🏭 Línea: {$request->linea}\n"
                . "📍 Lado afectado: $ladosTexto\n\n"
                . "📊 Bombas: " . round($bombasPorcentaje, 2) . "%\n"
                . "🌫️ Vapor: " . round($vaporPorcentaje, 2) . "%\n\n"
                . "⚠️ Superó el límite de cambio: *{$limiteCambio}%*\n"
                . "🔧 *¡CAMBIO INMEDIATO REQUERIDO!*";
        }
        // 🟡 COMPRA - SUPERÓ 1.3% PERO NO 1.46%
        elseif ($bombasPorcentaje >= $limiteCompra || $vaporPorcentaje >= $limiteCompra) {
            $mensaje = "🟡 *ALERTA - CONSIDERAR COMPRA DE CADENA* 🟡\n\n"
                . "🏭 Línea: {$request->linea}\n"
                . "📍 Lado afectado: $ladosTexto\n\n"
                . "📊 Bombas: " . round($bombasPorcentaje, 2) . "%\n"
                . "🌫️ Vapor: " . round($vaporPorcentaje, 2) . "%\n\n"
                . "📦 Superó el límite de compra: *{$limiteCompra}%*\n"
                . "🛒 *CONSIDERAR COMPRA PARA PRÓXIMO CAMBIO DE CADENA*";
        }

        // Enviar si hay mensaje
        if ($mensaje && class_exists(WhatsAppService::class)) {
            try {
                WhatsAppService::enviarMensaje($numero, $mensaje);
                Log::info('WhatsApp elongación enviado', [
                    'linea' => $request->linea,
                    'bombas' => $bombasPorcentaje,
                    'vapor' => $vaporPorcentaje,
                ]);
            } catch (\Exception $e) {
                Log::error('Error WhatsApp elongación: ' . $e->getMessage());
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Elongacion $elongacion)
    {
        return view('elongaciones.show', compact('elongacion'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Elongacion $elongacion)
    {
        try {
            $linea = $elongacion->linea;
            $elongacion->delete();
            
            return redirect()->route('elongaciones.index', ['linea' => $linea])
                ->with('success', 'Registro eliminado exitosamente');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar elongación: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el registro');
        }
    }

    /**
     * API para obtener última lectura
     */
    public function ultimaLectura($linea)
    {
        try {
            $ultima = Elongacion::where('linea', $linea)
                               ->orderBy('created_at', 'desc')
                               ->first();
            
            if ($ultima) {
                return response()->json([
                    'success' => true,
                    'hodometro' => $ultima->hodometro,
                    'fecha' => $ultima->created_at->format('d/m/Y H:i')
                ]);
            }
            
            return response()->json([
                'success' => true,
                'hodometro' => null,
                'fecha' => null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar última lectura'
            ], 500);
        }
    }
}