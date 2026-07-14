@php
    $linea = $lineaTabla['linea'] ?? null;
    $lineaNombre = $lineaTabla['linea_nombre'] ?? ($linea->nombre ?? 'Etiquetadora');
    $componentes = collect($lineaTabla['componentes'] ?? []);
    $maquinasTabla = collect($lineaTabla['maquinas'] ?? []);
    $registrosTabla = $lineaTabla['registros'] ?? [];
    $conteosComponentes = $lineaTabla['conteos_componentes'] ?? [];
    $conteosMaquinas = $lineaTabla['conteos_maquinas'] ?? [];
    $resumenEstados = $lineaTabla['resumen_estados'] ?? [];
    $componenteBuscado = request('componente_id') ?: request('componente');
    $busquedaComponente = strtolower(trim((string) request('componente')));
@endphp

@if($componentes->isNotEmpty() && $maquinasTabla->isNotEmpty())
    <div class="lavadora-card {{ $componentes->contains(fn ($item) => $busquedaComponente !== '' && str_contains(strtolower($item['nombre'] ?? ''), $busquedaComponente)) ? 'search-target-line' : '' }}"
         data-linea-card
         data-linea-id="{{ $linea?->id }}"
         data-linea-nombre="{{ $lineaNombre }}">
        <div class="lavadora-card-header">
            <div class="flex items-center gap-3">
                <div class="flex min-h-12 min-w-16 items-center justify-center rounded-xl bg-white/10 p-2">
                    @if($linea)
                        @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'xs'])
                    @else
                        <i class="fas fa-tags text-2xl"></i>
                    @endif
                </div>
                <div>
                    <h3>{{ $lineaNombre }}</h3>
                    <div class="badge">{{ $lineaTabla['analisis_count'] ?? 0 }} analisis</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        onclick='showLineaPreview(@json($lineaNombre), @json($linea?->id), @json($resumenEstados), @json($lineaTabla["total_celdas"] ?? 0), @json($lineaTabla["celdas_con_datos"] ?? 0))'
                        class="create-action create-action--compact create-action--on-dark">
                    <i class="fas fa-chart-pie"></i>
                    Resumen
                </button>
                @if($linea)
                    <a href="{{ route('analisis-etiquetadora.create', ['linea' => $linea->id, 'maquina' => request('maquina') ?: 'A']) }}"
                       class="create-action create-action--compact create-action--on-dark">
                        <i class="fas fa-plus"></i>
                        Nuevo analisis
                    </a>
                @endif
            </div>
        </div>

        <div class="table-wrapper">
            <div class="scroll-indicator">
                <i class="fas fa-arrows-alt-h mr-1"></i> Desplazate para ver mas
            </div>

            <table class="w-full compact-table border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="sticky-top-left sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm table-corner">
                            <div class="reductor-header">
                                <div class="reductor-name">MAQUINA</div>
                                <div class="reductor-label">COMPONENTE</div>
                            </div>
                        </th>

                        @foreach($componentes as $componente)
                            @php
                                $conteoEstado = $conteosComponentes[$componente['key']] ?? [
                                    'ok' => 0,
                                    'review' => 0,
                                    'warning' => 0,
                                    'danger' => 0,
                                    'changed' => 0,
                                    'empty' => $maquinasTabla->count(),
                                ];

                                $idsPorMaquina = collect($componente['por_maquina'] ?? [])
                                    ->map(fn ($item) => $item?->id)
                                    ->filter()
                                    ->values();
                                $searchTarget = filled($componenteBuscado)
                                    && (
                                        ($busquedaComponente !== '' && str_contains(strtolower($componente['nombre'] ?? ''), $busquedaComponente))
                                        || $idsPorMaquina->contains((int) request('componente_id'))
                                    );
                            @endphp

                            <th class="sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm {{ $searchTarget ? 'search-target-component search-target-header' : '' }}"
                                data-component-code="{{ $componente['key'] }}"
                                data-search-target="{{ $searchTarget ? 'true' : 'false' }}">
                                <div class="component-header">
                                    <div class="component-name">{{ $componente['nombre'] }}</div>
                                    <div class="component-industrial-icon">
                                        <i class="fas fa-tags text-3xl"></i>
                                    </div>
                                    <div class="component-code mt-1">{{ $componente['grupo'] ?: 'Componente' }}</div>
                                    <div class="flex justify-center gap-1 mt-2">
                                        @if($conteoEstado['ok'] > 0)
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        @endif
                                        @if($conteoEstado['review'] > 0)
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                        @endif
                                        @if($conteoEstado['warning'] > 0)
                                            <span class="w-1.5 h-1.5 bg-orange-500 rounded-full"></span>
                                        @endif
                                        @if($conteoEstado['danger'] > 0)
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                        @endif
                                        @if($conteoEstado['changed'] > 0)
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                        @endif
                                    </div>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($maquinasTabla as $maquina)
                        @php
                            $conteoMaquina = $conteosMaquinas[$maquina] ?? [
                                'total' => 0,
                                'ok' => 0,
                                'review' => 0,
                                'warning' => 0,
                                'danger' => 0,
                                'changed' => 0,
                            ];
                        @endphp
                        <tr>
                            <th class="sticky-left cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm align-top">
                                <div class="reductor-header">
                                <div class="reductor-name">Maquina {{ $maquina }}</div>
                                <div class="reductor-label">Etiquetadora</div>
                                <div class="text-xs text-gray-500 mt-1">
                                        {{ $conteoMaquina['total'] }}/{{ $conteoMaquina['total_posibles'] ?? $componentes->count() }}
                                </div>
                            </div>
                        </th>

                            @foreach($componentes as $componente)
                                @php
                                    $componentForMachine = $componente['por_maquina'][$maquina] ?? null;
                                    $registrosCelda = $componentForMachine
                                        ? collect($registrosTabla[$maquina][$componentForMachine->id] ?? [])
                                        : collect();
                                    $registro = $registrosCelda->first();
                                    $hasData = filled($registro);
                                    $totalHistorial = $registrosCelda->count();
                                    $color = 'cell-empty';
                                    $statusClass = 'bg-gray-100 text-gray-700 border-gray-200';
                                    $icon = 'fa-clipboard';
                                    $imagenes = [];
                                    $isNew = false;

                                    if ($hasData) {
                                        $estadoActual = $registro->estado ?? \App\Models\AnalisisEtiquetadora::ESTADO_BUENO;

                                        if (\App\Models\AnalisisEtiquetadora::esEstadoCambiado($estadoActual)) {
                                            $color = 'cell-changed';
                                            $statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                            $icon = 'fa-exchange-alt';
                                        } elseif (\App\Models\AnalisisEtiquetadora::esEstadoDanado($estadoActual)) {
                                            $color = 'cell-danger';
                                            $statusClass = 'bg-red-100 text-red-800 border-red-200';
                                            $icon = 'fa-times-circle';
                                        } elseif (\App\Models\AnalisisEtiquetadora::esEstadoRequiereRevision($estadoActual)) {
                                            $color = 'cell-review';
                                            $statusClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                            $icon = 'fa-tools';
                                        } elseif (\App\Models\AnalisisEtiquetadora::esEstadoDesgaste($estadoActual)) {
                                            $color = 'cell-warning';
                                            $statusClass = 'bg-orange-100 text-orange-800 border-orange-200';
                                            $icon = 'fa-exclamation-triangle';
                                        } else {
                                            $color = 'cell-ok';
                                            $statusClass = 'bg-green-100 text-green-800 border-green-200';
                                            $icon = 'fa-check-circle';
                                        }

                                        $imagenes = $registro->evidencia_fotos ?? [];
                                        if (is_string($imagenes)) {
                                            $imagenes = json_decode($imagenes, true) ?? [];
                                        } elseif (!is_array($imagenes)) {
                                            $imagenes = [];
                                        }

                                        $isNew = $registro->created_at && $registro->created_at->gt(now()->subDays(3));
                                    }

                                    $createUrl = $linea && $componentForMachine
                                        ? route('analisis-etiquetadora.create', [
                                            'linea' => $linea->id,
                                            'maquina' => $maquina,
                                            'componente_id' => $componentForMachine->id,
                                        ])
                                        : null;

                                    $historialUrl = $hasData
                                        ? route('analisis-etiquetadora.historial', [
                                            'linea_id' => $registro->linea_id,
                                            'maquina' => $registro->maquina,
                                            'componente_id' => $registro->componente_id,
                                        ])
                                        : ($linea && $componentForMachine
                                            ? route('analisis-etiquetadora.historial', [
                                                'linea_id' => $linea->id,
                                                'maquina' => $maquina,
                                                'componente_id' => $componentForMachine->id,
                                            ])
                                            : route('analisis-etiquetadora.historial'));

                                    $searchTargetCell = filled($componenteBuscado)
                                        && (
                                            ($busquedaComponente !== '' && str_contains(strtolower($componente['nombre'] ?? ''), $busquedaComponente))
                                            || ($componentForMachine && (int) request('componente_id') === (int) $componentForMachine->id)
                                        );

                                    $detallePayload = null;
                                    if ($hasData) {
                                        $detallePayload = [
                                            'id' => $registro->id,
                                            'linea' => $registro->linea->nombre ?? $lineaNombre,
                                            'componente' => $registro->componente->nombre ?? $componente['nombre'],
                                            'componente_codigo' => $registro->componente->codigo ?? ($componentForMachine->codigo ?? ''),
                                            'reductor' => $registro->reductor ?: 'Maquina ' . $maquina,
                                            'maquina' => $registro->maquina ?: $maquina,
                                            'lado' => $registro->lado ?? null,
                                            'fecha_analisis' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : '',
                                            'numero_orden' => $registro->numero_orden,
                                            'estado' => $registro->estado ?? \App\Models\AnalisisEtiquetadora::ESTADO_BUENO,
                                            'usuario_nombre' => $registro->usuario?->name ?? 'Usuario no registrado',
                                            'actividad' => $registro->actividad,
                                            'imagenes' => $imagenes,
                                            'color' => $color,
                                            'created_at' => $registro->created_at ? $registro->created_at->format('d/m/Y H:i') : '',
                                            'updated_at' => $registro->updated_at ? $registro->updated_at->format('d/m/Y H:i') : '',
                                            'is_new' => $isNew,
                                            'total_historial' => $totalHistorial,
                                            'edit_url' => route('analisis-etiquetadora.edit', ['analisisetiquetadora' => $registro->id]),
                                            'delete_url' => $canDeleteAnalysis ? route('analisis-etiquetadora.destroy', ['analisisetiquetadora' => $registro->id]) : null,
                                            'historial_url' => $historialUrl,
                                        ];
                                    }
                                @endphp

                                <td class="border px-3 py-2 align-top {{ $color }} analysis-cell {{ $hasData ? '' : 'no-data' }} {{ $searchTargetCell ? 'search-target-component search-target-cell' : '' }}"
                                    data-component-code="{{ $componente['key'] }}"
                                    data-linea-id="{{ $registro->linea_id ?? ($linea?->id ?? '') }}"
                                    data-reductor="Maquina {{ $maquina }}"
                                    @if($hasData)
                                        onclick='openAnalysisDetail(@json($detallePayload))'
                                    @endif>
                                    @if(!$componentForMachine)
                                        <div class="empty-cell">
                                            <div class="empty-cell-icon">
                                                <i class="fas fa-ban"></i>
                                            </div>
                                            <p class="text-gray-500 text-xs mb-1">No aplica</p>
                                            <p class="text-[10px] text-gray-400">Esta combinacion no existe en el catalogo del Excel.</p>
                                        </div>
                                    @elseif($hasData)
                                        @if($isNew)
                                            <div class="badge-new">NUEVO</div>
                                        @endif

                                        <div class="space-y-2">
                                            <div class="inline-flex items-center gap-1 rounded bg-white px-2 py-1 text-[10px] font-bold text-slate-600 border border-slate-200">
                                                <i class="fas fa-layer-group text-blue-600"></i>
                                                {{ $componentForMachine->cantidad_original ?: ($componentForMachine->cantidad_total . ' por maquina') }}
                                            </div>

                                            <div class="bg-blue-50 p-2 rounded mb-2">
                                                <div class="flex items-center gap-1">
                                                    <i class="fas fa-calendar-alt text-blue-600"></i>
                                                    <span class="text-xs font-bold text-blue-800">Fecha:</span>
                                                    <span class="text-xs font-semibold bg-white px-2 py-0.5 rounded">
                                                        {{ $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : 'NO ESPECIFICADA' }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                                    <span class="text-xs font-bold text-gray-800">Orden #{{ $registro->numero_orden }}</span>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                                    <i class="fas {{ $icon }} mr-1"></i>
                                                    {{ Str::limit($registro->estado ?? \App\Models\AnalisisEtiquetadora::ESTADO_BUENO, 22) }}
                                                </span>
                                                <span class="mt-1 inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    <i class="fas fa-user-check mr-1"></i>
                                                    Realizado por: {{ $registro->usuario?->name ?? 'Usuario no registrado' }}
                                                </span>
                                            </div>

                                            <p class="text-gray-700 text-xs">{{ Str::limit($registro->actividad, 80) }}</p>

                                            <div class="flex flex-col gap-1 mt-3">
                                                @if(!empty($imagenes))
                                                    <button onclick="event.stopPropagation(); openAllImages(@json($imagenes), @json($registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : ''), @json($registro->numero_orden), @json($registro->estado ?? 'Buen estado'))"
                                                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-xs font-medium">
                                                        <i class="fas fa-images mr-1"></i>
                                                        {{ count($imagenes) }} img
                                                    </button>
                                                @endif

                                                <a href="{{ $createUrl }}"
                                                   class="create-action create-action--compact create-action--success"
                                                   onclick="event.stopPropagation();">
                                                    <i class="fas fa-plus"></i>
                                                    Nuevo Registro
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="empty-cell">
                                            <div class="empty-cell-icon">
                                                <i class="fas fa-clipboard"></i>
                                            </div>
                                            <p class="text-gray-500 text-xs mb-3">Sin analisis</p>
                                            <p class="mb-3 rounded bg-white px-2 py-1 text-[10px] font-bold text-slate-600 border border-slate-200">
                                                {{ $componentForMachine->cantidad_original ?: ($componentForMachine->cantidad_total . ' por maquina') }}
                                            </p>
                                            @if($createUrl)
                                                <a href="{{ $createUrl }}"
                                                   class="create-action create-action--compact"
                                                   onclick="event.stopPropagation();">
                                                    <i class="fas fa-plus"></i>
                                                    Nuevo
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
