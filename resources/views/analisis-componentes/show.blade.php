@extends('layouts.app')

@section('title', 'Ver Análisis de Componente')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Análisis #{{ $analisisComponente->id }}</h4>
                    <div class="btn-group">
                        <a href="{{ route('analisis-componentes.edit', $analisisComponente) }}" 
                           class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('analisis-componentes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Listado
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información General</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 40%;">Lavadora:</th>
                                    <td>{{ $analisisComponente->linea->nombre ?? 'Lavadora ' . $analisisComponente->linea_id }}</td>
                                </tr>
                                <tr>
                                    <th>Componente:</th>
                                    <td>{{ $analisisComponente->componente->nombre ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Reductor:</th>
                                    <td>{{ $analisisComponente->reductor }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de Análisis:</th>
                                    <td>{{ $analisisComponente->fecha_analisis->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Número de Orden:</th>
                                    <td>{{ $analisisComponente->numero_orden }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Actividad Realizada</h5>
                            <div class="border p-3 bg-light rounded">
                                {{ $analisisComponente->actividad }}
                            </div>
                        </div>
                    </div>
                    
                    @if(!empty($analisisComponente->evidencia_fotos))
                        <div class="mt-4">
                            <h5>Evidencia Fotográfica</h5>
                            <div class="row">
                                @foreach($analisisComponente->evidencia_fotos as $index => $foto)
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="{{ asset('storage/' . $foto) }}" 
                                                 class="card-img-top" 
                                                 alt="Evidencia {{ $index + 1 }}"
                                                 style="height: 200px; object-fit: cover;">
                                            <div class="card-body text-center">
                                                <form action="{{ route('analisis-componentes.delete-foto', [$analisisComponente, $index]) }}" 
                                                      method="POST" 
                                                      onsubmit="return confirm('¿Eliminar esta foto?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ route('analisis-componentes.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Análisis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection