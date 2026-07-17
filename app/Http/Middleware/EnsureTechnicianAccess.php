<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AccessPermissionCatalog;
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
        $customPermission = AccessPermissionCatalog::permissionForRoute($routeName, $request->method());

        if (
            $customPermission
            && $user->usesCustomPermissionAccess()
            && $user->canUseCustomPermission($customPermission)
        ) {
            return $next($request);
        }

        if ($routeName && Str::startsWith($routeName, 'reportes.')) {
            return $this->denyReportesAccess($request);
        }

        if ($routeName && Str::startsWith($routeName, 'admin.costos.')) {
            abort_unless(
                $user->canAccessLavadoraCosts(),
                403,
                'No tienes permiso para acceder al modulo de Costos.'
            );

            return $next($request);
        }

        $allowedExactRoutes = [
            'dashboard',
            'tecnico.dashboard',

            'dashboard.global.lavadoras',
            'dashboard.global.etiquetadoras',
            'dashboard.operativo.lavadora',

            'dashboard_lavadora',
            'dashboard_etiquetadora',
            'lavadora.dashboard',
            'lavadora.costos.index',
            'etiquetadora.dashboard',

            'profile.edit',
            'profile.update',
            'profile.destroy',
            'profile.notifications',
            'profile.notifications.update',

            'notificaciones.configuracion',
            'notificaciones.configuracion.update',
            'notificaciones.verify.phone',

            'notifications.index',
            'notifications.open',
            'notifications.read',
            'notifications.read-all',
            'notifications.unread-count',

            // Rutas exactas permitidas
            'historico-revisados',
            'historico-revisados.index',
            'plan-accion',
            'plan-accion.index',
        ];

        $allowedPrefixes = [
            // Lavadora
            'analisis-lavadora.',
            'analisis-tendencia-mensual.lavadora.',

            // Etiquetadora
            'analisis-etiquetadora.',
            'api.etiquetadora.',

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

        abort(403, 'Este rol solo puede acceder a Lavadora, Etiquetadora, Historico Revisados y Plan de Accion.');
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
