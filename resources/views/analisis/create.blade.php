@extends('layouts.app')

@section('title', 'Nuevo Análisis')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Análisis</h1>
        <p class="text-gray-600">Registro simplificado de análisis de componentes</p>
    </div>

    <form action="{{ route('analisis.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Información básica -->
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">
                Información del Análisis
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Componente -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Componente *
                    </label>
                    <select name="componente_id" required class="w-full rounded-lg border-gray-300">
                        <option value="">Seleccione un componente</option>
                        @foreach($componentes as $componente)
                            <option value="{{ $componente->id }}">
                                {{ $componente->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Fecha -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha *
                    </label>
                    <input type="date" name="fecha" required
                           value="{{ date('Y-m-d') }}"
                           class="w-full rounded-lg border-gray-300">
                </div>

                <!-- Número de Orden -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Número de Orden *
                    </label>
                    <input type="text" name="numero_orden" required
                           placeholder="Ej: ORD-2024-001"
                           class="w-full rounded-lg border-gray-300">
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estado *
                    </label>
                    <select name="estado" required class="w-full rounded-lg border-gray-300">
                        <option value="">Seleccione estado</option>
                        <option value="BUENO">Bueno</option>
                        <option value="REGULAR">Regular</option>
                        <option value="DAÑADO">Dañado</option>
                        <option value="REEMPLAZADO">Reemplazado</option>
                    </select>
                </div>

                <!-- Actividad -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Actividad *
                    </label>
                    <input type="text" name="actividad" required
                           placeholder="Describa la actividad realizada"
                           class="w-full rounded-lg border-gray-300">
                </div>

                <!-- Fotos -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fotos de evidencia
                    </label>
                    <input type="file" name="fotos[]" multiple
                           accept="image/*"
                           class="w-full rounded-lg border-gray-300">
                    <p class="text-xs text-gray-500 mt-1">
                        Puede seleccionar múltiples imágenes (máx. 5MB cada una)
                    </p>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('analisis.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Guardar Análisis
            </button>
        </div>
    </form>
</div>
@endsection