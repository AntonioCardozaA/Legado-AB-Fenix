# Optimización 21: aviso de elongación posterior al commit

F09, cambio de código autorizado con pruebas. Se explicó previamente que la llamada HTTP y sus reintentos se ejecutaban dentro de DB::transaction, manteniendo abierta la transacción de captura.

ElongacionController registra ahora ese mismo envío con DB::afterCommit. Captura los porcentajes originales y el ciclo; no usa valores redondeados de una relectura. El método que genera mensaje, destinatario, umbrales y entrega conserva su implementación. Un rollback descarta el callback.

Comparación SQLite/HTTP simulado bajo el mismo fixture: antes el proveedor se invocaba con nivel de transacción 2 (1 del aislamiento de PHPUnit y 1 de la operación); después con nivel 1, fuera de la transacción de la operación. La prueba comprueba un único envío y un registro guardado. Otra prueba revierte una transacción exterior tras completar la operación: cero envíos y cero registros persistidos.

No se mide una reducción del tiempo total de respuesta: con conexión síncrona, WhatsApp aún ocupa el proceso después del commit. La mejora demostrada es cero llamadas al proveedor mientras la transacción de la operación permanece abierta y ningún aviso de un registro revertido. La cola real y su activación en producción requieren la revisión separada acordada.

Riesgo: enviar tras rollback o cambiar el mensaje. Se verifica commit/rollback y se conserva literalmente la construcción del mensaje y sus parámetros previos. Los fallos del proveedor siguen registrándose por el método existente. No se envió ningún WhatsApp real ni se alteraron datos MySQL.

La regresión ampliada detectó además ElongacionChainInstallationCostTest línea 64 redirigiendo a login en una secuencia de grupos; queda registrado para control separado, sin atribuirlo automáticamente al cambio.

Reversión: restaurar la llamada directa dentro de la transacción; no requiere datos o configuración, pero volvería a mantener bloqueos durante la espera HTTP.
