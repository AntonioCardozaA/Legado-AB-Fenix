# 📋 Guía de Uso: Checklist Dinámico de Componentes

## ¿Qué es el Checklist Dinámico?

Es una nueva funcionalidad que **genera automáticamente una lista de componentes** basada en la cantidad de unidades disponibles en cada módulo. 

**Ejemplo:**
- Si seleccionas **RODAJAS** (que tiene 2 unidades), aparecerán dos opciones:
  - ☐ RODAJAS #1
  - ☐ RODAJAS #2

---

## Cómo Usar

### 1️⃣ **Crear un Análisis**

1. Ve a **Pasteurizadora > Análisis > Crear Análisis**
2. Selecciona la **Línea** (Ej: P-03)
3. Selecciona el **Módulo** (Ej: Módulo 1, 2, 3...)
4. **IMPORTANTE:** Selecciona el **Componente** (Ej: RODAJAS)

### 2️⃣ **El Checklist Aparece**

Una vez que selecciones el componente, **automáticamente aparecerá un checklist** mostrando cada unidad del componente:

```
☐ RODAJAS #1
☐ RODAJAS #2
```

### 3️⃣ **Selecciona qué Revisar**

✅ Haz click en los checkbox de las unidades que revisarás
✅ Puedes seleccionar 1, 2 o todas las unidades
✅ El sistema muestra cuántas seleccionaste

**Ejemplo:**
```
✓ RODAJAS #1  (seleccionada)
☐ RODAJAS #2  (no seleccionada)
```

### 4️⃣ **Completa el Formulario**

Llena el resto del formulario normalmente:
- Nivel (Superior/Inferior)
- Lado (Vapor/Pasillo)
- Fecha
- Estado
- Actividad realizada
- Fotos

### 5️⃣ **Guarda**

Al hacer click en **"Guardar Análisis"**, el sistema valida que:
✅ Hayas seleccionado al menos un componente en el checklist
✅ Todos los datos requeridos estén completos

---

## Ver el Análisis Guardado

Cuando veas el análisis después de guardado:

**Sección "Componentes Revisados":**
```
✓ RODAJAS #1
✓ RODAJAS #2
```

Mostrará exactamente cuáles seleccionaste.

---

## Editar un Análisis

1. Abre el análisis guardado
2. Haz click en **"Editar"**
3. El checklist mostrará las selecciones previas **ya marcadas**
4. Puedes cambiar las selecciones si es necesario
5. Guarda los cambios

---

## Componentes con la Nueva Función

### Pasteurizadores SENCILLOS:
- **ANILLAS** (3 unidades)
- **EXCENTRICOS** (2 unidades)
- **PISTAS** (2 unidades)
- **VIGAS_FIJAS** (4 unidades)
- **VIGA_MOVIMIENTO** (1 unidad)
- **PLACAS_PERNO** (3 unidades)
- **ESPARRAGOS** (2 unidades)

### Pasteurizadores DOBLES:
- **ANILLAS** (5 unidades)
- **RODAJAS** (2 unidades) ← *Ejemplo de la documentación*
- **EXCENTRICOS** (2 unidades)
- **PISTAS** (4 unidades)
- **PLACAS_PERNO** (5 unidades)
- **VIGAS_MOVIMIENTO** (2 unidades)
- **ESPARRAGOS** (4 unidades)

---

## ✨ Ventajas

✅ **Precisión:** Sabe exactamente qué unidad revisaste
✅ **Automático:** Lista generada automáticamente según el componente
✅ **Flexible:** Revisar 1, 2 o todas las unidades
✅ **Rastreable:** Queda registro de qué se revisó en cada análisis
✅ **Fácil de usar:** Interface clara y intuitiva

---

## 🆘 Preguntas Frecuentes

**P: ¿Qué pasa si no se en el checklist?**
A: El sistema te mostrará un error: "Debe seleccionar al menos un componente a revisar"

**P: ¿Puedo cambiar los números después?**
A: Sí, puedes editar el análisis y modificar las selecciones

**P: ¿Funciona con componentes de una sola unidad?**
A: Sí, mostrará solo una opción (Ej: VIGA_MOVIMIENTO #1)

**P: ¿Se guarda en la base de datos?**
A: Sí, se guarda automáticamente junto con el análisis

---

## Flujo Visual

```
Seleccionar Componente
         ↓
Checklist aparece automáticamente
         ↓
Marcar qué revisar
         ↓
Llenar resto del formulario
         ↓
Guardar
         ↓
Ver en "Componentes Revisados"
         ↓
Editar si es necesario
```

---

**¡La nueva función está lista para usar! 🚀**
