<?php

namespace App\Support;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthRedirects
{
    private const AUTH_ROUTE_NAMES = [
        'login',
        'logout',
        'register',
    ];

    private const AUTH_ROUTE_PREFIXES = [
        'password.',
        'verification.',
    ];

    private const AUTH_PATH_PREFIXES = [
        '/login',
        '/logout',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/confirm-password',
        '/password',
        '/verify-email',
        '/email/verification-notification',
    ];

    public static function rememberCurrentUrlAsIntended(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $route = $request->route();
        $path = self::normalizePath('/'.$request->path());

        if (
            $request->isMethod('GET')
            && $route instanceof RoutingRoute
            && ! self::isAuthenticationPath($path)
            && ! self::isAuthenticationRoute($route)
        ) {
            $request->session()->put('url.intended', $request->fullUrl());

            return;
        }

        $request->session()->forget('url.intended');
    }

    public static function consumeSafeIntendedUrl(Request $request, string $fallback): string
    {
        $intended = $request->session()->get('url.intended');

        $request->session()->forget('url.intended');

        if (! is_string($intended) || trim($intended) === '') {
            return $fallback;
        }

        $components = parse_url($intended);

        if ($components === false || ! self::hasAllowedHost($request, $components)) {
            return $fallback;
        }

        $path = self::normalizePath($components['path'] ?? '/');

        if (self::isAuthenticationPath($path) || ! self::matchesProtectedGetRoute($path)) {
            return $fallback;
        }

        $query = isset($components['query']) ? '?'.$components['query'] : '';
        $fragment = isset($components['fragment']) ? '#'.$components['fragment'] : '';

        return $path.$query.$fragment;
    }

    private static function hasAllowedHost(Request $request, array $components): bool
    {
        $scheme = isset($components['scheme']) ? strtolower((string) $components['scheme']) : null;

        if ($scheme !== null && ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (! isset($components['host'])) {
            return true;
        }

        $host = strtolower((string) $components['host']);
        $requestHost = strtolower($request->getHost());

        if (! hash_equals($requestHost, $host)) {
            return false;
        }

        return ! isset($components['port']) || (int) $components['port'] === $request->getPort();
    }

    private static function matchesProtectedGetRoute(string $path): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
            return false;
        }

        return ! self::isAuthenticationRoute($route) && self::routeUsesAuth($route);
    }

    private static function routeUsesAuth(RoutingRoute $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (
                $middleware === 'auth'
                || str_starts_with($middleware, 'auth:')
                || $middleware === Authenticate::class
                || str_starts_with($middleware, Authenticate::class.':')
            ) {
                return true;
            }
        }

        return false;
    }

    private static function isAuthenticationRoute(RoutingRoute $route): bool
    {
        $routeName = $route->getName();

        if (! is_string($routeName)) {
            return false;
        }

        if (in_array($routeName, self::AUTH_ROUTE_NAMES, true)) {
            return true;
        }

        foreach (self::AUTH_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function isAuthenticationPath(string $path): bool
    {
        $path = strtolower(self::normalizePath($path));

        foreach (self::AUTH_PATH_PREFIXES as $authPath) {
            if ($path === $authPath || str_starts_with($path, $authPath.'/')) {
                return true;
            }
        }

        return false;
    }

    private static function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');

        return $path === '' ? '/' : '/'.$path;
    }
}
