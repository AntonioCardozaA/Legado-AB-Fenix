@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-4">
    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs opacity-90 uppercase tracking-wider">Análisis Realizados</p>
                    <p class="text-2xl font-bold mt-1">{{ $totalAnalisis }}</p>
                </div>
                <i class="fas fa-clipboard-check text-xl opacity-80"></i>
            </div>
            <div class="mt-2 text-xs">
                <span class="opacity-90">Último: {{ $ultimoAnalisis ? $ultimoAnalisis->fecha_analisis->format('d/m/Y') : 'N/A' }}</span>
            </div>
        </div>
        
        <div class="card p-4 bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs opacity-90 uppercase tracking-wider">Componentes Buenos</p>
                    <p class="text-2xl font-bold mt-1">{{ $porcentajeBuenos }}%</p>
                </div>
                <i class="fas fa-check-circle text-xl opacity-80"></i>
            </div>
        </div>
        
        <div class="card p-4 bg-gradient-to-r from-red-500 to-red-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs opacity-90 uppercase tracking-wider">Componentes Dañados</p>
                    <p class="text-2xl font-bold mt-1">{{ $totalDanados }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-xl opacity-80"></i>
            </div>
        </div>
        
        <div class="card p-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs opacity-90 uppercase tracking-wider">Paros Pendientes</p>
                    <p class="text-2xl font-bold mt-1">{{ $parosPendientes ?? 0 }}</p>
                </div>
                <i class="fas fa-tools text-xl opacity-80"></i>
            </div>
        </div>
    </div>

    <!-- Alertas de Actividades Próximas -->
    @if(isset($alertasActividades) && count($alertasActividades) > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 text-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-2 flex-1">
                <span class="font-medium text-yellow-800">Próximos 7 días:</span>
                <span class="text-yellow-700">{{ count($alertasActividades) }} actividad(es)</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Fila de Gráficos Principales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Gráfico de Tendencia por Línea (más pequeño) -->
        <div class="card p-3 lg:col-span-2">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia Elongación</h3>
                <select id="lineaFilter" class="text-xs border rounded px-2 py-1" onchange="filtrarLineas(this.value)">
                    <option value="todas">Todas las líneas</option>
                    @foreach($lineas ?? [] as $linea)
                        <option value="{{ $linea->nombre }}">{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @if(isset($tendenciaLabels) && count($tendenciaLabels) > 0 && isset($tendenciaDatasets) && count($tendenciaDatasets) > 0)
                <div style="height: 200px;">
                    <canvas id="tendenciaLineaChart"></canvas>
                </div>
            @else
                <div class="flex items-center justify-center h-40 bg-gray-50 rounded text-sm text-gray-500">
                    Sin datos suficientes
                </div>
            @endif
        </div>
        
        <!-- Gráfico de Estado de Componentes (circular) -->
        <div class="card p-3">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-2">Estado Componentes</h3>
            @if(isset($estadoComponentesLabels) && count($estadoComponentesLabels) > 0)
                <div style="height: 200px;">
                    <canvas id="estadoComponentesChart"></canvas>
                </div>
            @else
                <div class="flex items-center justify-center h-40 bg-gray-50 rounded text-sm text-gray-500">
                    Sin datos este mes
                </div>
            @endif
        </div>
    </div>

    <!-- Gráfico de Daños por Semana (ancho completo) -->
    <div class="card p-4">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia de Daños</h3>
            <div class="flex space-x-2">
                <button onclick="cambiarPeriodo('4semanas', this)" class="periodo-btn active px-3 py-1 text-xs bg-blue-600 text-white rounded">4 Sem</button>
                <button onclick="cambiarPeriodo('12semanas', this)" class="periodo-btn px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">12 Sem</button>
                <button onclick="cambiarPeriodo('52semanas', this)" class="periodo-btn px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">52 Sem</button>
            </div>
        </div>
        @if(isset($danosTendenciaData))
            <div style="height: 250px;">
                <canvas id="danosTendenciaChart"></canvas>
            </div>
        @else
            <div class="flex items-center justify-center h-40 bg-gray-50 rounded text-sm text-gray-500">
                No hay datos disponibles
            </div>
        @endif
    </div>

    <!-- Tabla de Análisis Recientes (compacta) -->
    <div class="card p-4">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Análisis Recientes</h3>
            <a href="{{ route('analisis-lavadora.index') }}" class="text-xs text-blue-600 hover:text-blue-800">
                Ver todos <i class="fas fa-arrow-right ml-1 text-xs"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto -mx-4 px-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Elongación</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($analisisRecientes->take(5) as $analisis)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $analisis->fecha_analisis->format('d/m') }}</td>
                        <td class="px-3 py-2 font-medium">{{ $analisis->linea->nombre }}</td>
                        <td class="px-3 py-2">{{ Str::limit($analisis->numero_orden, 8) }}</td>
                        <td class="px-3 py-2">
                            @if($analisis->elongacion_promedio)
                                @php
                                    $color = $analisis->elongacion_promedio > 178.19 ? 'text-red-600' : 
                                            ($analisis->elongacion_promedio > 176 ? 'text-yellow-600' : 'text-green-600');
                                @endphp
                                <span class="{{ $color }} font-medium text-xs">
                                    {{ number_format($analisis->elongacion_promedio, 1) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($analisis->tipo == 'lavadora')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                    {{ $analisis->estado == 'BUENO' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $analisis->estado }}
                                </span>
                            @else
                                @php
                                    $estado = $analisis->elongacion_promedio > 178.19 ? 'CRÍTICO' : 
                                             ($analisis->elongacion_promedio > 176 ? 'ATENCIÓN' : 'NORMAL');
                                    $estadoClass = $analisis->elongacion_promedio > 178.19 ? 'bg-red-100 text-red-800' : 
                                                  ($analisis->elongacion_promedio > 176 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $estadoClass }}">
                                    {{ $estado }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ $analisis->ruta_show ?? route('analisis.show', $analisis) }}" 
                               class="text-blue-600 hover:text-blue-800 text-xs">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-gray-500 text-sm">
                            No hay análisis recientes
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
let tendenciaLineaChart;
let estadoComponentesChart;
let danosTendenciaChart;

document.addEventListener('DOMContentLoaded', function() {
    
    // ===========================================
    // GRÁFICO DE TENDENCIA POR LÍNEA (más compacto)
    // ===========================================
    @if(isset($tendenciaLabels) && count($tendenciaLabels) > 0 && isset($tendenciaDatasets) && count($tendenciaDatasets) > 0)
    const tendenciaCtx = document.getElementById('tendenciaLineaChart').getContext('2d');
    tendenciaLineaChart = new Chart(tendenciaCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($tendenciaLabels) !!},
            datasets: {!! json_encode($tendenciaDatasets) !!}
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 8,
                        font: { size: 10 }
                    }
                },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: { font: { size: 9 } },
                    title: { display: false }
                },
                x: {
                    ticks: { font: { size: 9 }, maxRotation: 0 }
                }
            }
        }
    });
    @endif

    // ===========================================
    // GRÁFICO DE ESTADO DE COMPONENTES (circular)
    // ===========================================
    @if(isset($estadoComponentesLabels) && count($estadoComponentesLabels) > 0)
    const estadoCtx = document.getElementById('estadoComponentesChart').getContext('2d');
    
    // Generar colores automáticos
    const colores = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
        '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
    ];
    
    estadoComponentesChart = new Chart(estadoCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($estadoComponentesLabels) !!},
            datasets: [{
                data: {!! json_encode($estadoComponentesData) !!},
                backgroundColor: colores.slice(0, {!! json_encode($estadoComponentesLabels) !!}.length),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        font: { size: 9 }
                    }
                }
            },
            cutout: '65%'
        }
    });
    @endif

    // ===========================================
    // GRÁFICO DE TENDENCIA DE DAÑOS
    // ===========================================
    @if(isset($danosTendenciaData))
    const danosCtx = document.getElementById('danosTendenciaChart').getContext('2d');
    danosTendenciaChart = new Chart(danosCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($danosTendenciaData['4semanas']['labels']) !!},
            datasets: [{
                label: 'Daños',
                data: {!! json_encode($danosTendenciaData['4semanas']['data']) !!},
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { font: { size: 9 } }
                },
                x: {
                    ticks: { font: { size: 9 }, maxRotation: 0 }
                }
            }
        }
    });
    @endif
});

// Filtrar líneas en el gráfico de tendencia
function filtrarLinea(linea) {
    if (!tendenciaLineaChart) return;
    
    tendenciaLineaChart.data.datasets.forEach(dataset => {
        dataset.hidden = (linea !== 'todas' && dataset.label !== linea);
    });
    tendenciaLineaChart.update();
}

// Cambiar período de tendencia de daños
function cambiarPeriodo(periodo, btn) {
    if (!danosTendenciaChart) return;

    // Actualizar botones
    document.querySelectorAll('.periodo-btn').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white');
        b.classList.add('bg-gray-100', 'text-gray-700');
    });
    btn.classList.remove('bg-gray-100', 'text-gray-700');
    btn.classList.add('bg-blue-600', 'text-white');

    // Cargar datos
    fetch(`/api/analisis/danos-tendencia?periodo=${periodo}`)
        .then(res => res.json())
        .then(data => {
            danosTendenciaChart.data.labels = data.labels;
            danosTendenciaChart.data.datasets[0].data = data.data;
            danosTendenciaChart.update();
        })
        .catch(err => console.error('Error:', err));
}
</script>

<style>
.periodo-btn {
    transition: all 0.2s;
}
.card {
    @apply bg-white rounded-lg shadow-sm;
}
</style>
@endsection