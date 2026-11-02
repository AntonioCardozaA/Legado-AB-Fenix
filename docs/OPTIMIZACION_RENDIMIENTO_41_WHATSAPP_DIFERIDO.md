# Optimización 41: WhatsApp de elongación después de responder

F09/F26. Se explicó previamente que la optimización 21 sacaba el aviso de la transacción pero aún esperaba al proveedor antes de responder. Se añadió SendElongacionWhatsApp y se cambió únicamente el despacho del mensaje ya construido. Se conservan destinatario, texto, umbrales, porcentajes originales y contexto; no se serializan Request/modelos ni se releen valores redondeados posteriormente.

El callback de DB::afterCommit existente sigue siendo la entrada: rollback no registra el envío. Modo web predeterminado after_response: se ejecuta al terminar la respuesta. Un indicador por callback evita repetirlo si una aplicación reutilizada termina dos veces. Consola/sync conservan ejecución síncrona. Modo queue es opt-in; no se cambió .env, backend, worker ni se activó una cola en producción. Configuración: ELONGACION_WHATSAPP_DISPATCH_MODE (after_response/sync/queue), ELONGACION_WHATSAPP_QUEUE (default).

El job tiene timeout 45 y tries 1, incorporado al diagnóstico de presupuesto. No se añade reintento de job al transporte, que conserva su política actual. También se conserva el manejo no fatal de excepciones y la política existente de respuestas HTTP del proveedor; no se promete entrega ni se introduce idempotencia global. Reintentos manuales siguen requiriendo criterio operativo.

Dieciocho pruebas relacionadas pasan, 87 aserciones: commit/rollback, sincronía equivalente, cero llamadas antes de terminar, mensaje idéntico después de cambiar Request/ciclo, serialización de valores originales, excepción del proveedor y terminación repetida sin segundo envío. Todo usa Http::fake; no se envió ningún WhatsApp real.

Banco de un proceso con SQLite aislado, mismo mensaje/reloj/cliente y calentamiento de ambas variantes, proveedor simulado de 100 ms, tres muestras:

| Modo | Mediana antes de terminar ms | Llamadas antes de terminar |
|---|---:|---:|
| sync | 109,13 | 3 |
| after_response | 0,08 | 0 |

Las tres llamadas diferidas consumen 328,89 ms al terminar. Seis llamadas totales entre variantes, una sola huella de payload. Se reinició la factoría HTTP antes de medir para no acumular callbacks fake; se descartaron muestras que duplicaban artificialmente la demora. Esto demuestra separación del trabajo previo a responder, no menor trabajo total ni latencia HTTP real.

Límites: after_response ocupa el mismo proceso PHP y no es durable. Su efecto en el cliente depende del SAPI/buffering y debe comprobarse en staging; si el proceso termina antes, el aviso puede perderse. La cola durable requiere revisión/activación separada, backend y worker supervisado. Reversión inmediata: ELONGACION_WHATSAPP_DISPATCH_MODE=sync y actualizar configuración por el procedimiento de despliegue aprobado, o recuperar el envío directo del callback anterior.
