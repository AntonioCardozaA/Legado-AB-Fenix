# Numeración consecutiva de componentes de pasteurizadoras

La numeración de cada tipo continúa entre módulos dentro del equipo. La configuración existente de `AnalisisPasteurizadora` determina las cantidades: 3/2/2/3 en equipos sencillos y 5/2/4/5/2 en dobles para anillas, excéntricos, pistas, placas perno y rodajas, respectivamente. Las rodajas solo existen en dobles.

`PasteurizadoraNumeracion::numeros()` calcula el rango del módulo; `total()` obtiene el total del equipo y `configuracion()` entrega los rangos al navegador. Los demás tipos de piezas configuradas también usan esta secuencia. El flujo especial de brazos de torsión conserva su distribución actual.

Los números almacenados en `componentes_revisados` pasan a ser globales. `total_componentes` sigue expresando la capacidad del módulo para conservar los cálculos de avance, lados, niveles y ciclos. Los totales de `historico_revisados` expresan la capacidad del equipo. Cada inspección conserva su módulo, lado, nivel, ID, usuario, observaciones, evidencias, fechas y relaciones de resolución.

Las inspecciones sucesivas pueden referirse al mismo número: son historial válido. No se crean filas de componentes ni se impone una restricción de unicidad a las inspecciones. La validación rechaza íntegramente selecciones con números de otros módulos, incluso cuando contienen números válidos junto con inválidos.

## Archivos

| Archivo | Cambio |
| --- | --- |
| `app/Support/PasteurizadoraNumeracion.php` | Rangos, totales, conversión de posiciones y validación centralizados. |
| `app/Models/AnalisisPasteurizadora.php` | Números globales en ciclos, pendientes, reinspecciones, históricos, listas y porcentajes; compatibilidad con registros que solo guardan cantidades. |
| `app/Models/HistoricoRevisados.php` | Totales por equipo y conteo de números distintos. |
| `app/Http/Controllers/AnalisisPasteurizadoraController.php` | Captura normal, rápida, edición, validación, modales, resolución de indicadores y PDF. |
| `app/Exports/AnalisisPasteurizadoraExport.php` | Columna de números de componentes en Excel. |
| `database/migrations/2026_10_23_000001_use_consecutive_pasteurizadora_component_numbers.php` | Conversión versionada, transaccional, repetible y reversible. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/create.blade.php` | Selección de números globales y actualización al cambiar módulo. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/create-quick.blade.php` | Mismo cálculo en captura rápida. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/edit.blade.php` | Opciones y selección almacenada con números globales. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/index.blade.php` | Rangos por módulo y números globales en el diagrama y sus modales. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/excentricos-index.blade.php` | Rangos de excéntricos y números de las piezas revisadas. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/historial.blade.php` | Listas y acumulados consistentes con los números almacenados. |
| `resources/views/pasteurizadora/analisis-pasteurizadora/show.blade.php` | Detalle del componente con su número global. |
| `resources/views/pasteurizadora/pdf/analisis.blade.php` | Números revisados en la ficha exportada. |
| `tests/Unit/PasteurizadoraNumeracionTest.php` | Distribución completa de los 12 equipos y rechazo de rangos inválidos. |
| `tests/Feature/PasteurizadoraNumeracionTest.php` | Persistencia, edición, consultas, exportación, ciclos, indicadores y conservación de datos en la migración. |
| `tests/Frontend/pasteurizadora-numeracion.test.mjs` | Cambio de módulo y piezas únicas en los dos formularios mediante un DOM simulado. |

## Migración

En otra instalación, ejecutar con PHP compatible con las dependencias instaladas:

```console
php artisan migrate --path=database/migrations/2026_10_23_000001_use_consecutive_pasteurizadora_component_numbers.php
```

`numeracion_version` evita convertir dos veces un mismo análisis. La migración incluye registros eliminados lógicamente y utiliza actualizaciones directas para conservar sus fechas y evitar notificaciones o eventos de mantenimiento. Los registros de otros ámbitos o de componentes sin configuración permanecen intactos. Una posición inválida detiene la conversión y revierte sus actualizaciones de datos, indicando el ID que requiere revisión.

Los históricos existentes se actualizan a los totales del equipo. Sus cantidades revisadas se conservan si no hay análisis de origen que permitan reconstruirlas; no se inventan números físicos para esos resúmenes. El rollback restaura las posiciones locales de los análisis.

La migración se aplicó en MySQL local el 23 de octubre de 2026. Había 0 análisis y 11 resúmenes históricos. Se verificó la conservación de IDs, fechas, relaciones y cantidades históricas revisadas. El respaldo previo permanece en `storage/app/private/pasteurizadora-numeracion-2026-10-23.json` y está excluido de Git.

## Verificación

```console
php artisan test --compact --filter="PasteurizadoraNumeracionTest|PasteurizadoraAnalysisCycleTest|DashboardPasteurizadoraTest|CentralHidraulicaPasteurizadoraTest|PasteurizadoraMaintenanceAiFlowTest"
node --test tests/Frontend/pasteurizadora-numeracion.test.mjs
```

Se comprueban secuencias desde 1 hasta el total esperado, ausencia de huecos y duplicados, independencia por equipo y tipo, captura y edición, API, exportación, ciclos, reinspección y resolución del indicador correcto. Los casos de migración incluyen repetición, rollback, conservación de registros eliminados y relaciones, históricos sin análisis e interrupción transaccional por datos inválidos.

La prueba existente `RoleAccessTest::test_capturista_excentricos_can_store_only_excentricos_without_order_number` falla al buscar el texto `Indicador`. Se reprodujo el mismo fallo con los archivos originales de Git; la vista original utiliza el texto `ESTADO`.

Resultado final: 85 pruebas PHP aprobadas, con 2435 aserciones, incluyendo el resto de `RoleAccessTest` y excluyendo únicamente el fallo previo descrito. Las 4 pruebas de JavaScript también aprobaron. Se comprobaron la sintaxis de los PHP modificados, `git diff --check` y el estado aplicado de la migración en MySQL local. La interacción de los formularios se verificó con DOM simulado; no se ejecutó una sesión manual en navegador.
