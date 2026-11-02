# Optimización 06: precarga de periodicidad por línea

F05. Antes se consultaban catálogo, análisis con relaciones e historial de resets por cada componente. Se explicó previamente problema, causa, archivos, solución y riesgo. El usuario autorizó esta refactorización con pruebas.

LavadoraRevisionPeriodicityService carga ahora una vez el catálogo de la línea (incluyendo componentes sin línea como antes), agrupa IDs por la misma normalización PHP y precarga análisis con línea/componente/historial y resets. Los cálculos existentes reciben su subconjunto por componente. No se cambia el cálculo de ventanas, lados, identidades, fechas o restablecimientos. El método de escritura resetPendientes conserva su implementación. Cada llamada obtiene datos nuevos; no hay caché entre operaciones.

Riesgos verificados: mezclar líneas/componentes, perder historial, usar un reset de otra identidad o devolver datos obsoletos después de guardar. El cálculo individual original queda accesible internamente sin contexto como referencia de equivalencia.

Banco local de solo lectura con las mismas condiciones y código antes/después, una ejecución de calentamiento y tres muestras por escenario:

| Escenario | Mediana antes ms | Después ms | Consultas antes | Después | Huella completa |
|---|---:|---:|---:|---:|---|
| Estadísticas L-04 | 283,87 | 115,72 | 42 | 6 | Idéntica |
| Estados de todas las líneas | 2297,94 | 1234,94 | 292 | 46 | Idéntica |

PHP 8.4.12 y MySQL 8.4.3, fecha fija, transacción READ ONLY y sin proveedores externos. La carga del equipo varía; no se presentan percentiles de producción.

Diez pruebas existentes de periodicidad y revisión general: 77 aserciones aprobadas. Prueba nueva compara todos los campos contra los cálculos individuales, verifica seis consultas y comprueba que una revisión guardada después se refleja al consultar otra vez. Suite de periodicidad final: siete pruebas y 37 aserciones aprobadas.

Reversión sin datos: retirar la precarga de estadisticasLinea y pasar de nuevo solo los argumentos originales a resumenComponenteLinea; sus rutas originales de consulta siguen disponibles. No requiere cambios de tablas ni configuración.
