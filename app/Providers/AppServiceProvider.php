<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
    
    public function boot(): void
    {
        Paginator::useTailwind();
        
        // Compartir datos comunes con todas las vistas
        view()->composer('*', function ($view) {
            $user = auth()->user();
            if ($user) {
                $view->with('userRoles', $user->getRoleNames());
                $view->with('userRoleLabel', $user->role_label);
                $view->with('canAccessPasteurizadora', $user->canAccessModule(User::MODULE_PASTEURIZADORA));
            }
        });
    }
}
