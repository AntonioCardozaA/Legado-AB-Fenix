<!-- resources/views/paros/index.blade.php -->
@extends('layouts.app')

@section('title', 'Paros de Máquina')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Paros de Máquina</h1>
            <p class="text-gray-600">Programación y seguimiento de paros de mantenimiento</p>
        </div>
        <a href="{{ route('paros.create') }}" 
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Nuevo Paro
        </a>
    </div>

    <!-- Filtros -->
    <div class="card p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-lg border-gray-300">
                    <option value="">Todos</option>
                    <option value="Programado" {{ request('tipo') == 'Programado' ? 'selected' : '' }}>Programado</option>
                    <option value="Emergencia" {{ request('tipo') == 'Emergencia' ? 'selected' : '' }}>Emergencia</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full rounded-lg border-gray-300">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="completado">Completado</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Paros -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fechas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Planes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progreso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($paros as $paro)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $paro->linea->nombre }}</div>
                            <div class="text-sm text-gray-500">{{ $paro->linea->descripcion }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <div><strong>Inicio:</strong> {{ $paro->fecha_inicio->format('d/m/Y') }}</div>
                                <div><strong>Fin:</strong> {{ $paro->fecha_fin->format('d/m/Y') }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                {{ $paro->tipo == 'Programado' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                                {{ $paro->tipo }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <span class="font-medium">{{ $paro->planesAccion->count() }}</span> planes
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $totalPlanes = $paro->planesAccion->count();
                                $completados = $paro->planesAccion->where('estado', 'COMPLETADA')->count();
                                $porcentaje = $totalPlanes > 0 ? round(($completados / $totalPlanes) * 100) : 0;
                            @endphp
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                                </div>
                                <span class="ml-2 text-sm text-gray-600">{{ $porcentaje }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('paros.show', $paro) }}" 
                                   class="p-2 text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('paros.edit', $paro) }}" 
                                   class="p-2 text-yellow-600 hover:text-yellow-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t">
            {{ $paros->links() }}
        </div>
    </div>
</div>
@endsection