# 58. Comprobaciones repetidas de avisos de elongación

Problema: los destinos simples ya se consultaban por lotes, pero cada aviso de elongación repetía la lectura del registro, ciclo vigente, último registro y vencimiento, incluso cuando otros avisos contenían el mismo destino.

En `NotificationVisibilityService::filterExistingTargets` se reutiliza el resultado de la validación original únicamente durante esa lectura. La clave conserva tipo, ID y detalles con sus tipos originales; el texto del aviso no influye. No se reemplazan las reglas del modelo ni se comparte caché entre peticiones. Los avisos conservan orden e identidad; no se deduplican ni eliminan.

SQLite en memoria, reloj fijo 2026-10-31, mismos 224 avisos, ambas rutas calentadas: **768 → 96 consultas**, muestra emparejada **567,48 → 88,14 ms**, mismos IDs ordenados. La comparación usa el método individual original como oráculo. La prueba de reducción falla antes del cambio con 768 consultas en ambas rutas.

La cobertura incluye ciclos cerrados, dos activos, desempates por ID, inexistentes, IDs no canónicos, recordatorios por línea/fecha, detalles mixtos y vacíos. Otra lectura observa nuevas revisiones/ciclos y cambios de reloj/configuración; incluye fecha nula. Las 41 pruebas relacionadas aprueban 181 aserciones.

Límite: se reduce trabajo repetido, no se promete una consulta constante para destinos especiales distintos. Una escritura concurrente durante la misma lectura puede verse en la lectura siguiente; no se incorpora una transacción de snapshot nueva. Reversión: retirar solo el mapa local y su rama, conservando las mejoras anteriores por lotes.
