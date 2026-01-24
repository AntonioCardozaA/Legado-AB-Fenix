@extends('layouts.app')

@section('title', 'Registrar Elongaciones de Cadena')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-ruler-combined text-3xl text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-800">
                Registro de Elongaciones - Línea 7
            </h1>
        </div>
        <p class="text-gray-600">
            Complete las 10 mediciones para cada lado para registrar el análisis de elongación de cadena.
        </p>
    </div>

    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">

        <form method="POST" action="{{ route('elongaciones.store') }}" id="elongacionForm">
            @csrf

            {{-- Sección LAVADORA LINEA 7 --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-washer text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">
                        LAVADORA LINEA 7
                    </h2>
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
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
                                L. BOMBAS
                            </h3>
                            <span class="ml-auto px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">
                                10 mediciones
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                            @for($i = 1; $i <= 10; $i++)
                            <div>
                                <label for="bombas_{{ $i }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    M{{ $i }} <span class="text-gray-400 text-xs">(mm)</span>
                                </label>
                                <input type="number" 
                                       step="0.1" 
                                       min="0" 
                                       max="200"
                                       id="bombas_{{ $i }}"
                                       name="bombas_{{ $i }}"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-center"
                                       placeholder="173.0">
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
                                    <span class="text-xs text-gray-500">(mm)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span id="bombas_promedio_display" class="text-xl font-bold text-blue-600">
                                        0.00
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- LADO VAPOR --}}
                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-wind text-lg text-green-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">
                                L. VAPOR
                            </h3>
                            <span class="ml-auto px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">
                                10 mediciones
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                            @for($i = 1; $i <= 10; $i++)
                            <div>
                                <label for="vapor_{{ $i }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    M{{ $i }} <span class="text-gray-400 text-xs">(mm)</span>
                                </label>
                                <input type="number" 
                                       step="0.1" 
                                       min="0" 
                                       max="200"
                                       id="vapor_{{ $i }}"
                                       name="vapor_{{ $i }}"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm text-center"
                                       placeholder="173.0">
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
                                    <span class="text-xs text-gray-500">(mm)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span id="vapor_promedio_display" class="text-xl font-bold text-green-600">
                                        0.00
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hodómetro --}}
                <div class="mb-8">
                    <div class="max-w-md mx-auto">
                        <label for="hodometro" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                            HODÓMETRO
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <input type="number" 
                                       id="hodometro"
                                       name="hodometro"
                                       required
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-lg text-center font-medium py-3"
                                       placeholder="19355830">
                            </div>
                            <span class="text-lg font-medium text-gray-700 whitespace-nowrap">
                                HORAS
                            </span>
                        </div>
                        <p class="text-gray-400 text-sm text-center mt-2">
                            Última lectura: {{ $ultimaLectura ?? 'Sin registro' }}
                        </p>
                    </div>
                </div>

                {{-- Porcentajes --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    {{-- Porcentaje Bombas --}}
                    <div class="border border-blue-200 rounded-xl p-6 bg-blue-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">
                                PORCENTAJE BOMBAS
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
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="bombas_progress" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>

                    {{-- Porcentaje Vapor --}}
                    <div class="border border-green-200 rounded-xl p-6 bg-green-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-green-700 mb-1">
                                PORCENTAJE VAPOR
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
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="vapor_progress" class="bg-green-600 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                {{-- Alerta Límite --}}
                <div class="mb-6">
                    <div class="border border-amber-200 rounded-xl p-4 bg-amber-50/50">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-amber-800 mb-1">
                                    ¡ATENCIÓN! LÍMITE DE CAMBIO
                                </h4>
                                <p class="text-xs text-amber-600">
                                    Máximo 3% de elongación para cambio de cadena
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-amber-100 text-amber-800 text-sm font-medium rounded-full">
                                <i class="fas fa-ruler mr-1"></i>PASO 173 MM
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sección HOLGURA EN CADENA Y JUEGO DE LA RODAJA --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-cogs text-xl text-gray-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">
                        HOLGURA EN CADENA Y JUEGO DE LA RODAJA
                    </h2>
                </div>

                <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
                    <div class="bg-gray-50 px-6 py-3">
                        <h3 class="text-sm font-medium text-gray-800">
                            JUEGO RODAJA CADENA
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            LÍNEA
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            L. BOMBAS (mm)
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            L. VAPOR (mm)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                                                L-07
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div>
                                                <input type="number" 
                                                       step="0.01" 
                                                       id="juego_rodaja_bombas"
                                                       name="juego_rodaja_bombas"
                                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-center"
                                                       placeholder="0.00">
                                                <p class="text-xs text-gray-500 text-center mt-1">Holgura lado bombas</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div>
                                                <input type="number" 
                                                       step="0.01" 
                                                       id="juego_rodaja_vapor"
                                                       name="juego_rodaja_vapor"
                                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm text-center"
                                                       placeholder="0.00">
                                                <p class="text-xs text-gray-500 text-center mt-1">Holgura lado vapor</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <a href="{{ route('elongaciones.index') }}"
                       class="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="button" id="btnLimpiar"
                            class="px-5 py-2 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition flex items-center gap-2 border border-gray-200">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <div id="statusCalculo" class="hidden">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-sync fa-spin mr-1"></i>
                            Calculando...
                        </span>
                    </div>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center gap-2 font-medium">
                        <i class="fas fa-save"></i>
                        Guardar Registro
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmación -->
<div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-2xl text-blue-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    Confirmar Registro
                </h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-gray-600 mb-4">
                ¿Confirmar envío de datos?
            </p>
            <div id="resumenDatos" class="bg-gray-50 rounded-lg p-4 mb-4">
                <!-- Resumen será insertado aquí -->
            </div>
            <div class="border-l-4 border-amber-400 bg-amber-50 p-3 rounded">
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                    <p class="text-sm text-amber-700">
                        Una vez guardado, no podrá modificar este registro.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <button type="button" id="btnCancelarModal"
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                Cancelar
            </button>
            <button type="button" id="btnConfirmar"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                Confirmar y Guardar
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const form = document.getElementById('elongacionForm');
    const hodometro = document.getElementById('hodometro');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const statusCalculo = document.getElementById('statusCalculo');
    const confirmModal = document.getElementById('confirmModal');
    const btnCancelarModal = document.getElementById('btnCancelarModal');
    const btnConfirmar = document.getElementById('btnConfirmar');
    
    // Configuración
    const PASO_INICIAL = 173; // mm
    const LIMITE_ADVERTENCIA = 2; // %
    const LIMITE_PELIGRO = 3; // %
    
    // Arrays de campos - AHORA 10 MEDICIONES CADA UNO
    const bombasFields = Array.from({length: 10}, (_, i) => `bombas_${i + 1}`);
    const vaporFields = Array.from({length: 10}, (_, i) => `vapor_${i + 1}`);
    
    // Estado de la aplicación
    let estadoApp = {
        calculando: false,
        datosGuardados: false
    };
    
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
        
        return count > 0 ? sum / count : 0;
    }
    
    function calcularPorcentaje(promedio) {
        if (promedio <= 0) return 0;
        const porcentaje = ((promedio - PASO_INICIAL) / PASO_INICIAL * 100);
        return Math.max(0, porcentaje); // No permitir valores negativos
    }
    
    function determinarEstado(porcentaje) {
        if (porcentaje === 0) return {clase: 'secondary', texto: 'Sin datos', progreso: 0};
        if (porcentaje < 1) return {clase: 'normal', texto: 'Normal', progreso: porcentaje * 33};
        if (porcentaje < LIMITE_ADVERTENCIA) return {clase: 'alerta', texto: 'Atención', progreso: 33 + (porcentaje - 1) * 33};
        return {clase: 'critico', texto: '¡CRÍTICO!', progreso: 66 + (porcentaje - 2) * 10};
    }
    
    // ========== FUNCIONES DE ACTUALIZACIÓN DE UI ==========
    
    function mostrarCalculando(mostrar) {
        estadoApp.calculando = mostrar;
        if (mostrar) {
            statusCalculo.classList.remove('hidden');
            statusCalculo.classList.add('flex');
        } else {
            statusCalculo.classList.add('hidden');
            statusCalculo.classList.remove('flex');
        }
    }
    
    function actualizarCalculos() {
        if (estadoApp.calculando) return;
        
        mostrarCalculando(true);
        
        // Usar setTimeout para permitir que la UI se actualice
        setTimeout(() => {
            try {
                // Calcular promedios
                const bombasPromedio = calcularPromedio(bombasFields);
                const vaporPromedio = calcularPromedio(vaporFields);
                
                // Calcular porcentajes
                const bombasPorcentaje = calcularPorcentaje(bombasPromedio);
                const vaporPorcentaje = calcularPorcentaje(vaporPromedio);
                
                // Actualizar displays
                actualizarDisplayPromedios(bombasPromedio, vaporPromedio);
                actualizarDisplayPorcentajes(bombasPorcentaje, vaporPorcentaje);
                actualizarEstados(bombasPorcentaje, vaporPorcentaje);
                actualizarBarrasProgreso(bombasPorcentaje, vaporPorcentaje);
                
                // Verificar límites
                verificarLimites(bombasPorcentaje, vaporPorcentaje);
                
            } catch (error) {
                console.error('Error en cálculo:', error);
                mostrarMensaje('Error', 'Error en el cálculo de promedios', 'error');
            } finally {
                mostrarCalculando(false);
            }
        }, 100);
    }
    
    function actualizarDisplayPromedios(bombasPromedio, vaporPromedio) {
        document.getElementById('bombas_promedio_display').textContent = 
            bombasPromedio.toFixed(2);
        document.getElementById('vapor_promedio_display').textContent = 
            vaporPromedio.toFixed(2);
    }
    
    function actualizarDisplayPorcentajes(bombasPorcentaje, vaporPorcentaje) {
        const bombasDisplay = document.getElementById('bombas_porcentaje_display');
        const vaporDisplay = document.getElementById('vapor_porcentaje_display');
        
        bombasDisplay.textContent = bombasPorcentaje.toFixed(2) + '%';
        vaporDisplay.textContent = vaporPorcentaje.toFixed(2) + '%';
        
        // Aplicar clases de color según estado
        const bombasEstado = determinarEstado(bombasPorcentaje).clase;
        const vaporEstado = determinarEstado(vaporPorcentaje).clase;
        
        bombasDisplay.className = `text-3xl font-bold text-estado-${bombasEstado}`;
        vaporDisplay.className = `text-3xl font-bold text-estado-${vaporEstado}`;
    }
    
    function actualizarEstados(bombasPorcentaje, vaporPorcentaje) {
        const bombasEstado = determinarEstado(bombasPorcentaje);
        const vaporEstado = determinarEstado(vaporPorcentaje);
        
        const bombasStatus = document.getElementById('bombas_status');
        const vaporStatus = document.getElementById('vapor_status');
        
        bombasStatus.textContent = bombasEstado.texto;
        bombasStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-estado-${bombasEstado.clase} text-white`;
        
        vaporStatus.textContent = vaporEstado.texto;
        vaporStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-estado-${vaporEstado.clase} text-white`;
    }
    
    function actualizarBarrasProgreso(bombasPorcentaje, vaporPorcentaje) {
        const bombasProgreso = determinarEstado(bombasPorcentaje).progreso;
        const vaporProgreso = determinarEstado(vaporPorcentaje).progreso;
        
        const bombasBar = document.getElementById('bombas_progress');
        const vaporBar = document.getElementById('vapor_progress');
        
        bombasBar.style.width = Math.min(100, bombasProgreso) + '%';
        bombasBar.className = `h-2 rounded-full bg-estado-${determinarEstado(bombasPorcentaje).clase}`;
        
        vaporBar.style.width = Math.min(100, vaporProgreso) + '%';
        vaporBar.className = `h-2 rounded-full bg-estado-${determinarEstado(vaporPorcentaje).clase}`;
    }
    
    function verificarLimites(bombasPorcentaje, vaporPorcentaje) {
        const alerta = document.querySelector('.border-amber-200');
        if (!alerta) return;
        
        if (bombasPorcentaje >= LIMITE_PELIGRO || vaporPorcentaje >= LIMITE_PELIGRO) {
            alerta.classList.remove('border-amber-200', 'bg-amber-50/50');
            alerta.classList.add('border-red-200', 'bg-red-50/50');
            
            const icono = alerta.querySelector('i');
            if (icono) {
                icono.className = 'fas fa-exclamation-circle text-red-500 text-xl';
            }
            
            const titulo = alerta.querySelector('h4');
            if (titulo) {
                titulo.textContent = '¡ALERTA CRÍTICA!';
                titulo.className = 'text-sm font-medium text-red-800 mb-1';
            }
            
            const texto = alerta.querySelector('p');
            if (texto) {
                texto.textContent = 'Se ha superado el 3% de elongación. Se recomienda cambio de cadena inmediato.';
                texto.className = 'text-xs text-red-600';
            }
            
            const badge = alerta.querySelector('span');
            if (badge) {
                badge.classList.remove('bg-amber-100', 'text-amber-800');
                badge.classList.add('bg-red-100', 'text-red-800');
                badge.innerHTML = '<i class="fas fa-exclamation mr-1"></i>REQUIERE CAMBIO';
            }
        } else if (bombasPorcentaje >= LIMITE_ADVERTENCIA || vaporPorcentaje >= LIMITE_ADVERTENCIA) {
            alerta.classList.remove('border-red-200', 'bg-red-50/50');
            alerta.classList.add('border-amber-200', 'bg-amber-50/50');
        }
    }
    
    // ========== VALIDACIÓN ==========
    
    function validarMedicion(e) {
        const field = e.target;
        const value = parseFloat(field.value);
        
        if (field.value !== '') {
            if (isNaN(value) || value < 0 || value > 200) {
                field.classList.remove('input-valid', 'border-blue-500', 'border-green-500');
                field.classList.add('input-invalid', 'border-red-500');
                mostrarError(field, 'Valor entre 0 y 200 mm');
            } else {
                field.classList.remove('input-invalid', 'border-red-500');
                field.classList.add('input-valid');
                if (field.id.includes('bombas')) {
                    field.classList.add('border-blue-500');
                } else {
                    field.classList.add('border-green-500');
                }
                removerError(field);
            }
        } else {
            field.classList.remove('input-valid', 'input-invalid', 'border-red-500', 'border-blue-500', 'border-green-500');
            removerError(field);
        }
    }
    
    function validarHodometro() {
        if (hodometro.value !== '') {
            const value = parseInt(hodometro.value);
            if (isNaN(value) || value < 0) {
                hodometro.classList.remove('border-blue-500', 'bg-blue-50');
                hodometro.classList.add('border-red-500', 'bg-red-50');
                return false;
            } else {
                hodometro.classList.remove('border-red-500', 'bg-red-50');
                hodometro.classList.add('border-blue-500', 'bg-blue-50');
                return true;
            }
        }
        return false;
    }
    
    function mostrarError(field, mensaje) {
        removerError(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-xs mt-1';
        errorDiv.textContent = mensaje;
        field.parentNode.appendChild(errorDiv);
    }
    
    function removerError(field) {
        const errorDiv = field.parentNode.querySelector('.text-red-500');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    function validarFormulario() {
        let errores = [];
        
        // Validar hodómetro
        if (!hodometro.value) {
            errores.push('El hodómetro es requerido');
            hodometro.classList.add('border-red-500', 'bg-red-50');
        } else if (!validarHodometro()) {
            errores.push('Hodómetro inválido');
        }
        
        // Validar al menos una medición
        let tieneMediciones = false;
        [...bombasFields, ...vaporFields].forEach(id => {
            if (document.getElementById(id).value) {
                tieneMediciones = true;
            }
        });
        
        if (!tieneMediciones) {
            errores.push('Ingrese al menos una medición');
        }
        
        return {valido: errores.length === 0, errores};
    }
    
    // ========== FUNCIONES DE FORMULARIO ==========
    
    function mostrarResumen() {
        const bombasPromedio = calcularPromedio(bombasFields);
        const vaporPromedio = calcularPromedio(vaporFields);
        const bombasPorcentaje = calcularPorcentaje(bombasPromedio);
        const vaporPorcentaje = calcularPorcentaje(vaporPromedio);
        
        // Contar mediciones ingresadas
        let medicionesBombas = 0;
        let medicionesVapor = 0;
        
        bombasFields.forEach(id => {
            if (document.getElementById(id).value) medicionesBombas++;
        });
        
        vaporFields.forEach(id => {
            if (document.getElementById(id).value) medicionesVapor++;
        });
        
        const resumenHTML = `
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Hodómetro:</span>
                    <span class="text-sm font-semibold text-gray-900">${hodometro.value || 'No ingresado'} horas</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Bombas (${medicionesBombas}/10):</span>
                    <span class="text-sm font-semibold text-gray-900">${bombasPromedio.toFixed(2)} mm (${bombasPorcentaje.toFixed(2)}%)</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Vapor (${medicionesVapor}/10):</span>
                    <span class="text-sm font-semibold text-gray-900">${vaporPromedio.toFixed(2)} mm (${vaporPorcentaje.toFixed(2)}%)</span>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="flex gap-2 justify-center">
                        <span class="px-2 py-1 bg-estado-${determinarEstado(bombasPorcentaje).clase} text-white text-xs font-medium rounded">
                            Bombas: ${determinarEstado(bombasPorcentaje).texto}
                        </span>
                        <span class="px-2 py-1 bg-estado-${determinarEstado(vaporPorcentaje).clase} text-white text-xs font-medium rounded">
                            Vapor: ${determinarEstado(vaporPorcentaje).texto}
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('resumenDatos').innerHTML = resumenHTML;
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
    }
    
    function limpiarFormulario() {
        if (confirm('¿Está seguro de limpiar todos los datos?')) {
            // Limpiar campos de medición (ahora 10 cada uno)
            [...bombasFields, ...vaporFields].forEach(id => {
                const field = document.getElementById(id);
                field.value = '';
                field.classList.remove('input-valid', 'input-invalid', 'border-red-500', 'border-blue-500', 'border-green-500');
                removerError(field);
            });
            
            // Limpiar otros campos
            document.getElementById('juego_rodaja_bombas').value = '';
            document.getElementById('juego_rodaja_vapor').value = '';
            hodometro.value = '';
            hodometro.classList.remove('border-red-500', 'bg-red-50', 'border-blue-500', 'bg-blue-50');
            
            // Reiniciar cálculos
            actualizarCalculos();
            
            // Resetear alerta
            const alerta = document.querySelector('.border-red-200, .border-amber-200');
            if (alerta) {
                alerta.className = 'border border-amber-200 rounded-xl p-4 bg-amber-50/50';
                const icono = alerta.querySelector('i');
                if (icono) icono.className = 'fas fa-exclamation-triangle text-amber-500 text-xl';
                const titulo = alerta.querySelector('h4');
                if (titulo) titulo.textContent = '¡ATENCIÓN! LÍMITE DE CAMBIO';
                const texto = alerta.querySelector('p');
                if (texto) texto.textContent = 'Máximo 3% de elongación para cambio de cadena';
                const badge = alerta.querySelector('span');
                if (badge) badge.innerHTML = '<i class="fas fa-ruler mr-1"></i>PASO 173 MM';
            }
            
            // Mostrar mensaje
            mostrarMensaje('Éxito', 'Formulario limpiado correctamente', 'success');
        }
    }
    
    function mostrarMensaje(titulo, mensaje, tipo = 'info') {
        const tipos = {
            success: {color: 'green', icon: 'check-circle'},
            error: {color: 'red', icon: 'exclamation-circle'},
            warning: {color: 'amber', icon: 'exclamation-triangle'},
            info: {color: 'blue', icon: 'info-circle'}
        };
        
        const tipoInfo = tipos[tipo] || tipos.info;
        
        const mensajeHTML = `
            <div class="fixed top-4 right-4 max-w-sm w-full bg-${tipoInfo.color}-50 border border-${tipoInfo.color}-200 rounded-lg shadow-lg p-4 z-50 animate-slide-in">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${tipoInfo.icon} text-${tipoInfo.color}-500"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-${tipoInfo.color}-800">${titulo}</h3>
                        <p class="text-sm text-${tipoInfo.color}-600 mt-1">${mensaje}</p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-${tipoInfo.color}-50 text-${tipoInfo.color}-500 rounded-lg focus:ring-2 focus:ring-${tipoInfo.color}-200 p-1.5 hover:bg-${tipoInfo.color}-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        const contenedor = document.createElement('div');
        contenedor.innerHTML = mensajeHTML;
        document.body.appendChild(contenedor);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            contenedor.remove();
        }, 5000);
        
        // Remover al hacer clic
        contenedor.querySelector('button').addEventListener('click', () => {
            contenedor.remove();
        });
    }
    
    // ========== EVENT LISTENERS ==========
    
    // Escuchar cambios en las 10 mediciones de bombas
    bombasFields.forEach(id => {
        const field = document.getElementById(id);
        field.addEventListener('input', actualizarCalculos);
        field.addEventListener('blur', validarMedicion);
        field.addEventListener('focus', function() {
            this.select();
        });
    });
    
    // Escuchar cambios en las 10 mediciones de vapor
    vaporFields.forEach(id => {
        const field = document.getElementById(id);
        field.addEventListener('input', actualizarCalculos);
        field.addEventListener('blur', validarMedicion);
        field.addEventListener('focus', function() {
            this.select();
        });
    });
    
    // Escuchar hodómetro
    hodometro.addEventListener('input', function() {
        validarHodometro();
        actualizarCalculos();
    });
    
    hodometro.addEventListener('focus', function() {
        this.select();
    });
    
    // Botón limpiar
    btnLimpiar.addEventListener('click', limpiarFormulario);
    
    // Envío de formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const validacion = validarFormulario();
        
        if (!validacion.valido) {
            mostrarMensaje('Error de validación', validacion.errores.join(', '), 'error');
            return;
        }
        
        mostrarResumen();
    });
    
    // Manejo del modal
    btnCancelarModal.addEventListener('click', function() {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
    });
    
    // Confirmar envío desde modal
    btnConfirmar.addEventListener('click', function() {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        
        // Deshabilitar botón
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        
        // Enviar formulario
        setTimeout(() => {
            form.submit();
        }, 500);
    });
    
    // Cerrar modal al hacer clic fuera
    confirmModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            this.classList.remove('flex');
        }
    });
    
    // Prevenir decimales múltiples
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === '.' && this.value.includes('.')) {
                e.preventDefault();
            }
        });
    });
    
    // ========== INICIALIZACIÓN ==========
    
    // Cargar datos del localStorage si existen
    function cargarDatosGuardados() {
        const datos = localStorage.getItem('elongacionDraft');
        if (datos) {
            try {
                const parsed = JSON.parse(datos);
                
                // Cargar hodómetro
                if (parsed.hodometro) {
                    hodometro.value = parsed.hodometro;
                }
                
                // Cargar bombas (ahora 10)
                if (parsed.bombas) {
                    Object.entries(parsed.bombas).forEach(([id, value]) => {
                        const field = document.getElementById(id);
                        if (field) field.value = value;
                    });
                }
                
                // Cargar vapor (ahora 10)
                if (parsed.vapor) {
                    Object.entries(parsed.vapor).forEach(([id, value]) => {
                        const field = document.getElementById(id);
                        if (field) field.value = value;
                    });
                }
                
                actualizarCalculos();
                mostrarMensaje('Información', 'Datos recuperados del borrador anterior', 'info');
                
            } catch (error) {
                console.error('Error al cargar datos:', error);
            }
        }
    }
    
    // Guardar datos automáticamente (ahora con 10 mediciones)
    function guardarDatos() {
        if (estadoApp.datosGuardados) return;
        
        const datos = {
            hodometro: hodometro.value,
            bombas: bombasFields.reduce((acc, id) => {
                acc[id] = document.getElementById(id).value;
                return acc;
            }, {}),
            vapor: vaporFields.reduce((acc, id) => {
                acc[id] = document.getElementById(id).value;
                return acc;
            }, {}),
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('elongacionDraft', JSON.stringify(datos));
    }
    
    // Limpiar localStorage después de enviar
    function limpiarLocalStorage() {
        localStorage.removeItem('elongacionDraft');
        estadoApp.datosGuardados = true;
    }
    
    // Inicializar aplicación
    function inicializar() {
        cargarDatosGuardados();
        actualizarCalculos();
        
        // Guardar datos cada 30 segundos
        setInterval(guardarDatos, 30000);
        
        // Limpiar al salir si se envió correctamente
        window.addEventListener('beforeunload', function() {
            if (estadoApp.datosGuardados) {
                limpiarLocalStorage();
            }
        });
        
        // Marcar como enviado si hay parámetro de éxito
        if (window.location.search.includes('success=true')) {
            limpiarLocalStorage();
            mostrarMensaje('Éxito', 'Registro guardado correctamente', 'success');
        }
    }
    
    // Iniciar
    inicializar();
    
    // Animación CSS para mensajes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
});
</script>

<style>
    /* Estilos adicionales para inputs de medición */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
        height: 24px;
    }
    
    /* Clases de estado para los porcentajes */
    .text-estado-normal {
        color: #059669;
    }
    
    .text-estado-alerta {
        color: #d97706;
    }
    
    .text-estado-critico {
        color: #dc2626;
    }
    
    .bg-estado-normal {
        background-color: #10b981;
    }
    
    .bg-estado-alerta {
        background-color: #f59e0b;
    }
    
    .bg-estado-critico {
        background-color: #ef4444;
    }
    
    /* Estilos para inputs validados */
    .input-valid {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    
    .input-invalid {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    
    /* Responsive adjustments para 10 columnas */
    @media (max-width: 768px) {
        .grid-cols-5 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    
    @media (min-width: 768px) and (max-width: 1024px) {
        .grid-cols-5 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
</style>
@endsection