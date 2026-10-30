# Base interna de operación: pasteurizadoras y central hidráulica

Versión: 2026-10-28. Procedencia: documentación y comportamiento implementado en LEGADO AB FENIX.

Este documento describe el registro, la clasificación y el seguimiento dentro del sistema. Es un procedimiento interno de uso de la plataforma. No es un manual del fabricante, una ficha de ingeniería ni una especificación de reparación. Los límites, ajustes, lubricantes, materiales y repuestos deben verificarse con documentación del equipo y personal responsable.

## Inspección mecánica de pasteurizadoras

En Pasteurizadoras > Análisis Pasteurizadora se seleccionan equipo, componente, módulo, nivel y lado según la configuración disponible. Se registran fecha, estado, actividad, observaciones y evidencia fotográfica. Las inspecciones conservan su ubicación y su historial; una nueva inspección no prueba por sí sola que una reparación anterior haya sido eficaz.

Los componentes revisados se identifican mediante números consecutivos del equipo y tipo de pieza. La selección debe pertenecer al módulo configurado. La capacidad del módulo se conserva separada del total del equipo. Las reinspecciones pueden referirse a un mismo número y forman parte del historial válido. La secuencia y las cantidades se consultan en la configuración del equipo; no se extrapolan entre máquinas.

## Central hidráulica

En Pasteurizadoras > Central Hidráulica se seleccionan línea, componente del catálogo, configuración, piso y lado. Se capturan estado, actividad, observaciones, fecha y fotografías. El componente de central hidráulica pertenece a su propio catálogo; su ID no se interpreta como un ID del catálogo mecánico general.

Los hallazgos deben conservar el área central_hidraulica, piso, lado, código y nombre del componente. Las recomendaciones de la parte mecánica y de central hidráulica deben distinguirse por ubicación y área. Este documento no establece presiones de trabajo, viscosidades, pares de apriete ni compatibilidad de electroválvulas o sellos.

## Clasificación de hallazgos y propuestas de IA

El motor de reglas genera eventos a partir de los estados capturados: dañado implica severidad crítica; desgaste severo, alta; desgaste moderado o requiere revisión, media. Esta severidad corresponde a la clasificación del sistema y no sustituye la evaluación en campo.

El evento conserva el análisis de origen y su ubicación. La propuesta de plan incluye problema, justificación, acciones recomendadas, riesgos, prioridad, fecha sugerida, costo estimado y fuentes. Los costos son estimaciones y no cotizaciones. Si faltan fuentes o antecedentes, la propuesta debe indicar la información pendiente y no inventar reparaciones, resultados, límites ni refacciones.

## Revisión, ejecución y cierre

La IA propone; el responsable autorizado revisa, aprueba, rechaza o solicita información. Cuando se solicita información, se puede regenerar la sugerencia incorporando las observaciones del revisor. Se conserva la propuesta original y el historial de revisión. Una propuesta sin fuentes verificables requiere evidencia antes de aprobarse.

Al ejecutar un plan se registra responsable, fecha, resultado, costo real, horas reales y eficacia cuando estén disponibles. Aprobar no significa ejecutar y ejecutar no significa que el resultado haya sido eficaz. Para recomendaciones posteriores deben distinguirse planes pendientes, aprobados y completados, así como resultados efectivos, parciales o ineficaces.

## Fuentes y alcance

- docs/GUIA_USO_E_IMAGENES.md, sección Módulo Pasteurizadoras: captura y evidencia.
- docs/PASTEURIZADORA_NUMERACION.md: numeración y alcance por módulo.
- app/Services/Maintenance/PasteurizadoraMaintenanceRuleEngine.php: clasificación de eventos.
- app/Services/Maintenance/PasteurizadoraActionPlanPromptBuilder.php: alcance de las propuestas.
- app/Http/Controllers/PasteurizadoraAiPlanReviewController.php: revisión y regeneración.
- app/Http/Controllers/PlanAccionController.php: cierre y resultado de ejecución.

Para agregar conocimiento técnico específico deben cargarse manuales, procedimientos aprobados y fichas reales, indicando equipo, área, versión y vigencia. Este procedimiento interno no acredita especificaciones de fabricante que no estén documentadas.
