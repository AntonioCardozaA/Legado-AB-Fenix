# Corrección 55: conectar el formulario vigente de costos

Durante la adaptación de LEF autorizada por el usuario se detectó otro error previo en una funcionalidad visible: el botón Agregar documento de costos apunta a lef52124.costs.import, pero importCosts no existía en el controlador. La prueba HTTP reprodujo BadMethodCallException y 500 antes de modificar producción.

Se explicó problema, causa, archivo/servicio, solución y riesgos. Se añadió el método en Lef52124Controller y se conecta al Lef52124CostImportService ya existente, sin modificar su parser, detección de línea, totales, upsert ni transacciones. Usa el mismo control ensureAdmin que el formulario/importaciones LEF y validación XLS/XLSX hasta 20 MB y línea de respaldo opcional existente; transforma errores de lectura en error de validación del archivo como la importación LEF actual. No se ejecutó el importador contra MySQL operativo.

Tres pruebas nuevas generan un XLSX real en temporal y usan SQLite :memory:. Verifican importación mediante servicio real, importe y orden con ceros iniciales, usuario importador, repetición del mismo archivo sin duplicados, consulta posterior, rechazo de técnico/anónimo y archivo/entrada inválidos sin escrituras. El técnico ya era rechazado por middleware antes de alcanzar el método; el control admin también se mantiene en el controlador.

Nueve pruebas LEF pasan con 109 aserciones. Regresión completa posterior a 49–55: **449 pruebas, 5210 aserciones, cero errores/fallos/omitidas**, 98,96 s; informe temporal legado-regression-20261031-55.xml. Esta cifra sustituye como estado actual a los ocho casos fallidos históricos del documento 47. No se eliminaron pruebas: los contratos LEF se adaptaron a las funciones vigentes por decisión explícita del usuario.

Es una reparación de disponibilidad, no una mejora temporal. Reversión aislada: retirar importCosts y su import de servicio devolvería el formulario al error previo; no revierte datos importados. Los imports de clases corregidos en 53 son independientes.
