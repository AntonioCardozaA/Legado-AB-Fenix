<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analisis {{ $analisis->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .section { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Analisis #{{ $analisis->id }}</h1>
    <div class="muted">Generado {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <tr><th>Linea</th><td>{{ optional($analisis->linea)->nombre ?? 'Sin linea' }}</td></tr>
        <tr><th>Componente</th><td>{{ optional($analisis->componente)->nombre ?? 'Sin componente' }}</td></tr>
        <tr><th>Fecha</th><td>{{ optional($analisis->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</td></tr>
        <tr><th>Numero de orden</th><td>{{ $analisis->numero_orden ?? 'N/A' }}</td></tr>
        <tr><th>Reductor</th><td>{{ $analisis->reductor ?? 'N/A' }}</td></tr>
        <tr><th>Categoria</th><td>{{ optional($analisis->categoria)->nombre ?? 'N/A' }}</td></tr>
        <tr><th>Numero R</th><td>{{ optional($analisis->numeroR)->codigo ?? 'N/A' }}</td></tr>
    </table>

    <div class="section">
        <h2>Actividad</h2>
        <p>{{ $analisis->actividad }}</p>
    </div>

    @if($analisis->observaciones)
        <div class="section">
            <h2>Observaciones</h2>
            <p>{{ $analisis->observaciones }}</p>
        </div>
    @endif
</body>
</html>
