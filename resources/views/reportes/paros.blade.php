@extends('layouts.app')

@section('title', 'Reporte de Paros')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <a href="{{ route('reportes.index', ['tipo' => $tipoEquipo]) }}" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 mb-3">
                <i class="fas fa-arrow-left"></i>
                Volver a Reportes
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Reporte de Paros</h1>
            <p class="mt-1 text-gray-600">Consulta de paros por linea, periodo, tipo y planes de accion asociados.</p>
        </div>
        <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition">
            <i class="fas fa-print"></i>
            Imprimir
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="GET" action="{{ route('reportes.paros') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo equipo</label>
                <select name="tipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="lavadoras" {{ $tipoEquipo === 'lavadoras' ? 'selected' : '' }}>Lavadoras</option>
                    @if($canAccessPasteurizadora ?? false)
                        <option value="pasteurizadoras" {{ $tipoEquipo === 'pasteurizadoras' ? 'selected' : '' }}>Pasteurizadoras</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Linea</label>
                <select name="linea_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" {{ (string) $lineaId === (string) $linea->id ? 'selected' : '' }}>
                            {{ $linea->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo paro</label>
                <select name="tipo_paro" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="Programado" {{ request('tipo_paro') === 'Programado' ? 'selected' : '' }}>Programado</option>
                    <option value="Emergencia" {{ request('tipo_paro') === 'Emergencia' ? 'selected' : '' }}>Emergencia</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio', $fechaInicio->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin', $fechaFin->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>
                <a href="{{ route('reportes.paros') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-sm font-medium text-gray-500">Total paros</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $resumen['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-sm font-medium text-gray-500">Programados</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $resumen['programados'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-sm font-medium text-gray-500">Emergencia</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ $resumen['emergencia'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-sm font-medium text-gray-500">Horas totales</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ number_format($resumen['horas']) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Detalle de paros</h2>
            <span class="text-sm text-gray-500">{{ $paros->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Linea</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Inicio</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fin</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Horas</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Supervisor</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Planes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($paros as $paro)
                        @php
                            $horas = 0;
                            if ($paro->fecha_inicio && $paro->fecha_fin) {
                                $horas = ($paro->fecha_inicio->copy()->startOfDay()->diffInDays($paro->fecha_fin->copy()->startOfDay()) + 1) * 24;
                            }
                            $esEmergencia = str_contains(strtolower($paro->tipo), 'emerg');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $paro->linea?->nombre ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $paro->fecha_inicio?->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $paro->fecha_fin?->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $esEmergencia ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $paro->tipo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ number_format($horas) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $paro->supervisor?->name ?? 'Sin asignar' }}</td>
                            <td class="px-6 py-4 text-gray-700">
                                @if($paro->planesAccion->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($paro->planesAccion->take(2) as $plan)
                                            <div class="text-sm">
                                                <span class="font-medium">{{ $plan->estado ?? 'pendiente' }}:</span>
                                                {{ $plan->actividad ?? 'Sin actividad registrada' }}
                                            </div>
                                        @endforeach
                                        @if($paro->planesAccion->count() > 2)
                                            <div class="text-xs text-gray-500">+{{ $paro->planesAccion->count() - 2 }} planes mas</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">Sin planes asociados</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No se encontraron paros para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paros->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $paros->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
