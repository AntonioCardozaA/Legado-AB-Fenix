# 62. Solicitar embedding cuando existen candidatos autorizados

`AssistantKnowledgeSearchService` pedía el vector antes de comprobar permisos y candidatos. En un corpus vacío o sin fragmentos accesibles, la llamada no podía contribuir al resultado.

Se sustituye la resolución anticipada por una closure local a esa búsqueda, resuelta tras obtener fragmentos candidatos. Ambos módulos comparten el mismo resultado, incluso si el proveedor devuelve vacío o falla. No se comparte caché entre usuarios/peticiones, ni se reducen candidatos, contexto, puntuaciones o fuentes. Para consultas sin tokens léxicos se conserva la comprobación semántica previa original.

Prueba reproducida antes del cambio: corpus vacío, un proveedor falso que espera 100 ms, una llamada, 160,30 ms. Después: cero llamadas, 32,99 ms en la ejecución con las pruebas relacionadas. Hash de resultado vacío idéntico: `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945`. Son muestras con proveedor sintético, no ahorro medido del proveedor real.

49 pruebas de chat, ventana, grounding y recuperación híbrida incluidas en la regresión final pasan, 503 aserciones. Cuatro casos nuevos verifican ausencia de llamada sin candidatos/permisos, un vector compartido con puntuación semántica 1,0 y presencia explícita de ambos módulos, nuevas búsquedas tras retirar documentos, fallback léxico con puntuación semántica 0 ante fallo del proveedor y recuperación semántica sin tokens léxicos. El fixture restringe el acceso con el permiso configurable vigente y comprueba explícitamente la denegación; no se modifica autorización del producto.

La calidad técnica y latencia de un proveedor real requieren documentos/preguntas de evaluación y entorno autorizado. Esta mejora no afirma resolver esas mediciones. Reversión: resolver de nuevo el vector al inicio de `search` y pasar el array como antes.
