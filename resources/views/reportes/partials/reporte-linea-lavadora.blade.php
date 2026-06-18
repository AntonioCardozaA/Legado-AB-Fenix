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
    $analisis52124Reporte = $reporte['analisis_52124'] ?? [];
    $analisis30147Reporte = $reporte['analisis_30147'] ?? [];
    $ventanas52124Reporte = collect($analisis52124Reporte['ventanas'] ?? []);
    $ventanas30147Reporte = collect($analisis30147Reporte['ventanas'] ?? []);
    $trendToneClass = function ($tone) {
        return match ($tone) {
            'danger' => 'text-red-700 bg-red-50 border-red-200',
            'success' => 'text-green-700 bg-green-50 border-green-200',
            'warning' => 'text-amber-700 bg-amber-50 border-amber-200',
            default => 'text-blue-700 bg-blue-50 border-blue-200',
        };
    };

    $lavadoraIconosDisponibles = [
        'RV200_SIN_FIN',
        'SERVO_GRANDE',
        'SERVO_CHICO',
        'GUI_INF_TANQUE',
        'GUI_INT_TANQUE',
        'GUI_SUP_TANQUE',
        'BUJE_ESPIGA',
        'CATARINAS',
        'RV200',
    ];

    $lavadoraComponentIcon = function ($codigo) use ($lavadoraIconosDisponibles) {
        $codigo = strtoupper(trim((string) $codigo));

        foreach ($lavadoraIconosDisponibles as $codigoBase) {
            if ($codigo === $codigoBase || str_ends_with($codigo, '_' . $codigoBase)) {
                return asset('images/componentes-lavadora/' . $codigoBase . '.png');
            }
        }

        return asset('images/icono-maquina.png');
    };
    
    $totalDaños52 = $analisisTendencia->sum('total_danos_52_semanas');
    $totalDaños12 = $analisisTendencia->sum('total_danos_12_semanas');
    $totalDaños4 = $analisisTendencia->sum('total_danos_4_semanas');
    
    // Estado general
    $colorEstado = match($resumen['estado_general']['texto'] ?? 'SIN DATOS') {
        'CRÍTICO' => 'danger',
        'SEVERO / MODERADO', 'ALERTA' => 'warning',
        'REQUIERE REVISIÓN' => 'revision',
        'ESTABLE' => 'success',
        default => 'gray'
    };
@endphp

<style>
    :root {
        --primary-blue: #2563eb;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --dark: #111827;
        --dark-light: #374151;
        --border: #e5e7eb;
        --background: #f8fafc;
        --surface: #ffffff;
        --soft-blue: #eff6ff;
        --soft-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--surface);
        border-radius: 12px;
        padding: 18px;
        box-shadow: var(--soft-shadow);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(37, 99, 235, 0.04);
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

    .stat-icon.total { background: #dbeafe; color: #2563eb; }
    .stat-icon.analisis { background: #fffbeb; color: #d97706; }
    .stat-icon.elongacion { background: #d1fae5; color: #059669; }
    .stat-icon.criticos { background: #f3e8ff; color: #7c3aed; }

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
        background: var(--surface);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--soft-shadow);
        border: 1px solid var(--border);
        margin-bottom: 24px;
    }

    .modulo-header {
        background: linear-gradient(to right, #f9fafb, #ffffff);
        color: #111827;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
    }

    .modulo-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .modulo-icon {
        width: 48px;
        height: 48px;
        background: #dbeafe;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    .modulo-titulo {
        font-size: 20px;
        font-weight: 700;
    }

    .modulo-subtitulo {
        font-size: 13px;
        color: #6b7280;
    }

    .modulo-badge {
        background: #eff6ff;
        color: #1d4ed8;
        padding: 8px 16px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        border: 1px solid #bfdbfe;
    }

    .modulo-body {
        padding: 24px;
    }

    /* Grid de componentes */
    .componentes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }

    .componente-card {
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }

    .componente-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.06);
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
        border-radius: 8px;
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
        background: #ffedd5;
        color: #9a3412;
        border: 1px solid #fdba74;
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

    .estado-revision {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
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
        background: #f9fafb;
        border: 1px solid #e5e7eb;
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
        border-radius: 8px;
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
        border-radius: 8px;
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
            Componentes: {{ $resumen['componentes_revisados'] ?? 0 }}/{{ $resumen['total_componentes'] ?? count($componentes) }}
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
                        'Requiere revisión' => 'revision',
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
                            <img src="{{ $lavadoraComponentIcon($componente['codigo'] ?? null) }}" 
                                 alt="{{ $componente['nombre'] }}"
                                 class="w-8 h-8 object-contain"
                                 onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
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
                                        @elseif($estadoColor == 'revision') fa-tools
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
                            <span class="text-gray-500">Hodómetro: {{ $registro->hodometro_formateado ?? '-' }}</span>
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
                                    <td>{{ $registro->hodometro_formateado ?? '-' }}</td>
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
@if($analisisTendencia->count() > 0 || $ventanas52124Reporte->isNotEmpty())
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
        @if($ventanas52124Reporte->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @foreach($ventanas52124Reporte as $ventana)
                    <div class="p-4 rounded-lg border {{ $trendToneClass($ventana['tone'] ?? 'info') }}">
                        <div class="text-xs font-bold uppercase tracking-wide mb-1">{{ $ventana['label'] }}</div>
                        <div class="text-3xl font-bold">{{ $ventana['current'] ?? 0 }}</div>
                        <div class="text-xs mt-1">
                            Anterior: {{ $ventana['previous'] ?? 0 }}
                            <span class="font-semibold ml-2">{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</span>
                        </div>
                        <div class="text-[11px] mt-2 opacity-80">{{ $ventana['current_range'] ?? 'Sin rango' }}</div>
                        <div class="text-[11px] mt-1 opacity-80">
                            Componentes: {{ $ventana['current_componentes'] ?? 0 }} | Elongacion: {{ $ventana['current_elongaciones'] ?? 0 }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($analisisTendencia->count() > 0)
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
        @endif
        
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
            <a href="{{ route('analisis-tendencia-mensual.lavadora.index', ['linea_id' => $reporte['linea']->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-calendar-alt"></i>
                Ver análisis completo
            </a>
        </div>
    </div>
</div>
@endif

{{-- SECCIÓN 4: HISTÓRICO DE REVISIONES (Módulo 5) --}}
@if($ventanas30147Reporte->isNotEmpty())
<div class="modulo-section">
    <div class="modulo-header">
        <div class="modulo-header-left">
            <div class="modulo-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="modulo-titulo">ANALISIS 30-14-7</div>
                <div class="modulo-subtitulo">Fallas recientes contra periodo anterior</div>
            </div>
        </div>
        <div class="modulo-badge">
            <i class="fas fa-bolt mr-2"></i>
            {{ $analisis30147Reporte['resumen']['estado']['label'] ?? 'Sin fallas' }}
        </div>
    </div>
    <div class="modulo-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @foreach($ventanas30147Reporte as $ventana)
                <div class="p-4 rounded-lg border {{ $trendToneClass($ventana['tone'] ?? 'info') }}">
                    <div class="text-xs font-bold uppercase tracking-wide mb-1">{{ $ventana['label'] }}</div>
                    <div class="text-3xl font-bold">{{ $ventana['current'] ?? 0 }}</div>
                    <div class="text-xs mt-1">
                        Anterior: {{ $ventana['previous'] ?? 0 }}
                        <span class="font-semibold ml-2">{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</span>
                    </div>
                    <div class="text-[11px] mt-2 opacity-80">{{ $ventana['current_range'] ?? 'Sin rango' }}</div>
                    <div class="text-[11px] mt-1 opacity-80">
                        Componentes: {{ $ventana['current_componentes'] ?? 0 }} | Elongacion: {{ $ventana['current_elongaciones'] ?? 0 }}
                    </div>
                </div>
            @endforeach
        </div>

        <table class="industrial-table">
            <thead>
                <tr>
                    <th>Ventana</th>
                    <th>Periodo actual</th>
                    <th>Actual</th>
                    <th>Anterior</th>
                    <th>Diferencia</th>
                    <th>Origen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventanas30147Reporte as $ventana)
                    <tr>
                        <td class="font-medium">{{ $ventana['label'] }}</td>
                        <td>{{ $ventana['current_range'] ?? '-' }}</td>
                        <td>{{ $ventana['current'] ?? 0 }}</td>
                        <td>{{ $ventana['previous'] ?? 0 }}</td>
                        <td>{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</td>
                        <td>Comp: {{ $ventana['current_componentes'] ?? 0 }} / Elong: {{ $ventana['current_elongaciones'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

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
                            'Requiere revisión' => 'revision',
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
                                <img src="{{ $lavadoraComponentIcon($item->componente->codigo ?? null) }}" 
                                     class="w-6 h-6 object-contain"
                                     onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
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
@if(false && count($reductores) > 0)
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
