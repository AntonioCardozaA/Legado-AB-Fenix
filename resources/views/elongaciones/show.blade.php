@extends('layouts.app')

@section('title', 'Detalle de Registro')

@section('content')
@php
    $limiteCompra = 1.3;
    $limiteCambio = 1.46;
    $bombasCambio = $elongacion->bombas_porcentaje >= $limiteCambio;
    $vaporCambio = $elongacion->vapor_porcentaje >= $limiteCambio;
    $bombasCompra = $elongacion->bombas_porcentaje >= $limiteCompra && $elongacion->bombas_porcentaje < $limiteCambio;
    $vaporCompra = $elongacion->vapor_porcentaje >= $limiteCompra && $elongacion->vapor_porcentaje < $limiteCambio;
    $pasoInicial = $elongacion->paso_inicial ?? 173;
    $bombasVariacion = $elongacion->bombas_variacion_revision_mm;
    $vaporVariacion = $elongacion->vapor_variacion_revision_mm;
@endphp

<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="mb-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="flex items-start gap-3 text-2xl font-bold text-gray-800 sm:items-center sm:text-3xl">
                    <i class="fas fa-file-alt text-blue-600"></i>
                    Detalle de registro
                </h1>
                <p class="text-gray-600 mt-1">Línea {{ $elongacion->linea }} · {{ $elongacion->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap">
                @if($elongacion->cadenaCiclo)
                    <a href="{{ route('elongaciones.ciclos.show', $elongacion->cadenaCiclo) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition">
                        <i class="fas fa-link"></i>
                        Ver ciclo
                    </a>
                @endif
                <a href="{{ route('elongaciones.index', ['linea' => $elongacion->linea]) }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 divide-y md:divide-y-0 md:divide-x divide-gray-200">
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-500">Línea</p>
                <p class="text-xl font-bold text-gray-800">{{ $elongacion->linea }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-500">Ciclo</p>
                <p class="text-xl font-bold text-gray-800">{{ $elongacion->cadenaCiclo?->codigo ?? 'Sin ciclo' }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-500">Proveedor</p>
                <p class="text-xl font-bold text-gray-800">{{ $elongacion->proveedor_actual ?? 'Sin proveedor' }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-500">Paso inicial</p>
                <p class="text-xl font-bold text-gray-800">{{ $pasoInicial }} mm</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-500">Hodómetro</p>
                <p class="text-xl font-bold text-gray-800">{{ $elongacion->hodometro_formateado ?? '-' }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-500">Horas del ciclo</p>
                <p class="text-xl font-bold text-gray-800">{{ $elongacion->hodometro_ciclo_formateado ?? '-' }}</p>
            </div>
        </div>

        <div class="border-t border-gray-200 p-4 sm:p-6">
            @if($bombasCambio || $vaporCambio)
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="font-semibold text-red-800">Cambio de cadena requerido</p>
                    <p class="text-sm text-red-700 mt-1">Se superó el límite de {{ $limiteCambio }}% en al menos un lado.</p>
                </div>
            @elseif($bombasCompra || $vaporCompra)
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                    <p class="font-semibold text-yellow-800">Considerar compra de cadena</p>
                    <p class="text-sm text-yellow-700 mt-1">Se superó el límite de {{ $limiteCompra }}%.</p>
                </div>
            @else
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                    <p class="font-semibold text-green-800">Estado normal</p>
                    <p class="text-sm text-green-700 mt-1">Ambos lados permanecen por debajo del límite de compra.</p>
                </div>
            @endif


            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="border border-blue-200 rounded-xl overflow-hidden">
                    <div class="flex flex-col gap-2 border-b border-blue-100 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="font-semibold text-blue-800">Lado bombas</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $bombasCambio ? 'bg-red-100 text-red-800' : ($bombasCompra ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                            {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                        </span>
                    </div>
                    <div class="p-4">
                        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                                    <div class="font-medium p-2 bg-gray-50 rounded-lg border">
                                        {{ $elongacion->{'bombas_' . $i} ? number_format($elongacion->{'bombas_' . $i}, 1) . ' mm' : '-' }}
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <p class="text-gray-500">Promedio</p>
                                <p class="font-semibold text-gray-800">{{ number_format($elongacion->bombas_promedio, 2) }} mm</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Paso base</p>
                                <p class="font-semibold text-gray-800">{{ number_format($pasoInicial, 2) }} mm</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Aumento base</p>
                                <p class="font-semibold text-gray-800">+{{ number_format($elongacion->bombas_incremento_base_mm, 2) }} mm</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Vs rev. anterior</p>
                                <p class="font-semibold {{ $bombasVariacion === null ? 'text-slate-500' : ($bombasVariacion > 0 ? 'text-red-600' : ($bombasVariacion < 0 ? 'text-emerald-600' : 'text-slate-700')) }}">
                                    {{ $bombasVariacion === null ? 'Primera del ciclo' : (($bombasVariacion > 0 ? '+' : '') . number_format($bombasVariacion, 2) . ' mm') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Estado</p>
                                <p class="font-semibold {{ $bombasCambio ? 'text-red-600' : ($bombasCompra ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ $bombasCambio ? 'CAMBIO' : ($bombasCompra ? 'COMPRA' : 'NORMAL') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-green-200 rounded-xl overflow-hidden">
                    <div class="flex flex-col gap-2 border-b border-green-100 bg-green-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="font-semibold text-green-800">Lado vapor</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $vaporCambio ? 'bg-red-100 text-red-800' : ($vaporCompra ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                            {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                        </span>
                    </div>
                    <div class="p-4">
                        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                                    <div class="font-medium p-2 bg-gray-50 rounded-lg border">
                                        {{ $elongacion->{'vapor_' . $i} ? number_format($elongacion->{'vapor_' . $i}, 1) . ' mm' : '-' }}
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <p class="text-gray-500">Promedio</p>
                                <p class="font-semibold text-gray-800">{{ number_format($elongacion->vapor_promedio, 2) }} mm</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Paso base</p>
                                <p class="font-semibold text-gray-800">{{ number_format($pasoInicial, 2) }} mm</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Aumento base</p>
                                <p class="font-semibold text-gray-800">+{{ number_format($elongacion->vapor_incremento_base_mm, 2) }} mm</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Vs rev. anterior</p>
                                <p class="font-semibold {{ $vaporVariacion === null ? 'text-slate-500' : ($vaporVariacion > 0 ? 'text-red-600' : ($vaporVariacion < 0 ? 'text-emerald-600' : 'text-slate-700')) }}">
                                    {{ $vaporVariacion === null ? 'Primera del ciclo' : (($vaporVariacion > 0 ? '+' : '') . number_format($vaporVariacion, 2) . ' mm') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Estado</p>
                                <p class="font-semibold {{ $vaporCambio ? 'text-red-600' : ($vaporCompra ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ $vaporCambio ? 'CAMBIO' : ($vaporCompra ? 'COMPRA' : 'NORMAL') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border border-blue-200 rounded-xl p-4 bg-blue-50/30">
                    <p class="text-sm text-gray-600 mb-2">Juego de rodaja bombas</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $elongacion->juego_rodaja_bombas ? number_format($elongacion->juego_rodaja_bombas, 2) . ' mm' : '-' }}</p>
                </div>
                <div class="border border-green-200 rounded-xl p-4 bg-green-50/30">
                    <p class="text-sm text-gray-600 mb-2">Juego de rodaja vapor</p>
                    <p class="text-2xl font-bold text-green-600">{{ $elongacion->juego_rodaja_vapor ? number_format($elongacion->juego_rodaja_vapor, 2) . ' mm' : '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

