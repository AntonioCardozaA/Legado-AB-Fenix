<!-- resources/views/reportes/index.blade.php -->
@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Reportes y Análisis</h1>
        <p class="text-gray-600">Informes detallados y análisis estadísticos</p>
    </div>

    <!-- Tarjetas de acceso rápido -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('reportes.elongacion') }}" class="card p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg mr-4">
                    <i class="fas fa-ruler-combined text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Elongación de Cadenas</h3>
                    <p class="text-sm text-gray-600 mt-1">Reporte detallado por línea</p>
                </div>
            </div>
        </a>
        
        <a href="{{ route('reportes.componentes') }}" class="card p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg mr-4">
                    <i class="fas fa-cogs text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Estado de Componentes</h3>
                    <p class="text-sm text-gray-600 mt-1">Análisis por tipo de componente</p>
                </div>
            </div>
        </a>
        
        <a href="{{ route('reportes.paros') }}" class="card p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg mr-4">
                    <i class="fas fa-tools text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Paros de Mantenimiento</h3>
                    <p class="text-sm text-gray-600 mt-1">Historial y estadísticas</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Resumen por Línea -->
    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen por Línea</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Análisis Realizados</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Último Análisis</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Elongación Actual</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($lineas as $linea)
                    @php
                        $ultimoAnalisis = $linea->analisis()->latest()->first();
                        $estado = 'N/A';
                        $color = 'gray';
                        
                        if ($ultimoAnalisis) {
                            if ($ultimoAnalisis->elongacion_promedio > 178.19) {
                                $estado = 'CRÍTICO';
                                $color = 'red';
                            } elseif ($ultimoAnalisis->elongacion_promedio > 176) {
                                $estado = 'ATENCIÓN';
                                $color = 'yellow';
                            } else {
                                $estado = 'NORMAL';
                                $color = 'green';
                            }
                        }
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $linea->nombre }}</td>
                        <td class="px-4 py-3">{{ $linea->analisis_count }}</td>
                        <td class="px-4 py-3">
                            @if($ultimoAnalisis)
                            {{ $ultimoAnalisis->fecha_analisis->format('d/m/Y') }}
                            @else
                            N/A
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($ultimoAnalisis)
                            {{ number_format($ultimoAnalisis->elongacion_promedio, 2) }} mm
                            @else
                            N/A
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($ultimoAnalisis)
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $estado }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reportes Rápidos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Reporte por Semana -->
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Análisis de la Semana</h3>
            <div class="space-y-3">
                @php
                    $inicioSemana = now()->startOfWeek();
                    $finSemana = now()->endOfWeek();
                    $analisisSemana = \App\Models\Analisis::whereBetween('fecha_analisis', [$inicioSemana, $finSemana])->get();
                @endphp
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total de análisis:</span>
                    <span class="font-medium">{{ $analisisSemana->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Líneas analizadas:</span>
                    <span class="font-medium">{{ $analisisSemana->pluck('linea_id')->unique()->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Componentes revisados:</span>
                    <span class="font-medium">{{ $analisisSemana->sum(function($a) { return $a->componentes->count(); }) }}</span>
                </div>
            </div>
        </div>
        
        <!-- Alertas -->
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Alertas Recientes</h3>
            <div class="space-y-3">
                @php
                    $alertas = \App\Models\Analisis::where('elongacion_promedio', '>', 176)
                        ->where('fecha_analisis', '>=', now()->subWeek())
                        ->with('linea')
                        ->get();
                @endphp
                @forelse($alertas as $alerta)
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                        <div>
                            <div class="font-medium">Elongación elevada en {{ $alerta->linea->nombre }}</div>
                            <div class="text-sm text-yellow-700">
                                {{ number_format($alerta->elongacion_promedio, 2) }} mm 
                                ({{ $alerta->fecha_analisis->format('d/m/Y') }})
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-check-circle text-green-400 text-xl mb-2"></i>
                    <p>No hay alertas recientes</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection