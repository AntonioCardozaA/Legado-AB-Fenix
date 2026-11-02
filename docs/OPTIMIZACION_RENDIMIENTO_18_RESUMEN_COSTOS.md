# Optimización 18: resumen de costos para dashboard global

F07. Se explicó previamente que el dashboard llamaba dashboardData y preparaba presupuestos, pendientes, intervenciones e historial que su vista no consume. Se añade globalSummaryData sobre la misma preparación común y se retorna antes de esos detalles. DashboardController conserva el control de permisos y usa ese método; el módulo completo sigue usando dashboardData con todos sus datos.

Se conserva la lectura ordenada de gastos y los mismos cálculos de gasto anual, componente de mayor costo, más reemplazado y distribución por lavadora. No se cambian criterios monetarios, redondeos ni reglas de reemplazo. No se truncan tablas visibles ni se elimina ninguna función del módulo completo.

Comparación MySQL READ ONLY pareada, mismo proceso/datos/filtros/reloj, calentamiento de ambas variantes y cinco muestras alternadas:

| Variante | Mediana ms | Consultas | Huella de los campos consumidos |
|---|---:|---:|---|
| Preparación completa | 188,01 | 19 | 6f207b685a3a909006b1b30664c371f867e1df3968f759164d70b3a54d0b1c8f |
| Resumen global | 29,31 | 3 | Idéntica |

La huella completa de los datos internos del dashboard cambia intencionadamente al dejar de preparar campos no consumidos. Se comparan todos los campos que sí usa su Blade, identificados en la revisión de referencias. No se afirma equivalencia de un payload público que no exista.

Riesgo: perder datos visibles o alterar rankings. La prueba compara totales y rankings globales y por línea contra dashboardData con gastos de varios componentes y fuentes, verifica gasto anual esperado y que el módulo completo conserva historial/correcciones. Las otras trece pruebas de costos/estados aprobadas durante la regresión permanecen válidas; el primer fixture omitía sync_key obligatorio y se corrigió antes de validar.

Reversión: cambiar la llamada del dashboard de globalSummaryData a dashboardData. No requiere datos, DDL ni cambios de permisos. La lectura de gastos del resumen sigue siendo una colección anual; futuras mejoras de volumen requieren otra medición y equivalencia.
