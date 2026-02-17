<?php

namespace App\Http\Controllers;

use App\Models\Elongacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ElongacionController extends Controller
{
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
                              ->withQueryString(); // Mantener filtros en paginación
        
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
                'hodometro' => 'required|integer|min:0',
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

            // Obtener mediciones lado bombas
            $bombasMediciones = [];
            for ($i = 1; $i <= 10; $i++) {
                $bombasMediciones[] = $request->input("bombas_{$i}");
            }
            
            // Calcular promedio lado bombas
            $bombasPromedio = Elongacion::calcularPromedio($bombasMediciones);
            $bombasPorcentaje = Elongacion::calcularPorcentaje($bombasPromedio);
            
            // Obtener mediciones lado vapor
            $vaporMediciones = [];
            for ($i = 1; $i <= 10; $i++) {
                $vaporMediciones[] = $request->input("vapor_{$i}");
            }
            
            // Calcular promedio lado vapor
            $vaporPromedio = Elongacion::calcularPromedio($vaporMediciones);
            $vaporPorcentaje = Elongacion::calcularPorcentaje($vaporPromedio);

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
            ];

            // Agregar mediciones individuales
            for ($i = 1; $i <= 10; $i++) {
                $data["bombas_{$i}"] = $request->input("bombas_{$i}");
                $data["vapor_{$i}"] = $request->input("vapor_{$i}");
            }

            // Crear registro
            $elongacion = Elongacion::create($data);

            // Verificar si requiere cambio
            $mensaje = 'Registro guardado exitosamente';
            if ($elongacion->requiere_cambio) {
                $mensaje .= ' - ¡ATENCIÓN! Se recomienda cambio de cadena (elongación supera 2.4%)';
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
     * API para obtener última lectura (para AJAX)
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