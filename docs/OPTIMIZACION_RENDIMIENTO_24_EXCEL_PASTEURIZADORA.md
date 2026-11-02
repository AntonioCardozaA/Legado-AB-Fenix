# Optimización 24: Excel de pasteurizadora por lotes

F25, refactorización autorizada con pruebas. Se explicó previamente la colección completa de modelos, el uso de lotes de 500, los archivos y el riesgo de cambiar filas, orden, formato o snapshot.

AnalisisPasteurizadoraQueryExport usa FromQuery y WithCustomChunkSize del paquete instalado. Ambos exportadores comparten literalmente headings/map mediante MapsPasteurizadoraAnalysis. El exportador previo FromCollection sigue disponible para sus consumidores. El controlador extrae su consulta sin alterar filtros; PDF sigue materializando la colección como antes. Excel genera el archivo dentro de una transacción de lectura para mantener la instantánea en MySQL con aislamiento REPEATABLE READ. Se añade ID ascendente como desempate final para paginar fechas/created_at empatados, conservando el orden observado en el fixture y pendiente de confirmar con concurrencia real de staging.

Prueba XLSX real: 1003 filas sobre tres lotes y caso vacío, igualdad de cada celda con la exportación anterior, doce encabezados, orden completo y ceros iniciales en números de orden. Se comprueban tres lecturas principales con límite 500. Una prueba y siete aserciones aprobadas. Los archivos temporales de comparación se eliminan al finalizar.

Banco reproducible: `php tools/benchmark-export-memory.php collection` y `php tools/benchmark-export-memory.php query`. Cada proceso rechaza cualquier base que no sea SQLite :memory: antes de migrar/sembrar; no utiliza datos operativos. Mismo fixture de 5001 filas, mismo PHP 8.4.12 y límite de memoria de proceso 512M:

| Variante | Tiempo ms | Pico adicional bytes | Tamaño XLSX bytes |
|---|---:|---:|---:|
| Colección | 5625,50 | 116675072 | 239707 |
| Consulta/lotes | 4747,18 | 73440504 | 239707 |

Es una muestra por variante en procesos independientes, no percentiles. Mejora de pico adicional: 43234568 bytes (37,1 %); también menor tiempo en ese fixture. La prueba de celdas verifica equivalencia lógica: no se depende del hash binario del ZIP, que puede contener metadata temporal.

Límites: PhpSpreadsheet sigue almacenando celdas del libro; los lotes acotan modelos, no toda la memoria del archivo. El número de consultas crece por lotes, una compensación ante datasets grandes. El controlador verifica REPEATABLE READ en MySQL/MariaDB y conserva el exportador completo si no puede usar ese aislamiento o si el driver no es SQLite/MySQL/MariaDB. No cambia el aislamiento de la sesión. Deben verificarse motor transaccional y límites de worker/web en staging. No se cambiaron fotos, permisos, columnas ni exportación PDF. Colas de exportación grandes y otros exportadores quedan pendientes de perfil/operación.

Reversión: en exportExcel volver a materializar getAnalisisPasteurizadoraParaExportar y usar AnalisisPasteurizadoraExport, que conserva el mismo mapeo. No hay DDL ni cambios de registros MySQL.
