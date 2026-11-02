# Optimización 38: módulo estático de paneles de lavadoras

F21. Se explicó previamente la repetición del bloque JS en HTML y los riesgos de orden/permisos. Se extrajo el segundo bloque principal completo (aproximadamente 129 KB) a resources/js/dashboard-lavadora-panels.js y se añadió entrada de Vite. El primer bloque de compatibilidad, LEF y costos conserva su posición y comportamiento.

Los mismos datos se serializan en lavadoraPanelsData (application/json) con @json y son leídos por el módulo. La única rama de permiso de ese bloque se representa como planesUrl: se calcula con la misma canViewPlanActionType; queda null cuando no está permitido y el enlace se genera solo cuando existe. Servidor y middleware siguen aplicando las mismas reglas; no hay permisos cacheados ni datos nuevos.

Build aprobado: dashboard-lavadora-panels-C_dCV7dx.js, 42239 bytes (aproximadamente 10,99 KB gzip). Fixture con dos líneas y estados distintos, exactamente el mismo HTML/payload salvo separación del código: 264353 → 135650 bytes. Primera visita añade módulo cacheable y visitas posteriores pueden reutilizarlo. No se presenta esto como medición de LCP, parseo o latencia.

Chrome real, 1440/390 px, fuentes cargadas: estilos y capturas idénticos (cero píxeles distintos), sin errores JS o recursos fallidos. Se compararon labels/datasets de todas las gráficas, estado inicial, los cuatro filtros de severidad y el enlace de planes: resultados idénticos. El módulo define los mismos hooks globales antes de DOMContentLoaded; no se difiere la creación de gráficas ni cambia su orden.

Nueva prueba funcional: 22 aserciones de payload completo por sección y de URL permitida/null cambiando el permiso real del usuario con middleware activo. Las 26 pruebas frontend pasan. La comprobación inicial del test usaba lineaOptions como dato del controlador, pero se construye en Blade; se corrigió para comparar su derivación real desde lineasLavadora. Un primer script del banco no delimitaba correctamente el objeto JS y se descartó sin producir validación; se repitió con delimitación de llaves/cadenas.

Reversión: devolver el bloque JS original al mismo lugar y retirar el payload/entrypoint. Debe entregarse el build y manifiesto nuevos antes de activar código. Se conservan las funciones de compatibilidad para no eliminar funcionalidades; su retirada no forma parte de esta optimización.
