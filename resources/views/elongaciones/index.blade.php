@extends('layouts.app')

@section('title', 'Historial de Elongaciones')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('lavadora.dashboard') }}" 
               class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300
                      group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-history text-blue-600"></i>
                    Historial de Elongaciones
                </h1>
                <p class="text-gray-600 mt-1">
                    Registros de elongación de cadena - Límite de cambio: <span class="font-bold text-red-600">2.4%</span>
                </p>
            </div>
            <a href="{{ route('elongaciones.create') }}" 
               class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                <i class="fas fa-plus-circle"></i>
                Nuevo Registro
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" action="{{ route('elongaciones.index') }}" class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    {{-- Filtro por Línea --}}
                    <div class="flex-1">
                        <label for="linea" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-industry mr-1 text-blue-500"></i>
                            Línea:
                        </label>
                        <select name="linea" id="linea" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Todas las líneas</option>
                            @php
                                $lineas = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];
                            @endphp
                            @foreach($lineas as $linea)
                                <option value="{{ $linea }}" {{ request('linea') == $linea ? 'selected' : '' }}>
                                    {{ $linea }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filtro por Estado --}}
                    <div class="flex-1">
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-chart-line mr-1 text-blue-500"></i>
                            Estado:
                        </label>
                        <select name="estado" id="estado" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Todos los estados</option>
                            <option value="normal" {{ request('estado') == 'normal' ? 'selected' : '' }}>Normal (&lt;2.0%)</option>
                            <option value="alerta" {{ request('estado') == 'alerta' ? 'selected' : '' }}>Alerta (2.0% - 2.4%)</option>
                            <option value="critico" {{ request('estado') == 'critico' ? 'selected' : '' }}>Crítico (≥2.4%)</option>
                        </select>
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-filter mr-1"></i>
                            Filtrar
                        </button>
                        @if(request('linea') || request('estado'))
                            <a href="{{ route('elongaciones.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-times"></i>
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
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
            
            @php
                $promBombas = $elongaciones->avg('bombas_porcentaje');
                $promVapor = $elongaciones->avg('vapor_porcentaje');
            @endphp
            
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Prom. Bombas</p>
                        <p class="text-2xl font-bold {{ $promBombas >= 2.4 ? 'text-red-600' : ($promBombas >= 2.0 ? 'text-amber-600' : 'text-green-600') }}">
                            {{ $promBombas ? number_format($promBombas, 2) . '%' : '0.00%' }}
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
                        <p class="text-2xl font-bold {{ $promVapor >= 2.4 ? 'text-red-600' : ($promVapor >= 2.0 ? 'text-amber-600' : 'text-green-600') }}">
                            {{ $promVapor ? number_format($promVapor, 2) . '%' : '0.00%' }}
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
                        <p class="text-sm text-gray-600">Críticos (≥2.4%)</p>
                        <p class="text-2xl font-bold text-red-600">
                            {{ $elongaciones->filter(function($e) { return $e->requiere_cambio; })->count() }}
                        </p>
                    </div>
                    <div class="p-2 bg-red-50 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de registros --}}
    @if($elongaciones->count() > 0)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Línea</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hodómetro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bombas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vapor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($elongaciones as $registro)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $registro->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                        {{ $registro->linea }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($registro->hodometro, 0) }} h
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium {{ $registro->bombas_porcentaje >= 2.4 ? 'text-red-600' : ($registro->bombas_porcentaje >= 2.0 ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ number_format($registro->bombas_porcentaje, 2) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium {{ $registro->vapor_porcentaje >= 2.4 ? 'text-red-600' : ($registro->vapor_porcentaje >= 2.0 ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ number_format($registro->vapor_porcentaje, 2) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $estado = $registro->requiere_cambio ? 'critico' : ($registro->bombas_porcentaje >= 2.0 || $registro->vapor_porcentaje >= 2.0 ? 'alerta' : 'normal');
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @if($estado == 'critico') bg-red-100 text-red-800
                                        @elseif($estado == 'alerta') bg-amber-100 text-amber-800
                                        @else bg-green-100 text-green-800
                                        @endif">
                                        @if($estado == 'critico')
                                            Crítico
                                        @elseif($estado == 'alerta')
                                            Alerta
                                        @else
                                            Normal
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('elongaciones.show', $registro) }}" 
                                           class="text-blue-600 hover:text-blue-900" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('elongaciones.destroy', $registro) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('¿Eliminar este registro permanentemente?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar">
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
            
            {{-- Paginación --}}
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $elongaciones->links() }}
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i class="fas fa-chart-line text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay registros</h3>
            <p class="text-gray-500 mb-6">Comience registrando una nueva medición de elongación</p>
            <a href="{{ route('elongaciones.create') }}" 
               class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus-circle"></i>
                Nuevo Registro
            </a>
        </div>
    @endif

    {{-- Leyenda --}}
    <div class="mt-4 bg-white rounded-lg p-4 border border-gray-200">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span class="text-sm font-medium text-gray-700">Límite de cambio de cadena: 2.4% de elongación</span>
        </div>
        <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <span class="text-xs text-gray-600">Normal: &lt; 2.0%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-amber-500 rounded-full"></span>
                <span class="text-xs text-gray-600">Alerta: 2.0% - 2.4%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                <span class="text-xs text-gray-600">Crítico: ≥ 2.4% (requiere cambio)</span>
            </div>
        </div>
    </div>
</div>
@endsection