@extends('layouts.app')

@section('title', 'Control de Gastos')

@section('content')
@php
    $budgetYear = (int) ($filters['budget_year'] ?? now()->year);
    $canCreateLavadoraCosts = auth()->user()?->canCreateLavadoraCosts() ?? false;
    $canEditLavadoraCosts = auth()->user()?->canEditLavadoraCosts() ?? false;
    $canDeleteLavadoraCosts = auth()->user()?->canDeleteLavadoraCosts() ?? false;
    $canModifyLavadoraCosts = $canEditLavadoraCosts || $canDeleteLavadoraCosts;
    $canModifyLavadoraBudgets = $canCreateLavadoraCosts || $canEditLavadoraCosts;
@endphp

<style>
    :root {
        --expense-primary: #2563eb;
        --expense-primary-dark: #1e40af;
        --expense-accent: #0f766e;
        --expense-accent-soft: #14b8a6;
        --expense-warning: #f59e0b;
        --expense-danger: #dc2626;
        --expense-surface: #ffffff;
        --expense-surface-alt: #f8fafc;
        --expense-line: #dbe4f0;
        --expense-line-strong: #cbd5e1;
        --expense-text: #0f172a;
        --expense-muted: #64748b;
        --expense-muted-strong: #475569;
        --expense-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    }

    .expense-admin-shell {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 1.35rem;
        padding-bottom: 1rem;
        isolation: isolate;
    }

    .expense-admin-shell::before {
        content: '';
        position: absolute;
        inset: -2rem 0 auto;
        height: 20rem;
        background:
            radial-gradient(circle at left top, rgba(37, 99, 235, 0.12), transparent 34%),
            radial-gradient(circle at right top, rgba(20, 184, 166, 0.12), transparent 28%),
            linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(248, 250, 252, 0));
        z-index: -1;
        pointer-events: none;
    }

    .expense-admin-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.6rem;
        padding: 1.75rem;
        color: #fff;
        background:
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.22), transparent 28%),
            linear-gradient(135deg, #0f172a, #1e3a8a 58%, #0f766e);
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22);
    }

    .expense-admin-hero::before,
    .expense-admin-hero::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    .expense-admin-hero::before {
        inset: auto auto -3.5rem -2.5rem;
        width: 11rem;
        height: 11rem;
    }

    .expense-admin-hero::after {
        inset: -4rem -2rem auto auto;
        width: 9rem;
        height: 9rem;
    }

    .hero-copy {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1.25rem;
    }

    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.8rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        color: #dbeafe;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        backdrop-filter: blur(8px);
    }

    .expense-admin-hero h1 {
        margin: 0 0 0.65rem;
        font-size: clamp(2rem, 2.8vw, 2.6rem);
        font-weight: 900;
        letter-spacing: -0.04em;
    }

    .expense-admin-hero p {
        margin: 0;
        max-width: 48rem;
        color: rgba(226, 232, 240, 0.92);
        line-height: 1.7;
    }

    .hero-badges,
    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .hero-badges {
        margin-top: 1rem;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 0.95rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #f8fafc;
        font-size: 0.85rem;
        font-weight: 700;
        backdrop-filter: blur(10px);
    }

    .expense-admin-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .expense-card,
    .expense-panel {
        position: relative;
        overflow: hidden;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
        border-radius: 1.35rem;
        border: 1px solid rgba(203, 213, 225, 0.7);
        box-shadow: var(--expense-shadow);
    }

    .expense-card::before,
    .expense-panel::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 0.28rem;
        background: linear-gradient(90deg, var(--expense-primary), var(--expense-accent-soft));
    }

    .expense-card:nth-child(2)::before {
        background: linear-gradient(90deg, var(--expense-accent), #10b981);
    }

    .expense-card:nth-child(3)::before {
        background: linear-gradient(90deg, var(--expense-warning), #f97316);
    }

    .expense-card:nth-child(4)::before {
        background: linear-gradient(90deg, #334155, var(--expense-primary));
    }

    .expense-card {
        padding: 1.15rem;
    }

    .expense-card-label {
        color: var(--expense-muted);
        font-size: 0.77rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .expense-card-value {
        margin-top: 0.75rem;
        color: var(--expense-text);
        font-size: clamp(1.7rem, 2vw, 2rem);
        font-weight: 900;
        line-height: 1;
    }

    .expense-card-meta {
        margin-top: 0.55rem;
        color: var(--expense-muted-strong);
        font-size: 0.9rem;
        line-height: 1.55;
    }

    .expense-panel {
        padding: 1.25rem;
    }

    .panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(219, 228, 240, 0.9);
    }

    .panel-head h2,
    .panel-head h3 {
        margin: 0;
        color: var(--expense-text);
        font-size: 1.02rem;
        font-weight: 900;
        letter-spacing: -0.01em;
    }

    .panel-copy {
        margin-top: 0.4rem;
        color: var(--expense-muted);
        font-size: 0.92rem;
        line-height: 1.6;
        max-width: 56rem;
    }

    .toolbar-grid,
    .form-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 0.95rem;
        padding: 1rem;
        border-radius: 1.05rem;
        border: 1px solid rgba(219, 228, 240, 0.95);
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .toolbar-grid > div,
    .form-grid > div {
        grid-column: span 2;
        min-width: 0;
    }

    .field-label {
        display: block;
        margin-bottom: 0.45rem;
        color: var(--expense-muted-strong);
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .field-control,
    .input-inline,
    .textarea-inline,
    .select-inline {
        width: 100%;
        border: 1px solid var(--expense-line-strong);
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.78rem 0.92rem;
        color: var(--expense-text);
        font-size: 0.94rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .field-control:focus,
    .input-inline:focus,
    .textarea-inline:focus,
    .select-inline:focus {
        outline: none;
        border-color: var(--expense-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .textarea-inline,
    textarea.field-control {
        min-height: 4.4rem;
        resize: vertical;
    }

    .field-span-2 {
        grid-column: span 4 !important;
    }

    .field-span-3 {
        grid-column: span 6 !important;
    }

    .table-wrap {
        overflow: auto;
        border-radius: 1.05rem;
        border: 1px solid rgba(219, 228, 240, 0.95);
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .table-wrap table {
        width: 100%;
        min-width: 1020px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-wrap th,
    .table-wrap td {
        padding: 0.9rem 0.8rem;
        border-bottom: 1px solid rgba(219, 228, 240, 0.95);
        vertical-align: top;
        text-align: left;
    }

    .table-wrap th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #eff6ff;
        color: var(--expense-primary-dark);
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .table-wrap tbody tr {
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .table-wrap tbody tr:hover {
        background: rgba(248, 250, 252, 0.9);
    }

    .table-wrap tbody tr:last-child td {
        border-bottom: 0;
    }

    .table-actions {
        display: grid;
        gap: 0.55rem;
        min-width: 160px;
    }

    .table-actions form,
    .table-actions .create-action,
    .table-actions button {
        width: 100%;
    }

    .split-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
        gap: 1rem;
        align-items: start;
    }

    .rule-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.42rem 0.72rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        border: 1px solid transparent;
    }

    .rule-chip--success {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }

    .rule-chip--muted {
        background: #e2e8f0;
        color: #475569;
        border-color: #cbd5e1;
    }

    .budget-track {
        width: 100%;
        height: 0.72rem;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(203, 213, 225, 0.6);
    }

    .budget-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--expense-primary), var(--expense-accent-soft));
    }

    .budget-bar.is-warning {
        background: linear-gradient(90deg, var(--expense-warning), #f97316);
    }

    .budget-bar.is-critical {
        background: linear-gradient(90deg, #ef4444, var(--expense-danger));
    }

    .history-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 0.9rem;
    }

    .history-item {
        position: relative;
        padding: 1rem 1rem 1rem 1.15rem;
        border-radius: 1rem;
        background: #f8fafc;
        border: 1px solid rgba(219, 228, 240, 0.95);
    }

    .history-item::before {
        content: '';
        position: absolute;
        inset: 1rem auto 1rem 0;
        width: 0.22rem;
        border-radius: 999px;
        background: linear-gradient(180deg, var(--expense-primary), var(--expense-accent));
    }

    .history-item strong {
        color: var(--expense-text);
    }

    @media (max-width: 1280px) {
        .expense-admin-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .split-grid {
            grid-template-columns: 1fr;
        }

        .toolbar-grid > div,
        .form-grid > div {
            grid-column: span 3;
        }

        .field-span-2 {
            grid-column: span 6 !important;
        }

        .field-span-3 {
            grid-column: span 12 !important;
        }
    }

    @media (max-width: 1024px) {
        .toolbar-grid > div,
        .form-grid > div {
            grid-column: span 6;
        }

        .field-span-2,
        .field-span-3 {
            grid-column: span 12 !important;
        }
    }

    @media (max-width: 860px) {
        .table-wrap {
            overflow: visible;
            border: 0;
            background: transparent;
        }

        .table-wrap table,
        .table-wrap thead,
        .table-wrap tbody,
        .table-wrap tr,
        .table-wrap td {
            display: block;
            width: 100%;
        }

        .table-wrap table {
            min-width: 0;
        }

        .table-wrap thead {
            display: none;
        }

        .table-wrap tbody {
            display: grid;
            gap: 0.9rem;
        }

        .table-wrap tbody tr {
            border: 1px solid rgba(219, 228, 240, 0.95);
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .table-wrap td {
            display: grid;
            grid-template-columns: minmax(105px, 38%) minmax(0, 1fr);
            gap: 0.8rem;
            padding: 0.8rem 0.9rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            align-items: start;
        }

        .table-wrap td::before {
            content: attr(data-label);
            color: var(--expense-muted);
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .table-wrap td[colspan] {
            display: block;
            text-align: center;
        }

        .table-wrap td[colspan]::before {
            content: none;
        }

        .table-wrap td:last-child {
            border-bottom: 0;
        }

        .table-actions {
            min-width: 0;
        }
    }

    @media (max-width: 768px) {
        .expense-admin-shell {
            gap: 1rem;
        }

        .expense-admin-hero,
        .expense-panel,
        .expense-card {
            border-radius: 1.15rem;
        }

        .expense-admin-grid {
            grid-template-columns: 1fr;
        }

        .toolbar-grid,
        .form-grid {
            padding: 0.9rem;
        }

        .toolbar-grid > div,
        .form-grid > div {
            grid-column: span 12;
        }
    }

    @media (max-width: 640px) {
        .expense-admin-hero {
            padding: 1.25rem;
        }

        .hero-actions,
        .hero-actions .create-action {
            width: 100%;
        }

        .hero-actions .create-action {
            justify-content: center;
        }

        .table-wrap td {
            grid-template-columns: 1fr;
            gap: 0.45rem;
        }
    }
</style>

<div class="expense-admin-shell">
    <section class="expense-admin-hero">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1>Control de Gastos</h1>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('lavadora.costos.index') }}" class="create-action create-action--on-dark">
                    <i class="fas fa-chart-line"></i>
                    Ver módulo Costos
                </a>
            </div>
        </div>
    </section>

    <section class="expense-admin-grid">
        <article class="expense-card">
            <div class="expense-card-label">Conceptos cargados</div>
            <div class="expense-card-value">{{ $metrics['catalog_total'] }}</div>

        </article>
        <article class="expense-card">
            <div class="expense-card-label">Conceptos activos</div>
            <div class="expense-card-value">{{ $metrics['catalog_active'] }}</div>

        </article>
        <article class="expense-card">
            <div class="expense-card-label">Reglas automáticas</div>
            <div class="expense-card-value">{{ $metrics['rules_total'] }}</div>

        </article>
        <article class="expense-card">
            <div class="expense-card-label">Presupuestos {{ $budgetYear }}</div>
            <div class="expense-card-value">{{ $metrics['budgets_configured'] }}</div>
        
        </article>
    </section>

    @if($canCreateLavadoraCosts)
    <section class="expense-panel">
        <div class="panel-head">
            <div>
                <h2><i class="fas fa-plus-circle text-emerald-600 mr-2"></i>Nuevo componente de costo</h2>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.costos.catalog.store') }}" class="form-grid">
            @csrf
            <div>
                <label class="field-label" for="new-sku">SKU</label>
                <input id="new-sku" name="sku" class="field-control" value="{{ old('sku') }}">
            </div>
            <div class="field-span-2">
                <label class="field-label" for="new-nombre">Nombre</label>
                <input id="new-nombre" name="nombre" class="field-control" value="{{ old('nombre') }}" required>
            </div>
            <div>
                <label class="field-label" for="new-categoria">Categoría</label>
                <input id="new-categoria" name="categoria" class="field-control" value="{{ old('categoria') }}">
            </div>
            <div>
                <label class="field-label" for="new-unidad">Unidad</label>
                <input id="new-unidad" name="unidad_medida" class="field-control" value="{{ old('unidad_medida', 'Pieza') }}" required>
            </div>
            <div>
                <label class="field-label" for="new-costo">Costo unitario</label>
                <input id="new-costo" name="costo_unitario" type="number" min="0" step="0.01" class="field-control" value="{{ old('costo_unitario') }}" required>
            </div>
            <div class="field-span-2">
                <label class="field-label" for="new-aliases">Alias / palabras clave</label>
                <input id="new-aliases" name="aliases_input" class="field-control" value="{{ old('aliases_input') }}" placeholder="ACEITE, RETEN, O-RING">
            </div>
            <div class="field-span-3">
                <label class="field-label" for="new-observaciones">Observaciones</label>
                <textarea id="new-observaciones" name="observaciones" class="field-control">{{ old('observaciones') }}</textarea>
            </div>
            <div class="flex items-end">
                <button type="submit" class="create-action">
                    <i class="fas fa-floppy-disk"></i>
                    Guardar concepto
                </button>
            </div>
        </form>
    </section>
    @endif

    <section class="expense-panel">
        <div class="panel-head">
            <div>
                <h2><i class="fas fa-table text-blue-600 mr-2"></i>Catálogo de costos</h2>
            </div>
        </div>

        <form method="GET" class="toolbar-grid" style="margin-bottom: 1rem;">
            <div class="field-span-2">
                <label class="field-label" for="catalog-q">Buscar</label>
                <input id="catalog-q" name="q" class="field-control" value="{{ $filters['q'] ?? '' }}" placeholder="SKU, concepto o categoría">
            </div>
            <div>
                <label class="field-label" for="catalog-category">Categoría</label>
                <select id="catalog-category" name="categoria" class="field-control">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(($filters['categoria'] ?? null) === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="catalog-active">Estado</label>
                <select id="catalog-active" name="activo" class="field-control">
                    <option value="todos" @selected(($filters['activo'] ?? 'todos') === 'todos')>Todos</option>
                    <option value="activos" @selected(($filters['activo'] ?? 'todos') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(($filters['activo'] ?? 'todos') === 'inactivos')>Inactivos</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="catalog-sort">Ordenar por</label>
                <select id="catalog-sort" name="sort" class="field-control">
                    <option value="categoria" @selected(($filters['sort'] ?? 'categoria') === 'categoria')>Categoría</option>
                    <option value="nombre" @selected(($filters['sort'] ?? null) === 'nombre')>Nombre</option>
                    <option value="costo_unitario" @selected(($filters['sort'] ?? null) === 'costo_unitario')>Costo</option>
                    <option value="fecha_actualizacion" @selected(($filters['sort'] ?? null) === 'fecha_actualizacion')>Fecha actualización</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="create-action">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>
                <a href="{{ route('admin.costos.index') }}" class="create-action create-action--secondary">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Concepto</th>
                        <th>Categoría</th>
                        <th>Unidad</th>
                        <th>Costo</th>
                        <th>Alias</th>
                        <th>Observaciones</th>
                        <th>Actualizado</th>
                        @if($canModifyLavadoraCosts)
                            @if($canModifyLavadoraCosts)
                                <th>Acciones</th>
                            @endif
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php($formId = 'catalog-item-' . $item->id)
                        <tr>
                            <td>
                                <input class="input-inline" name="sku" value="{{ old('sku.' . $item->id, $item->sku) }}" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                            </td>
                            <td>
                                <textarea class="textarea-inline" name="nombre" form="{{ $formId }}" required @disabled(!$canEditLavadoraCosts)>{{ old('nombre.' . $item->id, $item->nombre) }}</textarea>
                            </td>
                            <td>
                                <input class="input-inline" name="categoria" value="{{ old('categoria.' . $item->id, $item->categoria) }}" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                            </td>
                            <td>
                                <input class="input-inline" name="unidad_medida" value="{{ old('unidad_medida.' . $item->id, $item->unidad_medida) }}" form="{{ $formId }}" required @disabled(!$canEditLavadoraCosts)>
                            </td>
                            <td>
                                <input class="input-inline" name="costo_unitario" type="number" min="0" step="0.01" value="{{ old('costo_unitario.' . $item->id, $item->costo_unitario) }}" form="{{ $formId }}" required @disabled(!$canEditLavadoraCosts)>
                            </td>
                            <td>
                                <textarea class="textarea-inline" name="aliases_input" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>{{ old('aliases_input.' . $item->id, implode(', ', $item->aliases ?? [])) }}</textarea>
                            </td>
                            <td>
                                <textarea class="textarea-inline" name="observaciones" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>{{ old('observaciones.' . $item->id, $item->observaciones) }}</textarea>
                            </td>
                            <td>
                                <div class="text-sm font-semibold text-slate-900">{{ optional($item->fecha_actualizacion)->format('d/m/Y') ?? 'Sin fecha' }}</div>
                                <div class="text-xs text-slate-500">{{ $item->updatedBy?->name ?? 'Sistema' }}</div>
                                <div class="mt-2">
                                    <span class="rule-chip">{{ $item->activo ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                            </td>
                            @if($canModifyLavadoraCosts)
                            <td>
                                @if($canEditLavadoraCosts)
                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.costos.catalog.update', $item) }}">
                                    @csrf
                                    @method('PUT')
                                </form>
                                @endif
                                <div class="table-actions">
                                    @if($canEditLavadoraCosts)
                                    <button type="submit" form="{{ $formId }}" class="create-action create-action--compact">
                                        <i class="fas fa-floppy-disk"></i>
                                        Guardar
                                    </button>
                                    <button type="button" class="create-action create-action--secondary create-action--compact" onclick="document.getElementById('{{ $formId }}').reset()">
                                        <i class="fas fa-ban"></i>
                                        Cancelar
                                    </button>
                                    <form method="POST" action="{{ route('admin.costos.catalog.toggle', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="create-action create-action--secondary create-action--compact">
                                            <i class="fas fa-power-off"></i>
                                            {{ $item->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    @endif
                                    @if($canDeleteLavadoraCosts)
                                    <form method="POST" action="{{ route('admin.costos.catalog.destroy', $item) }}" onsubmit="return confirm('Se eliminará el concepto si no tiene reglas ni gastos asociados. ¿Continuar?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="create-action create-action--danger create-action--compact">
                                            <i class="fas fa-trash"></i>
                                            Eliminar
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canModifyLavadoraCosts ? 9 : 8 }}">No se encontraron conceptos con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </section>

    <section class="split-grid">
        <article class="expense-panel">
            <div class="panel-head">
                <div>
                    <h3><i class="fas fa-microchip text-cyan-600 mr-2"></i>Reglas de automatización</h3>
                </div>
            </div>

            @if($canCreateLavadoraCosts)
            <form method="POST" action="{{ route('admin.costos.rules.store') }}" class="form-grid" style="margin-bottom: 1rem;">
                @csrf
                <div class="field-span-2">
                    <label class="field-label">Concepto de catálogo</label>
                    <select name="cost_catalog_item_id" class="field-control" required>
                        <option value="">Selecciona</option>
                        @foreach($catalogOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->categoria }} · {{ \Illuminate\Support\Str::limit($option->nombre, 70) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Lavadora</label>
                    <select name="linea_nombre" class="field-control">
                        <option value="">Todas</option>
                        @foreach($budgetRows as $budgetRow)
                            <option value="{{ $budgetRow['linea']->nombre }}">{{ $budgetRow['linea']->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Componente</label>
                    <select name="component_code" class="field-control">
                        <option value="">Cualquiera</option>
                        @foreach($componentCodes as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Disparador</label>
                    <select name="trigger_type" class="field-control" required>
                        @foreach($triggerOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Palabra clave</label>
                    <input name="trigger_keyword" class="field-control" placeholder="ACEITE, RETEN, O-RING">
                </div>
                <div>
                    <label class="field-label">Cantidad</label>
                    <input name="quantity" type="number" min="0.01" step="0.01" value="1" class="field-control" required>
                </div>
                <div class="field-span-2">
                    <label class="field-label">Notas</label>
                    <input name="notas" class="field-control">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="create-action">
                        <i class="fas fa-plus"></i>
                        Crear regla
                    </button>
                </div>
            </form>
            @endif

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Lavadora</th>
                            <th>Componente</th>
                            <th>Disparador</th>
                            <th>Keyword</th>
                            <th>Cantidad</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                            @php($formId = 'rule-form-' . $rule->id)
                            <tr>
                                <td>
                                    <select class="select-inline" name="cost_catalog_item_id" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                        @foreach($catalogOptions as $option)
                                            <option value="{{ $option->id }}" @selected($rule->cost_catalog_item_id === $option->id)>{{ \Illuminate\Support\Str::limit($option->nombre, 60) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="select-inline" name="linea_nombre" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                        <option value="">Todas</option>
                                        @foreach($budgetRows as $budgetRow)
                                            <option value="{{ $budgetRow['linea']->nombre }}" @selected($rule->linea_nombre === $budgetRow['linea']->nombre)>{{ $budgetRow['linea']->nombre }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="select-inline" name="component_code" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                        <option value="">Cualquiera</option>
                                        @foreach($componentCodes as $code)
                                            <option value="{{ $code }}" @selected($rule->component_code === $code)>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="select-inline" name="trigger_type" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                        @foreach($triggerOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($rule->trigger_type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="input-inline" name="trigger_keyword" value="{{ $rule->trigger_keyword }}" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                </td>
                                <td>
                                    <input class="input-inline" name="quantity" type="number" min="0.01" step="0.01" value="{{ $rule->quantity }}" form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                </td>
                                <td>
                                    <input type="hidden" name="activo" value="0" form="{{ $formId }}">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="activo" value="1" @checked($rule->activo) form="{{ $formId }}" @disabled(!$canEditLavadoraCosts)>
                                        Activa
                                    </label>
                                </td>
                                @if($canModifyLavadoraCosts)
                                    <td>
                                        @if($canEditLavadoraCosts)
                                            <form id="{{ $formId }}" method="POST" action="{{ route('admin.costos.rules.update', $rule) }}">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                        @endif
                                        <div class="table-actions">
                                            @if($canEditLavadoraCosts)
                                                <button type="submit" form="{{ $formId }}" class="create-action create-action--compact">
                                                    <i class="fas fa-floppy-disk"></i>
                                                    Guardar
                                                </button>
                                            @endif
                                            @if($canDeleteLavadoraCosts)
                                                <form method="POST" action="{{ route('admin.costos.rules.destroy', $rule) }}" onsubmit="return confirm('¿Eliminar esta regla automática?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="create-action create-action--danger create-action--compact">
                                                        <i class="fas fa-trash"></i>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canModifyLavadoraCosts ? 8 : 7 }}">No hay reglas registradas todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="expense-panel">
            <div class="panel-head">
                <div>
                    <h3><i class="fas fa-wallet text-emerald-600 mr-2"></i>Presupuesto anual por lavadora</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Lavadora</th>
                            <th>Presupuesto</th>
                            <th>Gastado</th>
                            <th>Disponible</th>
                            <th>Uso</th>
                            @if($canModifyLavadoraBudgets)
                                <th>Guardar</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($budgetRows as $row)
                            @php($formId = 'budget-form-' . $row['linea']->id)
                            <tr>
                                <td>
                                    <strong>{{ $row['linea']->nombre }}</strong>
                                    <div class="text-xs text-slate-500 mt-1">{{ $row['budget']?->updatedBy?->name ?? 'Sin responsable' }}</div>
                                </td>
                                <td>
                                    <input class="input-inline" name="annual_budget" type="number" min="0" step="0.01" value="{{ $row['budget']?->annual_budget ?? 0 }}" form="{{ $formId }}" @disabled(!$canModifyLavadoraBudgets)>
                                </td>
                                <td class="text-sm font-semibold text-slate-900">${{ number_format((float) $row['spent'], 2) }}</td>
                                <td class="text-sm font-semibold text-slate-900">${{ number_format((float) $row['remaining'], 2) }}</td>
                                <td style="min-width: 200px;">
                                    <div class="budget-track">
                                        <div class="budget-bar" style="width: {{ min((float) ($row['usage_percent'] ?? 0), 100) }}%;"></div>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-2">
                                        {{ is_null($row['usage_percent']) ? 'Sin presupuesto' : number_format((float) $row['usage_percent'], 1) . '% utilizado' }}
                                    </div>
                                </td>
                                @if($canModifyLavadoraBudgets)
                                    <td>
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.costos.budgets.upsert') }}">
                                            @csrf
                                            <input type="hidden" name="linea_id" value="{{ $row['linea']->id }}">
                                            <input type="hidden" name="year" value="{{ $budgetYear }}">
                                            <input type="hidden" name="observaciones" value="{{ $row['budget']?->observaciones }}">
                                        </form>
                                        <button type="submit" form="{{ $formId }}" class="create-action create-action--compact">
                                            <i class="fas fa-floppy-disk"></i>
                                            Guardar
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="expense-panel">
        <div class="panel-head">
            <div>
                <h3><i class="fas fa-timeline text-violet-600 mr-2"></i>Historial de modificaciones</h3>
            </div>
        </div>

        <div class="history-list">
            @forelse($history as $entry)
                <div class="history-item">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <strong>{{ $entry->catalogItem?->nombre ?? 'Concepto eliminado' }}</strong>
                            <div class="text-sm text-slate-500">
                                {{ strtoupper($entry->tipo_cambio) }} · {{ optional($entry->fecha_cambio)->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                · {{ $entry->usuario?->name ?? 'Sistema' }}
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ is_null($entry->costo_nuevo) ? 'Sin costo' : '$' . number_format((float) $entry->costo_nuevo, 2) }}
                        </div>
                    </div>
                    @if(!empty($entry->datos_anteriores))
                        <div class="text-sm text-slate-500 mt-2">
                            Costo anterior:
                            {{ is_null($entry->costo_anterior) ? 'N/D' : '$' . number_format((float) $entry->costo_anterior, 2) }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="history-item">
                    No hay historial adicional para mostrar.
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
