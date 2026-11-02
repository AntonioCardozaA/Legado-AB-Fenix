# Optimizaciones 07 y 08: sondeo y reutilización del listado

## 07 — Sondeo de campana (F04)

Problema explicado previamente: el intervalo lanzaba solicitudes simultáneas si una respuesta tardaba y seguía trabajando en pestañas ocultas. En el layout se evita iniciar una segunda petición mientras otra está pendiente; se libera el bloqueo tanto ante éxito como ante error HTTP/red. Se omiten consultas ocultas y se revalida al recuperar visibilidad. Se mantiene el HTML inicial y el intervalo de 30 segundos.

Pruebas Node con red y reloj simulados: diez minutos oculto pasan de 20 intentos programados en el algoritmo anterior a cero; al volver se consulta inmediatamente. Con una respuesta pendiente durante 90 segundos, los tres ticks y el refresco explícito generan una petición en lugar de cuatro. Son conteos de trabajo del escenario, no mediciones de CPU o latencia de navegador. Se conserva contenido ante error y se vuelve a consultar después. Trece pruebas frontend aprobadas, ocho de campana.

Riesgo: refresco detenido por un bloqueo mal liberado. Pruebas de éxito, rechazo de red y error HTTP comprueban recuperación. Una conexión que permanezca indefinidamente pendiente también retiene el bloqueo; no se introduce cancelación automática ni se afirma que el servidor deje de trabajar al ocultar la página.

## 08 — Consultas equivalentes del índice (F13)

Problema explicado previamente: listado, estadísticas y monitor repetían el ranking de registros vigentes. Se reutiliza la colección solo cuando SQL y bindings coinciden; para estadísticas se ignora únicamente el orden. Los filtros de estado/fecha continúan afectando al listado y no al resumen/monitor. El monitor recibe clones antes de incorporar los contadores históricos al listado, conservando sus atributos originales.

Archivo: AnalisisLavadoraController; nueva prueba WasherIndexQueryReuseTest. Riesgo: mezclar alcances. Prueba funcional con 15 aserciones comprueba listados filtrados, totales, monitor completo, atributos y número de rankings SQL.

Banco de solo lectura, misma base/admin/reloj, una ejecución de calentamiento y tres muestras por escenario:

| Filtro | Mediana antes ms | Después ms | SQL antes | Después | Contenido completo |
|---|---:|---:|---:|---:|---|
| Por defecto | 245,42 | 161,46 | 22 | 12 | Idéntico |
| L-04 explícita | 213,73 | 155,88 | 22 | 12 | Idéntico |
| L-04 y daño | 170,84 | 172,47 | 21 | 21 | Idéntico |
| L-04 y octubre | 173,15 | 157,56 | 16 | 16 | Idéntico |

Los filtros que generan consultas diferentes conservan sus lecturas independientes. No hay evidencia de mejora de latencia en el caso de daño; no se atribuye al cambio. El beneficio demostrado se concentra en la consulta común sin filtros adicionales.

Regresión ampliada: 30 de 31 pruebas aprobadas; falla `AnalisisLavadoraUserTest::test_reductor_model_is_assigned_by_location_on_target_line` porque un usuario sin rol recibe 403 al guardar. El cambio de reutilización está en index, no en store ni autorización. Se registra para investigar por separado sin relajar permisos para hacer pasar la prueba.

Revertir 07 requiere restaurar solo el bloque de refreshNotifications; revertir 08 requiere restaurar las tres lecturas de index y la ubicación del contador histórico. Ninguno altera datos o esquema.
