<?php

namespace App\Http\Controllers;

use App\Models\AnalisisTendenciaMensualPasteurizadora;
use App\Models\Linea;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisisTendenciaMensualPasteurizadoraController extends Controller
{
    private const PASTEURIZADORAS = [
        'P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-08',
        'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14',
    ];

    public function index(Request $request)
    {
        $lineas = $this->getLineasPasteurizadora();
        $lineaSeleccionada = $request->get('linea_id', $lineas->first()?->id);
        $analisis = collect();
        $meses = [];

        if ($lineaSeleccionada && $this->lineaEsPasteurizadora($lineaSeleccionada)) {
            $analisis = AnalisisTendenciaMensualPasteurizadora::where('linea_id', $lineaSeleccionada)
                ->orderBy('anio', 'desc')
                ->orderBy('mes', 'desc')
                ->get();

            foreach ($analisis as $item) {
                $meses[$item->anio][$item->mes] = $item;
            }
        }

        return view('analisis-tendencia-mensual.pasteurizadora.index', compact(
            'lineas',
            'lineaSeleccionada',
            'analisis',
            'meses'
        ));
    }

    public function create(Request $request)
    {
        $lineas = $this->getLineasPasteurizadora();
        $lineaSeleccionada = $request->get('linea_id');
        $anioActual = now()->year;
        $mesActual = now()->month;
        $existeRegistro = false;

        if ($lineaSeleccionada && $this->lineaEsPasteurizadora($lineaSeleccionada)) {
            $existeRegistro = AnalisisTendenciaMensualPasteurizadora::where('linea_id', $lineaSeleccionada)
                ->where('anio', $anioActual)
                ->where('mes', $mesActual)
                ->exists();
        }

        return view('analisis-tendencia-mensual.pasteurizadora.create', compact(
            'lineas',
            'lineaSeleccionada',
            'anioActual',
            'mesActual',
            'existeRegistro'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'anio' => 'required|integer|min:2020|max:2050',
            'mes' => 'required|integer|min:1|max:12',
            'total_danos_52_semanas' => 'required|numeric|min:0',
            'total_danos_12_semanas' => 'required|numeric|min:0',
            'total_danos_4_semanas' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if (!$this->lineaEsPasteurizadora($validated['linea_id'])) {
            return back()
                ->withInput()
                ->with('error', 'Seleccione una pasteurizadora valida.');
        }

        $existe = AnalisisTendenciaMensualPasteurizadora::where('linea_id', $validated['linea_id'])
            ->where('anio', $validated['anio'])
            ->where('mes', $validated['mes'])
            ->exists();

        if ($existe) {
            return back()
                ->withInput()
                ->with('error', 'Ya existe un analisis para este mes y pasteurizadora.');
        }

        DB::beginTransaction();

        try {
            $fechaReferencia = Carbon::create($validated['anio'], $validated['mes'], 1)->endOfMonth();

            AnalisisTendenciaMensualPasteurizadora::create([
                'linea_id' => $validated['linea_id'],
                'anio' => $validated['anio'],
                'mes' => $validated['mes'],
                'total_danos_52_semanas' => $validated['total_danos_52_semanas'],
                'total_danos_12_semanas' => $validated['total_danos_12_semanas'],
                'total_danos_4_semanas' => $validated['total_danos_4_semanas'],
                'fecha_corte_52' => $fechaReferencia->copy()->subWeeks(52),
                'fecha_corte_12' => $fechaReferencia->copy()->subWeeks(12),
                'fecha_corte_4' => $fechaReferencia->copy()->subWeeks(4),
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            DB::commit();

            return redirect()
                ->route('analisis-tendencia-mensual.pasteurizadora.index', ['linea_id' => $validated['linea_id']])
                ->with('success', 'Analisis mensual de pasteurizadora guardado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function show(AnalisisTendenciaMensualPasteurizadora $analisis)
    {
        $analisis->load('linea');

        $historial = AnalisisTendenciaMensualPasteurizadora::where('linea_id', $analisis->linea_id)
            ->where(function ($query) use ($analisis) {
                $query->where('anio', '<', $analisis->anio)
                    ->orWhere(function ($q) use ($analisis) {
                        $q->where('anio', $analisis->anio)
                            ->where('mes', '<=', $analisis->mes);
                    });
            })
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get()
            ->reverse();

        return view('analisis-tendencia-mensual.pasteurizadora.show', compact('analisis', 'historial'));
    }

    public function getTendenciaApi(Request $request)
    {
        $request->validate([
            'linea_id' => 'required|exists:lineas,id',
        ]);

        if (!$this->lineaEsPasteurizadora($request->linea_id)) {
            return response()->json(['success' => false, 'message' => 'Pasteurizadora invalida.'], 422);
        }

        $datos = AnalisisTendenciaMensualPasteurizadora::where('linea_id', $request->linea_id)
            ->orderBy('anio')
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                $variacion52 = $item->variacion_52_semanas;
                $variacion12 = $item->variacion_12_semanas;
                $variacion4 = $item->variacion_4_semanas;

                return [
                    'periodo' => $item->periodo,
                    'anio' => $item->anio,
                    'mes' => $item->mes,
                    'semanas_52' => $item->total_danos_52_semanas,
                    'semanas_12' => $item->total_danos_12_semanas,
                    'semanas_4' => $item->total_danos_4_semanas,
                    'variacion_52' => $variacion52 ? [
                        'valor' => $variacion52['porcentaje'],
                        'tendencia' => $variacion52['tendencia'],
                    ] : null,
                    'variacion_12' => $variacion12 ? [
                        'valor' => $variacion12['porcentaje'],
                        'tendencia' => $variacion12['tendencia'],
                    ] : null,
                    'variacion_4' => $variacion4 ? [
                        'valor' => $variacion4['porcentaje'],
                        'tendencia' => $variacion4['tendencia'],
                    ] : null,
                ];
            });

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

    private function lineaEsPasteurizadora($lineaId): bool
    {
        return Linea::whereKey($lineaId)
            ->whereIn('nombre', self::PASTEURIZADORAS)
            ->exists();
    }
}
