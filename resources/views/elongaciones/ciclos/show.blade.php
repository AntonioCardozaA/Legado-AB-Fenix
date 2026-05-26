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
            <a href="{{ route('elongaciones.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
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
            <p class="text-2xl font-bold text-gray-900">{{ \App\Support\HodometroHoras::formatear($resumen['vida_util_horas']) ?? '-' }}</p>
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
                        @php
                            $limiteCompra = 1.3;
                            $limiteCambio = 1.46;
                            $bombasCambio = $elongacion->bombas_porcentaje >= $limiteCambio;
                            $vaporCambio = $elongacion->vapor_porcentaje >= $limiteCambio;
                            $bombasCompra = $elongacion->bombas_porcentaje >= $limiteCompra && $elongacion->bombas_porcentaje < $limiteCambio;
                            $vaporCompra = $elongacion->vapor_porcentaje >= $limiteCompra && $elongacion->vapor_porcentaje < $limiteCambio;
                            $bombasBarraWidth = $bombasCambio ? 100 : min(($elongacion->bombas_porcentaje / $limiteCambio) * 100, 100);
                            $vaporBarraWidth = $vaporCambio ? 100 : min(($elongacion->vapor_porcentaje / $limiteCambio) * 100, 100);
                            $estadoTexto = $bombasCambio || $vaporCambio ? 'CAMBIO' : (($bombasCompra || $vaporCompra) ? 'COMPRA' : 'NORMAL');
                            $estadoColor = $bombasCambio || $vaporCambio ? 'red' : (($bombasCompra || $vaporCompra) ? 'yellow' : 'green');
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $elongacion->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $elongacion->hodometro_formateado ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $elongacion->hodometro_ciclo_formateado ?? '-' }}</td>
                            
                            <!-- Barra de progreso para Bombas -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1 min-w-[110px]">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-{{ $bombasCambio ? 'red' : ($bombasCompra ? 'yellow' : 'green') }}-600">
                                            {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500 {{ $bombasCambio ? 'bg-red-500' : ($bombasCompra ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                             style="width: {{ $bombasBarraWidth }}%">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Barra de progreso para Vapor -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1 min-w-[110px]">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-{{ $vaporCambio ? 'red' : ($vaporCompra ? 'yellow' : 'green') }}-600">
                                            {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500 {{ $vaporCambio ? 'bg-red-500' : ($vaporCompra ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                             style="width: {{ $vaporBarraWidth }}%">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($estadoColor === 'red')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">{{ $estadoTexto }}</span>
                                @elseif($estadoColor === 'yellow')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">{{ $estadoTexto }}</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">{{ $estadoTexto }}</span>
                                @endif
                            </td>
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
