<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\Componente;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\Paro;
use App\Models\PlanAccion;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteLavadoraGeneralExcel;
use Carbon\Carbon;
use App\Models\Elongacion;
use App\Models\HistorialRestablecimiento;
use App\Services\TendenciaDanosService;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    // Líneas de lavadoras
    protected $lavadoras = ['L-04','L-05','L-06','L-07','L-08','L-09','L-12','L-13'];

    // Líneas pasteurizadoras
    protected $pasteurizadoras = ['P-03','P-04','P-05','P-06','P-07','P-08','P-09','P-10','P-11','P-12','P-13','P-14'];

    // Componentes por línea
    protected $componentesPorLinea = [
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

    // Reductores por línea
    protected $reductoresPorLinea = [
        'L-04' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor Loca','Reductor Principal'],
        'L-05' => ['Reductor 1','Reductor 2','Reductor 3','Reductor 4','Reductor 5','Reductor 6','Reductor 7','Reductor 8','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor Principal','Reductor Loca'],
        'L-06' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor 20','Reductor 21','Reductor 22','Reductor Principal'],
        'L-07' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor 20','Reductor 21','Reductor 22','Reductor Principal'],
        'L-08' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor Loca'],
        'L-09' => ['Reductor 1','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor 13','Reductor 14','Reductor 15','Reductor 16','Reductor 17','Reductor 18','Reductor 19','Reductor Loca','Reductor Principal'],
        'L-12' => ['Reductor 1','Reductor 2','Reductor 3','Reductor 4','Reductor 5','Reductor 6','Reductor 7','Reductor 8','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor Loca','Reductor Principal'],
        'L-13' => ['Reductor 1','Reductor 2','Reductor 3','Reductor 4','Reductor 5','Reductor 6','Reductor 7','Reductor 8','Reductor 9','Reductor 10','Reductor 11','Reductor 12','Reductor Loca','Reductor Principal']
    ];

    public function index(Request $request)
    {
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $fechaInicio = $request->get('fecha_inicio') 
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();
            
        $fechaFin = $request->get('fecha_fin') 
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        $lineaId = $request->get('linea_id', $request->get('lineaId'));
        $lineasFiltro = Linea::whereIn('nombre', $this->getLineasPorTipo($tipoEquipo))
            ->orderBy('nombre')
            ->get();

        if ($lineaId) {
            $lineaSeleccionada = $lineasFiltro->firstWhere('id', (int) $lineaId);
            $lineaId = $lineaSeleccionada?->id;
        }

        $reporteGeneral = $this->generarReporteIndexOptimizado($tipoEquipo, $fechaInicio, $fechaFin);

        $lineas = $lineaId
            ? $lineasFiltro->where('id', (int) $lineaId)->values()
            : $lineasFiltro;
        $reporteDetallado = [
            'lineas' => $lineas
                ->map(fn ($linea) => $this->getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo))
                ->all(),
        ];
        $canAccessPasteurizadora = auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA) ?? false;

        return view('reportes.index', compact(
            'lineas', 
            'tipoEquipo', 
            'fechaInicio', 
            'fechaFin', 
            'reporteGeneral',
            'canAccessPasteurizadora',
            'lineaId',
            'lineasFiltro',
            'reporteDetallado'
        ));
    }

    public function pasteurizadora(Request $request)
    {
        $this->ensureCanAccessTipoEquipo('pasteurizadoras');

        return redirect()->route('reportes.index', array_merge(
            $request->query(),
            ['tipo' => 'pasteurizadoras']
        ));
    }

    public function elongacion(Request $request)
    {
        $fechaInicio = $request->filled('fecha_inicio')
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();

        $fechaFin = $request->filled('fecha_fin')
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        $lineas = Linea::whereIn('nombre', $this->lavadoras)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $lineaId = $request->get('linea_id');
        $lineaNombre = $request->get('linea');

        if ($lineaId) {
            $lineaNombre = $lineas->firstWhere('id', (int) $lineaId)?->nombre;
        }

        if ($lineaNombre && !$lineas->pluck('nombre')->contains($lineaNombre)) {
            $lineaNombre = null;
            $lineaId = null;
        }

        $consultaIds = Elongacion::query()
            ->whereIn('linea', $lineas->pluck('nombre'))
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if ($lineaNombre) {
            $consultaIds->where('linea', $lineaNombre);
        }

        $ultimosIds = $consultaIds
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('linea')
            ->pluck('id');

        $registros = Elongacion::whereIn('id', $ultimosIds)
            ->get()
            ->keyBy('linea');

        $datos = $lineas
            ->when($lineaNombre, fn ($coleccion) => $coleccion->where('nombre', $lineaNombre))
            ->map(function ($linea) use ($registros) {
                $registro = $registros->get($linea->nombre);

                if (!$registro) {
                    return null;
                }

                $pasoInicial = Elongacion::getPasoInicial($linea->nombre);
                $porcentaje = max((float) $registro->bombas_porcentaje, (float) $registro->vapor_porcentaje);
                $elongacion = $pasoInicial * (1 + ($porcentaje / 100));

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'fecha' => optional($registro->created_at)->format('d/m/Y'),
                    'elongacion' => round($elongacion, 2),
                    'porcentaje' => round($porcentaje, 2),
                    'horometro' => (int) ($registro->hodometro ?? 0),
                    'estado' => $this->estadoElongacionReporte($porcentaje),
                ];
            })
            ->filter()
            ->values();

        return view('reportes.elongacion', compact(
            'datos',
            'lineas',
            'lineaId',
            'lineaNombre',
            'fechaInicio',
            'fechaFin'
        ));
    }

    public function componentes(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodoComponentes($request->get('periodo', '1mes'));

        $lineas = Linea::whereIn('nombre', $this->lavadoras)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $lineaId = $request->get('linea');

        if ($lineaId && !$lineas->pluck('id')->contains((int) $lineaId)) {
            $lineaId = null;
        }

        $todosComponentes = Componente::query()
            ->where(function ($query) {
                $query->whereIn('linea', $this->lavadoras)
                    ->orWhereNull('linea')
                    ->orWhere('linea', '');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo'])
            ->mapWithKeys(fn ($componente) => [
                $componente->id => trim($componente->nombre . ' - ' . $componente->codigo, ' -'),
            ]);

        $consulta = AnalisisLavadora::with(['linea:id,nombre', 'componente:id,nombre,codigo,cantidad_total'])
            ->whereHas('linea', fn ($query) => $query->whereIn('nombre', $this->lavadoras));

        if ($fechaInicio && $fechaFin) {
            $consulta->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin]);
        }

        if ($lineaId) {
            $consulta->where('linea_id', $lineaId);
        }

        if ($request->filled('componente')) {
            $consulta->where('componente_id', $request->componente);
        }

        if ($request->filled('estado')) {
            $consulta->where('estado', $request->estado);
        }

        $analisis = $consulta
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get();

        $items = $this->construirReporteComponentes($analisis);
        $reporte = $lineaId ? $items->values()->all() : [];
        $reportePorLinea = $lineaId ? [] : $items->groupBy('linea_nombre')->sortKeys()->all();
        $estadisticas = $this->estadisticasComponentes($analisis);
        $estados = $this->distribucionEstadosComponentes($analisis);
        $estadisticasPorLinea = $this->estadisticasPorLineaComponentes($lineas, $analisis);
        $componentesCriticos = $items
            ->filter(fn ($item) => ($item['danados'] ?? 0) > 0)
            ->sortByDesc('danados')
            ->take(10)
            ->values()
            ->all();
        $componentesBajaRevision = $items
            ->filter(fn ($item) => ($item['porcentaje_revisado'] ?? 0) < 80)
            ->sortBy('porcentaje_revisado')
            ->take(10)
            ->values()
            ->all();

        return view('reportes.componentes', compact(
            'lineas',
            'todosComponentes',
            'estadisticas',
            'estados',
            'estadisticasPorLinea',
            'reporte',
            'reportePorLinea',
            'componentesCriticos',
            'componentesBajaRevision',
            'fechaInicio',
            'fechaFin'
        ));
    }

    public function paros(Request $request)
    {
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $fechaInicio = $request->filled('fecha_inicio')
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();

        $fechaFin = $request->filled('fecha_fin')
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        $lineas = Linea::whereIn('nombre', $this->getLineasPorTipo($tipoEquipo))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $lineaId = $request->get('linea_id');

        if ($lineaId && !$lineas->pluck('id')->contains((int) $lineaId)) {
            $lineaId = null;
        }

        $consulta = Paro::with(['linea:id,nombre', 'supervisor:id,name', 'planesAccion'])
            ->whereIn('linea_id', $lineas->pluck('id'))
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio);

        if ($lineaId) {
            $consulta->where('linea_id', $lineaId);
        }

        if ($request->filled('tipo_paro')) {
            $consulta->where('tipo', $request->tipo_paro);
        }

        $resumenParos = (clone $consulta)->get();

        $paros = $consulta
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'total' => $resumenParos->count(),
            'programados' => $resumenParos->filter(fn ($paro) => str_contains(strtolower($paro->tipo), 'program'))->count(),
            'emergencia' => $resumenParos->filter(fn ($paro) => str_contains(strtolower($paro->tipo), 'emerg'))->count(),
            'horas' => $resumenParos->sum(fn ($paro) => $this->calcularHorasParo($paro)),
        ];
        $canAccessPasteurizadora = auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA) ?? false;

        return view('reportes.paros', compact(
            'tipoEquipo',
            'lineas',
            'lineaId',
            'fechaInicio',
            'fechaFin',
            'paros',
            'resumen',
            'canAccessPasteurizadora'
        ));
    }

    private function resolverPeriodoComponentes(?string $periodo): array
    {
        $fechaFin = Carbon::now()->endOfDay();

        return match ($periodo) {
            '3meses' => [Carbon::now()->subMonths(3)->startOfDay(), $fechaFin],
            '6meses' => [Carbon::now()->subMonths(6)->startOfDay(), $fechaFin],
            '1anio' => [Carbon::now()->subYear()->startOfDay(), $fechaFin],
            'todo' => [null, $fechaFin],
            default => [Carbon::now()->subMonth()->startOfDay(), $fechaFin],
        };
    }

    private function estadoElongacionReporte(float $porcentaje): array
    {
        if ($porcentaje >= Elongacion::LIMITE_CAMBIO) {
            return ['bg' => 'bg-red-100', 'color' => 'text-red-700', 'text' => 'CRITICO'];
        }

        if ($porcentaje >= Elongacion::LIMITE_COMPRAR) {
            return ['bg' => 'bg-yellow-100', 'color' => 'text-yellow-700', 'text' => 'ATENCION'];
        }

        return ['bg' => 'bg-green-100', 'color' => 'text-green-700', 'text' => 'NORMAL'];
    }

    private function construirReporteComponentes($analisis)
    {
        return $analisis
            ->groupBy(fn ($registro) => ($registro->linea_id ?? 'sin-linea') . '-' . ($registro->componente_id ?? 'sin-componente'))
            ->map(function ($registros) {
                $primero = $registros->first();
                $componente = $primero->componente;
                $totalConfigurado = (int) ($componente->cantidad_total ?? 0);
                $revisado = $registros
                    ->map(fn ($registro) => trim(($registro->reductor ?? '') . '|' . ($registro->lado ?? '')))
                    ->filter()
                    ->unique()
                    ->count();

                if ($revisado === 0) {
                    $revisado = $registros->count();
                }

                $total = max($totalConfigurado, $revisado, $registros->count());

                $estados = [
                    'BUENO' => 0,
                    'REQUIERE_REVISION' => 0,
                    'DESGASTE_MODERADO' => 0,
                    'DESGASTE_SEVERO' => 0,
                    'DANADO_REQUIERE' => 0,
                    'DANADO_CAMBIADO' => 0,
                ];

                foreach ($registros as $registro) {
                    $estados[$this->estadoKeyComponentes($registro->estado)]++;
                }

                $danados = $estados['DANADO_REQUIERE'];

                return [
                    'linea_id' => $primero->linea_id,
                    'linea_nombre' => $primero->linea?->nombre ?? 'Sin linea',
                    'componente_id' => $primero->componente_id,
                    'componente' => $componente?->nombre ?? 'Sin componente',
                    'codigo' => $componente?->codigo ?? 'N/A',
                    'total' => $total,
                    'revisado' => $revisado,
                    'estados' => $estados,
                    'danados' => $danados,
                    'porcentaje_revisado' => $total > 0 ? min(100, round(($revisado / $total) * 100, 1)) : 0,
                ];
            })
            ->sortBy([
                ['linea_nombre', 'asc'],
                ['componente', 'asc'],
            ])
            ->values();
    }

    private function estadoKeyComponentes(?string $estado): string
    {
        if (AnalisisLavadora::esEstadoBueno($estado)) {
            return 'BUENO';
        }

        if (AnalisisLavadora::esEstadoRequiereRevision($estado)) {
            return 'REQUIERE_REVISION';
        }

        if ($estado === 'Desgaste severo') {
            return 'DESGASTE_SEVERO';
        }

        if ($estado === 'Desgaste moderado') {
            return 'DESGASTE_MODERADO';
        }

        if (AnalisisLavadora::esEstadoCambiado($estado) || str_contains($this->normalizarEstadoReporte($estado), 'cambiado')) {
            return 'DANADO_CAMBIADO';
        }

        if (AnalisisLavadora::esEstadoDanado($estado) || $this->esEstadoDanadoReporte($estado)) {
            return 'DANADO_REQUIERE';
        }

        return 'BUENO';
    }

    private function estadisticasComponentes($analisis): array
    {
        $total = $analisis->count();
        $buenEstado = $analisis->filter(fn ($registro) => AnalisisLavadora::esEstadoBueno($registro->estado))->count();
        $requiereRevision = $analisis->filter(fn ($registro) => AnalisisLavadora::esEstadoRequiereRevision($registro->estado))->count();
        $desgaste = $analisis->filter(fn ($registro) => AnalisisLavadora::esEstadoDesgaste($registro->estado))->count();
        $danados = $analisis->filter(fn ($registro) => AnalisisLavadora::esEstadoDanado($registro->estado) || $this->esEstadoDanadoReporte($registro->estado))->count();
        $reemplazados = $analisis->filter(fn ($registro) => AnalisisLavadora::esEstadoCambiado($registro->estado))->count();

        return [
            'total_componentes' => $total,
            'buen_estado' => $buenEstado,
            'requiere_revision' => $requiereRevision,
            'desgaste' => $desgaste,
            'danados' => $danados,
            'reemplazados' => $reemplazados,
            'porcentaje_bueno' => $total ? round(($buenEstado / $total) * 100, 1) : 0,
            'porcentaje_revision' => $total ? round(($requiereRevision / $total) * 100, 1) : 0,
            'porcentaje_desgaste' => $total ? round(($desgaste / $total) * 100, 1) : 0,
            'porcentaje_danado' => $total ? round(($danados / $total) * 100, 1) : 0,
        ];
    }

    private function distribucionEstadosComponentes($analisis): array
    {
        $total = max($analisis->count(), 1);
        $colores = [
            AnalisisLavadora::ESTADO_BUENO => 'bg-green-500',
            AnalisisLavadora::ESTADO_REQUIERE_REVISION => 'bg-yellow-500',
            'Desgaste moderado' => 'bg-orange-400',
            'Desgaste severo' => 'bg-orange-600',
            AnalisisLavadora::ESTADO_DANADO => 'bg-red-600',
            AnalisisLavadora::ESTADO_CAMBIADO => 'bg-blue-500',
        ];

        return $analisis
            ->groupBy('estado')
            ->map(fn ($registros, $estado) => [
                'cantidad' => $registros->count(),
                'porcentaje' => round(($registros->count() / $total) * 100, 1),
                'color' => $colores[$estado] ?? 'bg-slate-400',
            ])
            ->sortKeys()
            ->all();
    }

    private function estadisticasPorLineaComponentes($lineas, $analisis): array
    {
        $porLinea = $analisis->groupBy('linea_id');
        $estadisticas = [];

        foreach ($lineas as $linea) {
            $registros = $porLinea->get($linea->id, collect());

            $estadisticas[$linea->id] = [
                'total' => $registros->count(),
                'buen_estado' => $registros->filter(fn ($registro) => AnalisisLavadora::esEstadoBueno($registro->estado))->count(),
                'requiere_revision' => $registros->filter(fn ($registro) => AnalisisLavadora::esEstadoRequiereRevision($registro->estado))->count(),
                'desgaste' => $registros->filter(fn ($registro) => AnalisisLavadora::esEstadoDesgaste($registro->estado))->count(),
                'danado' => $registros->filter(fn ($registro) => AnalisisLavadora::esEstadoDanado($registro->estado) || $this->esEstadoDanadoReporte($registro->estado))->count(),
                'reemplazado' => $registros->filter(fn ($registro) => AnalisisLavadora::esEstadoCambiado($registro->estado))->count(),
            ];
        }

        return $estadisticas;
    }

    private function calcularHorasParo(Paro $paro): int
    {
        if (!$paro->fecha_inicio || !$paro->fecha_fin) {
            return 0;
        }

        return (Carbon::parse($paro->fecha_inicio)->startOfDay()
            ->diffInDays(Carbon::parse($paro->fecha_fin)->startOfDay()) + 1) * 24;
    }

    private function contarEvidenciasReporte($evidencias): int
    {
        if (is_array($evidencias)) {
            return count($evidencias);
        }

        if ($evidencias instanceof \Illuminate\Support\Collection) {
            return $evidencias->count();
        }

        if (is_string($evidencias) && trim($evidencias) !== '') {
            $decoded = json_decode($evidencias, true);

            return is_array($decoded) ? count($decoded) : 1;
        }

        return 0;
    }

    private function calcularHistoricoRevisadosLavadora(Linea $linea): array
    {
        $componentesLinea = $this->componentesPorLinea[$linea->nombre] ?? [];
        $cantidadesPorLinea = [
            'L-04' => 14,
            'L-05' => 14,
            'L-06' => 16,
            'L-07' => 16,
            'L-08' => 15,
            'L-09' => 14,
            'L-12' => 14,
            'L-13' => 14,
        ];
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
        $cantidadTotalLinea = $cantidadesPorLinea[$linea->nombre] ?? count($this->reductoresPorLinea[$linea->nombre] ?? []);
        $totalGeneral = 0;
        $revisadoGeneral = 0;
        $ultimaRevision = null;

        foreach (array_keys($componentesLinea) as $codigo) {
            $cantidadTotal = $cantidadTotalLinea;
            $mesesPeriodo = $periodicidad[$codigo] ?? 12;
            $fechaLimite = $fechaActual->copy()->subMonths($mesesPeriodo);
            $componenteIds = Componente::where('codigo', 'like', '%' . $codigo . '%')
                ->where('activo', true)
                ->pluck('id')
                ->all();

            if (empty($componenteIds)) {
                $totalGeneral += $cantidadTotal;
                continue;
            }

            $consultaBase = AnalisisLavadora::where('linea_id', $linea->id)
                ->whereIn('componente_id', $componenteIds)
                ->where('created_at', '>=', $fechaLimite)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('historial_restablecimientos')
                        ->whereRaw('analisis_id = analisis_componentes.id');
                });

            $revisados = (clone $consultaBase)
                ->whereNotNull('reductor')
                ->distinct('reductor')
                ->count('reductor');
            $ultimoAnalisis = (clone $consultaBase)
                ->orderByDesc('created_at')
                ->first(['fecha_analisis', 'created_at']);

            $revisadoGeneral += min($revisados, $cantidadTotal);
            $totalGeneral += $cantidadTotal;

            if (
                $ultimoAnalisis
                && (!$ultimaRevision || $ultimoAnalisis->created_at->gt($ultimaRevision->created_at))
            ) {
                $ultimaRevision = $ultimoAnalisis;
            }
        }

        return [
            'total_general' => $totalGeneral,
            'revisado_general' => $revisadoGeneral,
            'porcentaje_general' => $totalGeneral > 0 ? round(($revisadoGeneral / $totalGeneral) * 100, 1) : 0,
            'ultima_revision' => $ultimaRevision?->fecha_analisis
                ? Carbon::parse($ultimaRevision->fecha_analisis)->format('d/m/Y')
                : null,
        ];
    }

    private function generarReporteIndexOptimizado($tipoEquipo, $fechaInicio, $fechaFin)
    {
        if ($tipoEquipo === 'pasteurizadoras') {
            return $this->generarReporteIndexPasteurizadora($fechaInicio, $fechaFin);
        }

        $nombresLineas = $this->getLineasPorTipo($tipoEquipo);
        $lineas = Linea::whereIn('nombre', $nombresLineas)->get();
        $lineaIds = $lineas->pluck('id');
        $reporteGeneral = [];

        $estadoActual = AnalisisLavadora::ultimosPorComponente()
            ->with('componente:id,codigo')
            ->whereIn('linea_id', $lineaIds)
            ->get(['id', 'linea_id', 'componente_id', 'reductor', 'lado', 'estado', 'fecha_analisis', 'evidencia_fotos', 'created_at'])
            ->groupBy('linea_id');

        // 1. Estadísticas de análisis por línea (UNA consulta)
        $estadisticasAnalisis = DB::table('analisis_componentes')
            ->whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->select(
                'linea_id',
                DB::raw('COUNT(*) as total_analisis'),
                DB::raw('COUNT(DISTINCT componente_id) as componentes_revisados'),
                DB::raw('SUM(CASE WHEN estado = "Dañado - Requiere cambio" THEN 1 ELSE 0 END) as componentes_criticos'),
                DB::raw('SUM(CASE WHEN estado IN ("Desgaste moderado", "Desgaste severo") THEN 1 ELSE 0 END) as componentes_severos_moderados'),
                DB::raw('SUM(CASE WHEN estado = "Requiere revisión" THEN 1 ELSE 0 END) as componentes_revision')
            )
            ->groupBy('linea_id')
            ->get()
            ->keyBy('linea_id');

        // 2. Elongaciones por línea (UNA consulta)
        $elongaciones = Elongacion::whereIn('linea', $nombresLineas)
            ->select('linea', 'bombas_porcentaje', 'vapor_porcentaje', 'created_at')
            ->get()
            ->groupBy('linea');

        // 3. Análisis de tendencia (UNA consulta)
        $tendenciaService = app(TendenciaDanosService::class);
        $filasTendenciaPorLinea = $lineas->mapWithKeys(fn (Linea $linea) => [
            $linea->id => $tendenciaService->construirFilasMensuales(
                $linea,
                TendenciaDanosService::TIPO_LAVADORAS,
                12,
                $fechaFin
            ),
        ]);

        // 4. Historial de revisiones (UNA consulta)
        $historicos = HistorialRestablecimiento::whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_restablecimiento', [$fechaInicio, $fechaFin])
            ->select('linea_id', 'fecha_restablecimiento')
            ->orderBy('fecha_restablecimiento', 'desc')
            ->get()
            ->groupBy('linea_id');

        // 5. Últimas revisiones por línea
        $ultimasRevisiones = HistorialRestablecimiento::whereIn('linea_id', $lineaIds)
            ->select('linea_id', 'fecha_restablecimiento')
            ->orderBy('fecha_restablecimiento', 'desc')
            ->get()
            ->unique('linea_id')
            ->keyBy('linea_id');

        $planesPendientes = PlanAccion::whereIn('linea_id', $lineaIds)
            ->where('completado', false)
            ->where(function ($query) {
                $query->where('tipo_equipo', 'lavadora')
                    ->orWhereNull('tipo_equipo');
            })
            ->get(['linea_id'])
            ->groupBy('linea_id');

        $parosPorLinea = Paro::whereIn('linea_id', $lineaIds)
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio)
            ->get(['linea_id'])
            ->groupBy('linea_id');

        $analisis52124Reporte = $tendenciaService->calcularPorLineas(
            $lineas,
            TendenciaDanosService::TIPO_LAVADORAS,
            $fechaFin,
            $tendenciaService->ventanas52124()
        );
        $analisis30147Reporte = $tendenciaService->calcularPorLineas(
            $lineas,
            TendenciaDanosService::TIPO_LAVADORAS,
            $fechaFin,
            $tendenciaService->ventanas30147()
        );

        // Construir reporte para cada línea - VERSIÓN CORREGIDA (ELIMINADAS LAS LÍNEAS SUELTAS)
        foreach ($lineas as $linea) {
            $elongacionesLinea = $elongaciones->get($linea->nombre, collect([]));
            $tendenciasLinea = $filasTendenciaPorLinea->get($linea->id, collect());
            $historicosLinea = $historicos->get($linea->id, collect([]));
            $ultimaRevision = $ultimasRevisiones->get($linea->id);
            $historicoRevisados = $this->calcularHistoricoRevisadosLavadora($linea);
            $registrosLinea = $estadoActual->get($linea->id, collect([]));
            $planesLinea = $planesPendientes->get($linea->id, collect([]));
            $parosLinea = $parosPorLinea->get($linea->id, collect([]));
            $analisis52124Linea = $analisis52124Reporte[$linea->id] ?? $tendenciaService->resumenVacio($tendenciaService->ventanas52124());
            $analisis30147Linea = $analisis30147Reporte[$linea->id] ?? $tendenciaService->resumenVacio($tendenciaService->ventanas30147());
            $ultimaTendencia = $tendenciasLinea->first();
            $ultimoAnalisis = $registrosLinea
                ->sortByDesc(fn ($registro) => (string) ($registro->fecha_analisis ?? $registro->created_at ?? ''))
                ->first();
            $componentesRevisados = $registrosLinea
                ->map(fn ($registro) => $this->normalizarCodigoComponenteLavadora($registro->componente?->codigo, $linea))
                ->filter(fn ($codigo) => $codigo !== 'SIN_COMPONENTE')
                ->unique()
                ->count();

            $ultimaElongacion = $elongacionesLinea
                ->sortByDesc(fn ($registro) => (string) ($registro->created_at ?? ''))
                ->first();
            $promedioBombas = $ultimaElongacion ? (float) ($ultimaElongacion->bombas_porcentaje ?? 0) : 0;
            $promedioVapor = $ultimaElongacion ? (float) ($ultimaElongacion->vapor_porcentaje ?? 0) : 0;

            $maxElongacion = max($promedioBombas, $promedioVapor);

            // ⚠️ IMPORTANTE: Todo debe estar DENTRO de este array
            $reporteGeneral[$linea->id] = [
                'total_analisis' => $registrosLinea->count(),
                'componentes_revisados' => $componentesRevisados,
                'total_componentes' => count($this->componentesPorLinea[$linea->nombre] ?? []),
                'acciones_pendientes' => $registrosLinea->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado))->count(),
                'planes_pendientes' => $planesLinea->count(),
                'paros_count' => $parosLinea->count(),
                'evidencias_count' => $registrosLinea->sum(fn ($registro) => $this->contarEvidenciasReporte($registro->evidencia_fotos ?? null)),
                'ultimo_analisis' => $ultimoAnalisis?->fecha_analisis
                    ? Carbon::parse($ultimoAnalisis->fecha_analisis)->format('d/m/Y')
                    : null,
                'elongacion_max' => $maxElongacion,
                'promedio_bombas' => $promedioBombas,
                'promedio_vapor' => $promedioVapor,
                'analisis_tendencia_count' => $tendenciasLinea->count(),
                'total_danos_4' => $ultimaTendencia->total_danos_4_semanas ?? 0,
                'analisis_52124' => $analisis52124Linea,
                'analisis_30147' => $analisis30147Linea,
                'historicos' => $historicoRevisados['revisado_general'],
                'historico_revisados' => $historicoRevisados['revisado_general'],
                'historico_total' => $historicoRevisados['total_general'],
                'historico_porcentaje' => $historicoRevisados['porcentaje_general'],
                'ultima_revision_historico' => $historicoRevisados['ultima_revision'],
                'ultima_revision' => $ultimaRevision
                    ? Carbon::parse($ultimaRevision->fecha_restablecimiento)->format('d/m/Y')
                    : null,
                'componentes_criticos' => $registrosLinea->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado))->count(),
                'componentes_severos_moderados' => $registrosLinea->filter(fn ($registro) => $this->esEstadoDesgasteReporte($registro->estado))->count(),
                'componentes_revision' => $registrosLinea->filter(fn ($registro) => $this->esEstadoRevisionReporte($registro->estado))->count(),
                'reductores_count' => count($this->reductoresPorLinea[$linea->nombre] ?? []),
                'estado_general' => $this->determinarEstadoGeneralDesdeRegistros($registrosLinea)
            ];
        }

        return $reporteGeneral;
    }

    public function show(Request $request)
    {
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $fechaInicio = Carbon::parse($request->get('fecha_inicio', Carbon::now()->subMonth()))->startOfDay();
        $fechaFin = Carbon::parse($request->get('fecha_fin', Carbon::now()))->endOfDay();

        // 👇 obtener lineaId desde GET
        $lineaFiltro = $request->route('lineaId')
            ?? $request->get('lineaId', $request->get('linea_id', $request->get('linea')));

        $lineaId = is_numeric($lineaFiltro)
            ? (int) $lineaFiltro
            : ($lineaFiltro ? Linea::where('nombre', $lineaFiltro)->value('id') : null);

        if ($lineaId) {
            return $this->mostrarReporteLinea($lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        }

        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $reporte = $this->generarReporteGeneralOptimizado($tipoEquipo, $fechaInicio, $fechaFin);

        return view('reportes.show', compact('reporte', 'tipoEquipo', 'fechaInicio', 'fechaFin', 'lineaId'));
    }

    private function mostrarReporteLinea($lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
    {
        $linea = Linea::findOrFail($lineaId);
        $tipoEquipo = $this->tipoEquipoDeLinea($linea) ?? $this->normalizarTipoEquipo($tipoEquipo);

        $this->ensureCanAccessTipoEquipo($tipoEquipo);
        $this->ensureCanAccessLinea($linea);

        $lineaId = $linea->id;
        $reporte = $this->getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo);
        
        return view('reportes.show', compact('reporte', 'tipoEquipo', 'fechaInicio', 'fechaFin', 'lineaId'));
    }

  private function generarReporteGeneralOptimizado($tipoEquipo, $fechaInicio, $fechaFin)
{
    if ($tipoEquipo === 'pasteurizadoras') {
        return $this->generarReporteGeneralPasteurizadora($fechaInicio, $fechaFin);
    }

    $lineas = Linea::whereIn('nombre', $this->getLineasPorTipo($tipoEquipo))
        ->orderBy('nombre')
        ->get();

    return [
        'lineas' => $lineas
            ->map(fn ($linea) => $this->getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo))
            ->all(),
    ];

    $nombresLineas = $this->getLineasPorTipo($tipoEquipo);
    $lineas = Linea::whereIn('nombre', $nombresLineas)->get();
    $lineaIds = $lineas->pluck('id');

    /* =========================
       TODOS LOS ANALISIS CON DETALLES COMPLETOS
    ========================= */
    $todosAnalisis = DB::table('analisis_componentes')
        ->join('componentes', 'analisis_componentes.componente_id', '=', 'componentes.id')
        ->join('lineas', 'analisis_componentes.linea_id', '=', 'lineas.id')
        ->select(
            'analisis_componentes.id',
            'analisis_componentes.linea_id',
            'analisis_componentes.estado',
            'analisis_componentes.fecha_analisis',
            'analisis_componentes.reductor',
            'analisis_componentes.actividad',
            'analisis_componentes.numero_orden',
            'analisis_componentes.lado',
            'analisis_componentes.evidencia_fotos',
            'analisis_componentes.created_at',
            'componentes.nombre as componente_nombre',
            'componentes.codigo as componente_codigo',
            'lineas.nombre as linea_nombre'
        )
        ->whereIn('analisis_componentes.linea_id', $lineaIds)
        ->whereBetween('analisis_componentes.fecha_analisis', [$fechaInicio, $fechaFin])
        ->orderBy('analisis_componentes.fecha_analisis', 'desc')
        ->get()
        ->groupBy('linea_id');

    /* =========================
       ELONGACIONES
    ========================= */
    $elongaciones = Elongacion::whereIn('linea', $nombresLineas)
        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('linea');

    /* =========================
       COMPONENTES POR LÍNEA (del array definido)
    ========================= */
    $componentesPorLineaArray = [
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

    // Reductores por línea
    $reductoresPorLineaArray = [
        'L-04' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca', 'Reductor Principal'],
        'L-05' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Principal', 'Reductor Loca'],
        'L-06' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22', 'Reductor Principal'],
        'L-07' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22', 'Reductor Principal'],
        'L-08' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca'],
        'L-09' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                  'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                  'Reductor 18', 'Reductor 19', 'Reductor Loca', 'Reductor Principal'],
        'L-12' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal'],
        'L-13' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                  'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                  'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal']
    ];

    /* =========================
       REPORTE FINAL CON ANÁLISIS DETALLADOS
    ========================= */
    $reportesLineas = [];

    foreach ($lineas as $linea) {
        $analisisLinea = $todosAnalisis->get($linea->id, collect());
        
        // Obtener componentes específicos para esta línea
        $componentesLinea = collect();
        if (isset($componentesPorLineaArray[$linea->nombre])) {
            foreach ($componentesPorLineaArray[$linea->nombre] as $codigo => $nombre) {
                $componentesLinea->push((object)[
                    'id' => $codigo,
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                ]);
            }
        }

        // Obtener reductores específicos para esta línea
        $reductoresLinea = collect();
        if (isset($reductoresPorLineaArray[$linea->nombre])) {
            $reductoresLinea = collect($reductoresPorLineaArray[$linea->nombre]);
        }

        // Procesar análisis y agruparlos por reductor y componente
        $analisisAgrupados = [];
        foreach ($analisisLinea as $item) {
            $reductor = $item->reductor;
            $componenteCodigo = $item->componente_codigo;
            
            if (!isset($analisisAgrupados[$reductor][$componenteCodigo])) {
                $analisisAgrupados[$reductor][$componenteCodigo] = [];
            }
            
            // Procesar imágenes
            $imagenes = $item->evidencia_fotos;
            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true) ?? [];
            } elseif (is_array($imagenes)) {
                $imagenes = $imagenes;
            } else {
                $imagenes = [];
            }
            
            $analisisAgrupados[$reductor][$componenteCodigo][] = [
                'id' => $item->id,
                'fecha_analisis' => Carbon::parse($item->fecha_analisis)->format('Y-m-d'),
                'fecha_analisis_formateada' => Carbon::parse($item->fecha_analisis)->format('d/m/Y'),
                'estado' => $item->estado,
                'reductor' => $item->reductor,
                'actividad' => $item->actividad,
                'numero_orden' => $item->numero_orden,
                'lado' => $item->lado,
                'imagenes' => $imagenes,
                'componente' => [
                    'nombre' => $item->componente_nombre,
                    'codigo' => $item->componente_codigo
                ],
                'created_at' => $item->created_at,
                'edit_url' => route('analisis-lavadora.edit', ['analisislavadora' => $item->id]),
                'is_new' => $item->created_at && Carbon::parse($item->created_at)->gt(now()->subDays(3))
            ];
        }

        $elongacionesLinea = $elongaciones->get($linea->nombre, collect());

        $reportesLineas[] = [
            'linea' => $linea,
            'resumen' => [
                'total_analisis' => $analisisLinea->count(),
                'componentes_revisados' => $analisisLinea->pluck('componente_codigo')->unique()->count(),
                'componentes_criticos' => $analisisLinea->where('estado', 'Dañado - Requiere cambio')->count(),
                'componentes_severos_moderados' => $analisisLinea->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])->count(),
                'componentes_revision' => $analisisLinea->where('estado', 'Requiere revisión')->count(),
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo)
            ],
            'componentes_lista' => $componentesLinea,
            'reductores_lista' => $reductoresLinea,
            'analisis_agrupados' => $analisisAgrupados,
            'elongaciones' => $elongacionesLinea,
        ];
    }

    return ['lineas' => $reportesLineas];
}

    private function getReporteDetalladoLinea($linea, $fechaInicio, $fechaFin, $tipoEquipo)
    {
        if ($tipoEquipo === 'pasteurizadoras') {
            return $this->getReporteDetalladoPasteurizadora($linea, $fechaInicio, $fechaFin);
        }

        // 1. Obtener análisis del período (optimizado)
        $componentesLista = $this->getComponentesListaLavadora($linea);
        $reductoresLista = collect($this->reductoresPorLinea[$linea->nombre] ?? []);

        $analisis = AnalisisLavadora::where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->with(['componente:id,nombre,codigo'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get();

        $analisisHistorico = AnalisisLavadora::where('linea_id', $linea->id)
            ->with(['componente:id,nombre,codigo'])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $elongaciones = Elongacion::where('linea', $linea->nombre)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderByDesc('created_at')
            ->get();

        $tendenciaService = app(TendenciaDanosService::class);
        $analisisTendencia = $tendenciaService->construirFilasMensuales(
            $linea,
            TendenciaDanosService::TIPO_LAVADORAS,
            12,
            $fechaFin
        );
        $analisis52124Reporte = $tendenciaService->calcularParaLinea(
            $linea,
            TendenciaDanosService::TIPO_LAVADORAS,
            $fechaFin,
            $tendenciaService->ventanas52124()
        );
        $analisis30147Reporte = $tendenciaService->calcularParaLinea(
            $linea,
            TendenciaDanosService::TIPO_LAVADORAS,
            $fechaFin,
            $tendenciaService->ventanas30147()
        );

        $componentes = $this->procesarComponentesLavadora($analisisHistorico, $linea, $analisis);
        $reductores = $this->procesarReductoresLavadora($analisis, $elongaciones);
        $analisisAgrupados = $this->agruparAnalisisLavadora($analisis, $linea);
        $componentesRevisados = $analisis
            ->map(fn ($registro) => $this->normalizarCodigoComponenteLavadora($registro->componente?->codigo, $linea))
            ->filter(fn ($codigo) => $codigo !== 'SIN_COMPONENTE')
            ->unique()
            ->count();

        return [
            'tipo_equipo' => 'lavadoras',
            'linea' => $linea,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'resumen' => [
                'total_analisis' => $analisis->count(),
                'componentes_revisados' => $componentesRevisados,
                'total_componentes' => $componentesLista->count(),
                'componentes_criticos' => $analisis->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado))->count(),
                'componentes_severos_moderados' => $analisis->filter(fn ($registro) => $this->esEstadoDesgasteReporte($registro->estado))->count(),
                'componentes_revision' => $analisis->filter(fn ($registro) => $this->esEstadoRevisionReporte($registro->estado))->count(),
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo),
                'estado_general' => $this->determinarEstadoGeneralDesdeRegistros($analisis),
            ],
            'analisis' => $analisis,
            'analisis_historico' => $analisisHistorico,
            'elongaciones' => $elongaciones,
            'analisis_tendencia' => $analisisTendencia,
            'analisis_52124' => $analisis52124Reporte,
            'analisis_30147' => $analisis30147Reporte,
            'componentes' => $componentes,
            'reductores' => $reductores,
            'componentes_lista' => $componentesLista,
            'reductores_lista' => $reductoresLista,
            'analisis_agrupados' => $analisisAgrupados,
            'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo),
        ];

        // 2. Obtener estadísticas en una sola consulta
        $estadisticas = DB::table('analisis_componentes')
            ->where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->selectRaw('
                COUNT(*) as total_analisis,
                COUNT(DISTINCT componente_id) as componentes_revisados,
                SUM(CASE WHEN estado = "Dañado - Requiere cambio" THEN 1 ELSE 0 END) as componentes_criticos,
                SUM(CASE WHEN estado IN ("Desgaste moderado", "Desgaste severo") THEN 1 ELSE 0 END) as componentes_severos_moderados,
                SUM(CASE WHEN estado = "Requiere revisión" THEN 1 ELSE 0 END) as componentes_revision
            ')
            ->first();

        // 3. Elongaciones (optimizado con índices)
        $elongaciones = Elongacion::where('linea', $linea->nombre)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderBy('created_at', 'desc')
            ->select(['created_at', 'hodometro', 'bombas_porcentaje', 'vapor_porcentaje'])
            ->get();

        // 4. Análisis de tendencia (optimizado)
        $analisisTendencia = collect();

        // 5. Procesar componentes
        $componentes = $this->procesarComponentesLavadora($analisis, $linea);
        
        // 6. Procesar reductores
        $reductores = $this->procesarReductoresLavadora($analisis, $elongaciones);

        return [
            'linea' => $linea,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'resumen' => [
                'total_analisis' => $estadisticas->total_analisis ?? 0,
                'componentes_revisados' => $estadisticas->componentes_revisados ?? 0,
                'componentes_criticos' => $estadisticas->componentes_criticos ?? 0,
                'componentes_severos_moderados' => $estadisticas->componentes_severos_moderados ?? 0,
                'componentes_revision' => $estadisticas->componentes_revision ?? 0,
                'componentes_definidos' => $this->getComponentesDefinidos($tipoEquipo)
            ],
            'analisis' => $analisis,
            'elongaciones' => $elongaciones,
            'analisis_tendencia' => $analisisTendencia,
            'componentes' => $componentes,
            'reductores' => $reductores
        ];
    }

    private function calcularSeguimientoActualPasteurizadora($lineas): array
    {
        $lineas = collect($lineas)->filter();
        $lineaIds = $lineas->pluck('id')->all();

        if (empty($lineaIds)) {
            return [];
        }

        $registrosPorLinea = AnalisisPasteurizadora::queryForArea(AnalisisPasteurizadora::AREA_MECANICA)
            ->whereIn('linea_id', $lineaIds)
            ->where('resuelto_por_cambio', false)
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('linea_id');

        $seguimiento = [];

        foreach ($lineas as $linea) {
            $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
            $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
            $registrosLinea = $registrosPorLinea->get($linea->id, collect());
            $modulos = [];
            $celdasTotales = 0;
            $celdasCompletadas = 0;

            for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
                $componentesModulo = 0;
                $componentesCompletados = 0;

                foreach ($componentes as $codigo => $config) {
                    if (
                        AnalisisPasteurizadora::esBrazoTorsion($codigo)
                        && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                    ) {
                        continue;
                    }

                    $totalComponentes = (int) ($config['cantidad'] ?? 0);
                    $registrosComponente = $registrosLinea
                        ->where('modulo', $modulo)
                        ->where('componente', $codigo)
                        ->values();
                    $resumenCiclo = AnalisisPasteurizadora::buildResumenCicloComponenteFromCollection(
                        $registrosComponente,
                        $totalComponentes
                    );
                    $estadoVisible = $resumenCiclo['resumen_visible'] ?? [];
                    $completado = (bool) ($estadoVisible['completado'] ?? false);

                    $componentesModulo++;
                    $celdasTotales++;

                    if ($completado) {
                        $componentesCompletados++;
                        $celdasCompletadas++;
                    }
                }

                $modulos[$modulo] = [
                    'total' => $componentesModulo,
                    'completados' => $componentesCompletados,
                    'completado' => $componentesModulo > 0 && $componentesCompletados === $componentesModulo,
                ];
            }

            $seguimiento[$linea->id] = [
                'resumen' => [
                    'total' => $celdasTotales,
                    'completados' => $celdasCompletadas,
                    'pendientes' => max(0, $celdasTotales - $celdasCompletadas),
                    'porcentaje' => $celdasTotales > 0 ? round(($celdasCompletadas / $celdasTotales) * 100) : 0,
                    'completado' => $celdasTotales > 0 && $celdasCompletadas === $celdasTotales,
                ],
                'modulos' => $modulos,
            ];
        }

        return $seguimiento;
    }

    private function generarReporteIndexPasteurizadora($fechaInicio, $fechaFin)
    {
        $lineas = Linea::whereIn('nombre', $this->pasteurizadoras)
            ->orderBy('nombre')
            ->get();
        $lineaIds = $lineas->pluck('id');

        $registros = AnalisisPasteurizadora::queryForArea(AnalisisPasteurizadora::AREA_MECANICA)
            ->whereIn('linea_id', $lineaIds)
            ->where('resuelto_por_cambio', false)
            ->get([
                'id',
                'linea_id',
                'modulo',
                'nivel',
                'componente',
                'lado',
                'fecha_analisis',
                'estado',
                'cantidad_componentes_revisados',
                'total_componentes',
                'evidencia_fotos',
                'resuelto_por_cambio',
                'created_at',
            ])
            ->groupBy('linea_id');

        $tendenciaService = app(TendenciaDanosService::class);
        $filasTendenciaPorLinea = $lineas->mapWithKeys(fn (Linea $linea) => [
            $linea->id => $tendenciaService->construirFilasMensuales(
                $linea,
                TendenciaDanosService::TIPO_PASTEURIZADORAS,
                12,
                $fechaFin
            ),
        ]);

        $ultimasRevisiones = AnalisisPasteurizadora::queryForArea(AnalisisPasteurizadora::AREA_MECANICA)
            ->whereIn('linea_id', $lineaIds)
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get(['linea_id', 'fecha_analisis'])
            ->unique('linea_id')
            ->keyBy('linea_id');

        $planesPendientes = PlanAccion::whereIn('linea_id', $lineaIds)
            ->where('completado', false)
            ->where('tipo_equipo', 'pasteurizadora')
            ->get(['linea_id'])
            ->groupBy('linea_id');

        $parosPorLinea = Paro::whereIn('linea_id', $lineaIds)
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio)
            ->get(['linea_id'])
            ->groupBy('linea_id');

        $seguimientoActual = $this->calcularSeguimientoActualPasteurizadora($lineas);
        $analisis52124Reporte = $tendenciaService->calcularPorLineas(
            $lineas,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            $fechaFin,
            $tendenciaService->ventanas52124()
        );
        $analisis30147Reporte = $tendenciaService->calcularPorLineas(
            $lineas,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            $fechaFin,
            $tendenciaService->ventanas30147()
        );

        $reporteGeneral = [];

        foreach ($lineas as $linea) {
            $registrosLinea = $registros->get($linea->id, collect());
            $tendenciasLinea = $filasTendenciaPorLinea->get($linea->id, collect());
            $componentesDefinidos = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
            $avance = $this->calcularAvanceModulosPasteurizadora($linea, $registrosLinea);
            $ultimaRevision = $ultimasRevisiones->get($linea->id);
            $planesLinea = $planesPendientes->get($linea->id, collect());
            $parosLinea = $parosPorLinea->get($linea->id, collect());
            $seguimientoLinea = $seguimientoActual[$linea->id]['resumen'] ?? [];
            $analisis52124Linea = $analisis52124Reporte[$linea->id] ?? $tendenciaService->resumenVacio($tendenciaService->ventanas52124());
            $analisis30147Linea = $analisis30147Reporte[$linea->id] ?? $tendenciaService->resumenVacio($tendenciaService->ventanas30147());
            $ultimaTendencia = $tendenciasLinea->first();

            $reporteGeneral[$linea->id] = [
                'total_analisis' => $registrosLinea->count(),
                'componentes_revisados' => $registrosLinea->pluck('componente')->filter()->unique()->count(),
                'total_componentes' => count($componentesDefinidos),
                'componentes_criticos' => $registrosLinea
                    ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
                    ->count(),
                'componentes_severos_moderados' => $registrosLinea
                    ->filter(fn ($registro) => AnalisisPasteurizadora::esEstadoDesgaste($registro->estado))
                    ->count(),
                'componentes_revision' => $registrosLinea
                    ->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)
                    ->count(),
                'acciones_pendientes' => $registrosLinea
                    ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
                    ->count(),
                'planes_pendientes' => $planesLinea->count(),
                'paros_count' => $parosLinea->count(),
                'evidencias_count' => $registrosLinea->sum(fn ($registro) => $this->contarEvidenciasReporte($registro->evidencia_fotos ?? null)),
                'piezas_revisadas' => $registrosLinea->sum(fn ($registro) => (int) ($registro->cantidad_componentes_revisados ?? 0)),
                'analisis_tendencia_count' => $tendenciasLinea->count(),
                'total_danos_4' => $ultimaTendencia->total_danos_4_semanas ?? 0,
                'analisis_52124' => $analisis52124Linea,
                'analisis_30147' => $analisis30147Linea,
                'historicos' => $registrosLinea->count(),
                'ultima_revision' => $ultimaRevision
                    ? Carbon::parse($ultimaRevision->fecha_analisis)->format('d/m/Y')
                    : null,
                'reductores_count' => 0,
                'modulos_configurados' => $avance['total_modulos'],
                'modulos_con_analisis' => $avance['modulos_con_analisis'],
                'avance_historico_porcentaje' => $seguimientoLinea['porcentaje'] ?? $avance['porcentaje'],
                'celdas_totales' => $seguimientoLinea['total'] ?? null,
                'celdas_completadas' => $seguimientoLinea['completados'] ?? null,
                'celdas_pendientes' => $seguimientoLinea['pendientes'] ?? null,
                'niveles_count' => count(AnalisisPasteurizadora::NIVELES),
                'lados_count' => count(AnalisisPasteurizadora::LADOS),
                'estado_general' => $this->determinarEstadoGeneralPasteurizadora($registrosLinea),
            ];
        }

        return $reporteGeneral;
    }

    private function generarReporteGeneralPasteurizadora($fechaInicio, $fechaFin)
    {
        $lineas = Linea::whereIn('nombre', $this->pasteurizadoras)
            ->orderBy('nombre')
            ->get();

        return [
            'lineas' => $lineas
                ->map(fn ($linea) => $this->getReporteDetalladoPasteurizadora($linea, $fechaInicio, $fechaFin))
                ->all(),
        ];
    }

    private function getReporteDetalladoPasteurizadora($linea, $fechaInicio, $fechaFin)
    {
        $analisis = AnalisisPasteurizadora::query()
            ->with(['usuario:id,name', 'linea:id,nombre'])
            ->where('linea_id', $linea->id)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get();

        $analisisHistorico = AnalisisPasteurizadora::query()
            ->with(['usuario:id,name', 'linea:id,nombre'])
            ->where('linea_id', $linea->id)
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $tendenciaService = app(TendenciaDanosService::class);
        $analisisTendencia = $tendenciaService->construirFilasMensuales(
            $linea,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            12,
            $fechaFin
        );
        $analisis52124Reporte = $tendenciaService->calcularParaLinea(
            $linea,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            $fechaFin,
            $tendenciaService->ventanas52124()
        );
        $analisis30147Reporte = $tendenciaService->calcularParaLinea(
            $linea,
            TendenciaDanosService::TIPO_PASTEURIZADORAS,
            $fechaFin,
            $tendenciaService->ventanas30147()
        );

        $componentes = $this->procesarComponentesPasteurizadora($linea, $analisisHistorico, $analisis);
        $avance = $this->calcularAvanceModulosPasteurizadora($linea, $analisis);
        $componentesDefinidos = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $criticos = $analisis
            ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
            ->count();

        return [
            'tipo_equipo' => 'pasteurizadoras',
            'linea' => $linea,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'resumen' => [
                'total_analisis' => $analisis->count(),
                'componentes_revisados' => $analisis->pluck('componente')->filter()->unique()->count(),
                'total_componentes' => count($componentesDefinidos),
                'componentes_criticos' => $criticos,
                'componentes_severos_moderados' => $analisis
                    ->filter(fn ($registro) => AnalisisPasteurizadora::esEstadoDesgaste($registro->estado))
                    ->count(),
                'componentes_revision' => $analisis
                    ->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)
                    ->count(),
                'piezas_revisadas' => $analisis->sum(fn ($registro) => (int) ($registro->cantidad_componentes_revisados ?? 0)),
                'total_modulos' => $avance['total_modulos'],
                'modulos_con_analisis' => $avance['modulos_con_analisis'],
                'avance_historico_porcentaje' => $avance['porcentaje'],
                'componentes_definidos' => $this->getComponentesDefinidos('pasteurizadoras'),
                'estado_general' => $this->determinarEstadoGeneralPasteurizadora($analisis),
            ],
            'analisis' => $analisis,
            'analisis_historico' => $analisisHistorico,
            'analisis_tendencia' => $analisisTendencia,
            'analisis_52124' => $analisis52124Reporte,
            'analisis_30147' => $analisis30147Reporte,
            'componentes' => $componentes,
            'modulos' => $avance['modulos'],
        ];
    }

    private function procesarComponentesPasteurizadora($linea, $analisisHistorico, $analisisPeriodo = null)
    {
        $componentesLinea = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
        $resultado = collect();
        $analisisHistorico = collect($analisisHistorico);
        $analisisPeriodo = collect($analisisPeriodo ?? $analisisHistorico);

        foreach ($componentesLinea as $codigo => $config) {
            $analisisComponente = $analisisHistorico
                ->where('componente', $codigo)
                ->values();
            $analisisComponentePeriodo = $analisisPeriodo
                ->where('componente', $codigo)
                ->values();
            $ultimoAnalisis = $analisisComponente->first();
            $modulosAplicables = AnalisisPasteurizadora::esBrazoTorsion($codigo)
                ? AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
                : $totalModulos;
            $cantidadPorSeleccion = (int) ($config['cantidad'] ?? 0);
            $totalConfigurado = $cantidadPorSeleccion
                * max(1, $modulosAplicables)
                * max(1, count(AnalisisPasteurizadora::NIVELES))
                * max(1, count(AnalisisPasteurizadora::LADOS));
            $revisadasPeriodo = $analisisComponentePeriodo->sum(fn ($registro) => (int) ($registro->cantidad_componentes_revisados ?? 0));
            $revisadasHistorico = $analisisComponente->sum(fn ($registro) => (int) ($registro->cantidad_componentes_revisados ?? 0));

            $resultado->push([
                'codigo' => $codigo,
                'nombre' => $config['nombre'] ?? $codigo,
                'cantidad' => $cantidadPorSeleccion,
                'modulos_aplicables' => $modulosAplicables,
                'total_configurado' => $totalConfigurado,
                'cantidad_revisada' => $revisadasPeriodo,
                'cantidad_revisada_historico' => $revisadasHistorico,
                'porcentaje' => $totalConfigurado > 0 ? min(100, round(($revisadasPeriodo / $totalConfigurado) * 100, 1)) : 0,
                'total_analisis' => $analisisComponente->count(),
                'total_analisis_periodo' => $analisisComponentePeriodo->count(),
                'ultimo_analisis' => $ultimoAnalisis,
                'ultimo_estado' => $ultimoAnalisis?->estado,
                'ultimo_modulo' => $ultimoAnalisis?->modulo,
                'ultimo_nivel' => $ultimoAnalisis?->nivel,
                'ultimo_lado' => $ultimoAnalisis?->lado,
            ]);
        }

        return $resultado;
    }

    private function calcularAvanceModulosPasteurizadora($linea, $registros)
    {
        $registros = collect($registros);
        $componentesLinea = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
        $totalModulos = AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
        $modulos = collect();
        $modulosConAnalisis = 0;
        $totalComponentesModulo = 0;
        $componentesRevisadosModulo = 0;

        for ($modulo = 1; $modulo <= $totalModulos; $modulo++) {
            $componentesAplicables = collect($componentesLinea)
                ->reject(function ($config, $codigo) use ($linea, $modulo) {
                    return AnalisisPasteurizadora::esBrazoTorsion($codigo)
                        && $modulo > AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre);
                });
            $registrosModulo = $registros->where('modulo', $modulo)->values();
            $componentesConAnalisis = $registrosModulo->pluck('componente')->filter()->unique()->count();
            $totalComponentes = $componentesAplicables->count();
            $componentesRevisados = min($componentesConAnalisis, $totalComponentes);

            if ($registrosModulo->isNotEmpty()) {
                $modulosConAnalisis++;
            }

            $totalComponentesModulo += $totalComponentes;
            $componentesRevisadosModulo += $componentesRevisados;

            $modulos->push([
                'numero' => $modulo,
                'total_componentes' => $totalComponentes,
                'componentes_revisados' => $componentesRevisados,
                'porcentaje' => $totalComponentes > 0 ? round(($componentesRevisados / $totalComponentes) * 100, 1) : 0,
                'total_analisis' => $registrosModulo->count(),
                'criticos' => $registrosModulo
                    ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
                    ->count(),
                'ultima_revision' => optional($registrosModulo->sortByDesc('fecha_analisis')->first())->fecha_analisis,
                'niveles' => collect(AnalisisPasteurizadora::NIVELES)
                    ->mapWithKeys(fn ($nivel) => [$nivel => $registrosModulo->where('nivel', $nivel)->count()])
                    ->all(),
                'lados' => collect(AnalisisPasteurizadora::LADOS)
                    ->mapWithKeys(fn ($lado) => [$lado => $registrosModulo->where('lado', $lado)->count()])
                    ->all(),
            ]);
        }

        return [
            'modulos' => $modulos,
            'total_modulos' => $totalModulos,
            'modulos_con_analisis' => $modulosConAnalisis,
            'porcentaje' => $totalComponentesModulo > 0
                ? round(($componentesRevisadosModulo / $totalComponentesModulo) * 100, 1)
                : 0,
        ];
    }

    private function determinarEstadoGeneralPasteurizadora($registros)
    {
        $registros = collect($registros);

        $criticos = $registros
            ->filter(fn ($registro) => !$registro->resuelto_por_cambio && AnalisisPasteurizadora::esEstadoDanado($registro->estado))
            ->count();
        $desgaste = $registros
            ->filter(fn ($registro) => AnalisisPasteurizadora::esEstadoDesgaste($registro->estado))
            ->count();
        $revision = $registros
            ->where('estado', AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)
            ->count();

        if ($criticos > 0) {
            return ['texto' => 'CRITICO', 'color' => 'red'];
        }

        if ($desgaste > 0) {
            return ['texto' => 'SEVERO / MODERADO', 'color' => 'orange'];
        }

        if ($revision > 0) {
            return ['texto' => 'REQUIERE REVISION', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    private function getComponentesListaLavadora($linea)
    {
        return collect($this->componentesPorLinea[$linea->nombre] ?? [])
            ->map(function ($nombre, $codigo) {
                return (object) [
                    'id' => $codigo,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                ];
            })
            ->values();
    }

    private function normalizarCodigoComponenteLavadora(?string $codigo, $linea = null): string
    {
        if (!$codigo) {
            return 'SIN_COMPONENTE';
        }

        if ($linea) {
            $componentesLinea = $this->componentesPorLinea[$linea->nombre] ?? [];
        } else {
            $componentesLinea = [];

            foreach ($this->componentesPorLinea as $componentes) {
                $componentesLinea = array_merge($componentesLinea, $componentes);
            }
        }

        foreach (array_keys($componentesLinea) as $codigoBase) {
            if ($codigo === $codigoBase || str_ends_with($codigo, '_' . $codigoBase)) {
                return $codigoBase;
            }
        }

        return $codigo;
    }

    private function agruparAnalisisLavadora($analisis, $linea = null)
    {
        $agrupados = [];

        foreach (collect($analisis) as $item) {
            $reductor = $item->reductor ?: 'Sin reductor';
            $componenteCodigo = $this->normalizarCodigoComponenteLavadora(
                $item->componente?->codigo,
                $linea
            );

            if (!isset($agrupados[$reductor][$componenteCodigo])) {
                $agrupados[$reductor][$componenteCodigo] = [];
            }

            $imagenes = $item->evidencia_fotos;

            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true) ?? [];
            } elseif (!is_array($imagenes)) {
                $imagenes = [];
            }

            $agrupados[$reductor][$componenteCodigo][] = [
                'id' => $item->id,
                'fecha_analisis' => optional($item->fecha_analisis)->format('Y-m-d'),
                'fecha_analisis_formateada' => optional($item->fecha_analisis)->format('d/m/Y'),
                'estado' => $item->estado,
                'reductor' => $item->reductor,
                'actividad' => $item->actividad,
                'numero_orden' => $item->numero_orden,
                'lado' => $item->lado,
                'imagenes' => $imagenes,
                'componente' => [
                    'nombre' => $item->componente?->nombre,
                    'codigo' => $item->componente?->codigo,
                ],
                'created_at' => $item->created_at,
                'edit_url' => route('analisis-lavadora.edit', ['analisislavadora' => $item->id]),
                'is_new' => $item->created_at && $item->created_at->gt(now()->subDays(3)),
            ];
        }

        return $agrupados;
    }

    private function normalizarEstadoReporte($estado): string
    {
        $estado = strtolower((string) $estado);

        return strtr($estado, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);
    }

    private function esEstadoDanadoReporte($estado): bool
    {
        return str_contains($this->normalizarEstadoReporte($estado), 'requiere cambio');
    }

    private function esEstadoDesgasteReporte($estado): bool
    {
        return str_contains($this->normalizarEstadoReporte($estado), 'desgaste');
    }

    private function esEstadoRevisionReporte($estado): bool
    {
        $estado = $this->normalizarEstadoReporte($estado);

        return str_contains($estado, 'revision') || str_contains($estado, 'revisi');
    }

    private function determinarEstadoGeneralDesdeRegistros($registros)
    {
        $registros = collect($registros);

        if ($registros->filter(fn ($registro) => $this->esEstadoDanadoReporte($registro->estado ?? null))->isNotEmpty()) {
            return ['texto' => 'CRITICO', 'color' => 'red'];
        }

        if ($registros->filter(fn ($registro) => $this->esEstadoDesgasteReporte($registro->estado ?? null))->isNotEmpty()) {
            return ['texto' => 'SEVERO / MODERADO', 'color' => 'orange'];
        }

        if ($registros->filter(fn ($registro) => $this->esEstadoRevisionReporte($registro->estado ?? null))->isNotEmpty()) {
            return ['texto' => 'REQUIERE REVISION', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    private function procesarComponentesLavadora($analisisHistorico, $linea, $analisisPeriodo = null)
    {
        $componentesLinea = $this->componentesPorLinea[$linea->nombre] ?? [];
        $resultado = [];
        $analisisHistorico = collect($analisisHistorico);
        $analisisPeriodo = collect($analisisPeriodo ?? $analisisHistorico);
        
        foreach ($componentesLinea as $codigo => $nombre) {
            $analisisComponente = $analisisHistorico->filter(function ($item) use ($codigo, $linea) {
                return $this->normalizarCodigoComponenteLavadora($item->componente?->codigo, $linea) === $codigo;
            });
            $analisisComponentePeriodo = $analisisPeriodo->filter(function ($item) use ($codigo, $linea) {
                return $this->normalizarCodigoComponenteLavadora($item->componente?->codigo, $linea) === $codigo;
            });
            
            $ultimoAnalisis = $analisisComponente->first();
            
            $resultado[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'componente_id' => $ultimoAnalisis?->componente_id,
                'total_analisis' => $analisisComponente->count(),
                'total_analisis_periodo' => $analisisComponentePeriodo->count(),
                'ultimo_analisis' => $ultimoAnalisis,
                'ultimo_estado' => $ultimoAnalisis?->estado,
                'ultimo_reductor' => $ultimoAnalisis?->reductor,
                'ultimo_lado' => $ultimoAnalisis?->lado,
            ];
        }
        
        return $resultado;
    }

    private function procesarReductoresLavadora($analisis, $elongaciones)
    {
        // CORRECCIÓN: Asegurar que $analisis sea una colección
        $analisis = collect($analisis);
        
        // CORRECCIÓN: Filtrar items que tengan reductor antes de agrupar
        $analisisConReductor = $analisis->filter(function($item) {
            return !empty($item->reductor);
        });
        
        $reductoresAgrupados = $analisisConReductor->groupBy('reductor');
        $resultado = [];
        
        foreach ($reductoresAgrupados as $nombre => $analisisReductor) {
            $ultimoAnalisis = $analisisReductor->first();
            $ultimaElongacion = $elongaciones->isNotEmpty() ? $elongaciones->first() : null;
            
            $resultado[] = [
                'nombre' => $nombre,
                'total_analisis' => $analisisReductor->count(),
                'ultima_fecha' => $ultimoAnalisis?->fecha_analisis,
                'ultima_elongacion' => $ultimaElongacion ? ($ultimaElongacion->bombas_porcentaje ?? 0) : 0
            ];
        }
        
        return $resultado;
    }

    private function getComponentesDefinidos($tipoEquipo)
    {
        if ($tipoEquipo === 'lavadoras') {
            $todos = [];
            foreach ($this->componentesPorLinea as $componentes) {
                $todos = array_merge($todos, array_values($componentes));
            }
            return $todos;
        }
        $todos = [];
        foreach ($this->pasteurizadoras as $lineaNombre) {
            foreach (AnalisisPasteurizadora::getComponentesPorLinea($lineaNombre) as $componente) {
                $todos[] = $componente['nombre'] ?? null;
            }
        }

        return collect($todos)->filter()->unique()->values()->all();
    }

    private function getLineasPorTipo($tipo)
    {
        return $this->normalizarTipoEquipo($tipo) === 'lavadoras' ? $this->lavadoras : $this->pasteurizadoras;
    }

    private function tipoEquipoDeLinea(Linea $linea): ?string
    {
        if (in_array($linea->nombre, $this->pasteurizadoras, true)) {
            return 'pasteurizadoras';
        }

        if (in_array($linea->nombre, $this->lavadoras, true)) {
            return 'lavadoras';
        }

        return null;
    }

    private function normalizarTipoEquipo(?string $tipo): string
    {
        return $tipo === 'pasteurizadoras' ? 'pasteurizadoras' : 'lavadoras';
    }

    private function ensureCanAccessTipoEquipo(string $tipoEquipo): void
    {
        if (
            $this->normalizarTipoEquipo($tipoEquipo) === 'pasteurizadoras'
            && !auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)
        ) {
            abort(403, 'No tienes permiso para acceder al modulo de Pasteurizadora.');
        }
    }

    private function ensureCanAccessLinea(Linea $linea): void
    {
        if (
            str_starts_with($linea->nombre, 'P-')
            && !auth()->user()?->canAccessModule(User::MODULE_PASTEURIZADORA)
        ) {
            abort(403, 'No tienes permiso para acceder al modulo de Pasteurizadora.');
        }
    }

    private function determinarEstadoGeneral($lineaId, $fechaInicio, $fechaFin)
    {
        // CORRECCIÓN: Usar el mismo DB::table que en el resto del controlador
        $registros = DB::table('analisis_componentes')
            ->where('linea_id', $lineaId)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin])
            ->get(['estado']);

        return $this->determinarEstadoGeneralDesdeRegistros($registros);

        $consulta = DB::table('analisis_componentes')
            ->where('linea_id', $lineaId)
            ->whereBetween('fecha_analisis', [$fechaInicio, $fechaFin]);

        $criticos = (clone $consulta)
            ->where('estado', 'Dañado - Requiere cambio')
            ->count();

        $severosModerados = (clone $consulta)
            ->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])
            ->count();

        $requiereRevision = (clone $consulta)
            ->where('estado', 'Requiere revisión')
            ->count();

        if ($criticos > 0) {
            return ['texto' => 'CRÍTICO', 'color' => 'red'];
        }

        if ($severosModerados > 0) {
            return ['texto' => 'SEVERO / MODERADO', 'color' => 'orange'];
        }

        if ($requiereRevision > 0) {
            return ['texto' => 'REQUIERE REVISIÓN', 'color' => 'yellow'];
        }

        return ['texto' => 'ESTABLE', 'color' => 'green'];
    }

    public function exportar(Request $request)
    {
        $formato = $request->get('export_format', 'pdf');
        $lineaFiltro = $request->get('lineaId', $request->get('linea_id', $request->get('linea')));
        $lineaId = is_numeric($lineaFiltro)
            ? $lineaFiltro
            : Linea::where('nombre', $lineaFiltro)->value('id');
        $tipo = $request->get('export_tipo', $lineaId ? 'linea' : 'completo');
        $tipoEquipo = $this->normalizarTipoEquipo($request->get('tipo', 'lavadoras'));
        $this->ensureCanAccessTipoEquipo($tipoEquipo);

        $fechaInicio = Carbon::parse($request->get('fecha_inicio', Carbon::now()->subMonth()))->startOfDay();
        $fechaFin = Carbon::parse($request->get('fecha_fin', Carbon::now()))->endOfDay();

        if ($formato === 'pdf') {
            return $this->exportarPDF($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        } else {
            return $this->exportarExcel($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin);
        }
    }

    private function exportarPDF($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
{
    if ($tipo === 'linea' && $lineaId) {

        $linea = Linea::findOrFail($lineaId);
        $this->ensureCanAccessLinea($linea);

        $reporteLinea = $this->getReporteDetalladoLinea(
            $linea,
            $fechaInicio,
            $fechaFin,
            $tipoEquipo
        );
        $reporte = ['lineas' => [$reporteLinea]];
        $modoReporte = 'linea';

        $pdf = Pdf::loadView(
            'reportes.pdf.general-lavadoras',
            compact('reporte', 'fechaInicio', 'fechaFin', 'tipoEquipo', 'modoReporte')
        );

        return $pdf->download(
            "reporte_{$linea->nombre}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.pdf"
        );

    } else {

        $reporte = $this->generarReporteGeneralOptimizado(
            $tipoEquipo,
            $fechaInicio,
            $fechaFin
        );
        $modoReporte = 'general';

        $pdf = Pdf::loadView(
            'reportes.pdf.general-lavadoras',
            compact('reporte', 'fechaInicio', 'fechaFin', 'tipoEquipo', 'modoReporte')
        );

        return $pdf->download(
            "reporte_general_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.pdf"
        );
    }
}

    private function exportarExcel($tipo, $lineaId, $tipoEquipo, $fechaInicio, $fechaFin)
{
    if ($tipoEquipo === 'pasteurizadoras') {
        return redirect()->route('pasteurizadora.analisis-pasteurizadora.export.excel', array_filter([
            'linea_id' => $lineaId,
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
        ]));
    }

    if ($tipo === 'linea' && $lineaId) {

        $linea = Linea::findOrFail($lineaId);
        $this->ensureCanAccessLinea($linea);

        $reporte = $this->getReporteDetalladoLinea(
            $linea,
            $fechaInicio,
            $fechaFin,
            $tipoEquipo
        );

        return Excel::download(
            new ReporteLavadoraGeneralExcel($fechaInicio, $fechaFin, $lineaId),
            "reporte_{$linea->nombre}_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.xlsx"
        );

    } else {

        $reporte = $this->generarReporteGeneralOptimizado(
            $tipoEquipo,
            $fechaInicio,
            $fechaFin
        );

        return Excel::download(
            new ReporteLavadoraGeneralExcel($fechaInicio, $fechaFin),
            "reporte_general_{$fechaInicio->format('Ymd')}_{$fechaFin->format('Ymd')}.xlsx"
        );
    }
}
}
