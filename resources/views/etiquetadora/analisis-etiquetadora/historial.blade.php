@extends('layouts.app')

@section('title', 'Historico de Revisados Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

@php
    $registros = method_exists($analisis, 'getCollection') ? $analisis->getCollection() : collect($analisis);
    $lineasEtiquetadora = \App\Models\Linea::query()
        ->whereIn('nombre', \App\Support\EtiquetadoraCatalog::lineas())
        ->orderBy('nombre')
        ->get();
    $maquinasEtiquetadora = \App\Support\EtiquetadoraCatalog::maquinas();
    $lineaActual = request('linea_id');
    $maquinaActual = request('maquina');
    $totalRegistrosPagina = $registros->count();
    $totalPaginado = method_exists($analisis, 'total') ? $analisis->total() : $totalRegistrosPagina;
    $conEvidencia = $registros->filter(fn ($item) => collect($item->evidencia_fotos ?? [])->filter()->isNotEmpty())->count();
    $componentesUnicos = $registros->pluck('componente_id')->filter()->unique()->count();
    $maquinasResumen = collect($maquinasEtiquetadora)->map(function ($maquina) use ($registros, $totalRegistrosPagina) {
        $cantidad = $registros->where('maquina', $maquina)->count();
        $porcentaje = $totalRegistrosPagina > 0 ? round(($cantidad / $totalRegistrosPagina) * 100) : 0;

        return [
            'maquina' => $maquina,
            'cantidad' => $cantidad,
            'porcentaje' => $porcentaje,
            'color' => $porcentaje >= 50 ? 'success' : ($porcentaje >= 25 ? 'info' : ($porcentaje > 0 ? 'warning' : 'danger')),
        ];
    });

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

        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="etq-stat-card">
                <p class="etq-stat-label">Total registros</p>
                <p class="etq-stat-value">{{ $totalPaginado }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">{{ $totalRegistrosPagina }} visibles en esta pagina</p>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Con evidencia</p>
                <p class="etq-stat-value text-blue-700">{{ $conEvidencia }}</p>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Componentes</p>
                <p class="etq-stat-value text-emerald-700">{{ $componentesUnicos }}</p>
            </article>
            <article class="etq-stat-card">
                <p class="etq-stat-label">Maquinas</p>
                <p class="etq-stat-value text-slate-700">{{ $maquinasResumen->where('cantidad', '>', 0)->count() }}</p>
            </article>
        </section>

        @if($registros->isNotEmpty())
            <section class="etq-section-card mb-6">
                <div class="etq-table-section-header">
                    <div class="etq-table-section-title">
                        <i class="fas fa-chart-bar"></i>
                        <span>Avance visible por maquina</span>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                        {{ $totalRegistrosPagina }} registros
                    </span>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    @foreach($maquinasResumen as $item)
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-sm font-black text-slate-800">Maquina {{ $item['maquina'] }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                    {{ $item['cantidad'] }}
                                </span>
                            </div>
                            <div class="etq-progress-track">
                                <div class="etq-progress-bar {{ $item['color'] }}" style="width: {{ max($item['porcentaje'], 5) }}%;">
                                    {{ $item['porcentaje'] }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
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
                            <th class="etq-sticky-top" style="width: 10rem;">Orden</th>
                            <th class="etq-sticky-top" style="width: 10rem;">Evidencia</th>
                            <th class="etq-sticky-top" style="width: 20rem;">Actividad</th>
                            <th class="etq-sticky-top" style="width: 9rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analisis as $registro)
                            @php($evidencias = collect($registro->evidencia_fotos ?? [])->filter())
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
                                <td colspan="10" class="py-10 text-center text-gray-500">
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
