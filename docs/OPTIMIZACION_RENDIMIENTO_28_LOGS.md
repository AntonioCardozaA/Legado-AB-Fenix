# Optimización 28: diagnóstico mínimo al capturar lavadoras

F33. Antes de editar se explicó el formulario completo en logs y mensajes intermedios redundantes. Solo se modificó AnalisisLavadoraController: apertura rápida y comienzo del guardado pasan a debug con IDs/código/reductor; se retiran diagnósticos separados de línea/componente. No se toca AnalysisDeletionLogger, creación exitosa, validación fallida, advertencias, excepciones ni estado de WhatsApp.

Comparación con el mismo formulario sintético (actividad de 200 frases), mismo Monolog LineFormatter y reloj fijo, nivel debug habilitado, sin escribir logs: tres registros/6089 bytes antes, uno/146 bytes después. A nivel info el diagnóstico ya no se escribe. No se ha cambiado LOG_LEVEL ni .env. No se atribuye una reducción medida al tiempo total de guardado.

Tres pruebas funcionales de formulario rápido/guardado pasan (13 aserciones), además de la ejecución general documentada por separado. Riesgo: menos detalle de diagnóstico intermedio; los identificadores mínimos y eventos de éxito/error permiten correlacionar. Reversión: recuperar únicamente esas llamadas de Log; sin efecto en datos ni reglas.
