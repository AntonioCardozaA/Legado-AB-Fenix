# Optimización 15: usar el autoload estándar

F27. Se explicó previamente el registro de un fallback temporal prepend en bootstrap/app.php y sus búsquedas is_file por clase. Antes de retirarlo se verificó que el ClassLoader de Composer encuentra Application, Collection y Str, y que carga los nueve archivos de funciones/helpers incluidos manualmente por el fallback.

Se retira únicamente ese bloque temporal. Composer permanece como fuente del autoload y no se regeneran dependencias ni caches de producción. Se mantienen los mismos 602 archivos incluidos al arrancar; los autoloaders pasan de cuatro a tres.

Cinco procesos CLI independientes por variante, mismo PHP 8.4.12, caché/sesión array, logging null, IA deshabilitada en el proceso y sin invocar rutas ni proveedores:

| Variante | Muestras de arranque ms | Mediana ms |
|---|---|---:|
| Con fallback | 847,01 / 1007,76 / 645,50 / 770,36 / 665,35 | 770,36 |
| Composer estándar | 650,82 / 629,74 / 686,67 / 626,96 / 712,57 | 650,82 |

La variación de procesos y carga impide atribuir toda la diferencia exclusivamente al fallback. La evidencia firme es retirar búsquedas redundantes manteniendo las mismas inclusiones y arranque válido. No se infiere rendimiento de OPcache/SAPI web a partir de CLI.

Dieciséis pruebas de estados vigentes, formulario rápido, búsqueda, contexto de chat y observabilidad: 113 aserciones aprobadas con el autoload estándar.

Riesgo: un vendor incompleto que dependiera del fallback ya no arrancaría. La validación de Composer/lock y el autoload estándar deben formar parte del despliegue. Si staging detecta clases/helpers ausentes, restaurar el bloque inicial de bootstrap/app.php e investigar la instalación. La optimización no exige migrar datos ni activar colas.
