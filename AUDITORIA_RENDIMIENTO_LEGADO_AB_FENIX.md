# Auditoría integral de rendimiento — Legado AB Fénix

Fecha: **30 de octubre de 2026**, zona de referencia America/Mexico_City. Revisión Git: **656dda6**. Entorno auditado: copia local en Windows/Laragon, no producción.

**Resultado:** se confirmaron cuellos de botella de consultas en lavadoras, reportes y notificaciones; una carga global excesiva de imágenes; y procesamiento secundario que puede quedar dentro del tiempo de guardado. La prioridad es corregir estas causas antes de plantear una reescritura de navegación o aumentar infraestructura.

**Alcance de esta entrega:** análisis y mediciones. El único archivo creado es este informe. No se aplicaron optimizaciones, migraciones, cambios de configuración, instalaciones, envíos externos ni escrituras de registros.

## A. Diagnóstico general

### A.1. Problemas principales confirmados

1. **Dashboard global de lavadoras:** mediana de **7,147.97 ms** para preparar datos, sin renderizar Blade. Ejecutó **150 consultas**, incluidas **19** que usan el scope de últimos análisis por componente. Hubo **63 consultas repetidas con el mismo SQL y parámetros** dentro de cada muestra.
2. **Dashboard operativo de lavadoras:** mediana de **6,739.17 ms**, **134 consultas**, **67 repeticiones exactas** y las mismas **19 ejecuciones** del scope costoso.
3. **Notificaciones en todas las páginas autenticadas:** el renderizado de un formulario añadió **456 consultas**. El sondeo JSON de la campana también ejecutó **456 consultas**, con mediana de **469.02 ms**. El problema se encuentra en la validación individual de destinos y en cargar todas las notificaciones antes de seleccionar diez.
4. **Reportes de lavadoras:** mediana de **3,171.31 ms**, **449 consultas** y **133 repeticiones exactas**, antes de renderizar o generar PDF/Excel. Los cálculos de revisiones por línea/componente vuelven a consultar datos.
5. **Frontend global:** los cuatro PNG del logo pesan **9,450,912 bytes**, aproximadamente **9.45 MB decimales**. Sus etiquetas están en el layout autenticado. El layout también incluye Tailwind en tiempo de ejecución, Chart.js, Alpine y SweetAlert desde CDN.
6. **Búsqueda global:** una consulta válida de texto falló con SQLSTATE **42S02**, porque la búsqueda heredada consulta la tabla `analisis`, ausente en esta base local. Es un fallo funcional confirmado, no una medición de una búsqueda completada.
7. **Guardado e IA:** configuración efectiva local `QUEUE_CONNECTION=sync`, indexación histórica automática y embeddings activados. `onQueue()` asigna un nombre de cola, pero no cambia una conexión síncrona a asíncrona. Las latencias reales de guardado no se midieron para conservar datos y evitar llamadas externas.

Estos resultados muestran que el volumen local no necesita ser enorme para reproducir lentitud: hay **1,043 análisis en analisis_componentes**. Pasteurizadoras, central hidráulica y etiquetadoras tienen muy pocos registros o ninguno; sus tiempos no permiten demostrar escalabilidad.

### A.2. Qué se midió y qué no

| Categoría | Evidencia obtenida | Límites |
|---|---|---|
| Preparación de datos | 26 escenarios exitosos de controladores/endpoints de lectura, generalmente tres muestras secuenciales por escenario | Invocación directa, sin servidor HTTP ni middleware; autenticación resuelta en memoria |
| SQL | Cantidad, tiempo acumulado, duplicados exactos, índices y EXPLAIN de tres consultas reales | No se activó general log, slow log ni instrumentación persistente |
| Renderizado Blade | Cinco vistas compiladas y evaluadas en memoria; consultas adicionales, bytes HTML y tiempo | Una muestra por vista; incluye compilación en memoria, no equivale a vistas precacheadas ni a renderizado del navegador |
| Recursos estáticos | Tamaños y dimensiones; dos GET locales a archivos existentes; cabeceras HTTP | Loopback, sin simulación de conexión móvil ni navegador |
| Funcionalidad frontend | Cinco tests existentes aprobados con Node | DOM y Chart.js simulados; no miden LCP, INP, CLS ni memoria real |
| Inicio de sesión | Inspección de validación, rate limit, sesión y redirección | No se enviaron credenciales ni se inició sesión HTTP |
| Guardado/actualización | Inspección de validación, transacciones, archivos, jobs, notificaciones e IA | No se enviaron formularios ni se alteraron registros |
| PDF/Excel e importaciones | Inspección de consultas, exportadores y procesamiento | No se generaron archivos ni notificaciones de reporte |
| Concurrencia y grandes volúmenes | Riesgos identificados y protocolo propuesto | No se generaron registros, copias de tablas ni carga concurrente |
| TTFB de páginas, DOM, usabilidad, LCP, INP, CLS | Sin medición válida en navegador | No hay una distribución de campo ni una sesión de navegador instrumentada |

**No existe en esta auditoría un ranking HTTP completo de páginas.** La clasificación de C ordena el costo medido de preparación de datos. No debe presentarse como TTFB ni como tiempo hasta que una página es utilizable.

### A.3. Metodología y protección del entorno

- Se inspeccionaron las carpetas de aplicación, rutas, configuración, migraciones, vistas, recursos, exportadores, pruebas y documentación. El inventario incluye **42 controladores, 50 archivos de modelos, 59 servicios, 180 vistas y 100 migraciones**; los conteos incluyen subcarpetas.
- Se registraron **287 rutas** mediante la colección de rutas cargada en memoria. Los hallazgos combinan búsquedas estáticas en el conjunto e inspección dirigida de flujos importantes. Esto no implica cobertura dinámica de cada ruta ni revisión manual de cada línea.
- No se encontraron archivos `AGENTS.md` en el repositorio mediante búsqueda incluyendo archivos ocultos, ni en los directorios ascendentes comprobados.
- La terminal aislada falló al iniciar. Las lecturas y pruebas se ejecutaron mediante el mecanismo de escalación autorizado; esto no cambió el alcance de solo análisis.
- La base se verificó como MySQL local. Las consultas de medición se ejecutaron en una conexión con `START TRANSACTION READ ONLY`, `MAX_EXECUTION_TIME=2500` para los bancos Laravel y rollback final. La exploración inicial de metadatos usó un límite de 3000 ms.
- Caché y sesiones se sustituyeron **solo en memoria del proceso de medición** por drivers array; logs se descartaron mediante el canal null y el mailer se dejó en array. No se editó `.env`. Estas condiciones reducen efectos secundarios y cambian el costo respecto a la configuración habitual.
- Se cargó un usuario existente con rol admin únicamente en el proceso CLI y se precargaron permisos. No se creó una sesión real, no se imprimieron sus datos y no se probó autenticación HTTP. Esto favorece una referencia con permisos ya resueltos; faltan otros perfiles.
- Se invocaron únicamente métodos inspeccionados de consulta. No se ejecutaron `createQuick` de lavadoras, guardados, workers, scheduler, cron HTTP, comandos de reset, indexación o exportación.
- La medición no llamó `terminate()`; no se ejecutaron callbacks de finalización de una petición web.
- Para medir Blade se reemplazó el motor por una implementación temporal en memoria basada en `compileString()` y evaluación del PHP compilado. No se generaron archivos de vistas compiladas.
- No se instaló Telescope, Debugbar, Playwright, Lighthouse ni otra dependencia. No se ejecutó PHPUnit porque su arranque y pruebas pueden crear archivos o persistir efectos; se utilizaron solo los tests frontend existentes de lectura.
- El árbol Git estaba limpio al comenzar y siguió limpio después de las mediciones, antes de crear este documento.

Las muestras comparten un proceso ya arrancado por banco, MySQL existente y cachés de sistema no vaciadas. No constituyen mediciones de arranque en frío. La instrumentación de consultas agrega costo. **Tres muestras no bastan para p75, p95 ni garantías de servicio.** Tampoco se sumaron mediciones independientes para afirmar un tiempo total observado.

### A.4. Entorno comprobado

| Elemento | Resultado |
|---|---|
| Framework instalado y lock | Laravel **12.69.2** |
| PHP utilizado en las mediciones válidas | **8.4.12 NTS**, 64 bits |
| Otro PHP disponible | **8.3.33**; cargar vendor/autoload.php falla por requisito efectivo >=8.4.1 |
| Dependencias que elevan el mínimo | Symfony string 8.1.7, clock 8.1.0 y css-selector 8.1.6, entre las comprobadas |
| Requisito declarado del proyecto | composer.json permite `^8.2`; no expresa el mínimo efectivo del lock instalado |
| Servidor local | Apache **2.4.68** con mod_fcgid; configuración y proceso php-cgi apuntan a PHP 8.4.12 |
| MySQL | **8.4.3**, conexión local |
| Entorno y debug | `APP_ENV=local`, `APP_DEBUG=true`; adecuado identificarlo como desarrollo |
| Caché habitual | database |
| Sesiones habituales | file, 120 minutos |
| Colas habituales | sync |
| IA efectiva | habilitada; proveedor Gemini; despacho de planes after_response |
| Históricos IA | auto_index=true, embeddings_enabled=true, cola default |
| Configuración y rutas precacheadas | No en esta copia |
| Vistas compiladas existentes | Hay vistas en storage/framework/views; no se probó su vigencia para todas las fuentes |
| OPcache | Extensión no cargada en PHP CLI 8.4.12 comprobado; sin sonda web que confirme su estado efectivo en CGI |
| Memoria CLI | memory_limit 512M |
| Límites del servidor local | php.ini max_execution_time=36000; FcgidIOTimeout=36000; FcgidMaxProcesses=50 |
| CLI max_execution_time | 0 al inspeccionarlo; no equivale al límite de la petición web |
| Autoload | optimize-autoloader=true en composer.json y fallback manual prepended en bootstrap/app.php |

**No se extrapola ninguna configuración local a producción.** No se comprobó un servidor PHP-FPM: el servidor local observado usa CGI/FastCGI en Windows.

### A.5. Datos locales: conteos exactos frente a estimaciones

| Tabla | COUNT(*) exacto |
|---|---:|
| analisis_componentes | 1,043 |
| analisis_pasteurizadora | 4 |
| analisis_etiquetadora | 0 |
| analisis_central_hidraulica | 0 |
| notifications | 2,572 |
| maintenance_history_chunks | 1,198 |
| elongaciones | 29 |
| cadena_ciclos | 11 |
| lef52124_imports | 22 |

`information_schema.tables.TABLE_ROWS` y las cardinalidades de `SHOW INDEX` son estimaciones del motor. Por ejemplo, TABLE_ROWS indicó 2 análisis de pasteurizadora y 1008 chunks, distintos de COUNT(*). No se usaron esas estimaciones como conteos exactos ni como prueba de cambios de registros.

## B. Tabla de hallazgos priorizados

Leyenda: **M** = medición real local; **E** = evidencia estática o de configuración; **H** = hipótesis de impacto pendiente de prueba. Las mejoras indicadas son recomendaciones **no implementadas y no medidas después de un cambio**. Las prioridades expresan impacto y alcance; los hallazgos relacionados se solapan y sus ahorros no deben sumarse.

| ID / prioridad | Módulo | Archivo y línea aproximada | Problema y evidencia técnica | Impacto | Solución recomendada | Riesgo de implementación | Mejora esperada |
|---|---|---|---|---|---|---|---|
| F01 **Crítico** | Lavadoras, costos, reportes | app/Models/AnalisisLavadora.php:375,463 | **M/E:** scopeUltimosPorComponente usa NOT EXISTS correlacionado, joins y CASE con UPPER/REPLACE/LIKE para comparar códigos; EXPLAIN global recorre 1043 filas de análisis y estima 149 candidatas por búsqueda interna de línea. | Consulta dominante en páginas lentas; costo crece con historial y normalización repetida. | Comparar en staging una selección única por identidad, con ROW_NUMBER u otra estrategia equivalente. Primero preservar exactamente normalización, fechas, nulos, tipo de equipo y desempates; considerar claves normalizadas solo como fase posterior con autorización de esquema. | **Alto:** elegir por MAX(id) cambiaría la regla de fecha y estados vigentes. | Estimada: bajar el costo del cálculo compartido; sin porcentaje garantizado. En global, las 19 consultas del scope consumieron 3389–3799 ms de SQL por muestra. |
| F02 **Crítico** | Dashboards lavadoras | app/Http/Controllers/DashboardController.php:265,467,1469,1512,1530 | **M:** calcularEstadoLavadora se ejecuta desde resumen y detalle; vuelve a resolver nombre, elongación, análisis y planes por línea. Global: 150 SQL, 63 duplicados; operativo: 134 SQL, 67 duplicados. | Preparación de datos de 6.6–7.5 s antes del layout. | Construir un contexto por petición, consultar lotes de líneas/estados/planes/elongaciones y derivar tarjetas del mismo conjunto. Diferenciar líneas activas de las incluidas en ranking. | **Medio/alto:** no mezclar alcance de líneas activas e inactivas ni tipos de equipo. | Estimada: eliminar consultas idénticas y pasar de 19 cálculos SQL costosos a un pequeño número por alcance. Meta a validar, no resultado. |
| F03 **Crítico** | Todas las vistas autenticadas y campana | app/Services/NotificationVisibilityService.php:81,201,208; resources/views/layouts/app.blade.php:935 | **M:** carga todas las notificaciones; filterExistingTargets ejecuta exists/find por destino. En una atribución hubo 423 consultas a analisis_componentes, 21 a planes y 4 a pasteurizadoras para la colección general. Formularios y diagrama añadieron 456 SQL de vista. | Costo transversal en cada navegación; escalamiento por notificaciones del usuario. | Resolver destinos por lotes agrupados por modelo/ID, preservar reglas especiales de elongación y aplicar límites de forma compatible con exclusión de destinos inexistentes. Reutilizar el resultado dentro de la petición. | **Alto:** conservar visibilidad por rol, módulo, área, destinos eliminados y ciclos vigentes. | Estimada: consultas por cantidad de modelos en lugar de una por notificación; la pantalla seguirá mostrando diez ítems válidos. |
| F04 **Alto** | Navegación y notificaciones | resources/views/layouts/app.blade.php:1409–1431; app/Http/Controllers/NotificationController.php:146 | **M/E:** tras construir la campana en Blade, refreshNotifications se llama inmediatamente y cada 30 s. No hay control de petición en curso ni pausa por visibilidad. El endpoint repite 456 SQL. | Repite trabajo inicial y consumo periódico; varias pestañas multiplican solicitudes. | Reutilizar payload inicial, refrescar según necesidad/visibilidad, evitar solapamiento y aplicar backoff en errores. Optimizar F03 antes de reducir intervalos. | **Medio:** acordar frescura; revalidar al volver a una pestaña o tras marcar como leído. | Estimada: eliminar el sondeo inicial redundante y reducir solicitudes inútiles; frescura preservada por eventos y revalidación. |
| F05 **Alto** | Reportes e histórico revisados | app/Http/Controllers/ReporteController.php:537,591,645,651; app/Services/LavadoraRevisionPeriodicityService.php:62,332,472,872 | **M:** reporte 449 SQL, 133 duplicados. Atribución: 135 consultas a componentes, 105 a análisis, 99 a restablecimientos, 76 a líneas. La periodicidad consulta catálogos e historiales por componente/línea. | Mediana 3171 ms solo en datos; costo adicional a PDF/Excel. | Precargar análisis, componentes y resets para todas las líneas del reporte; pasar colecciones agrupadas a funciones de cálculo; reutilizar eventos para ambas ventanas de tendencia. | **Alto:** preservar periodicidad por componente, lado, ubicación y restablecimientos. | Estimada: reducir consultas anidadas manteniendo las mismas métricas; sin prometer un tiempo total. |
| F06 **Alto** | Layout global e identidad visual | resources/views/layouts/app.blade.php:623–645; resources/views/layouts/partials/site-icons.blade.php:2,13 | **M/E:** cuatro PNG de 1254×1254 suman 9450912 bytes; logo1 también se declara como favicon de 32×32. No tienen carga diferida. | Descarga y decodificación desproporcionadas en primera visita; el favicon referencia un archivo de 2.49 MB. | Exportar variantes dimensionadas y comprimidas; mantener los cuatro fotogramas y apariencia si la secuencia es requisito; usar favicon pequeño existente o derivado apropiado. Comparar PNG/WebP/AVIF con revisión visual. | **Bajo/medio:** conservar transparencia, colores y secuencia. No cambiar indicadores operativos. | Estimada: menos bytes y memoria de imagen. Peso actual medido; peso final y LCP pendientes. |
| F07 **Alto** | Costos y dashboard global | app/Services/LavadoraCostAnalyticsService.php:16,310,312; DashboardController.php:287 | **M:** costos tarda 854–876 ms para 20 SQL; la consulta de daños pendientes reutiliza el scope costoso. El dashboard global además construye todo el resumen de costos cuando hay permiso. | Supera por sí solo la referencia de 500 ms de backend; amplía el trabajo del dashboard. | Reutilizar estados vigentes de F01/F02 y separar resumen agregado de detalle de costos. Cargar detalle cuando se solicite; limitar columnas e historial. | **Medio/alto:** montos, cierres y presupuestos deben reconciliar exactamente; preservar permisos de costos. | Estimada: evitar repetir cálculo de pendientes y datos detallados al abrir el dashboard. |
| F08 **Alto** | Guardado, indexación histórica, WhatsApp | config/queue.php:16; config/maintenance_ai.php:97; app/Services/Maintenance/MaintenanceHistoryIndexer.php:210,264,506; AnalisisLavadoraController.php:941 | **E/H:** conexión sync, auto_index y embeddings activos. IndexMaintenanceHistoryRecord y SendLavadoraDamageWhatsApp usan dispatch/onQueue. onQueue no desacopla el trabajo del driver sync. | Indexación y posibles llamadas externas dentro de la petición; latencia variable al crear/actualizar análisis, costos, eventos o planes. | Usar conexión persistente y worker supervisado después de medir en staging; asegurar afterCommit, idempotencia, reintentos y trazabilidad. Revisar cuántos eventos saved dispara cada guardado. | **Alto:** consistencia eventual, errores de entrega y duplicación de trabajos. | Estimada: retirar trabajo secundario del tiempo de respuesta. Guardados no medidos; no se cuantifican segundos ahorrados. |
| F09 **Alto** | Elongaciones | app/Http/Controllers/ElongacionController.php:251,295–304,690; app/Services/WhatsAppService.php:28–31 | **E/H:** enviarNotificacionWhatsApp se llama dentro de DB::transaction; el cliente externo tiene timeout y reintentos. | Puede mantener una transacción abierta mientras espera red y elevar contención al guardar alertas. | Registrar intención de envío y despachar después del commit mediante cola real; conservar condiciones de compra/cambio y entrega idempotente. | **Alto:** no perder avisos, no enviar por un registro revertido ni duplicar alertas. | Estimada: acortar tiempo de transacción; medir duración y lock waits con proveedor simulado. No se enviaron mensajes. |
| F10 **Alto** | Búsqueda global | app/Http/Controllers/GlobalSearchController.php:49,70,1549–1557; app/Models/Analisis.php | **M:** q=guia dispara SQLSTATE 42S02 al consultar analisis, ausente localmente; diez consultas exitosas antecedieron al fallo. | La solicitud completa fracasa aun cuando módulos anteriores podían producir resultados; reintentos no corrigen el fallo. | Verificar mapeo del modelo heredado contra esquema y despliegue esperados; adaptar integración al dominio real o degradar esa fuente con manejo explícito. No crear una tabla vacía para ocultar el problema. | **Medio/alto:** no omitir información histórica ni ampliar permisos al buscar. | Estimada: restaurar búsqueda exitosa. No se declara un tiempo de búsqueda exitoso hasta corregir y medir. |
| F11 **Alto** | Compatibilidad y despliegue | composer.json:8; composer.lock; vendor/composer/platform_check.php:7; bootstrap/app.php:3 | **M/E:** el proyecto permite PHP ^8.2, pero el vendor/lock exige >=8.4.1. PHP 8.3.33 falló al cargar autoload; PHP 8.4.12 funciona. | Un proceso o despliegue con PHP permitido por composer.json puede responder 500 o no iniciar; no es la causa de tiempos altos en el banco 8.4. | Alinear requisito documentado, PHP CLI/CGI/workers y lock; verificar plataforma en el pipeline. Evaluar compatibilidad del lock antes de cambiar dependencias. | **Medio:** no resolver con actualización masiva ni desactivando platform_check. | Estimada: eliminar fallos de arranque por plataforma; no se atribuye mejora de latencia. |
| F12 **Medio** | Consultas y almacenamiento | Índices reales de analisis_componentes; migraciones de la tabla | **M:** SHOW INDEX encontró tres índices no únicos equivalentes (linea_id, fecha_analisis): idx_analisis_linea_fecha, idx_analisis_componentes_linea_fecha e idx_analisis_componentes. | Espacio y mantenimiento de índices redundantes; no resuelve por sí mismo el scope F01. | Verificar definición completa, visibilidad, dependencias FK y uso; comparar escritura en staging y conservar el índice necesario. Conciliar esquema real con migraciones. No ejecutar DROP en esta etapa. | **Medio/alto:** regresión de planes, DDL y dependencias. | Estimada: reducir mantenimiento redundante de escritura; magnitud pendiente. No se propone añadir otro índice equivalente. |
| F13 **Medio** | Listado/diagrama lavadora | app/Http/Controllers/AnalisisLavadoraController.php:59,99,103,132,162 | **M/E:** listado, estadísticas y monitor hacen tres selecciones con ultimosPorComponente; hubo 22 SQL y nueve duplicados exactos sin filtros. Estadísticas excluyen estado/fecha intencionalmente. | Trabajo repetido al filtrar o abrir el diagrama; el alcance de cada conjunto no siempre es idéntico. | Consultar un conjunto base cuando los alcances permitan derivarlo y separar agregados de la página visible. Mantener estadísticas sin los filtros que hoy excluyen. | **Medio/alto:** no calcular estadísticas solo con una página ni aplicarles filtros nuevos. | Estimada: evitar repeticiones para filtros equivalentes; base local actual 195.53 ms sin layout. |
| F14 **Medio** | Listados e historiales | AnalisisLavadoraController.php:99,1631; AnalisisPasteurizadoraController.php:482,1381; AnalisisEtiquetadoraController.php:97; AnalisisPasteurizadoraCentralHidraulicaController.php:50 | **E/H:** varios listados e historiales usan get sin paginación SQL; se filtra/agrupa en PHP. El conjunto de estado vigente puede estar acotado por catálogo, pero el historial no. | Memoria, HTML y consultas crecen con registros; no se midió con miles de inspecciones por equipo. | Paginar historiales por contexto, mantener agregados separados y preservar acceso a todas las páginas. Limitar columnas y servir evidencias solo al abrir detalles. | **Medio:** no truncar ciclos, cobertura por lado ni estadísticas globales. | Estimada: memoria y transferencia más estables con volumen; sin porcentaje probado. |
| F15 **Medio** | AJAX y formularios pasteurizadora | AnalisisPasteurizadoraController.php:1650,2235–2292; app/Models/AnalisisPasteurizadora.php:697,719,740,914 | **M/E:** contexto de revisión de un módulo/componente ejecutó 73 consultas; pendientes ejecutó 20. Varios helpers vuelven a calcular estado, lados y componentes. | Aunque el contexto local tardó 85.56 ms de mediana, el número de viajes SQL escala mal con latencia de red/base. | Cargar registros del contexto una vez y derivar disponibilidad, bloqueados, siguiente revisión y lados de una misma colección. Reutilizar resúmenes de ciclo existentes. | **Alto:** preservar tipo quick/normal, área, nivel, lado y numeración global. | Estimada: reducir consultas de una selección; medir con datos representativos, no prometer mejora proporcional a 73. |
| F16 **Medio** | Formularios rápidos lavadora | app/Http/Controllers/AnalisisLavadoraController.php:678,704,742 | **E:** abrir createQuick puede crear componentes o variantes por línea; no es una operación de lectura pura. También registra múltiples logs. | Coste y contención al abrir formulario; un GET puede realizar trabajo de escritura. | Abrir el formulario desde catálogo resuelto sin escribir y mover creación necesaria a un flujo de guardado validado. Medir por separado catálogo y evidencia. | **Alto:** conservar compatibilidad de códigos y componentes históricos. | Estimada: apertura predecible y menos escrituras; este GET se excluyó de mediciones por sus efectos. |
| F17 **Medio** | Filtros mensuales y búsqueda | AnalisisLavadoraController.php:94; AnalisisPasteurizadoraController.php:473; AnalisisEtiquetadoraController.php:81; GlobalSearchController.php:1557 | **M/E:** filtros MONTH/YEAR sobre fecha; EXPLAIN usó solo ocho bytes del índice (prefijo linea_id), estimando 71 filas. Búsqueda usa LIKE con comodín inicial. | Filtros de fecha no usan plenamente rango por índice existente; LIKE amplio pierde selectividad con volumen. | Transformar meses a intervalos semiabiertos sobre fecha; medir selectividad de búsquedas reales antes de decidir búsqueda de texto o índices. | **Medio:** zona horaria, límites inclusivos y semántica de coincidencia. | Estimada: aprovechar índice existente para fechas. No se propone un índice de texto sin evidencia. |
| F18 **Medio** | Dashboards e históricos | DashboardController.php:276,1760,2083; PasteurizadoraGlobal:348; TendenciaDanosService.php:99 | **E/H:** carga de históricos completos y elongaciones de todos los ciclos antes de filtrar ciclo actual; tendencias hacen recorridos por ventanas/fechas en PHP. | CPU e hidratación crecen aun cuando el usuario ve un resumen. | Separar histórico de estado actual; filtrar ciclos en SQL cuando sea equivalente; reutilizar eventos base y limitar detalle visual sin perder consultas históricas. | **Alto:** conservar datos necesarios para comparación temporal y resets. | Estimada: menos filas hidratadas y recorridos; no eliminar histórico ni sustituirlo por el último ID. |
| F19 **Medio** | Recursos globales y navegación | resources/views/layouts/app.blade.php:10–12,1109–1110; resources/views/layouts/guest.blade.php:13,19,21,28; vite.config.js | **E:** scripts clásicos de Tailwind y Chart.js en head sin defer; librerías globales en todas las vistas. Guest combina Tailwind CDN con CSS Vite. El layout autenticado no usa los entrypoints Vite observados. | Dependencia de red externa y trabajo inicial compartido incluso en formularios sin gráficas. | Servir CSS compilado y versionado; cargar Chart.js/plugins solo donde hacen falta; pasar JS por build con orden de dependencias explícito. Reutilizar paquetes ya instalados. | **Medio:** estilos dinámicos de Blade requieren safelist; carga defer mal ordenada rompe gráficas. | Estimada: menos bloqueo y JavaScript en vistas simples; falta waterfall del navegador para cuantificar. |
| F20 **Medio** | Servidor y caché estática | public/.htaccess; configuración Apache local; public/build/assets | **M:** GET CSS con Accept-Encoding:gzip devolvió 130501 bytes y ningún Content-Encoding, Cache-Control ni Expires. Sí hay ETag y Last-Modified. | No hay frescura explícita de largo plazo para assets con hash; CSS viaja sin gzip en el endpoint comprobado. | Configurar compresión de texto y caché prolongada para archivos con hash; política separada para recursos sin hash y páginas privadas. | **Medio:** no cachear respuestas privadas ni servir versiones antiguas de JS sin versionado. | Potencial medido en memoria: ese CSS comprime a 18591 bytes con gzip nivel 9; no es una medición del servidor después de un cambio. |
| F21 **Medio** | Vistas grandes y gráficas | resources/views/dashboard_lavadora.blade.php:3644,4059,4069,6830; dashboard_pasteurizadora.blade.php | **M/E:** fuente de dashboard lavadora 263445 bytes; HTML generado 501304 bytes. Inicializa numerosas tarjetas y gráficas y registra temporizadores; no se midió CPU de navegador. | Más parseo, trabajo inicial y contenido inline no reutilizable entre documentos. | Extraer JS/CSS a archivos con hash y cargar módulos/gráficas cuando sean visibles; actualizar instancias existentes cuando proceda. Medir antes el hilo principal. | **Medio:** conservar orden de inicialización y payload/indicadores. | Estimada: navegación con menos parseo repetido y mejor caché; INP/LCP aún desconocidos. |
| F22 **Medio** | Navegación y filtros | resources/views/layouts/app.blade.php:1575–1581; enlaces de navegación | **E:** los filtros globales usan window.location.assign y los enlaces cambian de documento. Se reconstruyen layout, campana, estilos y scripts inline. | Cada cambio de filtro paga el backend y render compartidos nuevamente. | Primero resolver F01–F05; después evaluar actualización parcial de tabla/resumen con estado en URL, cancelación y manejo de errores. | **Medio/alto:** CSRF, permisos, back/forward, foco y recarga de indicadores deben conservarse. | Estimada: evitar reconstrucción completa para filtros; no se requiere convertir todo a SPA. |
| F23 **Medio** | Inicialización 52-12-4 | resources/views/analisis-52-12-4/index.blade.php:936–966 | **E/H:** periodos se consulta antes de datos; la llamada a datos ocurre en finally. loadData cancela y descarta respuestas viejas, pero loadPeriods usa un flag y no cancela la solicitud al cambiar rápido de línea. | Una cascada añade un viaje de red; cambio rápido puede reutilizar límites de fecha de la línea anterior. | Devolver contexto inicial con datos o resolver en paralelo si son independientes; asociar periodos a línea/requestId y descartar resultados obsoletos. | **Medio:** no desalinear línea, fecha y análisis mostrado. | Estimada: quitar espera secuencial y errores por respuestas viejas; no se afirmó duplicación de requests sin traza. |
| F24 **Medio** | Evidencias y altas/ediciones | app/Services/ImageEvidenceOptimizer.php:20,56; public/js/evidence-image-compression.js; AnalisisLavadoraController.php:974–986 | **E/H:** hay compresión cliente y servidor; backend redimensiona/recodifica sincrónicamente y puede copiar a storage. Luego un análisis puede actualizarse y volver a indexarse. | CPU, memoria y E/S variables al subir varias fotos; no se midieron uploads. | Medir número de imágenes, dimensiones decodificadas y duplicación física; conservar límites y miniaturas; evaluar procesado diferido solo con estado de evidencia explícito. Agrupar reindexación redundante del mismo registro. | **Medio/alto:** conservar fotos, orientación, calidad legible y disponibilidad inmediata requerida. | Estimada: reducir trabajo repetido; ya existe optimización, no se recomienda reemplazarla sin comparación. |
| F25 **Medio** | Exportaciones y reportes | app/Exports/AnalisisPasteurizadoraExport.php:10; ReporteLavadoraGeneralExcel.php:37; ReporteController.php:2454,2502; app/Support/DompdfFactory.php:11 | **E/H:** exportadores basados en colecciones/arrays completos y generación en petición; Dompdf usa directorios temporales/fuentes. No se invocaron para no crear archivos. | Picos de memoria y ocupación de PHP con reportes grandes; tiempo de reporte no medido. | Separar preparación de datos y generación, usar consulta/chunks donde el formato lo permita y cola real para exportaciones grandes; descarga autorizada al finalizar. | **Alto:** conservar formato, totales, evidencias, trazabilidad y acceso a descargas. | Estimada: menos memoria/presión de workers; objetivo distinto del guardado sencillo <1 s. |
| F26 **Medio** | Colas, cron y reintentos | config/queue.php:45; app/Jobs/IndexMaintenanceHistoryRecord.php:33; CronQueueController.php:24–47 | **E/H:** retry_after de database=90 s; job de indexación timeout=120 s; lock cron=55 s y timeout CLI cron=45 s. Actualmente se usa sync. | Al migrar a cola real puede haber reintentos antes de finalizar un job; el cron HTTP no garantiza exclusión durante tareas mayores al lock. | Alinear timeout real de jobs, proveedor, worker, retry_after y lease; supervisar worker fuera de HTTP. Verificar semántica de max-time y soporte de timeout del SO. | **Alto:** duplicar llamadas externas o planes; no basta cambiar QUEUE_CONNECTION. | Estimada: evitar procesamiento duplicado y reducir ocupación web; riesgo latente, no incidente observado. |
| F27 **Medio** | Configuración de despliegue | bootstrap/cache; config/app.php; config/logging.php; php.ini externo | **M/E:** config/rutas no precacheadas localmente; OPcache ausente en CLI; entorno local con debug y nivel debug; autoload fallback añadido antes del estándar. | Arranque y compilación repetidos; configuración de desarrollo no demuestra un defecto en producción. | Auditar SAPI web y pipeline de producción; comprobar Composer y autoload estándar; medir OPcache y cachés en staging; conservar logging de errores y auditoría. | **Medio:** invalidación de caché al desplegar; no quitar fallback hasta verificar autoload. | Estimada: menos costo fijo por petición. Bootstrap aislado observado 339.55 ms una vez, sin atribuirlo todo a un solo factor. |
| F28 **Medio** | Formularios y duplicación | resources/views/lavadora/analisis-lavadora/create-quick.blade.php:495; create.blade.php; pasteurizadora/analisis-pasteurizadora/create-quick.blade.php:733–755; ElongacionController.php:564 | **E/H:** quick pasteurizadora deshabilita botón; elongaciones consume token de sesión. En lavadora quick se revisan fotos al submit pero no se encontró una protección equivalente completa frente a reenvío. | Repetir un POST puede repetir escrituras, indexación o notificaciones según flujo; no se probó doble envío. | Uniformar bloqueo de envío con recuperación en error y verificar idempotencia de la operación en servidor. Conservar la protección ya existente en elongaciones. | **Medio:** no bloquear reintentos legítimos ni accesibilidad. | Estimada: prevenir trabajo duplicado; no afirmar registros duplicados observados. |
| F29 **Medio** | Chat e IA | AssistantChatController.php:126–145; app/Services/Maintenance/OperationsAssistantService.php:235; AssistantKnowledgeSearchService.php:195,266 | **E/H:** el controlador carga todo el historial de conversación antes de generar respuesta; recuperación documental y embeddings trabajan dentro del flujo de respuesta. Hay límites de candidatos para chunks. | Mayor CPU, memoria y latencia con conversaciones largas; costo externo variable. | Acotar contexto enviado conservando historial almacenado, paginar presentación y medir por etapa recuperación/embedding/proveedor. Reusar embeddings de consulta cuando sea correcto y permitido por permisos. | **Alto:** grounding, contexto técnico, fuentes y calidad deben evaluarse; no bajar límites a ciegas. | Estimada: estabilizar latencia de conversaciones largas; no hubo llamadas al proveedor ni costos medidos. |
| F30 **Bajo** | Observabilidad y administración | AiObservabilityController.php:151–159,252–256; AdminUserController.php:49; ControlGastosController.php:107; AlertRecipientController.php:19 | **M/E:** observabilidad ejecutó 44 consultas, con mediana 59.59 ms; varias sumas/count se hacen por separado. Algunos catálogos administrativos se cargan completos. | Secundario con los volúmenes actuales; puede crecer con logs/usuarios/catálogo. | Combinar agregados equivalentes y paginar catálogos cuando el volumen lo justifique; mantener opciones útiles de formularios con búsqueda. | **Bajo/medio:** no cambiar filtros de métricas ni su base temporal. | Estimada: menos consultas de agregación; no merece adelantarse a F01–F09. |
| F31 **Bajo** | Composer de vistas y permisos | app/Providers/AppServiceProvider.php:52–69; resources/views/layouts/app.blade.php:657 | **E/H:** composer('*') recalcula datos comunes para cada vista/partial. Relaciones Spatie ya cargadas pueden reutilizarse; no se probó un N+1 propio de este composer. | CPU repetida y acoplamiento entre parciales; impacto independiente no aislado. | Calcular contexto de presentación una vez por petición y compartirlo solo donde se necesita. Mantener middleware y controles de permisos. | **Medio:** datos de permisos no deben compartirse entre usuarios o sesiones. | Estimada: reducir cálculos repetidos; magnitud desconocida. |
| F32 **Bajo** | Animación de diagramas | public/js/diagramas-lavadoras.js:30,55,127; public/js/diagram-animator/chain-animator.js:298–324 | **E/H:** monitor usa un intervalo por diagrama y sí limpia el anterior al recalcular; no pausa por visibilidad. El animador Canvas de ejemplo vuelve a dibujar aun pausado y no guarda el requestAnimationFrame para cancelarlo. | Consumo evitable fuera de pantalla; no se midió fuga de memoria. Canvas está en ejemplos/prueba, no en los diagramas SVG principales. | Pausar trabajo visual cuando corresponda y restaurarlo con el mismo estado; diseñar cleanup si se introduce navegación parcial. En Canvas, separar fondo estático y bucle activo. | **Medio:** preservar alternancia de colores, geometría y reanudación. | Estimada: menos trabajo de animación ocioso. Con navegación de documento completo no se afirmó una fuga persistente. |
| F33 **Medio** | Logs de captura | app/Http/Controllers/AnalisisLavadoraController.php:687,785,832,924,947; config/logging.php:61 | **E/H:** múltiples info por captura y logging de payload; canal single disponible y nivel local debug. El tamaño del log y su latencia de E/S no se perfilaron. | Escrituras y volumen evitables; posible inclusión de datos que no se necesitan para diagnóstico. | Conservar auditoría de negocio y errores; reducir payload y ruido, usar contexto mínimo y rotación; medir E/S con carga de captura representativa. | **Medio:** no perder evidencia de acciones o diagnóstico de fallos. | Estimada: menos E/S y crecimiento de logs; sin ahorro cuantificado. |

### B.1. Consultas y EXPLAIN: por qué no basta añadir índices

Se utilizó el SQL que genera Eloquent, con sus bindings, dentro de la transacción de lectura. **No se ejecutó EXPLAIN ANALYZE**, para no añadir ejecuciones innecesarias del SQL costoso.

| Consulta examinada | Plan observado | Interpretación |
|---|---|---|
| ultimosPorComponente + ordenVigente, todas las líneas | Tabla exterior analisis_componentes: ALL, rows=1043, Using where; Using filesort. Subconsulta actual: ALL, rows=1043. mas_reciente: ref por idx_analisis_linea_fecha, rows=149; Not exists. | El scope aplica normalización y comparación correlacionada. Hay índice utilizable por línea, pero no elimina el trabajo repetido de comparar identidades. |
| Mismo scope, filtro línea L-04 | Exterior ref por idx_analisis_linea_fecha, key_len=8, rows=71. Actual eq_ref por PK en subconsulta dependiente; mas_reciente ref con rows=149. Filesort exterior. | Acotar línea reduce candidatas exteriores; repetirlo por línea y sección sigue acumulando costo. |
| Análisis de L-04 por mes/año | ref por idx_analisis_linea_fecha, key_len=8, rows=71; Using index condition; Using where. | Se aprovecha linea_id; el mes mediante función no constituye el rango directo de fecha que se busca. |

Los valores `rows` y `filtered` de EXPLAIN son estimaciones, no filas examinadas reales ni milisegundos. `Using filesort` indica ordenamiento adicional y no demuestra por sí solo escritura temporal en disco. Interpretación basada en el [manual oficial de EXPLAIN de MySQL 8.4](https://dev.mysql.com/doc/refman/8.4/en/explain-output.html).

Índices reales relevantes:

- `analisis_componentes`: PK id; tres copias de (linea_id, fecha_analisis); índices de FK de componente, categoría, número y usuario; índices de estado_correccion y fecha_correccion.
- Cardinalidad estimada de linea_id: **7**, frente a **1043** filas; una línea por sí sola no es altamente selectiva. Cardinalidad estimada del prefijo completo línea/fecha: **174**.
- `componentes`: PK id e índice único de codigo. La normalización mediante funciones y comodines de F01 no se beneficia como una igualdad directa de codigo.
- `notifications`: PK id e índice (notifiable_type, notifiable_id). No se observó un compuesto con read_at/created_at, pero el costo dominante medido es consultar cada destino. **No se recomienda crear ese índice antes de resolver y volver a medir F03.**
- `maintenance_history_chunks`: único (module, source_type, source_id, chunk_index), índices (module, source_date), (module, linea_id, componente_id), además de FKs. La migración abierta de 2026_10_28 define estos accesos; no hay evidencia para añadir otro índice de module.
- `elongaciones`: índices por línea, (linea, created_at), ciclo y linea_id. Con 29 registros locales no se justifica añadir otro por una hipótesis de escala.

**Secuencia recomendada para cualquier cambio de índice:** medir distribución y consultas reales; eliminar consultas repetidas; probar reescritura equivalente; comprobar plan y tiempos; medir inserción/actualización; solo entonces proponer DDL con autorización y estrategia de reversión. No se aplicó ningún DDL.

### B.2. Semántica que debe conservarse al optimizar estados

El mayor riesgo está en acelerar la selección de últimos análisis cambiando inadvertidamente el resultado. La identidad actual incluye:

- Línea y tipo de equipo, incluyendo la compatibilidad con tipo_equipo nulo.
- Código base normalizado con alias; no necesariamente el componente_id físico.
- Reductor y lado, con el tratamiento actual de vacío y NULL.
- Orden por COALESCE(fecha_analisis, created_at, fecha de respaldo) y desempate por id.
- Estado original y estado operativo posterior a corrección/cambio.
- Alcance de línea activa para algunas tarjetas y de todas las líneas para otros rankings.

También deben conservarse: reseteos por periodicidad, numeración global de pasteurizadoras, módulos excluidos de ciertos componentes, nivel/lado/área, registros quick/normal, ciclos activos de cadenas, umbrales de compra/cambio y visibilidad por rol. No se recomienda reemplazar estas reglas por un simple MAX(id) ni archivar/eliminar históricos para ocultar el costo.

### B.3. Navegación, formularios y diagramas

**Navegación actual.** Los enlaces y filtros producen navegación de documento completo. Una página reutiliza archivos cacheables según el navegador, pero reconstruye HTML, layout, datos de campana y scripts inline. No se encontraron mecanismos de navegación parcial en el layout inspeccionado. Las rutas de compatibilidad que llaman otro método del controlador no añaden por sí mismas una redirección HTTP; el redirect de /lavadoras/diagramas a una familia concreta y la derivación de técnicos desde /dashboard sí añaden saltos cuando se usan.

**Formularios normales.** Preparar el alta de lavadora tardó 3.13–6.56 ms y la de pasteurizadora 2.55–3.68 ms sin selecciones, excluyendo el layout. En el render medido, esos mismos formularios añadieron 456 consultas por la campana. Esto da una causa concreta de lentitud compartida sin culpar a listas estáticas pequeñas. Con selección de módulo/componente, el contexto AJAX de pasteurizadora requiere 73 consultas; debe optimizarse por separado.

**Ediciones.** Se midieron altas y ediciones de consulta sin guardar. Lavadora carga relaciones y auditoría de cambios de fecha; pasteurizadora consulta usuario y líneas. No se recomienda retirar esas relaciones cuando sostienen la trazabilidad. Las muestras de edición dependen de los registros locales disponibles; falta una edición con muchas evidencias y cambios de fecha.

**Validación y envío.** El rate limit de login y las validaciones de formularios se conservaron. El tiempo de guardar no se midió: crear o editar puede ejecutar model events, notificaciones, indexación, costos y archivos, incluso si después se revierte una transacción. Un rollback no revierte llamadas externas ni archivos; por eso no se usó como justificación para probar POST reales.

**Diagramas principales.** Las lavadoras principales usan Blade/SVG, imágenes y el monitor de public/js/diagramas-lavadoras.js. El ejemplo Canvas es otro recorrido. No se atribuye su bucle a todos los diagramas. El monitor principal ya evita intervalos duplicados al recalcular colores y delega eventos de tooltip. Sus reglas deben preservarse. La memoria de navegador y el tiempo de actualización de indicadores después de recibir datos quedan pendientes de una sesión de DevTools.

**Oportunidades de caché.** Primero reutilizar datos dentro de una petición. Una caché entre peticiones requiere claves por usuario/perfil cuando haya información privada, módulo, área, línea y filtros, más invalidación tras altas, correcciones, resets y cambios de ciclo. Un TTL arbitrario para estados de mantenimiento puede mostrar indicadores obsoletos. No se implementó ni se recomienda cachear HTML privado indiscriminadamente.

### B.4. Recursos frontend y servidor

| Recurso | Bytes en disco | Dimensiones / observación |
|---|---:|---|
| logo1.png | 2,488,201 | 1254×1254; también favicon |
| logo2.png | 2,508,543 | 1254×1254 |
| logo3.png | 2,029,677 | 1254×1254 |
| logo4.png | 2,424,491 | 1254×1254 |
| fondo.png | 2,466,496 | 1376×768; login/welcome |
| icono-maquina-cover.png | 2,041,867 | 1774×887; tarjeta de módulo |
| icono-pas-cover.png | 1,794,776 | 1774×887; tarjeta de módulo |
| build/assets/app-BXS-Op9n.js | 81,848 | Gzip nivel 9 en memoria: 30,512 bytes |
| build/assets/app-By11peDe.css | 130,501 | Gzip nivel 9 en memoria: 18,591 bytes |

El paquete Vite observado es pequeño comparado con imágenes y HTML; **no representa todo el JavaScript que carga la aplicación**, porque varias librerías se sirven desde CDN y hay scripts inline. No se midieron tamaños remotos de CDN ni se generó un nuevo build. Las imágenes más pesadas encontradas incluyen icono-maquina.png de 5,216,230 bytes; su mera existencia no demuestra descarga en todas las páginas. La evidencia transversal de descarga corresponde a los cuatro logos referenciados por el layout, pendiente de confirmar caché/waterfall reales.

GET estáticos realizados sobre el virtual host local, resuelto explícitamente a 127.0.0.1:

| Archivo | HTTP | TTFB local | Total local | Descarga |
|---|---:|---:|---:|---:|
| CSS con hash | 200 | 1.995 ms | 2.220 ms | 130,501 bytes |
| logo-wing-fire.png | 200 | 4.932 ms | 10.400 ms | 1,794,003 bytes |

Ambas respuestas tienen Content-Length, ETag y Last-Modified, sin Cache-Control/Expires explícitos. El CSS no respondió con Content-Encoding aunque se solicitó gzip. **Estos TTFB son exclusivamente de archivos estáticos en loopback**; no describen TTFB de Laravel ni una conexión real de usuario. El logo-wing-fire se eligió para comprobar entrega de PNG, no como prueba de que sea el logo global vigente.

Para la configuración de producción, la [documentación de despliegue de Laravel 12](https://laravel.com/docs/12.x/deployment#optimization) contempla caché de configuración/rutas/vistas y exige revisar uso de env fuera de configuración. Son recomendaciones para un despliegue validado; no se ejecutó optimize, config:cache, route:cache, view:cache ni composer dump-autoload. La [documentación de OPcache](https://www.php.net/manual/en/book.opcache.php) sustenta evaluar caché de bytecode en el SAPI que realmente atiende tráfico. No se recomienda encender depuración o instalar un profiler en producción para esta auditoría.

### B.5. Áreas revisadas sin evidencia de un cuello crítico independiente

- Autenticación: reglas de LoginRequest, rate limit, sesión regenerada y redirecciones inspeccionados. No hay una medición que justifique reducir el costo del hash o relajar controles.
- Autorización: middleware de cuenta activa, perfil técnico, acceso a pasteurizadora, permisos personalizados y API key de Power BI revisados. Se conservan como requisitos; su costo real HTTP no se aisló.
- Modelos: se observaron relaciones eager loaded en múltiples listados; el principal N+1 confirmado está en validación de destinos de notificaciones y consultas por contexto, no en toda relación Eloquent.
- Planes de acción: usan paginación SQL de 15 y relaciones de trazabilidad. Datos del listado de lavadora: mediana 33.97 ms; no se identificó como prioridad frente a dashboards/notificaciones.
- 52-12-4: índice con paginación de importaciones; datos y períodos usan endpoints separados. loadData ya incluye AbortController y descarte por requestId; conservar estas protecciones.
- Historial de elongaciones: listado paginado y carga de cadenaCiclo. Comparación de ciclos tiene un patrón de consulta de última medición/count por ciclo; con 11 ciclos totales el escenario local fue rápido y no justifica priorizarlo sobre los cuellos medidos.
- Fotos: ya existen compresión cliente y optimizador servidor, con orientación y liberación de recursos GD. No se declara que todas las fotos se guardan sin optimizar.
- Scheduler: comandos diarios tienen withoutOverlapping; no se ejecutaron. Falta comprobar cron real, duración de comandos, coincidencia de horarios y carga de notificaciones.
- API Power BI: usa API key; servicio revisado para carga de imports/items. No se ejercitó HTTP ni se imprimieron secretos.
- Observabilidad IA: existen muestras limitadas y pruebas funcionales. El hecho de que haya telemetría de IA no sustituye métricas de navegación SQL/Blade/browser.

## C. Ranking de módulos por tiempo medido

### C.1. Preparación de datos: tres muestras por escenario exitoso

Unidad: **milisegundos**. La mediana se calcula con las tres muestras. SQL ms es la mediana del tiempo SQL acumulado informado por Laravel, **incluido** en el tiempo del escenario. Los tiempos no incluyen el bootstrap previo del proceso, autenticación HTTP, middleware, render Blade ni recursos de navegador.

| Orden | Escenario / ruta asociada | Muestra 1 | Muestra 2 | Muestra 3 | Mediana | Consultas SQL | Mediana SQL ms |
|---:|---|---:|---:|---:|---:|---:|---:|
| 1 | Dashboard global lavadoras — /dashboard/lavadoras | 6679.58 | 7147.97 | 7512.83 | **7147.97** | 150 | 3906.76 |
| 2 | Dashboard operativo lavadora — /dashboard/lavadora/operativo | 6739.17 | 6609.23 | 7081.75 | **6739.17** | 134 | 3609.16 |
| 3 | Reportes lavadoras — /reportes, tipo=lavadoras | 3403.20 | 3171.31 | 3130.91 | **3171.31** | 449 | 1192.31 |
| 4 | Selección de módulos — /dashboard | 1533.79 | 1446.87 | 1485.77 | **1485.77** | 61–63 | 773.25 |
| 5 | Dashboard global etiquetadoras — /dashboard/etiquetadoras | 1029.17 | 1013.56 | 993.92 | **1013.56** | 465 | 281.83 |
| 6 | Costos lavadora — /lavadora/costos | 861.59 | 853.75 | 876.17 | **861.59** | 20 | 816.74 |
| 7 | Sondeo campana — /notifications/unread-count | 446.08 | 506.15 | 469.02 | **469.02** | 456 | 285.40 |
| 8 | Análisis lavadora — /analisis-lavadora, línea predeterminada | 195.09 | 195.53 | 217.13 | **195.53** | 22 | 83.81 |
| 9 | Histórico revisados — /historico-revisados | 179.24 | 177.10 | 174.56 | **177.10** | 43 | 41.78 |
| 10 | Dashboard global pasteurizadoras — /dashboard/pasteurizadoras | 100.26 | 95.02 | 96.18 | **96.18** | 11 | 9.94 |
| 11 | AJAX contexto revisión pasteurizadora | 102.01 | 85.56 | 85.47 | **85.56** | 73 | 53.47 |
| 12 | Historial lavadora — contexto de componente/reductor existente | 117.19 | 81.20 | 79.46 | **81.20** | 4 | 8.02 |
| 13 | Análisis pasteurizadora — línea predeterminada | 105.74 | 72.35 | 77.10 | **77.10** | 6 | 5.29 |
| 14 | Observabilidad IA — /admin/observabilidad-ia | 93.11 | 55.47 | 59.59 | **59.59** | 44 | 38.80 |
| 15 | Planes lavadora — /plan-accion/lavadora | 54.17 | 32.19 | 33.97 | **33.97** | 16 | 18.34 |
| 16 | AJAX pendientes pasteurizadora | 46.10 | 25.91 | 24.54 | **25.91** | 20 | 15.08 |
| 17 | Datos 52-12-4 — /52-12-4/datos, L-04 | 42.03 | 21.89 | 23.58 | **23.58** | 9 | 7.39 |
| 18 | Edición lavadora — registro existente | 84.27 | 14.59 | 14.84 | **14.84** | 6–8 | 8.36 |
| 19 | Elongaciones — /elongaciones | 25.12 | 13.93 | 13.92 | **13.93** | 9 | 6.28 |
| 20 | Comparación ciclos — L-04 | 11.86 | 8.51 | 8.85 | **8.85** | 8 | 5.55 |
| 21 | Historial pasteurizadora | 10.00 | 6.65 | 8.45 | **8.45** | 4 | 4.03 |
| 22 | Edición pasteurizadora — registro existente | 86.54 | 4.95 | 7.07 | **7.07** | 3–5 | 3.39 |
| 23 | Índice 52-12-4 — /52-12-4 | 9.88 | 5.40 | 6.55 | **6.55** | 5 | 3.53 |
| 24 | Períodos 52-12-4 — L-04 | 4.97 | 4.66 | 3.66 | **4.66** | 2 | 2.22 |
| 25 | Alta normal lavadora — L-04, sin selecciones | 6.56 | 4.40 | 3.13 | **4.40** | 2 | 2.86 |
| 26 | Alta normal pasteurizadora — P-03, sin selecciones | 3.68 | 2.63 | 2.55 | **2.63** | 1 | 0.66 |

Detalles para interpretar el ranking:

- Dashboard de etiquetadoras invoca explícitamente el resumen de notificaciones en DashboardController:1399. Sus 465 consultas **no implican que existan cientos de inspecciones de etiquetadora**; la tabla local está vacía.
- Contexto y pendientes de pasteurizadora se probaron para P-03, módulo 1, ANILLAS, VAPOR, SUPERIOR, mediante invocación directa del controlador. Sus rutas web usan POST de consulta; no se enviaron por HTTP.
- Reportes se midió inicialmente con tipo_equipo=lavadora, parámetro que index no utiliza; su valor efectivo fue el predeterminado lavadoras. Una atribución posterior con tipo=lavadoras confirmó las 449 consultas.
- Los escenarios de alta normal no seleccionaron un componente/módulo; no representan todo el flujo posterior del formulario.
- Las primeras invocaciones de edición/historial que tenían argumentos incorrectos en el banco se descartaron; **no son defectos del producto**. Las muestras de la tabla corresponden a firmas válidas y parámetros equivalentes a HTTP.
- La muestra inicial exploratoria de lavadora global, antes del banco comparativo, fue **7755.86 ms, 152 consultas, 4241.95 ms SQL**. No se mezcló con la mediana de las tres muestras.
- Pico de memoria del proceso del banco principal: **66 MiB**; la exploración individual llegó a 56 MiB. Son máximos del proceso con consultas registradas, no memoria aislada de cada ruta ni límite de producción.
- La preparación de datos de formularios puede ser rápida y su apertura real seguir siendo lenta por el layout, imágenes y JavaScript.

**Escenario fallido fuera del ranking:** búsqueda global q=guia: 213.88 ms hasta excepción, diez consultas exitosas registradas y después una consulta fallida a analisis. Ese valor **no es tiempo de respuesta exitoso** ni evidencia de cumplimiento de la meta de filtros.

### C.2. Renderizado en memoria, separado de datos

Una muestra por vista. El motor de prueba compila cada fuente en memoria y evalúa Blade con datos locales. No reutiliza los archivos compilados de disco.

| Vista | Tiempo de render/compilación | SQL adicional de vistas | SQL ms | Bytes HTML | Etiquetas HTML aproximadas |
|---|---:|---:|---:|---:|---:|
| dashboard-modulos | 396.35 ms | 456 | 219.05 | 142,601 | 407 |
| dashboard_lavadora | 575.12 ms | 463 | 311.28 | 501,304 | 1,325 |
| lavadora/analisis-lavadora/create | 529.52 ms | 456 | 309.91 | 139,264 | 406 |
| pasteurizadora/analisis-pasteurizadora/create | 405.59 ms | 456 | 224.57 | 149,320 | 444 |
| lavadoras/diagramas/l04-l09 | 495.52 ms | 456 | 222.78 | 687,454 | 1,346 |

El conteo aproximado de etiquetas proviene de una expresión sobre HTML, no de un DOM real. Los bytes HTML incluyen contenido generado y datos locales, y se registró solo su longitud: no se guardaron ni publicaron datos de usuario.

**Implicación:** la campana convierte páginas aparentemente simples en cientos de consultas adicionales. El diagrama tiene un payload HTML importante; la experiencia visual necesita medir también SVG, imágenes, scripts y animaciones en navegador.

### C.3. Operaciones pendientes de evaluación end-to-end

| Operación solicitada | Estado y motivo | Siguiente medición |
|---|---|---|
| Carga inicial welcome/login | Recursos y código revisados; sin navegador instrumentado | TTFB, waterfall, LCP y usabilidad en sesión anónima de staging |
| Inicio de sesión | No se enviaron credenciales ni se persistió sesión | Usuario de pruebas y cookie jar aislado; separar POST login, redirección y dashboard |
| Navegación entre módulos | Controladores/render medidos por separado | HTTP autenticado, permisos reales y caché fría/caliente |
| Apertura completa de formularios | Datos y dos renders medidos | Tiempo hasta campos/listas/validación utilizables, móvil y escritorio |
| Guardado y actualización | No ejecutados por efectos sobre DB, archivos y proveedores | Copia aislada, mail/WhatsApp/IA falsos y escenarios con/sin fotos |
| Actualización de indicadores | Lógica inspeccionada; sin cronómetro del DOM | Medir desde respuesta recibida hasta indicador actualizado; comprobar mismas reglas |
| PDF/Excel/importaciones | No ejecutados por archivos y notificaciones | Medir preparación, generación, bytes y memoria por volumen |
| Central hidráulica y etiquetadoras con volumen | Datos insuficientes | Fixtures representativos en copia de staging |
| Concurrencia | No se realizó prueba de carga | Incrementos pequeños en staging con métricas de PHP/DB/colas y criterios de parada |
| LCP p75, INP p75, CLS | Sin datos de campo ni navegador real | RUM por página/dispositivo y pruebas de laboratorio complementarias |
| Producción | No accedida ni modificada | Verificación de configuración y mediciones autorizadas separadas |

## D. Plan de optimización

Ninguna fase se ejecutó. Cada cambio debe empezar con una línea base de su escenario y terminar con una comparación funcional y de rendimiento. Conviene mantener cambios pequeños para atribuir resultados.

### Fase 1. Mejoras rápidas y de menor riesgo

1. **F06: assets globales.** Preparar variantes comprimidas/dimensionadas de los cuatro logos y favicon; conservar secuencia e identidad visual. Registrar bytes antes/después y revisar visualmente.
2. **F04: solicitudes redundantes de campana.** Reutilizar el payload ya renderizado; evitar solapamientos y refrescos invisibles manteniendo revalidación al regresar. Validar lectura y borrado de destinos.
3. **F02/F13: duplicados dentro de la petición.** Reutilizar solo conjuntos con los mismos parámetros y alcance; no introducir caché compartida de permisos.
4. **F19: librerías por página.** Retirar cargas globales únicamente donde se demuestre ausencia de uso; usar paquetes ya instalados y build de staging con safelist de clases dinámicas.
5. **F20/F27: configuración de entrega.** Verificar pipeline y SAPI de producción antes de cambiar compresión, caché o OPcache; probar en staging y medir. No ejecutar comandos de optimización contra producción durante una auditoría.
6. **F33: logs.** Reducir payload de diagnóstico conservando auditoría de negocio y errores. F11 requiere alinear plataforma antes de cualquier despliegue.

Las optimizaciones de assets/sondeos son acotadas. F01/F03 tienen mayor riesgo semántico y deben pasar por Fase 2; no se presentan como arreglos triviales.

### Fase 2. Consultas y backend

1. **F03 primero en alcance transversal:** resolver destinos de notificaciones por lotes y reutilizar colección/conteos por petición. La paginación debe seguir encontrando diez destinos válidos, no diez filas que después desaparecen.
2. **F01 y F02:** extraer cálculo de estado vigente, construir prueba de equivalencia con alias, fechas editadas y estados de corrección; comparar SQL actual con alternativas. Obtener una única base por alcance para dashboards, costos y diagramas.
3. **F05:** precargar entradas de periodicidad y eventos por todas las líneas; calcular ventanas 52/12/4 y 30/14/7 sobre la misma fuente cuando compartan filtros.
4. **F07/F15/F18:** separar resúmenes y detalles, consultar cada contexto de revisión una vez, limitar el histórico que se hidrata sin recortar el histórico disponible al usuario.
5. **F14/F17:** paginación de históricos, agregados separados y rangos de fecha aprovechando índices existentes.
6. **F12:** revisar redundancia de índices y cualquier candidato nuevo solo después de tener consultas finales, planes y prueba de escritura. DDL requiere autorización.
7. **F10:** corregir integración heredada de búsqueda y volver a medir una respuesta completa antes de optimizar LIKE o introducir otra tecnología de búsqueda.

### Fase 3. Frontend y formularios

1. Separar JS/CSS grandes del HTML, servirlos con hash y cargar gráficas con visibilidad/interacción.
2. Completar la prevención de respuestas obsoletas en períodos 52-12-4; reducir cascadas de red donde los datos sean independientes.
3. Incorporar estados de carga, errores recuperables e idempotencia de envío donde falte. No cambiar umbrales, permisos ni reglas de selección.
4. Medir compresión, decodificación y miniaturas de evidencias; reducir doble procesamiento y reindexación del mismo registro.
5. Optimizar timers/animaciones visibles con mediciones del hilo principal, conservando tiempos y secuencia de indicadores.
6. Evaluar actualización parcial de filtros después de resolver el costo del backend y del layout. Mantener navegación accesible y URLs compartibles.

### Fase 4. Arquitectura y escalabilidad

1. **F08/F09/F26:** cola real para IA, indexación, WhatsApp y exportaciones; afterCommit, idempotencia y presupuesto coherente de timeout/retry. Worker supervisado y separado de tráfico web.
2. Evitar un worker prolongado dentro del endpoint HTTP de cron. Verificar mecanismos de ejecución disponibles antes de cambiar la operación.
3. Definir servicios de consulta y DTOs de resumen compartidos por dashboard/reportes; conservar reglas de negocio en una fuente comprobable.
4. Diseñar caché entre peticiones solo con invalidación y aislamiento de permisos. Resúmenes/materializaciones requieren reconciliación y autorización si cambian esquema.
5. Instrumentar duración total HTTP, SQL, Blade, proveedores, jobs y colas; RUM de Core Web Vitals por página/dispositivo. Aplicar muestreo y evitar registrar secretos o contenido sensible.
6. Probar concurrencia gradual y volúmenes representativos. No decidir Redis, Octane, otro servidor o una SPA sin evidencia de saturación y beneficio.

## E. Validación y criterios de aceptación

### E.1. Protocolo de medición antes y después

1. Usar una copia aislada y conocida de datos, con la misma versión de PHP, DB, código y configuración en ambas mediciones.
2. Medir por rol y filtros iguales; separar frío/caliente y tamaño de dataset. Registrar revisión Git, volumen y configuración efectiva.
3. Tras un calentamiento documentado, obtener suficientes muestras para p50/p95 de laboratorio. Medir p75 de Web Vitals con población representativa; tres muestras locales no sirven para esa conclusión.
4. Capturar tiempo total HTTP/TTFB, SQL count/tiempo/duplicados, filas retornadas, render, bytes transferidos, CPU y memoria. No sumar duraciones solapadas.
5. Probar un cambio por vez y comparar salidas/estados además de tiempos. Mantener presupuesto de error y plan de rollback.
6. Para concurrencia en staging: empezar con un usuario, aumentar de manera controlada y detener ante errores, crecimiento sostenido de cola, lock waits o saturación. No realizar carga intensiva en producción.

### E.2. Matriz de regresión por grupo de hallazgos

| Hallazgos | Comprobación funcional obligatoria | Comprobación de rendimiento |
|---|---|---|
| F01/F02/F07/F13/F18 | Igualdad de IDs vigentes y payload de estados por línea, alias, lado y reductor; fechas editadas/desempates; correcciones/cambios; líneas activas/inactivas; permisos de costos | Mismo dataset/filtros: comparar scopes ejecutados, SQL count, tiempo SQL y tiempo total; EXPLAIN; no empeorar escrituras |
| F03/F04 | Roles admin/técnico/supervisor/gerente; destinos eliminados; notificaciones leídas/no leídas; planes; elongaciones del ciclo actual y anterior | Número de consultas debe crecer por modelos/lotes, no por cada notificación; evitar request inicial repetida; confirmar payload idéntico |
| F05/F15 | Periodicidad, resets, numeración global, módulos aplicables, tipos quick/normal, áreas, niveles y lados | Queries por contexto/línea antes/después; histogramas por volumen; tiempo de selección y reporte |
| F06/F19/F20/F21/F22/F23/F32 | Comparación visual escritorio/móvil; gráficas; retroceso/adelante; foco; imágenes y colores; selección rápida de línea/fecha sin datos obsoletos | Waterfall, bytes transferidos, parseo/script, tareas largas, DOM y perfiles CPU; LCP/INP/CLS; memoria tras secuencia de navegación |
| F08/F09/F24/F25/F26 | Falla del proveedor, rollback, reintento, duplicación y job fuera de orden; ninguna pérdida de evidencias ni avisos; exportaciones/totales iguales | Tiempo de guardado separado de job; duración de transacción/locks; tiempo en cola; duración/errores de jobs; memoria de generación |
| F10 | Búsqueda exitosa por cada fuente y rol; no exposición de resultados fuera de permiso; esquema heredado correcto | Tiempo de respuesta completa y SQL con términos frecuentes/raros y sin coincidencia |
| F11/F27 | Misma plataforma de CLI/CGI/worker; autoload y rutas correctos con cachés; despliegue/invalidation/reinicio verificados | Bootstrap y HTTP frío/caliente comparables; OPcache del SAPI real, sin inferirlo del CLI |
| F12/F17 | Filtros de fecha y búsquedas con misma semántica; FKs y planes usados por otros módulos | SELECT y escrituras representativas, selectividad y EXPLAIN antes/después de proponer DDL |
| F14/F28/F29/F30/F31/F33 | Historial completo accesible, estadísticas globales correctas, reintento legítimo, grounding IA, permisos, auditoría y errores conservados | Filas/memoria por página, trabajo duplicado, contexto/tokens, agregados SQL y E/S de logs |

La [documentación de relaciones Eloquent](https://laravel.com/docs/12.x/eloquent-relationships#eager-loading) fundamenta cargar relaciones por lotes cuando hay N+1. En esta aplicación también hace falta cambiar consultas explícitas por elemento; añadir with() no corrige automáticamente filterExistingTargets. El código local de Dispatcher y el [capítulo de colas de Laravel 12](https://laravel.com/docs/12.x/queues#job-expirations-and-timeouts) ayudan a validar despacho y la relación entre timeout y retry_after.

### E.3. Pruebas existentes pertinentes

Se ejecutó, sin generar archivos de proyecto:

~~~powershell
& 'C:\laragon\bin\nodejs\node-v22\node.exe' --test tests/Frontend/dashboard-lef-52124.test.mjs tests/Frontend/pasteurizadora-numeracion.test.mjs
~~~

Resultado: **5 aprobadas, 0 fallidas**, duración total del runner **279.25 ms**. Verifican actualización de selector/gráficas LEF con stubs y numeración/limpieza de selección por módulo. Sus duraciones **no son métricas de navegador ni de rendimiento del sistema**.

Para una implementación posterior conviene ejecutar en un entorno aislado, tras revisar su bootstrap:

- DashboardLavadoraStateDetailTest y DashboardLavadoraElongacionTrendTest.
- LavadoraRevisionPeriodicityServiceTest y AnalisisLavadoraRevisionGeneralTest.
- PasteurizadoraAnalysisCycleTest, PasteurizadoraNumeracionTest y CentralHidraulicaPasteurizadoraTest.
- NotificationControllerTest, NotificationServiceTest, AdminRecordNotificationTest y pruebas de recordatorios.
- RoleAccessTest, AnalysisDeletionPermissionTest y GlobalSearchTest.
- LavadoraCostModuleTest, Lef52124DashboardDataTest y pruebas de costos/Power BI.
- WasherMaintenanceAiFlowTest, PasteurizadoraMaintenanceAiFlowTest e HybridKnowledgeRetrievalTest con proveedores falsos.

Esta lista es una propuesta de regresión, **no una afirmación de que PHPUnit pasó**. No se crearon tests nuevos ni se alteraron los existentes.

### E.4. Metas iniciales y estado

| Operación | Meta de referencia solicitada | Estado de comprobación |
|---|---|---|
| Navegación interna | TTFB <500 ms | No medido por HTTP; los controladores lentos ya exceden ampliamente esa referencia |
| Apertura de formulario | Utilizable <1 s | No demostrado; layout y recursos requieren navegador |
| Guardado sencillo | <1 s | No medido; separar fotos/proveedores/trabajo secundario |
| Filtros y búsqueda | <500 ms | Algunas consultas de lectura locales son rápidas; búsqueda global falla y no cumple funcionalmente |
| Indicadores | Actualización <500 ms tras recibir datos | No medido en DOM; conservar reglas y posiciones |
| LCP | <=2.5 s en p75 | Sin datos válidos |
| INP | <=200 ms en p75 | Sin datos válidos |
| CLS | <=0.1 | Sin datos válidos |

Las metas no son garantías. Exportaciones grandes, IA y procesamiento de evidencias necesitan presupuestos propios y comportamiento asíncrono explícito cuando corresponda.

## Apéndice: reproducción segura del banco principal

Este ejemplo reproduce la preparación del dashboard de lavadoras en CLI, sin render. Usa dependencias existentes y usuario admin solo dentro del proceso. **No prueba autenticación ni middleware.** La protección read-only bloquea escrituras SQL, pero no protege por sí sola archivos o servicios externos; por eso solo debe usarse con métodos inspeccionados de lectura. No extender a guardados, createQuick, cron o exportaciones.

El banco se pasó a PHP por stdin con un here-string literal de PowerShell, sin guardarlo como archivo de aplicación. Ajustar la ruta del ejecutable a PHP compatible.

~~~php
<?php
require 'vendor/autoload.php';

$env = Dotenv\Dotenv::parse(file_get_contents('.env'));
if (($env['DB_CONNECTION'] ?? '') !== 'mysql'
    || !in_array($env['DB_HOST'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('Solo base MySQL local para este banco.');
}

$app = require 'bootstrap/app.php';
$app->afterBootstrapping(
    Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    function ($app) {
        $app['config']->set([
            'cache.default' => 'array',
            'session.driver' => 'array',
            'logging.default' => 'null',
            'mail.default' => 'array',
        ]);
    }
);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$conn = Illuminate\Support\Facades\DB::connection();
$pdo = $conn->getPdo();
$pdo->exec('SET SESSION MAX_EXECUTION_TIME=2500');
$pdo->exec('START TRANSACTION READ ONLY');

try {
    $user = App\Models\User::whereHas(
        'roles', fn ($q) => $q->where('name', 'admin')
    )->firstOrFail();
    Illuminate\Support\Facades\Auth::guard('web')->setUser($user);
    $user->getAllPermissions();

    $session = new Illuminate\Session\Store(
        'audit-memory',
        new Illuminate\Session\ArraySessionHandler(120)
    );
    $session->start();

    for ($i = 1; $i <= 3; $i++) {
        $request = Illuminate\Http\Request::create('/dashboard/lavadoras', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($session);
        $app->instance('request', $request);

        $conn->flushQueryLog();
        $conn->enableQueryLog();
        $start = hrtime(true);
        $view = $app->make(App\Http\Controllers\DashboardController::class)
            ->lavadoraGlobal($request);
        $elapsed = (hrtime(true) - $start) / 1e6;
        $queries = $conn->getQueryLog();
        $conn->disableQueryLog();

        $seen = [];
        $duplicates = 0;
        foreach ($queries as $query) {
            $fingerprint = md5($query['query'] . serialize($query['bindings']));
            if (isset($seen[$fingerprint])) {
                $duplicates++;
            }
            $seen[$fingerprint] = true;
        }

        echo json_encode([
            'run' => $i,
            'controller_ms' => round($elapsed, 2),
            'sql_count' => count($queries),
            'sql_ms' => round(array_sum(array_column($queries, 'time')), 2),
            'exact_duplicate_sql' => $duplicates,
        ]) . PHP_EOL;
        unset($view);
    }
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
~~~

Para EXPLAIN se preparó `'EXPLAIN ' . $query->toSql()` y se ejecutó con `$query->getBindings()`; no se imprimieron credenciales ni resultados de negocio. Para contar duplicados se comparó SQL **y parámetros**, no solo textos semejantes. Las mediciones de render, estáticos y Node son bancos distintos y no se mezclan con este tiempo.

**Orden de actuación recomendado:** notificaciones compartidas, selección de estados vigentes/reutilización de consultas, reportes/periodicidad, recursos globales y desacoplamiento de trabajo secundario de guardados. El resto debe priorizarse con las mismas mediciones y con datos representativos de producción.

