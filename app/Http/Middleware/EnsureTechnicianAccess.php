<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('tecnico')) {
            return $next($request);
        }

        if ($user->hasAnyRole(['admin', 'ingeniero_mantenimiento', 'supervisor'])) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        $allowedExactRoutes = [
            'dashboard',
            'tecnico.dashboard',
            'dashboard.global.lavadoras',
            'dashboard.global.pasteurizadoras',
            'dashboard.operativo.lavadora',
            'dashboard.operativo.pasteurizadora',
            'dashboard_lavadora',
            'dashboard_pasteurizadora',
            'lavadora.dashboard',
            'pasteurizadora.dashboard',
            'profile.edit',
            'profile.update',
            'profile.destroy',
        ];

        $allowedPrefixes = [
            'analisis-lavadora.',
            'pasteurizadora.analisis-pasteurizadora.',
        ];

        if (in_array($routeName, $allowedExactRoutes, true)) {
            return $next($request);
        }

        foreach ($allowedPrefixes as $prefix) {
            if ($routeName && str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'Los tecnicos solo pueden acceder a Lavadora y Pasteurizadora.');
    }
}
