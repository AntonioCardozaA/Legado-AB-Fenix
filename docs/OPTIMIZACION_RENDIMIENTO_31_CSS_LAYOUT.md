# Optimización 31: CSS común como recurso versionado

F21. Se explicó antes de editar el bloque de unas 550 líneas repetido en cada documento. Se trasladaron exactamente sus reglas a public/css/layout-app.css; el enlace ocupa la misma posición de cascada y lleva SHA-256 abreviado en la URL. No cambia Tailwind ni las reglas por página.

Chrome real con perfil temporal, fixtures SQLite :memory: y servidor HTTP estático local que rechaza escrituras: formulario rápido, historial, diagramas L04/L09 y dashboard, a 1440 y 390 px. Se reutilizó exactamente el mismo HTML salvo sustituir el bloque CSS por el enlace. Los estilos calculados de elementos relevantes coinciden en las ocho comparaciones, sin excepciones JS, sin recursos fallidos y sin desbordamiento horizontal. Se inspeccionó captura móvil del formulario.

Comparación de capturas: cero píxeles diferentes en formulario/diagramas/dashboard a ambos tamaños. Historial difiere en 18/20 píxeles de 1.440.000/390.000; sus estilos calculados coinciden. No se afirma identidad de píxeles en esa vista. Las animaciones se congelaron para comparar presentación; esto no mide su CPU durante funcionamiento.

CSS: 15174 bytes fuera de cada HTML. El servidor sintético con Cache-Control explícito transfirió 15474 bytes en primera petición (incluye overhead CDP) y cero desde caché en las siguientes siete. Esa política del servidor de prueba no demuestra que Apache/producción ya tenga la misma caché: F20 se revisa por separado. Se conserva la misma descarga total CSS en primera visita, añadiendo una petición; el beneficio es su reutilización posterior. Diez pruebas funcionales pasan, 109 aserciones.

Herramientas reproducibles: tools/generate-browser-fixtures.php y tools/browser-performance-check.mjs. El primer script verifica SQLite en memoria antes de crear datos; el segundo sirve solo fixtures/public, bloquea escrituras HTTP y usa perfil Chrome separado. Artefactos/capturas se guardan en TEMP; no se abre un perfil del usuario ni se toca la DB local.

Riesgo: orden de estilos/caché obsoleta. Se preserva ubicación y se versiona por contenido. Reversión: reinsertar el bloque CSS en el mismo punto del layout y retirar su enlace.
