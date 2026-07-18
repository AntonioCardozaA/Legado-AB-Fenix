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
                'description' => 'Vistas principales y tableros globales del sistema.',
                'permissions' => [
                    'ver dashboard principal' => [
                        'label' => 'Dashboard principal',
                        'description' => 'Permite entrar al panel principal.',
                    ],
                    'ver dashboard tecnico' => [
                        'label' => 'Dashboard tecnico',
                        'description' => 'Permite entrar al panel compartido de tecnico/ingeniero.',
                    ],
                    'ver dashboard lavadoras' => [
                        'label' => 'Dashboards de Lavadoras',
                        'description' => 'Permite ver tableros globales y operativos de Lavadora.',
                    ],
                    'ver dashboard pasteurizadoras' => [
                        'label' => 'Dashboards de Pasteurizadoras',
                        'description' => 'Permite ver tableros globales y operativos de Pasteurizadora.',
                    ],
                    'ver dashboard etiquetadoras' => [
                        'label' => 'Dashboards de Etiquetadoras',
                        'description' => 'Permite ver tableros globales y operativos de Etiquetadora.',
                    ],
                ],
            ],
            'analisis_lavadora' => [
                'label' => 'Analisis de Lavadora',
                'description' => 'Listado, detalle, captura y mantenimiento de analisis de lavadoras.',
                'permissions' => [
                    User::PERMISSION_ACCESS_LAVADORA => [
                        'label' => 'Entrar al modulo Lavadora',
                        'description' => 'Permite abrir vistas generales del modulo.',
                    ],
                    'ver analisis lavadora' => [
                        'label' => 'Ver analisis',
                        'description' => 'Permite consultar listado, detalle, historial y datos auxiliares.',
                    ],
                    'crear analisis lavadora' => [
                        'label' => 'Crear analisis',
                        'description' => 'Permite abrir formularios y guardar nuevos registros.',
                    ],
                    'editar analisis lavadora' => [
                        'label' => 'Editar analisis',
                        'description' => 'Permite actualizar registros y eliminar fotos.',
                    ],
                    User::PERMISSION_DELETE_ANALYSIS => [
                        'label' => 'Eliminar analisis',
                        'description' => 'Permite eliminar registros de analisis cuando el flujo lo habilita.',
                    ],
                    User::PERMISSION_CLOSE_LAVADORA_DAMAGE => [
                        'label' => 'Gestionar cierre y costos del detalle',
                        'description' => 'Muestra y permite guardar las opciones nuevas del modal de detalles.',
                    ],
                ],
            ],
            'costos_lavadora' => [
                'label' => 'Costos de Lavadora',
                'description' => 'Permisos granulares para visualizar, consultar y modificar informacion de costos.',
                'permissions' => [
                    User::PERMISSION_VIEW_LAVADORA_COST_MODULE => [
                        'label' => 'Ver modulo de Costos',
                        'description' => 'Muestra accesos y botones hacia el modulo de Costos.',
                    ],
                    User::PERMISSION_ACCESS_LAVADORA_COSTS => [
                        'label' => 'Acceder a la vista de Costos',
                        'description' => 'Permite abrir el tablero y las vistas de administracion de costos.',
                    ],
                    User::PERMISSION_CREATE_LAVADORA_COSTS => [
                        'label' => 'Crear registros',
                        'description' => 'Permite registrar conceptos manuales, reglas, catalogo y presupuestos.',
                    ],
                    User::PERMISSION_EDIT_LAVADORA_COSTS => [
                        'label' => 'Editar registros',
                        'description' => 'Permite actualizar catalogos, reglas, presupuestos y sincronizaciones.',
                    ],
                    User::PERMISSION_DELETE_LAVADORA_COSTS => [
                        'label' => 'Eliminar registros',
                        'description' => 'Permite eliminar conceptos, reglas o gastos manuales permitidos.',
                    ],
                    User::PERMISSION_MANAGE_LAVADORA_COSTS => [
                        'label' => 'Manipular toda la informacion',
                        'description' => 'Concede todas las acciones del modulo de Costos.',
                    ],
                ],
            ],
            'analisis_etiquetadora' => [
                'label' => 'Analisis de Etiquetadora',
                'description' => 'Vistas y acciones del modulo de Etiquetadora.',
                'permissions' => [
                    User::PERMISSION_ACCESS_ETIQUETADORA => [
                        'label' => 'Entrar al modulo Etiquetadora',
                        'description' => 'Permite abrir vistas generales del modulo.',
                    ],
                    'ver analisis etiquetadora' => [
                        'label' => 'Ver analisis',
                        'description' => 'Permite consultar listado, detalle e historial.',
                    ],
                    'crear analisis etiquetadora' => [
                        'label' => 'Crear analisis',
                        'description' => 'Permite abrir formularios y guardar nuevos registros.',
                    ],
                    'editar analisis etiquetadora' => [
                        'label' => 'Editar analisis',
                        'description' => 'Permite actualizar registros y eliminar fotos.',
                    ],
                    'eliminar analisis etiquetadora' => [
                        'label' => 'Eliminar analisis',
                        'description' => 'Permite eliminar registros de Etiquetadora.',
                    ],
                ],
            ],
            'pasteurizadora' => [
                'label' => 'Pasteurizadora',
                'description' => 'Acceso a mecanica, central hidraulica y acciones de analisis.',
                'permissions' => [
                    User::PERMISSION_ACCESS_PASTEURIZADORA => [
                        'label' => 'Entrar al modulo Pasteurizadora',
                        'description' => 'Permite abrir vistas generales del modulo.',
                    ],
                    User::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA => [
                        'label' => 'Ver mecanica',
                        'description' => 'Permite entrar al analisis mecanico de Pasteurizadora.',
                    ],
                    User::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA => [
                        'label' => 'Ver central hidraulica',
                        'description' => 'Permite entrar al analisis de central hidraulica.',
                    ],
                    'crear analisis pasteurizadora' => [
                        'label' => 'Crear analisis',
                        'description' => 'Permite abrir formularios y guardar nuevos registros.',
                    ],
                    'editar analisis pasteurizadora' => [
                        'label' => 'Editar analisis',
                        'description' => 'Permite actualizar registros, fotos y lineas auxiliares.',
                    ],
                    'eliminar analisis pasteurizadora' => [
                        'label' => 'Eliminar analisis',
                        'description' => 'Permite eliminar registros de Pasteurizadora.',
                    ],
                    'exportar analisis pasteurizadora' => [
                        'label' => 'Exportar analisis',
                        'description' => 'Permite generar Excel y PDF desde Pasteurizadora.',
                    ],
                ],
            ],
            'tendencias' => [
                'label' => 'Tendencias mensuales',
                'description' => 'Analisis de tendencia 52-12-4 y 30-14-7.',
                'permissions' => [
                    'ver tendencias lavadora' => [
                        'label' => 'Ver tendencias Lavadora',
                        'description' => 'Permite consultar tendencias mensuales de Lavadora.',
                    ],
                    'crear tendencias lavadora' => [
                        'label' => 'Crear tendencias Lavadora',
                        'description' => 'Permite crear registros de tendencia de Lavadora.',
                    ],
                    'ver tendencias pasteurizadora' => [
                        'label' => 'Ver tendencias Pasteurizadora',
                        'description' => 'Permite consultar tendencias mensuales de Pasteurizadora.',
                    ],
                    'crear tendencias pasteurizadora' => [
                        'label' => 'Crear tendencias Pasteurizadora',
                        'description' => 'Permite crear registros de tendencia de Pasteurizadora.',
                    ],
                ],
            ],
            'plan_accion' => [
                'label' => 'Plan de Accion',
                'description' => 'Planes, checklist y notificaciones de seguimiento.',
                'permissions' => [
                    'ver planes accion' => [
                        'label' => 'Ver planes',
                        'description' => 'Permite consultar planes, dashboard y pendientes.',
                    ],
                    'crear planes accion' => [
                        'label' => 'Crear planes',
                        'description' => 'Permite registrar planes de accion.',
                    ],
                    'editar planes accion' => [
                        'label' => 'Editar planes',
                        'description' => 'Permite actualizar, notificar y completar checklist.',
                    ],
                    'eliminar planes accion' => [
                        'label' => 'Eliminar planes',
                        'description' => 'Permite eliminar planes de accion.',
                    ],
                ],
            ],
            'reportes' => [
                'label' => 'Reportes',
                'description' => 'Vistas de reportes y exportaciones.',
                'permissions' => [
                    'ver reportes' => [
                        'label' => 'Ver reportes',
                        'description' => 'Permite consultar todas las vistas de reportes.',
                    ],
                    'exportar reportes' => [
                        'label' => 'Exportar reportes',
                        'description' => 'Permite descargar PDF y Excel.',
                    ],
                ],
            ],
            'catalogos' => [
                'label' => 'Catalogos y mantenimiento',
                'description' => 'Lineas, historico revisados y registros base.',
                'permissions' => [
                    'ver historico revisados' => [
                        'label' => 'Ver historico revisados',
                        'description' => 'Permite consultar historicos de revision.',
                    ],
                    'restablecer historico revisados' => [
                        'label' => 'Restablecer historico',
                        'description' => 'Permite reiniciar estadisticas de historico revisados.',
                    ],
                    'ver lineas' => [
                        'label' => 'Ver lineas',
                        'description' => 'Permite consultar el catalogo de lineas.',
                    ],
                    'crear lineas' => [
                        'label' => 'Crear lineas',
                        'description' => 'Permite registrar nuevas lineas.',
                    ],
                    'editar lineas' => [
                        'label' => 'Editar lineas',
                        'description' => 'Permite actualizar y activar/desactivar lineas.',
                    ],
                    'eliminar lineas' => [
                        'label' => 'Eliminar lineas',
                        'description' => 'Permite eliminar lineas.',
                    ],
                ],
            ],
            'admin' => [
                'label' => 'Administracion',
                'description' => 'Gestion de usuarios y configuraciones personales.',
                'permissions' => [
                    'gestionar usuarios' => [
                        'label' => 'Gestionar usuarios',
                        'description' => 'Permite crear y editar usuarios y sus permisos.',
                    ],
                    'ver perfil' => [
                        'label' => 'Ver perfil',
                        'description' => 'Permite abrir perfil y configuracion de notificaciones.',
                    ],
                    'editar perfil' => [
                        'label' => 'Editar perfil',
                        'description' => 'Permite actualizar datos, contrasena y notificaciones propias.',
                    ],
                    'ver notificaciones' => [
                        'label' => 'Ver notificaciones',
                        'description' => 'Permite consultar notificaciones del usuario.',
                    ],
                    'gestionar notificaciones' => [
                        'label' => 'Gestionar notificaciones',
                        'description' => 'Permite marcar notificaciones y verificar telefono.',
                    ],
                ],
            ],
            'analisis_legacy' => [
                'label' => 'Analisis legado y elongaciones',
                'description' => 'Modulo de analisis original y mediciones de elongacion.',
                'permissions' => [
                    'ver analisis legado' => [
                        'label' => 'Ver analisis legado',
                        'description' => 'Permite consultar vistas del analisis original.',
                    ],
                    'crear analisis legado' => [
                        'label' => 'Crear analisis legado',
                        'description' => 'Permite crear registros del analisis original.',
                    ],
                    'editar analisis legado' => [
                        'label' => 'Editar analisis legado',
                        'description' => 'Permite actualizar registros y fotos.',
                    ],
                    'eliminar analisis legado' => [
                        'label' => 'Eliminar analisis legado',
                        'description' => 'Permite eliminar registros del analisis original.',
                    ],
                    'exportar analisis legado' => [
                        'label' => 'Exportar analisis legado',
                        'description' => 'Permite generar reportes del analisis original.',
                    ],
                    'ver elongaciones' => [
                        'label' => 'Ver elongaciones',
                        'description' => 'Permite consultar elongaciones y ciclos.',
                    ],
                    'crear elongaciones' => [
                        'label' => 'Crear elongaciones',
                        'description' => 'Permite registrar elongaciones.',
                    ],
                    'editar elongaciones' => [
                        'label' => 'Editar elongaciones',
                        'description' => 'Permite actualizar elongaciones.',
                    ],
                    'eliminar elongaciones' => [
                        'label' => 'Eliminar elongaciones',
                        'description' => 'Permite eliminar elongaciones.',
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
            ['routes' => ['admin.users.store', 'admin.users.update', 'admin.users.permissions.update'], 'permission' => 'gestionar usuarios'],

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
