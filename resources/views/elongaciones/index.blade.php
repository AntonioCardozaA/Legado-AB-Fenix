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
                    <div class="flex-1">
                        <label for="linea" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-industry mr-1 text-blue-500"></i>
                            Línea:
                        </label>
                        <select name="linea" id="linea" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Todas las líneas</option>
                            @php $lineas = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13']; @endphp
                            @foreach($lineas as $linea)
                                <option value="{{ $linea }}" {{ request('linea') == $linea ? 'selected' : '' }}>{{ $linea }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1">
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-chart-line mr-1 text-blue-500"></i>
                            Estado:
                        </label>
                        <select name="estado" id="estado" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Todos los estados</option>
                            <option value="normal" {{ request('estado') == 'normal' ? 'selected' : '' }}>NORMAL (&lt;1.3%)</option>
                            <option value="comprar" {{ request('estado') == 'comprar' ? 'selected' : '' }}>CONSIDERAR COMPRA (1.3% - 1.46%)</option>
                            <option value="cambio" {{ request('estado') == 'cambio' ? 'selected' : '' }}>CAMBIO REQUERIDO (≥1.46%)</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                        @if(request('linea') || request('estado'))
                            <a href="{{ route('elongaciones.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Registros</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $elongaciones->total() }}</p>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-lg"><i class="fas fa-database text-blue-600"></i></div>
                </div>
                @if(!request('linea') && !request('estado'))
                <p class="text-xs text-green-600 mt-2 flex items-center"><i class="fas fa-check-circle mr-1"></i> Mostrando último registro por línea</p>
                @endif
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Considerar Compra</p>
                        <p class="text-2xl font-bold text-yellow-600">
                            {{ $elongaciones->filter(function($e) { 
                                $max = max($e->bombas_porcentaje, $e->vapor_porcentaje);
                                return $max >= 1.3 && $max < 1.46; 
                            })->count() }}
                        </p>
                    </div>
                    <div class="p-2 bg-yellow-50 rounded-lg"><i class="fas fa-shopping-cart text-yellow-600"></i></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">1.3% - 1.46%</p>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">CAMBIO REQUERIDO</p>
                        <p class="text-2xl font-bold text-red-600">
                            {{ $elongaciones->filter(function($e) { 
                                return $e->bombas_porcentaje >= 1.46 || $e->vapor_porcentaje >= 1.46; 
                            })->count() }}
                        </p>
                    </div>
                    <div class="p-2 bg-red-50 rounded-lg"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">≥ 1.46%</p>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paso Inicial</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hodómetro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bombas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vapor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($elongaciones as $registro)
                            @php
                                $pasosIniciales = ['L-04' => 173, 'L-05' => 140, 'L-06' => 173, 'L-07' => 173, 'L-08' => 125, 'L-09' => 140, 'L-12' => 140, 'L-13' => 140];
                                $pasoInicial = $pasosIniciales[$registro->linea] ?? 173;
                                $limiteCompra = 1.3;
                                $limiteCambio = 1.46;
                                
                                $bombasCambio = $registro->bombas_porcentaje >= $limiteCambio;
                                $vaporCambio = $registro->vapor_porcentaje >= $limiteCambio;
                                $bombasCompra = $registro->bombas_porcentaje >= $limiteCompra && $registro->bombas_porcentaje < $limiteCambio;
                                $vaporCompra = $registro->vapor_porcentaje >= $limiteCompra && $registro->vapor_porcentaje < $limiteCambio;
                                
                                // Calcular ancho de barra (máximo 1.46%)
                                $bombasBarraWidth = $bombasCambio ? 100 : min(($registro->bombas_porcentaje / $limiteCambio) * 100, 100);
                                $vaporBarraWidth = $vaporCambio ? 100 : min(($registro->vapor_porcentaje / $limiteCambio) * 100, 100);
                                
                                $estadoTexto = 'NORMAL';
                                $estadoColor = 'green';
                                if ($bombasCambio || $vaporCambio) {
                                    $estadoTexto = 'CAMBIO';
                                    $estadoColor = 'red';
                                } elseif ($bombasCompra || $vaporCompra) {
                                    $estadoTexto = 'COMPRAR';
                                    $estadoColor = 'yellow';
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">{{ $registro->linea }}</span></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $pasoInicial }} mm</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                @if($registro->hodometro)
                                    {{ floor($registro->hodometro / 100) }} h {{ $registro->hodometro % 100 }} s
                                @else
                                    -
                                @endif
                                </td>
                                {{-- Lado Bombas con barra --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1 min-w-[100px]">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-{{ $bombasCambio ? 'red' : ($bombasCompra ? 'yellow' : 'green') }}-600">
                                                {{ number_format($registro->bombas_porcentaje, 2) }}%
                                            </span>
                                            @if($bombasCambio)
                                                <i class="fas fa-exclamation-circle text-red-500 ml-1" title="CAMBIO REQUERIDO (≥1.46%)"></i>
                                            @elseif($bombasCompra)
                                                <i class="fas fa-shopping-cart text-yellow-500 ml-1" title="CONSIDERAR COMPRA (≥1.3%)"></i>
                                            @endif
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $bombasCambio ? 'bg-red-500' : ($bombasCompra ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $bombasBarraWidth }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>0%</span>
                                            <span>1.3%</span>
                                            <span class="font-bold text-red-500">1.46%</span>
                                        </div>
                                    </div>
                                </td>
                                
                                {{-- Lado Vapor con barra --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1 min-w-[100px]">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-{{ $vaporCambio ? 'red' : ($vaporCompra ? 'yellow' : 'green') }}-600">
                                                {{ number_format($registro->vapor_porcentaje, 2) }}%
                                            </span>
                                            @if($vaporCambio)
                                                <i class="fas fa-exclamation-circle text-red-500 ml-1" title="CAMBIO REQUERIDO (≥1.46%)"></i>
                                            @elseif($vaporCompra)
                                                <i class="fas fa-shopping-cart text-yellow-500 ml-1" title="CONSIDERAR COMPRA (≥1.3%)"></i>
                                            @endif
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $vaporCambio ? 'bg-red-500' : ($vaporCompra ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $vaporBarraWidth }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>0%</span>
                                            <span>1.3%</span>
                                            <span class="font-bold text-red-500">1.46%</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($estadoColor == 'red')
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800"><i class="fas fa-exclamation-circle mr-1"></i>{{ $estadoTexto }}</span>
                                    @elseif($estadoColor == 'yellow')
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-shopping-cart mr-1"></i>{{ $estadoTexto }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>{{ $estadoTexto }}</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('elongaciones.show', $registro) }}" class="text-blue-600 hover:text-blue-900" title="Ver detalle"><i class="fas fa-eye"></i></a>
                                        <form action="{{ route('elongaciones.destroy', $registro) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este registro permanentemente?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">{{ $elongaciones->links() }}</div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i class="fas fa-chart-line text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay registros</h3>
            <p class="text-gray-500 mb-6">Comience registrando una nueva medición de elongación</p>
            <a href="{{ route('elongaciones.create') }}" class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus-circle"></i> Nuevo Registro</a>
        </div>
    @endif
    {{-- Leyenda --}}
    <div class="mt-4 bg-white rounded-lg p-4 border border-gray-200">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span class="text-sm font-medium text-gray-700">Límites de elongación:</span>
        </div>
        <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2"><span class="w-3 h-3 bg-green-500 rounded-full"></span><span class="text-xs text-gray-600">NORMAL: &lt; 1.3%</span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 bg-yellow-500 rounded-full"></span><span class="text-xs text-gray-600">CONSIDERAR COMPRA: 1.3% - 1.46%</span><i class="fas fa-shopping-cart text-yellow-500 text-xs ml-1"></i></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 bg-red-500 rounded-full"></span><span class="text-xs text-gray-600 font-bold">CAMBIO REQUERIDO: ≥ 1.46%</span><i class="fas fa-exclamation-circle text-red-500 text-xs ml-1"></i></div>
        </div>
        <div class="mt-3 pt-2 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-32 bg-gray-200 rounded-full h-2"><div class="bg-green-500 h-2 rounded-full" style="width: 70%"></div></div>
                <span class="text-xs text-gray-500">Barra de progreso: muestra qué tan cerca está del límite de cambio (1.46%)</span>
            </div>
            <div class="text-xs text-gray-500 mt-1"><i class="fas fa-ruler mr-1"></i>Pasos iniciales: L-04/L-06/L-07 = 173mm | L-05/L-09/L-12/L-13 = 140mm | L-08 = 125mm</div>
        </div>
    </div>
</div>
@endsection