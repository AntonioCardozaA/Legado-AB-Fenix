# Optimización 25: contar series históricas en una pasada

F18/F02. Se explicó antes de editar el costo aislado: 1044 modelos ya cargados, cero consultas SQL y mediana de 3318,72 ms. La causa era recorrerlos por cada mes y por cada línea, convirtiendo fecha_analisis a Carbon repetidamente.

DashboardController calcula ahora dos mapas de conteos (mes y línea/mes) en una pasada. Conserva comparación estricta del tipo/valor de ID de línea, doce meses, filas sin fecha, orden de etiquetas y todos los registros históricos. Los cinco registros mostrados por alcance y su formato no cambian. No se limita el histórico ni se filtran sus datos de origen.

Prueba nueva: varias líneas/años, límites de mes y fecha nula comparados contra la regla anterior; se conserva la colección completa. Junto a estados, ciclos y normalización: ocho pruebas y 42 aserciones aprobadas.

Comparación alternada, mismo proceso, misma colección y transacción MySQL READ ONLY, reloj/admin fijos. Se encapsuló el método anterior como closure con el mismo scope del controlador, sin modificar archivos entre variantes:

| Cálculo aislado | Mediana ms | SQL | Huella completa |
|---|---:|---:|---|
| Anterior | 3615,35 | 0 | 9f073c6b5a050634ae6ee4aee9697acbe376707b5aeed30313a356b5730591a1 |
| Una pasada | 334,35 | 0 | Idéntica |

Se compararon además ambos controladores completos, alternando únicamente el cuerpo del método histórico anterior/nuevo, manteniendo las demás optimizaciones, calentamiento y tres muestras por variante en la misma instantánea:

| Dashboard, preparación de datos | Mediana anterior ms | Nueva ms | SQL | Datos completos |
|---|---:|---:|---:|---|
| Global | 6912,63 | 2047,15 | 81 | Idénticos |
| Operativo | 5676,82 | 1909,84 | 81 | Idénticos |

La comparación pareada evita mezclar cambios de datos locales entre bancos realizados en momentos distintos. No incluye render, red, navegador ni concurrencia. Las medianas no son garantías de producción.

Riesgo: cambiar meses, mezclar tipos de ID o perder registros antiguos. Se verifican reglas y huella completa, y solo se sustituyen recorridos. Reversión: restaurar los filtros mensuales originales dentro de getHistoricoRevisiones; no requiere datos, esquema o cachés.
