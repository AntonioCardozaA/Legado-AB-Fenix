@extends('layouts.app')

@section('title', 'Historico de Revisados - Etiquetadora')

@section('content')
@php
    $registros = method_exists($analisis, 'getCollection') ? $analisis->getCollection() : collect($analisis);
    $lineasEtiquetadora = collect($lineasEtiquetadora ?? []);
    $maquinasEtiquetadora = collect($maquinasEtiquetadora ?? \App\Support\EtiquetadoraCatalog::maquinas());
    $estadisticasHistorico = collect($estadisticasHistorico ?? []);
    $resumenHistorico = $resumenHistorico ?? [
        'total_general' => 0,
        'revisado_general' => 0,
        'pendiente_general' => 0,
        'porcentaje_general' => 0,
        'componentes_total' => 0,
        'componentes_revisados' => 0,
        'componentes_completos' => 0,
        'componentes_pendientes' => 0,
        'ultima_revision' => null,
    ];

    $lineaSeleccionada = $lineaSeleccionada ?? null;
    $lineaActual = $lineaSeleccionada?->id ?? request('linea_id');
    $maquinaActual = filled(request('maquina')) ? strtoupper((string) request('maquina')) : '';
    $lineaSeleccionada = $lineaSeleccionada ?: (filled($lineaActual) ? $lineasEtiquetadora->firstWhere('id', (int) $lineaActual) : $lineasEtiquetadora->first());
    $lineaActual = $lineaSeleccionada?->id ?? $lineaActual;
    $totalRegistrosPagina = $registros->count();
    $totalPaginado = method_exists($analisis, 'total') ? $analisis->total() : $totalRegistrosPagina;

    $estadoColor = function (?string $estado): string {
        return match (true) {
            \App\Models\AnalisisEtiquetadora::esEstadoDanado($estado) => 'danger',
            \App\Models\AnalisisEtiquetadora::esEstadoDesgaste($estado) => 'warning',
            \App\Models\AnalisisEtiquetadora::esEstadoRequiereRevision($estado) => 'review',
            \App\Models\AnalisisEtiquetadora::esEstadoCambiado($estado) => 'changed',
            \App\Models\AnalisisEtiquetadora::esEstadoBueno($estado) => 'success',
            default => 'neutral',
        };
    };

    $porcentajeColorHistorico = function (float|int $porcentaje): string {
        return match (true) {
            $porcentaje >= 80 => 'success',
            $porcentaje >= 50 => 'info',
            $porcentaje >= 20 => 'warning',
            default => 'danger',
        };
    };

    $limpiarModuloHistorico = function (?string $texto, ?string $lineaNombre): string {
        $valor = trim((string) $texto);

        if ($valor !== '' && preg_match('/^\s*(?:LINEA|L)\s*-?\s*0?([0-9]{1,2})\s*[,:\-]?\s*/i', $valor, $matches) === 1) {
            $lineaTexto = 'L-' . str_pad((string) ((int) $matches[1]), 2, '0', STR_PAD_LEFT);

            if (trim((string) $lineaNombre) === $lineaTexto) {
                $valor = preg_replace('/^\s*(?:LINEA|L)\s*-?\s*0?[0-9]{1,2}\s*[,:\-]?\s*/i', '', $valor) ?? $valor;
            }
        }

        $valor = trim($valor, " \t\n\r\0\x0B,::-");

        return $valor !== '' ? $valor : 'Componentes de etiquetadora';
    };

    $maquinasDisponibles = $maquinasEtiquetadora
        ->map(fn ($maquina) => strtoupper((string) $maquina))
        ->filter()
        ->unique()
        ->values();

    if ($maquinasDisponibles->isEmpty() && filled($maquinaActual)) {
        $maquinasDisponibles = collect([$maquinaActual]);
    }

    $filasHistorico = $estadisticasHistorico
        ->flatMap(function (array $item) use ($lineaSeleccionada, $lineaActual, $maquinaActual, $porcentajeColorHistorico, $estadoColor, $limpiarModuloHistorico) {
            return collect($item['detalle_componentes'] ?? [])
                ->filter(function (array $detalle) use ($lineaSeleccionada, $maquinaActual): bool {
                    $detalleLinea = trim((string) ($detalle['linea'] ?? ''));
                    $detalleMaquina = strtoupper((string) ($detalle['maquina'] ?? ''));

                    return (!$lineaSeleccionada || $detalleLinea === trim((string) $lineaSeleccionada->nombre))
                        && (blank($maquinaActual) || $detalleMaquina === $maquinaActual);
                })
                ->map(function (array $detalle) use ($item, $lineaSeleccionada, $lineaActual, $porcentajeColorHistorico, $estadoColor, $limpiarModuloHistorico): array {
                    $maquina = strtoupper((string) ($detalle['maquina'] ?? ''));
                    $cantidadTotal = max(0, (int) ($detalle['cantidad_total'] ?? 0));
                    $cantidadRevisada = max(0, (int) ($detalle['cantidad_revisada'] ?? 0));
                    $cantidadPendiente = max($cantidadTotal - $cantidadRevisada, 0);
                    $porcentaje = $cantidadTotal > 0 ? round(($cantidadRevisada / $cantidadTotal) * 100, 1) : 0;
                    $estado = $detalle['estado_actual'] ?? null;
                    $descriptor = collect([
                        trim((string) ($item['grupo'] ?? '')),
                        trim((string) ($item['mecanismo'] ?? '')),
                    ])
                        ->filter(fn ($value) => filled($value))
                        ->unique(fn ($value) => strtolower(trim($value, " \t\n\r\0\x0B:-")))
                        ->values()
                        ->implode(' - ');
                    $lineaNombre = $detalle['linea'] ?: ($lineaSeleccionada?->nombre ?? '-');
                    $moduloVisual = $limpiarModuloHistorico($item['grupo'] ?? null, $lineaNombre);
                    $submoduloVisual = $limpiarModuloHistorico($item['mecanismo'] ?? null, $lineaNombre);
                    $descriptorVisual = collect([$moduloVisual, $submoduloVisual])
                        ->filter(fn ($value) => filled($value))
                        ->unique(fn ($value) => strtolower(trim((string) $value)))
                        ->implode(' - ');
                    $analysisParams = array_filter([
                        'linea_id' => $lineaActual,
                        'maquina' => $maquina,
                        'componente_id' => $detalle['componente_id'] ?? null,
                    ], fn ($value) => filled($value));

                    return [
                        'linea' => $lineaNombre,
                        'maquina' => $maquina,
                        'titulo' => filled($maquina) ? 'Etiquetadora ' . $maquina : 'Etiquetadora',
                        'componente' => $item['nombre'] ?? 'Componente',
                        'descriptor' => $descriptorVisual ?: $descriptor,
                        'grupo' => $item['grupo'] ?? null,
                        'mecanismo' => $item['mecanismo'] ?? null,
                        'modulo_visual' => $moduloVisual,
                        'submodulo_visual' => $submoduloVisual,
                        'cantidad_total' => $cantidadTotal,
                        'cantidad_revisada' => $cantidadRevisada,
                        'cantidad_pendiente' => $cantidadPendiente,
                        'porcentaje' => $porcentaje,
                        'color' => $porcentajeColorHistorico($porcentaje),
                        'estado' => $estado ?: 'Pendiente',
                        'estado_clase' => $estadoColor($estado),
                        'ultima_revision' => $detalle['ultima_revision'] ?? null,
                        'usuario_ultima_revision' => $detalle['usuario_ultima_revision'] ?? null,
                        'numero_orden_ultima_revision' => $detalle['numero_orden_ultima_revision'] ?? null,
                        'actividad_ultima_revision' => $detalle['actividad_ultima_revision'] ?? null,
                        'piezas_revisadas' => collect($detalle['piezas_revisadas'] ?? [])->values()->all(),
                        'piezas_pendientes' => collect($detalle['piezas_pendientes'] ?? [])->values()->all(),
                        'analysis_url' => route('analisis-etiquetadora.index', $analysisParams),
                        'modal_payload' => [
                            'linea' => $lineaNombre,
                            'maquina' => $maquina,
                            'titulo' => filled($maquina) ? 'Etiquetadora ' . $maquina : 'Etiquetadora',
                            'componente' => $item['nombre'] ?? 'Componente',
                            'descriptor' => $descriptorVisual ?: $descriptor,
                            'estado' => $estado ?: 'Pendiente',
                            'estado_clase' => $estadoColor($estado),
                            'cantidad_total' => $cantidadTotal,
                            'cantidad_revisada' => $cantidadRevisada,
                            'cantidad_pendiente' => $cantidadPendiente,
                            'porcentaje' => $porcentaje,
                            'ultima_revision' => $detalle['ultima_revision'] ?? null,
                            'usuario_ultima_revision' => $detalle['usuario_ultima_revision'] ?? null,
                            'numero_orden_ultima_revision' => $detalle['numero_orden_ultima_revision'] ?? null,
                            'actividad_ultima_revision' => $detalle['actividad_ultima_revision'] ?? null,
                            'analysis_url' => route('analisis-etiquetadora.index', $analysisParams),
                            'detalles' => [[
                                'linea' => $lineaNombre,
                                'maquina' => $maquina,
                                'cantidad_total' => $cantidadTotal,
                                'cantidad_revisada' => $cantidadRevisada,
                                'cantidad_pendiente' => $cantidadPendiente,
                                'piezas_revisadas' => collect($detalle['piezas_revisadas'] ?? [])->values()->all(),
                                'piezas_pendientes' => collect($detalle['piezas_pendientes'] ?? [])->values()->all(),
                                'ultima_revision' => $detalle['ultima_revision'] ?? null,
                                'usuario_ultima_revision' => $detalle['usuario_ultima_revision'] ?? null,
                                'estado_actual' => $estado,
                                'numero_orden_ultima_revision' => $detalle['numero_orden_ultima_revision'] ?? null,
                                'actividad_ultima_revision' => $detalle['actividad_ultima_revision'] ?? null,
                            ]],
                        ],
                    ];
                });
        })
        ->sortBy(fn (array $row): string => implode('|', [
            $row['maquina'] ?? '',
            $row['grupo'] ?? '',
            $row['mecanismo'] ?? '',
            $row['componente'] ?? '',
        ]))
        ->values();

    $filasPorMaquina = $filasHistorico
        ->groupBy(fn (array $fila): string => $fila['maquina'] ?: 'SIN_ASIGNAR')
        ->map(function ($filasMaquina) use ($porcentajeColorHistorico) {
            $filasMaquina = collect($filasMaquina);
            $totalMaquina = (int) $filasMaquina->sum('cantidad_total');
            $revisadoMaquina = (int) $filasMaquina->sum('cantidad_revisada');
            $porcentajeMaquina = $totalMaquina > 0 ? round(($revisadoMaquina / $totalMaquina) * 100, 1) : 0;

            return [
                'filas' => $filasMaquina,
                'modulos' => $filasMaquina->groupBy(function (array $fila): string {
                    return collect([$fila['modulo_visual'] ?? null, $fila['submodulo_visual'] ?? null])
                        ->filter(fn ($value) => filled($value))
                        ->map(fn ($value) => trim((string) $value))
                        ->unique(fn ($value) => strtolower($value))
                        ->implode(' - ') ?: 'Componentes de etiquetadora';
                }),
                'total' => $totalMaquina,
                'revisado' => $revisadoMaquina,
                'pendiente' => max($totalMaquina - $revisadoMaquina, 0),
                'porcentaje' => $porcentajeMaquina,
                'color' => $porcentajeColorHistorico($porcentajeMaquina),
            ];
        });

    $modulosHistorico = collect();
    if ($lineaSeleccionada) {
        $modulosHistorico = collect([[
            'linea_id' => $lineaSeleccionada->id,
            'linea_nombre' => $lineaSeleccionada->nombre,
            'totales' => [
                'total' => (int) $filasHistorico->sum('cantidad_total'),
                'revisado' => (int) $filasHistorico->sum('cantidad_revisada'),
                'pendiente' => max((int) $filasHistorico->sum('cantidad_total') - (int) $filasHistorico->sum('cantidad_revisada'), 0),
            ],
            'modulos' => $filasPorMaquina
                ->map(function (array $datosMaquina, string $maquina) use ($porcentajeColorHistorico): array {
                    $grupos = collect($datosMaquina['modulos'])
                        ->map(function ($componentesGrupo, string $nombreGrupo) use ($porcentajeColorHistorico): array {
                            $componentesGrupo = collect($componentesGrupo);
                            $totalGrupo = (int) $componentesGrupo->sum('cantidad_total');
                            $revisadoGrupo = (int) $componentesGrupo->sum('cantidad_revisada');
                            $porcentajeGrupo = $totalGrupo > 0 ? round(($revisadoGrupo / $totalGrupo) * 100, 1) : 0;

                            return [
                                'label' => $nombreGrupo,
                                'total' => $totalGrupo,
                                'revisado' => $revisadoGrupo,
                                'pendiente' => max($totalGrupo - $revisadoGrupo, 0),
                                'porcentaje' => $porcentajeGrupo,
                                'color' => $porcentajeColorHistorico($porcentajeGrupo),
                                'componentes' => $componentesGrupo->values(),
                            ];
                        })
                        ->values();

                    return [
                        'numero' => $maquina,
                        'label' => $maquina === 'SIN_ASIGNAR' ? 'Etiquetadora sin asignar' : 'Etiquetadora ' . $maquina,
                        'total' => $datosMaquina['total'],
                        'revisado' => $datosMaquina['revisado'],
                        'pendiente' => $datosMaquina['pendiente'],
                        'porcentaje' => $datosMaquina['porcentaje'],
                        'color' => $datosMaquina['color'],
                        'componentes_count' => $datosMaquina['filas']->count(),
                        'grupos' => $grupos,
                    ];
                })
                ->values(),
        ]])->filter(fn (array $lineaHistorico): bool => collect($lineaHistorico['modulos'])->isNotEmpty())->values();
    }

    $paletaEtiquetadorasHistorico = [
        '#e11d48', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0891b2',
        '#ea580c', '#4f46e5', '#0f766e', '#be123c', '#65a30d', '#9333ea',
        '#0369a1', '#ca8a04', '#db2777', '#15803d', '#c2410c', '#4338ca',
        '#0d9488', '#a16207', '#be185d', '#1d4ed8', '#047857', '#b45309',
    ];

    $colorEtiquetadoraHistorico = function (string $label, int $index, array &$usados) use ($paletaEtiquetadorasHistorico): string {
        if (count($usados) >= count($paletaEtiquetadorasHistorico)) {
            $hue = fmod($index * 137.508, 360);

            return "hsl({$hue}, 74%, 45%)";
        }

        $hash = abs((int) crc32($label ?: 'Etiquetadora'));
        $colorIndex = $hash % count($paletaEtiquetadorasHistorico);

        while (in_array($colorIndex, $usados, true) && count($usados) < count($paletaEtiquetadorasHistorico)) {
            $colorIndex = ($colorIndex + 1) % count($paletaEtiquetadorasHistorico);
        }

        $usados[] = $colorIndex;

        return $paletaEtiquetadorasHistorico[$colorIndex];
    };

    $chartHistorico = $modulosHistorico
        ->map(function (array $lineaHistorico) use ($colorEtiquetadoraHistorico): array {
            $totalLinea = (int) ($lineaHistorico['totales']['total'] ?? 0);
            $revisadoLinea = (int) ($lineaHistorico['totales']['revisado'] ?? 0);
            $porcentajeLinea = $totalLinea > 0 ? round(($revisadoLinea / $totalLinea) * 100, 1) : 0;
            $coloresUsados = [];

            return [
                'linea_id' => $lineaHistorico['linea_id'] ?? 0,
                'linea_nombre' => $lineaHistorico['linea_nombre'] ?? '-',
                'total' => $totalLinea,
                'revisado' => $revisadoLinea,
                'porcentaje' => $porcentajeLinea,
                'items' => collect($lineaHistorico['modulos'] ?? [])
                    ->values()
                    ->map(function (array $moduloData, int $index) use ($colorEtiquetadoraHistorico, &$coloresUsados): array {
                        $label = $moduloData['label'] ?? 'Etiquetadora';
                        $color = $colorEtiquetadoraHistorico($label, $index, $coloresUsados);

                        return [
                            'label' => $label,
                            'value' => max(0, (int) ($moduloData['revisado'] ?? 0)),
                            'total' => (int) ($moduloData['total'] ?? 0),
                            'revisado' => (int) ($moduloData['revisado'] ?? 0),
                            'pendiente' => (int) ($moduloData['pendiente'] ?? 0),
                            'porcentaje' => (float) ($moduloData['porcentaje'] ?? 0),
                            'color' => $color,
                        ];
                    })
                    ->values(),
            ];
        })
        ->filter(fn (array $lineaHistorico): bool => collect($lineaHistorico['items'] ?? [])->isNotEmpty())
        ->values();
@endphp

<style>
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --info-blue: #3b82f6;
        --light-gray: #f3f4f6;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
        --slate-900: #0f172a;
    }

    .historico-container {
        width: 100%;
        max-width: min(1400px, 100%);
        margin: 0 auto;
        padding: 24px;
        overflow-x: clip;
    }

    .historico-container *,
    .historico-container *::before,
    .historico-container *::after {
        box-sizing: border-box;
        min-width: 0;
    }

    .historico-container :where(h1, h2, h3, h4, p, span, a, button, div, td) {
        overflow-wrap: anywhere;
    }

    .lineas-section,
    .componentes-table,
    .grafica-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--medium-gray);
    }

    .lineas-section {
        padding: 20px;
        margin-bottom: 24px;
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .linea-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 10px 24px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 15px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        white-space: normal;
        line-height: 1.2;
        text-align: center;
        touch-action: manipulation;
    }

    .linea-btn i {
        color: #94a3b8;
        font-size: 14px;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
    }

    .linea-btn.active i {
        color: white;
    }

    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .resumen-card {
        background: white;
        border: 1px solid var(--medium-gray);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .resumen-icono {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .resumen-icono.total { background: #e2e8f0; color: #475569; }
    .resumen-icono.revisado { background: #dbeafe; color: #2563eb; }
    .resumen-icono.porcentaje { background: #d1fae5; color: #059669; }

    .resumen-info h4 {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .resumen-info .valor {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }

    .resumen-info .subvalor {
        font-size: 13px;
        color: #64748b;
        margin-top: 4px;
        font-weight: 600;
    }

    .componentes-table {
        overflow: hidden;
        margin-bottom: 24px;
    }

    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
        gap: 22px;
        padding: 20px;
        background: #f8fafc;
    }

    .linea-group-title {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px 22px;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.05);
    }

    .linea-group-main {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .linea-group-name {
        color: #1f2937;
        font-size: 18px;
        font-weight: 800;
    }

    .linea-group-meta {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
    }

    .linea-group-progress {
        min-width: 230px;
    }

    .modulo-summary-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-shadow: 0 4px 6px rgba(15, 23, 42, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .modulo-summary-card:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.1);
    }

    .modulo-summary-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .modulo-summary-title {
        margin: 0;
        color: var(--slate-900);
        font-size: 22px;
        font-weight: 800;
        line-height: 1.15;
    }

    .modulo-summary-subtitle {
        display: block;
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .modulo-summary-badge {
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .modulo-stats,
    .modulo-side-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .modulo-side-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .modulo-stat {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }

    .modulo-stat-label {
        display: block;
        margin-bottom: 4px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .modulo-stat-value {
        color: var(--slate-900);
        font-size: 18px;
        font-weight: 800;
    }

    .modulo-side-pill {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .modulo-side-pill.more {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .modulo-side-pill-label {
        color: #1e3a8a;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .modulo-side-pill.more .modulo-side-pill-label {
        color: #334155;
    }

    .modulo-side-pill-value {
        color: var(--slate-900);
        font-size: 13px;
        font-weight: 800;
    }

    .modulo-summary-footer {
        display: flex;
        justify-content: flex-end;
    }

    .maquinas-grid {
        display: grid;
        gap: 24px;
        padding: 20px;
        background: #f8fafc;
    }

    .maquina-card {
        overflow: hidden;
        border: 1px solid #dbe4ef;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.07);
    }

    .maquina-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 18px 22px;
        background: linear-gradient(135deg, #f8fafc, #eef4ff);
        border-bottom: 1px solid #e2e8f0;
    }

    .maquina-card-title {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f172a;
        font-size: 21px;
        font-weight: 800;
    }

    .maquina-card-title-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #dbeafe;
        color: #2563eb;
        font-size: 18px;
    }

    .maquina-card-summary {
        min-width: 250px;
    }

    .maquina-card-summary-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 7px;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
    }

    .maquina-card-progress {
        height: 10px;
        overflow: hidden;
        border-radius: 999px;
        background: #dbe4ef;
    }

    .maquina-card-progress > span {
        display: block;
        height: 100%;
        border-radius: inherit;
    }

    .maquina-card-progress > span.bg-success { background: #059669; }
    .maquina-card-progress > span.bg-info { background: #2563eb; }
    .maquina-card-progress > span.bg-warning { background: #d97706; }
    .maquina-card-progress > span.bg-danger { background: #dc2626; }

    .maquina-card-modulos {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
        gap: 18px;
        padding: 20px;
    }

    .maquina-module-title {
        font-size: 16px;
    }

    .modulo-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .modulo-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.1);
    }

    .modulo-card-header {
        padding: 16px 18px;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(135deg, #eef4ff, #ffffff);
    }

    .modulo-card-title {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #1e293b;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.35;
    }

    .modulo-card-title i {
        margin-top: 2px;
        color: #2563eb;
    }

    .modulo-card-meta {
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .modulo-componentes {
        display: grid;
        gap: 10px;
        padding: 14px;
    }

    .modulo-componente {
        padding: 13px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
    }

    .modulo-componente-heading,
    .modulo-componente-footer {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .modulo-componente-heading {
        color: #1e293b;
        font-size: 14px;
        font-weight: 800;
    }

    .modulo-componente-subtitle {
        margin-top: 4px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.4;
    }

    .modulo-componente-progress {
        position: relative;
        height: 9px;
        margin: 12px 0 10px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .modulo-componente-progress > span {
        display: block;
        height: 100%;
        border-radius: inherit;
    }

    .modulo-componente-progress > span.bg-success { background: #059669; }
    .modulo-componente-progress > span.bg-info { background: #2563eb; }
    .modulo-componente-progress > span.bg-warning { background: #d97706; }
    .modulo-componente-progress > span.bg-danger { background: #dc2626; }

    .modulo-componente-footer {
        align-items: center;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .modulo-componente-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .modulo-empty {
        grid-column: 1 / -1;
        padding: 40px 20px;
        color: #64748b;
        text-align: center;
        font-weight: 700;
    }

    .table-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .table-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        line-height: 1.35;
    }

    .table-header .badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .table-responsive {
        overflow-x: auto;
        width: 100%;
        background: white;
    }

    .table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
    }

    .table th {
        background: #f8fafc;
        padding: 16px;
        font-weight: 600;
        font-size: 14px;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
    }

    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        color: #334155;
        font-size: 14px;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .componente-nombre {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #1e293b;
    }

    .componente-icono {
        width: 50px;
        height: 50px;
        flex: 0 0 50px;
        border-radius: 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .componente-icono:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .componente-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .componente-nombre-texto {
        font-weight: 700;
        color: #1f2937;
    }

    .componente-meta {
        display: block;
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.4;
    }

    .cantidad-badge,
    .estado-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        gap: 6px;
        line-height: 1;
        white-space: nowrap;
    }

    .cantidad-badge {
        background: #e2e8f0;
        padding: 7px 12px;
        color: #1e293b;
    }

    .estado-badge {
        border: 1px solid transparent;
        padding: 7px 10px;
    }

    .estado-badge i {
        font-size: 7px;
    }

    .estado-badge.success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .estado-badge.review { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .estado-badge.warning { background: #ffedd5; color: #9a3412; border-color: #fed7aa; }
    .estado-badge.danger { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .estado-badge.changed { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
    .estado-badge.neutral { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

    .text-success { color: #10b981 !important; }
    .text-info { color: #3b82f6 !important; }
    .text-warning { color: #f59e0b !important; }
    .text-danger { color: #ef4444 !important; }

    .progreso-numerico {
        font-weight: 800;
        font-size: 15px;
    }

    .progress-container {
        width: 100%;
        background: #e2e8f0;
        border-radius: 8px;
        height: 24px;
        position: relative;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        font-size: 12px;
        font-weight: 600;
        color: white;
        transition: width 0.5s ease;
    }

    .progress-bar.bg-success { background: linear-gradient(90deg, #10b981, #059669) !important; }
    .progress-bar.bg-info { background: linear-gradient(90deg, #3b82f6, #2563eb) !important; }
    .progress-bar.bg-warning { background: linear-gradient(90deg, #f59e0b, #d97706) !important; }
    .progress-bar.bg-danger { background: linear-gradient(90deg, #ef4444, #dc2626) !important; }

    .progress-label {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #1e293b;
        font-size: 12px;
        font-weight: 600;
        z-index: 5;
    }

    .actions-cell {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
        min-height: 44px;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        touch-action: manipulation;
    }

    .btn-sm {
        min-height: 36px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #475569;
    }

    .btn-secondary:hover {
        background: #cbd5e1;
    }

    .grafica-section {
        padding: 24px;
        margin-bottom: 24px;
    }

    .grafica-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .acciones {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 24px;
    }

    .empty-state {
        padding: 44px 18px;
        color: #64748b;
        text-align: center;
        font-weight: 600;
    }

    .modal-module-overview {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
    }

    .modal-overview-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px;
    }

    .modal-overview-label {
        display: block;
        margin-bottom: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .modal-overview-value {
        display: block;
        color: var(--slate-900);
        font-size: 20px;
        font-weight: 800;
        line-height: 1.2;
    }

    .modal-levels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
        gap: 16px;
    }

    .modal-level-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .modal-level-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.1);
    }

    .modal-level-card:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.45);
        outline-offset: 3px;
    }

    .modal-level-card.is-selected {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16), 0 8px 16px rgba(15, 23, 42, 0.1);
    }

    .modal-level-header,
    .modal-side-header,
    .modal-review-summary,
    .grafica-linea-header,
    .grafica-legend-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .modal-level-title {
        margin: 0;
        color: var(--slate-900);
        font-size: 20px;
        font-weight: 800;
        line-height: 1.15;
    }

    .modal-level-subtitle {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .modal-level-badge {
        background: #dbeafe;
        border-radius: 999px;
        color: #1d4ed8;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .modal-level-section-title {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .modal-level-progress-meta,
    .modal-level-component-progress-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .modal-level-sides,
    .modal-side-components {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .modal-side-block,
    .modal-review-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .modal-review-context {
        flex-wrap: wrap;
        justify-content: flex-end;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
    }

    .modal-review-context span + span::before {
        content: "/";
        margin-right: 10px;
        color: #cbd5e1;
    }

    .modal-review-progress {
        min-width: 0;
    }

    .grafica-subtitle {
        margin: -16px 0 22px;
        color: #64748b;
        font-size: 14px;
        font-weight: 700;
    }

    .grafica-lineas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 420px), 1fr));
        gap: 20px;
    }

    .grafica-linea-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .grafica-linea-title {
        color: var(--slate-900);
        font-size: 20px;
        font-weight: 800;
    }

    .grafica-linea-summary {
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
    }

    .grafica-pie-layout {
        display: grid;
        grid-template-columns: minmax(min(100%, 250px), 320px) minmax(0, 1fr);
        gap: 18px;
        align-items: center;
    }

    .grafica-pie-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .grafica-pie-wrapper {
        width: 100%;
        max-width: 300px;
        aspect-ratio: 1;
        position: relative;
    }

    .grafica-pie-canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .grafica-pie-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        text-align: center;
        padding: 18px;
    }

    .grafica-pie-center-value {
        color: var(--slate-900);
        font-size: 32px;
        line-height: 1;
        font-weight: 800;
    }

    .grafica-pie-center-label {
        margin-top: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .grafica-legend {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .grafica-legend-title {
        color: var(--slate-900);
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .grafica-legend-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .grafica-legend-name {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--slate-900);
        font-size: 14px;
        font-weight: 800;
    }

    .grafica-color-dot {
        width: 12px;
        height: 12px;
        flex: 0 0 12px;
        border-radius: 999px;
    }

    .grafica-legend-value {
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .grafica-legend-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    .grafica-color-dot.success { background: #10b981; }
    .grafica-color-dot.info { background: #3b82f6; }
    .grafica-color-dot.warning { background: #f59e0b; }
    .grafica-color-dot.danger { background: #ef4444; }

    .etq-detail-modal {
        position: fixed;
        inset: 0;
        z-index: 1100;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.6);
    }

    .etq-detail-modal.show {
        display: flex;
    }

    .etq-detail-dialog {
        width: min(960px, 100%);
        max-height: calc(100vh - 40px);
        overflow: hidden;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.28);
    }

    .etq-detail-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
    }

    .etq-detail-heading {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .etq-detail-title-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #dbeafe;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .etq-detail-title {
        margin: 0;
        color: #0f172a;
        font-size: 22px;
        font-weight: 800;
        line-height: 1.2;
    }

    .etq-detail-subtitle {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .etq-detail-subtitle span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 6px 10px;
    }

    .etq-detail-close {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        border: 1px solid #e2e8f0;
        border-radius: 50%;
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .etq-detail-close:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .etq-detail-body {
        max-height: calc(100vh - 132px);
        overflow-y: auto;
        padding: 20px;
        background: #ffffff;
    }

    .etq-detail-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .etq-detail-stat,
    .etq-detail-section,
    .etq-detail-record {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .etq-detail-stat {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border-left: 4px solid #475569;
    }

    .etq-detail-stat-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
        background: #f3f4f6;
        color: #4b5563;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .etq-detail-stat.is-success { border-left-color: #10b981; }
    .etq-detail-stat.is-success .etq-detail-stat-icon { background: #d1fae5; color: #047857; }
    .etq-detail-stat.is-info { border-left-color: #3b82f6; }
    .etq-detail-stat.is-info .etq-detail-stat-icon { background: #dbeafe; color: #1d4ed8; }
    .etq-detail-stat.is-warning,
    .etq-detail-stat.is-review { border-left-color: #f59e0b; }
    .etq-detail-stat.is-warning .etq-detail-stat-icon,
    .etq-detail-stat.is-review .etq-detail-stat-icon { background: #fef3c7; color: #92400e; }
    .etq-detail-stat.is-danger { border-left-color: #ef4444; }
    .etq-detail-stat.is-danger .etq-detail-stat-icon { background: #fee2e2; color: #991b1b; }
    .etq-detail-stat.is-changed { border-left-color: #3b82f6; }
    .etq-detail-stat.is-changed .etq-detail-stat-icon { background: #dbeafe; color: #1d4ed8; }

    .etq-detail-stat-content {
        min-width: 0;
    }

    .etq-detail-label {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .etq-detail-value {
        display: block;
        margin-top: 6px;
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.25;
    }

    .etq-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
        gap: 14px;
    }

    .etq-detail-section {
        padding: 16px;
    }

    .etq-detail-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 12px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .etq-detail-meta-list {
        display: grid;
        gap: 8px;
    }

    .etq-detail-meta-row {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        border-radius: 8px;
        background: #f8fafc;
        padding: 10px;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
    }

    .etq-detail-meta-row span:first-child {
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .etq-detail-activity {
        min-height: 118px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 12px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.6;
        white-space: pre-line;
    }

    .etq-detail-pieces {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .etq-detail-piece {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 8px;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e40af;
        padding: 6px 9px;
        font-size: 12px;
        font-weight: 800;
    }

    .etq-detail-empty {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
    }

    .etq-detail-records {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 18px;
    }

    .etq-detail-record {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        padding: 13px;
    }

    .etq-detail-record-title {
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
    }

    .etq-detail-record-meta {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        margin-top: 4px;
    }

    .etq-detail-footer {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #e2e8f0;
    }

    @media (max-width: 768px) {
        .historico-container {
            padding: 12px;
            overflow-x: hidden;
        }

        .lineas-section,
        .componentes-table,
        .grafica-section {
            border-radius: 10px;
        }

        .lineas-section,
        .grafica-section {
            padding: 14px;
            margin-bottom: 16px;
        }

        .modulos-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 12px;
        }

        .modulo-stats,
        .modulo-side-summary,
        .modal-levels-grid,
        .grafica-lineas-grid,
        .grafica-pie-layout {
            grid-template-columns: 1fr;
        }

        .linea-group-title,
        .modulo-summary-header,
        .modal-level-header,
        .modal-side-header,
        .modal-review-summary,
        .grafica-linea-header,
        .grafica-legend-head {
            align-items: stretch;
            flex-direction: column;
        }

        .linea-group-progress {
            min-width: 0;
        }

        .modulo-summary-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .maquinas-grid,
        .maquina-card-modulos {
            gap: 12px;
            padding: 12px;
        }

        .maquina-card-header {
            align-items: stretch;
            flex-direction: column;
            gap: 14px;
            padding: 14px;
        }

        .maquina-card-summary {
            min-width: 0;
        }

        .modulo-card-header {
            padding: 14px;
        }

        .lineas-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .linea-btn {
            width: 100%;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }

        .resumen-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .resumen-card {
            align-items: flex-start;
            padding: 14px;
            border-radius: 10px;
        }

        .resumen-icono {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
            border-radius: 8px;
            font-size: 20px;
        }

        .resumen-info h4 {
            font-size: 12px;
        }

        .resumen-info .valor {
            font-size: 24px;
        }

        .table-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 14px;
        }

        .table-header h3 {
            font-size: 15px;
        }

        .table-header .badge {
            white-space: normal;
        }

        .table-responsive {
            overflow-x: visible;
        }

        .table,
        .table tbody,
        .table tr,
        .table td {
            display: block;
            min-width: 0;
            width: 100%;
        }

        .table thead {
            display: none;
        }

        .table tbody {
            padding: 12px;
            background: #f8fafc;
        }

        .table tbody tr {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            margin-bottom: 12px;
            padding: 12px;
        }

        .table tbody tr:last-child {
            margin-bottom: 0;
        }

        .table tbody tr:hover {
            background: white;
        }

        .table td {
            align-items: flex-start;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px 0;
        }

        .table td::before {
            color: #64748b;
            content: attr(data-label);
            flex: 0 0 120px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .table td:first-child {
            display: block;
            padding-top: 0;
        }

        .table td:first-child::before {
            display: block;
            flex: none;
            margin-bottom: 8px;
        }

        .table td:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .actions-cell,
        .acciones {
            align-items: stretch;
            flex-direction: column;
        }

        .actions-cell .btn,
        .acciones .btn {
            width: 100%;
        }

        .grafica-section {
            padding: 16px 12px;
        }

        .grafica-title {
            align-items: flex-start;
            font-size: 16px;
            line-height: 1.35;
            margin-bottom: 18px;
        }

        .etq-detail-modal {
            padding: 12px;
        }

        .etq-detail-dialog {
            max-height: calc(100vh - 24px);
            border-radius: 14px;
        }

        .etq-detail-header,
        .etq-detail-body {
            padding: 16px;
        }

        .etq-detail-title {
            font-size: 18px;
        }

        .etq-detail-summary,
        .etq-detail-grid {
            grid-template-columns: 1fr;
        }

        .etq-detail-record {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 420px) {
        .historico-container {
            padding: 10px;
        }

        .lineas-grid {
            grid-template-columns: 1fr;
        }

        .table tbody {
            padding: 10px;
        }

        .table tbody tr {
            padding: 10px;
        }

        .table td {
            align-items: stretch;
            flex-direction: column;
            gap: 4px;
        }

        .table td::before {
            flex: none;
            max-width: none;
        }

        .cantidad-badge,
        .estado-badge {
            align-self: flex-start;
        }

        .etq-detail-meta-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .etq-detail-subtitle span,
        .etq-detail-piece {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="historico-container">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('etiquetadora.dashboard') }}"
               class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 sm:w-auto bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                <i class="fas fa-arrow-left"></i>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="flex items-start gap-2 text-xl font-bold text-gray-800 sm:items-center sm:text-2xl">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Historico de Revisados
            </h1>
            <p class="mt-1 text-sm font-semibold text-gray-500">
                {{ $lineaSeleccionada?->nombre ?? 'Linea sin seleccionar' }} - {{ filled($maquinaActual) ? 'Etiquetadora ' . $maquinaActual : 'Todas las etiquetadoras' }}
            </p>
        </div>
    </div>

    <div class="lineas-section">
        <div class="lineas-title">
            <i class="fas fa-tags"></i>
            LINEAS DE ETIQUETADORA
        </div>

        <div class="lineas-grid">
            @forelse($lineasEtiquetadora as $linea)
                <a href="{{ route('analisis-etiquetadora.historial', array_filter(array_merge(request()->except(['linea_id', 'page']), ['linea_id' => $linea->id]), fn ($value) => filled($value))) }}"
                   class="linea-btn {{ (string) $lineaActual === (string) $linea->id ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    {{ $linea->nombre }}
                </a>
            @empty
                <div class="text-gray-500 py-2">No hay lineas de etiquetadora disponibles.</div>
            @endforelse
        </div>
    </div>

    <div class="lineas-section">
        <div class="lineas-title">
            <i class="fas fa-industry"></i>
            MAQUINAS
        </div>

        <div class="lineas-grid">
            <a href="{{ route('analisis-etiquetadora.historial', array_filter(array_merge(request()->except(['maquina', 'page']), ['linea_id' => $lineaActual]), fn ($value) => filled($value))) }}"
               class="linea-btn {{ blank($maquinaActual) ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i>
                Todas
            </a>
            @forelse($maquinasDisponibles as $maquina)
                <a href="{{ route('analisis-etiquetadora.historial', array_filter(array_merge(request()->except(['maquina', 'page']), ['linea_id' => $lineaActual, 'maquina' => $maquina]), fn ($value) => filled($value))) }}"
                   class="linea-btn {{ $maquinaActual === $maquina ? 'active' : '' }}">
                    <i class="fas fa-industry"></i>
                    Etiquetadora {{ $maquina }}
                </a>
            @empty
                <div class="text-gray-500 py-2">No hay maquinas disponibles.</div>
            @endforelse
        </div>
    </div>

    <div class="resumen-grid">
        <div class="resumen-card">
            <div class="resumen-icono total">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="resumen-info">
                <h4>Total Analisis</h4>
                <div class="valor">{{ number_format($resumenHistorico['total_general'] ?? 0) }}</div>
                <div class="subvalor">{{ number_format($totalPaginado) }} registros historicos</div>
            </div>
        </div>

        <div class="resumen-card">
            <div class="resumen-icono revisado">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="resumen-info">
                <h4>Analisis Realizados</h4>
                <div class="valor">{{ number_format($resumenHistorico['revisado_general'] ?? 0) }}</div>
                <div class="subvalor">{{ number_format($resumenHistorico['pendiente_general'] ?? 0) }} pendientes</div>
            </div>
        </div>

        <div class="resumen-card">
            <div class="resumen-icono porcentaje">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="resumen-info">
                <h4>Progreso General</h4>
                <div class="valor">{{ $resumenHistorico['porcentaje_general'] ?? 0 }}%</div>
                <div class="subvalor">Ultima revision: {{ $resumenHistorico['ultima_revision'] ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="componentes-table">
        <div class="table-header">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                MAQUINAS DE {{ $lineaSeleccionada?->nombre ?? 'LA LINEA SELECCIONADA' }}
            </h3>
            <span class="badge">{{ $filasPorMaquina->count() }} maquinas</span>
        </div>

        <div class="modulos-grid">
            @forelse($modulosHistorico as $lineaHistorico)
                @php
                    $lineaTotal = $lineaHistorico['totales']['total'] ?? 0;
                    $lineaRevisado = $lineaHistorico['totales']['revisado'] ?? 0;
                    $lineaPorcentaje = $lineaTotal > 0
                        ? round(($lineaRevisado / $lineaTotal) * 100, 1)
                        : 0;
                    $lineaColor = $porcentajeColorHistorico($lineaPorcentaje);
                @endphp

                <div class="linea-group-title">
                    <div class="linea-group-main">
                        <span class="linea-group-name">Linea {{ $lineaHistorico['linea_nombre'] }}</span>
                        <span class="linea-group-meta">
                            {{ collect($lineaHistorico['modulos'])->count() }} etiquetadoras / {{ number_format($lineaRevisado) }} de {{ number_format($lineaTotal) }} piezas revisadas
                        </span>
                    </div>
                    <div class="linea-group-progress">
                        <div class="progress-container">
                            <span class="progress-label">{{ $lineaPorcentaje }}%</span>
                            <div class="progress-bar bg-{{ $lineaColor }}" style="width: {{ max(0, min(100, (float) $lineaPorcentaje)) }}%;"></div>
                        </div>
                    </div>
                </div>

                @foreach($lineaHistorico['modulos'] as $moduloData)
                    @php
                        $templateId = 'etq-template-' . $lineaHistorico['linea_id'] . '-' . \Illuminate\Support\Str::slug((string) $moduloData['numero']);
                        $gruposModulo = collect($moduloData['grupos']);
                        $moduloNumero = $moduloData['numero'] === 'SIN_ASIGNAR' ? '' : $moduloData['numero'];
                        $analisisMaquinaUrl = route('analisis-etiquetadora.index', array_filter([
                            'linea_id' => $lineaHistorico['linea_id'],
                            'maquina' => filled($moduloNumero) ? $moduloNumero : null,
                        ], fn ($value) => filled($value)));
                    @endphp

                    <div class="modulo-summary-card">
                        <div class="modulo-summary-header">
                            <div>
                                <h4 class="modulo-summary-title">{{ $moduloData['label'] }}</h4>
                                <span class="modulo-summary-subtitle">Grupos de etiquetadora</span>
                            </div>
                            <span class="modulo-summary-badge">
                                {{ $gruposModulo->count() }} grupos / {{ $moduloData['componentes_count'] }} componentes
                            </span>
                        </div>

                        <div class="modulo-stats">
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Total</span>
                                <span class="modulo-stat-value">{{ number_format($moduloData['total']) }}</span>
                            </div>
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Revisado</span>
                                <span class="modulo-stat-value">{{ number_format($moduloData['revisado']) }}</span>
                            </div>
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Avance</span>
                                <span class="modulo-stat-value">{{ $moduloData['porcentaje'] }}%</span>
                            </div>
                        </div>

                        <div class="modulo-side-summary">
                            @foreach($gruposModulo->take(4) as $grupoData)
                                <div class="modulo-side-pill">
                                    <span class="modulo-side-pill-label">{{ $grupoData['label'] }}</span>
                                    <span class="modulo-side-pill-value">{{ number_format($grupoData['revisado']) }}/{{ number_format($grupoData['total']) }} | {{ $grupoData['porcentaje'] }}%</span>
                                </div>
                            @endforeach

                            @if($gruposModulo->count() > 4)
                                <div class="modulo-side-pill more">
                                    <span class="modulo-side-pill-label">Mas grupos</span>
                                    <span class="modulo-side-pill-value">{{ $gruposModulo->count() - 4 }} adicionales</span>
                                </div>
                            @endif
                        </div>

                        <div class="progress-container">
                            <span class="progress-label">{{ $moduloData['porcentaje'] }}%</span>
                            <div class="progress-bar bg-{{ $moduloData['color'] }}" style="width: {{ max(0, min(100, (float) $moduloData['porcentaje'])) }}%;"></div>
                        </div>

                        <div class="modulo-summary-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="abrirModalEtiquetadora('{{ $templateId }}', '{{ $moduloData['label'] }}', '{{ $lineaHistorico['linea_nombre'] }}', this)">
                                <i class="fas fa-layer-group"></i>
                                Ver Detalles
                            </button>
                        </div>
                    </div>

                    <template id="{{ $templateId }}">
                        <div class="modal-module-overview">
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Etiquetadora</span>
                                <span class="modal-overview-value">{{ $moduloData['label'] }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Linea</span>
                                <span class="modal-overview-value">{{ $lineaHistorico['linea_nombre'] }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Revisado</span>
                                <span class="modal-overview-value">{{ number_format($moduloData['revisado']) }}/{{ number_format($moduloData['total']) }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Avance general</span>
                                <span class="modal-overview-value">{{ $moduloData['porcentaje'] }}%</span>
                            </div>
                        </div>

                        <div class="modal-levels-grid">
                            @foreach($gruposModulo as $grupoData)
                                <article
                                    class="modal-level-card {{ $loop->first ? 'is-selected' : '' }}"
                                    tabindex="0"
                                    role="button"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    onclick="seleccionarNivelModal(this)"
                                    onkeydown="manejarNivelModal(event, this)">
                                    <div class="modal-level-header">
                                        <div>
                                            <h4 class="modal-level-title">{{ $grupoData['label'] }}</h4>
                                            <span class="modal-level-subtitle">{{ $moduloData['label'] }} / {{ $lineaHistorico['linea_nombre'] }}</span>
                                        </div>
                                        <span class="modal-level-badge">{{ $grupoData['porcentaje'] }}%</span>
                                    </div>

                                    <div>
                                        <div class="modal-level-section-title">Avance del grupo</div>
                                        <div class="modal-level-progress-meta">
                                            <span>Revisados</span>
                                            <span>{{ number_format($grupoData['revisado']) }}/{{ number_format($grupoData['total']) }}</span>
                                        </div>
                                        <div class="progress-container">
                                            <span class="progress-label">{{ $grupoData['porcentaje'] }}%</span>
                                            <div class="progress-bar bg-{{ $grupoData['color'] }}" style="width: {{ max(0, min(100, (float) $grupoData['porcentaje'])) }}%;"></div>
                                        </div>
                                    </div>

                                    <div class="modal-level-sides">
                                        <div class="modal-level-section-title">Componentes de este grupo</div>
                                        <div class="modal-side-components">
                                            @foreach($grupoData['componentes'] as $fila)
                                                <article class="modal-review-card">
                                                    <div class="modal-review-summary">
                                                        <div class="componente-nombre">
                                                            <span>{{ $fila['componente'] }}</span>
                                                        </div>
                                                        <span class="estado-badge {{ $fila['estado_clase'] }}">
                                                            <i class="fas fa-circle"></i>
                                                            {{ $fila['estado'] }}
                                                        </span>
                                                    </div>

                                                    <div class="modal-review-context">
                                                        <span>{{ number_format($fila['cantidad_revisada']) }}/{{ number_format($fila['cantidad_total']) }} piezas</span>
                                                        <span>{{ $fila['ultima_revision'] ?: 'Sin revision' }}</span>
                                                        @if(filled($fila['usuario_ultima_revision']))
                                                            <span>{{ $fila['usuario_ultima_revision'] }}</span>
                                                        @endif
                                                    </div>

                                                    <div class="modal-review-progress">
                                                        <div class="modal-level-component-progress-meta">
                                                            <span>Avance</span>
                                                            <span>{{ $fila['porcentaje'] }}%</span>
                                                        </div>
                                                        <div class="progress-container">
                                                            <span class="progress-label">{{ $fila['porcentaje'] }}%</span>
                                                            <div class="progress-bar bg-{{ $fila['color'] }}" style="width: {{ max(0, min(100, (float) $fila['porcentaje'])) }}%;"></div>
                                                        </div>
                                                    </div>

                                                    <div class="modulo-componente-actions">
                                                        <button type="button"
                                                                class="btn btn-sm btn-primary js-etq-detail-button"
                                                                data-component-detail='@json($fila['modal_payload'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
                                                            <i class="fas fa-eye"></i>
                                                            Ver
                                                        </button>
                                                        <a href="{{ $fila['analysis_url'] }}" class="btn btn-sm btn-secondary">
                                                            <i class="fas fa-chart-pie"></i>
                                                            Analisis
                                                        </a>
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="acciones">
                            <a href="{{ $analisisMaquinaUrl }}" class="btn btn-primary">
                                <i class="fas fa-chart-pie"></i>
                                Ver Analisis de la Etiquetadora
                            </a>
                        </div>
                    </template>
                @endforeach
            @empty
                <div class="modulo-empty">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>No hay componentes configurados para estos filtros.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($chartHistorico->isNotEmpty())
        <div class="grafica-section">
            <div class="grafica-title">
                <i class="fas fa-chart-pie text-blue-600"></i>
                GRAFICA DE AVANCE POR ETIQUETADORA
            </div>
            <div class="grafica-subtitle">
                Distribucion de revisados por etiquetadora dentro de la linea seleccionada.
            </div>

            <div class="grafica-lineas-grid">
                @foreach($chartHistorico as $chartLine)
                    @php
                        $chartId = 'etq-historico-pie-' . ($chartLine['linea_id'] ?? $loop->index);
                    @endphp
                    <div class="grafica-linea-card">
                        <div class="grafica-linea-header">
                            <div>
                                <div class="grafica-linea-title">Linea {{ $chartLine['linea_nombre'] }}</div>
                                <div class="grafica-linea-summary">
                                    {{ number_format($chartLine['revisado']) }} de {{ number_format($chartLine['total']) }} piezas revisadas
                                </div>
                            </div>
                        </div>

                        <div class="grafica-pie-layout">
                            <div class="grafica-pie-panel">
                                <div class="grafica-pie-wrapper">
                                    <canvas id="{{ $chartId }}" class="grafica-pie-canvas" data-grafica-pastel='@json($chartLine['items'])'></canvas>
                                    <div class="grafica-pie-center">
                                        <div class="grafica-pie-center-value">{{ $chartLine['porcentaje'] }}%</div>
                                        <div class="grafica-pie-center-label">avance general</div>
                                    </div>
                                </div>
                            </div>

                            <div class="grafica-legend">
                                <div class="grafica-legend-title">Etiquetadoras</div>
                                @foreach($chartLine['items'] as $chartItem)
                                    <div class="grafica-legend-item">
                                        <div class="grafica-legend-head">
                                            <div class="grafica-legend-name">
                                                <span class="grafica-color-dot" style="background: {{ $chartItem['color'] }};"></span>
                                                <span>{{ $chartItem['label'] }}</span>
                                            </div>
                                            <span class="grafica-legend-value">{{ $chartItem['porcentaje'] }}%</span>
                                        </div>
                                        <div class="grafica-legend-meta">
                                            <span>{{ number_format($chartItem['revisado']) }}/{{ number_format($chartItem['total']) }}</span>
                                            <span>{{ number_format($chartItem['pendiente']) }} pendientes</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="acciones">
        <a href="{{ route('analisis-etiquetadora.index', array_filter([
            'linea_id' => filled($lineaActual) ? $lineaActual : null,
            'maquina' => filled($maquinaActual) ? strtoupper((string) $maquinaActual) : null,
        ], fn ($value) => filled($value))) }}" class="btn btn-primary">
            <i class="fas fa-chart-pie"></i>
            Ver Analisis Detallado
        </a>

        <button type="button" class="btn btn-success" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i>
            Actualizar Datos
        </button>
    </div>
</div>

<div id="etqMachineDetailModal" class="etq-detail-modal" aria-hidden="true">
    <div class="etq-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="etqMachineDetailTitle">
        <div class="etq-detail-header">
            <div class="etq-detail-heading">
                <div class="etq-detail-title-icon" aria-hidden="true">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3 id="etqMachineDetailTitle" class="etq-detail-title">Detalle de Etiquetadora</h3>
                    <div id="etqMachineDetailSubtitle" class="etq-detail-subtitle"></div>
                </div>
            </div>
            <button type="button" class="etq-detail-close" aria-label="Cerrar detalle" data-etq-machine-close>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="etqMachineDetailBody" class="etq-detail-body"></div>
    </div>
</div>

<div id="etqComponentDetailModal" class="etq-detail-modal" aria-hidden="true">
    <div class="etq-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="etqComponentDetailTitle">
        <div class="etq-detail-header">
            <div class="etq-detail-heading">
                <div class="etq-detail-title-icon" aria-hidden="true">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 id="etqComponentDetailTitle" class="etq-detail-title">Detalle del componente</h3>
                    <div id="etqComponentDetailSubtitle" class="etq-detail-subtitle"></div>
                </div>
            </div>
            <button type="button" class="etq-detail-close" aria-label="Cerrar detalle" data-etq-detail-close>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="etqComponentDetailBody" class="etq-detail-body"></div>
    </div>
</div>

<script>
let etqLastDetailTrigger = null;
let etqLastMachineTrigger = null;

function escapeEtqHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function etqEstadoClass(value) {
    const allowed = ['success', 'review', 'warning', 'danger', 'changed', 'neutral'];
    return allowed.includes(value) ? value : 'neutral';
}

function etqFormatValue(value, fallback = '-') {
    return value === null || value === undefined || value === '' ? fallback : value;
}

function abrirModalEtiquetadora(templateId, titulo, linea, trigger = null) {
    const modal = document.getElementById('etqMachineDetailModal');
    const body = document.getElementById('etqMachineDetailBody');
    const template = document.getElementById(templateId);

    if (!modal || !body || !template) {
        return;
    }

    etqLastMachineTrigger = trigger;
    document.getElementById('etqMachineDetailTitle').textContent = titulo || 'Detalle de Etiquetadora';
    document.getElementById('etqMachineDetailSubtitle').innerHTML = `
        <span><i class="fas fa-tags"></i> Linea ${escapeEtqHtml(etqFormatValue(linea))}</span>
        <span><i class="fas fa-layer-group"></i> Grupos de componentes</span>
    `;
    body.innerHTML = '';
    body.appendChild(template.content.cloneNode(true));

    const firstCard = body.querySelector('.modal-level-card');
    if (firstCard) {
        seleccionarNivelModal(firstCard);
    }

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    modal.querySelector('[data-etq-machine-close]')?.focus();
}

function cerrarModalEtiquetadora() {
    const modal = document.getElementById('etqMachineDetailModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    etqLastMachineTrigger?.focus();
    etqLastMachineTrigger = null;
}

function seleccionarNivelModal(card) {
    const modalBody = card.closest('.etq-detail-body') || document;
    modalBody.querySelectorAll('.modal-level-card').forEach((item) => {
        item.classList.remove('is-selected');
        item.setAttribute('aria-pressed', 'false');
    });

    card.classList.add('is-selected');
    card.setAttribute('aria-pressed', 'true');
}

function manejarNivelModal(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        seleccionarNivelModal(card);
    }
}

function inicializarGraficasPastel() {
    if (typeof Chart === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-grafica-pastel]').forEach((canvas) => {
        const rawData = canvas.getAttribute('data-grafica-pastel');
        const modules = rawData ? JSON.parse(rawData) : [];
        const hasProgress = modules.some((item) => Number(item.value) > 0);
        const labels = hasProgress ? modules.map((item) => item.label) : ['Sin avance'];
        const values = hasProgress ? modules.map((item) => Number(item.value)) : [1];
        const colors = hasProgress ? modules.map((item) => item.color || '#94a3b8') : ['#cbd5e1'];
        const ctx = canvas.getContext('2d');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (!hasProgress) {
                                    return 'Sin avance registrado';
                                }

                                const modulo = modules[context.dataIndex];
                                return `${modulo.label}: ${modulo.revisado}/${modulo.total} revisados (${modulo.porcentaje}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
}

function etqUniqueValues(values) {
    return Array.from(new Set((values || []).filter((value) => value !== null && value !== undefined && value !== '')));
}

function renderEtqPieceList(pieces, emptyText) {
    const cleanPieces = etqUniqueValues(pieces).map((piece) => Number(piece)).filter((piece) => Number.isFinite(piece));

    if (!cleanPieces.length) {
        return `<span class="etq-detail-empty">${escapeEtqHtml(emptyText)}</span>`;
    }

    const visiblePieces = cleanPieces.slice(0, 36);
    const hiddenCount = cleanPieces.length - visiblePieces.length;
    const chips = visiblePieces
        .map((piece) => `
            <span class="etq-detail-piece">
                <i class="fas fa-check"></i>
                #${escapeEtqHtml(piece)}
            </span>
        `)
        .join('');

    return chips + (hiddenCount > 0
        ? `<span class="etq-detail-piece">+${hiddenCount} mas</span>`
        : '');
}

function renderEtqDetailStat(label, value, icon = 'fas fa-info-circle', accent = 'neutral') {
    return `
        <div class="etq-detail-stat is-${escapeEtqHtml(accent)}">
            <span class="etq-detail-stat-icon">
                <i class="${escapeEtqHtml(icon)}"></i>
            </span>
            <span class="etq-detail-stat-content">
                <span class="etq-detail-label">${escapeEtqHtml(label)}</span>
                <span class="etq-detail-value">${escapeEtqHtml(etqFormatValue(value))}</span>
            </span>
        </div>
    `;
}

function renderEtqDetailModal(data) {
    const modalTitle = document.getElementById('etqComponentDetailTitle');
    const modalSubtitle = document.getElementById('etqComponentDetailSubtitle');
    const modalBody = document.getElementById('etqComponentDetailBody');
    const detalles = Array.isArray(data.detalles) ? data.detalles : [];
    const estadoClass = etqEstadoClass(data.estado_clase);
    const piezasRevisadas = detalles.flatMap((detalle) => Array.isArray(detalle.piezas_revisadas) ? detalle.piezas_revisadas : []);
    const piezasPendientes = detalles.flatMap((detalle) => Array.isArray(detalle.piezas_pendientes) ? detalle.piezas_pendientes : []);
    const actividad = data.actividad_ultima_revision || detalles.find((detalle) => detalle.actividad_ultima_revision)?.actividad_ultima_revision;

    modalTitle.textContent = data.componente || 'Detalle del componente';
    modalSubtitle.innerHTML = `
        <span><i class="fas fa-layer-group"></i> Linea ${escapeEtqHtml(etqFormatValue(data.linea))}</span>
        <span><i class="fas fa-industry"></i> Etiquetadora ${escapeEtqHtml(etqFormatValue(data.maquina))}</span>
        ${data.descriptor ? `<span><i class="fas fa-cog"></i> ${escapeEtqHtml(data.descriptor)}</span>` : ''}
    `;

    modalBody.innerHTML = `
        <div class="etq-detail-summary">
            <div class="etq-detail-stat is-${estadoClass}">
                <span class="etq-detail-stat-icon">
                    <i class="fas fa-clipboard-check"></i>
                </span>
                <span class="etq-detail-stat-content">
                    <span class="etq-detail-label">Estado</span>
                    <span class="etq-detail-value">
                        <span class="estado-badge ${estadoClass}">
                            <i class="fas fa-circle"></i>
                            ${escapeEtqHtml(etqFormatValue(data.estado, 'Pendiente'))}
                        </span>
                    </span>
                </span>
            </div>
            ${renderEtqDetailStat('Ultima revision', data.ultima_revision || 'Sin revision', 'far fa-calendar-alt', 'info')}
            ${renderEtqDetailStat('Revisado', `${Number(data.cantidad_revisada || 0)} / ${Number(data.cantidad_total || 0)}`, 'fas fa-tasks', 'success')}
        </div>

        <div class="etq-detail-grid">
            <section class="etq-detail-section">
                <h4 class="etq-detail-section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Ultimo analisis
                </h4>
                <div class="etq-detail-meta-list">
                    <div class="etq-detail-meta-row">
                        <span>Usuario</span>
                        <strong>${escapeEtqHtml(etqFormatValue(data.usuario_ultima_revision, 'Sin usuario'))}</strong>
                    </div>
                    <div class="etq-detail-meta-row">
                        <span>Orden</span>
                        <strong>${escapeEtqHtml(etqFormatValue(data.numero_orden_ultima_revision, 'Sin orden'))}</strong>
                    </div>
                    <div class="etq-detail-meta-row">
                        <span>Avance</span>
                        <strong>${Number(data.porcentaje || 0)}%</strong>
                    </div>
                </div>
            </section>

            <section class="etq-detail-section">
                <h4 class="etq-detail-section-title">
                    <i class="fas fa-comment-dots"></i>
                    Actividad registrada
                </h4>
                <div class="etq-detail-activity">${escapeEtqHtml(etqFormatValue(actividad, 'Sin actividad registrada.'))}</div>
            </section>
        </div>

        <div class="etq-detail-grid" style="margin-top: 16px;">
            <section class="etq-detail-section">
                <h4 class="etq-detail-section-title">
                    <i class="fas fa-check-circle"></i>
                    Piezas revisadas
                </h4>
                <div class="etq-detail-pieces">${renderEtqPieceList(piezasRevisadas, 'Sin piezas revisadas')}</div>
            </section>

            <section class="etq-detail-section">
                <h4 class="etq-detail-section-title">
                    <i class="fas fa-clock"></i>
                    Piezas pendientes
                </h4>
                <div class="etq-detail-pieces">${renderEtqPieceList(piezasPendientes, 'Sin piezas pendientes')}</div>
            </section>
        </div>

        ${detalles.length > 1 ? renderEtqDetailRecords(detalles) : ''}

        <div class="etq-detail-footer">
            ${data.analysis_url ? `
                <a href="${escapeEtqHtml(data.analysis_url)}" class="btn btn-primary">
                    <i class="fas fa-chart-pie"></i>
                    Ver Analisis
                </a>
            ` : ''}
            <button type="button" class="btn btn-secondary" data-etq-detail-close>
                <i class="fas fa-times"></i>
                Cerrar
            </button>
        </div>
    `;
}

function renderEtqDetailRecords(detalles) {
    const records = detalles.map((detalle) => `
        <article class="etq-detail-record">
            <div>
                <div class="etq-detail-record-title">
                    Linea ${escapeEtqHtml(etqFormatValue(detalle.linea))} - Etiquetadora ${escapeEtqHtml(etqFormatValue(detalle.maquina))}
                </div>
                <div class="etq-detail-record-meta">
                    Ultima: ${escapeEtqHtml(etqFormatValue(detalle.ultima_revision, 'Sin revision'))}
                    ${detalle.usuario_ultima_revision ? ` - ${escapeEtqHtml(detalle.usuario_ultima_revision)}` : ''}
                </div>
            </div>
            <span class="progreso-numerico">
                ${Number(detalle.cantidad_revisada || 0)} / ${Number(detalle.cantidad_total || 0)}
            </span>
        </article>
    `).join('');

    return `
        <section class="etq-detail-records">
            <h4 class="etq-detail-section-title">
                <i class="fas fa-list-ul"></i>
                Detalle agrupado
            </h4>
            ${records}
        </section>
    `;
}

function abrirModalDetalleComponente(button) {
    const modal = document.getElementById('etqComponentDetailModal');

    if (!modal) {
        return;
    }

    try {
        const payload = JSON.parse(button.dataset.componentDetail || '{}');
        etqLastDetailTrigger = button;
        renderEtqDetailModal(payload);
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-etq-detail-close]')?.focus();
    } catch (error) {
        console.error('No se pudo abrir el detalle del componente', error);
    }
}

function cerrarModalDetalleComponente() {
    const modal = document.getElementById('etqComponentDetailModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    if (!document.getElementById('etqMachineDetailModal')?.classList.contains('show')) {
        document.body.style.overflow = '';
    }
    etqLastDetailTrigger?.focus();
    etqLastDetailTrigger = null;
}

document.addEventListener('click', function (event) {
    const detailButton = event.target.closest('.js-etq-detail-button');

    if (detailButton) {
        abrirModalDetalleComponente(detailButton);
        return;
    }

    if (event.target.matches('[data-etq-detail-close]') || event.target.closest('[data-etq-detail-close]')) {
        cerrarModalDetalleComponente();
        return;
    }

    if (event.target.matches('[data-etq-machine-close]') || event.target.closest('[data-etq-machine-close]')) {
        cerrarModalEtiquetadora();
        return;
    }

    const modal = document.getElementById('etqComponentDetailModal');

    if (modal && event.target === modal) {
        cerrarModalDetalleComponente();
    }

    const machineModal = document.getElementById('etqMachineDetailModal');

    if (machineModal && event.target === machineModal) {
        cerrarModalEtiquetadora();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const detailModal = document.getElementById('etqComponentDetailModal');
        const machineModal = document.getElementById('etqMachineDetailModal');

        if (detailModal?.classList.contains('show')) {
            cerrarModalDetalleComponente();
            return;
        }

        if (machineModal?.classList.contains('show')) {
            cerrarModalEtiquetadora();
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficasPastel();
});
</script>
@endsection
