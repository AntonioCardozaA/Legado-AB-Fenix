@php
    $resumen = $reporte['resumen'] ?? [];
    $componentes = collect($reporte['componentes'] ?? []);
    $maquinas = collect($reporte['maquinas'] ?? $reporte['reductores'] ?? []);
    $analisis = collect($reporte['analisis'] ?? []);
    $paros = collect($reporte['paros'] ?? []);
    $linea = $reporte['linea'] ?? null;

    $estadoBadge = function ($estado) {
        $estado = (string) $estado;

        if (str_contains($estado, 'Requiere cambio')) {
            return 'bg-red-100 text-red-700 border-red-200';
        }

        if (str_contains($estado, 'Desgaste')) {
            return 'bg-orange-100 text-orange-700 border-orange-200';
        }

        if (str_contains($estado, 'Requiere')) {
            return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        }

        if (str_contains($estado, 'Cambiado')) {
            return 'bg-blue-100 text-blue-700 border-blue-200';
        }

        return 'bg-green-100 text-green-700 border-green-200';
    };
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Analisis</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $resumen['total_analisis'] ?? 0 }}</div>
            <div class="mt-1 text-sm text-gray-500">
                Revisados: {{ $resumen['componentes_revisados'] ?? 0 }}/{{ $resumen['total_componentes'] ?? 0 }}
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Maquinas</div>
            <div class="mt-2 text-3xl font-bold text-emerald-700">{{ $resumen['maquinas_count'] ?? 0 }}</div>
            <div class="mt-1 text-sm text-gray-500">A, B y C por linea</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Unidades</div>
            <div class="mt-2 text-3xl font-bold text-indigo-700">{{ $resumen['total_unidades'] ?? 0 }}</div>
            <div class="mt-1 text-sm text-gray-500">Cantidad segun catalogo</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hallazgos</div>
            <div class="mt-2 text-3xl font-bold text-red-700">{{ $resumen['componentes_criticos'] ?? 0 }}</div>
            <div class="mt-1 text-sm text-gray-500">
                Revision: {{ $resumen['componentes_revision'] ?? 0 }} | Desgaste: {{ $resumen['componentes_severos_moderados'] ?? 0 }}
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Componentes de {{ $linea?->nombre ?? 'linea' }}</h3>
                <p class="text-sm text-gray-500">Organizacion por maquina, grupo y mecanismo</p>
            </div>
            <span class="text-sm font-semibold text-gray-600">{{ $componentes->count() }} componentes</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Maquina</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Grupo</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Mecanismo</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Componente</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 uppercase text-xs">Cantidad</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Ultimo estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($componentes as $componente)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $componente['maquina'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $componente['grupo'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $componente['mecanismo'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $componente['nombre'] ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $componente['codigo'] ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-900">
                                {{ $componente['cantidad_total'] ?? 0 }}
                            </td>
                            <td class="px-4 py-3">
                                @if(!empty($componente['ultimo_estado']))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $estadoBadge($componente['ultimo_estado']) }}">
                                        {{ $componente['ultimo_estado'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">Sin analisis</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay componentes configurados para esta linea.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @foreach($maquinas as $maquina)
            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="font-semibold text-gray-900">{{ $maquina['nombre'] ?? 'Maquina' }}</div>
                    <i class="fas fa-tags text-emerald-600"></i>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs uppercase font-semibold text-gray-500">Componentes</div>
                        <div class="text-lg font-bold text-gray-900">{{ $maquina['total_componentes'] ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase font-semibold text-gray-500">Unidades</div>
                        <div class="text-lg font-bold text-gray-900">{{ $maquina['total_unidades'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    Ultima revision: {{ !empty($maquina['ultima_fecha']) ? \Carbon\Carbon::parse($maquina['ultima_fecha'])->format('d/m/Y') : 'Sin datos' }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Analisis recientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Fecha</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Maquina</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Componente</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Estado</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Orden</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($analisis->take(20) as $item)
                        <tr>
                            <td class="px-4 py-3 text-gray-700">{{ optional($item->fecha_analisis)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $item->reductor }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('analisis-etiquetadora.show', ['analisisetiquetadora' => $item->id]) }}" class="text-blue-700 hover:text-blue-900 font-medium">
                                    {{ $item->componente?->nombre ?? 'Sin componente' }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $item->componente?->grupo }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $estadoBadge($item->estado) }}">
                                    {{ $item->estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $item->numero_orden }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay analisis en el periodo seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($paros->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Paros del periodo</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Inicio</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Fin</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Tipo</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Supervisor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($paros as $paro)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ optional($paro->fecha_inicio)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ optional($paro->fecha_fin)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $paro->tipo }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $paro->supervisor?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
