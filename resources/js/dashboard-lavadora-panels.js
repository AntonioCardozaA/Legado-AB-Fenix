import { createVisibleChartRenderer } from './visible-chart.mjs';

(() => {
    const data = JSON.parse(document.getElementById('lavadoraPanelsData').textContent);

    const charts = {
        fallas: null,
        planes: null,
        elongaciones: null,
        historico: null
    };

    const state = {
        fallasFilter: 'all',
        rankingScope: 'all',
        rankingSort: 'puntaje',
        elongacionLineaId: data.elongaciones?.default_linea_id ?? data.lineas?.[0]?.id ?? null,
        historicoScope: 'Todas'
    };

    let layoutReady = false;
    let responsiveChartResizeBound = false;
    let resizeChartsTimer = null;
    const renderVisibleChart = createVisibleChartRenderer();

    window.dashboardLavadoraInitCharts = function () {
        if (!layoutReady) {
            setupLayout();
            layoutReady = true;
        }

        if (!responsiveChartResizeBound) {
            bindResponsiveChartResize();
            responsiveChartResizeBound = true;
        }

        safeRenderSection('fallas', renderFallas);
        safeRenderSection('planes', renderPlanes);
        safeRenderSection('ranking', renderRanking);
        safeRenderSection('elongaciones', renderElongaciones);
        safeRenderSection('historico', renderHistorico);
    };

    window.initCharts = window.dashboardLavadoraInitCharts;

    function safeRenderSection(sectionKey, renderFn) {
        try {
            renderFn();
        } catch (error) {
            console.error(`Error al renderizar ${sectionKey}`, error);
            renderSectionFallback(sectionKey, error);
        }
    }

    function renderSectionFallback(sectionKey, error) {
        switch (sectionKey) {
            case 'fallas':
                setChartState('fallas', true, 'Error al cargar fallas', 'No fue posible construir la grafica de fallas por linea.', 'fa-triangle-exclamation');
                break;
            case 'planes':
                setChartState('planes', true, 'Error al cargar planes', 'No fue posible construir el resumen de planes de accion.', 'fa-triangle-exclamation');
                break;
            case 'ranking': {
                const list = document.getElementById('rankingList');
                const footer = document.getElementById('rankingFooter');
                const empty = document.getElementById('rankingEmpty');

                hideLoader('rankingLoader');
                if (list) list.hidden = true;
                if (footer) footer.hidden = true;
                if (empty) {
                    empty.hidden = false;
                    empty.innerHTML = emptyMarkup('Error al cargar ranking', 'No fue posible construir el ranking de daños.', 'fa-triangle-exclamation');
                }
                break;
            }
            case 'elongaciones':
                charts.elongaciones = destroyChartForCanvas('elongacionesChart', charts.elongaciones);
                setChartState('elongaciones', true, 'Error al cargar elongaciones', 'No fue posible construir la grafica de elongaciones.', 'fa-triangle-exclamation');
                break;
            case 'historico': {
                const body = document.getElementById('historicoTableBody');
                const footer = body?.closest('.chart-card')?.querySelector('.table-footer');
                const reviewList = document.getElementById('historicoReviewList');

                if (body) {
                    body.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-6">No fue posible cargar el historico de revisiones.</td></tr>`;
                }

                if (reviewList) {
                    reviewList.innerHTML = `
                        <div class="historico-review-empty">
                            <i class="fas fa-triangle-exclamation"></i>
                            <span>No fue posible cargar el historico de revisiones.</span>
                        </div>
                    `;
                }

                if (footer) {
                    footer.innerHTML = '<i class="fas fa-triangle-exclamation"></i> No fue posible cargar la trazabilidad de revisiones.';
                }

                setChartState('historico', true, 'Error al cargar historico', 'No fue posible construir la tendencia mensual de revisiones.', 'fa-triangle-exclamation');
                break;
            }
            default:
                break;
        }
    }

    function setupLayout() {
        setupFallasCard();
        setupPlanesCard();
        setupRankingCard();
        setupElongacionesCard();
        setupHistoricoCard();
    }

    function setupFallasCard() {
        const card = cardFromCanvas('fallasChart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Daños o anomalias por linea', 'fas fa-chart-bar');
        ensureAfterHeading(card, 'fallasCopy', `<p id="fallasCopy" class="panel-copy"></p>`);
        ensureAfterElement('fallasCopy', 'fallasToolbar', `
            <div id="fallasToolbar" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <div class="filter-chip-group" id="fallasSeverityFilters">
                    <button type="button" class="filter-chip active" data-filter="all">Vista total</button>
                    <button type="button" class="filter-chip" data-filter="criticas">Críticas</button>
                    <button type="button" class="filter-chip" data-filter="requiere_revision">Requiere revisión</button>
                    <button type="button" class="filter-chip" data-filter="severas_moderadas">Severo / Moderado</button>
                </div>
            </div>
        `);
        ensureChartShell('fallasChart', 'fallas');

        const description = card.querySelector('.chart-description');
        if (description && !document.getElementById('fallasLegend')) {
            description.insertAdjacentHTML('beforebegin', `
                <div class="legend-inline" id="fallasLegend">
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(239, 68, 68, 0.92);"></span> Crítico</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(245, 158, 11, 0.92);"></span> Requiere revisión</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(249, 115, 22, 0.9);"></span> Severo / Moderado</span>
                </div>
            `);
            description.insertAdjacentHTML('afterend', `
                <div class="subpanel-title" style="margin-top: 18px;">Lavadoras con mayor impacto</div>
                <div class="linea-breakdown" id="fallasBreakdown"></div>
            `);
        }

        document.querySelectorAll('#fallasSeverityFilters .filter-chip').forEach((button) => {
            if (button.dataset.bound === 'true') return;
            button.dataset.bound = 'true';
            button.addEventListener('click', function () {
                state.fallasFilter = this.dataset.filter;
                document.querySelectorAll('#fallasSeverityFilters .filter-chip').forEach((item) => item.classList.toggle('active', item === this));
                renderFallas();
            });
        });
    }

    function setupPlanesCard() {
        const card = cardFromCanvas('componentesChart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Planes de accion', 'fas fa-clipboard-check');
        ensureAfterHeading(card, 'planesCopy', `<p id="planesCopy" class="panel-copy"></p>`);
        ensureAfterElement('planesCopy', 'planesActions', `
            <div id="planesActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                ${data.planesUrl ? `                <a href="${escapeHtml(data.planesUrl)}" class="panel-link">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    Ir al modulo
                </a>` : ''}
            </div>
        `);
        ensureAfterElement('planesActions', 'planesBanner', `<div id="planesBanner" class="status-banner estable"></div>`);
        ensureChartShell('componentesChart', 'planes', { compact: true });

        const description = card.querySelector('.chart-description');
        if (description) {
            description.innerHTML = '<i class="fas fa-info-circle"></i> Planes activos';
        }

        if (description && !document.getElementById('planesPriorityList')) {
            description.insertAdjacentHTML('afterend', `
                <div class="subpanel-title" style="margin-top: 18px;">Carga por lavadora</div>
                <div class="priority-list" id="planesPriorityList"></div>
                <div class="subpanel-title" style="margin-top: 18px;">Planes activos prioritarios</div>
                <div class="worklist" id="planesWorkList"></div>
            `);
        }
    }

    function setupRankingCard() {
        const list = document.getElementById('rankingList');
        if (!list) return;

        const card = list.closest('.chart-card');
        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Ranking de daños', 'fas fa-trophy');
        if (!document.getElementById('rankingLoader')) {
            list.insertAdjacentHTML('beforebegin', loaderMarkup('rankingLoader'));
            list.insertAdjacentHTML('beforebegin', `<div class="chart-empty-state" id="rankingEmpty" hidden></div>`);
        }
    }

    function setupElongacionesCard() {
        const card = cardFromCanvas('elongacionesChart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Evolucion de elongaciones', 'fas fa-chart-line');
        ensureAfterHeading(card, 'elongacionesCopy', `<p id="elongacionesCopy" class="panel-copy"></p>`);
        ensureAfterElement('elongacionesCopy', 'elongacionesActions', `
            <div id="elongacionesActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <select id="elongacionesLineaSelect" class="panel-select">${elongacionLineaOptions(state.elongacionLineaId)}</select>
            </div>
        `);
        ensureChartShell('elongacionesChart', 'elongaciones', { tall: true });

        const select = document.getElementById('elongacionesLineaSelect');
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state.elongacionLineaId = Number(this.value);
                renderElongaciones();
            });
        }
    }

    function setupHistoricoCard() {
        const body = document.getElementById('historicoTableBody');
        if (!body) return;

        const card = body.closest('.chart-card');
        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Historico de revisiones', 'fas fa-history');
        ensureAfterHeading(card, 'historicoCopy', `<p id="historicoCopy" class="panel-copy"></p>`);
        ensureAfterElement('historicoCopy', 'historicoActions', `
            <div id="historicoActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <select id="historicoScopeSelect" class="panel-select">
                    <option value="Todas">Todas las lavadoras</option>
                    ${data.lineas.map((linea) => `<option value="${escapeHtml(linea.nombre)}">${escapeHtml(linea.nombre)}</option>`).join('')}
                </select>
            </div>
        `);
        const tableWrapper = card.querySelector('.overflow-x-auto');
        if (tableWrapper && !document.getElementById('historicoChart')) {
            tableWrapper.insertAdjacentHTML('beforebegin', `
                <div class="chart-shell compact">
                    ${loaderMarkup('historicoLoader')}
                    <div class="chart-empty-state" id="historicoEmpty" hidden></div>
                    <div class="chart-container" data-chart-container="historico">
                        <canvas id="historicoChart"></canvas>
                    </div>
                </div>
                <div class="subpanel-title" style="margin-top: 18px;">Ultimas revisiones registradas</div>
                <div class="historico-review-list" id="historicoReviewList" aria-live="polite"></div>
            `);
        }

        const headerRow = card.querySelector('thead tr');
        if (headerRow) {
            headerRow.innerHTML = `
                <th><i class="fas fa-calendar-day" style="color: #3b82f6;"></i> Fecha</th>
                <th><i class="fas fa-industry" style="color: #2563eb;"></i> Lavadora</th>
                <th><i class="fas fa-cube" style="color: #0f172a;"></i> Componente</th>
                <th><i class="fas fa-location-dot" style="color: #7c3aed;"></i> Ubicacion</th>
                <th><i class="fas fa-signal" style="color: #ef4444;"></i> Estado</th>
                <th><i class="fas fa-user" style="color: #10b981;"></i> Revisión</th>
            `;
        }

        const select = document.getElementById('historicoScopeSelect');
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state.historicoScope = this.value;
                renderHistorico();
            });
        }
    }

    function setupDamageTrendCard(config) {
        const card = cardFromCanvas(config.cardId);
        if (!card) return;

        const preserveInputs = (config.preserveInputs || [])
            .map((input) => `<input type="hidden" name="${escapeHtml(input.name)}" value="${escapeHtml(input.value || '')}">`)
            .join('');

        card.querySelectorAll('.chart-description').forEach((node) => node.remove());
        Array.from(card.children)
            .filter((node) => node.tagName === 'DIV' && !node.classList.contains('chart-container') && !node.classList.contains('chart-shell') && node.id !== config.actionsId)
            .forEach((node) => node.remove());

        card.classList.add('dashboard-panel');
        updateCardTitle(card, config.title, config.icon);
        ensureAfterHeading(card, config.actionsId, `
            <form id="${config.actionsId}" class="panel-actions trend-filter-form" method="GET" action="${escapeHtml(data.dashboardUrl || '')}" style="margin-bottom: 18px; justify-content: flex-start;" data-auto-filter-form>
                <select id="${config.selectId}" class="panel-select">${lineaOptions(state[config.stateKey])}</select>
                <label class="trend-date-field">
                    <span>Desde</span>
                    <input type="date" name="${escapeHtml(config.filterFromName)}" value="${escapeHtml(config.filterFromValue || '')}" class="panel-date-input">
                </label>
                <label class="trend-date-field">
                    <span>Hasta</span>
                    <input type="date" name="${escapeHtml(config.filterToName)}" value="${escapeHtml(config.filterToValue || '')}" class="panel-date-input">
                </label>
                ${preserveInputs}
                <a href="${escapeHtml(data.dashboardUrl || '')}" class="panel-button">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
            </form>
        `);
        ensureAfterElement(config.actionsId, `${config.prefix}Executive`, `
            <div id="${config.prefix}Executive" class="trend-executive-shell">
                <div class="trend-executive-topbar">
                    <div id="${config.prefix}Caption" class="trend-executive-caption"></div>
                    <div class="trend-executive-view-selector">
                        <button type="button" class="trend-executive-view-btn active" data-trend-prefix="${config.prefix}" data-trend-type="bar">Barras</button>
                        <button type="button" class="trend-executive-view-btn" data-trend-prefix="${config.prefix}" data-trend-type="line">Linea</button>
                    </div>
                </div>
                <div class="trend-executive-brief">
                    <div id="${config.prefix}WindowCards" class="trend-executive-window-grid"></div>
                </div>
            </div>
        `);
        ensureChartShell(config.cardId, config.prefix, { tall: true });

        const shell = document.querySelector(`[data-chart-container="${config.prefix}"]`)?.closest('.chart-shell');
        if (shell && !document.getElementById(`${config.prefix}Details`)) {
            shell.insertAdjacentHTML('afterend', `
                <div id="${config.prefix}Details" class="trend-detail-panel">
                    <div class="trend-chart-grid two-up">
                        <div class="trend-mini-chart">
                            <div class="subpanel-title">${escapeHtml(config.analysisLabel || 'Analisis')} - componentes criticos</div>
                            <div class="trend-mini-chart-container horizontal tall">
                                <canvas id="${config.prefix}ComponentBar"></canvas>
                            </div>
                        </div>
                        <div class="trend-mini-chart">
                            <div class="subpanel-title">${escapeHtml(config.analysisLabel || 'Analisis')} - tipos de daño frecuentes</div>
                            <div class="trend-mini-chart-container horizontal tall">
                                <canvas id="${config.prefix}DamageBar"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="subpanel-title" style="display: none; margin-top: 18px;">Detalle por componente y daño</div>
                    <div class="overflow-x-auto trend-detail-table-wrap" style="display: none;">
                        <table class="trend-detail-table">
                            <thead>
                                <tr>
                                    <th>Componente</th>
                                    <th>Total</th>
                                    <th>Participacion</th>
                                    <th>Daño principal</th>
                                    <th>Ubicacion</th>
                                    <th>Mes actual</th>
                                    <th>Ultima falla</th>
                                </tr>
                            </thead>
                            <tbody id="${config.prefix}DetailTable"></tbody>
                        </table>
                    </div>
                    <div class="subpanel-title" style="display: none; margin-top: 18px;">Ultimos daños registrados</div>
                    <div class="worklist" id="${config.prefix}RecentEvents" style="display: none;"></div>
                    <div class="legend-inline" id="${config.prefix}Criteria"></div>
                </div>
            `);
        }

        const select = document.getElementById(config.selectId);
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state[config.stateKey] = Number(this.value);
                config.renderFn();
            });
        }

        document.querySelectorAll(`[data-trend-prefix="${config.prefix}"]`).forEach((button) => {
            if (button.dataset.bound === 'true') return;
            button.dataset.bound = 'true';
            button.addEventListener('click', function () {
                state[config.chartTypeKey] = this.dataset.trendType || 'bar';
                config.renderFn();
            });
        });
    }

    function renderFallas() {
        const stats = document.getElementById('fallasStats');
        const breakdown = document.getElementById('fallasBreakdown');
        const description = cardFromCanvas('fallasChart')?.querySelector('.chart-description');
        const rows = Array.isArray(data.fallas) ? [...data.fallas] : [];
        const hasData = rows.some((item) => Number(item.total_componentes || 0) > 0);

        if (!hasData) {
            if (stats) {
                stats.innerHTML = miniStats([
                    ['Lavadoras sin datos', '0', 'No hay analisis vigentes', 'info'],
                    ['Críticas', '0', 'Sin registros', 'danger'],
                    ['Requiere revisión', '0', 'Sin registros', 'revision'],
                    ['Severo / Moderado', '0', 'Sin registros', 'severo']
                ]);
            }
            if (breakdown) breakdown.innerHTML = infoBox('No hay datos disponibles para construir la matriz de fallas por linea.');
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> Sin datos vigentes';
            destroy(charts.fallas);
            setChartState('fallas', true, 'Sin datos de fallas', 'No existen componentes evaluados para mostrar la distribucion por linea.', 'fa-database');
            return;
        }

        const key = state.fallasFilter === 'criticas'
            ? 'criticas'
            : (state.fallasFilter === 'requiere_revision'
                ? 'requiere_revision'
                : (state.fallasFilter === 'severas_moderadas' ? 'severas_moderadas' : 'impactados'));
        const sorted = rows.slice().sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0) || Number(b.porcentaje_impacto || 0) - Number(a.porcentaje_impacto || 0));

        const criticas = rows.reduce((sum, item) => sum + Number(item.criticas || 0), 0);
        const revisiones = rows.reduce((sum, item) => sum + Number(item.requiere_revision || 0), 0);
        const warnings = rows.reduce((sum, item) => sum + Number(item.severas_moderadas || 0), 0);
        const impactadas = rows.filter((item) => Number(item.impactados || 0) > 0).length;
        const promedio = rows.length ? rows.reduce((sum, item) => sum + Number(item.porcentaje_impacto || 0), 0) / rows.length : 0;

        if (stats) {
            stats.innerHTML = miniStats([
                ['Lavadoras impactadas', impactadas, `${rows.length} monitoreadas`, 'info'],
                ['Fallas críticas', criticas, 'Rojo = requiere cambio', 'danger'],
                ['Requiere revisión', revisiones, 'Amarillo = validar componente', 'revision'],
                ['Severo / Moderado', warnings, 'Naranja = seguimiento', 'severo'],
                ['Impacto promedio', percent(promedio, 1), 'Sobre componentes vigentes', 'success']
            ]);
        }

        if (breakdown) {
            breakdown.innerHTML = sorted.slice(0, 5).map((item) => `
                <div class="breakdown-item">
                    <div class="breakdown-item-top">
                        <div>
                            <div class="breakdown-title">${escapeHtml(item.linea)}</div>
                            <div class="breakdown-meta">Críticas: ${Number(item.criticas || 0)} · Revisión: ${Number(item.requiere_revision || 0)} · Severo / Moderado: ${Number(item.severas_moderadas || 0)} · Última revisión: ${escapeHtml(item.ultima_revision_humana || 'Sin fecha')}</div>
                        </div>
                        <span class="severity-pill ${item.estado === 'critico' ? 'critico' : (item.estado === 'riesgo' ? 'severo' : (item.estado === 'operativo' ? 'revision' : 'estable'))}">${percent(item.porcentaje_impacto || 0, 1)}</span>
                    </div>
                    <div class="progress-track"><div class="progress-bar" style="width: ${Math.min(Number(item.porcentaje_impacto || 0), 100)}%;"></div></div>
                </div>
            `).join('');

            breakdown.querySelectorAll('.breakdown-item').forEach((node, index) => {
                const item = sorted[index] || {};
                const meta = node.querySelector('.breakdown-meta');
                const pill = node.querySelector('.severity-pill');

                if (meta) {
                    meta.textContent = `Críticas: ${Number(item.criticas || 0)} · Revisión: ${Number(item.requiere_revision || 0)} · Severo / Moderado: ${Number(item.severas_moderadas || 0)} · Última revisión: ${item.ultima_revision_humana || 'Sin fecha'}`;
                }

                if (pill) {
                    const tone = item.estado === 'critico'
                        ? 'critico'
                        : (item.estado === 'riesgo' ? 'severo' : (item.estado === 'operativo' ? 'revision' : 'estable'));
                    pill.className = `severity-pill ${tone}`;
                }
            });
        }

        if (description) {
            const datoClave = state.fallasFilter === 'criticas'
                ? `Críticas: ${criticas}`
                : (state.fallasFilter === 'requiere_revision'
                    ? `Revisión: ${revisiones}`
                    : (state.fallasFilter === 'severas_moderadas'
                        ? `Severo / Moderado: ${warnings}`
                        : `Impactadas: ${impactadas}`));
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${datoClave} · Impacto promedio: ${percent(promedio, 1)}`;
        }

        const singleDatasetMeta = {
            criticas: {
                label: 'Críticas',
                backgroundColor: 'rgba(239, 68, 68, 0.92)',
                borderColor: '#dc2626'
            },
            requiere_revision: {
                label: 'Requiere revisión',
                backgroundColor: 'rgba(245, 158, 11, 0.92)',
                borderColor: '#d97706'
            },
            severas_moderadas: {
                label: 'Severo / Moderado',
                backgroundColor: 'rgba(249, 115, 22, 0.9)',
                borderColor: '#ea580c'
            }
        };

        const datasets = state.fallasFilter === 'all'
            ? [
                { label: 'Críticas', data: sorted.map((item) => Number(item.criticas || 0)), backgroundColor: 'rgba(239, 68, 68, 0.92)', borderColor: '#dc2626', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Requiere revisión', data: sorted.map((item) => Number(item.requiere_revision || 0)), backgroundColor: 'rgba(245, 158, 11, 0.92)', borderColor: '#d97706', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Severo / Moderado', data: sorted.map((item) => Number(item.severas_moderadas || 0)), backgroundColor: 'rgba(249, 115, 22, 0.88)', borderColor: '#ea580c', borderWidth: 2, borderRadius: 10, borderSkipped: false }
            ]
            : [{
                label: (singleDatasetMeta[state.fallasFilter] || singleDatasetMeta.severas_moderadas).label,
                data: sorted.map((item) => Number(item[state.fallasFilter] || 0)),
                backgroundColor: (singleDatasetMeta[state.fallasFilter] || singleDatasetMeta.severas_moderadas).backgroundColor,
                borderColor: (singleDatasetMeta[state.fallasFilter] || singleDatasetMeta.severas_moderadas).borderColor,
                borderWidth: 2,
                borderRadius: 10,
                borderSkipped: false
            }];

        destroy(charts.fallas);
        setChartState('fallas', false);
        charts.fallas = new Chart(document.getElementById('fallasChart').getContext('2d'), {
            type: 'bar',
            data: { labels: sorted.map((item) => item.linea), datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { stacked: state.fallasFilter === 'all', grid: { display: false }, ticks: { color: '#334155', font: { size: 12, weight: 700 } } },
                    y: { beginAtZero: true, stacked: state.fallasFilter === 'all', grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                },
                plugins: {
                    datalabels: { display: false },
                    legend: { display: state.fallasFilter === 'all', labels: { usePointStyle: true, padding: 16, color: '#334155', font: { size: 11, weight: 700 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            afterBody: (context) => {
                                const item = sorted[context[0].dataIndex];
                                return [
                                    `Críticas: ${Number(item.criticas || 0)}`,
                                    `Requiere revisión: ${Number(item.requiere_revision || 0)}`,
                                    `Severo / Moderado: ${Number(item.severas_moderadas || 0)}`,
                                    `Impactados: ${Number(item.impactados || 0)}`,
                                    `Impacto: ${percent(item.porcentaje_impacto || 0, 1)}`,
                                    `Última revisión: ${item.ultima_revision_humana || 'Sin fecha'}`
                                ];
                            },
                            afterBody: (context) => {
                                const item = sorted[context[0].dataIndex];
                                return [
                                    `Impactados: ${Number(item.impactados || 0)}`,
                                    `Impacto: ${percent(item.porcentaje_impacto || 0, 1)}`,
                                    `Ultima revision: ${item.ultima_revision_humana || 'Sin fecha'}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    function renderPlanes() {
        const summary = data.planes?.resumen || {};
        const status = data.planes?.estado_general || {};
        const porLinea = Array.isArray(data.planes?.por_linea) ? data.planes.por_linea : [];
        const plans = Array.isArray(data.planes?.planes) ? data.planes.planes : [];
        const stats = document.getElementById('planesStats');
        const banner = document.getElementById('planesBanner');
        const priority = document.getElementById('planesPriorityList');
        const work = document.getElementById('planesWorkList');
        const description = cardFromCanvas('componentesChart')?.querySelector('.chart-description');

        if (banner) {
            banner.className = `status-banner ${status.nivel || 'estable'}`;
            banner.innerHTML = `<i class="fas fa-shield-heart"></i><span>${escapeHtml(status.label || 'Controlado')} · Pendientes: ${Number(summary.pendientes || 0)} · Vencidos: ${Number(summary.vencidos || 0)}</span>`;
        }

        if (stats) {
            stats.innerHTML = miniStats([
                ['Activos', Number(summary.activos || 0), `${Number(summary.vencidos || 0)} vencidos`, 'danger'],
                ['Pendientes', Number(summary.pendientes || 0), `${Number(summary.proximos_7_dias || 0)} proximos a vencer`, 'warning'],
                ['Completados', Number(summary.completados || 0), `${Number(summary.avance || 0)}% avance global`, 'success'],
                ['Alta prioridad', Number(summary.prioridad_alta || 0), `${Number(summary.lineas_comprometidas || 0)} lavadoras comprometidas`, 'info']
            ]);
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> Avance: ${Number(summary.avance || 0)}% · Planes: ${Number(summary.total || 0)}`;
        }

        if (!Number(summary.total || 0)) {
            if (priority) priority.innerHTML = infoBox('No hay planes registrados para las lavadoras seleccionadas.');
            if (work) work.innerHTML = infoBox('Sin actividades abiertas.');
            destroy(charts.planes);
            setChartState('planes', true, 'Sin planes registrados', 'No existen planes de accion para construir el seguimiento operativo.', 'fa-clipboard');
            return;
        }

        if (priority) {
            priority.innerHTML = porLinea.slice(0, 5).map((item) => `
                <div class="priority-row">
                    <div class="priority-row-top">
                        <div>
                            <div class="priority-title">${escapeHtml(item.linea)}</div>
                            <div class="priority-meta">Abiertos: ${Number(item.abiertos || 0)} · Completados: ${Number(item.completados || 0)} · Alta prioridad: ${Number(item.alta_prioridad || 0)}</div>
                        </div>
                        <span class="severity-pill ${Number(item.alta_prioridad || 0) > 0 ? 'critico' : (Number(item.abiertos || 0) > 0 ? 'severo' : 'estable')}">${Number(item.porcentaje_cierre || 0)}% cierre</span>
                    </div>
                    <div class="progress-track"><div class="progress-bar" style="width: ${Math.min(Number(item.porcentaje_cierre || 0), 100)}%;"></div></div>
                </div>
            `).join('');
        }

        if (work) {
            work.innerHTML = plans.length
                ? plans.map((item) => `
                    <div class="work-item">
                        <div class="work-item-top">
                            <div>
                                <div class="work-title">${escapeHtml(item.linea)}</div>
                                <div class="work-meta">${escapeHtml(item.actividad || 'Sin descripcion')}</div>
                            </div>
                            <span class="severity-pill ${planClass(item.prioridad)}">${escapeHtml(item.prioridad_label || 'Sin fecha')}</span>
                        </div>
                        <div class="work-meta" style="margin-top: 8px;">Proxima fecha: ${escapeHtml(item.proxima_fecha_humana || 'Sin fecha')} · ${daysLabel(item.dias_restantes)}</div>
                    </div>
                `).join('')
                : infoBox('No hay actividades abiertas prioritarias en este momento.');
        }

        destroy(charts.planes);
        setChartState('planes', false);
        charts.planes = new Chart(document.getElementById('componentesChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pendientes criticos', 'Activos programados', 'Completados'],
                datasets: [{
                    data: [Number(summary.pendientes || 0), Number(summary.programados || 0), Number(summary.completados || 0)],
                    backgroundColor: ['rgba(239, 68, 68, 0.92)', 'rgba(245, 158, 11, 0.88)', 'rgba(16, 185, 129, 0.88)'],
                    borderColor: ['#dc2626', '#d97706', '#059669'],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: { label: (context) => `${context.label}: ${context.raw}` }
                    }
                }
            },
            plugins: [{
                id: 'planesCenterLabel',
                beforeDraw(chart) {
                    const { ctx } = chart;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = '#0f172a';
                    ctx.font = '700 24px sans-serif';
                    ctx.fillText(`${Number(summary.avance || 0)}%`, chart.width / 2, chart.height / 2 - 6);
                    ctx.fillStyle = '#64748b';
                    ctx.font = '600 11px sans-serif';
                    ctx.fillText('avance global', chart.width / 2, chart.height / 2 + 16);
                    ctx.restore();
                }
            }]
        });
    }

    function renderRanking() {
        const list = document.getElementById('rankingList');
        const footer = document.getElementById('rankingFooter');
        const empty = document.getElementById('rankingEmpty');

        if (!list) return;

        const rows = (Array.isArray(data.ranking) ? data.ranking : [])
            .map((item) => ({
                ...item,
                total_danos: Number(item.total_danos || 0),
                criticas: Number(item.criticas || 0),
                severos: Number(item.severos || 0),
                moderados: Number(item.moderados || 0),
                total_componentes: Number(item.total_componentes || 0),
                porcentaje_impacto: Number(item.porcentaje_impacto || 0),
                puntaje: Number(item.puntaje || item.total_danos || 0),
            }));

        rows.sort((a, b) => Number(b.total_danos || 0)
            - Number(a.total_danos || 0)
            || Number(b.criticas || 0)
            - Number(a.criticas || 0)
            || Number(b.severos || 0)
            - Number(a.severos || 0)
            || Number(b.moderados || 0)
            - Number(a.moderados || 0)
            || Number(b.puntaje || 0)
            - Number(a.puntaje || 0));

        hideLoader('rankingLoader');

        if (!rows.length) {
            list.hidden = true;
            if (footer) footer.hidden = true;
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = emptyMarkup('Sin lavadoras con daños', 'Aun no existen daños activos para mostrar en el ranking.', 'fa-list-check');
            }
            return;
        }

        if (empty) empty.hidden = true;
        list.hidden = false;
        if (footer) {
            footer.hidden = false;
            const totalDanos = rows.reduce((sum, item) => sum + Number(item.total_danos || 0), 0);
            footer.innerHTML = `<div><i class="fas fa-info-circle"></i> Daños activos: ${number(totalDanos, 0)} · Lavadoras: ${rows.length}</div>`;
        }

        list.innerHTML = rows.map((item, index) => `
            <li class="ranking-item">
                <div class="ranking-position ${index === 0 ? 'top-1' : (index === 1 ? 'top-2' : (index === 2 ? 'top-3' : ''))}">${index + 1}</div>
                <div class="ranking-asset">
                    <div class="asset-media">
                        <i class="fas fa-industry" style="font-size: 18px; color: #2563eb;"></i>
                    </div>
                    <div class="ranking-info">
                        <div class="ranking-linea">${escapeHtml(item.linea || 'Sin linea')}</div>
                        <div class="ranking-puntaje"><i class="fas fa-triangle-exclamation"></i> Criticas: ${Number(item.criticas || 0)} · Severo / Moderado: ${Number(item.severos || 0) + Number(item.moderados || 0)}</div>
                        <div class="ranking-meta">Total con daño: ${Number(item.total_danos || 0)} de ${Number(item.total_componentes || 0)} componentes · Impacto ${percent(item.porcentaje_impacto || 0, 1)} · ${elapsedDaysLabel(item.dias_desde_revision)}</div>
                    </div>
                </div>
                <div class="ranking-status-stack">
                    <span class="severity-pill ${severityClass(item.prioridad)}">${escapeHtml(item.prioridad_label || 'Estable')}</span>
                    <div class="ranking-badge"><i class="fas fa-bolt"></i> ${number(item.total_danos || 0, 0)} daños</div>
                </div>
            </li>
        `).join('');
    }

    function renderElongaciones() {
        const stats = document.getElementById('elongacionesStats');
        const description = cardFromCanvas('elongacionesChart')?.querySelector('.chart-description');
        const select = document.getElementById('elongacionesLineaSelect');
        const rows = Array.isArray(data.elongaciones?.lineas) ? data.elongaciones.lineas : [];
        const lineasConDatos = rows.filter((row) => !row.sin_datos && Array.isArray(row.labels) && row.labels.length);
        const item = lineasConDatos.find((row) => Number(row.linea_id) === Number(state.elongacionLineaId)) || lineasConDatos[0];

        if (item) state.elongacionLineaId = Number(item.linea_id);

        if (select) select.value = String(item?.linea_id ?? state.elongacionLineaId ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length) {
            if (stats) {
                stats.innerHTML = miniStats([
                    ['Mediciones', 0, 'Sin historial', 'info'],
                    ['Periodo', '-', 'No hay fechas', 'warning'],
                    ['Max actual', '0%', 'Sin lecturas', 'danger']
                ]);
            }
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> No existe historico de elongaciones para el ciclo actual de la lavadora seleccionada';
            charts.elongaciones = destroyChartForCanvas('elongacionesChart', charts.elongaciones);
            setChartState('elongaciones', true, 'Sin historial de elongaciones', 'Registra mediciones para visualizar la tendencia del ciclo actual de la cadena.', 'fa-wave-square');
            return;
        }

        const current = Number(item.actual_max || 0);
        const maxTone = current >= Number(item.threshold_cambio || 0) ? 'danger' : (current >= Number(item.threshold_compra || 0) ? 'severo' : 'success');
        const status = current >= Number(item.threshold_cambio || 0) ? 'critico' : (current >= Number(item.threshold_compra || 0) ? 'severo' : 'success');
        if (stats) {
            stats.innerHTML = miniStats([
                ['Mediciones', Number(item.mediciones || 0), escapeHtml(item.linea || ''), 'info'],
                ['Desde', escapeHtml(item.desde || '-'), `Hasta ${escapeHtml(item.hasta || '-')}`, 'success'],
                ['Max actual', percent(current, 2), `Compra ${percent(item.threshold_compra || 0, 2)}`, maxTone],
                ['Estado', current >= Number(item.threshold_cambio || 0) ? 'Crítico' : (current >= Number(item.threshold_compra || 0) ? 'Seguimiento' : 'Estable'), `Cambio ${percent(item.threshold_cambio || 0, 2)}`, status]
            ]);
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(item.linea)} · ${Number(item.mediciones || 0)} mediciones desde ${escapeHtml(item.desde || '-')} hasta ${escapeHtml(item.hasta || '-')}`;
        }

        const compactViewport = window.innerWidth <= 480;
        const narrowViewport = window.innerWidth <= 768;
        const pointRadius = compactViewport ? 2 : (narrowViewport ? 3 : 4);
        const pointHoverRadius = compactViewport ? 4 : 6;
        const maxTicksLimit = compactViewport ? 4 : (narrowViewport ? 6 : 8);

        const canvas = document.getElementById('elongacionesChart');
        if (!canvas) {
            throw new Error('El canvas de elongaciones no esta disponible.');
        }

        charts.elongaciones = destroyChartForCanvas('elongacionesChart', charts.elongaciones);
        setChartState('elongaciones', false);
        if (typeof Chart === 'undefined') {
            drawElongacionesCanvas(canvas, item);
            return;
        }

        try {
            charts.elongaciones = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: item.labels,
                    datasets: [
                        { label: 'Lado Bombas', data: (item.bombas || []).map((value) => Number(value || 0)), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.12)', borderWidth: compactViewport ? 2 : 3, pointRadius, pointHoverRadius, tension: 0.35, fill: true },
                        { label: 'Lado Vapor', data: (item.vapor || []).map((value) => Number(value || 0)), borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.08)', borderWidth: compactViewport ? 2 : 3, pointRadius, pointHoverRadius, tension: 0.35, fill: true },
                        { label: 'Umbral compra', data: new Array(item.labels.length).fill(Number(item.threshold_compra || 0)), borderColor: '#f97316', borderWidth: 2, pointRadius: 0, borderDash: [8, 4] },
                        { label: 'Umbral cambio', data: new Array(item.labels.length).fill(Number(item.threshold_cambio || 0)), borderColor: '#ef4444', borderWidth: 2, pointRadius: 0, borderDash: [8, 4] }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    resizeDelay: 120,
                    interaction: { mode: 'index', intersect: false },
                    layout: {
                        padding: {
                            top: 8,
                            right: compactViewport ? 6 : 12,
                            bottom: compactViewport ? 6 : 10,
                            left: 0
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        datalabels: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#fff',
                            bodyColor: '#e2e8f0',
                            callbacks: { label: (context) => `${context.dataset.label}: ${percent(context.raw || 0, 2)}` }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                autoSkip: true,
                                maxTicksLimit,
                                maxRotation: narrowViewport ? 0 : 45,
                                minRotation: narrowViewport ? 0 : 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grace: '8%',
                            grid: { color: 'rgba(148, 163, 184, 0.16)' },
                            ticks: {
                                color: '#64748b',
                                padding: 8,
                                callback: (value) => `${value}%`
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.warn('No se pudo renderizar elongaciones con Chart.js. Se usa canvas nativo.', error);
            charts.elongaciones = destroyChartForCanvas('elongacionesChart', charts.elongaciones);
            setChartState('elongaciones', false);
            drawElongacionesCanvas(canvas, item);
        }
    }

    function drawElongacionesCanvas(canvas, item) {
        const shell = canvas.closest('.chart-shell');
        const parent = canvas.parentElement;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const width = Math.max(320, Math.floor((parent?.clientWidth || shell?.clientWidth || 720)));
        const height = Math.max(240, Math.floor((parent?.clientHeight || shell?.clientHeight || 280)));
        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, width, height);

        const labels = Array.isArray(item.labels) ? item.labels : [];
        const bombas = (item.bombas || []).map((value) => Number(value || 0));
        const vapor = (item.vapor || []).map((value) => Number(value || 0));
        const compra = Number(item.threshold_compra || 0);
        const cambio = Number(item.threshold_cambio || 0);
        const values = [...bombas, ...vapor, compra, cambio].filter((value) => Number.isFinite(value));
        const maxValue = Math.max(2, ...values) * 1.12;
        const padding = { top: 22, right: 22, bottom: 42, left: 48 };
        const plotWidth = width - padding.left - padding.right;
        const plotHeight = height - padding.top - padding.bottom;
        const xFor = (index) => padding.left + (labels.length <= 1 ? plotWidth / 2 : (plotWidth * index) / (labels.length - 1));
        const yFor = (value) => padding.top + plotHeight - ((Number(value || 0) / maxValue) * plotHeight);

        ctx.font = '600 11px sans-serif';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.18)';
        ctx.fillStyle = '#64748b';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i += 1) {
            const value = (maxValue / 4) * i;
            const y = yFor(value);
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
            ctx.fillText(`${value.toFixed(value >= 10 ? 0 : 1)}%`, padding.left - 8, y);
        }

        ctx.strokeStyle = 'rgba(15, 23, 42, 0.22)';
        ctx.beginPath();
        ctx.moveTo(padding.left, padding.top);
        ctx.lineTo(padding.left, height - padding.bottom);
        ctx.lineTo(width - padding.right, height - padding.bottom);
        ctx.stroke();

        const drawSeries = (series, color, fillColor) => {
            if (!series.length) return;
            ctx.beginPath();
            series.forEach((value, index) => {
                const x = xFor(index);
                const y = yFor(value);
                if (index === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.setLineDash([]);
            ctx.stroke();

            if (series.length > 1) {
                ctx.lineTo(xFor(series.length - 1), height - padding.bottom);
                ctx.lineTo(xFor(0), height - padding.bottom);
                ctx.closePath();
                ctx.fillStyle = fillColor;
                ctx.fill();
            }

            series.forEach((value, index) => {
                ctx.beginPath();
                ctx.arc(xFor(index), yFor(value), 4, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                ctx.lineWidth = 2;
                ctx.strokeStyle = color;
                ctx.stroke();
            });
        };

        const drawThreshold = (value, color, label) => {
            const y = yFor(value);
            ctx.setLineDash([8, 5]);
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillStyle = color;
            ctx.textAlign = 'left';
            ctx.fillText(label, padding.left + 8, y - 10);
        };

        drawSeries(bombas, '#2563eb', 'rgba(37, 99, 235, 0.10)');
        drawSeries(vapor, '#ef4444', 'rgba(239, 68, 68, 0.08)');
        drawThreshold(compra, '#f97316', 'Compra');
        drawThreshold(cambio, '#ef4444', 'Cambio');

        ctx.fillStyle = '#64748b';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        labels.forEach((label, index) => {
            if (labels.length > 6 && index % Math.ceil(labels.length / 6) !== 0 && index !== labels.length - 1) return;
            ctx.fillText(String(label), xFor(index), height - padding.bottom + 14);
        });

        const legend = [
            ['Lado Bombas', '#2563eb'],
            ['Lado Vapor', '#ef4444'],
        ];
        ctx.font = '700 12px sans-serif';
        ctx.textBaseline = 'middle';
        const legendGap = 24;
        const legendItems = legend.map(([label, color]) => ({
            label,
            color,
            width: 12 + 8 + ctx.measureText(label).width,
        }));
        const legendWidth = legendItems.reduce((sum, item) => sum + item.width, 0) + (legendGap * (legendItems.length - 1));
        let legendX = Math.max(padding.left, width - padding.right - legendWidth);

        legendItems.forEach((item) => {
            ctx.fillStyle = item.color;
            ctx.fillRect(legendX, 14, 11, 11);
            ctx.fillStyle = '#334155';
            ctx.textAlign = 'left';
            ctx.fillText(item.label, legendX + 19, 20);
            legendX += item.width + legendGap;
        });

        bindElongacionesCanvasTooltip(canvas, {
            points: labels.map((label, index) => ({
                label,
                x: xFor(index),
                "Lado Bombas": bombas[index] ?? 0,
                "Lado Vapor": vapor[index] ?? 0,
                yLadoBombas: yFor(bombas[index] ?? 0),
                yLadoVapor: yFor(vapor[index] ?? 0),
            })),
            padding,
            plotWidth,
            width,
            height,
        });
    }

    function bindElongacionesCanvasTooltip(canvas, payload) {
        canvas.__elongacionTooltipPayload = payload;

        if (canvas.__elongacionTooltipBound) return;
        canvas.__elongacionTooltipBound = true;

        const tooltip = getElongacionesCanvasTooltip();
        const hideTooltip = () => {
            tooltip.style.opacity = '0';
            tooltip.style.pointerEvents = 'none';
        };

        canvas.addEventListener('mouseleave', hideTooltip);
        canvas.addEventListener('mousemove', (event) => {
            const current = canvas.__elongacionTooltipPayload;
            const points = current?.points || [];
            if (!points.length) {
                hideTooltip();
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const scaleX = current.width / rect.width;
            const scaleY = current.height / rect.height;
            const mouseX = (event.clientX - rect.left) * scaleX;
            const mouseY = (event.clientY - rect.top) * scaleY;
            const nearest = points
                .map((point) => ({
                    ...point,
                    distance: Math.min(
                        Math.hypot(mouseX - point.x, mouseY - point.yLadoBombas),
                        Math.hypot(mouseX - point.x, mouseY - point.yLadoVapor)
                    ),
                    xDistance: Math.abs(mouseX - point.x),
                }))
                .sort((a, b) => a.distance - b.distance || a.xDistance - b.xDistance)[0];

            if (!nearest || (nearest.distance > 28 && nearest.xDistance > 16)) {
                hideTooltip();
                return;
            }

            tooltip.innerHTML = `
                <div style="font-weight: 800; margin-bottom: 6px;">${escapeHtml(nearest.label)}</div>
                <div style="display: flex; align-items: center; gap: 7px; margin-bottom: 4px;">
                    <span style="width: 9px; height: 9px; border-radius: 999px; background: #2563eb; display: inline-block;"></span>
                    <span>Lado Bombas: <strong>${percent(nearest["Lado Bombas"], 2)}</strong></span>
                </div>
                
                <div style="display: flex; align-items: center; gap: 7px;">
                    <span style="width: 9px; height: 9px; border-radius: 999px; background: #ef4444; display: inline-block;"></span>
                    <span>Lado Vapor: <strong>${percent(nearest["Lado Vapor"], 2)}</strong></span>
                </div>
            `;

            const tooltipWidth = tooltip.offsetWidth || 170;
            const tooltipHeight = tooltip.offsetHeight || 84;
            const left = Math.min(window.innerWidth - tooltipWidth - 12, event.clientX + 14);
            const top = Math.max(12, event.clientY - tooltipHeight - 12);
            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
            tooltip.style.opacity = '1';
            tooltip.style.pointerEvents = 'auto';
        });
    }

    function getElongacionesCanvasTooltip() {
        let tooltip = document.getElementById('elongacionesCanvasTooltip');
        if (tooltip) return tooltip;

        tooltip = document.createElement('div');
        tooltip.id = 'elongacionesCanvasTooltip';
        tooltip.style.position = 'fixed';
        tooltip.style.zIndex = '9999';
        tooltip.style.minWidth = '160px';
        tooltip.style.padding = '10px 12px';
        tooltip.style.borderRadius = '12px';
        tooltip.style.background = 'rgba(15, 23, 42, 0.96)';
        tooltip.style.color = '#ffffff';
        tooltip.style.font = '700 12px/1.35 sans-serif';
        tooltip.style.boxShadow = '0 16px 35px rgba(15, 23, 42, 0.25)';
        tooltip.style.opacity = '0';
        tooltip.style.transition = 'opacity 120ms ease, transform 120ms ease';
        tooltip.style.pointerEvents = 'none';
        document.body.appendChild(tooltip);
        return tooltip;
    }

    function renderHistorico() {
        renderVisibleChart.cancel(document.getElementById('historicoChart'));
        const stats = document.getElementById('historicoStats');
        const footer = document.getElementById('historicoTableBody')?.closest('.chart-card')?.querySelector('.table-footer');
        const body = document.getElementById('historicoTableBody');
        const reviewList = document.getElementById('historicoReviewList');
        const labels = Array.isArray(data.historico?.labels) ? data.historico.labels : [];
        const series = data.historico?.series || {};
        const values = Array.isArray(series[state.historicoScope]) ? series[state.historicoScope] : (Array.isArray(series.Todas) ? series.Todas : []);
        const registrosPorAlcance = data.historico?.registros_por_alcance || {};
        const registros = Array.isArray(registrosPorAlcance[state.historicoScope])
            ? registrosPorAlcance[state.historicoScope]
            : (Array.isArray(data.historico?.registros)
                ? data.historico.registros
                    .filter((item) => state.historicoScope === 'Todas' || item.linea === state.historicoScope)
                    .slice(0, 5)
                : []);
        const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
        const peak = values.length ? Math.max(...values.map((value) => Number(value || 0))) : 0;
        const average = values.length ? total / values.length : 0;
        const last = registros[0]?.fecha_humana || data.historico?.resumen?.ultima_revision || 'Sin revision';

        if (stats) {
            stats.innerHTML = miniStats([
                ['Revisiones 12m', total, state.historicoScope === 'Todas' ? 'Vista consolidada' : escapeHtml(state.historicoScope), 'info'],
                ['Promedio mensual', number(average, 1), `${values.length || 0} cortes considerados`, 'success'],
                ['Pico mensual', peak, 'Mes con mayor actividad', 'warning'],
                ['Ultima revision', escapeHtml(last), `${registros.length} registros visibles`, 'danger']
            ]);
        }

        if (!values.length && !registros.length) {
            if (body) body.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-6">No hay historial disponible para el filtro seleccionado.</td></tr>`;
            if (reviewList) {
                reviewList.innerHTML = `
                    <div class="historico-review-empty">
                        <i class="fas fa-history"></i>
                        <span>No hay historial disponible para el filtro seleccionado.</span>
                    </div>
                `;
            }
            if (footer) footer.innerHTML = '<i class="fas fa-info-circle"></i> Se mostrara la trazabilidad cuando existan revisiones registradas.';
            destroy(charts.historico);
            setChartState('historico', true, 'Sin historico disponible', 'No existen revisiones suficientes para construir la tendencia mensual.', 'fa-history');
            return;
        }

        if (body) {
            body.innerHTML = registros.length
                ? registros.map((item) => `
                    <tr>
                        <td data-label="Fecha">${escapeHtml(item.fecha_humana || '-')}</td>
                        <td data-label="Lavadora">${escapeHtml(item.linea || '-')}</td>
                        <td data-label="Componente">${escapeHtml(item.componente || '-')}</td>
                        <td data-label="Ubicacion"><span class="historico-location">${escapeHtml(item.reductor || '-')}${item.lado ? ` - ${escapeHtml(item.lado)}` : ''}</span></td>
                        <td data-label="Estado"><span class="severity-pill ${severityFromEstado(item.estado)}" style="${severityPillStyle(item.estado, item.estado_color)}">${escapeHtml(item.estado || 'Sin estado')}</span></td>
                        <td data-label="Revision"><span class="historico-user">${escapeHtml(item.usuario || 'Sin usuario')}</span>${item.actividad ? `<span class="historico-activity">${escapeHtml(item.actividad)}</span>` : ''}</td>
                    </tr>
                `).join('')
                : `<tr><td colspan="6" class="text-center text-gray-500 py-6">No hay registros recientes para este alcance.</td></tr>`;
        }

        if (reviewList) {
            reviewList.innerHTML = registros.length
                ? registros.map((item) => {
                    const ubicacion = `${escapeHtml(item.reductor || '-')}${item.lado ? ` - ${escapeHtml(item.lado)}` : ''}`;
                    const actividad = item.actividad ? escapeHtml(item.actividad) : 'Sin actividad registrada';

                    return `
                        <article class="historico-review-item">
                            <div class="historico-review-top">
                                <span class="historico-review-date">
                                    <i class="fas fa-calendar-day"></i>
                                    <span>${escapeHtml(item.fecha_humana || '-')}</span>
                                </span>
                                <span class="historico-review-linea">
                                    <span>${escapeHtml(item.linea || '-')}</span>
                                </span>
                                <span class="severity-pill ${severityFromEstado(item.estado)}" style="${severityPillStyle(item.estado, item.estado_color)}">${escapeHtml(item.estado || 'Sin estado')}</span>
                            </div>
                            <div class="historico-review-main">
                                <div class="historico-review-field">
                                    <span class="historico-review-label">Componente</span>
                                    <span class="historico-review-value">${escapeHtml(item.componente || '-')}</span>
                                </div>
                                <div class="historico-review-field">
                                    <span class="historico-review-label">Ubicacion</span>
                                    <span class="historico-review-value">${ubicacion}</span>
                                </div>
                            </div>
                            <div class="historico-review-activity">
                                <div class="historico-review-field">
                                    <span class="historico-review-label">Revision</span>
                                    <span class="historico-user">${escapeHtml(item.usuario || 'Sin usuario')}</span>
                                </div>
                                <div class="historico-review-field">
                                    <span class="historico-review-label">Actividad</span>
                                    <span class="historico-activity">${actividad}</span>
                                </div>
                            </div>
                        </article>
                    `;
                }).join('')
                : `
                    <div class="historico-review-empty">
                        <i class="fas fa-filter"></i>
                        <span>No hay registros recientes para este alcance.</span>
                    </div>
                `;
        }

        if (footer) {
            footer.innerHTML = `<i class="fas fa-info-circle"></i> Revisiones visibles: ${registros.length} · ${escapeHtml(state.historicoScope === 'Todas' ? 'Todas las lavadoras' : state.historicoScope)}`;
        }

        setChartState('historico', false);
        const canvas = document.getElementById('historicoChart');
        renderVisibleChart(canvas, () => safeRenderSection('historico', () => {
            destroy(charts.historico);
            charts.historico = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: state.historicoScope === 'Todas' ? 'Todas las lavadoras' : state.historicoScope,
                        data: values.map((value) => Number(value || 0)),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.14)',
                        borderWidth: 3,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563eb',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        datalabels: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#fff',
                            bodyColor: '#e2e8f0',
                            callbacks: { label: (context) => `Revisiones: ${context.raw}` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#64748b' } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                    }
                }
            });
        }));
    }

    function handleTrendRenderFailure(config, error) {
        const dataset = data[config.dataKey] || {};
        const periodo = dataset.periodo || {};
        const tableBody = document.getElementById(`${config.prefix}DetailTable`);
        const recentEvents = document.getElementById(`${config.prefix}RecentEvents`);
        const criteria = document.getElementById(config.criteriaId);

        console.error(`No se pudo renderizar ${config.prefix}`, error);
        renderTrendExecutiveBlock(config, null, []);

        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-gray-500 py-6">No fue posible cargar el detalle de este periodo.</td></tr>`;
        }

        if (recentEvents) {
            recentEvents.innerHTML = infoBox('No fue posible cargar los eventos recientes.');
        }

        if (criteria) {
            criteria.innerHTML = trendCriteriaMarkup(dataset.criterios || [], periodo);
        }

        destroy(charts[config.chartKey]);
        destroy(charts[`${config.chartKey}DamageBar`]);
        destroy(charts[`${config.chartKey}ComponentBar`]);
        destroy(charts[`${config.chartKey}DamageComponentBar`]);
        setChartState(
            config.prefix,
            true,
            'Error al cargar la tendencia',
            'La grafica no pudo renderizarse con este corte. Recarga la vista o ajusta el filtro.',
            'fa-triangle-exclamation'
        );
    }

    function renderDamageTrendCard(config) {
        const dataset = data[config.dataKey] || {};
        const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const item = rows.find((row) => Number(row.linea_id) === Number(state[config.stateKey])) || rows[0];

        if (select) select.value = String(item?.linea_id ?? state[config.stateKey] ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length || !Array.isArray(item.series) || !item.series.length) {
            destroy(charts[config.chartKey]);
            setChartState(config.prefix, true, config.emptyTitle, config.emptyMessage, config.emptyIcon);
            return;
        }

        if (breakdown) {
            breakdown.innerHTML = ventanas.length
                ? ventanas.map((window) => `
                    <div class="breakdown-item">
                        <div class="breakdown-item-top">
                            <div>
                                <div class="breakdown-title">${escapeHtml(normalizeTrendLabel(window.label || 'Periodo'))}</div>
                                <div class="breakdown-meta">Actual: ${Number(window.current || 0)} (componentes ${Number(window.current_componentes || 0)}, elongaciones ${Number(window.current_elongaciones || 0)}) · Anterior: ${Number(window.previous || 0)} (componentes ${Number(window.previous_componentes || 0)}, elongaciones ${Number(window.previous_elongaciones || 0)})</div>
                            </div>
                            <span class="severity-pill ${trendPillClass(window.trend, Number(window.current || 0))}">${escapeHtml(trendDeltaLabel(Number(window.delta || 0), window.trend || 'stable'))}</span>
                        </div>
                        <div class="breakdown-meta">Rango actual: ${escapeHtml(window.current_range || '-')} · Rango anterior: ${escapeHtml(window.previous_range || '-')}</div>
                    </div>
                `).join('')
                : infoBox('No hay ventanas comparables disponibles para esta lavadora.');
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(item.linea || 'Sin linea')} · ${escapeHtml(resumen.estado || 'Sin fallas')} · Ultima falla ${escapeHtml(resumen.ultima_falla || 'Sin registro')} · Fuente ${escapeHtml(formatTrendCriteriaLabel(resumen.ultima_fuente || 'Sin registro'))}`;
        }

        const windowColors = (item.labels || []).map((label) => trendWindowColorForLabel(label));
        const borderColors = windowColors.map((color) => color.border);
        const currentColors = windowColors.map((color) => color.fill);
        const previousColors = windowColors.map((color) => color.soft);

        destroy(charts[config.chartKey]);
        setChartState(config.prefix, false);
        charts[config.chartKey] = new Chart(document.getElementById(config.cardId).getContext('2d'), {
            type: 'bar',
            data: {
                labels: (item.labels || []).map((label) => normalizeTrendLabel(label)),
                datasets: [
                    {
                        label: 'Periodo actual',
                        data: (item.actual || []).map((value) => Number(value || 0)),
                        borderColor: borderColors,
                        backgroundColor: currentColors,
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    },
                    {
                        label: 'Periodo anterior',
                        data: (item.anterior || []).map((value) => Number(value || 0)),
                        borderColor: borderColors,
                        backgroundColor: previousColors,
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, color: '#334155', font: { size: 11, weight: 700 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            title: (context) => normalizeTrendLabel(context[0]?.label || ''),
                            label: (context) => `${context.dataset.label}: ${Number(context.raw || 0)} fallas`,
                            afterBody: (contexts) => {
                                const window = ventanas[contexts[0]?.dataIndex ?? -1];
                                if (!window) return [];

                                return [
                                    `Actual: ${window.current_range || '-'}`,
                                    `Anterior: ${window.previous_range || '-'}`,
                                    `Componentes actuales: ${Number(window.current_componentes || 0)}`,
                                    `Elongaciones actuales: ${Number(window.current_elongaciones || 0)}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                }
            }
        });
    }

    function renderDamageTrendCardCompactLegacy(config) {
        const dataset = data[config.dataKey] || {};
        const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const item = rows.find((row) => Number(row.linea_id) === Number(state[config.stateKey])) || rows[0];

        if (select) select.value = String(item?.linea_id ?? state[config.stateKey] ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length || !Array.isArray(item.series) || !item.series.length) {
            destroy(charts[config.chartKey]);
            setChartState(config.prefix, true, config.emptyTitle, config.emptyMessage, config.emptyIcon);
            return;
        }

        const palette = ['#22c55e', '#ef4444', '#f08a36'];
        const fills = ['rgba(34, 197, 94, 0.86)', 'rgba(239, 68, 68, 0.86)', 'rgba(240, 138, 54, 0.86)'];
        const datasets = item.series.map((serie, index) => ({
            label: normalizeTrendLabel(serie.label || `Serie ${index + 1}`),
            data: Array.isArray(serie.data) ? serie.data.map((value) => Number(value || 0)) : [],
            borderColor: palette[index % palette.length],
            backgroundColor: fills[index % fills.length],
            borderWidth: 1,
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 42
        }));

        destroy(charts[config.chartKey]);
        setChartState(config.prefix, false);
        charts[config.chartKey] = new Chart(document.getElementById(config.cardId).getContext('2d'), {
            type: 'bar',
            data: {
                labels: (item.labels || []).map((label) => normalizeTrendLabel(label)),
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 18,
                            color: '#334155',
                            font: { size: 11, weight: 700 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            title: (context) => normalizeTrendLabel(context[0]?.label || ''),
                            label: (context) => `${context.dataset.label}: ${Number(context.raw || 0)} daños`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', maxRotation: 45, minRotation: 45 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        title: {
                            display: true,
                            text: 'Total de daños',
                            color: '#64748b',
                            font: { size: 12, weight: 700 }
                        },
                        ticks: { precision: 0, color: '#64748b' }
                    }
                }
            }
        });
    }

    function trendWindowRoleForLabel(label) {
        const normalized = String(label || '').toLowerCase();

        if (normalized.includes('52')) return 'historico acumulado';
        if (normalized.includes('30')) return 'referencia operativa';
        if (normalized.includes('12')) return 'impacto trimestral';
        if (normalized.includes('14')) return 'respuesta reciente';

        return 'control inmediato';
    }

    function trendCompareSeries(series) {
        const current = Number(series[series.length - 1] || 0);
        const previous = Number(series[series.length - 2] || 0);
        const diff = current - previous;
        const percentage = previous === 0 ? null : (diff / previous) * 100;

        return {
            current,
            previous,
            diff,
            percentage
        };
    }

    function trendZeroStreak(series) {
        let streak = 0;

        for (let index = series.length - 1; index >= 0; index -= 1) {
            if (Number(series[index] || 0) !== 0) {
                break;
            }

            streak += 1;
        }

        return streak;
    }

    function trendExecutiveDeltaCopy(delta) {
        if (delta.diff < 0) {
            const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
            return `Bajo ${number(Math.abs(delta.diff), 2)}${pct}`;
        }

        if (delta.diff > 0) {
            const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
            return `Subio ${number(delta.diff, 2)}${pct}`;
        }

        return 'Sin cambio';
    }

    function renderTrendExecutiveBlock(config, item, series) {
        const windowCards = document.getElementById(`${config.prefix}WindowCards`);
        const caption = document.getElementById(`${config.prefix}Caption`);
        const chartType = state[config.chartTypeKey] || 'bar';

        document.querySelectorAll(`[data-trend-prefix="${config.prefix}"]`).forEach((button) => {
            button.classList.toggle('active', button.dataset.trendType === chartType);
        });

        if (!windowCards || !caption) {
            return;
        }

        if (!item || !Array.isArray(item.labels) || !item.labels.length || !series.length) {
            windowCards.innerHTML = '';
            caption.textContent = 'Sin corte disponible.';
            return;
        }

        const latestLabel = item.labels[item.labels.length - 1] || 'Sin corte';

        caption.textContent = `Corte actual: ${latestLabel}.`;

        windowCards.innerHTML = series.map((windowItem) => {
            const delta = trendCompareSeries(windowItem.data || []);
            const deltaClass = delta.diff < 0
                ? 'trend-executive-window-delta--positive'
                : (delta.diff > 0 ? 'trend-executive-window-delta--alert' : 'trend-executive-window-delta--neutral');
            const deltaIcon = delta.diff < 0 ? 'fa-arrow-down' : (delta.diff > 0 ? 'fa-arrow-up' : 'fa-minus');

            return `
                <article class="trend-executive-window-card" style="--trend-accent: ${windowItem.color}">
                    <div class="trend-executive-window-label">${escapeHtml(windowItem.label)}</div>
                    <div class="trend-executive-window-value">${number(delta.current, 2)}</div>
                    <div class="trend-executive-window-role">${escapeHtml(windowItem.role)}</div>
                    <div class="trend-executive-window-delta ${deltaClass}">
                        <i class="fas ${deltaIcon}"></i>
                        <span>${escapeHtml(trendExecutiveDeltaCopy(delta))} vs corte anterior</span>
                    </div>
                </article>
            `;
        }).join('');
    }

    function renderDamageTrendCardCompact(config) {
        const dataset = data[config.dataKey] || {};
        const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const item = rows.find((row) => Number(row.linea_id) === Number(state[config.stateKey])) || rows[0];
        const periodo = dataset.periodo || item?.resumen?.periodo || {};
        const tableBody = document.getElementById(`${config.prefix}DetailTable`);
        const recentEvents = document.getElementById(`${config.prefix}RecentEvents`);
        const criteria = document.getElementById(config.criteriaId);

        if (select) select.value = String(item?.linea_id ?? state[config.stateKey] ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length || !Array.isArray(item.series) || !item.series.length) {
            renderTrendExecutiveBlock(config, null, []);
            if (tableBody) tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-gray-500 py-6">No hay detalle por componente para este periodo.</td></tr>`;
            if (recentEvents) recentEvents.innerHTML = infoBox('Sin eventos recientes para mostrar.');
            if (criteria) criteria.innerHTML = trendCriteriaMarkup(dataset.criterios || [], periodo);
            destroy(charts[config.chartKey]);
            destroy(charts[`${config.chartKey}DamageBar`]);
            destroy(charts[`${config.chartKey}ComponentBar`]);
            destroy(charts[`${config.chartKey}DamageComponentBar`]);
            setChartState(config.prefix, true, config.emptyTitle, config.emptyMessage, config.emptyIcon);
            return;
        }

        const componentes = Array.isArray(item.componentes) ? item.componentes : [];
        const matriz = Array.isArray(item.matriz_componentes_danos) ? item.matriz_componentes_danos : componentes;
        const eventos = Array.isArray(item.eventos) ? item.eventos : [];
        const chartType = state[config.chartTypeKey] || 'bar';
        const isBar = chartType === 'bar';
        const isSmallScreen = window.innerWidth <= 640;
        const labels = item.labels || [];
        const executiveSeries = item.series.map((serie, index) => {
            const colors = trendWindowColorForLabel(serie.label || `Serie ${index + 1}`);
            const normalized = normalizeTrendLabel(serie.label || `Serie ${index + 1}`);

            return {
                label: normalized,
                data: Array.isArray(serie.data) ? serie.data.map((value) => Number(value || 0)) : [],
                role: trendWindowRoleForLabel(normalized),
                color: colors.border,
                fill: colors.fill,
                soft: colors.soft,
                dashed: normalized.toLowerCase().includes('52') || normalized.toLowerCase().includes('30')
            };
        });

        if (tableBody) {
            tableBody.innerHTML = matriz.length
                ? matriz.slice(0, 10).map((row) => {
                    const danosChips = (row.danos || []).slice(0, 3).map((damage) => `<span class="severity-pill ${trendCriteriaPillClass(damage.estado || '')}">${escapeHtml(formatTrendCriteriaLabel(damage.estado || 'Sin estado'))}: ${number(damage.total || 0)}</span>`).join('');
                    const ubicaciones = (row.ubicaciones || []).slice(0, 3).map((location) => `<span>${escapeHtml(location.ubicacion || 'Sin ubicacion')} (${number(location.total || 0)})</span>`).join('');

                    return `
                        <tr>
                            <td data-label="Componente">
                                <div class="trend-value-stack">
                                    <strong>${escapeHtml(row.componente || 'Sin componente')}</strong>
                                    ${row.codigo ? `<span class="trend-muted">${escapeHtml(row.codigo)}</span>` : ''}
                                    <div class="trend-damage-chips">${danosChips}</div>
                                </div>
                            </td>
                            <td data-label="Total">${number(row.total || 0)}</td>
                            <td data-label="Participacion">${percent(row.porcentaje || 0, 1)}</td>
                            <td data-label="Daño principal">${escapeHtml(formatTrendCriteriaLabel(row.dano_principal || 'Sin dato'))}<div class="trend-muted">${number(row.dano_principal_total || 0)} veces</div></td>
                            <td data-label="Ubicacion"><div class="trend-location-list">${ubicaciones || '<span>Sin ubicacion</span>'}</div></td>
                            <td data-label="Mes actual"><span class="severity-pill ${Number(row.delta_mes || 0) > 0 ? 'critico' : (Number(row.delta_mes || 0) < 0 ? 'estable' : 'revision')}">${Number(row.delta_mes || 0) > 0 ? '+' : ''}${number(row.delta_mes || 0)}</span></td>
                            <td data-label="Ultima falla">${escapeHtml(row.ultima_falla || 'Sin fecha')}</td>
                        </tr>
                    `;
                }).join('')
                : `<tr><td colspan="7" class="text-center text-gray-500 py-6">No hay detalle por componente para este periodo.</td></tr>`;
        }

        if (recentEvents) {
            recentEvents.innerHTML = eventos.length
                ? eventos.slice(0, 6).map((event) => `
                    <div class="work-item">
                        <div class="work-item-top">
                            <div>
                                <div class="work-title">${escapeHtml(event.fecha || 'Sin fecha')} - ${escapeHtml(event.componente || 'Sin componente')}</div>
                                <div class="work-meta">${escapeHtml(event.ubicacion || event.reductor || event.lado || 'Sin ubicacion')}</div>
                            </div>
                            <span class="severity-pill ${trendCriteriaPillClass(event.estado || '')}">${escapeHtml(formatTrendCriteriaLabel(event.estado || 'Sin estado'))}</span>
                        </div>
                    </div>
                `).join('')
                : infoBox('Sin eventos recientes para mostrar.');
        }

        if (criteria) {
            criteria.innerHTML = trendCriteriaMarkup(dataset.criterios || [], periodo);
        }

        renderTrendDetailCharts(config, item);
        renderTrendExecutiveBlock(config, item, executiveSeries);

        destroy(charts[config.chartKey]);
        setChartState(config.prefix, false);
        charts[config.chartKey] = new Chart(document.getElementById(config.cardId).getContext('2d'), {
            type: chartType,
            data: {
                labels,
                datasets: executiveSeries.map((serie, index) => ({
                    label: serie.label,
                    data: serie.data,
                    borderColor: serie.color,
                    backgroundColor: isBar ? serie.fill : serie.soft,
                    borderWidth: isBar ? 0 : (index === executiveSeries.length - 1 ? 4 : 3),
                    borderDash: isBar ? [] : (serie.dashed ? [8, 6] : []),
                    pointBackgroundColor: serie.color,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: isBar ? 0 : ((context) => context.dataIndex === labels.length - 1 ? 5 : 3),
                    pointHoverRadius: 7,
                    tension: 0.32,
                    fill: false,
                    borderRadius: isBar ? 10 : 0,
                    borderSkipped: isBar ? false : undefined,
                    barPercentage: isBar ? 0.72 : undefined,
                    categoryPercentage: isBar ? 0.74 : undefined,
                    maxBarThickness: isBar ? 28 : undefined
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            padding: 14,
                            color: '#334155',
                            font: { size: isSmallScreen ? 10 : 12, weight: '700' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            label: (context) => {
                                const currentValue = Number(context.parsed.y || context.raw || 0);
                                const previousValue = Number(context.dataset.data?.[context.dataIndex - 1] || 0);
                                const diff = currentValue - previousValue;
                                const change = context.dataIndex === 0
                                    ? 'Sin comparativo anterior'
                                    : (diff < 0
                                        ? `Bajo ${number(Math.abs(diff), 2)}`
                                        : (diff > 0 ? `Subio ${number(diff, 2)}` : 'Sin cambio'));

                                return `${context.dataset.label}: ${number(currentValue, 2)} daños. ${change}.`;
                            }
                        }
                    },
                    datalabels: isBar ? {
                        display: (context) => Number(context.raw || 0) > 0,
                        anchor: 'end',
                        align: 'top',
                        offset: 8,
                        clamp: true,
                        clip: false,
                        color: '#ffffff',
                        backgroundColor: (context) => context.dataset.borderColor,
                        borderRadius: 999,
                        padding: {
                            top: 4,
                            right: 8,
                            bottom: 4,
                            left: 8
                        },
                        formatter: (value) => number(value, 2),
                        font: {
                            size: isSmallScreen ? 9 : 11,
                            weight: '800'
                        }
                    } : {
                        display: (context) => !isSmallScreen && context.dataIndex === labels.length - 1 && Number(context.raw || 0) > 0,
                        anchor: 'end',
                        align: 'top',
                        offset: 6,
                        clamp: true,
                        clip: false,
                        color: (context) => context.dataset.borderColor,
                        backgroundColor: null,
                        borderRadius: 0,
                        padding: 0,
                        formatter: (value) => number(value, 2),
                        textStrokeColor: 'rgba(255, 255, 255, 0.98)',
                        textStrokeWidth: 2,
                        font: {
                            size: 10,
                            weight: '800'
                        }
                    }
                },
                layout: {
                    padding: {
                        top: isBar ? 72 : 16,
                        right: 8,
                        bottom: 10,
                        left: 4
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#475569',
                            maxRotation: isSmallScreen ? 48 : 0,
                            minRotation: isSmallScreen ? 48 : 0,
                            font: { size: isSmallScreen ? 10 : 11, weight: '700' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: isBar ? '36%' : '8%',
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                        title: {
                            display: true,
                            text: 'Daños registrados',
                            color: '#64748b',
                            font: { size: 12, weight: '800' }
                        },
                        ticks: {
                            precision: 0,
                            color: '#64748b',
                            callback: (value) => number(value, 2)
                        }
                    }
                }
            }
        });
    }

    function renderTrendDetailCharts(config, item) {
        const graphData = item?.graficas || {};

        destroy(charts[`${config.chartKey}ComponentBar`]);
        destroy(charts[`${config.chartKey}DamageBar`]);
        destroy(charts[`${config.chartKey}DamageComponentBar`]);

        charts[`${config.chartKey}ComponentBar`] = createTrendHorizontalBarChart(
            `${config.prefix}ComponentBar`,
            graphData.barras_componentes_totales || {},
            'Componentes con mayor incidencia',
            { datasetLabel: 'Daños por componente' }
        );
        charts[`${config.chartKey}DamageBar`] = createTrendHorizontalBarChart(
            `${config.prefix}DamageBar`,
            graphData.pastel_danos || {},
            'Tipos de daño frecuentes',
            { datasetLabel: 'Daños por tipo de daño', damageMode: true }
        );
    }

    function trendMetaList(items, labelKey, limit = 3) {
        if (!Array.isArray(items) || !items.length) {
            return null;
        }

        return items
            .slice(0, limit)
            .map((item) => `${item[labelKey] || 'Sin dato'} (${number(item.total || 0)})`)
            .join(', ');
    }

    function trendComponentTooltipLines(meta) {
        const lines = [];

        if (!meta) return lines;
        if (meta.codigo) lines.push(`Codigo: ${meta.codigo}`);
        if (meta.porcentaje !== undefined) lines.push(`Participacion: ${percent(meta.porcentaje || 0, 1)}`);
        if (meta.dano_principal) lines.push(`Daño principal: ${formatTrendCriteriaLabel(meta.dano_principal)}`);
        if (meta.ultima_falla) lines.push(`Ultima falla: ${meta.ultima_falla}`);

        const locations = trendMetaList(meta.ubicaciones, 'ubicacion');
        if (locations) lines.push(`Ubicaciones: ${locations}`);

        const damages = trendMetaList(meta.danos, 'estado');
        if (damages) lines.push(`Daños: ${damages}`);

        return lines;
    }

    function trendDamageTooltipLines(meta) {
        const lines = [];

        if (!meta) return lines;
        if (meta.porcentaje !== undefined) lines.push(`Participacion: ${percent(meta.porcentaje || 0, 1)}`);
        if (meta.ultima_falla) lines.push(`Ultima falla: ${meta.ultima_falla}`);
        if (meta.historial_total !== undefined) {
            lines.push(`Historial del periodo: ${number(meta.historial_total || 0)} registros`);
        }
        if (meta.componentes_afectados !== undefined) {
            lines.push(`Componentes afectados: ${number(meta.componentes_afectados || 0)}`);
        }

        const components = trendMetaList(meta.componentes, 'componente', 4);
        if (components) lines.push(`Componentes: ${components}`);

        return lines;
    }

    function trendLocationTooltipLines(meta) {
        const lines = [];

        if (!meta) return lines;

        const components = trendMetaList(meta.componentes, 'componente', 4);
        if (components) lines.push(`Componentes: ${components}`);

        const damages = trendMetaList(meta.danos, 'estado', 3);
        if (damages) lines.push(`Daños principales: ${damages}`);

        return lines;
    }

    function indicatorChartPalette(label) {
        const normalized = normalizeStatusText(label);

        if (normalized.includes('requiere cambio') || normalized.includes('elongacion')) {
            return {
                background: 'rgba(239, 68, 68, 0.92)',
                border: '#dc2626',
                text: '#ffffff'
            };
        }

        if (normalized.includes('requiere revision')) {
            return {
                background: 'rgba(245, 158, 11, 0.92)',
                border: '#d97706',
                text: '#ffffff'
            };
        }

        if (normalized.includes('desgaste sever') || normalized.includes('desgaste moder')) {
            return {
                background: 'rgba(249, 115, 22, 0.92)',
                border: '#ea580c',
                text: '#ffffff'
            };
        }

        if (normalized.includes('cambiado')) {
            return {
                background: 'rgba(59, 130, 246, 0.92)',
                border: '#2563eb',
                text: '#ffffff'
            };
        }

        if (normalized.includes('buen estado') || normalized.includes('estable')) {
            return {
                background: 'rgba(16, 185, 129, 0.9)',
                border: '#059669',
                text: '#ffffff'
            };
        }

        return {
            background: 'rgba(100, 116, 139, 0.9)',
            border: '#475569',
            text: '#ffffff'
        };
    }

    function createTrendDoughnutChart(canvasId, chartData, title) {
        const canvas = document.getElementById(canvasId);
        const values = Array.isArray(chartData?.data) ? chartData.data.map((value) => Number(value || 0)) : [];

        if (!canvas || !values.some((value) => value > 0)) {
            return null;
        }

        const colors = ['#2563eb', '#ef4444', '#10b981', '#f59e0b', '#7c3aed', '#0f766e', '#db2777', '#64748b'];

        return new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            color: '#334155',
                            font: { size: 10, weight: 700 }
                        }
                    },
                    title: {
                        display: true,
                        text: title,
                        color: '#0f172a',
                        font: { size: 11, weight: 800 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((sum, value) => sum + Number(value || 0), 0);
                                const value = Number(context.raw || 0);
                                const pct = total ? ((value / total) * 100).toFixed(1) : '0.0';

                                return `${context.label}: ${number(value)} (${pct}%)`;
                            },
                            afterLabel: (context) => {
                                const meta = chartData.meta?.[context.dataIndex] || null;

                                return canvasId.toLowerCase().includes('damage')
                                    ? trendDamageTooltipLines(meta)
                                    : trendComponentTooltipLines(meta);
                            }
                        }
                    }
                }
            }
        });
    }

    function createTrendHorizontalBarChart(canvasId, chartData, title, options = {}) {
        const canvas = document.getElementById(canvasId);
        const values = Array.isArray(chartData?.data) ? chartData.data.map((value) => Number(value || 0)) : [];

        if (!canvas || !values.some((value) => value > 0)) {
            return null;
        }

        const isDamageChart = Boolean(options.damageMode) || canvasId.toLowerCase().includes('damagebar');
        const labels = Array.isArray(chartData?.labels) ? chartData.labels : [];
        const indicatorKeys = labels.map((label, index) => {
            if (isDamageChart) {
                return chartData.meta?.[index]?.estado || label;
            }

            return chartData.principal?.[index]
                || chartData.meta?.[index]?.dano_principal
                || chartData.meta?.[index]?.danos?.[0]?.estado
                || '';
        });
        const palettes = indicatorKeys.map((label) => indicatorChartPalette(label));

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: options.datasetLabel || (isDamageChart ? 'Daños por tipo de daño' : 'Daños por componente'),
                    data: values,
                    backgroundColor: palettes.map((item) => item.background),
                    borderColor: palettes.map((item) => item.border),
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    minBarLength: 6,
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: title,
                        color: '#0f172a',
                        font: { size: 11, weight: 800 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            label: (context) => {
                                const meta = chartData.meta?.[context.dataIndex] || null;
                                const pct = chartData.porcentajes?.[context.dataIndex] ?? meta?.porcentaje ?? null;
                                const principal = chartData.principal?.[context.dataIndex] || null;

                                if (isDamageChart) {
                                    return pct !== null
                                        ? `${number(context.raw || 0)} componentes/ubicaciones con este ultimo daño (${percent(pct, 1)})`
                                        : `${number(context.raw || 0)} componentes/ubicaciones con este ultimo daño`;
                                }

                                return principal
                                    ? `${number(context.raw || 0)} daños - ${formatTrendCriteriaLabel(principal)}`
                                    : `${number(context.raw || 0)} daños`;
                            },
                            afterLabel: (context) => {
                                const meta = chartData.meta?.[context.dataIndex] || null;

                                if (isDamageChart) {
                                    return trendDamageTooltipLines(meta);
                                }

                                return canvasId.toLowerCase().includes('location')
                                    ? trendLocationTooltipLines(meta)
                                    : trendComponentTooltipLines(meta);
                            }
                        }
                    },
                    datalabels: {
                        display: (context) => Number(context.raw || 0) > 0,
                        color: (context) => palettes[context.dataIndex]?.text || '#ffffff',
                        font: {
                            size: 11,
                            weight: '800'
                        },
                        formatter: (value) => number(value, 0)
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        ticks: { precision: 0, color: '#64748b' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: '#334155',
                            font: { size: 10, weight: 700 }
                        }
                    }
                }
            }
        });
    }

    function createTrendStackedHorizontalBarChart(canvasId, chartData, title) {
        const canvas = document.getElementById(canvasId);
        const componentLabels = Array.isArray(chartData?.labels) ? chartData.labels : [];
        const series = Array.isArray(chartData?.series) ? chartData.series : [];
        const metaComponents = Array.isArray(chartData?.meta?.componentes) ? chartData.meta.componentes : [];
        const hasData = series.some((serie) => Array.isArray(serie.data) && serie.data.some((value) => Number(value || 0) > 0));

        if (!canvas || !componentLabels.length || !hasData) {
            return null;
        }

        const reducerLabels = series.map((serie, index) => serie.label || `Reductor ${index + 1}`);
        const datasets = componentLabels
            .map((componentLabel, componentIndex) => {
                const data = series.map((serie) => Number(serie.data?.[componentIndex] || 0));
                const segmentMeta = series.map((serie) => serie.meta?.[componentIndex] || null);
                const componentMeta = metaComponents[componentIndex] || null;
                const indicatorKey = componentMeta?.dano_principal
                    || segmentMeta.find((item) => item?.ultimo_dano)?.ultimo_dano
                    || segmentMeta.find((item) => Array.isArray(item?.danos) && item.danos.length)?.danos?.[0]?.estado
                    || '';
                const palette = indicatorChartPalette(indicatorKey);

                return {
                    label: componentLabel || `Componente ${componentIndex + 1}`,
                    data,
                    backgroundColor: palette.background,
                    borderColor: palette.border,
                    borderWidth: 2,
                    borderRadius: 7,
                    borderSkipped: false,
                    stack: 'fallas',
                    componentIndex,
                    textColor: palette.text,
                    segmentMeta
                };
            })
            .filter((dataset) => dataset.data.some((value) => value > 0));

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: reducerLabels,
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            boxWidth: 10,
                            color: '#334155',
                            font: { size: 10, weight: 700 }
                        }
                    },
                    title: {
                        display: true,
                        text: title,
                        color: '#0f172a',
                        font: { size: 11, weight: 800 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            title: (context) => {
                                return context[0]?.label || 'Sin reductor / servo-reductor';
                            },
                            label: (context) => `${context.dataset.label || 'Sin componente'}: ${number(context.raw || 0)} daños`,
                            afterLabel: (context) => {
                                const componentIndex = context.dataset.componentIndex;
                                const componentMeta = chartData.meta?.componentes?.[componentIndex] || null;
                                const segmentMeta = context.dataset.segmentMeta?.[context.dataIndex] || null;
                                const lines = [];

                                if (segmentMeta?.ubicacion) lines.push(`Reductor/ubicacion: ${segmentMeta.ubicacion}`);
                                if (segmentMeta?.ultimo_dano) lines.push(`Ultimo daño: ${formatTrendCriteriaLabel(segmentMeta.ultimo_dano)}`);
                                if (segmentMeta?.ultima_falla) lines.push(`Ultimo registro: ${segmentMeta.ultima_falla}`);

                                const damages = trendMetaList(segmentMeta?.danos, 'estado', 4);
                                if (damages) lines.push(`Daños en este reductor: ${damages}`);

                                return [...lines, ...trendComponentTooltipLines(componentMeta)];
                            }
                        }
                    },
                    datalabels: {
                        display: (context) => Number(context.raw || 0) > 0,
                        formatter: (value) => number(value, 0),
                        color: (context) => context.dataset.textColor || '#ffffff',
                        font: { size: 10, weight: '800' }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: true,
                        grid: { color: 'rgba(148, 163, 184, 0.14)' },
                        title: {
                            display: true,
                            text: 'Total de daños',
                            color: '#64748b',
                            font: { size: 11, weight: 800 }
                        },
                        ticks: {
                            precision: 0,
                            color: '#334155',
                            font: { size: 10, weight: 700 }
                        }
                    },
                    y: {
                        stacked: true,
                        grid: { color: 'rgba(148, 163, 184, 0.14)' },
                        title: {
                            display: true,
                            text: 'Reductor / Servo-Reductor / ubicaciones',
                            color: '#64748b',
                            font: { size: 11, weight: 800 }
                        },
                        ticks: {
                            color: '#334155',
                            font: { size: 10, weight: 700 }
                        }
                    }
                }
            }
        });
    }

    function renderTrendCriteria(node, criteria) {
        if (!node) return;

        node.innerHTML = (criteria || [])
            .map((label) => `<span class="severity-pill ${trendCriteriaPillClass(label)}">${escapeHtml(formatTrendCriteriaLabel(label))}</span>`)
            .join('');
    }

    function trendCriteriaMarkup(criteria, periodo = {}) {
        const periodChip = periodo?.label
            ? `<span class="severity-pill revision">Periodo: ${escapeHtml(periodo.label)}</span>`
            : '';
        const criteriaChips = (criteria || [])
            .map((label) => `<span class="severity-pill ${trendCriteriaPillClass(label)}">${escapeHtml(formatTrendCriteriaLabel(label))}</span>`)
            .join('');

        return `${periodChip}${criteriaChips}`;
    }

    function formatTrendCriteriaLabelLegacy(label) {
        const raw = String(label || '');
        const lower = raw.toLowerCase();

        if (lower.includes('requiere cambio')) return 'Dañado - Requiere cambio';
        if (lower.includes('desgaste sever')) return 'Desgaste severo';
        if (lower.includes('desgaste moder')) return 'Desgaste moderado';
        if (lower.includes('elong')) return 'Elongación fuera de límite (> 1.46%)';

        return raw;
    }

    function formatTrendCriteriaLabel(label) {
        const raw = String(label || '');
        const normalized = raw
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        if (normalized.includes('requiere cambio')) return 'Dañado - Requiere cambio';
        if (normalized.includes('requiere revision')) return 'Requiere revision';
        if (normalized.includes('cambiado')) return 'Cambiado';
        if (normalized.includes('desgaste sever')) return 'Desgaste severo';
        if (normalized.includes('desgaste moder')) return 'Desgaste moderado';
        if (normalized.includes('elong')) return 'Elongacion fuera de limite (> 1.46%)';

        return raw;
    }

    function trendCriteriaPillClass(label) {
        const normalized = normalizeStatusText(formatTrendCriteriaLabel(label));

        if (normalized.includes('requiere cambio')) return 'critico';
        if (normalized.includes('requiere revision')) return 'revision';
        if (normalized.includes('desgaste')) return 'severo';
        if (normalized.includes('elong')) return 'critico';
        if (normalized.includes('cambiado')) return 'cambiado';

        return 'estable';
    }

    function normalizeTrendLabel(label) {
        const raw = String(label || '');

        return raw.replace(/dias/gi, 'días');
    }

    function trendWindowColorForLabel(label) {
        const normalized = String(label || '').toLowerCase();

        if (normalized.includes('52') || normalized.includes('30')) {
            return {
                border: '#047857',
                fill: 'rgba(16, 185, 129, 0.88)',
                soft: 'rgba(16, 185, 129, 0.22)'
            };
        }

        if (normalized.includes('12') || normalized.includes('14')) {
            return {
                border: '#dc2626',
                fill: 'rgba(239, 68, 68, 0.88)',
                soft: 'rgba(239, 68, 68, 0.22)'
            };
        }

        if (normalized.includes('4') || normalized.includes('7')) {
            return {
                border: '#d97706',
                fill: 'rgba(245, 158, 11, 0.9)',
                soft: 'rgba(245, 158, 11, 0.24)'
            };
        }

        return {
            border: '#475569',
            fill: 'rgba(100, 116, 139, 0.85)',
            soft: 'rgba(100, 116, 139, 0.2)'
        };
    }

    function toneToMiniStat(tone) {
        switch (tone) {
            case 'danger':
                return 'danger';
            case 'warning':
                return 'warning';
            case 'success':
                return 'success';
            default:
                return 'info';
        }
    }

    function trendPillClass(trendName, currentValue) {
        if (trendName === 'up') return 'critico';
        if (trendName === 'down') return 'estable';

        return Number(currentValue || 0) > 0 ? 'severo' : 'revision';
    }

    function trendDeltaLabel(delta, trendName) {
        const signed = `${delta > 0 ? '+' : ''}${delta}`;

        if (trendName === 'up') return `Alza ${signed}`;
        if (trendName === 'down') return `Baja ${signed}`;

        return delta === 0 ? 'Estable' : signed;
    }

    function trendDeltaText(variation) {
        if (!variation) return 'Sin comparativo';

        const difference = Number(variation.diferencia || 0);
        const percentage = Number(variation.porcentaje || 0);

        if (!difference) return 'Sin cambio vs mes anterior';

        return `${difference > 0 ? '+' : ''}${number(difference)} vs mes anterior (${percent(percentage, 1)})`;
    }

    function trendDeltaClass(variation) {
        if (!variation) return 'info';

        if (variation.tendencia === 'up') return 'danger';
        if (variation.tendencia === 'down') return 'success';

        return 'info';
    }

    function ensureChartShell(canvasId, prefix, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const container = canvas.closest('.chart-container');
        if (!container) return;

        container.dataset.chartContainer = prefix;
        if (options.tall) container.classList.add('tall');

        let shell = container.parentElement;
        if (!shell.classList.contains('chart-shell')) {
            const nextShell = document.createElement('div');
            nextShell.className = `chart-shell${options.compact ? ' compact' : ''}`;
            container.parentNode.insertBefore(nextShell, container);
            nextShell.appendChild(container);
            shell = nextShell;
        } else if (options.compact) {
            shell.classList.add('compact');
        }

        if (!document.getElementById(`${prefix}Loader`)) {
            shell.insertAdjacentHTML('afterbegin', loaderMarkup(`${prefix}Loader`));
        }

        if (!document.getElementById(`${prefix}Empty`)) {
            shell.insertAdjacentHTML('afterbegin', `<div class="chart-empty-state" id="${prefix}Empty" hidden></div>`);
        }
    }

    function setChartState(prefix, empty, title = '', message = '', icon = 'fa-database') {
        const loader = document.getElementById(`${prefix}Loader`);
        const emptyNode = document.getElementById(`${prefix}Empty`);
        const container = document.querySelector(`[data-chart-container="${prefix}"]`);

        if (loader) loader.classList.add('is-hidden');
        if (container) container.hidden = empty;
        if (emptyNode) {
            emptyNode.hidden = !empty;
            if (empty) emptyNode.innerHTML = emptyMarkup(title, message, icon);
        }
    }

    function ensureAfterHeading(card, id, html) {
        if (document.getElementById(id)) return;
        const heading = card.querySelector('h3');
        if (heading) heading.insertAdjacentHTML('afterend', html);
    }

    function ensureAfterElement(referenceId, id, html) {
        if (document.getElementById(id)) return;
        const reference = document.getElementById(referenceId);
        if (reference) reference.insertAdjacentHTML('afterend', html);
    }

    function updateCardTitle(card, text, iconClass) {
        const title = card.querySelector('h3 span');
        const icon = card.querySelector('h3 i');
        if (title) title.textContent = text;
        if (icon) icon.className = iconClass;
    }

    function cardFromCanvas(id) {
        return document.getElementById(id)?.closest('.chart-card') ?? null;
    }

    function loaderMarkup(id) {
        return `
            <div class="card-loader" id="${id}">
                <div class="skeleton-line large"></div>
                <div class="skeleton-line medium"></div>
                <div class="skeleton-line large"></div>
                <div class="skeleton-line small"></div>
            </div>
        `;
    }

    function miniStats(items) {
        return items.map(([label, value, meta, tone]) => `
            <div class="mini-stat ${tone || 'info'}">
                <div class="mini-stat-label">${escapeHtml(String(label))}</div>
                <div class="mini-stat-value">${escapeHtml(String(value))}</div>
                <div class="mini-stat-meta">${escapeHtml(String(meta || ''))}</div>
            </div>
        `).join('');
    }

    function infoBox(message) {
        return `<div class="breakdown-item"><div class="breakdown-meta">${escapeHtml(message)}</div></div>`;
    }

    function emptyMarkup(title, message, icon) {
        return `<i class="fas ${escapeHtml(icon)}"></i><div style="font-weight: 800; color: #0f172a;">${escapeHtml(title)}</div><div>${escapeHtml(message)}</div>`;
    }

    function lineaOptions(selectedId) {
        return (data.lineas || []).map((linea) => `<option value="${linea.id}" ${Number(linea.id) === Number(selectedId) ? 'selected' : ''}>${escapeHtml(linea.nombre)}</option>`).join('');
    }

    function elongacionLineaOptions(selectedId) {
        const lineas = Array.isArray(data.elongaciones?.lineas)
            ? data.elongaciones.lineas.filter((linea) => !linea.sin_datos && Array.isArray(linea.labels) && linea.labels.length)
            : [];

        return lineas.map((linea) => `<option value="${linea.linea_id}" ${Number(linea.linea_id) === Number(selectedId) ? 'selected' : ''}>${escapeHtml(linea.linea)}</option>`).join('');
    }

    function bindResponsiveChartResize() {
        const resizeCharts = () => {
            window.clearTimeout(resizeChartsTimer);
            resizeChartsTimer = window.setTimeout(() => {
                if (charts.elongaciones) {
                    renderElongaciones();
                }

                Object.entries(charts).forEach(([key, chart]) => {
                    if (!chart || key === 'elongaciones' || !chart.canvas || !chart.canvas.isConnected) return;

                    try {
                        chart.resize();
                        chart.update('none');
                    } catch (error) {
                        // Ignora instancias que hayan sido destruidas por estados sin datos.
                    }
                });
            }, 140);
        };

        window.addEventListener('resize', resizeCharts, { passive: true });
        window.addEventListener('orientationchange', resizeCharts);
    }

    function destroy(instance) {
        if (!instance) return null;

        try {
            instance.destroy();
        } catch (error) {
            // Ignora instancias que ya no pueden destruirse de nuevo.
        }

        return null;
    }

    function destroyChartForCanvas(canvasId, instance = null) {
        const canvas = document.getElementById(canvasId);
        const boundChart = canvas && window.Chart && typeof Chart.getChart === 'function'
            ? Chart.getChart(canvas)
            : null;

        if (boundChart && boundChart !== instance) {
            destroy(boundChart);
        }

        return destroy(instance);
    }

    function hideLoader(id) {
        const loader = document.getElementById(id);
        if (loader) loader.classList.add('is-hidden');
    }

    function number(value, decimals = 0) {
        return Number(value || 0).toLocaleString('es-MX', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function percent(value, decimals = 1) {
        return `${number(value, decimals)}%`;
    }

    function daysLabel(value) {
        if (value === null || value === undefined || Number.isNaN(Number(value))) return 'Sin fecha programada';
        const days = Number(value);
        if (days < 0) return `Vencido por ${Math.abs(days)} dias`;
        if (days === 0) return 'Vence hoy';
        if (days === 1) return 'Vence en 1 dia';
        return `Vence en ${days} dias`;
    }

    function elapsedDaysLabel(value) {
        if (value === null || value === undefined || Number.isNaN(Number(value))) return 'Sin antiguedad disponible';
        const days = Math.max(Math.round(Number(value)), 0);
        if (days === 0) return 'Revisado hoy';
        if (days === 1) return 'Sin revisar desde 1 dia';
        return `Sin revisar desde ${days} dias`;
    }

    function severityClass(level) {
        switch (level) {
            case 'critico':
            case 'alta':
                return 'critico';
            case 'severo':
            case 'moderado':
            case 'media':
            case 'sin_fecha':
                return 'severo';
            case 'cambiado':
                return 'cambiado';
            default:
                return 'estable';
        }
    }

    function planClass(level) {
        switch (level) {
            case 'alta':
                return 'critico';
            case 'media':
            case 'sin_fecha':
                return 'severo';
            default:
                return 'estable';
        }
    }

    function normalizeStatusText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function severityFromEstado(estado) {
        const normalized = normalizeStatusText(estado);

        if (normalized.includes('requiere cambio')) return 'critico';
        if (normalized.includes('desgaste sever')) return 'severo';
        if (normalized.includes('desgaste moder')) return 'moderado';
        if (normalized.includes('requiere revision')) return 'revision';
        if (normalized.includes('elongacion')) return 'critico';
        if (normalized.includes('cambiado')) return 'cambiado';
        if (normalized.includes('buen estado') || normalized.includes('estable')) return 'estable';

        return 'estable';
    }

    function hexToRgba(hex, alpha = 1) {
        const normalized = String(hex || '').trim();
        const match = normalized.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

        if (!match) return '';

        let value = match[1];
        if (value.length === 3) {
            value = value.split('').map((char) => char + char).join('');
        }

        const r = parseInt(value.slice(0, 2), 16);
        const g = parseInt(value.slice(2, 4), 16);
        const b = parseInt(value.slice(4, 6), 16);

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function severityPillStyle(estado, color) {
        const normalizedColor = String(color || '').trim();
        if (!normalizedColor.startsWith('#')) return '';

        const background = hexToRgba(normalizedColor, 0.14);
        const border = hexToRgba(normalizedColor, 0.24);

        if (!background || !border) return '';

        return `background: ${background}; color: ${normalizedColor}; border-color: ${border};`;
    }

    function trend(values) {
        const series = Array.isArray(values) ? values.map((value) => Number(value || 0)) : [];
        const total = series.length;
        if (!total) return [];

        const sumX = series.reduce((sum, _, index) => sum + index, 0);
        const sumY = series.reduce((sum, value) => sum + value, 0);
        const sumXY = series.reduce((sum, value, index) => sum + (index * value), 0);
        const sumXX = series.reduce((sum, _, index) => sum + (index * index), 0);
        const divisor = (total * sumXX) - (sumX * sumX);
        if (!divisor) return [...series];

        const slope = ((total * sumXY) - (sumX * sumY)) / divisor;
        const intercept = (sumY - (slope * sumX)) / total;
        return series.map((_, index) => Number((intercept + (slope * index)).toFixed(2)));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
