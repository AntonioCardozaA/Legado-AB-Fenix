# Optimización 17: contexto de revisión de pasteurizadora

F15, refactorización autorizada con pruebas. Se explicó previamente que los helpers releían línea y consultas iguales para estados, siguiente revisión, disponibles y bloqueados. El riesgo era mezclar áreas, ciclos o numeración y retener datos entre operaciones.

Se añade PasteurizadoraRevisionContext, creado dentro de cada operación de consulta. Comparte la línea ya leída y colecciones únicamente con la misma conexión, SQL y bindings. No se registra como singleton ni se almacena en modelos, caché global o sesión. Los helpers reciben un parámetro opcional final y conservan todas sus reglas y filtros; sin él siguen ejecutando sus lecturas originales. Un contexto nuevo se crea también para el endpoint de pendientes. No se comparte un contexto entre operaciones de escritura.

Los helpers mantienen tipo quick/normal, área, soft deletes, excludeId, orden cronológico, lados/niveles, ciclos y numeración. No se reemplazan las reglas por cálculos nuevos. Las consultas distintas, como reinspección frente a quick, se conservan separadas.

Banco MySQL READ ONLY, mismo P-03/modulo 1/ANILLAS/SUPERIOR/VAPOR, admin/reloj fijos y cinco muestras por variante:

| Variante | Mediana ms | SQL | Huella JSON completa |
|---|---:|---:|---|
| Original | 121,63 | 73 | 0a66466bd4122cf523dd9628cd2cacf76584c98087bed08ed8643ca07788c76e |
| Contexto explícito | 34,08 | 5 | Idéntica |

La reducción medida corresponde a ese contexto local; el número de consultas con otros datos depende también de relaciones y consultas distintas requeridas. No se afirma que todo contexto siempre ejecute exactamente cinco.

Prueba nueva de equivalencia (12 aserciones): estados y siguiente revisión con quick/normal/todos, disponibles/bloqueados por lado, cero consultas al repetir una lectura idéntica en la misma operación y datos actualizados con un contexto nuevo después de guardar. Las regresiones de ciclos/central hidráulica/numeración aprobaron 38 de 39 pruebas existentes.

Fallo de texto en PasteurizadoraNumeracionTest línea 65: espera `Ubicacion: #7 Vapor - Superior` en el índice. Se volvió a ejecutar con revisionContext=null, usando todas las lecturas originales, y falla en la misma línea (16 aserciones previas aprobadas). Se restauró el contexto tras ese control. No se modifica el formato del índice ni se oculta esa regresión previa.

Reversión: dejar de pasar contexto desde resolveRevisionContext/getRemainingComponentsAjax. Los helpers siguen aceptando null y conservan su camino original. No hay migraciones ni invalidación de caché o datos.
