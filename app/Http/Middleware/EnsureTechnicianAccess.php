<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianAccess
{
    private const REPORTES_RESTRINGIDOS_MESSAGE = 'No cuentas con los permisos necesarios para visualizar los reportes.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->usesTechnicianAccessProfile()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && Str::startsWith($routeName, 'reportes.')) {
            return $this->denyReportesAccess($request);
        }

        $allowedExactRoutes = [
            'dashboard',
            'tecnico.dashboard',

            'dashboard.global.lavadoras',
            'dashboard.operativo.lavadora',

            'dashboard_lavadora',
            'lavadora.dashboard',

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

        abort(403, 'Este rol solo puede acceder a Lavadora, Historico Revisados y Plan de Accion.');
    }

    private function denyReportesAccess(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => self::REPORTES_RESTRINGIDOS_MESSAGE,
            ], 403);
        }

        return redirect()
            ->route('tecnico.dashboard')
            ->with('acceso_restringido', self::REPORTES_RESTRINGIDOS_MESSAGE);
    }
}
