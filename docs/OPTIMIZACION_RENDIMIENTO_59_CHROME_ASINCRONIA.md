# 59. Campana, LEF y Canvas en Chrome

`tools/browser-async-check.mjs` ejecuta los scripts reales extraídos del layout y LEF, y el archivo Canvas, con DOM, fetch, AbortController, temporizadores, visibilidad e IntersectionObserver nativos de Chrome. El servidor y los datos son sintéticos locales: no usa base operativa, credenciales, CDN ni proveedores.

Resultado en `D:\legado-browser-async-QQkbtH\results.json`:

- Campana: cero fetch inicial, una petición ante llamadas simultáneas, recuperación de HTTP 503. Se activa otra pestaña real, se comprueba `document.hidden` y se atraviesan 31 segundos: cero consultas ocultas y revalidación al volver.
- LEF: retardos iguales de 650 ms para períodos y 400 ms para datos. Transporte secuencial de referencia 1081,20 ms; paralelo 668,10 ms, datos disponibles a 405,10 ms. Las peticiones comienzan separadas 0,36 ms. Cambio rápido conserva solo línea 2 y sus fechas, sin errores anteriores ni fecha bloqueada. Son tiempos del banco, no latencia del servidor real ni comparación completa de dos versiones de la pantalla.
- Canvas a 1440/390 px: fondo dibujado una vez pausado, cero frames adicionales pausado; un solo RAF pendiente al reproducir y 17/16 dibujos en 250 ms; fuera de vista cero dibujos y posición conservada; pausa manual vuelve a cero frames. Se usan Canvas y observador reales, sin sustituir las funciones de dibujo.
- Cero excepciones JavaScript.

La primera comprobación Canvas exigía conservar el ID de RAF; el código cancela y sustituye ese ID correctamente. Se corrigió el banco para contar callbacks pendientes. No se modificó el animador para satisfacer esa expectativa equivocada.

Esta comprobación complementa las pruebas simuladas existentes. No mide CPU/GPU ahorrada, LCP/INP de campo ni integridad de una respuesta del backend operativo. Puede repetirse con Node 22 y Chrome instalados; genera artefactos y perfil aislado bajo TEMP.
