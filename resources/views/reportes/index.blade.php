@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="max-w-7xl mx-auto">

    <!-- ENCABEZADO -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Centro de Reportes</h1>
        <p class="text-gray-600">Seleccione el tipo de equipo para consultar informes</p>
    </div>

    <!-- ========================= -->
    <!-- SECCIÓN LAVADORAS -->
    <!-- ========================= -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="p-3 bg-blue-100 rounded-lg mr-4">
                <i class="fas fa-soap text-blue-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800">Reportes de Lavadoras</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <a href="{{ route('reportes.elongacion') }}" class="card p-6 hover:shadow-lg transition-shadow border-l-4 border-blue-500">
                <h3 class="font-semibold text-gray-800">Elongación de Cadenas</h3>
                <p class="text-sm text-gray-600 mt-1">Análisis por línea</p>
            </a>

            <a href="{{ route('reportes.componentes') }}" class="card p-6 hover:shadow-lg transition-shadow border-l-4 border-green-500">
                <h3 class="font-semibold text-gray-800">Estado de Componentes</h3>
                <p class="text-sm text-gray-600 mt-1">Condición actual</p>
            </a>

            <a href="{{ route('reportes.paros') }}" class="card p-6 hover:shadow-lg transition-shadow border-l-4 border-yellow-500">
                <h3 class="font-semibold text-gray-800">Paros de Mantenimiento</h3>
                <p class="text-sm text-gray-600 mt-1">Historial y estadísticas</p>
            </a>
        </div>

        <!-- Resumen Lavadoras -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Resumen por Línea (Lavadoras)</h3>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Análisis</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Último</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Elongación</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($lineasLavadoras as $linea)
                        @php
                            $ultimo = $linea->analisisLavadora()->latest()->first();
                            $estado = 'N/A';
                            $color = 'gray';

                            if ($ultimo) {
                                if ($ultimo->elongacion_promedio > 178.19) {
                                    $estado = 'CRÍTICO';
                                    $color = 'red';
                                } elseif ($ultimo->elongacion_promedio > 176) {
                                    $estado = 'ATENCIÓN';
                                    $color = 'yellow';
                                } else {
                                    $estado = 'NORMAL';
                                    $color = 'green';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $linea->nombre }}</td>
                            <td class="px-4 py-3">{{ $linea->analisis_lavadora_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                {{ $ultimo ? $ultimo->fecha_analisis->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $ultimo ? number_format($ultimo->elongacion_promedio, 2).' mm' : 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($ultimo)
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                    {{ $estado }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- SECCIÓN PASTEURIZADORAS -->
    <!-- ========================= -->
   

</div>
@endsection
