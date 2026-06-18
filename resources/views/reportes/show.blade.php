@extends('layouts.app')

@php
    $esPasteurizadora = ($tipoEquipo ?? 'lavadoras') === 'pasteurizadoras';
    $nombreEquipoPlural = $esPasteurizadora ? 'Pasteurizadoras' : 'Lavadoras';
    $iconoEquipo = $esPasteurizadora ? 'fa-temperature-half' : 'fa-droplet';
    $logoEquipo = $esPasteurizadora ? asset('images/icono_pas.png') : asset('images/icono-maquina.png');
    $claseEquipo = $esPasteurizadora ? 'pasteurizadora' : 'lavadora';
    $lineaActual = $lineaId ? ($reporte['linea'] ?? null) : null;
    $tituloReporte = $lineaActual
        ? 'Reporte Detallado - ' . $lineaActual->nombre
        : 'Reporte General de ' . $nombreEquipoPlural;
    $periodoReporte = $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y');
    $estadoGeneral = $lineaActual ? ($reporte['resumen']['estado_general'] ?? null) : null;
    $estadoTexto = $estadoGeneral['texto'] ?? ($lineaActual ? 'SIN DATOS' : 'GENERAL');
    $estadoColor = $estadoGeneral['color'] ?? 'blue';
    $totalLineas = $lineaActual ? 1 : collect($reporte['lineas'] ?? [])->count();

    $exportParams = [
        'tipo' => $tipoEquipo,
        'fecha_inicio' => $fechaInicio->format('Y-m-d'),
        'fecha_fin' => $fechaFin->format('Y-m-d'),
        'export_tipo' => $lineaId ? 'linea' : 'completo',
    ];

    if ($lineaId) {
        $exportParams['lineaId'] = $lineaId;
        $exportParams['linea_id'] = $lineaId;
    }
@endphp

@section('title', $tituloReporte)

@section('content')

<style>
    .report-show {
        --report-blue: #2563eb;
        --report-blue-soft: #eff6ff;
        --report-blue-border: #bfdbfe;
        --report-green: #047857;
        --report-green-soft: #ecfdf5;
        --report-green-border: #bbf7d0;
        --report-red: #b91c1c;
        --report-red-soft: #fef2f2;
        --report-amber: #b45309;
        --report-amber-soft: #fffbeb;
        --report-purple: #6d28d9;
        --report-purple-soft: #f5f3ff;
        --report-slate: #0f172a;
        --report-muted: #64748b;
        --report-border: #e5e7eb;
        max-width: 1280px;
        margin: 0 auto;
    }

    .report-toolbar {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 24px;
    }

    .report-back {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        color: #1d4ed8;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        gap: 8px;
        padding: 10px 14px;
        text-decoration: none;
        transition: background-color .2s ease, color .2s ease;
    }

    .report-back:hover {
        background: #dbeafe;
        color: #1e40af;
    }

    .report-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .report-action {
        align-items: center;
        border-radius: 8px;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        gap: 8px;
        justify-content: center;
        min-height: 42px;
        padding: 10px 14px;
        text-decoration: none;
        transition: background-color .2s ease, filter .2s ease;
    }

    .report-action:hover {
        filter: brightness(.96);
    }

    .report-action.pdf {
        background: #dc2626;
        color: #ffffff;
    }

    .report-hero {
        background: #ffffff;
        border: 1px solid var(--report-border);
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .report-hero-main {
        align-items: center;
        background: linear-gradient(to right, #f9fafb, #ffffff);
        display: grid;
        gap: 18px;
        grid-template-columns: minmax(0, 1fr) auto;
        padding: 24px;
    }

    .report-heading {
        align-items: center;
        display: flex;
        gap: 16px;
        min-width: 0;
    }

    .report-logo {
        align-items: center;
        background: var(--report-blue-soft);
        border: 1px solid var(--report-blue-border);
        border-radius: 8px;
        display: flex;
        height: 58px;
        justify-content: center;
        padding: 8px;
        width: 58px;
        flex: 0 0 auto;
    }

    .report-logo.pasteurizadora {
        background: var(--report-green-soft);
        border-color: var(--report-green-border);
    }

    .report-logo img {
        height: 100%;
        object-fit: contain;
        width: 100%;
    }

    .report-kicker {
        color: var(--report-muted);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .report-title {
        color: var(--report-slate);
        font-size: clamp(24px, 2.4vw, 30px);
        font-weight: 700;
        line-height: 1.1;
        margin: 3px 0 0;
    }

    .report-subtitle {
        color: var(--report-muted);
        font-size: 14px;
        font-weight: 400;
        margin-top: 7px;
    }

    .report-status {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 600;
        gap: 8px;
        justify-content: center;
        padding: 9px 13px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .report-status.green { background: var(--report-green-soft); color: var(--report-green); }
    .report-status.red { background: var(--report-red-soft); color: var(--report-red); }
    .report-status.orange,
    .report-status.yellow { background: var(--report-amber-soft); color: var(--report-amber); }
    .report-status.blue { background: var(--report-blue-soft); color: var(--report-blue); }

    .report-hero-strip {
        background: #ffffff;
        border-top: 1px solid var(--report-border);
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .report-mini-stat {
        align-items: center;
        border-right: 1px solid var(--report-border);
        display: flex;
        gap: 12px;
        min-width: 0;
        padding: 14px 18px;
    }

    .report-mini-stat:last-child {
        border-right: 0;
    }

    .report-mini-icon {
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--report-border);
        border-radius: 8px;
        color: var(--report-blue);
        display: flex;
        height: 38px;
        justify-content: center;
        width: 38px;
        flex: 0 0 auto;
    }

    .report-mini-label {
        color: var(--report-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .report-mini-value {
        color: var(--report-slate);
        font-size: 15px;
        font-weight: 700;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .report-section-stack {
        display: grid;
        gap: 20px;
    }

    .report-line-shell {
        min-width: 0;
    }

    .report-line-divider {
        align-items: center;
        color: #374151;
        display: flex;
        font-size: 16px;
        font-weight: 600;
        gap: 10px;
        margin: 8px 0 14px;
    }

    .report-line-divider span {
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--report-border);
        border-radius: 12px;
        display: inline-flex;
        gap: 8px;
        padding: 8px 12px;
    }

    .report-line-divider:after {
        background: var(--report-border);
        content: '';
        height: 1px;
        flex: 1;
    }

    .modal-overlay {
        align-items: center;
        background: rgba(15, 23, 42, .62);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 18px;
        position: fixed;
        z-index: 9999;
    }

    .modal-content {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, .2);
        max-height: 90vh;
        max-width: 1040px;
        overflow: auto;
        padding: 20px;
        width: min(96vw, 1040px);
    }

    .modal-header {
        align-items: center;
        border-bottom: 1px solid var(--report-border);
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
    }

    .modal-header h2 {
        color: var(--report-slate);
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .modal-close {
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #334155;
        cursor: pointer;
        display: flex;
        height: 36px;
        justify-content: center;
        width: 36px;
    }

    .modal-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .modal-tabs button {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        color: #1d4ed8;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 12px;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .modal-content table {
        border-collapse: collapse;
        width: 100%;
    }

    .modal-content th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        padding: 10px;
        text-align: left;
        text-transform: uppercase;
    }

    .modal-content td {
        border-top: 1px solid #e2e8f0;
        padding: 10px;
    }

    @media (max-width: 900px) {
        .report-toolbar,
        .report-hero-main {
            align-items: stretch;
            grid-template-columns: 1fr;
        }

        .report-toolbar {
            flex-direction: column;
        }

        .report-actions,
        .report-back {
            width: 100%;
        }

        .report-action {
            flex: 1 1 150px;
        }

        .report-hero-strip {
            grid-template-columns: 1fr;
        }

        .report-mini-stat {
            border-right: 0;
            border-bottom: 1px solid var(--report-border);
        }

        .report-mini-stat:last-child {
            border-bottom: 0;
        }
    }

    @media (max-width: 640px) {
        .report-heading {
            align-items: flex-start;
        }

        .report-logo {
            height: 48px;
            width: 48px;
        }
    }
</style>

<div class="report-show">
    <div class="report-toolbar">
        <a href="{{ route('reportes.index', ['tipo' => $tipoEquipo, 'fecha_inicio' => $fechaInicio->format('Y-m-d'), 'fecha_fin' => $fechaFin->format('Y-m-d')]) }}" class="report-back">
            <i class="fas fa-arrow-left"></i>
            Volver a reportes
        </a>

        <div class="report-actions">
            <a href="{{ route('reportes.export-pdf', array_merge($exportParams, ['export_format' => 'pdf'])) }}" class="report-action pdf">
                <i class="fas fa-file-pdf"></i>
                Exportar PDF
            </a>
        </div>
    </div>

    <header class="report-hero">
        <div class="report-hero-main">
            <div class="report-heading">
                <div class="report-logo {{ $claseEquipo }}">
                    <img src="{{ $logoEquipo }}" alt="{{ $nombreEquipoPlural }}">
                </div>
                <div>
                    <div class="report-kicker">
                        Reporte de {{ $nombreEquipoPlural }}
                    </div>
                    <h1 class="report-title">{{ $tituloReporte }}</h1>
                    <div class="report-subtitle">
                        Sistema Legado Ave Fenix
                    </div>
                </div>
            </div>

            <span class="report-status {{ $estadoColor }}">
                <i class="fas {{ $lineaActual ? 'fa-circle-check' : 'fa-layer-group' }}"></i>
                {{ $estadoTexto }}
            </span>
        </div>

        <div class="report-hero-strip">
            <div class="report-mini-stat">
                <div class="report-mini-icon"><i class="fas fa-calendar-days"></i></div>
                <div>
                    <div class="report-mini-label">Periodo</div>
                    <div class="report-mini-value">{{ $periodoReporte }}</div>
                </div>
            </div>
            <div class="report-mini-stat">
                <div class="report-mini-icon"><i class="fas {{ $iconoEquipo }}"></i></div>
                <div>
                    <div class="report-mini-label">Tipo de equipo</div>
                    <div class="report-mini-value">{{ $nombreEquipoPlural }}</div>
                </div>
            </div>
            <div class="report-mini-stat">
                <div class="report-mini-icon"><i class="fas fa-diagram-project"></i></div>
                <div>
                    <div class="report-mini-label">{{ $lineaActual ? 'Linea' : 'Lineas ' }}</div>
                    <div class="report-mini-value">{{ $lineaActual?->nombre ?? $totalLineas }}</div>
                </div>
            </div>
        </div>
    </header>

    <div class="report-section-stack">
        @if($lineaId)
            <div class="report-line-shell">
                @include($esPasteurizadora ? 'reportes.partials.reporte-linea-pasteurizadora' : 'reportes.partials.reporte-linea-lavadora', ['reporte' => $reporte])
            </div>
        @else
            @foreach($reporte['lineas'] as $reporteLinea)
                <section class="report-line-shell">
                    <div class="report-line-divider">
                        <span>
                            <i class="fas {{ $iconoEquipo }}"></i>
                            {{ $reporteLinea['linea']->nombre ?? 'Linea' }}
                        </span>
                    </div>

                    @include($esPasteurizadora ? 'reportes.partials.reporte-linea-pasteurizadora' : 'reportes.partials.reporte-linea-lavadora', ['reporte' => $reporteLinea])
                </section>
            @endforeach
        @endif
    </div>
</div>

<div id="modalLavadora" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Detalles</h2>
            <button type="button" onclick="cerrarModal()" class="modal-close" aria-label="Cerrar">&times;</button>
        </div>

        <div class="modal-tabs">
            <button type="button" onclick="mostrarTab('analisis')">Analisis</button>
            <button type="button" onclick="mostrarTab('historial')">Historial</button>
            <button type="button" onclick="mostrarTab('componentes')">Componentes</button>
            <button type="button" onclick="mostrarTab('grafica')">Tendencia</button>
        </div>

        <div id="tab-analisis" class="tab-content"></div>
        <div id="tab-historial" class="tab-content"></div>
        <div id="tab-componentes" class="tab-content"></div>

        <div id="tab-grafica" class="tab-content">
            <canvas id="graficaTendencia"></canvas>
        </div>
    </div>
</div>

<script>
function abrirModal(titulo) {
    document.getElementById('modalTitulo').innerText = titulo;
    document.getElementById('modalLavadora').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalLavadora').style.display = 'none';
}

function mostrarTab(tab) {
    document.querySelectorAll('.tab-content').forEach(function(content) {
        content.classList.remove('active');
    });

    const currentTab = document.getElementById('tab-' + tab);

    if (currentTab) {
        currentTab.classList.add('active');
    }
}

function verTodosAnalisis(datos) {
    let tabla = '<table><tr><th>Fecha</th><th>Componente</th><th>Estado</th></tr>';

    datos.forEach(function(item) {
        tabla += '<tr><td>' + (item.fecha_analisis || '') + '</td><td>' + (item.componente?.nombre || '') + '</td><td>' + (item.estado || '') + '</td></tr>';
    });

    tabla += '</table>';

    document.getElementById('tab-analisis').innerHTML = tabla;
    abrirModal('Todos los analisis');
    mostrarTab('analisis');
}

function verHistorial(datos) {
    let tabla = '<table><tr><th>Fecha</th><th>Evento</th></tr>';

    datos.forEach(function(item) {
        tabla += '<tr><td>' + (item.fecha || '') + '</td><td>' + (item.descripcion || '') + '</td></tr>';
    });

    tabla += '</table>';

    document.getElementById('tab-historial').innerHTML = tabla;
    abrirModal('Historial');
    mostrarTab('historial');
}

function verComponentes(datos) {
    let tabla = '<table><tr><th>Componente</th><th>Total analisis</th><th>Estado</th></tr>';

    datos.forEach(function(item) {
        tabla += '<tr><td>' + (item.nombre || '') + '</td><td>' + (item.total_analisis || 0) + '</td><td>' + (item.ultimo_estado || 'Sin datos') + '</td></tr>';
    });

    tabla += '</table>';

    document.getElementById('tab-componentes').innerHTML = tabla;
    abrirModal('Componentes');
    mostrarTab('componentes');
}

function mostrarGrafica(datos) {
    abrirModal('Tendencia');
    mostrarTab('grafica');

    const ctx = document.getElementById('graficaTendencia');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.meses,
            datasets: [{
                label: 'Danos',
                data: datos.valores,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.2)',
                fill: true
            }]
        },
        options: {
            responsive: true
        }
    });
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModal();
    }
});
</script>

@endsection
