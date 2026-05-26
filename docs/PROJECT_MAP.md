# Mapa Del Proyecto

Este documento resume donde vive cada parte importante sin cambiar el comportamiento actual de la aplicacion.

## Dominio

- Lavadoras: `AnalisisLavadoraController`, `AnalisisLavadora`, vistas en `resources/views/lavadora`.
- Pasteurizadoras: `AnalisisPasteurizadoraController`, `AnalisisPasteurizadora`, vistas en `resources/views/pasteurizadora`.
- Elongaciones: `ElongacionController`, `Elongacion`, `CadenaCiclo`, vistas en `resources/views/elongaciones`.
- Plan de accion: `PlanAccionController`, `PlanAccion`, vistas en `resources/views/plan-accion`.
- Reportes: `ReporteController`, exports en `app/Exports`, vistas en `resources/views/reportes`.
- Notificaciones: `NotificationService`, `ElongacionReminderService`, `WhatsAppService`.

## Rutas

- `routes/web.php`: rutas web de la aplicacion y endpoints internos usados por las vistas.
- `routes/auth.php`: login, registro, recuperacion de password y verificacion de correo.
- `routes/console.php`: agenda del scheduler.

## Archivos Que Conviene Tratar Con Cuidado

- `routes/web.php`: muchas vistas dependen de nombres de ruta existentes.
- `app/Http/Controllers/DashboardController.php`: concentra calculos de dashboards.
- `app/Http/Controllers/AnalisisLavadoraController.php`: maneja filtros, alta/edicion, evidencias y datos auxiliares.
- `app/Http/Controllers/AnalisisPasteurizadoraController.php`: maneja ciclos de revision, evidencias y AJAX.
- `app/Http/Controllers/ReporteController.php`: contiene consultas y armado de reportes.
- `resources/views/layouts/app.blade.php`: layout base de casi todas las pantallas autenticadas.

## Mejoras Futuras Recomendadas

- Mover reglas de validacion a `FormRequest`.
- Mover catalogos de lineas/componentes a configuracion o servicios de dominio.
- Extraer consultas de dashboard y reportes a clases dedicadas.
- Mover JS/CSS inline de vistas grandes a assets versionados por Vite.
- Revisar permisos por rol con policies o middleware mas especificos.
- Aumentar pruebas de permisos, rutas criticas, reportes y subida/eliminacion de evidencias.
