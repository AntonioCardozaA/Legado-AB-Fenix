# Estado actual de implementación y pruebas

Este cierre conserva la regresión posterior a 56. La continuación 58–63 y sus comprobaciones están en [validación 64](OPTIMIZACION_RENDIMIENTO_64_VALIDACION_ACTUAL.md).

Actualiza el cierre histórico 47 con la continuación 49–56. La auditoría original permanece intacta. No se desplegó a producción ni se modificaron esquema/datos de negocio del MySQL operativo; los fixtures usan SQLite :memory:. No se activaron colas durables ni proveedores externos.

## Cambios de esta continuación

| Documento | Resultado |
|---|---|
| 49/50 | Historial/listado y detalle hidráulico paginados; catálogo sin consultas por fila. Listado de fixture 501 registros: 1011 → 11 SQL, 501 → 25 modelos, 1,24 MB → 159 KB HTML. Las dos etapas tienen bancos separados; no se suman porcentajes ni se mezclan sus medianas. |
| 51/52 | Corregidos fixtures de rol/login y expectativas visuales; aviso hidráulico prueba rechazo y concesión explícita de permisos. Producción conserva autorización. |
| 53 | Por decisión del usuario, cobertura LEF adaptada a rutas vigentes. Reparados dos imports que causaban 500 en costos; sin cambios de importes/consultas. XLSX vigente se lee y valida realmente. |
| 54 | Histórico etiquetadora reutiliza una fuente completa: 12 → 8 SQL y mediana 2056,70 → 1688,04 ms en fixture; payload/HTML idénticos. Conserva todos los ciclos y el historial individual paginado existente. |
| 55 | Reparado método ausente del formulario existente de importación de costos. Se usa el servicio original, control admin y validación; carga real de XLSX y repetición sin duplicados comprobadas. |
| 56 | Historial agrupado de pasteurizadora con 25 registros completos por grupo y metadatos globales: HTML -50,4 %, pico adicional -47,3 % en fixture. Totales/numeración/fechas iguales y todas las páginas accesibles. |

## Regresión vigente

PHP 8.4.12, AI_ENABLED=false, AI_HISTORY_INDEX_AUTO=false, LOG_CHANNEL=null, MAIL_MAILER=array; suite con SQLite en memoria, sin caché de resultados. Resultado completo después del cambio 56: **452 pruebas, 5384 aserciones, cero errores, cero fallos y cero omitidas**, 104,45 s. Informe: `C:\Users\alons\AppData\Local\Temp\legado-regression-20261031-56.xml`.

Los ocho casos históricos de base ya no están pendientes: cinco se corrigieron en fixtures/expectativas verificando las reglas actuales, y tres LEF se adaptaron al alcance elegido explícitamente por el usuario. No se suprimieron pruebas ni se implementaron endpoints ausentes para satisfacer expectativas antiguas. En ese trabajo aparecieron y se corrigieron dos fallos reales de las funciones vigentes de costos.

Las 26 pruebas frontend pasan. Build Vite aprobado, siete gzip: 633630 bytes de fuentes compiladas → 112248 bytes comprimidos. Las siete entradas existen y cada gzip reproduce exactamente su archivo original. Las entradas del manifiesto mantienen los hashes del build previo; no hubo cambios de librerías ni dependencias. Composer validate y check-platform-reqs --lock se repitieron y pasan sin red. PHP lint aprueba los 99 archivos modificados/nuevos comprobados. Después solo se retiró un espacio final de Blade, sin efecto funcional; git diff --check queda limpio. Los 15 enlaces locales de los 13 documentos de cierre comprobados existen. Las capturas e interacción de evidencias de pasteurizadora pasan en Chrome a dos resoluciones, con los límites descritos en 56.

## Trabajo que sigue abierto

El [seguimiento](OPTIMIZACION_RENDIMIENTO_PROGRESO.md) conserva parciales y la [propuesta operativa](OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md) delimita las acciones excluidas de la autorización actual. Permanecen DDL de índices, recuperación de la fuente heredada ausente, activación de colas y despliegue/OPcache/medición del entorno de destino. Navegación parcial, idempotencia durable, carga diferida adicional de gráficas y cambios de calidad/recuperación IA requieren diseño/evidencia y las autorizaciones de riesgo correspondientes.

Los historiales de lavadora/pasteurizadora aún leen metadatos completos para preservar agregados; etiquetadora conserva la fuente necesaria para ciclos. No se declara que su costo sea constante con cualquier volumen. No se atribuyen garantías de latencia de producción a los bancos sintéticos ni se vuelve a presentar la auditoría completa como cerrada.
