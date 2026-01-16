<!-- resources/views/reportes/componentes.blade.php -->
@extends('layouts.app')

@section('title', 'Reporte de Componentes')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Reporte de Componentes</h1>
                <p class="text-gray-600">Estado y análisis de componentes por línea</p>
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

    <!-- Filtros -->
    <div class="card p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodo</label>
                <select name="periodo" class="w-full rounded-lg border-gray-300">
                    <option value="1mes" {{ request('periodo') == '1mes' ? 'selected' : '' }}>Último mes</option>
                    <option value="3meses" {{ request('periodo') == '3meses' ? 'selected' : '' }}>Últimos 3 meses</option>
                    <option value="6meses" {{ request('periodo') == '6meses' ? 'selected' : '' }}>Últimos 6 meses</option>
                    <option value="1anio" {{ request('periodo') == '1anio' ? 'selected' : '' }}>Último año</option>
                </select>
            </div>
            
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Componente</label>
                <select name="componente" class="w-full rounded-lg border-gray-300">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Componente::all() as $componente)
                    <option value="{{ $componente->id }}" {{ request('componente') == $componente->id ? 'selected' : '' }}>
                        {{ $componente->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Gráfico de Estado -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Distribución por Estado</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <canvas id="estadoComponentesChart" height="200"></canvas>
            </div>
            <div>
                <div class="space-y-4">
                    @php
                        $totalComponentes = collect($reporte)->sum('revisado');
                        $estados = ['BUENO' => 0, 'REGULAR' => 0, 'DAÑADO' => 0, 'REEMPLAZADO' => 0];
                        
                        foreach($reporte as $item) {
                            foreach($item['estados'] as $estado => $cantidad) {
                                if(isset($estados[$estado])) {
                                    $estados[$estado] += $cantidad;
                                }
                            }
                        }
                    @endphp
                    
                    @foreach($estados as $estado => $cantidad)
                    @php
                        $porcentaje = $totalComponentes > 0 ? ($cantidad / $totalComponentes) * 100 : 0;
                        $colors = [
                            'BUENO' => 'bg-green-100 text-green-800',
                            'REGULAR' => 'bg-yellow-100 text-yellow-800',
                            'DAÑADO' => 'bg-red-100 text-red-800',
                            'REEMPLAZADO' => 'bg-blue-100 text-blue-800',
                        ];
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $estado }}</span>
                            <span class="text-sm font-medium text-gray-700">{{ $cantidad }} ({{ number_format($porcentaje, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full 
                                {{ $estado == 'BUENO' ? 'bg-green-600' : 
                                   ($estado == 'REGULAR' ? 'bg-yellow-600' : 
                                   ($estado == 'DAÑADO' ? 'bg-red-600' : 'bg-blue-600')) }}" 
                                style="width: {{ $porcentaje }}%">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Componentes -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Componente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total / Revisados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buen Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Regular</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dañados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reemplazados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% Revisión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($reporte as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $item['componente'] }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <span class="font-medium">{{ $item['total'] }}</span> / 
                                <span class="{{ $item['revisado'] < $item['total'] ? 'text-yellow-600' : 'text-green-600' }}">
                                    {{ $item['revisado'] }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                {{ $item['estados']['BUENO'] ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                {{ $item['estados']['REGULAR'] ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                                {{ $item['estados']['DAÑADO'] ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                {{ $item['estados']['REEMPLAZADO'] ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="h-2 rounded-full 
                                        {{ $item['porcentaje_revisado'] < 50 ? 'bg-red-600' : 
                                           ($item['porcentaje_revisado'] < 80 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                                        style="width: {{ $item['porcentaje_revisado'] }}%">
                                    </div>
                                </div>
                                <span class="text-sm font-medium">{{ number_format($item['porcentaje_revisado'], 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Resumen General</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Componentes:</span>
                    <span class="font-medium">{{ collect($reporte)->sum('total') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Componentes Revisados:</span>
                    <span class="font-medium">{{ collect($reporte)->sum('revisado') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Porcentaje Total Revisión:</span>
                    <span class="font-medium">
                        @php
                            $total = collect($reporte)->sum('total');
                            $revisado = collect($reporte)->sum('revisado');
                            $porcentajeTotal = $total > 0 ? ($revisado / $total) * 100 : 0;
                        @endphp
                        {{ number_format($porcentajeTotal, 1) }}%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Componentes con Mayor Daño</h3>
            <div class="space-y-2">
                @php
                    $componentesDanados = collect($reporte)->sortByDesc(function($item) {
                        return ($item['estados']['DAÑADO'] ?? 0) + ($item['estados']['REEMPLAZADO'] ?? 0);
                    })->take(3);
                @endphp
                @foreach($componentesDanados as $item)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-700 truncate">{{ $item['componente'] }}</span>
                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                        {{ ($item['estados']['DAÑADO'] ?? 0) + ($item['estados']['REEMPLAZADO'] ?? 0) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Componentes con Menor Revisión</h3>
            <div class="space-y-2">
                @php
                    $componentesBajaRevision = collect($reporte)->where('porcentaje_revisado', '<', 80)->sortBy('porcentaje_revisado')->take(3);
                @endphp
                @foreach($componentesBajaRevision as $item)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-700 truncate">{{ $item['componente'] }}</span>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                        {{ number_format($item['porcentaje_revisado'], 1) }}%
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Gráfico de estado de componentes
const estadoCtx = document.getElementById('estadoComponentesChart').getContext('2d');
const estadoComponentesChart = new Chart(estadoCtx, {
    type: 'doughnut',
    data: {
        labels: ['Buen Estado', 'Regular', 'Dañados', 'Reemplazados'],
        datasets: [{
            data: [
                {{ $estados['BUENO'] }},
                {{ $estados['REGULAR'] }},
                {{ $estados['DAÑADO'] }},
                {{ $estados['REEMPLAZADO'] }}
            ],
            backgroundColor: [
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#3b82f6'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function exportarExcel() {
    window.location.href = "{{ route('analisis.exportar.excel') }}?tipo=componentes&{{ http_build_query(request()->query()) }}";
}

function imprimirReporte() {
    window.print();
}
</script>
@endpush