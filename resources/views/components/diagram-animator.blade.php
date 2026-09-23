@props([
    'diagramId' => 'line-04',
    'imagePath' => 'images/Diagramas-Lavadoras/linea4.png',
    'title' => 'Línea 04'
])

<div class="diagram-animator-container" style="max-width: 100%; margin: 0 auto;">
    <h3 class="text-center mb-4">{{ $title }}</h3>
    
    <div class="diagram-canvas-wrapper" style="position: relative; display: inline-block; width: 100%; max-width: 100%;">
        <!-- Canvas para la animación -->
        <canvas 
            id="diagram-canvas-{{ $diagramId }}"
            class="diagram-canvas"
            style="
                display: block;
                border: 2px solid #ccc;
                width: 100%;
                height: auto;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
            "
        ></canvas>
    </div>

    <!-- Controles -->
    <div class="diagram-controls mt-4 text-center">
        <button 
            id="toggle-{{ $diagramId }}"
            class="btn btn-sm btn-primary"
            onclick="toggleDiagramAnimation('{{ $diagramId }}')"
        >
            ▶ Iniciar animación
        </button>
        
        <button 
            id="reset-{{ $diagramId }}"
            class="btn btn-sm btn-secondary ms-2"
            onclick="resetDiagramAnimation('{{ $diagramId }}')"
        >
            ↻ Reiniciar
        </button>

        <!-- Control de velocidad -->
        <div class="mt-3">
            <label for="speed-{{ $diagramId }}" class="form-label">Velocidad:</label>
            <input 
                type="range"
                id="speed-{{ $diagramId }}"
                class="form-range"
                min="0.5"
                max="3"
                step="0.1"
                value="1"
                onchange="setDiagramSpeed('{{ $diagramId }}', this.value)"
                style="max-width: 200px;"
            />
            <span id="speed-value-{{ $diagramId }}" class="ms-2">1.0x</span>
        </div>
    </div>
</div>

<script>
    // Variables globales para cada diagrama
    window.diagramInstances = window.diagramInstances || {};
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initializeDiagram('{{ $diagramId }}', '{{ $imagePath }}');
    });

    function toggleDiagramAnimation(diagramId) {
        if (window.diagramInstances[diagramId]) {
            window.diagramInstances[diagramId].isPlaying = !window.diagramInstances[diagramId].isPlaying;
            const button = document.getElementById('toggle-' + diagramId);
            button.textContent = window.diagramInstances[diagramId].isPlaying ? '⏸ Pausar' : '▶ Iniciar animación';
        }
    }

    function resetDiagramAnimation(diagramId) {
        if (window.diagramInstances[diagramId]) {
            window.diagramInstances[diagramId].chainOffset = 0;
            window.diagramInstances[diagramId].isPlaying = false;
            const button = document.getElementById('toggle-' + diagramId);
            button.textContent = '▶ Iniciar animación';
        }
    }

    function setDiagramSpeed(diagramId, speed) {
        if (window.diagramInstances[diagramId]) {
            window.diagramInstances[diagramId].animationSpeed = parseFloat(speed);
            document.getElementById('speed-value-' + diagramId).textContent = parseFloat(speed).toFixed(1) + 'x';
        }
    }
</script>
