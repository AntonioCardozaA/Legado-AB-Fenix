<?php

namespace App\Http\Controllers;

use App\Models\Paro;
use App\Models\Linea;
use App\Models\PlanAccion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ParoController extends Controller
{
    public function index()
    {
        $paros = Paro::with(['linea', 'supervisor', 'planesAccion'])
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(15);
        
        return view('paros.index', compact('paros'));
    }
    
    public function create()
    {
        $lineas = Linea::where('activa', true)->get();
        return view('paros.create', compact('lineas'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'linea_id' => 'required|exists:lineas,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'tipo' => 'required|in:Programado,Emergencia',
            'descripcion' => 'nullable|string',
        ]);
        
        $paro = Paro::create([
            'linea_id' => $validated['linea_id'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'tipo' => $validated['tipo'],
            'supervisor_id' => auth()->id(),
            'descripcion' => $validated['descripcion'],
        ]);
        
        return redirect()->route('paros.show', $paro)
            ->with('success', 'Paro registrado exitosamente');
    }
    
    public function show(Paro $paro)
    {
        $paro->load(['linea', 'supervisor', 'planesAccion.responsable']);
        return view('paros.show', compact('paro'));
    }
    
    public function agregarPlanAccion(Request $request, Paro $paro)
    {
        $validated = $request->validate([
            'actividad' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_planeada' => 'required|date',
            'responsable_id' => 'required|exists:users,id',
            'plan_referencia' => 'nullable|string',
        ]);
        
        $plan = PlanAccion::create([
            'paro_id' => $paro->id,
            'actividad' => $validated['actividad'],
            'descripcion' => $validated['descripcion'],
            'fecha_planeada' => $validated['fecha_planeada'],
            'estado' => 'PENDIENTE',
            'responsable_id' => $validated['responsable_id'],
            'plan_referencia' => $validated['plan_referencia'],
        ]);
        
        return back()->with('success', 'Plan de acción agregado');
    }
}