📋 CHECKLIST DE VERIFICACIÓN - POST IMPLEMENTACIÓN

═══════════════════════════════════════════════════════════════

ANTES DE EJECUTAR:

□ Verificar que todos los archivos fueron modificados correctamente
□ Realizar backup de la base de datos
□ Verificar que el servidor laragon está corriendo

═══════════════════════════════════════════════════════════════

PASO 1: EJECUTAR MIGRACIÓN

```bash
cd d:\laragon\www\legado-ab-fenix
php artisan migrate
```

Deberías ver:
✓ Migration completed successfully
✓ Tabla analisis_pasteurizadora actualizada con columna componentes_revisados

═══════════════════════════════════════════════════════════════

PASO 2: PROBAR CREACIÓN DE ANÁLISIS

1. Ir a: Pasteurizadora > Análisis > Crear Análisis
2. Seleccionar Línea (ej: P-03 o P-06)
3. Seleccionar Módulo (ej: Módulo 1)
4. **SELECCIONAR COMPONENTE** (ej: RODAJAS para P-06 o ANILLAS)

✓ Verificar:
  - Checklist aparece dinámicamente
  - Número de checkboxes = cantidad del componente
  - Nombres: "RODAJAS #1", "RODAJAS #2", etc.
  - Se puede seleccionar múltiples
  - Validación: requiere al menos 1 seleccionado

5. Llenar resto del formulario
6. Guardar análisis

═══════════════════════════════════════════════════════════════

PASO 3: VERIFICAR GUARDADO

1. Ir a listado de análisis
2. Abrir el análisis creado
3. Ver sección "Componentes Revisados"

✓ Verificar:
  - Aparece la sección con fondo indigo
  - Muestra: "✓ RODAJAS #1" y "✓ RODAJAS #2"
  - Muestra conteo: "2 de 2"
  - Campo BD tiene JSON válido

═══════════════════════════════════════════════════════════════

PASO 4: PROBAR EDICIÓN

1. Desde el análisis guardado, click "Editar"
2. Ir a sección "Componentes a revisar"

✓ Verificar:
  - Checkboxes aparecen pre-seleccionados
  - Los valores anteriores están marcados
  - Puedes cambiar las selecciones
  - Guardar actualiza correctamente

═══════════════════════════════════════════════════════════════

PASO 5: PROBAR CREATE-QUICK

1. Ir a Pasteurizadora > Crear Análisis Rápido
2. Seleccionar línea, módulo, componente
3. El sistema debe mostrar el checklist automáticamente

✓ Verificar:
  - Mismo comportamiento que create.blade.php
  - Checklist dinámico funciona
  - Validación funciona

═══════════════════════════════════════════════════════════════

PASO 6: VALIDACIÓN EN BD

1. Abrir herramienta BD (ej: phpMyAdmin)
2. Ir a tabla: analisis_pasteurizadora
3. Buscar los registros creados

✓ Verificar:
  - Columna componentes_revisados existe
  - Contiene JSON válido: [1, 2]
  - revisadas_piezas = count(componentes_revisados)

═══════════════════════════════════════════════════════════════

❌ POSIBLES ERRORES Y SOLUCIONES:

ERROR: "SQLSTATE[42S22]: Column not found"
→ Migración no ejecutada
→ SOLUCIÓN: php artisan migrate

ERROR: Checklist no aparece
→ JavaScript no se está ejecutando
→ SOLUCIÓN: Verificar consola del navegador (F12)

ERROR: "Debe seleccionar al menos un componente"
→ Validación funcionando correctamente
→ SOLUCIÓN: Seleccionar al menos un checkbox

ERROR: Checklist aparece pero vacío
→ No hay componentes mapeados
→ SOLUCIÓN: Verificar que la línea existe en PASTEURIZADORES

═══════════════════════════════════════════════════════════════

✅ ÉXITO CUANDO:

1. Migraciones ejecutadas sin errores
2. Checklist aparece al seleccionar componente
3. Se puede guardar y ver componentes seleccionados
4. BD contiene campo con JSON válido
5. Edición restaura valores previos
6. create-quick funciona igual que create

═══════════════════════════════════════════════════════════════
