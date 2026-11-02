# Optimización 30: tarjetas de estado por lotes

F02. Antes se explicó el costo restante de 53 consultas para ocho tarjetas. DashboardController carga análisis vigentes con sus componentes, elongaciones de ciclos activos actuales y conteos de planes por línea una vez por operación. Los umbrales y el cálculo de cada tarjeta permanecen en calcularEstadoLavadora. El método sin contexto conserva las consultas individuales como referencia; no existe caché entre peticiones.

Se conserva orden de líneas del llamador, activas/inactivas según ese alcance, ranking fecha/ID, ciclo activo actual mediante el mismo método del modelo, planes lavadora/null no completados, prioridades y carruseles. Seis pruebas de estados/histórico pasan (30 aserciones), incluida equivalencia completa con múltiples líneas, dos ciclos activos, desempate de mediciones y planes de otros tipos/completados.

Medición alternada de ambas variantes en el mismo proceso/snapshot MySQL READ ONLY, reloj/usuario fijos, calentamiento y tres muestras: 53 → 6 consultas; mediana 384,44 → 290,73 ms. La huella JSON completa es idéntica (fa3e14f8ccff9315cef6c9bbd68e452477cf54b9acb5e57a168a99150a3ee6c7). Es el cálculo de tarjetas, sin render/navegador; no deben sumarse los ahorros de bancos distintos.

Riesgo: atribución de ciclo o plan a otra línea. Prueba y comparación completa cubren esos casos. Reversión: getEstadoLavadoras vuelve a invocar el método individual sin contexto. No cambia datos/esquema.
