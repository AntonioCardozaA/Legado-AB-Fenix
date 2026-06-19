@php
    $lineasReporte = collect($reporte['lineas'] ?? []);
    $esPasteurizadora = ($tipoEquipo ?? 'lavadoras') === 'pasteurizadoras';
    $tipoTitulo = $esPasteurizadora ? 'Pasteurizadoras' : 'Lavadoras';
    $tipoSingular = $esPasteurizadora ? 'Pasteurizadora' : 'Lavadora';
    $esReporteLinea = ($modoReporte ?? null) === 'linea' || $lineasReporte->count() === 1;
    $lineaUnica = $esReporteLinea ? ($lineasReporte->first()['linea'] ?? null) : null;
    $nombreLineaUnica = optional($lineaUnica)->nombre;
    $tituloDocumento = $esReporteLinea && $nombreLineaUnica
        ? 'Reporte de ' . $tipoSingular . ' ' . $nombreLineaUnica
        : 'Reporte General de ' . $tipoTitulo;
    $subtituloDocumento = $esReporteLinea
        ? 'Detalle completo por linea'
        : 'Reporte general de mantenimiento';
    $platformName = 'Legado AB Fenix';

    $logoCandidates = [
        public_path('images/logo.png'),
        public_path('images/logoo.png'),
        public_path('images/icono-maquina.png'),
    ];

    $resolveLocalImage = function ($path) {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $raw = trim(str_replace('\\', '/', $path));

        if (preg_match('#^data:image/#i', $raw)) {
            return $raw;
        }

        if (preg_match('#^https?://#i', $raw)) {
            $urlPath = parse_url($raw, PHP_URL_PATH);
            $raw = $urlPath ? $urlPath : $raw;
        }

        $raw = urldecode(str_replace('\\', '/', $raw));
        $absoluteRaw = preg_match('#^[A-Za-z]:/#', $raw) ? $raw : null;
        $raw = ltrim($raw, '/');
        $raw = preg_replace('#^(public/|app/public/|storage/app/public/|public/storage/|storage/)#', '', $raw);
        $base = basename($raw);

        $candidates = [];

        if ($absoluteRaw) {
            $candidates[] = $absoluteRaw;
        }

        if ($raw !== '') {
            $candidates[] = public_path('storage/' . $raw);
            $candidates[] = storage_path('app/public/' . $raw);
            $candidates[] = public_path($raw);
        }

        if ($base && $base !== '.' && $base !== $raw) {
            $candidates[] = public_path('storage/analisis-evidencias/' . $base);
            $candidates[] = storage_path('app/public/analisis-evidencias/' . $base);
            $candidates[] = public_path('storage/analisis-pasteurizadora/' . $base);
            $candidates[] = storage_path('app/public/analisis-pasteurizadora/' . $base);
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    };

    $imageDataUri = function ($path) use ($resolveLocalImage) {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        if (preg_match('#^data:image/#i', $path)) {
            return $path;
        }

        $resolved = $resolveLocalImage($path);

        if (!$resolved || !is_file($resolved)) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? @mime_content_type($resolved) : null;

        if (!$mime || strpos($mime, 'image/') !== 0) {
            $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                default => 'image/jpeg',
            };
        }

        $contents = @file_get_contents($resolved);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    };

    $platformLogoPath = collect($logoCandidates)->first(fn ($path) => is_file($path));
    $platformLogo = $platformLogoPath ? $imageDataUri($platformLogoPath) : null;

    $normalizeImages = function ($imagenes) use (&$normalizeImages) {
        if ($imagenes instanceof \Illuminate\Support\Collection) {
            $imagenes = $imagenes->all();
        }

        if (is_string($imagenes)) {
            $value = trim($imagenes);

            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $normalizeImages($decoded);
            }

            return [$value];
        }

        if (is_object($imagenes)) {
            $imagenes = method_exists($imagenes, 'toArray') ? $imagenes->toArray() : get_object_vars($imagenes);
        }

        if (!is_array($imagenes)) {
            return [];
        }

        $result = [];

        foreach ($imagenes as $value) {
            if ($value instanceof \Illuminate\Support\Collection || is_array($value) || is_object($value)) {
                foreach ($normalizeImages($value) as $nested) {
                    $result[] = $nested;
                }

                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                $result[] = trim($value);
            }
        }

        return array_values(array_unique($result));
    };

    $getImages = function ($registro) use ($normalizeImages) {
        $imagenes = data_get($registro, 'imagenes', data_get($registro, 'evidencia_fotos', []));

        return $normalizeImages($imagenes);
    };

    $formatDate = function ($value) {
        if (!$value) {
            return 'Sin fecha';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('d/m/Y');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatDateTime = function ($value) {
        if (!$value) {
            return 'Sin fecha';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('d/m/Y H:i');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatValue = function ($value) use (&$formatValue, $formatDateTime) {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $formatDateTime($value);
        }

        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if (is_bool($value)) {
            return $value ? 'Si' : 'No';
        }

        if (is_array($value)) {
            $parts = [];

            foreach ($value as $key => $item) {
                if ($item instanceof \Illuminate\Support\Collection) {
                    $item = $item->all();
                }

                if (is_array($item)) {
                    $item = implode(', ', array_filter(array_map(fn ($nested) => is_scalar($nested) ? (string) $nested : json_encode($nested), $item)));
                } elseif (is_bool($item)) {
                    $item = $item ? 'Si' : 'No';
                } elseif (!is_scalar($item) && $item !== null) {
                    $item = method_exists($item, '__toString') ? (string) $item : json_encode($item);
                }

                if ($item !== null && $item !== '') {
                    $parts[] = is_string($key) ? $key . ': ' . $item : (string) $item;
                }
            }

            return count($parts) ? implode(' | ', $parts) : 'Sin dato';
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : 'Sin dato';
        }

        return ($value === null || $value === '') ? 'Sin dato' : (string) $value;
    };

    $stateClass = function ($estado) {
        $value = strtolower((string) $estado);

        if (strpos($value, 'requiere cambio') !== false || strpos($value, 'danado') !== false || strpos($value, 'da') === 0) {
            return 'state-danger';
        }

        if (strpos($value, 'severo') !== false || strpos($value, 'moderado') !== false) {
            return 'state-warning';
        }

        if (strpos($value, 'revision') !== false || strpos($value, 'revisi') !== false) {
            return 'state-attention';
        }

        if (strpos($value, 'cambiado') !== false) {
            return 'state-info';
        }

        if (strpos($value, 'buen') !== false || strpos($value, 'estable') !== false) {
            return 'state-good';
        }

        return 'state-neutral';
    };

    $flattenLavadoraAnalisis = function ($agrupados) {
        $items = [];

        foreach ((array) $agrupados as $componentes) {
            foreach ((array) $componentes as $registros) {
                foreach ((array) $registros as $registro) {
                    $items[] = $registro;
                }
            }
        }

        usort($items, function ($a, $b) {
            return strcmp((string) data_get($b, 'fecha_analisis', ''), (string) data_get($a, 'fecha_analisis', ''));
        });

        return $items;
    };

    $getLineAnalisis = function ($lineaReporte) use ($flattenLavadoraAnalisis) {
        if (isset($lineaReporte['analisis'])) {
            return collect($lineaReporte['analisis'])->sortByDesc(fn ($item) => data_get($item, 'fecha_analisis'))->values();
        }

        return collect($flattenLavadoraAnalisis($lineaReporte['analisis_agrupados'] ?? []));
    };

    $lineEvidenceCount = function ($lineaReporte) use ($getLineAnalisis, $getImages) {
        return $getLineAnalisis($lineaReporte)->sum(fn ($registro) => count($getImages($registro)));
    };

    $totalComponentesResumen = function ($resumen) {
        $total = data_get($resumen, 'total_componentes');

        if ($total !== null) {
            return $total;
        }

        $definidos = data_get($resumen, 'componentes_definidos', 0);

        return is_countable($definidos) ? count($definidos) : $definidos;
    };

    $buildLavadoraComponentStats = function ($componentes, $analisisPlanos) {
        return collect($componentes)->map(function ($componente) use ($analisisPlanos) {
            $codigo = data_get($componente, 'codigo', data_get($componente, 'id'));
            $nombre = data_get($componente, 'nombre', $codigo);
            $registros = collect($analisisPlanos)
                ->filter(fn ($registro) => data_get($registro, 'componente.codigo') === $codigo)
                ->values();
            $ultimo = $registros->sortByDesc(fn ($registro) => data_get($registro, 'fecha_analisis'))->first();
            $criticos = $registros->filter(function ($registro) {
                $estado = strtolower((string) data_get($registro, 'estado', ''));
                return strpos($estado, 'requiere cambio') !== false || strpos($estado, 'danado') !== false || strpos($estado, 'da') === 0;
            })->count();

            return [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'total_analisis' => $registros->count(),
                'criticos' => $criticos,
                'ultimo_estado' => data_get($ultimo, 'estado'),
                'ultima_fecha' => data_get($ultimo, 'fecha_analisis'),
            ];
        });
    };
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDocumento }}</title>
    <style>
        @page { margin: 54px 24px 46px 24px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.35;
            margin: 0;
        }
        .cover {
            border: 1px solid #d1d5db;
            padding: 18px;
            margin-bottom: 18px;
        }
        .page-header,
        .page-footer {
            position: fixed;
            left: 0;
            right: 0;
            color: #4b5563;
        }
        .page-header {
            top: -38px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 8px;
        }
        .page-footer {
            bottom: -30px;
            border-top: 1px solid #d1d5db;
            padding-top: 7px;
            font-size: 8.5px;
        }
        .page-header table,
        .page-footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .page-logo {
            width: 28px;
            max-height: 24px;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 6px;
        }
        .page-brand {
            font-size: 10px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
        }
        .page-number:after { content: counter(page); }
        .brand-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .brand-logo {
            width: 70px;
            max-height: 52px;
            object-fit: contain;
        }
        .brand-name {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .doc-kicker {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .8px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 6px 0;
            color: #0f172a;
        }
        h2 {
            font-size: 15px;
            color: #0f172a;
            margin: 0 0 8px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #2563eb;
        }
        h3 {
            font-size: 12px;
            color: #111827;
            margin: 0 0 6px 0;
        }
        .muted { color: #6b7280; }
        .small { font-size: 9px; }
        .section { margin-bottom: 14px; }
        .machine-title {
            background: #111827;
            color: #ffffff;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .machine-title .line {
            font-size: 18px;
            font-weight: bold;
        }
        .machine-title .meta {
            font-size: 10px;
            color: #d1d5db;
            margin-top: 3px;
        }
        .summary-table,
        .data-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            width: 25%;
            padding: 9px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            vertical-align: top;
        }
        .summary-value {
            display: block;
            font-size: 18px;
            font-weight: bold;
            color: #1d4ed8;
            margin-top: 2px;
        }
        .summary-label {
            color: #4b5563;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: .5px;
        }
        .data-table th {
            background: #eef2ff;
            color: #1e3a8a;
            border: 1px solid #c7d2fe;
            padding: 6px;
            font-size: 8.5px;
            text-align: left;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
        }
        .detail-table td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            vertical-align: top;
        }
        .detail-table .label {
            width: 18%;
            background: #f9fafb;
            color: #4b5563;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .state-danger { background: #fee2e2; color: #991b1b; }
        .state-warning { background: #fef3c7; color: #92400e; }
        .state-attention { background: #fef9c3; color: #854d0e; }
        .state-good { background: #dcfce7; color: #166534; }
        .state-info { background: #dbeafe; color: #1d4ed8; }
        .state-neutral { background: #f3f4f6; color: #374151; }
        .analysis-card {
            border: 1px solid #d1d5db;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .analysis-head {
            background: #f3f4f6;
            padding: 7px 8px;
            border-bottom: 1px solid #d1d5db;
            font-weight: bold;
        }
        .analysis-body { padding: 8px; }
        .evidence-grid { margin-top: 7px; }
        .evidence-item {
            display: inline-block;
            width: 31.5%;
            margin: 0 1% 7px 0;
            vertical-align: top;
            border: 1px solid #d1d5db;
            padding: 4px;
            page-break-inside: avoid;
        }
        .evidence-item img {
            width: 100%;
            max-height: 118px;
            object-fit: cover;
            display: block;
        }
        .evidence-caption {
            display: block;
            margin-top: 3px;
            font-size: 7.5px;
            color: #6b7280;
            word-break: break-all;
        }
        .machine-footer {
            border-top: 1px solid #d1d5db;
            margin-top: 16px;
            padding-top: 8px;
            color: #4b5563;
        }
        .machine-footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-logo {
            width: 26px;
            max-height: 22px;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 6px;
        }
        .page-break { page-break-after: always; }
        .empty {
            padding: 10px;
            border: 1px dashed #d1d5db;
            color: #6b7280;
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <table>
            <tr>
                <td>
                    @if($platformLogo)
                        <img src="{{ $platformLogo }}" class="page-logo" alt="{{ $platformName }}">
                    @endif
                    <span class="page-brand">{{ $platformName }}</span>
                </td>
                <td style="text-align: right;">
                    {{ $tituloDocumento }} | {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>
    <div class="page-footer">
        <table>
            <tr>
                <td>
                    @if($platformLogo)
                        <img src="{{ $platformLogo }}" class="page-logo" alt="{{ $platformName }}">
                    @endif
                    {{ $platformName }} | Documento generado automaticamente
                </td>
                <td style="text-align: right;">Pagina <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    <div class="cover">
        <table class="brand-row">
            <tr>
                <td style="width: 78px;">
                    @if($platformLogo)
                        <img src="{{ $platformLogo }}" class="brand-logo" alt="{{ $platformName }}">
                    @endif
                </td>
                <td>
                    <div class="brand-name">{{ $platformName }}</div>
                    <div class="doc-kicker">{{ $subtituloDocumento }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="doc-kicker">Generado</div>
                    <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                </td>
            </tr>
        </table>

        <h1>{{ $tituloDocumento }}</h1>
        <div class="muted">
            Periodo: {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
        </div>

        <div style="height: 14px;"></div>

        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">{{ $esReporteLinea ? 'Maquina / linea' : 'Maquinas / lineas' }}</span>
                    <span class="summary-value" style="{{ $esReporteLinea ? 'font-size: 16px;' : '' }}">
                        {{ $esReporteLinea ? ($nombreLineaUnica ?? 'Sin linea') : $lineasReporte->count() }}
                    </span>
                </td>
                <td>
                    <span class="summary-label">Analisis registrados</span>
                    <span class="summary-value">{{ $lineasReporte->sum(fn ($linea) => $getLineAnalisis($linea)->count()) }}</span>
                </td>
                <td>
                    <span class="summary-label">Componentes criticos</span>
                    <span class="summary-value">{{ $lineasReporte->sum(fn ($linea) => data_get($linea, 'resumen.componentes_criticos', 0)) }}</span>
                </td>
                <td>
                    <span class="summary-label">{{ $esReporteLinea ? 'Tipo de reporte' : 'Lineas con analisis' }}</span>
                    <span class="summary-value" style="{{ $esReporteLinea ? 'font-size: 13px;' : '' }}">
                        {{ $esReporteLinea ? 'Linea' : $lineasReporte->filter(fn ($linea) => $getLineAnalisis($linea)->isNotEmpty())->count() }}
                    </span>
                </td>
            </tr>
        </table>

        <div class="section" style="margin-top: 16px;">
            <h2>{{ $esReporteLinea ? 'Resumen de la linea' : 'Resumen por linea' }}</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Linea</th>
                        <th>Tipo</th>
                        <th>Analisis</th>
                        <th>Componentes revisados</th>
                        <th>Criticos</th>
                        <th>Estado general</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lineasReporte as $lineaReporte)
                        @php
                            $linea = $lineaReporte['linea'] ?? null;
                            $resumen = $lineaReporte['resumen'] ?? [];
                            $estadoGeneral = data_get($resumen, 'estado_general.texto', data_get($lineaReporte, 'estado_general.texto', 'Sin datos'));
                        @endphp
                        <tr>
                            <td><strong>{{ optional($linea)->nombre ?? 'Sin linea' }}</strong></td>
                            <td>{{ $tipoSingular }}</td>
                            <td>{{ $getLineAnalisis($lineaReporte)->count() }}</td>
                            <td>{{ data_get($resumen, 'componentes_revisados', 0) }} / {{ $totalComponentesResumen($resumen) }}</td>
                            <td>{{ data_get($resumen, 'componentes_criticos', 0) }}</td>
                            <td><span class="badge {{ $stateClass($estadoGeneral) }}">{{ $estadoGeneral }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No hay datos para el periodo seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($lineasReporte as $lineaReporte)
        @php
            $linea = $lineaReporte['linea'] ?? null;
            $nombreLinea = optional($linea)->nombre ?? 'Sin linea';
            $resumen = $lineaReporte['resumen'] ?? [];
            $analisisPlanos = $getLineAnalisis($lineaReporte);
            $estadoGeneral = data_get($resumen, 'estado_general.texto', data_get($lineaReporte, 'estado_general.texto', 'Sin datos'));
            $ultimoAnalisisLinea = $analisisPlanos->sortByDesc(fn ($registro) => data_get($registro, 'fecha_analisis'))->first();
            $componentNameMap = collect($lineaReporte['componentes'] ?? [])
                ->mapWithKeys(fn ($component) => [data_get($component, 'codigo') => data_get($component, 'nombre')]);
            $componentStatsLavadora = $esPasteurizadora
                ? collect()
                : $buildLavadoraComponentStats($lineaReporte['componentes_lista'] ?? [], $analisisPlanos);
        @endphp

        <div class="machine-title">
            <div class="line">{{ $tipoSingular }} {{ $nombreLinea }}</div>
            <div class="meta">
                Periodo {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
                | Estado: {{ $estadoGeneral }}
                | Ultimo analisis: {{ $formatDate(data_get($ultimoAnalisisLinea, 'fecha_analisis')) }}
            </div>
        </div>

        <div class="section">
            <h2>Indicadores principales</h2>
            <table class="summary-table">
                <tr>
                    <td>
                        <span class="summary-label">Total analisis</span>
                        <span class="summary-value">{{ $analisisPlanos->count() }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Componentes revisados</span>
                        <span class="summary-value">{{ data_get($resumen, 'componentes_revisados', 0) }} / {{ $totalComponentesResumen($resumen) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Criticos / accion</span>
                        <span class="summary-value">{{ data_get($resumen, 'componentes_criticos', data_get($resumen, 'acciones_pendientes', 0)) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Ultimo analisis</span>
                        <span class="summary-value" style="font-size: 12px;">{{ $formatDate(data_get($ultimoAnalisisLinea, 'fecha_analisis')) }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="summary-label">{{ $esPasteurizadora ? 'Modulos revisados' : 'Elongaciones' }}</span>
                        <span class="summary-value">
                            @if($esPasteurizadora)
                                {{ data_get($resumen, 'modulos_con_analisis', 0) }} / {{ data_get($resumen, 'total_modulos', 0) }}
                            @else
                                {{ isset($lineaReporte['elongaciones']) ? collect($lineaReporte['elongaciones'])->count() : 0 }}
                            @endif
                        </span>
                    </td>
                    <td>
                        <span class="summary-label">Revision / desgaste</span>
                        <span class="summary-value">{{ data_get($resumen, 'componentes_revision', 0) }} / {{ data_get($resumen, 'componentes_severos_moderados', 0) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Piezas revisadas</span>
                        <span class="summary-value">{{ data_get($resumen, 'piezas_revisadas', data_get($resumen, 'componentes_revisados', 0)) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Estado general</span>
                        <span class="summary-value" style="font-size: 12px;">
                            <span class="badge {{ $stateClass($estadoGeneral) }}">{{ $estadoGeneral }}</span>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Informacion de componentes</h2>
            @if($esPasteurizadora)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Componente</th>
                            <th>Cant.</th>
                            <th>Modulos</th>
                            <th>Total configurado</th>
                            <th>Revisado</th>
                            <th>Avance</th>
                            <th>Analisis</th>
                            <th>Ultimo estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($lineaReporte['componentes'] ?? []) as $componente)
                            @php
                                $ultimoEstado = data_get($componente, 'ultimo_estado', 'Sin dato');
                            @endphp
                            <tr>
                                <td>{{ data_get($componente, 'codigo') }}</td>
                                <td><strong>{{ data_get($componente, 'nombre') }}</strong></td>
                                <td>{{ data_get($componente, 'cantidad', 0) }}</td>
                                <td>{{ data_get($componente, 'modulos_aplicables', 0) }}</td>
                                <td>{{ data_get($componente, 'total_configurado', 0) }}</td>
                                <td>{{ data_get($componente, 'cantidad_revisada', 0) }}</td>
                                <td>{{ number_format((float) data_get($componente, 'porcentaje', 0), 1) }}%</td>
                                <td>{{ data_get($componente, 'total_analisis', 0) }}</td>
                                <td><span class="badge {{ $stateClass($ultimoEstado) }}">{{ $ultimoEstado }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No hay componentes configurados para esta linea.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Componente</th>
                            <th>Analisis</th>
                            <th>Criticos</th>
                            <th>Ultimo estado</th>
                            <th>Ultima revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($componentStatsLavadora as $componente)
                            <tr>
                                <td>{{ $componente['codigo'] }}</td>
                                <td><strong>{{ $componente['nombre'] }}</strong></td>
                                <td>{{ $componente['total_analisis'] }}</td>
                                <td>{{ $componente['criticos'] }}</td>
                                <td><span class="badge {{ $stateClass($componente['ultimo_estado'] ?? 'Sin dato') }}">{{ $componente['ultimo_estado'] ?? 'Sin dato' }}</span></td>
                                <td>{{ $formatDate($componente['ultima_fecha'] ?? null) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No hay componentes configurados para esta linea.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if($esPasteurizadora && collect($lineaReporte['modulos'] ?? [])->isNotEmpty())
            <div class="section">
                <h2>Avance por modulo</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Modulo</th>
                            <th>Componentes</th>
                            <th>Avance</th>
                            <th>Analisis</th>
                            <th>Criticos</th>
                            <th>Niveles</th>
                            <th>Lados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($lineaReporte['modulos'] ?? []) as $modulo)
                            <tr>
                                <td><strong>{{ data_get($modulo, 'numero') }}</strong></td>
                                <td>{{ data_get($modulo, 'componentes_revisados', 0) }} / {{ data_get($modulo, 'total_componentes', 0) }}</td>
                                <td>{{ number_format((float) data_get($modulo, 'porcentaje', 0), 1) }}%</td>
                                <td>{{ data_get($modulo, 'total_analisis', 0) }}</td>
                                <td>{{ data_get($modulo, 'criticos', 0) }}</td>
                                <td>{{ $formatValue(data_get($modulo, 'niveles', [])) }}</td>
                                <td>{{ $formatValue(data_get($modulo, 'lados', [])) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!$esPasteurizadora && isset($lineaReporte['elongaciones']) && collect($lineaReporte['elongaciones'])->isNotEmpty())
            <div class="section">
                <h2>Analisis de elongacion</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Linea</th>
                            <th>Bomba</th>
                            <th>Vapor</th>
                            <th>Maximo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(collect($lineaReporte['elongaciones'])->take(20) as $elongacion)
                            <tr>
                                <td>{{ $formatDate(data_get($elongacion, 'created_at')) }}</td>
                                <td>{{ data_get($elongacion, 'linea', $nombreLinea) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'promedio_bombas', data_get($elongacion, 'bomba'))) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'promedio_vapor', data_get($elongacion, 'vapor'))) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'elongacion_max', data_get($elongacion, 'maximo'))) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @php
            $tendenciaRows = collect($lineaReporte['analisis_tendencia'] ?? []);
            $ventanas52124Pdf = collect(data_get($lineaReporte, 'analisis_52124.ventanas', []));
            $ventanas30147Pdf = collect(data_get($lineaReporte, 'analisis_30147.ventanas', []));
        @endphp

        @if($tendenciaRows->isNotEmpty() || $ventanas52124Pdf->isNotEmpty() || $ventanas30147Pdf->isNotEmpty())
            <div class="section">
                <h2>Seguimiento automatico de tendencia</h2>

                @if($ventanas52124Pdf->isNotEmpty() || $ventanas30147Pdf->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Analisis</th>
                                <th>Ventana</th>
                                <th>Periodo actual</th>
                                <th>Actual</th>
                                <th>Anterior</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ventanas52124Pdf as $ventana)
                                <tr>
                                    <td>52-12-4</td>
                                    <td>{{ data_get($ventana, 'label') }}</td>
                                    <td>{{ data_get($ventana, 'current_range', '-') }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'current')) }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'previous')) }}</td>
                                    <td>{{ (($delta = (int) data_get($ventana, 'delta', 0)) > 0 ? '+' : '') . $delta }}</td>
                                </tr>
                            @endforeach
                            @foreach($ventanas30147Pdf as $ventana)
                                <tr>
                                    <td>30-14-7</td>
                                    <td>{{ data_get($ventana, 'label') }}</td>
                                    <td>{{ data_get($ventana, 'current_range', '-') }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'current')) }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'previous')) }}</td>
                                    <td>{{ (($delta = (int) data_get($ventana, 'delta', 0)) > 0 ? '+' : '') . $delta }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if($tendenciaRows->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th>52 semanas</th>
                                <th>12 semanas</th>
                                <th>4 semanas</th>
                                <th>30 dias</th>
                                <th>14 dias</th>
                                <th>7 dias</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tendenciaRows->take(8) as $tendencia)
                                @php
                                    $periodoTendencia = data_get($tendencia, 'periodo')
                                        ?: trim(data_get($tendencia, 'mes') . '/' . data_get($tendencia, 'anio'), '/');
                                @endphp
                            <tr>
                                <td>{{ $periodoTendencia }}</td>
                                <td>{{ $formatValue(data_get($tendencia, 'total_danos_52_semanas')) }}</td>
                                <td>{{ $formatValue(data_get($tendencia, 'total_danos_12_semanas')) }}</td>
                                <td>{{ $formatValue(data_get($tendencia, 'total_danos_4_semanas')) }}</td>
                                <td>{{ $formatValue(data_get($tendencia, 'total_danos_30_dias')) }}</td>
                                <td>{{ $formatValue(data_get($tendencia, 'total_danos_14_dias')) }}</td>
                                <td>{{ $formatValue(data_get($tendencia, 'total_danos_7_dias')) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

        <div class="section">
            <h2>Detalle de analisis y evidencias</h2>
            @forelse($analisisPlanos as $registro)
                @php
                    $imagenes = $getImages($registro);
                    $estado = data_get($registro, 'estado', 'Sin estado');
                    $componenteCodigo = $esPasteurizadora
                        ? data_get($registro, 'componente', data_get($registro, 'componente_codigo'))
                        : data_get($registro, 'componente.codigo', data_get($registro, 'componente_codigo', data_get($registro, 'componente')));

                    if (is_array($componenteCodigo) || is_object($componenteCodigo)) {
                        $componenteCodigo = data_get($componenteCodigo, 'codigo', $formatValue($componenteCodigo));
                    }

                    $componenteNombre = data_get($registro, 'componente.nombre', $componentNameMap->get($componenteCodigo, $componenteCodigo));
                @endphp
                <div class="analysis-card">
                    <div class="analysis-head">
                        Analisis #{{ data_get($registro, 'id', 'N/A') }}
                        | {{ $formatDate(data_get($registro, 'fecha_analisis')) }}
                        | <span class="badge {{ $stateClass($estado) }}">{{ $estado }}</span>
                    </div>
                    <div class="analysis-body">
                        @if($esPasteurizadora)
                            <table class="detail-table">
                                <tr>
                                    <td class="label">Componente</td>
                                    <td>{{ $componenteNombre }} <span class="muted">({{ $componenteCodigo }})</span></td>
                                    <td class="label">Modulo / nivel / lado</td>
                                    <td>{{ $formatValue(data_get($registro, 'modulo')) }} / {{ $formatValue(data_get($registro, 'nivel')) }} / {{ $formatValue(data_get($registro, 'lado')) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Orden</td>
                                    <td>{{ $formatValue(data_get($registro, 'numero_orden')) }}</td>
                                    <td class="label">Responsable</td>
                                    <td>{{ $formatValue(data_get($registro, 'responsable', data_get($registro, 'usuario.name'))) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Cantidad revisada</td>
                                    <td>{{ $formatValue(data_get($registro, 'cantidad_componentes_revisados')) }}</td>
                                    <td class="label">Resuelto por cambio</td>
                                    <td>{{ $formatValue(data_get($registro, 'resuelto_por_cambio')) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Actividad</td>
                                    <td colspan="3">{{ $formatValue(data_get($registro, 'actividad')) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Observaciones</td>
                                    <td colspan="3">{{ $formatValue(data_get($registro, 'observaciones')) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Planes PCM</td>
                                    <td colspan="3">
                                        PCM1: {{ $formatValue(data_get($registro, 'plan_accion_pcm1')) }}
                                        | PCM2: {{ $formatValue(data_get($registro, 'plan_accion_pcm2')) }}
                                        | PCM3: {{ $formatValue(data_get($registro, 'plan_accion_pcm3')) }}
                                        | PCM4: {{ $formatValue(data_get($registro, 'plan_accion_pcm4')) }}
                                    </td>
                                </tr>
                            </table>
                        @else
                            <table class="detail-table">
                                <tr>
                                    <td class="label">Componente</td>
                                    <td>{{ $componenteNombre }} <span class="muted">({{ data_get($registro, 'componente.codigo', 'N/A') }})</span></td>
                                    <td class="label">Reductor</td>
                                    <td>{{ $formatValue(data_get($registro, 'reductor')) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Lado</td>
                                    <td>{{ $formatValue(data_get($registro, 'lado')) }}</td>
                                    <td class="label">Orden</td>
                                    <td>{{ $formatValue(data_get($registro, 'numero_orden')) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Actividad</td>
                                    <td colspan="3">{{ $formatValue(data_get($registro, 'actividad')) }}</td>
                                </tr>
                            </table>
                        @endif

                        <div class="evidence-grid">
                            <strong>Evidencias fotograficas:</strong>
                            @if(count($imagenes) > 0)
                                <div style="height: 5px;"></div>
                                @foreach($imagenes as $index => $foto)
                                    @php
                                        $src = $imageDataUri($foto);
                                    @endphp
                                    <div class="evidence-item">
                                        @if($src)
                                            <img src="{{ $src }}" alt="Evidencia {{ $index + 1 }}">
                                        @else
                                            <div class="empty small">No se encontro el archivo local.</div>
                                        @endif
                                        <span class="evidence-caption">{{ $index + 1 }}. {{ basename(str_replace('\\', '/', $foto)) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="muted"> Sin evidencias registradas.</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">No hay analisis registrados para esta maquina en el periodo seleccionado.</div>
            @endforelse
        </div>

        <div class="machine-footer">
            <table>
                <tr>
                    <td>
                        @if($platformLogo)
                            <img src="{{ $platformLogo }}" class="footer-logo" alt="{{ $platformName }}">
                        @endif
                        <strong>{{ $platformName }}</strong>
                    </td>
                    <td style="text-align: right;">
                        {{ $tipoSingular }} {{ $nombreLinea }} | Documento generado automaticamente
                    </td>
                </tr>
            </table>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
