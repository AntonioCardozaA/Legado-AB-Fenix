# Optimización 12: búsqueda disponible cuando falta una fuente

F10, cambio de código autorizado con pruebas. El modelo legado apunta a analisis y existe una migración histórica para esa tabla, pero no está presente en la base MySQL local. No hay evidencia para remapear ese modelo a otro dominio. Se explicó previamente problema, archivos, solución y riesgo.

GlobalSearchController comprueba la existencia de esa fuente solo si el usuario puede consultarla y la consulta tiene al menos dos caracteres. Si falta, devuelve los demás resultados e identifica legacy_analyses como fuente no disponible. El layout muestra un aviso explícito. Si existe, conserva su consulta y permiso originales. No se capturan indiscriminadamente excepciones SQL ni se ocultan otros errores del esquema.

Comparación local READ ONLY, mismo q=guia y límite 50, tres muestras: antes todas terminaban en SQLSTATE 42S02, después todas devuelven cinco resultados y el aviso. Tiempos hasta fallo antes: 404,81 / 310,19 / 445,55 ms; respuesta completa después: 362,10 / 498,10 / 633,23 ms. **No hay mejora de latencia demostrada**: se corrige la indisponibilidad, no se compara una respuesta útil anterior inexistente. Mantener la corrección evita que reintentar la búsqueda repita un fallo determinista.

Pruebas SQLite: fuente ausente conserva resultados de lavadora y anuncia indisponibilidad; fuente existente devuelve un análisis legado; permiso restringido lo excluye. Las otras seis pruebas de búsqueda pasaron durante la regresión. Los fixtures finales usan un término sin escapes dependientes de motor y un usuario con permisos explícitamente restringidos; los roles por defecto pueden permitir legado.

Riesgo: omitir historial sin avisar o ampliar acceso. El aviso y la consulta cuando existe mantienen visible esa distinción; no se eliminan registros ni se crea una tabla vacía. La recuperación del esquema heredado real queda pendiente de revisar migraciones y despliegue por separado.

Si se desea revertir esta corrección por el criterio estricto de latencia, se puede restaurar el merge original y retirar el aviso; volvería el fallo completo cuando falte la tabla. Se recomienda conservarla por disponibilidad y resolver el esquema en su revisión específica.
