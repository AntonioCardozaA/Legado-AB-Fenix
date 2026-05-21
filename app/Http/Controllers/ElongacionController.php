<?php

namespace App\Http\Controllers;

use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ElongacionController extends Controller
{
    const LIMITE_COMPRA = 1.3;
    const LIMITE_CAMBIO = 1.46;

    public function index(Request $request)
    {
        $query = Elongacion::with('cadenaCiclo');

        $hasLineaFilter = $request->filled('linea');
        $hasEstadoFilter = $request->filled('estado');
        $hasProveedorFilter = $request->filled('proveedor');
        $hasCicloFilter = $request->filled('cadena_ciclo_id');

        if ($hasLineaFilter) {
            $query->porLinea($request->linea);
        }

        if ($hasEstadoFilter) {
            $query->porEstado($request->estado);
        }

        if ($hasProveedorFilter) {
            $query->where('proveedor', 'like', '%' . $request->proveedor . '%');
        }

        if ($hasCicloFilter) {
            $query->where('cadena_ciclo_id', $request->cadena_ciclo_id);
        }

        if (!$hasLineaFilter && !$hasEstadoFilter && !$hasProveedorFilter && !$hasCicloFilter) {
            $ultimosIds = Elongacion::select(DB::raw('MAX(id) as id'))
                ->groupBy('linea')
                ->pluck('id');

            $query->whereIn('id', $ultimosIds);
        }

        $elongaciones = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $ciclos = collect();

        if ($hasLineaFilter) {
            $ciclos = CadenaCiclo::porLinea($request->linea)
                ->orderByDesc('numero_ciclo')
                ->get();
        }

        return view('elongaciones.index', [
            'elongaciones' => $elongaciones,
            'ciclos' => $ciclos,
            'lineas' => array_keys(Elongacion::PASOS_INICIALES),
        ]);
    }

    public function create(Request $request)
    {
        $lineaSeleccionada = $request->get('linea', 'L-04');
        $lineas = array_keys(Elongacion::PASOS_INICIALES);

        $ultimasLecturasPorLinea = Elongacion::with('cadenaCiclo')
            ->whereIn('id', Elongacion::selectRaw('MAX(id) as id')->groupBy('linea'))
            ->get()
            ->keyBy('linea');

        $ciclosActivosPorLinea = CadenaCiclo::activos()
            ->orderBy('linea')
            ->get()
            ->keyBy('linea');

        return view('elongaciones.create', [
            'lineaSeleccionada' => $lineaSeleccionada,
            'ultimaLectura' => $ultimasLecturasPorLinea->get($lineaSeleccionada),
            'ultimasLecturasPorLinea' => $ultimasLecturasPorLinea,
            'lineas' => $lineas,
            'pasosIniciales' => Elongacion::PASOS_INICIALES,
            'ciclosActivosPorLinea' => $ciclosActivosPorLinea,
            'cicloActivo' => $ciclosActivosPorLinea->get($lineaSeleccionada),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'linea' => 'required|in:' . implode(',', array_keys(Elongacion::PASOS_INICIALES)),
                'cadena_ciclo_id' => 'nullable|integer|exists:cadena_ciclos,id',
                'nueva_cadena' => 'nullable|boolean',
                'proveedor' => 'nullable|string|max:255',
                'hodometro_inicial' => 'nullable|integer|min:0',
                'fecha_instalacion' => 'nullable|date',
                'observaciones_cadena' => 'nullable|string|max:1000',
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

            $elongacion = DB::transaction(function () use ($request) {
                $linea = Linea::where('nombre', $request->linea)->first();
                $pasoInicial = Elongacion::getPasoInicial($request->linea);
                $ciclo = $this->resolverCicloParaRegistro($request, $linea, $pasoInicial);

                $bombasMediciones = $this->obtenerMediciones($request, 'bombas');
                $vaporMediciones = $this->obtenerMediciones($request, 'vapor');

                $bombasPromedio = Elongacion::calcularPromedio($bombasMediciones);
                $vaporPromedio = Elongacion::calcularPromedio($vaporMediciones);

                $bombasPorcentaje = Elongacion::calcularPorcentaje($bombasPromedio, $pasoInicial);
                $vaporPorcentaje = Elongacion::calcularPorcentaje($vaporPromedio, $pasoInicial);

                $estadoDetallado = $this->resolverEstadoDetallado($bombasPorcentaje, $vaporPorcentaje);
                $estado = $this->resolverEstadoGeneral($estadoDetallado);

                $requiereCambio = $bombasPorcentaje >= self::LIMITE_CAMBIO || $vaporPorcentaje >= self::LIMITE_CAMBIO;

                $data = [
                    'linea_id' => $linea?->id,
                    'linea' => $request->linea,
                    'cadena_ciclo_id' => $ciclo->id,
                    'proveedor' => $ciclo->proveedor,
                    'seccion' => 'LAVADORA',
                    'hodometro' => $request->hodometro,
                    'hodometro_ciclo' => $this->calcularHodometroCiclo($request->hodometro, $ciclo->hodometro_inicial),
                    'juego_rodaja_bombas' => $request->juego_rodaja_bombas,
                    'juego_rodaja_vapor' => $request->juego_rodaja_vapor,
                    'bombas_promedio' => $bombasPromedio,
                    'bombas_porcentaje' => $bombasPorcentaje,
                    'vapor_promedio' => $vaporPromedio,
                    'vapor_porcentaje' => $vaporPorcentaje,
                    'requiere_cambio' => $requiereCambio,
                    'estado' => $estado,
                    'estado_detallado' => $estadoDetallado,
                    'paso_inicial' => $pasoInicial,
                ];

                for ($i = 1; $i <= 10; $i++) {
                    $data["bombas_{$i}"] = $request->input("bombas_{$i}");
                    $data["vapor_{$i}"] = $request->input("vapor_{$i}");
                }

                $elongacion = Elongacion::create($data);

                $this->enviarNotificacionWhatsApp($request, $bombasPorcentaje, $vaporPorcentaje, $ciclo);

                return $elongacion;
            });

            $mensaje = 'Registro guardado exitosamente';

            if ($request->boolean('nueva_cadena')) {
                $mensaje = 'Nueva cadena registrada y medicion guardada exitosamente';
            }

            if ($elongacion->requiere_cambio) {
                $mensaje .= ' - CAMBIO REQUERIDO';
            } elseif ($elongacion->requiere_compra) {
                $mensaje .= ' - considerar compra de cadena';
            }

            return redirect()->route('elongaciones.index', ['linea' => $request->linea])
                ->with('success', $mensaje);

        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            Log::error('Error al guardar elongacion: ' . $e->getMessage(), [
                'linea' => $request->linea,
            ]);

            return back()->withInput()
                ->with('error', 'Error al guardar el registro: ' . $e->getMessage());
        }
    }

    public function show(Elongacion $elongacion)
    {
        $elongacion->load('cadenaCiclo');

        return view('elongaciones.show', compact('elongacion'));
    }

    public function showCiclo(CadenaCiclo $ciclo)
    {
        $ciclo->load('lineaModel');

        $elongaciones = $ciclo->elongaciones()
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $ultimaMedicion = $ciclo->elongaciones()->latest('created_at')->first();

        $resumen = [
            'total_registros' => $ciclo->elongaciones()->count(),
            'vida_util_horas' => $ciclo->elongaciones()->max('hodometro_ciclo'),
            'max_bombas' => $ciclo->elongaciones()->max('bombas_porcentaje'),
            'max_vapor' => $ciclo->elongaciones()->max('vapor_porcentaje'),
            'promedio_bombas' => round((float) $ciclo->elongaciones()->avg('bombas_porcentaje'), 2),
            'promedio_vapor' => round((float) $ciclo->elongaciones()->avg('vapor_porcentaje'), 2),
            'ultimo_estado' => $ultimaMedicion?->estado,
        ];

        return view('elongaciones.ciclos.show', compact('ciclo', 'elongaciones', 'resumen', 'ultimaMedicion'));
    }

    public function comparacionCiclos(Request $request)
    {
        $lineas = array_keys(Elongacion::PASOS_INICIALES);
        $lineaSeleccionada = $request->get('linea', $lineas[0] ?? null);

        $ciclos = CadenaCiclo::porLinea($lineaSeleccionada)
            ->orderByDesc('numero_ciclo')
            ->get()
            ->map(function (CadenaCiclo $ciclo) {
                $ultimaMedicion = $ciclo->elongaciones()->latest('created_at')->first();

                $ultimaFecha = $ciclo->retirada_en ?: $ultimaMedicion?->created_at;

                $diasOperacion = $ciclo->instalada_en && $ultimaFecha
                    ? (int) $ciclo->instalada_en->copy()->startOfDay()->diffInDays($ultimaFecha->copy()->startOfDay())
                    : null;

                return [
                    'ciclo' => $ciclo,
                    'registros' => $ciclo->elongaciones()->count(),
                    'vida_util_horas' => $ciclo->elongaciones()->max('hodometro_ciclo'),
                    'max_bombas' => $ciclo->elongaciones()->max('bombas_porcentaje'),
                    'max_vapor' => $ciclo->elongaciones()->max('vapor_porcentaje'),
                    'promedio_bombas' => round((float) $ciclo->elongaciones()->avg('bombas_porcentaje'), 2),
                    'promedio_vapor' => round((float) $ciclo->elongaciones()->avg('vapor_porcentaje'), 2),
                    'ultimo_estado' => $ultimaMedicion?->estado,
                    'ultima_medicion' => $ultimaMedicion,
                    'dias_operacion' => $diasOperacion,
                ];
            });

        return view('elongaciones.ciclos.comparacion', compact('ciclos', 'lineas', 'lineaSeleccionada'));
    }

    public function destroy(Elongacion $elongacion)
    {
        try {
            $linea = $elongacion->linea;
            $elongacion->delete();

            return redirect()->route('elongaciones.index', ['linea' => $linea])
                ->with('success', 'Registro eliminado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al eliminar elongacion: ' . $e->getMessage());

            return back()->with('error', 'Error al eliminar el registro');
        }
    }

    public function ultimaLectura($linea)
    {
        try {
            $ultima = Elongacion::with('cadenaCiclo')
                ->where('linea', $linea)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultima) {
                return response()->json([
                    'success' => true,
                    'hodometro' => $ultima->hodometro,
                    'hodometro_ciclo' => $ultima->hodometro_ciclo,
                    'fecha' => $ultima->created_at->format('d/m/Y H:i'),
                    'ciclo' => $ultima->cadenaCiclo?->codigo,
                    'proveedor' => $ultima->proveedor_actual,
                ]);
            }

            return response()->json([
                'success' => true,
                'hodometro' => null,
                'hodometro_ciclo' => null,
                'fecha' => null,
                'ciclo' => null,
                'proveedor' => null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar ultima lectura',
            ], 500);
        }
    }

    private function obtenerMediciones(Request $request, string $lado): array
    {
        $mediciones = [];

        for ($i = 1; $i <= 10; $i++) {
            $mediciones[] = $request->input("{$lado}_{$i}");
        }

        return $mediciones;
    }

    private function resolverCicloParaRegistro(Request $request, ?Linea $linea, int $pasoInicial): CadenaCiclo
    {
        $cicloActivo = CadenaCiclo::porLinea($request->linea)
            ->activos()
            ->orderByDesc('numero_ciclo')
            ->first();

        if ($request->filled('cadena_ciclo_id')) {
            $cicloSeleccionado = CadenaCiclo::whereKey($request->cadena_ciclo_id)
                ->where('linea', $request->linea)
                ->first();

            if (!$cicloSeleccionado) {
                throw ValidationException::withMessages([
                    'cadena_ciclo_id' => 'El ciclo seleccionado no pertenece a la linea indicada.',
                ]);
            }

            return $cicloSeleccionado;
        }

        if ($request->boolean('nueva_cadena')) {
            if (!$request->filled('proveedor')) {
                throw ValidationException::withMessages([
                    'proveedor' => 'El proveedor es obligatorio al registrar una nueva cadena.',
                ]);
            }

            if ($cicloActivo) {
                $cicloActivo->update([
                    'activa' => false,
                    'retirada_en' => $request->input('fecha_instalacion', now()),
                ]);
            }

            $numeroCiclo = (int) CadenaCiclo::porLinea($request->linea)->max('numero_ciclo') + 1;

            $hodometroInicial = $request->filled('hodometro_inicial')
                ? (int) $request->hodometro_inicial
                : ($request->filled('hodometro') ? (int) $request->hodometro : 0);

            return CadenaCiclo::create([
                'linea_id' => $linea?->id,
                'linea' => $request->linea,
                'codigo' => $this->buildCodigoCiclo($request->linea, $numeroCiclo),
                'numero_ciclo' => $numeroCiclo,
                'proveedor' => $request->proveedor,
                'paso_inicial' => $pasoInicial,
                'hodometro_inicial' => $hodometroInicial,
                'instalada_en' => $request->input('fecha_instalacion', now()),
                'activa' => true,
                'observaciones' => $request->observaciones_cadena,
            ]);
        }

        if ($cicloActivo) {
            return $cicloActivo;
        }

        if (!$request->filled('proveedor')) {
            throw ValidationException::withMessages([
                'proveedor' => 'El proveedor es obligatorio para iniciar el primer ciclo de esta linea.',
            ]);
        }

        return CadenaCiclo::create([
            'linea_id' => $linea?->id,
            'linea' => $request->linea,
            'codigo' => $this->buildCodigoCiclo($request->linea, 1),
            'numero_ciclo' => 1,
            'proveedor' => $request->proveedor,
            'paso_inicial' => $pasoInicial,
            'hodometro_inicial' => $request->filled('hodometro_inicial')
                ? (int) $request->hodometro_inicial
                : ($request->filled('hodometro') ? (int) $request->hodometro : 0),
            'instalada_en' => $request->input('fecha_instalacion', now()),
            'activa' => true,
            'observaciones' => $request->observaciones_cadena,
        ]);
    }

    private function resolverEstadoDetallado(float $bombasPorcentaje, float $vaporPorcentaje): string
    {
        $maximo = max($bombasPorcentaje, $vaporPorcentaje);

        if ($maximo >= self::LIMITE_CAMBIO) {
            return 'cambio';
        }

        if ($maximo >= self::LIMITE_COMPRA) {
            return 'comprar';
        }

        return 'normal';
    }

    private function resolverEstadoGeneral(string $estadoDetallado): string
    {
        return match ($estadoDetallado) {
            'cambio' => 'critico',
            'comprar' => 'alerta',
            default => 'normal',
        };
    }

    private function calcularHodometroCiclo($hodometroActual, $hodometroInicial): ?int
    {
        if ($hodometroActual === null) {
            return null;
        }

        if ($hodometroInicial === null) {
            return (int) $hodometroActual;
        }

        return max((int) $hodometroActual - (int) $hodometroInicial, 0);
    }

    private function buildCodigoCiclo(string $linea, int $numeroCiclo): string
    {
        return sprintf('%s-C%03d', $linea, $numeroCiclo);
    }

    private function obtenerEstadoLado(float $porcentaje): string
    {
        if ($porcentaje >= self::LIMITE_CAMBIO) {
            return 'critico';
        }

        if ($porcentaje >= self::LIMITE_COMPRA) {
            return 'compra';
        }

        return 'normal';
    }

    private function generarDetalleAfectaciones(float $bombasPorcentaje, float $vaporPorcentaje): string
    {
        $detalles = [];

        $estadoBombas = $this->obtenerEstadoLado($bombasPorcentaje);
        $estadoVapor = $this->obtenerEstadoLado($vaporPorcentaje);

        if ($estadoBombas === 'critico') {
            $detalles[] = '🚨 Bombas: CRITICO / CAMBIO URGENTE (' . round($bombasPorcentaje, 2) . '%)';
        } elseif ($estadoBombas === 'compra') {
            $detalles[] = '⚠️ Bombas: ALERTA DE COMPRA (' . round($bombasPorcentaje, 2) . '%)';
        }

        if ($estadoVapor === 'critico') {
            $detalles[] = '🚨 Vapor: CRITICO / CAMBIO URGENTE (' . round($vaporPorcentaje, 2) . '%)';
        } elseif ($estadoVapor === 'compra') {
            $detalles[] = '⚠️ Vapor: ALERTA DE COMPRA (' . round($vaporPorcentaje, 2) . '%)';
        }

        return !empty($detalles)
            ? implode("\n", $detalles)
            : 'Sin afectacion';
    }

    private function enviarNotificacionWhatsApp(Request $request, float $bombasPorcentaje, float $vaporPorcentaje, CadenaCiclo $ciclo): void
    {
        $numero = '5214921933175';
        $mensaje = null;

        $bombasEstado = $this->obtenerEstadoLado($bombasPorcentaje);
        $vaporEstado = $this->obtenerEstadoLado($vaporPorcentaje);

        $hayCritico = $bombasEstado === 'critico' || $vaporEstado === 'critico';
        $hayCompra = $bombasEstado === 'compra' || $vaporEstado === 'compra';

        $detalleAfectaciones = $this->generarDetalleAfectaciones($bombasPorcentaje, $vaporPorcentaje);

        if ($hayCritico) {
            $mensaje = "*🚨 ALERTA CRITICA - ⛓️ CAMBIO DE CADENA URGENTE PARA EVITAR DAÑOS*\n\n"
                . "📍 Lav Linea: {$request->linea}\n"
                . "Ciclo: {$ciclo->codigo}\n"
                . "Proveedor: {$ciclo->proveedor}\n\n"
                . "📌 Afectaciones detectadas:\n"
                . "{$detalleAfectaciones}\n\n"
                . "⚙️ Bombas: " . round($bombasPorcentaje, 2) . "%\n"
                . "💨 Vapor: " . round($vaporPorcentaje, 2) . "%\n\n"
                . "Limite de compra: " . self::LIMITE_COMPRA . "%\n"
                . "Limite de cambio: " . self::LIMITE_CAMBIO . "%\n\n"
                . "CAMBIO INMEDIATO REQUERIDO";

        } elseif ($hayCompra) {
            $mensaje = "*⚠️ ALERTA - ⛓️ CONSIDERAR COMPRA DE CADENA PARA SU PROXIMO CAMBIO*\n\n"
                . "📍 Lav Linea: {$request->linea}\n"
                . "Ciclo: {$ciclo->codigo}\n"
                . "Proveedor: {$ciclo->proveedor}\n\n"
                . "📌 Afectaciones detectadas:\n"
                . "{$detalleAfectaciones}\n\n"
                . "⚙️ Bombas: " . round($bombasPorcentaje, 2) . "%\n"
                . "💨 Vapor: " . round($vaporPorcentaje, 2) . "%\n\n"
                . "🛒 Supero el limite de compra: " . self::LIMITE_COMPRA . "%";
        }

        if ($mensaje && class_exists(WhatsAppService::class)) {
            try {
                WhatsAppService::enviarMensaje($numero, $mensaje);

                Log::info('WhatsApp elongacion enviado', [
                    'linea' => $request->linea,
                    'ciclo' => $ciclo->codigo,
                    'bombas' => $bombasPorcentaje,
                    'vapor' => $vaporPorcentaje,
                    'estado_bombas' => $bombasEstado,
                    'estado_vapor' => $vaporEstado,
                ]);

            } catch (\Exception $e) {
                Log::error('Error WhatsApp elongacion: ' . $e->getMessage());
            }
        }
    }
}
