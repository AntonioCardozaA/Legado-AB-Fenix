<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tendencias por lineas 2025 vs 2026</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9px; }
        h1 { color: #1f2348; font-size: 19px; margin: 0 0 4px; }
        h2 { color: #1f2348; font-size: 13px; margin: 20px 0 8px; padding-bottom: 5px; border-bottom: 2px solid #1e40af; }
        h3 { color: #334155; font-size: 10px; margin: 14px 0 5px; }
        p { margin: 3px 0; }
        .subtitle { color: #64748b; font-size: 10px; }
        .meta { width: 100%; margin: 14px 0 12px; border-collapse: collapse; }
        .meta td { background: #f8fafc; border: 1px solid #dbe3ef; padding: 6px; vertical-align: top; }
        .meta strong { display: block; color: #475569; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        table.report { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .report th { background: #1f2348; color: #fff; font-weight: bold; padding: 5px 4px; text-align: left; }
        .report td { border: 1px solid #dbe3ef; padding: 4px; vertical-align: middle; }
        .report tr:nth-child(even) td { background: #f8fafc; }
        .report .number { text-align: right; }
        .report .line { font-weight: bold; }
        .report .focused td { background: #eff6ff; }
        .status-better { color: #047857; font-weight: bold; }
        .status-worse { color: #b91c1c; font-weight: bold; }
        .status-stable { color: #475569; font-weight: bold; }
        .status-empty { color: #94a3b8; }
        .monthly th, .monthly td { text-align: center; font-size: 7.5px; padding: 3px 2px; }
        .monthly th:first-child, .monthly td:first-child { width: 58px; text-align: left; }
        .empty { border: 1px solid #dbe3ef; color: #64748b; padding: 10px; }
        .footer { color: #94a3b8; font-size: 8px; margin-top: 16px; text-align: right; }
    </style>
</head>
<body>
    <h1>Tendencias por lineas 2025 vs 2026</h1>
    <p class="subtitle">Comparativo de LEF por lavadora y por linea completa. Menor LEF significa mejor resultado.</p>

    <table class="meta">
        <tr>
            <td><strong>Periodo</strong>{{ $payload['filters']['period'] }}</td>
            <td><strong>Busqueda</strong>{{ $payload['filters']['search'] }}</td>
            <td><strong>Resultado</strong>{{ $payload['filters']['status'] }}</td>
            <td><strong>Linea enfocada</strong>{{ $payload['focused_line_name'] ?: 'Ninguna' }}</td>
            <td><strong>Generado</strong>{{ $payload['generated_at'] }}</td>
        </tr>
    </table>

    <h2>Tendencias por lineas 2025 vs 2026</h2>
    @if(count($payload['lines']))
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 16%;">Linea / lavadora</th>
                    <th>Mes referencia</th>
                    <th class="number">2025</th>
                    <th class="number">2026</th>
                    <th class="number">Cambio</th>
                    <th class="number">Variacion %</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['lines'] as $line)
                    @php
                        $status = $line['status'] ?? 'empty';
                        $variation = $line['variation'] ?? null;
                    @endphp
                    <tr class="{{ $payload['focused_line_id'] === ($line['linea_id'] ?? null) ? 'focused' : '' }}">
                        <td class="line">{{ $line['linea'] ?? 'Sin linea' }}</td>
                        <td>{{ data_get($line, 'latest_2026.month') ? ($payload['months'][data_get($line, 'latest_2026.month') - 1] ?? '-') : '-' }}</td>
                        <td class="number">{{ data_get($line, 'same_month_2025.value') !== null ? number_format((float) data_get($line, 'same_month_2025.value'), 2) : '—' }}</td>
                        <td class="number">{{ data_get($line, 'latest_2026.value') !== null ? number_format((float) data_get($line, 'latest_2026.value'), 2) : '—' }}</td>
                        <td class="number">{{ data_get($variation, 'delta') !== null ? number_format((float) data_get($variation, 'delta'), 2) : '—' }}</td>
                        <td class="number">{{ data_get($variation, 'formatted_percent') ?: '—' }}</td>
                        <td class="status-{{ $status }}">{{ ['better' => 'Baja / mejora', 'worse' => 'Sube / revisar', 'stable' => 'Sin cambio', 'empty' => 'Sin datos'][$status] ?? 'Sin datos' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No hay lineas que coincidan con los filtros seleccionados.</div>
    @endif

    <h2>Comparativo mensual por linea completa - {{ $payload['period_label'] }}</h2>
    @foreach([2025, 2026] as $year)
        <h3>Año {{ $year }}</h3>
        @if(count($payload['line_totals']))
            <table class="report monthly">
                <thead>
                    <tr>
                        <th>Linea</th>
                        @foreach($payload['months'] as $month)
                            <th>{{ $month }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['line_totals'] as $line)
                        <tr class="{{ $payload['focused_line_id'] === ($line['linea_id'] ?? null) ? 'focused' : '' }}">
                            <td class="line">{{ $line['linea'] ?? 'Sin linea' }}</td>
                            @foreach($line['series'][$year] ?? [] as $point)
                                <td>{{ $point['value'] !== null ? number_format((float) $point['value'], 2) : '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No hay datos de linea completa para los filtros seleccionados.</div>
        @endif
    @endforeach

    <p class="footer">Reporte generado desde LEGADO AB FENIX</p>
</body>
</html>
