# SISTEMA DE ANIMACIÓN DE DIAGRAMAS DE CADENAS
## Guía de Configuración y Uso

Este sistema permite animar diagramas de cadenas industriales usando Canvas en HTML5, con una imagen de fondo y cadena animada que sigue un recorrido específico.

---

## 📁 ESTRUCTURA DE ARCHIVOS

```
resources/
  └── views/
        └── components/
              └── diagram-animator.blade.php    (Componente reutilizable)
  
  └── configs/
        └── diagrams/
              └── README.md                     (Este archivo)

public/
  └── js/
        └── diagram-animator/
              └── chain-animator.js             (Lógica principal de animación)
  
  └── images/
        └── Diagramas-Lavadoras/
              ├── linea4.png                    (Imagen L-04, L-09)
              ├── linea5.png                    (Imagen L-05, L-12, L-13)
              ├── linea6.png                    (Imagen L-06, L-07)
              └── linea7.png                    (Imagen L-06, L-07)
```

---

## 🚀 CÓMO USAR

### 1. IMPLEMENTACIÓN BÁSICA EN UNA VISTA BLADE

Incluye el script en tu layout (generalmente `app.blade.php`):

```blade
<!-- En <head> o antes de </body> -->
<script src="{{ asset('js/diagram-animator/chain-animator.js') }}"></script>
```

Luego usa el componente en cualquier vista:

```blade
<x-diagram-animator 
    diagramId="line-04"
    imagePath="{{ asset('images/Diagramas-Lavadoras/linea4.png') }}"
    title="Línea de Lavadora 04 y 09"
/>
```

---

## 🔧 PARÁMETROS DEL COMPONENTE

El componente `diagram-animator.blade.php` acepta:

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `diagramId` | String | ID único del diagrama (ej: `line-04`) |
| `imagePath` | String | Ruta de la imagen PNG del diagrama |
| `title` | String | Título mostrado sobre el diagrama |

---

## ⚙️ CÓMO CONFIGURAR UN NUEVO DIAGRAMA

### PASO 1: Obtener las dimensiones de la imagen

1. Abre la imagen del diagrama
2. Verifica sus dimensiones (ancho x alto en pixels)
3. Identifica visualmente el recorrido de la cadena (línea roja)

### PASO 2: Crear la función de configuración

En `chain-animator.js`, añade una nueva función de configuración:

```javascript
/**
 * CONFIGURACIÓN LÍNEA XX (L-XX)
 * Descripción del diagrama
 */
function getDiagramConfig_LineXX() {
    return {
        // MODIFICABLE: Velocidad de la cadena (pixels por frame)
        // Mayor número = cadena más rápida
        chainSpeed: 2,
        
        // MODIFICABLE: Tamaño de los eslabones de la cadena
        // Mayor número = eslabones más grandes
        chainLinkSize: 8,
        
        // MODIFICABLE: Radio de las catarinas/engranes
        sprocketRadius: 20,
        
        // MODIFICABLE: Puntos que definen el recorrido
        // Sigue el recorrido rojo en la imagen
        chainPathPoints: [
            { x: 100, y: 500 },   // Punto inicial
            { x: 100, y: 150 },   // Sube
            { x: 800, y: 100 },   // Parte superior
            { x: 800, y: 500 },   // Baja
            { x: 100, y: 500 }    // Retorna al inicio
        ],
        
        // MODIFICABLE: Posiciones de catarinas
        sprockets: [
            {
                x: 100,           // Posición X
                y: 500,           // Posición Y
                radius: 20,       // Radio
                label: 'RED 1'    // Etiqueta
            },
            {
                x: 800,
                y: 500,
                radius: 20,
                label: 'RED XX'
            }
        ],
        
        // Estilos
        chainColor: '#FF0000',
        chainOutlineColor: '#8B0000',
        chainLineWidth: 4,
        sprocketColor: '#2C3E50',
        sprocketBoltColor: '#34495E'
    };
}
```

### PASO 3: Registrar la nueva configuración

En la función `getDiagramConfig()`, agrega tu nueva línea:

```javascript
function getDiagramConfig(diagramId) {
    const configs = {
        'line-04': getDiagramConfig_Line04(),
        'line-05': getDiagramConfig_Line05(),
        'line-06': getDiagramConfig_Line06(),
        'line-07': getDiagramConfig_Line07(),
        'line-XX': getDiagramConfig_LineXX(),  // ← NUEVA LÍNEA
    };

    return configs[diagramId] || null;
}
```

### PASO 4: Usar el nuevo diagrama

En tu vista Blade:

```blade
<x-diagram-animator 
    diagramId="line-XX"
    imagePath="{{ asset('images/Diagramas-Lavadoras/linea-xx.png') }}"
    title="Línea XX"
/>
```

---

## 📐 CÓMO CALCULAR LOS PUNTOS DEL RECORRIDO

### Método Visual

1. **Abre la imagen en un editor (Photoshop, GIMP, Paint, etc.)**
2. **Activa las coordenadas del cursor** para ver X, Y
3. **Sigue el contorno rojo** del diagrama y registra los puntos clave:
   - Esquinas
   - Donde sube/baja
   - Donde entra/sale de máquinas
   - Bifurcaciones

### Ejemplo para Línea 04

```
Recorrido:
RED 1 (70, 610) → Sube → (70, 200) → Esquina → (120, 100)
      → Recorre arriba → (600, 80) → ... → RED 19 (1200, 610)
      → Bifurcación Espreado → LOCA → Retorna
```

### Herramienta Recomendada

Usa un editor con herramienta de medir:
- **Paint**: Hover para ver coordenadas
- **GIMP**: Ver → Mostrar coordenadas del puntero
- **Photoshop**: Window → Info
- **Navegador**: Abre DevTools, inspecciona el canvas

---

## 🎨 PERSONALIZACIÓN

### Cambiar velocidad de la cadena

En la configuración:
```javascript
chainSpeed: 2,  // Cambiar a 1 para más lento, 3 para más rápido
```

### Cambiar tamaño de eslabones

```javascript
chainLinkSize: 8,  // Cambiar a 10 para más grandes, 6 para más pequeños
```

### Cambiar color de la cadena

```javascript
chainColor: '#FF0000',        // Rojo
chainOutlineColor: '#8B0000', // Rojo oscuro
```

### Cambiar apariencia de catarinas

```javascript
sprocketColor: '#2C3E50',     // Color principal
sprocketBoltColor: '#34495E'  // Color de pernos
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### La cadena no se mueve
- Verifica que `isPlaying` sea `true`
- Haz clic en "Iniciar animación"

### La cadena no sigue el recorrido
- Revisa los puntos en `chainPathPoints`
- Aumenta la cantidad de puntos para más precisión

### Los eslabones se ven amontonados
- Aumenta `chainLinkSize` en la configuración

### La imagen se ve pixelada
- Verifica la resolución de la imagen original
- Aumenta las dimensiones del Canvas si es necesario

### El Canvas no ocupa todo el ancho
- Verifica el CSS en el componente Blade
- Ajusta `max-width` si es necesario

---

## 📋 LISTA DE CONFIGURACIONES EXISTENTES

✅ **Línea 04** (L-04, L-09) - `getDiagramConfig_Line04()`
✅ **Línea 05** (L-05, L-12, L-13) - `getDiagramConfig_Line05()`
✅ **Línea 06** (L-06, L-07) - `getDiagramConfig_Line06()`
✅ **Línea 07** (igual a 06) - `getDiagramConfig_Line07()`

---

## 🔄 PRÓXIMAS MEJORAS SUGERIDAS

- [ ] Agregar configurable el número de pernos en catarinas
- [ ] Permitir configuración via JSON
- [ ] Agregar animación de dirección variable (clockwise/counter)
- [ ] Agregar pausa automática
- [ ] Exportar diagrama como imagen/video

---

## 📝 NOTAS IMPORTANTES

- **No modifiques** controladores, rutas ni base de datos
- **Las imágenes originales** se conservan como fondo
- **El sistema es responsive** y se adapta a diferentes pantallas
- **Compatible** con navegadores modernos (Chrome, Firefox, Safari, Edge)

---

## 📧 SOPORTE

Para agregar más diagramas o realizar personalizaciones, asegúrate de:
1. Tener la imagen del diagrama en `public/images/Diagramas-Lavadoras/`
2. Crear una función de configuración en `chain-animator.js`
3. Registrar el ID en `getDiagramConfig()`
4. Usar el componente Blade con el ID correcto
