# Optimización 45: subconjuntos de fuentes PDF

F25. La configuración de Dompdf desactivaba font subsetting e incrustaba fuentes completas. Antes de editar se explicó el cambio, el riesgo de caracteres perdidos y la comparación requerida. Se cambió únicamente dompdf.options.enable_font_subsetting a true; no cambia tipografía, CSS, contenido, tamaño de página ni la fábrica alternativa, cuyo valor de biblioteca ya era true.

Banco tools/benchmark-report-pdf.php: SQLite :memory:, 165 registros sintéticos de origen resumidos por la plantilla corporativa en una página, mismo emisor/reloj/datos, recursos remotos deshabilitados y caché de fuentes temporal nueva en cada proceso. No representa 165 filas dibujadas ni usa fotos operativas. Una muestra por variante:

| Variante | PDF bytes | Generación ms | Pico adicional bytes | Páginas |
|---|---:|---:|---:|---:|
| Fuentes completas | 1629469 | 15613,42 | 89708904 | 1 |
| Subconjuntos | 777774 | 15790,64 | 89708904 | 1 |

El beneficio demostrado es 52,3 % menos bytes. No demuestra menor CPU, tiempo o memoria de generación. El PDF anterior de 12,57 s pertenece a otra muestra de perfil, no se usa como comparación del cambio.

Se extrajo el texto completo con Smalot PdfParser (la misma biblioteca que usa DocumentContentExtractor) y se compararon todos los comandos de dibujo de las páginas del backend CPDF. Ambas huellas coinciden: texto e23e0e10961c64a4aa767388da02e9251b344e2aabb3a1acb03d263627e2446a; dibujo ba768b331fd86cec803be04e56ab2b3d4c0e98ef4ee4fcd4e72ad7cce61a1d1f. Se conserva el mismo número de páginas. No se afirma una comparación raster de lectores PDF externos.

Prueba funcional adicional de dos páginas: configuración aplicada, acentos, ñ, Ø, ±, €, texto extraído, geometría y comandos no vacíos idénticos, archivo menor. No usa base operativa ni red. La mejora no sustituye el trabajo pendiente de generación durable fuera de HTTP para reportes grandes.

Reversión: recuperar enable_font_subsetting=false y aplicar el procedimiento de configuración del despliegue. Ante incompatibilidad de un lector o fuente particular, conservar la variante anterior y reproducir con un documento autorizado antes de ampliar el cambio.
