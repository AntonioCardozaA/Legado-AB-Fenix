<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Análisis de Componentes</title>
    <style>
        /* Estilos generales */
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
        }
        
        /* Encabezado */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .logo-container {
            width: 20%;
        }
        
        .logo {
            max-height: 60px;
            max-width: 150px;
        }
        
        .title-container {
            width: 60%;
            text-align: center;
        }
        
        .title-container h1 {
            color: #1e40af;
            margin: 0;
            font-size: 18px;
        }
        
        .title-container .subtitle {
            color: #6b7280;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .meta-info {
            width: 20%;
            text-align: right;
            font-size: 9px;
            color: #4b5563;
        }
        
        /* Filtros aplicados */
        .filtros-section {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }
        
        .filtros-section h3 {
            color: #374151;
            margin: 0 0 8px 0;
            font-size: 11px;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
        }
        
        .filtro-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filtro-label {
            font-weight: bold;
            color: #4b5563;
            min-width: 70px;
        }
        
        .filtro-value {
            color: #1f2937;
        }
        
        /* Tablas por lavadora */
        .lavadora-section {
            page-break-inside: avoid;
            margin-bottom: 25px;
        }
        
        .lavadora-header {
            background: linear-gradient(to right, #3b82f6, #1d4ed8);
            color: white;
            padding: 8px 12px;
            border-radius: 4px 4px 0 0;
            margin-bottom: 0;
        }
        
        .lavadora-header h2 {
            margin: 0;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .lavadora-stats {
            display: flex;
            gap: 15px;
            margin-top: 5px;
            font-size: 9px;
            opacity: 0.9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            font-size: 9px;
        }
        
        th {
            background-color: #e5e7eb;
            color: #374151;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
        }
        
        td {
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }
        
        /* Colores de estado */
        .estado-ok {
            background-color: #d1fae5;
            color: #065f46;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }
        
        .estado-warning {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }
        
        .estado-danger {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }
        
        /* Resumen al final */
        .resumen-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .resumen-item {
            text-align: center;
        }
        
        .resumen-number {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }
        
        .resumen-label {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        /* Pie de página */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-10 { margin-bottom: 10px; }
        .mt-10 { margin-top: 10px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <div class="logo-container">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" class="logo" alt="Logo">
            @else
                <div style="font-size: 16px; font-weight: bold; color: #3b82f6;">LOGO EMPRESA</div>
            @endif
        </div>
        
        <div class="title-container">
            <h1>REPORTE DE ANÁLISIS DE COMPONENTES</h1>
            <div class="subtitle">Sistema de Monitoreo y Mantenimiento de Lavadoras</div>
        </div>
        
        <div class="meta-info">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            Página: <span class="page-number"></span>
        </div>
    </div>

    <!-- Filtros aplicados -->
    @if(isset($filtros) && (array_filter($filtros)))
    <div class="filtros-section">
        <h3>📊 FILTROS APLICADOS</h3>
        <div class="filtros-grid">
            @if($filtros['fecha'])
                <div class="filtro-item">
                    <span class="filtro-label">Período:</span>
                    <span class="filtro-value">{{ date('F Y', strtotime($filtros['fecha'] . '-01')) }}</span>
                </div>
            @endif
            
            @if($filtros['linea'])
                <div class="filtro-item">
                    <span class="filtro-label">Lavadora:</span>
                    <span class="filtro-value">{{ $filtros['linea'] }}</span>
                </div>
            @endif
            
            @if($filtros['componente'])
                <div class="filtro-item">
                    <span class="filtro-label">Componente:</span>
                    <span class="filtro-value">{{ $filtros['componente'] }}</span>
                </div>
            @endif
            
            @if($filtros['reductor'])
                <div class="filtro-item">
                    <span class="filtro-label">Reductor:</span>
                    <span class="filtro-value">{{ $filtros['reductor'] }}</span>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Contenido por lavadora -->
    @forelse($analisisAgrupados as $lavadora => $items)
        @php
            $estadisticas = [
                'total' => $items->count(),
                'ok' => $items->where('estado', 'Buen estado')->count(),
                'warning' => $items->filter(fn($item) => 
                    str_contains($item->estado ?? '', 'Desgaste')
                )->count(),
                'danger' => $items->filter(fn($item) => 
                    str_contains($item->estado ?? '', 'Dañado')
                )->count(),
            ];
        @endphp
        
        <div class="lavadora-section">
            <div class="lavadora-header">
                <h2>🏭 LAVADORA: {{ $lavadora }}</h2>
                <div class="lavadora-stats">
                    <span>✅ Buen estado: {{ $estadisticas['ok'] }}</span>
                    <span>⚠️ Desgaste: {{ $estadisticas['warning'] }}</span>
                    <span>❌ Dañados: {{ $estadisticas['danger'] }}</span>
                    <span>📊 Total: {{ $estadisticas['total'] }}</span>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Componente</th>
                        <th width="10%">Reductor</th>
                        <th width="12%">Fecha Análisis</th>
                        <th width="10%">N° Orden</th>
                        <th width="18%">Estado</th>
                        <th width="30%">Actividad / Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        @php
                            $estadoClass = 'estado-ok';
                            if (str_contains($item->estado ?? '', 'Desgaste')) {
                                $estadoClass = 'estado-warning';
                            } elseif (str_contains($item->estado ?? '', 'Dañado')) {
                                $estadoClass = 'estado-danger';
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->componente->nombre ?? 'N/A' }}</td>
                            <td>{{ $item->reductor }}</td>
                            <td>{{ $item->fecha_analisis->format('d/m/Y') }}</td>
                            <td class="text-center">{{ $item->numero_orden }}</td>
                            <td>
                                <span class="{{ $estadoClass }}">
                                    {{ $item->estado ?? 'Buen estado' }}
                                </span>
                            </td>
                            <td>{!! nl2br(e($item->actividad)) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if(!$loop->last)
            <div style="margin: 15px 0; border-top: 1px dashed #d1d5db;"></div>
        @endif
    @empty
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <h3>No se encontraron análisis con los filtros aplicados</h3>
            <p>No hay datos para mostrar en el reporte.</p>
        </div>
    @endforelse

    <!-- Resumen final -->
    @if($analisisAgrupados->isNotEmpty())
        @php
            $totalAnalisis = 0;
            $totalOk = 0;
            $totalWarning = 0;
            $totalDanger = 0;
            
            foreach($analisisAgrupados as $items) {
                $totalAnalisis += $items->count();
                $totalOk += $items->where('estado', 'Buen estado')->count();
                $totalWarning += $items->filter(fn($item) => 
                    str_contains($item->estado ?? '', 'Desgaste')
                )->count();
                $totalDanger += $items->filter(fn($item) => 
                    str_contains($item->estado ?? '', 'Dañado')
                )->count();
            }
        @endphp
        
        <div class="resumen-section">
            <h3 style="text-align: center; color: #1e40af; margin-bottom: 15px;">
                📋 RESUMEN GENERAL
            </h3>
            
            <div class="resumen-grid">
                <div class="resumen-item">
                    <div class="resumen-number">{{ $totalAnalisis }}</div>
                    <div class="resumen-label">Total de Análisis</div>
                </div>
                
                <div class="resumen-item">
                    <div class="resumen-number" style="color: #10b981;">{{ $totalOk }}</div>
                    <div class="resumen-label">✅ Buen Estado</div>
                </div>
                
                <div class="resumen-item">
                    <div class="resumen-number" style="color: #f59e0b;">{{ $totalWarning }}</div>
                    <div class="resumen-label">⚠️ Desgaste</div>
                </div>
                
                <div class="resumen-item">
                    <div class="resumen-number" style="color: #ef4444;">{{ $totalDanger }}</div>
                    <div class="resumen-label">❌ Dañados</div>
                </div>
                
                <div class="resumen-item">
                    <div class="resumen-number">{{ $analisisAgrupados->count() }}</div>
                    <div class="resumen-label">Lavadoras Analizadas</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Pie de página -->
    <div class="footer">
        <div>
            Reporte generado automáticamente por el Sistema de Análisis de Componentes |
            {{ config('app.name', 'Laravel') }} |
            Página <span class="page-number"></span>
        </div>
        <div style="margin-top: 5px;">
            © {{ date('Y') }} - Todos los derechos reservados
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size);
            
            // Números de página en encabezado y pie
            $pdf->page_text(30, 20, "Reporte de Análisis de Componentes", $font, 9);
            $pdf->page_text($pdf->get_width() - 50, 20, date('d/m/Y'), $font, 9);
        }
    </script>
</body>
</html>