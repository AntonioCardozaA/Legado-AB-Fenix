@extends('layouts.app')

@section('title', 'Registrar Elongaciones de Cadena')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('elongaciones.index') }}" 
               class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <i class="fas fa-ruler-combined text-3xl text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-800">Registro de Elongaciones</h1>
        </div>
        <p class="text-gray-600">
            Registrar 10 mediciones para cada lado. | 
            <span class="font-bold text-red-600">LÍMITE DE CAMBIO: 1.46%</span> | 
            <span class="font-bold text-yellow-600">Compra desde 1.3%</span>
        </p>
    </div>

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg"><p class="text-red-600">{{ session('error') }}</p></div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('elongaciones.store') }}" id="elongacionForm">
            @csrf

            {{-- Selector de Línea --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-industry text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">Seleccionar Línea</h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
                    @php
                        $lineas = ['L-04' => 'Lav 4', 'L-05' => 'Lav 5', 'L-06' => 'Lav 6', 'L-07' => 'Lav 7', 'L-08' => 'Lav 8', 'L-09' => 'Lav 9', 'L-12' => 'Lav 12', 'L-13' => 'Lav 13'];
                        $pasosIniciales = ['L-04' => 173, 'L-05' => 140, 'L-06' => 173, 'L-07' => 173, 'L-08' => 125, 'L-09' => 140, 'L-12' => 140, 'L-13' => 140];
                    @endphp
                    @foreach($lineas as $codigo => $nombre)
                        <div class="relative">
                            <input type="radio" id="linea_{{ $loop->iteration }}" name="linea" value="{{ $codigo }}" data-paso="{{ $pasosIniciales[$codigo] }}" class="hidden peer linea-radio" {{ old('linea', $lineaSeleccionada ?? 'L-07') == $codigo ? 'checked' : '' }} required>
                            <label for="linea_{{ $loop->iteration }}" class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all duration-200">
                                <div class="text-base font-semibold text-gray-700 mb-1">{{ $codigo }}</div>
                                <div class="text-xs text-gray-500 text-center">{{ $nombre }}</div>
                                <div class="text-xs font-medium mt-1 text-blue-600">{{ $pasosIniciales[$codigo] }} mm</div>
                                <div class="absolute top-2 right-2 hidden peer-checked:block"><i class="fas fa-check-circle text-blue-500"></i></div>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('linea')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Sección LAVADORA --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-washer text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">LAVADORA <span id="linea_display">LÍNEA 7</span></h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {{-- LADO BOMBAS --}}
                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-tint text-lg text-blue-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">LADO BOMBAS</h3>
                            <span class="ml-auto px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">10 mediciones</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                            @for($i = 1; $i <= 10; $i++)
                            <div>
                                <label for="bombas_{{ $i }}" class="block text-xs font-medium text-gray-700 mb-1">M{{ $i }} <span class="text-gray-400">(mm)</span></label>
                                <input type="number" step="0.1" min="0" max="200" id="bombas_{{ $i }}" name="bombas_{{ $i }}" value="{{ old('bombas_' . $i) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-center" placeholder="173.0">
                                @error('bombas_' . $i)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            @endfor
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2"><span class="text-sm font-medium text-gray-700">PROMEDIO =</span></div>
                                <div><span id="bombas_promedio_display" class="text-xl font-bold text-blue-600">0.00</span><span class="text-sm text-gray-500 ml-1">mm</span></div>
                            </div>
                        </div>
                        <div id="alerta_bombas" class="mt-4 hidden"><div class="flex items-center gap-2 p-3 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i><span id="alerta_bombas_texto" class="text-sm font-medium"></span></div></div>
                    </div>

                    {{-- LADO VAPOR --}}
                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-wind text-lg text-green-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">LADO VAPOR</h3>
                            <span class="ml-auto px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">10 mediciones</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                            @for($i = 1; $i <= 10; $i++)
                            <div>
                                <label for="vapor_{{ $i }}" class="block text-xs font-medium text-gray-700 mb-1">M{{ $i }} <span class="text-gray-400">(mm)</span></label>
                                <input type="number" step="0.1" min="0" max="200" id="vapor_{{ $i }}" name="vapor_{{ $i }}" value="{{ old('vapor_' . $i) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm text-center" placeholder="173.0">
                                @error('vapor_' . $i)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            @endfor
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2"><span class="text-sm font-medium text-gray-700">PROMEDIO =</span></div>
                                <div><span id="vapor_promedio_display" class="text-xl font-bold text-green-600">0.00</span><span class="text-sm text-gray-500 ml-1">mm</span></div>
                            </div>
                        </div>
                        <div id="alerta_vapor" class="mt-4 hidden"><div class="flex items-center gap-2 p-3 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i><span id="alerta_vapor_texto" class="text-sm font-medium"></span></div></div>
                    </div>
                </div>

                {{-- Hodómetro --}}
                <div class="mb-8">
                    <div class="max-w-md mx-auto">
                        <label for="hodometro" class="block text-sm font-medium text-gray-700 mb-2 text-center">HODÓMETRO <span class="text-gray-400 text-xs">(opcional)</span></label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1"><input type="number" id="hodometro" name="hodometro" value="{{ old('hodometro') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-lg text-center font-medium py-3" placeholder="Ingrese horas (opcional)"></div>
                            <span class="text-lg font-medium text-gray-700 whitespace-nowrap">HORAS</span>
                        </div>
                        @error('hodometro')<p class="text-red-500 text-sm text-center mt-2">{{ $message }}</p>@enderror
                        <div id="ultima_lectura_container" class="text-center mt-2"><p class="text-gray-400 text-sm">Última lectura: <span id="ultima_lectura">@if(isset($ultimaLectura) && $ultimaLectura){{ number_format($ultimaLectura->hodometro, 0) }} horas @else Sin registro @endif</span></p></div>
                    </div>
                </div>

                {{-- Porcentajes con barras de progreso --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="border border-blue-200 rounded-xl p-6 bg-blue-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">ELONGACIÓN BOMBAS</h4>
                            <div id="bombas_porcentaje_display" class="text-3xl font-bold text-blue-600">0.00%</div>
                            <div class="mt-2"><span id="bombas_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Sin datos</span></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div id="bombas_progress" class="h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>0%</span>
                            <span class="font-bold text-yellow-600">Compra: 1.3%</span>
                            <span class="font-bold text-red-600">Cambio: 1.46%</span>
                        </div>
                    </div>

                    <div class="border border-green-200 rounded-xl p-6 bg-green-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-green-700 mb-1">ELONGACIÓN VAPOR</h4>
                            <div id="vapor_porcentaje_display" class="text-3xl font-bold text-green-600">0.00%</div>
                            <div class="mt-2"><span id="vapor_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Sin datos</span></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div id="vapor_progress" class="h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>0%</span>
                            <span class="font-bold text-yellow-600">Compra: 1.3%</span>
                            <span class="font-bold text-red-600">Cambio: 1.46%</span>
                        </div>
                    </div>
                </div>

                {{-- Alerta Límite --}}
                <div id="alerta_limite" class="mb-6 border border-amber-200 rounded-xl p-4 bg-amber-50/50">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-amber-800 mb-1">LÍMITE DE CAMBIO: 1.46%</h4>
                            <p class="text-xs text-amber-600"><span id="paso_inicial_text">Paso inicial: 173 mm</span> - <span class="font-bold text-yellow-600">Comprar desde 1.3%</span> - <span class="font-bold text-red-600">Cambio desde 1.46%</span></p>
                            <div id="mensaje_cambio_lados" class="mt-2 text-sm font-medium hidden"><i class="fas fa-exclamation-circle mr-1"></i><span id="lados_a_cambiar"></span></div>
                        </div>
                        <span id="paso_badge" class="px-3 py-1 bg-amber-100 text-amber-800 text-sm font-medium rounded-full"><i class="fas fa-ruler mr-1"></i>PASO <span id="paso_actual">173</span> MM</span>
                    </div>
                </div>

                {{-- JUEGO DE RODAJA --}}
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200"><i class="fas fa-cogs text-xl text-gray-600"></i><h2 class="text-xl font-semibold text-gray-800">JUEGO DE RODAJA - HOLGURA</h2></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label for="juego_rodaja_bombas" class="block text-sm font-medium text-gray-700 mb-2">LADO BOMBAS (mm)</label><input type="number" step="0.01" min="0" id="juego_rodaja_bombas" name="juego_rodaja_bombas" value="{{ old('juego_rodaja_bombas') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="0.00">@error('juego_rodaja_bombas')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror</div>
                        <div><label for="juego_rodaja_vapor" class="block text-sm font-medium text-gray-700 mb-2">LADO VAPOR (mm)</label><input type="number" step="0.01" min="0" id="juego_rodaja_vapor" name="juego_rodaja_vapor" value="{{ old('juego_rodaja_vapor') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="0.00">@error('juego_rodaja_vapor')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror</div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('elongaciones.index') }}" class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition flex items-center gap-2"><i class="fas fa-arrow-left"></i> Cancelar</a>
                        <button type="button" id="btnLimpiar" class="px-5 py-2.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition flex items-center gap-2 border border-gray-200"><i class="fas fa-broom"></i> Limpiar</button>
                    </div>
                    <button type="submit" class="px-8 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center gap-2 font-medium shadow-md"><i class="fas fa-save"></i> Guardar Registro</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('elongacionForm');
    const hodometro = document.getElementById('hodometro');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const lineaInputs = document.querySelectorAll('input[name="linea"]');
    const lineaDisplay = document.getElementById('linea_display');
    const alertaBombas = document.getElementById('alerta_bombas');
    const alertaBombasTexto = document.getElementById('alerta_bombas_texto');
    const alertaVapor = document.getElementById('alerta_vapor');
    const alertaVaporTexto = document.getElementById('alerta_vapor_texto');
    const pasoActual = document.getElementById('paso_actual');
    const pasoInicialText = document.getElementById('paso_inicial_text');
    
    // LÍMITES CORRECTOS
    const LIMITE_COMPRA = 1.3;
    const LIMITE_CAMBIO = 1.46;
    
    const pasosIniciales = {'L-04': 173, 'L-05': 140, 'L-06': 173, 'L-07': 173, 'L-08': 125, 'L-09': 140, 'L-12': 140, 'L-13': 140};
    const bombasFields = Array.from({length: 10}, (_, i) => `bombas_${i + 1}`);
    const vaporFields = Array.from({length: 10}, (_, i) => `vapor_${i + 1}`);
    
    function getPasoInicialActual() {
        const lineaSeleccionada = document.querySelector('input[name="linea"]:checked');
        return lineaSeleccionada ? pasosIniciales[lineaSeleccionada.value] || 173 : 173;
    }
    
    function getLineaSeleccionada() {
        const lineaSeleccionada = document.querySelector('input[name="linea"]:checked');
        return lineaSeleccionada ? lineaSeleccionada.value : 'L-07';
    }
    
    function actualizarPlaceholders() {
        const paso = getPasoInicialActual();
        bombasFields.forEach(id => { const campo = document.getElementById(id); if(campo) campo.placeholder = paso.toFixed(1); });
        vaporFields.forEach(id => { const campo = document.getElementById(id); if(campo) campo.placeholder = paso.toFixed(1); });
    }
    
    function actualizarLineaSeleccionada() {
        const lineaSeleccionada = getLineaSeleccionada();
        const paso = pasosIniciales[lineaSeleccionada] || 173;
        const numeroLinea = lineaSeleccionada.replace('L-', '');
        if(lineaDisplay) lineaDisplay.textContent = `LÍNEA ${numeroLinea}`;
        if(pasoActual) pasoActual.textContent = paso;
        if(pasoInicialText) pasoInicialText.innerHTML = `Paso inicial: ${paso} mm`;
        actualizarPlaceholders();
        actualizarCalculos();
    }
    
    function calcularPromedio(fields) {
        let sum = 0, count = 0;
        fields.forEach(id => {
            const field = document.getElementById(id);
            if(field) {
                const value = parseFloat(field.value);
                if(!isNaN(value) && value > 0) { sum += value; count++; }
            }
        });
        return count > 0 ? sum / count : 0;
    }
    
    function calcularPorcentaje(promedio) {
        const pasoInicial = getPasoInicialActual();
        if(promedio <= 0 || pasoInicial <= 0) return 0;
        return ((promedio - pasoInicial) / pasoInicial) * 100;
    }
    
    function determinarEstado(porcentaje) {
        if(porcentaje <= 0) return {clase: 'normal', texto: 'Sin datos', color: 'gray'};
        if(porcentaje < LIMITE_COMPRA) return {clase: 'normal', texto: 'Normal', color: 'green'};
        if(porcentaje < LIMITE_CAMBIO) return {clase: 'comprar', texto: 'Comprar cadena', color: 'yellow'};
        return {clase: 'critico', texto: '¡CAMBIO!', color: 'red'};
    }
    
    function actualizarBarraProgreso(elementoId, porcentaje) {
        const barra = document.getElementById(elementoId);
        if(!barra) return;
        
        let ancho;
        if(porcentaje >= LIMITE_CAMBIO) {
            ancho = 100;
        } else {
            ancho = (porcentaje / LIMITE_CAMBIO) * 100;
        }
        barra.style.width = Math.min(ancho, 100) + '%';
        
        if(porcentaje >= LIMITE_CAMBIO) {
            barra.className = 'h-3 rounded-full transition-all duration-300 bg-red-600';
        } else if(porcentaje >= LIMITE_COMPRA) {
            barra.className = 'h-3 rounded-full transition-all duration-300 bg-yellow-500';
        } else {
            barra.className = 'h-3 rounded-full transition-all duration-300 bg-green-500';
        }
    }
    
    function actualizarAlertaIndividual(lado, porcentaje) {
        const alerta = lado === 'bombas' ? alertaBombas : alertaVapor;
        const texto = lado === 'bombas' ? alertaBombasTexto : alertaVaporTexto;
        if(!alerta || !texto) return;
        
        if(porcentaje >= LIMITE_CAMBIO) {
            alerta.classList.remove('hidden');
            const alertaDiv = alerta.querySelector('div');
            if(alertaDiv) alertaDiv.className = 'flex items-center gap-2 p-3 rounded-lg bg-red-50 border border-red-200';
            texto.innerHTML = '<span class="text-red-700 font-bold">🚨 ¡ALERTA CRÍTICA! LÍMITE DE CAMBIO SUPERADO - ' + porcentaje.toFixed(2) + '%</span>';
        } else if(porcentaje >= LIMITE_COMPRA) {
            alerta.classList.remove('hidden');
            const alertaDiv = alerta.querySelector('div');
            if(alertaDiv) alertaDiv.className = 'flex items-center gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200';
            texto.innerHTML = '<span class="text-yellow-700 font-medium">🛒 ¡COMPRAR CADENA! ' + porcentaje.toFixed(2) + '% (límite 1.3%)</span>';
        } else {
            alerta.classList.add('hidden');
        }
    }
    
    function actualizarCalculos() {
        const pasoInicial = getPasoInicialActual();
        const bombasPromedio = calcularPromedio(bombasFields);
        const vaporPromedio = calcularPromedio(vaporFields);
        const bombasPorcentaje = calcularPorcentaje(bombasPromedio);
        const vaporPorcentaje = calcularPorcentaje(vaporPromedio);
        
        document.getElementById('bombas_promedio_display').textContent = bombasPromedio.toFixed(2);
        document.getElementById('vapor_promedio_display').textContent = vaporPromedio.toFixed(2);
        document.getElementById('bombas_porcentaje_display').textContent = bombasPorcentaje.toFixed(2) + '%';
        document.getElementById('vapor_porcentaje_display').textContent = vaporPorcentaje.toFixed(2) + '%';
        
        const bombasEstado = determinarEstado(bombasPorcentaje);
        const vaporEstado = determinarEstado(vaporPorcentaje);
        
        const bombasStatus = document.getElementById('bombas_status');
        const vaporStatus = document.getElementById('vapor_status');
        if(bombasStatus) {
            bombasStatus.textContent = bombasEstado.texto;
            bombasStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-${bombasEstado.color}-100 text-${bombasEstado.color}-800`;
        }
        if(vaporStatus) {
            vaporStatus.textContent = vaporEstado.texto;
            vaporStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-${vaporEstado.color}-100 text-${vaporEstado.color}-800`;
        }
        
        actualizarBarraProgreso('bombas_progress', bombasPorcentaje);
        actualizarBarraProgreso('vapor_progress', vaporPorcentaje);
        actualizarAlertaIndividual('bombas', bombasPorcentaje);
        actualizarAlertaIndividual('vapor', vaporPorcentaje);
        
        const alerta = document.getElementById('alerta_limite');
        const mensajeCambioLados = document.getElementById('mensaje_cambio_lados');
        const ladosACambiar = document.getElementById('lados_a_cambiar');
        
        if(alerta) {
            const bombasCritico = bombasPorcentaje >= LIMITE_CAMBIO;
            const vaporCritico = vaporPorcentaje >= LIMITE_CAMBIO;
            const bombasCompra = bombasPorcentaje >= LIMITE_COMPRA && bombasPorcentaje < LIMITE_CAMBIO;
            const vaporCompra = vaporPorcentaje >= LIMITE_COMPRA && vaporPorcentaje < LIMITE_CAMBIO;
            
            if(bombasCritico || vaporCritico) {
                alerta.className = 'mb-6 border border-red-200 rounded-xl p-4 bg-red-50/50';
                alerta.querySelector('i').className = 'fas fa-exclamation-circle text-red-500 text-xl';
                alerta.querySelector('h4').textContent = '🚨 ¡ALERTA CRÍTICA! CAMBIO DE CADENA REQUERIDO';
                alerta.querySelector('p').innerHTML = `<span class="font-bold">Paso inicial: ${pasoInicial} mm</span> - Superó el límite de cambio (${LIMITE_CAMBIO}%) - CAMBIO INMEDIATO`;
                if(mensajeCambioLados && ladosACambiar) {
                    let lados = [];
                    if(bombasCritico) lados.push('LADO BOMBAS');
                    if(vaporCritico) lados.push('LADO VAPOR');
                    if(lados.length > 0) {
                        mensajeCambioLados.classList.remove('hidden');
                        ladosACambiar.innerHTML = lados.length === 2 ? '<span class="font-bold text-red-700">AMBOS LADOS</span> necesitan cambio de cadena' : `<span class="font-bold text-red-700">${lados[0]}</span> necesita cambio de cadena`;
                    }
                }
            } else if(bombasCompra || vaporCompra) {
                alerta.className = 'mb-6 border border-yellow-200 rounded-xl p-4 bg-yellow-50/50';
                alerta.querySelector('i').className = 'fas fa-shopping-cart text-yellow-500 text-xl';
                alerta.querySelector('h4').textContent = '🛒 ALERTA: CONSIDERAR COMPRA DE CADENA';
                alerta.querySelector('p').innerHTML = `<span class="font-bold">Paso inicial: ${pasoInicial} mm</span> - Superó el límite de compra (${LIMITE_COMPRA}%) - Preparar compra`;
                if(mensajeCambioLados && ladosACambiar) {
                    let lados = [];
                    if(bombasCompra) lados.push('LADO BOMBAS');
                    if(vaporCompra) lados.push('LADO VAPOR');
                    if(lados.length > 0) {
                        mensajeCambioLados.classList.remove('hidden');
                        ladosACambiar.innerHTML = lados.length === 2 ? '<span class="font-bold text-yellow-700">AMBOS LADOS</span> requieren compra de cadena' : `<span class="font-bold text-yellow-700">${lados[0]}</span> requiere compra de cadena`;
                    }
                }
            } else {
                alerta.className = 'mb-6 border border-green-200 rounded-xl p-4 bg-green-50/50';
                alerta.querySelector('i').className = 'fas fa-check-circle text-green-500 text-xl';
                alerta.querySelector('h4').textContent = '✅ ESTADO NORMAL';
                alerta.querySelector('p').innerHTML = `<span class="font-bold">Paso inicial: ${pasoInicial} mm</span> - Ambos lados dentro de los límites normales (< ${LIMITE_COMPRA}%)`;
                if(mensajeCambioLados) mensajeCambioLados.classList.add('hidden');
            }
        }
    }
    
    lineaInputs.forEach(input => input.addEventListener('change', function() { actualizarLineaSeleccionada(); setTimeout(actualizarCalculos, 10); }));
    [...bombasFields, ...vaporFields].forEach(id => {
        const field = document.getElementById(id);
        if(field) field.addEventListener('input', actualizarCalculos);
    });
    if(hodometro) hodometro.addEventListener('blur', function() { const value = parseInt(this.value); if(this.value !== '' && (isNaN(value) || value < 0)) this.classList.add('border-red-500'); else this.classList.remove('border-red-500'); });
    if(btnLimpiar) btnLimpiar.addEventListener('click', function() { if(confirm('¿Está seguro de limpiar todos los datos?')) { form.reset(); setTimeout(() => actualizarLineaSeleccionada(), 50); } });
    
    actualizarLineaSeleccionada();
    actualizarCalculos();
});
</script>
@endsection