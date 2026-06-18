@php
    $analisis = collect($analisis ?? []);
    $platformName = 'Legado AB Fenix';
    $lineaNombre = optional($linea ?? null)->nombre;
    $esReporteLinea = !empty($lineaNombre);
    $periodoTexto = ($fechaInicio || $fechaFin)
        ? (($fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'Inicio') . ' - ' . ($fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'Actual'))
        : 'Todos los registros disponibles';

    $logoCandidates = [
        public_path('images/logo.png'),
        public_path('images/logoo.png'),
        public_path('images/icono_pas.png'),
        public_path('images/icono-pas-cover.png'),
    ];

    $imageDataUri = function ($path) {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $raw = trim(str_replace('\\', '/', $path));

        if (preg_match('#^data:image/#i', $raw)) {
            return $raw;
        }

        if (preg_match('#^https?://#i', $raw)) {
            $urlPath = parse_url($raw, PHP_URL_PATH);
            $raw = $urlPath ?: $raw;
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
            $candidates[] = public_path('storage/analisis-pasteurizadora/' . $base);
            $candidates[] = storage_path('app/public/analisis-pasteurizadora/' . $base);
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                $mime = function_exists('mime_content_type') ? @mime_content_type($candidate) : null;

                if (!$mime || strpos($mime, 'image/') !== 0) {
                    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'bmp' => 'image/bmp',
                        default => 'image/jpeg',
                    };
                }

                $contents = @file_get_contents($candidate);

                return $contents === false ? null : 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }

        return null;
    };

    $platformLogoPath = collect($logoCandidates)->first(fn ($path) => is_file($path));
    $platformLogo = $platformLogoPath ? $imageDataUri($platformLogoPath) : null;

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

    $formatValue = function ($value) use (&$formatValue) {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('d/m/Y H:i');
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

    $normalizeImages = function ($imagenes) use (&$normalizeImages) {
        if ($imagenes instanceof \Illuminate\Support\Collection) {
            $imagenes = $imagenes->all();
        }

        if (is_string($imagenes)) {
            $decoded = json_decode($imagenes, true);

            return is_array($decoded) ? $normalizeImages($decoded) : (trim($imagenes) !== '' ? [trim($imagenes)] : []);
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
            } elseif (is_string($value) && trim($value) !== '') {
                $result[] = trim($value);
            }
        }

        return array_values(array_unique($result));
    };

    $stateClass = function ($estado) {
        $value = strtolower((string) $estado);

        if (str_contains($value, 'requiere cambio') || str_contains($value, 'danado') || str_contains($value, 'dañado')) {
            return 'state-danger';
        }

        if (str_contains($value, 'severo') || str_contains($value, 'moderado')) {
            return 'state-warning';
        }

        if (str_contains($value, 'revision') || str_contains($value, 'revisi')) {
            return 'state-attention';
        }

        if (str_contains($value, 'cambiado')) {
            return 'state-info';
        }

        if (str_contains($value, 'buen') || str_contains($value, 'estable')) {
            return 'state-good';
        }

        return 'state-neutral';
    };

    $lineasResumen = $analisis->groupBy(fn ($item) => optional($item->linea)->nombre ?: 'Sin linea')->sortKeys();
    $totalCriticos = $analisis->filter(fn ($item) => \App\Models\AnalisisPasteurizadora::esEstadoDanado($item->estado) && !$item->resuelto_por_cambio)->count();
    $totalRevision = $analisis->where('estado', \App\Models\AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count();
    $totalDesgaste = $analisis->filter(fn ($item) => \App\Models\AnalisisPasteurizadora::esEstadoDesgaste($item->estado))->count();
    $totalPiezas = $analisis->sum(fn ($item) => (int) ($item->cantidad_componentes_revisados ?? 0));
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $tituloDocumento }}</title>
    <style>
        @page { margin: 54px 24px 46px 24px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.35; margin: 0; }
        .page-header, .page-footer { position: fixed; left: 0; right: 0; color: #4b5563; }
        .page-header { top: -38px; border-bottom: 1px solid #d1d5db; padding-bottom: 8px; }
        .page-footer { bottom: -30px; border-top: 1px solid #d1d5db; padding-top: 7px; font-size: 8.5px; }
        .page-header table, .page-footer table { width: 100%; border-collapse: collapse; }
        .page-logo { width: 28px; max-height: 24px; object-fit: contain; vertical-align: middle; margin-right: 6px; }
        .page-brand { font-size: 10px; font-weight: bold; color: #111827; text-transform: uppercase; }
        .page-number:after { content: counter(page); }
        .cover { border: 1px solid #d1d5db; padding: 18px; margin-bottom: 18px; }
        .brand-row { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .brand-logo { width: 70px; max-height: 52px; object-fit: contain; }
        .brand-name { font-size: 13px; font-weight: bold; color: #111827; letter-spacing: .5px; text-transform: uppercase; }
        .doc-kicker { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .8px; }
        h1 { font-size: 24px; margin: 0 0 6px 0; color: #0f172a; }
        h2 { font-size: 15px; color: #0f172a; margin: 0 0 8px 0; padding-bottom: 5px; border-bottom: 2px solid #2563eb; }
        h3 { font-size: 12px; color: #111827; margin: 0 0 6px 0; }
        .muted { color: #6b7280; }
        .small { font-size: 9px; }
        .section { margin-bottom: 14px; }
        .summary-table, .data-table, .detail-table { width: 100%; border-collapse: collapse; }
        .summary-table td { width: 25%; padding: 9px; border: 1px solid #d1d5db; background: #f9fafb; vertical-align: top; }
        .summary-label { color: #4b5563; text-transform: uppercase; font-size: 8px; letter-spacing: .5px; }
        .summary-value { display: block; font-size: 18px; font-weight: bold; color: #1d4ed8; margin-top: 2px; }
        .data-table th { background: #eef2ff; color: #1e3a8a; border: 1px solid #c7d2fe; padding: 6px; font-size: 8.5px; text-align: left; text-transform: uppercase; }
        .data-table td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        .detail-table td { border: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
        .detail-table .label { width: 18%; background: #f9fafb; color: #4b5563; font-weight: bold; }
        .badge { display: inline-block; border-radius: 999px; padding: 2px 7px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .state-danger { background: #fee2e2; color: #991b1b; }
        .state-warning { background: #fef3c7; color: #92400e; }
        .state-attention { background: #fef9c3; color: #854d0e; }
        .state-good { background: #dcfce7; color: #166534; }
        .state-info { background: #dbeafe; color: #1d4ed8; }
        .state-neutral { background: #f3f4f6; color: #374151; }
        .line-title { background: #111827; color: #fff; padding: 10px 12px; margin: 14px 0 10px; page-break-inside: avoid; }
        .line-title .line { font-size: 16px; font-weight: bold; }
        .line-title .meta { font-size: 9px; color: #d1d5db; margin-top: 3px; }
        .analysis-card { border: 1px solid #d1d5db; margin-bottom: 10px; page-break-inside: avoid; }
        .analysis-head { background: #f3f4f6; padding: 7px 8px; border-bottom: 1px solid #d1d5db; font-weight: bold; }
        .analysis-body { padding: 8px; }
        .evidence-grid { margin-top: 7px; }
        .evidence-item { display: inline-block; width: 31.5%; margin: 0 1% 7px 0; vertical-align: top; border: 1px solid #d1d5db; padding: 4px; page-break-inside: avoid; }
        .evidence-item img { width: 100%; max-height: 118px; object-fit: cover; display: block; }
        .evidence-caption { display: block; margin-top: 3px; font-size: 7.5px; color: #6b7280; word-break: break-all; }
        .empty { padding: 10px; border: 1px dashed #d1d5db; color: #6b7280; background: #f9fafb; }
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
                <td style="text-align: right;">{{ $tituloDocumento }} | {{ $periodoTexto }}</td>
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
                    <div class="doc-kicker">{{ $analisisAreaLabel ?? 'Mecanica' }} | Reporte de pasteurizadora</div>
                </td>
                <td style="text-align: right;">
                    <div class="doc-kicker">Generado</div>
                    <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                </td>
            </tr>
        </table>

        <h1>{{ $tituloDocumento }}</h1>
        <div class="muted">Periodo: {{ $periodoTexto }}</div>

        <div style="height: 14px;"></div>

        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">{{ $esReporteLinea ? 'Linea' : 'Lineas' }}</span>
                    <span class="summary-value" style="{{ $esReporteLinea ? 'font-size: 16px;' : '' }}">{{ $esReporteLinea ? $lineaNombre : $lineasResumen->count() }}</span>
                </td>
                <td>
                    <span class="summary-label">Analisis</span>
                    <span class="summary-value">{{ $analisis->count() }}</span>
                </td>
                <td>
                    <span class="summary-label">Criticos</span>
                    <span class="summary-value">{{ $totalCriticos }}</span>
                </td>
                <td>
                    <span class="summary-label">Piezas revisadas</span>
                    <span class="summary-value">{{ $totalPiezas }}</span>
                </td>
            </tr>
        </table>

        <div class="section" style="margin-top: 16px;">
            <h2>{{ $esReporteLinea ? 'Resumen de la linea' : 'Resumen por linea' }}</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Linea</th>
                        <th>Analisis</th>
                        <th>Modulos</th>
                        <th>Componentes</th>
                        <th>Criticos</th>
                        <th>Revision</th>
                        <th>Desgaste</th>
                        <th>Piezas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lineasResumen as $nombreLinea => $registrosLinea)
                        <tr>
                            <td><strong>{{ $nombreLinea }}</strong></td>
                            <td>{{ $registrosLinea->count() }}</td>
                            <td>{{ $registrosLinea->pluck('modulo')->filter()->unique()->count() }}</td>
                            <td>{{ $registrosLinea->pluck('componente')->filter()->unique()->count() }}</td>
                            <td>{{ $registrosLinea->filter(fn ($item) => \App\Models\AnalisisPasteurizadora::esEstadoDanado($item->estado) && !$item->resuelto_por_cambio)->count() }}</td>
                            <td>{{ $registrosLinea->where('estado', \App\Models\AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION)->count() }}</td>
                            <td>{{ $registrosLinea->filter(fn ($item) => \App\Models\AnalisisPasteurizadora::esEstadoDesgaste($item->estado))->count() }}</td>
                            <td>{{ $registrosLinea->sum(fn ($item) => (int) ($item->cantidad_componentes_revisados ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No hay datos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @forelse($lineasResumen as $nombreLinea => $registrosLinea)
        <div class="line-title">
            <div class="line">Pasteurizadora {{ $nombreLinea }}</div>
            <div class="meta">
                {{ $registrosLinea->count() }} analisis | {{ $registrosLinea->pluck('modulo')->filter()->unique()->count() }} modulos | Ultimo analisis: {{ $formatDate(optional($registrosLinea->sortByDesc('fecha_analisis')->first())->fecha_analisis) }}
            </div>
        </div>

        <div class="section">
            <h2>Detalle consolidado</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Modulo</th>
                        <th>Nivel</th>
                        <th>Lado</th>
                        <th>Componente</th>
                        <th>Estado</th>
                        <th>Orden</th>
                        <th>Revisadas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registrosLinea as $item)
                        <tr>
                            <td>{{ $formatDate($item->fecha_analisis) }}</td>
                            <td>{{ $formatValue($item->modulo) }}</td>
                            <td>{{ $formatValue($item->nivel) }}</td>
                            <td>{{ $formatValue($item->lado) }}</td>
                            <td><strong>{{ $item->componente_nombre }}</strong></td>
                            <td><span class="badge {{ $stateClass($item->estado) }}">{{ $item->estado }}</span></td>
                            <td>{{ $formatValue($item->numero_orden) }}</td>
                            <td>{{ (int) ($item->cantidad_componentes_revisados ?? 0) }} / {{ (int) ($item->total_componentes ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Informacion detallada por analisis</h2>
            @foreach($registrosLinea as $item)
                @php
                    $imagenes = $normalizeImages($item->evidencia_fotos ?? []);
                @endphp
                <div class="analysis-card">
                    <div class="analysis-head">
                        Analisis #{{ $item->id }} | {{ $formatDate($item->fecha_analisis) }} | <span class="badge {{ $stateClass($item->estado) }}">{{ $item->estado }}</span>
                    </div>
                    <div class="analysis-body">
                        <table class="detail-table">
                            <tr>
                                <td class="label">Componente</td>
                                <td>{{ $item->componente_nombre }} <span class="muted">({{ $item->componente }})</span></td>
                                <td class="label">Modulo / nivel / lado</td>
                                <td>{{ $formatValue($item->modulo) }} / {{ $formatValue($item->nivel) }} / {{ $formatValue($item->lado) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Orden</td>
                                <td>{{ $formatValue($item->numero_orden) }}</td>
                                <td class="label">Responsable</td>
                                <td>{{ $formatValue($item->responsable ?: optional($item->usuario)->name) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Cantidad revisada</td>
                                <td>{{ (int) ($item->cantidad_componentes_revisados ?? 0) }} / {{ (int) ($item->total_componentes ?? 0) }}</td>
                                <td class="label">Resuelto por cambio</td>
                                <td>{{ $formatValue($item->resuelto_por_cambio) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Actividad</td>
                                <td colspan="3">{{ $formatValue($item->actividad) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Observaciones</td>
                                <td colspan="3">{{ $formatValue($item->observaciones) }}</td>
                            </tr>
                            <tr>
                                <td class="label">52-12-4</td>
                                <td colspan="3">
                                    52: {{ $formatValue($item->valor_anterior_52) }} -> {{ $formatValue($item->valor_actual_52) }}
                                    | 12: {{ $formatValue($item->valor_anterior_12) }} -> {{ $formatValue($item->valor_actual_12) }}
                                    | 4: {{ $formatValue($item->valor_anterior_4) }} -> {{ $formatValue($item->valor_actual_4) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Planes PCM</td>
                                <td colspan="3">
                                    PCM1: {{ $formatValue($item->plan_accion_pcm1) }}
                                    | PCM2: {{ $formatValue($item->plan_accion_pcm2) }}
                                    | PCM3: {{ $formatValue($item->plan_accion_pcm3) }}
                                    | PCM4: {{ $formatValue($item->plan_accion_pcm4) }}
                                </td>
                            </tr>
                        </table>

                        <div class="evidence-grid">
                            <strong>Evidencias fotograficas:</strong>
                            @if(count($imagenes) > 0)
                                <div style="height: 5px;"></div>
                                @foreach($imagenes as $index => $foto)
                                    @php($src = $imageDataUri($foto))
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
            @endforeach
        </div>
    @empty
        <div class="empty">No hay analisis registrados para los filtros seleccionados.</div>
    @endforelse
</body>
</html>
