@extends('layouts.app')

@section('title', 'Analisis de Etiquetadora')

@section('content')
<link rel="stylesheet" href="{{ asset('css/diagramas-lavadoras.css') }}">
@include('etiquetadora.partials.styles')
@include('etiquetadora.analisis-etiquetadora.partials.industrial-styles')

@php
    $canDeleteAnalysis = $canDeleteAnalysis ?? (auth()->user()?->canDeleteAnalysis() ?? false);
    $lineas = $lineas ?? collect();
    $maquinas = collect($maquinas ?? ['A', 'B', 'C']);
    $grupos = collect($grupos ?? []);
    $todosComponentes = collect($todosComponentes ?? []);
    $tablaLineas = $tablaLineas ?? [];
    $estadisticas = $estadisticas ?? [];
    $estadoModalItems = $estadoModalItems ?? [];
    $lineaFiltro = (string) request('linea_id', 'todas');
    $lineaFiltro = $lineaFiltro === '' ? 'todas' : $lineaFiltro;
    $mostrarTodas = $lineaFiltro === 'todas';
    $filtrosAvanzadosActivos = request()->hasAny(['maquina', 'grupo', 'componente', 'componente_id', 'fecha', 'estado']);
@endphp

<div class="max-w-full mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <a href="{{ route('etiquetadora.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 mt-3">
                Analisis de Etiquetadora
            </h1>
        </div>

        <div class="create-actions create-actions--end">
            <a href="{{ route('etiquetadora.dashboard') }}" class="create-action create-action--secondary">
                <i class="fas fa-gauge-high"></i>
                Dashboard
            </a>
            <a href="{{ route('analisis-etiquetadora.select-linea') }}" class="create-action">
                <i class="fas fa-plus-circle"></i>
                Nuevo Analisis
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="filters-section">
        <div class="lineas-title">
            <i class="fas fa-tags"></i>
            LINEAS DE ETIQUETADORA:
            <button type="button"
                    onclick="selectLinea('todas')"
                    class="linea-item {{ $mostrarTodas ? 'active' : '' }}">
                <i class="fas fa-globe"></i>
                Todas
            </button>
        </div>

        <form method="GET" action="{{ route('analisis-etiquetadora.index') }}" id="filterForm">
            <div class="lineas-grid">
                @foreach($lineas as $linea)
                    <button type="button"
                            class="linea-item {{ $lineaFiltro === (string) $linea->id ? 'active' : '' }}"
                            onclick="selectLinea('{{ $linea->id }}')">
                        @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'xs'])
                        {{ $linea->nombre }}
                    </button>
                @endforeach

                <input type="hidden" name="linea_id" id="lineaInput" value="{{ $lineaFiltro }}">
            </div>

            <div class="filters-divider"></div>

            <div class="filters-row">
                <button type="button"
                        class="filter-link {{ $filtrosAvanzadosActivos ? 'active' : '' }}"
                        onclick="toggleAdvancedFilters()">
                    <i class="fas fa-sliders-h"></i>
                    Filtros avanzados
                    <i id="advancedFiltersIcon" class="fas {{ $filtrosAvanzadosActivos ? 'fa-chevron-up' : 'fa-chevron-down' }} ml-1"></i>
                </button>

                <button type="submit" class="btn-apply">
                    <i class="fas fa-search"></i>
                    Aplicar filtros
                </button>

                <a href="{{ route('analisis-etiquetadora.index', ['linea_id' => 'todas']) }}" class="btn-clear">
                    <i class="fas fa-times"></i>
                    Limpiar
                </a>
            </div>

            <div id="advancedFiltersPanel" class="advanced-filters-panel {{ $filtrosAvanzadosActivos ? 'show' : '' }}">
                <div class="advanced-filters-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-tags mr-1"></i> Maquina</label>
                        <select name="maquina" class="filter-select">
                            <option value="">Todas las maquinas</option>
                            @foreach($maquinas as $maquina)
                                <option value="{{ $maquina }}" @selected(request('maquina') === $maquina)>Maquina {{ $maquina }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-layer-group mr-1"></i> Grupo</label>
                        <select name="grupo" class="filter-select">
                            <option value="">Todos los grupos</option>
                            @foreach($grupos as $grupo)
                                <option value="{{ $grupo }}" @selected(request('grupo') === $grupo)>{{ $grupo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-cog mr-1"></i> Componente</label>
                        <select name="componente" class="filter-select">
                            <option value="">Todos los componentes</option>
                            @foreach($todosComponentes as $valor => $nombre)
                                <option value="{{ $valor }}" @selected(request('componente') === $valor)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="far fa-calendar-alt mr-1"></i> Mes / Anio</label>
                        <input type="month" name="fecha" value="{{ request('fecha') }}" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-clipboard-check mr-1"></i> Estado</label>
                        <select name="estado" class="filter-select">
                            <option value="">Todos los estados</option>
                            @foreach(\App\Models\AnalisisEtiquetadora::getEstadoOpciones() as $estado => $label)
                                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <button onclick='openEstadoModal("total", "Total analisis", @json($estadoModalItems["total"] ?? []))'
                class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-gray-600 hover:shadow-lg hover:bg-gray-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total analisis</p>
                    <h3 class="text-2xl font-bold text-gray-700 mt-1">{{ $estadisticas['total'] ?? 0 }}</h3>
                    <p class="text-xs text-gray-500 group-hover:text-gray-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-gray-100 text-gray-600 p-2 rounded-full"><i class="fas fa-chart-line"></i></div>
            </div>
        </button>

        <button onclick='openEstadoModal("buen_estado", "Buen Estado", @json($estadoModalItems["buen_estado"] ?? []))'
                class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-emerald-600 hover:shadow-lg hover:bg-emerald-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Buen estado</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ $estadisticas['buen_estado'] ?? 0 }}</h3>
                    <p class="text-xs text-emerald-500 group-hover:text-emerald-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-emerald-100 text-emerald-600 p-2 rounded-full group-hover:bg-emerald-200 transition"><i class="fas fa-check-circle"></i></div>
            </div>
        </button>

        <button onclick='openEstadoModal("requiere_revision", "Requiere revision", @json($estadoModalItems["requiere_revision"] ?? []))'
                class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-yellow-500 hover:shadow-lg hover:bg-yellow-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wide">Requiere revision</p>
                    <h3 class="text-2xl font-bold text-yellow-600 mt-1">{{ $estadisticas['requiere_revision'] ?? 0 }}</h3>
                    <p class="text-xs text-yellow-500 group-hover:text-yellow-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-yellow-100 text-yellow-600 p-2 rounded-full group-hover:bg-yellow-200 transition"><i class="fas fa-tools"></i></div>
            </div>
        </button>

        <button onclick='openEstadoModal("desgaste", "Severo / Moderado", @json($estadoModalItems["desgaste"] ?? []))'
                class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-orange-500 hover:shadow-lg hover:bg-orange-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Severo / Moderado</p>
                    <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $estadisticas['desgaste'] ?? 0 }}</h3>
                    <p class="text-xs text-orange-500 group-hover:text-orange-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-orange-100 text-orange-600 p-2 rounded-full group-hover:bg-orange-200 transition"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </button>

        <button onclick='openEstadoModal("danado", "Danados", @json($estadoModalItems["danado"] ?? []))'
                class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-red-600 hover:shadow-lg hover:bg-red-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">Da&ntilde;ados</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $estadisticas['danado_requiere'] ?? 0 }}</h3>
                    <p class="text-xs text-red-500 group-hover:text-red-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-red-100 text-red-600 p-2 rounded-full group-hover:bg-red-200 transition"><i class="fas fa-times-circle"></i></div>
            </div>
        </button>

        <button onclick='openEstadoModal("cambiado", "Cambiados", @json($estadoModalItems["cambiado"] ?? []))'
                class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-sky-600 hover:shadow-lg hover:bg-sky-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-sky-600 uppercase tracking-wide">Cambiados</p>
                    <h3 class="text-2xl font-bold text-sky-600 mt-1">{{ $estadisticas['cambiado'] ?? 0 }}</h3>
                    <p class="text-xs text-sky-500 group-hover:text-sky-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-sky-100 text-sky-600 p-2 rounded-full group-hover:bg-sky-200 transition"><i class="fas fa-sync-alt"></i></div>
            </div>
        </button>
    </div>


    @if($mostrarTodas)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center gap-3">
                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                <p class="text-blue-700">Vista global de Etiquetadora por linea, Maquina A, Maquina B y Maquina C.</p>
            </div>
        </div>
    @endif

    @forelse($tablaLineas as $lineaTabla)
        @include('etiquetadora.analisis-etiquetadora.partials.tabla-industrial', ['lineaTabla' => $lineaTabla])
    @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-blue-400 mb-4">
                <i class="fas fa-clipboard-list text-5xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Sistema de Analisis de Componentes</h3>
            <p class="text-gray-500 mb-6">No hay componentes de Etiquetadora para los filtros seleccionados.</p>
            <a href="{{ route('analisis-etiquetadora.select-linea') }}" class="create-action">
                <i class="fas fa-plus-circle"></i>
                Nuevo Analisis
            </a>
        </div>
    @endforelse
</div>

@include('etiquetadora.analisis-etiquetadora.partials.modals')
@include('etiquetadora.analisis-etiquetadora.partials.scripts')
@endsection
