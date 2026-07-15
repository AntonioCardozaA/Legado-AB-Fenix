@extends('layouts.app')

@section('title', 'Historico de Revisados Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

@php
    $registros = method_exists($analisis, 'getCollection') ? $analisis->getCollection() : collect($analisis);
    $lineasEtiquetadora = collect($lineasEtiquetadora ?? []);
    $maquinasEtiquetadora = collect($maquinasEtiquetadora ?? \App\Support\EtiquetadoraCatalog::maquinas());
    $estadisticasHistorico = collect($estadisticasHistorico ?? []);
    $resumenHistorico = $resumenHistorico ?? [
        'total_general' => 0,
        'revisado_general' => 0,
        'pendiente_general' => 0,
        'porcentaje_general' => 0,
        'componentes_total' => 0,
        'componentes_revisados' => 0,
        'componentes_completos' => 0,
        'componentes_pendientes' => 0,
        'ultima_revision' => null,
    ];
    $lineaActual = request('linea_id');
    $maquinaActual = request('maquina');
    $totalRegistrosPagina = $registros->count();
    $totalPaginado = method_exists($analisis, 'total') ? $analisis->total() : $totalRegistrosPagina;
    $conEvidencia = $registros->filter(fn ($item) => collect($item->evidencia_fotos ?? [])->filter()->isNotEmpty())->count();

    $estadoClass = function (?string $estado): string {
        return match (true) {
            \App\Models\AnalisisEtiquetadora::esEstadoDanado($estado) => 'bg-red-100 text-red-800 border-red-200',
            \App\Models\AnalisisEtiquetadora::esEstadoDesgaste($estado) => 'bg-orange-100 text-orange-800 border-orange-200',
            \App\Models\AnalisisEtiquetadora::esEstadoRequiereRevision($estado) => 'bg-amber-100 text-amber-800 border-amber-200',
            \App\Models\AnalisisEtiquetadora::esEstadoCambiado($estado) => 'bg-blue-100 text-blue-800 border-blue-200',
            \App\Models\AnalisisEtiquetadora::esEstadoBueno($estado) => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    };
@endphp

@once
<style>
    .etq-historico-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .etq-historico-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        padding: 1rem;
        text-align: left;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
    }

    .etq-historico-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem;
        vertical-align: middle;
    }

    .etq-historico-table tbody tr:hover {
        background: #f8fafc;
    }

    .etq-component-icon {
        display: inline-flex;
        width: 3rem;
        height: 3rem;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 0.8rem;
        border: 1px solid #e2e8f0;
        background: #eff6ff;
        color: #2563eb;
        font-size: 1.25rem;
    }

    .etq-progress-text-success { color: #059669; }
    .etq-progress-text-info { color: #2563eb; }
    .etq-progress-text-warning { color: #d97706; }
    .etq-progress-text-danger { color: #dc2626; }

    .etq-chart-scroll {
        overflow-x: auto;
        padding-bottom: 0.25rem;
    }

    .etq-chart {
        display: flex;
        align-items: flex-end;
        gap: 1rem;
        min-height: 18.75rem;
        min-width: max-content;
        padding: 1.25rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.9rem;
        background:
            repeating-linear-gradient(
                to top,
                transparent,
                transparent 49px,
                rgba(15, 23, 42, 0.06) 49px,
                rgba(15, 23, 42, 0.06) 50px
            ),
            #f8fafc;
    }

    .etq-chart-col {
        display: flex;
        width: 5.5rem;
        flex: 0 0 5.5rem;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
    }

    .etq-chart-bar {
        position: relative;
        width: 3.4rem;
        height: 12.5rem;
        overflow: hidden;
        border-radius: 0.55rem 0.55rem 0 0;
        background: #e2e8f0;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
    }

    .etq-chart-fill {
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        min-height: 0;
        padding-bottom: 0.35rem;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 900;
        transition: height 0.8s ease;
    }

    .etq-chart-fill.success { background: linear-gradient(0deg, #10b981, #059669); }
    .etq-chart-fill.info { background: linear-gradient(0deg, #3b82f6, #2563eb); }
    .etq-chart-fill.warning { background: linear-gradient(0deg, #f59e0b, #d97706); }
    .etq-chart-fill.danger { background: linear-gradient(0deg, #ef4444, #dc2626); }

    .etq-chart-label {
        margin-top: 0.7rem;
        width: 100%;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1.25;
        text-align: center;
    }

    .etq-chart-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
        border-top: 1px solid #e2e8f0;
        margin-top: 1rem;
        padding-top: 1rem;
    }

    .etq-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .etq-legend-color {
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
    }

    .etq-legend-color.success { background: #10b981; }
    .etq-legend-color.info { background: #3b82f6; }
    .etq-legend-color.warning { background: #f59e0b; }
    .etq-legend-color.danger { background: #ef4444; }
</style>
@endonce

<div class="etq-page">
    <div class="etq-container" style="max-width: 88rem;">
        <header class="etq-header">
            <div class="etq-header-main">
                <a href="{{ route('analisis-etiquetadora.index', request()->only(['linea_id', 'maquina'])) }}" class="etq-back-link">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <div class="etq-accent-bar"></div>
                <div>
                    <h1 class="etq-title">HISTORICO DE REVISADOS</h1>
                    <p class="etq-subtitle">Registros de analisis de Etiquetadora por linea, maquina y componente.</p>
                </div>
            </div>

            <a href="{{ route('analisis-etiquetadora.select-linea') }}" class="create-action">
                <i class="fas fa-plus-circle"></i>
                Nuevo Analisis
            </a>
        </header>

        <section class="etq-section-card mb-6">
            <div class="border-b border-gray-200 bg-white p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-black uppercase tracking-wide text-slate-800">
                    <i class="fas fa-route text-blue-600"></i>
                    Lineas de Etiquetadora
                </h2>
                <div class="etq-line-filter">
                    <a href="{{ route('analisis-etiquetadora.historial', array_merge(request()->except(['linea_id', 'page']), ['linea_id' => null])) }}"
                       class="etq-line-filter-link {{ blank($lineaActual) ? 'active' : '' }}">
                        <i class="fas fa-layer-group"></i>
                        Todas
                    </a>
                    @foreach($lineasEtiquetadora as $linea)
                        <a href="{{ route('analisis-etiquetadora.historial', array_merge(request()->except(['linea_id', 'page']), ['linea_id' => $linea->id])) }}"
                           class="etq-line-filter-link {{ (string) $lineaActual === (string) $linea->id ? 'active' : '' }}">
                            @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'xs'])
                            {{ $linea->nombre }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-gray-100 bg-slate-50 p-5">
                <div class="lineas-title mb-3 flex items-center gap-2 text-sm font-black uppercase tracking-wide text-slate-800">
                    <i class="fas fa-compress-alt text-blue-600"></i>
                    Maquinas
                </div>
                <div class="etq-line-filter">
                    <a href="{{ route('analisis-etiquetadora.historial', request()->except(['maquina', 'page'])) }}"
                       class="etq-line-filter-link {{ blank($maquinaActual) ? 'active' : '' }}">
                        <i class="fas fa-layer-group"></i>
                        Todas
                    </a>
                    @foreach($maquinasEtiquetadora as $maquina)
                        <a href="{{ route('analisis-etiquetadora.historial', array_merge(request()->except(['maquina', 'page']), ['maquina' => $maquina])) }}"
                           class="etq-line-filter-link {{ strtoupper((string) $maquinaActual) === $maquina ? 'active' : '' }}">
                            <i class="fas fa-tag"></i>
                            Maquina {{ $maquina }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="etq-stat-card">
                <p class="etq-stat-label">Cantidad total</p>
                <p class="etq-stat-value">{{ number_format($resumenHistorico['total_general'] ?? 0) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">Piezas catalogadas en el filtro</p>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Cantidad revisada</p>
                <p class="etq-stat-value text-blue-700">{{ number_format($resumenHistorico['revisado_general'] ?? 0) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">
                    {{ number_format($resumenHistorico['componentes_completos'] ?? 0) }} completos; {{ number_format($resumenHistorico['componentes_revisados'] ?? 0) }} con avance
                </p>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Pendientes</p>
                <p class="etq-stat-value text-amber-700">{{ number_format($resumenHistorico['pendiente_general'] ?? 0) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">
                    {{ number_format($resumenHistorico['componentes_pendientes'] ?? 0) }} componentes con piezas pendientes
                </p>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Progreso general</p>
                <p class="etq-stat-value text-emerald-700">{{ $resumenHistorico['porcentaje_general'] ?? 0 }}%</p>
                <div class="etq-progress-track mt-3">
                    <div class="etq-progress-bar success" style="width: {{ max((float) ($resumenHistorico['porcentaje_general'] ?? 0), 5) }}%;">
                        {{ $resumenHistorico['porcentaje_general'] ?? 0 }}%
                    </div>
                </div>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Registros historicos</p>
                <p class="etq-stat-value text-slate-700">{{ number_format($totalPaginado) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">
                    Ultima revision: {{ $resumenHistorico['ultima_revision'] ?? '-' }}
                </p>
            </article>
        </section>

        <section class="etq-section-card mb-6">
            <div class="etq-table-section-header">
                <div class="etq-table-section-title">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Analisis de Etiquetadora</span>
                </div>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                    Tabla de avance
                </span>
            </div>

            <div class="overflow-x-auto bg-white">
                <table class="etq-historico-table">
                    <thead>
                        <tr>
                            <th>Componente</th>
                            <th>Grupo</th>
                            <th>Cantidad total</th>
                            <th>Cantidad revisada</th>
                            <th>Pendientes</th>
                            <th>Avance</th>
                            <th>Ultima revision</th>
                            <th>Usuario</th>
                            <th>Estado actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estadisticasHistorico as $item)
                            @php
                                $lineasItem = collect($item['lineas'] ?? []);
                                $maquinasItem = collect($item['maquinas'] ?? [])->map(fn ($maquina) => 'Maquina ' . $maquina);
                                $analisisParams = collect([
                                    'linea_id' => filled($lineaActual) ? $lineaActual : 'todas',
                                    'maquina' => filled($maquinaActual) ? strtoupper((string) $maquinaActual) : null,
                                    'componente' => $item['nombre'] ?? null,
                                ])->filter(fn ($value) => filled($value))->all();
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="etq-component-icon">
                                            <i class="fas fa-tags"></i>
                                        </span>
                                        <div>
                                            <div class="font-black text-slate-800">{{ $item['nombre'] }}</div>
                                            <div class="mt-1 text-xs font-semibold text-slate-500">
                                                {{ $lineasItem->join(', ') ?: 'Todas las lineas' }}
                                                @if($maquinasItem->isNotEmpty())
                                                    | {{ $maquinasItem->join(', ') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-slate-700">{{ $item['grupo'] ?: 'Sin grupo' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $item['mecanismo'] ?: 'Sin mecanismo' }}</div>
                                </td>
                                <td>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">
                                        {{ number_format($item['cantidad_total']) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-black etq-progress-text-{{ $item['color'] }}">
                                        {{ number_format($item['cantidad_revisada']) }} / {{ number_format($item['cantidad_total']) }}
                                    </span>
                                    <div class="mt-1 text-xs font-semibold text-slate-500">
                                        {{ number_format($item['componentes_completos']) }} completos; {{ number_format($item['componentes_revisados']) }} con avance
                                    </div>
                                </td>
                                <td>
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                                        {{ number_format($item['cantidad_pendiente']) }}
                                    </span>
                                </td>
                                <td style="min-width: 12rem;">
                                    <div class="etq-progress-track">
                                        <div class="etq-progress-bar {{ $item['color'] }}" style="width: {{ max((float) $item['porcentaje'], 5) }}%;">
                                            {{ $item['porcentaje'] }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="font-bold text-slate-700">{{ $item['ultima_revision'] ?? '-' }}</td>
                                <td class="font-bold text-slate-700">{{ $item['usuario_ultima_revision'] ?? '-' }}</td>
                                <td>
                                    @if($item['estado_actual'] ?? null)
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $estadoClass($item['estado_actual']) }}">
                                            {{ $item['estado_actual'] }}
                                        </span>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('analisis-etiquetadora.index', $analisisParams) }}" class="etq-mini-action primary">
                                        <i class="fas fa-eye"></i>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-10 text-center text-gray-500">
                                    No hay componentes de Etiquetadora para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($estadisticasHistorico->isNotEmpty())
            <section class="etq-section-card mb-6">
                <div class="etq-table-section-header">
                    <div class="etq-table-section-title">
                        <i class="fas fa-chart-bar"></i>
                        <span>Grafica de avance por componente</span>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                        Etiquetadora
                    </span>
                </div>

                <div class="p-5">
                    <div class="etq-chart-scroll">
                        <div class="etq-chart">
                            @foreach($estadisticasHistorico as $item)
                                @php
                                    $alturaBarra = ((float) $item['porcentaje'] / 100) * 200;
                                    $alturaVisible = $item['porcentaje'] > 0 ? max($alturaBarra, 12) : 0;
                                @endphp
                                <div class="etq-chart-col">
                                    <div class="etq-chart-bar" title="{{ $item['nombre'] }} ({{ number_format($item['cantidad_revisada']) }}/{{ number_format($item['cantidad_total']) }})">
                                        <div class="etq-chart-fill {{ $item['color'] }}" style="height: {{ $alturaVisible }}px;">
                                            @if($item['porcentaje'] > 0)
                                                <span>{{ $item['porcentaje'] }}%</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="etq-chart-label" title="{{ $item['nombre'] }}">
                                        {{ \Illuminate\Support\Str::limit($item['nombre'], 24) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 flex justify-between px-2 text-xs font-bold text-slate-500">
                        <span>0%</span>
                        <span>25%</span>
                        <span>50%</span>
                        <span>75%</span>
                        <span>100%</span>
                    </div>

                    <div class="etq-chart-legend">
                        <span class="etq-legend-item"><span class="etq-legend-color success"></span>80-100%</span>
                        <span class="etq-legend-item"><span class="etq-legend-color info"></span>50-79%</span>
                        <span class="etq-legend-item"><span class="etq-legend-color warning"></span>20-49%</span>
                        <span class="etq-legend-item"><span class="etq-legend-color danger"></span>0-19%</span>
                    </div>
                </div>
            </section>
        @endif

        <section class="etq-section-card">
            <div class="etq-table-section-header">
                <div class="etq-table-section-title">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Registros revisados</span>
                </div>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                    Etiquetadora
                </span>
            </div>

            <div class="etq-table-wrapper">
                <table class="etq-compact-table">
                    <thead>
                        <tr>
                            <th class="etq-sticky-corner" style="width: 10rem;">Fecha</th>
                            <th class="etq-sticky-top" style="width: 11rem;">Linea</th>
                            <th class="etq-sticky-top" style="width: 9rem;">Maquina</th>
                            <th class="etq-sticky-top" style="width: 18rem;">Componente</th>
                            <th class="etq-sticky-top" style="width: 12rem;">Grupo</th>
                            <th class="etq-sticky-top" style="width: 12rem;">Estado</th>
                            <th class="etq-sticky-top" style="width: 12rem;">Piezas</th>
                            <th class="etq-sticky-top" style="width: 12rem;">Usuario</th>
                            <th class="etq-sticky-top" style="width: 10rem;">Orden</th>
                            <th class="etq-sticky-top" style="width: 10rem;">Evidencia</th>
                            <th class="etq-sticky-top" style="width: 20rem;">Actividad</th>
                            <th class="etq-sticky-top" style="width: 9rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analisis as $registro)
                            @php
                                $evidencias = collect($registro->evidencia_fotos ?? [])->filter();
                                $piezasRegistro = $registro->componentes_revisados_lista;
                                $totalPiezasRegistro = $registro->total_componentes ?: (int) ($registro->componente?->cantidad_total ?? 0);
                            @endphp
                            <tr>
                                <td class="etq-sticky-left bg-white font-black text-slate-700">
                                    {{ optional($registro->fecha_analisis)->format('d/m/Y') ?? '-' }}
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @include('etiquetadora.partials.presentation-icons', ['linea' => $registro->linea, 'size' => 'xs'])
                                        <span class="font-black text-slate-800">{{ $registro->linea->nombre ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>Maquina {{ $registro->maquina }}</td>
                                <td>
                                    <div class="font-black text-slate-800">{{ $registro->componente->nombre ?? '-' }}</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-500">{{ $registro->componente->mecanismo ?? 'Sin mecanismo' }}</div>
                                </td>
                                <td>{{ $registro->componente->grupo ?? '-' }}</td>
                                <td>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $estadoClass($registro->estado) }}">
                                        {{ $registro->estado }}
                                    </span>
                                </td>
                                <td>
                                    @if(!empty($piezasRegistro))
                                        <div class="font-black text-slate-700">
                                            {{ count($piezasRegistro) }} / {{ $totalPiezasRegistro ?: count($piezasRegistro) }}
                                        </div>
                                        <div class="mt-1 text-xs font-semibold text-slate-500">
                                            #{{ collect($piezasRegistro)->join(', #') }}
                                        </div>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">Sin piezas validas</span>
                                    @endif
                                </td>
                                <td>{{ $registro->usuario->name ?? 'Usuario no registrado' }}</td>
                                <td>{{ $registro->numero_orden ?: '-' }}</td>
                                <td>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                        {{ $evidencias->count() }} {{ $evidencias->count() === 1 ? 'foto' : 'fotos' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="line-clamp-3 text-xs leading-5 text-slate-600">
                                        {{ $registro->actividad ?: 'Sin actividad registrada.' }}
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('analisis-etiquetadora.show', $registro) }}" class="etq-mini-action primary">
                                        <i class="fas fa-eye"></i>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-10 text-center text-gray-500">
                                    No hay registros revisados para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-white px-5 py-4">
                {{ $analisis->links() }}
            </div>
        </section>
    </div>
</div>
@endsection
