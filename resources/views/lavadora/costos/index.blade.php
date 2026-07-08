@extends('layouts.app')

@section('title', 'Costos | Lavadora')

@section('content')
@php
    $summary = $dashboard['summary'];
    $filters = $dashboard['filters'];
    $selectedLinea = $dashboard['selected_linea'];
@endphp

<style>
    .cost-shell {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        font-family: inherit;
    }

    .cost-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        background: #fff;
        color: #1f2937;
        padding: 1.75rem;
        border: 1px solid rgba(229, 231, 235, 0.95);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    }

    .cost-hero::after {
        content: '';
        position: absolute;
        inset: auto -3rem -3rem auto;
        width: 11rem;
        height: 11rem;
        background: rgba(148, 163, 184, 0.1);
        border-radius: 999px;
    }

    .cost-hero-copy {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
        align-items: flex-start;
    }

    .cost-hero h1 {
        margin: 0 0 0.6rem;
        font-size: clamp(1.8rem, 2.5vw, 2.4rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        font-family: inherit;
        color: #1f2937;
    }

    .cost-hero p {
        margin: 0;
        max-width: 50rem;
        color: #64748b;
        line-height: 1.65;
        font-family: inherit;
    }

    .hero-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.9rem;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 700;
        border: 1px solid rgba(203, 213, 225, 0.9);
        font-family: inherit;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .cost-panel {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 1.25rem;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
    }

    .panel-pad {
        padding: 1.25rem;
    }

    .panel-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .panel-title h2,
    .panel-title h3 {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        font-family: inherit;
    }

    .panel-subtle {
        color: #64748b;
        font-size: 0.9rem;
        font-family: inherit;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .field-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-family: inherit;
    }

    .field-control {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 0.85rem;
        background: #fff;
        padding: 0.8rem 0.95rem;
        color: #0f172a;
        font-size: 0.95rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        font-family: inherit;
    }

    .field-control:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: end;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1rem;
    }

    .stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.1rem;
        padding: 1rem;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border: 1px solid rgba(148, 163, 184, 0.18);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 0.3rem;
        background: linear-gradient(90deg, #1d4ed8, #14b8a6);
    }

    .stat-label {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-family: inherit;
    }

    .stat-value {
        margin-top: 0.65rem;
        color: #0f172a;
        font-size: 1.55rem;
        font-weight: 900;
        line-height: 1.05;
        font-family: inherit;
    }

    .stat-meta {
        margin-top: 0.45rem;
        color: #475569;
        font-size: 0.88rem;
        line-height: 1.45;
        font-family: inherit;
    }

    .content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr);
        gap: 1rem;
    }

    .chart-wrap {
        height: 300px;
    }

    .chart-wrap.compact {
        height: 260px;
    }

    .budget-list {
        display: grid;
        gap: 0.9rem;
    }

    .budget-row {
        padding: 0.95rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: #f8fafc;
    }

    .budget-row-top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        margin-bottom: 0.55rem;
    }

    .budget-name {
        color: #0f172a;
        font-weight: 800;
    }

    .budget-meta {
        color: #64748b;
        font-size: 0.85rem;
    }

    .budget-track {
        width: 100%;
        height: 0.7rem;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.16);
        overflow: hidden;
    }

    .budget-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0ea5e9, #14b8a6);
    }

    .comparison-list,
    .replacement-list {
        display: grid;
        gap: 0.85rem;
    }

    .comparison-pill,
    .replacement-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .comparison-pill.up {
        border-color: rgba(239, 68, 68, 0.2);
        background: rgba(254, 242, 242, 0.9);
    }

    .comparison-pill.down {
        border-color: rgba(16, 185, 129, 0.2);
        background: rgba(240, 253, 244, 0.92);
    }

    .pill-value {
        font-weight: 900;
        color: #0f172a;
    }

    .table-shell {
        overflow-x: auto;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .table-shell table {
        width: 100%;
        border-collapse: collapse;
        min-width: 720px;
    }

    .table-shell th,
    .table-shell td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        text-align: left;
        font-size: 0.92rem;
    }

    .table-shell th {
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .amount {
        font-variant-numeric: tabular-nums;
        font-weight: 800;
        color: #0f172a;
    }

    @media (max-width: 1200px) {
        .filter-grid,
        .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .cost-hero,
        .panel-pad {
            padding: 1rem;
        }

        .filter-grid,
        .stat-grid {
            grid-template-columns: 1fr;
        }

        .hero-actions,
        .filter-actions {
            width: 100%;
        }

        .hero-actions a,
        .filter-actions button,
        .filter-actions a {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="cost-shell">
    <section class="cost-hero">
        <div class="cost-hero-copy">
            <div>
                <h1>Costos de Lavadoras</h1>
                {{--
                    El módulo consolida los gastos generados automáticamente por cambios de estado y actividades de mantenimiento.
                    Todo el tablero se actualiza con base en los análisis registrados, sin capturas manuales de gasto.
                --}}

                <div class="hero-badges">
                    <span class="hero-badge"><i class="fas fa-calendar-range"></i> {{ $filters['range_label'] }}</span>
                    <span class="hero-badge"><i class="fas fa-chart-line"></i> {{ count($dashboard['by_component']) }} componentes con costo</span>
                    <span class="hero-badge"><i class="fas fa-industry"></i> {{ count($dashboard['budgets']) }} lavadoras monitoreadas</span>
                </div>
            </div>

            <div class="hero-actions">
                <a href="{{ route('lavadora.dashboard') }}" class="create-action create-action--secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver al menú
                </a>
                @if(auth()->user()?->hasRole('admin'))
                    <a href="{{ route('admin.costos.index') }}" class="create-action create-action--success">
                        <i class="fas fa-sliders"></i>
                        Control de Gastos
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="cost-panel panel-pad">
        <div class="panel-title">
            <h2><i class="fas fa-filter text-blue-600 mr-2"></i>Filtros</h2>
            <span class="panel-subtle">Rangos mensual, trimestral, semestral, anual o personalizados.</span>
        </div>

        <form method="GET" class="filter-grid">
            <div>
                <label class="field-label" for="preset">Periodo</label>
                <select id="preset" name="preset" class="field-control">
                    @foreach($presets as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['preset'] ?? 'anual') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="linea_id">Lavadora</label>
                <select id="linea_id" name="linea_id" class="field-control">
                    <option value="">Todas</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" @selected(($filters['linea_id'] ?? null) == $linea->id)>{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="budget_year">Año de presupuesto</label>
                <select id="budget_year" name="budget_year" class="field-control">
                    @foreach($budgetYears as $year)
                        <option value="{{ $year }}" @selected(($filters['budget_year'] ?? now()->year) == $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="from">Desde</label>
                <input id="from" name="from" type="date" class="field-control" value="{{ $filters['from'] }}">
            </div>

            <div>
                <label class="field-label" for="to">Hasta</label>
                <input id="to" name="to" type="date" class="field-control" value="{{ $filters['to'] }}">
            </div>

            <div class="filter-actions">
                <button type="submit" class="create-action">
                    <i class="fas fa-chart-column"></i>
                    Actualizar tablero
                </button>
                <a href="{{ route('lavadora.costos.index') }}" class="create-action create-action--secondary">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="stat-grid">
        <article class="stat-card">
            <div class="stat-label">Gasto del periodo</div>
            <div class="stat-value">${{ number_format((float) $summary['range_total'], 2) }}</div>
            <div class="stat-meta">Rango activo: {{ $filters['range_label'] }}</div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Gasto del mes</div>
            <div class="stat-value">${{ number_format((float) $summary['month_total'], 2) }}</div>
            <div class="stat-meta">Comparativa automática contra el mes anterior disponible abajo.</div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Gasto del año</div>
            <div class="stat-value">${{ number_format((float) $summary['year_total'], 2) }}</div>
            <div class="stat-meta">Incluye todos los gastos del año calendario actual.</div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Mayor costo acumulado</div>
            <div class="stat-value">{{ $summary['top_component']['label'] ?? 'Sin datos' }}</div>
            <div class="stat-meta">
                {{ isset($summary['top_component']['total']) ? '$' . number_format((float) $summary['top_component']['total'], 2) : 'Aún sin acumulado.' }}
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Lavadora con mayor gasto</div>
            <div class="stat-value">{{ $summary['top_lavadora']['label'] ?? 'Sin datos' }}</div>
            <div class="stat-meta">
                {{ isset($summary['top_lavadora']['total']) ? '$' . number_format((float) $summary['top_lavadora']['total'], 2) : 'Aún sin registros.' }}
            </div>
        </article>
    </section>

    <section class="content-grid">
        <article class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-coins text-amber-500 mr-2"></i>Costos por componente</h3>
                <span class="panel-subtle">Dónde se está concentrando la inversión.</span>
            </div>
            <div class="chart-wrap">
                <canvas id="costByComponentChart"></canvas>
            </div>
        </article>

        <article class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-industry text-cyan-600 mr-2"></i>Costos por lavadora</h3>
                <span class="panel-subtle">Distribución del gasto entre líneas.</span>
            </div>
            <div class="chart-wrap">
                <canvas id="costByLavadoraChart"></canvas>
            </div>
        </article>
    </section>

    <section class="content-grid">
        <article class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-wave-square text-indigo-600 mr-2"></i>Evolución del gasto</h3>
                <span class="panel-subtle">Tendencia histórica del rango visible.</span>
            </div>
            <div class="chart-wrap compact">
                <canvas id="costTrendChart"></canvas>
            </div>
        </article>

        <article class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-wallet text-emerald-600 mr-2"></i>Presupuesto anual</h3>
                <span class="panel-subtle">Seguimiento por lavadora para {{ $filters['budget_year'] }}.</span>
            </div>

            <div class="budget-list">
                @foreach($dashboard['budgets'] as $budget)
                    <div class="budget-row">
                        <div class="budget-row-top">
                            <div>
                                <div class="budget-name">{{ $budget['linea'] }}</div>
                                <div class="budget-meta">
                                    Asignado: ${{ number_format((float) $budget['assigned'], 2) }}
                                    · Gastado: ${{ number_format((float) $budget['spent'], 2) }}
                                    · Disponible: ${{ number_format((float) $budget['remaining'], 2) }}
                                </div>
                            </div>
                            <div class="budget-name">
                                {{ is_null($budget['usage_percent']) ? 'Sin presupuesto' : number_format((float) $budget['usage_percent'], 1) . '%' }}
                            </div>
                        </div>
                        <div class="budget-track">
                            <div class="budget-bar" style="width: {{ min((float) ($budget['usage_percent'] ?? 0), 100) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="content-grid">
        <article class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-repeat text-sky-600 mr-2"></i>Estadísticas generales</h3>
                <span class="panel-subtle">Comparativas y componentes más reemplazados.</span>
            </div>

            <div class="comparison-list">
                @foreach([
                    ['title' => 'Comparativa mensual', 'payload' => $summary['month_comparison']],
                    ['title' => 'Comparativa anual', 'payload' => $summary['year_comparison']],
                ] as $comparison)
                    @php
                        $comparisonClass = $comparison['payload']['trend'];
                    @endphp
                    <div class="comparison-pill {{ $comparisonClass }}">
                        <div>
                            <div class="budget-name">{{ $comparison['title'] }}</div>
                            <div class="budget-meta">
                                Actual: ${{ number_format((float) $comparison['payload']['current'], 2) }}
                                · Anterior: ${{ number_format((float) $comparison['payload']['previous'], 2) }}
                            </div>
                        </div>
                        <div class="pill-value">
                            {{ $comparison['payload']['delta'] >= 0 ? '+' : '-' }}${{ number_format(abs((float) $comparison['payload']['delta']), 2) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="panel-title" style="margin-top: 1.2rem;">
                <h3><i class="fas fa-screwdriver-wrench text-orange-500 mr-2"></i>Componentes más reemplazados</h3>
            </div>
            <div class="replacement-list">
                @forelse($dashboard['top_replacements']->take(6) as $replacement)
                    <div class="replacement-item">
                        <div>
                            <div class="budget-name">{{ $replacement['label'] }}</div>
                            <div class="budget-meta">Reemplazos detectados automáticamente por estado `Cambiado`.</div>
                        </div>
                        <div class="pill-value">{{ $replacement['total'] }}</div>
                    </div>
                @empty
                    <div class="replacement-item">
                        <div>
                            <div class="budget-name">Sin reemplazos detectados</div>
                            <div class="budget-meta">Aún no existen registros con estado `Cambiado` en el rango seleccionado.</div>
                        </div>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-clock-rotate-left text-violet-600 mr-2"></i>Historial reciente de gastos</h3>
                <span class="panel-subtle">Últimos movimientos calculados por el sistema.</span>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Lavadora</th>
                            <th>Componente</th>
                            <th>Concepto</th>
                            <th>Origen</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dashboard['history'] as $entry)
                            <tr>
                                <td>{{ $entry['fecha'] }}</td>
                                <td>{{ $entry['lavadora'] }}</td>
                                <td>{{ $entry['componente'] }}</td>
                                <td>{{ $entry['concepto'] }}</td>
                                <td>{{ $entry['tipo'] }}</td>
                                <td class="amount">${{ number_format((float) $entry['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No hay gastos calculados todavía para el rango seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    @if($selectedLinea)
        <section class="cost-panel panel-pad">
            <div class="panel-title">
                <h3><i class="fas fa-magnifying-glass-chart text-blue-700 mr-2"></i>Detalle por lavadora: {{ $selectedLinea['linea'] }}</h3>
                <span class="panel-subtle">
                    Total gastado: ${{ number_format((float) $selectedLinea['total'], 2) }}
                </span>
            </div>

            <div class="stat-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 1rem;">
                <article class="stat-card">
                    <div class="stat-label">Total gastado</div>
                    <div class="stat-value">${{ number_format((float) $selectedLinea['total'], 2) }}</div>
                    <div class="stat-meta">Acumulado visible para {{ $selectedLinea['linea'] }}.</div>
                </article>
                <article class="stat-card">
                    <div class="stat-label">Componente top</div>
                    <div class="stat-value">{{ $selectedLinea['top_components'][0]['label'] ?? 'Sin datos' }}</div>
                    <div class="stat-meta">
                        {{ isset($selectedLinea['top_components'][0]['total']) ? '$' . number_format((float) $selectedLinea['top_components'][0]['total'], 2) : 'Aún sin gasto asociado.' }}
                    </div>
                </article>
                <article class="stat-card">
                    <div class="stat-label">Más reemplazado</div>
                    <div class="stat-value">{{ $selectedLinea['top_replacements'][0]['label'] ?? 'Sin datos' }}</div>
                    <div class="stat-meta">
                        {{ isset($selectedLinea['top_replacements'][0]['total']) ? $selectedLinea['top_replacements'][0]['total'] . ' eventos' : 'Aún sin reemplazos.' }}
                    </div>
                </article>
            </div>

            <div class="content-grid">
                <div class="cost-panel panel-pad" style="box-shadow:none;">
                    <div class="panel-title">
                        <h3>Evolución del gasto</h3>
                    </div>
                    <div class="chart-wrap compact">
                        <canvas id="selectedLineaTrendChart"></canvas>
                    </div>
                </div>

                <div class="cost-panel panel-pad" style="box-shadow:none;">
                    <div class="panel-title">
                        <h3>Historial de la lavadora</h3>
                    </div>
                    <div class="table-shell">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Componente</th>
                                    <th>Concepto</th>
                                    <th>Origen</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedLinea['history'] as $entry)
                                    <tr>
                                        <td>{{ $entry['fecha'] }}</td>
                                        <td>{{ $entry['componente'] }}</td>
                                        <td>{{ $entry['concepto'] }}</td>
                                        <td>{{ $entry['tipo'] }}</td>
                                        <td class="amount">${{ number_format((float) $entry['total'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">Sin historial reciente.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@section('scripts')
<script>
    const costDashboard = @json($dashboard);

    function money(value) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
        }).format(Number(value || 0));
    }

    function ensureChart(canvasId, type, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) {
            return null;
        }

        const hasData = Array.isArray(config?.data?.datasets)
            && config.data.datasets.some((dataset) => Array.isArray(dataset.data) && dataset.data.some((value) => Number(value || 0) > 0));

        if (!hasData) {
            const wrap = canvas.parentElement;
            if (wrap) {
                wrap.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-slate-500">Sin datos para graficar en este filtro.</div>';
            }
            return null;
        }

        return new Chart(canvas.getContext('2d'), {
            type,
            ...config,
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        ensureChart('costByComponentChart', 'bar', {
            data: {
                labels: (costDashboard.by_component || []).map((item) => item.label),
                datasets: [{
                    label: 'Costo acumulado',
                    data: (costDashboard.by_component || []).map((item) => Number(item.total || 0)),
                    backgroundColor: 'rgba(37, 99, 235, 0.88)',
                    borderColor: '#1d4ed8',
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => money(context.raw),
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#475569' },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#64748b',
                            callback: (value) => money(value),
                        },
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                    },
                },
            },
        });

        ensureChart('costByLavadoraChart', 'doughnut', {
            data: {
                labels: (costDashboard.by_lavadora || []).map((item) => item.label),
                datasets: [{
                    data: (costDashboard.by_lavadora || []).map((item) => Number(item.total || 0)),
                    backgroundColor: [
                        '#0ea5e9',
                        '#14b8a6',
                        '#f59e0b',
                        '#f97316',
                        '#8b5cf6',
                        '#ef4444',
                        '#06b6d4',
                        '#22c55e',
                    ],
                    borderWidth: 0,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 16, color: '#334155' },
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${money(context.raw)}`,
                        },
                    },
                },
            },
        });

        ensureChart('costTrendChart', 'line', {
            data: {
                labels: costDashboard.trend?.labels || [],
                datasets: [{
                    label: 'Gasto',
                    data: costDashboard.trend?.values || [],
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(20, 184, 166, 0.14)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => money(context.raw),
                        },
                    },
                },
                scales: {
                    x: { ticks: { color: '#475569' }, grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#64748b', callback: (value) => money(value) },
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                    },
                },
            },
        });

        if (costDashboard.selected_linea) {
            ensureChart('selectedLineaTrendChart', 'line', {
                data: {
                    labels: costDashboard.selected_linea.trend?.labels || [],
                    datasets: [{
                        label: costDashboard.selected_linea.linea || 'Lavadora',
                        data: costDashboard.selected_linea.trend?.values || [],
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        borderWidth: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => money(context.raw),
                            },
                        },
                    },
                    scales: {
                        x: { ticks: { color: '#475569' }, grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#64748b', callback: (value) => money(value) },
                            grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        },
                    },
                },
            });
        }
    });
</script>
@endsection
