<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AdminRecordNotificationService;
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
        app(AdminRecordNotificationService::class)->registerModelEvents();
        
        // Compartir datos comunes con todas las vistas
        view()->composer('*', function ($view) {
            $user = auth()->user();
            if ($user) {
                $canAccessPasteurizadora = $user->canAccessModule(User::MODULE_PASTEURIZADORA);
                $pasteurizadoraComingSoon = $user->shouldShowPasteurizadoraComingSoon();

                $view->with('userRoles', $user->getRoleNames());
                $view->with('userRoleLabel', $user->role_label);
                $view->with('canAccessPasteurizadora', $canAccessPasteurizadora);
                $view->with('canSeePasteurizadora', $canAccessPasteurizadora || $pasteurizadoraComingSoon);
                $view->with('pasteurizadoraComingSoon', $pasteurizadoraComingSoon);
            }
        });
    }
}
