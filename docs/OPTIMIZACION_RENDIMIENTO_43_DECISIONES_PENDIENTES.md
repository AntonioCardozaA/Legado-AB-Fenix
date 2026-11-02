# Decisiones y límites pendientes de la auditoría

No se considera resuelto un hallazgo por haberlo leído. Este documento separa cambios sin beneficio demostrado, propuestas que requieren aprobación y validaciones aún pendientes. No se ejecutó DDL, ni se activó una cola durable en producción.

## F17: rango mensual evaluado, sin reescritura aplicada

MySQL local, misma línea/mes/datos, transacción READ ONLY y cinco muestras: MONTH/YEAR, mediana 1,3598 ms; intervalo sobre fecha, mediana 1,7027 ms. Los IDs ordenados coinciden. EXPLAIN cambia de ref con linea_id a range con linea_id/fecha_analisis; estima 72 → 26 filas y longitud de clave 8 → 11 bytes. Son estimaciones del optimizador, no filas leídas medidas.

No se aplica el cambio: mejora el plan pero no la latencia local. Se conserva la semántica de entradas heredadas/inválidas. La prueba del intervalo incluyó el día cero de MySQL para no excluir fechas heredadas que MONTH/YEAR admiten; el conteo local de esas fechas fue cero. Antes de recomendarlo se requiere volumen representativo y comparación de todos los filtros/motores. No hay código nuevo que revertir.

## F12: propuesta de índices, pendiente de autorización

SHOW INDEX confirma tres índices no únicos con columnas linea_id/fecha_analisis en el mismo orden: idx_analisis_linea_fecha, idx_analisis_componentes_linea_fecha e idx_analisis_componentes. Los planes observados usan el primero. No se encontró FORCE INDEX/USE INDEX en app. No se ha eliminado ninguno.

Propuesta revisable: conservar idx_analisis_linea_fecha y evaluar retirar los otros dos en una copia de staging. Antes: comprobar SHOW CREATE TABLE, tipo/prefijo/dirección/visibilidad, FK y consultas externas; medir SELECT, INSERT/UPDATE y bloqueo de metadatos. Después: planes/resultados/escrituras equivalentes. Reversión: recrear ambos índices sobre las mismas columnas con sus opciones originales. No se crea migración ejecutable ni se propone índice nuevo sin esas pruebas. El usuario excluyó cambios de esquema de la autorización actual.

## F18: evolución de ciclos conservada

La optimización 25 ya acelera las series históricas sin cambiar datos. El método de evolución restante mide 11,41 ms de mediana y tres consultas sobre 29 elongaciones locales. Mantiene la regla de ciclo activo o, si no existe, ciclo del último registro con ciclo; no equivale a MAX(numero_ciclo). No se añade complejidad ni consulta extra sin demostrar beneficio con mayor volumen.

## F03 y F30: casos secundarios conservados

Los destinos simples ya se consultan por lotes. La continuación 58 añade un fixture de 224 avisos de elongación y reutiliza validaciones idénticas solo durante una lectura: 768 → 96 consultas, mismos IDs ordenados. Conserva la comprobación original de ciclo/vencimiento y los identificadores heredados, en vez de reemplazarla por existencia de ID. Destinos especiales distintos siguen evaluándose individualmente; no se promete consulta constante.

Observabilidad y directorio tienen optimizaciones 10/40. ControlGastos ya pagina sus artículos principales a 15; otras colecciones alimentan formularios/reglas y no deben recortarse como si fueran una tabla paginada. El conteo de cierre fue 71 artículos, 21 reglas y cero destinatarios de alerta. No se justifica otra paginación a ciegas con ese volumen.

## F14: historiales restantes

La continuación implementa los otros historiales mediante fixtures representativos: 49/50 pagina historial/detalle hidráulico y elimina catálogo por fila; 56 pagina registros completos de cada grupo de pasteurizadora con totales globales y pruebas de todas las páginas. La optimización 54 elimina la lectura duplicada del seguimiento agregado de etiquetadora; su historial individual ya usa paginate(10). La 26 de lavadoras se conserva.

Las pruebas recorren IDs sin huecos/duplicados, grupos, lados, numeración, filtros y evidencias. Los agregados de pasteurizadora coinciden con la agrupación original y su HTML/pico adicional se reducen 50,4/47,3 % en el banco. Estos porcentajes no son mediciones sobre el volumen operativo pequeño. La lectura de metadatos y ciclos sigue siendo completa: F14 no implica memoria constante, y una agregación adicional en SQL requiere perfil y equivalencia antes de sustituir esas reglas.

## F21/F22: navegación e inicialización restante

Se separó CSS común/ambos dashboards y los bloques principales de JS de lavadoras y pasteurizadoras (31/37/38/46). La continuación 60 perfila y difiere la construcción de la gráfica histórica de lavadoras: tabla/totales inmediatos, mismos datos al mostrarla, filtros e impresión comprobados, cero píxeles distintos iniciales. Con recursos calentados, muestras CPU de Chart.js 157,68 → 96,70 ms escritorio y 131,02 → 89,39 ms móvil. Es una pareja de perfiles locales, no CPU total ni una garantía de producción. Compatibilidad anterior conservada; otras cargas diferidas solo se justificarán con perfil propio.

La continuación 66 implementa navegación parcial en un único listado: directorio de usuarios. Conserva consulta/DOM/totales, URL/popstate normal y rápido, foco, permisos y formulario de creación; peticiones canceladas/obsoletas y fallback comprobados en Chrome 1440/390. En el mismo banco, respuesta completa/fragmento 294508 → 84700 bytes y render 80,91 → 31,63 ms. No se interceptan formularios ni enlaces globalmente. Navegación fría/caliente y métricas de campo del servidor de destino siguen siendo validaciones de despliegue.

## F24/F25/F29: perfiles y calidad pendientes

Evidencias: evaluación 42; algoritmo conservado y reindexación agrupada. XLSX pasteurizador: optimización 24 comprobada; guard de snapshot corregido para MySQL con nombre antiguo de variable, sin ocultar otros errores SQL. Orden anterior/nuevo también coincide en las cuatro filas MySQL locales.

Reporte general, 5001 filas: el experimento FromGenerator no reduce pico de memoria (96,819 → 96,820 MB) y su muestra temporal es peor (4,765 → 5,144 s). Se revirtió completamente y el exportador sigue sin diff (44). La continuación 65 retira un cálculo descartado del controlador, conservando esa clase: 1001 filas, general 19 → 6 SQL y 3267 → 1608 ms, mismas celdas; por línea también comprobado. No se recomienda recuperar la reescritura FromGenerator. El usuario decidió expresamente dejar exportaciones durables/migraciones pendientes el 2 de noviembre.

PDF, resumen corporativo con 165 registros de origen: subsetting de fuentes (45) reduce 1629469 → 777774 bytes, 52,3 %, con texto y comandos de página iguales. No mejora generación: 15,61 → 15,79 s, mismo pico de 89,7 MB; no se presenta como optimización de CPU o memoria. Una prueba adicional conserva texto Unicode, geometría y comandos en dos páginas. La descarga asíncrona durable necesita diseño, infraestructura y aprobación de activación, no solo otro tipo de export.

Chat: la optimización 13 lee la misma ventana efectiva. La continuación 62 difiere el embedding hasta tener candidatos autorizados y lo reutiliza entre ambos módulos, también ante fallo/vacío. No se añadió caché compartida ni se bajaron candidatos/grounding. 49 pruebas de contexto, fuentes y ranking híbrido pasan con proveedor falso; corpus vacío una llamada → cero, mismo resultado. La recuperación léxica previa mide 152,72 ms/10 SQL; el nuevo banco de proveedor falso de 100 ms mide 160,30 → 32,99 ms. Ninguno mide calidad/latencia del proveedor real, que siguen pendientes; no hubo gasto de API.

## F08/F09/F11/F26/F27/F28: revisión operativa separada

after_response no sustituye una cola durable. Backend, workers, retry_after/visibility, lease, timeout, fallos y descarga autorizada requieren staging y aprobación de activación. PHP/OPcache deben verificarse en CLI, SAPI y worker reales. 63 extiende el bloqueo común a pasteurizadoras y restaura presentación/estado al volver; Chrome confirma dos admisiones → una. No equivale a idempotencia del servidor. El usuario decidió expresamente dejar idempotencia durable, sus transacciones/migraciones y exportaciones durables pendientes. La revisión automática rechazó ese parche de esquema/rollback y no se aplicó; no se añadieron sus archivos por otra vía.

## Comprobaciones finales y límites operativos

La continuación cierra los ocho casos históricos: fixtures con rol/login y expectativas visuales vigentes (51), autorización de área explícita en prueba de aviso (52) y cobertura LEF adaptada a su alcance actual por decisión del usuario (53). En las funciones vigentes de costos se repararon dos errores reales: imports ausentes y método de carga ausente (53/55). No se relajaron permisos ni se eliminaron pruebas.

Regresión vigente: 462 pruebas PHP, 5503 aserciones, cero errores/fallos/omitidas; 32 pruebas frontend, lint de 111 PHP y build/gzip aprobados. Tras ajustar el anuncio accesible pasan 14 pruebas relacionadas/137 aserciones y Chrome de nuevo. Directorio: fragmento/DOM/permisos, XLSX completo/por línea y navegación real comprobados. Detalles actuales en [cierre 67](OPTIMIZACION_RENDIMIENTO_67_CIERRE_CODIGO.md); 47/57/64 conservan regresiones históricas.

El diagnóstico de colas sigue sin preparación operativa en sync/Windows; no constituye un fallo nuevo de la suite. La propuesta concreta de entrega, reversión, DDL y activación está en [despliegue 48](OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md). Ninguno de esos pendientes se declara resuelto por documentación.
