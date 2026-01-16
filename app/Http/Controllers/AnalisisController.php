<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Linea;
use App\Models\Componente;
use App\Models\Medicion;
use App\Models\AnalisisComponente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AnalisisController extends Controller
{
    /**
     * Listado de análisis
     */
    public function index()
    {
        $analisis = Analisis::with(['linea', 'usuario'])
            ->orderBy('fecha_analisis', 'desc')
            ->paginate(20);

        return view('analisis.index', compact('analisis'));
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        $lineas = Linea::where('activa', true)->get();
        $componentes = Componente::where('activo', true)->get();

        return view('analisis.create', compact('lineas', 'componentes'));
    }

    /**
     * Guardar análisis
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'fecha_analisis' => 'required|date',
            'numero_orden' => 'required|string|max:50',
            'horometro' => 'nullable|integer',
            'juego_rodaja_bombas' => 'nullable|numeric',

            'mediciones_l_bombas' => 'nullable|array',
            'mediciones_l_bombas.*' => 'numeric',

            'mediciones_l_vapor' => 'nullable|array',
            'mediciones_l_vapor.*' => 'numeric',

            'componentes' => 'required|array',
            'componentes.*.componente_id' => 'required|exists:componentes,id',
            'componentes.*.cantidad_revisada' => 'required|integer|min:0',
            'componentes.*.estado' => 'required|in:BUENO,REGULAR,DAÑADO,REEMPLAZADO',
            'componentes.*.actividad' => 'nullable|string',
            'componentes.*.observaciones' => 'nullable|string',
            'fotos.*.*' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();
        
        try {
            // Calcular elongación
            $elongacion = $this->calcularElongacion($request);

            // Crear análisis
            $analisis = Analisis::create([
                'linea_id' => $validated['linea_id'],
                'fecha_analisis' => $validated['fecha_analisis'],
                'numero_orden' => $validated['numero_orden'],
                'horometro' => $validated['horometro'] ?? null,
                'elongacion_promedio' => $elongacion['promedio'],
                'juego_rodaja' => $validated['juego_rodaja_bombas'] ?? null,
                'usuario_id' => auth()->id(),
            ]);

            // Guardar mediciones
            if ($request->filled('mediciones_l_bombas')) {
                $this->guardarMediciones($analisis, 'L_BOMBAS', $request->mediciones_l_bombas);
            }

            if ($request->filled('mediciones_l_vapor')) {
                $this->guardarMediciones($analisis, 'L_VAPOR', $request->mediciones_l_vapor);
            }

            // Guardar componentes
            foreach ($request->componentes as $index => $componenteData) {
                $fotos = [];

                if ($request->hasFile("fotos.{$index}")) {
                    foreach ($request->file("fotos.{$index}") as $foto) {
                        $path = $foto->store('evidencia-analisis', 'public');
                        $fotos[] = $path;
                    }
                }

                // Crear componente del análisis
                AnalisisComponente::create([
                    'analisis_id' => $analisis->id,
                    'componente_id' => $componenteData['componente_id'],
                    'cantidad_revisada' => $componenteData['cantidad_revisada'],
                    'estado' => $componenteData['estado'],
                    'actividad' => $componenteData['actividad'] ?? null,
                    'observaciones' => $componenteData['observaciones'] ?? null,
                    'evidencia_fotos' => $fotos,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('analisis.show', $analisis)
                ->with('success', 'Análisis registrado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar el análisis: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle de análisis
     */
    public function show(Analisis $analisis)
    {
        // Cargar relaciones con manejo de errores
        try {
            // Cargar análisis con relaciones
            $analisis->load([
                'linea',
                'mediciones',
                'usuario'
            ]);
            
            // Obtener componentes con sus relaciones
            $componentes = $analisis->componentes()->with('componente')->get();
            
        } catch (\Exception $e) {
            // Si hay error en las relaciones, obtener datos manualmente
            $componentes = DB::table('analisis_componentes')
                ->where('analisis_id', $analisis->id)
                ->get()
                ->map(function ($item) {
                    $item->evidencia_fotos = json_decode($item->evidencia_fotos, true) ?? [];
                    $item->componente_nombre = DB::table('componentes')
                        ->where('id', $item->componente_id)
                        ->value('nombre') ?? 'Componente no encontrado';
                    return $item;
                });
        }

        return view('analisis.show', compact('analisis', 'componentes'));
    }

    /**
     * Formulario de edición
     */
    public function edit(Analisis $analisis)
    {
        $lineas = Linea::where('activa', true)->get();
        $componentes = Componente::where('activo', true)->get();
        
        // Obtener componentes del análisis
        $analisisComponentes = $analisis->componentes()->with('componente')->get();

        return view('analisis.edit', compact('analisis', 'lineas', 'componentes', 'analisisComponentes'));
    }

    /**
     * Actualizar análisis
     */
    public function update(Request $request, Analisis $analisis)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'fecha_analisis' => 'required|date',
            'numero_orden' => 'required|string|max:50',
            'horometro' => 'nullable|integer',
            'juego_rodaja_bombas' => 'nullable|numeric',

            'mediciones_l_bombas' => 'nullable|array',
            'mediciones_l_bombas.*' => 'numeric',

            'mediciones_l_vapor' => 'nullable|array',
            'mediciones_l_vapor.*' => 'numeric',

            'componentes' => 'required|array',
            'componentes.*.id' => 'sometimes|exists:analisis_componentes,id',
            'componentes.*.componente_id' => 'required|exists:componentes,id',
            'componentes.*.cantidad_revisada' => 'required|integer|min:0',
            'componentes.*.estado' => 'required|in:BUENO,REGULAR,DAÑADO,REEMPLAZADO',
            'componentes.*.actividad' => 'nullable|string',
            'componentes.*.observaciones' => 'nullable|string',
            'fotos.*.*' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Actualizar análisis
            $elongacion = $this->calcularElongacion($request);
            
            $analisis->update([
                'linea_id' => $validated['linea_id'],
                'fecha_analisis' => $validated['fecha_analisis'],
                'numero_orden' => $validated['numero_orden'],
                'horometro' => $validated['horometro'] ?? null,
                'elongacion_promedio' => $elongacion['promedio'],
                'juego_rodaja' => $validated['juego_rodaja_bombas'] ?? null,
            ]);

            // Actualizar mediciones
            $this->actualizarMediciones($analisis, $request);

            // Actualizar componentes
            $this->actualizarComponentes($analisis, $request);

            DB::commit();

            return redirect()
                ->route('analisis.show', $analisis)
                ->with('success', 'Análisis actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar el análisis: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar análisis
     */
    public function destroy(Analisis $analisis)
    {
        DB::beginTransaction();
        
        try {
            // Eliminar fotos de los componentes
            foreach ($analisis->componentes as $componente) {
                if ($componente->evidencia_fotos) {
                    foreach ($componente->evidencia_fotos as $foto) {
                        Storage::disk('public')->delete($foto);
                    }
                }
            }
            
            // Eliminar el análisis (esto eliminará en cascada los componentes y mediciones)
            $analisis->delete();
            
            DB::commit();
            
            return redirect()
                ->route('analisis.index')
                ->with('success', 'Análisis eliminado exitosamente');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar el análisis: ' . $e->getMessage());
        }
    }

    /* =====================================================
     | MÉTODOS PRIVADOS
     ===================================================== */

    /**
     * Calcula elongación promedio
     */
    private function calcularElongacion(Request $request): array
    {
        $pasoCadena = 173; // mm

        $mediciones = array_merge(
            $request->mediciones_l_bombas ?? [],
            $request->mediciones_l_vapor ?? []
        );

        if (count($mediciones) === 0) {
            return [
                'promedio' => null,
                'porcentaje' => null,
                'max_permitido' => 3,
            ];
        }

        $promedio = array_sum($mediciones) / count($mediciones);
        $porcentaje = (($promedio - $pasoCadena) / $pasoCadena) * 100;

        return [
            'promedio' => round($promedio, 2),
            'porcentaje' => round($porcentaje, 2),
            'max_permitido' => 3,
        ];
    }

    /**
     * Guarda mediciones asociadas
     */
    private function guardarMediciones(Analisis $analisis, string $tipo, array $mediciones): void
    {
        Medicion::create([
            'analisis_id' => $analisis->id,
            'tipo' => $tipo,
            'medicion_1' => $mediciones[0] ?? 0,
            'medicion_2' => $mediciones[1] ?? 0,
            'medicion_3' => $mediciones[2] ?? 0,
            'medicion_4' => $mediciones[3] ?? 0,
            'medicion_5' => $mediciones[4] ?? 0,
            'medicion_6' => $mediciones[5] ?? 0,
            'medicion_7' => $mediciones[6] ?? 0,
            'medicion_8' => $mediciones[7] ?? 0,
            'promedio'   => array_sum($mediciones) / max(count($mediciones), 1),
        ]);
    }

    /**
     * Actualizar mediciones
     */
    private function actualizarMediciones(Analisis $analisis, Request $request): void
    {
        // Eliminar mediciones existentes
        $analisis->mediciones()->delete();

        // Crear nuevas mediciones
        if ($request->filled('mediciones_l_bombas')) {
            $this->guardarMediciones($analisis, 'L_BOMBAS', $request->mediciones_l_bombas);
        }

        if ($request->filled('mediciones_l_vapor')) {
            $this->guardarMediciones($analisis, 'L_VAPOR', $request->mediciones_l_vapor);
        }
    }

    /**
     * Actualizar componentes
     */
    private function actualizarComponentes(Analisis $analisis, Request $request): void
    {
        $componentesExistentesIds = [];
        
        foreach ($request->componentes as $componenteData) {
            $fotos = [];
            
            // Si hay fotos nuevas
            if (isset($componenteData['id']) && isset($request->fotos[$componenteData['id']])) {
                foreach ($request->fotos[$componenteData['id']] as $foto) {
                    $path = $foto->store('evidencia-analisis', 'public');
                    $fotos[] = $path;
                }
            }
            
            if (isset($componenteData['id'])) {
                // Actualizar componente existente
                $analisisComponente = AnalisisComponente::find($componenteData['id']);
                if ($analisisComponente) {
                    // Mantener fotos existentes si no hay nuevas
                    if (empty($fotos) && $analisisComponente->evidencia_fotos) {
                        $fotos = $analisisComponente->evidencia_fotos;
                    }
                    
                    $analisisComponente->update([
                        'componente_id' => $componenteData['componente_id'],
                        'cantidad_revisada' => $componenteData['cantidad_revisada'],
                        'estado' => $componenteData['estado'],
                        'actividad' => $componenteData['actividad'] ?? null,
                        'observaciones' => $componenteData['observaciones'] ?? null,
                        'evidencia_fotos' => $fotos,
                    ]);
                    
                    $componentesExistentesIds[] = $componenteData['id'];
                }
            } else {
                // Crear nuevo componente
                AnalisisComponente::create([
                    'analisis_id' => $analisis->id,
                    'componente_id' => $componenteData['componente_id'],
                    'cantidad_revisada' => $componenteData['cantidad_revisada'],
                    'estado' => $componenteData['estado'],
                    'actividad' => $componenteData['actividad'] ?? null,
                    'observaciones' => $componenteData['observaciones'] ?? null,
                    'evidencia_fotos' => $fotos,
                ]);
            }
        }
        
        // Eliminar componentes que no están en la solicitud
        AnalisisComponente::where('analisis_id', $analisis->id)
            ->whereNotIn('id', $componentesExistentesIds)
            ->delete();
    }
}