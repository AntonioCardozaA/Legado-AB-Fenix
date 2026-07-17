<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Services\LavadoraCostAnalyticsService;
use App\Support\LavadoraCostSupport;
use Illuminate\Http\Request;

class LavadoraCostController extends Controller
{
    public function index(Request $request, LavadoraCostAnalyticsService $analytics)
    {
        abort_unless(
            $request->user()?->canAccessLavadoraCosts(),
            403,
            'No tienes permiso para acceder al modulo de Costos.'
        );

        $filters = $request->validate([
            'preset' => 'nullable|in:mensual,trimestral,semestral,anual,custom',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'linea_id' => 'nullable|exists:lineas,id',
            'budget_year' => 'nullable|integer|min:2024|max:2100',
        ]);

        $lineas = Linea::query()
            ->whereIn('nombre', LavadoraCostSupport::LAVADORA_LINEAS)
            ->orderBy('nombre')
            ->get();

        return view('lavadora.costos.index', [
            'lineas' => $lineas,
            'dashboard' => $analytics->dashboardData($filters),
            'presets' => [
                'mensual' => 'Mensual',
                'trimestral' => 'Trimestral',
                'semestral' => 'Semestral',
                'anual' => 'Anual',
                'custom' => 'Rango personalizado',
            ],
            'budgetYears' => range(now()->year - 1, now()->year + 2),
        ]);
    }
}
