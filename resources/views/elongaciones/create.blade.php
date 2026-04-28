@extends('layouts.app')

@section('title', 'Registrar Elongaciones de Cadena')

@section('content')
@php
    $ciclosJson = $ciclosActivosPorLinea->mapWithKeys(function ($ciclo) {
        return [
            $ciclo->linea => [
                'id' => $ciclo->id,
                'codigo' => $ciclo->codigo,
                'proveedor' => $ciclo->proveedor,
                'hodometro_inicial' => $ciclo->hodometro_inicial,
                'instalada_en' => optional($ciclo->instalada_en)->format('Y-m-d'),
            ],
        ];
    })->toArray();

    $lecturasJson = $ultimasLecturasPorLinea->mapWithKeys(function ($lectura) {
        return [
            $lectura->linea => [
                'hodometro' => $lectura->hodometro,
                'hodometro_ciclo' => $lectura->hodometro_ciclo,
                'fecha' => $lectura->created_at?->format('d/m/Y H:i'),
                'ciclo' => $lectura->cadenaCiclo?->codigo,
                'proveedor' => $lectura->proveedor_actual,
            ],
        ];
    })->toArray();
@endphp

<div class="max-w-7xl mx-auto py-10 px-4">
    <div class="mb-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('elongaciones.index') }}"
                       class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 group">
                        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="font-medium">Volver</span>
                    </a>
                    <i class="fas fa-ruler-combined text-3xl text-blue-600"></i>
                    <h1 class="text-3xl font-bold text-gray-800">Registro de elongaciones</h1>
                </div>
                <p class="text-gray-600">
                    Seguimos usando el mismo cálculo de porcentajes y alertas, ahora con control por ciclo de cadena y proveedor.
                </p>
            </div>

            <a href="{{ route('elongaciones.ciclos.comparacion', ['linea' => old('linea', $lineaSeleccionada)]) }}"
               class="inline-flex items-center gap-2 px-5 py-3 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition shadow-md">
                <i class="fas fa-code-compare"></i>
                Comparar ciclos
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-600">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('elongaciones.store') }}" id="elongacionForm">
            @csrf

            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-industry text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">Seleccionar línea</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
                    @foreach($lineas as $codigo)
                        <div class="relative">
                            <input
                                type="radio"
                                id="linea_{{ $loop->iteration }}"
                                name="linea"
                                value="{{ $codigo }}"
                                data-paso="{{ $pasosIniciales[$codigo] }}"
                                class="hidden peer linea-radio"
                                {{ old('linea', $lineaSeleccionada) === $codigo ? 'checked' : '' }}
                                required
                            >
                            <label for="linea_{{ $loop->iteration }}" class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all duration-200">
                                <div class="text-base font-semibold text-gray-700 mb-1">{{ $codigo }}</div>
                                <div class="text-xs font-medium mt-1 text-blue-600">{{ $pasosIniciales[$codigo] }} mm</div>
                                <div class="absolute top-2 right-2 hidden peer-checked:block"><i class="fas fa-check-circle text-blue-500"></i></div>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('linea')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
                <div class="xl:col-span-2 border border-gray-200 rounded-xl p-5 bg-slate-50">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Ciclo activo</p>
                            <h3 id="ciclo_codigo" class="text-2xl font-bold text-slate-900">
                                {{ $cicloActivo?->codigo ?? 'Sin ciclo activo' }}
                            </h3>
                        </div>
                        <span id="ciclo_estado_badge" class="px-3 py-1 rounded-full text-xs font-semibold {{ $cicloActivo ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $cicloActivo ? 'Continuidad activa' : 'Se abrirá el primer ciclo' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="rounded-lg bg-white border border-slate-200 p-4">
                            <p class="text-slate-500 mb-1">Proveedor del ciclo</p>
                            <p id="ciclo_proveedor" class="font-semibold text-slate-800">{{ $cicloActivo?->proveedor ?? 'Pendiente por definir' }}</p>
                        </div>
                        <div class="rounded-lg bg-white border border-slate-200 p-4">
                            <p class="text-slate-500 mb-1">Hodómetro base</p>
                            <p id="ciclo_hodometro_inicial" class="font-semibold text-slate-800">
                                {{ $cicloActivo?->hodometro_inicial_formateado ?? 'Sin base registrada' }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-white border border-slate-200 p-4">
                            <p class="text-slate-500 mb-1">Instalada en</p>
                            <p id="ciclo_instalada_en" class="font-semibold text-slate-800">{{ optional($cicloActivo?->instalada_en)->format('d/m/Y') ?? 'Sin fecha' }}</p>
                        </div>
                    </div>
                </div>

                <div class="border border-blue-200 rounded-xl p-5 bg-blue-50/70">
                    <p class="text-sm font-medium text-blue-700 mb-1">Última lectura de la línea</p>
                    <p id="ultima_lectura_texto" class="text-xl font-bold text-blue-900">
                        @if($ultimaLectura && $ultimaLectura->hodometro !== null)
                            {{ $ultimaLectura->hodometro_formateado }}
                        @else
                            Sin registro
                        @endif
                    </p>
                    <div class="mt-3 text-sm text-blue-900 space-y-1">
                        <p id="ultima_lectura_ciclo">Ciclo: {{ $ultimaLectura?->cadenaCiclo?->codigo ?? 'Sin ciclo' }}</p>
                        <p id="ultima_lectura_proveedor">Proveedor: {{ $ultimaLectura?->proveedor_actual ?? 'Sin proveedor' }}</p>
                        <p id="ultima_lectura_hodometro_ciclo">
                            Horas del ciclo: {{ $ultimaLectura?->hodometro_ciclo_formateado ?? 'Sin dato' }}
                        </p>
                        <p id="ultima_lectura_fecha">Última fecha: {{ $ultimaLectura?->created_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                    </div>
                </div>
            </div>

            <div class="mb-8 border border-amber-200 rounded-2xl bg-amber-50/60 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <input type="hidden" name="nueva_cadena" value="0">
                            <input type="checkbox" id="nueva_cadena" name="nueva_cadena" value="1" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" {{ old('nueva_cadena') ? 'checked' : '' }}>
                            <label for="nueva_cadena" class="text-lg font-semibold text-amber-900">Instalar nueva cadena / reiniciar ciclo</label>
                        </div>
                        <p class="text-sm text-amber-800">
                            Al activar esta opción se cierra el ciclo activo de la línea y se crea uno nuevo con su proveedor, base de hodómetro y trazabilidad propia.
                        </p>
                    </div>
                    <div class="text-sm text-amber-900 bg-white/80 border border-amber-200 rounded-lg px-4 py-3">
                        <p class="font-semibold">Límites vigentes</p>
                        <p>Compra desde 1.30%</p>
                        <p>Cambio desde 1.46%</p>
                    </div>
                </div>

                <div id="panel_nueva_cadena" class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 {{ old('nueva_cadena') ? '' : 'hidden' }}">
                    <div>
                        <label for="proveedor" class="block text-sm font-medium text-gray-700 mb-2">Proveedor</label>
                        <input type="text" id="proveedor" name="proveedor" value="{{ old('proveedor') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Proveedor de la cadena">
                        @error('proveedor')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="hodometro_inicial" class="block text-sm font-medium text-gray-700 mb-2">Hodómetro base del ciclo</label>
                        <input type="number" id="hodometro_inicial" name="hodometro_inicial" value="{{ old('hodometro_inicial') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Ej. 1205 para 12:05 h">
                        <p class="text-xs text-gray-500 mt-1">Vista previa: <span id="hodometro_inicial_preview">{{ old('hodometro_inicial') !== null && old('hodometro_inicial') !== '' ? \App\Support\HodometroHoras::formatear(old('hodometro_inicial')) : 'Sin captura' }}</span></p>
                        @error('hodometro_inicial')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="fecha_instalacion" class="block text-sm font-medium text-gray-700 mb-2">Fecha de instalación</label>
                        <input type="date" id="fecha_instalacion" name="fecha_instalacion" value="{{ old('fecha_instalacion', now()->toDateString()) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        @error('fecha_instalacion')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 xl:col-span-1">
                        <label for="observaciones_cadena" class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                        <textarea id="observaciones_cadena" name="observaciones_cadena" rows="1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Motivo del cambio, lote, observaciones">{{ old('observaciones_cadena') }}</textarea>
                        @error('observaciones_cadena')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-washer text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">Lavadora <span id="linea_display">{{ old('linea', $lineaSeleccionada) }}</span></h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-tint text-lg text-blue-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">Lado bombas</h3>
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
                        <div class="pt-4 border-t border-gray-200 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Promedio</span>
                            <div><span id="bombas_promedio_display" class="text-xl font-bold text-blue-600">0.00</span><span class="text-sm text-gray-500 ml-1">mm</span></div>
                        </div>
                        <div id="alerta_bombas" class="mt-4 hidden"><div class="flex items-center gap-2 p-3 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i><span id="alerta_bombas_texto" class="text-sm font-medium"></span></div></div>
                    </div>

                    <div class="border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-wind text-lg text-green-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">Lado vapor</h3>
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
                        <div class="pt-4 border-t border-gray-200 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Promedio</span>
                            <div><span id="vapor_promedio_display" class="text-xl font-bold text-green-600">0.00</span><span class="text-sm text-gray-500 ml-1">mm</span></div>
                        </div>
                        <div id="alerta_vapor" class="mt-4 hidden"><div class="flex items-center gap-2 p-3 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i><span id="alerta_vapor_texto" class="text-sm font-medium"></span></div></div>
                    </div>
                </div>

                <div class="mb-8">
                    <div class="max-w-md mx-auto">
                        <label for="hodometro" class="block text-sm font-medium text-gray-700 mb-2 text-center">Hodómetro actual <span class="text-gray-400 text-xs">(opcional)</span></label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1"><input type="number" id="hodometro" name="hodometro" value="{{ old('hodometro') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-lg text-center font-medium py-3" placeholder="Ej. 1205 para 12:05 h"></div>
                            <span class="text-lg font-medium text-gray-700 whitespace-nowrap">HORAS</span>
                        </div>
                        <p class="text-xs text-center text-gray-500 mt-2">Vista previa: <span id="hodometro_preview">{{ old('hodometro') !== null && old('hodometro') !== '' ? \App\Support\HodometroHoras::formatear(old('hodometro')) : 'Sin captura' }}</span></p>
                        @error('hodometro')<p class="text-red-500 text-sm text-center mt-2">{{ $message }}</p>@enderror
                        <div class="text-center mt-2 text-sm text-gray-500">
                            <p>Última lectura: <span id="ultima_lectura_resumen">{{ $ultimaLectura && $ultimaLectura->hodometro !== null ? number_format($ultimaLectura->hodometro, 0) . ' h' : 'Sin registro' }}</span></p>
                            <p>Horas acumuladas del ciclo: <span id="ultima_lectura_ciclo_resumen">{{ $ultimaLectura && $ultimaLectura->hodometro_ciclo !== null ? number_format($ultimaLectura->hodometro_ciclo, 0) . ' h' : 'Sin dato' }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="border border-blue-200 rounded-xl p-6 bg-blue-50/30">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">Elongación bombas</h4>
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
                            <h4 class="text-sm font-medium text-green-700 mb-1">Elongación vapor</h4>
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

                <div id="alerta_limite" class="mb-6 border border-amber-200 rounded-xl p-4 bg-amber-50/50">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-amber-800 mb-1">Límites del análisis</h4>
                            <p class="text-xs text-amber-600"><span id="paso_inicial_text">Paso inicial: {{ $pasosIniciales[old('linea', $lineaSeleccionada)] ?? 173 }} mm</span> - compra desde 1.3% - cambio desde 1.46%</p>
                            <div id="mensaje_cambio_lados" class="mt-2 text-sm font-medium hidden"><i class="fas fa-exclamation-circle mr-1"></i><span id="lados_a_cambiar"></span></div>
                        </div>
                        <span id="paso_badge" class="px-3 py-1 bg-amber-100 text-amber-800 text-sm font-medium rounded-full">
                            <i class="fas fa-ruler mr-1"></i>PASO <span id="paso_actual">{{ $pasosIniciales[old('linea', $lineaSeleccionada)] ?? 173 }}</span> MM
                        </span>
                    </div>
                </div>

                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                        <i class="fas fa-cogs text-xl text-gray-600"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Juego de rodaja - holgura</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="juego_rodaja_bombas" class="block text-sm font-medium text-gray-700 mb-2">Lado bombas (mm)</label>
                            <input type="number" step="0.01" min="0" id="juego_rodaja_bombas" name="juego_rodaja_bombas" value="{{ old('juego_rodaja_bombas') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="0.00">
                            @error('juego_rodaja_bombas')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="juego_rodaja_vapor" class="block text-sm font-medium text-gray-700 mb-2">Lado vapor (mm)</label>
                            <input type="number" step="0.01" min="0" id="juego_rodaja_vapor" name="juego_rodaja_vapor" value="{{ old('juego_rodaja_vapor') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="0.00">
                            @error('juego_rodaja_vapor')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('elongaciones.index') }}" class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition flex items-center gap-2"><i class="fas fa-arrow-left"></i> Cancelar</a>
                        <button type="button" id="btnLimpiar" class="px-5 py-2.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition flex items-center gap-2 border border-gray-200"><i class="fas fa-broom"></i> Limpiar</button>
                    </div>
                    <button type="submit" class="px-8 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center gap-2 font-medium shadow-md"><i class="fas fa-save"></i> Guardar registro</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('elongacionForm');
    const hodometro = document.getElementById('hodometro');
    const hodometroInicial = document.getElementById('hodometro_inicial');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const nuevaCadena = document.getElementById('nueva_cadena');
    const panelNuevaCadena = document.getElementById('panel_nueva_cadena');
    const proveedorInput = document.getElementById('proveedor');
    const lineaInputs = document.querySelectorAll('input[name="linea"]');
    const lineaDisplay = document.getElementById('linea_display');
    const alertaBombas = document.getElementById('alerta_bombas');
    const alertaBombasTexto = document.getElementById('alerta_bombas_texto');
    const alertaVapor = document.getElementById('alerta_vapor');
    const alertaVaporTexto = document.getElementById('alerta_vapor_texto');
    const pasoActual = document.getElementById('paso_actual');
    const pasoInicialText = document.getElementById('paso_inicial_text');

    const LIMITE_COMPRA = 1.3;
    const LIMITE_CAMBIO = 1.46;
    const pasosIniciales = @json($pasosIniciales);
    const ciclosActivos = @json($ciclosJson);
    const ultimasLecturas = @json($lecturasJson);
    const bombasFields = Array.from({ length: 10 }, (_, i) => `bombas_${i + 1}`);
    const vaporFields = Array.from({ length: 10 }, (_, i) => `vapor_${i + 1}`);

    function getLineaSeleccionada() {
        const lineaSeleccionada = document.querySelector('input[name="linea"]:checked');
        return lineaSeleccionada ? lineaSeleccionada.value : 'L-04';
    }

    function getPasoInicialActual() {
        const linea = getLineaSeleccionada();
        return pasosIniciales[linea] || 173;
    }

    function formatearHodometro(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return null;
        }

        const numero = Number(valor);
        if (!Number.isFinite(numero)) {
            return null;
        }

        const entero = Math.trunc(numero);
        const horas = Math.trunc(entero / 100);
        const segundos = Math.abs(entero % 100).toString().padStart(2, '0');

        return `${horas.toLocaleString()}:${segundos} h`;
    }

    function actualizarVistaPreviaHodometro(campo, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return;

        preview.textContent = formatearHodometro(campo ? campo.value : null) || 'Sin captura';
    }

    function actualizarPanelNuevaCadena() {
        const linea = getLineaSeleccionada();
        const ciclo = ciclosActivos[linea] || null;
        const crearNuevo = nuevaCadena.checked;

        panelNuevaCadena.classList.toggle('hidden', !crearNuevo);
        proveedorInput.required = crearNuevo || !ciclo;

        document.getElementById('ciclo_codigo').textContent = ciclo ? ciclo.codigo : 'Sin ciclo activo';
        document.getElementById('ciclo_proveedor').textContent = ciclo ? ciclo.proveedor : 'Pendiente por definir';
        document.getElementById('ciclo_hodometro_inicial').textContent = ciclo && ciclo.hodometro_inicial !== null ? formatearHodometro(ciclo.hodometro_inicial) : 'Sin base registrada';
        document.getElementById('ciclo_instalada_en').textContent = ciclo && ciclo.instalada_en ? ciclo.instalada_en.split('-').reverse().join('/') : 'Sin fecha';

        const badge = document.getElementById('ciclo_estado_badge');
        if (crearNuevo) {
            badge.textContent = 'Se creará un nuevo ciclo';
            badge.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700';
        } else if (ciclo) {
            badge.textContent = 'Continuidad activa';
            badge.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700';
        } else {
            badge.textContent = 'Se abrirá el primer ciclo';
            badge.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700';
        }
    }

    function actualizarUltimaLectura() {
        const linea = getLineaSeleccionada();
        const lectura = ultimasLecturas[linea] || null;

        document.getElementById('ultima_lectura_texto').textContent = lectura && lectura.hodometro !== null ? formatearHodometro(lectura.hodometro) : 'Sin registro';
        document.getElementById('ultima_lectura_ciclo').textContent = `Ciclo: ${lectura && lectura.ciclo ? lectura.ciclo : 'Sin ciclo'}`;
        document.getElementById('ultima_lectura_proveedor').textContent = `Proveedor: ${lectura && lectura.proveedor ? lectura.proveedor : 'Sin proveedor'}`;
        document.getElementById('ultima_lectura_hodometro_ciclo').textContent = `Horas del ciclo: ${lectura && lectura.hodometro_ciclo !== null ? formatearHodometro(lectura.hodometro_ciclo) : 'Sin dato'}`;
        document.getElementById('ultima_lectura_fecha').textContent = `Última fecha: ${lectura && lectura.fecha ? lectura.fecha : 'Sin fecha'}`;
        document.getElementById('ultima_lectura_resumen').textContent = lectura && lectura.hodometro !== null ? formatearHodometro(lectura.hodometro) : 'Sin registro';
        document.getElementById('ultima_lectura_ciclo_resumen').textContent = lectura && lectura.hodometro_ciclo !== null ? formatearHodometro(lectura.hodometro_ciclo) : 'Sin dato';
    }

    function actualizarPlaceholders() {
        const paso = getPasoInicialActual();
        bombasFields.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.placeholder = paso.toFixed(1);
        });
        vaporFields.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.placeholder = paso.toFixed(1);
        });
    }

    function actualizarLineaSeleccionada() {
        const linea = getLineaSeleccionada();
        const paso = getPasoInicialActual();
        if (lineaDisplay) lineaDisplay.textContent = linea;
        if (pasoActual) pasoActual.textContent = paso;
        if (pasoInicialText) pasoInicialText.innerHTML = `Paso inicial: ${paso} mm`;
        actualizarPlaceholders();
        actualizarPanelNuevaCadena();
        actualizarUltimaLectura();
        actualizarCalculos();
    }

    function calcularPromedio(fields) {
        let sum = 0;
        let count = 0;
        fields.forEach(id => {
            const field = document.getElementById(id);
            if (!field) return;
            const value = parseFloat(field.value);
            if (!Number.isNaN(value) && value > 0) {
                sum += value;
                count++;
            }
        });
        return count > 0 ? sum / count : 0;
    }

    function calcularPorcentaje(promedio) {
        const pasoInicial = getPasoInicialActual();
        if (promedio <= 0 || pasoInicial <= 0) return 0;
        return ((promedio - pasoInicial) / pasoInicial) * 100;
    }

    function determinarEstado(porcentaje) {
        if (porcentaje <= 0) return { texto: 'Sin datos', color: 'gray' };
        if (porcentaje < LIMITE_COMPRA) return { texto: 'Normal', color: 'green' };
        if (porcentaje < LIMITE_CAMBIO) return { texto: 'Comprar cadena', color: 'yellow' };
        return { texto: 'Cambio requerido', color: 'red' };
    }

    function actualizarBarraProgreso(elementoId, porcentaje) {
        const barra = document.getElementById(elementoId);
        if (!barra) return;

        const ancho = porcentaje >= LIMITE_CAMBIO ? 100 : Math.min((porcentaje / LIMITE_CAMBIO) * 100, 100);
        barra.style.width = `${ancho}%`;

        if (porcentaje >= LIMITE_CAMBIO) {
            barra.className = 'h-3 rounded-full transition-all duration-300 bg-red-600';
        } else if (porcentaje >= LIMITE_COMPRA) {
            barra.className = 'h-3 rounded-full transition-all duration-300 bg-yellow-500';
        } else {
            barra.className = 'h-3 rounded-full transition-all duration-300 bg-green-500';
        }
    }

    function actualizarAlertaIndividual(lado, porcentaje) {
        const alerta = lado === 'bombas' ? alertaBombas : alertaVapor;
        const texto = lado === 'bombas' ? alertaBombasTexto : alertaVaporTexto;
        if (!alerta || !texto) return;

        if (porcentaje >= LIMITE_CAMBIO) {
            alerta.classList.remove('hidden');
            const alertaDiv = alerta.querySelector('div');
            if (alertaDiv) alertaDiv.className = 'flex items-center gap-2 p-3 rounded-lg bg-red-50 border border-red-200';
            texto.innerHTML = `<span class="text-red-700 font-bold">Cambio requerido: ${porcentaje.toFixed(2)}%</span>`;
        } else if (porcentaje >= LIMITE_COMPRA) {
            alerta.classList.remove('hidden');
            const alertaDiv = alerta.querySelector('div');
            if (alertaDiv) alertaDiv.className = 'flex items-center gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200';
            texto.innerHTML = `<span class="text-yellow-700 font-medium">Considerar compra: ${porcentaje.toFixed(2)}%</span>`;
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
        document.getElementById('bombas_porcentaje_display').textContent = `${bombasPorcentaje.toFixed(2)}%`;
        document.getElementById('vapor_porcentaje_display').textContent = `${vaporPorcentaje.toFixed(2)}%`;

        const bombasEstado = determinarEstado(bombasPorcentaje);
        const vaporEstado = determinarEstado(vaporPorcentaje);

        const bombasStatus = document.getElementById('bombas_status');
        const vaporStatus = document.getElementById('vapor_status');
        if (bombasStatus) {
            bombasStatus.textContent = bombasEstado.texto;
            bombasStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-${bombasEstado.color}-100 text-${bombasEstado.color}-800`;
        }
        if (vaporStatus) {
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

        if (!alerta) return;

        const bombasCritico = bombasPorcentaje >= LIMITE_CAMBIO;
        const vaporCritico = vaporPorcentaje >= LIMITE_CAMBIO;
        const bombasCompra = bombasPorcentaje >= LIMITE_COMPRA && bombasPorcentaje < LIMITE_CAMBIO;
        const vaporCompra = vaporPorcentaje >= LIMITE_COMPRA && vaporPorcentaje < LIMITE_CAMBIO;

        if (bombasCritico || vaporCritico) {
            alerta.className = 'mb-6 border border-red-200 rounded-xl p-4 bg-red-50/50';
            alerta.querySelector('i').className = 'fas fa-exclamation-circle text-red-500 text-xl';
            alerta.querySelector('h4').textContent = 'Alerta crítica: cambio de cadena requerido';
            alerta.querySelector('p').innerHTML = `<span class="font-bold">Paso inicial: ${pasoInicial} mm</span> - se superó el límite de cambio (${LIMITE_CAMBIO}%)`;
            if (mensajeCambioLados && ladosACambiar) {
                const lados = [];
                if (bombasCritico) lados.push('lado bombas');
                if (vaporCritico) lados.push('lado vapor');
                mensajeCambioLados.classList.remove('hidden');
                ladosACambiar.textContent = lados.join(' y ');
            }
        } else if (bombasCompra || vaporCompra) {
            alerta.className = 'mb-6 border border-yellow-200 rounded-xl p-4 bg-yellow-50/50';
            alerta.querySelector('i').className = 'fas fa-shopping-cart text-yellow-500 text-xl';
            alerta.querySelector('h4').textContent = 'Alerta: considerar compra de cadena';
            alerta.querySelector('p').innerHTML = `<span class="font-bold">Paso inicial: ${pasoInicial} mm</span> - se superó el límite de compra (${LIMITE_COMPRA}%)`;
            if (mensajeCambioLados && ladosACambiar) {
                const lados = [];
                if (bombasCompra) lados.push('lado bombas');
                if (vaporCompra) lados.push('lado vapor');
                mensajeCambioLados.classList.remove('hidden');
                ladosACambiar.textContent = lados.join(' y ');
            }
        } else {
            alerta.className = 'mb-6 border border-green-200 rounded-xl p-4 bg-green-50/50';
            alerta.querySelector('i').className = 'fas fa-check-circle text-green-500 text-xl';
            alerta.querySelector('h4').textContent = 'Estado normal';
            alerta.querySelector('p').innerHTML = `<span class="font-bold">Paso inicial: ${pasoInicial} mm</span> - ambos lados dentro de los límites normales`;
            if (mensajeCambioLados) mensajeCambioLados.classList.add('hidden');
        }
    }

    lineaInputs.forEach(input => input.addEventListener('change', actualizarLineaSeleccionada));
    [...bombasFields, ...vaporFields].forEach(id => {
        const field = document.getElementById(id);
        if (field) field.addEventListener('input', actualizarCalculos);
    });

    nuevaCadena.addEventListener('change', actualizarPanelNuevaCadena);

    if (hodometro) {
        hodometro.addEventListener('input', function () {
            actualizarVistaPreviaHodometro(this, 'hodometro_preview');
        });
        hodometro.addEventListener('blur', function () {
            const value = parseInt(this.value, 10);
            if (this.value !== '' && (Number.isNaN(value) || value < 0)) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });
    }

    if (hodometroInicial) {
        hodometroInicial.addEventListener('input', function () {
            actualizarVistaPreviaHodometro(this, 'hodometro_inicial_preview');
        });
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            if (confirm('¿Está seguro de limpiar todos los datos?')) {
                form.reset();
                setTimeout(actualizarLineaSeleccionada, 50);
            }
        });
    }

    actualizarLineaSeleccionada();
    actualizarCalculos();
    actualizarVistaPreviaHodometro(hodometro, 'hodometro_preview');
    actualizarVistaPreviaHodometro(hodometroInicial, 'hodometro_inicial_preview');
});
</script>
@endsection
