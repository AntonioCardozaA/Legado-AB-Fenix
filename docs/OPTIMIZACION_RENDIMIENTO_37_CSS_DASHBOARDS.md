# Optimización 37: CSS de dashboards como assets de Vite

F21. Antes de editar se explicó la repetición de aproximadamente 78 KB y 60 KB de CSS estático en los dashboards de lavadoras y pasteurizadoras. Se trasladaron sus reglas completas a resources/css/dashboard-lavadora.css y dashboard-pasteurizadora.css, con dos entradas adicionales en vite.config.js. Cada vista enlaza su hoja en el mismo punto que ocupaba style. No cambia JavaScript, payload, orden de consultas, gráfica, temporizador o permiso.

El build minifica/versiona y genera gzip: dashboard-lavadora-BmTTjWUS.css, 49,43 KB (9,34 KB gzip estimado); dashboard-pasteurizadora-lsVVYkep.css, 38,74 KB (7,38 KB gzip estimado). Ya no se repite ese CSS en cada HTML; primera visita añade una petición cacheable. npm run build pasó con cinco sidecars de texto (559047 → 93724 bytes de cuerpo en total). No se atribuye ahorro de latencia ni de CPU medido.

Comparación Chrome real con el mismo HTML sintético salvo los enlaces CSS, a 1440/390 px. La primera captura de lavadoras de escritorio se tomó antes de terminar las fuentes de iconos y se descartó: mostraba cuadrados en la referencia. Se añadió espera explícita de document.fonts.ready y fallo del banco ante timeout, y se repitieron ambas variantes. Resultado final: cuatro comparaciones con estilos relevantes idénticos y cero píxeles distintos, sin errores JS, recursos fallidos u overflow horizontal. Se inspeccionaron las capturas de escritorio para localizar la diferencia de fuentes.

Trece pruebas de estados, historial, tendencias y dashboard pasteurizador pasan, 90 aserciones. El fixture de pasteurizadora usa admin porque el técnico recibe la redirección de acceso original; no se modificó ese control para probar la vista.

Reversión: reinsertar los bloques originales en los mismos puntos y retirar sus @vite. Build obligatorio antes de despliegue con entradas/manifiesto nuevos. La extracción/diferimiento del JavaScript queda separada: exige pruebas de las interacciones y permisos incrustados en esos scripts; no se declara resuelta por este cambio de CSS.
