@extends('layouts.app')

@section('title', 'Historial de Registros - Elongaciones')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-50 rounded-xl">
                    <i class="fas fa-history text-2xl text-blue-600"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        Historial de Registros
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Registros de elongaciones de cadena - Lavadora Línea 
                    </p>
                </div>
            </div>
            <a href="{{ route('elongaciones.create') }}" 
               class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 shadow-md">
                <i class="fas fa-plus-circle"></i>
                Nuevo Registro
            </a>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Registros</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $elongaciones->total() }}</p>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <i class="fas fa-database text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Prom. Bombas</p>
                        <p class="text-2xl font-bold text-gray-800">
                            @php
                                $promBombas = $elongaciones->avg('bombas_porcentaje');
                                echo $promBombas ? number_format($promBombas, 2) . '%' : '0.00%';
                            @endphp
                        </p>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <i class="fas fa-tint text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Prom. Vapor</p>
                        <p class="text-2xl font-bold text-gray-800">
                            @php
                                $promVapor = $elongaciones->avg('vapor_porcentaje');
                                echo $promVapor ? number_format($promVapor, 2) . '%' : '0.00%';
                            @endphp
                        </p>
                    </div>
                    <div class="p-2 bg-green-50 rounded-lg">
                        <i class="fas fa-wind text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Último Hodómetro</p>
                        <p class="text-2xl font-bold text-gray-800">
                            @if($elongaciones->count() > 0)
                                {{ number_format($elongaciones->first()->hodometro, 0) }}h
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <i class="fas fa-tachometer-alt text-gray-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-hashtag text-gray-500"></i>
                                ID
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-industry text-gray-500"></i>
                                Línea/Sección
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-tachometer-alt text-gray-500"></i>
                                Hodómetro
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-tint text-blue-500"></i>
                                Lado Bombas
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-wind text-green-500"></i>
                                Lado Vapor
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-calendar text-gray-500"></i>
                                Fecha/Hora
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-cogs text-gray-500"></i>
                                Acciones
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($elongaciones as $elongacion)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        {{-- ID --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                #{{ $elongacion->id }}
                            </span>
                        </td>

                        {{-- Línea y Sección --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-industry mr-1"></i>
                                        {{ $elongacion->linea }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">{{ $elongacion->seccion }}</p>
                            </div>
                        </td>

                        {{-- Hodómetro --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-gray-100 rounded-lg">
                                    <i class="fas fa-tachometer-alt text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ number_format($elongacion->hodometro, 0) }}h</p>
                                    <p class="text-xs text-gray-500">horas</p>
                                </div>
                            </div>
                        </td>

                        {{-- Lado Bombas --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Promedio:</span>
                                    <span class="font-semibold text-gray-900">{{ number_format($elongacion->bombas_promedio, 2) }} mm</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Porcentaje:</span>
                                    @php
                                        $bombasClass = $elongacion->bombas_porcentaje < 2 ? 'normal' : 
                                                       ($elongacion->bombas_porcentaje < 3 ? 'alerta' : 'critico');
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium estado-{{ $bombasClass }}">
                                        {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                                        @if($bombasClass === 'critico')
                                            <i class="fas fa-exclamation ml-1"></i>
                                        @endif
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-{{ $bombasClass === 'normal' ? 'green' : ($bombasClass === 'alerta' ? 'amber' : 'red') }}-500 h-1.5 rounded-full" 
                                         style="width: {{ min($elongacion->bombas_porcentaje * 33, 100) }}%"></div>
                                </div>
                            </div>
                        </td>

                        {{-- Lado Vapor --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Promedio:</span>
                                    <span class="font-semibold text-gray-900">{{ number_format($elongacion->vapor_promedio, 2) }} mm</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Porcentaje:</span>
                                    @php
                                        $vaporClass = $elongacion->vapor_porcentaje < 2 ? 'normal' : 
                                                      ($elongacion->vapor_porcentaje < 3 ? 'alerta' : 'critico');
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium estado-{{ $vaporClass }}">
                                        {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                                        @if($vaporClass === 'critico')
                                            <i class="fas fa-exclamation ml-1"></i>
                                        @endif
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-{{ $vaporClass === 'normal' ? 'green' : ($vaporClass === 'alerta' ? 'amber' : 'red') }}-500 h-1.5 rounded-full" 
                                         style="width: {{ min($elongacion->vapor_porcentaje * 33, 100) }}%"></div>
                                </div>
                            </div>
                        </td>

                        {{-- Fecha --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $elongacion->created_at->format('d/m/Y') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-clock text-gray-400"></i>
                                    <span class="text-sm text-gray-600">
                                        {{ $elongacion->created_at->format('H:i') }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Acciones --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('elongaciones.show', $elongacion) }}" 
                                   class="inline-flex items-center gap-1 px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition duration-200"
                                   title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                    <span class="hidden md:inline">Ver</span>
                                </a>
                                
                                <form action="{{ route('elongaciones.destroy', $elongacion) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('¿Está seguro de eliminar este registro? Esta acción no se puede deshacer.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="inline-flex items-center gap-1 px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition duration-200"
                                            title="Eliminar registro">
                                        <i class="fas fa-trash"></i>
                                        <span class="hidden md:inline">Eliminar</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="space-y-4">
                                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-inbox text-2xl text-gray-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay registros</h3>
                                    <p class="text-gray-600 mb-4">Aún no se han registrado elongaciones de cadena.</p>
                                    <a href="{{ route('elongaciones.create') }}" 
                                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                        <i class="fas fa-plus-circle"></i>
                                        Crear primer registro
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Paginación --}}
        @if($elongaciones->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Mostrando {{ $elongaciones->firstItem() ?? 0 }} - {{ $elongaciones->lastItem() ?? 0 }} de {{ $elongaciones->total() }} registros
                </div>
                <div>
                    {{ $elongaciones->links('vendor.pagination.tailwind') }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    /* Estados consistentes con el formulario */
    .estado-normal {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    
    .estado-alerta {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }
    
    .estado-critico {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    /* Hover effects */
    tbody tr {
        transition: all 0.2s ease;
    }
    
    tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    /* Badge styles */
    .badge-linea {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .px-6 {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
    }
</style>
@endsection