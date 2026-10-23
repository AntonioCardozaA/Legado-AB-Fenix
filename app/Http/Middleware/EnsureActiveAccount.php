<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $version = (int) $user->session_version;
        $sessionVersion = $request->session()->get('auth_session_version');

        // Legacy sessions are accepted only until the first account revocation.
        // A valid remember-me cookie can start a new session after a fresh login.
        $validVersion = $sessionVersion !== null
            ? (int) $sessionVersion === $version
            : ($version === 0 || Auth::guard('web')->viaRemember());

        if ($user->activo && $validVersion) {
            $request->session()->put('auth_session_version', $version);

            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'Tu acceso fue revocado. Vuelve a iniciar sesion o contacta al administrador.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('login')->with('status', $message);
    }
}
