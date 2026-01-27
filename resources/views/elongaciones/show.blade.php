@extends('layouts.app')

@section('title', 'Detalle de Registro #' . $elongacion->id)

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-50 rounded-xl">
                    <i class="fas fa-file-alt text-2xl text-blue-600"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        Detalle de Registro #{{ $elongacion->id }}
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Análisis completo de elongación de cadena - Lavadora Línea 7
                    </p>
                </div>
            </div>
            <a href="{{ route('elongaciones.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200">
                <i class="fas fa-arrow-left"></i>
                Volver al listado
            </a>
        </div>
    </div>

    {{-- Card Principal --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
        
        {{-- Información General --}}
        <div class="border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                {{-- Línea --}}
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-industry text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Línea</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->linea }}</p>
                        </div>
                    </div>
                </div>

                {{-- Sección --}}
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-washer text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Sección</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->seccion }}</p>
                        </div>
                    </div>
                </div>

                {{-- Hodómetro --}}
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <i class="fas fa-tachometer-alt text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Hodómetro</p>
                            <p class="text-xl font-bold text-gray-800">{{ number_format($elongacion->hodometro, 0) }} horas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contenido --}}
        <div class="p-6">
            {{-- Tabla de Mediciones --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4">
        <i class="fas fa-ruler-combined text-blue-600"></i>
        <h2 class="text-xl font-semibold text-gray-800">
            Mediciones de Elongación
        </h2>
    </div>

    <div class="overflow-x-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Lado Bombas --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-blue-50 px-4 py-3 border-b border-blue-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-tint text-blue-600"></i>
                            <h3 class="font-semibold text-blue-800">L. BOMBAS</h3>
                        </div>
                        @php
                            $bombasEstado = $elongacion->bombas_porcentaje < 2 ? 'normal' : 
                                           ($elongacion->bombas_porcentaje < 3 ? 'alerta' : 'critico');
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-medium estado-{{ $bombasEstado }}">
                            {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                        </span>
                    </div>
                </div>
                
                <div class="p-4">
                    {{-- Mediciones individuales --}}
                    <div class="grid grid-cols-5 gap-3 mb-4">
                        @for($i = 1; $i <= 10; $i++)
                        @php
                            // Intentar obtener el valor de diferentes maneras
                            $valor = null;
                            
                            // Primero intenta con la propiedad dinámica
                            if(property_exists($elongacion, "bombas_$i") || isset($elongacion->{"bombas_$i"})) {
                                $valor = $elongacion->{"bombas_$i"};
                            }
                            // Si es nulo, verifica en los atributos
                            if(is_null($valor) && isset($elongacion->attributes["bombas_$i"])) {
                                $valor = $elongacion->attributes["bombas_$i"];
                            }
                        @endphp
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                            <div class="font-medium text-gray-800 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                {{ !is_null($valor) && $valor != '' ? number_format($valor, 1) . ' mm' : '-' }}
                            </div>
                        </div>
                        @endfor
                    </div>

                    {{-- Resumen --}}
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Promedio</p>
                                <p class="text-lg font-bold text-gray-800">
                                    {{ number_format($elongacion->bombas_promedio, 2) }} mm
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Paso inicial</p>
                                <p class="text-lg font-medium text-gray-700">173.00 mm</p>
                            </div>
                        </div>
                        
                        {{-- Barra de progreso --}}
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>0%</span>
                                <span>1%</span>
                                <span>2%</span>
                                <span>3%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-{{ $bombasEstado === 'normal' ? 'green' : ($bombasEstado === 'alerta' ? 'amber' : 'red') }}-500 h-2.5 rounded-full" 
                                     style="width: {{ min($elongacion->bombas_porcentaje * 33, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lado Vapor --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-green-50 px-4 py-3 border-b border-green-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-wind text-green-600"></i>
                            <h3 class="font-semibold text-green-800">L. VAPOR</h3>
                        </div>
                        @php
                            $vaporEstado = $elongacion->vapor_porcentaje < 2 ? 'normal' : 
                                          ($elongacion->vapor_porcentaje < 3 ? 'alerta' : 'critico');
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-medium estado-{{ $vaporEstado }}">
                            {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                        </span>
                    </div>
                </div>
                
                <div class="p-4">
                    {{-- Mediciones individuales --}}
                    <div class="grid grid-cols-5 gap-3 mb-4">
                        @for($i = 1; $i <= 10; $i++)
                        @php
                            // Intentar obtener el valor de diferentes maneras
                            $valor = null;
                            
                            // Primero intenta con la propiedad dinámica
                            if(property_exists($elongacion, "vapor_$i") || isset($elongacion->{"vapor_$i"})) {
                                $valor = $elongacion->{"vapor_$i"};
                            }
                            // Si es nulo, verifica en los atributos
                            if(is_null($valor) && isset($elongacion->attributes["vapor_$i"])) {
                                $valor = $elongacion->attributes["vapor_$i"];
                            }
                        @endphp
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                            <div class="font-medium text-gray-800 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                {{ !is_null($valor) && $valor != '' ? number_format($valor, 1) . ' mm' : '-' }}
                            </div>
                        </div>
                        @endfor
                    </div>

                    {{-- Resumen --}}
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Promedio</p>
                                <p class="text-lg font-bold text-gray-800">
                                    {{ number_format($elongacion->vapor_promedio, 2) }} mm
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Paso inicial</p>
                                <p class="text-lg font-medium text-gray-700">173.00 mm</p>
                            </div>
                        </div>
                        
                        {{-- Barra de progreso --}}
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>0%</span>
                                <span>1%</span>
                                <span>2%</span>
                                <span>3%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-{{ $vaporEstado === 'normal' ? 'green' : ($vaporEstado === 'alerta' ? 'amber' : 'red') }}-500 h-2.5 rounded-full" 
                                     style="width: {{ min($elongacion->vapor_porcentaje * 33, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

            {{-- Juego de Rodaja --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-cogs text-gray-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Juego de Rodaja - Holgura
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Línea --}}
                    <div class="border border-gray-200 rounded-xl p-4 text-center">
                        <div class="text-sm text-gray-600 mb-2">Línea</div>
                        <div class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">
                            {{ $elongacion->linea }}
                        </div>
                    </div>

                    {{-- Lado Bombas --}}
                    <div class="border border-blue-200 rounded-xl p-4 text-center bg-blue-50/30">
                        <div class="text-sm text-gray-600 mb-2">L. Bombas (mm)</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $elongacion->juego_rodaja_bombas ? number_format($elongacion->juego_rodaja_bombas, 2) . ' mm' : '-' }}
                        </div>
                    </div>

                    {{-- Lado Vapor --}}
                    <div class="border border-green-200 rounded-xl p-4 text-center bg-green-50/30">
                        <div class="text-sm text-gray-600 mb-2">L. Vapor (mm)</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ $elongacion->juego_rodaja_vapor ? number_format($elongacion->juego_rodaja_vapor, 2) . ' mm' : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alertas de Estado --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Alerta Bombas --}}
                <div class="border border-{{ $bombasEstado === 'normal' ? 'green' : ($bombasEstado === 'alerta' ? 'amber' : 'red') }}-200 rounded-xl p-4 bg-{{ $bombasEstado === 'normal' ? 'green' : ($bombasEstado === 'alerta' ? 'amber' : 'red') }}-50/30">
                    <div class="flex items-start gap-3">
                        @if($bombasEstado === 'normal')
                        <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                        @elseif($bombasEstado === 'alerta')
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl mt-1"></i>
                        @else
                        <i class="fas fa-exclamation-circle text-red-500 text-xl mt-1"></i>
                        @endif
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-800 mb-1">LADO BOMBAS</h4>
                            <p class="text-sm text-gray-600">
                                @if($bombasEstado === 'normal')
                                    <span class="font-medium text-green-600">DENTRO DEL LÍMITE PERMITIDO</span>
                                    <br>Elongación: {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                                @elseif($bombasEstado === 'alerta')
                                    <span class="font-medium text-amber-600">¡ATENCIÓN! APROXIMÁNDOSE AL LÍMITE</span>
                                    <br>Elongación: {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                                @else
                                    <span class="font-medium text-red-600">¡CRÍTICO! SUPERA EL 3%</span>
                                    <br>Se recomienda cambio inmediato de cadena
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Alerta Vapor --}}
                <div class="border border-{{ $vaporEstado === 'normal' ? 'green' : ($vaporEstado === 'alerta' ? 'amber' : 'red') }}-200 rounded-xl p-4 bg-{{ $vaporEstado === 'normal' ? 'green' : ($vaporEstado === 'alerta' ? 'amber' : 'red') }}-50/30">
                    <div class="flex items-start gap-3">
                        @if($vaporEstado === 'normal')
                        <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                        @elseif($vaporEstado === 'alerta')
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl mt-1"></i>
                        @else
                        <i class="fas fa-exclamation-circle text-red-500 text-xl mt-1"></i>
                        @endif
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-800 mb-1">LADO VAPOR</h4>
                            <p class="text-sm text-gray-600">
                                @if($vaporEstado === 'normal')
                                    <span class="font-medium text-green-600">DENTRO DEL LÍMITE PERMITIDO</span>
                                    <br>Elongación: {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                                @elseif($vaporEstado === 'alerta')
                                    <span class="font-medium text-amber-600">¡ATENCIÓN! APROXIMÁNDOSE AL LÍMITE</span>
                                    <br>Elongación: {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                                @else
                                    <span class="font-medium text-red-600">¡CRÍTICO! SUPERA EL 3%</span>
                                    <br>Se recomienda cambio inmediato de cadena
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Información Adicional --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-calendar"></i>
                        <span class="text-sm">
                            Registrado el: {{ $elongacion->created_at->format('d/m/Y H:i:s') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">ID:</span>
                        <span class="font-medium text-gray-800">#{{ $elongacion->id }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pie de página con botones --}}
        <div class="border-t border-gray-200 p-6 bg-gray-50">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="fas fa-info-circle"></i>
                    <span>Registro de mantenimiento preventivo - Elongación de cadena</span>
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="{{ route('elongaciones.create') }}" 
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 shadow-md">
                        <i class="fas fa-plus-circle"></i>
                        Nuevo Registro
                    </a>
                    
                    <form action="{{ route('elongaciones.destroy', $elongacion) }}" 
                          method="POST" 
                          class="inline"
                          onsubmit="return confirm('¿Está seguro de eliminar este registro permanentemente? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition duration-200 border border-red-200">
                            <i class="fas fa-trash"></i>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estados consistentes con el formulario */
    .estado-normal {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    
    .estado-alerta {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }
    
    .estado-critico {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .grid-cols-5 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    
    @media (max-width: 480px) {
        .grid-cols-5 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endsection