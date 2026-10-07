<?php

namespace App\Http\Controllers;

use App\Models\Lef52124Import;
use App\Models\Linea;
use App\Models\User;
use App\Services\Lef52124DataService;
use App\Services\Lef52124ImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Lef52124Controller extends Controller
{
    public function __construct(private readonly Lef52124DataService $dataService)
    {
    }

    public function index(Request $request): View
    {
        $lineas = $this->dataService->lineas();
        $selectedLineaId = (int) ($request->integer('linea_id') ?: $this->dataService->defaultLineaId($lineas));
        $canManage = $this->canManage($request->user());
        $imports = $canManage
            ? Lef52124Import::with(['linea:id,nombre', 'user:id,name'])
                ->latest()
                ->paginate(10, ['*'], 'importaciones_page')
                ->withQueryString()
                ->fragment('historial-importaciones')
            : collect();

        return view('analisis-52-12-4.index', compact('lineas', 'selectedLineaId', 'canManage', 'imports'));
    }

    public function periods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
        ]);

        return response()->json($this->dataService->periodsPayload((int) $validated['linea_id']));
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linea_id' => ['nullable', 'integer', 'exists:lineas,id'],
            'import_id' => ['nullable', 'integer', 'exists:lef52124_imports,id'],
            'data_date' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', 'in:all,52,12,4'],
            'analysis_type' => ['nullable', 'in:all,machines,parts,washer,comparison'],
        ]);

        return response()->json($this->dataService->dataPayload($validated));
    }

    public function washerMachineTrend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
        ]);

        return response()->json($this->dataService->washerMachineTrendPayload((int) $validated['linea_id']));
    }

    public function store(Request $request, Lef52124ImportService $service): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
            'data_date' => ['required', 'date_format:Y-m-d'],
            'observations' => ['nullable', 'string', 'max:1000'],
            'archivo' => ['required', 'file', 'mimes:xls,xlsx', 'max:20480'],
        ]);

        try {
            $service->import(
                $request->file('archivo'),
                Linea::findOrFail($validated['linea_id']),
                Carbon::createFromFormat('Y-m-d', $validated['data_date']),
                $validated['observations'] ?? null,
                $request->user()
            );
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'archivo' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('lef52124.index', ['linea_id' => $validated['linea_id']])
            ->with('success', 'Importacion 52-12-4 completada correctamente.');
    }

    public function destroy(Request $request, Lef52124Import $import): RedirectResponse
    {
        $this->ensureAdmin($request);
        $lineaId = $import->linea_id;
        $import->delete();

        return redirect()
            ->route('lef52124.index', ['linea_id' => $lineaId])
            ->with('success', 'Importacion eliminada correctamente.');
    }

    private function canManage(?User $user): bool
    {
        return (bool) $user?->hasRole(User::ROLE_ADMIN);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($this->canManage($request->user()), 403, 'Solo administradores pueden gestionar importaciones 52-12-4.');
    }
}
