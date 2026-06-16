@php
    $linea = $reporte['linea'];
    $resumen = $reporte['resumen'] ?? [];
    $analisis = collect($reporte['analisis'] ?? []);
    $componentes = collect($reporte['componentes'] ?? []);
    $modulos = collect($reporte['modulos'] ?? []);
    $analisisTendencia = collect($reporte['analisis_tendencia'] ?? []);
    $totalDanos52 = $analisisTendencia->sum('total_danos_52_semanas');
    $totalDanos12 = $analisisTendencia->sum('total_danos_12_semanas');
    $totalDanos4 = $analisisTendencia->sum('total_danos_4_semanas');
    $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : 'Sin fecha';
    $estadoClass = function ($estado) {
        $estado = (string) $estado;

        if (\App\Models\AnalisisPasteurizadora::esEstadoDanado($estado)) {
            return 'danger';
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estado)) {
            return 'warning';
        }

        if ($estado === \App\Models\AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION) {
            return 'review';
        }

        if ($estado === \App\Models\AnalisisPasteurizadora::ESTADO_CAMBIADO) {
            return 'changed';
        }

        return 'ok';
    };
@endphp

<style>
    .pasteur-report {
        --report-blue: #2563eb;
        --report-dark: #0f172a;
        --report-border: #e2e8f0;
        --report-muted: #64748b;
    }

    .pasteur-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .pasteur-stat {
        background: #ffffff;
        border: 1px solid var(--report-border);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
    }

    .pasteur-stat-label {
        color: var(--report-muted);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .pasteur-stat-value {
        color: var(--report-dark);
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 8px;
    }

    .pasteur-section {
        background: #ffffff;
        border: 1px solid var(--report-border);
        border-radius: 18px;
        box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
        margin-bottom: 28px;
        overflow: hidden;
    }

    .pasteur-section-head {
        align-items: center;
        background: linear-gradient(135deg, #111827, #1f2937);
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 22px;
    }

    .pasteur-section-title {
        font-size: 18px;
        font-weight: 800;
        margin: 0;
    }

    .pasteur-section-subtitle {
        color: #cbd5e1;
        font-size: 12px;
        font-weight: 600;
        margin-top: 3px;
    }

    .pasteur-section-body {
        padding: 22px;
    }

    .pasteur-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 14px;
    }

    .pasteur-module,
    .pasteur-component {
        background: #f8fafc;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        padding: 14px;
    }

    .pasteur-module-head,
    .pasteur-component-head {
        align-items: flex-start;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .pasteur-module-title,
    .pasteur-component-title {
        color: #111827;
        font-size: 15px;
        font-weight: 800;
    }

    .pasteur-meta {
        color: var(--report-muted);
        font-size: 12px;
        font-weight: 600;
    }

    .pasteur-progress {
        background: #e2e8f0;
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
    }

    .pasteur-progress > span {
        background: linear-gradient(90deg, #2563eb, #10b981);
        display: block;
        height: 100%;
    }

    .pasteur-badge {
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 800;
        padding: 5px 9px;
        white-space: nowrap;
    }

    .pasteur-badge.ok { background: #dcfce7; color: #166534; }
    .pasteur-badge.review { background: #fef3c7; color: #92400e; }
    .pasteur-badge.warning { background: #ffedd5; color: #9a3412; }
    .pasteur-badge.danger { background: #fee2e2; color: #991b1b; }
    .pasteur-badge.changed { background: #dbeafe; color: #1e40af; }
    .pasteur-badge.neutral { background: #e2e8f0; color: #334155; }

    .pasteur-table {
        border-collapse: collapse;
        width: 100%;
    }

    .pasteur-table th {
        background: #f1f5f9;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        padding: 12px;
        text-align: left;
        text-transform: uppercase;
    }

    .pasteur-table td {
        border-top: 1px solid var(--report-border);
        color: #334155;
        font-size: 13px;
        padding: 12px;
        vertical-align: top;
    }

    .pasteur-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 28px;
    }

    .pasteur-action {
        align-items: center;
        background: #eff6ff;
        border-radius: 10px;
        color: #1d4ed8;
        display: inline-flex;
        font-size: 13px;
        font-weight: 800;
        gap: 8px;
        padding: 10px 14px;
        text-decoration: none;
    }

    .pasteur-action:hover {
        background: #dbeafe;
    }

    .pasteur-component-img {
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--report-border);
        border-radius: 10px;
        display: flex;
        height: 42px;
        justify-content: center;
        width: 42px;
        flex-shrink: 0;
    }

    .pasteur-component-img img {
        height: 34px;
        object-fit: contain;
        width: 34px;
    }

    @media (max-width: 768px) {
        .pasteur-section-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="pasteur-report">
    <div class="pasteur-stats">
        <div class="pasteur-stat">
            <div class="pasteur-stat-label">Total analisis</div>
            <div class="pasteur-stat-value">{{ $resumen['total_analisis'] ?? 0 }}</div>
            <div class="pasteur-meta">Registros en el periodo</div>
        </div>

        <div class="pasteur-stat">
            <div class="pasteur-stat-label">Componentes</div>
            <div class="pasteur-stat-value">{{ $resumen['componentes_revisados'] ?? 0 }}/{{ $resumen['total_componentes'] ?? $componentes->count() }}</div>
            <div class="pasteur-meta">Tipos con revision registrada</div>
        </div>

        <div class="pasteur-stat">
            <div class="pasteur-stat-label">Modulos</div>
            <div class="pasteur-stat-value">{{ $resumen['modulos_con_analisis'] ?? 0 }}/{{ $resumen['total_modulos'] ?? 0 }}</div>
            <div class="pasteur-meta">{{ number_format($resumen['avance_historico_porcentaje'] ?? 0, 1) }}% de avance por modulo</div>
        </div>

        <div class="pasteur-stat">
            <div class="pasteur-stat-label">Criticos</div>
            <div class="pasteur-stat-value">{{ $resumen['componentes_criticos'] ?? 0 }}</div>
            <div class="pasteur-meta">Pendientes por cambio</div>
        </div>

        <div class="pasteur-stat">
            <div class="pasteur-stat-label">Analisis 52-12-4</div>
            <div class="pasteur-stat-value">{{ $analisisTendencia->count() }}</div>
            <div class="pasteur-meta">Danos 4 sem: {{ number_format($totalDanos4, 2) }}</div>
        </div>
    </div>

    <div class="pasteur-actions">
        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $linea->id]) }}" class="pasteur-action">
            <i class="fas fa-chart-pie"></i>
            Analisis mecanico
        </a>
        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.create', ['linea' => $linea->id]) }}" class="pasteur-action">
            <i class="fas fa-plus-circle"></i>
            Nuevo analisis
        </a>
        <a href="{{ route('historico-revisados.index', ['tipo' => 'pasteurizadora', 'linea_id' => $linea->id]) }}" class="pasteur-action">
            <i class="fas fa-history"></i>
            Historico revisados
        </a>
        <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.index', ['linea_id' => $linea->id]) }}" class="pasteur-action">
            <i class="fas fa-chart-line"></i>
            52-12-4
        </a>
    </div>

    <div class="pasteur-section">
        <div class="pasteur-section-head">
            <div>
                <h2 class="pasteur-section-title">Avance por modulos</h2>
                <div class="pasteur-section-subtitle">{{ $linea->nombre }} - componentes, niveles y lados</div>
            </div>
            <span class="pasteur-badge neutral">{{ $modulos->count() }} modulos</span>
        </div>
        <div class="pasteur-section-body">
            <div class="pasteur-grid">
                @forelse($modulos as $modulo)
                    <article class="pasteur-module">
                        <div class="pasteur-module-head">
                            <div>
                                <div class="pasteur-module-title">Modulo {{ $modulo['numero'] }}</div>
                                <div class="pasteur-meta">{{ $modulo['componentes_revisados'] }}/{{ $modulo['total_componentes'] }} componentes</div>
                            </div>
                            <span class="pasteur-badge {{ ($modulo['criticos'] ?? 0) > 0 ? 'danger' : 'ok' }}">
                                {{ ($modulo['criticos'] ?? 0) > 0 ? ($modulo['criticos'] . ' crit.') : 'OK' }}
                            </span>
                        </div>
                        <div class="pasteur-progress"><span style="width: {{ $modulo['porcentaje'] }}%"></span></div>
                        <div class="pasteur-meta mt-2">
                            {{ number_format($modulo['porcentaje'], 1) }}% - {{ $modulo['total_analisis'] }} registros - Ult: {{ $formatDate($modulo['ultima_revision']) }}
                        </div>
                        <div class="pasteur-meta mt-2">
                            Sup: {{ $modulo['niveles']['SUPERIOR'] ?? 0 }} - Inf: {{ $modulo['niveles']['INFERIOR'] ?? 0 }} - Vapor: {{ $modulo['lados']['VAPOR'] ?? 0 }} - Pasillo: {{ $modulo['lados']['PASILLO'] ?? 0 }}
                        </div>
                    </article>
                @empty
                    <div class="pasteur-meta">No hay modulos configurados para esta pasteurizadora.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="pasteur-section">
        <div class="pasteur-section-head">
            <div>
                <h2 class="pasteur-section-title">Componentes de la maquina</h2>
                <div class="pasteur-section-subtitle">Configuracion propia de {{ $linea->nombre }}</div>
            </div>
            <span class="pasteur-badge neutral">{{ $componentes->count() }} componentes</span>
        </div>
        <div class="pasteur-section-body">
            <div class="pasteur-grid">
                @forelse($componentes as $componente)
                    @php $badge = $estadoClass($componente['ultimo_estado'] ?? ''); @endphp
                    <article class="pasteur-component">
                        <div class="pasteur-component-head">
                            <div class="flex items-start gap-3">
                                <div class="pasteur-component-img">
                                    <img
                                        src="{{ asset('images/componentes-pasteurizadora/' . $componente['codigo'] . '.png') }}"
                                        alt="{{ $componente['nombre'] }}"
                                        onerror="this.src='{{ asset('images/icono_pas.png') }}'">
                                </div>
                                <div>
                                    <div class="pasteur-component-title">{{ $componente['nombre'] }}</div>
                                    <div class="pasteur-meta">{{ $componente['codigo'] }}</div>
                                </div>
                            </div>
                            <span class="pasteur-badge {{ $badge }}">{{ $componente['ultimo_estado'] ?? 'Sin datos' }}</span>
                        </div>
                        <div class="pasteur-meta">
                            Analisis: {{ $componente['total_analisis'] }} - Revisadas: {{ $componente['cantidad_revisada'] }}/{{ $componente['total_configurado'] }}
                        </div>
                        <div class="pasteur-progress mt-2"><span style="width: {{ $componente['porcentaje'] }}%"></span></div>
                        <div class="pasteur-meta mt-2">
                            {{ number_format($componente['porcentaje'], 1) }}% - Cantidad base {{ $componente['cantidad'] }} - Modulos {{ $componente['modulos_aplicables'] }}
                        </div>
                    </article>
                @empty
                    <div class="pasteur-meta">No hay componentes configurados para esta pasteurizadora.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="pasteur-section">
        <div class="pasteur-section-head">
            <div>
                <h2 class="pasteur-section-title">Ultimos analisis registrados</h2>
                <div class="pasteur-section-subtitle">Detalle mecanico dentro del periodo seleccionado</div>
            </div>
            <span class="pasteur-badge neutral">{{ $analisis->count() }} registros</span>
        </div>
        <div class="pasteur-section-body overflow-x-auto">
            <table class="pasteur-table">
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
                        @php $badge = $estadoClass($item->estado); @endphp
                        <tr>
                            <td>{{ $formatDate($item->fecha_analisis) }}</td>
                            <td>Modulo {{ $item->modulo }}</td>
                            <td>{{ $item->nivel ?? '-' }} / {{ $item->lado ?? '-' }}</td>
                            <td>{{ $item->componente_nombre }}</td>
                            <td><span class="pasteur-badge {{ $badge }}">{{ $item->estado }}</span></td>
                            <td>{{ $item->numero_orden ?: '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->actividad, 80) }}</td>
                            <td>
                                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.show', ['analisispasteurizadora' => $item->id]) }}" class="text-blue-700 font-bold">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No hay analisis registrados en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($analisisTendencia->isNotEmpty())
        <div class="pasteur-section">
            <div class="pasteur-section-head">
                <div>
                    <h2 class="pasteur-section-title">Analisis 52-12-4</h2>
                    <div class="pasteur-section-subtitle">Tendencia mensual de danos</div>
                </div>
                <span class="pasteur-badge neutral">{{ $analisisTendencia->count() }} periodos</span>
            </div>
            <div class="pasteur-section-body overflow-x-auto">
                <div class="pasteur-stats">
                    <div class="pasteur-stat">
                        <div class="pasteur-stat-label">52 semanas</div>
                        <div class="pasteur-stat-value">{{ number_format($totalDanos52, 2) }}</div>
                    </div>
                    <div class="pasteur-stat">
                        <div class="pasteur-stat-label">12 semanas</div>
                        <div class="pasteur-stat-value">{{ number_format($totalDanos12, 2) }}</div>
                    </div>
                    <div class="pasteur-stat">
                        <div class="pasteur-stat-label">4 semanas</div>
                        <div class="pasteur-stat-value">{{ number_format($totalDanos4, 2) }}</div>
                    </div>
                </div>

                <table class="pasteur-table">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>52 semanas</th>
                            <th>12 semanas</th>
                            <th>4 semanas</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analisisTendencia->take(6) as $item)
                            <tr>
                                <td>{{ $item->periodo }}</td>
                                <td>{{ number_format($item->total_danos_52_semanas, 2) }}</td>
                                <td>{{ number_format($item->total_danos_12_semanas, 2) }}</td>
                                <td>{{ number_format($item->total_danos_4_semanas, 2) }}</td>
                                <td>{{ $item->observaciones ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
