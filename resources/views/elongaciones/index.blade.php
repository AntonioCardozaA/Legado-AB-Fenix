@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="section-title">
            <i class="fas fa-history me-2"></i>Historial de Registros
        </h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('elongaciones.create') }}" class="btn btn-primary-custom">
            <i class="fas fa-plus-circle me-2"></i>Nuevo Registro
        </a>
    </div>
</div>

<div class="table-responsive-custom">
    <table class="table table-hover table-custom align-middle">
        <thead>
            <tr>
                <th><i class="fas fa-hashtag"></i> ID</th>
                <th><i class="fas fa-industry"></i> Línea</th>
                <th><i class="fas fa-cogs"></i> Sección</th>
                <th><i class="fas fa-tachometer-alt"></i> Hodómetro</th>
                <th><i class="fas fa-ruler-vertical"></i> Bomba Prom.</th>
                <th><i class="fas fa-percentage"></i> Bomba %</th>
                <th><i class="fas fa-ruler-vertical"></i> Vapor Prom.</th>
                <th><i class="fas fa-percentage"></i> Vapor %</th>
                <th><i class="fas fa-calendar"></i> Fecha</th>
                <th><i class="fas fa-actions"></i> Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($elongaciones as $elongacion)
            <tr>
                <td>#{{ $elongacion->id }}</td>

                <td>
                    <span class="badge badge-linea">
                        {{ $elongacion->linea }}
                    </span>
                </td>

                <td>{{ $elongacion->seccion }}</td>

                <td>
                    <span class="badge badge-hodometro">
                        {{ number_format($elongacion->hodometro, 0) }} h
                    </span>
                </td>

                <td class="fw-semibold">
                    {{ number_format($elongacion->bombas_promedio, 2) }} mm
                </td>

                <td>
                    <span class="badge porcentaje 
                        {{ $elongacion->bombas_porcentaje < 2 ? 'estado-normal' : 
                           ($elongacion->bombas_porcentaje < 3 ? 'estado-alerta' : 'estado-critico') }}">
                        {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                    </span>
                </td>

                <td class="fw-semibold">
                    {{ number_format($elongacion->vapor_promedio, 2) }} mm
                </td>

                <td>
                    <span class="badge porcentaje 
                        {{ $elongacion->vapor_porcentaje < 2 ? 'estado-normal' : 
                           ($elongacion->vapor_porcentaje < 3 ? 'estado-alerta' : 'estado-critico') }}">
                        {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                    </span>
                </td>

                <td>
                    {{ $elongacion->created_at->format('d/m/Y H:i') }}
                </td>

                <td>
                    <a href="{{ route('elongaciones.show', $elongacion) }}" 
                       class="btn btn-sm btn-info" title="Ver Detalles">
                        <i class="fas fa-eye"></i>
                    </a>

                    <form action="{{ route('elongaciones.destroy', $elongacion) }}" 
                          method="POST" class="d-inline"
                          onsubmit="return confirm('¿Eliminar este registro?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-4">
                    <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No hay registros aún</p>
                    <a href="{{ route('elongaciones.create') }}" class="btn btn-primary-custom">
                        <i class="fas fa-plus-circle me-2"></i>Crear primer registro
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($elongaciones->hasPages())
<div class="mt-4">
    {{ $elongaciones->links() }}
</div>
@endif

{{-- ===== ESTILOS ===== --}}
<style>
/* Badges generales */
.badge-linea {
    background-color: #e0f2fe;
    color: #0369a1;
    font-weight: 600;
}

.badge-hodometro {
    background-color: #1f2937;
    color: #fff;
    font-weight: 600;
}

/* Porcentajes */
.porcentaje {
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Estados (mismos del formulario) */
.estado-normal {
    background-color: #dcfce7;
    color: #166534;
}

.estado-alerta {
    background-color: #fef3c7;
    color: #92400e;
}

.estado-critico {
    background-color: #fee2e2;
    color: #991b1b;
}

/* Hover tabla */
.table-custom tbody tr:hover {
    background-color: #f8fafc;
}
</style>
@endsection
