@extends('layouts.app')

@section('title', 'Maquinas - Envasado')

@section('content')
<style>
    .modulos-container {
        min-height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 40px 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .modulos-header {
        text-align: center;
        margin-bottom: 48px;
    }

    .modulos-header h1 {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
    }

    .modulos-header p {
        font-size: 1.1rem;
        color: #64748b;
        max-width: 600px;
        margin: 0 auto;
    }

    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 32px;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    .modulo-card {
        background: white;
        border-radius: 32px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        position: relative;
    }

    .modulo-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 35px -12px rgba(0, 0, 0, 0.25);
    }

    .modulo-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        filter: grayscale(0.2);
    }

    .modulo-card.disabled:hover {
        transform: none;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modulo-header {
        padding: 32px;
        text-align: center;
        position: relative;
        transition: all 0.3s ease;
    }

    .modulo-icon {
        width: 100px;
        height: 100px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 48px;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .modulo-icon.has-image {
        background: transparent !important;
        box-shadow: none !important;
        padding: 0;
        width: 200px;    /* Ancho fijo */
        height: 200px;   /* Alto fijo */
    }

    .modulo-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: all 0.3s ease;
    }
    
    .modulo-card:hover .modulo-icon.has-image img {
        transform: scale(1.1);
    }

    .modulo-icon:not(.has-image) {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
    }

    .modulo-icon.blue:not(.has-image) {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
    }

    .modulo-icon.orange:not(.has-image) {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
        box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
    }

    .modulo-icon.green:not(.has-image) {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
    }

    .modulo-icon.purple:not(.has-image) {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
    }

    .modulo-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }

    .modulo-header p {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .modulo-stats {
        background: #f8fafc;
        padding: 20px 24px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .stat-item {
        text-align: center;
    }

    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
    }

    .stat-value.critico {
        color: #ef4444;
    }

    /* ANIMACIÓN DE PARPADEO PARA ALERTAS CRÍTICAS */
    @keyframes criticalPulse {
        0% {
            color: #ef4444;
            text-shadow: 0 0 0 rgba(239, 68, 68, 0);
            transform: scale(1);
        }
        50% {
            color: #dc2626;
            text-shadow: 0 0 8px rgba(239, 68, 68, 0.8), 0 0 12px rgba(239, 68, 68, 0.4);
            transform: scale(1.08);
        }
        100% {
            color: #ef4444;
            text-shadow: 0 0 0 rgba(239, 68, 68, 0);
            transform: scale(1);
        }
    }

    @keyframes criticalBlink {
        0% {
            opacity: 1;
            background-color: transparent;
        }
        50% {
            opacity: 0.7;
            background-color: rgba(239, 68, 68, 0.15);
        }
        100% {
            opacity: 1;
            background-color: transparent;
        }
    }

    .stat-value.critico.blinking {
        animation: criticalPulse 1s ease-in-out infinite;
        display: inline-block;
    }

    .stat-item.has-critics {
        animation: criticalBlink 1.2s ease-in-out infinite;
        border-radius: 12px;
        padding: 4px 0;
        margin: -4px 0;
    }

    /* Contenedor para el número con efecto de pulso */
    .critical-number {
        display: inline-block;
        transition: all 0.3s ease;
    }

    .critical-number.blinking {
        animation: criticalPulse 0.8s ease-in-out infinite;
    }

    .stat-value.riesgo {
        color: #f97316;
    }

    .stat-value.bueno {
        color: #10b981;
    }

    .modulo-footer {
        padding: 20px 24px;
        text-align: center;
        background: white;
    }

    .btn-acceder {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 24px;
        background: #1e293b;
        color: white;
        border-radius: 40px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .modulo-card:hover .btn-acceder {
        background: #3b82f6;
        transform: translateX(5px);
    }

    .modulo-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 6px 12px;
        background: #10b981;
        color: white;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .last-update {
        text-align: center;
        margin-top: 48px;
        color: #94a3b8;
        font-size: 0.8rem;
    }

    @media (max-width: 768px) {
        .modulos-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .modulos-header h1 {
            font-size: 1.8rem;
        }
        
        .modulo-header h2 {
            font-size: 1.4rem;
        }
        
        .modulo-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .modulo-icon {
            width: 80px;
            height: 80px;
            font-size: 36px;
        }
    }
</style>

<div class="modulos-container">
    <div class="modulos-header">
        <h1>
            Hola {{ Auth::user()->name }}, Bienvenido.
        </h1>
    
    <div class="modulos-grid">
  @foreach($modulos as $modulo)
    @if($modulo['activo'])
        @php
            $hasCriticos = $modulo['estadisticas']['alertas_criticas'] > 0;
            $tieneImagen = isset($modulo['imagen_personalizada']) && $modulo['imagen_personalizada'] && !empty($modulo['icono_imagen']);
            $bloqueado = $modulo['bloqueado'] ?? false;
            $mensajeBloqueo = $modulo['mensaje_bloqueo'] ?? 'Estamos trabajando en ello, estara disponible muy pronto.';
            // La ruta está definida en el controlador dentro de cada módulo
            $rutaModulo = $modulo['ruta'] ?? route('dashboard');
        @endphp
                <div class="modulo-card"
                     @if($bloqueado)
                         data-coming-soon-message="{{ $mensajeBloqueo }}"
                     @else
                         onclick="window.location.href='{{ $rutaModulo }}'"
                     @endif>
                    <div class="modulo-header">
                        <div class="modulo-icon {{ $modulo['color'] }} {{ $tieneImagen ? 'has-image' : '' }}">
                            @if($tieneImagen)
                                <img src="{{ asset($modulo['icono_imagen']) }}" alt="{{ $modulo['nombre'] }}">
                            @else
                                <i class="fas {{ $modulo['icono'] }}"></i>
                            @endif
                        </div>
                        <h2>{{ $modulo['nombre'] }}</h2>
                        <p>{{ $modulo['descripcion'] }}</p>
                    </div>
                    
                    <div class="modulo-stats">
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-microchip mr-1"></i>
                                Equipos
                            </div>
                            <div class="stat-value">{{ $modulo['estadisticas']['total_equipos'] }}</div>
                        </div>
                        <div class="stat-item {{ $hasCriticos ? 'has-critics' : '' }}">
                            <div class="stat-label">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Alertas Críticas
                            </div>
                            <div class="stat-value critico">
                                <span class="critical-number {{ $hasCriticos ? 'blinking' : '' }}">
                                    {{ $modulo['estadisticas']['alertas_criticas'] }}
                                </span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-chart-line mr-1"></i>
                                Severo / Moderado
                            </div>
                            <div class="stat-value riesgo">{{ $modulo['estadisticas']['en_riesgo'] }}</div>
                        </div>
                        @if(isset($modulo['estadisticas']['requiere_revision']))
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-tools mr-1"></i>
                                    Revisión
                                </div>
                                <div class="stat-value" style="color: #f59e0b;">{{ $modulo['estadisticas']['requiere_revision'] }}</div>
                            </div>
                        @endif
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-check-circle mr-1"></i>
                                Buen Estado
                            </div>
                            <div class="stat-value bueno">{{ $modulo['estadisticas']['buen_estado'] }}</div>
                        </div>
                    </div>
                    
                    <div class="modulo-footer">
                        <button type="button" class="btn-acceder">
                            Acceder al Módulo
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            @else
                <div class="modulo-card disabled">
                    <div class="modulo-header">
                        <div class="modulo-icon {{ $modulo['color'] }}" style="opacity: 0.5;">
                            <i class="fas {{ $modulo['icono'] }}"></i>
                        </div>
                        <h2>{{ $modulo['nombre'] }}</h2>
                        <p>{{ $modulo['descripcion'] }}</p>
                    </div>
                    
                    <div class="modulo-footer">
                        <button class="btn-acceder" disabled style="background: #94a3b8; cursor: not-allowed;">
                            <i class="fas fa-lock mr-2"></i>
                            Próximamente
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="last-update">
        <i class="fas fa-sync-alt mr-2"></i>
        Última actualización de datos: {{ $modulos[0]['estadisticas']['ultima_actualizacion'] ?? now()->format('d/m/Y H:i') }}
    </div>
</div>

<script>
    
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.modulo-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        document.querySelectorAll('[data-coming-soon-message]').forEach(card => {
            card.addEventListener('click', function() {
                Swal.fire({
                    icon: 'info',
                    text: this.dataset.comingSoonMessage,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1e40af'
                });
            });
        });

        
        const criticalNumbers = document.querySelectorAll('.critical-number.blinking');
        if (criticalNumbers.length > 0) {
            console.log(`⚠️ Hay ${criticalNumbers.length} módulo(s) con alertas críticas`);
            
    
            let originalTitle = document.title;
            let criticalCount = 0;
            document.querySelectorAll('.stat-value.critico').forEach(el => {
                let value = parseInt(el.innerText);
                if (value > 0) criticalCount += value;
            });
            
            if (criticalCount > 0) {
                
                setInterval(() => {
                    if (document.title === originalTitle) {
                        document.title = `⚠️ ${criticalCount} ALERTA(S) CRÍTICA(S) ⚠️`;
                    } else {
                        document.title = originalTitle;
                    }
                }, 2000);
            }
        }
    });

    
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.modulo-icon img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                console.error('Error loading image:', this.src);
                
                const parent = this.parentElement;
                parent.classList.remove('has-image');
                parent.innerHTML = '<i class="fas fa-industry"></i>';
            });
        });
    });
</script>
@endsection
