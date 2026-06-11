/**
 * ANIMADOR DE DIAGRAMAS DE CADENAS
 * Sistema modular para animar cadenas industriales en Canvas
 * 
 * PUNTOS DE CONFIGURACIÃ“N:
 * - chainSpeed: velocidad de movimiento de la cadena (pixels/frame)
 * - sprocketRadius: radio de las catarinas (engranes)
 * - chainLinkSize: tamaÃ±o de cada eslabÃ³n de la cadena
 * - chainPathPoints: puntos que define el recorrido de la cadena
 * - sprockets: posiciones y radios de las catarinas
 */

function initializeDiagram(diagramId, imagePath) {
    const canvas = document.getElementById('diagram-canvas-' + diagramId);
    if (!canvas) {
        console.error('Canvas no encontrado para diagrama:', diagramId);
        return;
    }

    const ctx = canvas.getContext('2d');
    
    // Obtener la configuraciÃ³n del diagrama
    const diagramConfig = getDiagramConfig(diagramId);
    if (!diagramConfig) {
        console.error('ConfiguraciÃ³n no encontrada para diagrama:', diagramId);
        return;
    }

    // Cargar la imagen base
    const img = new Image();
    img.onload = function() {
        // Ajustar el canvas al tamaÃ±o de la imagen
        canvas.width = img.width;
        canvas.height = img.height;
        
        // ESCALAR puntos: Si estÃ¡n en porcentaje, convertir a pÃ­xeles
        const scaledConfig = JSON.parse(JSON.stringify(diagramConfig)); // Copiar config
        
        // Escalar chainPathPoints
        scaledConfig.chainPathPoints = diagramConfig.chainPathPoints.map(point => {
            return {
                x: (typeof point.x === 'string' || point.x < 1) ? (point.x * img.width) : point.x,
                y: (typeof point.y === 'string' || point.y < 1) ? (point.y * img.height) : point.y
            };
        });
        
        // Escalar sprockets
        scaledConfig.sprockets = diagramConfig.sprockets.map(sprocket => {
            return {
                x: sprocket.isPercentage ? (sprocket.x * img.width) : sprocket.x,
                y: sprocket.isPercentage ? (sprocket.y * img.height) : sprocket.y,
                radius: sprocket.radius,
                label: sprocket.label
            };
        });
        
        // Crear instancia del animador
        const animatorInstance = {
            canvas: canvas,
            ctx: ctx,
            img: img,
            config: scaledConfig,
            chainOffset: 0,
            isPlaying: false,
            animationSpeed: 1.0,
            lastFrameTime: Date.now()
        };

        // Guardar la instancia global
        window.diagramInstances[diagramId] = animatorInstance;

        // Iniciar el loop de animaciÃ³n
        animate(diagramId);
    };
    
    img.src = imagePath;
}

function getDiagramConfig(diagramId) {
    // Importar las configuraciones especÃ­ficas de cada diagrama
    const configs = {
        'line-04': getDiagramConfig_Line04(),
        'line-05': getDiagramConfig_Line05(),
        'line-06': getDiagramConfig_Line06(),
        'line-07': getDiagramConfig_Line07(),
        'line-09': getDiagramConfig_Line04(), // Mismo que line-04
        'line-12': getDiagramConfig_Line05(), // Mismo que line-05
        'line-13': getDiagramConfig_Line05(), // Mismo que line-05
    };

    return configs[diagramId] || null;
}

/**
 * CONFIGURACIÃ“N LÃNEA 04 (L-04, L-09)
 * Diagrama con mÃ¡quinas RED 09-RED 18 + Espreado + LOCA
 */
function getDiagramConfig_Line04() {
    return {
        // MODIFICABLE: Velocidad de la cadena (pixels por frame)
        chainSpeed: 3,
        
        // MODIFICABLE: TamaÃ±o de los eslabones de la cadena
        chainLinkSize: 10,
        
        // MODIFICABLE: Radio de las catarinas/engranes
        sprocketRadius: 22,
        
        // MODIFICABLE: Puntos que definen el recorrido de la cadena
        // Basados en el diagrama visual (lÃ­nea roja)
        // ESCALA: Coordenadas relativas a la imagen (se ajustan automÃ¡ticamente)
        chainPathPoints: [
            // INICIO - RED 1 (abajo a la izquierda)
            { x: 60, y: 0.95 },     // Abajo izquierda (proporciÃ³n de altura)
            
            // SUBE por lado izquierdo
            { x: 60, y: 0.45 },     // Medio izquierdo
            { x: 60, y: 0.15 },     // Arriba izquierdo
            
            // ESQUINA superior izquierda
            { x: 80, y: 0.08 },
            
            // PARTE SUPERIOR - recorre todo
            { x: 300, y: 0.06 },    // Centro superior
            { x: 800, y: 0.08 },    // Hacia la derecha
            
            // ESQUINA superior derecha
            { x: 900, y: 0.12 },
            
            // BAJA por lado derecho
            { x: 920, y: 0.35 },    // Medio derecho
            { x: 920, y: 0.95 },    // RED 19 abajo derecha
            
            // BIFURCACIÃ“N - va al Espreado (Ã¡rea derecha)
            { x: 1000, y: 0.70 },   // Hacia derecha
            { x: 1050, y: 0.50 },   // Dentro espreado
            { x: 1050, y: 0.15 },   // Sube en espreado
            
            // RETORNA
            { x: 920, y: 0.10 },    // Vuelve hacia atrÃ¡s
            { x: 300, y: 0.05 },    // Recorre hacia atrÃ¡s por arriba
            { x: 60, y: 0.08 }      // Retorna
        ],
        
        // MODIFICABLE: Posiciones de las catarinas/engranes
        sprockets: [
            {
                x: 60,      // PosiciÃ³n X (% del ancho)
                y: 0.95,    // PosiciÃ³n Y (% del alto)
                radius: 22,
                label: 'RED 1',
                isPercentage: true
            },
            {
                x: 920,
                y: 0.95,
                radius: 22,
                label: 'RED 19',
                isPercentage: true
            },
            {
                x: 60,
                y: 0.15,
                radius: 20,
                label: 'RED 9',
                isPercentage: true
            },
            {
                x: 920,
                y: 0.15,
                radius: 20,
                label: 'RED 18',
                isPercentage: true
            }
        ],
        
        // Estilos de la cadena
        chainColor: '#FF0000',
        chainOutlineColor: '#8B0000',
        chainLineWidth: 5,
        sprocketColor: '#2C3E50',
        sprocketBoltColor: '#34495E'
    };
}

/**
 * CONFIGURACIÃ“N LÃNEA 05 (L-05, L-12, L-13)
 * Diagrama con menos mÃ¡quinas (RED 02-11)
 */
function getDiagramConfig_Line05() {
    return {
        chainSpeed: 3,
        chainLinkSize: 10,
        sprocketRadius: 22,
        
        chainPathPoints: [
            // Lado izquierdo
            { x: 0.065, y: 0.95 },     // RED 1 - inicio
            { x: 0.065, y: 0.45 },     // Sube
            { x: 0.090, y: 0.10 },     // Esquina superior
            
            // Parte superior
            { x: 0.380, y: 0.06 },    // Recorre la parte superior
            { x: 0.870, y: 0.08 },    // Esquina superior derecha
            
            // Lado derecho
            { x: 0.900, y: 0.35 },    // Baja
            { x: 0.900, y: 0.95 },    // Llega abajo
            
            // BifurcaciÃ³n Espreado
            { x: 0.980, y: 0.70 },    // Va al Espreado
            { x: 1.030, y: 0.45 },    // Dentro del Espreado
            { x: 1.030, y: 0.12 },    // Sube en Espreado
            { x: 0.900, y: 0.08 },    // Retorna
            
            // Retorno
            { x: 0.380, y: 0.05 },
            { x: 0.065, y: 0.08 }
        ],
        
        sprockets: [
            { x: 0.065, y: 0.95, radius: 22, label: 'RED 1', isPercentage: true },
            { x: 0.900, y: 0.95, radius: 22, label: 'RED 11', isPercentage: true },
            { x: 0.065, y: 0.10, radius: 20, label: 'RED 2', isPercentage: true },
            { x: 0.900, y: 0.10, radius: 20, label: 'RED 10', isPercentage: true }
        ],
        
        chainColor: '#FF0000',
        chainOutlineColor: '#8B0000',
        chainLineWidth: 5,
        sprocketColor: '#2C3E50',
        sprocketBoltColor: '#34495E'
    };
}

/**
 * CONFIGURACIÃ“N LÃNEA 06 (L-06, L-07)
 * Diagrama con mÃ¡s mÃ¡quinas (RED 09-20)
 */
function getDiagramConfig_Line06() {
    return {
        chainSpeed: 3,
        chainLinkSize: 10,
        sprocketRadius: 22,
        
        chainPathPoints: [
            // Lado izquierdo
            { x: 0.055, y: 0.95 },
            { x: 0.055, y: 0.45 },
            { x: 0.085, y: 0.10 },
            
            // Parte superior principal
            { x: 0.450, y: 0.06 },
            { x: 0.900, y: 0.08 },
            
            // Lado derecho
            { x: 0.930, y: 0.35 },
            { x: 0.930, y: 0.95 },
            
            // BifurcaciÃ³n al RED 21 (Espreado derecho)
            { x: 1.010, y: 0.70 },
            { x: 1.060, y: 0.45 },
            { x: 1.060, y: 0.12 },
            { x: 0.930, y: 0.08 },
            
            // Retorno
            { x: 0.450, y: 0.05 },
            { x: 0.055, y: 0.08 }
        ],
        
        sprockets: [
            { x: 0.055, y: 0.95, radius: 22, label: 'RED 1', isPercentage: true },
            { x: 0.930, y: 0.95, radius: 22, label: 'RED 20', isPercentage: true },
            { x: 0.055, y: 0.10, radius: 20, label: 'RED 9', isPercentage: true },
            { x: 0.930, y: 0.10, radius: 20, label: 'RED 19', isPercentage: true },
            { x: 1.060, y: 0.45, radius: 18, label: 'RED 21', isPercentage: true }
        ],
        
        chainColor: '#FF0000',
        chainOutlineColor: '#8B0000',
        chainLineWidth: 5,
        sprocketColor: '#2C3E50',
        sprocketBoltColor: '#34495E'
    };
}

/**
 * CONFIGURACIÃ“N LÃNEA 07 (IdÃ©ntica a LÃ­nea 06)
 */
function getDiagramConfig_Line07() {
    return getDiagramConfig_Line06();
}

/**
 * FUNCIÃ“N PRINCIPAL DE ANIMACIÃ“N
 * Loop que se ejecuta continuamente para animar la cadena
 */
function animate(diagramId) {
    const instance = window.diagramInstances[diagramId];
    if (!instance) return;

    const { canvas, ctx, img, config, isPlaying, animationSpeed } = instance;

    // Limpiar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Dibujar imagen de fondo
    ctx.drawImage(img, 0, 0);

    // Si estÃ¡ en pausa, saltar animaciÃ³n de cadena
    if (isPlaying) {
        // Actualizar posiciÃ³n de la cadena
        // MODIFICABLE: Cambiar la velocidad multiplicando chainSpeed
        instance.chainOffset = (instance.chainOffset + config.chainSpeed * animationSpeed) % getTotalChainLength(config.chainPathPoints, config.chainLinkSize);

        // Dibujar cadena animada
        drawAnimatedChain(ctx, config, instance.chainOffset);

        // Dibujar catarinas/engranes
        drawSprockets(ctx, config, instance.chainOffset);
    }

    // Solicitar siguiente frame
    requestAnimationFrame(() => animate(diagramId));
}

/**
 * Calcula la longitud total del recorrido de la cadena
 */
function getTotalChainLength(pathPoints, linkSize) {
    let totalLength = 0;
    
    for (let i = 0; i < pathPoints.length; i++) {
        const current = pathPoints[i];
        const next = pathPoints[(i + 1) % pathPoints.length];
        
        const dx = next.x - current.x;
        const dy = next.y - current.y;
        totalLength += Math.sqrt(dx * dx + dy * dy);
    }
    
    return totalLength;
}

/**
 * Dibuja la cadena animada siguiendo el recorrido
 * MODIFICABLE: Cambiar estilo de la cadena (color, grosor, etc.)
 */
function drawAnimatedChain(ctx, config, chainOffset) {
    const pathPoints = config.chainPathPoints;
    const linkSize = config.chainLinkSize;
    const chainColor = config.chainColor;
    const chainOutlineColor = config.chainOutlineColor;
    const chainLineWidth = config.chainLineWidth;

    // Dibujar lÃ­nea base de la cadena (sombra/fondo)
    ctx.strokeStyle = chainOutlineColor;
    ctx.lineWidth = chainLineWidth + 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    ctx.beginPath();
    ctx.moveTo(pathPoints[0].x, pathPoints[0].y);
    for (let i = 1; i < pathPoints.length; i++) {
        ctx.lineTo(pathPoints[i].x, pathPoints[i].y);
    }
    ctx.closePath();
    ctx.stroke();

    // Dibujar la cadena como serie de eslabones MÃS VISIBLES
    ctx.strokeStyle = chainColor;
    ctx.lineWidth = chainLineWidth + 1;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    let currentDistance = -chainOffset;
    let totalDistance = getTotalChainLength(pathPoints, linkSize);

    // Dibujar eslabones como segmentos conectados
    while (currentDistance < totalDistance) {
        const point = getPointAtDistance(pathPoints, currentDistance + chainOffset);
        const nextPoint = getPointAtDistance(pathPoints, currentDistance + chainOffset + linkSize * 0.8);

        if (point && nextPoint) {
            // Dibujar eslabÃ³n principal
            ctx.beginPath();
            ctx.moveTo(point.x, point.y);
            ctx.lineTo(nextPoint.x, nextPoint.y);
            ctx.stroke();

            // Dibujar punto de conexiÃ³n (remache)
            ctx.fillStyle = chainColor;
            ctx.beginPath();
            ctx.arc(point.x, point.y, chainLineWidth * 0.8, 0, Math.PI * 2);
            ctx.fill();
        }

        currentDistance += linkSize * 1.5;
    }
}


/**
 * Obtiene un punto en el recorrido a una distancia especÃ­fica
 */
function getPointAtDistance(pathPoints, distance) {
    let currentDistance = 0;

    for (let i = 0; i < pathPoints.length; i++) {
        const current = pathPoints[i];
        const next = pathPoints[(i + 1) % pathPoints.length];

        const dx = next.x - current.x;
        const dy = next.y - current.y;
        const segmentLength = Math.sqrt(dx * dx + dy * dy);

        if (currentDistance + segmentLength >= distance) {
            const ratio = (distance - currentDistance) / segmentLength;
            return {
                x: current.x + dx * ratio,
                y: current.y + dy * ratio
            };
        }

        currentDistance += segmentLength;
    }

    return null;
}

/**
 * Dibuja las catarinas/engranes que giran con la cadena
 * MODIFICABLE: Cambiar tamaÃ±o, color y cantidad de dientes
 */
function drawSprockets(ctx, config, rotation) {
    config.sprockets.forEach(sprocket => {
        // Dibujar cÃ­rculo principal
        ctx.fillStyle = config.sprocketColor;
        ctx.beginPath();
        ctx.arc(sprocket.x, sprocket.y, sprocket.radius, 0, Math.PI * 2);
        ctx.fill();

        // Dibujar dientes/pernos
        const boltCount = 12;
        const boltRadius = sprocket.radius * 0.6;
        
        for (let i = 0; i < boltCount; i++) {
            const angle = (i / boltCount) * Math.PI * 2 + rotation * 0.05;
            const boltX = sprocket.x + Math.cos(angle) * boltRadius;
            const boltY = sprocket.y + Math.sin(angle) * boltRadius;

            ctx.fillStyle = config.sprocketBoltColor;
            ctx.beginPath();
            ctx.arc(boltX, boltY, 4, 0, Math.PI * 2);
            ctx.fill();
        }

        // Dibujar etiqueta
        ctx.fillStyle = '#FFF';
        ctx.font = 'bold 12px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(sprocket.label, sprocket.x, sprocket.y);
    });
}

