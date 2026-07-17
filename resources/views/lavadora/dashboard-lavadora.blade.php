@extends('layouts.app')

@section('title', 'Menú | Lavadora')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- HEADER MEJORADO --}}
        <div class="mb-10 animate-fade-in">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
            <div class="mb-2 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                <!-- Imagen a la izquierda -->
                <div class="mb-2 flex h-24 w-24 items-center justify-center sm:mb-4 sm:h-32 sm:w-32">
                            <img src="{{ asset('images/icono-maquina.png') }}" 
                                 alt="Icono de Maquinaria" 
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        </div>
                <!-- Barra decorativa (opcional, puedes mantenerla o quitarla) -->
                <div class="h-10 w-2 bg-gradient-to-b from-gray-800 to-gray-600 rounded-full"></div>
                
                <h1 class="text-3xl font-black tracking-tight text-gray-800 sm:text-4xl">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-800 to-gray-600">
                        LAVADORA
                    </span>
                </h1>
            </div>
        </div>
                {{-- BADGE DE ESTADO --}}
                <div class="hidden sm:block">
                    <div class="bg-white/80 backdrop-blur-sm px-4 py-2 rounded-2xl shadow-sm border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-gray-700">Legado Ave Fenix</span>
                            </div>
                            <div class="h-4 w-px bg-gray-300"></div>
                            <span class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRID DE OPCIONES MEJORADO CON COLOR RGB 31 35 72 --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            
            {{-- ANALISIS LAVADORA --}}
            <a href="{{ route('analisis-lavadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                {{-- Barra superior con el color especificado --}}
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                
                {{-- Efecto de brillo hover con el color especificado --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        {{-- Icono con animación usando el color especificado --}}
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-chart-pie text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        {{-- Contenido --}}
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS LAVADORA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registra y consulta los análisis de componentes
                        </p>
                        
                        {{-- Indicador de acción con el color especificado --}}
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- ELONGACION LAVADORA --}}
            <a href="{{ route('elongaciones.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-ruler-combined text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ELONGACIÓN CADENA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registro y seguimiento de elongaciones
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- HISTORICO --}}
            <a href="{{ route('historico-revisados.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-history text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            HISTÓRICO DE REVISADOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza registros de componentes revisados
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- PLAN DE ACCION --}}
            <a href="{{ route('plan-accion.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-tasks text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            PLAN DE ACCIÓN
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Administración y seguimiento de acciones preventivas
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- ANALISIS 52-12-4 --}}
            <a href="{{ route('analisis-tendencia-mensual.lavadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-search text-xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS 52 - 12 - 4 / 30 - 14 - 7
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualizacion automatica de tendencia de daños
                        </p>
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            @if($canViewLavadoraCostsModule ?? (auth()->user()?->canViewLavadoraCostModule() ?? false))
                 {{-- COSTOS --}}
            <a href="{{ route('lavadora.costos.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-coins text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            COSTOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza gastos, presupuestos y tendencias por componente y lavadora
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif
        </div>

        {{-- FOOTER CON ESTADÍSTICAS RÁPIDAS (OPCIONAL) --}}
        @php
            $trendModules = [
                ['key' => '52124', 'title' => 'Analisis 52-12-4'],
                ['key' => '30147', 'title' => 'Analisis 30-14-7'],
            ];
        @endphp

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.ChartDataLabels) {
            Chart.register(ChartDataLabels);
        }

        const dashboardTrendDatasets = {
            '52124': @json($analisis52124 ?? []),
            '30147': @json($analisis30147 ?? []),
        };

        const dashboardTrendMobileQuery = window.matchMedia('(max-width: 768px)');
        const dashboardTrendStates = {};

        function normalizeTrendLabel(label) {
            return String(label || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        }

        function formatMetric(value) {
            return new Intl.NumberFormat('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(value || 0));
        }

        function getWindowPresentation(label) {
            const normalized = normalizeTrendLabel(label);

            if (normalized.includes('52')) {
                return { role: 'historico acumulado', color: '#16a34a', fill: 'rgba(34, 197, 94, 0.18)', dashed: true };
            }

            if (normalized.includes('30')) {
                return { role: 'referencia operativa', color: '#16a34a', fill: 'rgba(34, 197, 94, 0.18)', dashed: true };
            }

            if (normalized.includes('12')) {
                return { role: 'impacto trimestral', color: '#dc2626', fill: 'rgba(239, 68, 68, 0.16)', dashed: false };
            }

            if (normalized.includes('14')) {
                return { role: 'respuesta reciente', color: '#dc2626', fill: 'rgba(239, 68, 68, 0.16)', dashed: false };
            }

            return { role: 'control inmediato', color: '#f97316', fill: 'rgba(249, 115, 22, 0.18)', dashed: false };
        }

        function compareSeries(series) {
            const current = Number(series[series.length - 1] || 0);
            const previous = Number(series[series.length - 2] || 0);
            const diff = current - previous;
            const percentage = previous === 0 ? null : (diff / previous) * 100;

            return { current, previous, diff, percentage, trend: diff < 0 ? 'down' : (diff > 0 ? 'up' : 'stable') };
        }

        function zeroStreak(series) {
            let streak = 0;

            for (let index = series.length - 1; index >= 0; index -= 1) {
                if (Number(series[index] || 0) !== 0) {
                    break;
                }

                streak += 1;
            }

            return streak;
        }

        function deltaChipCopy(delta) {
            if (delta.diff < 0) {
                const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
                return `Bajo ${formatMetric(Math.abs(delta.diff))}${pct}`;
            }

            if (delta.diff > 0) {
                const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
                return `Subio ${formatMetric(delta.diff)}${pct}`;
            }

            return 'Sin cambio';
        }

        function buildWindows(row) {
            return (Array.isArray(row?.series) ? row.series : []).map((serie) => {
                const presentation = getWindowPresentation(serie.label);

                return {
                    label: serie.label || 'Ventana',
                    data: Array.isArray(serie.data) ? serie.data.map((value) => Number(value || 0)) : [],
                    role: presentation.role,
                    color: presentation.color,
                    fill: presentation.fill,
                    dashed: presentation.dashed
                };
            });
        }

        function getRowForState(state) {
            const rows = state.rows || [];

            return rows.find((row) => String(row.linea_id) === String(state.lineId))
                || rows.find((row) => !row.sin_datos && Array.isArray(row.labels) && row.labels.length)
                || rows[0]
                || null;
        }

        function renderTrendCards(state, windows) {
            const cardsHost = document.getElementById(`dashboardTrendCards${state.key}`);

            if (!cardsHost) {
                return;
            }

            if (!windows.length) {
                cardsHost.innerHTML = '';
                return;
            }

            cardsHost.innerHTML = windows.map((windowItem) => {
                const delta = compareSeries(windowItem.data);
                const deltaClass = delta.diff < 0
                    ? 'dashboard-trend-window-delta--positive'
                    : (delta.diff > 0 ? 'dashboard-trend-window-delta--alert' : 'dashboard-trend-window-delta--neutral');
                const deltaIcon = delta.diff < 0 ? 'fa-arrow-down' : (delta.diff > 0 ? 'fa-arrow-up' : 'fa-minus');

                return `
                    <article class="dashboard-trend-window-card" style="--dashboard-trend-accent: ${windowItem.color}">
                        <div class="dashboard-trend-window-label">${windowItem.label}</div>
                        <div class="dashboard-trend-window-value">${formatMetric(delta.current)}</div>
                        <div class="dashboard-trend-window-role">${windowItem.role}</div>
                        <div class="dashboard-trend-window-delta ${deltaClass}">
                            <i class="fas ${deltaIcon}"></i>
                            <span>${deltaChipCopy(delta)} vs periodo anterior</span>
                        </div>
                    </article>
                `;
            }).join('');
        }

        function renderTrendStatus(state, row, windows) {
            const statusCard = document.getElementById(`dashboardTrendStatusCard${state.key}`);
            const titleNode = document.getElementById(`dashboardTrendStatusTitle${state.key}`);
            const copyNode = document.getElementById(`dashboardTrendStatusCopy${state.key}`);
            const captionNode = document.getElementById(`dashboardTrendCaption${state.key}`);

            if (!statusCard || !titleNode || !copyNode || !captionNode) {
                return;
            }

            const latestLabel = Array.isArray(row?.labels) && row.labels.length
                ? row.labels[row.labels.length - 1]
                : 'Sin corte';

            if (!row || row.sin_datos || windows.length < 3 || !Array.isArray(row.labels) || !row.labels.length) {
                statusCard.className = 'dashboard-trend-status dashboard-trend-status--neutral';
                titleNode.textContent = 'Sin tendencia disponible';
                copyNode.textContent = 'Esta lavadora todavia no tiene historial suficiente para mostrar una lectura comparable en este dashboard.';
                captionNode.textContent = 'Sin corte disponible.';
                return;
            }

            const baseWindow = windows[0];
            const midWindow = windows[1];
            const recentWindow = windows[2];
            const midDelta = compareSeries(midWindow.data);
            const recentDelta = compareSeries(recentWindow.data);
            const recentZeroRun = zeroStreak(recentWindow.data);

            let tone = 'neutral';
            let title = 'Monitoreo en curso';
            let copy = `${recentWindow.label} no presenta repunte, pero todavia se requiere continuidad para que ${baseWindow.label} refleje una mejora mas amplia.`;

            if ((recentWindow.data.length > 1 && recentDelta.diff <= 0 && midDelta.diff < 0) || (recentDelta.current === 0 && recentZeroRun >= 2 && midDelta.diff <= 0)) {
                tone = 'positive';

                if (recentDelta.current === 0 && recentZeroRun >= 2) {
                    title = 'Implementacion funcionando';
                    copy = `${recentWindow.label} se mantiene en 0 daños durante ${recentZeroRun} periodos y ${midWindow.label} sigue bajando frente al corte anterior.`;
                } else {
                    title = 'Tendencia de baja confirmada';
                    copy = `${recentWindow.label} ${deltaChipCopy(recentDelta).toLowerCase()} y ${midWindow.label} tambien viene a la baja.`;
                }
            } else if (recentDelta.diff > 0 || midDelta.diff > 0) {
                tone = 'alert';
                title = 'Repunte reciente';
                copy = `${recentWindow.label} o ${midWindow.label} subieron frente al corte anterior. Conviene revisar la ejecucion antes de que el historico vuelva a crecer.`;
            }

            statusCard.className = `dashboard-trend-status dashboard-trend-status--${tone}`;
            titleNode.textContent = title;
            copyNode.textContent = copy;
            captionNode.textContent = `Corte actual: ${latestLabel}.`;
        }

        function updateTrendViewButtons(state) {
            document.querySelectorAll(`[data-dashboard-trend-module="${state.key}"]`).forEach((button) => {
                button.classList.toggle('active', button.dataset.dashboardTrendType === state.chartType);
            });
        }

        function renderTrendChart(state) {
            const row = getRowForState(state);
            const windows = buildWindows(row);
            const labels = Array.isArray(row?.labels) ? row.labels : [];
            const emptyNode = document.getElementById(`dashboardTrendEmpty${state.key}`);
            const canvas = document.getElementById(`dashboardTrendChart${state.key}`);
            const isBar = state.chartType === 'bar';
            const isSmall = dashboardTrendMobileQuery.matches;

            renderTrendCards(state, windows);
            renderTrendStatus(state, row, windows);
            updateTrendViewButtons(state);

            if (state.chart) {
                state.chart.destroy();
                state.chart = null;
            }

            if (!canvas || !emptyNode) {
                return;
            }

            if (!row || row.sin_datos || !labels.length || !windows.length) {
                emptyNode.hidden = false;
                emptyNode.textContent = 'Sin tendencia disponible para la lavadora seleccionada.';
                canvas.style.display = 'none';
                return;
            }

            emptyNode.hidden = true;
            canvas.style.display = '';

            state.chart = new Chart(canvas.getContext('2d'), {
                type: state.chartType,
                data: {
                    labels,
                    datasets: windows.map((windowItem, datasetIndex) => ({
                        label: windowItem.label,
                        data: windowItem.data,
                        borderColor: windowItem.color,
                        backgroundColor: windowItem.fill,
                        borderWidth: isBar ? 0 : (datasetIndex === windows.length - 1 ? 4 : 3),
                        borderDash: isBar ? [] : (windowItem.dashed ? [8, 6] : []),
                        pointBackgroundColor: windowItem.color,
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
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 10,
                                padding: 14,
                                color: '#334155',
                                font: {
                                    size: isSmall ? 10 : 12,
                                    weight: '700'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#fff',
                            bodyColor: '#e2e8f0',
                            callbacks: {
                                label: (context) => {
                                    const currentValue = Number(context.parsed.y || 0);
                                    const previousValue = Number(context.dataset.data?.[context.dataIndex - 1] || 0);
                                    const diff = currentValue - previousValue;
                                    const change = context.dataIndex === 0
                                        ? 'Sin comparativo anterior'
                                        : (diff < 0
                                            ? `Bajo ${formatMetric(Math.abs(diff))}`
                                            : (diff > 0 ? `Subio ${formatMetric(diff)}` : 'Sin cambio'));

                                    return `${context.dataset.label}: ${formatMetric(currentValue)} daños. ${change}.`;
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
                            formatter: (value) => formatMetric(value),
                            font: {
                                size: isSmall ? 9 : 11,
                                weight: '800'
                            }
                        } : {
                            display: (context) => !isSmall && context.dataIndex === labels.length - 1 && Number(context.raw || 0) > 0,
                            anchor: 'end',
                            align: 'top',
                            offset: 6,
                            clamp: true,
                            clip: false,
                            color: (context) => context.dataset.borderColor,
                            backgroundColor: null,
                            borderRadius: 0,
                            padding: 0,
                            formatter: (value) => formatMetric(value),
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
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#475569',
                                maxRotation: isSmall ? 48 : 0,
                                minRotation: isSmall ? 48 : 0,
                                font: {
                                    size: isSmall ? 10 : 11,
                                    weight: '700'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grace: isBar ? '36%' : '8%',
                            grid: {
                                color: 'rgba(148, 163, 184, 0.18)'
                            },
                            title: {
                                display: true,
                                text: 'Daños registrados',
                                color: '#64748b',
                                font: {
                                    size: 12,
                                    weight: '800'
                                }
                            },
                            ticks: {
                                color: '#64748b',
                                callback: (value) => formatMetric(value)
                            }
                        }
                    }
                }
            });
        }

        function initTrendModule(key) {
            const dataset = dashboardTrendDatasets[key] || {};
            const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
            const select = document.getElementById(`dashboardTrendSelect${key}`);
            const defaultLineId = String(
                dataset.default_linea_id
                || rows.find((row) => !row.sin_datos && Array.isArray(row.labels) && row.labels.length)?.linea_id
                || rows[0]?.linea_id
                || (select ? select.value : '')
            );

            const state = {
                key,
                rows,
                lineId: defaultLineId,
                chartType: 'bar',
                chart: null
            };

            dashboardTrendStates[key] = state;

            if (select) {
                select.value = defaultLineId;
                select.addEventListener('change', function () {
                    state.lineId = this.value;
                    renderTrendChart(state);
                });
            }

            document.querySelectorAll(`[data-dashboard-trend-module="${key}"]`).forEach((button) => {
                button.addEventListener('click', function () {
                    state.chartType = this.dataset.dashboardTrendType || 'bar';
                    renderTrendChart(state);
                });
            });

            renderTrendChart(state);
        }

        initTrendModule('52124');
        initTrendModule('30147');

        window.addEventListener('resize', function () {
            Object.values(dashboardTrendStates).forEach((state) => {
                renderTrendChart(state);
            });
        });
    });
</script>

{{-- ANIMACIONES PERSONALIZADAS --}}
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }
    
    /* Mejoras de accesibilidad */
    a:focus-visible {
        outline: 3px solid rgba(31, 35, 72, 0.5);
        outline-offset: 2px;
    }

    /* Estilo para el hover del título */
    .group:hover h3 {
        color: rgb(245, 192, 37) !important;
    }
    .dashboard-trend-suite {
        margin-top: 48px;
    }

    .dashboard-trend-suite-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .dashboard-trend-kicker {
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgb(31, 35, 72);
        margin-bottom: 6px;
    }

    .dashboard-trend-suite-header h2 {
        margin: 0;
        font-size: clamp(1.75rem, 3vw, 2.4rem);
        font-weight: 900;
        color: #111827;
    }

    .dashboard-trend-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border-radius: 999px;
        border: 1px solid rgba(31, 35, 72, 0.12);
        background: white;
        color: rgb(31, 35, 72);
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .dashboard-trend-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
    }

    .dashboard-trend-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .dashboard-trend-panel {
        min-width: 0;
        border-radius: 28px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.98));
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
        padding: 22px;
    }

    .dashboard-trend-toolbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .dashboard-trend-heading h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #0f172a;
    }

    .dashboard-trend-actions {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .dashboard-trend-select-wrap {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 150px;
    }

    .dashboard-trend-select-wrap span {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .dashboard-trend-select {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: white;
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        padding: 0 14px;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
    }

    .dashboard-trend-view-selector {
        display: inline-flex;
        align-items: center;
        padding: 4px;
        border-radius: 16px;
        background: rgba(241, 245, 249, 0.95);
        border: 1px solid rgba(148, 163, 184, 0.16);
        gap: 4px;
    }

    .dashboard-trend-view-btn {
        border: none;
        border-radius: 12px;
        background: transparent;
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
        padding: 10px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dashboard-trend-view-btn.active {
        background: rgb(31, 35, 72);
        color: white;
        box-shadow: 0 8px 20px rgba(31, 35, 72, 0.18);
    }

    .dashboard-trend-brief {
        display: grid;
        grid-template-columns: minmax(230px, 0.95fr) minmax(0, 1.45fr);
        gap: 16px;
        margin-bottom: 18px;
    }

    .dashboard-trend-status {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 22px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        min-width: 0;
    }

    .dashboard-trend-status::after {
        content: '';
        position: absolute;
        right: -34px;
        bottom: -58px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.12);
    }

    .dashboard-trend-status--positive {
        border-color: rgba(16, 185, 129, 0.24);
        background: linear-gradient(145deg, #ecfdf5, #f8fafc);
    }

    .dashboard-trend-status--positive::after {
        background: rgba(16, 185, 129, 0.16);
    }

    .dashboard-trend-status--alert {
        border-color: rgba(239, 68, 68, 0.22);
        background: linear-gradient(145deg, #fef2f2, #fff7ed);
    }

    .dashboard-trend-status--alert::after {
        background: rgba(239, 68, 68, 0.14);
    }

    .dashboard-trend-status--neutral {
        border-color: rgba(245, 158, 11, 0.22);
        background: linear-gradient(145deg, #fffbeb, #f8fafc);
    }

    .dashboard-trend-status--neutral::after {
        background: rgba(245, 158, 11, 0.14);
    }

    .dashboard-trend-eyebrow,
    .dashboard-trend-window-label {
        position: relative;
        z-index: 1;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .dashboard-trend-status-title {
        position: relative;
        z-index: 1;
        margin-top: 10px;
        font-size: 28px;
        line-height: 1.08;
        font-weight: 900;
        color: #0f172a;
    }

    .dashboard-trend-status-copy {
        position: relative;
        z-index: 1;
        margin: 12px 0 0;
        color: #475569;
        font-size: 14px;
        line-height: 1.55;
    }

    .dashboard-trend-window-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
        gap: 14px;
        min-width: 0;
    }

    .dashboard-trend-window-card {
        position: relative;
        overflow: hidden;
        min-width: 0;
        border-radius: 20px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: white;
        padding: 18px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    }

    .dashboard-trend-window-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--dashboard-trend-accent, rgb(31, 35, 72));
    }

    .dashboard-trend-window-value {
        margin-top: 12px;
        color: #0f172a;
        font-size: 30px;
        line-height: 1.1;
        font-weight: 900;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
    }

    .dashboard-trend-window-role {
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
    }

    .dashboard-trend-window-delta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 14px;
        border-radius: 999px;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.35;
        max-width: 100%;
    }

    .dashboard-trend-window-delta--positive {
        background: #d1fae5;
        color: #065f46;
    }

    .dashboard-trend-window-delta--alert {
        background: #fee2e2;
        color: #991b1b;
    }

    .dashboard-trend-window-delta--neutral {
        background: #fef3c7;
        color: #92400e;
    }

    .dashboard-trend-chart-card {
        border-radius: 24px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: white;
        padding: 18px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
    }

    .dashboard-trend-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .dashboard-trend-chart-title {
        color: #0f172a;
        font-size: 16px;
        font-weight: 800;
    }

    .dashboard-trend-caption {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .dashboard-trend-chart-wrap {
        position: relative;
        height: 380px;
    }

    .dashboard-trend-empty {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        color: #64748b;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.5;
        border-radius: 18px;
        background: linear-gradient(145deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.95));
        border: 1px dashed rgba(148, 163, 184, 0.34);
    }

    @media (max-width: 1280px) {
        .dashboard-trend-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1024px) {
        .dashboard-trend-brief {
            grid-template-columns: 1fr;
        }

        .dashboard-trend-window-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .dashboard-trend-suite {
            margin-top: 40px;
        }

        .dashboard-trend-panel {
            padding: 18px;
            border-radius: 24px;
        }

        .dashboard-trend-toolbar,
        .dashboard-trend-chart-header,
        .dashboard-trend-suite-header {
            flex-direction: column;
            align-items: stretch;
        }

        .dashboard-trend-actions {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }

        .dashboard-trend-select-wrap,
        .dashboard-trend-view-selector {
            width: 100%;
        }

        .dashboard-trend-view-selector {
            justify-content: space-between;
        }

        .dashboard-trend-view-btn {
            flex: 1 1 0;
        }

        .dashboard-trend-status {
            padding: 18px;
        }

        .dashboard-trend-status-title {
            font-size: 24px;
        }

        .dashboard-trend-window-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-trend-chart-wrap {
            height: 320px;
        }
    }
</style>
@endsection
