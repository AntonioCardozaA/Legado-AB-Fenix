<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Services\TendenciaDanosService;
use Illuminate\Http\Request;

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
            'analisis-tendencia-mensual.pasteurizadora.index'
        );
    }

    public function index30147(Request $request, TendenciaDanosService $tendenciaDanos)
    {
        return $this->renderIndex(
            $request,
            $tendenciaDanos,
            'analisis-tendencia-mensual.pasteurizadora.index-30-14-7'
        );
    }

    private function renderIndex(Request $request, TendenciaDanosService $tendenciaDanos, string $view)
    {
        $lineas = $this->getLineasPasteurizadora();
        $lineaSeleccionada = $request->get('linea_id', $lineas->first()?->id);
        $analisis = collect();
        $meses = [];

        if ($lineaSeleccionada) {
            $linea = $lineas->firstWhere('id', (int) $lineaSeleccionada);

            if ($linea) {
                $analisis = $tendenciaDanos->construirFilasMensuales(
                    $linea,
                    TendenciaDanosService::TIPO_PASTEURIZADORAS
                );

                foreach ($analisis as $item) {
                    $meses[$item->anio][$item->mes] = $item;
                }
            }
        }

        return view($view, compact(
            'lineas',
            'lineaSeleccionada',
            'analisis',
            'meses'
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
        ]);

        $linea = $this->getLineasPasteurizadora()->firstWhere('id', (int) $request->linea_id);

        if (!$linea) {
            return response()->json(['success' => false, 'message' => 'Pasteurizadora invalida.'], 422);
        }

        $datos = $tendenciaDanos
            ->construirFilasMensuales($linea, TendenciaDanosService::TIPO_PASTEURIZADORAS)
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
}
