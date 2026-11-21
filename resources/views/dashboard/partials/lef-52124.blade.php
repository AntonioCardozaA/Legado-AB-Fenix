    <div class="dashboard-panels-full">
        <div class="chart-card lef-dashboard-card">
            <div class="lef-dashboard-header">
                <div>
                    <h3><i class="fas fa-chart-line"></i> <span>52-12-4</span></h3>
                    <p class="lef-dashboard-period">Periodo de análisis: {{ $lefDashboard['period_label'] ?? 'Sin datos disponibles' }}</p>
                    @if($lefUrl ?? null)
                        <a class="mt-2 inline-block text-sm font-semibold text-blue-700 hover:underline" href="{{ $lefUrl }}">Abrir análisis 52-12-4</a>
                    @endif
                </div>
                <div class="lef-dashboard-filter">
                    <label for="lefDashboardLinea">{{ ucfirst($equipmentName) }}</label>
                    <select id="lefDashboardLinea" @disabled(empty($lefDashboard['lineas']))>
                        @foreach($lefDashboard['lineas'] as $linea)
                            <option value="{{ $linea['id'] }}">{{ $linea['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="lefDashboardLoading" class="lef-dashboard-message" role="status">Cargando análisis...</div>
            <div id="lefDashboardEmpty" class="lef-dashboard-message" role="status" hidden></div>
            <div id="lefDashboardCharts" hidden>
                <section class="lef-dashboard-section" aria-labelledby="lefWasherTitle">
                    <h4 id="lefWasherTitle" class="sr-only">LEF por periodo</h4>
                    <p id="lefWasherEmpty" class="lef-dashboard-message" hidden></p>
                    <div id="lefWasherChartWrap" class="lef-dashboard-chart"><canvas id="lefDashboardWasherChart" role="img" aria-label="Valores LEF de 52, 12 y 4 semanas"></canvas></div>
                </section>
                <section class="lef-dashboard-section" aria-labelledby="lefParetoTitle">
                    <h4 id="lefParetoTitle">Pareto LEF - Máquinas</h4>
                    <p id="lefDashboardPosition" class="lef-dashboard-position" role="status" aria-live="polite"></p>
                    <p id="lefParetoEmpty" class="lef-dashboard-message" hidden></p>
                    <div id="lefParetoChartWrap" class="lef-dashboard-scroll">
                        <div id="lefDashboardParetoWidth" class="lef-dashboard-pareto"><canvas id="lefDashboardParetoChart" role="img" aria-label="Valores LEF por máquina, ordenados de mayor a menor según 52 semanas"></canvas></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
<script type="application/json" id="lefDashboardData" data-equipment="{{ $equipmentName }}">@json($lefDashboard)</script>
<script src="{{ asset('js/dashboard-lef-52124.js') }}"></script>
