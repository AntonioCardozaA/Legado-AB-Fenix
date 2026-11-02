# Optimización 39: una carga de Chart.js en pasteurizadoras

F19. Se explicó previamente la misma URL cargada dos veces: en el head del layout y al final de la vista, antes de inicializar gráficas. Se retiró exclusivamente la segunda etiqueta. No cambia versión, librería, datos, callbacks ni permisos; esta vista no declara without-charts y conserva la carga del layout.

Chrome real, mismo HTML sintético, 1440/390 px: Chart disponible, datasets de todas las gráficas iguales y cero excepciones/recursos fallidos. Se reduce de dos etiquetas/evaluaciones del mismo script a una; no se atribuye ahorro de transferencia porque la segunda ya podía venir de caché. No se midió una ganancia de latencia. Tres pruebas de dashboards mecánico/hidráulico pasan (40 aserciones), con comprobación de una sola etiqueta en el render.

Reversión: reinsertar esa etiqueta justo antes del bloque de inicialización. La carga redundante no se sustituye por defer ni se altera el orden de otras dependencias.
