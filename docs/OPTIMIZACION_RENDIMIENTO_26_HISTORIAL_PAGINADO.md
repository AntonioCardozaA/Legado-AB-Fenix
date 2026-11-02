# Optimización 26: historial de lavadoras paginado

F14. Se explicó previamente la carga completa de modelos/HTML y el riesgo de omitir registros o cambiar los agregados. El controlador conserva los filtros y orden fecha/creación/ID descendentes. Lee únicamente lado y evidencias para los totales completos, usando el accessor original para evidencias heredadas. Carga 25 registros por sección (Vapor, Pasillo, Sin lado), o 50 para componentes sin separación. Cada sección tiene página independiente y conserva los demás parámetros.

Todos los registros siguen accesibles. Se conservan permisos, edición, fotos, títulos, totales globales y numeración continua por sección. La comparación de lados reproduce strtoupper/trim de PHP; MySQL usa comparación binaria de los valores originales para evitar que una colación equipare VÁPOR con VAPOR.

Tres pruebas aprobadas, 82 aserciones: historial existente por lado, 165 registros recorridos íntegramente sin duplicados, desempates, parámetros conservados, fotos fuera de primera página, valores heredados/null/vacíos y paginación general.

Banco reproducible: tools/benchmark-history-response.php. Solo SQLite :memory: comprobado antes de migrar/crear 165 filas sintéticas; usuario/reloj/sesión iguales, calentamiento y tres muestras, render incluido y sin proveedores:

| Métrica | Antes | Después |
|---|---:|---:|
| Modelos completos visibles | 165 | 75 |
| HTML bytes | 1658440 | 839610 |
| Consultas | 6 | 13 |
| Mediana ms | 257,36 | 246,65 |

El beneficio demostrado es reducir modelos y HTML aproximadamente a la mitad; la variación temporal no demuestra una aceleración importante. Las consultas aumentan porque tres páginas cargan sus relaciones por separado. Con historiales pequeños existe ese costo adicional. No se midió navegador/producción; faltan perfiles de DOM y volúmenes mayores. El conteo aún recorre metadatos de todo el historial para conservar evidencia heredada exactamente.

Reversión propuesta si el costo SQL perjudica los casos reales: recuperar get() y historialSecciones originales, el cálculo de estadísticas en Blade y quitar enlaces/offset de paginación. No cambia esquema ni registros. Se conserva por ahora por la reducción verificada de HTML y modelos, sin atribuirle ganancias de latencia no demostradas.
