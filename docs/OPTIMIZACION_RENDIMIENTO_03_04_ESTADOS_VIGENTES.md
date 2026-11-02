# Optimizaciones 03 y 04: estados vigentes de lavadoras

## Problema, solución y riesgo explicados antes de editar

03: el dashboard calculaba los estados por línea para el resumen y volvía a hacerlo para las tarjetas. Se reutiliza el mismo array dentro de esa operación. Archivos: DashboardController y su prueba de detalle. Riesgo: desalinear resumen y tarjetas; se verifica que ambos usan los mismos estados y que el resumen no vuelve a consultar cada línea.

04: `ultimosPorComponente` comparaba cada registro contra posibles registros más recientes mediante NOT EXISTS, repitiendo normalizaciones de códigos y alias. Se usa ROW_NUMBER por identidad, ordenando por fecha efectiva descendente e ID descendente. Archivos: AnalisisLavadora, controladores Dashboard/AnalisisLavadora/Reporte, servicios de revisión general y costos, y pruebas. Riesgo mayor autorizado: cambiar identidad, desempates, filtros o alcance. Se conserva la consulta original como fallback para motores antiguos y como referencia de equivalencia.

Se mantienen la normalización SQL original, tipo lavadora/NULL, lado y reductor con NULL equivalente a cadena vacía, fecha efectiva y desempate por ID. Para línea NULL cada fila conserva su identidad independiente, como en la comparación original con igualdad SQL. Los filtros de estado permanecen fuera del ranking. Los filtros por línea se pasan también al interior del ranking, evitando procesar todas las líneas en una vista de una sola línea.

Una prueba inicial del ranking sin acotar líneas empeoró una consulta individual (aproximadamente 108–170 ms frente a 48–59 ms). Esa variante no se conserva en los consumidores por línea.

En costos se detectó un orden implícito entre componentes empatados. Se fija orden por ID antes de agrupar. Una nueva comparación confirmó igualdad completa de ambos dashboards, incluido ese ranking.

## Resultados

Banco CLI local, PHP 8.4.12, MySQL 8.4.3, mismo admin y fecha fija 2026-10-30, caché/sesión array, logging null y transacción READ ONLY. Sin render de Blade en estas mediciones. No se controló toda la carga del equipo.

03 reduce establemente 53 consultas: global de 150 a 97, operativo de 134 a 81 (la primera muestra global incorpora dos consultas adicionales de permisos en ambos casos). Por sí sola no produjo una mejora consistente de tiempo total: mediana global 12893,95 → 13838,03 ms; operativo 12708,71 → 12442,48 ms. Se conserva la reducción de trabajo duplicado y se evalúa junto a 04; no se atribuye una mejora de latencia global a 03 aisladamente.

04: antes de fijar el orden explícito de costos, tres muestras globales dieron 7315,10 / 7352,04 / 7329,93 ms y 725,58–829,00 ms SQL, frente a 5295,15–6572,62 ms SQL de 03. El contenido de estados coincidía; la diferencia de orden del ranking se corrigió antes de validar la entrega.

Comparación final sobre **la misma instantánea y en el mismo proceso**, alternando el fallback original y el ranking, sin modificar archivos ni datos entre variantes:

| Dashboard | Consulta original ms | Ranking ms | Contenido serializado |
|---|---:|---:|---|
| Global | 8764,76 | 5832,78 | Idéntico |
| Operativo | 10275,16 | 7103,79 | Idéntico |

Es una comparación pareada local, no una estimación de percentiles ni garantía de producción. La reducción SQL y la equivalencia complementan esa muestra.

Se compararon además IDs ordenados en MySQL: 781 vigentes globales y 54 en L-04, iguales entre algoritmos. Huellas de resultados completos: global `3300a6138b9a30d34bf710b4e4398918b1a644016b8fb8b909405a37e024cb0d`; operativo `d1d6828a36877a9d8b2075dda9f4c715cd625f67f98505ba530cbe04e4888933`.

## Validación y reversión

19 pruebas existentes de estados, evolución, costos y revisión general: 117 aserciones aprobadas. Nueva prueba de equivalencia: 5 aserciones sobre alias, lados NULL/vacíos, fechas editadas y empatadas, tipos de equipo, filtros exteriores y lista de líneas vacía. La prueba del resumen confirma solo dos consultas agregadas sin recalcular estados. No se cambian índices, tablas, datos, dependencias ni reglas de negocio.

Para revertir 04, restaurar el cuerpo del scope desde `ultimosPorComponenteCorrelacionado` manteniendo el argumento opcional de líneas; las llamadas pueden conservarse aplicando ese filtro exterior. Para revertir 03, restaurar el cálculo del resumen y sus dos consumidores. No se necesita revertir datos. Si staging muestra diferencias semánticas o regresión sostenida, desactivar primero el ranking y medir con el fallback.
