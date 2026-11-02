# Optimización 32: Chart.js solo si la vista lo necesita

F19. Se explicó previamente la carga global y se verificó ausencia de Chart en los tres formularios de alta normal, rápida y edición de lavadoras, así como en parciales compartidos. Esas vistas declaran without-charts; el layout conserva la carga global para las demás. No se usa defer ni se altera el orden de gráficas existentes.

Chrome real con los mismos fixtures, a 1440/390 px: formulario sin Chart y sin excepciones; dashboard con Chart disponible y sin excepciones. Sin recursos fallidos ni overflow horizontal. Referencia previa: recurso Chart.js de 210082 bytes descomprimidos y 61712 bytes transferidos en primera navegación; después no se solicita en los formularios indicados. El caché ya evitaba transferencia en visitas calientes, pero seguía evaluándose la biblioteca. No se convierte ese peso en una garantía de tiempo ahorrado; no se mide aquí LCP/INP.

Pruebas funcionales comprueban que las tres vistas abren y no incluyen el script; guardado conserva validación/redirect y creación de componente. El dashboard mantiene la carga. Reversión: retirar las tres secciones without-charts; la condición conserva comportamiento predeterminado.
