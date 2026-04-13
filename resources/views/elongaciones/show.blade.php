@extends('layouts.app')

@section('title', 'Detalle de Registro')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-file-alt text-blue-600"></i>
                    Detalle de Registro
                </h1>
                <p class="text-gray-600 mt-1">
                    Análisis de elongación - Línea {{ $elongacion->linea }}
                </p>
            </div>
            <a href="{{ route('elongaciones.index', ['linea' => $elongacion->linea]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>
        </div>
    </div>

    @php
        // Definir pasos iniciales por línea
        $pasosIniciales = [
            'L-04' => 173,
            'L-05' => 140,
            'L-06' => 173,
            'L-07' => 173,
            'L-08' => 125,
            'L-09' => 140,
            'L-12' => 140,
            'L-13' => 140,
        ];
        
        $pasoInicial = $pasosIniciales[$elongacion->linea] ?? 173;
        
        // NUEVOS LÍMITES: Solo compra 1.3% y cambio 1.46%
        $limiteCompra = 1.3;
        $limiteCambio = 1.46;
        
        // Determinar estados para Bombas
        $bombasCambio = $elongacion->bombas_porcentaje >= $limiteCambio;
        $bombasCompra = $elongacion->bombas_porcentaje >= $limiteCompra && $elongacion->bombas_porcentaje < $limiteCambio;
        $bombasNormal = $elongacion->bombas_porcentaje < $limiteCompra;
        
        // Determinar estados para Vapor
        $vaporCambio = $elongacion->vapor_porcentaje >= $limiteCambio;
        $vaporCompra = $elongacion->vapor_porcentaje >= $limiteCompra && $elongacion->vapor_porcentaje < $limiteCambio;
        $vaporNormal = $elongacion->vapor_porcentaje < $limiteCompra;

        // Calcular barras de progreso - EL LÍMITE MÁXIMO ES 1.46%
        // Si supera 1.46%, la barra se muestra al 100% (completamente llena)
        $maxReferencia = $limiteCambio; // Usamos el límite de cambio como referencia máxima
        $bombasBarra = ($elongacion->bombas_porcentaje >= $limiteCambio) ? 100 : (($elongacion->bombas_porcentaje / $maxReferencia) * 100);
        $vaporBarra = ($elongacion->vapor_porcentaje >= $limiteCambio) ? 100 : (($elongacion->vapor_porcentaje / $maxReferencia) * 100);
        
        // Determinar color para las barras
        function getBarColor($porcentaje, $limiteCompra, $limiteCambio) {
            if ($porcentaje >= $limiteCambio) return 'bg-red-500';
            if ($porcentaje >= $limiteCompra) return 'bg-yellow-500';
            return 'bg-green-500';
        }
        
        $bombasBarColor = getBarColor($elongacion->bombas_porcentaje, $limiteCompra, $limiteCambio);
        $vaporBarColor = getBarColor($elongacion->vapor_porcentaje, $limiteCompra, $limiteCambio);
    @endphp

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        
        {{-- Información General --}}
        <div class="border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-5 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-industry text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Línea</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->linea }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-washer text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Sección</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->seccion }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-ruler text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Paso Inicial</p>
                            <p class="text-xl font-bold text-gray-800">{{ $pasoInicial }} mm</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <i class="fas fa-tachometer-alt text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Hodómetro</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->hodometro ? number_format($elongacion->hodometro, 0) . ' h' : '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 rounded-lg">
                            <i class="fas fa-calendar text-amber-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Registrado</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="p-6">

            {{-- ALERTA CRÍTICA BOMBAS (CAMBIO) --}}
            @if($bombasCambio)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    <div class="flex-1">
                        <h4 class="font-medium text-red-800">
                            🚨 ¡ALERTA CRÍTICA! CAMBIO DE CADENA REQUERIDO
                        </h4>
                        <p class="text-sm text-red-700 mt-1">
                            <b>LADO BOMBAS</b> supera el límite de cambio de <b>{{ $limiteCambio }}%</b>.
                        </p>
                        <p class="text-sm text-red-700 mt-1">
                            Valor actual: 
                            <b>{{ number_format($elongacion->bombas_porcentaje,2) }}%</b>
                        </p>

                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="{{ $bombasBarColor }} h-4 rounded-full transition-all duration-300" style="width: {{ $bombasBarra }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>0%</span>
                                <span class="font-bold text-yellow-600">Compra: {{ $limiteCompra }}%</span>
                                <span class="font-bold text-red-600">CAMBIO: {{ $limiteCambio }}%</span>
                                @if($elongacion->bombas_porcentaje > $limiteCambio)
                                    <span class="font-bold text-red-600">Actual: {{ number_format($elongacion->bombas_porcentaje,2) }}%</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-2 p-2 bg-red-100 rounded-lg">
                            <p class="text-sm font-medium text-red-800">
                                <i class="fas fa-tools mr-1"></i>
                                Se recomienda realizar <b>CAMBIO INMEDIATO DE CADENA</b>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ALERTA CRÍTICA VAPOR (CAMBIO) --}}
            @if($vaporCambio)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    <div class="flex-1">
                        <h4 class="font-medium text-red-800">
                            🚨 ¡ALERTA CRÍTICA! CAMBIO DE CADENA REQUERIDO
                        </h4>
                        <p class="text-sm text-red-700 mt-1">
                            <b>LADO VAPOR</b> supera el límite de cambio de <b>{{ $limiteCambio }}%</b>.
                        </p>
                        <p class="text-sm text-red-700 mt-1">
                            Valor actual: 
                            <b>{{ number_format($elongacion->vapor_porcentaje,2) }}%</b>
                        </p>

                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="{{ $vaporBarColor }} h-4 rounded-full transition-all duration-300" style="width: {{ $vaporBarra }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>0%</span>
                                <span class="font-bold text-yellow-600">Compra: {{ $limiteCompra }}%</span>
                                <span class="font-bold text-red-600">CAMBIO: {{ $limiteCambio }}%</span>
                                @if($elongacion->vapor_porcentaje > $limiteCambio)
                                    <span class="font-bold text-red-600">Actual: {{ number_format($elongacion->vapor_porcentaje,2) }}%</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-2 p-2 bg-red-100 rounded-lg">
                            <p class="text-sm font-medium text-red-800">
                                <i class="fas fa-tools mr-1"></i>
                                Se recomienda realizar <b>CAMBIO INMEDIATO DE CADENA</b>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ALERTA COMPRA BOMBAS --}}
            @if($bombasCompra)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-shopping-cart text-yellow-500 text-xl"></i>
                    <div class="flex-1">
                        <h4 class="font-medium text-yellow-800">
                            🛒 CONSIDERAR COMPRA DE CADENA
                        </h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            <b>LADO BOMBAS</b> supera el límite de compra de <b>{{ $limiteCompra }}%</b>.
                        </p>
                        <p class="text-sm text-yellow-700">
                            Valor actual: 
                            <b>{{ number_format($elongacion->bombas_porcentaje,2) }}%</b>
                        </p>

                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="{{ $bombasBarColor }} h-4 rounded-full transition-all duration-300" style="width: {{ $bombasBarra }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>0%</span>
                                <span class="font-bold text-yellow-600">Compra: {{ $limiteCompra }}%</span>
                                <span class="font-bold text-red-600">CAMBIO: {{ $limiteCambio }}%</span>
                            </div>
                        </div>

                        <div class="mt-2 p-2 bg-yellow-100 rounded-lg">
                            <p class="text-sm font-medium text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>
                                Se recomienda <b>COMPRAR CADENA</b> para preparar el próximo cambio.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ALERTA COMPRA VAPOR --}}
            @if($vaporCompra)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-shopping-cart text-yellow-500 text-xl"></i>
                    <div class="flex-1">
                        <h4 class="font-medium text-yellow-800">
                            🛒 CONSIDERAR COMPRA DE CADENA
                        </h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            <b>LADO VAPOR</b> supera el límite de compra de <b>{{ $limiteCompra }}%</b>.
                        </p>
                        <p class="text-sm text-yellow-700">
                            Valor actual: 
                            <b>{{ number_format($elongacion->vapor_porcentaje,2) }}%</b>
                        </p>

                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="{{ $vaporBarColor }} h-4 rounded-full transition-all duration-300" style="width: {{ $vaporBarra }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>0%</span>
                                <span class="font-bold text-yellow-600">Compra: {{ $limiteCompra }}%</span>
                                <span class="font-bold text-red-600">CAMBIO: {{ $limiteCambio }}%</span>
                            </div>
                        </div>

                        <div class="mt-2 p-2 bg-yellow-100 rounded-lg">
                            <p class="text-sm font-medium text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>
                                Se recomienda <b>COMPRAR CADENA</b> para preparar el próximo cambio.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Mensaje de estado normal --}}
            @if($bombasNormal && $vaporNormal)
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <div>
                        <h4 class="font-medium text-green-800">
                            ✅ ESTADO NORMAL
                        </h4>
                        <p class="text-sm text-green-700 mt-1">
                            Ambos lados se encuentran por debajo del límite de compra de <b>{{ $limiteCompra }}%</b>.
                        </p>
                        <p class="text-sm text-green-700">
                            Bombas: <b>{{ number_format($elongacion->bombas_porcentaje,2) }}%</b> | 
                            Vapor: <b>{{ number_format($elongacion->vapor_porcentaje,2) }}%</b>
                        </p>

                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="{{ $bombasBarColor }} h-3 rounded-full" style="width: {{ $bombasBarra }}%"></div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 mt-1">
                                <div class="{{ $vaporBarColor }} h-3 rounded-full" style="width: {{ $vaporBarra }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>0%</span>
                                <span>Compra: {{ $limiteCompra }}%</span>
                                <span>CAMBIO: {{ $limiteCambio }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Mediciones --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-ruler-combined text-blue-600"></i>
                    Mediciones de Elongación
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Bombas --}}
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-100 flex justify-between items-center">
                            <h3 class="font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-tint"></i>
                                LADO BOMBAS
                            </h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($bombasCambio) bg-red-100 text-red-800
                                @elseif($bombasCompra) bg-yellow-100 text-yellow-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ number_format($elongacion->bombas_porcentaje,2) }}%
                                @if($bombasCambio)
                                    <i class="fas fa-exclamation-circle ml-1"></i>
                                @elseif($bombasCompra)
                                    <i class="fas fa-shopping-cart ml-1"></i>
                                @endif
                            </span>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-5 gap-3 mb-4">
                                @for($i=1;$i<=10;$i++)
                                    @php $m = $elongacion->{'bombas_'.$i}; @endphp
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                                        <div class="font-medium p-2 bg-gray-50 rounded-lg border">
                                            {{ $m ? number_format($m,1).' mm' : '-' }}
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="border-t pt-4 flex justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Promedio</p>
                                    <p class="text-lg font-bold">
                                        {{ number_format($elongacion->bombas_promedio,2) }} mm
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Paso inicial</p>
                                    <p class="text-lg font-bold">{{ number_format($pasoInicial,2) }} mm</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Elongación</p>
                                    <p class="text-lg font-bold 
                                        @if($bombasCambio) text-red-600
                                        @elseif($bombasCompra) text-yellow-600
                                        @else text-green-600
                                        @endif">
                                        {{ number_format($elongacion->bombas_porcentaje,2) }}%
                                    </p>
                                </div>
                            </div>
                            
                            {{-- Barra de progreso en la sección de mediciones --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Progreso de elongación</div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="{{ $bombasBarColor }} h-2.5 rounded-full transition-all duration-300" style="width: {{ $bombasBarra }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>0%</span>
                                    <span>Compra: {{ $limiteCompra }}%</span>
                                    <span class="font-bold text-red-600">CAMBIO: {{ $limiteCambio }}%</span>
                                    @if($elongacion->bombas_porcentaje > $limiteCambio)
                                        <span class="font-bold text-red-600">{{ number_format($elongacion->bombas_porcentaje,2) }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Vapor --}}
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-green-50 px-4 py-3 border-b border-green-100 flex justify-between items-center">
                            <h3 class="font-semibold text-green-800 flex items-center gap-2">
                                <i class="fas fa-wind"></i>
                                LADO VAPOR
                            </h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($vaporCambio) bg-red-100 text-red-800
                                @elseif($vaporCompra) bg-yellow-100 text-yellow-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ number_format($elongacion->vapor_porcentaje,2) }}%
                                @if($vaporCambio)
                                    <i class="fas fa-exclamation-circle ml-1"></i>
                                @elseif($vaporCompra)
                                    <i class="fas fa-shopping-cart ml-1"></i>
                                @endif
                            </span>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-5 gap-3 mb-4">
                                @for($i=1;$i<=10;$i++)
                                    @php $m = $elongacion->{'vapor_'.$i}; @endphp
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                                        <div class="font-medium p-2 bg-gray-50 rounded-lg border">
                                            {{ $m ? number_format($m,1).' mm' : '-' }}
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="border-t pt-4 flex justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Promedio</p>
                                    <p class="text-lg font-bold">
                                        {{ number_format($elongacion->vapor_promedio,2) }} mm
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Paso inicial</p>
                                    <p class="text-lg font-bold">{{ number_format($pasoInicial,2) }} mm</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Elongación</p>
                                    <p class="text-lg font-bold 
                                        @if($vaporCambio) text-red-600
                                        @elseif($vaporCompra) text-yellow-600
                                        @else text-green-600
                                        @endif">
                                        {{ number_format($elongacion->vapor_porcentaje,2) }}%
                                    </p>
                                </div>
                            </div>
                            
                            {{-- Barra de progreso en la sección de mediciones --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Progreso de elongación</div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="{{ $vaporBarColor }} h-2.5 rounded-full transition-all duration-300" style="width: {{ $vaporBarra }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>0%</span>
                                    <span>Compra: {{ $limiteCompra }}%</span>
                                    <span class="font-bold text-red-600">CAMBIO: {{ $limiteCambio }}%</span>
                                    @if($elongacion->vapor_porcentaje > $limiteCambio)
                                        <span class="font-bold text-red-600">{{ number_format($elongacion->vapor_porcentaje,2) }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Juego de Rodaja --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-cogs text-gray-600"></i>
                    Juego de Rodaja - Holgura
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-blue-200 rounded-xl p-4 bg-blue-50/30">
                        <div class="text-sm text-gray-600 mb-2">LADO BOMBAS</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $elongacion->juego_rodaja_bombas ? number_format($elongacion->juego_rodaja_bombas, 2) . ' mm' : '-' }}
                        </div>
                    </div>

                    <div class="border border-green-200 rounded-xl p-4 bg-green-50/30">
                        <div class="text-sm text-gray-600 mb-2">LADO VAPOR</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ $elongacion->juego_rodaja_vapor ? number_format($elongacion->juego_rodaja_vapor, 2) . ' mm' : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Información adicional --}}
            <div class="mt-4 text-xs text-gray-400 text-right">
                <p>Registro creado: {{ $elongacion->created_at->format('d/m/Y H:i:s') }}</p>
                @if($elongacion->created_at != $elongacion->updated_at)
                    <p>Última actualización: {{ $elongacion->updated_at->format('d/m/Y H:i:s') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection