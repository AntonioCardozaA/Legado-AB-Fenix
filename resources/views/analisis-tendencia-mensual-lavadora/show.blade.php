{{-- resources/views/analisis-tendencia-mensual-lavadora/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle del Análisis Mensual')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('analisis-tendencia-mensual-lavadora.index', ['linea_id' => $analisis->linea_id]) }}" 
           class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
            Volver al análisis
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-t-2xl px-8 py-6 text-white">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold flex items-center gap-3">
                    <i class="fas fa-chart-line"></i>
                    Análisis {{ $analisis->periodo }}
                </h1>
                <p class="text-blue-100 mt-2">
                    <i class="fas fa-washing-machine mr-2"></i>
                    {{ $analisis->linea->nombre }}
                </p>
            </div>
        </div>
    </div>

    {{-- Cards de períodos --}}
    <div class="bg-white rounded-b-2xl shadow-xl p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- 52 Semanas --}}
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-6 border border-purple-100">
                <h3 class="font-semibold text-purple-800 mb-4">52 Semanas</h3>
                <div class="text-4xl font-bold text-purple-600 mb-2">
                    {{ number_format($analisis->total_danos_52_semanas, 2) }}
                </div>
                @php $variacion52 = $analisis->variacion_52_semanas; @endphp
                @if($variacion52)
                    <div class="mt-4 pt-4 border-t border-purple-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">vs mes anterior</span>
                            <span class="{{ $variacion52['diferencia'] > 0 ? 'text-red-600' : ($variacion52['diferencia'] < 0 ? 'text-green-600' : 'text-yellow-600') }} font-bold">
                                {{ $variacion52['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion52['diferencia'], 2) }}
                                ({{ $variacion52['porcentaje'] > 0 ? '+' : '' }}{{ number_format($variacion52['porcentaje'], 2) }}%)
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 12 Semanas --}}
            <div class="bg-gradient-to-br from-orange-50 to-white rounded-xl p-6 border border-orange-100">
                <h3 class="font-semibold text-orange-800 mb-4">12 Semanas</h3>
                <div class="text-4xl font-bold text-orange-600 mb-2">
                    {{ number_format($analisis->total_danos_12_semanas, 2) }}
                </div>
                @php $variacion12 = $analisis->variacion_12_semanas; @endphp
                @if($variacion12)
                    <div class="mt-4 pt-4 border-t border-orange-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">vs mes anterior</span>
                            <span class="{{ $variacion12['diferencia'] > 0 ? 'text-red-600' : ($variacion12['diferencia'] < 0 ? 'text-green-600' : 'text-yellow-600') }} font-bold">
                                {{ $variacion12['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion12['diferencia'], 2) }}
                                ({{ $variacion12['porcentaje'] > 0 ? '+' : '' }}{{ number_format($variacion12['porcentaje'], 2) }}%)
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 4 Semanas --}}
            <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-6 border border-green-100">
                <h3 class="font-semibold text-green-800 mb-4">4 Semanas</h3>
                <div class="text-4xl font-bold text-green-600 mb-2">
                    {{ number_format($analisis->total_danos_4_semanas, 2) }}
                </div>
                @php $variacion4 = $analisis->variacion_4_semanas; @endphp
                @if($variacion4)
                    <div class="mt-4 pt-4 border-t border-green-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">vs mes anterior</span>
                            <span class="{{ $variacion4['diferencia'] > 0 ? 'text-red-600' : ($variacion4['diferencia'] < 0 ? 'text-green-600' : 'text-yellow-600') }} font-bold">
                                {{ $variacion4['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion4['diferencia'], 2) }}
                                ({{ $variacion4['porcentaje'] > 0 ? '+' : '' }}{{ number_format($variacion4['porcentaje'], 2) }}%)
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Observaciones --}}
        @if($analisis->observaciones)
            <div class="bg-blue-50 rounded-xl p-6">
                <h4 class="font-semibold text-blue-800 mb-2">Observaciones</h4>
                <p class="text-gray-700">{{ $analisis->observaciones }}</p>
            </div>
        @endif

        {{-- Fechas de corte --}}
        <div class="mt-6 grid grid-cols-3 gap-4 text-sm text-gray-500">
            <div>Corte 52 sem: {{ $analisis->fecha_corte_52?->format('d/m/Y') }}</div>
            <div>Corte 12 sem: {{ $analisis->fecha_corte_12?->format('d/m/Y') }}</div>
            <div>Corte 4 sem: {{ $analisis->fecha_corte_4?->format('d/m/Y') }}</div>
        </div>
    </div>
</div>
@endsection