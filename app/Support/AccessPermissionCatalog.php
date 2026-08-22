<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class AccessPermissionCatalog
{
    public const CUSTOM_ACCESS_CONTROL = 'usar permisos personalizados';

    public static function groups(): array
    {
        return [
            'dashboards' => [
                'label' => 'Dashboards',
                'description' => 'Tableros principales.',
                'permissions' => [
                    'ver dashboard principal' => [
                        'label' => 'Principal',
                        'description' => 'Panel inicial.',
                    ],
                    'ver dashboard tecnico' => [
                        'label' => 'Tecnico',
                        'description' => 'Panel tecnico.',
                    ],
                    'ver dashboard lavadoras' => [
                        'label' => 'Lavadoras',
                        'description' => 'Tableros de Lavadora.',
                    ],
                    'ver dashboard pasteurizadoras' => [
                        'label' => 'Pasteurizadoras',
                        'description' => 'Tableros de Pasteurizadora.',
                    ],
                    'ver dashboard etiquetadoras' => [
                        'label' => 'Etiquetadoras',
                        'description' => 'Tableros de Etiquetadora.',
                    ],
                ],
            ],
            'analisis_lavadora' => [
                'label' => 'Lavadora',
                'description' => 'Acceso y analisis.',
                'permissions' => [
                    User::PERMISSION_ACCESS_LAVADORA => [
                        'label' => 'Acceso modulo',
                        'description' => 'Vistas generales.',
                    ],
                    'ver analisis lavadora' => [
                        'label' => 'Ver',
                        'description' => 'Listado e historial.',
                    ],
                    'crear analisis lavadora' => [
                        'label' => 'Crear',
                        'description' => 'Nuevos registros.',
                    ],
                    'editar analisis lavadora' => [
                        'label' => 'Editar',
                        'description' => 'Registros y fotos.',
                    ],
                    User::PERMISSION_DELETE_ANALYSIS => [
                        'label' => 'Eliminar',
                        'description' => 'Borrado habilitado.',
                    ],
                    User::PERMISSION_CLOSE_LAVADORA_DAMAGE => [
                        'label' => 'Cierre y costos',
                        'description' => 'Cierra danos y costos.',
                    ],
                ],
            ],
            'costos_lavadora' => [
                'label' => 'Costos de Lavadora',
                'description' => 'Consulta y gestion de costos.',
                'permissions' => [
                    User::PERMISSION_VIEW_LAVADORA_COST_MODULE => [
                        'label' => 'Ver acceso',
                        'description' => 'Muestra el modulo.',
                    ],
                    User::PERMISSION_ACCESS_LAVADORA_COSTS => [
                        'label' => 'Abrir costos',
                        'description' => 'Tablero y admin.',
                    ],
                    User::PERMISSION_CREATE_LAVADORA_COSTS => [
                        'label' => 'Crear',
                        'description' => 'Conceptos y reglas.',
                    ],
                    User::PERMISSION_EDIT_LAVADORA_COSTS => [
                        'label' => 'Editar',
                        'description' => 'Catalogos y reglas.',
                    ],
                    User::PERMISSION_DELETE_LAVADORA_COSTS => [
                        'label' => 'Eliminar',
                        'description' => 'Gastos o reglas.',
                    ],
                    User::PERMISSION_MANAGE_LAVADORA_COSTS => [
                        'label' => 'Control total',
                        'description' => 'Todas las acciones.',
                    ],
                ],
            ],
            'analisis_etiquetadora' => [
                'label' => 'Etiquetadora',
                'description' => 'Acceso y analisis.',
                'permissions' => [
                    User::PERMISSION_ACCESS_ETIQUETADORA => [
                        'label' => 'Acceso modulo',
                        'description' => 'Vistas generales.',
                    ],
                    'ver analisis etiquetadora' => [
                        'label' => 'Ver',
                        'description' => 'Listado e historial.',
                    ],
                    'crear analisis etiquetadora' => [
                        'label' => 'Crear',
                        'description' => 'Nuevos registros.',
                    ],
                    'editar analisis etiquetadora' => [
                        'label' => 'Editar',
                        'description' => 'Registros y fotos.',
                    ],
                    'eliminar analisis etiquetadora' => [
                        'label' => 'Eliminar',
                        'description' => 'Borra registros.',
                    ],
                ],
            ],
            'pasteurizadora' => [
                'label' => 'Pasteurizadora',
                'description' => 'Mecanica, central y analisis.',
                'permissions' => [
                    User::PERMISSION_ACCESS_PASTEURIZADORA => [
                        'label' => 'Acceso modulo',
                        'description' => 'Vistas generales.',
                    ],
                    User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA => [
                        'label' => 'Mecanica',
                        'description' => 'Analisis mecanico.',
                    ],
                    User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA => [
                        'label' => 'Central hidraulica',
                        'description' => 'Analisis hidraulico.',
                    ],
                    'crear analisis pasteurizadora' => [
                        'label' => 'Crear',
                        'description' => 'Nuevos registros.',
                    ],
                    'editar analisis pasteurizadora' => [
                        'label' => 'Editar',
                        'description' => 'Registros y fotos.',
                    ],
                    'eliminar analisis pasteurizadora' => [
                        'label' => 'Eliminar',
                        'description' => 'Borra registros.',
                    ],
                    'exportar analisis pasteurizadora' => [
                        'label' => 'Exportar',
                        'description' => 'Excel y PDF.',
                    ],
                ],
            ],
            'tendencias' => [
                'label' => 'Tendencias',
                'description' => 'Analisis de tendencia 52-12-4 y 30-14-7.',
                'permissions' => [
                    'ver tendencias lavadora' => [
                        'label' => 'Ver lavadora',
                        'description' => 'Consulta tendencias.',
                    ],
                    'crear tendencias lavadora' => [
                        'label' => 'Crear lavadora',
                        'description' => 'Nuevas tendencias.',
                    ],
                    'ver tendencias pasteurizadora' => [
                        'label' => 'Ver pasteurizadora',
                        'description' => 'Consulta tendencias.',
                    ],
                    'crear tendencias pasteurizadora' => [
                        'label' => 'Crear pasteurizadora',
                        'description' => 'Nuevas tendencias.',
                    ],
                ],
            ],
            'plan_accion' => [
                'label' => 'Planes',
                'description' => 'Seguimiento y checklist.',
                'permissions' => [
                    'ver planes accion' => [
                        'label' => 'Ver',
                        'description' => 'Planes y pendientes.',
                    ],
                    'crear planes accion' => [
                        'label' => 'Crear',
                        'description' => 'Nuevos planes.',
                    ],
                    'editar planes accion' => [
                        'label' => 'Editar',
                        'description' => 'Actualiza y notifica.',
                    ],
                    'eliminar planes accion' => [
                        'label' => 'Eliminar',
                        'description' => 'Borra planes.',
                    ],
                ],
            ],
            'reportes' => [
                'label' => 'Reportes',
                'description' => 'Consulta y exportacion.',
                'permissions' => [
                    'ver reportes' => [
                        'label' => 'Ver',
                        'description' => 'Vistas de reporte.',
                    ],
                    'exportar reportes' => [
                        'label' => 'Exportar',
                        'description' => 'PDF y Excel.',
                    ],
                ],
            ],
            'catalogos' => [
                'label' => 'Catalogos',
                'description' => 'Lineas e historicos.',
                'permissions' => [
                    'ver historico revisados' => [
                        'label' => 'Ver historico',
                        'description' => 'Consulta revisiones.',
                    ],
                    'restablecer historico revisados' => [
                        'label' => 'Reiniciar historico',
                        'description' => 'Reinicia estadisticas.',
                    ],
                    'ver lineas' => [
                        'label' => 'Ver lineas',
                        'description' => 'Consulta lineas.',
                    ],
                    'crear lineas' => [
                        'label' => 'Crear lineas',
                        'description' => 'Nuevas lineas.',
                    ],
                    'editar lineas' => [
                        'label' => 'Editar lineas',
                        'description' => 'Actualiza estados.',
                    ],
                    'eliminar lineas' => [
                        'label' => 'Eliminar lineas',
                        'description' => 'Borra lineas.',
                    ],
                ],
            ],
            'admin' => [
                'label' => 'Admin',
                'description' => 'Usuarios, IA y perfil.',
                'permissions' => [
                    'gestionar usuarios' => [
                        'label' => 'Usuarios',
                        'description' => 'Crea y edita accesos.',
                    ],
                    User::PERMISSION_VIEW_AI_OBSERVABILITY => [
                        'label' => 'Observabilidad IA',
                        'description' => 'Metricas y errores IA.',
                    ],
                    'ver perfil' => [
                        'label' => 'Perfil',
                        'description' => 'Datos y ajustes.',
                    ],
                    'editar perfil' => [
                        'label' => 'Editar perfil',
                        'description' => 'Datos y contrasena.',
                    ],
                    'ver notificaciones' => [
                        'label' => 'Ver notificaciones',
                        'description' => 'Consulta avisos.',
                    ],
                    'gestionar notificaciones' => [
                        'label' => 'Gestionar avisos',
                        'description' => 'Marca y verifica.',
                    ],
                ],
            ],
            'analisis_legacy' => [
                'label' => 'Legado y elongaciones',
                'description' => 'Analisis original y mediciones.',
                'permissions' => [
                    'ver analisis legado' => [
                        'label' => 'Ver legado',
                        'description' => 'Consulta registros.',
                    ],
                    'crear analisis legado' => [
                        'label' => 'Crear legado',
                        'description' => 'Nuevos registros.',
                    ],
                    'editar analisis legado' => [
                        'label' => 'Editar legado',
                        'description' => 'Registros y fotos.',
                    ],
                    'eliminar analisis legado' => [
                        'label' => 'Eliminar legado',
                        'description' => 'Borra registros.',
                    ],
                    'exportar analisis legado' => [
                        'label' => 'Exportar legado',
                        'description' => 'Genera reportes.',
                    ],
                    'ver elongaciones' => [
                        'label' => 'Ver elongaciones',
                        'description' => 'Consulta ciclos.',
                    ],
                    'crear elongaciones' => [
                        'label' => 'Crear elongaciones',
                        'description' => 'Nuevas mediciones.',
                    ],
                    'editar elongaciones' => [
                        'label' => 'Editar elongaciones',
                        'description' => 'Actualiza mediciones.',
                    ],
                    'eliminar elongaciones' => [
                        'label' => 'Eliminar elongaciones',
                        'description' => 'Borra mediciones.',
                    ],
                ],
            ],
        ];
    }

    public static function names(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->push(self::CUSTOM_ACCESS_CONTROL)
            ->unique()
            ->values()
            ->all();
    }

    public static function visibleNames(): array
    {
        return array_values(array_diff(self::names(), [self::CUSTOM_ACCESS_CONTROL]));
    }

    public static function permissionForRoute(?string $routeName, string $method = 'GET'): ?string
    {
        if (!$routeName) {
            return null;
        }

        $method = strtoupper($method);

        foreach (self::routeRules() as $rule) {
            $methods = $rule['methods'] ?? null;

            if ($methods && !in_array($method, $methods, true)) {
                continue;
            }

            foreach ((array) $rule['routes'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $rule['permission'];
                }
            }
        }

        return null;
    }

    public static function defaultAllows(User $user, string $permission): bool
    {
        return self::allows($user, $permission, true);
    }

    public static function roleDefaultAllows(User $user, string $permission): bool
    {
        return self::allows($user, $permission, false);
    }

    private static function allows(User $user, string $permission, bool $includeDirectUserPermissions): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        return match ($permission) {
            'gestionar usuarios' => false,
            User::PERMISSION_VIEW_AI_OBSERVABILITY => false,
            User::PERMISSION_ACCESS_PASTEURIZADORA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
            User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
            'ver dashboard pasteurizadoras',
            'ver tendencias pasteurizadora',
            'crear tendencias pasteurizadora',
            'crear analisis pasteurizadora',
            'editar analisis pasteurizadora',
            'eliminar analisis pasteurizadora',
            'exportar analisis pasteurizadora' => $includeDirectUserPermissions
                ? $user->canAccessModule(User::MODULE_PASTEURIZADORA)
                : $user->canAccessModuleByDefault(User::MODULE_PASTEURIZADORA),
            'ver lineas',
            'crear lineas',
            'editar lineas',
            'eliminar lineas',
            'restablecer historico revisados' => $user->hasAnyRole(User::elevatedMaintenanceRoles()),
            'ver reportes',
            'exportar reportes' => !$user->usesTechnicianAccessProfile(),
            User::PERMISSION_VIEW_LAVADORA_COST_MODULE,
            User::PERMISSION_ACCESS_LAVADORA_COSTS,
            User::PERMISSION_CREATE_LAVADORA_COSTS,
            User::PERMISSION_EDIT_LAVADORA_COSTS,
            User::PERMISSION_DELETE_LAVADORA_COSTS,
            User::PERMISSION_MANAGE_LAVADORA_COSTS => false,
            User::PERMISSION_CLOSE_LAVADORA_DAMAGE => false,
            'eliminar analisis etiquetadora',
            'eliminar analisis pasteurizadora',
            'eliminar analisis legado',
            User::PERMISSION_DELETE_ANALYSIS => $includeDirectUserPermissions
                ? $user->canDeleteAnalysis()
                : false,
            default => true,
        };
    }

    private static function routeRules(): array
    {
        return [
            ['routes' => ['dashboard', 'dashboard.alias'], 'permission' => 'ver dashboard principal'],
            ['routes' => ['tecnico.dashboard'], 'permission' => 'ver dashboard tecnico'],
            ['routes' => ['dashboard.global.lavadoras', 'dashboard.operativo.lavadora', 'dashboard_lavadora', 'lavadora.dashboard', 'api.danos-tendencia'], 'permission' => 'ver dashboard lavadoras'],
            ['routes' => ['dashboard.global.pasteurizadoras', 'dashboard.operativo.pasteurizadora', 'dashboard_pasteurizadora', 'pasteurizadora.dashboard'], 'permission' => 'ver dashboard pasteurizadoras'],
            ['routes' => ['dashboard.global.etiquetadoras', 'dashboard_etiquetadora', 'etiquetadora.dashboard'], 'permission' => 'ver dashboard etiquetadoras'],

            ['routes' => ['admin.users.index', 'admin.users.edit'], 'methods' => ['GET'], 'permission' => 'gestionar usuarios'],
            ['routes' => ['admin.users.store', 'admin.users.update', 'admin.users.destroy', 'admin.users.permissions.update'], 'permission' => 'gestionar usuarios'],
            ['routes' => ['admin.ai-observability.index'], 'methods' => ['GET'], 'permission' => User::PERMISSION_VIEW_AI_OBSERVABILITY],

            ['routes' => ['lavadora.costos.index', 'analisis-lavadora.costos.manage', 'admin.costos.index'], 'methods' => ['GET'], 'permission' => User::PERMISSION_ACCESS_LAVADORA_COSTS],
            ['routes' => ['analisis-lavadora.costos.manual.store', 'admin.costos.catalog.store', 'admin.costos.rules.store', 'admin.costos.budgets.upsert'], 'permission' => User::PERMISSION_CREATE_LAVADORA_COSTS],
            ['routes' => ['analisis-lavadora.costos.automatic.*', 'admin.costos.catalog.update', 'admin.costos.catalog.toggle', 'admin.costos.rules.update', 'admin.costos.budgets.upsert'], 'permission' => User::PERMISSION_EDIT_LAVADORA_COSTS],
            ['routes' => ['analisis-lavadora.costos.manual.destroy', 'admin.costos.catalog.destroy', 'admin.costos.rules.destroy'], 'permission' => User::PERMISSION_DELETE_LAVADORA_COSTS],

            ['routes' => ['analisis-lavadora.index', 'analisis-lavadora.historial', 'analisis-lavadora.show', 'analisis-lavadora.get-*'], 'methods' => ['GET'], 'permission' => 'ver analisis lavadora'],
            ['routes' => ['analisis-lavadora.select-linea', 'analisis-lavadora.create', 'analisis-lavadora.create-quick'], 'methods' => ['GET'], 'permission' => 'crear analisis lavadora'],
            ['routes' => ['analisis-lavadora.store'], 'permission' => 'crear analisis lavadora'],
            ['routes' => ['analisis-lavadora.edit', 'analisis-lavadora.update', 'analisis-lavadora.delete-foto'], 'permission' => 'editar analisis lavadora'],
            ['routes' => ['analisis-lavadora.correccion.update'], 'permission' => User::PERMISSION_CLOSE_LAVADORA_DAMAGE],
            ['routes' => ['analisis-lavadora.destroy'], 'permission' => User::PERMISSION_DELETE_ANALYSIS],

            ['routes' => ['analisis-etiquetadora.index', 'analisis-etiquetadora.historial', 'analisis-etiquetadora.show', 'api.etiquetadora.*'], 'methods' => ['GET'], 'permission' => 'ver analisis etiquetadora'],
            ['routes' => ['analisis-etiquetadora.select-linea', 'analisis-etiquetadora.create'], 'methods' => ['GET'], 'permission' => 'crear analisis etiquetadora'],
            ['routes' => ['analisis-etiquetadora.store'], 'permission' => 'crear analisis etiquetadora'],
            ['routes' => ['analisis-etiquetadora.edit', 'analisis-etiquetadora.update', 'analisis-etiquetadora.delete-foto'], 'permission' => 'editar analisis etiquetadora'],
            ['routes' => ['analisis-etiquetadora.destroy'], 'permission' => 'eliminar analisis etiquetadora'],

            ['routes' => ['pasteurizadora.analisis-pasteurizadora.index', 'pasteurizadora.analisis-pasteurizadora.select-linea', 'pasteurizadora.analisis-pasteurizadora.historial', 'pasteurizadora.analisis-pasteurizadora.historico-revisados', 'pasteurizadora.analisis-pasteurizadora.plan-accion.index', 'pasteurizadora.analisis-pasteurizadora.show', 'pasteurizadora.analisis-pasteurizadora.ajax.*', 'api.pasteurizadora.*'], 'methods' => ['GET', 'POST'], 'permission' => User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA],
            ['routes' => ['pasteurizadora.analisis-pasteurizadora.create*', 'pasteurizadora.analisis-pasteurizadora.store*', 'pasteurizadora.analisis-pasteurizadora.crear-lineas'], 'permission' => 'crear analisis pasteurizadora'],
            ['routes' => ['pasteurizadora.analisis-pasteurizadora.edit', 'pasteurizadora.analisis-pasteurizadora.update', 'pasteurizadora.analisis-pasteurizadora.delete-foto', 'pasteurizadora.analisis-pasteurizadora.plan-accion.*'], 'permission' => 'editar analisis pasteurizadora'],
            ['routes' => ['pasteurizadora.analisis-pasteurizadora.destroy'], 'permission' => 'eliminar analisis pasteurizadora'],
            ['routes' => ['pasteurizadora.analisis-pasteurizadora.export.*', 'pasteurizadora.analisis-pasteurizadora.export-process'], 'permission' => 'exportar analisis pasteurizadora'],

            ['routes' => ['pasteurizadora.central-hidraulica.index', 'pasteurizadora.central-hidraulica.select-linea', 'pasteurizadora.central-hidraulica.historial', 'pasteurizadora.central-hidraulica.historico-revisados', 'pasteurizadora.central-hidraulica.show', 'pasteurizadora.central-hidraulica.ajax.*'], 'methods' => ['GET', 'POST'], 'permission' => User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA],
            ['routes' => ['pasteurizadora.central-hidraulica.create*', 'pasteurizadora.central-hidraulica.store*'], 'permission' => 'crear analisis pasteurizadora'],
            ['routes' => ['pasteurizadora.central-hidraulica.edit', 'pasteurizadora.central-hidraulica.update', 'pasteurizadora.central-hidraulica.delete-foto'], 'permission' => 'editar analisis pasteurizadora'],
            ['routes' => ['pasteurizadora.central-hidraulica.destroy'], 'permission' => 'eliminar analisis pasteurizadora'],
            ['routes' => ['pasteurizadora.central-hidraulica.export.*', 'pasteurizadora.central-hidraulica.export-process'], 'permission' => 'exportar analisis pasteurizadora'],

            ['routes' => ['analisis-tendencia-mensual.lavadora.index', 'analisis-tendencia-mensual.lavadora.analisis-*', 'analisis-tendencia-mensual.lavadora.show'], 'methods' => ['GET'], 'permission' => 'ver tendencias lavadora'],
            ['routes' => ['analisis-tendencia-mensual.lavadora.create'], 'methods' => ['GET'], 'permission' => 'crear tendencias lavadora'],
            ['routes' => ['analisis-tendencia-mensual.lavadora.store'], 'permission' => 'crear tendencias lavadora'],
            ['routes' => ['analisis-tendencia-mensual.pasteurizadora.index', 'analisis-tendencia-mensual.pasteurizadora.analisis-*', 'analisis-tendencia-mensual.pasteurizadora.show'], 'methods' => ['GET'], 'permission' => 'ver tendencias pasteurizadora'],
            ['routes' => ['analisis-tendencia-mensual.pasteurizadora.create'], 'methods' => ['GET'], 'permission' => 'crear tendencias pasteurizadora'],
            ['routes' => ['analisis-tendencia-mensual.pasteurizadora.store'], 'permission' => 'crear tendencias pasteurizadora'],

            ['routes' => ['historico-revisados.index'], 'methods' => ['GET'], 'permission' => 'ver historico revisados'],
            ['routes' => ['historico-revisados.reset-estadisticas', 'historico-revisados.check-reset-status'], 'permission' => 'restablecer historico revisados'],

            ['routes' => ['plan-accion.index', 'plan-accion.show', 'plan-accion.dashboard', 'plan-accion.por-lavadora', 'plan-accion.lavadora.index', 'plan-accion.notificaciones-pendientes'], 'methods' => ['GET'], 'permission' => 'ver planes accion'],
            ['routes' => ['plan-accion.create'], 'methods' => ['GET'], 'permission' => 'crear planes accion'],
            ['routes' => ['plan-accion.store'], 'permission' => 'crear planes accion'],
            ['routes' => ['plan-accion.edit', 'plan-accion.update', 'plan-accion.lavadora.edit', 'plan-accion.lavadora.update', 'plan-accion.notificar', 'plan-accion.notificacion.marcar-leida', 'plan-accion.checklist'], 'permission' => 'editar planes accion'],
            ['routes' => ['plan-accion.destroy', 'plan-accion.lavadora.destroy'], 'permission' => 'eliminar planes accion'],

            ['routes' => ['reportes.index', 'reportes.show*', 'reportes.elongacion', 'reportes.componentes', 'reportes.paros', 'reportes.pasteurizadora'], 'methods' => ['GET'], 'permission' => 'ver reportes'],
            ['routes' => ['reportes.export-*'], 'permission' => 'exportar reportes'],

            ['routes' => ['lineas.index', 'lineas.show'], 'methods' => ['GET'], 'permission' => 'ver lineas'],
            ['routes' => ['lineas.create'], 'methods' => ['GET'], 'permission' => 'crear lineas'],
            ['routes' => ['lineas.store'], 'permission' => 'crear lineas'],
            ['routes' => ['lineas.edit', 'lineas.update', 'lineas.toggle'], 'permission' => 'editar lineas'],
            ['routes' => ['lineas.destroy'], 'permission' => 'eliminar lineas'],

            ['routes' => ['profile.edit', 'profile.notifications', 'notificaciones.configuracion'], 'methods' => ['GET'], 'permission' => 'ver perfil'],
            ['routes' => ['profile.update', 'profile.destroy', 'profile.notifications.update', 'notificaciones.configuracion.update'], 'permission' => 'editar perfil'],
            ['routes' => ['notifications.index', 'notifications.open', 'notifications.unread-count'], 'methods' => ['GET'], 'permission' => 'ver notificaciones'],
            ['routes' => ['notifications.read', 'notifications.read-all', 'notificaciones.verify.phone'], 'permission' => 'gestionar notificaciones'],

            ['routes' => ['analisis.index', 'analisis.porLinea', 'analisis.show', 'analisis.estadisticas', 'analisis.numeros-r', 'analisis.linea.componentes', 'analisis.componente.reductores', 'api.categorias.numeros-r', 'api.estadisticas.dashboard', 'api.analisis.tendencia'], 'methods' => ['GET'], 'permission' => 'ver analisis legado'],
            ['routes' => ['analisis.nuevo', 'analisis.seleccionar-componente', 'analisis.crear'], 'methods' => ['GET'], 'permission' => 'crear analisis legado'],
            ['routes' => ['analisis.store'], 'permission' => 'crear analisis legado'],
            ['routes' => ['analisis.edit', 'analisis.update', 'analisis.eliminar-foto', 'analisis.elongacion.create', 'analisis.elongacion.store'], 'permission' => 'editar analisis legado'],
            ['routes' => ['analisis.destroy'], 'permission' => 'eliminar analisis legado'],
            ['routes' => ['analisis.exportar.*', 'analisis.analisis.exportar.*', 'analisis.exportar.pdf'], 'permission' => 'exportar analisis legado'],

            ['routes' => ['elongaciones.index', 'elongaciones.show', 'elongaciones.ciclos.*', 'elongaciones.ultima-lectura', 'elongaciones.reporte*'], 'methods' => ['GET'], 'permission' => 'ver elongaciones'],
            ['routes' => ['elongaciones.create'], 'methods' => ['GET'], 'permission' => 'crear elongaciones'],
            ['routes' => ['elongaciones.store'], 'permission' => 'crear elongaciones'],
            ['routes' => ['elongaciones.edit', 'elongaciones.update'], 'permission' => 'editar elongaciones'],
            ['routes' => ['elongaciones.destroy'], 'permission' => 'eliminar elongaciones'],
        ];
    }
}
