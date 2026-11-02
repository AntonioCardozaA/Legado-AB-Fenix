# Optimización 13: leer únicamente la ventana efectiva del chat

F29, cambio de código autorizado con pruebas. Se explicó previamente que el controlador hidrataba toda la conversación y OperationsAssistantService::sanitizeHistory ya limitaba las entradas a history_window (ocho por defecto), antes de sanearlas.

AssistantChatController consulta ahora los últimos N IDs del mismo usuario y conversación, excluye el mensaje actual y revierte la colección para conservar orden cronológico. Usa exactamente el mismo max(1, history_window) del servicio. No se baja la ventana, no se cambian prompts ni se filtran roles antes del límite. La presentación de mensajes y la política previa de conservación no se alteran.

Prueba equivalente antes/después con veinte mensajes y ventana tres: antes se hidrataban/pasaban veinte entradas al servicio, después tres, exactamente las últimas y en el mismo orden. Se conservan veintidós mensajes tras pregunta/respuesta y el mensaje de otra conversación permanece aislado. El proveedor se simula; no se realizan llamadas externas.

Ocho pruebas de ventana, envío, conversaciones y referencias de seguimiento: 93 aserciones aprobadas. Se demuestra menor volumen leído y equivalencia del contexto efectivo, no menor latencia del proveedor. Con el límite de conservación predeterminado de treinta, el ahorro habitual es acotado; no se presenta como el cuello dominante del sistema.

Riesgo: pérdida de referencia conversacional o historial. Se mantiene el mismo subconjunto que consumía el servicio y se comprueba orden/aislamiento/conservación. Reversión: restaurar oldest/get y retirar limit/reverse/values de esa consulta. Sin DDL ni cambios de datos.
