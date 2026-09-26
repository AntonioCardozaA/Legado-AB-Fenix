@extends('layouts.app')

@section('title', '52-12-4')

@section('content')
@php
    $lineasPayload = $lineas->map(fn ($linea) => ['id' => $linea->id, 'nombre' => $linea->nombre])->values();
@endphp

<style>
    .lef-shell {
        --lef-navy: rgb(31, 35, 72);
        --lef-blue: #1e40af;
        --lef-blue-soft: #eff6ff;
        --lef-yellow: #f59e0b;
        --lef-border: #e2e8f0;
        --lef-muted: #64748b;
        --lef-text: #0f172a;
        max-width: 1680px;
        margin: 0 auto;
        color: var(--lef-text);
    }
    .lef-shell * { min-width: 0; }
    .lef-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
        margin-bottom: 18px;
        padding: clamp(18px, 3vw, 28px);
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
        color: var(--lef-text);
        overflow: hidden;
        position: relative;
    }
    .lef-header::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, var(--lef-navy), var(--lef-blue), #94a3b8);
    }
    .lef-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
        position: relative;
        z-index: 1;
    }
    .lef-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: clamp(46px, 9vw, 58px);
        height: clamp(46px, 9vw, 58px);
        flex: 0 0 auto;
        border-radius: 14px;
        background: var(--lef-navy);
        color: #ffffff;
        border: 1px solid rgba(31, 35, 72, 0.12);
        box-shadow: 0 10px 18px rgba(31, 35, 72, 0.16);
    }
    .lef-kicker {
        display: block;
        margin-bottom: 4px;
        color: var(--lef-blue);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .lef-title { font-size: clamp(1.55rem, 5vw, 2.35rem); font-weight: 900; line-height: 1; margin: 0; overflow-wrap: anywhere; }
    .lef-subtitle { margin-top: 7px; color: #475569; font-size: clamp(0.86rem, 2.5vw, 0.95rem); line-height: 1.45; max-width: 68ch; }
    .lef-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        max-width: 100%;
        border-radius: 12px;
        padding: 0.72rem 1.1rem;
        background: linear-gradient(135deg, var(--lef-blue), #1d4ed8);
        color: #fff !important;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
        border: 1px solid rgba(30, 64, 175, 0.9);
        box-shadow: 0 12px 24px rgba(30, 64, 175, 0.2);
        transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        touch-action: manipulation;
        position: relative;
        z-index: 1;
    }
    .lef-action:hover { background: linear-gradient(135deg, #1d4ed8, #1e3a8a); box-shadow: 0 16px 28px rgba(30, 64, 175, 0.26); transform: translateY(-1px); }
    .lef-action.secondary { background: #f8fafc; color: #334155 !important; border-color: #cbd5e1; box-shadow: none; }
    .lef-action.on-dark { background: var(--lef-navy); color: #fff !important; border-color: var(--lef-navy); box-shadow: 0 12px 24px rgba(31, 35, 72, 0.18); }
    .lef-action.on-dark:hover { background: #111827; border-color: #111827; box-shadow: 0 16px 28px rgba(31, 35, 72, 0.24); }
    .lef-lines {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 2px 10px;
        margin-bottom: 16px;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }
    .lef-line-tab {
        flex: 0 0 auto;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        border-radius: 12px;
        min-height: 42px;
        padding: 0 16px;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: all 0.2s ease;
    }
    .lef-line-tab:hover { border-color: #93c5fd; background: #eff6ff; color: var(--lef-blue); }
    .lef-line-tab.active { border-color: var(--lef-navy); background: var(--lef-navy); color: #fff; box-shadow: 0 10px 18px rgba(31, 35, 72, 0.16); }
    .lef-panel { background: #fff; border: 1px solid var(--lef-border); border-radius: 18px; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07); margin-bottom: 16px; overflow: hidden; }
    .lef-panel-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid #eef2f7; flex-wrap: wrap; background: linear-gradient(180deg, #fff, #f8fafc); }
    .lef-panel-title { margin: 0; font-size: 0.98rem; font-weight: 900; color: var(--lef-navy); overflow-wrap: anywhere; }
    .lef-panel-body { padding: clamp(14px, 2vw, 18px); }
    .lef-filters, .lef-import-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr)); gap: 12px; align-items: end; }
    .lef-field label { display: block; margin-bottom: 6px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--lef-muted); font-weight: 900; }
    .lef-field :where(select, input, textarea) {
        width: 100%;
        min-height: 44px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 0.58rem 0.78rem;
        color: var(--lef-text);
        background: #fff;
        font-weight: 700;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
    }
    .lef-field :where(select, input, textarea):focus { outline: 3px solid rgba(30, 64, 175, 0.18); border-color: #60a5fa; }
    .lef-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr)); gap: 14px; margin-bottom: 16px; }
    .lef-kpi {
        display: flex;
        min-width: 0;
        min-height: 118px;
        flex-direction: column;
        justify-content: flex-start;
        background: #fff;
        border: 1px solid var(--lef-border);
        border-top: 4px solid var(--lef-yellow);
        border-radius: 16px;
        padding: clamp(13px, 2.2vw, 16px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }
    .lef-kpi-label {
        color: #566987;
        font-size: clamp(0.68rem, 1.8vw, 0.76rem);
        line-height: 1.35;
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: 0.035em;
        overflow-wrap: anywhere;
    }
    .lef-kpi-value {
        margin-top: 10px;
        max-width: 100%;
        color: var(--lef-navy);
        font-size: clamp(1.05rem, 3.8vw, 1.28rem);
        line-height: 1.16;
        font-weight: 900;
        overflow-wrap: break-word;
        word-break: normal;
        hyphens: auto;
    }
    .lef-kpi-value.is-long { font-size: clamp(1rem, 2.5vw, 1.16rem); line-height: 1.18; text-wrap: balance; }
    .lef-kpi-meta { margin-top: auto; padding-top: 8px; color: #475569; font-size: clamp(0.76rem, 1.8vw, 0.82rem); line-height: 1.35; overflow-wrap: anywhere; }
    .lef-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .lef-chart-wrap { width: 100%; max-width: 100%; overflow-x: auto; min-height: clamp(360px, 52vh, 460px); -webkit-overflow-scrolling: touch; }
    .lef-chart-inner { position: relative; width: 100%; height: clamp(360px, 52vh, 440px); min-width: min(760px, 100%); }
    .lef-chart-inner.compact { min-width: min(420px, 100%); height: clamp(280px, 42vh, 320px); }
    .lef-alert { border-radius: 14px; padding: 12px 14px; margin-bottom: 14px; font-weight: 800; border: 1px solid transparent; }
    .lef-alert.success { background: #dcfce7; color: #166534; }
    .lef-alert.error { background: #fee2e2; color: #991b1b; }
    .lef-alert.info { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .lef-table-wrap { overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch; }
    .lef-table { width: 100%; min-width: 1110px; border-collapse: collapse; table-layout: fixed; font-size: 0.86rem; }
    .lef-table th, .lef-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; overflow-wrap: anywhere; }
    .lef-table th { background: #f8fafc; color: #475569; text-transform: uppercase; font-size: 0.72rem; line-height: 1.25; letter-spacing: 0.04em; overflow-wrap: normal; word-break: normal; }
    .lef-table th:nth-child(1), .lef-table td:nth-child(1) { width: 58px; }
    .lef-table th:nth-child(2), .lef-table td:nth-child(2) { width: 230px; }
    .lef-table th:nth-child(3), .lef-table td:nth-child(3) { width: 145px; }
    .lef-table th:nth-child(4), .lef-table td:nth-child(4) { width: 170px; }
    .lef-table th:nth-child(5), .lef-table td:nth-child(5) { width: 155px; }
    .lef-table th:nth-child(6), .lef-table td:nth-child(6),
    .lef-table th:nth-child(7), .lef-table td:nth-child(7),
    .lef-table th:nth-child(8), .lef-table td:nth-child(8) { width: 88px; }
    .lef-table th:nth-child(9), .lef-table td:nth-child(9) { width: 92px; text-align: right; }
    #historial-importaciones { margin-bottom: 112px; }
    .lef-import-pagination {
        border-top: 1px solid #e2e8f0;
        padding: 14px 240px 14px 18px;
    }
    .lef-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        border: 0;
        border-radius: 999px;
        padding: 0 12px;
        background: #fef2f2;
        color: #b91c1c;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
        cursor: pointer;
    }
    .lef-delete:hover { background: #fee2e2; color: #991b1b; }
    .lef-action.is-loading i { animation: lef-spin 0.8s linear infinite; }
    @keyframes lef-spin { to { transform: rotate(360deg); } }
    @media (min-width: 1180px) { .lef-grid.two { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 720px) {
        .lef-header { align-items: stretch; border-radius: 18px; }
        .lef-title-wrap { align-items: flex-start; }
        .lef-action { width: 100%; }
        .lef-panel-header { align-items: stretch; }
        .lef-lines { margin-inline: -0.25rem; padding-inline: 0.25rem; }
        .lef-line-tab { padding-inline: 14px; }
        .lef-table-wrap { overflow-x: visible; }
        .lef-table { min-width: 0; border-collapse: separate; border-spacing: 0; }
        .lef-table thead { display: none; }
        .lef-table, .lef-table tbody, .lef-table tr, .lef-table td { display: block; width: 100%; }
        .lef-table tbody { display: grid; gap: 12px; padding: 12px; }
        .lef-table tr { border: 1px solid #e2e8f0; border-radius: 14px; padding: 10px 12px; background: #fff; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05); }
        .lef-table td { display: grid; grid-template-columns: minmax(118px, 0.44fr) minmax(0, 1fr); gap: 10px; border-bottom: 0; padding: 8px 0; text-align: left !important; }
        .lef-table td::before { content: attr(data-label); color: #64748b; font-size: 0.72rem; font-weight: 900; letter-spacing: 0.04em; text-transform: uppercase; }
        .lef-table td[colspan] { display: block; text-align: center !important; color: #64748b; }
        .lef-table td[colspan]::before { content: none; }
        .lef-table td[data-label="Acciones"] { align-items: center; }
        .lef-delete { width: 100%; max-width: 160px; }
        #historial-importaciones { margin-bottom: 132px; }
        .lef-import-pagination { padding: 14px 18px 96px; }
    }
    @media (max-width: 480px) {
        .lef-title-icon { display: none; }
        .lef-kpis { grid-template-columns: 1fr; }
        .lef-kpi { min-height: auto; }
        .lef-chart-inner { min-width: 520px; }
        .lef-chart-inner.compact { min-width: 100%; }
        .lef-table td { grid-template-columns: 1fr; gap: 3px; }
    }
</style>

<div class="lef-shell" data-lef-dashboard>
    <div class="lef-header">
        <div class="lef-title-wrap">
            <span class="lef-title-icon" aria-hidden="true"><i class="fas fa-chart-column"></i></span>
            <div>
                <span class="lef-kicker"></span>
                <h1 class="lef-title">LEF-52-12-4</h1>
            </div>
        </div>
        @if($canManage)
            <button class="lef-action on-dark" type="button" data-toggle-import><i class="fas fa-file-import"></i>Importar datos 52-12-4</button>
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
        </div>
        <div class="lef-panel-body">
            <div class="lef-filters">
                <div class="lef-field"><label for="filter_date">Fecha / periodo</label><input id="filter_date" type="date" data-filter-date></div>
                <div class="lef-field">
                    <label for="filter_analysis">Tipo de analisis</label>
                    <select id="filter_analysis" data-filter-analysis>
                        <option value="all">Vista general</option>
                        <option value="machines">Maquinas</option>
                        <option value="parts">Partes de Lavadora</option>
                        <option value="washer">Linea general</option>
                        <option value="comparison">Comparacion entre lineas</option>
                    </select>
                </div>
                <div class="lef-field">
                    <label for="filter_period">Periodo</label>
                    <select id="filter_period" data-filter-period>
                        <option value="all">Comparativo 52-12-4</option>
                        <option value="52">Ventana 52 semanas</option>
                        <option value="12">Ventana 12 semanas</option>
                        <option value="4">Ventana 4 semanas</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                <button class="lef-action secondary" type="button" data-trend-toggle style="margin-right: 10px;">
                    <i class="fas fa-chart-line"></i>
                    <span data-trend-label>Ver tendencia</span>
                </button>
                <button class="lef-action secondary" type="button" data-clear-filters>
                    <i class="fas fa-rotate-left"></i>
                    <span data-clear-label>Limpiar</span>
                </button>
            </div>
            <div class="lef-alert info" data-feedback hidden style="margin-top: 14px; margin-bottom: 0;"></div>
        </div>
    </div>

    <div class="lef-kpis" data-kpis></div>
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
            <div class="lef-panel-header"><h2 class="lef-panel-title">LEF 52-12-4 - Linea general</h2></div>
            <div class="lef-panel-body"><div class="lef-kpis" data-washer-kpis></div><div class="lef-chart-wrap"><div class="lef-chart-inner compact"><canvas id="washerChart"></canvas></div></div></div>
        </section>
        <section class="lef-panel" data-card="comparison">
            <div class="lef-panel-header"><h2 class="lef-panel-title">Comparativo 52-12-4 general entre lineas</h2></div>
            <div class="lef-panel-body"><div class="lef-chart-wrap"><div class="lef-chart-inner" data-chart-width="comparison"><canvas id="comparisonChart"></canvas></div></div></div>
        </section>
    </div>

    <section class="lef-panel" data-trend-panel hidden>
        <div class="lef-panel-header">
            <div>
                <h2 class="lef-panel-title">Tendencia 52-12-4 - Maquina LAVADORA</h2>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.84rem;" data-trend-title>
                    Enero a diciembre 2025 y 2026
                </p>
            </div>
        </div>
        <div class="lef-panel-body">
            <div class="lef-alert info" data-trend-empty hidden></div>
            <div class="lef-chart-wrap" data-trend-chart-wrap>
                <div class="lef-chart-inner" style="min-width: 980px;">
                    <canvas id="washerTrendChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    @if($canManage)
        <section class="lef-panel" id="historial-importaciones">
            <div class="lef-panel-header"><h2 class="lef-panel-title">Historial de importaciones</h2></div>
            <div class="lef-table-wrap">
                <table class="lef-table">
                    <thead><tr><th>Linea</th><th>Archivo</th><th>Fecha informacion</th><th>Fecha importacion</th><th>Usuario</th><th>Maquinas</th><th>Partes</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse($imports as $import)
                            <tr>
                                <td data-label="Linea">{{ $import->linea?->nombre }}</td>
                                <td data-label="Archivo">{{ $import->source_filename }}</td>
                                <td data-label="Fecha informacion">{{ $import->data_date?->format('d/m/Y') }}</td>
                                <td data-label="Fecha importacion">{{ $import->created_at?->format('d/m/Y H:i') }}</td>
                                <td data-label="Usuario">{{ $import->user?->name ?? 'N/A' }}</td>
                                <td data-label="Maquinas">{{ $import->machines_count }}</td>
                                <td data-label="Partes">{{ $import->parts_count }}</td>
                                <td data-label="Estado">{{ ucfirst($import->status) }}</td>
                                <td data-label="Acciones">
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
            @if($imports->hasPages())
                <div class="lef-import-pagination">
                    {{ $imports->links() }}
                </div>
            @endif
        </section>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lineas = @json($lineasPayload);
    const endpoints = {
        data: @json(route('lef52124.data')),
        periods: @json(route('lef52124.periods')),
        trend: @json(route('lef52124.trend.washer-machine')),
    };
    const initialFilters = { lineaId: Number(@json($selectedLineaId)), dataDate: null, period: 'all', analysis: 'all' };
    const state = {
        lineaId: initialFilters.lineaId,
        dataDate: null,
        period: 'all',
        analysis: 'all',
        loadingPeriods: false,
        dataRequestId: 0,
        activeDataController: null,
        trendVisible: false,
        trendRequestId: 0,
        activeTrendController: null,
    };
    const charts = {};
    const compactQuery = window.matchMedia('(max-width: 640px)');
    const colors = { weeks52: '#f59e0b', weeks12: '#fdba74', weeks4: '#fbbf24', highlight: '#1e40af' };
    const periodDefinitions = [
        { key: '52', label: '52 SEM', datasetLabel: '1 - 52 SEM', valueKey: 'value_52_weeks', formattedKey: 'formatted_52', topKey: 'top_52', washerKey: 'washer_52', color: colors.weeks52, highlightColor: colors.highlight },
        { key: '12', label: '12 SEM', datasetLabel: '2 - 12 SEM', valueKey: 'value_12_weeks', formattedKey: 'formatted_12', topKey: 'top_12', washerKey: 'washer_12', color: colors.weeks12, highlightColor: '#2563eb' },
        { key: '4', label: '4 SEM', datasetLabel: '3 - 4 SEM', valueKey: 'value_4_weeks', formattedKey: 'formatted_4', topKey: 'top_4', washerKey: 'washer_4', color: colors.weeks4, highlightColor: '#60a5fa' },
    ];
    const valueLabelPlugin = {
        id: 'lefValueLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.fillStyle = '#1f2348';
            ctx.font = `700 ${compactQuery.matches ? 8 : 10}px sans-serif`;
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (meta.hidden) return;
                meta.data.forEach((bar, index) => {
                    const raw = dataset.data[index];
                    if (raw === null || raw === undefined || raw === '') return;
                    const value = Number(raw);
                    if (Number.isFinite(value)) ctx.fillText(formatNumber(value), bar.x, Math.max(12, bar.y - 4));
                });
            });
            ctx.restore();
        }
    };
    const nodes = {
        date: document.querySelector('[data-filter-date]'),
        analysis: document.querySelector('[data-filter-analysis]'),
        period: document.querySelector('[data-filter-period]'),
        clear: document.querySelector('[data-clear-filters]'),
        clearLabel: document.querySelector('[data-clear-label]'),
        trendButton: document.querySelector('[data-trend-toggle]'),
        trendLabel: document.querySelector('[data-trend-label]'),
        trendPanel: document.querySelector('[data-trend-panel]'),
        trendTitle: document.querySelector('[data-trend-title]'),
        trendEmpty: document.querySelector('[data-trend-empty]'),
        trendChartWrap: document.querySelector('[data-trend-chart-wrap]'),
        feedback: document.querySelector('[data-feedback]'),
        kpis: document.querySelector('[data-kpis]'),
        washerKpis: document.querySelector('[data-washer-kpis]'),
        charts: document.querySelector('[data-charts]'),
        machinesTitle: document.querySelector('[data-machines-title]'),
    };

    document.querySelector('[data-toggle-import]')?.addEventListener('click', toggleImportPanel);
    nodes.trendButton?.addEventListener('click', toggleTrendPanel);
    nodes.clear?.addEventListener('click', resetFilters);
    document.querySelectorAll('[data-line-tab]').forEach((button) => button.addEventListener('click', () => {
        state.lineaId = Number(button.dataset.lineaId);
        selectLineTab();
        loadPeriods(true);
        if (state.trendVisible) loadTrend();
    }));
    nodes.date?.addEventListener('change', () => { state.dataDate = nodes.date.value || null; loadData(); });
    nodes.analysis?.addEventListener('change', () => { state.analysis = nodes.analysis.value; loadData(); });
    nodes.period?.addEventListener('change', () => { state.period = nodes.period.value; loadData(); });
    document.querySelectorAll('[data-delete-import]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!confirm('Eliminar esta importacion tambien eliminara sus registros 52-12-4.')) event.preventDefault();
    }));
    compactQuery.addEventListener?.('change', loadData);

    loadPeriods(true);

    function toggleImportPanel() {
        const panel = document.getElementById('lefImportPanel');
        if (!panel) return;
        panel.hidden = !panel.hidden;
        if (!panel.hidden) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function toggleTrendPanel() {
        state.trendVisible = !state.trendVisible;
        if (nodes.trendPanel) nodes.trendPanel.hidden = !state.trendVisible;
        if (nodes.trendLabel) nodes.trendLabel.textContent = state.trendVisible ? 'Ocultar tendencia' : 'Ver tendencia';

        if (state.trendVisible) {
            loadTrend();
            nodes.trendPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    function loadTrend() {
        if (!state.lineaId || !state.trendVisible) return;
        const requestId = ++state.trendRequestId;
        state.activeTrendController?.abort();
        state.activeTrendController = new AbortController();
        setTrendLoading(true);

        fetch(`${endpoints.trend}?linea_id=${encodeURIComponent(state.lineaId)}`, {
            headers: { 'Accept': 'application/json' },
            signal: state.activeTrendController.signal
        })
            .then((response) => {
                if (!response.ok) throw new Error('No se pudo cargar la tendencia de Lavadora.');
                return response.json();
            })
            .then((payload) => {
                if (requestId !== state.trendRequestId) return;
                renderTrend(payload);
            })
            .catch((error) => {
                if (error.name === 'AbortError') return;
                renderTrendEmpty(error.message || 'No se pudo cargar la tendencia de Lavadora.');
            })
            .finally(() => {
                if (requestId !== state.trendRequestId) return;
                setTrendLoading(false);
            });
    }
    function setTrendLoading(isLoading) {
        if (!nodes.trendButton) return;
        nodes.trendButton.disabled = isLoading;
        nodes.trendButton.classList.toggle('is-loading', isLoading);
        nodes.trendButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        if (nodes.trendLabel) nodes.trendLabel.textContent = isLoading ? 'Cargando...' : (state.trendVisible ? 'Ocultar tendencia' : 'Ver tendencia');
    }
    function renderTrend(payload) {
        if (!payload.has_data) {
            renderTrendEmpty(`No existen registros mensuales 52-12-4 para la maquina Lavadora en ${currentLineName()} durante 2025 o 2026.`);
            return;
        }

        if (nodes.trendTitle) {
            nodes.trendTitle.textContent = `${payload.machine_name || 'LAVADORA'} | ${payload.linea || currentLineName()} | Enero-diciembre 2025 y 2026`;
        }

        if (nodes.trendEmpty) nodes.trendEmpty.hidden = true;
        if (nodes.trendChartWrap) nodes.trendChartWrap.hidden = false;

        const canvas = document.getElementById('washerTrendChart');
        if (!canvas) return;

        charts.washerTrendChart?.destroy();
        const yearStyles = {
            2025: { dash: [7, 5], alpha: 0.78 },
            2026: { dash: [], alpha: 1 },
        };
        const trendColors = {
            52: '#16a34a',
            12: '#dc2626',
            4: '#f97316',
        };
        const datasets = [];

        (payload.years || [2025, 2026]).forEach((year) => {
            const rows = payload.series?.[year] || [];
            periodDefinitions.forEach((period) => {
                datasets.push({
                    label: `${year} - ${period.label}`,
                    data: rows.map((row) => row[period.valueKey]),
                    borderColor: trendColors[period.key],
                    backgroundColor: trendColors[period.key],
                    borderDash: yearStyles[year]?.dash || [],
                    borderWidth: year === 2026 ? 3 : 2,
                    pointRadius: compactQuery.matches ? 3 : 4,
                    pointHoverRadius: compactQuery.matches ? 5 : 6,
                    tension: 0.28,
                    spanGaps: false,
                });
            });
        });

        charts.washerTrendChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: payload.months || ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets,
            },
            plugins: [valueLabelPlugin],
            options: trendChartOptions(),
        });
    }
    function renderTrendEmpty(message) {
        charts.washerTrendChart?.destroy();
        delete charts.washerTrendChart;
        if (nodes.trendChartWrap) nodes.trendChartWrap.hidden = true;
        if (nodes.trendEmpty) {
            nodes.trendEmpty.textContent = message;
            nodes.trendEmpty.hidden = false;
        }
    }
    function selectLineTab() {
        document.querySelectorAll('[data-line-tab]').forEach((button) => button.classList.toggle('active', Number(button.dataset.lineaId) === Number(state.lineaId)));
    }
    function syncStateFromControls() {
        if (nodes.date) state.dataDate = nodes.date.value || null;
        if (nodes.analysis) state.analysis = nodes.analysis.value || 'all';
        if (nodes.period) state.period = nodes.period.value || 'all';
        selectLineTab();
    }
    function resetFilters() {
        const lineChanged = Number(state.lineaId) !== Number(initialFilters.lineaId);
        state.lineaId = initialFilters.lineaId;
        state.dataDate = initialFilters.dataDate;
        state.period = initialFilters.period;
        state.analysis = initialFilters.analysis;
        if (nodes.date) nodes.date.value = '';
        if (nodes.analysis) nodes.analysis.value = initialFilters.analysis;
        if (nodes.period) nodes.period.value = initialFilters.period;
        selectLineTab();
        hideFeedback();

        if (lineChanged) {
            loadPeriods(true);
            return;
        }

        loadData({ clear: true });
    }
    function loadPeriods(resetImport) {
        if (!state.lineaId || state.loadingPeriods) return;
        state.loadingPeriods = true;
        fetch(`${endpoints.periods}?linea_id=${encodeURIComponent(state.lineaId)}`, { headers: { 'Accept': 'application/json' } })
            .then((response) => {
                if (!response.ok) throw new Error('No se pudieron consultar los periodos disponibles.');
                return response.json();
            })
            .then((data) => {
                const imports = data.items || [];
                if (nodes.date) {
                    if (resetImport) nodes.date.value = '';
                    nodes.date.min = imports.length ? imports[imports.length - 1].data_date : '';
                    nodes.date.max = imports.length ? imports[0].data_date : '';
                    nodes.date.disabled = false;
                    state.dataDate = nodes.date.value || null;
                }
            })
            .catch((error) => showFeedback(error.message || 'No se pudieron consultar los periodos disponibles.', 'error'))
            .finally(() => { state.loadingPeriods = false; loadData(); });
    }
    function loadData(options = {}) {
        syncStateFromControls();
        if (!state.lineaId) return;
        const requestId = ++state.dataRequestId;
        state.activeDataController?.abort();
        state.activeDataController = new AbortController();
        if (options.clear) setClearLoading(true);
        const params = new URLSearchParams({ linea_id: state.lineaId, period: state.period, analysis_type: state.analysis });
        if (state.dataDate) params.set('data_date', state.dataDate);
        fetch(`${endpoints.data}?${params.toString()}`, { headers: { 'Accept': 'application/json' }, signal: state.activeDataController.signal })
            .then((response) => {
                if (!response.ok) throw new Error('No se pudieron cargar los datos 52-12-4.');
                return response.json();
            })
            .then((payload) => {
                if (requestId !== state.dataRequestId) return;
                hideFeedback();
                renderDashboard(payload);
            })
            .catch((error) => {
                if (error.name === 'AbortError') return;
                renderEmpty(error.message || 'No se pudieron cargar los datos 52-12-4.');
            })
            .finally(() => {
                if (requestId !== state.dataRequestId) return;
                if (options.clear) setClearLoading(false);
            });
    }
    function renderDashboard(payload) {
        if (!payload.has_data) return renderEmpty(payload.message || 'No existen datos 52-12-4 para la linea seleccionada.');
        nodes.charts.hidden = false;
        renderKpis(payload.summary || {});
        renderWasherKpis(payload.washer);
        renderVisibility();
        nodes.machinesTitle.textContent = 'Pareto de LEF - Maquinas';
        renderGroupedBar('machinesChart', payload.machines || [], 'Maquina', false);
        renderGroupedBar('partsChart', payload.parts || [], 'Parte', false);
        renderWasherChart(payload.washer);
        renderComparisonChart(payload.comparison || []);
    }
    function renderEmpty(message) {
        destroyAll();
        nodes.kpis.innerHTML = '';
        nodes.washerKpis.innerHTML = '';
        nodes.charts.hidden = true;
        showFeedback(message, 'error');
    }
    function setClearLoading(isLoading) {
        if (!nodes.clear) return;
        nodes.clear.disabled = isLoading;
        nodes.clear.classList.toggle('is-loading', isLoading);
        nodes.clear.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        if (nodes.clearLabel) nodes.clearLabel.textContent = isLoading ? 'Limpiando...' : 'Limpiar';
    }
    function showFeedback(message, type = 'info') {
        if (!nodes.feedback) return;
        nodes.feedback.textContent = message;
        nodes.feedback.className = `lef-alert ${type}`;
        nodes.feedback.hidden = false;
    }
    function hideFeedback() {
        if (!nodes.feedback) return;
        nodes.feedback.hidden = true;
    }
    function renderVisibility() {
        const visible = { machines: ['all', 'machines'].includes(state.analysis), parts: ['all', 'parts'].includes(state.analysis), washer: ['all', 'washer'].includes(state.analysis), comparison: ['all', 'comparison'].includes(state.analysis) };
        Object.entries(visible).forEach(([key, show]) => { const card = document.querySelector(`[data-card="${key}"]`); if (card) card.hidden = !show; });
    }
    function renderKpis(summary) {
        const periods = selectedPeriodDefinitions();
        const cards = [['Linea seleccionada', summary.linea || currentLineName(), '']];

        periods.forEach((period) => {
            const top = summary[period.topKey];
            cards.push([`Mayor afectacion ${period.label}`, top?.name || 'Sin dato', top?.value || '']);
        });

        periods.forEach((period) => {
            cards.push([`LEF linea general ${period.label}`, summary[period.washerKey] || 'Sin dato', '']);
        });

        nodes.kpis.innerHTML = cards.map(kpiMarkup).join('');
    }
    function renderWasherKpis(washer) {
        const cards = selectedPeriodDefinitions().map((period) => [period.label, washer?.[period.formattedKey] || 'Sin dato', washer?.name || 'Linea general']);
        nodes.washerKpis.innerHTML = cards.map(kpiMarkup).join('');
    }
    function kpiMarkup([label, value, meta]) {
        const valueText = String(value ?? '');
        const valueClass = valueText.length > 18 ? 'lef-kpi-value is-long' : 'lef-kpi-value';
        return `<div class="lef-kpi"><div class="lef-kpi-label">${escapeHtml(label)}</div><div class="${valueClass}">${escapeHtml(valueText)}</div><div class="lef-kpi-meta">${escapeHtml(meta || '')}</div></div>`;
    }
    function renderGroupedBar(canvasId, rows, xLabel, highlightWasher) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        setChartWidth(canvasId === 'machinesChart' ? 'machines' : 'parts', rows.length, rows.map((row) => row.name));
        charts[canvasId]?.destroy();
        charts[canvasId] = new Chart(canvas, { type: 'bar', data: { labels: rows.map((row) => row.name), datasets: datasetsForRows(rows, highlightWasher) }, plugins: [valueLabelPlugin], options: baseChartOptions(xLabel, 'Valor LEF') });
    }
    function renderWasherChart(row) {
        const canvas = document.getElementById('washerChart');
        if (!canvas) return;
        const periods = selectedPeriodDefinitions();
        charts.washerChart?.destroy();
        charts.washerChart = new Chart(canvas, { type: 'bar', data: { labels: periods.map((period) => period.label), datasets: [{ label: row?.name || 'Linea general', data: periods.map((period) => row ? row[period.valueKey] : 0), backgroundColor: periods.map((period) => period.color), borderRadius: 8 }] }, plugins: [valueLabelPlugin], options: baseChartOptions('Periodo', 'Valor LEF', false) });
    }
    function renderComparisonChart(rows) {
        const canvas = document.getElementById('comparisonChart');
        if (!canvas) return;
        setChartWidth('comparison', rows.length, rows.map((row) => row.linea));
        charts.comparisonChart?.destroy();
        charts.comparisonChart = new Chart(canvas, { type: 'bar', data: { labels: rows.map((row) => row.linea), datasets: selectedPeriodDefinitions().map((period) => dataset(period.datasetLabel, rows.map((row) => row[period.valueKey]), period.color)) }, plugins: [valueLabelPlugin], options: baseChartOptions('Linea', 'Valor LEF', false) });
    }
    function datasetsForRows(rows, highlightWasher) {
        return selectedPeriodDefinitions().map((period) => dataset(
            period.datasetLabel,
            rows.map((row) => row[period.valueKey]),
            rows.map((row) => highlightWasher && row.highlight ? period.highlightColor : period.color)
        ));
    }
    function selectedPeriodDefinitions() { return state.period === 'all' ? periodDefinitions : periodDefinitions.filter((period) => period.key === state.period); }
    function dataset(label, data, backgroundColor) { return { label, data, backgroundColor, borderRadius: 7, borderSkipped: false, maxBarThickness: compactQuery.matches ? 26 : 44 }; }
    function baseChartOptions(xTitle, yTitle, rotate = true) {
        const compact = compactQuery.matches;
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 450 },
            plugins: {
                legend: {
                    position: compact ? 'bottom' : 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: compact ? 10 : 14,
                        color: '#334155',
                        font: { size: compact ? 10 : 12, weight: '700' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.96)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    callbacks: { label: (context) => `${context.dataset.label}: ${formatNumber(context.raw)}` }
                }
            },
            layout: { padding: { top: compact ? 12 : 18, right: 8, bottom: compact ? 12 : 18, left: 4 } },
            scales: {
                x: {
                    grid: { display: false },
                    title: { display: !compact, text: xTitle, color: '#64748b', font: { weight: 'bold' } },
                    ticks: {
                        autoSkip: false,
                        maxRotation: rotate ? (compact ? 0 : 18) : 0,
                        minRotation: rotate ? 0 : 0,
                        color: '#475569',
                        font: { size: compact ? 9 : 10, weight: '700' },
                        callback(value) { return wrapLabel(this.getLabelForValue(value), compact ? 12 : 16); }
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '18%',
                    grid: { color: 'rgba(148, 163, 184, 0.20)' },
                    title: { display: !compact, text: yTitle, color: '#64748b', font: { weight: 'bold' } },
                    ticks: { callback: (value) => formatNumber(value), color: '#475569', font: { size: compact ? 10 : 11 } }
                }
            }
        };
    }
    function trendChartOptions() {
        const compact = compactQuery.matches;
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 450 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: compact ? 'bottom' : 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: compact ? 10 : 14,
                        color: '#334155',
                        font: { size: compact ? 10 : 12, weight: '700' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.96)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.raw === null ? 'Sin registro' : formatNumber(context.raw)}`,
                    }
                }
            },
            layout: { padding: { top: compact ? 12 : 18, right: 12, bottom: compact ? 12 : 18, left: 4 } },
            scales: {
                x: {
                    grid: { color: 'rgba(148, 163, 184, 0.12)' },
                    title: { display: !compact, text: 'Mes', color: '#64748b', font: { weight: 'bold' } },
                    ticks: { color: '#475569', font: { size: compact ? 10 : 11, weight: '700' } }
                },
                y: {
                    beginAtZero: true,
                    grace: '18%',
                    grid: { color: 'rgba(148, 163, 184, 0.20)' },
                    title: { display: !compact, text: 'Valor LEF', color: '#64748b', font: { weight: 'bold' } },
                    ticks: { callback: (value) => formatNumber(value), color: '#475569', font: { size: compact ? 10 : 11 } }
                }
            }
        };
    }
    function setChartWidth(key, total, labels = []) {
        const node = document.querySelector(`[data-chart-width="${key}"]`);
        if (!node) return;
        const minimum = compactQuery.matches ? 520 : 760;
        const longestLabel = labels.reduce((max, label) => Math.max(max, String(label || '').length), 0);
        const perItem = (compactQuery.matches ? 78 : 118) + Math.min(longestLabel, 36);
        node.style.minWidth = `${Math.max(minimum, total * perItem)}px`;
    }
    function destroyAll() { Object.keys(charts).forEach((key) => { charts[key]?.destroy(); delete charts[key]; }); }
    function currentLineName() { return lineas.find((linea) => Number(linea.id) === Number(state.lineaId))?.nombre || 'Sin linea'; }
    function formatNumber(value) { if (value === null || value === undefined || Number.isNaN(Number(value))) return '0.00'; return Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function wrapLabel(value, limit) {
        const words = String(value || '').replace(/([/-])/g, '$1 ').split(/\s+/).filter(Boolean);
        const lines = [];
        let currentLine = '';

        words.forEach((word) => {
            if (word.length > limit) {
                if (currentLine) lines.push(currentLine);
                for (let index = 0; index < word.length; index += limit) {
                    lines.push(word.slice(index, index + limit));
                }
                currentLine = '';
                return;
            }

            const nextLine = currentLine ? `${currentLine} ${word}` : word;
            if (nextLine.length <= limit) {
                currentLine = nextLine;
                return;
            }

            if (currentLine) lines.push(currentLine);
            currentLine = word;
        });

        if (currentLine) lines.push(currentLine);

        return lines.length ? lines : [''];
    }
    function escapeHtml(value) { return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
});
</script>
@endsection
