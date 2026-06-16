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
        grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
        gap: 36px;
        max-width: 1320px;
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

    .modulo-card.modulo-card-machine {
        --machine-cover-scale: 1.04;
        --machine-cover-hover-scale: 1.08;
        --machine-cover-position: center 47%;
        --machine-tint: rgba(59, 130, 246, 0.16);
        --machine-title-accent-start: #2563eb;
        --machine-title-accent-end: #38bdf8;
        --machine-title-glow: rgba(37, 99, 235, 0.32);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 26px 50px -30px rgba(15, 23, 42, 0.38), 0 16px 30px -24px rgba(59, 130, 246, 0.2);
    }

    .modulo-card.modulo-card-machine.modulo-card-lavadora {
        --machine-cover-scale: 1.02;
        --machine-cover-hover-scale: 1.06;
        --machine-cover-position: center 44%;
        --machine-tint: rgba(59, 130, 246, 0.18);
    }

    .modulo-card.modulo-card-machine.modulo-card-pasteurizadora {
        --machine-cover-scale: 1.03;
        --machine-cover-hover-scale: 1.07;
        --machine-cover-position: center 46%;
        --machine-tint: rgba(249, 115, 22, 0.18);
        --machine-title-accent-start: #ea580c;
        --machine-title-accent-end: #f59e0b;
        --machine-title-glow: rgba(234, 88, 12, 0.32);
    }

    .modulo-card.modulo-card-machine::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        border: 1px solid rgba(255, 255, 255, 0.55);
        pointer-events: none;
        z-index: 0;
    }

    .modulo-card.modulo-card-machine .modulo-header {
        padding: 0 0 26px;
    }

    .modulo-card.modulo-card-machine .modulo-copy {
        position: relative;
        z-index: 4;
        width: calc(100% - 42px);
        margin: -38px auto 0;
        padding: 18px 22px 16px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(255, 255, 255, 0.92);
        border-radius: 24px;
        box-shadow: 0 22px 42px -32px rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .modulo-copy p:empty {
        display: none;
    }

    .modulo-card.modulo-card-machine .modulo-copy h2,
    .modulo-card.modulo-card-machine .modulo-copy p {
        padding: 0;
    }

    .modulo-card.modulo-card-machine .modulo-copy h2 {
        margin: 0 auto 6px;
        font-size: 1.9rem;
        letter-spacing: -0.03em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding-bottom: 12px;
        background: linear-gradient(135deg, #0f172a 0%, #334155 58%, var(--machine-title-accent-end) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: none;
    }

    .modulo-card.modulo-card-machine .modulo-copy h2::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 78px;
        height: 4px;
        border-radius: 999px;
        transform: translateX(-50%);
        background: linear-gradient(90deg, var(--machine-title-accent-start) 0%, var(--machine-title-accent-end) 100%);
        box-shadow: 0 10px 18px -10px var(--machine-title-glow);
    }

    .modulo-card.modulo-card-machine .modulo-copy p {
        margin: 0;
        color: #60708a;
    }

    .modulo-card.modulo-card-machine .modulo-footer {
        padding: 24px 28px 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
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
        width: 200px;
        height: 200px;
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image {
        width: 100%;
        min-width: 100%;
        height: clamp(220px, 24vw, 265px);
        border-radius: 32px 32px 24px 24px;
        overflow: hidden;
        margin: 0;
        position: relative;
        isolation: isolate;
        box-shadow: inset 0 -24px 40px -36px rgba(15, 23, 42, 0.65);
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.06) 0%, rgba(15, 23, 42, 0.03) 48%, rgba(15, 23, 42, 0.16) 100%),
            radial-gradient(circle at top right, var(--machine-tint) 0%, transparent 58%);
        z-index: 2;
        pointer-events: none;
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image::after {
        content: '';
        position: absolute;
        inset: auto 0 0 0;
        height: 42%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.52) 56%, rgba(255, 255, 255, 0.96) 100%);
        z-index: 3;
        pointer-events: none;
    }

    .modulo-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image img {
        width: 100%;
        height: 100%;
        border-radius: 0;
        display: block;
        object-fit: cover;
        object-position: var(--machine-cover-position);
        transform: scale(var(--machine-cover-scale));
        transform-origin: center;
        position: relative;
        z-index: 1;
    }
    
    .modulo-card:hover .modulo-icon.has-image img {
        transform: scale(1.1);
    }

    .modulo-card.modulo-card-machine:hover .modulo-icon.has-image img {
        transform: scale(var(--machine-cover-hover-scale));
    }

    .modulo-card.modulo-card-machine .modulo-stats {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.75) 0%, #f8fbff 100%);
        padding: 24px 24px 22px;
        border-top: 1px solid rgba(226, 232, 240, 0.85);
        border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    }

    .modulo-card.modulo-card-machine .btn-acceder {
        background: linear-gradient(135deg, #1e293b 0%, #31466b 100%);
        box-shadow: 0 16px 24px -20px rgba(15, 23, 42, 0.78);
        padding: 12px 28px;
    }

    .modulo-card.modulo-card-machine:hover .btn-acceder {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-2px);
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

        .modulo-card.modulo-card-machine .modulo-header {
            padding: 0 0 24px;
        }

        .modulo-card.modulo-card-machine .modulo-copy {
            width: calc(100% - 28px);
            margin-top: -30px;
            padding: 16px 18px 14px;
        }

        .modulo-card.modulo-card-machine .modulo-copy h2 {
            font-size: 1.6rem;
            padding-bottom: 10px;
        }

        .modulo-card.modulo-card-machine .modulo-copy h2::after {
            width: 64px;
        }

        .modulo-card.modulo-card-machine .modulo-icon.has-image {
            width: 100%;
            min-width: 100%;
            height: 190px;
        }
    }
</style>

<div class="modulos-container">
    <div class="modulos-header">
        <h1>
            Hola {{ Auth::user()->name }}, Bienvenido.
        </h1>
    </div>

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
                <div class="modulo-card {{ in_array($modulo['id'] ?? '', ['lavadora', 'pasteurizadora'], true) ? 'modulo-card-machine modulo-card-' . ($modulo['id'] ?? '') : '' }}"
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
                        <div class="modulo-copy">
                            <h2>{{ $modulo['nombre'] }}</h2>
                            <p>{{ $modulo['descripcion'] }}</p>
                        </div>
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
                <div class="modulo-card disabled {{ in_array($modulo['id'] ?? '', ['lavadora', 'pasteurizadora'], true) ? 'modulo-card-machine modulo-card-' . ($modulo['id'] ?? '') : '' }}">
                    <div class="modulo-header">
                        <div class="modulo-icon {{ $modulo['color'] }}" style="opacity: 0.5;">
                            <i class="fas {{ $modulo['icono'] }}"></i>
                        </div>
                        <div class="modulo-copy">
                            <h2>{{ $modulo['nombre'] }}</h2>
                            <p>{{ $modulo['descripcion'] }}</p>
                        </div>
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
