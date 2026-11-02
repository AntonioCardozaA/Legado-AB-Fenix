# Optimización 09: solicitudes de periodos 52/12/4

F23. Se explicó antes de editar que loadPeriods bloqueaba cambios rápidos con un flag y disparaba la consulta de datos solamente en finally. El riesgo era aplicar fechas de otra línea o sobrescribir gráficas con respuestas viejas.

La vista `analisis-52-12-4/index.blade.php` asigna ID y AbortController a cada solicitud de periodos, cancela la anterior y descarta resultados/errores obsoletos. Limpia los límites antiguos y permite consultar datos en paralelo, después de preparar los filtros. Una respuesta de periodos ya no vuelve a consultar datos. También se descartan errores de datos que pertenecen a solicitudes anteriores.

La fecha queda temporalmente deshabilitada mientras se recuperan sus límites, y se habilita tanto ante éxito como ante fallo actual. El comportamiento de selección de línea, periodo, análisis y limpieza de fecha se conserva; no se cambia el backend ni sus permisos.

Validación: tres pruebas nuevas de red simulada, más la regresión existente de selector de gráficas, aprobadas. Se comprueba que datos comienza antes de resolver periodos y que solo hay una consulta de cada tipo; dos cambios rápidos conservan exclusivamente resultados de la última línea incluso si la cancelación llega tarde. Los errores obsoletos no borran el dashboard actual.

Comparación de dependencia de red bajo las mismas respuestas: antes, inicio de datos requería completar periodos; después, ambas peticiones empiezan en la misma operación. Se elimina esa espera secuencial. No se midió una mejora en milisegundos con navegador real y no se atribuye a esta prueba un LCP/INP.

Reversión: restaurar loadPeriods y retirar sus dos campos de estado; no hay cambios de datos o esquema.
