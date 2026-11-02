# Corrección 51: fixtures y contratos visuales de regresión

No es una mejora de latencia. Se explicó antes de cambiar cuatro archivos de pruebas; no se modifica código de producción para hacerlas pasar.

- AnalisisLavadoraUserTest: la asignación de modelo de reductor se ejecutaba con usuario sin rol y obtenía 403 antes de evaluar el comportamiento. Usa ahora rol técnico, como los otros casos de captura; conserva las tres ubicaciones/modelos y la comprobación de base de datos.
- ElongacionChainInstallationCostTest: el flujo de costos no autenticaba usuario en rutas protegidas. Autentica un admin de fixture y finge HTTP para impedir mensajes externos. Conserva cantidades, totales por SKU, grupos de líneas, continuidad y los rollbacks por catálogo/precio inválidos. No desactiva middleware.
- Lef52124DashboardDataTest: la etiqueta Solo lavadora ya no existe. La prueba verifica el canvas específico de lavadora y también el de Pareto; conserva periodo, título Pareto, selector y ausencia del gráfico anterior.
- PasteurizadoraNumeracionTest: la ubicación real es Ubicacion: #7 Lado Vapor - Superior. Se comprueba esa ubicación dentro de una misma info-line del DOM. Conserva creación, edición, número global, porcentaje, historial y mapeo Excel. Un primer ajuste esperaba el formato del carrusel (Nivel Superior), pero el fixture es del bloque normal; se corrigió leyendo el DOM real, sin cambiar la UI.

Veintisiete pruebas pasan, 273 aserciones. Los cuatro casos fallidos de base correspondientes pasan con las mismas comprobaciones de negocio; se añaden verificaciones del segundo canvas y de existencia de ubicaciones DOM. Los otros casos de base se atienden por separado. No se atribuye ahorro de tiempo a corregir fixtures y no se elimina ninguna prueba.
