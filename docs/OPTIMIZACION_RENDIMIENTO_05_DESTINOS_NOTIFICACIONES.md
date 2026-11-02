# Optimización 05: destinos de notificaciones por lotes

## Problema y decisión

F03: después de filtrar destinatario y permisos, NotificationVisibilityService comprobaba cada análisis o plan con una consulta exists. El usuario autorizó este cambio de código con pruebas. Se explicó previamente problema, causa, archivos, solución y riesgo.

Se agrupan IDs canónicos por clase permitida y se consultan lotes de hasta 400. Se conserva withoutGlobalScopes del algoritmo anterior, la prioridad de plan sobre análisis, el orden y el filtrado completo antes de paginar o contar. No se limita el conjunto a diez filas antes de validar destinos. Los IDs de formato no canónico usan el camino anterior, conservando coerción específica de la base. Los avisos de elongación mantienen sus comprobaciones de ciclo vigente y revisión, sin caché ni alteración.

Archivos: NotificationVisibilityService y NotificationTargetBatchTest. Riesgo: diferencias de visibilidad o destino; se verifica contra el método individual original y con permisos. Ningún estado se comparte entre peticiones o usuarios.

## Comparación equivalente

Mismo banco que optimización 02: PHP 8.4.12, MySQL 8.4.3 local, admin fijo, reloj fijo, 2572 notificaciones, 1043 análisis, transacción READ ONLY, sesión/caché array, logging null y sin proveedores externos. Una ejecución de calentamiento y cinco muestras por escenario. No se alteraron datos ni se vaciaron cachés del servidor. El HTML se compila en memoria y su huella excluye scripts, como en 02.

| Escenario | Mediana antes ms | Después ms | SQL antes | Después |
|---|---:|---:|---:|---:|
| unread-count | 549,25 | 198,23 | 456 | 12 |
| Formulario lavadora, datos y render | 584,07 | 312,03 | 458 | 14 |
| Página de notificaciones, datos | 681,55 | 184,01 | 456 | 12 |
| Resumen de etiquetadora | 628,07 | 194,69 | 456 | 12 |

Las cuatro huellas de salida permanecieron idénticas. HTML comparable: 140007 bytes antes y después. Los tiempos son locales; no incluyen red, navegador ni carga concurrente.

## Pruebas y límites

Dos pruebas nuevas, cinco aserciones: 300 avisos mezclados más casos especiales, destinos eliminados, prioridad de plan, IDs heredados, conservación de orden y aislamiento por destinatario/rol. Las comprobaciones por lotes necesitan cuatro consultas en ese fixture frente a una por cada referencia individual (tres modelos y un ID no canónico). Otras 25 pruebas de no leídas, recordatorios, servicio de notificaciones, estados vigentes y costos pasaron en la ejecución de regresión; el primer intento de la nueva prueba tenía un fixture sin reductor obligatorio, corregido antes de aprobarla.

Se conserva el fallo previo documentado de apertura de un plan de pasteurizadora; no está causado por el filtrado por lotes. Pendiente: evaluar por separado los destinos de elongaciones con ciclos y cualquier reutilización adicional dentro de una petición.

Reversión sin datos: restaurar filterExistingTargets a su filtro individual original. No requiere DDL ni cambios de configuración.
