<!-- resources/views/analisis/index.blade.php -->
@extends('layouts.app')

@section('title', 'Análisis de Lavadoras')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header con filtros -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Análisis de Lavadoras</h1>
                <p class="text-gray-600">Registro y seguimiento de análisis por línea</p>
            </div>
            <a href="{{ route('analisis.create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i> Nuevo Análisis
            </a>
        </div>
        
        <!-- Filtros -->
        <div class="card mt-4 p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Línea</label>
                    <select name="linea" class="w-full rounded-lg border-gray-300">
                        <option value="">Todas</option>
                        @foreach(\App\Models\Linea::all() as $linea)
                        <option value="{{ $linea->id }}" {{ request('linea') == $linea->id ? 'selected' : '' }}>
                            {{ $linea->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" 
                           class="w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" 
                           class="w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado Elongación</label>
                    <select name="estado_elongacion" class="w-full rounded-lg border-gray-300">
                        <option value="">Todos</option>
                        <option value="normal" {{ request('estado_elongacion') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="atencion" {{ request('estado_elongacion') == 'atencion' ? 'selected' : '' }}>Atención</option>
                        <option value="critico" {{ request('estado_elongacion') == 'critico' ? 'selected' : '' }}>Crítico</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-filter mr-2"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen Estadístico -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-sm text-gray-500">Total Análisis</div>
            <div class="text-2xl font-bold text-gray-800">{{ $analisis->total() }}</div>
        </div>
        
        <div class="card p-4">
            <div class="text-sm text-gray-500">Última Semana</div>
            <div class="text-2xl font-bold text-gray-800">
                {{ \App\Models\Analisis::where('fecha_analisis', '>=', now()->subWeek())->count() }}
            </div>
        </div>
        
        <div class="card p-4">
            <div class="text-sm text-gray-500">Componentes Dañados</div>
            <div class="text-2xl font-bold text-red-600">
                {{ \App\Models\AnalisisComponente::whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])
                    ->whereHas('analisis', function($q) {
                        $q->where('fecha_analisis', '>=', now()->subMonth());
                    })->count() }}
            </div>
        </div>
        
        <div class="card p-4">
            <div class="text-sm text-gray-500">Elongación > 3%</div>
            <div class="text-2xl font-bold text-yellow-600">
                {{ \App\Models\Analisis::where('elongacion_promedio', '>', 178.19)
                    ->where('fecha_analisis', '>=', now()->subMonth())->count() }}
            </div>
        </div>
    </div>

    <!-- Tabla de Análisis -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Elongación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Componentes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($analisis as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $item->fecha_analisis->format('d/m/Y') }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $item->fecha_analisis->diffForHumans() }}
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $item->linea->nombre }}</div>
                            <div class="text-sm text-gray-500">Horómetro: {{ number_format($item->horometro) }}</div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                {{ $item->numero_orden }}
                            </span>
                        </td>
                        
                        <td class="px-6 py-4">
                            @php
                                $porcentaje = (($item->elongacion_promedio - 173) / 173) * 100;
                                $estado = $porcentaje > 3 ? 'critico' : ($porcentaje > 2 ? 'atencion' : 'normal');
                            @endphp
                            <div class="flex items-center">
                                <div class="mr-3">
                                    <div class="font-medium">{{ number_format($item->elongacion_promedio, 2) }} mm</div>
                                    <div class="text-xs text-gray-500">{{ number_format($porcentaje, 2) }}%</div>
                                </div>
                                @if($estado == 'critico')
                                <i class="fas fa-exclamation-triangle text-red-500"></i>
                                @elseif($estado == 'atencion')
                                <i class="fas fa-exclamation-circle text-yellow-500"></i>
                                @else
                                <i class="fas fa-check-circle text-green-500"></i>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <div class="flex items-center">
                                    <span class="mr-2">{{ $item->componentes->count() }} revisados</span>
                                    @if($item->componentes->whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])->count() > 0)
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                                        {{ $item->componentes->whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])->count() }} dañados
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <div>{{ $item->usuario->name }}</div>
                                <div class="text-gray-500 text-xs">{{ $item->usuario->puesto ?? 'Usuario' }}</div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('analisis.show', $item) }}" 
                                   class="p-2 text-blue-600 hover:text-blue-800" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('analisis.edit', $item) }}" 
                                   class="p-2 text-yellow-600 hover:text-yellow-800" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('analisis.destroy', $item) }}" method="POST" 
                                      onsubmit="return confirm('¿Está seguro de eliminar este análisis?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:text-red-800" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="px-6 py-4 border-t">
            {{ $analisis->links() }}
        </div>
    </div>

    <!-- Exportar -->
    <div class="mt-6 flex justify-end">
        <a href="{{ route('analisis.exportar.excel') . '?' . http_build_query(request()->query()) }}" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-file-excel mr-2"></i> Exportar a Excel
        </a>
    </div>
</div>
@endsection