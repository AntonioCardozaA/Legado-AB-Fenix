@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Diagramas Animados de Lavadoras</h1>
            <p class="text-muted">Cadenas industriales con animación en tiempo real</p>
        </div>
    </div>

    <!-- TAB NAVIGATION -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="line04-tab" data-bs-toggle="tab" data-bs-target="#line04" type="button">
                Línea 04 (L-04, L-09)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="line05-tab" data-bs-toggle="tab" data-bs-target="#line05" type="button">
                Línea 05 (L-05, L-12, L-13)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="line06-tab" data-bs-toggle="tab" data-bs-target="#line06" type="button">
                Línea 06 (L-06, L-07)
            </button>
        </li>
    </ul>

    <!-- TAB CONTENT -->
    <div class="tab-content">
        
        <!-- LÍNEA 04 -->
        <div class="tab-pane fade show active" id="line04" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <x-diagram-animator 
                        diagramId="line-04"
                        imagePath="{{ asset('images/Diagramas-Lavadoras/linea4.png') }}"
                        title="Línea 04 y 09 - Diagrama de Cadena"
                    />
                </div>
            </div>
        </div>

        <!-- LÍNEA 05 -->
        <div class="tab-pane fade" id="line05" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <x-diagram-animator 
                        diagramId="line-05"
                        imagePath="{{ asset('images/Diagramas-Lavadoras/linea5.png') }}"
                        title="Línea 05, 12 y 13 - Diagrama de Cadena"
                    />
                </div>
            </div>
        </div>

        <!-- LÍNEA 06 -->
        <div class="tab-pane fade" id="line06" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <x-diagram-animator 
                        diagramId="line-06"
                        imagePath="{{ asset('images/Diagramas-Lavadoras/linea6.png') }}"
                        title="Línea 06 y 07 - Diagrama de Cadena"
                    />
                </div>
            </div>
        </div>

    </div>

    <!-- INFORMACIÓN -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="alert alert-info">
                <h5>ℹ️ Información sobre los Diagramas</h5>
                <p>
                    Los diagramas animados muestran el recorrido de la cadena industrial en cada línea de lavadoras.
                    Puedes controlar la animación con los botones debajo de cada diagrama:
                </p>
                <ul>
                    <li><strong>▶ Iniciar animación</strong> - Comienza o pausa la cadena</li>
                    <li><strong>↻ Reiniciar</strong> - Vuelve al inicio</li>
                    <li><strong>Velocidad</strong> - Ajusta la rapidez de la cadena (0.5x a 3.0x)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- GUÍA TÉCNICA -->
    <div class="row mt-4">
        <div class="col-12">
            <details class="border p-3 rounded">
                <summary><strong>💻 Información Técnica para Desarrolladores</strong></summary>
                
                <div class="mt-3">
                    <h6>Cómo agregar nuevos diagramas:</h6>
                    <ol>
                        <li>Coloca la imagen PNG en <code>public/images/Diagramas-Lavadoras/</code></li>
                        <li>Edita <code>public/js/diagram-animator/chain-animator.js</code></li>
                        <li>Crea una función <code>getDiagramConfig_LineXX()</code> con:
                            <ul>
                                <li>chainSpeed: velocidad de movimiento</li>
                                <li>chainLinkSize: tamaño de eslabones</li>
                                <li>chainPathPoints: puntos del recorrido (sigue la línea roja)</li>
                                <li>sprockets: posiciones de catarinas</li>
                            </ul>
                        </li>
                        <li>Registra el ID en <code>getDiagramConfig()</code></li>
                        <li>Usa en una vista Blade:
                            <pre>&lt;x-diagram-animator diagramId="line-XX" imagePath="{{ asset('images/Diagramas-Lavadoras/lineaxx.png') }}" title="Línea XX" /&gt;</pre>
                        </li>
                    </ol>

                    <h6 class="mt-3">Puntos de configuración modificables:</h6>
                    <ul>
                        <li><strong>chainSpeed</strong> (línea ~120): 1-3 (más rápido)</li>
                        <li><strong>chainLinkSize</strong> (línea ~122): 6-12 (tamaño eslabones)</li>
                        <li><strong>chainPathPoints</strong> (línea ~126): array con { x, y } puntos</li>
                        <li><strong>sprockets</strong> (línea ~148): posiciones de catarinas</li>
                        <li><strong>chainColor</strong>: código hex del color (#FF0000 rojo)</li>
                    </ul>

                    <h6 class="mt-3">Documentación:</h6>
                    <p>
                        Lee <code>resources/configs/diagrams/README.md</code> para una guía completa
                        sobre cómo crear nuevos diagramas y personalizarlos.
                    </p>
                </div>
            </details>
        </div>
    </div>

</div>

<!-- Cargar el script de animación -->
<script src="{{ asset('js/diagram-animator/chain-animator.js') }}"></script>

@endsection

@push('styles')
<style>
    .diagram-animator-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .diagram-canvas-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #f8f9fa;
        border-radius: 4px;
        padding: 10px;
    }

    .diagram-canvas {
        max-width: 100%;
        height: auto;
        border: 1px solid #dee2e6;
    }

    .diagram-controls {
        margin-top: 20px;
    }

    .form-range {
        display: inline-block;
        max-width: 200px;
        vertical-align: middle;
    }

    @media (max-width: 768px) {
        .diagram-animator-container {
            padding: 15px;
        }

        .diagram-controls button {
            margin-bottom: 10px;
            width: 100%;
            font-size: 0.9rem;
        }

        .form-range {
            width: 100%;
            max-width: 100%;
        }
    }
</style>
@endpush
