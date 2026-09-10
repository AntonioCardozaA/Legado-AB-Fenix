<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasteurizadoraExcentricosCaptureAccess
{
    private const ALLOWED_ROUTES = [
        'pasteurizadora.analisis-pasteurizadora.excentricos.index',
        'pasteurizadora.analisis-pasteurizadora.create-quick',
        'pasteurizadora.analisis-pasteurizadora.store-quick',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user?->usesPasteurizadoraExcentricosAccessProfile()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        $message = 'Este acceso solo permite registrar y consultar Excentricos de Pasteurizadoras.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        return redirect()
            ->route('pasteurizadora.analisis-pasteurizadora.excentricos.index')
            ->with('acceso_restringido', $message);
    }
}
