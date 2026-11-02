# Cierre del código autorizado — 2 de noviembre de 2026

Se completa la continuación con [Excel sin preparación duplicada (65)](OPTIMIZACION_RENDIMIENTO_65_EXCEL_SIN_CALCULO_DUPLICADO.md) y [navegación parcial del directorio (66)](OPTIMIZACION_RENDIMIENTO_66_NAVEGACION_DIRECTORIO.md). Las optimizaciones anteriores permanecen documentadas en el [seguimiento de los 33 hallazgos](OPTIMIZACION_RENDIMIENTO_PROGRESO.md). No quedan cambios de código comprometidos por implementar en este alcance; no equivale a certificar toda la auditoría o el rendimiento de producción al 100 %.

## Alcance confirmado por el usuario

El usuario permite dejar despliegue pendiente. Ante la consulta específica sobre migraciones `submission_receipts`/`background_exports`, transacciones de idempotencia y exportaciones privadas en cola, respondió: **«Dejar esos cambios de arquitectura pendientes»**. Se conservan pendientes F28 durable y F25 asíncrono; no se presentan como implementados.

La revisión automática había rechazado el parche de idempotencia por añadir esquema y cambiar transacciones/manejo de archivos, fuera de la autorización previa. No se aplicó ni se intentó por otro mecanismo. Se comprobó ausencia de `config/performance.php`, `app/Services/IdempotentSubmission.php` y `database/migrations/performance`. No se añadieron esas migraciones ni se alteró el guardado de análisis en esta continuación.

## Validación final

- **462 pruebas PHP, 5503 aserciones, cero errores/fallos/omitidas, 99,70 s.** SQLite :memory:, caché/sesión aisladas, cola sync, IA/indexación automática desactivadas y mail array. Informe `C:\Users\alons\AppData\Local\Temp\legado-regression-20261102-67.xml`.
- Tras el ajuste final del anuncio accesible: **14 pruebas relacionadas/137 aserciones** aprobadas y Chrome repetido con fixtures definitivos. El caso de carrera de popstate también se comprueba con respuestas retrasadas reales.
- **32/32 pruebas frontend**, sintaxis de **111 PHP** modificados/nuevos, Composer validate y check-platform-reqs --lock aprobados; sin actualizar dependencias.
- Build aprobado, siete gzip: **634371 → 112562 bytes**, mismos hashes del build anterior. Se entrega también el nuevo `public/js/partial-directory.js`; su URL usa filemtime. Manifiesto/assets/sidecars deben entregarse juntos; public/build está ignorado por Git.
- Excel: celdas/hojas idénticas; general 19 → 6 SQL y mediana 3267 → 1608 ms sobre el mismo fixture. Directorio: documento/fragmento del mismo dataset 294508 → 84700 bytes y mediana 80,91 → 31,63 ms. Los bancos y sus límites se detallan en 65/66.

La auditoría original sigue intacta. No se hicieron commits, DDL ni escrituras de negocio sobre MySQL operativo; las migraciones de pruebas solo usan SQLite en memoria. No se activaron colas durables, proveedores ni despliegue.

## Qué permanece pendiente

1. **Arquitectura pospuesta por decisión del usuario:** idempotencia durable de servidor y exportaciones durables con descarga autorizada. La protección de doble submit existente es de interfaz; las exportaciones actuales siguen siendo síncronas.
2. **Despliegue/entorno:** [entrega 48](OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md), SAPI/OPcache/cachés, compresión del servidor de destino, índices revisados, recuperación de la fuente heredada con sus datos y backend/workers de colas.
3. **Validación de campo:** concurrencia y métricas de producción, fotos representativas y calidad/latencia de proveedores reales. Las mejoras locales no sustituyen esos datos. Metadatos/ciclos completos continúan escalando con el historial, preservando agregados e información disponible.

F17 y el generador XLSX general se mantienen sin aplicar porque no demostraron mejora. No se recortan permisos, historia, candidatos IA ni calidad de imágenes para declarar un porcentaje artificial de cierre.
