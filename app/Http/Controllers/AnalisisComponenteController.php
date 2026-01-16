<?php

namespace App\Http\Controllers;

use App\Models\AnalisisComponente;
use App\Models\Analisis;
use App\Models\Componente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnalisisComponenteController extends Controller
{
    /**
     * Mostrar formulario para crear un componente específico
     */
    public function create(Analisis $analisis)
    {
        $componentes = Componente::where('activo', true)->get();
        
        return view('analisis-componentes.create', compact('analisis', 'componentes'));
    }

    /**
     * Guardar un nuevo componente para un análisis
     */
    public function store(Request $request, Analisis $analisis)
    {
        $validated = $request->validate([
            'componente_id' => 'required|exists:componentes,id',
            'cantidad_revisada' => 'required|integer|min:0',
            'estado' => 'required|in:BUENO,REGULAR,DAÑADO,REEMPLAZADO',
            'actividad' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'fotos.*' => 'nullable|image|max:5120',
        ]);

        $fotos = [];
        
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('evidencia-analisis', 'public');
                $fotos[] = $path;
            }
        }

        // Crear el componente del análisis
        $analisisComponente = AnalisisComponente::create([
            'analisis_id' => $analisis->id,
            'componente_id' => $validated['componente_id'],
            'cantidad_revisada' => $validated['cantidad_revisada'],
            'estado' => $validated['estado'],
            'actividad' => $validated['actividad'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'evidencia_fotos' => $fotos,
        ]);

        return redirect()
            ->route('analisis.show', $analisis)
            ->with('success', 'Componente agregado exitosamente al análisis');
    }

    /**
     * Mostrar detalles de un componente específico
     */
    public function show(AnalisisComponente $analisisComponente)
    {
        $analisisComponente->load(['analisis', 'componente']);
        
        return view('analisis-componentes.show', compact('analisisComponente'));
    }

    /**
     * Mostrar formulario para editar un componente
     */
    public function edit(AnalisisComponente $analisisComponente)
    {
        $componentes = Componente::where('activo', true)->get();
        
        return view('analisis-componentes.edit', compact('analisisComponente', 'componentes'));
    }

    /**
     * Actualizar un componente
     */
    public function update(Request $request, AnalisisComponente $analisisComponente)
    {
        $validated = $request->validate([
            'componente_id' => 'required|exists:componentes,id',
            'cantidad_revisada' => 'required|integer|min:0',
            'estado' => 'required|in:BUENO,REGULAR,DAÑADO,REEMPLAZADO',
            'actividad' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'fotos.*' => 'nullable|image|max:5120',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'string',
        ]);

        $fotos = $analisisComponente->evidencia_fotos ?? [];
        
        // Eliminar fotos marcadas para eliminar
        if ($request->filled('eliminar_fotos')) {
            foreach ($request->eliminar_fotos as $fotoPath) {
                Storage::disk('public')->delete($fotoPath);
                $fotos = array_filter($fotos, function($foto) use ($fotoPath) {
                    return $foto !== $fotoPath;
                });
            }
        }
        
        // Agregar nuevas fotos
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('evidencia-analisis', 'public');
                $fotos[] = $path;
            }
        }
        
        // Reindexar array
        $fotos = array_values($fotos);

        // Actualizar el componente
        $analisisComponente->update([
            'componente_id' => $validated['componente_id'],
            'cantidad_revisada' => $validated['cantidad_revisada'],
            'estado' => $validated['estado'],
            'actividad' => $validated['actividad'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'evidencia_fotos' => $fotos,
        ]);

        return redirect()
            ->route('analisis.show', $analisisComponente->analisis)
            ->with('success', 'Componente actualizado exitosamente');
    }

    /**
     * Eliminar un componente
     */
    public function destroy(AnalisisComponente $analisisComponente)
    {
        $analisis = $analisisComponente->analisis;
        
        // Eliminar fotos del almacenamiento
        if ($analisisComponente->evidencia_fotos) {
            foreach ($analisisComponente->evidencia_fotos as $foto) {
                Storage::disk('public')->delete($foto);
            }
        }
        
        // Eliminar el componente
        $analisisComponente->delete();

        return redirect()
            ->route('analisis.show', $analisis)
            ->with('success', 'Componente eliminado exitosamente');
    }

    /**
     * API: Obtener componentes de un análisis (para AJAX)
     */
    public function getComponentesByAnalisis(Analisis $analisis)
    {
        $componentes = $analisis->componentes()
            ->with('componente')
            ->get()
            ->map(function ($componente) {
                return [
                    'id' => $componente->id,
                    'componente_id' => $componente->componente_id,
                    'componente_nombre' => $componente->componente->nombre ?? 'N/A',
                    'cantidad_revisada' => $componente->cantidad_revisada,
                    'estado' => $componente->estado,
                    'actividad' => $componente->actividad,
                    'observaciones' => $componente->observaciones,
                    'fotos' => $componente->evidencia_fotos,
                ];
            });

        return response()->json($componentes);
    }

    /**
     * Exportar componente a PDF
     */
    public function exportToPdf(AnalisisComponente $analisisComponente)
    {
        $analisisComponente->load(['analisis', 'componente']);
        
        // Aquí puedes generar el PDF
        // return PDF::loadView('analisis-componentes.pdf', compact('analisisComponente'))->download();
        
        return redirect()->back()->with('info', 'Funcionalidad de PDF en desarrollo');
    }
}