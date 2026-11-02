# 63. Bloqueo y recuperación de envío de pasteurizadora

El formulario normal no usaba la protección común de lavadoras. El rápido deshabilitaba su botón y cambiaba texto/clases después de validar, pero no restauraba esa presentación al volver atrás.

Ambos formularios usan `prevent-duplicate-submit.js`, después de sus validadores existentes por propagación de submit. El rápido conserva el spinner/texto `Guardando...` mediante la opción `data-submit-loading`; el helper captura y restaura disabled, HTML, clases y aria-busy en pageshow. No se cambia la validación de imágenes, checklist, numeración, rutas ni guardado. Los formularios de lavadora siguen usando el helper sin activar la presentación opcional.

Chrome real, endpoints sintéticos, `D:\legado-browser-async-2MWZ1Q`: dos `requestSubmit` inmediatos admiten dos envíos sin guard y uno con guard; validación rechazada no bloquea; volver atrás restaura botón `Save`, clase original y estado habilitado. En esa corrida Chrome recargó el documento (`bfcache=false`); no se presenta como comprobación de BFCache real. La prueba unitaria pageshow existente cubre la restauración con estado conservado.

La suite frontend completa aprueba **32/32 pruebas**, incluida presentación opcional y recuperación; la cobertura HTTP de numeración comprueba que ambos formularios entregan el helper y atributos esperados. No se hicieron escrituras de negocio ni peticiones a proveedores desde Chrome.

Límite explícito de F28: este guard no es idempotencia durable de servidor ni impide un cliente que envíe POST directamente. No se deduplica por fecha/componente/importe porque dos inspecciones iguales pueden ser legítimas. Una garantía durable requiere clave de operación, persistencia atómica con la escritura, replay autorizado y pruebas de concurrencia/fallo; esquema e infraestructura siguen sujetos a revisión separada.

Reversión: retirar los atributos/script de ambas vistas y restaurar el bloque visual previo del formulario rápido; conservar el helper de lavadoras.
