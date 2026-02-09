@extends('layouts.app')

@section('title', 'Ver Análisis de Componente')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xxl-10">
            <!-- Header con gradiente -->
            <div class="header-analisis mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="analisis-icon me-3">
                            <i class="fas fa-microscope fa-2x"></i>
                        </div>
                        <div>
                            <h1 class="mb-0">Análisis #{{ $analisisComponente->id }}</h1>
                            <p class="text-muted mb-0">
                                <i class="far fa-calendar-alt me-1"></i>
                                {{ $analisisComponente->fecha_analisis->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('analisis-componentes.edit', $analisisComponente) }}" 
                           class="btn btn-warning btn-lg">
                            <i class="fas fa-edit me-2"></i> Editar
                        </a>
                        <a href="{{ route('analisis-componentes.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta Principal -->
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Información General -->
                        <div class="col-lg-6">
                            <div class="info-card h-100">
                                <div class="card-header-custom bg-primary">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Información General
                                    </h5>
                                </div>
                                <div class="card-body-custom">
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-washing-machine me-2 text-primary"></i>
                                                Lavadora
                                            </div>
                                            <div class="info-value">
                                                <span class="badge bg-primary-light text-primary">
                                                    {{ $analisisComponente->linea->nombre ?? 'Lavadora ' . $analisisComponente->linea_id }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-cogs me-2 text-success"></i>
                                                Componente
                                            </div>
                                            <div class="info-value">
                                                <span class="badge bg-success-light text-success">
                                                    {{ $analisisComponente->componente->nombre ?? 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-tachometer-alt me-2 text-warning"></i>
                                                Reductor
                                            </div>
                                            <div class="info-value">
                                                <span class="badge bg-warning-light text-warning">
                                                    {{ $analisisComponente->reductor }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-hashtag me-2 text-info"></i>
                                                Número de Orden
                                            </div>
                                            <div class="info-value">
                                                <span class="badge bg-info-light text-info">
                                                    {{ $analisisComponente->numero_orden }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actividad Realizada -->
                        <div class="col-lg-6">
                            <div class="info-card h-100">
                                <div class="card-header-custom bg-success">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tasks me-2"></i>
                                        Actividad Realizada
                                    </h5>
                                </div>
                                <div class="card-body-custom">
                                    <div class="actividad-content">
                                        <div class="actividad-icon">
                                            <i class="fas fa-clipboard-check text-success"></i>
                                        </div>
                                        <div class="actividad-text">
                                            {{ $analisisComponente->actividad }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Evidencia Fotográfica -->
                    @if(!empty($analisisComponente->evidencia_fotos))
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="section-header mb-4">
                                    <h3>
                                        <i class="fas fa-camera me-2 text-danger"></i>
                                        Evidencia Fotográfica
                                        <span class="badge bg-danger ms-2">
                                            {{ count($analisisComponente->evidencia_fotos) }}
                                        </span>
                                    </h3>
                                </div>
                                
                                <div class="gallery-container">
                                    @foreach($analisisComponente->evidencia_fotos as $index => $foto)
                                        <div class="photo-card">
                                            <div class="photo-header">
                                                <span class="photo-number">#{{ $index + 1 }}</span>
                                                <button type="button" 
                                                        class="btn-view-photo" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#photoModal{{ $index }}">
                                                    <i class="fas fa-expand"></i>
                                                </button>
                                            </div>
                                            <div class="photo-image" 
                                                 onclick="openModal('{{ asset('storage/' . $foto) }}')"
                                                 style="cursor: pointer;">
                                                <img src="{{ asset('storage/' . $foto) }}" 
                                                     alt="Evidencia {{ $index + 1 }}"
                                                     class="img-fluid">
                                                <div class="photo-overlay">
                                                    <i class="fas fa-search-plus"></i>
                                                </div>
                                            </div>
                                            <div class="photo-footer">
                                                <form action="{{ route('analisis-componentes.delete-foto', [$analisisComponente, $index]) }}" 
                                                      method="POST" 
                                                      class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger delete-btn"
                                                            onclick="return confirmDelete()">
                                                        <i class="fas fa-trash me-1"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Acciones -->
                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('analisis-componentes.create') }}" class="btn btn-primary btn-lg">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Nuevo Análisis
                                    </a>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-info">
                                        <i class="fas fa-print me-2"></i>
                                        Imprimir
                                    </button>
                                    <button class="btn btn-outline-success">
                                        <i class="fas fa-download me-2"></i>
                                        Exportar PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver foto en grande -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidencia Fotográfica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="">
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Estilos personalizados */
    .header-analisis {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .analisis-icon {
        background: rgba(255,255,255,0.2);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .info-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }
    
    .info-card:hover {
        transform: translateY(-5px);
    }
    
    .card-header-custom {
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    
    .card-body-custom {
        padding: 1.5rem;
    }
    
    .info-grid {
        display: grid;
        gap: 1rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
        display: flex;
        align-items: center;
    }
    
    .info-value {
        font-weight: 500;
    }
    
    .badge.bg-primary-light {
        background-color: rgba(102, 126, 234, 0.1) !important;
    }
    
    .badge.bg-success-light {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }
    
    .badge.bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    
    .badge.bg-info-light {
        background-color: rgba(23, 162, 184, 0.1) !important;
    }
    
    .actividad-content {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        height: 100%;
    }
    
    .actividad-icon {
        font-size: 2rem;
        margin-top: 0.25rem;
    }
    
    .actividad-text {
        flex: 1;
        line-height: 1.6;
        color: #444;
        font-size: 0.95rem;
    }
    
    .section-header {
        position: relative;
        padding-left: 1.5rem;
    }
    
    .section-header::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: linear-gradient(to bottom, #dc3545, #ff6b6b);
        border-radius: 2px;
    }
    
    .gallery-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .photo-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .photo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .photo-header {
        background: #f8f9fa;
        padding: 0.75rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .photo-number {
        font-weight: 600;
        color: #666;
    }
    
    .btn-view-photo {
        background: none;
        border: none;
        color: #6c757d;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        transition: color 0.2s;
    }
    
    .btn-view-photo:hover {
        color: #dc3545;
    }
    
    .photo-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }
    
    .photo-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .photo-card:hover .photo-image img {
        transform: scale(1.05);
    }
    
    .photo-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .photo-image:hover .photo-overlay {
        opacity: 1;
    }
    
    .photo-overlay i {
        color: white;
        font-size: 2rem;
    }
    
    .photo-footer {
        padding: 1rem;
        background: #f8f9fa;
    }
    
    .delete-form {
        margin: 0;
    }
    
    .delete-btn {
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .delete-btn:hover {
        transform: scale(1.05);
    }
    
    @media (max-width: 768px) {
        .header-analisis {
            padding: 1.5rem;
        }
        
        .gallery-container {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
        
        .actividad-content {
            flex-direction: column;
        }
        
        .analisis-icon {
            width: 50px;
            height: 50px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function openModal(imageSrc) {
        document.getElementById('modalImage').src = imageSrc;
        var modal = new bootstrap.Modal(document.getElementById('photoModal'));
        modal.show();
    }
    
    function confirmDelete() {
        return confirm('¿Está seguro de eliminar esta foto? Esta acción no se puede deshacer.');
    }
    
    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush