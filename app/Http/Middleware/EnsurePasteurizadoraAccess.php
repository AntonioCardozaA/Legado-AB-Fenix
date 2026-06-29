<?php

namespace App\Http\Middleware;

use App\Models\Linea;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasteurizadoraAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            !$user
            || $user->canAccessModule(User::MODULE_PASTEURIZADORA)
            || !$this->isPasteurizadoraRequest($request)
        ) {
            return $next($request);
        }

        if ($this->isAllowedPlanActionReadRequest($request, $user)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No tienes permiso para acceder al modulo de Pasteurizadora.',
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('pasteurizadora_bloqueada', 'No tienes permiso para acceder al modulo de Pasteurizadora.');
    }

    private function isPasteurizadoraRequest(Request $request): bool
    {
        $route = $request->route();

        $routeValues = [
            $request->path(),
            $route?->getName(),
            $route?->uri(),
        ];

        foreach ($routeValues as $value) {
            if ($this->containsPasteurizadora($value)) {
                return true;
            }
        }

        foreach (['tipo', 'tipo_equipo', 'export_tipo'] as $key) {
            if ($this->containsPasteurizadora($request->query($key) ?? $request->input($key))) {
                return true;
            }
        }

        foreach (['linea_id', 'lineaId'] as $key) {
            if ($this->lineaIsPasteurizadora($request->query($key) ?? $request->input($key))) {
                return true;
            }
        }

        foreach (($route?->parameters() ?? []) as $key => $value) {
            if (in_array($key, ['linea', 'linea_id', 'lineaId', 'lavadora'], true) && $this->lineaIsPasteurizadora($value)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedPlanActionReadRequest(Request $request, User $user): bool
    {
        $routeName = $request->route()?->getName();

        if (!in_array($routeName, [
            'plan-accion.index',
            'plan-accion.show',
            'plan-accion.dashboard',
            'plan-accion.por-lavadora',
            'plan-accion.notificaciones-pendientes',
        ], true)) {
            return false;
        }

        return $user->canViewPlanActionType(User::MODULE_PASTEURIZADORA);
    }

    private function containsPasteurizadora(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->contains(fn ($item) => $this->containsPasteurizadora($item));
        }

        if (!$value) {
            return false;
        }

        return Str::contains(Str::lower((string) $value), [
            'pasteurizadora',
            'pasteurizadoras',
            'analisis-pasteurizadora',
        ]);
    }

    private function lineaIsPasteurizadora(mixed $value): bool
    {
        if (!$value || !is_numeric($value)) {
            return false;
        }

        return Linea::whereKey((int) $value)
            ->where('nombre', 'like', 'P-%')
            ->exists();
    }
}
