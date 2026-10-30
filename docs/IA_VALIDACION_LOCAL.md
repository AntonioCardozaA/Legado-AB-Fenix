# Consolidación local de IA — 28 de octubre de 2026

## Cambios

- La generación acepta `knowledge_sources: []`, como permite el esquema JSON. El campo sigue siendo obligatorio y debe ser una lista. Sin fuentes se reduce la confianza y se solicita evidencia. La aprobación exige al menos una fuente y, para propuestas nuevas, comprueba que coincida con el contexto registrado.
- La migración `2026_10_28_000001_create_maintenance_history_chunks_table.php` crea el índice faltante con identidad única por módulo, tipo de fuente, registro y fragmento.
- El historial incluye análisis, eventos y planes de lavadoras y pasteurizadoras, análisis de central hidráulica, elongaciones y costos de lavadoras. Conserva resultados de ejecución y eficacia. El catálogo de componentes hidráulicos no se mezcla con el catálogo general.
- Las altas, actualizaciones y bajas despachan el índice con el módulo correcto. La recuperación respeta el área y excluye propuestas IA sin aprobar como antecedentes de planes aprobados.
- La importación documental es repetible: conserva la procedencia y el hash de cada documento y evita duplicarlo. Los procedimientos internos se entregan como `procedure`, sin atribuirles autoridad de fabricante.
- La búsqueda web de Gemini prueba los modelos alternativos configurados ante errores transitorios como 429. No cambia de modelo ante errores de credenciales.
- `maintenance:ai:check` informa tablas, documentos, configuración y tamaño del índice, sin claves. Las pruebas remotas son opcionales y utilizan consultas sintéticas públicas sin contenido de documentos o registros internos.

## Aplicación en la base local

Se ejecutaron exclusivamente la nueva migración y las importaciones siguientes:

```console
php artisan migrate --path=database/migrations/2026_10_28_000001_create_maintenance_history_chunks_table.php --force
php artisan pasteurizadora:knowledge:import-internal --without-embeddings
php artisan maintenance:history:index --module=lavadora --without-embeddings
php artisan maintenance:history:index --module=pasteurizadora --without-embeddings
php artisan maintenance:ai:check --json
```

Resultado del primer recorrido: 1,191 fragmentos de historial de lavadoras y 7 de pasteurizadoras, con cero errores. Los registros eliminados lógicamente quedan excluidos. No se borraron ni modificaron inspecciones o planes operativos durante la carga.

Pasteurizadoras dispone ahora de dos documentos internos indexados y siete fragmentos: procedimiento de uso, clasificación y revisión del sistema; y numeración de componentes. No se localizaron manuales del fabricante en los archivos disponibles. Esos manuales, fichas y procedimientos de reparación aprobados siguen siendo necesarios para respaldar especificaciones técnicas del equipo.

La carga se hizo sin embeddings externos. El índice se puede consultar por texto y metadatos. El envío de documentos internos al proveedor para generar embeddings fue rechazado por la revisión automática al haberse solicitado trabajo local; no se efectuó. Tampoco se regeneró el plan real fallido con envío de contexto interno. Su estado se conserva para revisión.

## Comprobación de los servicios

```console
php artisan maintenance:ai:check --probe --json
php artisan maintenance:ai:check --probe-web --json
```

La respuesta estructurada sintética fue correcta con `gemini-3.5-flash-lite`, mediante el mecanismo de modelos alternativos. La prueba de embeddings sintéticos devolvió un vector de 3,072 dimensiones. Esto valida conectividad, sin indexar contenido interno.

La búsqueda web sintética recibió HTTP 429 por cuota del proveedor, también al probar los modelos alternativos. El comando devuelve código de salida 1 y `web_error_category: quota_or_rate_limit`; no presenta este estado como una búsqueda exitosa. La cuota requiere atención en la cuenta del proveedor; no se modificaron facturación ni credenciales.

El modo local conserva `after_response`, con conexión de cola `sync`. No requiere un worker dedicado para ese modo. No se inspeccionó ni se modificó producción.

## Verificación automatizada

Se prueban los flujos de generación y revisión de ambos módulos, fuentes vacías, bloqueo de aprobación, importación sin duplicados, indexación y borrado por módulo, recuperación del historial, trazabilidad de procedimientos, diagnóstico sin secretos y modelos alternativos para búsqueda web. Las pruebas usan SQLite en memoria y proveedores simulados; no escriben en la base operativa.

Resultado final: **99 pruebas aprobadas y 830 aserciones**. `git diff --check` terminó sin errores. El diagnóstico local sin pruebas externas devolvió `ok: true`; la búsqueda web remota sigue marcada como limitada por cuota.

Además, se construyó sin solicitudes externas el contexto del plan real de pasteurizadora que había fallado: recuperó seis fuentes, cuatro de ellas en la lista de fuentes técnicas, y se construyó correctamente el prompt. El contenido de contingencia guardado pasa la validación estructurada corregida. Se conservó el estado `requires_information`; esta comprobación no equivale a una nueva propuesta generada por el proveedor ni a su aprobación.

Se utilizó PHP 8.4.12 de Laragon: las dependencias instaladas requieren PHP >= 8.4.1.
