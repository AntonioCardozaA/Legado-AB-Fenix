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
                'cadena_ciclo_id' => $lectura->cadena_ciclo_id,
                'hodometro' => $lectura->hodometro,
                'hodometro_ciclo' => $lectura->hodometro_ciclo,
                'bombas_promedio' => $lectura->bombas_promedio,
                'vapor_promedio' => $lectura->vapor_promedio,
                'fecha' => $lectura->created_at?->format('d/m/Y H:i'),
                'ciclo' => $lectura->cadenaCiclo?->codigo,
                'proveedor' => $lectura->proveedor_actual,
            ],
        ];
    })->toArray();
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 sm:py-10">
    <div class="mb-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-2 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('elongaciones.index') }}"
                       class="flex w-full items-center justify-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 group sm:w-auto">
                        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="font-medium">Volver</span>
                    </a>
                    <i class="fas fa-ruler-combined text-2xl text-blue-600 sm:text-3xl"></i>
                    <h1 class="text-2xl font-bold text-gray-800 sm:text-3xl">Registro de elongaciones</h1>
                </div>
                <p class="text-gray-600">
                    Seguimos usando el mismo cálculo de porcentajes y alertas, ahora con control por ciclo de cadena y proveedor.
                </p>
            </div>

            <a href="{{ route('elongaciones.ciclos.comparacion', ['linea' => old('linea', $lineaSeleccionada)]) }}"
               class="inline-flex w-full items-center justify-center gap-2 px-5 py-3 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition shadow-md sm:w-auto">
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

    <div class="rounded-2xl bg-white p-4 shadow-lg sm:p-8">
        <form method="POST" action="{{ route('elongaciones.store') }}" id="elongacionForm">
            @csrf
            <input type="hidden" name="form_token" value="{{ $formToken }}">

            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-industry text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">Seleccionar línea</h2>
                </div>

                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8">
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
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
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

            <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50/60 p-4 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="mb-2 flex items-start gap-3">
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

                @error('nueva_cadena_costs')
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        No se pudo registrar la nueva cadena porque falta configuracion en lavadoras/costos.
                    </div>
                @enderror

                @error('form_token')
                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ $message }}
                    </div>
                @enderror

                <div id="panel_resumen_costos" class="hidden">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Resumen previo de costos</h3>
                            <p class="text-sm text-slate-500">
                                Se usará el catálogo activo para estimar el costo y, al guardar, se conservará una copia histórica del precio unitario aplicado.
                            </p>
                        </div>
                        <span id="cost_preview_status" class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                            Configuración lista
                        </span>
                    </div>

                    @error('nueva_cadena_costs')
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    @error('form_token')
                        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lavadora</p>
                            <p id="cost_preview_linea" class="mt-2 text-xl font-bold text-slate-900">{{ old('linea', $lineaSeleccionada) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de cadena</p>
                            <p id="cost_preview_chain_type" class="mt-2 text-base font-semibold text-slate-900">Pendiente</p>
                        </div>
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Costo total estimado</p>
                            <p id="cost_preview_total" class="mt-2 text-2xl font-bold text-blue-900">$0.00</p>
                        </div>
                    </div>

                    <div id="cost_preview_error" class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Material</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Cantidad</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Costo unitario</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="cost_preview_rows" class="divide-y divide-slate-100 bg-white"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-washer text-xl text-blue-600"></i>
                    <h2 class="text-xl font-semibold text-gray-800">Lavadora <span id="linea_display">{{ old('linea', $lineaSeleccionada) }}</span></h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="rounded-xl border border-gray-200 p-4 shadow-sm sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <i class="fas fa-tint text-lg text-blue-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">Lado bombas</h3>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded sm:ml-auto">10 mediciones</span>
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
                        <div class="flex flex-col gap-1 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-sm font-medium text-gray-700">Promedio</span>
                            <div><span id="bombas_promedio_display" class="text-xl font-bold text-blue-600">0.00</span><span class="text-sm text-gray-500 ml-1">mm</span></div>
                        </div>
                        <div id="alerta_bombas" class="mt-4 hidden"><div class="flex items-center gap-2 p-3 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i><span id="alerta_bombas_texto" class="text-sm font-medium"></span></div></div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4 shadow-sm sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <i class="fas fa-wind text-lg text-green-600"></i>
                            <h3 class="text-lg font-medium text-gray-800">Lado vapor</h3>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded sm:ml-auto">10 mediciones</span>
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
                        <div class="flex flex-col gap-1 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-sm font-medium text-gray-700">Promedio</span>
                            <div><span id="vapor_promedio_display" class="text-xl font-bold text-green-600">0.00</span><span class="text-sm text-gray-500 ml-1">mm</span></div>
                        </div>
                        <div id="alerta_vapor" class="mt-4 hidden"><div class="flex items-center gap-2 p-3 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i><span id="alerta_vapor_texto" class="text-sm font-medium"></span></div></div>
                    </div>
                </div>

                <div class="mb-8">
                    <div class="max-w-md mx-auto">
                        <label for="hodometro" class="block text-sm font-medium text-gray-700 mb-2 text-center">Hodómetro actual <span class="text-gray-400 text-xs">(opcional)</span></label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
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
                    <div class="rounded-xl border border-blue-200 bg-blue-50/30 p-4 sm:p-6">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">Elongación bombas</h4>
                            <div id="bombas_porcentaje_display" class="text-2xl font-bold text-blue-600 sm:text-3xl">0.00%</div>
                            <div class="mt-2"><span id="bombas_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Sin datos</span></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div id="bombas_progress" class="h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-gray-500">
                            <span>0%</span>
                            <span class="font-bold text-yellow-600">Compra: 1.3%</span>
                            <span class="font-bold text-red-600">Cambio: 1.46%</span>
                        </div>
                        <div class="mt-4 space-y-1 text-center">
                            <p class="text-sm font-medium text-blue-900">Aumento vs paso base: <span id="bombas_incremento_base_display">0.00 mm</span></p>
                            <p class="text-xs text-gray-600"><span id="bombas_variacion_revision_label">Comparativo:</span> <span id="bombas_variacion_revision_display" class="font-medium text-slate-600">Sin referencia</span></p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-green-200 bg-green-50/30 p-4 sm:p-6">
                        <div class="text-center mb-4">
                            <h4 class="text-sm font-medium text-green-700 mb-1">Elongación vapor</h4>
                            <div id="vapor_porcentaje_display" class="text-2xl font-bold text-green-600 sm:text-3xl">0.00%</div>
                            <div class="mt-2"><span id="vapor_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Sin datos</span></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div id="vapor_progress" class="h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-gray-500">
                            <span>0%</span>
                            <span class="font-bold text-yellow-600">Compra: 1.3%</span>
                            <span class="font-bold text-red-600">Cambio: 1.46%</span>
                        </div>
                        <div class="mt-4 space-y-1 text-center">
                            <p class="text-sm font-medium text-green-900">Aumento vs paso base: <span id="vapor_incremento_base_display">0.00 mm</span></p>
                            <p class="text-xs text-gray-600"><span id="vapor_variacion_revision_label">Comparativo:</span> <span id="vapor_variacion_revision_display" class="font-medium text-slate-600">Sin referencia</span></p>
                        </div>
                    </div>
                </div>

                <div id="alerta_limite" class="mb-6 border border-amber-200 rounded-xl p-4 bg-amber-50/50">
                    <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
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

                <div class="flex flex-col md:flex-row justify-between items-stretch gap-4 pt-6 border-t border-gray-200">
                    <div class="create-actions">
                        <a href="{{ route('elongaciones.index') }}" class="create-action create-action--secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
                        <button type="button" id="btnLimpiar" class="create-action create-action--secondary"><i class="fas fa-broom"></i> Limpiar</button>
                    </div>
                    <button type="submit" class="create-action"><i class="fas fa-save"></i> Guardar registro</button>
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
    const panelResumenCostos = document.getElementById('panel_resumen_costos');
    const costPreviewRows = document.getElementById('cost_preview_rows');
    const costPreviewError = document.getElementById('cost_preview_error');
    const costPreviewStatus = document.getElementById('cost_preview_status');
    const costPreviewLinea = document.getElementById('cost_preview_linea');
    const costPreviewChainType = document.getElementById('cost_preview_chain_type');
    const costPreviewTotal = document.getElementById('cost_preview_total');

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

    function money(value) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
        }).format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderCostPreview() {
        if (panelResumenCostos) {
            panelResumenCostos.classList.add('hidden');
        }

        return;

        if (!panelResumenCostos || !costPreviewRows || !costPreviewLinea || !costPreviewChainType || !costPreviewTotal) {
            return;
        }

        const linea = getLineaSeleccionada();
        const preview = chainCostSummaries[linea] || null;
        const visible = nuevaCadena.checked;

        panelResumenCostos.classList.toggle('hidden', !visible);

        if (!visible) {
            return;
        }

        costPreviewLinea.textContent = linea;
        costPreviewRows.innerHTML = '';

        if (!preview) {
            costPreviewChainType.textContent = 'Sin configuración';
            costPreviewTotal.textContent = money(0);
            costPreviewError.textContent = 'No existe una configuración de costos para la lavadora seleccionada.';
            costPreviewError.classList.remove('hidden');
            costPreviewStatus.textContent = 'Configuración incompleta';
            costPreviewStatus.className = 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800';
            return;
        }

        costPreviewChainType.textContent = preview.chain_type || 'Pendiente';
        costPreviewTotal.textContent = money(preview.total_cost || 0);

        if (Array.isArray(preview.errors) && preview.errors.length > 0) {
            costPreviewError.innerHTML = preview.errors.map((error) => `<div>${escapeHtml(error)}</div>`).join('');
            costPreviewError.classList.remove('hidden');
            costPreviewStatus.textContent = 'Faltan datos de catálogo';
            costPreviewStatus.className = 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800';
        } else {
            costPreviewError.classList.add('hidden');
            costPreviewError.textContent = '';
            costPreviewStatus.textContent = 'Configuración lista';
            costPreviewStatus.className = 'inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800';
        }

        costPreviewRows.innerHTML = (preview.items || []).map((item) => {
            const unit = item.unidad_medida || 'unidad';
            const subtotal = item.subtotal === null || item.subtotal === undefined ? 'Pendiente' : money(item.subtotal);
            const unitCost = item.costo_unitario === null || item.costo_unitario === undefined ? 'Pendiente' : money(item.costo_unitario);

            return `
                <tr>
                    <td class="px-4 py-4 align-top">
                        <div class="font-semibold text-slate-900">${escapeHtml(item.nombre)}</div>
                        <div class="mt-1 text-xs text-slate-500">${escapeHtml(item.descripcion || '')}</div>
                    </td>
                    <td class="px-4 py-4 align-top text-slate-600">${escapeHtml(item.sku || 'Sin SKU')}</td>
                    <td class="px-4 py-4 align-top text-slate-700">${Number(item.cantidad || 0).toFixed(2)} ${escapeHtml(unit)}</td>
                    <td class="px-4 py-4 align-top text-slate-700">${escapeHtml(unitCost)}</td>
                    <td class="px-4 py-4 align-top font-semibold text-slate-900">${escapeHtml(subtotal)}</td>
                </tr>
            `;
        }).join('');
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
        renderCostPreview();
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
        renderCostPreview();
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

    function calcularIncrementoMm(promedio) {
        const pasoInicial = getPasoInicialActual();
        if (promedio <= 0 || pasoInicial <= 0) return 0;
        return Math.max(promedio - pasoInicial, 0);
    }

    function formatearMm(valor) {
        return `${Number(valor || 0).toFixed(2)} mm`;
    }

    function formatearMmConSigno(valor) {
        const numero = Number(valor || 0);
        const prefijo = numero > 0 ? '+' : '';

        return `${prefijo}${numero.toFixed(2)} mm`;
    }

    function obtenerVariacionVsRevision(lado, promedioActual) {
        if (promedioActual <= 0) {
            return {
                label: 'Comparativo:',
                texto: 'Sin captura actual',
                className: 'font-medium text-slate-600',
            };
        }

        if (nuevaCadena.checked) {
            return {
                label: 'Comparativo:',
                texto: 'Primera revision del nuevo ciclo',
                className: 'font-medium text-slate-600',
            };
        }

        const linea = getLineaSeleccionada();
        const lectura = ultimasLecturas[linea] || null;
        const ciclo = ciclosActivos[linea] || null;
        const promedioAnteriorRaw = lectura ? lectura[`${lado}_promedio`] : null;
        const promedioAnterior = promedioAnteriorRaw === null || promedioAnteriorRaw === undefined || promedioAnteriorRaw === ''
            ? null
            : Number(promedioAnteriorRaw);
        const mismoCiclo = !ciclo || !lectura || !lectura.cadena_ciclo_id || Number(ciclo.id) === Number(lectura.cadena_ciclo_id);

        if (!lectura || !Number.isFinite(promedioAnterior) || !mismoCiclo) {
            return {
                label: 'Comparativo:',
                texto: 'Primera revision del ciclo',
                className: 'font-medium text-slate-600',
            };
        }

        const delta = promedioActual - promedioAnterior;
        let className = 'font-medium text-slate-700';

        if (delta > 0) {
            className = 'font-medium text-red-600';
        } else if (delta < 0) {
            className = 'font-medium text-emerald-600';
        }

        return {
            label: 'Variacion vs ultima revision:',
            texto: formatearMmConSigno(delta),
            className,
        };
    }

    function actualizarComparativoRevision(lado, promedioActual) {
        const label = document.getElementById(`${lado}_variacion_revision_label`);
        const display = document.getElementById(`${lado}_variacion_revision_display`);
        if (!label || !display) return;

        const comparativo = obtenerVariacionVsRevision(lado, promedioActual);
        label.textContent = comparativo.label;
        display.textContent = comparativo.texto;
        display.className = comparativo.className;
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
        const bombasIncrementoBase = calcularIncrementoMm(bombasPromedio);
        const vaporIncrementoBase = calcularIncrementoMm(vaporPromedio);

        document.getElementById('bombas_promedio_display').textContent = bombasPromedio.toFixed(2);
        document.getElementById('vapor_promedio_display').textContent = vaporPromedio.toFixed(2);
        document.getElementById('bombas_porcentaje_display').textContent = `${bombasPorcentaje.toFixed(2)}%`;
        document.getElementById('vapor_porcentaje_display').textContent = `${vaporPorcentaje.toFixed(2)}%`;
        document.getElementById('bombas_incremento_base_display').textContent = formatearMm(bombasIncrementoBase);
        document.getElementById('vapor_incremento_base_display').textContent = formatearMm(vaporIncrementoBase);
        actualizarComparativoRevision('bombas', bombasPromedio);
        actualizarComparativoRevision('vapor', vaporPromedio);

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

    nuevaCadena.addEventListener('change', function () {
        actualizarPanelNuevaCadena();
        actualizarCalculos();
        renderCostPreview();
    });

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

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!nuevaCadena.checked) {
                return;
            }

            const newCycleConfirmationMessage = `Se registrara una nueva cadena para ${getLineaSeleccionada()}.\n\nDeseas continuar?`;

            if (!window.confirm(newCycleConfirmationMessage)) {
                event.preventDefault();
            }

            return;

            const preview = chainCostSummaries[getLineaSeleccionada()] || null;

            if (!preview || (Array.isArray(preview.errors) && preview.errors.length > 0)) {
                event.preventDefault();
                renderCostPreview();
                alert('No es posible registrar la nueva cadena porque faltan materiales o precios en el catalogo.');
                return;
            }

            const confirmationMessage = `Se registrara una nueva cadena para ${getLineaSeleccionada()}.\nTipo: ${preview.chain_type}\nCosto estimado: ${money(preview.total_cost || 0)}.\n\nDeseas continuar?`;

            if (!window.confirm(confirmationMessage)) {
                event.preventDefault();
            }
        });
    }

    actualizarLineaSeleccionada();
    actualizarCalculos();
    renderCostPreview();
    actualizarVistaPreviaHodometro(hodometro, 'hodometro_preview');
    actualizarVistaPreviaHodometro(hodometroInicial, 'hodometro_inicial_preview');
});
</script>
@endsection
