# Optimización 46: JavaScript estático de pasteurizadoras

F21. Se explicó previamente la separación del script y el riesgo de romper funciones globales, áreas o permisos. Se trasladó el bloque completo a resources/js/dashboard-pasteurizadora.js con entrada de Vite. Los trece conjuntos de datos mantienen exactamente sus ternarios de mecánica/hidráulica en Blade. Las rutas mantienen sus condiciones originales y son null cuando no están permitidas. El canvas condicional conserva su ID/vacío original.

Se conservan como propiedades de window refreshData, showPasteurizadoraDetail, showCentralHidraulicaDetail y closeModal, porque los controles existentes usan esos nombres. No se cambia el comportamiento de gráficos, modales, temporizadores ni consultas. El JSON mantiene escapes de HTML/comillas; solo permite barras legibles en rutas. No hay caché de datos privados ni permisos.

Build aprobado: dashboard-pasteurizadora-ArhOH6Zk.js, 32344 bytes, 7558 bytes gzip generado. HTML real con mismo emisor/reloj/datos, una falla y un plan por área:

| Dashboard | HTML antes | Después |
|---|---:|---:|
| Mecánica | 203462 | 144156 |
| Hidráulica | 207418 | 147416 |

La reducción corresponde a código repetido en el documento; la primera visita añade un módulo cacheable. No se afirma menor CPU ni Web Vitals medidos.

Cuatro pruebas PHP pasan, 122 aserciones, incluidas las existentes de ambas áreas y una nueva que compara los trece campos/contratos y URLs con planes permitidos/restringidos. Se corrigió una referencia del test: Blade construye la colección hidráulica desde estadoCentralHidraulicaPasteurizadora, no desde el nombre de la colección local de la vista.

Chrome real: ambas variantes consecutivas en la misma sesión/perfil aislado, a 1440/390 px. Se compararon datasets de todas las gráficas, apertura/cierre, texto y enlaces de los modales de una falla mecánica y otra hidráulica. Todo coincide; los controles funcionan y no se reportan excepciones JS ni fallos de red en la ejecución final. Estilos calculados iguales.

El banco se corrigió para redirigir también localhost escapado dentro del JSON de imágenes. Una ejecución sin fuentes terminadas se descartó. Una comparación capturada durante animación difería en 116 píxeles; se repitieron ambas variantes fijando Chart en su estado final (solo en el banco). Resultado final: cero píxeles distintos en las cuatro comparaciones. No se ha modificado la animación de producción.

Reversión: recuperar el bloque inline anterior y retirar payload/entrada Vite. Entregar módulos, gzip y manifiesto juntos antes de activar código. El bloque de compatibilidad de lavadoras conserva su estado anterior; no se elimina por asumir que está en desuso.
