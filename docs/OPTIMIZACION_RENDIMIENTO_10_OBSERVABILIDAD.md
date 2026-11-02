# Optimización 10: agregados de observabilidad

F30. Se explicó antes de editar el coste de sumas y conteos separados, la consolidación y el riesgo de cambiar nulos o filtros. AiObservabilityController obtiene diez agregados de interacciones en una lectura y cinco conteos de ciclo de documentos en una lectura por modelo. Se conservan filtros, conversiones, porcentajes, muestras de latencia y permisos.

Tres pruebas existentes de métricas, filtros y acceso: once aserciones aprobadas. El contenido completo del controlador coincide en MySQL, incluida la paginación.

El primer banco separado dio 58,14 → 74,05 ms de mediana, pese a reducir consultas de 44 a 27. Se comunicó la regresión observada. Para distinguir variación del entorno, se ejecutaron ambas clases en el mismo proceso, misma transacción READ ONLY, usuario/reloj/base idénticos, calentamiento de ambas y cinco muestras alternando el orden:

| Variante | Muestras ms | Mediana ms | SQL |
|---|---|---:|---:|
| Original | 69,19 / 80,39 / 55,74 / 53,16 / 69,60 | 69,19 | 44 |
| Consolidada | 50,03 / 54,96 / 44,52 / 40,41 / 50,34 | 50,03 | 27 |

La variante consolidada fue más rápida en cada pareja de esa comparación. Se conserva por ese resultado, reducción SQL y equivalencia, sin afirmar una garantía de producción. Huella idéntica `a301c9bd0e309d56b0654c6294826000a37cefa53d407554fff34d475d30e5a8`.

Reversión: restaurar interactionMetrics y documentLifecycleFor a los agregados separados de HEAD. No afecta datos, tablas, colas ni dependencias.
