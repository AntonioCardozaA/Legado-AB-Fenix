# Optimización 20: suspender trabajo de diagramas inactivos

F32. Se explicó antes de editar que el Canvas de ejemplo redibujaba incluso pausado y que SVG mantenía intervalos ocultos. Se conservan geometría, velocidad, colores y controles; Canvas corresponde al componente de ejemplo, no sustituye los diagramas SVG de operación.

Canvas conserva el ID de requestAnimationFrame, cancela bucles previos, dibuja una vez al pausar/reiniciar y programa frames solo cuando reproduce y está visible. IntersectionObserver y visibilitychange suspenden/reanudan conservando desplazamiento. Inicializar una instancia reemplaza y desconecta su observador anterior. Un fondo inicializado oculto se dibuja al volver aunque esté pausado.

SVG conserva los índices de color al suspender y retomar el intervalo. Un observador de intersección compartido y visibilitychange controlan la suspensión. CSS pausa sus animaciones y SMIL se pausa/reanuda respetando la pausa manual. Recalcular tras mutaciones no duplica intervalos; ocultar no altera las severidades ni las coordenadas.

Archivos: ambos scripts de diagramas, CSS de lavadoras, controles del componente Canvas y dos archivos de pruebas frontend. Riesgo: bucles duplicados, pérdida de posición, secuencia de color o pausa manual; cubierto por seis pruebas de ciclo de vida con reloj/DOM/red de animación simulados.

Comparación Canvas con las mismas 60 oportunidades de frame: antes pausado dibujaba 61 veces (incluido inicial), después una. Dos llamadas de inicio antes dejaban dos callbacks pendientes; después uno. Documento oculto deja de avanzar/dibujar, vuelve desde el mismo desplazamiento. SVG oculto/fuera de viewport conserva cero intervalos activos y retoma uno al volver; colores y pausa manual se verifican.

Se demuestra trabajo programado evitado, no ahorro medido de CPU/GPU ni ausencia de fugas en navegador real. Queda revisión visual escritorio/móvil y perfil de integración. Con navegación completa, la descarga del documento limpia sus observadores; no se introduce navegación parcial ni se afirma una fuga persistente previa.

Reversión: restaurar los scripts, reglas de pausa y dos llamadas animate en controles. No cambia datos, estados de mantenimiento ni permisos.
