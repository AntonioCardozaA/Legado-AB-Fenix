# Optimización 01: evitar el sondeo inicial duplicado de notificaciones

Fecha: 30 de octubre de 2026. Entorno local Windows/Laragon. Referencia: hallazgo **F04, parcialmente atendido**, de `AUDITORIA_RENDIMIENTO_LEGADO_AB_FENIX.md`.

## Problema y decisión

El layout ya construye la campana con `availableNotificationsFor()` y `availableUnreadNotificationsCountFor()` y entrega el listado y contador en el HTML. Después, el script ejecutaba `refreshNotifications()` inmediatamente y repetía esa consulta cada 30 segundos.

Se retiró **solo la llamada inmediata**. Se conserva `setInterval(refreshNotifications, 30000)`, la función de refresco, su endpoint, las acciones de lectura y las reglas de autorización/visibilidad. No hay cambios de esquema, registros, índices, dependencias ni configuración.

Se eligió este alcance por ser acotado y reversible. El filtrado de destinos por lotes de F03 tiene mayor riesgo semántico y requiere una intervención distinta y autorización antes de avanzar con ese cambio de alto riesgo. La pausa por visibilidad y la prevención de solicitudes solapadas del resto de F04 tampoco forman parte de esta entrega.

## Archivos de esta entrega

- `resources/views/layouts/app.blade.php:1430`: sustituye la invocación inmediata por un comentario que explica el uso del HTML inicial.
- `tests/Frontend/notifications-polling.test.mjs`: prueba el bloque real del script con reloj, DOM y red simulados.
- Este documento: condiciones, resultados, límites y reversión.

El informe original de auditoría conserva sus resultados históricos.

## Riesgo y comportamiento esperado

La campana muestra al abrir la página los datos incluidos en el HTML. Una notificación creada entre la consulta de Blade y el siguiente refresco se incorporará al siguiente sondeo, hasta unos 30 segundos después en condiciones normales. Los temporizadores pueden retrasarse si el navegador suspende o limita la pestaña; no se establece una garantía estricta de entrega en 30 segundos.

La función puede seguir invocándose explícitamente. Las acciones de marcar como leído y abrir notificaciones mantienen su navegación/recarga actual. Las reglas que descartan destinos inexistentes, filtros por rol y ciclos de elongaciones no se modificaron.

El costo de construir inicialmente la campana en Blade permanece. Por ello **no se afirma una mejora de TTFB ni que F03 esté resuelto**.

## Comparación del comportamiento inicial

Se extrajo el bloque `function refreshNotifications()` y su inicialización del layout vigente y se ejecutó mediante `node:vm`, antes y después de editar. Se usó la misma página simulada con campana, respuesta JSON y temporizador. No se usó un navegador real ni se enviaron peticiones HTTP durante esta prueba.

| Medición | Antes | Después |
|---|---:|---:|
| Solicitudes de notificaciones al ejecutar el script | 1 | **0** |
| Temporizadores periódicos registrados | 1 | 1 |
| Intervalo configurado | 30000 ms | 30000 ms |
| Solicitudes acumuladas tras el primer tick a los 30 s | 2 | **1** |

Mejora demostrada: **una petición redundante menos por carga del layout con campana**, antes del primer intervalo. La actividad periódica posterior permanece.

## Medición actual del trabajo evitado

Se midió el endpoint directamente en CLI para cuantificar la llamada retirada; el endpoint no fue modificado. Se hizo una invocación de calentamiento y cinco muestras antes, y se repitió el mismo banco después:

- PHP 8.4.12 NTS y MySQL 8.4.3 local.
- Mismo usuario admin existente, seleccionado de forma determinista por ID y verificado mediante huella interna. No se creó una sesión HTTP ni se publicaron sus datos.
- Mismos conteos en ambas mediciones: 2572 notificaciones y 1043 filas en analisis_componentes.
- Reloj fijo: 2026-10-30 10:00:00 America/Mexico_City, equivalente a 16:00 UTC.
- Caché y sesión array, logging null y mailer array, únicamente dentro del proceso del banco.
- `START TRANSACTION READ ONLY`, límite SQL por sentencia de 2500 ms y rollback final.
- Invocación directa de `NotificationController::unreadCount()`; sin middleware, HTTP, render Blade ni descarga de recursos.
- Registro de consultas habilitado solo para cada muestra; no se imprimieron bindings ni contenido privado de las notificaciones.

| Muestra | Antes: controlador ms | Después: controlador ms | SQL antes | SQL después | Antes: SQL ms | Después: SQL ms |
|---:|---:|---:|---:|---:|---:|---:|
| 1 | 868.92 | 740.54 | 905 | 905 | 541.27 | 456.93 |
| 2 | 813.62 | 745.37 | 905 | 905 | 507.41 | 459.53 |
| 3 | 767.29 | 769.00 | 905 | 905 | 472.15 | 473.16 |
| 4 | 795.00 | 763.84 | 905 | 905 | 492.72 | 465.29 |
| 5 | 798.77 | 767.59 | 905 | 905 | 497.87 | 470.67 |
| **Mediana** | **798.77** | **763.84** | **905** | **905** | **497.87** | **465.29** |

El payload JSON completo tuvo la misma huella SHA-256 en las diez muestras: `18041db19093a4a410214697b22e8988dbcbece857ca58b8317ff3e9850b1e22`. En este caso el usuario tenía 440 notificaciones disponibles/no leídas y la respuesta mostraba 10 elementos. La comparación de condiciones y huellas fue idéntica.

**Interpretación:** el endpoint conserva el mismo trabajo. La variación de sus tiempos no se atribuye al cambio del layout. La mejora consiste en evitar una ejecución inicial de una llamada que, para este usuario local, realiza **905 consultas** y tardaba aproximadamente **0.8 segundos** en el banco. El número de consultas evitadas se deriva de la petición eliminada y del costo observado de ese endpoint; no es una captura de red/SQL de una navegación real optimizada.

La auditoría anterior registró 456 consultas para su escenario de usuario. Esta nueva línea base usa un admin seleccionado determinísticamente y su distribución de leídas/no leídas; no se comparan esos usuarios entre sí para atribuir ganancias. Los conteos de datos y la selección del usuario sí coinciden en el antes/después de esta entrega.

No se suman estos tiempos a TTFB ni se promete que la página se vuelva utilizable 0.8 segundos antes: la consulta era asíncrona. Se reduce trabajo inicial del servidor y una solicitud del navegador. Falta medir el efecto sobre la experiencia completa bajo tráfico representativo.

## Pruebas y resultado funcional

### Prueba de regresión frontend

Antes de cambiar el layout, la nueva prueba de estado inicial falló como se esperaba: detectó **1 petición cuando se esperaba 0**. Esto verifica que la prueba identifica el comportamiento que se quería eliminar.

Después del cambio pasaron cinco pruebas nuevas que verifican:

1. Los datos iniciales permanecen visibles y no se solicita información antes de los 30 segundos; el primer sondeo actualiza contador y lista.
2. El refresco continúa cada 30 segundos.
3. Las páginas sin campana no consultan notificaciones.
4. Un fallo de red conserva el contenido mostrado y el siguiente intervalo permite recuperarse.
5. La invocación explícita de refresco sigue disponible inmediatamente.

Junto con las cinco pruebas frontend existentes de LEF y numeración de pasteurizadoras, resultado: **10 aprobadas, 0 fallidas**.

Comando ejecutado:

```powershell
& 'C:\laragon\bin\nodejs\node-v22\node.exe' --test tests/Frontend/notifications-polling.test.mjs tests/Frontend/dashboard-lef-52124.test.mjs tests/Frontend/pasteurizadora-numeracion.test.mjs
```

Estas pruebas usan stubs; no demuestran renderizado ni rendimiento de un navegador real.

### Pruebas PHP de notificaciones

Se ejecutó `tests/Feature/NotificationControllerTest.php` antes de editar el layout y después, con SQLite `:memory:`, caché/sesión array, correo array, IA desactivada y sin caché de resultados PHPUnit. `tests/TestCase.php` rechaza una conexión distinta de SQLite en memoria. Las vistas de pruebas se compilan en el directorio temporal configurado por ese TestCase.

Resultado en ambas ejecuciones: **16 pruebas, 50 aserciones, 15 aprobadas y 1 fallida**.

Fallo preexistente, sin cambio de resultado:

`test_target_role_open_can_view_pasteurizadora_action_plan_notification`, en `tests/Feature/NotificationControllerTest.php:487`.

- Esperaba redirigir a `/plan-accion?tipo=pasteurizadora&linea_id=1&open_plan_id=1`.
- Redirige a `/notifications`.

Esta entrega no corrige ese defecto ni lo atribuye a la optimización. No se declara que toda la suite PHP esté en verde. Las pruebas restantes de lectura, permisos, destinos y navegación de notificaciones siguieron pasando.

Comando PHP, con variables de aislamiento de proceso preparadas:

```powershell
& 'C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe' vendor/bin/phpunit --do-not-cache-result tests/Feature/NotificationControllerTest.php
```

## Criterio de conservación y reversión

La mejora se conserva porque elimina la solicitud inicial y sus consultas asociadas, mantiene el refresco periódico y no introduce fallos nuevos en las pruebas ejecutadas. No se realizaron otras optimizaciones en esta entrega.

Revertir si se determina que el producto necesita una segunda lectura inmediata tras generar el HTML, o si una comprobación en navegador demuestra que alguna página con campana no entrega sus datos iniciales correctamente. La reversión funcional es volver a colocar `refreshNotifications();` inmediatamente antes de `setInterval(refreshNotifications, 30000)` y ajustar las pruebas/documentación a esa decisión. No requiere cambios de base de datos.

La siguiente validación de experiencia real es comparar navegaciones con el mismo usuario y datos, revisar Network y confirmar lista/contador iniciales, lectura, apertura y actualización a los 30 segundos. Los cambios de alto riesgo del cálculo de estados y filtrado de destinos quedan pendientes de autorización.
