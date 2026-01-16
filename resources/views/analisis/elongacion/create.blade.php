@extends('layouts.app')

@section('title', 'Medición de Elongación')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- TÍTULO -->
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Medición de Elongación – Lavadora
        </h1>
        <p class="text-gray-600">
            Análisis general posterior a la revisión de componentes
        </p>
    </div>

    <!-- INFO DEL ANÁLISIS -->
    <div class="card p-4 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Línea:</span>
                <span>{{ $analisis->linea->nombre }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Fecha:</span>
                <span>{{ $analisis->fecha_analisis }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Orden:</span>
                <span>{{ $analisis->numero_orden }}</span>
            </div>
        </div>
    </div>

    <form action="{{ route('analisis.elongacion.store', $analisis->id) }}" method="POST" class="space-y-6">
        @csrf

        <!-- ================= HORÓMETRO ================= -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-stopwatch mr-2 text-blue-600"></i>
                Horómetro
            </h2>

            <div class="w-64">
                <label class="block text-sm font-medium mb-1">
                    Horómetro actual
                </label>
                <input type="number" name="horometro" required
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <!-- ================= ELONGACIÓN ================= -->
        <div class="card p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">
                <i class="fas fa-ruler-combined mr-2 text-blue-600"></i>
                Mediciones de Elongación (Paso 173 mm)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- LADO BOMBAS -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3">Lado Bombas</h3>

                    <div class="grid grid-cols-4 gap-2">
                        @for($i = 1; $i <= 8; $i++)
                            <div>
                                <label class="block text-xs text-gray-600">
                                    Medición {{ $i }}
                                </label>
                                <input type="number" step="0.1"
                                       name="bombas_mediciones[]"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        @endfor
                    </div>

                    <div class="mt-4 w-40">
                        <label class="block text-sm font-medium mb-1">
                            Juego de rodaja (mm)
                        </label>
                        <input type="number" step="0.1"
                               name="juego_rodaja_bombas"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- LADO VAPOR -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3">Lado Vapor</h3>

                    <div class="grid grid-cols-4 gap-2">
                        @for($i = 1; $i <= 8; $i++)
                            <div>
                                <label class="block text-xs text-gray-600">
                                    Medición {{ $i }}
                                </label>
                                <input type="number" step="0.1"
                                       name="vapor_mediciones[]"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        @endfor
                    </div>

                    <div class="mt-4 w-40">
                        <label class="block text-sm font-medium mb-1">
                            Juego de rodaja (mm)
                        </label>
                        <input type="number" step="0.1"
                               name="juego_rodaja_vapor"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

            </div>

            <!-- INFO -->
            <div class="p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                Elongación máxima permitida: <strong>3% (5.19 mm)</strong>
            </div>
        </div>

        <!-- BOTONES -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('analisis.show', $analisis->id) }}"
               class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">
                Volver
            </a>

            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>
                Guardar Elongación
            </button>
        </div>

    </form>
</div>
@endsection
