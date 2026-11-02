# Corrección 53: LEF vigente y endpoint de costos

El usuario eligió expresamente conservar el alcance actual de LEF y adaptar la cobertura a sus funciones vigentes. No se añaden endpoints impact/trend.lines ni nuevas reglas de comparación global. Los tres casos que invocaban esas rutas ausentes ahora ejercitan costos/datos, tendencia-lavadora y exportar/excel existentes.

Al probar la ruta vigente de costos se confirmó un defecto de producción previo: HTTP 500, Class App\Http\Controllers\Lef52124CostEntry not found. Lef52124Controller carecía de los imports App\Models\Lef52124CostEntry e Illuminate\Support\Collection, usados por costData. Se explicó antes de editar y se añadieron solo esos imports. No se altera ninguna consulta ni cálculo. Es una corrección de disponibilidad; no se compara el tiempo de un error con el de una respuesta válida como mejora de rendimiento.

Cobertura resultante:

- Costos globales conservan filas sin línea asignada; con linea_id conservan solo esa línea. Se verifican suma de ambos años, número de registros, doce meses, orden y valores por línea. No se traslada a este endpoint la regla distinta que esperaba el antiguo test de impact.
- Tendencia de lavadora conserva los doce meses de 2025/2026 y las tres ventanas, valores de la máquina lavadora y null para meses sin datos; valida línea obligatoria/existente. No se prueba una comparación global que no forma parte del módulo actual.
- Export vigente se descarga por HTTP autenticado y se lee con PhpSpreadsheet: cuatro hojas, tres ventanas con valores de dos líneas, meses sin valor y exclusión de fechas fuera de 2025/al corte fijo 31/10/2026. El anónimo no descarga. No se impone al export actual la política solo-admin de una ruta ausente.

Seis pruebas LEF pasan, 82 aserciones, incluidas las de datos/dashboard. Las otras correcciones de base están en 51/52. Queda por ejecutar la regresión completa posterior; no se declara resuelta solo por este subconjunto.
