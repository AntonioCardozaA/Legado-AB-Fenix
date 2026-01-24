@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Reporte de Elongaciones</h1>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" action="{{ route('elongaciones.reporte') }}" class="row">
                <div class="col-md-4">
                    <select name="linea" class="form-control" onchange="this.form.submit()">
                        <option value="">Todas las líneas</option>
                        @foreach(['L-01', 'L-02', 'L-03', 'L-04', 'L-05', 'L-06', 'L-07'] as $lineaOpt)
                            <option value="{{ $lineaOpt }}" 
                                    {{ $linea == $lineaOpt ? 'selected' : '' }}>
                                {{ $lineaOpt }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @foreach($elongaciones->groupBy('linea') as $lineaNombre => $elongacionesLinea)
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h3>Línea {{ $lineaNombre }}</h3>
        </div>
        <div class="card-body">
            @foreach($elongacionesLinea->groupBy('seccion') as $seccion => $elongacionesSeccion)
            <h4>{{ $seccion }}</h4>
            <table class="table table-bordered table-sm mb-4">
                <thead>
                    <tr class="table-primary">
                        <th>Fecha</th>
                        <th>Hodómetro</th>
                        <th>Tipo</th>
                        @for($i = 1; $i <= 8; $i++)
                            <th>M{{ $i }}</th>
                        @endfor
                        <th>Promedio</th>
                        <th>%</th>
                        <th>Juego Rodaja</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($elongacionesSeccion->groupBy('hodometro') as $hodometro => $mediciones)
                        @foreach($mediciones->sortBy('tipo') as $elongacion)
                        <tr>
                            <td>{{ $elongacion->created_at->format('d/m/Y') }}</td>
                            <td>{{ number_format($elongacion->hodometro, 0) }}</td>
                            <td>{{ strtoupper($elongacion->tipo) }}</td>
                            @for($i = 1; $i <= 8; $i++)
                                <td>
                                    {{ $elongacion->{"medicion_$i"} ? number_format($elongacion->{"medicion_$i"}, 2) : '-' }}
                                </td>
                            @endfor
                            <td>{{ number_format($elongacion->promedio, 2) }}</td>
                            <td class="{{ $elongacion->porcentaje > 3 ? 'text-danger fw-bold' : '' }}">
                                {{ number_format($elongacion->porcentaje, 2) }}%
                            </td>
                            <td>{{ $elongacion->juego_rodaja }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            @endforeach
        </div>
    </div>
    @endforeach
    
    @if($elongaciones->isEmpty())
        <div class="alert alert-info">
            No hay registros para mostrar.
        </div>
    @endif
</div>
@endsection