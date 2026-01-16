<!-- resources/views/reportes/elongacion.blade.php -->
@extends('layouts.app')

@section('title', 'Reporte de Elongación')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Reporte de Elongación</h1>
                <p class="text-gray-600">Mediciones y análisis de elongación de cadenas</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="exportarExcel()" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </button>
                <button onclick="imprimirReporte()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i> Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- Gráfico de Elongación -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Tendencia de Elongación</h2>
        <canvas id="elongacionChart" height="150"></canvas>
    </div>

    <!-- Tabla de Datos -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última Medición</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Elongación (mm)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% Elongación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horómetro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($datos as $item)
                    @php
                        $porcentaje = (($item['elongacion'] - 173) / 173) * 100;
                    @endphp
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $item['linea'] }}</td>
                        <td class="px-6 py-4">{{ $item['fecha'] }}</td>
                        <td class="px-6 py-4 font-medium">{{ number_format($item['elongacion'], 2) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <span class="mr-2">{{ number_format($porcentaje, 2) }}%</span>
                                @if($porcentaje > 3)
                                <i class="fas fa-exclamation-triangle text-red-500"></i>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ number_format($item['horometro']) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                {{ $item['estado']['bg'] }} {{ $item['estado']['color'] }}">
                                {{ $item['estado']['text'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('analisis.porLinea', ['linea' => str_replace('L-', '', $item['linea'])]) }}" 
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-history"></i> Historial
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resumen Estadístico -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Resumen</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Líneas analizadas:</span>
                    <span class="font-medium">{{ count($datos) }}/14</span>
                </div>
                <div class="flex justify-between">
                    <span>Promedio elongación:</span>
                    <span class="font-medium">
                        {{ number_format(collect($datos)->avg('elongacion') ?? 0, 2) }} mm
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Líneas en estado crítico:</span>
                    <span class="font-medium text-red-600">
                        {{ collect($datos)->where('estado.text', 'CRÍTICO')->count() }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card p-6 md:col-span-2">
            <h3 class="font-semibold text-gray-800 mb-3">Recomendaciones</h3>
            <div class="space-y-3">
                @foreach(collect($datos)->where('estado.text', 'CRÍTICO') as $critica)
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="font-medium text-red-800">{{ $critica['linea'] }} requiere atención inmediata</div>
                    <div class="text-sm text-red-600 mt-1">
                        Elongación: {{ number_format($critica['elongacion'], 2) }} mm 
                        ({{ number_format((($critica['elongacion'] - 173) / 173) * 100, 2) }}%)
                    </div>
                </div>
                @endforeach
                
                @if(collect($datos)->where('estado.text', 'CRÍTICO')->isEmpty())
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-check-circle text-green-400 text-xl mb-2"></i>
                    <p>Todas las líneas están dentro de los parámetros aceptables</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Gráfico de elongación
const ctx = document.getElementById('elongacionChart').getContext('2d');
const elongacionChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode(collect($datos)->pluck('linea')) !!},
        datasets: [{
            label: 'Elongación (mm)',
            data: {!! json_encode(collect($datos)->pluck('elongacion')) !!},
            backgroundColor: {!! json_encode(collect($datos)->map(function($item) {
                if ($item['estado']['text'] == 'CRÍTICO') return '#ef4444';
                if ($item['estado']['text'] == 'ATENCIÓN') return '#f59e0b';
                return '#10b981';
            })) !!},
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: false,
                min: 173,
                title: {
                    display: true,
                    text: 'Elongación (mm)'
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            annotation: {
                annotations: {
                    line1: {
                        type: 'line',
                        yMin: 178.19,
                        yMax: 178.19,
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        label: {
                            display: true,
                            content: 'Límite 3%',
                            position: 'end'
                        }
                    }
                }
            }
        }
    }
});

function exportarExcel() {
    // Implementar exportación a Excel
    window.location.href = "{{ route('analisis.exportar.excel') }}?tipo=elongacion";
}

function imprimirReporte() {
    window.print();
}
</script>
@endpush