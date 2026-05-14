<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->hasRole('tecnico')) {
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

            // Rutas exactas permitidas
            'historico-revisados',
            'historico-revisados.index',
            'plan-accion',
            'plan-accion.index',
        ];

        $allowedPrefixes = [
            // Lavadora
            'analisis-lavadora.',

            // Pasteurizadora
            'pasteurizadora.analisis-pasteurizadora.',

            // Histórico revisados
            'historico-revisados.',

            // Plan de acción
            'plan-accion.',
        ];

        if ($routeName && in_array($routeName, $allowedExactRoutes, true)) {
            return $next($request);
        }

        foreach ($allowedPrefixes as $prefix) {
            if ($routeName && Str::startsWith($routeName, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'Los técnicos solo pueden acceder a Lavadora, Pasteurizadora, Histórico Revisados y Plan de Acción.');
    }
}