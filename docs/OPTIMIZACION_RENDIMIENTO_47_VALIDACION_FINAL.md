# Validación final local — 31 de octubre de 2026

**Registro histórico anterior a la continuación 49–56.** El estado vigente está en [validación 57](OPTIMIZACION_RENDIMIENTO_57_VALIDACION_ACTUAL.md): 452 pruebas y 5384 aserciones, cero errores/fallos. Los ocho casos descritos aquí fueron atendidos posteriormente; se conserva la evidencia original.

Esta comprobación cierra los cambios de código descritos hasta la optimización 46. No equivale a desplegarlos ni a aprobar todos los hallazgos de la auditoría en producción. La [matriz de seguimiento](OPTIMIZACION_RENDIMIENTO_PROGRESO.md) conserva los parciales y pendientes.

## Regresión

PHP 8.4.12, SQLite en memoria, caché/sesión en memoria, correo array, IA e indexación automática desactivadas. Ejecución completa con `vendor/bin/phpunit --do-not-cache-result --no-output --log-junit ...`: **442 pruebas, 4903 aserciones, tres errores y cinco fallos**, 74,81 s. Informe temporal: `C:\Users\alons\AppData\Local\Temp\legado-regression-final-20261031.xml`.

Son los mismos ocho casos que se reprodujeron previamente con clases HEAD y vistas originales en un directorio temporal, sin restaurar el árbol de trabajo:

| Clase | Caso / causa observada en la base |
|---|---|
| AnalisisLavadoraUserTest | test_reductor_model_is_assigned_by_location_on_target_line: 403 del fixture sin rol. |
| ElongacionChainInstallationCostTest | test_store_chain_installation_cost_flow_covers_supported_groups_and_rollback_rules: redirección a login durante el flujo. |
| Lef52124CostImpactTest | test_cost_impact_uses_only_assigned_excel_cost_rows: ruta lef52124.impact ausente. |
| Lef52124DashboardDataTest | test_lavadoras_dashboard_renders_latest_lef_period_and_both_charts: expectativa anterior del texto LEF. |
| Lef52124LineTrendComparisonTest | test_line_trend_cards_use_washer_machine_and_monthly_chart_uses_line_total: ruta ausente. |
| Lef52124LineTrendComparisonTest | test_only_admin_can_export_filtered_line_trends_to_excel: ruta ausente. |
| NotificationControllerTest | test_target_role_open_can_view_pasteurizadora_action_plan_notification: destino notificaciones frente al plan esperado. |
| PasteurizadoraNumeracionTest | test_captura_consulta_edicion_y_exportacion_conservan_el_numero_global: texto anterior de ubicación. |

No aparecen casos fallidos nuevos. La suite completa **no está verde**; no se modifican rutas, permisos ni expectativas ajenas para ocultarlo. Una comprobación adicional del resumen de tendencias con inicio posterior al fin coincide con las consultas independientes: no se confirmó una regresión y no se añadió una corrección especulativa. La prueba conserva ese caso adicional.

`node --test tests/Frontend/*.test.mjs`: **26/26 aprobadas**, incluyendo sondeo, cancelación de periodos, doble envío y ciclo de animación. Composer validate y check-platform-reqs --lock aprobados sin actualizar paquetes. Sintaxis aprobada en los 79 PHP modificados/nuevos; el banco de solo lectura ampliado también pasa lint. Tras retirar cuatro espacios finales del diff, cuatro pruebas de vista/formulario rápido pasan con 40 aserciones y git diff --check queda limpio. No se repitió toda la suite por esa limpieza sin efecto funcional.

El último build de Vite pasa y genera siete sidecars gzip. Se verificó que las siete entradas del manifiesto existen y que cada gzip descomprime exactamente al original. Los recursos compilados no están versionados: la [entrega de assets es un requisito del despliegue](OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md).

## Integración real del diagrama

Chrome aislado, 1440 y 390 px, vista Blade/SVG real y script real. El banco `tools/generate-browser-fixtures.php` proporciona dos severidades mediante el contrato monitorAlertas, pues la vista independiente no obtiene alertas operativas ni muestra controles de pausa. La pausa manual se representa mediante su clase y sincronización; no se afirma haber pulsado un botón inexistente.

`LEGADO_DIAGRAM_VISIBILITY_PROBE=1` activa el caso opcional de `tools/browser-performance-check.mjs`. Las dos resoluciones comprueban:

- Sincronizar dos veces conserva un único intervalo.
- Fuera de pantalla: IntersectionObserver marca invisible, elimina el intervalo y mantiene pausado el SVG; tras 3,2 s conserva la severidad.
- Al regresar: reaparece un único intervalo, continúa la secuencia rojo/naranja y se conserva la pausa manual del SVG.

Ambos diagramChecks.passed son true. Artefactos temporales: `C:\Users\alons\AppData\Local\Temp\legado-browser-check-8YI3Gy`. No son mediciones de CPU, GPU, consumo energético ni fuga de memoria; Canvas conserva sus pruebas de ciclo simuladas. Las comparaciones de apariencia y modales de dashboards están descritas en 37/38/46.

## Perfiles de cierre, sin cambio adicional

`tools/benchmark-readonly-endpoints.php` admite dashboards, catalogues, histories y knowledge. Rechaza otro host y ejecuta MySQL en transacción READ ONLY con rollback; no renderiza respuestas HTTP ni mide arranque frío. Usuario y reloj fijos, sin proveedores externos.

| Preparación de datos, tres muestras calientes | Mediana | SQL |
|---|---:|---:|
| Dashboard global lavadoras | 1187,14 ms | 34 |
| Dashboard operativo lavadoras | 1433,94 ms | 34 |
| Reporte lavadoras | 1548,95 ms | 136 |
| Recuperación documental solo léxica | 152,72 ms | 10 |

Estas cifras describen el estado medido, no una comparación equivalente con la auditoría inicial. Los registros locales cambiaron durante el trabajo; no se mezclan datasets para atribuir porcentajes. Las comparaciones antes/después válidas se conservan por optimización. Los hashes completos de global/operativo coinciden con el banco pareado de 25 en esa ejecución de cierre.

La consulta léxica `guia reductor cadena` devuelve cinco resultados y el mismo hash en las tres muestras. IA y búsqueda semántica explícitamente desactivadas: no mide embedding, proveedor, calidad semántica ni respuesta final del asistente. No justifica bajar candidatos ni añadir caché compartida.

Catálogos: 71 artículos de costo, 21 reglas y cero destinatarios de alerta; ControlGastos ya pagina artículos a 15. Conteo de historiales posterior: 1045 lavadora, cuatro pasteurizadora, cero hidráulica y cero etiquetadora. No hubo escrituras nuestras sobre esos datos; el incremento del conteo de lavadora es actividad externa al banco. El historial principal de lavadoras ya se prueba con 165 filas. Los otros historiales completos y sus reglas de agrupación/ciclos quedan explícitamente pendientes de evaluación con volumen representativo, no se declaran paginados.

`maintenance:queue:check --json` devuelve exit 1 esperado: sync, sin retry_after verificable ni señales de timeout del worker en Windows. Presupuesto actual: jobs máximos 195 s, worker 240 s, retry_after mínimo 270 s, lease cron 275 s. No se cambió .env, backend ni ejecución de workers. El diagnóstico no acredita disponibilidad de infraestructura remota.
