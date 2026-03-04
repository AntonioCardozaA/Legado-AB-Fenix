{{-- resources/views/analisis-tendencia-mensual-lavadora/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Nuevo Análisis Mensual 52-12-4')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('analisis-tendencia-mensual-lavadora.index') }}" 
           class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
            Volver al análisis
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-3 rounded-xl">
                    <i class="fas fa-calendar-alt text-white text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white">Nuevo Análisis Mensual</h2>
                    <p class="text-blue-100 mt-1">Ingresa los totales de daños del período</p>
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <form method="POST" action="{{ route('analisis-tendencia-mensual-lavadora.store') }}" class="p-8">
            @csrf

            {{-- Línea --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-washing-machine mr-2 text-blue-600"></i>
                    Línea (Lavadora) <span class="text-red-500">*</span>
                </label>
                <select name="linea_id" id="linea_id" 
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-0"
                        required>
                    <option value="">Seleccionar línea...</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" {{ old('linea_id', $lineaSeleccionada) == $linea->id ? 'selected' : '' }}>
                            {{ $linea->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Período --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-calendar mr-2 text-blue-600"></i>
                        Mes <span class="text-red-500">*</span>
                    </label>
                    <select name="mes" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-0" required>
                        @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $index => $mes)
                            <option value="{{ $index + 1 }}" {{ old('mes', $mesActual) == $index + 1 ? 'selected' : '' }}>
                                {{ $mes }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                        Año <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="anio" 
                           value="{{ old('anio', $anioActual) }}"
                           min="2020" max="2030"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-0"
                           required>
                </div>
            </div>

            @if($existeRegistro)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Ya existe un análisis para este mes. Puedes editarlo o crear uno nuevo para otro período.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Datos del análisis --}}
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-blue-600"></i>
                    Totales de Daños del Período
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- resources/views/analisis-tendencia-mensual-lavadora/create.blade.php --}}
{{-- Solo muestro los inputs modificados --}}

        {{-- 52 Semanas --}}
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-calendar-week text-purple-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-purple-800">52 Semanas</h4>
                    <p class="text-xs text-gray-500">Último año</p>
                </div>
            </div>
            <input type="number" 
                name="total_danos_52_semanas"
                value="{{ old('total_danos_52_semanas') }}"
                min="0"
                step="0.01"  {{-- Permite decimales --}}
                class="w-full px-4 py-3 text-2xl font-bold text-purple-600 border-2 border-purple-200 rounded-xl focus:border-purple-500 focus:ring-0 text-center"
                placeholder="0.00"
                required>
            <p class="text-xs text-gray-500 mt-3 text-center">
                Total de daños en las últimas 52 semanas
            </p>
        </div>

        {{-- 12 Semanas --}}
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-calendar-week text-orange-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-orange-800">12 Semanas</h4>
                    <p class="text-xs text-gray-500">Último trimestre</p>
                </div>
            </div>
            <input type="number" 
                name="total_danos_12_semanas"
                value="{{ old('total_danos_12_semanas') }}"
                min="0"
                step="0.01"  {{-- Permite decimales --}}
                class="w-full px-4 py-3 text-2xl font-bold text-orange-600 border-2 border-orange-200 rounded-xl focus:border-orange-500 focus:ring-0 text-center"
                placeholder="0.00"
                required>
            <p class="text-xs text-gray-500 mt-3 text-center">
                Total de daños en las últimas 12 semanas
            </p>
        </div>

        {{-- 4 Semanas --}}
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-calendar-week text-green-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-green-800">4 Semanas</h4>
                    <p class="text-xs text-gray-500">Último mes</p>
                </div>
            </div>
            <input type="number" 
                name="total_danos_4_semanas"
                value="{{ old('total_danos_4_semanas') }}"
                min="0"
                step="0.01"  {{-- Permite decimales --}}
                class="w-full px-4 py-3 text-2xl font-bold text-green-600 border-2 border-green-200 rounded-xl focus:border-green-500 focus:ring-0 text-center"
                placeholder="0.00"
                required>
            <p class="text-xs text-gray-500 mt-3 text-center">
                Total de daños en las últimas 4 semanas
            </p>
        </div>
                </div>
            </div>

            {{-- Observaciones --}}
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-sticky-note mr-2 text-blue-600"></i>
                    Observaciones (opcional)
                </label>
                <textarea name="observaciones" rows="4"
                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-0"
                          placeholder="Notas adicionales sobre el análisis...">{{ old('observaciones') }}</textarea>
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-3 border-t pt-6">
                <a href="{{ route('analisis-tendencia-mensual-lavadora.index') }}" 
                   class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 transition flex items-center gap-2 shadow-lg shadow-blue-500/30">
                    <i class="fas fa-save"></i>
                    Guardar Análisis Mensual
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('linea_id').addEventListener('change', function() {
    if (this.value) {
        window.location.href = '{{ route("analisis-tendencia-mensual-lavadora.create") }}?linea_id=' + this.value;
    }
});
</script>
@endsection