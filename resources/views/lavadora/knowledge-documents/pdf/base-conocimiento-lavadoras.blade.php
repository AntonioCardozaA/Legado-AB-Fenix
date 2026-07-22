@php
    $generatedAt = $knowledge['generated_at'] ?? now();
    $overview = $knowledge['overview'] ?? [];
    $formatDate = function ($value, $withTime = false) {
        if (!$value) {
            return 'Sin registro';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        }

        try {
            return \Carbon\Carbon::parse($value)->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $formatMoney = fn ($value) => '$' . number_format((float) $value, 2, '.', ',');
    $formatValue = fn ($value, $fallback = 'Sin dato') => filled($value) || $value === 0 || $value === '0' ? (string) $value : $fallback;
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $knowledge['title'] }}</title>
    <style>
        @page { margin: 42px 24px 36px 24px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5px; color: #1f2937; line-height: 1.42; margin: 0; }
        .page-header, .page-footer { position: fixed; left: 0; right: 0; color: #4b5563; }
        .page-header { top: -28px; border-bottom: 1px solid #d1d5db; padding-bottom: 6px; font-size: 8px; }
        .page-footer { bottom: -24px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8px; }
        .page-number:after { content: counter(page); }
        h1 { font-size: 22px; margin: 0 0 6px 0; color: #0f172a; }
        h2 { font-size: 14px; margin: 18px 0 8px 0; color: #0f172a; padding-bottom: 4px; border-bottom: 2px solid #0f766e; }
        h3 { font-size: 11px; margin: 12px 0 6px 0; color: #111827; }
        .muted { color: #6b7280; }
        .small { font-size: 8px; }
        .cover { border: 1px solid #d1d5db; padding: 18px; margin-bottom: 16px; }
        .summary-table, .data-table, .detail-table { width: 100%; border-collapse: collapse; }
        .summary-table td { width: 25%; border: 1px solid #d1d5db; background: #f8fafc; padding: 8px; vertical-align: top; }
        .summary-label { display: block; text-transform: uppercase; font-size: 7px; color: #64748b; letter-spacing: .6px; }
        .summary-value { display: block; margin-top: 2px; font-size: 16px; font-weight: bold; color: #0f766e; }
        .pill { display: inline-block; border-radius: 999px; padding: 2px 6px; font-size: 7px; font-weight: bold; background: #e2e8f0; color: #0f172a; }
        .section-card { border: 1px solid #e5e7eb; padding: 10px 12px; margin-bottom: 10px; }
        .bullet-list { margin: 0; padding-left: 14px; }
        .bullet-list li { margin-bottom: 4px; }
        .data-table th { background: #ecfeff; color: #134e4a; border: 1px solid #99f6e4; padding: 5px; text-align: left; font-size: 7.8px; text-transform: uppercase; }
        .data-table td { border: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
        .detail-table td { border: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
        .detail-table .label { width: 22%; background: #f8fafc; font-weight: bold; color: #334155; }
        .two-col { width: 100%; border-collapse: separate; border-spacing: 12px 0; }
        .two-col td { width: 50%; vertical-align: top; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .state-grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .state-grid td { width: 20%; border: 1px solid #d1d5db; padding: 6px; background: #f8fafc; vertical-align: top; }
        .line-card { border: 1px solid #cbd5e1; margin-bottom: 10px; page-break-inside: avoid; }
        .line-head { background: #f8fafc; border-bottom: 1px solid #cbd5e1; padding: 8px 10px; }
        .line-body { padding: 10px; }
        .tag { display: inline-block; margin-right: 4px; margin-bottom: 4px; padding: 2px 6px; border: 1px solid #cbd5e1; border-radius: 999px; font-size: 7.5px; color: #334155; }
    </style>
</head>
<body>
    <div class="page-header">
        {{ $knowledge['project_name'] }} | {{ $knowledge['title'] }} | Generado {{ $formatDate($generatedAt, true) }}
    </div>
    <div class="page-footer">
        Documento tecnico interno | Pagina <span class="page-number"></span>
    </div>

    <div class="cover">
        <div class="pill">Base maestra para IA y consulta tecnica</div>
        <h1>{{ $knowledge['title'] }}</h1>
        <div class="muted">
            Documento consolidado del modulo lavadora con reglas tecnicas, configuracion de cadenas,
            componentes, estado operativo, evidencias y arquitectura del asistente.
        </div>
        <div class="muted small" style="margin-top: 6px;">
            Generado: {{ $formatDate($generatedAt, true) }}
        </div>

        <div style="height: 12px;"></div>

        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">Lineas activas</span>
                    <span class="summary-value">{{ $overview['lineas_activas'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Analisis</span>
                    <span class="summary-value">{{ $overview['analisis_registrados'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Eventos</span>
                    <span class="summary-value">{{ $overview['eventos_mantenimiento'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Planes</span>
                    <span class="summary-value">{{ $overview['planes_accion'] ?? 0 }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="summary-label">Elongaciones</span>
                    <span class="summary-value">{{ $overview['elongaciones'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Fotos</span>
                    <span class="summary-value">{{ $overview['fotos_registradas'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Docs de conocimiento</span>
                    <span class="summary-value">{{ $overview['documentos_conocimiento'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Chunks</span>
                    <span class="summary-value">{{ $overview['fragmentos_conocimiento'] ?? 0 }}</span>
                </td>
            </tr>
        </table>
    </div>

    <h2>Alcance</h2>
    <div class="section-card">
        <ul class="bullet-list">
            @foreach($knowledge['scope'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>

    <h2>Mapa del Modulo</h2>
    @foreach($knowledge['module_map'] as $module)
        <div class="section-card">
            <h3>{{ $module['name'] }}</h3>
            <div>{{ $module['purpose'] }}</div>
            <table class="detail-table" style="margin-top: 8px;">
                <tr>
                    <td class="label">Rutas / entradas</td>
                    <td>{{ implode(', ', $module['routes']) }}</td>
                </tr>
                <tr>
                    <td class="label">Fuentes de datos</td>
                    <td>{{ implode(', ', $module['sources']) }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <h2>Modelo de Datos Relevante</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tabla</th>
                <th>Proposito</th>
                <th>Relacion clave</th>
                <th>Campos utiles</th>
            </tr>
        </thead>
        <tbody>
            @foreach($knowledge['data_model'] as $entity)
                <tr>
                    <td class="mono"><strong>{{ $entity['table'] }}</strong></td>
                    <td>{{ $entity['purpose'] }}</td>
                    <td>{{ $entity['relationship'] }}</td>
                    <td>{{ implode(', ', $entity['fields']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Arquitectura del Asistente</h2>
    <div class="section-card">
        <ul class="bullet-list">
            @foreach($knowledge['assistant_architecture'] as $step)
                <li><strong>{{ $step['step'] }}:</strong> {{ $step['detail'] }}</li>
            @endforeach
        </ul>
    </div>

    <h2>Reglas Tecnicas</h2>
    <table class="two-col">
        <tr>
            <td>
                <div class="section-card">
                    <h3>Disparadores por analisis</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Condicion</th>
                                <th>Evento</th>
                                <th>Severidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($knowledge['technical_rules']['analysis_rules'] as $rule)
                                <tr>
                                    <td>{{ $rule['condition'] }}</td>
                                    <td class="mono">{{ $rule['event_type'] }}</td>
                                    <td>{{ $rule['severity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
            <td>
                <div class="section-card">
                    <h3>Reglas de elongacion</h3>
                    <table class="detail-table">
                        <tr>
                            <td class="label">Formula</td>
                            <td class="mono">{{ $knowledge['technical_rules']['elongation_rules']['formula'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Umbral preventivo</td>
                            <td>{{ $knowledge['technical_rules']['elongation_rules']['warning_threshold'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Umbral critico</td>
                            <td>{{ $knowledge['technical_rules']['elongation_rules']['critical_threshold'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Delta tendencia</td>
                            <td>{{ $knowledge['technical_rules']['elongation_rules']['trend_min_delta'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Rodaja max mm</td>
                            <td>{{ $formatValue($knowledge['technical_rules']['elongation_rules']['rodaja_max_mm'], 'No configurado') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Revision</td>
                            <td>
                                Cada {{ $knowledge['technical_rules']['revision_schedule']['interval_months'] }} meses,
                                alerta {{ $knowledge['technical_rules']['revision_schedule']['lead_days'] }} dias antes,
                                zona {{ $knowledge['technical_rules']['revision_schedule']['timezone'] }}.
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Contexto IA</td>
                            <td>
                                Max chars: {{ $knowledge['technical_rules']['knowledge_rules']['plan_context_chars'] }} |
                                Chunks: {{ $knowledge['technical_rules']['knowledge_rules']['plan_knowledge_chunks'] }} |
                                Historial chat: {{ $knowledge['technical_rules']['knowledge_rules']['chat_history_window'] }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <h2>Configuracion de Cadena</h2>
    @foreach($knowledge['chain_groups'] as $group)
        <div class="section-card">
            <h3>{{ $group['chain_type'] }}</h3>
            <div class="muted">Lineas: {{ implode(', ', $group['lineas']) }}</div>
            <table class="data-table" style="margin-top: 8px;">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Material</th>
                        <th>Cantidad</th>
                        <th>Descripcion tecnica</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['items'] as $item)
                        <tr>
                            <td class="mono">{{ $item['sku'] }}</td>
                            <td>{{ $item['nombre'] }}</td>
                            <td>{{ $item['cantidad'] }}</td>
                            <td>{{ $item['descripcion'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <h2>Perfil por Linea</h2>
    @foreach($knowledge['line_profiles'] as $profile)
        <div class="line-card">
            <div class="line-head">
                <strong>{{ $profile['linea'] }}</strong>
                <span class="tag">Paso inicial {{ $profile['paso_inicial'] }}</span>
                <span class="tag">{{ $profile['grupo_cadena'] }}</span>
                <span class="tag">Diagrama {{ $profile['diagrama'] }}</span>
            </div>
            <div class="line-body">
                <table class="detail-table">
                    <tr>
                        <td class="label">Analisis / componentes</td>
                        <td>{{ $profile['analisis_registrados'] }} analisis | {{ $profile['componentes_distintos'] }} componentes</td>
                        <td class="label">Eventos / planes</td>
                        <td>{{ $profile['eventos_registrados'] }} eventos | {{ $profile['planes_registrados'] }} planes</td>
                    </tr>
                    <tr>
                        <td class="label">Ultima revision</td>
                        <td>{{ $profile['ultima_revision_componentes'] }}</td>
                        <td class="label">Ultima elongacion</td>
                        <td>{{ $profile['ultima_elongacion'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Estado elongacion</td>
                        <td>{{ $formatValue($profile['estado_elongacion_actual']) }}</td>
                        <td class="label">Max elongacion actual</td>
                        <td>{{ $formatValue($profile['max_elongacion_actual']) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Ciclo activo</td>
                        <td colspan="3">
                            @if($profile['ciclo_activo'])
                                {{ $profile['ciclo_activo']['codigo'] }}
                                | ciclo {{ $profile['ciclo_activo']['numero_ciclo'] }}
                                | proveedor {{ $formatValue($profile['ciclo_activo']['proveedor']) }}
                                | instalada {{ $profile['ciclo_activo']['instalada_en'] }}
                                | hodometro inicial {{ $formatValue($profile['ciclo_activo']['hodometro_inicial']) }}
                            @else
                                Sin ciclo activo registrado.
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Componentes clave</td>
                        <td colspan="3">
                            @forelse($profile['componentes_clave'] as $component)
                                <span class="tag">{{ $component['nombre'] }} ({{ $component['total'] }})</span>
                            @empty
                                Sin historial suficiente.
                            @endforelse
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

    <h2>Catalogo Tecnico de Componentes</h2>
    <table class="two-col">
        <tr>
            <td>
                <div class="section-card">
                    <h3>Familias detectadas</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Familia</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($knowledge['component_catalog']['families'] as $family)
                                <tr>
                                    <td>{{ $family['family'] }}</td>
                                    <td>{{ $family['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
            <td>
                <div class="section-card">
                    <h3>Grupos mas frecuentes</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($knowledge['component_catalog']['groups'] as $group)
                                <tr>
                                    <td>{{ $group['grupo'] }}</td>
                                    <td>{{ $group['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-card">
        <h3>Top componentes revisados</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Codigo</th>
                    <th>Revisiones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge['component_catalog']['top_analyzed'] as $component)
                    <tr>
                        <td>{{ $component['componente'] }}</td>
                        <td class="mono">{{ $formatValue($component['codigo']) }}</td>
                        <td>{{ $component['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Estado Operativo</h2>
    <table class="state-grid">
        <tr>
            <td>
                <strong>Estados de analisis</strong>
                <div class="small" style="margin-top: 6px;">
                    @foreach($knowledge['operational_state']['analysis_states'] as $state)
                        {{ $state['estado'] }}: {{ $state['total'] }}<br>
                    @endforeach
                </div>
            </td>
            <td>
                <strong>Estados de evento</strong>
                <div class="small" style="margin-top: 6px;">
                    @foreach($knowledge['operational_state']['event_statuses'] as $state)
                        {{ $state['estado'] }}: {{ $state['total'] }}<br>
                    @endforeach
                </div>
            </td>
            <td>
                <strong>Tipos de evento</strong>
                <div class="small" style="margin-top: 6px;">
                    @foreach($knowledge['operational_state']['event_types'] as $item)
                        {{ $item['tipo'] }}: {{ $item['total'] }}<br>
                    @endforeach
                </div>
            </td>
            <td>
                <strong>Estados de plan</strong>
                <div class="small" style="margin-top: 6px;">
                    @foreach($knowledge['operational_state']['plan_statuses'] as $state)
                        {{ $state['estado'] }}: {{ $state['total'] }}<br>
                    @endforeach
                </div>
            </td>
            <td>
                <strong>Origen del plan</strong>
                <div class="small" style="margin-top: 6px;">
                    @foreach($knowledge['operational_state']['plan_sources'] as $source)
                        {{ $source['source'] }}: {{ $source['total'] }}<br>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    <h2>Resumen de Costos</h2>
    <div class="section-card">
        <div>
            <strong>Entradas:</strong> {{ $knowledge['cost_summary']['entries'] }}
            | <strong>Monto total:</strong> {{ $formatMoney($knowledge['cost_summary']['total_amount']) }}
        </div>
        <table class="data-table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th>Origen</th>
                    <th>Entradas</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge['cost_summary']['sources'] as $source)
                    <tr>
                        <td>{{ $source['source'] }}</td>
                        <td>{{ $source['entries'] }}</td>
                        <td>{{ $formatMoney($source['total_cost']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Evidencia Fotografica</h2>
    <div class="section-card">
        <div>
            <strong>Analisis con fotos:</strong> {{ $knowledge['evidence_summary']['analyses_with_photos'] }}
            | <strong>Total de fotos:</strong> {{ $knowledge['evidence_summary']['photo_count'] }}
        </div>
        <table class="data-table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th>Linea</th>
                    <th>Analisis con evidencia</th>
                    <th>Fotos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge['evidence_summary']['by_line'] as $item)
                    <tr>
                        <td>{{ $item['linea'] }}</td>
                        <td>{{ $item['analisis'] }}</td>
                        <td>{{ $item['fotos'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section-card">
        <h3>Evidencias recientes referenciadas</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Analisis</th>
                    <th>Linea</th>
                    <th>Componente</th>
                    <th>Estado</th>
                    <th>Fotos</th>
                    <th>Archivos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge['evidence_summary']['recent_evidence'] as $item)
                    <tr>
                        <td>#{{ $item['analisis_id'] }}<br><span class="small">{{ $item['fecha'] }}</span></td>
                        <td>{{ $item['linea'] }}</td>
                        <td>{{ $formatValue($item['componente']) }}</td>
                        <td>{{ $formatValue($item['estado']) }}</td>
                        <td>{{ $item['photo_count'] }}</td>
                        <td class="small">{{ implode(', ', $item['photos']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Inventario de Conocimiento</h2>
    <div class="section-card">
        <div>
            <strong>Documentos:</strong> {{ $knowledge['knowledge_inventory']['documents_total'] }}
            | <strong>Indexados:</strong> {{ $knowledge['knowledge_inventory']['indexed_documents'] }}
            | <strong>Vigentes:</strong> {{ $knowledge['knowledge_inventory']['vigent_documents'] }}
            | <strong>Chunks:</strong> {{ $knowledge['knowledge_inventory']['total_chunks'] }}
        </div>
        <table class="data-table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Tipo</th>
                    <th>Linea</th>
                    <th>Componente</th>
                    <th>Estado</th>
                    <th>Indexacion</th>
                    <th>Chunks</th>
                    <th>Cargado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge['knowledge_inventory']['documents'] as $document)
                    <tr>
                        <td>{{ $document['title'] }}</td>
                        <td>{{ $document['type'] }}</td>
                        <td>{{ $formatValue($document['linea'], 'General') }}</td>
                        <td>{{ $formatValue($document['componente']) }}</td>
                        <td>{{ $document['status'] }}</td>
                        <td>{{ $document['indexing_status'] }}</td>
                        <td>{{ $document['chunks'] }}</td>
                        <td>{{ $document['uploaded_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
