# Continuación 58–63 y regresión actual

Este documento conserva el cierre del 31 de octubre. La continuación 65/66 y el alcance confirmado el 2 de noviembre están en [cierre 67](OPTIMIZACION_RENDIMIENTO_67_CIERRE_CODIGO.md).

El usuario pidió continuar los pendientes y permite dejar el despliegue pendiente. Se implementaron cinco mejoras de código adicionales y se completaron verificaciones locales de navegador. La auditoría original se mantiene histórica; no se declara cerrada toda la auditoría mientras existan las limitaciones indicadas abajo.

| Cambio | Evidencia |
|---|---|
| [58: avisos de elongación](OPTIMIZACION_RENDIMIENTO_58_AVISOS_ELONGACION.md) | Mismos 224 avisos: 768 → 96 SQL, mismos IDs ordenados; resultado reutilizado solo durante una lectura. |
| [59: navegador real](OPTIMIZACION_RENDIMIENTO_59_CHROME_ASINCRONIA.md) | Campana con pestaña oculta real, LEF con fetch/abort nativos y Canvas escritorio/móvil comprobados con fuentes reales y endpoints sintéticos. |
| [60: gráfica visible](OPTIMIZACION_RENDIMIENTO_60_GRAFICA_VISIBLE.md) | Construcción histórica diferida, tabla/totales inmediatos; perfil Chart.js calentado 157,68 → 96,70 ms escritorio y 131,02 → 89,39 ms móvil. Datos/filtros/impresión iguales y cero píxeles distintos iniciales. |
| [61: resumen anual](OPTIMIZACION_RENDIMIENTO_61_COLUMNAS_RESUMEN_COSTOS.md) | 5001 filas, notas de detalle sin hidratar: pico adicional 38,15 → 11,56 MB, mediana 676,62 → 591,15 ms; hash completo igual. |
| [62: embedding necesario](OPTIMIZACION_RENDIMIENTO_62_EMBEDDING_NECESARIO.md) | Corpus vacío: una llamada → cero, mismo resultado; vector único por búsqueda con ambos módulos, fallo léxico y permisos comprobados. |
| [63: envío pasteurizadora](OPTIMIZACION_RENDIMIENTO_63_ENVIO_PASTEURIZADORA.md) | Bloqueo común y recuperación normal/rápido, validadores conservados. Chrome: dos admisiones → una; no equivale a idempotencia durable. |

## Comprobaciones

- PHP 8.4.12, SQLite :memory:, caché/sesión en memoria, cola sync, AI_ENABLED=false, AI_HISTORY_INDEX_AUTO=false, LOG_CHANNEL=null y MAIL_MAILER=array. Los casos de IA usan un proveedor falso; no hay llamadas externas ni gasto.
- Suite completa sin caché de resultados: **459 pruebas, 5432 aserciones, cero errores/fallos/omitidas, 87,80 s**. JUnit: `C:\Users\alons\AppData\Local\Temp\legado-regression-20261031-64.xml`. Incluye el último caso semántico sin tokens y comprobación explícita de presencia de ambos módulos; la corrida previa de 458 casos también aprobó.
- Frontend: **32/32 pruebas**. Sintaxis de **105 PHP** modificados/nuevos sin errores. Composer validate y check-platform-reqs --lock aprobados sin red ni actualización de paquetes.
- Build Vite aprobado: siete assets y gzip, 634371 → 112562 bytes. Módulo de lavadoras `dashboard-lavadora-panels-CaghyotJ.js`; publicar build/manifiesto/sidecars junto al código. public/build sigue ignorado por Git.
- Chrome usa perfiles aislados y servidor de fixtures; artefactos específicos y límites en 59/60/63. No se atribuyen los tiempos de endpoints sintéticos ni muestras CPU a producción.

No se ejecutaron migraciones, DDL ni escrituras de negocio sobre el MySQL operativo; las migraciones de pruebas se ejecutan solo en SQLite aislado. No se activaron workers/colas durables, proveedores ni despliegue. No se hicieron commits ni se descartaron cambios ajenos.

## Pendientes que no se ocultan

[Despliegue 48](OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md): entrega atómica de assets/código, SAPI/OPcache/cachés, compresión de destino, backend de colas y workers, índices y recuperación de la fuente heredada con sus datos. El usuario permite dejar estos puntos pendientes.

Persisten decisiones de arquitectura que no son simples arreglos de rendimiento: idempotencia durable de servidor (requiere clave/persistencia atómica y pruebas de concurrencia/fallo), exportaciones durables con descarga autorizada y navegación parcial si la medición posterior al despliegue la justifica. No se han implementado esas funciones ni se presentan como pendientes exclusivamente operativos ya resueltos en código. Su detalle está en [43](OPTIMIZACION_RENDIMIENTO_43_DECISIONES_PENDIENTES.md) y [48](OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md).

Calidad de fotos reales/proveedor semántico, concurrencia del entorno de destino y métricas de campo requieren datos/entorno representativos. Metadatos/ciclos completos siguen escalando con el historial porque alimentan agregados; no se recortan para lograr un porcentaje. F17 y el generador XLSX general siguen sin aplicarse porque no demostraron mejora. Este documento registra mejoras comprobadas y límites, no un 100 % artificial.
