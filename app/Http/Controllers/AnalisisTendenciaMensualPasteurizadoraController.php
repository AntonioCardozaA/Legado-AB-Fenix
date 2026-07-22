<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Services\TendenciaDanosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnalisisTendenciaMensualPasteurizadoraController extends Controller
{
    private const PASTEURIZADORAS = [
        'P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08',
        'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14',
    ];

    public function index(Request $request, TendenciaDanosService $tendenciaDanos)
    {
        return $this->index52124($request, $tendenciaDanos);
    }

    public function index52124(Request $request, TendenciaDanosService $tendenciaDanos)
    {
        return $this->renderIndex(
            $request,
            $tendenciaDanos,
            'analisis-tendencia-mensual.pasteurizadora.index',
            '52124'
        );
    }

    public function index30147(Request $request, TendenciaDanosService $tendenciaDanos)
    {
        return $this->renderIndex(
            $request,
            $tendenciaDanos,
            'analisis-tendencia-mensual.pasteurizadora.index-30-14-7',
            '30147'
        );
    }

    private function renderIndex(Request $request, TendenciaDanosService $tendenciaDanos, string $view, string $analisisTipo)
    {
        $validated = $request->validate([
            'linea_id' => ['nullable', 'integer'],
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $lineas = $this->getLineasPasteurizadora();
        $lineaSeleccionada = $validated['linea_id'] ?? $lineas->first()?->id;
        $fechaInicio = $validated['fecha_inicio'] ?? null;
        $fechaFin = $validated['fecha_fin'] ?? null;
        $this->validarOrdenRangoFechas($fechaInicio, $fechaFin);
        $fechaInicioCarbon = $fechaInicio ? Carbon::createFromFormat('Y-m-d', $fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::createFromFormat('Y-m-d', $fechaFin)->endOfDay() : null;
        $analisis = collect();
        $meses = [];
        $ventanas = $analisisTipo === '30147'
            ? $tendenciaDanos->ventanas30147()
            : $tendenciaDanos->ventanas52124();
        $rangoTendencia = [
            'from' => $fechaInicioCarbon,
            'to' => $fechaFinCarbon ?: now()->copy()->endOfDay(),
        ];
        $analisisDetalle = $tendenciaDanos->construirDashboard(
            $lineas,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            $ventanas,
            $rangoTendencia
        );
        $detalleLinea = null;

        if ($lineaSeleccionada) {
            $linea = $lineas->firstWhere('id', (int) $lineaSeleccionada);

            if ($linea) {
                $eventosLinea = $tendenciaDanos
                    ->obtenerEventos(collect([$linea]), TendenciaDanosService::TIPO_PASTEURIZADORAS, $rangoTendencia)
                    ->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())
                    ->values();
                $fechaInicioHistorica = $fechaInicioCarbon ?: ($eventosLinea->first()['occurred_at'] ?? null);
                $analisis = $tendenciaDanos->construirFilasMensuales(
                    $linea,
                    TendenciaDanosService::TIPO_PASTEURIZADORAS,
                    12,
                    $fechaFinCarbon,
                    $fechaInicioHistorica ? $fechaInicioHistorica->copy()->startOfMonth() : null
                );
                $detalleLinea = collect($analisisDetalle['lineas'] ?? [])
                    ->firstWhere('linea_id', (int) $lineaSeleccionada);

                foreach ($analisis as $item) {
                    $meses[$item->anio][$item->mes] = $item;
                }
            }
        }

        return view($view, compact(
            'lineas',
            'lineaSeleccionada',
            'fechaInicio',
            'fechaFin',
            'analisis',
            'meses',
            'analisisTipo',
            'analisisDetalle',
            'detalleLinea'
        ));
    }

    public function create(Request $request)
    {
        return redirect()
            ->route('analisis-tendencia-mensual.pasteurizadora.index', $request->only('linea_id'))
            ->with('info', 'La tendencia 52-12-4 y 30-14-7 se calcula automaticamente desde los analisis registrados.');
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('analisis-tendencia-mensual.pasteurizadora.index', $request->only('linea_id'))
            ->with('info', 'Ya no es necesario capturar totales manuales; la tendencia se actualiza automaticamente.');
    }

    public function show($analisis, Request $request)
    {
        return redirect()
            ->route('analisis-tendencia-mensual.pasteurizadora.index', $request->only('linea_id'))
            ->with('info', 'El detalle historico ahora se consulta desde la vista automatica de tendencias.');
    }

    public function getTendenciaApi(Request $request, TendenciaDanosService $tendenciaDanos)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $this->validarOrdenRangoFechas($request->input('fecha_inicio'), $request->input('fecha_fin'));

        $linea = $this->getLineasPasteurizadora()->firstWhere('id', (int) $request->linea_id);
        $fechaInicio = $request->filled('fecha_inicio')
            ? Carbon::createFromFormat('Y-m-d', $request->fecha_inicio)->startOfDay()
            : null;
        $fechaFin = $request->filled('fecha_fin')
            ? Carbon::createFromFormat('Y-m-d', $request->fecha_fin)->endOfDay()
            : null;

        if (!$linea) {
            return response()->json(['success' => false, 'message' => 'Pasteurizadora invalida.'], 422);
        }

        $datos = $tendenciaDanos
            ->construirFilasMensuales($linea, TendenciaDanosService::TIPO_PASTEURIZADORAS, 12, $fechaFin, $fechaInicio)
            ->sortBy(fn ($item) => sprintf('%04d%02d', $item->anio, $item->mes))
            ->values()
            ->map(fn ($item) => [
                'periodo' => $item->periodo,
                'anio' => $item->anio,
                'mes' => $item->mes,
                'semanas_52' => $item->total_danos_52_semanas,
                'semanas_12' => $item->total_danos_12_semanas,
                'semanas_4' => $item->total_danos_4_semanas,
                'dias_30' => $item->total_danos_30_dias,
                'dias_14' => $item->total_danos_14_dias,
                'dias_7' => $item->total_danos_7_dias,
                'variacion_52' => $this->formatearVariacion($item->variacion_52_semanas),
                'variacion_12' => $this->formatearVariacion($item->variacion_12_semanas),
                'variacion_4' => $this->formatearVariacion($item->variacion_4_semanas),
                'variacion_30' => $this->formatearVariacion($item->variacion_30_dias),
                'variacion_14' => $this->formatearVariacion($item->variacion_14_dias),
                'variacion_7' => $this->formatearVariacion($item->variacion_7_dias),
            ]);

        return response()->json([
            'success' => true,
            'data' => $datos,
        ]);
    }

    private function getLineasPasteurizadora()
    {
        return Linea::whereIn('nombre', self::PASTEURIZADORAS)
            ->orderBy('nombre')
            ->get();
    }

    private function formatearVariacion(?array $variacion): ?array
    {
        if (!$variacion) {
            return null;
        }

        return [
            'valor' => $variacion['porcentaje'],
            'diferencia' => $variacion['diferencia'],
            'tendencia' => $variacion['tendencia'],
        ];
    }

    private function validarOrdenRangoFechas(?string $fechaInicio, ?string $fechaFin): void
    {
        if ($fechaInicio && $fechaFin && $fechaInicio > $fechaFin) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            ]);
        }
    }
}
