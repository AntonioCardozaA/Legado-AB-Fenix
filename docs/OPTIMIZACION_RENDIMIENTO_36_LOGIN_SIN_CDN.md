# Optimización 36: retirar Tailwind CDN duplicado en guest

F19. Se explicó previamente la doble carga de CSS/compilador del login y el riesgo de cambiar tipografía o controles. Se retiró solo el script Tailwind CDN de layouts/guest.blade.php; se conserva CSS/JS Vite, plugin forms, Bootstrap, FontAwesome y el comportamiento de autenticación.

El primer prototipo sin CDN reveló diferencia en la pila font-sans: el CDN imponía ui-sans-serif mientras el build declaraba Figtree. Se ajustó tailwind.config.js a la misma pila efectiva original, sin introducir una fuente nueva. La configuración autenticada es independiente y no cambia.

Chrome con exactamente el mismo HTML del login, antes/después del recurso y de la corrección, a 1440 y 390 px: estilos calculados iguales y cero píxeles diferentes en ambas capturas. Sin excepciones JavaScript, recursos fallidos u overflow. Ya no se solicita ni ejecuta el compilador CDN. No se afirma un tiempo HTTP/Web Vitals medido.

Quince pruebas de login, recuperación, registro y confirmación pasan, 29 aserciones. npm run build pasó con el PATH de Node configurado en el proceso de prueba y generó los tres sidecars gzip; no se modificó el PATH del sistema. El primer intento sin ese PATH se descartó por no encontrar node.

Reversión: recuperar el script CDN y la declaración original de font-sans, reconstruir los assets y entregar el manifiesto junto al build. Ningún cambio en credenciales, sesiones, permisos o validadores.
