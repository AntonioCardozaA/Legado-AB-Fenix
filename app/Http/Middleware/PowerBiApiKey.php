<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PowerBiApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('services.powerbi.api_key', '');
        $providedKey = (string) $request->header('X-API-KEY', '');

        if ($configuredKey === '' || $providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'message' => 'No autorizado',
            ], 401);
        }

        return $next($request);
    }
}
