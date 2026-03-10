@extends('layouts.app')

@section('title', 'Histórico de Revisados - Pasteurizadora')

@section('content')
<style>
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --info-blue: #3b82f6;
        --light-gray: #f3f4f6;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
    }

    .historico-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .lineas-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .linea-btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 24px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 15px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .linea-btn i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
    }

    .linea-btn.active i {
        color: white;
    }

    .componentes-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
    }

    .table-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-header .badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background: #f8fafc;
        padding: 16px;
        font-weight: 600;
        font-size: 14px;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }

    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .componente-nombre {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #1e293b;
    }

    .componente-imagen {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .componente-imagen:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .componente-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }

    .cantidad-badge {
        background: #e2e8f0;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        color: #1e293b;
    }

    .progreso-numerico {
        font-weight: 600;
        font-size: 15px;
    }

    .progress-container {
        width: 100%;
        background: #e2e8f0;
        border-radius: 8px;
        height: 24px;
        position: relative;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        font-size: 12px;
        font-weight: 600;
        color: white;
        transition: width 0.5s ease;
    }

    .progress-bar.bg-success {
        background: linear-gradient(90deg, #10b981, #059669) !important;
    }
    
    .progress-bar.bg-info {
        background: linear-gradient(90deg, #3b82f6, #2563eb) !important;
    }
    
    .progress-bar.bg-warning {
        background: linear-gradient(90deg, #f59e0b, #d97706) !important;
    }
    
    .progress-bar.bg-danger {
        background: linear-gradient(90deg, #ef4444, #dc2626) !important;
    }

    .progress-label {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #1e293b;
        font-size: 12px;
        font-weight: 600;
        z-index: 5;
    }

    .grafica-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
    }

    .grafica-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .grafica-vertical-container {
        display: flex;
        justify-content: space-around;
        align-items: flex-end;
        min-height: 300px;
        padding: 20px 10px;
        background: #f8fafc;
        border-radius: 12px;
        margin-bottom: 20px;
        position: relative;
        border: 1px solid #e2e8f0;
    }

    .grafica-columna {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        max-width: 100px;
        position: relative;
        z-index: 2;
    }

    .grafica-barra-vertical {
        width: 50px;
        height: 200px;
        background: #e2e8f0;
        border-radius: 8px 8px 0 0;
        position: relative;
        margin-bottom: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .grafica-barra-vertical:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 12px rgba(0,0,0,0.15);
    }

    .grafica-barra-vertical .barra-relleno {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 0%;
        transition: height 1s ease;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding-bottom: 5px;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }

    .grafica-etiqueta {
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        color: #475569;
        margin-top: 8px;
        max-width: 100px;
        word-wrap: break-word;
    }

    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .resumen-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .resumen-icono {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .resumen-icono.total { background: #e2e8f0; color: #475569; }
    .resumen-icono.revisado { background: #dbeafe; color: #2563eb; }
    .resumen-icono.porcentaje { background: #d1fae5; color: #059669; }

    .resumen-info h4 {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .resumen-info .valor {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 500px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }

    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }
</style>

@php
    $componentesData = [
        'anillas' => ['nombre' => 'Anillas', 'icono' => 'fa-ring'],
        'ventanas' => ['nombre' => 'Ventanas', 'icono' => 'fa-window-maximize'],
        'cortinas' => ['nombre' => 'Cortinas', 'icono' => 'fa-chess-board'],
        'placas' => ['nombre' => 'Placas', 'icono' => 'fa-square'],
        'pernos' => ['nombre' => 'Pernos', 'icono' => 'fa-grip-lines'],
        'parrillas' => ['nombre' => 'Parrillas', 'icono' => 'fa-border-all'],
        'reglillas' => ['nombre' => 'Reglillas', 'icono' => 'fa-ruler'],
        'rodamientos' => ['nombre' => 'Rodamientos', 'icono' => 'fa-cog'],
        'excentricos' => ['nombre' => 'Excéntricos', 'icono' => 'fa-balance-scale'],
        'levas' => ['nombre' => 'Levas', 'icono' => 'fa-chart-line'],
        'pistas' => ['nombre' => 'Pistas', 'icono' => 'fa-road'],
    ];
@endphp

<div class="historico-container">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Histórico de Revisados - Pasteurizadora
            </h1>
        </div>
    </div>

    {{-- Líneas --}}
    <div class="lineas-section">
        <div class="lineas-title">
            <i class="fas fa-temperature-high"></i>
            LÍNEAS DE PASTEURIZADORA
        </div>
        
        <div class="lineas-grid">
            @foreach($lineas as $linea)
                <a href="{{ route('analisis-pasteurizadora.historico-revisados', ['linea_id' => $linea->id]) }}" 
                   class="linea-btn {{ $lineaSeleccionada && $lineaSeleccionada->id == $linea->id ? 'active' : '' }}">
                    <i class="fas fa-temperature-high"></i>
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Resumen --}}
    @if(isset($estadisticas['resumen']))
    <div class="resumen-grid">
        <div class="resumen-card">
            <div class="resumen-icono total">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="resumen-info">
                <h4>Total Componentes</h4>
                <div class="valor">{{ $estadisticas['resumen']['total_general'] }}</div>
            </div>
        </div>

        <div class="resumen-card">
            <div class="resumen-icono revisado">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="resumen-info">
                <h4>Revisados</h4>
                <div class="valor">{{ $estadisticas['resumen']['total_revisado'] }}</div>
            </div>
        </div>

        <div class="resumen-card">
            <div class="resumen-icono porcentaje">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="resumen-info">
                <h4>Progreso General</h4>
                <div class="valor">{{ $estadisticas['resumen']['porcentaje_general'] }}%</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Tabla de componentes --}}
    <div class="componentes-table">
        <div class="table-header">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                ANÁLISIS DE {{ $lineaSeleccionada->nombre ?? 'LA LÍNEA SELECCIONADA' }}
            </h3>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>Cantidad Total</th>
                        <th>Cantidad Revisada</th>
                        <th>Progreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($componentesData as $codigo => $data)
                        @php
                            $stats = $estadisticas[$codigo] ?? [
                                'total' => 16,
                                'revisadas' => 0,
                                'porcentaje' => 0,
                                'color' => 'danger'
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="componente-nombre">
                                    <div class="componente-imagen">
                                        <img src="{{ asset('images/componentes-pasteurizadora/' . $codigo . '.png') }}" 
                                             alt="{{ $data['nombre'] }}"
                                             class="componente-img"
                                             onerror="this.src='{{ asset('images/extras/sin imagen.png') }}'">
                                    </div>
                                    <span>{{ $data['nombre'] }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="cantidad-badge">{{ $stats['total'] }}</span>
                            </td>
                            <td>
                                <span class="progreso-numerico text-{{ $stats['color'] }}">
                                    {{ $stats['revisadas'] }} / {{ $stats['total'] }}
                                </span>
                            </td>
                            <td style="width: 250px;">
                                <div class="progress-container">
                                    <div class="progress-bar bg-{{ $stats['color'] }}" 
                                         style="width: {{ $stats['porcentaje'] }}%;">
                                        {{ $stats['porcentaje'] }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-primary" 
                                        style="padding: 6px 12px; font-size: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px;"
                                        onclick="verDetalleComponente('{{ $codigo }}', '{{ $data['nombre'] }}', {{ $stats['revisadas'] }}, {{ $stats['total'] }})">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-3xl mb-2"></i>
                                <p>No hay componentes configurados para esta línea</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gráfica --}}
    @if(count($componentesData) > 0)
    <div class="grafica-section">
        <div class="grafica-title">
            <i class="fas fa-chart-bar text-blue-600"></i>
            GRÁFICA DE AVANCE POR COMPONENTE
        </div>

        <div class="grafica-vertical-container">
            @foreach($componentesData as $codigo => $data)
                @php
                    $stats = $estadisticas[$codigo] ?? [
                        'total' => 16,
                        'revisadas' => 0,
                        'porcentaje' => 0,
                        'color' => 'danger'
                    ];
                    $alturaBarra = $stats['porcentaje'] * 2; // 100% = 200px
                @endphp
                <div class="grafica-columna">
                    <div class="grafica-barra-vertical" title="{{ $data['nombre'] }} ({{ $stats['revisadas'] }}/{{ $stats['total'] }})">
                        <div class="barra-relleno bg-{{ $stats['color'] }}" 
                             style="height: {{ $alturaBarra }}px;">
                            <span class="grafica-valor">{{ $stats['porcentaje'] }}%</span>
                        </div>
                    </div>
                    <div class="grafica-etiqueta" title="{{ $data['nombre'] }}">
                        {{ $data['nombre'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grafica-referencias">
            <span>0%</span>
            <span>25%</span>
            <span>50%</span>
            <span>75%</span>
            <span>100%</span>
        </div>
    </div>
    @endif

    {{-- Acciones --}}
    <div class="acciones">
        <a href="{{ route('analisis-pasteurizadora.index', ['linea_id' => $lineaSeleccionada->id ?? '']) }}" 
           class="btn btn-primary">
            <i class="fas fa-chart-pie"></i>
            Ver Análisis Detallado
        </a>
        <button class="btn btn-success" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i>
            Actualizar Datos
        </button>
    </div>
</div>

{{-- Modal de detalle --}}
<div id="componenteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-cog"></i>
                <span id="modalComponenteNombre">Detalle del Componente</span>
            </h3>
            <button onclick="cerrarModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="text-center mb-6">
                <img id="modalComponenteImagen" src="" alt="Componente" class="w-32 h-32 object-contain mx-auto mb-4">
                <p id="modalComponenteDesc" class="text-gray-600"></p>
            </div>
            
            <div class="mt-6">
                <h4 class="font-semibold text-gray-700 mb-3">Progreso Actual</h4>
                <div class="progress-container" style="height: 32px; margin-bottom: 20px;">
                    <div id="modalProgressBar" class="progress-bar" style="width: 0%;">0%</div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-600" id="modalRevisado">0</div>
                        <div class="text-xs text-gray-500">Revisados</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-gray-700" id="modalTotal">0</div>
                        <div class="text-xs text-gray-500">Totales</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="#" id="modalVerAnalisisLink" class="btn btn-primary w-full justify-center" style="width: 100%; background: #3b82f6;">
                    <i class="fas fa-chart-pie"></i>
                    Ver Análisis de este Componente
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalleComponente(codigo, nombre, revisado, total) {
    const modal = document.getElementById('componenteModal');
    const porcentaje = total > 0 ? Math.round((revisado / total) * 100) : 0;
    
    document.getElementById('modalComponenteNombre').textContent = nombre;
    document.getElementById('modalComponenteDesc').textContent = `${nombre} - Código: ${codigo}`;
    document.getElementById('modalRevisado').textContent = revisado;
    document.getElementById('modalTotal').textContent = total;
    
    const img = document.getElementById('modalComponenteImagen');
    img.src = `{{ asset('images/componentes-pasteurizadora/') }}/${codigo}.png`;
    img.onerror = function() { this.src = '{{ asset('images/extras/sin imagen.png') }}'; };
    
    const progressBar = document.getElementById('modalProgressBar');
    progressBar.style.width = porcentaje + '%';
    progressBar.textContent = porcentaje + '%';
    
    progressBar.className = 'progress-bar';
    if (porcentaje >= 80) {
        progressBar.classList.add('bg-success');
    } else if (porcentaje >= 50) {
        progressBar.classList.add('bg-info');
    } else if (porcentaje >= 20) {
        progressBar.classList.add('bg-warning');
    } else {
        progressBar.classList.add('bg-danger');
    }
    
    const lineaId = '{{ $lineaSeleccionada->id ?? '' }}';
    const link = document.getElementById('modalVerAnalisisLink');
    link.href = "{{ route('analisis-pasteurizadora.index') }}?linea_id=" + lineaId + "&componente=" + codigo;
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('componenteModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('componenteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const barras = document.querySelectorAll('.barra-relleno');
    barras.forEach(barra => {
        const altura = barra.style.height;
        barra.style.height = '0';
        setTimeout(() => {
            barra.style.height = altura;
        }, 100);
    });
});
</script>
@endsection