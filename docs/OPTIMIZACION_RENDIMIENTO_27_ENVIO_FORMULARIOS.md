# Optimización 27: evitar doble envío desde formularios

F28. Se explicó previamente que normal y rápido admitían envíos repetidos mientras respondía el servidor. Se añadió un script compartido y un atributo en ambos formularios. El listener actúa después de los validadores existentes, conserva el primer envío, rechaza el siguiente y deshabilita sus botones con aria-busy. pageshow restaura los estados previos, incluido un botón que ya estuviera deshabilitado.

Las 26 pruebas frontend pasan, incluidas cuatro nuevas: doble envío inmediato, validación que impide enviar, restauración con retroceso/avance y otros formularios intactos. Bajo la misma simulación de dos submit consecutivos, se admiten dos peticiones antes y una después. No se atribuye una mejora al tiempo de una petición individual.

Límite: previene duplicados de interacción en estas vistas; no es idempotencia del servidor, ni cubre clientes externos, form.submit() directo o reintentos tras perder la respuesta. Esa garantía requiere diseño de clave/reconciliación y revisión del esquema, fuera de este cambio. Verificación de navegador real pendiente. Reversión: retirar el script y los dos atributos; ningún dato cambia.
