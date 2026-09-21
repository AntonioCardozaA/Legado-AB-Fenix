@extends('layouts.app')

@section('title', '52-12-4')

@section('content')
@php
    $lineasPayload = $lineas->map(fn ($linea) => ['id' => $linea->id, 'nombre' => $linea->nombre])->values();
@endphp

<style>
    .lef-shell { max-width: 1680px; margin: 0 auto; padding: clamp(14px, 2vw, 22px); color: #0f172a; }
    .lef-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .lef-title { font-size: clamp(1.35rem, 2vw, 2rem); font-weight: 900; margin: 0; }
    .lef-subtitle { margin-top: 4px; color: #64748b; font-size: 0.92rem; }
    .lef-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; border-radius: 10px; padding: 0.68rem 1rem; background: #1e40af; color: #fff; font-weight: 800; border: 1px solid #1e3a8a; box-shadow: 0 12px 24px rgba(30, 64, 175, 0.18); transition: transform 0.2s ease, background 0.2s ease; }
    .lef-action:hover { background: #1d4ed8; transform: translateY(-1px); }
    .lef-action.secondary { background: #fff; color: #334155; border-color: #cbd5e1; box-shadow: none; }
    .lef-lines { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 16px; }
    .lef-line-tab { flex: 0 0 auto; border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 10px; min-height: 42px; padding: 0 16px; font-weight: 900; cursor: pointer; }
    .lef-line-tab.active { border-color: #f59e0b; background: #fff7ed; color: #9a3412; box-shadow: inset 0 -3px 0 #f59e0b; }
    .lef-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06); margin-bottom: 16px; }
    .lef-panel-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #eef2f7; flex-wrap: wrap; }
    .lef-panel-title { margin: 0; font-size: 0.98rem; font-weight: 900; }
    .lef-panel-body { padding: 16px; }
    .lef-filters, .lef-import-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
    .lef-import-grid { align-items: end; }
    .lef-field label { display: block; margin-bottom: 5px; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; font-weight: 900; }
    .lef-field :where(select, input, textarea) { width: 100%; min-height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.55rem 0.75rem; color: #0f172a; background: #fff; }
    .lef-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .lef-kpi { background: #fff; border: 1px solid #e2e8f0; border-top: 4px solid #f59e0b; border-radius: 12px; padding: 13px; box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05); min-width: 0; }
    .lef-kpi-label { color: #64748b; font-size: 0.72rem; text-transform: uppercase; font-weight: 900; }
    .lef-kpi-value { margin-top: 7px; font-size: 1.15rem; font-weight: 900; color: #0f172a; overflow-wrap: anywhere; }
    .lef-kpi-meta { margin-top: 3px; color: #64748b; font-size: 0.78rem; }
    .lef-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .lef-chart-wrap { overflow-x: auto; min-height: 390px; }
    .lef-chart-inner { height: 380px; min-width: 760px; }
    .lef-chart-inner.compact { min-width: 420px; height: 320px; }
    .lef-empty { display: grid; place-items: center; min-height: 260px; border: 1px dashed #cbd5e1; border-radius: 12px; color: #64748b; text-align: center; padding: 24px; background: #f8fafc; }
    .lef-empty i { font-size: 2rem; color: #f59e0b; margin-bottom: 10px; }
    .lef-alert { border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; font-weight: 700; }
    .lef-alert.success { background: #dcfce7; color: #166534; }
    .lef-alert.error { background: #fee2e2; color: #991b1b; }
    .lef-table-wrap { overflow-x: auto; }
    .lef-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
    .lef-table th, .lef-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
    .lef-table th { background: #f8fafc; color: #475569; text-transform: uppercase; font-size: 0.72rem; }
    .lef-delete { color: #b91c1c; font-weight: 900; border: 0; background: transparent; cursor: pointer; }
    @media (min-width: 1180px) { .lef-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="lef-shell" data-lef-dashboard>
    <div class="lef-header">
        <div>
            <h1 class="lef-title">52-12-4</h1>
            <p class="lef-subtitle">Indicadores LEF por maquina, partes de Lavadora y comparativo entre lineas.</p>
        </div>
        @if($canManage)
            <button class="lef-action" type="button" data-toggle-import><i class="fas fa-file-import"></i>Importar datos 52-12-4</button>
        @endif
    </div>

    @if(session('success')) <div class="lef-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="lef-alert error">{{ $errors->first() }}</div> @endif

    <div class="lef-lines" role="tablist" aria-label="Lineas 52-12-4">
        @forelse($lineas as $linea)
            <button type="button" class="lef-line-tab {{ (int) $selectedLineaId === (int) $linea->id ? 'active' : '' }}" data-line-tab data-linea-id="{{ $linea->id }}">{{ $linea->nombre }}</button>
        @empty
            <span class="text-sm text-gray-500">No hay lineas activas configuradas.</span>
        @endforelse
    </div>

    @if($canManage)
        <div class="lef-panel" id="lefImportPanel" hidden>
            <div class="lef-panel-header"><h2 class="lef-panel-title">Importacion Excel</h2></div>
            <form class="lef-panel-body" action="{{ route('lef52124.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="lef-import-grid">
                    <div class="lef-field">
                        <label for="import_linea_id">Linea</label>
                        <select id="import_linea_id" name="linea_id" required>
                            @foreach($lineas as $linea)
                                <option value="{{ $linea->id }}" @selected((int) $selectedLineaId === (int) $linea->id)>{{ $linea->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lef-field">
                        <label for="data_date">Fecha de datos</label>
                        <input id="data_date" type="date" name="data_date" value="{{ old('data_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="lef-field">
                        <label for="archivo">Archivo Excel</label>
                        <input id="archivo" type="file" name="archivo" accept=".xls,.xlsx" required>
                    </div>
                    <div class="lef-field">
                        <label for="observations">Observacion</label>
                        <input id="observations" type="text" name="observations" value="{{ old('observations') }}" maxlength="1000">
                    </div>
                    <button class="lef-action" type="submit"><i class="fas fa-upload"></i>Cargar</button>
                </div>
            </form>
        </div>
    @endif

    <div class="lef-panel">
        <div class="lef-panel-header">
            <h2 class="lef-panel-title">Filtros</h2>
            <button class="lef-action secondary" type="button" data-refresh><i class="fas fa-rotate"></i>Actualizar</button>
        </div>
        <div class="lef-panel-body">
            <div class="lef-filters">
                <div class="lef-field">
                    <label for="filter_linea">Linea</label>
                    <select id="filter_linea" data-filter-linea>
                        @foreach($lineas as $linea)
                            <option value="{{ $linea->id }}" @selected((int) $selectedLineaId === (int) $linea->id)>{{ $linea->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lef-field"><label for="filter_import">Fecha / periodo</label><select id="filter_import" data-filter-import></select></div>
                <div class="lef-field">
                    <label for="filter_analysis">Tipo de analisis</label>
                    <select id="filter_analysis" data-filter-analysis>
                        <option value="all">Mostrar todo</option>
                        <option value="machines">Maquinas</option>
                        <option value="parts">Partes de Lavadora</option>
                        <option value="washer">Lavadora</option>
                        <option value="comparison">Comparacion de Lavadoras</option>
                        <option value="washer_vs_machines">Lavadora vs todas las maquinas</option>
                    </select>
                </div>
                <div class="lef-field">
                    <label for="filter_period">Periodo</label>
                    <select id="filter_period" data-filter-period>
                        <option value="all">Mostrar los tres</option>
                        <option value="52">52 semanas</option>
                        <option value="12">12 semanas</option>
                        <option value="4">4 semanas</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="lef-kpis" data-kpis></div>
    <div class="lef-empty" data-empty hidden>
        <div>
            <i class="fas fa-database"></i>
            <div data-empty-title>No existen datos 52-12-4 para la linea seleccionada.</div>
            @if($canManage)
                <button class="lef-action" type="button" data-toggle-import-empty style="margin-top: 14px;"><i class="fas fa-file-import"></i>Importar informacion</button>
            @endif
        </div>
    </div>

    <div class="lef-grid two" data-charts>
        <section class="lef-panel" data-card="machines">
            <div class="lef-panel-header"><h2 class="lef-panel-title" data-machines-title>Pareto de LEF - Maquinas</h2></div>
            <div class="lef-panel-body"><div class="lef-chart-wrap"><div class="lef-chart-inner" data-chart-width="machines"><canvas id="machinesChart"></canvas></div></div></div>
        </section>
        <section class="lef-panel" data-card="parts">
            <div class="lef-panel-header"><h2 class="lef-panel-title">Pareto de LEF - Partes de Lavadora</h2></div>
            <div class="lef-panel-body"><div class="lef-chart-wrap"><div class="lef-chart-inner" data-chart-width="parts"><canvas id="partsChart"></canvas></div></div></div>
        </section>
        <section class="lef-panel" data-card="washer">
            <div class="lef-panel-header"><h2 class="lef-panel-title">LEF 52-12-4 - Lavadora</h2></div>
            <div class="lef-panel-body"><div class="lef-kpis" data-washer-kpis></div><div class="lef-chart-wrap"><div class="lef-chart-inner compact"><canvas id="washerChart"></canvas></div></div></div>
        </section>
        <section class="lef-panel" data-card="comparison">
            <div class="lef-panel-header"><h2 class="lef-panel-title">Comparativo 52-12-4 de Lavadoras</h2></div>
            <div class="lef-panel-body"><div class="lef-chart-wrap"><div class="lef-chart-inner" data-chart-width="comparison"><canvas id="comparisonChart"></canvas></div></div></div>
        </section>
    </div>

    @if($canManage)
        <section class="lef-panel">
            <div class="lef-panel-header"><h2 class="lef-panel-title">Historial de importaciones</h2></div>
            <div class="lef-table-wrap">
                <table class="lef-table">
                    <thead><tr><th>Linea</th><th>Archivo</th><th>Fecha informacion</th><th>Fecha importacion</th><th>Usuario</th><th>Maquinas</th><th>Partes</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse($imports as $import)
                            <tr>
                                <td>{{ $import->linea?->nombre }}</td>
                                <td>{{ $import->source_filename }}</td>
                                <td>{{ $import->data_date?->format('d/m/Y') }}</td>
                                <td>{{ $import->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $import->user?->name ?? 'N/A' }}</td>
                                <td>{{ $import->machines_count }}</td>
                                <td>{{ $import->parts_count }}</td>
                                <td>{{ ucfirst($import->status) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('lef52124.destroy', $import) }}" data-delete-import>
                                        @csrf @method('DELETE')
                                        <button class="lef-delete" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-gray-500">No hay importaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lineas = @json($lineasPayload);
    const endpoints = { data: @json(route('lef52124.data')), periods: @json(route('lef52124.periods')) };
    const state = { lineaId: Number(@json($selectedLineaId)), importId: null, period: 'all', analysis: 'all', loadingPeriods: false };
    const charts = {};
    const colors = { weeks52: '#f59e0b', weeks12: '#fdba74', weeks4: '#fbbf24', highlight: '#1e40af' };
    const valueLabelPlugin = {
        id: 'lefValueLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.fillStyle = '#334155';
            ctx.font = '700 10px sans-serif';
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (meta.hidden) return;
                meta.data.forEach((bar, index) => {
                    const value = Number(dataset.data[index]);
                    if (Number.isFinite(value)) ctx.fillText(formatNumber(value), bar.x, bar.y - 4);
                });
            });
            ctx.restore();
        }
    };
    const nodes = {
        linea: document.querySelector('[data-filter-linea]'),
        import: document.querySelector('[data-filter-import]'),
        analysis: document.querySelector('[data-filter-analysis]'),
        period: document.querySelector('[data-filter-period]'),
        kpis: document.querySelector('[data-kpis]'),
        washerKpis: document.querySelector('[data-washer-kpis]'),
        empty: document.querySelector('[data-empty]'),
        charts: document.querySelector('[data-charts]'),
        machinesTitle: document.querySelector('[data-machines-title]'),
    };

    document.querySelector('[data-toggle-import]')?.addEventListener('click', toggleImportPanel);
    document.querySelector('[data-toggle-import-empty]')?.addEventListener('click', toggleImportPanel);
    document.querySelector('[data-refresh]')?.addEventListener('click', loadData);
    document.querySelectorAll('[data-line-tab]').forEach((button) => button.addEventListener('click', () => {
        state.lineaId = Number(button.dataset.lineaId);
        nodes.linea.value = state.lineaId;
        selectLineTab();
        loadPeriods(true);
    }));
    nodes.linea?.addEventListener('change', () => { state.lineaId = Number(nodes.linea.value); selectLineTab(); loadPeriods(true); });
    nodes.import?.addEventListener('change', () => { state.importId = nodes.import.value ? Number(nodes.import.value) : null; loadData(); });
    nodes.analysis?.addEventListener('change', () => { state.analysis = nodes.analysis.value; loadData(); });
    nodes.period?.addEventListener('change', () => { state.period = nodes.period.value; loadData(); });
    document.querySelectorAll('[data-delete-import]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!confirm('Eliminar esta importacion tambien eliminara sus registros 52-12-4.')) event.preventDefault();
    }));

    loadPeriods(true);

    function toggleImportPanel() {
        const panel = document.getElementById('lefImportPanel');
        if (!panel) return;
        panel.hidden = !panel.hidden;
        if (!panel.hidden) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function selectLineTab() {
        document.querySelectorAll('[data-line-tab]').forEach((button) => button.classList.toggle('active', Number(button.dataset.lineaId) === Number(state.lineaId)));
    }
    function loadPeriods(resetImport) {
        if (!state.lineaId || state.loadingPeriods) return;
        state.loadingPeriods = true;
        fetch(`${endpoints.periods}?linea_id=${encodeURIComponent(state.lineaId)}`, { headers: { 'Accept': 'application/json' } })
            .then((response) => response.json())
            .then((data) => {
                const imports = data.items || [];
                nodes.import.innerHTML = imports.length ? imports.map((item) => `<option value="${item.id}">${escapeHtml(item.data_date_label)} - ${escapeHtml(item.source_filename)}</option>`).join('') : '<option value="">Sin datos importados</option>';
                state.importId = resetImport && imports.length ? Number(imports[0].id) : (nodes.import.value ? Number(nodes.import.value) : null);
                if (state.importId) nodes.import.value = String(state.importId);
            })
            .finally(() => { state.loadingPeriods = false; loadData(); });
    }
    function loadData() {
        if (!state.lineaId) return;
        const params = new URLSearchParams({ linea_id: state.lineaId, period: state.period, analysis_type: state.analysis });
        if (state.importId) params.set('import_id', state.importId);
        fetch(`${endpoints.data}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then((response) => response.json())
            .then(renderDashboard)
            .catch(() => renderEmpty('No se pudieron cargar los datos 52-12-4.'));
    }
    function renderDashboard(payload) {
        if (!payload.has_data) return renderEmpty(payload.message || 'No existen datos 52-12-4 para la linea seleccionada.');
        nodes.empty.hidden = true;
        nodes.charts.hidden = false;
        renderKpis(payload.summary || {});
        renderWasherKpis(payload.washer);
        renderVisibility();
        nodes.machinesTitle.textContent = state.analysis === 'washer_vs_machines' ? 'Lavadora vs todas las maquinas' : 'Pareto de LEF - Maquinas';
        renderGroupedBar('machinesChart', payload.machines || [], 'Maquina', state.analysis === 'washer_vs_machines');
        renderGroupedBar('partsChart', payload.parts || [], 'Parte', false);
        renderWasherChart(payload.washer);
        renderComparisonChart(payload.comparison || []);
    }
    function renderEmpty(message) {
        destroyAll();
        nodes.kpis.innerHTML = '';
        nodes.washerKpis.innerHTML = '';
        nodes.empty.hidden = false;
        nodes.charts.hidden = true;
        const title = nodes.empty.querySelector('[data-empty-title]');
        if (title) title.textContent = message;
    }
    function renderVisibility() {
        const visible = { machines: ['all', 'machines', 'washer_vs_machines'].includes(state.analysis), parts: ['all', 'parts'].includes(state.analysis), washer: ['all', 'washer'].includes(state.analysis), comparison: ['all', 'comparison'].includes(state.analysis) };
        Object.entries(visible).forEach(([key, show]) => { const card = document.querySelector(`[data-card="${key}"]`); if (card) card.hidden = !show; });
    }
    function renderKpis(summary) {
        const cards = [
            ['Linea seleccionada', summary.linea || currentLineName(), ''],
            ['Mayor afectacion 52 SEM', summary.top_52?.name || 'Sin dato', summary.top_52?.value || ''],
            ['Mayor afectacion 12 SEM', summary.top_12?.name || 'Sin dato', summary.top_12?.value || ''],
            ['Mayor afectacion 4 SEM', summary.top_4?.name || 'Sin dato', summary.top_4?.value || ''],
            ['LEF Lavadora 52 SEM', summary.washer_52 || 'Sin dato', ''],
            ['LEF Lavadora 12 SEM', summary.washer_12 || 'Sin dato', ''],
            ['LEF Lavadora 4 SEM', summary.washer_4 || 'Sin dato', ''],
            ['Ultima actualizacion', summary.updated_at || 'Sin dato', ''],
        ];
        nodes.kpis.innerHTML = cards.map(kpiMarkup).join('');
    }
    function renderWasherKpis(washer) {
        const cards = [['52 SEM', washer?.formatted_52 || 'Sin dato', washer?.name || 'Lavadora'], ['12 SEM', washer?.formatted_12 || 'Sin dato', washer?.name || 'Lavadora'], ['4 SEM', washer?.formatted_4 || 'Sin dato', washer?.name || 'Lavadora']];
        nodes.washerKpis.innerHTML = cards.map(kpiMarkup).join('');
    }
    function kpiMarkup([label, value, meta]) {
        return `<div class="lef-kpi"><div class="lef-kpi-label">${escapeHtml(label)}</div><div class="lef-kpi-value">${escapeHtml(value)}</div><div class="lef-kpi-meta">${escapeHtml(meta || '')}</div></div>`;
    }
    function renderGroupedBar(canvasId, rows, xLabel, highlightWasher) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        setChartWidth(canvasId === 'machinesChart' ? 'machines' : 'parts', rows.length);
        charts[canvasId]?.destroy();
        charts[canvasId] = new Chart(canvas, { type: 'bar', data: { labels: rows.map((row) => row.name), datasets: datasetsForRows(rows, highlightWasher) }, plugins: [valueLabelPlugin], options: baseChartOptions(xLabel, 'Valor LEF') });
    }
    function renderWasherChart(row) {
        const canvas = document.getElementById('washerChart');
        if (!canvas) return;
        charts.washerChart?.destroy();
        charts.washerChart = new Chart(canvas, { type: 'bar', data: { labels: ['52 SEM', '12 SEM', '4 SEM'], datasets: [{ label: row?.name || 'Lavadora', data: row ? [row.value_52_weeks, row.value_12_weeks, row.value_4_weeks] : [0, 0, 0], backgroundColor: [colors.weeks52, colors.weeks12, colors.weeks4], borderRadius: 8 }] }, plugins: [valueLabelPlugin], options: baseChartOptions('Periodo', 'Valor LEF', false) });
    }
    function renderComparisonChart(rows) {
        const canvas = document.getElementById('comparisonChart');
        if (!canvas) return;
        setChartWidth('comparison', rows.length);
        charts.comparisonChart?.destroy();
        charts.comparisonChart = new Chart(canvas, { type: 'bar', data: { labels: rows.map((row) => row.linea), datasets: [dataset('1 - 52 SEM', rows.map((row) => row.value_52_weeks), colors.weeks52), dataset('2 - 12 SEM', rows.map((row) => row.value_12_weeks), colors.weeks12), dataset('3 - 4 SEM', rows.map((row) => row.value_4_weeks), colors.weeks4)] }, plugins: [valueLabelPlugin], options: baseChartOptions('Linea', 'Valor LEF', false) });
    }
    function datasetsForRows(rows, highlightWasher) {
        return [
            dataset('1 - 52 SEM', rows.map((row) => row.value_52_weeks), rows.map((row) => highlightWasher && row.highlight ? colors.highlight : colors.weeks52)),
            dataset('2 - 12 SEM', rows.map((row) => row.value_12_weeks), rows.map((row) => highlightWasher && row.highlight ? '#2563eb' : colors.weeks12)),
            dataset('3 - 4 SEM', rows.map((row) => row.value_4_weeks), rows.map((row) => highlightWasher && row.highlight ? '#60a5fa' : colors.weeks4)),
        ];
    }
    function dataset(label, data, backgroundColor) { return { label, data, backgroundColor, borderRadius: 6, borderSkipped: false }; }
    function baseChartOptions(xTitle, yTitle, rotate = true) {
        return { responsive: true, maintainAspectRatio: false, animation: { duration: 450 }, plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${formatNumber(context.raw)}` } } }, scales: { x: { grid: { display: false }, title: { display: true, text: xTitle, font: { weight: 'bold' } }, ticks: { maxRotation: rotate ? 52 : 0, minRotation: rotate ? 35 : 0, color: '#475569', font: { size: 10 }, callback(value) { return truncate(this.getLabelForValue(value), 24); } } }, y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.24)' }, title: { display: true, text: yTitle, font: { weight: 'bold' } }, ticks: { callback: (value) => formatNumber(value), color: '#475569' } } } };
    }
    function setChartWidth(key, total) { const node = document.querySelector(`[data-chart-width="${key}"]`); if (node) node.style.minWidth = `${Math.max(760, total * 92)}px`; }
    function destroyAll() { Object.keys(charts).forEach((key) => { charts[key]?.destroy(); delete charts[key]; }); }
    function currentLineName() { return lineas.find((linea) => Number(linea.id) === Number(state.lineaId))?.nombre || 'Sin linea'; }
    function formatNumber(value) { if (value === null || value === undefined || Number.isNaN(Number(value))) return '0.00'; return Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function truncate(value, limit) { const text = String(value || ''); return text.length > limit ? `${text.slice(0, limit - 1)}...` : text; }
    function escapeHtml(value) { return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
});
</script>
@endsection
