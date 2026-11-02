# Optimización 29: una fuente de eventos por reporte

F05. Se explicó previamente que las filas mensuales por línea y las ventanas 52/12/4 y 30/14/7 repetían lecturas. TendenciaDanosService construye ahora un contexto local mediante construirResumenReporte; los cuatro recorridos de ReporteController (resumen/detalle de lavadoras y pasteurizadoras) lo usan. No hay caché compartida ni cambios en estados, fechas, áreas o permisos.

La fuente amplia se filtra de nuevo al rango original de cada cálculo antes de resumir. Es necesario para que ultima_falla de 30 días no incluya fallas antiguas que solo corresponden a 52 semanas. Se conserva orden por fecha/ID, mecánica e hidráulica, límites de mes y las variaciones entre filas. Los métodos originales sin fuente opcional mantienen las lecturas independientes como referencia.

Cinco pruebas pasan (27 aserciones), incluidas dos nuevas: equivalencia JSON completa, fechas futuras, dos años, línea vacía, límites de inicio, falla antigua fuera de ventana corta, lectura fresca tras alta y presencia de ambas áreas de pasteurizadora.

Banco alternado, un proceso, MySQL READ ONLY, mismo admin/reloj/snapshot, calentamiento y tres muestras por variante; mide el cálculo compartido, no el render completo:

| Fuente | SQL antes → después | Mediana ms antes → después | Datos completos |
|---|---:|---:|---|
| Lavadoras, 8 líneas | 28 → 3 | 393,68 → 217,31 | Idénticos |
| Pasteurizadoras, 12 líneas locales | 32 → 3 | 169,59 → 91,67 | Idénticos |

Las doce líneas del segundo banco incluyen todas las P-% locales; no equivalen necesariamente al catálogo de cada reporte. No se extrapolan tiempos al navegador. Riesgo de mezcla de rangos/áreas cubierto por equivalencia; reversión: recuperar llamadas independientes en ReporteController. Métodos originales siguen disponibles y ningún dato cambia.
