@php
    $linea = $reporte['linea'];
    $resumen = $reporte['resumen'] ?? [];
    $analisis = collect($reporte['analisis'] ?? []);
    $analisisHistorico = collect($reporte['analisis_historico'] ?? $analisis);
    $historialAuditado = collect($reporte['analisis_historico_detallado'] ?? []);
    $componentes = collect($reporte['componentes'] ?? []);
    $modulos = collect($reporte['modulos'] ?? []);
    $analisisTendencia = collect($reporte['analisis_tendencia'] ?? []);
    $analisis52124Reporte = $reporte['analisis_52124'] ?? [];
    $analisis30147Reporte = $reporte['analisis_30147'] ?? [];
    $ventanas52124Reporte = collect($analisis52124Reporte['ventanas'] ?? []);
    $ventanas30147Reporte = collect($analisis30147Reporte['ventanas'] ?? []);
    $ventanaPrincipal52124 = $ventanas52124Reporte->first();
    $ventanaPrincipal30147 = $ventanas30147Reporte->first();
    $etiquetaVentanaResumen = function ($ventana) {
        $label = (string) ($ventana['label'] ?? '');

        return trim(str_replace(
            [' semanas', ' semana', ' dias', ' dia'],
            ['s', 's', 'd', 'd'],
            $label
        ));
    };

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
            'danger' => 'text-red-700 bg-red-50 border-red-200',
            'success' => 'text-green-700 bg-green-50 border-green-200',
            'warning' => 'text-amber-700 bg-amber-50 border-amber-200',
            default => 'text-blue-700 bg-blue-50 border-blue-200',
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

    $trendDeltaClass = function ($variacion) {
        $diferencia = data_get($variacion, 'diferencia');

        if ($diferencia > 0) {
            return 'text-red-600';
        }

        if ($diferencia < 0) {
            return 'text-green-600';
        }

        return '';
    };

    $formatTrendDelta = function ($variacion) {
        $diferencia = data_get($variacion, 'diferencia');

        if ($diferencia === null) {
            return '-';
        }

        return ($diferencia > 0 ? '+' : '') . number_format($diferencia, 2);
    };

    $evidenceList = function ($item) {
        $imagenes = is_array($item)
            ? ($item['evidencias'] ?? [])
            : ($item->evidencia_fotos ?? []);

        if (is_string($imagenes)) {
            $imagenes = json_decode($imagenes, true) ?? [];
        }

        return collect(is_array($imagenes) ? $imagenes : [])->filter()->values();
    };

    $evidenceUrl = fn ($foto) => \Illuminate\Support\Facades\Storage::url($foto);

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
        width: 100%;
        max-width: 100%;
        overflow-x: clip;
    }

    .pasteur-report-detail *,
    .pasteur-report-detail *::before,
    .pasteur-report-detail *::after {
        box-sizing: border-box;
        min-width: 0;
    }

    .pasteur-report-detail h2,
    .pasteur-report-detail h3,
    .pasteur-report-detail h4,
    .pasteur-report-detail p,
    .pasteur-report-detail span,
    .pasteur-report-detail td {
        overflow-wrap: anywhere;
    }

    .pasteur-report-detail .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
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

    .pasteur-report-detail .stat-window-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
        margin-top: 10px;
        position: relative;
        z-index: 1;
    }

    .pasteur-report-detail .stat-window-pill {
        background: rgba(255, 255, 255, .78);
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 4px;
        text-align: center;
    }

    .pasteur-report-detail .stat-window-label {
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .pasteur-report-detail .stat-window-value {
        color: #111827;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 2px;
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
    .pasteur-report-detail .stat-icon.fallas { background: #f3e8ff; color: #7c3aed; }

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
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr));
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

    .pasteur-report-detail .detail-mini-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 12px;
    }

    .pasteur-report-detail .detail-mini {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 8px;
        color: #64748b;
        font-size: 12px;
        padding: 8px;
    }

    .pasteur-report-detail .detail-mini strong {
        color: #111827;
        display: block;
        font-family: 'JetBrains Mono', monospace;
        font-size: 14px;
        line-height: 1.2;
        margin-top: 2px;
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
        min-width: 760px;
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
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
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

    .pasteur-report-detail .evidence-list {
        align-items: center;
        display: flex;
        gap: 6px;
    }

    .pasteur-report-detail .evidence-thumb {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        display: block;
        height: 34px;
        object-fit: cover;
        width: 34px;
    }

    .pasteur-report-detail .evidence-more {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        color: #1d4ed8;
        display: inline-flex;
        font-size: 11px;
        font-weight: 700;
        height: 28px;
        justify-content: center;
        min-width: 28px;
        padding: 0 7px;
    }

    @media (max-width: 768px) {
        .pasteur-report-detail .modulo-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .pasteur-report-detail .stats-grid,
        .pasteur-report-detail .componentes-grid,
        .pasteur-report-detail .metric-grid,
        .pasteur-report-detail .detail-mini-grid {
            grid-template-columns: 1fr;
        }

        .pasteur-report-detail .quick-action {
            justify-content: center;
            width: 100%;
        }

        .pasteur-report-detail .industrial-table {
            min-width: 680px;
        }

        .pasteur-report-detail .modulo-body,
        .pasteur-report-detail .stat-card,
        .pasteur-report-detail .metric-card,
        .pasteur-report-detail .trend-card {
            padding: 14px;
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
            <div class="stat-value">{{ $ventanaPrincipal52124['current'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 mt-2">
                Daños en {{ $ventanaPrincipal52124['label'] ?? '52 semanas' }}
            </div>
            @if($ventanas52124Reporte->isNotEmpty())
                <div class="stat-window-grid">
                    @foreach($ventanas52124Reporte as $ventana)
                        <div class="stat-window-pill">
                            <div class="stat-window-label">{{ $etiquetaVentanaResumen($ventana) }}</div>
                            <div class="stat-window-value">{{ $ventana['current'] ?? 0 }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon criticos">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="stat-label">Analisis 30-14-7</span>
            </div>
            <div class="stat-value">{{ $ventanaPrincipal30147['current'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 mt-2">
                Daños en {{ $ventanaPrincipal30147['label'] ?? '30 dias' }}
            </div>
            @if($ventanas30147Reporte->isNotEmpty())
                <div class="stat-window-grid">
                    @foreach($ventanas30147Reporte as $ventana)
                        <div class="stat-window-pill">
                            <div class="stat-window-label">{{ $etiquetaVentanaResumen($ventana) }}</div>
                            <div class="stat-window-value">{{ $ventana['current'] ?? 0 }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
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
                    <div class="modulo-subtitulo">{{ $linea->nombre }} - historial por componente mecanico</div>
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
                            <span>Historial: {{ $componente['total_analisis'] }}</span>
                            <span>Periodo: {{ $componente['total_analisis_periodo'] ?? 0 }}</span>
                        </div>

                        <div class="progress-track">
                            <span class="progress-fill" style="width: {{ $porcentajeCompletado }}%"></span>
                        </div>

                        <div class="text-xs text-gray-500 mt-2">
                            {{ number_format($porcentajeCompletado, 1) }}% del periodo
                        </div>

                        <div class="detail-mini-grid">
                            <div class="detail-mini">
                                Revisadas
                                <strong>{{ $componente['cantidad_revisada'] }}/{{ $componente['total_configurado'] }}</strong>
                            </div>
                            <div class="detail-mini">
                                Historico
                                <strong>{{ $componente['cantidad_revisada_historico'] ?? 0 }}</strong>
                            </div>
                            <div class="detail-mini">
                                Base
                                <strong>{{ $componente['cantidad'] }}</strong>
                            </div>
                            <div class="detail-mini">
                                Modulos
                                <strong>{{ $componente['modulos_aplicables'] }}</strong>
                            </div>
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
                                    @if(!empty($componente['ultimo_modulo']))
                                        - Modulo {{ $componente['ultimo_modulo'] }}
                                    @endif
                                    @if(!empty($componente['ultimo_nivel']) || !empty($componente['ultimo_lado']))
                                        - {{ $componente['ultimo_nivel'] ?? '-' }} / {{ $componente['ultimo_lado'] ?? '-' }}
                                    @endif
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

            @if($componentes->count() > 0)
                <div class="text-center mt-4">
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                        <i class="fas fa-chart-pie"></i>
                        Ver todos los analisis
                    </a>
                </div>
            @endif
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
                        <div class="modulo-subtitulo">Tendencias de daños por periodo</div>
                    </div>
                </div>
                <div class="modulo-badge">
                    <i class="fas fa-chart-bar"></i>
                    {{ $analisis52124Reporte['resumen']['estado']['label'] ?? ($analisisTendencia->count() . ' periodos') }}
                </div>
            </div>

            <div class="modulo-body">
                @if($ventanas52124Reporte->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        @foreach($ventanas52124Reporte as $ventana)
                            @php $ladosVentana = $ventana['current_lados'] ?? []; @endphp
                            <div class="p-4 rounded-lg border {{ $trendToneClass($ventana['tone'] ?? 'info') }}">
                                <div class="text-xs font-bold uppercase tracking-wide mb-1">{{ $ventana['label'] }}</div>
                                <div class="text-3xl font-bold">{{ $ventana['current'] ?? 0 }}</div>
                                <div class="text-xs mt-1">
                                    Anterior: {{ $ventana['previous'] ?? 0 }}
                                    <span class="font-semibold ml-2">{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</span>
                                </div>
                                <div class="text-[11px] mt-2 opacity-80">{{ $ventana['current_range'] ?? 'Sin rango' }}</div>
                                <div class="text-[11px] mt-1 opacity-80">
                                    Analisis registrados: {{ $ventana['current_componentes'] ?? 0 }}
                                </div>
                                @if(!empty($ladosVentana))
                                    <div class="text-[11px] mt-1 opacity-80">
                                        Vapor: {{ $ladosVentana['VAPOR'] ?? 0 }} - Pasillo: {{ $ladosVentana['PASILLO'] ?? 0 }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @php
                    $ventanaHistorial52124 = $ventanas52124Reporte->first() ?? [];
                    $eventosHistorial52124 = collect($ventanaHistorial52124['current_eventos'] ?? []);
                @endphp
                @if($eventosHistorial52124->isNotEmpty())
                    <div class="mb-6 overflow-x-auto">
                        <div class="text-sm font-semibold text-gray-700 mb-2">
                            Daños detectados en {{ $ventanaHistorial52124['label'] ?? 'el periodo' }}
                        </div>
                        <table class="industrial-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Componente</th>
                                    <th>Modulo</th>
                                    <th>Nivel</th>
                                    <th>Lado</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($eventosHistorial52124 as $evento)
                                    <tr>
                                        <td>{{ $evento['fecha'] ?? '-' }}</td>
                                        <td>{{ $evento['componente'] ?? '-' }}</td>
                                        <td>{{ $evento['modulo'] ?? '-' }}</td>
                                        <td>{{ $evento['nivel'] ?? '-' }}</td>
                                        <td>{{ $evento['lado'] ?? '-' }}</td>
                                        <td>{{ $evento['estado'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($analisisTendencia->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-sm text-gray-500 mb-1">52 Semanas</div>
                            <div class="text-2xl font-bold text-gray-800">{{ number_format($totalDanos52, 2) }}</div>
                            <div class="text-xs text-gray-500">Total daños</div>
                        </div>
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-sm text-gray-500 mb-1">12 Semanas</div>
                            <div class="text-2xl font-bold text-gray-800">{{ number_format($totalDanos12, 2) }}</div>
                            <div class="text-xs text-gray-500">Total daños</div>
                        </div>
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-sm text-gray-500 mb-1">4 Semanas</div>
                            <div class="text-2xl font-bold text-gray-800">{{ number_format($totalDanos4, 2) }}</div>
                            <div class="text-xs text-gray-500">Total daños</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="industrial-table">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>52 Semanas</th>
                                    <th>Vs Mes Ant</th>
                                    <th>12 Semanas</th>
                                    <th>Vs Mes Ant</th>
                                    <th>4 Semanas</th>
                                    <th>Vs Mes Ant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyTrendRows as $item)
                                    @php
                                        $variacion52 = $item->variacion_52_semanas ?? null;
                                        $variacion12 = $item->variacion_12_semanas ?? null;
                                        $variacion4 = $item->variacion_4_semanas ?? null;
                                    @endphp
                                    <tr>
                                        <td class="font-medium">{{ $item->periodo }}</td>
                                        <td>{{ number_format($item->total_danos_52_semanas, 2) }}</td>
                                        <td class="{{ $trendDeltaClass($variacion52) }}">{{ $formatTrendDelta($variacion52) }}</td>
                                        <td>{{ number_format($item->total_danos_12_semanas, 2) }}</td>
                                        <td class="{{ $trendDeltaClass($variacion12) }}">{{ $formatTrendDelta($variacion12) }}</td>
                                        <td>{{ number_format($item->total_danos_4_semanas, 2) }}</td>
                                        <td class="{{ $trendDeltaClass($variacion4) }}">{{ $formatTrendDelta($variacion4) }}</td>
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    @foreach($ventanas30147Reporte as $ventana)
                        @php $ladosVentana = $ventana['current_lados'] ?? []; @endphp
                        <div class="p-4 rounded-lg border {{ $trendToneClass($ventana['tone'] ?? 'info') }}">
                            <div class="text-xs font-bold uppercase tracking-wide mb-1">{{ $ventana['label'] }}</div>
                            <div class="text-3xl font-bold">{{ $ventana['current'] ?? 0 }}</div>
                            <div class="text-xs mt-1">
                                Anterior: {{ $ventana['previous'] ?? 0 }}
                                <span class="font-semibold ml-2">{{ (($ventana['delta'] ?? 0) > 0 ? '+' : '') . ($ventana['delta'] ?? 0) }}</span>
                            </div>
                            <div class="text-[11px] mt-2 opacity-80">{{ $ventana['current_range'] ?? 'Sin rango' }}</div>
                            <div class="text-[11px] mt-1 opacity-80">
                                Analisis registrados: {{ $ventana['current_componentes'] ?? 0 }}
                            </div>
                            @if(!empty($ladosVentana))
                                <div class="text-[11px] mt-1 opacity-80">
                                    Vapor: {{ $ladosVentana['VAPOR'] ?? 0 }} - Pasillo: {{ $ladosVentana['PASILLO'] ?? 0 }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @php
                    $ventanaHistorial30147 = $ventanas30147Reporte->first() ?? [];
                    $eventosHistorial30147 = collect($ventanaHistorial30147['current_eventos'] ?? []);
                @endphp
                @if($eventosHistorial30147->isNotEmpty())
                    <div class="mb-6 overflow-x-auto">
                        <div class="text-sm font-semibold text-gray-700 mb-2">
                            Daños detectados en {{ $ventanaHistorial30147['label'] ?? 'el periodo' }}
                        </div>
                        <table class="industrial-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Componente</th>
                                    <th>Modulo</th>
                                    <th>Nivel</th>
                                    <th>Lado</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($eventosHistorial30147 as $evento)
                                    <tr>
                                        <td>{{ $evento['fecha'] ?? '-' }}</td>
                                        <td>{{ $evento['componente'] ?? '-' }}</td>
                                        <td>{{ $evento['modulo'] ?? '-' }}</td>
                                        <td>{{ $evento['nivel'] ?? '-' }}</td>
                                        <td>{{ $evento['lado'] ?? '-' }}</td>
                                        <td>{{ $evento['estado'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

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
                                <th>Lados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ventanas30147Reporte as $ventana)
                                @php $ladosVentana = $ventana['current_lados'] ?? []; @endphp
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
                                    <td>Analisis: {{ $ventana['current_componentes'] ?? 0 }}</td>
                                    <td>
                                        @if(!empty($ladosVentana))
                                            V: {{ $ladosVentana['VAPOR'] ?? 0 }} / P: {{ $ladosVentana['PASILLO'] ?? 0 }}
                                        @else
                                            -
                                        @endif
                                    </td>
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
                {{ $analisisHistorico->count() }} revisiones
            </div>
        </div>

        <div class="modulo-body">
            <div class="overflow-x-auto">
                <table class="industrial-table">
                    <thead>
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Modulo</th>
                            <th>Nivel / Lado</th>
                            <th>Componente</th>
                            <th>Piezas</th>
                            <th>Estado</th>
                            <th>Cambio</th>
                            <th>Accion / Observacion</th>
                            <th>Usuario</th>
                            <th>Evidencias</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($historialAuditado->isNotEmpty() ? $historialAuditado : $analisisHistorico)->take(12) as $item)
                            @php
                                $filaAuditada = is_array($item);
                                $estadoActual = $filaAuditada ? ($item['estado'] ?? null) : ($item->estado ?? null);
                                $evidencias = $evidenceList($item);
                                $showUrl = $filaAuditada
                                    ? ($item['show_url'] ?? null)
                                    : route('pasteurizadora.analisis-pasteurizadora.show', ['analisispasteurizadora' => $item->id]);
                                $componenteNombreFila = $filaAuditada
                                    ? ($item['componente_nombre'] ?? '-')
                                    : ($item->componente_nombre ?? '-');
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-medium">{{ $filaAuditada ? ($item['fecha_label'] ?? '-') : $formatDate($item->fecha_analisis) }}</div>
                                    <div class="text-xs text-gray-500">{{ $filaAuditada ? ($item['hora_label'] ?? '-') : ($item->created_at?->format('H:i') ?? '-') }}</div>
                                </td>
                                <td>Modulo {{ $filaAuditada ? ($item['modulo'] ?? '-') : $item->modulo }}</td>
                                <td>{{ $filaAuditada ? ($item['nivel'] ?? '-') : ($item->nivel ?? '-') }} / {{ $filaAuditada ? ($item['lado'] ?? '-') : ($item->lado ?? '-') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <img
                                            src="{{ $pasteurComponentIcon($filaAuditada ? ($item['componente_codigo'] ?? null) : ($item->componente ?? null)) }}"
                                            class="w-6 h-6 object-contain"
                                            alt="{{ $componenteNombreFila }}"
                                            onerror="this.src='{{ asset('images/icono_pas.png') }}'">
                                        <span>{{ $componenteNombreFila }}</span>
                                    </div>
                                </td>
                                <td>{{ $filaAuditada ? ($item['componentes_label'] ?? 'Sin detalle') : 'Sin detalle' }}</td>
                                <td>
                                    <span class="estado-badge {{ $estadoClass($estadoActual) }}">
                                        <i class="fas {{ $estadoIcon($estadoActual) }}"></i>
                                        {{ $estadoActual }}
                                    </span>
                                </td>
                                <td class="max-w-xs">
                                    @if($filaAuditada)
                                        <div class="font-semibold text-gray-800">{{ $item['cambio_resumen'] ?? '-' }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $item['cambio_detalle'] ?? '' }}</div>
                                    @else
                                        <span class="text-xs text-gray-400">Sin comparativo</span>
                                    @endif
                                </td>
                                <td class="max-w-xs">
                                    <div class="font-semibold text-gray-800">
                                        {{ $filaAuditada ? ($item['accion_correctiva'] ?? '-') : ($item->numero_orden ?: '-') }}
                                    </div>
                                    <p class="truncate text-xs text-gray-600 mt-1">
                                        {{ \Illuminate\Support\Str::limit($filaAuditada ? ($item['observaciones'] ?? '') : ($item->actividad ?? ''), 90) ?: '-' }}
                                    </p>
                                </td>
                                <td>
                                    {{ $filaAuditada ? ($item['usuario_nombre'] ?? 'Usuario no registrado') : ($item->usuario?->name ?? $item->responsable ?? 'Usuario no registrado') }}
                                </td>
                                <td>
                                    @if($evidencias->isNotEmpty())
                                        <div class="evidence-list">
                                            @foreach($evidencias->take(3) as $foto)
                                                <a href="{{ $evidenceUrl($foto) }}" target="_blank" rel="noopener" title="Ver evidencia">
                                                    <img
                                                        src="{{ $evidenceUrl($foto) }}"
                                                        class="evidence-thumb"
                                                        alt="Evidencia de {{ $componenteNombreFila }}"
                                                        onerror="this.style.display='none'">
                                                </a>
                                            @endforeach
                                            @if($evidencias->count() > 3)
                                                <span class="evidence-more">+{{ $evidencias->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">Sin evidencia</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ $showUrl }}"
                                       class="inline-flex items-center gap-1 text-xs text-blue-700 font-bold hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
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
