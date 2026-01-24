@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow">
            <div class="card-header card-header-custom">
                <h3 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>Detalle de Registro #{{ $elongacion->id }}
                </h3>
            </div>
            
            <div class="card-body p-4">
                <!-- Información general -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-box">
                            <div class="info-label">Línea</div>
                            <div class="info-value">{{ $elongacion->linea }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <div class="info-label">Sección</div>
                            <div class="info-value">{{ $elongacion->seccion }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <div class="info-label">Hodómetro</div>
                            <div class="info-value hodometro-display">
                                {{ number_format($elongacion->hodometro, 0) }} HORAS
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de mediciones -->
                <div class="table-responsive-custom mb-5">
                    <table class="table table-bordered text-center">
                        <thead class="bg-light">
                            <tr>
                                <th colspan="9" class="bg-primary text-white">MEDICIONES DE ELONGACIÓN</th>
                            </tr>
                            <tr>
                                <th>TIPO</th>
                                <th>M1</th>
                                <th>M2</th>
                                <th>M3</th>
                                <th>M4</th>
                                <th>M5</th>
                                <th>M6</th>
                                <th>M7</th>
                                <th>M8</th>
                                <th>PROMEDIO</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Lado Bombas -->
                            <tr>
                                <td class="fw-bold bg-light">L. BOMBAS</td>
                                <td>{{ $elongacion->bombas_1 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_2 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_3 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_4 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_5 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_6 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_7 ?? '-' }}</td>
                                <td>{{ $elongacion->bombas_8 ?? '-' }}</td>
                                <td class="fw-bold">{{ number_format($elongacion->bombas_promedio, 2) }}</td>
                                <td class="{{ $elongacion->bombas_porcentaje > 3 ? 'percentage-high' : 'fw-bold' }}">
                                    {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                                </td>
                            </tr>
                            <!-- Lado Vapor -->
                            <tr>
                                <td class="fw-bold bg-light">L. VAPOR</td>
                                <td>{{ $elongacion->vapor_1 ?? '-' }}</td>
                                <td>{{ $elongacion->vapor_2 ?? '-' }}</td>
                                <td>{{ $elongacion->vapor_3 ?? '-' }}</td>
                                <td>{{ $elongacion->vapor_4 ?? '-' }}</td>
                                <td colspan="4">-</td>
                                <td class="fw-bold">{{ number_format($elongacion->vapor_promedio, 2) }}</td>
                                <td class="{{ $elongacion->vapor_porcentaje > 3 ? 'percentage-high' : 'fw-bold' }}">
                                    {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Juego de Rodaja -->
                <div class="table-responsive-custom">
                    <table class="table table-bordered text-center">
                        <thead class="bg-light">
                            <tr>
                                <th colspan="3" class="bg-success text-white">JUEGO DE RODAJA</th>
                            </tr>
                            <tr>
                                <th>LINEA</th>
                                <th>L. BOMBAS</th>
                                <th>L. VAPOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">{{ $elongacion->linea }}</td>
                                <td>{{ $elongacion->juego_rodaja_bombas ?? '-' }}</td>
                                <td>{{ $elongacion->juego_rodaja_vapor ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Información adicional -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="alert {{ $elongacion->bombas_porcentaje > 3 ? 'alert-danger' : 'alert-success' }} alert-custom">
                            <i class="fas fa-{{ $elongacion->bombas_porcentaje > 3 ? 'exclamation-circle' : 'check-circle' }} me-2"></i>
                            Lado Bombas: 
                            @if($elongacion->bombas_porcentaje > 3)
                                <strong class="percentage-high">¡SUPERA EL 3%! - CAMBIO RECOMENDADO</strong>
                            @else
                                <strong>DENTRO DEL LÍMITE PERMITIDO</strong>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert {{ $elongacion->vapor_porcentaje > 3 ? 'alert-danger' : 'alert-success' }} alert-custom">
                            <i class="fas fa-{{ $elongacion->vapor_porcentaje > 3 ? 'exclamation-circle' : 'check-circle' }} me-2"></i>
                            Lado Vapor: 
                            @if($elongacion->vapor_porcentaje > 3)
                                <strong class="percentage-high">¡SUPERA EL 3%! - CAMBIO RECOMENDADO</strong>
                            @else
                                <strong>DENTRO DEL LÍMITE PERMITIDO</strong>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Fecha de registro -->
                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Registrado el: {{ $elongacion->created_at->format('d/m/Y H:i:s') }}
                    </small>
                </div>
                
                <!-- Botones -->
                <div class="mt-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('elongaciones.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al listado
                        </a>
                        <div>
                            <a href="{{ route('elongaciones.create') }}" class="btn btn-primary-custom me-2">
                                <i class="fas fa-plus-circle me-2"></i>Nuevo Registro
                            </a>
                            <form action="{{ route('elongaciones.destroy', $elongacion) }}" 
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este registro permanentemente?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i>Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-box {
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    text-align: center;
}
.info-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 5px;
}
.info-value {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--primary-color);
}
.hodometro-display {
    font-size: 1.2rem !important;
}
</style>
@endsection