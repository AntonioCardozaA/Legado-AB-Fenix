<?php

$frameworkBasePath = dirname(__DIR__) . '/vendor/laravel/framework/src/Illuminate';

if (is_dir($frameworkBasePath)) {
    // Fallback temporal mientras Composer vuelve a registrar laravel/framework.
    spl_autoload_register(static function (string $class) use ($frameworkBasePath): void {
        $supportPrefix = 'Illuminate\\Support\\';

        if (str_starts_with($class, $supportPrefix)) {
            $relativePath = str_replace('\\', '/', substr($class, strlen($supportPrefix))) . '.php';

            foreach (['Macroable', 'Collections', 'Conditionable', 'Reflection'] as $supportPath) {
                $candidate = $frameworkBasePath . '/' . $supportPath . '/' . $relativePath;

                if (is_file($candidate)) {
                    require_once $candidate;
                    return;
                }
            }
        }

        $frameworkPrefix = 'Illuminate\\';

        if (!str_starts_with($class, $frameworkPrefix)) {
            return;
        }

        $candidate = $frameworkBasePath . '/' . str_replace('\\', '/', substr($class, strlen($frameworkPrefix))) . '.php';

        if (is_file($candidate)) {
            require_once $candidate;
        }
    }, prepend: true);

    foreach ([
        '/Collections/functions.php',
        '/Collections/helpers.php',
        '/Events/functions.php',
        '/Filesystem/functions.php',
        '/Foundation/helpers.php',
        '/Log/functions.php',
        '/Reflection/helpers.php',
        '/Support/functions.php',
        '/Support/helpers.php',
    ] as $helperPath) {
        $fullHelperPath = $frameworkBasePath . $helperPath;

        if (is_file($fullHelperPath)) {
            require_once $fullHelperPath;
        }
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use App\Http\Middleware\EnsureTechnicianAccess;
use App\Http\Middleware\EnsurePasteurizadoraAccess;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
        'role' => RoleMiddleware::class,
        'permission' => PermissionMiddleware::class,
        'role_or_permission' => RoleOrPermissionMiddleware::class,
        'technician.access' => EnsureTechnicianAccess::class,
        'pasteurizadora.access' => EnsurePasteurizadoraAccess::class,
         ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
