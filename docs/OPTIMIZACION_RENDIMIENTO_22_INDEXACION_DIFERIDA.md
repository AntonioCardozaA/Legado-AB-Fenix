# Optimización 22: agrupar indexación tras la respuesta

F08/F24. Refactorización de tareas autorizada con pruebas. Se explicó previamente que alta y guardado posterior de evidencias disparaban indexaciones separadas, y que una cola sync ejecuta su trabajo durante el guardado.

MaintenanceHistoryIndexer usa ahora MaintenanceHistoryDispatcher. Si el modo elegido es after_response y la petición es web, registra el trabajo después del commit y lo agrupa en atributos de esa petición por módulo/tipo/ID. Al terminar la respuesta ejecuta el último trabajo de cada fuente. Un borrado posterior sustituye una indexación pendiente. Un rollback no incorpora su trabajo al grupo. La agrupación no vive en un singleton, variable estática ni caché compartida.

El modo específico AI_HISTORY_INDEX_DISPATCH_MODE puede fijarse a queue o after_response; si no se configura, toma maintenance_ai.dispatch_mode, que ya seleccionaba after_response local y queue fuera de local. En consola y modo queue se conserva el despacho normal afterCommit hacia la conexión configurada. No se modifica .env ni QUEUE_CONNECTION y no se activa una cola de producción.

Pruebas con SQLite y proveedor simulado: dos guardados inmediatos producen dos embeddings; los mismos estados finales agrupados producen cero llamadas antes de terminar la respuesta y una después. Las huellas content_hash finales coinciden. Se incluye cambio de actividad y evidencia, sin depender de archivos reales. Borrado, rollback y dos peticiones con el mismo ID comprueban la última operación y aislamiento. Tres pruebas nuevas, diez aserciones; otras diecisiete pruebas de IA/índice de pasteurizadora pasaron en la regresión.

La comparación demuestra trabajo de proveedor evitado y retirado del camino anterior a la respuesta en ese modo, no una mejora de milisegundos contra un proveedor real. No hubo llamadas externas ni escrituras MySQL. La compresión y disponibilidad de fotos se conservan: solo se agrupa su indexación.

Riesgos y límites: after_response sigue ocupando el proceso PHP y no ofrece la durabilidad de una cola supervisada si el proceso termina abruptamente. Las excepciones se reportan y ejecutan failed del job; una revisión de despliegue debe elegir cola durable para producción. El modo queue con conexión sync sigue siendo síncrono y no se presenta como resuelto por cambiar un nombre de cola. No se altera esa conexión sin la autorización separada acordada.

Reversión inmediata del comportamiento web: configurar AI_HISTORY_INDEX_DISPATCH_MODE=queue y reconstruir configuración según el procedimiento de despliegue. Reversión de código: restaurar dispatchIndex/dispatchDelete originales. No requiere cambios de datos o esquema; no se borran chunks.
