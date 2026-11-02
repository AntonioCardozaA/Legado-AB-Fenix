# Optimización 49: historial hidráulico paginado

F14. El listado y el historial dentro del detalle de central hidráulica usaban get(), hidratando y renderizando todo. Se explicó problema, archivos, solución y riesgo antes de modificar el controlador y ambas vistas. Se añadieron 25 registros por página, total global del filtro en el listado, enlaces que conservan parámetros y desempate ID descendente después de fecha/created_at. El detalle usa historial_page independiente y mantiene el registro principal y sus evidencias.

No se cambia selección de contexto, filtros, permisos, estados, soft deletes ni exportaciones. Los registros empatados no tenían orden final definido; ahora tienen uno estable para evitar huecos/repeticiones al paginar un conjunto sin cambios concurrentes. No se promete un snapshot entre peticiones si se insertan registros entre páginas.

Banco tools/benchmark-central-history.php: solo SQLite :memory: comprobado antes de migrar, 501 inspecciones sintéticas, mismo usuario/rol, reloj, sesión, filtros y campos; una preparación y tres muestras calientes por caso. Reutiliza el guard del banco de lavadoras y descarta su salida. Preparación más render, sin HTTP/red ni imágenes reales.

| Caso | Antes | Después |
|---|---:|---:|
| Listado, mediana ms | 722,13 | 65,58 |
| Listado, SQL | 1011 | 60 |
| Listado, modelos visibles | 501 | 25 |
| Listado, total del filtro | 501 | 501 |
| Listado, HTML bytes | 1236329 | 159445 |
| Listado, pico adicional bytes | 8598400 | 1458328 |
| Detalle, mediana ms | 322,13 | 60,16 |
| Detalle, SQL | 516 | 65 |
| Detalle, modelos visibles | 251 | 25 |
| Detalle, total del contexto | 251 | 251 |
| Detalle, HTML bytes | 344441 | 129155 |
| Detalle, pico adicional bytes | 3753632 | 1416464 |

Ocho pruebas del módulo pasan, 128 aserciones: tres nuevas recorren todas las páginas, fechas empatadas, filtros, soft deletes, totales completos, contexto/lado, evidencia y ausencia de escritura, vacío, fuera de rango y acceso anónimo; cinco existentes verifican captura/detalle/catálogo. No se eliminaron filas para obtener la mejora. No se atribuyen esos tiempos a una instalación operativa con cero filas hidráulicas: es una prueba de escalado reproducible.

El banco reveló consultas de catálogo repetidas por fila; todavía están presentes en esta medición. Su eliminación se evalúa como cambio siguiente por separado. Reversión: restaurar las dos consultas get y contador count, y retirar los enlaces de paginación. No requiere DDL.
