@extends('layouts.app')

@section('title', $analisisTitulo ?? 'Central Hidraulica')

@section('content')
@php
    use App\Models\AnalisisCentralHidraulica;

    $analisisCollection = collect($analisis);
    $estadoGrupo = request('estado_grupo');
    $estadoFiltro = request('estado');
    $pisoFiltro = request('piso');
    $ladoFiltro = request('lado');
    $componenteFiltro = request('componente_id');

    $registrosPorEstado = [
        'total' => $analisisCollection,
        'buen_estado' => $analisisCollection->where('estado', AnalisisCentralHidraulica::ESTADO_BUENO),
        'requiere_revision' => $analisisCollection->where('estado', AnalisisCentralHidraulica::ESTADO_REQUIERE_REVISION),
        'desgaste' => $analisisCollection->whereIn('estado', AnalisisCentralHidraulica::ESTADOS_DESGASTE),
        'danado' => $analisisCollection->filter(fn ($item) => AnalisisCentralHidraulica::esEstadoDanado($item->estado)),
        'cambiado' => $analisisCollection->where('estado', AnalisisCentralHidraulica::ESTADO_CAMBIADO),
    ];

    $estadisticas = [
        'total' => $registrosPorEstado['total']->count(),
        'buen_estado' => $registrosPorEstado['buen_estado']->count(),
        'requiere_revision' => $registrosPorEstado['requiere_revision']->count(),
        'desgaste' => $registrosPorEstado['desgaste']->count(),
        'danado' => $registrosPorEstado['danado']->count(),
        'cambiado' => $registrosPorEstado['cambiado']->count(),
    ];
    $analisisPorLinea = $analisisCollection
        ->groupBy(fn ($item) => $item->linea?->nombre ?? 'Sin linea')
        ->sortKeys();

    $estadoCellClass = function (?string $estado): array {
        if (!$estado) {
            return ['cell-empty', 'fa-clipboard', 'Sin analisis'];
        }

        if (AnalisisCentralHidraulica::esEstadoDanado($estado)) {
            return ['cell-danger', 'fa-times-circle', $estado];
        }

        if (AnalisisCentralHidraulica::esEstadoDesgaste($estado)) {
            return ['cell-warning', 'fa-exclamation-triangle', $estado];
        }

        if (AnalisisCentralHidraulica::esEstadoRequiereRevision($estado)) {
            return ['cell-review', 'fa-tools', $estado];
        }

        if (AnalisisCentralHidraulica::esEstadoCambiado($estado)) {
            return ['cell-changed', 'fa-sync-alt', $estado];
        }

        return ['cell-ok', 'fa-check-circle', $estado];
    };

    $centralEstadoModalPayload = function (AnalisisCentralHidraulica $item) use ($analisisRoute, $estadoCellClass, $analisisCollection): array {
        [$cellClass] = $estadoCellClass($item->estado);
        $imagenesCentral = collect($item->evidencia_fotos ?? [])
            ->filter()
            ->map(fn ($foto) => asset('storage/' . ltrim(str_replace('\\', '/', $foto), '/')))
            ->values()
            ->all();
        $contextRegistrosCount = $analisisCollection->filter(fn ($registro) =>
            (int) ($registro->linea_id ?? 0) === (int) ($item->linea_id ?? 0)
            && (int) ($registro->configuracion_id ?? 0) === (int) ($item->configuracion_id ?? 0)
            && (($registro->lado ?? null) === ($item->lado ?? null))
        )->count();
        $historialParams = array_filter([
            'linea_id' => $item->linea_id,
            'componente_id' => $item->componente_id,
            'piso' => $item->piso,
            'lado' => $item->lado,
        ], fn ($value) => filled($value));

        return [
            'id' => $item->id,
            'linea' => $item->linea?->nombre ?? 'Central Hidraulica',
            'piso' => $item->piso_label,
            'lado' => $item->lado ? $item->lado_label : 'Piso completo',
            'componente' => $item->componente_nombre,
            'cantidad' => $item->cantidad_display,
            'estado' => $item->estado,
            'fecha' => optional($item->fecha_analisis)->format('d/m/Y') ?: 'Sin fecha',
            'orden' => $item->numero_orden ? 'Orden #' . $item->numero_orden : 'Sin orden',
            'usuario' => $item->usuario?->name ?? $item->responsable ?? 'Usuario no registrado',
            'actividad' => \Illuminate\Support\Str::limit($item->actividad ?: 'Sin actividad registrada', 110),
            'detail' => [
                'id' => $item->id,
                'linea' => $item->linea?->nombre ?? 'Central Hidraulica',
                'piso' => $item->piso_label,
                'lado' => $item->lado ? $item->lado_label : 'Piso completo',
                'componente' => $item->componente_nombre,
                'cantidad' => $item->cantidad_display,
                'fecha_analisis' => optional($item->fecha_analisis)->format('d/m/Y') ?: 'Sin fecha',
                'numero_orden' => $item->numero_orden ?: 'Sin orden',
                'estado' => $item->estado,
                'usuario_nombre' => $item->usuario?->name ?? $item->responsable ?? 'Usuario no registrado',
                'actividad' => $item->actividad ?: 'Sin actividad registrada',
                'color' => $cellClass,
                'imagenes' => $imagenesCentral,
                'created_at' => optional($item->created_at)->format('d/m/Y H:i') ?: '',
                'updated_at' => optional($item->updated_at)->format('d/m/Y H:i') ?: '',
                'show_url' => $analisisRoute('show', $item->id),
                'edit_url' => $analisisRoute('edit', $item->id),
                'registros_count' => $contextRegistrosCount,
                'historial_url' => $contextRegistrosCount > 2 ? $analisisRoute('historial', $historialParams) : null,
            ],
        ];
    };
    $centralEstadoModalItems = collect($registrosPorEstado)
        ->map(fn ($items) => collect($items)->map(fn ($item) => $centralEstadoModalPayload($item))->values()->all())
        ->all();

    $contextMatchesFilters = function (array $contexto) use ($ladoFiltro, $estadoFiltro, $estadoGrupo): bool {
        if ($ladoFiltro && ($contexto['lado'] ?? null) !== $ladoFiltro) {
            return false;
        }

        $estadoActual = $contexto['estado'] ?? null;

        if ($estadoFiltro) {
            return $estadoActual === $estadoFiltro;
        }

        if ($estadoGrupo === 'desgaste') {
            return $estadoActual && AnalisisCentralHidraulica::esEstadoDesgaste($estadoActual);
        }

        if ($estadoGrupo === 'danado') {
            return $estadoActual && AnalisisCentralHidraulica::esEstadoDanado($estadoActual);
        }

        return true;
    };
    $centralMatrices = [];

    foreach ($seguimientoCentral as $seguimiento) {
        $lineaSeguimiento = $seguimiento['linea'] ?? null;
        $matrix = [
            'linea' => $lineaSeguimiento,
            'pisos' => [],
            'componentes' => [],
        ];

        foreach (($seguimiento['pisos'] ?? []) as $piso) {
            $pisoKey = $piso['key'] ?? null;

            if (!$pisoKey || ($pisoFiltro && $pisoKey !== $pisoFiltro)) {
                continue;
            }

            $matrix['pisos'][$pisoKey] = [
                'key' => $pisoKey,
                'label' => $piso['label'] ?? $pisoKey,
            ];

            foreach (($piso['componentes'] ?? []) as $item) {
                $config = $item['configuracion'] ?? null;

                if (!$config) {
                    continue;
                }

                if ($componenteFiltro && (int) $componenteFiltro !== (int) $config->componente_id) {
                    continue;
                }

                $contextos = collect($item['contextos'] ?? [])
                    ->filter(fn ($contexto) => is_array($contexto) && $contextMatchesFilters($contexto))
                    ->map(function ($contexto) use ($estadoCellClass, $lineaSeguimiento, $config, $pisoKey) {
                        $estadoActual = $contexto['estado'] ?? null;
                        [$cellClass, $statusIcon, $statusLabel] = $estadoCellClass($estadoActual);

                        return [
                            'contexto' => $contexto,
                            'ultimo' => $contexto['ultimo'] ?? null,
                            'porcentaje' => $contexto['porcentaje'] ?? null,
                            'cell_class' => $cellClass,
                            'status_icon' => $statusIcon,
                            'status_label' => $statusLabel,
                            'registros_count' => (int) ($contexto['registros_count'] ?? 0),
                            'create_params' => array_filter([
                                'linea_id' => $lineaSeguimiento?->id,
                                'configuracion_id' => $config->id,
                                'piso' => $pisoKey,
                                'lado' => $contexto['lado'] ?? null,
                            ], fn ($value) => filled($value)),
                        ];
                    })
                    ->values();

                if ($contextos->isEmpty()) {
                    continue;
                }

                $componenteKey = (string) ($config->componente_id ?? $config->id);

                if (!isset($matrix['componentes'][$componenteKey])) {
                    $matrix['componentes'][$componenteKey] = [
                        'nombre' => $config->componente_nombre,
                        'codigo' => $config->componente?->codigo,
                        'cells' => [],
                    ];
                }

                $matrix['componentes'][$componenteKey]['cells'][$pisoKey] = [
                    'configuracion' => $config,
                    'contextos' => $contextos,
                ];
            }
        }

        if (!empty($matrix['componentes']) && !empty($matrix['pisos'])) {
            $matrix['pisos'] = array_values($matrix['pisos']);
            $matrix['componentes'] = array_values($matrix['componentes']);
            $centralMatrices[] = $matrix;
        }
    }
@endphp

<style>
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --changed-blue: #3b82f6;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
        --soft-shadow: 0 4px 6px rgba(15, 23, 42, 0.05);
    }

    .central-index-shell,
    .central-index {
        width: 100%;
        max-width: 100%;
        overflow-x: clip;
    }

    .central-index-shell *,
    .central-index * {
        box-sizing: border-box;
        min-width: 0;
    }

    .central-index-shell :is(h1, h2, h3, p, span, a, button, th, td, label),
    .central-index :is(h1, h2, h3, p, span, a, button, th, td, label) {
        overflow-wrap: anywhere;
    }

    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .lineas-title i {
        color: #3b82f6;
        font-size: 16px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .linea-item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        min-height: 44px;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }

    .linea-item i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-item:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }

    .linea-item.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .linea-item.active i {
        color: white;
    }

    .filters-divider {
        margin: 24px 0 16px 0;
        border-top: 2px solid #f1f5f9;
    }

    .filters-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
    }

    .filter-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        min-height: 44px;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }

    .filter-link:hover,
    .filter-link.active {
        background: #f8fafc;
        color: #2563eb;
        font-weight: 600;
    }

    .btn-apply {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 28px;
        background: #3b82f6;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        min-height: 44px;
        transition: all 0.2s ease;
        margin-left: auto;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
        touch-action: manipulation;
    }

    .btn-apply:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px -1px rgba(59, 130, 246, 0.3);
    }

    .btn-clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 24px;
        background: white;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        cursor: pointer;
        min-height: 44px;
        transition: all 0.2s ease;
        text-decoration: none;
        touch-action: manipulation;
    }

    .btn-clear:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #475569;
    }

    .advanced-filters-panel {
        margin-top: 20px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        display: none;
        border: 1px solid #e2e8f0;
    }

    .advanced-filters-panel.show {
        display: block;
    }

    .advanced-filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
        gap: 16px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-select,
    .filter-input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        color: #1e293b;
        background: white;
        transition: all 0.2s ease;
    }

    .filter-select:focus,
    .filter-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 83, 192, 0.1);
    }

    .stat-action-card {
        min-height: 6rem;
        min-width: 0;
        max-width: 100%;
        text-align: left;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }

    .central-index .central-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)) !important;
        gap: 0.75rem !important;
    }

    .central-index .central-stats-grid > * {
        min-height: auto;
        padding: 0.8rem !important;
        border-radius: 0.75rem !important;
    }

    .central-index .central-stats-grid h3 {
        font-size: 1.35rem !important;
        line-height: 1.1;
    }

    .central-index .central-stats-grid p {
        line-height: 1.15;
    }

    .central-index .central-stats-grid .rounded-full {
        padding: 0.45rem !important;
    }

    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 1px solid var(--medium-gray);
        border-radius: 8px;
        width: 100%;
        max-width: 100%;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }

    .central-card,
    .pasteurizadora-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 28px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
    }

    .central-card-header,
    .pasteurizadora-card-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 22px 28px;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .central-card-header > *,
    .pasteurizadora-card-header > * {
        min-width: 0;
    }

    .central-card-icon {
        width: 3rem;
        height: 3rem;
        flex: 0 0 3rem;
        overflow: hidden;
        border-radius: 999px;
    }

    .central-card-header h3,
    .pasteurizadora-card-header h3 {
        color: white;
        font-size: 1.45rem;
        font-weight: 700;
        line-height: 1.05;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .central-card-header .badge,
    .pasteurizadora-card-header .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.2);
        border: 0;
        color: white;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 12px;
    }

    .central-card .table-wrapper,
    .pasteurizadora-card .table-wrapper {
        border: none;
        border-radius: 0;
        border-top: 1px solid #e2e8f0;
        box-shadow: none;
    }

    .scroll-indicator {
        position: sticky;
        left: 0;
        z-index: 30;
        display: none;
        width: fit-content;
        margin: 10px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 4px 8px;
        pointer-events: none;
    }

    .table-wrapper:hover .scroll-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    @media (hover: none) {
        .table-wrapper .scroll-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
    }

    .compact-table {
        width: max-content;
        min-width: 100%;
        border-collapse: collapse;
    }

    .compact-table td,
    .compact-table th {
        padding: 12px !important;
        font-size: 0.82rem !important;
        min-width: 150px;
        white-space: normal !important;
    }

    .compact-table thead th {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #dbeafe;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .compact-table tbody th {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #1f2937;
        position: sticky;
        left: 0;
        z-index: 10;
    }

    .compact-table tbody td {
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }

    .central-matrix-table .sticky-left {
        left: 0;
        position: sticky;
        z-index: 25;
    }

    .central-matrix-table thead .sticky-left {
        z-index: 45;
    }

    .central-matrix-table .central-component-cell {
        min-width: 210px;
        width: 210px;
    }

    .central-matrix-table .central-piso-cell {
        min-width: 340px;
        width: 340px;
    }

    .reductor-header {
        display: grid;
        gap: 4px;
        justify-items: center;
    }

    .reductor-name {
        color: #1e40af;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .reductor-label {
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .component-header {
        display: grid;
        gap: 8px;
        justify-items: center;
        text-align: center;
    }

    .component-name {
        color: #1e40af;
        font-size: 0.92rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .central-cell-stack {
        display: grid;
        gap: 10px;
        min-height: 100%;
    }

    .central-context-entry {
        border-radius: 10px;
        min-height: 178px;
        padding: 10px;
    }

    .central-analysis-card {
        cursor: pointer;
        position: relative;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .central-analysis-card:hover {
        box-shadow: 0 14px 22px rgba(15, 23, 42, 0.12);
        transform: translateY(-1px);
    }

    .central-analysis-card.no-data {
        cursor: default;
    }

    .central-analysis-card.no-data:hover {
        box-shadow: none;
        transform: none;
    }

    .central-analysis-meta {
        border: 1px solid rgba(191, 219, 254, 0.72);
    }

    .central-analysis-activity {
        display: block;
        color: #374151;
        font-size: 0.78rem !important;
        line-height: 1.4;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: normal;
    }

    .central-cell-content {
        display: flex;
        flex-direction: column;
        gap: 9px;
        height: 100%;
    }

    .central-cell-topline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
    }

    .central-cell-actions {
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        gap: 8px;
        margin-top: auto;
    }

    .central-cell-actions .create-action {
        width: 100%;
        justify-content: center;
    }

    .central-empty-analysis {
        display: grid;
        min-height: 86px;
        place-items: center;
        gap: 8px;
        border-radius: 8px;
        color: #6b7280;
        text-align: center;
    }

    .central-empty-analysis i {
        color: #c4c9d1;
        font-size: 1.65rem;
    }

    .central-no-aplica {
        display: grid;
        min-height: 150px;
        place-items: center;
        border-radius: 10px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: 0.78rem;
        font-weight: 700;
        text-align: center;
    }

    .cell-ok { background-color: #f0f9ff; border-left: 4px solid var(--success-green) !important; }
    .cell-review { background-color: #fefce8; border-left: 4px solid var(--warning-yellow) !important; }
    .cell-warning { background-color: #fff7ed; border-left: 4px solid #f97316 !important; }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid var(--danger-red) !important; }
    .cell-changed { background-color: #eff6ff; border-left: 4px solid var(--changed-blue) !important; }
    .cell-empty { background-color: var(--light-gray); border-left: 4px solid #cbd5e1 !important; }

    .central-context-card {
        display: grid;
        gap: 8px;
        line-height: 1.35;
    }

    .central-context-card .info-line {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #374151;
        overflow-wrap: anywhere;
    }

    .central-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        max-width: 100%;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.45);
        background: rgba(255, 255, 255, 0.75);
        padding: 6px 9px;
        font-size: 12px;
        font-weight: 700;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .central-index-shell {
            padding: 1rem 0.75rem;
        }

        .filters-section {
            padding: 16px;
        }

        .filters-row {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .btn-apply,
        .btn-clear,
        .filter-link {
            width: 100%;
            justify-content: center;
            margin-left: 0;
        }

        .compact-table td,
        .compact-table th {
            min-width: 142px;
            padding: 10px !important;
            font-size: 0.76rem !important;
        }

        .compact-table tbody th {
            min-width: 126px;
        }

        .central-matrix-table .central-component-cell {
            min-width: 152px;
            width: 152px;
        }

        .central-matrix-table .central-piso-cell {
            min-width: min(74vw, 300px);
            width: min(74vw, 300px);
        }

        .central-context-entry {
            min-height: 150px;
            padding: 8px;
        }

        .central-cell-topline,
        .central-analysis-meta > div {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .central-cell-topline {
            flex-direction: column;
        }

        .central-analysis-card:hover {
            transform: none;
        }

        .central-card-header,
        .pasteurizadora-card-header {
            align-items: stretch;
            padding: 14px;
        }

        .central-card-header > div:not(.central-card-icon),
        .pasteurizadora-card-header > div:not(.central-card-icon) {
            width: 100%;
        }

        #centralDetailModal {
            align-items: flex-end;
            padding: 0.5rem;
        }

        #centralDetailModal > div {
            max-height: calc(100dvh - 1rem);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        #centralDetailModal > div > div {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #centralDetailModal .central-detail-modal-body {
            max-height: calc(100dvh - 5.75rem);
            padding: 1rem;
        }
    }

    @media (max-width: 420px) {
        .central-index-shell {
            padding-inline: 0.5rem;
        }

        .filters-section {
            padding: 14px;
        }

        .lineas-grid {
            align-items: stretch;
            flex-direction: column;
        }

        .linea-item {
            width: 100%;
        }

        .central-matrix-table .central-component-cell {
            min-width: 136px;
            width: 136px;
        }

        .central-matrix-table .central-piso-cell {
            min-width: 248px;
            width: 248px;
        }

        .compact-table td,
        .compact-table th {
            padding: 8px !important;
        }

        .central-analysis-meta,
        .central-analysis-meta span,
        .central-analysis-meta div {
            max-width: 100%;
        }
    }
</style>

<div class="central-index central-index-shell mx-auto max-w-full px-4 py-6">
    <div class="mb-6 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}"
               class="group flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-gray-600 transition-all duration-300 hover:bg-gray-200 hover:text-gray-900">
                <svg class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-800">
                <span>Analisis de Central Hidraulica</span>
            </h1>
        </div>

    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($lineas->count() > 0)
        <div class="filters-section">
            <form method="GET" action="{{ $analisisRoute('index') }}" id="filterForm">
                <div class="lineas-title">
                    <i class="fas fa-oil-can"></i>
                    CENTRAL HIDRAULICA DE PASTEURIZADORA:
                </div>

                <div class="lineas-grid">
                    @foreach($lineas as $l)
                        <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}"
                             onclick="selectLinea('{{ $l->id }}')">
                            {{ $l->nombre }}
                        </div>
                    @endforeach

                    <input type="hidden" name="linea_id" id="lineaInput" value="{{ request('linea_id', 'todas') }}">
                    <input type="hidden" name="estado_grupo" value="{{ request('estado_grupo') }}">
                </div>

                <div class="filters-divider"></div>

                <div class="filters-row">
                    <div class="filter-link {{ request()->hasAny(['piso', 'lado', 'componente_id', 'estado', 'fecha']) ? 'active' : '' }}"
                         onclick="toggleAdvancedFilters()">
                        <i class="fas fa-sliders-h"></i>
                        Filtros avanzados
                        <i id="advancedFiltersIcon" class="fas fa-chevron-down ml-1"></i>
                    </div>

                    <button type="submit" class="btn-apply">
                        <i class="fas fa-search"></i>
                        Aplicar filtros
                    </button>

                    <a href="{{ $analisisRoute('index', ['linea_id' => 'todas']) }}" class="btn-clear">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </div>

                <div id="advancedFiltersPanel"
                     class="advanced-filters-panel {{ request()->hasAny(['piso', 'lado', 'componente_id', 'estado', 'fecha']) ? 'show' : '' }}">
                    <div class="advanced-filters-grid">
                        <div class="filter-group">
                            <label><i class="fas fa-layer-group mr-1"></i> Piso</label>
                            <select name="piso" class="filter-select">
                                <option value="">Todos los pisos</option>
                                @foreach($pisosCentral as $piso => $label)
                                    <option value="{{ $piso }}" {{ request('piso') === $piso ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-arrows-left-right mr-1"></i> Lado</label>
                            <select name="lado" class="filter-select">
                                <option value="">Todos los lados</option>
                                @foreach($ladosCentral as $lado => $label)
                                    <option value="{{ $lado }}" {{ request('lado') === $lado ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-oil-can mr-1"></i> Componente / revision</label>
                            <select name="componente_id" class="filter-select">
                                <option value="">Todos los componentes y revisiones</option>
                                @foreach($componentesCentral as $componente)
                                    <option value="{{ $componente->id }}" {{ request('componente_id') == $componente->id ? 'selected' : '' }}>{{ $componente->nombre_display }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-clipboard-check mr-1"></i> Estado</label>
                            <select name="estado" class="filter-select">
                                <option value="">Todos los estados</option>
                                @foreach($estadoOpciones as $estado => $label)
                                    <option value="{{ $estado }}" {{ request('estado') === $estado ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="far fa-calendar-alt mr-1"></i> Mes</label>
                            <input type="month" name="fecha" value="{{ request('fecha') }}" class="filter-input">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="central-stats-grid grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-gray-600 hover:shadow-lg transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total analisis</p>
                    <h3 class="text-2xl font-bold text-gray-700 mt-1">{{ $estadisticas['total'] }}</h3>
                </div>
                <div class="bg-gray-100 text-gray-600 p-2 rounded-full"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

        <button type="button"
                onclick="openCentralEstadoModal('buen_estado', 'Buen estado')"
                class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-emerald-600 hover:shadow-lg hover:bg-emerald-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Buen estado</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ $estadisticas['buen_estado'] }}</h3>
                    <p class="text-xs text-emerald-500 group-hover:text-emerald-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-emerald-100 text-emerald-600 p-2 rounded-full group-hover:bg-emerald-200 transition"><i class="fas fa-check-circle"></i></div>
            </div>
        </button>

        <button type="button"
                onclick="openCentralEstadoModal('requiere_revision', 'Requiere revision')"
                class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-yellow-500 hover:shadow-lg hover:bg-yellow-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wide">Requiere revision</p>
                    <h3 class="text-2xl font-bold text-yellow-600 mt-1">{{ $estadisticas['requiere_revision'] }}</h3>
                    <p class="text-xs text-yellow-500 group-hover:text-yellow-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-yellow-100 text-yellow-600 p-2 rounded-full group-hover:bg-yellow-200 transition"><i class="fas fa-tools"></i></div>
            </div>
        </button>

        <button type="button"
                onclick="openCentralEstadoModal('desgaste', 'Severo / Moderado')"
                class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-orange-500 hover:shadow-lg hover:bg-orange-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Severo / Moderado</p>
                    <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $estadisticas['desgaste'] }}</h3>
                    <p class="text-xs text-orange-500 group-hover:text-orange-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-orange-100 text-orange-600 p-2 rounded-full group-hover:bg-orange-200 transition"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </button>

        <button type="button"
                onclick="openCentralEstadoModal('danado', 'Danados')"
                class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-red-600 hover:shadow-lg hover:bg-red-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">Danados</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $estadisticas['danado'] }}</h3>
                    <p class="text-xs text-red-500 group-hover:text-red-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-red-100 text-red-600 p-2 rounded-full group-hover:bg-red-200 transition"><i class="fas fa-times-circle"></i></div>
            </div>
        </button>

        <button type="button"
                onclick="openCentralEstadoModal('cambiado', 'Cambiados')"
                class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-sky-600 hover:shadow-lg hover:bg-sky-50 transition-all text-left w-full cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-sky-600 uppercase tracking-wide">Cambiados</p>
                    <h3 class="text-2xl font-bold text-sky-600 mt-1">{{ $estadisticas['cambiado'] }}</h3>
                    <p class="text-xs text-sky-500 group-hover:text-sky-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                </div>
                <div class="bg-sky-100 text-sky-600 p-2 rounded-full group-hover:bg-sky-200 transition"><i class="fas fa-sync-alt"></i></div>
            </div>
        </button>
    </div>

    <section class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle mt-0.5 text-xl text-blue-500"></i>
     
        </div>
    </section>

    <div class="space-y-6">
        @forelse($centralMatrices as $matrix)
            @php
                $lineaSeguimiento = $matrix['linea'];
                $pisosMatrix = $matrix['pisos'];
                $componentesMatrix = $matrix['componentes'];
            @endphp

            <div class="central-card pasteurizadora-card">
                <div class="central-card-header pasteurizadora-card-header">
                    <div class="central-card-icon">
                        <img src="{{ asset('images/icono_pas.png') }}" alt="Icono" class="h-full w-full object-contain">
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3>Central Hidraulica {{ $lineaSeguimiento?->nombre }}</h3>
                    </div>

                </div>

                <div class="table-wrapper">
                    <div class="scroll-indicator">
                        <i class="fas fa-arrows-alt-h mr-1"></i>
                        Desplazate para ver pisos y componentes
                    </div>

                    <table class="w-full compact-table border-collapse central-matrix-table">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="sticky-left cell-header central-component-cell text-center text-sm font-bold text-blue-900">
                                    <div class="reductor-header">
                                        <div class="reductor-name">Componente</div>
                                        <div class="reductor-label">Central Hidraulica</div>
                                    </div>
                                </th>
                                @foreach($pisosMatrix as $pisoMatrix)
                                    <th class="cell-header central-piso-cell text-center text-sm font-bold text-blue-900">
                                        <div class="component-header">
                                            <i class="fas fa-layer-group text-2xl text-blue-600"></i>
                                            <div class="component-name">{{ $pisoMatrix['label'] }}</div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($componentesMatrix as $componenteRow)
                                <tr>
                                    <th class="sticky-left cell-header central-component-cell align-top text-left text-sm font-bold text-blue-900">
                                        <div class="flex items-start gap-3">
                                            <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                                <i class="fas fa-oil-can"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="font-bold text-gray-900">{{ $componenteRow['nombre'] }}</div>
                                                <div class="mt-1 text-xs font-semibold text-gray-500">SKU</div>
                                            </div>
                                        </div>
                                    </th>

                                    @foreach($pisosMatrix as $pisoMatrix)
                                        @php
                                            $cell = $componenteRow['cells'][$pisoMatrix['key']] ?? null;
                                        @endphp
                                        @if(!$cell)
                                            <td class="cell-empty align-middle text-center">
                                                <div class="central-no-aplica">
                                                    No aplica en este piso
                                                </div>
                                            </td>
                                        @else
                                            @php
                                                $config = $cell['configuracion'];
                                            @endphp
                                            <td class="align-top">
                                                <div class="central-cell-stack">
                                                    @foreach($cell['contextos'] as $contextData)
                                                        @php
                                                            $contexto = $contextData['contexto'];
                                                            $ultimo = $contextData['ultimo'];
                                                            $estadoBadge = $ultimo?->estado_badge ?? [
                                                                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                                'icon' => $contextData['status_icon'],
                                                            ];
                                                            $imagenesCentral = $ultimo
                                                                ? collect($ultimo->evidencia_fotos ?? [])
                                                                    ->filter()
                                                                    ->map(fn ($foto) => asset('storage/' . ltrim(str_replace('\\', '/', $foto), '/')))
                                                                    ->values()
                                                                    ->all()
                                                                : [];
                                                            $contextDetailPayload = $ultimo ? [
                                                                'id' => $ultimo->id,
                                                                'linea' => $ultimo->linea?->nombre ?? $lineaSeguimiento?->nombre ?? 'Central Hidraulica',
                                                                'piso' => $ultimo->piso_label,
                                                                'lado' => $ultimo->lado ? $ultimo->lado_label : 'Piso completo',
                                                                'componente' => $ultimo->componente_nombre,
                                                                'cantidad' => $ultimo->cantidad_display,
                                                                'fecha_analisis' => optional($ultimo->fecha_analisis)->format('d/m/Y') ?: 'Sin fecha',
                                                                'numero_orden' => $ultimo->numero_orden ?: 'Sin orden',
                                                                'estado' => $ultimo->estado,
                                                                'usuario_nombre' => $ultimo->usuario?->name ?? $ultimo->responsable ?? 'Usuario no registrado',
                                                                'actividad' => $ultimo->actividad ?: 'Sin actividad registrada',
                                                                'color' => $contextData['cell_class'],
                                                                'imagenes' => $imagenesCentral,
                                                                'created_at' => optional($ultimo->created_at)->format('d/m/Y H:i') ?: '',
                                                                'updated_at' => optional($ultimo->updated_at)->format('d/m/Y H:i') ?: '',
                                                                'show_url' => $analisisRoute('show', $ultimo->id),
                                                                'edit_url' => $analisisRoute('edit', $ultimo->id),
                                                                'registros_count' => (int) ($contextData['registros_count'] ?? 0),
                                                                'historial_url' => ((int) ($contextData['registros_count'] ?? 0) > 2) ? $analisisRoute('historial', array_filter([
                                                                    'linea_id' => $ultimo->linea_id,
                                                                    'componente_id' => $ultimo->componente_id,
                                                                    'piso' => $ultimo->piso,
                                                                    'lado' => $ultimo->lado,
                                                                ], fn ($value) => filled($value))) : null,
                                                                'create_url' => $analisisRoute('create-quick', $contextData['create_params']),
                                                            ] : [];
                                                        @endphp

                                                        <div class="central-context-entry central-analysis-card {{ $ultimo ? '' : 'no-data' }} {{ $contextData['cell_class'] }}"
                                                             @if($ultimo)
                                                                 role="button"
                                                                 tabindex="0"
                                                                 title="Ver detalles del analisis"
                                                                 onclick="openCentralDetail({{ json_encode($contextDetailPayload) }})"
                                                                 onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openCentralDetail({{ json_encode($contextDetailPayload) }}); }"
                                                             @endif>
                                                            <div class="central-cell-content">
                                                                <div class="central-cell-topline">
                                                                    <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-bold text-slate-700">
                                                                        <i class="fas fa-location-dot text-blue-600"></i>
                                                                        {{ $contexto['lado_label'] ?? 'Piso completo' }}
                                                                    </span>
                                                                </div>

                                                                @if($ultimo)
                                                                    <div class="central-analysis-meta rounded bg-blue-50 p-2">
                                                                        <div class="flex items-center gap-1">
                                                                            <i class="fas fa-calendar-alt text-blue-600"></i>
                                                                            <span class="text-xs font-bold text-blue-800">Fecha:</span>
                                                                            <span class="rounded bg-white px-2 py-0.5 text-xs font-semibold">
                                                                                {{ optional($ultimo->fecha_analisis)->format('d/m/Y') ?: 'NO ESPECIFICADA' }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="flex items-center gap-1">
                                                                            <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                                                            <span class="text-xs font-bold text-gray-800">{{ $ultimo->numero_orden ? 'Orden #' . $ultimo->numero_orden : 'Sin orden' }}</span>
                                                                        </div>
                                                                    </div>

                                                                    <div>
                                                                        <span class="inline-flex items-center rounded border px-2 py-1 text-xs font-medium {{ $estadoBadge['class'] }}">
                                                                            <i class="fas {{ $estadoBadge['icon'] }} mr-1"></i>
                                                                            {{ \Illuminate\Support\Str::limit($ultimo->estado, 22) }}
                                                                        </span>
                                                                        <span class="mt-1 inline-flex w-full items-center rounded border border-slate-200 bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                                                            <i class="fas fa-user-check mr-1"></i>
                                                                            Realizado por: {{ $ultimo->usuario?->name ?? $ultimo->responsable ?? 'Usuario no registrado' }}
                                                                        </span>
                                                                    </div>

                                                                    @if($ultimo->actividad)
                                                                        <p class="central-analysis-activity">
                                                                            {{ \Illuminate\Support\Str::limit($ultimo->actividad, 80) }}
                                                                        </p>
                                                                    @endif
                                                                @else
                                                                    <div class="central-empty-analysis">
                                                                        <i class="fas fa-clipboard"></i>
                                                                        <span class="text-xs font-semibold">Sin analisis</span>
                                                                    </div>
                                                                @endif

                                                                <div class="central-cell-actions">
                                                                    <a href="{{ $analisisRoute('create-quick', $contextData['create_params']) }}"
                                                                       class="create-action create-action--compact {{ $ultimo ? 'create-action--success' : '' }}"
                                                                       onclick="event.stopPropagation();">
                                                                        <i class="fas fa-plus"></i>
                                                                        {{ $ultimo ? 'Nuevo Registro' : 'Nuevo' }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-10 text-center text-gray-500 shadow-sm">
                No hay contextos de central hidraulica con los filtros actuales.
            </div>
        @endforelse
    </div>

</div>

<div id="centralEstadoModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     onclick="if (event.target === this) closeCentralEstadoModal()">
    <div class="max-h-[85vh] w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-xl">
        <div id="centralEstadoModalHeader" class="border-b px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <h3 id="centralEstadoModalTitle" class="break-words text-xl font-bold text-gray-900">
                    Detalle de registros
                </h3>
                <button type="button"
                        onclick="closeCentralEstadoModal()"
                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-white/40 hover:text-gray-900"
                        aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div id="centralEstadoModalContent" class="max-h-[calc(85vh-76px)] overflow-auto bg-gray-50 p-6">
            <div class="py-8 text-center text-gray-500">
                Cargando registros...
            </div>
        </div>
    </div>
</div>

<div id="centralDetailModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     onclick="if (event.target === this) closeCentralDetailModal()">
    <div class="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-xl">
        <div class="border-b border-gray-100 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                        <i class="fas fa-oil-can"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="break-words text-base font-bold text-gray-900">Detalle del Analisis</h3>
                        <p id="central-detail-subtitle" class="mt-0.5 break-words text-sm text-gray-500"></p>
                    </div>
                </div>
                <button type="button"
                        onclick="closeCentralDetailModal()"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="central-detail-modal-body max-h-[calc(90vh-82px)] overflow-auto bg-gray-50 p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border-l-4 border-gray-700 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-100 p-3">
                            <i class="fas fa-industry text-xl text-gray-700"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Central</p>
                            <p id="central-detail-linea" class="mt-1 break-words text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-gray-700 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-100 p-3">
                            <i class="fas fa-oil-can text-xl text-gray-700"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Componente</p>
                            <p id="central-detail-componente" class="mt-1 break-words text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-gray-700 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-100 p-3">
                            <i class="fas fa-layer-group text-xl text-gray-700"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Piso</p>
                            <p id="central-detail-piso" class="mt-1 break-words text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-gray-700 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-100 p-3">
                            <i class="fas fa-location-dot text-xl text-gray-700"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lado</p>
                            <p id="central-detail-lado" class="mt-1 break-words text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-gray-700 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-100 p-3">
                            <i class="far fa-calendar-alt text-xl text-gray-700"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fecha</p>
                            <p id="central-detail-fecha" class="mt-1 break-words text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-gray-700 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-100 p-3">
                            <i class="fas fa-hashtag text-xl text-gray-700"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Orden</p>
                            <p id="central-detail-orden" class="mt-1 break-words text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-blue-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-lg bg-blue-100 p-2">
                            <i class="fas fa-user-check text-blue-600"></i>
                        </div>
                        <h4 class="border-b-2 border-blue-200 text-sm font-semibold uppercase tracking-wider text-gray-700">Responsable</h4>
                    </div>
                    <div id="central-detail-usuario" class="rounded-lg bg-blue-50 px-4 py-3 text-center text-sm font-semibold text-blue-700"></div>
                </div>

                <div class="rounded-lg border border-green-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-lg bg-green-100 p-2">
                            <i class="fas fa-clipboard-check text-green-600"></i>
                        </div>
                        <h4 class="border-b-2 border-green-200 text-sm font-semibold uppercase tracking-wider text-gray-700">Estado</h4>
                    </div>
                    <div id="central-detail-estado" class="rounded-lg px-4 py-3 text-center text-sm font-bold"></div>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-lg bg-gray-200 p-2">
                        <i class="fas fa-sticky-note text-gray-700"></i>
                    </div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-700">Actividad</h4>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p id="central-detail-actividad" class="whitespace-pre-line text-sm leading-relaxed text-gray-700"></p>
                </div>
            </div>

            <div id="central-detail-images-section" class="mt-4 hidden rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-700">Evidencias</h4>
                <div id="central-detail-images" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
            </div>

            <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end">
                <a id="central-detail-edit-btn" href="#" class="create-action create-action--secondary">
                    <i class="fas fa-pen"></i>
                    Editar
                </a>
                <a id="central-detail-history-btn" href="#" class="create-action create-action--secondary">
                    <i class="fas fa-history"></i>
                    Historial
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const CENTRAL_ESTADO_MODAL_ITEMS = @json($centralEstadoModalItems);
let currentCentralEstadoModalItems = [];

function centralEscapeHtml(value) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };

    return String(value ?? '').replace(/[&<>"']/g, (char) => map[char]);
}

function openCentralEstadoModal(tipo, titulo) {
    const modal = document.getElementById('centralEstadoModal');
    const header = document.getElementById('centralEstadoModalHeader');
    const title = document.getElementById('centralEstadoModalTitle');
    const content = document.getElementById('centralEstadoModalContent');

    if (!modal || !header || !title || !content) {
        return;
    }

    const configs = {
        buen_estado: {
            header: 'bg-emerald-100',
            title: 'text-emerald-900',
            icon: 'fa-check-circle',
            badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            accent: 'border-emerald-500',
        },
        requiere_revision: {
            header: 'bg-yellow-100',
            title: 'text-yellow-900',
            icon: 'fa-screwdriver-wrench',
            badge: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            accent: 'border-yellow-500',
        },
        desgaste: {
            header: 'bg-orange-100',
            title: 'text-orange-900',
            icon: 'fa-triangle-exclamation',
            badge: 'bg-orange-100 text-orange-800 border-orange-200',
            accent: 'border-orange-500',
        },
        danado: {
            header: 'bg-red-100',
            title: 'text-red-900',
            icon: 'fa-circle-exclamation',
            badge: 'bg-red-100 text-red-800 border-red-200',
            accent: 'border-red-500',
        },
        cambiado: {
            header: 'bg-sky-100',
            title: 'text-sky-900',
            icon: 'fa-arrows-rotate',
            badge: 'bg-sky-100 text-sky-800 border-sky-200',
            accent: 'border-sky-500',
        },
    };
    const config = configs[tipo] || {
        header: 'bg-slate-100',
        title: 'text-slate-900',
        icon: 'fa-chart-line',
        badge: 'bg-slate-100 text-slate-800 border-slate-200',
        accent: 'border-slate-500',
    };

    currentCentralEstadoModalItems = Array.isArray(CENTRAL_ESTADO_MODAL_ITEMS[tipo])
        ? CENTRAL_ESTADO_MODAL_ITEMS[tipo]
        : [];

    header.className = `border-b px-6 py-4 ${config.header}`;
    title.className = `break-words text-xl font-bold ${config.title}`;
    title.innerHTML = `<i class="fas ${config.icon} mr-2"></i>${centralEscapeHtml(titulo)} (${currentCentralEstadoModalItems.length})`;

    if (currentCentralEstadoModalItems.length === 0) {
        content.innerHTML = `
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <p class="font-semibold text-slate-700">No hay registros en esta categoria.</p>
            </div>
        `;
    } else {
        const grouped = new Map();

        currentCentralEstadoModalItems.forEach((item) => {
            const key = item.linea || 'Central Hidraulica';

            if (!grouped.has(key)) {
                grouped.set(key, []);
            }

            grouped.get(key).push(item);
        });

        let html = '<div class="space-y-6">';

        grouped.forEach((items, linea) => {
            html += `
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Central Hidraulica</p>
                            <h4 class="text-lg font-bold text-slate-900">${centralEscapeHtml(linea)}</h4>
                        </div>
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                            ${items.length} registro${items.length === 1 ? '' : 's'}
                        </span>
                    </div>
                    <div class="space-y-3 bg-slate-50 p-4">
            `;

            items.forEach((item) => {
                html += `
                    <button type="button"
                            data-central-estado-id="${Number(item.id)}"
                            class="w-full rounded-xl border border-slate-200 border-l-4 ${config.accent} bg-white p-4 text-left shadow-sm transition hover:border-slate-300 hover:shadow-md">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 space-y-3">
                                <div class="flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                    <span class="rounded-full bg-slate-100 px-3 py-1">${centralEscapeHtml(item.piso || 'Sin piso')}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1">${centralEscapeHtml(item.lado || 'Piso completo')}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1">${centralEscapeHtml(item.fecha || 'Sin fecha')}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1">${centralEscapeHtml(item.orden || 'Sin orden')}</span>
                                </div>
                                <div>
                                    <p class="break-words text-base font-bold text-slate-900">${centralEscapeHtml(item.componente || 'Sin componente')}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">${centralEscapeHtml(item.cantidad || 'Sin cantidad registrada')}</p>
                                </div>
                                <p class="break-words text-sm text-slate-600">${centralEscapeHtml(item.actividad || 'Sin actividad registrada')}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold ${config.badge}">
                                    <i class="fas ${config.icon}"></i>
                                    ${centralEscapeHtml(item.estado || 'Sin estado')}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white">
                                    <i class="fas fa-eye"></i>
                                    Ver detalle
                                </span>
                            </div>
                        </div>
                    </button>
                `;
            });

            html += `
                    </div>
                </section>
            `;
        });

        html += '</div>';
        content.innerHTML = html;
        content.querySelectorAll('[data-central-estado-id]').forEach((button) => {
            button.addEventListener('click', () => openCentralEstadoDetail(button.dataset.centralEstadoId));
        });
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeCentralEstadoModal() {
    const modal = document.getElementById('centralEstadoModal');

    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    currentCentralEstadoModalItems = [];
    document.body.style.overflow = '';
}

function openCentralEstadoDetail(id) {
    const item = currentCentralEstadoModalItems.find((candidate) => Number(candidate.id) === Number(id));

    if (!item || !item.detail) {
        return;
    }

    closeCentralEstadoModal();
    openCentralDetail(item.detail);
}

function selectLinea(lineaId) {
    document.getElementById('lineaInput').value = lineaId;
    document.getElementById('filterForm').submit();
}

function toggleAdvancedFilters() {
    const panel = document.getElementById('advancedFiltersPanel');
    const icon = document.getElementById('advancedFiltersIcon');

    panel.classList.toggle('show');
    icon.style.transform = panel.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
}

function centralSetText(id, value) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value || '';
    }
}

function centralStatusClasses(color) {
    if (color === 'cell-ok') {
        return 'bg-green-800 text-white';
    }

    if (color === 'cell-review') {
        return 'bg-yellow-700 text-white';
    }

    if (color === 'cell-warning') {
        return 'bg-orange-700 text-white';
    }

    if (color === 'cell-danger') {
        return 'bg-red-800 text-white';
    }

    if (color === 'cell-changed') {
        return 'bg-blue-800 text-white';
    }

    return 'bg-gray-800 text-white';
}

function centralImageDownloadName(src, index) {
    let path = String(src || '');

    try {
        path = new URL(path, window.location.origin).pathname;
    } catch (error) {
        path = path.split('?')[0].split('#')[0];
    }

    const rawName = decodeURIComponent(path.split('/').filter(Boolean).pop() || '');
    const safeName = rawName.replace(/[^A-Za-z0-9._-]/g, '_');

    return safeName || `central-hidraulica-evidencia-${index + 1}.jpg`;
}

function openCentralDetail(analysisData) {
    centralSetText('central-detail-subtitle', `${analysisData.piso || ''} | ${analysisData.lado || ''}`);
    centralSetText('central-detail-linea', analysisData.linea);
    centralSetText('central-detail-componente', analysisData.componente);
    centralSetText('central-detail-piso', analysisData.piso);
    centralSetText('central-detail-lado', analysisData.lado);
    centralSetText('central-detail-fecha', analysisData.fecha_analisis);
    centralSetText('central-detail-orden', analysisData.numero_orden);
    centralSetText('central-detail-usuario', `Realizado por: ${analysisData.usuario_nombre || 'Usuario no registrado'}`);
    centralSetText('central-detail-estado', analysisData.estado || 'Sin estado');
    centralSetText('central-detail-actividad', analysisData.actividad || 'Sin actividad registrada');

    const estado = document.getElementById('central-detail-estado');
    if (estado) {
        estado.className = `rounded-lg px-4 py-3 text-center text-sm font-bold ${centralStatusClasses(analysisData.color)}`;
    }

    const links = {
        'central-detail-edit-btn': analysisData.edit_url,
        'central-detail-history-btn': Number(analysisData.registros_count || 0) > 2 ? analysisData.historial_url : null,
    };

    Object.entries(links).forEach(([id, href]) => {
        const link = document.getElementById(id);

        if (!link) {
            return;
        }

        if (href) {
            link.href = href;
            link.classList.remove('hidden');
        } else {
            link.classList.add('hidden');
        }
    });

    const imagesSection = document.getElementById('central-detail-images-section');
    const imagesGrid = document.getElementById('central-detail-images');
    const images = Array.isArray(analysisData.imagenes) ? analysisData.imagenes.filter(Boolean) : [];

    if (imagesSection && imagesGrid) {
        imagesGrid.innerHTML = '';

        if (images.length > 0) {
            images.forEach((src, index) => {
                const card = document.createElement('div');
                card.className = 'overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm';

                const imageLink = document.createElement('a');
                imageLink.href = src;
                imageLink.target = '_blank';
                imageLink.rel = 'noopener';
                imageLink.className = 'block';

                const img = document.createElement('img');
                img.src = src;
                img.alt = `Evidencia ${index + 1}`;
                img.className = 'h-28 w-full object-cover';

                const downloadLink = document.createElement('a');
                downloadLink.href = src;
                downloadLink.download = centralImageDownloadName(src, index);
                downloadLink.className = 'flex items-center justify-center gap-2 border-t border-gray-200 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-50 hover:text-blue-800';
                downloadLink.innerHTML = '<i class="fas fa-download"></i><span>Descargar</span>';

                imageLink.appendChild(img);
                card.appendChild(imageLink);
                card.appendChild(downloadLink);
                imagesGrid.appendChild(card);
            });

            imagesSection.classList.remove('hidden');
        } else {
            imagesSection.classList.add('hidden');
        }
    }

    const modal = document.getElementById('centralDetailModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeCentralDetailModal() {
    const modal = document.getElementById('centralDetailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const estadoModal = document.getElementById('centralEstadoModal');
        const detailModal = document.getElementById('centralDetailModal');

        if (estadoModal && !estadoModal.classList.contains('hidden')) {
            closeCentralEstadoModal();
            return;
        }

        if (detailModal && !detailModal.classList.contains('hidden')) {
            closeCentralDetailModal();
        }
    }
});
</script>
@endsection
