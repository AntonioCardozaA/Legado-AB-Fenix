@php
    $resumen = $reporte['resumen'] ?? [];
    $componentes = $reporte['componentes'] ?? [];
    $reductores = $reporte['reductores'] ?? [];
    $analisis = $reporte['analisis'] ?? collect([]);
    $paros = $reporte['paros'] ?? collect([]);
    
    // Usar los datos que ya vienen del controlador
    $elongaciones = $reporte['elongaciones'] ?? collect([]);
    
    $promedioBombas = $elongaciones->avg('bombas_porcentaje') ?: 0;
    $promedioVapor = $elongaciones->avg('vapor_porcentaje') ?: 0;
    $maxElongacion = max($promedioBombas, $promedioVapor);
    
    // Usar los datos que ya vienen del controlador
    $analisisTendencia = $reporte['analisis_tendencia'] ?? collect([]);
    
    $totalDaños52 = $analisisTendencia->sum('total_danos_52_semanas');
    $totalDaños12 = $analisisTendencia->sum('total_danos_12_semanas');
    $totalDaños4 = $analisisTendencia->sum('total_danos_4_semanas');
    
    // Estado general
    $colorEstado = match($resumen['estado_general']['texto'] ?? 'SIN DATOS') {
        'CRÍTICO' => 'danger',
        'ALERTA' => 'warning',
        'ESTABLE' => 'success',
        default => 'gray'
    };
@endphp

<style>
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --dark: #0f172a;
        --dark-light: #1e293b;
        --border: #e2e8f0;
        --background: #f8fafc;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, transparent, rgba(37, 99, 235, 0.05));
        border-radius: 50%;
    }

    .stat-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-icon.total { background: #e2e8f0; color: #475569; }
    .stat-icon.analisis { background: #dbeafe; color: #2563eb; }
    .stat-icon.elongacion { background: #fee2e2; color: #dc2626; }
    .stat-icon.criticos { background: #fef3c7; color: #d97706; }

    .stat-label {
        font-size: 14px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--dark);
        font-family: 'JetBrains Mono', monospace;
    }

    /* Secciones de módulos */
    .modulo-section {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border);
        margin-bottom: 30px;
    }

    .modulo-header {
        background: linear-gradient(135deg, var(--dark), var(--dark-light));
        color: white;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modulo-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .modulo-icon {
        width: 48px;
        height: 48px;
        background: rgba(59, 130, 246, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: var(--primary-blue);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modulo-titulo {
        font-size: 20px;
        font-weight: 700;
    }

    .modulo-subtitulo {
        font-size: 13px;
        color: #94a3b8;
    }

    .modulo-badge {
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 16px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modulo-body {
        padding: 24px;
    }

    /* Grid de componentes */
    .componentes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }

    .componente-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 16px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }

    .componente-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-blue);
    }

    .componente-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .componente-icono {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
    }

    .componente-nombre {
        font-weight: 700;
        color: var(--dark);
    }

    .componente-stats {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 8px;
    }

    /* Badges de estado */
    .estado-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 12px;
    }

    .estado-bueno {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .estado-desgaste-moderado {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .estado-desgaste-severo {
        background: #ffedd5;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }

    .estado-danado {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .estado-cambiado {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }

    .estado-normal {
        background: #dcfce7;
        color: #166534;
    }

    .estado-alerta {
        background: #fef9c3;
        color: #854d0e;
    }

    .estado-critico {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Tablas industriales */
    .industrial-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .industrial-table th {
        background: #f8fafc;
        padding: 16px;
        font-weight: 600;
        font-size: 12px;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }

    .industrial-table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .industrial-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Gráfica de elongación */
    .elongacion-chart-container {
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px;
    }

    .elongacion-barra {
        height: 30px;
        background: #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 8px;
        position: relative;
    }

    .elongacion-progreso {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        transition: width 0.5s ease;
    }

    .elongacion-progreso.alerta {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }

    .elongacion-progreso.critico {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }

    .elongacion-marca {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: rgba(0,0,0,0.3);
    }

    /* Leyenda */
    .leyenda {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .leyenda-color {
        width: 16px;
        height: 16px;
        border-radius: 4px;
    }

    .leyenda-color.bueno { background: #10b981; }
    .leyenda-color.alerta { background: #f59e0b; }
    .leyenda-color.critico { background: #ef4444; }

    /* Grid de reductores */
    .reductores-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .reductor-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px;
        border: 1px solid var(--border);
    }

    .reductor-nombre {
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
    }

    .reductor-valor {
        font-family: 'JetBrains Mono', monospace;
        font-size: 14px;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .modulo-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

{{-- Stats Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon total">
                <i class="fas fa-chart-pie"></i>
            </div>
            <span class="stat-label">Total Análisis</span>
        </div>
        <div class="stat-value">{{ $resumen['total_analisis'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">
            Componentes: {{ $resumen['componentes_revisados'] ?? 0 }}/{{ count($reporte['componentes_definidos'] ?? []) }}
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon analisis">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <span class="stat-label">Componentes Críticos</span>
        </div>
        <div class="stat-value">{{ $resumen['componentes_criticos'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">
            Requieren atención inmediata
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon elongacion">
                <i class="fas fa-chart-line"></i>
            </div>
            <span class="stat-label">Elongación Máx</span>
        </div>
        <div class="stat-value {{ $maxElongacion >= 2.4 ? 'text-red-600' : ($maxElongacion >= 2.0 ? 'text-yellow-600' : 'text-green-600') }}">
            {{ number_format($maxElongacion, 2) }}%
        </div>
        <div class="text-sm text-gray-500 mt-2">
            Bombas: {{ number_format($promedioBombas, 2) }}% | Vapor: {{ number_format($promedioVapor, 2) }}%
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon criticos">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span class="stat-label">Análisis 52-12-4</span>
        </div>
        <div class="stat-value">{{ $analisisTendencia->count() }}</div>
        <div class="text-sm text-gray-500 mt-2">
            Daños: {{ number_format($totalDaños4, 2) }} (4 sem)
        </div>
    </div>
</div>

{{-- SECCIÓN 1: ANÁLISIS LAVADORA (Módulo 1) --}}
<div class="modulo-section">
    <div class="modulo-header">
        <div class="modulo-header-left">
            <div class="modulo-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div>
                <div class="modulo-titulo">ANÁLISIS DE COMPONENTES</div>
                <div class="modulo-subtitulo">{{ $reporte['linea']->nombre }} · Últimos análisis</div>
            </div>
        </div>
        <div class="modulo-badge">
            <i class="fas fa-chart-bar mr-2"></i>
            {{ count($componentes) }} Componentes
        </div>
    </div>
    <div class="modulo-body">
        <div class="componentes-grid">
            @forelse($componentes as $componente)
                @php
                    $estadoColor = match($componente['ultimo_estado'] ?? '') {
                        'Dañado - Requiere cambio' => 'danado',
                        'Desgaste severo' => 'desgaste-severo',
                        'Desgaste moderado' => 'desgaste-moderado',
                        'Cambiado' => 'cambiado',
                        default => 'bueno'
                    };
                    
                    $porcentajeCompletado = $componente['total_analisis'] > 0 ? 
                        min(100, ($componente['total_analisis'] / 12) * 100) : 0;
                @endphp
                
                <div class="componente-card">
                    <div class="componente-header">
                        <div class="componente-icono">
                            <img src="{{ asset('images/componentes-lavadora/' . $componente['codigo'] . '.png') }}" 
                                 alt="{{ $componente['nombre'] }}"
                                 class="w-8 h-8 object-contain"
                                 onerror="this.src='{{ asset('images/componentes-lavadora/default.png') }}'">
                        </div>
                        <div class="flex-1">
                            <div class="componente-nombre">{{ $componente['nombre'] }}</div>
                            <div class="text-xs text-gray-500">{{ $componente['codigo'] }}</div>
                        </div>
                    </div>
                    
                    <div class="componente-stats">
                        <span>Análisis: {{ $componente['total_analisis'] }}</span>
                        <span>Prom: {{ number_format($componente['promedio_elongacion'] ?? 0, 2) }} mm</span>
                    </div>
                    
                    @if($componente['ultimo_analisis'])
                        <div class="mt-2 p-2 bg-white rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500">Último estado:</span>
                                <span class="estado-badge estado-{{ $estadoColor }}">
                                    <i class="fas 
                                        @if($estadoColor == 'danado') fa-times-circle
                                        @elseif($estadoColor == 'desgaste-severo') fa-exclamation-triangle
                                        @elseif($estadoColor == 'desgaste-moderado') fa-exclamation-circle
                                        @elseif($estadoColor == 'cambiado') fa-exchange-alt
                                        @else fa-check-circle
                                        @endif mr-1"></i>
                                    {{ $componente['ultimo_estado'] }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ \Carbon\Carbon::parse($componente['ultimo_analisis']->fecha_analisis)->format('d/m/Y') }}
                            </div>
                        </div>
                    @endif
                    
                    <a href="{{ route('analisis-lavadora.index', ['linea_id' => $reporte['linea']->id, 'componente_id' => $componente['codigo']]) }}" 
                       class="mt-3 text-xs text-blue-600 hover:text-blue-800 flex items-center justify-center gap-1">
                        <i class="fas fa-search"></i>
                        Ver detalles del componente
                    </a>
                </div>
            @empty
                <div class="col-span-full text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>No hay análisis registrados en este período</p>
                </div>
            @endforelse
        </div>
        
        @if(count($componentes) > 0)
            <div class="text-center mt-4">
                <a href="{{ route('analisis-lavadora.index', ['linea_id' => $reporte['linea']->id]) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-chart-pie"></i>
                    Ver todos los análisis
                </a>
            </div>
        @endif
    </div>
</div>

{{-- SECCIÓN 2: ELONGACIÓN LAVADORA (Módulo 3) --}}
@if($elongaciones->count() > 0)
<div class="modulo-section">
    <div class="modulo-header">
        <div class="modulo-header-left">
            <div class="modulo-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="modulo-titulo">ELONGACIÓN DE CADENA</div>
                <div class="modulo-subtitulo">Registro histórico · Límite 2.4%</div>
            </div>
        </div>
        <div class="modulo-badge">
            <i class="fas fa-history mr-2"></i>
            {{ $elongaciones->count() }} registros
        </div>
    </div>
    <div class="modulo-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Gráfica de elongación --}}
            <div class="elongacion-chart-container">
                <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-blue-600"></i>
                    Evolución de Elongación
                </h4>
                
                @php
                    $ultimosRegistros = $elongaciones->sortByDesc('created_at')->take(5);
                    $maxValor = max(2.5, $ultimosRegistros->max('bombas_porcentaje') ?? 0, 
                                               $ultimosRegistros->max('vapor_porcentaje') ?? 0);
                @endphp
                
                @foreach($ultimosRegistros as $registro)
                    @php
                        $porcentajeBombas = ($registro->bombas_porcentaje / $maxValor) * 100;
                        $porcentajeVapor = ($registro->vapor_porcentaje / $maxValor) * 100;
                        $fecha = $registro->created_at->format('d/m');
                        
                        $claseBombas = $registro->bombas_porcentaje >= 2.4 ? 'critico' : 
                                      ($registro->bombas_porcentaje >= 2.0 ? 'alerta' : '');
                        $claseVapor = $registro->vapor_porcentaje >= 2.4 ? 'critico' : 
                                     ($registro->vapor_porcentaje >= 2.0 ? 'alerta' : '');
                    @endphp
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium">{{ $fecha }}</span>
                            <span class="text-gray-500">Hodómetro: {{ number_format($registro->hodometro, 0) }}h</span>
                        </div>
                        
                        {{-- Barra Bombas --}}
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs w-16">Bombas:</span>
                            <div class="flex-1 elongacion-barra">
                                <div class="elongacion-progreso {{ $claseBombas }}" style="width: {{ $porcentajeBombas }}%"></div>
                                @if($registro->bombas_porcentaje >= 2.0)
                                    <div class="elongacion-marca" style="left: {{ (2.0/$maxValor)*100 }}%"></div>
                                @endif
                                @if($registro->bombas_porcentaje >= 2.4)
                                    <div class="elongacion-marca" style="left: {{ (2.4/$maxValor)*100 }}%"></div>
                                @endif
                            </div>
                            <span class="text-xs font-mono w-16 text-right {{ $registro->bombas_porcentaje >= 2.4 ? 'text-red-600' : ($registro->bombas_porcentaje >= 2.0 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ number_format($registro->bombas_porcentaje, 2) }}%
                            </span>
                        </div>
                        
                        {{-- Barra Vapor --}}
                        <div class="flex items-center gap-2">
                            <span class="text-xs w-16">Vapor:</span>
                            <div class="flex-1 elongacion-barra">
                                <div class="elongacion-progreso {{ $claseVapor }}" style="width: {{ $porcentajeVapor }}%"></div>
                            </div>
                            <span class="text-xs font-mono w-16 text-right {{ $registro->vapor_porcentaje >= 2.4 ? 'text-red-600' : ($registro->vapor_porcentaje >= 2.0 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ number_format($registro->vapor_porcentaje, 2) }}%
                            </span>
                        </div>
                    </div>
                @endforeach
                
                <div class="leyenda">
                    <div class="leyenda-item">
                        <div class="leyenda-color bueno"></div>
                        <span class="text-xs">Normal (&lt;2.0%)</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color alerta"></div>
                        <span class="text-xs">Alerta (2.0-2.4%)</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color critico"></div>
                        <span class="text-xs">Crítico (≥2.4%)</span>
                    </div>
                </div>
            </div>
            
            {{-- Tabla de últimos registros --}}
            <div>
                <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-list text-blue-600"></i>
                    Últimos registros
                </h4>
                
                <div class="overflow-x-auto">
                    <table class="industrial-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hodómetro</th>
                                <th>Bombas</th>
                                <th>Vapor</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($elongaciones->sortByDesc('created_at')->take(5) as $registro)
                                @php
                                    $estado = $registro->requiere_cambio ? 'critico' : 
                                             ($registro->bombas_porcentaje >= 2.0 || $registro->vapor_porcentaje >= 2.0 ? 'alerta' : 'normal');
                                @endphp
                                <tr>
                                    <td>{{ $registro->created_at->format('d/m/Y') }}</td>
                                    <td>{{ number_format($registro->hodometro, 0) }}h</td>
                                    <td class="{{ $registro->bombas_porcentaje >= 2.4 ? 'text-red-600' : ($registro->bombas_porcentaje >= 2.0 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ number_format($registro->bombas_porcentaje, 2) }}%
                                    </td>
                                    <td class="{{ $registro->vapor_porcentaje >= 2.4 ? 'text-red-600' : ($registro->vapor_porcentaje >= 2.0 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ number_format($registro->vapor_porcentaje, 2) }}%
                                    </td>
                                    <td>
                                        <span class="estado-badge estado-{{ $estado }}">
                                            @if($estado == 'critico')
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Crítico
                                            @elseif($estado == 'alerta')
                                                <i class="fas fa-exclamation-circle mr-1"></i> Alerta
                                            @else
                                                <i class="fas fa-check-circle mr-1"></i> Normal
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-4">
                    <a href="{{ route('elongaciones.index', ['linea' => $reporte['linea']->nombre]) }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                        <i class="fas fa-chart-line"></i>
                        Ver historial completo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- SECCIÓN 3: ANÁLISIS 52-12-4 (Módulo 4) --}}
@if($analisisTendencia->count() > 0)
<div class="modulo-section">
    <div class="modulo-header">
        <div class="modulo-header-left">
            <div class="modulo-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <div class="modulo-titulo">ANÁLISIS 52-12-4</div>
                <div class="modulo-subtitulo">Tendencias de daños por período</div>
            </div>
        </div>
        <div class="modulo-badge">
            <i class="fas fa-chart-bar mr-2"></i>
            {{ $analisisTendencia->count() }} períodos
        </div>
    </div>
    <div class="modulo-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                <div class="text-sm text-gray-500 mb-1">52 SEMANAS</div>
                <div class="text-2xl font-bold text-gray-800">{{ number_format($totalDaños52, 2) }}</div>
                <div class="text-xs text-gray-500">Total daños</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                <div class="text-sm text-gray-500 mb-1">12 SEMANAS</div>
                <div class="text-2xl font-bold text-gray-800">{{ number_format($totalDaños12, 2) }}</div>
                <div class="text-xs text-gray-500">Total daños</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                <div class="text-sm text-gray-500 mb-1">4 SEMANAS</div>
                <div class="text-2xl font-bold text-gray-800">{{ number_format($totalDaños4, 2) }}</div>
                <div class="text-xs text-gray-500">Total daños</div>
            </div>
        </div>
        
        <table class="industrial-table">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>52 Semanas</th>
                    <th>Vs Mes Ant</th>
                    <th>12 Semanas</th>
                    <th>Vs Mes Ant</th>
                    <th>4 Semanas</th>
                    <th>Vs Mes Ant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analisisTendencia->sortByDesc('anio')->sortByDesc('mes')->take(6) as $item)
                    @php
                        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        $mesNombre = $meses[$item->mes - 1] ?? '';
                        
                        $variacion52 = $item->variacion_52_semanas ?? null;
                        $variacion12 = $item->variacion_12_semanas ?? null;
                        $variacion4 = $item->variacion_4_semanas ?? null;
                    @endphp
                    <tr>
                        <td class="font-medium">{{ $mesNombre }} {{ $item->anio }}</td>
                        <td>{{ number_format($item->total_danos_52_semanas, 2) }}</td>
                        <td class="{{ isset($variacion52['diferencia']) && $variacion52['diferencia'] > 0 ? 'text-red-600' : (isset($variacion52['diferencia']) && $variacion52['diferencia'] < 0 ? 'text-green-600' : '') }}">
                            @if(isset($variacion52['diferencia']))
                                {{ $variacion52['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion52['diferencia'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ number_format($item->total_danos_12_semanas, 2) }}</td>
                        <td class="{{ isset($variacion12['diferencia']) && $variacion12['diferencia'] > 0 ? 'text-red-600' : (isset($variacion12['diferencia']) && $variacion12['diferencia'] < 0 ? 'text-green-600' : '') }}">
                            @if(isset($variacion12['diferencia']))
                                {{ $variacion12['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion12['diferencia'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ number_format($item->total_danos_4_semanas, 2) }}</td>
                        <td class="{{ isset($variacion4['diferencia']) && $variacion4['diferencia'] > 0 ? 'text-red-600' : (isset($variacion4['diferencia']) && $variacion4['diferencia'] < 0 ? 'text-green-600' : '') }}">
                            @if(isset($variacion4['diferencia']))
                                {{ $variacion4['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion4['diferencia'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="text-center mt-4">
            <a href="{{ route('analisis-tendencia-mensual-lavadora.index', ['linea_id' => $reporte['linea']->id]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-calendar-alt"></i>
                Ver análisis completo
            </a>
        </div>
    </div>
</div>
@endif

{{-- SECCIÓN 4: HISTÓRICO DE REVISIONES (Módulo 5) --}}
@if($analisis->count() > 0)
<div class="modulo-section">
    <div class="modulo-header">
        <div class="modulo-header-left">
            <div class="modulo-icon">
                <i class="fas fa-history"></i>
            </div>
            <div>
                <div class="modulo-titulo">HISTÓRICO DE REVISIONES</div>
                <div class="modulo-subtitulo">Últimos análisis registrados</div>
            </div>
        </div>
        <div class="modulo-badge">
            <i class="fas fa-clipboard-list mr-2"></i>
            {{ $analisis->count() }} revisiones
        </div>
    </div>
    <div class="modulo-body">
        <table class="industrial-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Componente</th>
                    <th>Reductor</th>
                    <th>Estado</th>
                    <th>Orden</th>
                    <th>Actividad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analisis->sortByDesc('fecha_analisis')->take(10) as $item)
                    @php
                        $estadoColor = match($item->estado) {
                            'Dañado - Requiere cambio' => 'danado',
                            'Desgaste severo' => 'desgaste-severo',
                            'Desgaste moderado' => 'desgaste-moderado',
                            'Cambiado' => 'cambiado',
                            default => 'bueno'
                        };
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($item->fecha_analisis)->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('images/componentes-lavadora/' . ($item->componente->codigo ?? 'default') . '.png') }}" 
                                     class="w-6 h-6 object-contain"
                                     onerror="this.src='{{ asset('images/componentes-lavadora/default.png') }}'">
                                <span>{{ $item->componente->nombre ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td>{{ $item->reductor }}</td>
                        <td>
                            <span class="estado-badge estado-{{ $estadoColor }}">
                                {{ $item->estado }}
                            </span>
                        </td>
                        <td>{{ $item->numero_orden }}</td>
                        <td class="max-w-xs">
                            <p class="truncate">{{ $item->actividad }}</p>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="text-center mt-4">
            <a href="{{ route('historico-revisados.index', ['linea_id' => $reporte['linea']->id]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-history"></i>
                Ver historial completo
            </a>
        </div>
    </div>
</div>
@endif

{{-- SECCIÓN 5: REDUCTORES (Parte del Módulo 1) --}}
@if(count($reductores) > 0)
<div class="modulo-section">
    <div class="modulo-header">
        <div class="modulo-header-left">
            <div class="modulo-icon">
                <i class="fas fa-compress-alt"></i>
            </div>
            <div>
                <div class="modulo-titulo">ANÁLISIS POR REDUCTOR</div>
                <div class="modulo-subtitulo">Estado de componentes por reductor</div>
            </div>
        </div>
    </div>
    <div class="modulo-body">
        <div class="reductores-grid">
            @foreach($reductores as $reductor)
                @php
                    $totalAnalisis = $reductor['total_analisis'] ?? 0;
                    $ultimaElongacion = $reductor['ultima_elongacion'] ?? 0;
                    $ultimaFecha = $reductor['ultima_fecha'] ?? null;
                    
                    $estadoElongacion = $ultimaElongacion >= 2.4 ? 'critico' : 
                                       ($ultimaElongacion >= 2.0 ? 'alerta' : 'normal');
                @endphp
                
                <div class="reductor-card">
                    <div class="reductor-nombre">{{ $reductor['nombre'] }}</div>
                    <div class="text-xs text-gray-500 mb-2">
                        <i class="far fa-calendar-alt mr-1"></i>
                        {{ $ultimaFecha ? \Carbon\Carbon::parse($ultimaFecha)->format('d/m/Y') : 'Sin datos' }}
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Análisis:</span>
                        <span class="font-semibold">{{ $totalAnalisis }}</span>
                    </div>
                    @if($ultimaElongacion > 0)
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-gray-500">Últ. elongación:</span>
                            <span class="reductor-valor {{ $estadoElongacion == 'critico' ? 'text-red-600' : ($estadoElongacion == 'alerta' ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ number_format($ultimaElongacion, 2) }} mm
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif