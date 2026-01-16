// app/Http/Controllers/PlanAccionController.php
<?php

namespace App\Http\Controllers;

use App\Models\PlanAccion;
use Illuminate\Http\Request;

class PlanAccionController extends Controller
{
    public function actualizarEstado(Request $request, PlanAccion $plan)
    {
        $request->validate([
            'estado' => 'required|in:PENDIENTE,EN_PROCESO,COMPLETADA,ATRASADA',
            'observaciones_dano' => 'nullable|string',
            'encontro_dano' => 'boolean',
        ]);
        
        $plan->update([
            'estado' => $request->estado,
            'encontro_dano' => $request->encontro_dano ?? false,
            'observaciones_dano' => $request->observaciones_dano,
            'fecha_ejecucion' => $request->estado == 'COMPLETADA' ? now() : null,
        ]);
        
        return back()->with('success', 'Estado actualizado');
    }
}