<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card p-6 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm opacity-90">Análisis Realizados</p>
                    <p class="text-3xl font-bold mt-2">{{ $totalAnalisis }}</p>
                </div>
                <i class="fas fa-clipboard-check text-2xl opacity-80"></i>
            </div>
            <div class="mt-4 text-sm">
                <span class="opacity-90">Último: {{ $ultimoAnalisis->fecha_analisis ?? 'N/A' }}</span>
            </div>
        </div>
        
        <div class="card p-6 bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm opacity-90">Componentes en Buen Estado</p>
                    <p class="text-3xl font-bold mt-2">{{ $porcentajeBuenos }}%</p>
                </div>
                <i class="fas fa-check-circle text-2xl opacity-80"></i>
            </div>
        </div>
        
        <div class="card p-6 bg-gradient-to-r from-red-500 to-red-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm opacity-90">Componentes Dañados</p>
                    <p class="text-3xl font-bold mt-2">{{ $totalDanados }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-2xl opacity-80"></i>
            </div>
        </div>
        
        <div class="card p-6 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm opacity-90">Paros Pendientes</p>
                    <p class="text-3xl font-bold mt-2">{{ $parosPendientes }}</p>
                </div>
                <i class="fas fa-tools text-2xl opacity-80"></i>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gráfico de Tendencia por Línea -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tendencia de Elongación por Línea</h3>
            <canvas id="tendenciaLineaChart" height="250"></canvas>
        </div>
        
        <!-- Gráfico de Estado de Componentes -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Estado de Componentes (Último Mes)</h3>
            <canvas id="estadoComponentesChart" height="250"></canvas>
        </div>
        
        <!-- Gráfico de Daños por Semana -->
        <div class="card p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tendencia de Daños (4, 12, 52 semanas)</h3>
            <div class="flex space-x-4 mb-4">
                <button onclick="cambiarPeriodo('4semanas')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">4 Semanas</button>
                <button onclick="cambiarPeriodo('12semanas')" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg">12 Semanas</button>
                <button onclick="cambiarPeriodo('52semanas')" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg">52 Semanas</button>
            </div>
            <canvas id="danosTendenciaChart" height="300"></canvas>
        </div>
    </div>

    <!-- Tabla de Análisis Recientes -->
    <div class="card p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Análisis Recientes</h3>
            <a href="{{ route('analisis.index') }}" class="text-blue-600 hover:text-blue-800">
                Ver todos <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Elongación</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($analisisRecientes as $analisis)
                    <tr>
                        <td class="px-4 py-3">{{ $analisis->fecha_analisis->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $analisis->linea->nombre }}</td>
                        <td class="px-4 py-3">{{ $analisis->numero_orden }}</td>
                        <td class="px-4 py-3">
                            @php
                                $color = $analisis->elongacion_promedio > 178.19 ? 'text-red-600' : 
                                        ($analisis->elongacion_promedio > 176 ? 'text-yellow-600' : 'text-green-600');
                            @endphp
                            <span class="{{ $color }} font-medium">
                                {{ number_format($analisis->elongacion_promedio, 2) }} mm
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($analisis->elongacion_promedio > 178.19)
                            <span class="badge-estado badge-danado">CRÍTICO</span>
                            @elseif($analisis->elongacion_promedio > 176)
                            <span class="badge-estado badge-pendiente">ATENCIÓN</span>
                            @else
                            <span class="badge-estado badge-bueno">NORMAL</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('analisis.show', $analisis) }}" 
                               class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Configuración de gráficos
const tendenciaLineaCtx = document.getElementById('tendenciaLineaChart').getContext('2d');
const tendenciaLineaChart = new Chart(tendenciaLineaCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($tendenciaLabels) !!},
        datasets: {!! json_encode($tendenciaDatasets) !!}
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: false,
                title: {
                    display: true,
                    text: 'Elongación (mm)'
                }
            }
        }
    }
});

// Script para cambiar período de tendencia de daños
function cambiarPeriodo(periodo) {
    // Llamada AJAX para actualizar gráfico según período
    fetch(`/api/analisis/danos-tendencia?periodo=${periodo}`)
        .then(response => response.json())
        .then(data => {
            // Actualizar gráfico
            danosTendenciaChart.data = data;
            danosTendenciaChart.update();
        });
}
</script>
@endsection