<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use Illuminate\Http\Request;

class LineaController extends Controller
{
    /**
     * Constructor: aplica middleware de autenticación y roles
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|ingeniero_mantenimiento']);
    }

    /**
     * Listar todas las líneas
     */
    public function index()
    {
        $lineas = Linea::all();
        return view('lineas.index', compact('lineas'));
    }

    /**
     * Mostrar formulario para crear nueva línea
     */
    public function create()
    {
        return view('lineas.create');
    }

    /**
     * Guardar nueva línea
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:lineas,nombre',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'required|boolean',
        ]);

        Linea::create($request->only('nombre', 'descripcion', 'activo'));

        return redirect()->route('lineas.index')
                         ->with('success', 'Línea creada correctamente.');
    }

    /**
     * Mostrar detalle de una línea
     */
    public function show(Linea $linea)
    {
        return view('lineas.show', compact('linea'));
    }

    /**
     * Mostrar formulario para editar línea
     */
    public function edit(Linea $linea)
    {
        return view('lineas.edit', compact('linea'));
    }

    /**
     * Actualizar línea
     */
    public function update(Request $request, Linea $linea)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:lineas,nombre,' . $linea->id,
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'required|boolean',
        ]);

        $linea->update($request->only('nombre', 'descripcion', 'activo'));

        return redirect()->route('lineas.index')
                         ->with('success', 'Línea actualizada correctamente.');
    }

    /**
     * Eliminar línea
     */
    public function destroy(Linea $linea)
    {
        $linea->delete();

        return redirect()->route('lineas.index')
                         ->with('success', 'Línea eliminada correctamente.');
    }

    /**
     * Alternar estado activo/inactivo de la línea
     */
    public function toggleActivo(Linea $linea)
    {
        $linea->activo = !$linea->activo;
        $linea->save();

        return redirect()->route('lineas.index')
                         ->with('success', 'Estado de la línea actualizado correctamente.');
    }
}
