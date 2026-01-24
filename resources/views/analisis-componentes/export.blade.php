@extends('layouts.app')

@section('title', 'Exportar Análisis')

@section('content')
<div class="container-fluid">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Exportación de Análisis</h2>
            <p class="text-muted mb-0">
                Filtra y exporta los análisis por lavadora y componentes
            </p>
        </div>

        <a href="{{ route('analisis-componentes.export', request()->query()) }}"
           class="btn btn-success btn-lg shadow">
            <i class="fas fa-file-excel me-2"></i> Exportar Excel
        </a>
    </div>

    {{-- FILTROS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-semibold">
            <i class="fas fa-filter me-2"></i> Filtros de búsqueda
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('analisis-componentes.index') }}">
                <div class="row g-3">

                    {{-- Lavadora --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Lavadora</label>
                        <select name="linea_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($lineas as $linea)
                                <option value="{{ $linea->id }}"
                                    {{ request('linea_id') == $linea->id ? 'selected' : '' }}>
                                    {{ $linea->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Componente --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Componente</label>
                        <select name="componente_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($componentes as $componente)
                                <option value="{{ $componente->id }}"
                                    {{ request('componente_id') == $componente->id ? 'selected' : '' }}>
                                    {{ $componente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reductor --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Reductor</label>
                        <input type="text" name="reductor"
                               value="{{ request('reductor') }}"
                               class="form-control"
                               placeholder="Ej. R-01">
                    </div>

                    {{-- Mes --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Mes</label>
                        <input type="month" name="fecha"
                               value="{{ request('fecha') }}"
                               class="form-control">
                    </div>

                    {{-- Botones --}}
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>

                        <a href="{{ route('analisis-componentes.index') }}"
                           class="btn btn-outline-secondary w-100">
                            Limpiar
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- RESULTADOS AGRUPADOS POR LAVADORA --}}
    @forelse($analisisAgrupados as $lavadora => $items)

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <span>
                    <i class="fas fa-industry me-2"></i>
                    {{ $lavadora }}
                </span>
                <span class="badge bg-light text-dark">
                    Total: {{ $items->count() }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Componente</th>
                                <th>Reductor</th>
                                <th>Fecha</th>
                                <th>N° Orden</th>
                                <th>Actividad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->componente->nombre ?? 'N/A' }}</td>
                                    <td>{{ $item->reductor }}</td>
                                    <td>{{ $item->fecha_analisis->format('d/m/Y') }}</td>
                                    <td>{{ $item->numero_orden }}</td>
                                    <td>{{ Str::limit($item->actividad, 60) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @empty
        <div class="alert alert-warning text-center">
            <i class="fas fa-info-circle me-1"></i>
            No hay análisis con los filtros seleccionados
        </div>
    @endforelse

</div>
@endsection
