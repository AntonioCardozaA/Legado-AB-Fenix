@extends('layouts.app')

@section('title', 'Historial de Elongaciones')

@section('content')
<style>
    .elongacion-date-alert {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.7rem;
        border-radius: 0.85rem;
        font-weight: 700;
        line-height: 1.1;
        cursor: help;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .elongacion-date-alert:hover {
        transform: translateY(-1px);
    }

    .elongacion-date-alert--upcoming,
    .elongacion-date-alert--due-today {
        color: #c2410c;
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.28), 0 10px 18px rgba(249, 115, 22, 0.12);
    }

    .elongacion-date-alert--overdue {
        color: #b91c1c;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.32), 0 12px 22px rgba(239, 68, 68, 0.16);
    }

    .elongacion-date-alert--pulse-soft {
        animation: elongacionPulseSoft 1.8s ease-in-out infinite;
    }

    .elongacion-date-alert--pulse-strong {
        animation: elongacionPulseStrong 1.05s ease-in-out infinite;
    }

    .elongacion-date-alert[data-tooltip]::before,
    .elongacion-date-alert[data-tooltip]::after {
        position: absolute;
        left: 50%;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.18s ease, transform 0.18s ease;
        z-index: 20;
    }

    .elongacion-date-alert[data-tooltip]::before {
        content: "";
        bottom: calc(100% + 4px);
        transform: translateX(-50%) translateY(4px);
        border-width: 6px;
        border-style: solid;
        border-color: #0f172a transparent transparent transparent;
    }

    .elongacion-date-alert[data-tooltip]::after {
        content: attr(data-tooltip);
        bottom: calc(100% + 14px);
        transform: translateX(-50%) translateY(4px);
        min-width: 12rem;
        max-width: 16rem;
        padding: 0.55rem 0.7rem;
        border-radius: 0.75rem;
        background: #0f172a;
        color: #f8fafc;
        font-size: 0.72rem;
        font-weight: 600;
        text-align: center;
        white-space: normal;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22);
    }

    .elongacion-date-alert[data-tooltip]:hover::before,
    .elongacion-date-alert[data-tooltip]:hover::after,
    .elongacion-date-alert[data-tooltip]:focus-visible::before,
    .elongacion-date-alert[data-tooltip]:focus-visible::after {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .elongacion-date-note {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    @keyframes elongacionPulseSoft {
        0%, 100% {
            transform: scale(1);
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.28), 0 10px 18px rgba(249, 115, 22, 0.12);
        }

        50% {
            transform: scale(1.025);
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.4), 0 14px 24px rgba(249, 115, 22, 0.2);
        }
    }

    @keyframes elongacionPulseStrong {
        0%, 100% {
            transform: scale(1);
            box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.32), 0 12px 22px rgba(239, 68, 68, 0.16);
        }

        50% {
            transform: scale(1.045);
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.46), 0 16px 28px rgba(220, 38, 38, 0.26);
        }
    }
</style>
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="mb-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-6">
            <a href="{{ route('lavadora.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 group w-fit">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-history text-blue-600"></i>
                    Historial de elongaciones
                </h1>
            </div>      
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('elongaciones.ciclos.comparacion', ['linea' => request('linea')]) }}"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition shadow-md">
                    <i class="fas fa-code-compare"></i>
                    Comparar ciclos
                </a>
                <a href="{{ route('elongaciones.create') }}"
                   class="create-action">
                    <i class="fas fa-plus-circle"></i>
                    Nuevo registro
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" action="{{ route('elongaciones.index') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div>
                    <label for="linea" class="block text-sm font-medium text-gray-700 mb-1">Línea</label>
                    <select name="linea" id="linea" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todas las líneas</option>
                        @foreach($lineas as $linea)
                            <option value="{{ $linea }}" {{ request('linea') === $linea ? 'selected' : '' }}>{{ $linea }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="estado" id="estado" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        <option value="normal" {{ request('estado') === 'normal' ? 'selected' : '' }}>Normal (&lt;1.3%)</option>
                        <option value="comprar" {{ request('estado') === 'comprar' ? 'selected' : '' }}>Compra (1.3% - 1.46%)</option>
                        <option value="cambio" {{ request('estado') === 'cambio' ? 'selected' : '' }}>Cambio (≥1.46%)</option>
                    </select>
                </div>

                <div>
                    <label for="cadena_ciclo_id" class="block text-sm font-medium text-gray-700 mb-1">Ciclo</label>
                    <select name="cadena_ciclo_id" id="cadena_ciclo_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($ciclos as $ciclo)
                            <option value="{{ $ciclo->id }}" {{ (string) request('cadena_ciclo_id') === (string) $ciclo->id ? 'selected' : '' }}>
                                {{ $ciclo->codigo }} | {{ $ciclo->proveedor }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="proveedor" class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                    <input type="text" id="proveedor" name="proveedor" value="{{ request('proveedor') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Buscar proveedor">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    @if(request()->query())
                        <a href="{{ route('elongaciones.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-600">Registros visibles</p>
                <p class="text-2xl font-bold text-gray-800">{{ $elongaciones->total() }}</p>
                @if(!request()->query())
                @endif
            </div>

            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-600">En compra</p>
                <p class="text-2xl font-bold text-yellow-600">
                    {{ $elongaciones->getCollection()->filter(fn($e) => max($e->bombas_porcentaje, $e->vapor_porcentaje) >= 1.3 && max($e->bombas_porcentaje, $e->vapor_porcentaje) < 1.46)->count() }}
                </p>
                <p class="text-xs text-gray-500 mt-2">1.3% - 1.46%</p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-600">Cambio requerido</p>
                <p class="text-2xl font-bold text-red-600">
                    {{ $elongaciones->getCollection()->filter(fn($e) => $e->bombas_porcentaje >= 1.46 || $e->vapor_porcentaje >= 1.46)->count() }}
                </p>
                <p class="text-xs text-gray-500 mt-2">≥ 1.46%</p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-600">Ciclos filtrados</p>
                <p class="text-2xl font-bold text-slate-900">{{ $ciclos->count() ?: '—' }}</p>
                <p class="text-xs text-gray-500 mt-2">Disponible cuando eliges una línea</p>
            </div>
        </div>
    </div>

    @if($elongaciones->count() > 0)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Línea</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ciclo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hodómetro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horas ciclo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lado Bombas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lado Vapor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($elongaciones as $registro)
                            @php
                                $limiteCompra = 1.3;
                                $limiteCambio = 1.46;
                                $bombasCambio = $registro->bombas_porcentaje >= $limiteCambio;
                                $vaporCambio = $registro->vapor_porcentaje >= $limiteCambio;
                                $bombasCompra = $registro->bombas_porcentaje >= $limiteCompra && $registro->bombas_porcentaje < $limiteCambio;
                                $vaporCompra = $registro->vapor_porcentaje >= $limiteCompra && $registro->vapor_porcentaje < $limiteCambio;
                                $bombasBarraWidth = $bombasCambio ? 100 : min(($registro->bombas_porcentaje / $limiteCambio) * 100, 100);
                                $vaporBarraWidth = $vaporCambio ? 100 : min(($registro->vapor_porcentaje / $limiteCambio) * 100, 100);
                                $estadoTexto = $bombasCambio || $vaporCambio ? 'CAMBIO' : (($bombasCompra || $vaporCompra) ? 'COMPRA' : 'NORMAL');
                                $estadoColor = $bombasCambio || $vaporCambio ? 'red' : (($bombasCompra || $vaporCompra) ? 'yellow' : 'green');
                                $bombasVariacion = $registro->bombas_variacion_revision_mm;
                                $vaporVariacion = $registro->vapor_variacion_revision_mm;
                                $bombasVariacionTexto = $bombasVariacion === null ? '1ra revisión' : (($bombasVariacion > 0 ? '+' : '') . number_format($bombasVariacion, 2) . ' mm');
                                $vaporVariacionTexto = $vaporVariacion === null ? '1ra revisión' : (($vaporVariacion > 0 ? '+' : '') . number_format($vaporVariacion, 2) . ' mm');
                                $bombasVariacionColor = $bombasVariacion === null ? 'text-gray-400' : ($bombasVariacion > 0 ? 'text-red-500' : ($bombasVariacion < 0 ? 'text-emerald-600' : 'text-slate-500'));
                                $vaporVariacionColor = $vaporVariacion === null ? 'text-gray-400' : ($vaporVariacion > 0 ? 'text-red-500' : ($vaporVariacion < 0 ? 'text-emerald-600' : 'text-slate-500'));
                                $isLatestAlertableRecord = in_array($registro->id, $latestAlertableRecordIds ?? [], true);
                                $fechaAlertStatus = $isLatestAlertableRecord ? $registro->revision_status : 'normal';
                                $hasFechaAlert = $isLatestAlertableRecord && $registro->revision_needs_alert;
                                $fechaAlertClasses = match ($fechaAlertStatus) {
                                    'upcoming' => 'elongacion-date-alert elongacion-date-alert--upcoming elongacion-date-alert--pulse-soft',
                                    'due_today' => 'elongacion-date-alert elongacion-date-alert--due-today elongacion-date-alert--pulse-soft',
                                    'overdue' => 'elongacion-date-alert elongacion-date-alert--overdue elongacion-date-alert--pulse-strong',
                                    default => 'text-sm text-gray-900',
                                };
                                $fechaNoteColor = match ($fechaAlertStatus) {
                                    'upcoming', 'due_today' => 'text-orange-600',
                                    'overdue' => 'text-red-600',
                                    default => 'text-transparent',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex flex-col items-start gap-1">
                                        <span
                                            @if($hasFechaAlert)
                                                data-elongacion-alert="{{ $fechaAlertStatus }}"
                                                data-tooltip="Se necesita nuevo registro de elongaci&oacute;n"
                                                title="Se necesita nuevo registro de elongaci&oacute;n"
                                                tabindex="0"
                                            @else
                                                data-elongacion-alert="none"
                                            @endif
                                            class="{{ $fechaAlertClasses }}"
                                        >
                                            @if($hasFechaAlert)
                                                <i class="fas fa-triangle-exclamation text-xs" aria-hidden="true"></i>
                                            @endif
                                            <span>{{ $registro->created_at->format('d/m/Y H:i') }}</span>
                                        </span>
                                        @if($hasFechaAlert && $registro->revision_status_label)
                                            <span class="elongacion-date-note {{ $fechaNoteColor }}">{{ $registro->revision_status_label }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">{{ $registro->linea }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    @if($registro->cadenaCiclo)
                                        <a href="{{ route('elongaciones.ciclos.show', $registro->cadenaCiclo) }}" class="font-semibold text-slate-900 hover:text-blue-700">
                                            {{ $registro->cadenaCiclo->codigo }}
                                        </a>
                                        <div class="text-xs text-gray-500">#{{ $registro->cadenaCiclo->numero_ciclo }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $registro->proveedor_actual ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $registro->hodometro_formateado ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $registro->hodometro_ciclo_formateado ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1 min-w-[110px]">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-{{ $bombasCambio ? 'red' : ($bombasCompra ? 'yellow' : 'green') }}-600">{{ number_format($registro->bombas_porcentaje, 2) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $bombasCambio ? 'bg-red-500' : ($bombasCompra ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $bombasBarraWidth }}%"></div>
                                        </div>
                                        <div class="text-[11px] text-gray-500 leading-tight">Base: +{{ number_format($registro->bombas_incremento_base_mm, 2) }} mm</div>
                                        <div class="text-[11px] leading-tight {{ $bombasVariacionColor }}">Rev.: {{ $bombasVariacionTexto }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1 min-w-[110px]">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-{{ $vaporCambio ? 'red' : ($vaporCompra ? 'yellow' : 'green') }}-600">{{ number_format($registro->vapor_porcentaje, 2) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $vaporCambio ? 'bg-red-500' : ($vaporCompra ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $vaporBarraWidth }}%"></div>
                                        </div>
                                        <div class="text-[11px] text-gray-500 leading-tight">Base: +{{ number_format($registro->vapor_incremento_base_mm, 2) }} mm</div>
                                        <div class="text-[11px] leading-tight {{ $vaporVariacionColor }}">Rev.: {{ $vaporVariacionTexto }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($estadoColor === 'red')
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">{{ $estadoTexto }}</span>
                                    @elseif($estadoColor === 'yellow')
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">{{ $estadoTexto }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">{{ $estadoTexto }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('elongaciones.show', $registro) }}" class="text-blue-600 hover:text-blue-900" title="Ver detalle"><i class="fas fa-eye"></i></a>
                                        @if($registro->cadenaCiclo)
                                            <a href="{{ route('elongaciones.ciclos.show', $registro->cadenaCiclo) }}" class="text-slate-700 hover:text-slate-900" title="Ver historial del ciclo"><i class="fas fa-link"></i></a>
                                        @endif
                                        <form action="{{ route('elongaciones.destroy', $registro) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este registro permanentemente?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">{{ $elongaciones->links() }}</div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i class="fas fa-chart-line text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay registros</h3>
            <p class="text-gray-500 mb-6">Comienza registrando una nueva medición o iniciando un nuevo ciclo de cadena.</p>
            <a href="{{ route('elongaciones.create') }}" class="create-action"><i class="fas fa-plus-circle"></i> Nuevo registro</a>
        </div>
    @endif
</div>
@endsection
