<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans; font-size: 11px; }
        h2 { margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #eee; }
        .logo { height: 50px; }
    </style>
</head>
<body>

<img src="{{ public_path('img/logo.png') }}" class="logo">

@foreach($analisisAgrupados as $lavadora => $items)
    <h2>{{ $lavadora }}</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Componente</th>
                <th>Reductor</th>
                <th>Fecha</th>
                <th>N° Orden</th>
                <th>Actividad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->componente->nombre ?? 'N/A' }}</td>
                    <td>{{ $item->reductor }}</td>
                    <td>{{ $item->fecha_analisis->format('d/m/Y') }}</td>
                    <td>{{ $item->numero_orden }}</td>
                    <td>{{ $item->actividad }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

</body>
</html>
