# 60. Construir la gráfica histórica cuando se aproxima a la pantalla

El perfil inicial de lavadoras muestra costo relevante en Chart.js y tareas largas. La gráfica histórica se construía aunque quedara muy por debajo de la pantalla. Se difiere solamente su construcción Canvas; tabla, cinco revisiones, controles y estadísticas se preparan como antes.

`resources/js/visible-chart.mjs` usa un observador compartido y margen de 250 px, sustituye configuraciones pendientes por la última y las cancela al cambiar a un filtro vacío. Construye inmediatamente cerca de la pantalla, sin IntersectionObserver y durante impresión; `beforeprint` vacía pendientes. El dashboard mantiene su manejo de errores original. No se difieren datos, permisos ni cálculos del backend.

`tools/prepare-visible-chart-comparison.mjs` compila ambas variantes con las mismas opciones de esbuild en TEMP. La variante anterior retira solo la envoltura diferida. `browser-performance-check.mjs` permite calentamiento, perfiles CPU y prueba de desplazamiento/filtros/impresión. Ambas páginas reciben exactamente el mismo HTML/dataset; preload y script usan la variante correspondiente.

Comparación emparejada con recursos calentados, artefacto `D:\legado-browser-check-9KhSFD`:

| Medida | Antes | Después |
|---|---:|---:|
| Muestras CPU atribuidas a Chart.js, 1440 px | 157,68 ms | 96,70 ms |
| Muestras CPU atribuidas a Chart.js, 390 px | 131,02 ms | 89,39 ms |
| Tarea larga máxima, 1440 px | 136 ms | 74 ms |
| Tarea larga máxima, 390 px | 87 ms | 63 ms |
| Gráfica histórica construida inicialmente fuera de pantalla | Sí | No |

Es una pareja de perfiles muestreados locales; no es CPU total de navegación, mediana de campo ni garantía de producción. Una primera medición fría/caliente se descartó como referencia final por sesgo de caché. La gráfica realiza su trabajo al desplazarse: no se elimina ese costo.

Chrome 1440/390: mismos labels/valores al mostrarla, filtro L-04 y restauración para imprimir correctos; tabla disponible inicialmente, DOM igual (661 elementos), cero excepciones y **cero píxeles distintos en ambas capturas iniciales**. La prueba de impresión espera la etiqueta vigente `Todas las lavadoras`; se corrigió esa expectativa del banco. Cinco pruebas unitarias del helper cubren pendiente obsoleto, impresión, filtro vacío y fallback.

Build Vite aprobado. El helper se empaqueta con el módulo de lavadoras; no añade una petición de script separada ni cambia dependencias. Reversión: construcción directa de la gráfica manteniendo el resto del módulo.
