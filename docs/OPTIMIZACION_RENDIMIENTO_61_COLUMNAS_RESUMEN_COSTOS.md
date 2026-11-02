# 61. No hidratar notas y detalle en el resumen global de costos

El resumen global ya evitaba cálculos del módulo completo, pero seleccionaba todas las columnas de cada movimiento anual. Notas y snapshots del catálogo no participan en sus tres indicadores/ranking por lavadora.

`LavadoraCostAnalyticsService` selecciona, solo para `globalSummaryData`, ID, línea, análisis, origen, monto, snapshot del componente y metadatos. Conserva filtros, orden, agrupación PHP, desempates, relaciones, redondeos y consulta anual. El dashboard completo conserva `SELECT *`; no se borran notas ni se recorta historial. Los metadatos se mantienen porque intervienen en etiquetas, líneas heredadas y correcciones.

`tools/benchmark-cost-summary.php`: SQLite en memoria, reloj fijo, 5001 movimientos con notas de unos 3,7 KB, calentamiento y tres muestras por versión:

| Medida | Antes | Después |
|---|---:|---:|
| Mediana | 676,62 ms | 591,15 ms |
| Pico adicional de memoria, mediana | 38153456 bytes | 11555672 bytes |
| Consultas | 3 | 3 |

Hash completo del resumen idéntico en las seis muestras: `2f93c1b2f0717338065cfff9947b8123a0851430e51639e50126db6b9f94c76e`. La reducción de memoria es 69,7 % en ese fixture; depende del tamaño de las columnas excluidas. La colección sigue escalando con filas y metadatos, sin promesa de memoria constante.

Pruebas de costos: 11 casos/68 aserciones aprobados. La nueva equivalencia cubre dos líneas, montos negativos, snapshots vacíos/alias RV200, metadata de corrección, empates, seis filtros incluidos años anteriores/rango vacío, y permanencia de notas completas. Reversión: retirar el `select` condicional, sin cambios de esquema.
