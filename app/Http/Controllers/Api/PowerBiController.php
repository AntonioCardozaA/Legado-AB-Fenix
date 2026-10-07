<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Lef52124DataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PowerBiController extends Controller
{
    public function __construct(private readonly Lef52124DataService $dataService)
    {
    }

    public function analisis52124(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linea_id' => ['nullable', 'integer', 'exists:lineas,id'],
            'import_id' => ['nullable', 'integer', 'exists:lef52124_imports,id'],
            'data_date' => ['nullable', 'date_format:Y-m-d'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', 'in:all,52,12,4'],
            'analysis_type' => ['nullable', 'in:all,machines,parts,washer,comparison'],
        ]);

        if (
            isset($validated['data_date'], $validated['fecha'])
            && $validated['data_date'] !== $validated['fecha']
        ) {
            throw ValidationException::withMessages([
                'fecha' => 'Los parametros fecha y data_date deben coincidir cuando se envian juntos.',
            ]);
        }

        if (isset($validated['fecha']) && !isset($validated['data_date'])) {
            $validated['data_date'] = $validated['fecha'];
        }

        unset($validated['fecha']);

        return response()->json([
            'success' => true,
            'generated_at' => now()->toIso8601String(),
            'filters' => [
                'linea_id' => $validated['linea_id'] ?? null,
                'import_id' => $validated['import_id'] ?? null,
                'data_date' => $validated['data_date'] ?? null,
                'period' => $validated['period'] ?? 'all',
                'analysis_type' => $validated['analysis_type'] ?? 'all',
            ],
            'data' => $this->dataService->powerBiPayload($validated),
        ]);
    }
}
