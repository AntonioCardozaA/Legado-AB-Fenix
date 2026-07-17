@php
    $monitorAlertas = collect($monitorAlertas ?? []);
    $catarinas = $catarinas ?? [];
    $paneles = $paneles ?? [];
    $baseWidth = $baseWidth ?? ($svgWidth ?? 1468);
    $baseHeight = $baseHeight ?? ($svgHeight ?? 382);
    $contentTransform = $contentTransform ?? null;

    $normalizarReductorMonitor = function ($valor) {
        $valor = trim((string) ($valor ?? ''));

        if ($valor === '') {
            return null;
        }

        $valorUpper = strtoupper($valor);

        if ($valorUpper === 'LOCA' || str_contains($valorUpper, 'LOCA')) {
            return 'Reductor Loca';
        }

        if (str_contains($valorUpper, 'PRINCIPAL')) {
            return 'Reductor Principal';
        }

        if (preg_match('/(?:REDUCTOR|RED)\s*0*([0-9]+)/i', $valor, $matches)) {
            return 'Reductor ' . (int) $matches[1];
        }

        if (preg_match('/^0*([0-9]+)$/', $valor, $matches)) {
            return 'Reductor ' . (int) $matches[1];
        }

        return $valor;
    };

    $posicionesReductores = [];

    foreach ($catarinas as $index => $catarina) {
        $key = $normalizarReductorMonitor($catarina['label'] ?? null);

        if (!$key) {
            continue;
        }

        $posicionesReductores[$key] = [
            'x' => (float) ($catarina['x'] ?? 0),
            'y' => (float) ($catarina['y'] ?? 0),
            'r' => (float) ($catarina['r'] ?? 18),
            'index' => $index,
        ];
    }

    foreach ($paneles as $index => $panel) {
        foreach (['label', 'bottomLabel'] as $labelKey) {
            $key = $normalizarReductorMonitor($panel[$labelKey] ?? null);

            if (!$key || isset($posicionesReductores[$key])) {
                continue;
            }

            $posicionesReductores[$key] = [
                'x' => (float) (($panel['x'] ?? 0) + (($panel['w'] ?? 0) / 2)),
                'y' => (float) (($panel['y'] ?? 0) + (($panel['h'] ?? 0) / 2)),
                'r' => 22,
                'index' => $index,
            ];
        }
    }

    $severityRank = [
        'green' => 0,
        'yellow' => 1,
        'orange' => 2,
        'red' => 3,
    ];

    $severityLabel = [
        'green' => 'Sin anomalias',
        'yellow' => 'Advertencia',
        'orange' => 'Atencion Inmediata',
        'red' => 'Critico',
    ];

    $formatearLadoMonitor = function ($lado) {
        $ladoNormalizado = strtoupper(trim((string) ($lado ?? '')));

        if (str_contains($ladoNormalizado, 'VAPOR')) {
            return 'Lado Vapor';
        }

        if (str_contains($ladoNormalizado, 'PASILLO')) {
            return 'Lado Pasillo';
        }

        return $ladoNormalizado;
    };

    $gruposReductores = [];
    $fallbackIndex = 0;

    foreach ($monitorAlertas as $alerta) {
        $reductorNombre = data_get($alerta, 'reductor', '');
        $reductorKey = $normalizarReductorMonitor($reductorNombre);

        if (!$reductorKey) {
            continue;
        }

        $severity = data_get($alerta, 'severity', 'green');
        $severity = array_key_exists($severity, $severityRank) ? $severity : 'green';
        $rank = $severityRank[$severity];

        if (!isset($gruposReductores[$reductorKey])) {
            if (isset($posicionesReductores[$reductorKey])) {
                $posicion = $posicionesReductores[$reductorKey];
            } else {
                $posicion = [
                    'x' => max(80, $baseWidth - 170),
                    'y' => 54 + ($fallbackIndex * 42),
                    'r' => 18,
                    'index' => 1000 + $fallbackIndex,
                    'fallback' => true,
                ];
                $fallbackIndex++;
            }

            $gruposReductores[$reductorKey] = [
                'reductor' => $reductorKey,
                'posicion' => $posicion,
                'items' => [],
                'afectados' => [],
                'severity' => 'green',
                'rank' => 0,
            ];
        }

        $gruposReductores[$reductorKey]['items'][] = $alerta;

        if ($rank > 0) {
            $gruposReductores[$reductorKey]['afectados'][] = $alerta;
        }

        if ($rank > $gruposReductores[$reductorKey]['rank']) {
            $gruposReductores[$reductorKey]['rank'] = $rank;
            $gruposReductores[$reductorKey]['severity'] = $severity;
        }
    }

    uasort($gruposReductores, function ($a, $b) {
        $ax = data_get($a, 'posicion.x', 0);
        $bx = data_get($b, 'posicion.x', 0);

        if ($ax === $bx) {
            return data_get($a, 'posicion.y', 0) <=> data_get($b, 'posicion.y', 0);
        }

        return $ax <=> $bx;
    });
@endphp

@if (!empty($gruposReductores))
    <g class="monitor-fallas-overlay" @if($contentTransform) transform="{{ $contentTransform }}" @endif aria-label="Monitoreo de fallas">
        @foreach ($gruposReductores as $grupoIndex => $grupo)
            @php
                $posicion = $grupo['posicion'];
                $severity = $grupo['severity'];
                $rank = $grupo['rank'];
                $afectados = collect($grupo['afectados'])
                    ->sortByDesc(fn ($item) => $severityRank[data_get($item, 'severity', 'green')] ?? 0)
                    ->values();
                $todos = collect($grupo['items']);
                $primerItem = $afectados->first() ?? $todos->first();
                $componentesTooltip = $afectados->isNotEmpty()
                    ? $afectados
                        ->map(function ($item) use ($formatearLadoMonitor) {
                            $componente = data_get($item, 'componente');
                            $lado = $formatearLadoMonitor(data_get($item, 'lado'));

                            if (!$componente) {
                                return null;
                            }

                            return $lado ? "{$componente} ({$lado})" : $componente;
                        })
                        ->filter()
                        ->unique()
                        ->implode(', ')
                    : 'Sin componentes con anomalia';
                $ladosTooltip = $afectados
                    ->map(fn ($item) => $formatearLadoMonitor(data_get($item, 'lado')))
                    ->filter()
                    ->unique()
                    ->implode(', ');
                $fechaTooltip = data_get($primerItem, 'fecha', 'Sin fecha');
                $observacionesTooltip = data_get($primerItem, 'observaciones', 'Sin observaciones');
                $nivelTooltip = $severityLabel[$severity] ?? $severity;
                $ringRadius = max(24, ($posicion['r'] ?? 18) + 14);
            @endphp

            <g
                class="monitor-reductor monitor-severity-{{ $severity }} {{ $rank > 0 ? 'has-alerts' : 'is-healthy' }}"
                data-monitor-tooltip
                data-monitor-kind="reductor"
                data-monitor-reductor="{{ e($grupo['reductor']) }}"
                data-monitor-componente="{{ e($componentesTooltip) }}"
                data-monitor-lado="{{ e($ladosTooltip) }}"
                data-monitor-dano="{{ e($nivelTooltip) }}"
                data-monitor-fecha="{{ e($fechaTooltip) }}"
                data-monitor-observaciones="{{ e($observacionesTooltip) }}"
            >
                @if ($rank > 0)
                    <circle class="monitor-reductor-pulse" cx="{{ $posicion['x'] }}" cy="{{ $posicion['y'] }}" r="{{ $ringRadius + 4 }}" />
                @endif
                <circle class="monitor-reductor-halo" cx="{{ $posicion['x'] }}" cy="{{ $posicion['y'] }}" r="{{ $ringRadius }}" />
                <circle class="monitor-reductor-core" cx="{{ $posicion['x'] }}" cy="{{ $posicion['y'] }}" r="{{ max(10, $posicion['r'] * 0.52) }}" />

                @if ($rank > 0)
                    <text class="monitor-reductor-count" x="{{ $posicion['x'] }}" y="{{ $posicion['y'] + 4 }}" text-anchor="middle">{{ $afectados->count() }}</text>
                @else
                    <path
                        class="monitor-reductor-ok"
                        d="M {{ $posicion['x'] - 6 }} {{ $posicion['y'] }} L {{ $posicion['x'] - 1 }} {{ $posicion['y'] + 5 }} L {{ $posicion['x'] + 8 }} {{ $posicion['y'] - 6 }}"
                    />
                @endif
            </g>

            @if ($afectados->isNotEmpty())
                @foreach ($afectados as $itemIndex => $item)
                    @php
                        $itemSeverity = data_get($item, 'severity', 'yellow');
                        $itemRank = $severityRank[$itemSeverity] ?? 1;
                        $stackCount = max(1, $afectados->count());
                        $lane = $loop->parent->index % 4;
                        $labelWidth = 128;
                        $labelHeight = 24;
                        $stackX = $posicion['x'] - ($labelWidth / 2);
                        $stackYOptions = [
                            $posicion['y'] + $ringRadius + 16,
                            $posicion['y'] - $ringRadius - (($stackCount * ($labelHeight + 4)) + 16),
                            $posicion['y'] + $ringRadius + 48,
                            $posicion['y'] - $ringRadius - (($stackCount * ($labelHeight + 4)) + 48),
                        ];
                        $stackY = $stackYOptions[$lane] ?? $stackYOptions[0];
                        $labelX = min(max(8, $stackX), max(8, $baseWidth - $labelWidth - 8));
                        $labelY = min(max(12, $stackY + ($itemIndex * ($labelHeight + 4))), max(12, $baseHeight - $labelHeight - 8));
                        $iconX = $labelX + 13;
                        $iconY = $labelY + 12;
                        $connectorX = $labelX + ($labelWidth / 2);
                        $connectorY = $labelY + ($labelHeight / 2);
                        $nombreComponente = data_get($item, 'componente', 'Componente');
                        $nombreCorto = \Illuminate\Support\Str::limit($nombreComponente, 18);
                        $nivelItem = $severityLabel[$itemSeverity] ?? data_get($item, 'nivel', 'Advertencia');
                        $ladoItem = $formatearLadoMonitor(data_get($item, 'lado'));
                    @endphp

                    <g
                        class="monitor-componente monitor-severity-{{ $itemSeverity }} monitor-rank-{{ $itemRank }}"
                        data-monitor-tooltip
                        data-monitor-kind="componente"
                        data-monitor-reductor="{{ e(data_get($item, 'reductor', $grupo['reductor'])) }}"
                        data-monitor-componente="{{ e($nombreComponente) }}"
                        data-monitor-lado="{{ e($ladoItem) }}"
                        data-monitor-dano="{{ e(data_get($item, 'estado', $nivelItem)) }}"
                        data-monitor-fecha="{{ e(data_get($item, 'fecha', 'Sin fecha')) }}"
                        data-monitor-observaciones="{{ e(data_get($item, 'observaciones', 'Sin observaciones')) }}"
                    >
                        <line
                            class="monitor-componente-line"
                            x1="{{ $posicion['x'] }}"
                            y1="{{ $posicion['y'] }}"
                            x2="{{ $connectorX }}"
                            y2="{{ $connectorY }}"
                        />
                        <circle class="monitor-componente-glow" cx="{{ $iconX }}" cy="{{ $iconY }}" r="12" />
                        <rect class="monitor-componente-label-bg" x="{{ $labelX }}" y="{{ $labelY }}" width="{{ $labelWidth }}" height="{{ $labelHeight }}" rx="5" />
                        <path
                            class="monitor-alert-icon"
                            d="M {{ $iconX }} {{ $iconY - 8 }} L {{ $iconX + 8 }} {{ $iconY + 7 }} L {{ $iconX - 8 }} {{ $iconY + 7 }} Z"
                        />
                        <text class="monitor-alert-bang" x="{{ $iconX }}" y="{{ $iconY + 5 }}" text-anchor="middle">!</text>
                        <text class="monitor-componente-label" x="{{ $labelX + 28 }}" y="{{ $labelY + 16 }}">{{ $nombreCorto }}</text>
                    </g>
                @endforeach
            @endif
        @endforeach
    </g>
@endif
