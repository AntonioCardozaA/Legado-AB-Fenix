# Optimización 11: abrir formulario rápido sin persistir catálogo

F16, cambio de código de riesgo alto autorizado por el usuario. Se explicó previamente que createQuick insertaba un componente o variante de línea al abrir el formulario. Store ya crea/resuelve el código contextual al guardar.

AnalisisLavadoraController mantiene la búsqueda por código y, si pertenece a otra línea, la búsqueda de variante con el sufijo anterior. Si falta, construye un modelo provisional con los mismos atributos para presentar el formulario, sin persistirlo. El POST mantiene su resolución existente. El historial de componentes ya existentes se consulta como antes; un componente provisional no tiene registros previos.

Pruebas equivalentes SQLite antes/después: abrir un código inexistente ejecutaba un INSERT; ahora ejecuta cero escrituras. Abrir un código de otra línea creaba una segunda fila; ahora conserva únicamente la existente. La prueba del historial existente pasó antes y después. El guardado posterior sigue creando el código contextual L04_reductor_1_SERVO_CHICO y un análisis.

Tres pruebas nuevas finales aprobadas (13 aserciones); junto con la prueba del índice, cuatro pruebas y 28 aserciones. Se verificaron apertura HTTP, campo de código, historial y POST. No se hizo un GET mutante contra MySQL real para obtener una línea base, ni se atribuye un ahorro en milisegundos no medido. La mejora demostrada es eliminar una escritura y su posible contención por apertura sin guardado.

Riesgo: compatibilidad de códigos y referencias históricas. Se conserva el código que muestra la variante antigua y no se modifica la resolución de store. No se eliminan componentes existentes ni historiales. Reversión: restaurar únicamente createQuick; no requiere cambios de datos.
