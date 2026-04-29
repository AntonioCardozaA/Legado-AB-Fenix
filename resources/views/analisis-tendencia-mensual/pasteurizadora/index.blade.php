@extends('layouts.app')

@section('title', 'Análisis de Tendencia 52-12-4 - Pasteurizadora')

@section('content')
<style>
    .tendencia-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }
    .tendencia-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 24px;
    }
    .stat-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
    }
    .stat-value {
        font-size: 32px;
        font-weight: 700;
    }
    .trend-up { color: #10b981; }
    .trend-down { color: #ef4444; }
    .trend-neutral { color: #f59e0b; }
    .period-tab {
        cursor: pointer;
        padding: 8px 20px;
        border-radius: 999px;
        transition: all 0.3s ease;
    }
    .period-tab.active {
        background: #3b82f6;
        color: white;
    }
    .period-tab:not(.active):hover {
        background: #e2e8f0;
    }
    .chart-container {
        height: 400px;
        position: relative;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('analisis-pasteurizadora.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Análisis de Tendencia 52-12-4</h1>
                <p class="text-gray-500">Evolución de indicadores por período</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl p-6 border border-gray-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Línea</label>
                <select id="filtroLinea" class="w-full rounded-lg border-gray-300">
                    <option value="">Todas las líneas</option>
                    @foreach($lineas ?? [] as $linea)
                        <option value="{{ $linea->id }}">{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Componente</label>
                <select id="filtroComponente" class="w-full rounded-lg border-gray-300">
                    <option value="">Todos los componentes</option>
                    @foreach(\App\Models\AnalisisPasteurizadora::getComponentesPorLinea('P-03') as $key => $comp)
                        <option value="{{ $key }}">{{ $comp['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <div class="flex gap-2">
                    <button onclick="cambiarPeriodo('52')" id="tab52" class="period-tab active flex-1 text-center">52 semanas</button>
                    <button onclick="cambiarPeriodo('12')" id="tab12" class="period-tab flex-1 text-center">12 semanas</button>
                    <button onclick="cambiarPeriodo('4')" id="tab4" class="period-tab flex-1 text-center">4 semanas</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" id="statsContainer">
        <div class="stat-card">
            <div class="text-gray-500 text-sm mb-2">Valor Promedio</div>
            <div class="stat-value" id="promedioValor">--</div>
            <div class="text-xs text-gray-400 mt-2" id="promedioPeriodo">52 semanas</div>
        </div>
        <div class="stat-card">
            <div class="text-gray-500 text-sm mb-2">Tendencia</div>
            <div class="stat-value" id="tendenciaValor">--</div>
            <div class="text-xs text-gray-400 mt-2" id="tendenciaPeriodo">vs período anterior</div>
        </div>
        <div class="stat-card">
            <div class="text-gray-500 text-sm mb-2">Mejor Valor</div>
            <div class="stat-value text-green-600" id="mejorValor">--</div>
            <div class="text-xs text-gray-400 mt-2" id="mejorFecha">--</div>
        </div>
    </div>

    {{-- Gráfica --}}
    <div class="bg-white rounded-xl p-6 border border-gray-200">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-line text-blue-600"></i>
            Evolución Histórica
        </h3>
        <div class="chart-container">
            <canvas id="tendenciaChart"></canvas>
        </div>
    </div>

    {{-- Tabla de datos --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-6">
        <div class="bg-gray-50 px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Datos Históricos</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Componente</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor 52s</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor 12s</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor 4s</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="tablaDatos">
                    @forelse($analisis ?? [] as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ $item->fecha_formateada }}</td>
                        <td class="px-6 py-4 text-sm">{{ $item->linea->nombre ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">Módulo {{ $item->modulo }}</td>
                        <td class="px-6 py-4 text-sm">{{ $item->componente_nombre }}</td>
                        <td class="px-6 py-4 text-sm text-right font-mono">{{ number_format($item->valor_actual_52 ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-right font-mono">{{ number_format($item->valor_actual_12 ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-right font-mono">{{ number_format($item->valor_actual_4 ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-center">
                            <button onclick="editarValores({{ $item->id }})" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-chart-line text-3xl mb-2 block"></i>
                            No hay datos de tendencia disponibles
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal para editar valores --}}
<div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Editar Valores 52-12-4</h3>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor 52 semanas</label>
                    <input type="number" name="valor_52" id="editValor52" step="0.01" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor 12 semanas</label>
                    <input type="number" name="valor_12" id="editValor12" step="0.01" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor 4 semanas</label>
                    <input type="number" name="valor_4" id="editValor4" step="0.01" class="w-full rounded-lg border-gray-300">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart = null;
let currentPeriodo = '52';

document.addEventListener('DOMContentLoaded', function() {
    inicializarGrafica();
    
    document.getElementById('filtroLinea')?.addEventListener('change', function() {
        actualizarDatos();
    });
    
    document.getElementById('filtroComponente')?.addEventListener('change', function() {
        actualizarDatos();
    });
});

function inicializarGrafica() {
    const ctx = document.getElementById('tendenciaChart').getContext('2d');
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Valor',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: (ctx) => `Valor: ${ctx.raw}` } }
            }
        }
    });
}

function cambiarPeriodo(periodo) {
    currentPeriodo = periodo;
    
    document.getElementById('tab52').classList.remove('active');
    document.getElementById('tab12').classList.remove('active');
    document.getElementById('tab4').classList.remove('active');
    
    if (periodo === '52') document.getElementById('tab52').classList.add('active');
    else if (periodo === '12') document.getElementById('tab12').classList.add('active');
    else document.getElementById('tab4').classList.add('active');
    
    actualizarDatos();
}

function actualizarDatos() {
    const lineaId = document.getElementById('filtroLinea')?.value || '';
    const componente = document.getElementById('filtroComponente')?.value || '';
    
    fetch(`/analisis-pasteurizadora/ajax/estadisticas-tendencia?linea_id=${lineaId}&componente=${componente}&periodo=${currentPeriodo}`)
        .then(res => res.json())
        .then(data => {
            if (chart) {
                chart.data.labels = data.labels || [];
                chart.data.datasets[0].data = data.valores || [];
                chart.update();
            }
            
            document.getElementById('promedioValor').textContent = data.promedio || '--';
            document.getElementById('tendenciaValor').innerHTML = data.tendencia || '--';
            document.getElementById('mejorValor').textContent = data.mejor_valor || '--';
            document.getElementById('mejorFecha').textContent = data.mejor_fecha || '--';
        });
}

function editarValores(id) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    
    fetch(`/analisis-pasteurizadora/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('editValor52').value = data.valor_actual_52 || '';
            document.getElementById('editValor12').value = data.valor_actual_12 || '';
            document.getElementById('editValor4').value = data.valor_actual_4 || '';
            form.action = `/analisis-pasteurizadora/analisis-52124`;
            
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;
            form.appendChild(inputId);
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
}

function cerrarModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    
    const hiddenInput = document.querySelector('#editForm input[name="id"]');
    if (hiddenInput) hiddenInput.remove();
}

document.getElementById('editForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(res => res.json())
    .then(() => {
        cerrarModal();
        actualizarDatos();
        location.reload();
    });
});
</script>
@endsection
