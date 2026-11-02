<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Models\User;
use App\Services\AdminRecordNotificationService;
use App\Services\Maintenance\FailoverAiProvider;
use App\Services\Maintenance\GeminiProvider;
use App\Services\Maintenance\MaintenanceHistoryIndexer;
use App\Services\Maintenance\NullAiProvider;
use App\Services\Maintenance\OpenAiProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    private const COMMON_CONTEXT_VIEWS = [
        'analisis.show',
        'dashboard_etiquetadora',
        'dashboard_lavadora',
        'dashboard_pasteurizadora',
        'tecnico.dashboard',
        'layouts.app',
        'layouts.navigation',
        'lavadora.analisis-lavadora.show',
        'lavadora.analisis-lavadora.index',
        'lavadora.analisis-lavadora.edit',
        'etiquetadora.analisis-etiquetadora.show',
        'etiquetadora.analisis-etiquetadora.index',
        'etiquetadora.analisis-etiquetadora.partials.tabla-industrial',
        'etiquetadora.analisis-etiquetadora.partials.modals',
        'historico-revisados.index',
        'reportes.index',
        'reportes.paros',
        'pasteurizadora.analisis-pasteurizadora.show',
        'pasteurizadora.analisis-pasteurizadora.index',
    ];

    public function register(): void
    {
        $this->app->singleton(AiProviderInterface::class, function ($app) {
            if (!(bool) config('maintenance_ai.enabled', false)) {
                return $app->make(NullAiProvider::class);
            }

            return $app->make(FailoverAiProvider::class);
        });
    }
    
    public function boot(): void
    {
        RateLimiter::for('assistant-chat', function (Request $request) {
            $limit = max(1, (int) config('maintenance_ai.chat.rate_limit_per_minute', 12));
            $userKey = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($limit)->by('assistant-chat:' . $userKey);
        });

        Paginator::useTailwind();
        app(AdminRecordNotificationService::class)->registerModelEvents();
        app(MaintenanceHistoryIndexer::class)->registerModelEvents();
        
        // Only consumers need this context. Recompute for the current user on every composition.
        view()->composer(self::COMMON_CONTEXT_VIEWS, function ($view) {
            $user = auth()->user();
            if ($user) {
                $canAccessPasteurizadora = $user->canAccessModule(User::MODULE_PASTEURIZADORA);
                $pasteurizadoraComingSoon = $user->shouldShowPasteurizadoraComingSoon();

                $view->with('userRoles', $user->getRoleNames());
                $view->with('userRoleLabel', $user->role_label);
                $view->with('canAccessPasteurizadora', $canAccessPasteurizadora);
                $view->with('canSeePasteurizadora', $canAccessPasteurizadora || $pasteurizadoraComingSoon);
                $view->with('pasteurizadoraComingSoon', $pasteurizadoraComingSoon);
                $view->with('canDeleteAnalysis', $user->canDeleteAnalysis());
            }
        });
    }
}
