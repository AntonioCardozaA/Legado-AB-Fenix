# 66. Filtros y páginas del directorio mediante fragmento

Los filtros GET y la paginación del directorio reconstruían el layout, la campana y el formulario de creación. Se extrae el bloque existente de filtros/listado a `admin.users.partials.directory`. El controlador atiende `X-Directory-Fragment: 1` con ese bloque y `Cache-Control: private, no-store`; sin esa cabecera devuelve la página completa. Consulta, orden, total, permisos, enlaces de edición y paginación a 25 permanecen iguales.

`public/js/partial-directory.js` actualiza solo ese bloque, registra URL con pushState y atiende popstate. Usa cancelación y número de petición; la vuelta rápida a una búsqueda ya mostrada cancela también cualquier navegación pendiente anterior. Conserva foco/cursor de controles reemplazados y respeta el foco trasladado al formulario de creación durante la espera. No sustituye ese formulario ni sus datos escritos.

El layout delega únicamente filtros marcados del directorio. El script intercepta solo sus enlaces de paginación/limpieza, dejando edición, POST, enlaces externos y clics modificados con su comportamiento normal. Si falta el script/API, la navegación completa sigue disponible. Respuesta inválida, HTTP fallido o redirección requieren documento completo; no se ejecutan scripts recibidos en fragmentos. Cada petición sigue atravesando autorización y sesión existentes; no se introduce caché de permisos ni cambios CSRF.

## Rendimiento y equivalencia

`tools/benchmark-directory-fragment.php`: misma versión, usuario, reloj, 165 usuarios sintéticos y primera página de 25; calentamiento y tres muestras por modo. Ejecución final sin Chrome ni pruebas concurrentes: documento completo mediana **80,91 ms**, fragmento **31,63 ms**; **294508 → 84700 bytes** de HTML. Se comparan modos completo/parcial dentro del mismo banco, no datasets generados en ejecuciones distintas. Son preparación/render del controlador y HTML sin gzip, no navegación total, INP/LCP ni latencia de producción. Una corrida concurrente con Chrome se descarta como referencia temporal.

La cabecera aún no tenía efecto antes del cambio: respondía con todo el documento. La versión inicial del banco midió 293881 bytes completos en ambos modos; los correos de los fixtures se generan de nuevo entre procesos, por lo que esos bytes no se presentan como una comparación exacta entre versiones. Las pruebas HTTP comparan el DOM completo del bloque, IDs y total con/sin cabecera en cinco escenarios (filtros, segunda página, vacío y rol).

Chrome 1440/390, HTML real generado en SQLite y servidor de fixtures con retardos locales: `D:\legado-browser-directory-check-ENLDvf\results.json`. Prueba mismo documento, creación sin perder texto, foco, atrás/adelante normal y rápido, respuesta anterior retrasada, paginación, Ctrl+clic, fallback sin helper, fragmento inválido y redirección a login. Cero excepciones JavaScript. La autorización real se verifica en PHPUnit: invitado redirigido y administrador cuyo rol se retira recibe 403 aunque envíe la cabecera.

El último ajuste ubica el anuncio accesible dentro del bloque, sin añadir separación al contenedor exterior. Después pasan de nuevo 14 pruebas relacionadas/137 aserciones y Chrome con fixtures regenerados. No se afirma comparación de píxeles del directorio ni se intercepta la navegación global del sistema.

Reversión: dejar de delegar en el layout o retirar el script; filtros/paginación vuelven al documento completo. El servidor puede conservar la vista parcial sin que los clientes antiguos la soliciten.
