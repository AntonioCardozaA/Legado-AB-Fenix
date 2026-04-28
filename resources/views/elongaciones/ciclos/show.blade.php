@extends('layouts.app')

@section('title', 'Historial por Ciclo')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Historial del ciclo {{ $ciclo->codigo }}</h1>
            <p class="text-gray-600 mt-1">Línea {{ $ciclo->linea }} · Proveedor {{ $ciclo->proveedor }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('elongaciones.ciclos.comparacion', ['linea' => $ciclo->linea]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition">
                <i class="fas fa-code-compare"></i>
                Comparar ciclos
            </a>
            <a href="{{ route('elongaciones.index', ['linea' => $ciclo->linea, 'cadena_ciclo_id' => $ciclo->id]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
            <p class="text-sm text-gray-600">Registros</p>
            <p class="text-2xl font-bold text-gray-900">{{ $resumen['total_registros'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
            <p class="text-sm text-gray-600">Vida útil acumulada</p>
            <p class="text-2xl font-bold text-gray-900">{{ $resumen['vida_util_horas'] !== null ? number_format($resumen['vida_util_horas'], 0) . ' h' : '-' }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
            <p class="text-sm text-gray-600">Máx. bombas / vapor</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($resumen['max_bombas'] ?? 0, 2) }}% / {{ number_format($resumen['max_vapor'] ?? 0, 2) }}%</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
            <p class="text-sm text-gray-600">Último estado</p>
            <p class="text-2xl font-bold text-gray-900">{{ strtoupper($resumen['ultimo_estado'] ?? 'sin dato') }}</p>
        </div>
    </div>

    @if($ultimaMedicion)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-900">
            Última medición: {{ $ultimaMedicion->created_at->format('d/m/Y H:i') }} · Hodómetro {{ $ultimaMedicion->hodometro_formateado ?? 'sin dato' }} · Horas del ciclo {{ $ultimaMedicion->hodometro_ciclo_formateado ?? 'sin dato' }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hodómetro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horas ciclo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bombas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vapor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($elongaciones as $elongacion)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $elongacion->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $elongacion->hodometro_formateado ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $elongacion->hodometro_ciclo_formateado ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ number_format($elongacion->bombas_porcentaje, 2) }}%</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ number_format($elongacion->vapor_porcentaje, 2) }}%</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ strtoupper($elongacion->estado) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <a href="{{ route('elongaciones.show', $elongacion) }}" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">{{ $elongaciones->links() }}</div>
    </div>
</div>
@endsection
