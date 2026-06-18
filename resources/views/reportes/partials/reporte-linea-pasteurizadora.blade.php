@php
    $linea = $reporte['linea'];
    $resumen = $reporte['resumen'] ?? [];
    $analisis = collect($reporte['analisis'] ?? []);
    $componentes = collect($reporte['componentes'] ?? []);
    $modulos = collect($reporte['modulos'] ?? []);
    $analisisTendencia = collect($reporte['analisis_tendencia'] ?? []);
    $analisis52124Reporte = $reporte['analisis_52124'] ?? [];
    $analisis30147Reporte = $reporte['analisis_30147'] ?? [];
    $ventanas52124Reporte = collect($analisis52124Reporte['ventanas'] ?? []);
    $ventanas30147Reporte = collect($analisis30147Reporte['ventanas'] ?? []);

    $pasteurIconosDisponibles = [
        'VIGAS_MOVIMIENTO',
        'VIGA_MOVIMIENTO',
        'BRAZO_TORSION',
        'PLACAS_PERNO',
        'VIGAS_FIJAS',
        'ESPARRAGOS',
        'EXCENTRICOS',
        'REGLILLAS',
        'ANILLAS',
        'RODAJAS',
        'PISTAS',
    ];

    $pasteurComponentIcon = function ($codigo) use ($pasteurIconosDisponibles) {
        $codigo = strtoupper(trim((string) $codigo));

        foreach ($pasteurIconosDisponibles as $codigoBase) {
            if ($codigo === $codigoBase || str_ends_with($codigo, '_' . $codigoBase)) {
                return asset('images/componentes-pasteurizadora/' . $codigoBase . '.png');
            }
        }

        return asset('images/icono_pas.png');
    };

    $totalDanos52 = $analisisTendencia->sum('total_danos_52_semanas');
    $totalDanos12 = $analisisTendencia->sum('total_danos_12_semanas');
    $totalDanos4 = $analisisTendencia->sum('total_danos_4_semanas');

    $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : 'Sin fecha';
    $safePercent = fn ($value) => max(0, min(100, (float) ($value ?? 0)));

    $estadoClass = function ($estado) {
        $estado = (string) $estado;

        if (\App\Models\AnalisisPasteurizadora::esEstadoDanado($estado)) {
            return 'estado-danado';
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estado)) {
            return $estado === 'Desgaste severo' ? 'estado-desgaste-severo' : 'estado-desgaste-moderado';
        }

        if ($estado === \App\Models\AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION) {
            return 'estado-revision';
        }

        if ($estado === \App\Models\AnalisisPasteurizadora::ESTADO_CAMBIADO) {
            return 'estado-cambiado';
        }

        return 'estado-bueno';
    };

    $estadoIcon = function ($estado) {
        $estado = (string) $estado;

        if (\App\Models\AnalisisPasteurizadora::esEstadoDanado($estado)) {
            return 'fa-times-circle';
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estado)) {
            return 'fa-exclamation-triangle';
        }

        if ($estado === \App\Models\AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION) {
            return 'fa-tools';
        }

        if ($estado === \App\Models\AnalisisPasteurizadora::ESTADO_CAMBIADO) {
            return 'fa-exchange-alt';
        }

        return 'fa-check-circle';
    };

    $trendToneClass = function ($tone) {
        return match ($tone) {
            'danger' => 'trend-danger',
            'success' => 'trend-success',
            'warning' => 'trend-warning',
            default => 'trend-info',
        };
    };

    $trendBadgeClass = function ($tone) {
        return match ($tone) {
            'danger' => 'estado-danado',
            'success' => 'estado-bueno',
            'warning' => 'estado-revision',
            default => 'estado-cambiado',
        };
    };

    $monthlyTrendRows = $analisisTendencia
        ->sortByDesc(fn ($item) => sprintf('%04d%02d', (int) $item->anio, (int) $item->mes))
        ->take(6);
@endphp

@once
<style>
    .pasteur-report-detail {
        --primary-blue: #2563eb;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --dark: #111827;
        --border: #e5e7eb;
        --surface: #ffffff;
        --background: #f8fafc;
        --soft-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .pasteur-report-detail .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .pasteur-report-detail .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--soft-shadow);
        overflow: hidden;
        padding: 18px;
        position: relative;
        transition: all .3s ease;
    }

    .pasteur-report-detail .stat-card:hover {
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, .08);
    }

    .pasteur-report-detail .stat-card::after {
        background: rgba(37, 99, 235, .04);
        border-radius: 50%;
        content: '';
        height: 100px;
        position: absolute;
        right: 0;
        top: 0;
        width: 100px;
    }

    .pasteur-report-detail .stat-header {
        align-items: center;
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
    }

    .pasteur-report-detail .stat-icon {
        align-items: center;
        border-radius: 12px;
        display: flex;
        font-size: 24px;
        height: 48px;
        justify-content: center;
        width: 48px;
        flex: 0 0 auto;
    }

    .pasteur-report-detail .stat-icon.total { background: #dbeafe; color: #2563eb; }
    .pasteur-report-detail .stat-icon.analisis { background: #fffbeb; color: #d97706; }
    .pasteur-report-detail .stat-icon.elongacion { background: #d1fae5; color: #059669; }
    .pasteur-report-detail .stat-icon.criticos { background: #f3e8ff; color: #7c3aed; }
    .pasteur-report-detail .stat-icon.fallas { background: #fee2e2; color: #dc2626; }

    .pasteur-report-detail .stat-label {
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .pasteur-report-detail .stat-value {
        color: var(--dark);
        font-family: 'JetBrains Mono', monospace;
        font-size: 32px;
        font-weight: 700;
        line-height: 1.1;
    }

    .pasteur-report-detail .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
    }

    .pasteur-report-detail .quick-action {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        color: #1d4ed8;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        gap: 8px;
        min-height: 42px;
        padding: 10px 14px;
        text-decoration: none;
        transition: background-color .2s ease, color .2s ease;
    }

    .pasteur-report-detail .quick-action:hover {
        background: #dbeafe;
        color: #1e40af;
    }

    .pasteur-report-detail .modulo-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--soft-shadow);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .pasteur-report-detail .modulo-header {
        align-items: center;
        background: linear-gradient(to right, #f9fafb, #ffffff);
        border-bottom: 1px solid var(--border);
        color: #111827;
        display: flex;
        justify-content: space-between;
        padding: 20px 24px;
    }

    .pasteur-report-detail .modulo-header-left {
        align-items: center;
        display: flex;
        gap: 16px;
        min-width: 0;
    }

    .pasteur-report-detail .modulo-icon {
        align-items: center;
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        color: #2563eb;
        display: flex;
        font-size: 24px;
        height: 48px;
        justify-content: center;
        width: 48px;
        flex: 0 0 auto;
    }

    .pasteur-report-detail .modulo-titulo {
        color: #111827;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
    }

    .pasteur-report-detail .modulo-subtitulo {
        color: #6b7280;
        font-size: 13px;
        margin-top: 3px;
    }

    .pasteur-report-detail .modulo-badge {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 40px;
        color: #1d4ed8;
        display: inline-flex;
        font-size: 14px;
        font-weight: 600;
        gap: 8px;
        padding: 8px 16px;
        white-space: nowrap;
    }

    .pasteur-report-detail .modulo-body {
        padding: 24px;
    }

    .pasteur-report-detail .componentes-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }

    .pasteur-report-detail .componente-card {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 16px;
        transition: all .3s ease;
    }

    .pasteur-report-detail .componente-card:hover {
        border-color: var(--primary-blue);
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, .06);
        transform: translateY(-2px);
    }

    .pasteur-report-detail .componente-header {
        align-items: center;
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
    }

    .pasteur-report-detail .componente-icono {
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 8px;
        display: flex;
        height: 42px;
        justify-content: center;
        width: 42px;
        flex: 0 0 auto;
    }

    .pasteur-report-detail .componente-icono img {
        height: 34px;
        object-fit: contain;
        width: 34px;
    }

    .pasteur-report-detail .componente-nombre {
        color: var(--dark);
        font-weight: 700;
        line-height: 1.25;
    }

    .pasteur-report-detail .componente-stats {
        color: #64748b;
        display: flex;
        font-size: 13px;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .pasteur-report-detail .progress-track {
        background: #e2e8f0;
        border-radius: 999px;
        height: 9px;
        overflow: hidden;
    }

    .pasteur-report-detail .progress-fill {
        background: linear-gradient(90deg, #2563eb, #10b981);
        display: block;
        height: 100%;
    }

    .pasteur-report-detail .estado-badge {
        align-items: center;
        border-radius: 40px;
        display: inline-flex;
        flex-wrap: wrap;
        font-size: 12px;
        font-weight: 600;
        gap: 6px;
        line-height: 1.2;
        max-width: 100%;
        padding: 6px 12px;
    }

    .pasteur-report-detail .estado-bueno {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .pasteur-report-detail .estado-desgaste-moderado,
    .pasteur-report-detail .estado-desgaste-severo {
        background: #ffedd5;
        border: 1px solid #fdba74;
        color: #9a3412;
    }

    .pasteur-report-detail .estado-danado {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .pasteur-report-detail .estado-cambiado {
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }

    .pasteur-report-detail .estado-revision {
        background: #fef3c7;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .pasteur-report-detail .estado-normal {
        background: #dcfce7;
        color: #166534;
    }

    .pasteur-report-detail .industrial-table {
        border-collapse: collapse;
        font-size: 14px;
        width: 100%;
    }

    .pasteur-report-detail .industrial-table th {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-bottom: 2px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .5px;
        padding: 16px;
        text-align: left;
        text-transform: uppercase;
    }

    .pasteur-report-detail .industrial-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 16px;
        vertical-align: middle;
    }

    .pasteur-report-detail .industrial-table tbody tr:hover {
        background: #f8fafc;
    }

    .pasteur-report-detail .metric-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        margin-bottom: 24px;
    }

    .pasteur-report-detail .metric-card,
    .pasteur-report-detail .trend-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
    }

    .pasteur-report-detail .metric-card {
        background: #ffffff;
        text-align: center;
    }

    .pasteur-report-detail .metric-label,
    .pasteur-report-detail .trend-label {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .pasteur-report-detail .metric-label {
        color: #64748b;
    }

    .pasteur-report-detail .metric-value,
    .pasteur-report-detail .trend-value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.1;
        margin-top: 6px;
    }

    .pasteur-report-detail .trend-info { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .pasteur-report-detail .trend-success { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
    .pasteur-report-detail .trend-warning { background: #fffbeb; border-color: #fde68a; color: #b45309; }
    .pasteur-report-detail .trend-danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

    .pasteur-report-detail .empty-state {
        align-items: center;
        color: #64748b;
        display: flex;
        flex-direction: column;
        gap: 8px;
        justify-content: center;
        min-height: 120px;
        text-align: center;
    }

    .pasteur-report-detail .componentes-grid > .empty-state {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .pasteur-report-detail .modulo-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .pasteur-report-detail .stats-grid,
        .pasteur-report-detail .componentes-grid,
        .pasteur-report-detail .metric-grid {
            grid-template-columns: 1fr;
        }

        .pasteur-report-detail .quick-action {
            justify-content: center;
            width: 100%;
        }
    }
</style>
@endonce

<div class="pasteur-report-detail">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon total">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <span class="stat-label">Total Analisis</span>
            </div>
            <div class="stat-value">{{ $resumen['total_analisis'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 mt-2">
                Componentes: {{ $resumen['componentes_revisados'] ?? 0 }}/{{ $resumen['total_componentes'] ?? $componentes->count() }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon analisis">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <span class="stat-label">Componentes Criticos</span>
            </div>
            <div class="stat-value">{{ $resumen['componentes_criticos'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 mt-2">
                Pendientes por cambio
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon elongacion">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span class="stat-label">Avance Modulos</span>
            </div>
            <div class="stat-value">{{ number_format($resumen['avance_historico_porcentaje'] ?? 0, 1) }}%</div>
            <div class="text-sm text-gray-500 mt-2">
                {{ $resumen['modulos_con_analisis'] ?? 0 }}/{{ $resumen['total_modulos'] ?? $modulos->count() }} modulos con analisis
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon criticos">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span class="stat-label">Analisis 52-12-4</span>
            </div>
            <div class="stat-value">{{ ($ventanas52124Reporte->last()['current'] ?? null) ?? $analisisTendencia->count() }}</div>
            <div class="text-sm text-gray-500 mt-2">
                Danos: {{ number_format($totalDanos4, 2) }} (4 sem)
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon fallas">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="stat-label">Analisis 30-14-7</span>
            </div>
            <div class="stat-value">{{ $ventanas30147Reporte->last()['current'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 mt-2">
                {{ $analisis30147Reporte['resumen']['estado']['label'] ?? 'Fallas recientes' }}
            </div>
        </div>
    </div>

    <div class="modulo-section">
        <div class="modulo-header">
            <div class="modulo-header-left">
                <div class="modulo-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <div class="modulo-titulo">ANALISIS DE COMPONENTES</div>
                    <div class="modulo-subtitulo">{{ $linea->nombre }} - componentes mecanicos de pasteurizadora</div>
                </div>
            </div>
            <div class="modulo-badge">
                <i class="fas fa-cubes"></i>
                {{ $componentes->count() }} Componentes
            </div>
        </div>

        <div class="modulo-body">
            <div class="componentes-grid">
                @forelse($componentes as $componente)
                    @php
                        $estadoActual = $componente['ultimo_estado'] ?? null;
                        $porcentajeCompletado = $safePercent($componente['porcentaje'] ?? 0);
                    @endphp

                    <div class="componente-card">
                        <div class="componente-header">
                            <div class="componente-icono">
                                <img
                                    src="{{ $pasteurComponentIcon($componente['codigo'] ?? null) }}"
                                    alt="{{ $componente['nombre'] }}"
                                    onerror="this.src='{{ asset('images/icono_pas.png') }}'">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="componente-nombre">{{ $componente['nombre'] }}</div>
                                <div class="text-xs text-gray-500">{{ $componente['codigo'] }}</div>
                            </div>
                        </div>

                        <div class="componente-stats">
                            <span>Analisis: {{ $componente['total_analisis'] }}</span>
                            <span>Revisadas: {{ $componente['cantidad_revisada'] }}/{{ $componente['total_configurado'] }}</span>
                        </div>

                        <div class="progress-track">
                            <span class="progress-fill" style="width: {{ $porcentajeCompletado }}%"></span>
                        </div>

                        <div class="text-xs text-gray-500 mt-2">
                            {{ number_format($porcentajeCompletado, 1) }}% revisado - Base {{ $componente['cantidad'] }} - Modulos {{ $componente['modulos_aplicables'] }}
                        </div>

                        <div class="mt-3 p-2 bg-white rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center gap-2 flex-wrap">
                                <span class="text-xs font-medium text-gray-500">Ultimo estado:</span>
                                @if($estadoActual)
                                    <span class="estado-badge {{ $estadoClass($estadoActual) }}">
                                        <i class="fas {{ $estadoIcon($estadoActual) }}"></i>
                                        {{ $estadoActual }}
                                    </span>
                                @else
                                    <span class="estado-badge estado-normal">
                                        <i class="fas fa-circle-info"></i>
                                        Sin datos
                                    </span>
                                @endif
                            </div>

                            @if($componente['ultimo_analisis'] ?? null)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $formatDate($componente['ultimo_analisis']->fecha_analisis) }}
                                </div>
                            @endif
                        </div>

                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id, 'componente' => $componente['codigo']]) }}"
                           class="mt-3 text-xs text-blue-600 hover:text-blue-800 flex items-center justify-center gap-1">
                            <i class="fas fa-search"></i>
                            Ver detalles del componente
                        </a>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fas fa-info-circle text-3xl"></i>
                        <p>No hay componentes configurados para esta pasteurizadora.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="modulo-section">
        <div class="modulo-header">
            <div class="modulo-header-left">
                <div class="modulo-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="modulo-titulo">AVANCE POR MODULOS</div>
                    <div class="modulo-subtitulo">Revision por modulo, nivel y lado de la linea</div>
                </div>
            </div>
            <div class="modulo-badge">
                <i class="fas fa-diagram-project"></i>
                {{ $modulos->count() }} Modulos
            </div>
        </div>

        <div class="modulo-body">
            <div class="componentes-grid">
                @forelse($modulos as $modulo)
                    @php
                        $porcentajeModulo = $safePercent($modulo['porcentaje'] ?? 0);
                        $criticosModulo = $modulo['criticos'] ?? 0;
                    @endphp

                    <div class="componente-card">
                        <div class="componente-header">
                            <div class="componente-icono">
                                <i class="fas fa-cube text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="componente-nombre">Modulo {{ $modulo['numero'] }}</div>
                                <div class="text-xs text-gray-500">Ultima revision: {{ $formatDate($modulo['ultima_revision']) }}</div>
                            </div>
                            <span class="estado-badge {{ $criticosModulo > 0 ? 'estado-danado' : 'estado-bueno' }}">
                                <i class="fas {{ $criticosModulo > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' }}"></i>
                                {{ $criticosModulo > 0 ? $criticosModulo . ' crit.' : 'OK' }}
                            </span>
                        </div>

                        <div class="componente-stats">
                            <span>Componentes: {{ $modulo['componentes_revisados'] }}/{{ $modulo['total_componentes'] }}</span>
                            <span>Registros: {{ $modulo['total_analisis'] }}</span>
                        </div>

                        <div class="progress-track">
                            <span class="progress-fill" style="width: {{ $porcentajeModulo }}%"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-3 text-xs text-gray-600">
                            <div class="bg-white border border-gray-100 rounded-lg p-2">
                                Superior: {{ $modulo['niveles']['SUPERIOR'] ?? 0 }}
                            </div>
                            <div class="bg-white border border-gray-100 rounded-lg p-2">
                                Inferior: {{ $modulo['niveles']['INFERIOR'] ?? 0 }}
                            </div>
                            <div class="bg-white border border-gray-100 rounded-lg p-2">
                                Vapor: {{ $modulo['lados']['VAPOR'] ?? 0 }}
                            </div>
                            <div class="bg-white border border-gray-100 rounded-lg p-2">
                                Pasillo: {{ $modulo['lados']['PASILLO'] ?? 0 }}
                            </div>
                        </div>

                        <div class="text-xs text-gray-500 mt-2">
                            {{ number_format($porcentajeModulo, 1) }}% de avance del modulo
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fas fa-info-circle text-3xl"></i>
                        <p>No hay modulos configurados para esta pasteurizadora.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if($analisisTendencia->count() > 0 || $ventanas52124Reporte->isNotEmpty())
        <div class="modulo-section">
            <div class="modulo-header">
                <div class="modulo-header-left">
                    <div class="modulo-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <div class="modulo-titulo">ANALISIS 52-12-4</div>
                        <div class="modulo-subtitulo">Tendencias de danos por periodo</div>
                    </div>
                </div>
                <div class="modulo-badge">
                    <i class="fas fa-chart-bar"></i>
                    {{ $analisis52124Reporte['resumen']['estado']['label'] ?? ($analisisTendencia->count() . ' periodos') }}
                </div>
            </div>

            <div class="modulo-body">
                @if($ventanas52124Reporte->isNotEmpty())
                    <div class="metric-grid">
                        @foreach($ventanas52124Reporte as $ventana)
                            <div class="trend-card {{ $trendToneClass($ventana['tone'] ?? 'info') }}">
                                <div class="trend-label">{{ $ventana['label'] }}</div>
                                <div class="trend-value">{{ $ventana['current'] ?? 0 }}</div>
                                <div class="text-xs mt-1">
                                    Anterior: {{ $ventana['previous'] ?? 0 }}
                                    <span class="font-semibold ml-2">{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</span>
                                </div>
                                <div class="text-[11px] mt-2 opacity-80">{{ $ventana['current_range'] ?? 'Sin rango' }}</div>
                                <div class="text-[11px] mt-1 opacity-80">
                                    Componentes: {{ $ventana['current_componentes'] ?? 0 }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($analisisTendencia->count() > 0)
                    <div class="metric-grid">
                        <div class="metric-card">
                            <div class="metric-label">52 Semanas</div>
                            <div class="metric-value">{{ number_format($totalDanos52, 2) }}</div>
                            <div class="text-xs text-gray-500">Total danos</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-label">12 Semanas</div>
                            <div class="metric-value">{{ number_format($totalDanos12, 2) }}</div>
                            <div class="text-xs text-gray-500">Total danos</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-label">4 Semanas</div>
                            <div class="metric-value">{{ number_format($totalDanos4, 2) }}</div>
                            <div class="text-xs text-gray-500">Total danos</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="industrial-table">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>52 Semanas</th>
                                    <th>12 Semanas</th>
                                    <th>4 Semanas</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyTrendRows as $item)
                                    <tr>
                                        <td class="font-medium">{{ $item->periodo }}</td>
                                        <td>{{ number_format($item->total_danos_52_semanas, 2) }}</td>
                                        <td>{{ number_format($item->total_danos_12_semanas, 2) }}</td>
                                        <td>{{ number_format($item->total_danos_4_semanas, 2) }}</td>
                                        <td>{{ $item->observaciones ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="text-center mt-4">
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.index', ['linea_id' => $linea->id]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                        <i class="fas fa-calendar-alt"></i>
                        Ver analisis completo
                    </a>
                </div>
            </div>
        </div>
    @endif

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
                    <i class="fas fa-bolt"></i>
                    {{ $analisis30147Reporte['resumen']['estado']['label'] ?? 'Sin fallas' }}
                </div>
            </div>

            <div class="modulo-body">
                <div class="metric-grid">
                    @foreach($ventanas30147Reporte as $ventana)
                        <div class="trend-card {{ $trendToneClass($ventana['tone'] ?? 'info') }}">
                            <div class="trend-label">{{ $ventana['label'] }}</div>
                            <div class="trend-value">{{ $ventana['current'] ?? 0 }}</div>
                            <div class="text-xs mt-1">
                                Anterior: {{ $ventana['previous'] ?? 0 }}
                                <span class="font-semibold ml-2">{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</span>
                            </div>
                            <div class="text-[11px] mt-2 opacity-80">{{ $ventana['current_range'] ?? 'Sin rango' }}</div>
                            <div class="text-[11px] mt-1 opacity-80">
                                Componentes: {{ $ventana['current_componentes'] ?? 0 }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="overflow-x-auto">
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
                                    <td>
                                        <span class="estado-badge {{ $trendBadgeClass($ventana['tone'] ?? 'info') }}">
                                            {{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}
                                        </span>
                                    </td>
                                    <td>Componentes: {{ $ventana['current_componentes'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="modulo-section">
        <div class="modulo-header">
            <div class="modulo-header-left">
                <div class="modulo-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div>
                    <div class="modulo-titulo">HISTORICO DE REVISIONES</div>
                    <div class="modulo-subtitulo">Ultimos analisis registrados de la pasteurizadora</div>
                </div>
            </div>
            <div class="modulo-badge">
                <i class="fas fa-clipboard-list"></i>
                {{ $analisis->count() }} revisiones
            </div>
        </div>

        <div class="modulo-body">
            <div class="overflow-x-auto">
                <table class="industrial-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Modulo</th>
                            <th>Nivel / Lado</th>
                            <th>Componente</th>
                            <th>Estado</th>
                            <th>Orden</th>
                            <th>Actividad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analisis->take(12) as $item)
                            @php $estadoActual = $item->estado; @endphp
                            <tr>
                                <td>{{ $formatDate($item->fecha_analisis) }}</td>
                                <td>Modulo {{ $item->modulo }}</td>
                                <td>{{ $item->nivel ?? '-' }} / {{ $item->lado ?? '-' }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <img
                                            src="{{ $pasteurComponentIcon($item->componente ?? null) }}"
                                            class="w-6 h-6 object-contain"
                                            alt="{{ $item->componente_nombre }}"
                                            onerror="this.src='{{ asset('images/icono_pas.png') }}'">
                                        <span>{{ $item->componente_nombre }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="estado-badge {{ $estadoClass($estadoActual) }}">
                                        <i class="fas {{ $estadoIcon($estadoActual) }}"></i>
                                        {{ $estadoActual }}
                                    </span>
                                </td>
                                <td>{{ $item->numero_orden ?: '-' }}</td>
                                <td class="max-w-xs">
                                    <p class="truncate">{{ \Illuminate\Support\Str::limit($item->actividad, 90) ?: '-' }}</p>
                                </td>
                                <td>
                                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.show', ['analisispasteurizadora' => $item->id]) }}"
                                       class="text-blue-700 font-bold">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-info-circle text-3xl"></i>
                                        <p>No hay analisis registrados en este periodo.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('historico-revisados.index', ['tipo' => 'pasteurizadora', 'linea_id' => $linea->id]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-history"></i>
                    Ver historial completo
                </a>
            </div>
        </div>
    </div>
</div>
