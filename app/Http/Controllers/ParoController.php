<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\Paro;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParoController extends Controller
{
    public function index(Request $request): View
    {
        $paros = Paro::query()
            ->with(['linea', 'supervisor', 'planesAccion'])
            ->when($request->filled('linea'), fn ($query) => $query->where('linea_id', $request->integer('linea')))
            ->when($request->filled('tipo'), fn ($query) => $query->where('tipo', $request->string('tipo')->toString()))
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('paros.index', compact('paros'));
    }

    public function create(): View
    {
        $lineas = Linea::query()->orderBy('nombre')->get();

        return view('paros.create', compact('lineas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateParo($request);

        $paro = Paro::create([
            'linea_id' => $validated['linea_id'],
            'fecha_inicio' => $this->normalizeDate($validated['fecha_inicio']),
            'fecha_fin' => $this->normalizeDate($validated['fecha_fin']),
            'tipo' => $validated['tipo'],
            'supervisor_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('paros.show', $paro)
            ->with('status', 'paro-created');
    }

    public function show(Paro $paro): View
    {
        $paro->load(['linea', 'supervisor', 'planesAccion.responsable']);

        return view('paros.show', compact('paro'));
    }

    public function edit(Paro $paro): RedirectResponse
    {
        return redirect()
            ->route('paros.show', $paro)
            ->with('status', 'paro-edit-redirected');
    }

    public function update(Request $request, Paro $paro): RedirectResponse
    {
        $validated = $this->validateParo($request);

        $paro->update([
            'linea_id' => $validated['linea_id'],
            'fecha_inicio' => $this->normalizeDate($validated['fecha_inicio']),
            'fecha_fin' => $this->normalizeDate($validated['fecha_fin']),
            'tipo' => $validated['tipo'],
        ]);

        return redirect()
            ->route('paros.show', $paro)
            ->with('status', 'paro-updated');
    }

    public function destroy(Paro $paro): RedirectResponse
    {
        $paro->delete();

        return redirect()
            ->route('paros.index')
            ->with('status', 'paro-deleted');
    }

    protected function validateParo(Request $request): array
    {
        return $request->validate([
            'linea_id' => ['required', 'exists:lineas,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'tipo' => ['required', 'in:Programado,Emergencia'],
        ]);
    }

    protected function normalizeDate(string $value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
