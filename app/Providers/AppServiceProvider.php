<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }
    
    public function boot()
    {
        Paginator::useTailwind();
        
        // Compartir datos comunes con todas las vistas
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $view->with('userRoles', auth()->user()->getRoleNames());
            }
        });
    }
}