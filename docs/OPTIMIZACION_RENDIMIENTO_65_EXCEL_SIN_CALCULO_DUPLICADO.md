# 65. Evitar calcular un reporte que el Excel no utiliza

En `ReporteController::exportarExcel`, las dos ramas de lavadoras construían un reporte detallado/general en `$reporte`, pero nunca lo utilizaban. Después `ReporteLavadoraGeneralExcel` volvía a consultar sus propios datos. Se retira únicamente esa preparación descartada. Se conservan resolución de línea, autorización, fechas, nombre del archivo y el exportador original; PDF, redirección a pasteurizadoras y aviso de Excel no disponible en etiquetadoras permanecen iguales.

Banco `tools/benchmark-report-controller.php`, SQLite :memory:, 1001 análisis, mismo usuario/reloj/filtros, una muestra de calentamiento y tres medidas por caso/version. Cronómetro y pico terminan antes de leer el XLSX de comprobación:

| Medida | Antes | Después |
|---|---:|---:|
| Reporte general, mediana | 3267,11 ms | 1608,39 ms |
| Reporte general, SQL | 19 | 6 |
| Reporte por línea, mediana | 2450,15 ms | 1618,03 ms |
| Reporte por línea, SQL | 19 | 7 |
| Pico adicional, general | 39051160 bytes | 24364648 bytes |

Todas las hojas/celdas coinciden antes/después en las seis muestras de cada caso. Hash general `6cb7084563a1f939c775d4250988612d921f09ec6bf929ac68d76c113ffe9e66`; por línea `8c91664ca16d07886202782e07ddadf594ad2c4df1d60ce73367d9d2df193eca`. La nueva prueba compara celdas con el exportador vigente e incluye dos líneas y registros dentro/fuera de fechas. No se altera el archivo de la clase de exportación; el experimento FromGenerator descartado en 44 sigue revertido.

El banco invoca la etapa Excel del controlador, sin la notificación previa del endpoint; no se atribuyen estos tiempos a una descarga por red ni a producción. No se cambian formatos ni se añaden exportaciones asíncronas. El usuario decidió dejar esa arquitectura pendiente el 2 de noviembre.

Reversión acotada: reponer las dos asignaciones eliminadas del controlador. No requiere migraciones ni cambios de datos.
