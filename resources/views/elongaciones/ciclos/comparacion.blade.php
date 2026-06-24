@extends('layouts.app')

@section('title', 'Comparación de Ciclos')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Comparación de ciclos por línea</h1>
            <p class="text-gray-600 mt-1">Consulta vida útil, proveedor y comportamiento de elongación entre cadenas instaladas en la misma línea.</p>
        </div>
        <a href="{{ route('elongaciones.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition w-fit">
            <i class="fas fa-arrow-left"></i>
            Volver al historial
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('elongaciones.ciclos.comparacion') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
            <div class="w-full md:max-w-sm">
                <label for="linea" class="block text-sm font-medium text-gray-700 mb-1">Línea</label>
                <select name="linea" id="linea" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    @foreach($lineas as $linea)
                        <option value="{{ $linea }}" {{ $lineaSeleccionada === $linea ? 'selected' : '' }}>{{ $linea }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter mr-1"></i> Consultar
                </button>
                <a href="{{ route('elongaciones.create', ['linea' => $lineaSeleccionada]) }}" class="create-action">
                    <i class="fas fa-plus-circle mr-1"></i> Nuevo registro
                </a>
            </div>
        </form>
    </div>

    @if($ciclos->isEmpty())
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i class="fas fa-link text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Sin ciclos registrados para {{ $lineaSeleccionada }}</h3>
            <p class="text-gray-500">Registra la primera cadena de esta línea para comenzar la trazabilidad.</p>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            @foreach($ciclos as $item)
                @php
                    $ciclo = $item['ciclo'];
                    $activo = $ciclo->activa;
                @endphp
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-slate-50 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500">Ciclo {{ $ciclo->numero_ciclo }}</p>
                            <h2 class="text-2xl font-bold text-slate-900">{{ $ciclo->codigo }}</h2>
                            <p class="text-sm text-slate-600 mt-1">Proveedor: {{ $ciclo->proveedor }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                            {{ $activo ? 'Activo' : 'Cerrado' }}
                        </span>
                    </div>

                    <div class="p-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Instalada en</p>
                            <p class="font-semibold text-gray-900">{{ optional($ciclo->instalada_en)->format('d/m/Y') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Base de hodómetro</p>
                            <p class="font-semibold text-gray-900">{{ $ciclo->hodometro_inicial_formateado ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Registros</p>
                            <p class="font-semibold text-gray-900">{{ $item['registros'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Vida útil acumulada</p>
                            <p class="font-semibold text-gray-900">{{ $item['vida_util_horas'] !== null ? \App\Support\HodometroHoras::formatear($item['vida_util_horas']) : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Promedio bombas / vapor</p>
                            <p class="font-semibold text-gray-900">{{ number_format($item['promedio_bombas'], 2) }}% / {{ number_format($item['promedio_vapor'], 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Máximo bombas / vapor</p>
                            <p class="font-semibold text-gray-900">{{ number_format($item['max_bombas'] ?? 0, 2) }}% / {{ number_format($item['max_vapor'] ?? 0, 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Último estado</p>
                            <p class="font-semibold text-gray-900">{{ strtoupper($item['ultimo_estado'] ?? 'sin dato') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Días operando</p>
                            <p class="font-semibold text-gray-900">{{ $item['dias_operacion'] !== null ? (int) $item['dias_operacion'] . ' días' : '-' }}</p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 flex items-center justify-between gap-4">
                        <div class="text-sm text-gray-500">
                            @if($item['ultima_medicion'])
                                Última medición {{ $item['ultima_medicion']->created_at->format('d/m/Y H:i') }}
                            @else
                                Sin mediciones asociadas
                            @endif
                        </div>
                        <a href="{{ route('elongaciones.ciclos.show', $ciclo) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-eye"></i>
                            Ver historial
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
