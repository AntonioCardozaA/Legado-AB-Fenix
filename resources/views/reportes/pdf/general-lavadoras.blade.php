<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte general</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .muted { color: #6b7280; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Reporte general de lavadoras</h1>
    <div class="muted">
        Periodo: {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Linea</th>
                <th>Total analisis</th>
                <th>Componentes revisados</th>
                <th>Criticos</th>
                <th>Elongaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($reporte['lineas'] ?? []) as $lineaReporte)
                <tr>
                    <td>{{ optional($lineaReporte['linea'] ?? null)->nombre ?? 'Sin linea' }}</td>
                    <td>{{ $lineaReporte['resumen']['total_analisis'] ?? 0 }}</td>
                    <td>{{ $lineaReporte['resumen']['componentes_revisados'] ?? 0 }}</td>
                    <td>{{ $lineaReporte['resumen']['componentes_criticos'] ?? 0 }}</td>
                    <td>{{ isset($lineaReporte['elongaciones']) ? $lineaReporte['elongaciones']->count() : 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay datos para el periodo seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
