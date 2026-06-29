{{-- resources/views/analisis-tendencia-mensual-lavadora/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle del Análisis Mensual')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('analisis-tendencia-mensual.lavadora.index', ['linea_id' => $analisis->linea_id]) }}"
           class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
            Volver al análisis
        </a>
    </div>

    {{-- Header --}}
    <div class="rounded-t-2xl bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-5 text-white sm:px-8 sm:py-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="flex items-start gap-3 text-2xl font-bold sm:items-center sm:text-3xl">
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
    <div class="rounded-b-2xl bg-white p-4 shadow-xl sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- 52 Semanas --}}
            <div class="rounded-xl border border-purple-100 bg-gradient-to-br from-purple-50 to-white p-4 sm:p-6">
                <h3 class="font-semibold text-purple-800 mb-4">52 Semanas</h3>
                <div class="mb-2 text-3xl font-bold text-purple-600 sm:text-4xl">
                    {{ number_format($analisis->total_danos_52_semanas, 2) }}
                </div>
                @php $variacion52 = $analisis->variacion_52_semanas; @endphp
                @if($variacion52)
                    <div class="mt-4 pt-4 border-t border-purple-100">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
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
            <div class="rounded-xl border border-orange-100 bg-gradient-to-br from-orange-50 to-white p-4 sm:p-6">
                <h3 class="font-semibold text-orange-800 mb-4">12 Semanas</h3>
                <div class="mb-2 text-3xl font-bold text-orange-600 sm:text-4xl">
                    {{ number_format($analisis->total_danos_12_semanas, 2) }}
                </div>
                @php $variacion12 = $analisis->variacion_12_semanas; @endphp
                @if($variacion12)
                    <div class="mt-4 pt-4 border-t border-orange-100">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
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
            <div class="rounded-xl border border-green-100 bg-gradient-to-br from-green-50 to-white p-4 sm:p-6">
                <h3 class="font-semibold text-green-800 mb-4">4 Semanas</h3>
                <div class="mb-2 text-3xl font-bold text-green-600 sm:text-4xl">
                    {{ number_format($analisis->total_danos_4_semanas, 2) }}
                </div>
                @php $variacion4 = $analisis->variacion_4_semanas; @endphp
                @if($variacion4)
                    <div class="mt-4 pt-4 border-t border-green-100">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
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
            <div class="rounded-xl bg-blue-50 p-4 sm:p-6">
                <h4 class="font-semibold text-blue-800 mb-2">Observaciones</h4>
                <p class="text-gray-700">{{ $analisis->observaciones }}</p>
            </div>
        @endif

        {{-- Fechas de corte --}}
        <div class="mt-6 grid grid-cols-1 gap-3 text-sm text-gray-500 sm:grid-cols-3 sm:gap-4">
            <div>Corte 52 sem: {{ $analisis->fecha_corte_52?->format('d/m/Y') }}</div>
            <div>Corte 12 sem: {{ $analisis->fecha_corte_12?->format('d/m/Y') }}</div>
            <div>Corte 4 sem: {{ $analisis->fecha_corte_4?->format('d/m/Y') }}</div>
        </div>
    </div>
</div>
@endsection
