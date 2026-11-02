# Optimización 56: registros completos por página de cada grupo

F14. El historial de pasteurizadora cargaba texto, evidencias y usuario de todos los análisis y renderizaba cada registro de todos los grupos. Se explicó antes de modificar controlador/vista y crear PasteurizadoraHistoryPages. Se conservan todos los metadatos necesarios para agrupar y calcular cifras completas; solo se cargan los registros completos visibles, 25 por grupo. Cada grupo tiene un nombre de página estable y un ancla; sus enlaces mantienen los filtros y las páginas de otros grupos.

El bloque de agrupación original se trasladó al soporte conservando clave, normalización de secciones, totales, piezas únicas, fechas mínima/máxima, orden de grupos y orden creado/ID dentro del grupo. La selección de visibilidad PHP original conserva tipos normal/quick/heredados, fecha actual y estados. El filtro de área y perfil de excéntricos sigue en el controlador. Se consulta usuario/evidencia/actividad de los IDs visibles por lotes de hasta 500. No se trunca ni elimina historia.

Los metadatos siguen siendo O(N) del historial filtrado. Se reduce la hidratación de campos pesados y el HTML, no se promete memoria constante ni snapshot entre páginas si hay escrituras concurrentes. Conteo/metadatos y detalle pueden reflejar instantes distintos bajo aislamiento sin snapshot, como count/datos de una paginación convencional.

## Comparación equivalente

tools/benchmark-pasteurizer-history.php, SQLite :memory: verificado antes de migrar, 165 filas sintéticas de P-06, módulo 2 de cinco anillas, numeración 6–10, 55 registros por Vapor/Pasillo/Sin lado. Mismo usuario, reloj, sesión, filtros y campos. Una preparación y tres muestras calientes. Sin HTTP ni fotos reales.

La referencia --baseline carga exclusivamente el método historial y vista anteriores desde Git HEAD 9d2ba10, con el resto del controlador actual, en clase/vistas temporales. No restaura archivos del proyecto. Rechaza HEAD si ya contiene la nueva paginación. El cálculo extra del hash de comprobación se realiza después de capturar tiempo/pico de render; la primera medición con ese cálculo dentro del cronómetro fue descartada. Un intento de referencia leyó el bloque equivocado de la vista actual y falló; se corrigió para leer la vista resuelta de referencia y se repitieron ambas variantes.

| Magnitud | Original | Paginado |
|---|---:|---:|
| Mediana preparación/render, ms | 307,07 | 279,04 |
| SQL | 5 | 7 |
| Modelos completos visibles | 165 | 75 |
| HTML bytes | 1946012 | 964890 |
| Pico adicional bytes | 8220560 | 4329472 |
| Total de cada sección/grupo | 55 | 55 |

Beneficio principal: 50,4 % menos HTML y 47,3 % menos pico adicional; la mejora temporal es moderada y tres muestras no prueban percentiles ni significación estadística. Los agregados completos coinciden: SHA256 39df696ae261f4ccb4fd56b83329b8dbf479cf21304b6c9deb56af76b73efd00. No se compara el contenido parcial de una página con el HTML completo como si debieran tener los mismos bytes.

## Validación funcional y de navegador

Treinta y ocho pruebas de historial/numeración/ciclos pasan, 435 aserciones. Tres nuevas recorren todos los IDs de las tres páginas de cada grupo, sin huecos/duplicados, con totales y piezas completas, fechas empatadas, filtros, evidencias, acceso, vacío y página fuera de rango. Cubren visibilidad de tipos heredados y numeración inferida de cantidades sin JSON, conservando área. Dos expectativas iniciales del fixture se corrigieron: la columna tipo_registro no admite null y el acceso HTML denegado redirige al dashboard con aviso; se verifican cadenas vacías heredadas y la política real. El equipo del fixture se ajustó a P-06 para que cinco anillas y números 6–10 correspondan a su catálogo; las dos variantes se volvieron a medir con ese mismo equipo.

Chrome aislado, antes/después en una sesión, 1440/390 px: tres grupos, totales, apertura de evidencia, carga de imagen, caption, cierre por Escape y restauración del scroll correctos. Doce enlaces de paginación apuntan a anclas existentes. La imagen fixture.jpg la sirve el banco desde el icono PNG existente, sin escribir en storage; esto prueba interacción, no calidad de fotografías. Las pruebas PHP ejercitan las páginas por las rutas reales; el servidor de capturas solo entrega HTML de fixtures.

Estilos calculados iguales. DOM 10150 → 4842 nodos. Capturas iniciales: 929 píxeles distintos en escritorio y 255 en móvil; se concentran en líneas/bordes de la cronología cuya altura cambia con la paginación. Revisión visual: cabecera, texto, tarjetas y geometría visibles conservados. No se declara igualdad absoluta de píxeles ni ahorro de CPU/GPU. El primer selector del banco confundió registros con grupos (168 frente a tres); se corrigió para contar encabezados y se repitió. Ejecución final sin excepciones JS ni fallos de carga registrados: temporal legado-browser-check-6wZsNG.

Regresión completa posterior: 452 pruebas, 5384 aserciones, cero errores/fallos/omitidas. Build/gzip y 26 pruebas frontend aprobados. Reversión: recuperar la consulta completa y el bloque de agrupación anterior en Blade, retirar soporte/paginación/datos nuevos del controlador de forma conjunta. No requiere DDL ni activación de colas.
