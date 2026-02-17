@extends('layouts.app')

@section('title', 'Registrar Elongaciones de Cadena')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-ruler-combined text-3xl text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-800">
                Registro de Elongaciones
            </h1>
        </div>
        <p class="text-gray-600">
            Complete las 10 mediciones para cada lado. Límite de cambio: <span class="font-bold text-red-600">2.4%</span>
        </p>
    </div>

    {{-- Mensajes --}}
    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-600">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">

        <form method="POST" action="{{ route('elongaciones.store') }}" id="elongacionForm">
            @csrf

            {{-- Selector de Línea --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-industry text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Seleccionar Línea
                    </h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
                    @php
                        $lineas = [
                            'L-04' => 'Línea 4',
                            'L-05' => 'Línea 5',
                            'L-06' => 'Línea 6',
                            'L-07' => 'Línea 7',
                            'L-08' => 'Línea 8',
                            'L-09' => 'Línea 9',
                            'L-12' => 'Línea 12',
                            'L-13' => 'Línea 13'
                        ];
                    @endphp
                    
                    @foreach($lineas as $codigo => $nombre)
                        <div class="relative">
                            <input type="radio" 
                                   id="linea_{{ $loop->iteration }}" 
                                   name="linea" 
                                   value="{{ $codigo }}"
                                   class="hidden peer"
                                   {{ old('linea', $lineaSeleccionada ?? 'L-07') == $codigo ? 'checked' : '' }}
                                   required>
                            <label for="linea_{{ $loop->iteration }}" 
                                   class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all duration-200">
                                <div class="text-base font-semibold text-gray-700 mb-1">
                                    {{ $codigo }}
                                </div>
                                <div class="text-xs text-gray-500 text-center">
                                    {{ $nombre }}
                                </div>
                                <div class="absolute top-2 right-2 hidden peer-checked:block">
                                    <i class="fas fa-check-circle text-blue-500"></i>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                @error('linea')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Sección LAVADORA --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-washer text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">
                        LAVADORA <span id="linea_display">LÍNEA 7</span>
                    </h2>
                    <span id="linea_badge" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                        L-07
                    </span>
                </div>

                {{-- Lados Bombas y Vapor --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                    {{-- LADO BOMBAS --}}
                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-tint text-lg text-blue-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">
                                LADO BOMBAS
                            </h3>
                            <span class="ml-auto px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">
                                10 mediciones
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                            @for($i = 1; $i <= 10; $i++)
                            <div>
                                <label for="bombas_{{ $i }}" class="block text-xs font-medium text-gray-700 mb-1">
                                    M{{ $i }} <span class="text-gray-400">(mm)</span>
                                </label>
                                <input type="number" 
                                       step="0.1" 
                                       min="0" 
                                       max="200"
                                       id="bombas_{{ $i }}"
                                       name="bombas_{{ $i }}"
                                       value="{{ old('bombas_' . $i) }}"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-center"
                                       placeholder="173.0">
                                @error('bombas_' . $i)
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            @endfor
                        </div>

                        {{-- Promedio Bombas --}}
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-700">
                                        PROMEDIO =
                                    </span>
                                </div>
                                <div>
                                    <span id="bombas_promedio_display" class="text-xl font-bold text-blue-600">
                                        0.00
                                    </span>
                                    <span class="text-sm text-gray-500 ml-1">mm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- LADO VAPOR --}}
                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-wind text-lg text-green-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">
                                LADO VAPOR
                            </h3>
                            <span class="ml-auto px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">
                                10 mediciones
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                            @for($i = 1; $i <= 10; $i++)
                            <div>
                                <label for="vapor_{{ $i }}" class="block text-xs font-medium text-gray-700 mb-1">
                                    M{{ $i }} <span class="text-gray-400">(mm)</span>
                                </label>
                                <input type="number" 
                                       step="0.1" 
                                       min="0" 
                                       max="200"
                                       id="vapor_{{ $i }}"
                                       name="vapor_{{ $i }}"
                                       value="{{ old('vapor_' . $i) }}"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm text-center"
                                       placeholder="173.0">
                                @error('vapor_' . $i)
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            @endfor
                        </div>

                        {{-- Promedio Vapor --}}
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-700">
                                        PROMEDIO =
                                    </span>
                                </div>
                                <div>
                                    <span id="vapor_promedio_display" class="text-xl font-bold text-green-600">
                                        0.00
                                    </span>
                                    <span class="text-sm text-gray-500 ml-1">mm</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hodómetro --}}
                <div class="mb-8">
                    <div class="max-w-md mx-auto">
                        <label for="hodometro" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                            HODÓMETRO <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <input type="number" 
                                       id="hodometro"
                                       name="hodometro"
                                       value="{{ old('hodometro') }}"
                                       required
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-lg text-center font-medium py-3"
                                       placeholder="Ingrese horas">
                            </div>
                            <span class="text-lg font-medium text-gray-700 whitespace-nowrap">
                                HORAS
                            </span>
                        </div>
                        @error('hodometro')
                            <p class="text-red-500 text-sm text-center mt-2">{{ $message }}</p>
                        @enderror
                        <div id="ultima_lectura_container" class="text-center mt-2">
                            <p class="text-gray-400 text-sm">
                                Última lectura: 
                                <span id="ultima_lectura">
                                    @if($ultimaLectura)
                                        {{ number_format($ultimaLectura->hodometro, 0) }} horas
                                    @else
                                        Sin registro
                                    @endif
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Porcentajes --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    {{-- Porcentaje Bombas --}}
                    <div class="border border-blue-200 rounded-xl p-6 bg-blue-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">
                                ELONGACIÓN BOMBAS
                            </h4>
                            <div id="bombas_porcentaje_display" class="text-3xl font-bold text-blue-600">
                                0.00%
                            </div>
                            <div class="mt-2">
                                <span id="bombas_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Sin datos
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="bombas_progress" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    {{-- Porcentaje Vapor --}}
                    <div class="border border-green-200 rounded-xl p-6 bg-green-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-green-700 mb-1">
                                ELONGACIÓN VAPOR
                            </h4>
                            <div id="vapor_porcentaje_display" class="text-3xl font-bold text-green-600">
                                0.00%
                            </div>
                            <div class="mt-2">
                                <span id="vapor_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Sin datos
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="vapor_progress" class="bg-green-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                {{-- Alerta Límite --}}
                <div id="alerta_limite" class="mb-6 border border-amber-200 rounded-xl p-4 bg-amber-50/50">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-amber-800 mb-1">
                                LÍMITE DE CAMBIO: 2.4%
                            </h4>
                            <p class="text-xs text-amber-600">
                                Paso inicial: 173 mm - Máximo 2.4% de elongación para cambio de cadena
                            </p>
                        </div>
                        <span class="px-3 py-1 bg-amber-100 text-amber-800 text-sm font-medium rounded-full">
                            <i class="fas fa-ruler mr-1"></i>PASO 173 MM
                        </span>
                    </div>
                </div>
            </div>

            {{-- JUEGO DE RODAJA --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-cogs text-xl text-gray-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">
                        JUEGO DE RODAJA - HOLGURA
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Lado Bombas --}}
                    <div>
                        <label for="juego_rodaja_bombas" class="block text-sm font-medium text-gray-700 mb-2">
                            LADO BOMBAS (mm)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0"
                               id="juego_rodaja_bombas"
                               name="juego_rodaja_bombas"
                               value="{{ old('juego_rodaja_bombas') }}"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="0.00">
                        @error('juego_rodaja_bombas')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Lado Vapor --}}
                    <div>
                        <label for="juego_rodaja_vapor" class="block text-sm font-medium text-gray-700 mb-2">
                            LADO VAPOR (mm)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0"
                               id="juego_rodaja_vapor"
                               name="juego_rodaja_vapor"
                               value="{{ old('juego_rodaja_vapor') }}"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                               placeholder="0.00">
                        @error('juego_rodaja_vapor')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <a href="{{ route('elongaciones.index') }}"
                       class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="button" id="btnLimpiar"
                            class="px-5 py-2.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition flex items-center gap-2 border border-gray-200">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>

                <button type="submit"
                        class="px-8 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center gap-2 font-medium shadow-md">
                    <i class="fas fa-save"></i>
                    Guardar Registro
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const form = document.getElementById('elongacionForm');
    const hodometro = document.getElementById('hodometro');
    const btnLimpiar = document.getElementById('btnLimpiar');
    
    // Elementos para selección de línea
    const lineaInputs = document.querySelectorAll('input[name="linea"]');
    const lineaDisplay = document.getElementById('linea_display');
    const lineaBadge = document.getElementById('linea_badge');
    
    // Configuración
    const PASO_INICIAL = 173;
    const LIMITE_ADVERTENCIA = 2.0;
    const LIMITE_PELIGRO = 2.4;
    
    // Arrays de campos - 10 mediciones cada uno
    const bombasFields = Array.from({length: 10}, (_, i) => `bombas_${i + 1}`);
    const vaporFields = Array.from({length: 10}, (_, i) => `vapor_${i + 1}`);
    
    // ========== FUNCIONES DE SELECCIÓN DE LÍNEA ==========
    
    function actualizarLineaSeleccionada() {
        const lineaSeleccionada = document.querySelector('input[name="linea"]:checked')?.value || 'L-07';
        
        // Actualizar UI
        const numeroLinea = lineaSeleccionada.replace('L-', '');
        lineaDisplay.textContent = `LÍNEA ${numeroLinea}`;
        lineaBadge.textContent = lineaSeleccionada;
    }
    
    // ========== FUNCIONES DE CÁLCULO ==========
    
    function calcularPromedio(fields) {
        let sum = 0;
        let count = 0;
        
        fields.forEach(id => {
            const field = document.getElementById(id);
            const value = parseFloat(field.value);
            if (!isNaN(value) && value > 0) {
                sum += value;
                count++;
            }
        });
        
        return count > 0 ? (sum / count) : 0;
    }
    
    function calcularPorcentaje(promedio) {
        if (promedio <= 0) return 0;
        return ((promedio - PASO_INICIAL) / PASO_INICIAL * 100);
    }
    
    function determinarEstado(porcentaje) {
        if (porcentaje <= 0) return {clase: 'secondary', texto: 'Sin datos', color: 'gray'};
        if (porcentaje < LIMITE_ADVERTENCIA) return {clase: 'normal', texto: 'Normal', color: 'green'};
        if (porcentaje < LIMITE_PELIGRO) return {clase: 'alerta', texto: 'Alerta', color: 'amber'};
        return {clase: 'critico', texto: '¡CRÍTICO!', color: 'red'};
    }
    
    function actualizarCalculos() {
        // Calcular promedios
        const bombasPromedio = calcularPromedio(bombasFields);
        const vaporPromedio = calcularPromedio(vaporFields);
        
        // Calcular porcentajes
        const bombasPorcentaje = calcularPorcentaje(bombasPromedio);
        const vaporPorcentaje = calcularPorcentaje(vaporPromedio);
        
        // Actualizar displays
        document.getElementById('bombas_promedio_display').textContent = bombasPromedio.toFixed(2);
        document.getElementById('vapor_promedio_display').textContent = vaporPromedio.toFixed(2);
        
        document.getElementById('bombas_porcentaje_display').textContent = bombasPorcentaje.toFixed(2) + '%';
        document.getElementById('vapor_porcentaje_display').textContent = vaporPorcentaje.toFixed(2) + '%';
        
        // Actualizar estados
        const bombasEstado = determinarEstado(bombasPorcentaje);
        const vaporEstado = determinarEstado(vaporPorcentaje);
        
        const bombasStatus = document.getElementById('bombas_status');
        const vaporStatus = document.getElementById('vapor_status');
        
        bombasStatus.textContent = bombasEstado.texto;
        bombasStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-${bombasEstado.color}-100 text-${bombasEstado.color}-800`;
        
        vaporStatus.textContent = vaporEstado.texto;
        vaporStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-${vaporEstado.color}-100 text-${vaporEstado.color}-800`;
        
        // Actualizar barras de progreso (basado en 3% como máximo)
        const maxReferencia = 3.0;
        document.getElementById('bombas_progress').style.width = Math.min(100, (bombasPorcentaje / maxReferencia) * 100) + '%';
        document.getElementById('vapor_progress').style.width = Math.min(100, (vaporPorcentaje / maxReferencia) * 100) + '%';
        
        // Actualizar colores de las barras
        document.getElementById('bombas_progress').className = `h-2.5 rounded-full transition-all duration-300 bg-${bombasEstado.color}-600`;
        document.getElementById('vapor_progress').className = `h-2.5 rounded-full transition-all duration-300 bg-${vaporEstado.color}-600`;
        
        // Actualizar alerta
        const alerta = document.getElementById('alerta_limite');
        if (bombasPorcentaje >= LIMITE_PELIGRO || vaporPorcentaje >= LIMITE_PELIGRO) {
            alerta.className = 'mb-6 border border-red-200 rounded-xl p-4 bg-red-50/50';
            alerta.querySelector('i').className = 'fas fa-exclamation-circle text-red-500 text-xl';
            alerta.querySelector('h4').textContent = '¡ALERTA CRÍTICA! SUPERÓ 2.4%';
            alerta.querySelector('p').textContent = 'Se recomienda cambio de cadena inmediato';
        } else {
            alerta.className = 'mb-6 border border-amber-200 rounded-xl p-4 bg-amber-50/50';
            alerta.querySelector('i').className = 'fas fa-exclamation-triangle text-amber-500 text-xl';
            alerta.querySelector('h4').textContent = 'LÍMITE DE CAMBIO: 2.4%';
            alerta.querySelector('p').textContent = 'Paso inicial: 173 mm - Máximo 2.4% de elongación para cambio de cadena';
        }
    }
    
    // ========== EVENT LISTENERS ==========
    
    // Escuchar cambios en la selección de línea
    lineaInputs.forEach(input => {
        input.addEventListener('change', actualizarLineaSeleccionada);
    });
    
    // Escuchar cambios en las mediciones
    [...bombasFields, ...vaporFields].forEach(id => {
        const field = document.getElementById(id);
        field.addEventListener('input', actualizarCalculos);
        field.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (this.value !== '' && (isNaN(value) || value < 0 || value > 200)) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });
    });
    
    // Hodómetro
    hodometro.addEventListener('blur', function() {
        const value = parseInt(this.value);
        if (this.value !== '' && (isNaN(value) || value < 0)) {
            this.classList.add('border-red-500');
        } else {
            this.classList.remove('border-red-500');
        }
    });
    
    // Botón limpiar
    btnLimpiar.addEventListener('click', function() {
        if (confirm('¿Está seguro de limpiar todos los datos?')) {
            form.reset();
            actualizarCalculos();
        }
    });
    
    // Inicializar
    actualizarLineaSeleccionada();
    actualizarCalculos();
});
</script>
@endsection