<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use Illuminate\Http\Request;

class ElongacionController extends Controller
{
    public function create(Analisis $analisis)
    {
        return view('analisis.elongacion.create', compact('analisis'));
    }

    public function store(Request $request, Analisis $analisis)
    {
        $bombas = array_filter($request->bombas_mediciones ?? []);
        $vapor  = array_filter($request->vapor_mediciones ?? []);

        $resBombas = $this->calcularElongacion($bombas);
        $resVapor  = $this->calcularElongacion($vapor);

        $analisis->elongacion()->create([
            'horometro' => $request->horometro,
            'mediciones_bombas' => $bombas,
            'mediciones_vapor' => $vapor,
            'juego_rodaja_bombas' => $request->juego_rodaja_bombas,
            'juego_rodaja_vapor' => $request->juego_rodaja_vapor,
            'elongacion_bombas_mm' => $resBombas['mm'],
            'elongacion_bombas_pct' => $resBombas['pct'],
            'estado_bombas' => $resBombas['estado'],
            'elongacion_vapor_mm' => $resVapor['mm'],
            'elongacion_vapor_pct' => $resVapor['pct'],
            'estado_vapor' => $resVapor['estado'],
        ]);

        return redirect()->route('analisis.show', $analisis)
            ->with('success','Elongación calculada correctamente');
    }

    private function calcularElongacion(array $mediciones)
    {
        $paso = 173;

        if (count($mediciones) === 0) {
            return ['mm'=>0,'pct'=>0,'estado'=>'SIN DATOS'];
        }

        $promedio = array_sum($mediciones)/count($mediciones);
        $elongacionMm = $promedio - $paso;
        $elongacionPct = ($elongacionMm/$paso)*100;

        if ($elongacionPct <= 2) $estado='BUENO';
        elseif ($elongacionPct <=3) $estado='ALERTA';
        else $estado='CRITICO';

        return [
            'mm'=>round($elongacionMm,2),
            'pct'=>round($elongacionPct,2),
            'estado'=>$estado
        ];
    }
}
