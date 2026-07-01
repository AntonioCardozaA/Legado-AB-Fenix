@php
    $rawLineasReporte = collect($reporte['lineas'] ?? []);

    if ($rawLineasReporte->isEmpty() && isset($reporte['linea'])) {
        $rawLineasReporte = collect([$reporte]);
    }

    $lineasReporte = $rawLineasReporte->values();
    $primerReporte = $lineasReporte->first() ?? [];
    $tipoEquipo = $tipoEquipo ?? data_get($primerReporte, 'tipo_equipo', 'lavadoras');
    $esPasteurizadora = $tipoEquipo === 'pasteurizadoras';
    $tipoTitulo = $esPasteurizadora ? 'Pasteurizadoras' : 'Lavadoras';
    $tipoSingular = $esPasteurizadora ? 'Pasteurizadora' : 'Lavadora';
    $esReporteLinea = ($modoReporte ?? null) === 'linea' || $lineasReporte->count() === 1;
    $lineaUnica = $esReporteLinea ? data_get($primerReporte, 'linea') : null;
    $nombreLineaUnica = optional($lineaUnica)->nombre;
    $fechaInicioPdf = $fechaInicio ?? data_get($primerReporte, 'fecha_inicio', now()->subMonth());
    $fechaFinPdf = $fechaFin ?? data_get($primerReporte, 'fecha_fin', now());
    $fechaInicioPdf = $fechaInicioPdf instanceof \Carbon\CarbonInterface
        ? $fechaInicioPdf
        : \Carbon\Carbon::parse($fechaInicioPdf);
    $fechaFinPdf = $fechaFinPdf instanceof \Carbon\CarbonInterface
        ? $fechaFinPdf
        : \Carbon\Carbon::parse($fechaFinPdf);
    $companyName = 'Departamento de Envasado';
    $platformName = 'Legado AB Fenix';
    $documentCode = 'MNT-' . ($esPasteurizadora ? 'PAS' : 'LAV') . '-' . $fechaFinPdf->format('Ymd');
    $tituloDocumento = $esReporteLinea && $nombreLineaUnica
        ? 'Reporte tecnico de ' . $tipoSingular . ' ' . $nombreLineaUnica
        : 'Reporte tecnico general de ' . $tipoTitulo;
    $subtituloDocumento = $esReporteLinea
        ? 'Detalle por linea'
        : 'Resumen y detalle por lineas';

    $logoCandidates = [
        public_path('images/logo.png'),
        public_path('images/logoo.png'),
        public_path('images/icono-maquina.png'),
    ];

    $normalizeText = function ($value): string {
        $text = (string) $value;
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

        return strtr($text, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'Ã¡' => 'a',
            'Ã©' => 'e',
            'Ã­' => 'i',
            'Ã³' => 'o',
            'Ãº' => 'u',
            'Ã±' => 'n',
            'Ã' => 'a',
            'Ã‰' => 'e',
            'Ã' => 'i',
            'Ã“' => 'o',
            'Ãš' => 'u',
            'Ã‘' => 'n',
        ]);
    };

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

    $formatNumber = function ($value, int $decimals = 1) {
        return is_numeric($value) ? number_format((float) $value, $decimals) : 'Sin dato';
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
                    $item = implode(', ', array_filter(array_map(
                        fn ($nested) => is_scalar($nested) ? (string) $nested : json_encode($nested),
                        $item
                    )));
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

    $stateClass = function ($estado) use ($normalizeText) {
        $value = $normalizeText($estado);

        if (str_contains($value, 'requiere cambio') || str_contains($value, 'danado') || str_starts_with($value, 'da')) {
            return 'state-danger';
        }

        if (str_contains($value, 'severo') || str_contains($value, 'moderado') || str_contains($value, 'desgaste')) {
            return 'state-warning';
        }

        if (str_contains($value, 'revision') || str_contains($value, 'revisi')) {
            return 'state-attention';
        }

        if (str_contains($value, 'cambiado')) {
            return 'state-info';
        }

        if (str_contains($value, 'buen') || str_contains($value, 'estable') || str_contains($value, 'normal')) {
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
            return collect($lineaReporte['analisis'])
                ->sortByDesc(fn ($item) => data_get($item, 'fecha_analisis'))
                ->values();
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

    $getComponentCodigo = function ($registro) use ($formatValue) {
        $codigo = data_get($registro, 'componente.codigo', data_get($registro, 'componente_codigo', data_get($registro, 'componente')));

        if (is_array($codigo) || is_object($codigo)) {
            $codigo = data_get($codigo, 'codigo', $formatValue($codigo));
        }

        return (string) $codigo;
    };

    $componentCodeMatches = function ($actual, $expected) {
        $actual = (string) $actual;
        $expected = (string) $expected;

        return $actual === $expected || str_ends_with($actual, '_' . $expected);
    };

    $isCriticalState = function ($estado) use ($normalizeText) {
        $value = $normalizeText($estado);

        return str_contains($value, 'requiere cambio') || str_contains($value, 'danado') || str_starts_with($value, 'da');
    };

    $buildLavadoraComponentStats = function ($lineaReporte, $analisisPlanos) use ($getComponentCodigo, $componentCodeMatches, $isCriticalState) {
        $source = collect($lineaReporte['componentes'] ?? []);

        if ($source->isEmpty()) {
            $source = collect($lineaReporte['componentes_lista'] ?? []);
        }

        return $source->map(function ($componente) use ($analisisPlanos, $getComponentCodigo, $componentCodeMatches, $isCriticalState) {
            $codigo = data_get($componente, 'codigo', data_get($componente, 'id'));
            $nombre = data_get($componente, 'nombre', $codigo);
            $registrosPeriodo = collect($analisisPlanos)
                ->filter(fn ($registro) => $componentCodeMatches($getComponentCodigo($registro), $codigo))
                ->values();
            $ultimoPeriodo = $registrosPeriodo
                ->sortByDesc(fn ($registro) => data_get($registro, 'fecha_analisis'))
                ->first();
            $ultimoHistorico = data_get($componente, 'ultimo_analisis', $ultimoPeriodo);
            $ultimo = $ultimoHistorico ?: $ultimoPeriodo;

            return [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'total_analisis_periodo' => data_get($componente, 'total_analisis_periodo', $registrosPeriodo->count()),
                'total_analisis' => data_get($componente, 'total_analisis', $registrosPeriodo->count()),
                'criticos' => $registrosPeriodo->filter(fn ($registro) => $isCriticalState(data_get($registro, 'estado')))->count(),
                'ultimo_estado' => data_get($componente, 'ultimo_estado', data_get($ultimo, 'estado')),
                'ultima_fecha' => data_get($ultimo, 'fecha_analisis'),
                'ubicacion' => trim((string) data_get($componente, 'ultimo_reductor', data_get($ultimo, 'reductor', '')) . ' ' . (string) data_get($componente, 'ultimo_lado', data_get($ultimo, 'lado', ''))),
            ];
        })->values();
    };

    $coveragePercent = function ($revisados, $total): float {
        $total = (float) $total;

        if ($total <= 0) {
            return 0;
        }

        return min(100, round(((float) $revisados / $total) * 100, 1));
    };

    $lineConclusion = function ($resumen, $analisisPlanos) use ($esPasteurizadora) {
        $total = collect($analisisPlanos)->count();
        $criticos = (int) data_get($resumen, 'componentes_criticos', 0);
        $revision = (int) data_get($resumen, 'componentes_revision', 0);
        $desgaste = (int) data_get($resumen, 'componentes_severos_moderados', 0);
        $paros = (int) data_get($resumen, 'total_paros', data_get($resumen, 'paros_count', 0));

        if ($total === 0) {
            return 'No se registraron analisis en el periodo.';
        }

        if ($criticos > 0) {
            return 'Condicion prioritaria: existen componentes criticos o con requerimiento de cambio. Programar intervencion, responsable y seguimiento PCM.';
        }

        if (($revision + $desgaste) > 0) {
            return 'Condicion bajo seguimiento: hay hallazgos en revision o desgaste. Mantener monitoreo tecnico y confirmar tendencia en la siguiente inspeccion.';
        }

        if ($paros > 0) {
            return 'Condicion operativa estable con paros registrados. Revisar causas y cierre de acciones para sostener disponibilidad del equipo.';
        }

        return $esPasteurizadora
            ? 'Condicion general estable para la pasteurizadora en el periodo evaluado.'
            : 'Condicion general estable para la lavadora en el periodo evaluado.';
    };

    $hasUsefulValue = function ($value) use ($formatValue): bool {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($item instanceof \Illuminate\Support\Collection) {
                    $item = $item->all();
                }

                if (is_array($item)) {
                    if (count(array_filter($item, fn ($nested) => $nested !== null && trim($formatValue($nested)) !== '')) > 0) {
                        return true;
                    }

                    continue;
                }

                if ($item !== null && trim($formatValue($item)) !== '') {
                    return true;
                }
            }

            return false;
        }

        $text = trim($formatValue($value));

        return $text !== ''
            && !in_array($text, ['Sin dato', 'Sin fecha', 'N/A', '-'], true);
    };

    $makeFact = function (string $label, $value, bool $wide = false) use ($formatValue, $hasUsefulValue) {
        if (!$hasUsefulValue($value)) {
            return null;
        }

        return [
            'label' => $label,
            'value' => $formatValue($value),
            'wide' => $wide,
        ];
    };

    $totalAnalisisDocumento = $lineasReporte->sum(fn ($linea) => $getLineAnalisis($linea)->count());
    $totalCriticosDocumento = $lineasReporte->sum(fn ($linea) => data_get($linea, 'resumen.componentes_criticos', 0));
    $totalRevisionDocumento = $lineasReporte->sum(fn ($linea) => data_get($linea, 'resumen.componentes_revision', 0));
    $totalDesgasteDocumento = $lineasReporte->sum(fn ($linea) => data_get($linea, 'resumen.componentes_severos_moderados', 0));
    $totalParosDocumento = $lineasReporte->sum(fn ($linea) => data_get($linea, 'resumen.total_paros', data_get($linea, 'resumen.paros_count', 0)));
    $totalEvidenciasDocumento = $lineasReporte->sum(fn ($linea) => $lineEvidenceCount($linea));
    $lineasConAnalisis = $lineasReporte->filter(fn ($linea) => $getLineAnalisis($linea)->isNotEmpty())->count();
    $estadoDocumento = $totalCriticosDocumento > 0
        ? 'CRITICO'
        : (($totalRevisionDocumento + $totalDesgasteDocumento) > 0 ? 'EN SEGUIMIENTO' : 'ESTABLE');
    $lineHasOperationalData = function ($lineaReporte) use ($getLineAnalisis): bool {
        return $getLineAnalisis($lineaReporte)->isNotEmpty()
            || collect(data_get($lineaReporte, 'paros', []))->isNotEmpty()
            || collect(data_get($lineaReporte, 'elongaciones', []))->isNotEmpty()
            || collect(data_get($lineaReporte, 'reductores', []))->isNotEmpty()
            || collect(data_get($lineaReporte, 'modulos', []))->isNotEmpty();
    };
    $lineasDetalle = $esReporteLinea
        ? $lineasReporte
        : $lineasReporte->filter(fn ($lineaReporte) => $lineHasOperationalData($lineaReporte))->values();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDocumento }}</title>
    <style>
        @page { margin: 68px 28px 42px 28px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #172033;
            font-size: 8.6px;
            line-height: 1.28;
            margin: 0;
        }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        .page-header,
        .page-footer {
            position: fixed;
            left: 0;
            right: 0;
            color: #475569;
        }
        .page-header {
            top: -50px;
            height: 42px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 7px;
        }
        .page-footer {
            bottom: -29px;
            height: 24px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 7.8px;
        }
        .page-logo {
            width: 28px;
            max-height: 24px;
            display: block;
        }
        .page-brand {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .page-number:after { content: counter(page); }
        .cover {
            border: 1px solid #cbd5e1;
            border-top: 5px solid #172554;
            background: #f8fafc;
            padding: 13px 14px 12px 14px;
            margin-bottom: 11px;
            page-break-inside: avoid;
        }
        .cover-head {
            border-bottom: 1px solid #dbe3ee;
            padding-bottom: 9px;
            margin-bottom: 10px;
        }
        .cover-head-table td {
            vertical-align: middle;
        }
        .cover-logo-cell {
            width: 68px;
        }
        .cover-brand-cell {
            padding-left: 2px;
        }
        .cover-document-cell {
            width: 185px;
            text-align: right;
        }
        .brand-logo {
            width: 54px;
            max-height: 40px;
            display: block;
        }
        .kicker {
            color: #64748b;
            font-size: 7.6px;
            text-transform: uppercase;
            letter-spacing: .7px;
            font-weight: bold;
        }
        .company {
            font-size: 13px;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        h1 {
            color: #0f172a;
            font-size: 21px;
            margin: 3px 0 3px 0;
            line-height: 1.1;
        }
        .subtitle {
            color: #475569;
            font-size: 9.6px;
            margin-bottom: 9px;
        }
        .meta-strip,
        .metric-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px 0;
            table-layout: fixed;
            margin: 0 0 7px 0;
        }
        .meta-card,
        .metric-card {
            vertical-align: top;
            border: 1px solid #dbe3ee;
            background: #ffffff;
            overflow-wrap: break-word;
        }
        .meta-card {
            padding: 6px 7px;
        }
        .metric-card {
            padding: 7px 7px 6px 7px;
            height: 50px;
        }
        .meta-value {
            display: block;
            margin-top: 2px;
            color: #0f172a;
            font-weight: bold;
            font-size: 9.2px;
        }
        .kpi-value {
            display: block;
            color: #172554;
            font-size: 17px;
            font-weight: bold;
            margin-top: 2px;
        }
        .kpi-note {
            display: block;
            color: #64748b;
            font-size: 7.6px;
            margin-top: 1px;
        }
        .executive-note,
        .conclusion-box {
            border-left: 4px solid #b88a00;
            background: #fffdf5;
            padding: 8px 9px;
            color: #334155;
            margin-top: 6px;
        }
        .section {
            margin-bottom: 10px;
        }
        .section-header {
            margin: 10px 0 6px 0;
            border-bottom: 2px solid #172554;
            padding-bottom: 4px;
        }
        .section-title {
            color: #0f172a;
            font-size: 12.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .section-subtitle {
            color: #64748b;
            font-size: 8px;
            margin-top: 2px;
        }
        .data-table th {
            background: #172554;
            color: #ffffff;
            border: 1px solid #172554;
            padding: 5.2px 5.5px;
            text-align: left;
            text-transform: uppercase;
            font-size: 7.4px;
            letter-spacing: .25px;
        }
        .data-table td {
            border: 1px solid #dbe3ee;
            padding: 4.8px 5.5px;
            vertical-align: top;
            background: #ffffff;
        }
        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .machine-title {
            background: #0f172a;
            color: #ffffff;
            padding: 8px 10px;
            margin: 9px 0 9px 0;
            page-break-inside: avoid;
        }
        .machine-line {
            font-size: 15.5px;
            font-weight: bold;
            letter-spacing: .2px;
        }
        .machine-meta {
            color: #cbd5e1;
            font-size: 8.4px;
            margin-top: 3px;
        }
        .badge {
            display: inline-block;
            border-radius: 2px;
            padding: 2px 6px;
            font-size: 7.2px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .15px;
            white-space: nowrap;
        }
        .state-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .state-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .state-attention { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .state-good { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .state-info { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .state-neutral { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .progress {
            height: 7px;
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            margin-top: 3px;
        }
        .progress-fill {
            height: 5px;
            background: #1d4ed8;
        }
        .muted { color: #64748b; }
        .strong { font-weight: bold; color: #0f172a; }
        .small { font-size: 7.8px; }
        .empty {
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            color: #64748b;
            padding: 8px 9px;
        }
        .analysis-card {
            border: 1px solid #cbd5e1;
            margin-bottom: 7px;
            background: #ffffff;
        }
        .analysis-head {
            background: #e2e8f0;
            border-bottom: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-weight: bold;
            color: #0f172a;
            page-break-after: avoid;
        }
        .analysis-body { padding: 6px; }
        .analysis-core { page-break-inside: avoid; }
        .analysis-title {
            font-size: 9px;
            color: #0f172a;
        }
        .analysis-meta {
            color: #475569;
            font-size: 7.6px;
            margin-top: 2px;
        }
        .fact-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .fact-table td {
            border: 1px solid #dbe3ee;
            padding: 4px 5px;
            vertical-align: top;
        }
        .fact-label-cell {
            width: 14%;
            background: #f8fafc;
            color: #64748b;
            font-size: 7.1px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .25px;
        }
        .fact-value-cell {
            width: 36%;
            color: #172033;
            font-size: 8.2px;
            background: #ffffff;
            overflow-wrap: break-word;
        }
        .fact-empty-cell {
            background: #ffffff;
            border-color: #ffffff;
        }
        .evidence-grid {
            margin-top: 4px;
            page-break-before: avoid;
        }
        .evidence-title {
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .evidence-item {
            display: inline-block;
            width: 18.7%;
            margin: 0 .8% 5px 0;
            vertical-align: top;
            border: 1px solid #cbd5e1;
            padding: 3px;
            page-break-inside: avoid;
            background: #f8fafc;
        }
        .evidence-item img {
            width: 100%;
            max-height: 72px;
            display: block;
        }
        .evidence-caption {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 6.8px;
            word-break: break-all;
        }
        .line-footer {
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            padding-top: 6px;
            margin-top: 9px;
            page-break-inside: avoid;
        }
        .footer-logo {
            width: 24px;
            max-height: 20px;
            vertical-align: middle;
            margin-right: 6px;
        }
        .page-break { page-break-after: always; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="page-header">
        <table>
            <tr>
                <td style="width: 55%;">
                    <table style="width: auto;">
                        <tr>
                            @if($platformLogo)
                                <td style="width: 34px; vertical-align: middle;">
                                    <img src="{{ $platformLogo }}" class="page-logo" alt="{{ $platformName }}">
                                </td>
                            @endif
                            <td style="vertical-align: middle; padding-left: {{ $platformLogo ? '6px' : '0' }};">
                                <span class="page-brand">{{ $companyName }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="text-align: right;">
                    <span class="strong">{{ $documentCode }}</span><br>
                    {{ $tituloDocumento }} | {{ $fechaInicioPdf->format('d/m/Y') }} - {{ $fechaFinPdf->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="page-footer">
        <table>
            <tr>
                <td>{{ $platformName }}</td>
                <td style="text-align: right;">Pagina <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    <div class="cover">
        <div class="cover-head">
            <table class="cover-head-table">
                <tr>
                    @if($platformLogo)
                        <td class="cover-logo-cell">
                            <img src="{{ $platformLogo }}" class="brand-logo" alt="{{ $platformName }}">
                        </td>
                    @endif
                    <td class="cover-brand-cell">
                        <div class="company">{{ $companyName }}</div>
                        <div class="kicker">{{ $platformName }} | Gestion tecnica de mantenimiento</div>
                    </td>
                    <td class="cover-document-cell">
                        <div class="kicker">Documento</div>
                        <div class="strong">{{ $documentCode }}</div>
                        <div class="muted small">Generado: {{ now()->format('d/m/Y H:i') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <h1>{{ $tituloDocumento }}</h1>
        <div class="subtitle">{{ $subtituloDocumento }}</div>

        <table class="meta-strip">
            <tr>
            <td class="meta-card">
                <span class="kicker">Equipo</span>
                <span class="meta-value">{{ $tipoTitulo }}</span>
            </td>
            <td class="meta-card">
                <span class="kicker">Periodo</span>
                <span class="meta-value">{{ $fechaInicioPdf->format('d/m/Y') }} - {{ $fechaFinPdf->format('d/m/Y') }}</span>
            </td>
            <td class="meta-card">
                <span class="kicker">Alcance</span>
                <span class="meta-value">{{ $esReporteLinea ? ($nombreLineaUnica ?? 'Linea unica') : $lineasReporte->count() . ' lineas' }}</span>
            </td>
            <td class="meta-card">
                <span class="kicker">Estado global</span>
                <span class="meta-value"><span class="badge {{ $stateClass($estadoDocumento) }}">{{ $estadoDocumento }}</span></span>
            </td>
            <td class="meta-card">
                <span class="kicker">Lineas con datos</span>
                <span class="meta-value">{{ $lineasConAnalisis }} / {{ $lineasReporte->count() }}</span>
            </td>
            </tr>
        </table>

        <table class="metric-grid">
            <tr>
            <td class="metric-card">
                <span class="kicker">Analisis</span>
                <span class="kpi-value">{{ $totalAnalisisDocumento }}</span>
            </td>
            <td class="metric-card">
                <span class="kicker">Criticos</span>
                <span class="kpi-value">{{ $totalCriticosDocumento }}</span>
            </td>
            <td class="metric-card">
                <span class="kicker">Revision</span>
                <span class="kpi-value">{{ $totalRevisionDocumento }}</span>
            </td>
            <td class="metric-card">
                <span class="kicker">Desgaste</span>
                <span class="kpi-value">{{ $totalDesgasteDocumento }}</span>
            </td>
            </tr>
        </table>

        <div class="executive-note">
            <strong>Resumen:</strong>
            @if($totalAnalisisDocumento === 0)
                No se encontraron analisis para el periodo seleccionado. Conviene validar filtros, captura de datos y programa de inspeccion.
            @elseif($totalCriticosDocumento > 0)
                El reporte identifica {{ $totalCriticosDocumento }} hallazgo(s) critico(s). Se recomienda priorizar intervenciones y confirmar cierre en planes PCM.
            @elseif(($totalRevisionDocumento + $totalDesgasteDocumento) > 0)
                El reporte no presenta criticos, pero mantiene hallazgos en revision o desgaste. Se recomienda seguimiento por tendencia y proxima inspeccion.
            @else
                La informacion cargada no muestra condiciones criticas en el periodo. Mantener rutina de inspeccion y evidencia documental.
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-title">{{ $esReporteLinea ? 'Resumen de la linea' : 'Resumen por linea' }}</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Linea</th>
                    <th>Tipo</th>
                    <th>Analisis</th>
                    <th>Componentes</th>
                    <th>Criticos</th>
                    <th>Revision / desgaste</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lineasReporte as $lineaReporte)
                    @php
                        $linea = data_get($lineaReporte, 'linea');
                        $resumen = data_get($lineaReporte, 'resumen', []);
                        $estadoGeneral = data_get($resumen, 'estado_general.texto', data_get($lineaReporte, 'estado_general.texto', 'Sin datos'));
                        $totalComponentes = $totalComponentesResumen($resumen);
                    @endphp
                    <tr>
                        <td><span class="strong">{{ optional($linea)->nombre ?? 'Sin linea' }}</span></td>
                        <td>{{ data_get($lineaReporte, 'tipo_equipo', $tipoSingular) }}</td>
                        <td>{{ $getLineAnalisis($lineaReporte)->count() }}</td>
                        <td>
                            {{ data_get($resumen, 'componentes_revisados', 0) }} / {{ $totalComponentes }}
                            <div class="progress"><div class="progress-fill" style="width: {{ $coveragePercent(data_get($resumen, 'componentes_revisados', 0), $totalComponentes) }}%;"></div></div>
                        </td>
                        <td>{{ data_get($resumen, 'componentes_criticos', 0) }}</td>
                        <td>{{ data_get($resumen, 'componentes_revision', 0) }} / {{ data_get($resumen, 'componentes_severos_moderados', 0) }}</td>
                        <td><span class="badge {{ $stateClass($estadoGeneral) }}">{{ $estadoGeneral }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No hay datos para el periodo seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach($lineasDetalle as $lineaReporte)
        @php
            $linea = data_get($lineaReporte, 'linea');
            $nombreLinea = optional($linea)->nombre ?? 'Sin linea';
            $resumen = data_get($lineaReporte, 'resumen', []);
            $analisisPlanos = $getLineAnalisis($lineaReporte);
            $estadoGeneral = data_get($resumen, 'estado_general.texto', data_get($lineaReporte, 'estado_general.texto', 'Sin datos'));
            $ultimoAnalisisLinea = $analisisPlanos->sortByDesc(fn ($registro) => data_get($registro, 'fecha_analisis'))->first();
            $componentNameMap = collect($lineaReporte['componentes'] ?? [])
                ->mapWithKeys(fn ($component) => [data_get($component, 'codigo') => data_get($component, 'nombre')]);
            $componentStatsLavadora = $esPasteurizadora
                ? collect()
                : $buildLavadoraComponentStats($lineaReporte, $analisisPlanos);
            $parosLinea = collect($lineaReporte['paros'] ?? []);
            $elongacionesLinea = collect($lineaReporte['elongaciones'] ?? []);
            $reductoresLinea = collect($lineaReporte['reductores'] ?? []);
            $totalComponentesLinea = $totalComponentesResumen($resumen);
            $observacionesCount = $analisisPlanos->filter(fn ($registro) => trim((string) data_get($registro, 'observaciones', '')) !== '')->count();
        @endphp

        <div class="machine-title">
            <div class="machine-line">{{ $tipoSingular }} {{ $nombreLinea }}</div>
            <div class="machine-meta">
                Periodo {{ $fechaInicioPdf->format('d/m/Y') }} - {{ $fechaFinPdf->format('d/m/Y') }}
                | Estado: {{ $estadoGeneral }}
                | Ultimo analisis: {{ $formatDate(data_get($ultimoAnalisisLinea, 'fecha_analisis')) }}
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <div class="section-title">Indicadores principales</div>
            </div>
            <table class="metric-grid">
                <tr>
                <td class="metric-card">
                    <span class="kicker">Analisis</span>
                    <span class="kpi-value">{{ $analisisPlanos->count() }}</span>
                    <span class="kpi-note">Registros cargados</span>
                </td>
                <td class="metric-card">
                    <span class="kicker">Componentes</span>
                    <span class="kpi-value" style="font-size: 14px;">{{ data_get($resumen, 'componentes_revisados', 0) }} / {{ $totalComponentesLinea }}</span>
                    <span class="kpi-note">Cobertura {{ $formatNumber($coveragePercent(data_get($resumen, 'componentes_revisados', 0), $totalComponentesLinea), 1) }}%</span>
                </td>
                <td class="metric-card">
                    <span class="kicker">Criticos</span>
                    <span class="kpi-value">{{ data_get($resumen, 'componentes_criticos', 0) }}</span>
                    <span class="kpi-note">Cambio / accion</span>
                </td>
                <td class="metric-card">
                    <span class="kicker">Revision / desgaste</span>
                    <span class="kpi-value" style="font-size: 14px;">{{ data_get($resumen, 'componentes_revision', 0) }} / {{ data_get($resumen, 'componentes_severos_moderados', 0) }}</span>
                    <span class="kpi-note">Hallazgos no criticos</span>
                </td>
                <td class="metric-card">
                    <span class="kicker">{{ $esPasteurizadora ? 'Modulos' : 'Elongaciones' }}</span>
                    <span class="kpi-value" style="font-size: 14px;">
                        @if($esPasteurizadora)
                            {{ data_get($resumen, 'modulos_con_analisis', 0) }} / {{ data_get($resumen, 'total_modulos', 0) }}
                        @else
                            {{ $elongacionesLinea->count() }}
                        @endif
                    </span>
                    <span class="kpi-note">{{ $esPasteurizadora ? 'Avance por modulo' : 'Mediciones cadena' }}</span>
                </td>
                <td class="metric-card">
                    <span class="kicker">Estado</span>
                    <span class="kpi-value" style="font-size: 10px;"><span class="badge {{ $stateClass($estadoGeneral) }}">{{ $estadoGeneral }}</span></span>
                </td>
                </tr>
            </table>

            <div class="conclusion-box">
                <strong>Conclusion:</strong> {{ $lineConclusion($resumen, $analisisPlanos) }}
                @if($observacionesCount > 0)
                    <br><span class="muted">Observaciones documentadas en {{ $observacionesCount }} registro(s).</span>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <div class="section-title">Informacion de componentes</div>
                <div class="section-subtitle">Estado y avance por componente.</div>
            </div>

            @if($esPasteurizadora)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Componente</th>
                            <th>Cant.</th>
                            <th>Modulos</th>
                            <th>Total config.</th>
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
                                $avanceComponente = (float) data_get($componente, 'porcentaje', 0);
                            @endphp
                            <tr>
                                <td class="nowrap">{{ data_get($componente, 'codigo') }}</td>
                                <td><span class="strong">{{ data_get($componente, 'nombre') }}</span></td>
                                <td>{{ data_get($componente, 'cantidad', 0) }}</td>
                                <td>{{ data_get($componente, 'modulos_aplicables', 0) }}</td>
                                <td>{{ data_get($componente, 'total_configurado', 0) }}</td>
                                <td>{{ data_get($componente, 'cantidad_revisada', 0) }}</td>
                                <td>
                                    {{ $formatNumber($avanceComponente, 1) }}%
                                    <div class="progress"><div class="progress-fill" style="width: {{ min(100, max(0, $avanceComponente)) }}%;"></div></div>
                                </td>
                                <td>{{ data_get($componente, 'total_analisis_periodo', data_get($componente, 'total_analisis', 0)) }}</td>
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
                            <th>Analisis periodo</th>
                            <th>Analisis historico</th>
                            <th>Criticos periodo</th>
                            <th>Ultimo estado</th>
                            <th>Ubicacion</th>
                            <th>Ultima revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($componentStatsLavadora as $componente)
                            @php $ultimoEstado = $componente['ultimo_estado'] ?? 'Sin dato'; @endphp
                            <tr>
                                <td class="nowrap">{{ $componente['codigo'] }}</td>
                                <td><span class="strong">{{ $componente['nombre'] }}</span></td>
                                <td>{{ $componente['total_analisis_periodo'] }}</td>
                                <td>{{ $componente['total_analisis'] }}</td>
                                <td>{{ $componente['criticos'] }}</td>
                                <td><span class="badge {{ $stateClass($ultimoEstado) }}">{{ $ultimoEstado }}</span></td>
                                <td>{{ $componente['ubicacion'] ?: 'Sin dato' }}</td>
                                <td>{{ $formatDate($componente['ultima_fecha'] ?? null) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No hay componentes configurados para esta linea.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if($esPasteurizadora && collect($lineaReporte['modulos'] ?? [])->isNotEmpty())
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Avance por modulo</div>
                    <div class="section-subtitle">Cobertura mecanica por modulo, niveles y lados inspeccionados.</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Modulo</th>
                            <th>Componentes</th>
                            <th>Avance</th>
                            <th>Analisis</th>
                            <th>Criticos</th>
                            <th>Ultima revision</th>
                            <th>Niveles</th>
                            <th>Lados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($lineaReporte['modulos'] ?? []) as $modulo)
                            @php $avanceModulo = (float) data_get($modulo, 'porcentaje', 0); @endphp
                            <tr>
                                <td><span class="strong">{{ data_get($modulo, 'numero') }}</span></td>
                                <td>{{ data_get($modulo, 'componentes_revisados', 0) }} / {{ data_get($modulo, 'total_componentes', 0) }}</td>
                                <td>
                                    {{ $formatNumber($avanceModulo, 1) }}%
                                    <div class="progress"><div class="progress-fill" style="width: {{ min(100, max(0, $avanceModulo)) }}%;"></div></div>
                                </td>
                                <td>{{ data_get($modulo, 'total_analisis', 0) }}</td>
                                <td>{{ data_get($modulo, 'criticos', 0) }}</td>
                                <td>{{ $formatDate(data_get($modulo, 'ultima_revision')) }}</td>
                                <td>{{ $formatValue(data_get($modulo, 'niveles', [])) }}</td>
                                <td>{{ $formatValue(data_get($modulo, 'lados', [])) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!$esPasteurizadora && $reductoresLinea->isNotEmpty())
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Resumen por reductor</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reductor</th>
                            <th>Analisis</th>
                            <th>Ultima revision</th>
                            <th>Referencia elongacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reductoresLinea as $reductor)
                            <tr>
                                <td><span class="strong">{{ data_get($reductor, 'nombre', 'Sin reductor') }}</span></td>
                                <td>{{ data_get($reductor, 'total_analisis', 0) }}</td>
                                <td>{{ $formatDate(data_get($reductor, 'ultima_fecha')) }}</td>
                                <td>{{ $formatValue(data_get($reductor, 'ultima_elongacion')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!$esPasteurizadora && $elongacionesLinea->isNotEmpty())
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Analisis de elongacion</div>
                    <div class="section-subtitle">Mediciones de cadena asociadas a la linea.</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Linea</th>
                            <th>Bombas prom.</th>
                            <th>Bombas %</th>
                            <th>Vapor prom.</th>
                            <th>Vapor %</th>
                            <th>Estado</th>
                            <th>Hodometro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($elongacionesLinea as $elongacion)
                            @php
                                $estadoElongacion = data_get($elongacion, 'estado_detallado', data_get($elongacion, 'estado', data_get($elongacion, 'requiere_cambio') ? 'Requiere cambio' : 'Normal'));
                            @endphp
                            <tr>
                                <td>{{ $formatDateTime(data_get($elongacion, 'created_at')) }}</td>
                                <td>{{ data_get($elongacion, 'linea', $nombreLinea) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'bombas_promedio', data_get($elongacion, 'promedio_bombas'))) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'bombas_porcentaje')) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'vapor_promedio', data_get($elongacion, 'promedio_vapor'))) }}</td>
                                <td>{{ $formatValue(data_get($elongacion, 'vapor_porcentaje')) }}</td>
                                <td><span class="badge {{ $stateClass($estadoElongacion) }}">{{ $estadoElongacion }}</span></td>
                                <td>{{ $formatValue(data_get($elongacion, 'hodometro')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($parosLinea->isNotEmpty())
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Paros de mantenimiento</div>
                    <div class="section-subtitle">Eventos de paro que cruzan el periodo del reporte.</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Tipo</th>
                            <th>Duracion estimada</th>
                            <th>Supervisor</th>
                            <th>Planes pendientes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parosLinea as $paro)
                            @php
                                $horasParo = data_get($paro, 'fecha_inicio') && data_get($paro, 'fecha_fin')
                                    ? (\Carbon\Carbon::parse(data_get($paro, 'fecha_inicio'))->startOfDay()->diffInDays(\Carbon\Carbon::parse(data_get($paro, 'fecha_fin'))->startOfDay()) + 1) * 24
                                    : 0;
                            @endphp
                            <tr>
                                <td>{{ $formatDate(data_get($paro, 'fecha_inicio')) }}</td>
                                <td>{{ $formatDate(data_get($paro, 'fecha_fin')) }}</td>
                                <td>{{ $formatValue(data_get($paro, 'tipo')) }}</td>
                                <td>{{ $horasParo }} h</td>
                                <td>{{ $formatValue(data_get($paro, 'supervisor.name')) }}</td>
                                <td>{{ data_get($resumen, 'planes_pendientes', 0) }}</td>
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
                <div class="section-header">
                    <div class="section-title">Seguimiento automatico de tendencia</div>
                    <div class="section-subtitle">Comparativos 52-12-4 y 30-14-7 tendencia de daños.</div>
                </div>

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
                                <th>Lados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ventanas52124Pdf as $ventana)
                                @php $ladosVentanaPdf = data_get($ventana, 'current_lados', []); @endphp
                                <tr>
                                    <td>52-12-4</td>
                                    <td>{{ data_get($ventana, 'label') }}</td>
                                    <td>{{ data_get($ventana, 'current_range', '-') }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'current')) }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'previous')) }}</td>
                                    <td>{{ (($delta = (int) data_get($ventana, 'delta', 0)) > 0 ? '+' : '') . $delta }}</td>
                                    <td>
                                        @if(!empty($ladosVentanaPdf))
                                            V: {{ data_get($ladosVentanaPdf, 'VAPOR', 0) }} / P: {{ data_get($ladosVentanaPdf, 'PASILLO', 0) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @foreach($ventanas30147Pdf as $ventana)
                                @php $ladosVentanaPdf = data_get($ventana, 'current_lados', []); @endphp
                                <tr>
                                    <td>30-14-7</td>
                                    <td>{{ data_get($ventana, 'label') }}</td>
                                    <td>{{ data_get($ventana, 'current_range', '-') }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'current')) }}</td>
                                    <td>{{ $formatValue(data_get($ventana, 'previous')) }}</td>
                                    <td>{{ (($delta = (int) data_get($ventana, 'delta', 0)) > 0 ? '+' : '') . $delta }}</td>
                                    <td>
                                        @if(!empty($ladosVentanaPdf))
                                            V: {{ data_get($ladosVentanaPdf, 'VAPOR', 0) }} / P: {{ data_get($ladosVentanaPdf, 'PASILLO', 0) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if($tendenciaRows->isNotEmpty())
                    <table class="data-table" style="margin-top: 7px;">
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
                            @foreach($tendenciaRows as $tendencia)
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
            <div class="section-header">
                <div class="section-title">Detalle de analisis y evidencias</div>
            </div>

            @forelse($analisisPlanos as $registro)
                @php
                    $imagenes = $getImages($registro);
                    $estado = data_get($registro, 'estado', 'Sin estado');
                    $componenteCodigo = $esPasteurizadora
                        ? data_get($registro, 'componente', data_get($registro, 'componente_codigo'))
                        : $getComponentCodigo($registro);

                    if (is_array($componenteCodigo) || is_object($componenteCodigo)) {
                        $componenteCodigo = data_get($componenteCodigo, 'codigo', $formatValue($componenteCodigo));
                    }

                    $componenteNombre = data_get($registro, 'componente.nombre', $componentNameMap->get($componenteCodigo, $componenteCodigo));
                    $responsableRegistro = data_get($registro, 'responsable', data_get($registro, 'usuario.name', 'Sin dato'));
                    $analysisFacts = [];

                    if ($esPasteurizadora) {
                        $ubicacion = collect([
                            data_get($registro, 'modulo'),
                            data_get($registro, 'nivel'),
                            data_get($registro, 'lado'),
                        ])
                            ->filter(fn ($value) => $hasUsefulValue($value))
                            ->map(fn ($value) => $formatValue($value))
                            ->implode(' / ');

                        $resolucion = collect([
                            'Fecha' => data_get($registro, 'fecha_resolucion'),
                            'Nota' => data_get($registro, 'nota_resolucion'),
                        ])
                            ->filter(fn ($value) => $hasUsefulValue($value))
                            ->map(fn ($value, $label) => $label . ': ' . $formatValue($value))
                            ->implode(' | ');

                        $planesPcm = collect([
                            'PCM1' => data_get($registro, 'plan_accion_pcm1'),
                            'PCM2' => data_get($registro, 'plan_accion_pcm2'),
                            'PCM3' => data_get($registro, 'plan_accion_pcm3'),
                            'PCM4' => data_get($registro, 'plan_accion_pcm4'),
                        ])
                            ->filter(fn ($value) => $hasUsefulValue($value))
                            ->map(fn ($value, $label) => $label . ': ' . $formatValue($value))
                            ->implode(' | ');

                        $analysisFacts = array_values(array_filter([
                            $makeFact('Componente', trim($componenteNombre . ' (' . $componenteCodigo . ')')),
                            $makeFact('Ubicacion', $ubicacion),
                            $makeFact('Orden', data_get($registro, 'numero_orden')),
                            $makeFact('Responsable', $responsableRegistro),
                            $makeFact('Cantidad revisada', data_get($registro, 'cantidad_componentes_revisados')),
                            $makeFact('Resuelto por cambio', data_get($registro, 'resuelto_por_cambio')),
                            $makeFact('Actividad', data_get($registro, 'actividad'), true),
                            $makeFact('Observaciones', data_get($registro, 'observaciones'), true),
                            $makeFact('Resolucion', $resolucion, true),
                            $makeFact('Planes PCM', $planesPcm, true),
                        ]));
                    } else {
                        $analysisFacts = array_values(array_filter([
                            $makeFact('Componente', trim($componenteNombre . ' (' . ($componenteCodigo ?: 'N/A') . ')')),
                            $makeFact('Reductor', data_get($registro, 'reductor')),
                            $makeFact('Lado', data_get($registro, 'lado')),
                            $makeFact('Orden', data_get($registro, 'numero_orden')),
                            $makeFact('Responsable', $responsableRegistro),
                            $makeFact('Registrado', data_get($registro, 'created_at')),
                            $makeFact('Actividad', data_get($registro, 'actividad'), true),
                        ]));
                    }
                @endphp
                <div class="analysis-card">
                    <div class="analysis-head">
                        <div class="analysis-title">
                            Analisis #{{ data_get($registro, 'id', 'N/A') }}
                            | {{ $formatDate(data_get($registro, 'fecha_analisis')) }}
                            | <span class="badge {{ $stateClass($estado) }}">{{ $estado }}</span>
                        </div>
                    </div>
                    <div class="analysis-body">
                        <div class="analysis-core">
                            @if(count($analysisFacts) > 0)
                                @php $factsList = collect($analysisFacts)->values(); @endphp
                                <table class="fact-table">
                                    @for($factIndex = 0; $factIndex < $factsList->count(); $factIndex++)
                                        @php
                                            $fact = $factsList->get($factIndex);
                                            $nextFact = null;

                                            if (!$fact['wide'] && $factIndex + 1 < $factsList->count()) {
                                                $candidateFact = $factsList->get($factIndex + 1);

                                                if (!$candidateFact['wide']) {
                                                    $nextFact = $candidateFact;
                                                    $factIndex++;
                                                }
                                            }
                                        @endphp
                                        @if($fact['wide'])
                                            <tr>
                                                <td class="fact-label-cell">{{ $fact['label'] }}</td>
                                                <td class="fact-value-cell" colspan="3">{{ $fact['value'] }}</td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td class="fact-label-cell">{{ $fact['label'] }}</td>
                                                <td class="fact-value-cell">{{ $fact['value'] }}</td>
                                                @if($nextFact)
                                                    <td class="fact-label-cell">{{ $nextFact['label'] }}</td>
                                                    <td class="fact-value-cell">{{ $nextFact['value'] }}</td>
                                                @else
                                                    <td class="fact-empty-cell"></td>
                                                    <td class="fact-empty-cell"></td>
                                                @endif
                                            </tr>
                                        @endif
                                    @endfor
                                </table>
                            @else
                                <div class="empty small">El registro solo contiene fecha y estado.</div>
                            @endif
                        </div>

                        <div class="evidence-grid">
                            <div class="evidence-title">Evidencias fotograficas{{ count($imagenes) > 0 ? ' (' . count($imagenes) . ')' : '' }}</div>
                            @if(count($imagenes) > 0)
                                @foreach($imagenes as $index => $foto)
                                    @php $src = $imageDataUri($foto); @endphp
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
                                <span class="muted">Sin evidencias.</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">No hay analisis registrados para esta maquina en el periodo seleccionado.</div>
            @endforelse
        </div>

        <div class="line-footer">
            <table>
                <tr>
                    <td>
                        @if($platformLogo)
                            <img src="{{ $platformLogo }}" class="footer-logo" alt="{{ $platformName }}">
                        @endif
                        <span class="strong">{{ $companyName }}</span>
                    </td>
                    <td style="text-align: right;">
                        {{ $tipoSingular }} {{ $nombreLinea }} | {{ $platformName }} | {{ $documentCode }}
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
