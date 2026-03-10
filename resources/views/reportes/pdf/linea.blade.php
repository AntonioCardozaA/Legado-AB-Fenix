<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte {{ $reporte['linea']->nombre }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            font-size: 24px;
            color: #2563eb;
            margin: 0;
        }
        .header p {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #2563eb;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 8px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-yellow {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-green {
            background: #dcfce7;
            color: #166534;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE LAVADORA</h1>
        <p>{{ $reporte['linea']->nombre }}</p>
        <p>Período: {{ $reporte['fecha_inicio']->format('d/m/Y') }} - {{ $reporte['fecha_fin']->format('d/m/Y') }}</p>
        <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Resumen -->
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-label">Total Análisis</div>
            <div class="stat-value">{{ $reporte['resumen']['total_analisis'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Total Paros</div>
            <div class="stat-value">{{ $reporte['resumen']['total_paros'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Horas Paro</div>
            <div class="stat-value">{{ number_format($reporte['resumen']['horas_paro'], 1) }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Componentes</div>
            <div class="stat-value">{{ $reporte['resumen']['componentes_revisados'] }}</div>
        </div>
    </div>

    <!-- Análisis de Elongación -->
    <div class="section">
        <h2 class="section-title">ANÁLISIS DE ELONGACIÓN</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Componente</th>
                    <th>Elongación (mm)</th>
                    <th>Horómetro</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['analisis'] as $analisis)
                <tr>
                    <td>{{ $analisis->fecha_analisis->format('d/m/Y') }}</td>
                    <td>{{ $analisis->componente->nombre ?? 'N/A' }}</td>
                    <td>{{ number_format($analisis->elongacion_promedio, 2) }}</td>
                    <td>{{ number_format($analisis->horometro) }}</td>
                    <td>
                        @php
                            $badgeClass = $analisis->elongacion_promedio > 178.19 ? 'badge-red' : 
                                         ($analisis->elongacion_promedio > 176 ? 'badge-yellow' : 'badge-green');
                            $texto = $analisis->elongacion_promedio > 178.19 ? 'CRÍTICO' : 
                                    ($analisis->elongacion_promedio > 176 ? 'ATENCIÓN' : 'NORMAL');
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $texto }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Componentes -->
    <div class="section">
        <h2 class="section-title">ESTADO DE COMPONENTES</h2>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            @foreach($reporte['componentes'] as $compData)
            <div class="card">
                <h3 style="margin: 0 0 5px 0; color: #2563eb;">{{ $compData['componente']->nombre }}</h3>
                <p style="margin: 2px 0;">Total: {{ $compData['componente']->cantidad_total }}</p>
                <p style="margin: 2px 0;">Análisis: {{ $compData['total_analisis'] }}</p>
                @if($compData['ultimo_estado'])
                <p style="margin: 2px 0;">
                    Último estado: 
                    <strong style="color: {{ $compData['ultimo_estado'] == 'MALO' ? '#dc2626' : '#16a34a' }}">
                        {{ $compData['ultimo_estado'] }}
                    </strong>
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Paros -->
    <div class="section">
        <h2 class="section-title">PAROS DE MANTENIMIENTO</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha Inicio</th>
                    <th>Tipo</th>
                    <th>Duración (h)</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['paros'] as $paro)
                <tr>
                    <td>{{ $paro->fecha_inicio->format('d/m/Y H:i') }}</td>
                    <td>{{ $paro->tipo }}</td>
                    <td>{{ $paro->duracion_horas }}</td>
                    <td>{{ $paro->motivo }}</td>
                    <td>
                        @if($paro->planesAccion->where('estado', 'COMPLETADA')->count() > 0)
                            <span style="color: #16a34a;">Completado</span>
                        @else
                            <span style="color: #ca8a04;">En proceso</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Este reporte es generado automáticamente por el sistema de mantenimiento.</p>
        <p>Documento confidencial - Solo para uso interno</p>
    </div>
</body>
</html>