<?php

namespace App\Http\Controllers;

use App\Models\AnalisisComponente;
use App\Models\Linea;
use App\Models\Componente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\AnalisisComponentesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


class AnalisisComponenteController extends Controller
{
    /**
     * LISTADO + FILTROS
     */
public function index(Request $request) 
{
    $query = AnalisisComponente::with(['linea', 'componente'])
        ->orderBy('fecha_analisis', 'desc');

    // 🔍 Filtros
    if ($request->filled('linea_id')) {
        $query->where('linea_id', $request->linea_id);
    }

    if ($request->filled('componente_id')) {
        $query->where('componente_id', $request->componente_id);
    }

    if ($request->filled('reductor')) {
        $query->where('reductor', $request->reductor);
    }

    if ($request->filled('fecha')) {
        $query->whereMonth('fecha_analisis', substr($request->fecha, 5, 2))
              ->whereYear('fecha_analisis', substr($request->fecha, 0, 4));
    }

    // 📄 Resultados
    $analisis = $query->paginate(10)->withQueryString();

    // ✅ Listas para filtros
    $lineas = Linea::orderBy('nombre')->get();
    $componentes = Componente::orderBy('nombre')->get();

    $reductores = AnalisisComponente::select('reductor')
        ->whereNotNull('reductor')
        ->distinct()
        ->orderBy('reductor')
        ->pluck('reductor');

    // ✅ Retorno con filtros actuales
    return view('analisis-componentes.index', [
        'analisis'    => $analisis,
        'lineas'      => $lineas,
        'componentes' => $componentes,
        'reductores'  => $reductores,
        'filtros'     => $request->all(), // 🔥 AQUÍ LO QUE FALTABA
    ]);
}



    /**
     * SELECCIONAR LAVADORA
     */
    public function selectLinea()
    {
        $lineas = Linea::orderBy('nombre')->get();
        return view('analisis-componentes.select-linea', compact('lineas'));
    }

    /**
     * CREAR ANÁLISIS (CON LAVADORA)
     */
    public function createWithLinea(Linea $linea)
    {
        return view('analisis-componentes.create', [
            'linea' => $linea,
            'componentes' => Componente::orderBy('nombre')->get(),
            'reductores' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4'],
        ]);
    }

    /**
     * GUARDAR ANÁLISIS
     */
  public function store(Request $request)
{
    $validated = $request->validate([
        'linea_id' => 'required|exists:lineas,id',
        'componente_id' => 'required|exists:componentes,id',
        'reductor' => 'required|string|max:255',
        'fecha_analisis' => 'required|date',
        'numero_orden' => 'required|string|max:50',
        'actividad' => 'required|string',
        'evidencia_fotos.*' => 'image|max:2048',
    ]);

    // 📸 Manejo de fotos
    $fotos = [];

    if ($request->hasFile('evidencia_fotos')) {
        foreach ($request->file('evidencia_fotos') as $foto) {
            $fotos[] = $foto->store(
                'evidencias/analisis-componentes',
                'public'
            );
        }
    }

    $validated['evidencia_fotos'] = $fotos;

    // 💾 Guardar análisis
    AnalisisComponente::create($validated);

    // 🔁 Redirección según origen
    if ($request->filled('redirect_to')) {
        return redirect($request->redirect_to)
            ->with('success', 'Análisis agregado correctamente.');
    }

    // 🔙 Redirección por defecto
    return redirect()
        ->route('analisis-componentes.index')
        ->with('success', 'Análisis agregado correctamente.');
}



    /**
     * VER ANÁLISIS
     */
    public function show(AnalisisComponente $analisisComponente)
    {
        $analisisComponente->load(['linea', 'componente']);
        return view('analisis-componentes.show', compact('analisisComponente'));
    }

    /**
     * EDITAR
     */
    public function edit(AnalisisComponente $analisisComponente)
    {
        return view('analisis-componentes.edit', [
            'analisisComponente' => $analisisComponente,
            'lineas' => Linea::orderBy('nombre')->get(),
            'componentes' => Componente::orderBy('nombre')->get(),
            'reductores' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4'],
        ]);
    }

    /**
     * ACTUALIZAR
     */
    public function update(Request $request, AnalisisComponente $analisisComponente)
{
    $validated = $request->validate([
        'linea_id' => 'required|exists:lineas,id',
        'componente_id' => 'required|exists:componentes,id',
        'reductor' => 'required|string|max:255',
        'fecha_analisis' => 'required|date',
        'numero_orden' => 'required|string|max:50',
        'actividad' => 'required|string',
        'evidencia_fotos.*' => 'image|max:2048',
    ]);

    $fotos = $analisisComponente->evidencia_fotos ?? [];

    if ($request->hasFile('evidencia_fotos')) {
        foreach ($request->file('evidencia_fotos') as $foto) {
            $fotos[] = $foto->store('evidencias/analisis-componentes', 'public');
        }
    }

    $validated['evidencia_fotos'] = $fotos;

    $analisisComponente->update($validated);

    return redirect()
        ->route('analisis-componentes.index')
        ->with('success', 'Análisis actualizado correctamente.');
}


    /**
     * ELIMINAR
     */
    public function destroy(AnalisisComponente $analisisComponente)
    {
        foreach ($analisisComponente->evidencia_fotos ?? [] as $foto) {
            Storage::disk('public')->delete($foto);
        }

        $analisisComponente->delete();

        return back()->with('success', 'Análisis eliminado.');
    }

    /**
     * EXPORTAR A EXCEL (CSV)
     */
    public function export(Request $request)
    {
        $query = AnalisisComponente::with(['linea', 'componente'])
            ->orderBy('fecha_analisis', 'desc');

        // mismos filtros que index
        if ($request->filled('linea_id')) {
            $query->where('linea_id', $request->linea_id);
        }

        if ($request->filled('componente_id')) {
            $query->where('componente_id', $request->componente_id);
        }

        if ($request->filled('reductor')) {
            $query->where('reductor', $request->reductor);
        }

        if ($request->filled('fecha')) {
            $query->whereMonth('fecha_analisis', substr($request->fecha, 5, 2))
                  ->whereYear('fecha_analisis', substr($request->fecha, 0, 4));
        }

        $analisis = $query->get();

        $response = new StreamedResponse(function () use ($analisis) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Lavadora',
                'Componente',
                'Reductor',
                'Fecha Análisis',
                'Número Orden',
                'Actividad',
            ]);

            foreach ($analisis as $item) {
                fputcsv($handle, [
                    $item->id,
                    $item->linea->nombre ?? 'Lavadora ' . $item->linea_id,
                    $item->componente->nombre ?? 'N/A',
                    $item->reductor,
                    optional($item->fecha_analisis)->format('d/m/Y'),
                    $item->numero_orden,
                    $item->actividad,
                ]);
            }

            fclose($handle);
        });

        $fileName = 'analisis_componentes_' . now()->format('Ymd_His') . '.csv';

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set(
            'Content-Disposition',
            "attachment; filename={$fileName}"
        );

        return $response;
    }
    public function exportExcel(Request $request)
{
    return Excel::download(
        new AnalisisComponentesExport($request),
        'analisis_componentes.xlsx'
    );
}
    public function exportPdf(Request $request)
{
    $query = AnalisisComponente::with(['linea', 'componente']);

    if ($request->filled('linea_id')) {
        $query->where('linea_id', $request->linea_id);
    }

    if ($request->filled('componente_id')) {
        $query->where('componente_id', $request->componente_id);
    }

    if ($request->filled('reductor')) {
        $query->where('reductor', $request->reductor);
    }

    if ($request->filled('fecha')) {
        $query->whereMonth('fecha_analisis', substr($request->fecha, 5, 2))
              ->whereYear('fecha_analisis', substr($request->fecha, 0, 4));
    }

    $analisisAgrupados = $query->get()
        ->groupBy(fn ($a) => $a->linea->nombre ?? 'Sin línea');

    $pdf = Pdf::loadView('analisis-componentes.export-pdf', compact('analisisAgrupados'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('analisis_componentes.pdf');
}

    public function createQuick(Request $request)
{
    // Validar que venga el contexto
    $request->validate([
        'linea_id' => 'required|exists:lineas,id',
        'componente_id' => 'required|exists:componentes,id',
        'reductor' => 'required|string',
    ]);

    $linea = Linea::findOrFail($request->linea_id);
    $componente = Componente::findOrFail($request->componente_id);
    $reductor = $request->reductor;

    return view('analisis-componentes.create-quick', [
        'linea' => $linea,
        'componente' => $componente,
        'reductor' => $reductor,
        'fecha_sugerida' => now()->toDateString(),
        'redirect_to' => url()->previous(),
    ]);
}

}
