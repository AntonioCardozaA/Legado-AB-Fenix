@extends('layouts.app')

@section('content')
<div class="container-fluid mt-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-2">🎬 Diagramas Animados - Cadenas Industriales</h1>
            <p class="text-muted mb-4">Haz clic en "Iniciar animación" para ver la cadena en movimiento</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="tab-04" data-bs-toggle="tab" 
                            data-bs-target="#diagram-04" type="button">
                        📊 Línea 04 (L-04, L-09)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-05" data-bs-toggle="tab" 
                            data-bs-target="#diagram-05" type="button">
                        📊 Línea 05 (L-05, L-12, L-13)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-06" data-bs-toggle="tab" 
                            data-bs-target="#diagram-06" type="button">
                        📊 Línea 06 (L-06, L-07)
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- LÍNEA 04 -->
                <div class="tab-pane fade show active" id="diagram-04">
                    <x-diagram-animator 
                        diagramId="line-04"
                        imagePath="{{ asset('images/Diagramas-Lavadoras/linea4.png') }}"
                        title="Línea 04 y 09 - Cadena Animada"
                    />
                </div>

                <!-- LÍNEA 05 -->
                <div class="tab-pane fade" id="diagram-05">
                    <x-diagram-animator 
                        diagramId="line-05"
                        imagePath="{{ asset('images/Diagramas-Lavadoras/linea5.png') }}"
                        title="Línea 05, 12 y 13 - Cadena Animada"
                    />
                </div>

                <!-- LÍNEA 06 -->
                <div class="tab-pane fade" id="diagram-06">
                    <x-diagram-animator 
                        diagramId="line-06"
                        imagePath="{{ asset('images/Diagramas-Lavadoras/linea6.png') }}"
                        title="Línea 06 y 07 - Cadena Animada"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- INFO -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="alert alert-success">
                <h5>✅ Sistema de Animación Operativo</h5>
                <p class="mb-0">
                    Los diagramas se animan completamente en Canvas HTML5. 
                    La cadena industrial (línea roja) se mueve continuamente, 
                    las catarinas/engranes giran sincronizadas. 
                    <strong>Haz clic en "▶ Iniciar animación"</strong> para ver el movimiento.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- INCLUIR SCRIPT DE ANIMACIÓN -->
<script src="{{ asset('js/diagram-animator/chain-animator.js') }}"></script>

@endsection

@push('styles')
<style>
    body {
        background-color: #f8f9fa;
    }

    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
    }

    .nav-link {
        font-weight: 500;
        color: #495057;
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        color: #007bff;
        border-bottom-color: #007bff;
    }

    .nav-link.active {
        color: #007bff;
        border-bottom-color: #007bff;
        background-color: transparent;
    }

    .diagram-animator-container {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .diagram-canvas {
        max-width: 100%;
        height: auto;
        border: 2px solid #dee2e6;
        border-radius: 4px;
    }

    .diagram-controls button {
        font-weight: 500;
    }

    .diagram-controls button:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    @media (max-width: 768px) {
        .diagram-animator-container {
            padding: 15px;
        }

        .diagram-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .diagram-controls button {
            width: 100%;
        }

        .form-range {
            width: 100%;
        }

        h1 {
            font-size: 1.5rem;
        }
    }
</style>
@endpush
