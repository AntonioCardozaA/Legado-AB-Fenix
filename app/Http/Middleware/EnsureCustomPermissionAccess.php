<?php

namespace App\Http\Middleware;

use App\Support\AccessPermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomPermissionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $permission = AccessPermissionCatalog::permissionForRoute(
            $request->route()?->getName(),
            $request->method()
        );
        $routeName = $request->route()?->getName();

        if ($user?->usesPasteurizadoraExcentricosAccessProfile()
            && in_array($routeName, [
                'pasteurizadora.analisis-pasteurizadora.excentricos.index',
                'pasteurizadora.analisis-pasteurizadora.create-quick',
                'pasteurizadora.analisis-pasteurizadora.store-quick',
            ], true)
        ) {
            return $next($request);
        }

        if (!$user || !$permission || $user->canUseCustomPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No cuenta con permisos suficientes para acceder a esta vista o ejecutar esta accion.',
            ], 403);
        }

        abort(403, 'No cuenta con permisos suficientes para acceder a esta vista o ejecutar esta accion.');
    }
}
