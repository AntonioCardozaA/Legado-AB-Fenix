# Optimización 16: codificación sin pérdida de logos y favicon existente

F06. Se explicó antes de editar el peso global, la alternativa WebP con PNG de respaldo, archivos y riesgo de transparencia/compatibilidad. No se retocan imágenes ni se modifica su composición, resolución o secuencia.

`tools/optimize-brand-assets.php` codifica WebP lossless, comprueba dimensiones y cada píxel visible y su alfa antes de guardar. El RGB bajo píxeles completamente transparentes cambia por la codificación; no cambia su transparencia ni su apariencia. Los originales permanecen intactos. Las variantes tienen hash en el nombre y se sirven con picture/source; el img original es fallback. Los wrappers usan display:contents y conservan clases y temporización de los cuatro frames.

| Logo | PNG original bytes | WebP bytes | Resolución |
|---|---:|---:|---|
| 1 | 2488201 | 1555788 | 1254×1254 |
| 2 | 2508543 | 1590358 | 1254×1254 |
| 3 | 2029677 | 1534618 | 1254×1254 |
| 4 | 2424491 | 1528768 | 1254×1254 |
| Total | 9450912 | 6209532 | Sin cambio |

Reducción comprobada de 3241380 bytes (34,3 %) para navegadores compatibles con WebP. No se midió tiempo de descarga ni CPU real de navegador. La ocupación de memoria de píxeles no baja porque se conserva resolución.

El favicon deja de apuntar al logo1 de 2488201 bytes con query de versión y usa el icon-192.png existente de 68833 bytes. Los enlaces 192/512, Apple y manifest se conservan. Se revisó visualmente el icono existente y el logo original/variante; el verificador de píxeles es la comprobación determinista de equivalencia visible, independiente del fondo usado por el visor.

Siete pruebas funcionales de layout/formulario/campana: 36 aserciones aprobadas. No se ha realizado comparación de capturas de escritorio/móvil en navegador real; queda esa comprobación de integración visual.

Revertir requiere retirar los picture/source y restaurar los dos enlaces de favicon. No hace falta tocar los PNG ni datos. Las variantes se pueden mantener sin uso; no se eliminan originales.
