<?php

namespace App\Http\Controllers;

use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalisisPasteurizadoraController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX - Versión Industrial
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // 1. Obtener todas las líneas para los filtros
        $lineas = Linea::all();
        
        // 2. Determinar línea seleccionada
        $lineaId = $request->get('linea_id', 'todas');
        $lineaSeleccionada = $lineaId !== 'todas' ? Linea::find($lineaId) : null;
        
        // 3. Construir query base - SOLO REGISTROS NO RESUELTOS
        $query = AnalisisPasteurizadora::with('linea')
            ->where('resuelto_por_cambio', false); // Excluir registros resueltos
        
        if ($lineaId !== 'todas' && $lineaId) {
            $query->where('linea_id', $lineaId);
        }
        
        // 4. Aplicar filtros adicionales
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }
        
        if ($request->filled('componente')) {
            $query->where('componente', $request->componente);
        }
        
        if ($request->filled('fecha')) {
            $query->whereYear('fecha_analisis', substr($request->fecha, 0, 4))
                  ->whereMonth('fecha_analisis', substr($request->fecha, 5, 2));
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        // 5. Obtener análisis como colección
        $analisis = $query->latest('fecha_analisis')->get();
        
        // 6. Calcular estadísticas (considerando solo registros no resueltos)
        $totalAnalisis = $analisis->count();
        $totalDanados = $analisis->where('estado', 'Dañado - Requiere cambio')->count();
        $totalCambiados = $analisis->where('estado', 'Cambiado')->count();
        $totalModulosAnalizados = $analisis->pluck('modulo')->unique()->count();
        
        // 7. Obtener datos para gráfica de tendencias
        $datosTendencia = $this->getDatosTendencia($lineaId, $request);
        
        return view('analisis-pasteurizadora.index', compact(
            'analisis', 
            'lineas', 
            'totalAnalisis', 
            'totalDanados', 
            'totalCambiados',
            'totalModulosAnalizados',
            'lineaSeleccionada',
            'datosTendencia'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard(Request $request)
    {
        $nombreLinea = $request->get('linea', 'L-07');
        $linea = Linea::where('nombre', $nombreLinea)->firstOrFail();
        $lineaId = $linea->id;

        // Último registro (considerando no resueltos)
        $ultimoRegistro = AnalisisPasteurizadora::where('linea_id', $lineaId)
            ->where('resuelto_por_cambio', false)
            ->latest('fecha_analisis')
            ->first();

        // Actividades recientes
        $actividadesRecientes = AnalisisPasteurizadora::where('linea_id', $lineaId)
            ->whereNotNull('actividad')
            ->where('resuelto_por_cambio', false)
            ->orderBy('fecha_analisis', 'desc')
            ->limit(10)
            ->get();

        // Estadísticas por componente (considerando no resueltos)
        $estadisticasComponentes = $this->getEstadisticasComponentes($lineaId);

        // Análisis 52-12-4 para gráfica
        $analisisTendencia = AnalisisPasteurizadora::where('linea_id', $lineaId)
            ->whereNotNull('valor_actual_52')
            ->where('resuelto_por_cambio', false)
            ->orderBy('fecha_analisis', 'desc')
            ->limit(12)
            ->get();

        return view('analisis-pasteurizadora.dashboard', compact(
            'linea',
            'ultimoRegistro',
            'actividadesRecientes',
            'estadisticasComponentes',
            'analisisTendencia'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        // Validar que se reciba un ID de línea
        $lineaId = $request->get('linea_id');
        
        if (!$lineaId) {
            return redirect()->route('analisis-pasteurizadora.select-linea')
                ->with('error', 'Debe seleccionar una línea primero');
        }
        
        $linea = Linea::findOrFail($lineaId);
        $fechaSugerida = date('Y-m-d');
        
        return view('analisis-pasteurizadora.create', compact('linea', 'fechaSugerida'));
    }

    public function createWithLinea(Linea $linea)
    {
        $fechaSugerida = date('Y-m-d');
        $lineas = Linea::all();
        
        return view('analisis-pasteurizadora.create', compact('linea', 'fechaSugerida', 'lineas'));
    }

    public function createQuick(Request $request)
    {
        $linea_id = $request->linea_id;
        $modulo = $request->modulo;
        $componente = $request->componente;
        $fecha = $request->fecha;

        $linea = Linea::find($linea_id);

        return view('analisis-pasteurizadora.create-quick', [
            'linea' => $linea,
            'modulo' => $modulo,
            'componente' => $componente,
            'fecha' => $fecha
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'modulo' => 'required|integer|min:1|max:16',
            'componente' => 'required|string',
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'fecha_analisis' => 'required|date',
            'numero_orden' => 'required|string|max:50',
            'estado' => 'required|in:Buen estado,Desgaste moderado,Desgaste severo,Dañado - Requiere cambio,Cambiado',
            'actividad' => 'required|string',
            'evidencia_fotos' => 'nullable|array',
            'evidencia_fotos.*' => 'nullable|image|max:5120', // 5MB max
        ]);

        // Procesar imágenes si existen
        $fotosPaths = [];
        if ($request->hasFile('evidencia_fotos')) {
            foreach ($request->file('evidencia_fotos') as $foto) {
                $path = $foto->store('analisis-pasteurizadora', 'public');
                $fotosPaths[] = $path;
            }
        }

        // Crear el análisis
        $analisis = AnalisisPasteurizadora::create([
            'linea_id' => $validated['linea_id'],
            'modulo' => $validated['modulo'],
            'componente' => $validated['componente'],
            'lado' => $validated['lado'],
            'fecha_analisis' => $validated['fecha_analisis'],
            'numero_orden' => $validated['numero_orden'],
            'estado' => $validated['estado'],
            'actividad' => $validated['actividad'],
            'evidencia_fotos' => $fotosPaths,
            'resuelto_por_cambio' => false, // Por defecto no resuelto
        ]);

        // *** LÓGICA ESPECIAL: Si el estado es "Cambiado", marcar registros anteriores como resueltos ***
        if ($validated['estado'] === 'Cambiado') {
            $this->marcarRegistrosAnterioresComoResueltos($analisis);
        }

        return redirect()
            ->route('analisis-pasteurizadora.index', ['linea_id' => $validated['linea_id']])
            ->with('success', 'Análisis registrado correctamente.');
    }

    /**
     * Marcar registros anteriores de "Dañado - Requiere cambio" como resueltos
     */
    private function marcarRegistrosAnterioresComoResueltos($nuevoAnalisis)
    {
        // Buscar registros anteriores con el mismo módulo y componente
        // que tengan estado "Dañado - Requiere cambio" y no estén resueltos
        $registrosAnteriores = AnalisisPasteurizadora::where('linea_id', $nuevoAnalisis->linea_id)
            ->where('modulo', $nuevoAnalisis->modulo)
            ->where('componente', $nuevoAnalisis->componente)
            ->where('estado', 'Dañado - Requiere cambio')
            ->where('resuelto_por_cambio', false)
            ->where('id', '!=', $nuevoAnalisis->id) // Excluir el nuevo registro
            ->get();

        $contador = 0;
        foreach ($registrosAnteriores as $registro) {
            $registro->update([
                'resuelto_por_cambio' => true,
                'fecha_resolucion' => now(),
                'nota_resolucion' => "Resuelto automáticamente por cambio en orden #{$nuevoAnalisis->numero_orden} del " . now()->format('d/m/Y H:i')
            ]);
            $contador++;
        }

        // Opcional: Log de la operación
        if ($contador > 0) {
            \Log::info("Se resolvieron {$contador} registros de 'Dañado - Requiere cambio' al crear análisis ID {$nuevoAnalisis->id}");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $analisis = AnalisisPasteurizadora::with('linea')->findOrFail($id);
        
        $estadisticas = $this->getEstadisticasCompletas($analisis);
        $analisis52124 = $this->getAnalisis52124($analisis);
        
        $relacionados = AnalisisPasteurizadora::where('linea_id', $analisis->linea_id)
            ->when($analisis->componente === 'ANILLAS', function($q) {
                return $q->where('componente', 'ANILLAS');
            }, function($q) use ($analisis) {
                return $q->where('modulo', $analisis->modulo)
                         ->where('componente', $analisis->componente);
            })
            ->where('id', '!=', $id)
            ->latest('fecha_analisis')
            ->limit(5)
            ->get();

        return view('analisis-pasteurizadora.show', compact(
            'analisis', 
            'estadisticas', 
            'analisis52124',
            'relacionados'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        $lineas = Linea::all();

        return view('analisis-pasteurizadora.edit', compact('analisis', 'lineas'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);

        $validated = $request->validate([
            'modulo' => 'nullable|integer|min:1|max:16',
            'componente' => 'nullable|string',
            'fecha_analisis' => 'nullable|date',
            'numero_orden' => 'nullable|string',
            'actividad' => 'nullable|string',
            'estado' => 'nullable|string|in:Buen estado,Desgaste moderado,Desgaste severo,Dañado - Requiere cambio,Cambiado',
            'lado' => 'nullable|in:VAPOR,PASILLO',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        // Si el estado cambia a "Cambiado", marcar registros anteriores como resueltos
        if (isset($validated['estado']) && 
            $validated['estado'] === 'Cambiado' && 
            $analisis->estado !== 'Cambiado') {
            
            // Crear un objeto temporal para pasar a la función
            $tempAnalisis = (object)[
                'linea_id' => $analisis->linea_id,
                'modulo' => $validated['modulo'] ?? $analisis->modulo,
                'componente' => $validated['componente'] ?? $analisis->componente,
                'numero_orden' => $validated['numero_orden'] ?? $analisis->numero_orden,
            ];
            
            $this->marcarRegistrosAnterioresComoResueltos($tempAnalisis);
        }

        $analisis->update($validated);

        return redirect()
            ->route('analisis-pasteurizadora.show', $analisis->id)
            ->with('success', 'Análisis actualizado correctamente');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        
        if ($analisis->evidencia_fotos) {
            foreach ($analisis->evidencia_fotos as $foto) {
                Storage::disk('public')->delete($foto);
            }
        }
        
        $analisis->delete();

        return redirect()
            ->route('analisis-pasteurizadora.index')
            ->with('success', 'Registro eliminado correctamente');
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT LINEA
    |--------------------------------------------------------------------------
    */

    public function selectLinea()
    {
        $lineas = Linea::all();
        
        return view('analisis-pasteurizadora.select-linea', compact('lineas'));
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL (incluye todos los registros, incluso los resueltos)
    |--------------------------------------------------------------------------
    */

    public function historial(Request $request)
    {
        $query = AnalisisPasteurizadora::with('linea')
            ->orderBy('fecha_analisis', 'desc');

        // Filtrar por línea
        if ($request->filled('linea_id')) {
            $query->where('linea_id', $request->linea_id);
        }

        // Filtrar por módulo
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }

        // Filtrar por componente
        if ($request->filled('componente')) {
            $query->where('componente', $request->componente);
        }

        // Filtrar por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtrar por resueltos/no resueltos
        if ($request->filled('resueltos')) {
            if ($request->resueltos === 'si') {
                $query->where('resuelto_por_cambio', true);
            } elseif ($request->resueltos === 'no') {
                $query->where('resuelto_por_cambio', false);
            }
        }

        // Filtrar por rango de fechas
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_analisis', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_analisis', '<=', $request->fecha_fin);
        }

        $analisis = $query->paginate(10)->withQueryString();
        
        // Obtener todas las líneas para el filtro
        $lineas = Linea::whereIn('nombre', [
            'L-03', 'L-04', 'L-05', 'L-06', 'L-07', 
            'L-08', 'L-09', 'L-10', 'L-11', 'L-12', 'L-13', 'L-14'
        ])->orderBy('nombre')->get();

        return view('analisis-pasteurizadora.historial', compact('analisis', 'lineas'));
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT PDF
    |--------------------------------------------------------------------------
    */

    public function exportPdf(Request $request)
    {
        $nombreLinea = $request->get('linea', 'L-07');
        $linea = Linea::where('nombre', $nombreLinea)->firstOrFail();

        $registro = AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->where('resuelto_por_cambio', false)
            ->latest('fecha_analisis')
            ->first();

        $actividades = AnalisisPasteurizadora::where('linea_id', $linea->id)
            ->whereNotNull('actividad')
            ->where('resuelto_por_cambio', false)
            ->orderBy('fecha_analisis', 'desc')
            ->limit(20)
            ->get();

        $estadisticas = [];
        if ($registro) {
            $estadisticas = $this->getEstadisticasCompletas($registro);
        }

        $pdf = Pdf::loadView('analisis-pasteurizadora.export-pdf', compact(
            'registro', 
            'actividades', 
            'nombreLinea',
            'estadisticas'
        ));

        return $pdf->download(
            'analisis_pasteurizadora_' . $nombreLinea . '_' . date('Ymd') . '.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS PRIVADOS
    |--------------------------------------------------------------------------
    */

    private function getDatosTendencia($lineaId, $request)
    {
        $query = AnalisisPasteurizadora::query()
            ->where('resuelto_por_cambio', false); // Solo no resueltos para tendencias
        
        if ($lineaId !== 'todas' && $lineaId) {
            $query->where('linea_id', $lineaId);
        }
        
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }
        
        if ($request->filled('componente')) {
            $query->where('componente', $request->componente);
        }
        
        $fechaLimite = now()->subMonths(12);
        
        return $query->where('fecha_analisis', '>=', $fechaLimite)
                    ->selectRaw('YEAR(fecha_analisis) as año, MONTH(fecha_analisis) as mes, COUNT(*) as total')
                    ->groupBy('año', 'mes')
                    ->orderBy('año')
                    ->orderBy('mes')
                    ->get()
                    ->map(function($item) {
                        return [
                            'fecha' => $item->año . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT),
                            'total' => $item->total
                        ];
                    });
    }

    private function getEstadisticasComponentes($lineaId)
    {
        // Definir constantes de componentes si no existen en el modelo
        $componentes = [
            'ANILLAS' => 'Anillas',
            'PLACAS_PERNO' => 'Placas Perno',
            'REGLILLAS' => 'Reglillas',
            'RODAMIENTOS' => 'Rodamientos',
            'EXCENTRICOS' => 'Excéntricos',
            'PISTAS' => 'Pistas',
            'ESPARRAGOS' => 'Espárragos'
        ];
        
        $estadisticas = [];
        
        foreach (array_keys($componentes) as $componente) {
            $query = AnalisisPasteurizadora::where('linea_id', $lineaId)
                ->where('componente', $componente)
                ->where('resuelto_por_cambio', false); // Solo no resueltos
            
            $total = $query->count();
            $danados = (clone $query)->where('estado', 'Dañado - Requiere cambio')->count();
            $cambiados = (clone $query)->where('estado', 'Cambiado')->count();
            
            $estadisticas[$componente] = [
                'total' => $total,
                'danados' => $danados,
                'cambiados' => $cambiados,
                'porcentaje_danos' => $total > 0 ? round(($danados / $total) * 100, 1) : 0,
                'nombre' => $componentes[$componente]
            ];
        }
        
        return $estadisticas;
    }

    private function getEstadisticasCompletas($analisis)
    {
        // Obtener estadísticas históricas del mismo módulo y componente
        $historicos = AnalisisPasteurizadora::where('linea_id', $analisis->linea_id)
            ->where('modulo', $analisis->modulo)
            ->where('componente', $analisis->componente)
            ->orderBy('fecha_analisis')
            ->get();

        $totalRegistros = $historicos->count();
        $vecesCambiado = $historicos->where('estado', 'Cambiado')->count();
        $vecesDanado = $historicos->where('estado', 'Dañado - Requiere cambio')->count();
        
        // Calcular días desde último cambio
        $ultimoCambio = $historicos->where('estado', 'Cambiado')->last();
        $diasDesdeUltimoCambio = $ultimoCambio 
            ? now()->diffInDays($ultimoCambio->fecha_analisis) 
            : null;

        return [
            'total_historicos' => $totalRegistros,
            'veces_cambiado' => $vecesCambiado,
            'veces_danado' => $vecesDanado,
            'dias_ultimo_cambio' => $diasDesdeUltimoCambio,
            'frecuencia_danos' => $totalRegistros > 0 
                ? round(($vecesDanado / $totalRegistros) * 100, 1) 
                : 0
        ];
    }

    private function getAnalisis52124($analisis)
    {
        // Implementar análisis 52-12-4 según necesidades
        return [
            'semanal' => null,
            'mensual' => null,
            'trimestral' => null
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ADICIONALES PARA RUTAS EXISTENTES
    |--------------------------------------------------------------------------
    */

public function historicoRevisados(Request $request)
{
    // Obtener todas las líneas para el selector
    $lineas = Linea::all();
    
    // Obtener línea seleccionada (si existe)
    $lineaSeleccionada = null;
    if ($request->has('linea_id')) {
        $lineaSeleccionada = Linea::find($request->linea_id);
    }
    
    // Definir los componentes
    $componentes = [
        ['codigo' => 'ANILLAS', 'nombre' => 'Anillas'],
        ['codigo' => 'PLACAS_PERNO', 'nombre' => 'Placas Perno'],
        ['codigo' => 'REGILLAS', 'nombre' => 'Reglillas'],
        ['codigo' => 'RODAMIENTOS', 'nombre' => 'Rodamientos'],
        ['codigo' => 'EXCENTRICOS', 'nombre' => 'Excéntricos'],
        ['codigo' => 'PISTAS', 'nombre' => 'Pistas'],
        ['codigo' => 'ESPARRAGOS', 'nombre' => 'Espárragos']
    ];
    
    // Crear array de componentes por módulo
    $componentesModulos = collect();
    
    // Si hay línea seleccionada, generar combinaciones módulo-componente
    if ($lineaSeleccionada) {
        for ($modulo = 1; $modulo <= 16; $modulo++) {
            foreach ($componentes as $componente) {
                $componentesModulos->push([
                    'codigo' => $componente['codigo'],
                    'nombre' => $componente['nombre'],
                    'modulo' => $modulo
                ]);
            }
        }
    }
    
    // Calcular estadísticas
    $estadisticas = [];
    $totalGeneral = 0;
    $totalRevisado = 0;
    
    if ($lineaSeleccionada) {
        foreach ($componentes as $componente) {
            $estadisticas[$componente['codigo']] = [];
            
            for ($modulo = 1; $modulo <= 16; $modulo++) {
                // Consultar análisis para este componente y módulo
                $analisis = AnalisisPasteurizadora::where('linea_id', $lineaSeleccionada->id)
                    ->where('componente', $componente['codigo'])
                    ->where('modulo', $modulo)
                    ->get();
                
                // Calcular total y revisadas
                $total = 16; // Cada componente tiene 16 piezas por módulo
                $revisadas = 0;
                
                // Sumar las piezas revisadas de todos los análisis
                foreach ($analisis as $a) {
                    $campoRevisadas = 'revisadas_' . strtolower($componente['codigo']);
                    if (isset($a->$campoRevisadas)) {
                        $revisadas += $a->$campoRevisadas;
                    }
                }
                
                // Limitar revisadas al total máximo
                $revisadas = min($revisadas, $total);
                
                // Calcular porcentaje
                $porcentaje = $total > 0 ? round(($revisadas / $total) * 100) : 0;
                
                // Determinar color
                if ($porcentaje >= 80) {
                    $color = 'success';
                } elseif ($porcentaje >= 50) {
                    $color = 'info';
                } elseif ($porcentaje >= 20) {
                    $color = 'warning';
                } else {
                    $color = 'danger';
                }
                
                $estadisticas[$componente['codigo']][$modulo] = [
                    'total' => $total,
                    'revisadas' => $revisadas,
                    'porcentaje' => $porcentaje,
                    'color' => $color
                ];
                
                // Acumular totales generales
                $totalGeneral += $total;
                $totalRevisado += $revisadas;
            }
        }
        
        // Calcular resumen general
        $estadisticas['resumen'] = [
            'total_general' => $totalGeneral,
            'total_revisado' => $totalRevisado,
            'porcentaje_general' => $totalGeneral > 0 ? round(($totalRevisado / $totalGeneral) * 100) : 0
        ];
    }
    
    return view('historico-revisados.pasteurizadora.index', compact(
        'lineas', 
        'lineaSeleccionada', 
        'componentesModulos', 
        'estadisticas'
    ));
}

public function planAccion()
{
    $pendientes = AnalisisPasteurizadora::where('estado', 'Dañado - Requiere cambio')
        ->where('resuelto_por_cambio', false)
        ->with('linea')
        ->get();

    $lineas = Linea::all();

    $lineaSeleccionada = null; // 👈 agregar esto

    return view('plan-accion.pasteurizadora.index', compact(
        'pendientes',
        'lineas',
        'lineaSeleccionada'
    ));
}

    public function updatePlanAccion(Request $request)
    {
        $validated = $request->validate([
            'acciones' => 'required|array',
            'acciones.*.id' => 'required|exists:analisis_pasteurizadora,id',
            'acciones.*.plan' => 'required|string',
            'acciones.*.fecha_compromiso' => 'required|date',
        ]);

        foreach ($validated['acciones'] as $accion) {
            AnalisisPasteurizadora::where('id', $accion['id'])->update([
                'plan_accion' => $accion['plan'],
                'fecha_compromiso' => $accion['fecha_compromiso']
            ]);
        }

        return redirect()->back()->with('success', 'Plan de acción actualizado correctamente');
    }

    public function analisis52124()
    {
        $analisis = AnalisisPasteurizadora::where('resuelto_por_cambio', false)
            ->with('linea')
            ->latest('fecha_analisis')
            ->limit(52)
            ->get();
            
        return view('analisis-pasteurizadora.analisis-tendencia', compact('analisis'));
    }

    public function updateAnalisis52124(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:analisis_pasteurizadora,id',
            'valor_52' => 'nullable|numeric',
            'valor_12' => 'nullable|numeric',
            'valor_4' => 'nullable|numeric',
        ]);

        $analisis = AnalisisPasteurizadora::findOrFail($validated['id']);
        $analisis->update([
            'valor_actual_52' => $validated['valor_52'] ?? $analisis->valor_actual_52,
            'valor_actual_12' => $validated['valor_12'] ?? $analisis->valor_actual_12,
            'valor_actual_4' => $validated['valor_4'] ?? $analisis->valor_actual_4,
        ]);

        return redirect()->back()->with('success', 'Análisis 52-12-4 actualizado');
    }

    public function exportExcel(Request $request)
    {
        // Implementar exportación a Excel usando Laravel Excel
        $lineaId = $request->get('linea_id');
        $query = AnalisisPasteurizadora::with('linea');
        
        if ($lineaId && $lineaId !== 'todas') {
            $query->where('linea_id', $lineaId);
        }
        
        $analisis = $query->get();
        
        // Por ahora redirigir con mensaje
        return redirect()->back()->with('info', 'Exportación a Excel en desarrollo');
    }

    public function exportProcess(Request $request)
    {
        // Implementar proceso de exportación personalizado
        $formato = $request->get('formato', 'excel');
        $datos = $request->get('datos', []);
        
        return redirect()->back()->with('info', "Exportación a {$formato} en proceso");
    }

    public function getComponentesPorLineaAjax(Request $request)
    {
        $lineaId = $request->get('linea_id');
        $linea = Linea::find($lineaId);
        
        if (!$linea) {
            return response()->json([]);
        }

        $componentes = [
            'ANILLAS' => 'Anillas (Ventanas-Cortinas)',
            'PLACAS_PERNO' => 'Placas Perno',
            'REGLILLAS' => 'Reglillas (Parrillas)',
            'RODAMIENTOS' => 'Rodamientos',
            'EXCENTRICOS' => 'Excéntricos - Levas',
            'PISTAS' => 'Pistas',
            'ESPARRAGOS' => 'Espárragos',
        ];
        
        return response()->json($componentes);
    }

    public function getActividadesPorModulo(Request $request)
    {
        $lineaId = $request->get('linea_id');
        $modulo = $request->get('modulo');
        
        $actividades = AnalisisPasteurizadora::where('linea_id', $lineaId)
            ->where('modulo', $modulo)
            ->whereNotNull('actividad')
            ->where('resuelto_por_cambio', false)
            ->latest('fecha_analisis')
            ->limit(10)
            ->pluck('actividad');
        
        return response()->json($actividades);
    }

    public function getEstadisticasComponentesAjax(Request $request)
    {
        $lineaId = $request->get('linea_id');
        $estadisticas = $this->getEstadisticasComponentes($lineaId);
        
        return response()->json($estadisticas);
    }

    public function deleteFoto($id, $fotoIndex)
    {
        $analisis = AnalisisPasteurizadora::findOrFail($id);
        
        if (isset($analisis->evidencia_fotos[$fotoIndex])) {
            Storage::disk('public')->delete($analisis->evidencia_fotos[$fotoIndex]);
            $fotos = $analisis->evidencia_fotos;
            unset($fotos[$fotoIndex]);
            $analisis->evidencia_fotos = array_values($fotos);
            $analisis->save();
            
            return redirect()->back()->with('success', 'Foto eliminada correctamente');
        }
        
        return redirect()->back()->with('error', 'No se pudo eliminar la foto');
    }

    public function storeQuick(Request $request)
    {
        // Redirigir al método store con los mismos datos
        return $this->store($request);
    }
}