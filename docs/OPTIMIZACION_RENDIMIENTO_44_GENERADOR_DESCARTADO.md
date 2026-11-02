# Experimento 44: generador de filas descartado

F25. Se explicó previamente la matriz completa del XLSX general y se propuso un generador conservando array() como compatibilidad. La biblioteca instalada soporta FromGenerator mediante LazyCollection, por lo que se probó ese cambio sin alterar las filas, datos cargados o eventos de formato.

Banco tools/benchmark-general-report.php, SQLite :memory: verificado, 5001 filas sintéticas, temporales aislados, mismo mes/reloj/rol y límite de memoria de 512 MB. Una muestra por variante, no una comparación estadística:

| Variante | ms | Pico adicional bytes | XLSX bytes |
|---|---:|---:|---:|
| Original FromArray | 4764,99 | 96819096 | 494338 |
| Prototipo FromGenerator | 5143,53 | 96819928 | 494338 |
| Original tras revertir | 4800,63 | 96819160 | 494338 |

El pico no disminuye: domina una fase posterior de escritura del libro. El tiempo del prototipo tampoco demuestra mejora; no se afirma una regresión temporal estadísticamente significativa con una sola muestra. Se propuso revertir y se retiró el experimento completo. git diff no muestra cambio en ReporteLavadoraGeneralExcel.php; PHP lint y generación real del archivo original pasan tras restaurarlo.

El prototipo solo se usó en el banco; no se conserva ni se despliega. No se escribió una prueba permanente para un cambio descartado ni se afirma equivalencia celda por celda del prototipo. Un intento separado de cargar la referencia temporal sin autoloader falló antes de exportar y se descartó; el banco original ejecutado antes del cambio es la referencia válida.

La reducción de memoria demostrada para XLSX de pasteurizadora (optimización 24) permanece. Para el reporte general, una reducción mayor requiere revisar la retención de modelos y/o estrategia de almacenamiento de celdas con pruebas completas de formato; ejecución durable fuera de HTTP sigue sujeta a revisión operativa. No se cambia calidad, se omiten filas, ni se convierte el reporte a otro formato.
