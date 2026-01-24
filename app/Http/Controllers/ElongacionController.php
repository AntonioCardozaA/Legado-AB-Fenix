<?php

namespace App\Http\Controllers;

use App\Models\Elongacion;
use Illuminate\Http\Request;

class ElongacionController extends Controller
{
   public function index()
{
    $elongaciones = Elongacion::orderBy('created_at', 'desc')
        ->paginate(10); // 👈 IMPORTANTE

    return view('elongaciones.index', compact('elongaciones'));
}


    public function create()
    {
        return view('elongaciones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'hodometro' => 'required|integer|min:0',
        ]);

        // Obtener mediciones lado bombas
        $bombasMediciones = [];
        for ($i = 1; $i <= 8; $i++) {
            $field = "bombas_{$i}";
            $bombasMediciones[] = $request->$field ?? null;
        }
        
        // Calcular promedio lado bombas
        $bombasPromedio = Elongacion::calcularPromedio($bombasMediciones);
        $bombasPorcentaje = Elongacion::calcularPorcentaje($bombasPromedio);
        
        // Obtener mediciones lado vapor
        $vaporMediciones = [];
        for ($i = 1; $i <= 4; $i++) {
            $field = "vapor_{$i}";
            $vaporMediciones[] = $request->$field ?? null;
        }
        
        // Calcular promedio lado vapor
        $vaporPromedio = Elongacion::calcularPromedio($vaporMediciones);
        $vaporPorcentaje = Elongacion::calcularPorcentaje($vaporPromedio);

        // Crear registro
        $elongacion = Elongacion::create([
            'linea' => $request->linea ?? 'L-07',
            'seccion' => $request->seccion ?? 'LAVADORA',
            
            // Lado bombas
            'bombas_1' => $request->bombas_1,
            'bombas_2' => $request->bombas_2,
            'bombas_3' => $request->bombas_3,
            'bombas_4' => $request->bombas_4,
            'bombas_5' => $request->bombas_5,
            'bombas_6' => $request->bombas_6,
            'bombas_7' => $request->bombas_7,
            'bombas_8' => $request->bombas_8,
            'bombas_promedio' => $bombasPromedio,
            'bombas_porcentaje' => $bombasPorcentaje,
            
            // Lado vapor
            'vapor_1' => $request->vapor_1,
            'vapor_2' => $request->vapor_2,
            'vapor_3' => $request->vapor_3,
            'vapor_4' => $request->vapor_4,
            'vapor_promedio' => $vaporPromedio,
            'vapor_porcentaje' => $vaporPorcentaje,
            
            // Hodómetro y juego de rodaja
            'hodometro' => $request->hodometro,
            'juego_rodaja_bombas' => $request->juego_rodaja_bombas,
            'juego_rodaja_vapor' => $request->juego_rodaja_vapor,
        ]);

        return redirect()->route('elongaciones.index')
            ->with('success', 'Registro guardado exitosamente');
    }

    public function show(Elongacion $elongacion)
    {
        return view('elongaciones.show', compact('elongacion'));
    }

    public function destroy(Elongacion $elongacion)
    {
        $elongacion->delete();
        return redirect()->route('elongaciones.index')
            ->with('success', 'Registro eliminado exitosamente');
    }
}