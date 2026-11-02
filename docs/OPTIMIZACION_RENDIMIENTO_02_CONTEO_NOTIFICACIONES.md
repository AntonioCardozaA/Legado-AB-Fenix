# Optimización 02: reutilizar la colección para contar notificaciones no leídas

Fecha de referencia: 30 de octubre de 2026. Entorno local Windows/Laragon.

## Alcance y decisión previa

Una sola optimización conceptual: **calcular las no leídas desde la colección completa que ya obtuvo y filtró el servicio**, en los puntos donde se necesitaban ambos conjuntos.

Hallazgos relacionados de la auditoría: consultas repetidas de F03 y resumen de etiquetadoras. Esta entrega no implementa la resolución de destinos por lotes ni altera sus reglas.

Antes del cambio, el layout, el controlador de notificaciones y el resumen de etiquetadoras llamaban a `availableNotificationsFor()` y después a `availableUnreadNotificationsFor()` o su contador. Cada segunda consulta volvía a filtrar permisos y a comprobar los destinos de las no leídas.

Se verificó el código instalado de Laravel: `HasDatabaseNotifications::unreadNotifications()` agrega el scope `DatabaseNotification::scopeUnread()`, cuya condición es `whereNull('read_at')`. Ambos caminos de NotificationVisibilityService aplican la misma visibilidad y validación de destinos. Por eso se reutiliza el resultado ya validado y se cuenta su subconjunto con read_at nulo.

La explicación de problema, causa, archivos, solución y riesgo se entregó antes de editar.

## Cambios

- `app/Http/Controllers/NotificationController.php`: index cuenta sobre la colección completa antes de paginar; unreadCount cuenta antes de limitar los diez elementos de respuesta.
- `app/Http/Controllers/DashboardController.php`: el resumen de etiquetadoras cuenta sobre sus notificaciones ya filtradas por módulo, antes de tomar las cinco más recientes.
- `resources/views/layouts/app.blade.php`: contador de campana calculado sobre la colección disponible completa.
- `tests/Feature/NotificationUnreadReuseTest.php`: cuatro pruebas nuevas.
- Este documento: mediciones, límites y reversión.

No se cambió NotificationVisibilityService, sus consultas de autorización, ni las comprobaciones de destinos y ciclos. No se agregó caché, singleton ni estado persistente entre peticiones. No se alteraron registros, esquema o dependencias.

La optimización 01 permanece: la campana sigue usando el HTML inicial y refrescándose cada 30 segundos.

## Riesgo y equivalencia

El contador y la lista ahora reflejan el mismo conjunto consultado dentro de esa operación. Si otra pestaña marca una notificación como leída después de esa consulta, el cambio se verá al refrescar. Antes existía una segunda lectura que podía observar un estado posterior y producir una lista/contador de instantes diferentes.

El conteo **no se calcula sobre los diez elementos mostrados, las cinco recientes ni la página de veinte**. Se conserva el conteo global del conjunto disponible, con los mismos permisos y exclusión de destinos inexistentes.

La página de notificaciones y el layout aún cargan sus colecciones por separado; esta entrega no introduce una caché compartida para eliminar también esa repetición. Sigue pendiente optimizar las comprobaciones individuales de destinos con autorización para un cambio de mayor riesgo.

## Banco equivalente antes y después

- Mismo código de banco en PHP CLI por stdin.
- PHP 8.4.12 NTS y MySQL 8.4.3 local.
- Mismo admin existente, ordenado por ID; identidad verificada internamente por huella sin publicar datos del usuario.
- Misma base: 2572 notificaciones y 1043 filas en analisis_componentes en ambas ejecuciones.
- Reloj fijo: 2026-10-30 10:00 America/Mexico_City, 16:00 UTC.
- Caché y sesión array, logging null y mailer array solo en el proceso del banco.
- Conexión con START TRANSACTION READ ONLY, MAX_EXECUTION_TIME=2500 por sentencia y rollback final.
- Una ejecución de calentamiento y cinco muestras secuenciales por escenario, antes de editar y después.
- Consultas registradas únicamente durante cada muestra. No se imprimieron bindings ni contenido privado.
- Las pruebas funcionales SQLite se ejecutaron fuera de los intervalos de medición MySQL.
- Para el formulario, compilación y evaluación Blade en memoria sin escribir vistas compiladas. ID y token de la sesión de prueba fijos para comparar salida.
- Sin HTTP autenticado real, navegador, CDN, carga concurrente ni proveedores externos.

Las condiciones registradas coincidieron. No se vaciaron cachés del sistema/DB ni se controló toda la carga del equipo; los tiempos presentan variación. La reducción estable de consultas es evidencia adicional al descenso de las medianas. Cinco muestras no justifican percentiles de campo ni garantías.

## Resultados medidos

Milisegundos. SQL ms es tiempo acumulado incluido en el tiempo del escenario; no se suma a él.

| Escenario | Antes ms, mediana | Después ms, mediana | SQL antes | SQL después | Antes SQL ms, mediana | Después SQL ms, mediana |
|---|---:|---:|---:|---:|---:|---:|
| Endpoint /notifications/unread-count | 1337.15 | 512.53 | 905 | 456 | 816.54 | 319.37 |
| Alta lavadora L-04: datos y render Blade en memoria | 1341.93 | 762.60 | 907 | 458 | 815.11 | 430.24 |
| Página /notifications: preparación de datos, sin render | 1241.31 | 664.46 | 905 | 456 | 778.51 | 427.32 |
| Resumen privado de notificaciones de etiquetadora, sin render | 1259.05 | 631.02 | 905 | 456 | 746.76 | 390.04 |

Se evitaron **449 consultas por muestra** en cada escenario de este usuario. El endpoint bajó aproximadamente a la mitad de consultas; la mejora depende de cuántas no leídas requieran validar destinos. No se extrapola el mismo ahorro a usuarios con cero o pocas no leídas.

El formulario conserva **140007 bytes de HTML** en las cinco muestras antes y después. Se comparó su HTML excluyendo bloques script; esa exclusión forma parte del banco y no de la aplicación. Los scripts se verificaron con las pruebas frontend. No se afirma equivalencia byte a byte de todos los scripts a partir de esa huella.

El escenario /notifications mide el controlador y los datos del paginador, sin render de esa página. El escenario de etiquetadora mide exclusivamente el resumen privado invocado por el banco mediante ReflectionMethod, no el dashboard completo.

Estos resultados **no son TTFB ni tiempo hasta que el formulario es utilizable en navegador**. La mejora de backend y render en memoria está demostrada en los escenarios locales; falta medir la experiencia completa con red, imágenes y JavaScript.

## Contenido y huellas

Las diez muestras de cada escenario, cinco antes y cinco después, tuvieron la misma huella del contenido comparado:

| Escenario | SHA-256 |
|---|---|
| Endpoint /notifications/unread-count | `18041db19093a4a410214697b22e8988dbcbece857ca58b8317ff3e9850b1e22` |
| Alta lavadora L-04: datos y render Blade en memoria | `f774451e7c8c9b0d340eddf54bd88cfd91ba354764543a2137e88ec483c04aa8` |
| Página /notifications: preparación de datos, sin render | `e49ee370fea12676021c6871c7543594a9ac83c94fc306983a40ae1caabfc11c` |
| Resumen privado de notificaciones de etiquetadora, sin render | `7ef81175d9530ab82311f4b4129a69dd712f309f8156121591c3c641008dca60` |

El JSON se comparó completo con reloj fijo. En /notifications se compararon total, no leídas e IDs de la página. En etiquetadora se comparó el resumen. No se guardaron los contenidos privados originales.

## Pruebas

### Línea base

Las pruebas existentes NotificationControllerTest y DashboardEtiquetadoraTest dieron **18 pruebas, 60 aserciones, 17 aprobadas y 1 fallo previo**.

Las cuatro pruebas nuevas se ejecutaron antes de modificar producción. Las comprobaciones de conteos, permisos, paginación y destinos pasaron. Tres pruebas fallaron solo en el presupuesto de consultas: observaron 2 en lugar de 1 para API y 4 en lugar de 2 para página y dashboard. La prueba de colección vacía pasó.

### Después del cambio

**PHP: 22 pruebas, 83 aserciones; 21 aprobadas y el mismo fallo previo.** Las cuatro nuevas pasaron. Verifican:

1. No leídas fuera de los diez elementos recientes, exclusión por rol, destinos inexistentes y notificaciones de otro usuario; una consulta a notifications en el endpoint.
2. Conteo global fuera de la página visible de veinte, total y badge renderizado correcto; dos consultas a notifications entre controlador y layout.
3. Conteo específico de etiquetadora antes de limitar a cinco, exclusión de otras categorías/roles/destinos y dos consultas entre dashboard y layout.
4. Colección vacía conserva ceros y lista vacía.

Comando PHP ejecutado con IA desactivada, correo array y aislamiento existente a SQLite :memory::

~~~powershell
& 'C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe' vendor/bin/phpunit --do-not-cache-result tests/Feature/NotificationUnreadReuseTest.php tests/Feature/NotificationControllerTest.php tests/Feature/DashboardEtiquetadoraTest.php
~~~

Fallo previo conservado: `NotificationControllerTest::test_target_role_open_can_view_pasteurizadora_action_plan_notification`, línea 487. Espera la ruta del plan de pasteurizadora y recibe /notifications. Se observó también antes del cambio y en la entrega 01; no se corrigió dentro de esta optimización.

**Frontend: 10 aprobadas, 0 fallidas**, incluyendo las cinco pruebas de la entrega 01. Confirman la ausencia de sondeo inicial, refresco periódico, recuperación de errores y numeración/selector LEF.

~~~powershell
& 'C:\laragon\bin\nodejs\node-v22\node.exe' --test tests/Frontend/notifications-polling.test.mjs tests/Frontend/dashboard-lef-52124.test.mjs tests/Frontend/pasteurizadora-numeracion.test.mjs
~~~

También pasó la revisión de sintaxis PHP de ambos controladores y la prueba nueva, y git diff --check.

No se declara toda la suite PHP en verde ni se presentan pruebas con stubs como una validación en navegador real.

## Muestras

### Endpoint /notifications/unread-count

| Muestra | Antes ms | Después ms | SQL antes | SQL después |
|---:|---:|---:|---:|---:|
| 1 | 1337.15 | 468.21 | 905 | 456 |
| 2 | 1426.42 | 521.12 | 905 | 456 |
| 3 | 1396.05 | 759.25 | 905 | 456 |
| 4 | 972.33 | 486.83 | 905 | 456 |
| 5 | 937.71 | 512.53 | 905 | 456 |

### Alta lavadora L-04: datos y render Blade en memoria

| Muestra | Antes ms | Después ms | SQL antes | SQL después |
|---:|---:|---:|---:|---:|
| 1 | 1341.93 | 778.18 | 907 | 458 |
| 2 | 1376.72 | 766.07 | 907 | 458 |
| 3 | 1286.81 | 614.60 | 907 | 458 |
| 4 | 1313.71 | 646.81 | 907 | 458 |
| 5 | 1441.71 | 762.60 | 907 | 458 |

### Página /notifications: preparación de datos, sin render

| Muestra | Antes ms | Después ms | SQL antes | SQL después |
|---:|---:|---:|---:|---:|
| 1 | 1160.55 | 643.21 | 905 | 456 |
| 2 | 1457.98 | 706.17 | 905 | 456 |
| 3 | 1193.27 | 664.46 | 905 | 456 |
| 4 | 1241.31 | 734.75 | 905 | 456 |
| 5 | 1251.81 | 618.86 | 905 | 456 |

### Resumen privado de notificaciones de etiquetadora, sin render

| Muestra | Antes ms | Después ms | SQL antes | SQL después |
|---:|---:|---:|---:|---:|
| 1 | 1277.60 | 603.55 | 905 | 456 |
| 2 | 1230.91 | 631.02 | 905 | 456 |
| 3 | 1259.05 | 653.76 | 905 | 456 |
| 4 | 997.90 | 655.24 | 905 | 456 |
| 5 | 1344.68 | 507.90 | 905 | 456 |

## Conservación y reversión

Se conserva el cambio por la reducción de consultas, descenso de tiempos locales, igualdad del contenido comparado y ausencia de fallos nuevos en las pruebas ejecutadas.

Si una validación funcional detecta discrepancias, la reversión es restaurar únicamente las llamadas previas a availableUnreadNotificationsFor/availableUnreadNotificationsCountFor en los cuatro puntos modificados, ajustar las pruebas y registrar el resultado. No requiere migraciones ni cambios de datos. No revertir la eliminación del sondeo inicial de la entrega 01 ni cambios ajenos.

Sigue pendiente una comprobación de navegador con el mismo usuario: contenido y contador al abrir formulario, lista paginada, lectura y apertura de notificaciones y refresco posterior. El defecto de redirección preexistente requiere atención separada; la reescritura de estados y la resolución de destinos por lotes permanecen fuera del alcance autorizado de bajo riesgo.

