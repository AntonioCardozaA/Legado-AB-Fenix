# Optimización 50: catálogo hidráulico sin consultas por fila

Tras paginar (49), el render aún hace 60 SQL en listado y 65 en detalle. AnalisisCentralHidraulica.cantidad_display consulta es_contabilizable en su configuración; la rama sin relación cargada consulta codigo del componente repetidamente. Se explicó el cambio antes de editar: anticipar esa relación en las lecturas de historial/detalle del controlador, sin cambiar los modelos ni el cálculo de cantidades.

Se carga configuracion.componente con id/codigo. La selección reducida es intencional: el accessor original sin relación seleccionaba solo codigo y trataba todo componente distinto de aceite como contabilizable, incluso con bandera contabilizable=false en el catálogo. Cargar todas las columnas cambiaría esa regla. La relación componente del análisis conserva sus columnas completas y los nombres siguen iguales. No se cambian lecturas de captura/index/export ni se crea caché entre peticiones.

Mismo banco, fixture y condiciones de 49; comparación inmediatamente anterior a este cambio (tres muestras calientes) frente a posterior:

| Caso | Antes | Después |
|---|---:|---:|
| Listado, SQL | 60 | 11 |
| Listado, mediana ms | 82,25 | 80,31 |
| Detalle, SQL | 65 | 15 |
| Detalle, mediana ms | 85,23 | 62,82 |

HTML idéntico: listado 159445 bytes, SHA256 b78d72ff2414396047b8fa987668070bb2c9f9acaf54318331c29de371c56343; detalle 129155 bytes, SHA256 2996acbafcdafeff3b7f0637b46893525cc0a9ba2f22251270f0671207731e60. Se mantienen 25 modelos visibles y totales 501/251. El tiempo del listado apenas mejora y tres muestras no demuestran significación estadística; el beneficio demostrado es la eliminación de consultas repetidas y la reducción de trabajo de base, no un porcentaje garantizado de latencia.

Nueve pruebas del módulo pasan, 159 aserciones. La nueva cubre aceite con unidad/cantidad, componente no aceite con bandera false, configuración ausente y presupuesto de consultas de catálogo que no crece por fila. Las demás recorren todos los IDs, filtros, lados, evidencias y acceso. Reversión: retirar las tres cargas anidadas, manteniendo la paginación ya comprobada. No requiere esquema ni activación operativa.
