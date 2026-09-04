<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\AnalisisEtiquetadora;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Support\AccessPermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    private const EMPTY_QUERY_LIMIT = 10;
    private const DEFAULT_QUERY_LIMIT = 40;
    private const MAX_QUERY_LIMIT = 50;

    private const HIDDEN_SEARCH_ROUTE_PATTERNS = [
        'api.*',
        'cron.*',
        'dashboard.alias',
        'diagramas.animados',
        'global-search.index',
        'historico-revisados.check-reset-status',
        'login',
        'logout',
        'password.*',
        'register',
        'storage.*',
        'test-*',
        'up',
        'verification.*',
        'welcome',
        '*.ajax.*',
        '*.get-*',
        '*.unread-count',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_QUERY_LIMIT],
        ]);

        $user = $request->user();
        $query = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? ($query === '' ? self::EMPTY_QUERY_LIMIT : self::DEFAULT_QUERY_LIMIT));

        $items = $this->shortcutResults($user, $query);

        if (mb_strlen($query) >= 2) {
            $items = $items
                ->merge($this->lineResults($user, $query))
                ->merge($this->washerAnalysisResults($user, $query))
                ->merge($this->labelerAnalysisResults($user, $query))
                ->merge($this->pasteurizerAnalysisResults($user, $query))
                ->merge($this->actionPlanResults($user, $query))
                ->merge($this->elongationResults($user, $query))
                ->merge($this->legacyAnalysisResults($user, $query));
        }

        return response()->json([
            'query' => $query,
            'items' => $items->take($limit)->values(),
        ]);
    }

    private function shortcutResults(User $user, string $query): Collection
    {
        $shortcuts = collect($this->shortcutDefinitions())
            ->merge($this->routeShortcutDefinitions())
            ->unique(fn (array $shortcut): string => $this->shortcutIdentity($shortcut))
            ->values();

        return $shortcuts
            ->filter(fn (array $shortcut): bool => $this->canOpenShortcut($user, $shortcut))
            ->filter(fn (array $shortcut): bool => $this->matchesShortcut($shortcut, $query))
            ->map(fn (array $shortcut): array => $this->formatShortcut($shortcut))
            ->unique('url')
            ->values();
    }

    private function shortcutDefinitions(): array
    {
        return [
            [
                'title' => 'Dashboard principal',
                'description' => 'Vista general del sistema',
                'section' => 'Acceso rapido',
                'icon' => 'fa-chart-line',
                'route' => ['dashboard'],
                'permission' => 'ver dashboard principal',
                'keywords' => ['dashboard', 'inicio', 'principal', 'general'],
            ],
            [
                'title' => 'Lavadora',
                'description' => 'Dashboard y operacion de lavadoras',
                'section' => 'Modulo',
                'icon' => 'fa-droplet',
                'route' => ['lavadora.dashboard'],
                'permission' => 'ver dashboard lavadoras',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['lavadora', 'lavadoras', 'lavado'],
            ],
            [
                'title' => 'Etiquetadora',
                'description' => 'Dashboard y analisis de etiquetadoras',
                'section' => 'Modulo',
                'icon' => 'fa-tags',
                'route' => ['etiquetadora.dashboard'],
                'permission' => 'ver dashboard etiquetadoras',
                'module' => User::MODULE_ETIQUETADORA,
                'keywords' => ['etiquetadora', 'etiquetadoras', 'etiquetas'],
            ],
            [
                'title' => 'Pasteurizadora',
                'description' => 'Dashboard de pasteurizadoras',
                'section' => 'Modulo',
                'icon' => 'fa-temperature-high',
                'route' => ['pasteurizadora.dashboard'],
                'permission' => 'ver dashboard pasteurizadoras',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['pasteurizadora', 'pasteurizadoras', 'mecanica', 'hidraulica'],
            ],
            [
                'title' => 'Plan de accion',
                'description' => 'Seguimiento de actividades y pendientes',
                'section' => 'Operacion',
                'icon' => 'fa-list-check',
                'route' => ['plan-accion.index'],
                'can' => fn (User $user): bool => $user->canViewPlanActionType(User::MODULE_LAVADORA),
                'keywords' => ['plan', 'accion', 'actividad', 'pendiente', 'pcm'],
            ],
            [
                'title' => 'Reportes',
                'description' => 'Reportes y exportaciones',
                'section' => 'Consulta',
                'icon' => 'fa-chart-bar',
                'route' => ['reportes.index'],
                'permission' => 'ver reportes',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'reportes', 'pdf', 'excel', 'exportar'],
            ],
            [
                'title' => 'Elongaciones',
                'description' => 'Mediciones y ciclos de cadenas',
                'section' => 'Lavadora',
                'icon' => 'fa-link',
                'route' => ['elongaciones.index'],
                'permission' => 'ver elongaciones',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['elongacion', 'elongaciones', 'cadena', 'ciclo'],
            ],
            [
                'title' => 'Historico de revisados',
                'description' => 'Consulta de componentes revisados',
                'section' => 'Consulta',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['historico-revisados.index'],
                'permission' => 'ver historico revisados',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['historico', 'revisados', 'historial'],
            ],
            [
                'title' => 'Notificaciones',
                'description' => 'Centro de avisos del sistema',
                'section' => 'Usuario',
                'icon' => 'fa-bell',
                'route' => ['notifications.index'],
                'permission' => 'ver notificaciones',
                'keywords' => ['notificacion', 'notificaciones', 'avisos', 'alertas'],
            ],
            [
                'title' => 'Configurar notificaciones',
                'description' => 'Preferencias personales de avisos',
                'section' => 'Usuario',
                'icon' => 'fa-sliders',
                'route' => ['notificaciones.configuracion'],
                'permission' => 'ver perfil',
                'keywords' => ['configurar', 'notificaciones', 'preferencias'],
            ],
            [
                'title' => 'Control de gastos',
                'description' => 'Costos, catalogo y presupuestos de lavadora',
                'section' => 'Administracion',
                'icon' => 'fa-coins',
                'route' => ['admin.costos.index'],
                'can' => fn (User $user): bool => $user->canAccessLavadoraCosts(),
                'keywords' => ['costos', 'gastos', 'presupuesto', 'catalogo'],
            ],
            [
                'title' => 'Gestion de usuarios',
                'description' => 'Usuarios, roles y permisos',
                'section' => 'Administracion',
                'icon' => 'fa-user-shield',
                'route' => ['admin.users.index'],
                'permission' => 'gestionar usuarios',
                'keywords' => ['usuarios', 'roles', 'permisos', 'admin'],
            ],
            [
                'title' => 'Observabilidad IA',
                'description' => 'Metricas y trazas de la IA operativa',
                'section' => 'Administracion',
                'icon' => 'fa-brain',
                'route' => ['admin.ai-observability.index'],
                'can' => fn (User $user): bool => $user->canViewAiObservability(),
                'keywords' => ['ia', 'inteligencia', 'observabilidad', 'metricas'],
            ],
            [
                'title' => 'Dashboard tecnico',
                'description' => 'Panel compartido para tecnicos e ingenieros',
                'section' => 'Dashboards',
                'icon' => 'fa-screwdriver-wrench',
                'route' => ['tecnico.dashboard'],
                'permission' => 'ver dashboard tecnico',
                'keywords' => ['tecnico', 'ingeniero', 'dashboard', 'inicio'],
            ],
            [
                'title' => 'Dashboard global Lavadora',
                'description' => 'Resumen global de lavadoras',
                'section' => 'Dashboards',
                'icon' => 'fa-droplet',
                'route' => ['dashboard.global.lavadoras'],
                'permission' => 'ver dashboard lavadoras',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['lavadora', 'lavadoras', 'global', 'resumen'],
            ],
            [
                'title' => 'Dashboard operativo Lavadora',
                'description' => 'Vista operativa de lavadoras',
                'section' => 'Dashboards',
                'icon' => 'fa-gauge-high',
                'route' => ['dashboard.operativo.lavadora'],
                'permission' => 'ver dashboard lavadoras',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['lavadora', 'operativo', 'dashboard', 'estado'],
            ],
            [
                'title' => 'Dashboard global Etiquetadora',
                'description' => 'Resumen global de etiquetadoras',
                'section' => 'Dashboards',
                'icon' => 'fa-tags',
                'route' => ['dashboard.global.etiquetadoras'],
                'permission' => 'ver dashboard etiquetadoras',
                'module' => User::MODULE_ETIQUETADORA,
                'keywords' => ['etiquetadora', 'etiquetadoras', 'global', 'resumen'],
            ],
            [
                'title' => 'Dashboard global Pasteurizadora',
                'description' => 'Resumen global de pasteurizadoras',
                'section' => 'Dashboards',
                'icon' => 'fa-temperature-high',
                'route' => ['dashboard.global.pasteurizadoras'],
                'permission' => 'ver dashboard pasteurizadoras',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['pasteurizadora', 'pasteurizadoras', 'global', 'resumen'],
            ],
            [
                'title' => 'Dashboard operativo Pasteurizadora',
                'description' => 'Vista operativa de pasteurizadoras',
                'section' => 'Dashboards',
                'icon' => 'fa-gauge-high',
                'route' => ['dashboard.operativo.pasteurizadora'],
                'permission' => 'ver dashboard pasteurizadoras',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['pasteurizadora', 'operativo', 'dashboard', 'estado'],
            ],
            [
                'title' => 'Analisis Lavadora',
                'description' => 'Listado de analisis de lavadoras',
                'section' => 'Lavadora',
                'icon' => 'fa-clipboard-list',
                'route' => ['analisis-lavadora.index'],
                'permission' => 'ver analisis lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['analisis', 'lavadora', 'listado', 'revision'],
            ],
            [
                'title' => 'Nuevo analisis Lavadora',
                'description' => 'Seleccion de linea para capturar analisis',
                'section' => 'Lavadora',
                'icon' => 'fa-plus-circle',
                'route' => ['analisis-lavadora.select-linea'],
                'permission' => 'crear analisis lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['crear', 'nuevo', 'analisis', 'lavadora', 'captura'],
            ],
            [
                'title' => 'Captura rapida Lavadora',
                'description' => 'Formulario rapido de analisis de lavadora',
                'section' => 'Lavadora',
                'icon' => 'fa-bolt',
                'route' => ['analisis-lavadora.create-quick'],
                'permission' => 'crear analisis lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['captura', 'rapida', 'crear', 'analisis', 'lavadora'],
            ],
            [
                'title' => 'Historial analisis Lavadora',
                'description' => 'Historial de registros de lavadoras',
                'section' => 'Lavadora',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['analisis-lavadora.historial'],
                'permission' => 'ver analisis lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['historial', 'historico', 'analisis', 'lavadora'],
            ],
            [
                'title' => 'Analisis Etiquetadora',
                'description' => 'Listado de analisis de etiquetadoras',
                'section' => 'Etiquetadora',
                'icon' => 'fa-tags',
                'route' => ['analisis-etiquetadora.index'],
                'permission' => 'ver analisis etiquetadora',
                'module' => User::MODULE_ETIQUETADORA,
                'keywords' => ['analisis', 'etiquetadora', 'etiquetas', 'revision'],
            ],
            [
                'title' => 'Nuevo analisis Etiquetadora',
                'description' => 'Seleccion de linea para capturar analisis',
                'section' => 'Etiquetadora',
                'icon' => 'fa-plus-circle',
                'route' => ['analisis-etiquetadora.select-linea'],
                'permission' => 'crear analisis etiquetadora',
                'module' => User::MODULE_ETIQUETADORA,
                'keywords' => ['crear', 'nuevo', 'analisis', 'etiquetadora'],
            ],
            [
                'title' => 'Historial analisis Etiquetadora',
                'description' => 'Historial de registros de etiquetadoras',
                'section' => 'Etiquetadora',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['analisis-etiquetadora.historial'],
                'permission' => 'ver analisis etiquetadora',
                'module' => User::MODULE_ETIQUETADORA,
                'keywords' => ['historial', 'historico', 'analisis', 'etiquetadora'],
            ],
            [
                'title' => 'Analisis Pasteurizadora Mecanica',
                'description' => 'Listado de analisis mecanicos',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-temperature-high',
                'route' => ['pasteurizadora.analisis-pasteurizadora.index'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA),
                'keywords' => ['analisis', 'pasteurizadora', 'mecanica', 'componentes'],
            ],
            [
                'title' => 'Nuevo analisis Pasteurizadora',
                'description' => 'Seleccion de linea para analisis mecanico',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-plus-circle',
                'route' => ['pasteurizadora.analisis-pasteurizadora.select-linea'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA)
                    && $user->canUseCustomPermission('crear analisis pasteurizadora'),
                'keywords' => ['crear', 'nuevo', 'analisis', 'pasteurizadora', 'mecanica'],
            ],
            [
                'title' => 'Captura rapida Pasteurizadora',
                'description' => 'Formulario rapido de analisis mecanico',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-bolt',
                'route' => ['pasteurizadora.analisis-pasteurizadora.create-quick'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA)
                    && $user->canUseCustomPermission('crear analisis pasteurizadora'),
                'keywords' => ['captura', 'rapida', 'analisis', 'pasteurizadora', 'mecanica'],
            ],
            [
                'title' => 'Historial Pasteurizadora Mecanica',
                'description' => 'Historial de analisis mecanicos',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['pasteurizadora.analisis-pasteurizadora.historial'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA),
                'keywords' => ['historial', 'historico', 'pasteurizadora', 'mecanica'],
            ],
            [
                'title' => 'Formulario Pasteurizadora Mecanica',
                'description' => 'Formulario completo de analisis mecanico',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-clipboard-list',
                'route' => ['pasteurizadora.analisis-pasteurizadora.create-legacy'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA)
                    && $user->canUseCustomPermission('crear analisis pasteurizadora'),
                'keywords' => ['formulario', 'crear', 'analisis', 'pasteurizadora', 'mecanica'],
            ],
            [
                'title' => 'Historico revisados Pasteurizadora Mecanica',
                'description' => 'Componentes revisados del analisis mecanico',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['pasteurizadora.analisis-pasteurizadora.historico-revisados'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA),
                'keywords' => ['historico', 'revisados', 'pasteurizadora', 'mecanica', 'componentes'],
            ],
            [
                'title' => 'Plan de accion Pasteurizadora Mecanica',
                'description' => 'Planes asociados al analisis mecanico',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-list-check',
                'route' => ['pasteurizadora.analisis-pasteurizadora.plan-accion.index'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA)
                    && $user->canViewPlanActionType(User::MODULE_PASTEURIZADORA),
                'keywords' => ['plan', 'accion', 'pasteurizadora', 'mecanica'],
            ],
            [
                'title' => 'Crear plan Pasteurizadora Mecanica',
                'description' => 'Formulario de plan para analisis mecanico',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-plus-circle',
                'route' => ['pasteurizadora.analisis-pasteurizadora.plan-accion.create'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA)
                    && $user->canUseCustomPermission('editar analisis pasteurizadora'),
                'keywords' => ['crear', 'plan', 'accion', 'pasteurizadora', 'mecanica'],
            ],
            [
                'title' => 'Central hidraulica',
                'description' => 'Analisis de central hidraulica',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-oil-can',
                'route' => ['pasteurizadora.central-hidraulica.index'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA),
                'keywords' => ['central', 'hidraulica', 'aceite', 'pasteurizadora'],
            ],
            [
                'title' => 'Nuevo analisis Central hidraulica',
                'description' => 'Seleccion de linea para central hidraulica',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-plus-circle',
                'route' => ['pasteurizadora.central-hidraulica.select-linea'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA)
                    && $user->canUseCustomPermission('crear analisis pasteurizadora'),
                'keywords' => ['crear', 'nuevo', 'central', 'hidraulica', 'pasteurizadora'],
            ],
            [
                'title' => 'Captura rapida Central hidraulica',
                'description' => 'Formulario rapido de central hidraulica',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-bolt',
                'route' => ['pasteurizadora.central-hidraulica.create-quick'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA)
                    && $user->canUseCustomPermission('crear analisis pasteurizadora'),
                'keywords' => ['captura', 'rapida', 'central', 'hidraulica'],
            ],
            [
                'title' => 'Historial Central hidraulica',
                'description' => 'Historial de analisis de central hidraulica',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['pasteurizadora.central-hidraulica.historial'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA),
                'keywords' => ['historial', 'central', 'hidraulica', 'pasteurizadora'],
            ],
            [
                'title' => 'Formulario Central hidraulica',
                'description' => 'Formulario completo de central hidraulica',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-clipboard-list',
                'route' => ['pasteurizadora.central-hidraulica.create-legacy'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA)
                    && $user->canUseCustomPermission('crear analisis pasteurizadora'),
                'keywords' => ['formulario', 'central', 'hidraulica', 'crear', 'analisis'],
            ],
            [
                'title' => 'Historico revisados Central hidraulica',
                'description' => 'Componentes revisados de central hidraulica',
                'section' => 'Pasteurizadora',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['pasteurizadora.central-hidraulica.historico-revisados'],
                'can' => fn (User $user): bool => $user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA),
                'keywords' => ['historico', 'revisados', 'central', 'hidraulica', 'componentes'],
            ],
            [
                'title' => 'Tendencias Lavadora',
                'description' => 'Analisis mensual de danos en lavadoras',
                'section' => 'Tendencias',
                'icon' => 'fa-chart-line',
                'route' => ['analisis-tendencia-mensual.lavadora.index'],
                'permission' => 'ver tendencias lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['tendencia', 'tendencias', 'mensual', 'lavadora'],
            ],
            [
                'title' => 'Tendencia Lavadora 52-12-4',
                'description' => 'Vista de tendencia 52-12-4',
                'section' => 'Tendencias',
                'icon' => 'fa-chart-line',
                'route' => ['analisis-tendencia-mensual.lavadora.analisis-52-12-4'],
                'permission' => 'ver tendencias lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['52', '12', '4', '52124', 'tendencia', 'lavadora'],
            ],
            [
                'title' => 'Tendencia Lavadora 30-14-7',
                'description' => 'Vista de tendencia 30-14-7',
                'section' => 'Tendencias',
                'icon' => 'fa-chart-line',
                'route' => ['analisis-tendencia-mensual.lavadora.analisis-30-14-7'],
                'permission' => 'ver tendencias lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['30', '14', '7', '30147', 'tendencia', 'lavadora'],
            ],
            [
                'title' => 'Crear tendencia Lavadora',
                'description' => 'Captura de analisis de tendencia de lavadora',
                'section' => 'Tendencias',
                'icon' => 'fa-plus-circle',
                'route' => ['analisis-tendencia-mensual.lavadora.create'],
                'permission' => 'crear tendencias lavadora',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['crear', 'tendencia', 'mensual', 'lavadora'],
            ],
            [
                'title' => 'Tendencias Pasteurizadora',
                'description' => 'Analisis mensual de danos en pasteurizadoras',
                'section' => 'Tendencias',
                'icon' => 'fa-chart-line',
                'route' => ['analisis-tendencia-mensual.pasteurizadora.index'],
                'permission' => 'ver tendencias pasteurizadora',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['tendencia', 'tendencias', 'mensual', 'pasteurizadora'],
            ],
            [
                'title' => 'Tendencia Pasteurizadora 52-12-4',
                'description' => 'Vista de tendencia 52-12-4',
                'section' => 'Tendencias',
                'icon' => 'fa-chart-line',
                'route' => ['analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4'],
                'permission' => 'ver tendencias pasteurizadora',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['52', '12', '4', '52124', 'tendencia', 'pasteurizadora'],
            ],
            [
                'title' => 'Tendencia Pasteurizadora 30-14-7',
                'description' => 'Vista de tendencia 30-14-7',
                'section' => 'Tendencias',
                'icon' => 'fa-chart-line',
                'route' => ['analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7'],
                'permission' => 'ver tendencias pasteurizadora',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['30', '14', '7', '30147', 'tendencia', 'pasteurizadora'],
            ],
            [
                'title' => 'Crear tendencia Pasteurizadora',
                'description' => 'Captura de analisis de tendencia de pasteurizadora',
                'section' => 'Tendencias',
                'icon' => 'fa-plus-circle',
                'route' => ['analisis-tendencia-mensual.pasteurizadora.create'],
                'permission' => 'crear tendencias pasteurizadora',
                'module' => User::MODULE_PASTEURIZADORA,
                'keywords' => ['crear', 'tendencia', 'mensual', 'pasteurizadora'],
            ],
            [
                'title' => 'Dashboard Plan de accion',
                'description' => 'Indicadores de actividades y fechas proximas',
                'section' => 'Operacion',
                'icon' => 'fa-list-check',
                'route' => ['plan-accion.dashboard'],
                'can' => fn (User $user): bool => $this->canViewAnyPlanActionType($user),
                'keywords' => ['dashboard', 'plan', 'accion', 'actividades', 'pcm'],
            ],
            [
                'title' => 'Plan de accion Lavadora',
                'description' => 'Actividades y pendientes de lavadoras',
                'section' => 'Operacion',
                'icon' => 'fa-list-check',
                'route' => ['plan-accion.index', ['tipo' => User::MODULE_LAVADORA]],
                'can' => fn (User $user): bool => $user->canViewPlanActionType(User::MODULE_LAVADORA),
                'keywords' => ['plan', 'accion', 'lavadora', 'actividades'],
            ],
            [
                'title' => 'Plan de accion Etiquetadora',
                'description' => 'Actividades y pendientes de etiquetadoras',
                'section' => 'Operacion',
                'icon' => 'fa-list-check',
                'route' => ['plan-accion.index', ['tipo' => User::MODULE_ETIQUETADORA]],
                'can' => fn (User $user): bool => $user->canViewPlanActionType(User::MODULE_ETIQUETADORA),
                'keywords' => ['plan', 'accion', 'etiquetadora', 'actividades'],
            ],
            [
                'title' => 'Plan de accion Pasteurizadora',
                'description' => 'Actividades y pendientes de pasteurizadoras',
                'section' => 'Operacion',
                'icon' => 'fa-list-check',
                'route' => ['plan-accion.index', ['tipo' => User::MODULE_PASTEURIZADORA]],
                'can' => fn (User $user): bool => $user->canViewPlanActionType(User::MODULE_PASTEURIZADORA),
                'keywords' => ['plan', 'accion', 'pasteurizadora', 'actividades'],
            ],
            [
                'title' => 'Crear plan de accion',
                'description' => 'Registrar nueva actividad de mantenimiento',
                'section' => 'Operacion',
                'icon' => 'fa-plus-circle',
                'route' => ['plan-accion.create'],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('crear planes accion')
                    && $user->canAccessModule(User::MODULE_LAVADORA),
                'keywords' => ['crear', 'nuevo', 'plan', 'accion', 'actividad'],
            ],
            [
                'title' => 'Crear plan de accion Etiquetadora',
                'description' => 'Registrar actividad para etiquetadoras',
                'section' => 'Operacion',
                'icon' => 'fa-plus-circle',
                'route' => ['plan-accion.create', ['tipo' => User::MODULE_ETIQUETADORA]],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('crear planes accion')
                    && $user->canAccessModule(User::MODULE_ETIQUETADORA),
                'keywords' => ['crear', 'plan', 'accion', 'etiquetadora', 'actividad'],
            ],
            [
                'title' => 'Crear plan de accion Pasteurizadora',
                'description' => 'Registrar actividad para pasteurizadoras',
                'section' => 'Operacion',
                'icon' => 'fa-plus-circle',
                'route' => ['plan-accion.create', ['tipo' => User::MODULE_PASTEURIZADORA]],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('crear planes accion')
                    && $user->canAccessModule(User::MODULE_PASTEURIZADORA),
                'keywords' => ['crear', 'plan', 'accion', 'pasteurizadora', 'actividad'],
            ],
            [
                'title' => 'Revision IA de planes',
                'description' => 'Cola de planes sugeridos por IA',
                'section' => 'IA',
                'icon' => 'fa-robot',
                'route' => ['plan-accion.ai.index'],
                'can' => fn (User $user): bool => $user->canReviewWasherAiPlans(),
                'keywords' => ['revision', 'ia', 'inteligencia', 'sugeridos', 'planes'],
            ],
            [
                'title' => 'Documentos de conocimiento Lavadora',
                'description' => 'Base de conocimiento tecnica para lavadoras',
                'section' => 'IA',
                'icon' => 'fa-file-lines',
                'route' => ['lavadora.knowledge-documents.index'],
                'can' => fn (User $user): bool => $user->canManageWasherKnowledgeDocuments(),
                'keywords' => ['documentos', 'conocimiento', 'base', 'lavadora', 'manuales'],
            ],
            [
                'title' => 'Crear documento de conocimiento',
                'description' => 'Cargar informacion tecnica de lavadoras',
                'section' => 'IA',
                'icon' => 'fa-file-circle-plus',
                'route' => ['lavadora.knowledge-documents.create'],
                'can' => fn (User $user): bool => $user->canManageWasherKnowledgeDocuments(),
                'keywords' => ['crear', 'cargar', 'documento', 'conocimiento', 'manual'],
            ],
            [
                'title' => 'Costos Lavadora',
                'description' => 'Vista de costos por analisis y refacciones',
                'section' => 'Lavadora',
                'icon' => 'fa-coins',
                'route' => ['lavadora.costos.index'],
                'can' => fn (User $user): bool => $user->canAccessLavadoraCosts(),
                'keywords' => ['costos', 'lavadora', 'refacciones', 'gastos'],
            ],
            [
                'title' => 'Reporte general Lavadoras',
                'description' => 'Reporte de estado y analisis de lavadoras',
                'section' => 'Reportes',
                'icon' => 'fa-chart-bar',
                'route' => ['reportes.index', ['tipo' => 'lavadoras']],
                'permission' => 'ver reportes',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'lavadoras', 'general', 'analisis'],
            ],
            [
                'title' => 'Reporte por linea',
                'description' => 'Vista detallada por linea',
                'section' => 'Reportes',
                'icon' => 'fa-chart-bar',
                'route' => ['reportes.show'],
                'permission' => 'ver reportes',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'linea', 'detalle', 'detallado'],
            ],
            [
                'title' => 'Reporte Etiquetadoras',
                'description' => 'Reporte de estado y analisis de etiquetadoras',
                'section' => 'Reportes',
                'icon' => 'fa-chart-bar',
                'route' => ['reportes.index', ['tipo' => 'etiquetadoras']],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('ver reportes')
                    && $user->canAccessModule(User::MODULE_ETIQUETADORA),
                'keywords' => ['reporte', 'etiquetadoras', 'etiquetas', 'general'],
            ],
            [
                'title' => 'Reporte Pasteurizadoras',
                'description' => 'Reporte de estado y analisis de pasteurizadoras',
                'section' => 'Reportes',
                'icon' => 'fa-chart-bar',
                'route' => ['reportes.pasteurizadora'],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('ver reportes')
                    && $user->canAccessModule(User::MODULE_PASTEURIZADORA),
                'keywords' => ['reporte', 'pasteurizadoras', 'pasteurizadora', 'general'],
            ],
            [
                'title' => 'Reporte de elongacion',
                'description' => 'Consulta de elongaciones por periodo',
                'section' => 'Reportes',
                'icon' => 'fa-link',
                'route' => ['reportes.elongacion'],
                'permission' => 'ver reportes',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'elongacion', 'cadena', 'cadenas'],
            ],
            [
                'title' => 'Reporte de componentes',
                'description' => 'Resumen por componente y estado',
                'section' => 'Reportes',
                'icon' => 'fa-gears',
                'route' => ['reportes.componentes'],
                'permission' => 'ver reportes',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'componentes', 'estado', 'danos'],
            ],
            [
                'title' => 'Reporte de paros Lavadora',
                'description' => 'Consulta de paros de lavadoras por periodo',
                'section' => 'Reportes',
                'icon' => 'fa-stopwatch',
                'route' => ['reportes.paros'],
                'permission' => 'ver reportes',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'paros', 'lavadora', 'tiempos', 'produccion'],
            ],
            [
                'title' => 'Reporte de paros Etiquetadora',
                'description' => 'Consulta de paros de etiquetadoras por periodo',
                'section' => 'Reportes',
                'icon' => 'fa-stopwatch',
                'route' => ['reportes.paros', ['tipo' => 'etiquetadoras']],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('ver reportes')
                    && $user->canAccessModule(User::MODULE_ETIQUETADORA),
                'keywords' => ['reporte', 'paros', 'etiquetadora', 'etiquetas', 'tiempos', 'produccion'],
            ],
            [
                'title' => 'Reporte de paros Pasteurizadora',
                'description' => 'Consulta de paros de pasteurizadoras por periodo',
                'section' => 'Reportes',
                'icon' => 'fa-stopwatch',
                'route' => ['reportes.paros', ['tipo' => 'pasteurizadoras']],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('ver reportes')
                    && $user->canAccessModule(User::MODULE_PASTEURIZADORA),
                'keywords' => ['reporte', 'paros', 'pasteurizadora', 'tiempos', 'produccion'],
            ],
            [
                'title' => 'Lineas',
                'description' => 'Catalogo de lineas',
                'section' => 'Catalogo',
                'icon' => 'fa-industry',
                'route' => ['lineas.index'],
                'permission' => 'ver lineas',
                'keywords' => ['linea', 'lineas', 'catalogo'],
            ],
            [
                'title' => 'Crear linea',
                'description' => 'Registrar una nueva linea',
                'section' => 'Catalogo',
                'icon' => 'fa-plus-circle',
                'route' => ['lineas.create'],
                'permission' => 'crear lineas',
                'keywords' => ['crear', 'nueva', 'linea', 'catalogo'],
            ],
            [
                'title' => 'Historico revisados Lavadora',
                'description' => 'Avance de componentes revisados de lavadora',
                'section' => 'Catalogo',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['historico-revisados.index', ['tipo' => User::MODULE_LAVADORA]],
                'permission' => 'ver historico revisados',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['historico', 'revisados', 'lavadora', 'componentes'],
            ],
            [
                'title' => 'Historico revisados Pasteurizadora',
                'description' => 'Avance de componentes revisados de pasteurizadora',
                'section' => 'Catalogo',
                'icon' => 'fa-clock-rotate-left',
                'route' => ['historico-revisados.index', ['tipo' => User::MODULE_PASTEURIZADORA]],
                'can' => fn (User $user): bool => $user->canUseCustomPermission('ver historico revisados')
                    && $user->canAccessModule(User::MODULE_PASTEURIZADORA),
                'keywords' => ['historico', 'revisados', 'pasteurizadora', 'componentes'],
            ],
            [
                'title' => 'Analisis legado',
                'description' => 'Modulo original de analisis',
                'section' => 'Legado',
                'icon' => 'fa-clipboard-list',
                'route' => ['analisis.index'],
                'permission' => 'ver analisis legado',
                'keywords' => ['analisis', 'legado', 'original', 'registros'],
            ],
            [
                'title' => 'Nuevo analisis legado',
                'description' => 'Seleccion de linea del modulo original',
                'section' => 'Legado',
                'icon' => 'fa-plus-circle',
                'route' => ['analisis.nuevo'],
                'permission' => 'crear analisis legado',
                'keywords' => ['crear', 'nuevo', 'analisis', 'legado'],
            ],
            [
                'title' => 'Estadisticas analisis legado',
                'description' => 'Indicadores del modulo original',
                'section' => 'Legado',
                'icon' => 'fa-chart-pie',
                'route' => ['analisis.estadisticas'],
                'permission' => 'ver analisis legado',
                'keywords' => ['estadisticas', 'analisis', 'legado', 'indicadores'],
            ],
            [
                'title' => 'Crear elongacion',
                'description' => 'Registrar medicion de elongacion',
                'section' => 'Lavadora',
                'icon' => 'fa-plus-circle',
                'route' => ['elongaciones.create'],
                'permission' => 'crear elongaciones',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['crear', 'elongacion', 'medicion', 'cadena'],
            ],
            [
                'title' => 'Comparacion de ciclos',
                'description' => 'Comparar ciclos de elongacion de cadenas',
                'section' => 'Lavadora',
                'icon' => 'fa-code-compare',
                'route' => ['elongaciones.ciclos.comparacion'],
                'permission' => 'ver elongaciones',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['comparacion', 'ciclos', 'elongacion', 'cadenas'],
            ],
            [
                'title' => 'Reporte de elongaciones',
                'description' => 'Reporte operativo de elongaciones',
                'section' => 'Lavadora',
                'icon' => 'fa-file-lines',
                'route' => ['elongaciones.reporte'],
                'permission' => 'ver elongaciones',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['reporte', 'elongaciones', 'cadena', 'lecturas'],
            ],
            [
                'title' => 'Perfil',
                'description' => 'Datos y preferencias del usuario',
                'section' => 'Usuario',
                'icon' => 'fa-user-circle',
                'route' => ['profile.edit'],
                'permission' => 'ver perfil',
                'keywords' => ['perfil', 'usuario', 'cuenta', 'password', 'contrasena'],
            ],
            [
                'title' => 'Diagrama Lavadoras L04 L09',
                'description' => 'Diagrama visual de lavadoras L04 y L09',
                'section' => 'Diagramas',
                'icon' => 'fa-diagram-project',
                'route' => ['lavadoras.diagramas.l04-l09'],
                'permission' => 'ver dashboard lavadoras',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['diagrama', 'lavadoras', 'l04', 'l09', 'cadena'],
            ],
            [
                'title' => 'Diagrama Lavadoras L05 L12 L13',
                'description' => 'Diagrama visual de lavadoras L05, L12 y L13',
                'section' => 'Diagramas',
                'icon' => 'fa-diagram-project',
                'route' => ['lavadoras.diagramas.l05-l12-l13'],
                'permission' => 'ver dashboard lavadoras',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['diagrama', 'lavadoras', 'l05', 'l12', 'l13', 'cadena'],
            ],
            [
                'title' => 'Diagrama Lavadoras L06 L07',
                'description' => 'Diagrama visual de lavadoras L06 y L07',
                'section' => 'Diagramas',
                'icon' => 'fa-diagram-project',
                'route' => ['lavadoras.diagramas.l06-l07'],
                'permission' => 'ver dashboard lavadoras',
                'module' => User::MODULE_LAVADORA,
                'keywords' => ['diagrama', 'lavadoras', 'l06', 'l07', 'cadena'],
            ],
        ];
    }

    private function routeShortcutDefinitions(): Collection
    {
        return collect(Route::getRoutes())
            ->filter(fn (RouteItem $route): bool => $this->isSearchableViewRoute($route))
            ->map(fn (RouteItem $route): array => $this->shortcutFromRoute($route))
            ->values();
    }

    private function isSearchableViewRoute(RouteItem $route): bool
    {
        $routeName = $route->getName();

        if (!$routeName || !in_array('GET', $route->methods(), true)) {
            return false;
        }

        if ($route->parameterNames() !== []) {
            return false;
        }

        foreach (self::HIDDEN_SEARCH_ROUTE_PATTERNS as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return false;
            }
        }

        return true;
    }

    private function shortcutFromRoute(RouteItem $route): array
    {
        $routeName = (string) $route->getName();
        $uri = $route->uri();

        return [
            'title' => $this->titleForRoute($routeName),
            'description' => $this->descriptionForRoute($routeName),
            'section' => $this->sectionForRoute($routeName),
            'icon' => $this->iconForRoute($routeName),
            'route' => [$routeName],
            'can' => fn (User $user): bool => $this->canOpenNamedRoute($user, $routeName),
            'keywords' => $this->keywordsForRoute($routeName, $uri),
        ];
    }

    private function canOpenNamedRoute(User $user, string $routeName): bool
    {
        if (Str::startsWith($routeName, 'lavadora.knowledge-documents.')) {
            return $user->canManageWasherKnowledgeDocuments();
        }

        if ($routeName === 'lavadora.costos.index' || Str::startsWith($routeName, 'admin.costos.')) {
            return $user->canAccessLavadoraCosts();
        }

        if (Str::startsWith($routeName, 'plan-accion.ai.')) {
            return $user->canReviewWasherAiPlans();
        }

        if ($routeName === 'admin.ai-observability.index') {
            return $user->canViewAiObservability();
        }

        if (Str::startsWith($routeName, 'lavadoras.diagramas.')) {
            return $user->canAccessModule(User::MODULE_LAVADORA)
                && $user->canUseCustomPermission('ver dashboard lavadoras');
        }

        if (Str::startsWith($routeName, 'pasteurizadora.central-hidraulica.')) {
            if (!$user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA)) {
                return false;
            }
        } elseif (Str::startsWith($routeName, 'pasteurizadora.analisis-pasteurizadora.')) {
            if (!$user->canAccessPasteurizadoraArea(AnalisisPasteurizadora::AREA_MECANICA)) {
                return false;
            }
        }

        $module = $this->moduleForRoute($routeName);

        if ($module && !$user->canAccessModule($module)) {
            return false;
        }

        if (in_array($routeName, ['plan-accion.create', 'plan-accion.lavadora.index'], true)) {
            if (!$user->canAccessModule(User::MODULE_LAVADORA)) {
                return false;
            }
        }

        $permission = AccessPermissionCatalog::permissionForRoute($routeName, 'GET');

        if ($permission && !$user->canUseCustomPermission($permission)) {
            return false;
        }

        return true;
    }

    private function moduleForRoute(string $routeName): ?string
    {
        if (Str::contains($routeName, 'etiquetadora')) {
            return User::MODULE_ETIQUETADORA;
        }

        if (Str::contains($routeName, 'pasteurizadora')) {
            return User::MODULE_PASTEURIZADORA;
        }

        if (Str::contains($routeName, ['lavadora', 'lavadoras', 'elongacion', 'elongaciones'])) {
            return User::MODULE_LAVADORA;
        }

        if (Str::startsWith($routeName, 'reportes.')) {
            return User::MODULE_LAVADORA;
        }

        return null;
    }

    private function titleForRoute(string $routeName): string
    {
        $titles = [
            'assistant-chat.index' => 'Asistente IA',
            'dashboard_etiquetadora' => 'Dashboard Etiquetadora',
            'dashboard_lavadora' => 'Dashboard Lavadora',
            'dashboard_pasteurizadora' => 'Dashboard Pasteurizadora',
            'lavadoras.diagramas.index' => 'Diagramas Lavadoras',
            'analisis.exportar.excel' => 'Exportar analisis legado a Excel',
            'analisis.analisis.exportar.lavadoras' => 'Exportar analisis Lavadoras',
            'pasteurizadora.analisis-pasteurizadora.export.excel' => 'Exportar Pasteurizadora Mecanica a Excel',
            'pasteurizadora.analisis-pasteurizadora.export.pdf' => 'Exportar Pasteurizadora Mecanica a PDF',
            'pasteurizadora.central-hidraulica.export.excel' => 'Exportar Central hidraulica a Excel',
            'pasteurizadora.central-hidraulica.export.pdf' => 'Exportar Central hidraulica a PDF',
            'plan-accion.lavadora.index' => 'Plan de accion Lavadora clasico',
            'profile.notifications' => 'Notificaciones del perfil',
            'reportes.export-excel' => 'Exportar reportes a Excel',
            'reportes.export-pdf' => 'Exportar reportes a PDF',
        ];

        if (isset($titles[$routeName])) {
            return $titles[$routeName];
        }

        $title = str_replace(['.', '_', '-'], ' ', $routeName);
        $title = preg_replace('/\bindex\b/u', '', $title) ?? $title;
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);

        return Str::title($title);
    }

    private function descriptionForRoute(string $routeName): string
    {
        if (Str::contains($routeName, 'export')) {
            return 'Descarga y exportacion de informacion';
        }

        if (Str::contains($routeName, ['create', 'crear'])) {
            return 'Formulario de captura';
        }

        if (Str::contains($routeName, ['dashboard', 'diagramas'])) {
            return 'Vista visual del modulo';
        }

        if (Str::contains($routeName, ['historial', 'historico'])) {
            return 'Consulta historica de registros';
        }

        return 'Vista directa del sistema';
    }

    private function sectionForRoute(string $routeName): string
    {
        if (Str::startsWith($routeName, ['assistant-chat.', 'lavadora.knowledge-documents.', 'plan-accion.ai.'])) {
            return 'IA';
        }

        if (Str::startsWith($routeName, ['dashboard', 'tecnico.dashboard'])) {
            return 'Dashboards';
        }

        if (Str::startsWith($routeName, 'admin.')) {
            return 'Administracion';
        }

        if (Str::startsWith($routeName, 'reportes.')) {
            return 'Reportes';
        }

        if (Str::startsWith($routeName, 'analisis-tendencia-mensual.')) {
            return 'Tendencias';
        }

        if (Str::startsWith($routeName, 'plan-accion.')) {
            return 'Operacion';
        }

        if (Str::startsWith($routeName, ['lineas.', 'historico-revisados.'])) {
            return 'Catalogo';
        }

        if (Str::startsWith($routeName, 'lavadoras.diagramas.')) {
            return 'Diagramas';
        }

        if (Str::contains($routeName, 'etiquetadora')) {
            return 'Etiquetadora';
        }

        if (Str::contains($routeName, 'pasteurizadora')) {
            return 'Pasteurizadora';
        }

        if (Str::contains($routeName, ['lavadora', 'elongaciones'])) {
            return 'Lavadora';
        }

        if (Str::startsWith($routeName, 'analisis.')) {
            return 'Legado';
        }

        if (Str::startsWith($routeName, ['notifications.', 'notificaciones.', 'profile.'])) {
            return 'Usuario';
        }

        return 'Vistas';
    }

    private function iconForRoute(string $routeName): string
    {
        if (Str::startsWith($routeName, ['assistant-chat.', 'plan-accion.ai.'])) {
            return 'fa-robot';
        }

        if (Str::startsWith($routeName, 'lavadora.knowledge-documents.')) {
            return 'fa-file-lines';
        }

        if (Str::contains($routeName, 'export')) {
            return 'fa-file-export';
        }

        if (Str::startsWith($routeName, 'admin.users.')) {
            return 'fa-user-shield';
        }

        if (Str::startsWith($routeName, 'admin.costos.')) {
            return 'fa-coins';
        }

        if ($routeName === 'admin.ai-observability.index') {
            return 'fa-brain';
        }

        if (Str::startsWith($routeName, 'plan-accion.')) {
            return 'fa-list-check';
        }

        if (Str::startsWith($routeName, 'reportes.')) {
            return 'fa-chart-bar';
        }

        if (Str::startsWith($routeName, 'lineas.')) {
            return 'fa-industry';
        }

        if (Str::startsWith($routeName, 'historico-revisados.')) {
            return 'fa-clock-rotate-left';
        }

        if (Str::startsWith($routeName, 'lavadoras.diagramas.')) {
            return 'fa-diagram-project';
        }

        if (Str::contains($routeName, 'central-hidraulica')) {
            return 'fa-oil-can';
        }

        if (Str::contains($routeName, 'pasteurizadora')) {
            return 'fa-temperature-high';
        }

        if (Str::contains($routeName, 'etiquetadora')) {
            return 'fa-tags';
        }

        if (Str::contains($routeName, 'elongaciones')) {
            return 'fa-link';
        }

        if (Str::contains($routeName, 'lavadora')) {
            return 'fa-droplet';
        }

        if (Str::startsWith($routeName, ['notifications.', 'notificaciones.'])) {
            return 'fa-bell';
        }

        if (Str::startsWith($routeName, 'profile.')) {
            return 'fa-user-circle';
        }

        return 'fa-arrow-up-right-from-square';
    }

    private function keywordsForRoute(string $routeName, string $uri): array
    {
        $raw = str_replace(['.', '_', '-', '/', '{', '}'], ' ', $routeName . ' ' . $uri);
        $tokens = preg_split('/\s+/u', $raw) ?: [];

        $keywords = array_merge($tokens, ['vista', 'abrir']);

        if (Str::contains($routeName, 'export')) {
            $keywords = array_merge($keywords, ['exportar', 'descargar', 'excel', 'pdf']);
        }

        if (Str::contains($routeName, ['create', 'crear'])) {
            $keywords = array_merge($keywords, ['crear', 'nuevo', 'captura']);
        }

        if (Str::contains($routeName, 'index')) {
            $keywords = array_merge($keywords, ['listado', 'lista']);
        }

        if (Str::contains($routeName, ['historial', 'historico'])) {
            $keywords = array_merge($keywords, ['historial', 'historico', 'consulta']);
        }

        if (Str::contains($routeName, 'dashboard')) {
            $keywords = array_merge($keywords, ['dashboard', 'tablero', 'inicio']);
        }

        if (Str::startsWith($routeName, 'assistant-chat.')) {
            $keywords = array_merge($keywords, ['asistente', 'chat', 'ia', 'inteligencia']);
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($keyword): string => trim((string) $keyword), $keywords)
        )));
    }

    private function shortcutIdentity(array $shortcut): string
    {
        if (isset($shortcut['route']) && is_array($shortcut['route'])) {
            return ($shortcut['route'][0] ?? '')
                . '|'
                . json_encode($shortcut['route'][1] ?? [], JSON_THROW_ON_ERROR);
        }

        return (string) ($shortcut['url'] ?? $shortcut['title'] ?? '');
    }

    private function lineResults(User $user, string $query): Collection
    {
        if (!$user->canUseCustomPermission('ver lineas')) {
            return collect();
        }

        $like = $this->likeTerm($query);

        return Linea::query()
            ->where(function (Builder $builder) use ($like): void {
                $builder->where('nombre', 'like', $like)
                    ->orWhere('descripcion', 'like', $like)
                    ->orWhere('tipo', 'like', $like);
            })
            ->orderBy('nombre')
            ->limit(3)
            ->get()
            ->map(fn (Linea $linea): array => $this->formatResult(
                'Linea ' . $linea->nombre,
                trim((string) ($linea->descripcion ?: 'Catalogo de lineas')),
                'Catalogo',
                'fa-industry',
                route('lineas.show', $linea->id, false),
                $linea->activo ? 'Activa' : 'Inactiva'
            ));
    }

    private function washerAnalysisResults(User $user, string $query): Collection
    {
        if (
            !$user->canAccessModule(User::MODULE_LAVADORA)
            || !$user->canUseCustomPermission('ver analisis lavadora')
        ) {
            return collect();
        }

        $like = $this->likeTerm($query);

        return AnalisisLavadora::query()
            ->with(['linea', 'componente'])
            ->where(function (Builder $builder) use ($query, $like): void {
                $this->matchCommonAnalysisColumns($builder, $query, $like);
                $builder->orWhere('reductor', 'like', $like)
                    ->orWhere('lado', 'like', $like)
                    ->orWhereHas('linea', fn (Builder $linea): Builder => $linea->where('nombre', 'like', $like))
                    ->orWhereHas('componente', function (Builder $componente) use ($like): void {
                        $componente->where('nombre', 'like', $like)
                            ->orWhere('codigo', 'like', $like);
                    });
            })
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map(fn (AnalisisLavadora $analisis): array => $this->formatResult(
                'Analisis Lavadora #' . $analisis->id,
                $this->analysisDescription([
                    $analisis->linea?->nombre,
                    $analisis->componente?->nombre,
                    $analisis->reductor,
                    $analisis->estado,
                ]),
                'Registros',
                'fa-droplet',
                route('analisis-lavadora.show', $analisis->id, false),
                $analisis->fecha_analisis?->format('d/m/Y')
            ));
    }

    private function labelerAnalysisResults(User $user, string $query): Collection
    {
        if (
            !$user->canAccessModule(User::MODULE_ETIQUETADORA)
            || !$user->canUseCustomPermission('ver analisis etiquetadora')
        ) {
            return collect();
        }

        $like = $this->likeTerm($query);

        return AnalisisEtiquetadora::query()
            ->with(['linea', 'componente'])
            ->where(function (Builder $builder) use ($query, $like): void {
                $this->matchCommonAnalysisColumns($builder, $query, $like);
                $builder->orWhere('reductor', 'like', $like)
                    ->orWhere('maquina', 'like', $like)
                    ->orWhereHas('linea', fn (Builder $linea): Builder => $linea->where('nombre', 'like', $like))
                    ->orWhereHas('componente', function (Builder $componente) use ($like): void {
                        $componente->where('nombre', 'like', $like)
                            ->orWhere('codigo', 'like', $like);
                    });
            })
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map(fn (AnalisisEtiquetadora $analisis): array => $this->formatResult(
                'Analisis Etiquetadora #' . $analisis->id,
                $this->analysisDescription([
                    $analisis->linea?->nombre,
                    $analisis->componente?->nombre,
                    $analisis->maquina ? 'Maquina ' . $analisis->maquina : null,
                    $analisis->estado,
                ]),
                'Registros',
                'fa-tags',
                route('analisis-etiquetadora.show', $analisis->id, false),
                $analisis->fecha_analisis?->format('d/m/Y')
            ));
    }

    private function pasteurizerAnalysisResults(User $user, string $query): Collection
    {
        if (!$user->canAccessModule(User::MODULE_PASTEURIZADORA)) {
            return collect();
        }

        $like = $this->likeTerm($query);
        $areas = [
            AnalisisPasteurizadora::AREA_MECANICA => [
                'title' => 'Analisis Pasteurizadora',
                'route' => 'pasteurizadora.analisis-pasteurizadora.show',
                'icon' => 'fa-temperature-high',
            ],
            AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA => [
                'title' => 'Central Hidraulica',
                'route' => 'pasteurizadora.central-hidraulica.show',
                'icon' => 'fa-oil-can',
            ],
        ];

        return collect($areas)
            ->flatMap(function (array $areaConfig, string $area) use ($user, $query, $like): Collection {
                if (!$user->canAccessPasteurizadoraArea($area)) {
                    return collect();
                }

                return AnalisisPasteurizadora::queryForArea($area)
                    ->with('linea')
                    ->where(function (Builder $builder) use ($query, $like): void {
                        $this->matchCommonAnalysisColumns($builder, $query, $like);
                        $builder->orWhere('modulo', 'like', $like)
                            ->orWhere('nivel', 'like', $like)
                            ->orWhere('lado', 'like', $like)
                            ->orWhere('componente', 'like', $like)
                            ->orWhereHas('linea', fn (Builder $linea): Builder => $linea->where('nombre', 'like', $like));
                    })
                    ->orderByDesc('fecha_analisis')
                    ->orderByDesc('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (AnalisisPasteurizadora $analisis): array => $this->formatResult(
                        $areaConfig['title'] . ' #' . $analisis->id,
                        $this->analysisDescription([
                            $analisis->linea?->nombre,
                            $analisis->componente,
                            $analisis->modulo ? 'Modulo ' . $analisis->modulo : null,
                            $analisis->estado,
                        ]),
                        'Registros',
                        $areaConfig['icon'],
                        route($areaConfig['route'], $analisis->id, false),
                        $analisis->fecha_analisis?->format('d/m/Y')
                    ));
            })
            ->values();
    }

    private function actionPlanResults(User $user, string $query): Collection
    {
        if (!$user->canUseCustomPermission('ver planes accion')) {
            return collect();
        }

        $allowedTypes = collect([
            User::MODULE_LAVADORA,
            User::MODULE_ETIQUETADORA,
            User::MODULE_PASTEURIZADORA,
        ])->filter(fn (string $type): bool => $user->canViewPlanActionType($type));

        if ($allowedTypes->isEmpty()) {
            return collect();
        }

        $like = $this->likeTerm($query);

        return PlanAccion::query()
            ->with('linea')
            ->where(function (Builder $builder) use ($allowedTypes): void {
                $builder->whereIn('tipo_equipo', $allowedTypes->all());

                if ($allowedTypes->contains(User::MODULE_LAVADORA)) {
                    $builder->orWhereNull('tipo_equipo');
                }
            })
            ->where(function (Builder $builder) use ($query, $like): void {
                if (ctype_digit($query)) {
                    $builder->whereKey((int) $query);
                }

                $builder->orWhere('actividad', 'like', $like)
                    ->orWhere('estado', 'like', $like)
                    ->orWhere('tipo_equipo', 'like', $like)
                    ->orWhere('area_pasteurizadora', 'like', $like)
                    ->orWhere('priority_level', 'like', $like)
                    ->orWhereHas('linea', fn (Builder $linea): Builder => $linea->where('nombre', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(function (PlanAccion $plan): array {
                $tipo = $plan->tipo_equipo ?: User::MODULE_LAVADORA;

                return $this->formatResult(
                    'Plan de accion #' . $plan->id,
                    $this->analysisDescription([
                        $plan->linea?->nombre,
                        $plan->actividad,
                        $plan->estado,
                    ]),
                    'Operacion',
                    'fa-list-check',
                    route('plan-accion.index', [
                        'tipo' => $tipo,
                        'linea_id' => $plan->linea_id,
                    ], false),
                    $plan->completado ? 'Completado' : 'Pendiente'
                );
            });
    }

    private function elongationResults(User $user, string $query): Collection
    {
        if (!$user->canUseCustomPermission('ver elongaciones')) {
            return collect();
        }

        $like = $this->likeTerm($query);

        return Elongacion::query()
            ->where(function (Builder $builder) use ($query, $like): void {
                if (ctype_digit($query)) {
                    $builder->whereKey((int) $query);
                }

                $builder->orWhere('linea', 'like', $like)
                    ->orWhere('proveedor', 'like', $like)
                    ->orWhere('seccion', 'like', $like)
                    ->orWhere('estado', 'like', $like)
                    ->orWhere('estado_detallado', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(fn (Elongacion $elongacion): array => $this->formatResult(
                'Elongacion #' . $elongacion->id,
                $this->analysisDescription([
                    $elongacion->linea,
                    $elongacion->proveedor,
                    $elongacion->estado_detallado ?: $elongacion->estado,
                ]),
                'Lavadora',
                'fa-link',
                route('elongaciones.show', $elongacion->id, false),
                $elongacion->created_at?->format('d/m/Y')
            ));
    }

    private function legacyAnalysisResults(User $user, string $query): Collection
    {
        if (!$user->canUseCustomPermission('ver analisis legado')) {
            return collect();
        }

        $like = $this->likeTerm($query);

        return Analisis::query()
            ->with(['linea', 'componente'])
            ->where(function (Builder $builder) use ($query, $like): void {
                $this->matchCommonAnalysisColumns($builder, $query, $like);
                $builder->orWhere('reductor', 'like', $like)
                    ->orWhere('observaciones', 'like', $like)
                    ->orWhereHas('linea', fn (Builder $linea): Builder => $linea->where('nombre', 'like', $like))
                    ->orWhereHas('componente', function (Builder $componente) use ($like): void {
                        $componente->where('nombre', 'like', $like)
                            ->orWhere('codigo', 'like', $like);
                    });
            })
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(fn (Analisis $analisis): array => $this->formatResult(
                'Analisis legado #' . $analisis->id,
                $this->analysisDescription([
                    $analisis->linea?->nombre,
                    $analisis->componente?->nombre,
                    $analisis->actividad,
                ]),
                'Registros',
                'fa-clipboard-list',
                route('analisis.show', $analisis->id, false),
                $analisis->fecha_analisis?->format('d/m/Y')
            ));
    }

    private function canOpenShortcut(User $user, array $shortcut): bool
    {
        if (isset($shortcut['can']) && is_callable($shortcut['can'])) {
            return (bool) $shortcut['can']($user);
        }

        if (!empty($shortcut['module']) && !$user->canAccessModule($shortcut['module'])) {
            return false;
        }

        $permission = $shortcut['permission'] ?? $this->permissionForShortcut($shortcut);

        if ($permission) {
            return $user->canUseCustomPermission($permission);
        }

        return true;
    }

    private function canViewAnyPlanActionType(User $user): bool
    {
        return collect([
            User::MODULE_LAVADORA,
            User::MODULE_ETIQUETADORA,
            User::MODULE_PASTEURIZADORA,
        ])->contains(fn (string $type): bool => $user->canViewPlanActionType($type));
    }

    private function permissionForShortcut(array $shortcut): ?string
    {
        $routeName = $shortcut['route'][0] ?? null;

        return is_string($routeName)
            ? AccessPermissionCatalog::permissionForRoute($routeName, 'GET')
            : null;
    }

    private function matchesShortcut(array $shortcut, string $query): bool
    {
        if ($query === '') {
            return true;
        }

        $needle = $this->normalizeSearchText($query);
        $haystack = $this->normalizeSearchText(implode(' ', [
            $shortcut['title'] ?? '',
            $shortcut['description'] ?? '',
            $shortcut['section'] ?? '',
            implode(' ', $shortcut['keywords'] ?? []),
        ]));

        if (Str::contains($haystack, $needle)) {
            return true;
        }

        $tokens = preg_split('/\s+/u', $needle) ?: [];

        return collect($tokens)
            ->filter()
            ->every(fn (string $token): bool => Str::contains($haystack, $token));
    }

    private function normalizeSearchText(string $value): string
    {
        $normalized = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    private function formatShortcut(array $shortcut): array
    {
        return $this->formatResult(
            $shortcut['title'],
            $shortcut['description'],
            $shortcut['section'],
            $shortcut['icon'],
            route($shortcut['route'][0], $shortcut['route'][1] ?? [], false)
        );
    }

    private function formatResult(
        string $title,
        ?string $description,
        string $section,
        string $icon,
        string $url,
        ?string $badge = null
    ): array {
        return [
            'key' => md5($section . '|' . $title . '|' . $url),
            'title' => $title,
            'description' => $description ?: 'Abrir resultado',
            'section' => $section,
            'icon' => $icon,
            'url' => $url,
            'badge' => $badge,
        ];
    }

    private function matchCommonAnalysisColumns(Builder $builder, string $query, string $like): void
    {
        if (ctype_digit($query)) {
            $builder->whereKey((int) $query)
                ->orWhere('numero_orden', 'like', $like);
        }

        $builder->orWhere('actividad', 'like', $like)
            ->orWhere('estado', 'like', $like);
    }

    private function likeTerm(string $query): string
    {
        return '%' . addcslashes($query, '\%_') . '%';
    }

    /**
     * @param array<int, mixed> $parts
     */
    private function analysisDescription(array $parts): string
    {
        return collect($parts)
            ->filter(fn ($part): bool => filled($part))
            ->map(fn ($part): string => trim((string) $part))
            ->implode(' - ');
    }
}
