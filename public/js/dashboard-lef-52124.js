(() => {
    const source = document.getElementById('lefDashboardData');
    const select = document.getElementById('lefDashboardLinea');
    if (!source || !select) return;

    const payload = JSON.parse(source.textContent);
    const lines = Array.isArray(payload.lineas) ? payload.lineas : [];
    const loading = document.getElementById('lefDashboardLoading');
    const empty = document.getElementById('lefDashboardEmpty');
    const chartsNode = document.getElementById('lefDashboardCharts');
    const washerEmpty = document.getElementById('lefWasherEmpty');
    const paretoEmpty = document.getElementById('lefParetoEmpty');
    const washerWrap = document.getElementById('lefWasherChartWrap');
    const paretoWrap = document.getElementById('lefParetoChartWrap');
    const paretoWidth = document.getElementById('lefDashboardParetoWidth');
    const position = document.getElementById('lefDashboardPosition');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const number = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const periods = [
        { label: '52 SEM', legend: '1 - 52 SEM', key: 'value_52_weeks', color: '#f59e0b', selected: [13, 148, 136] },
        { label: '12 SEM', legend: '2 - 12 SEM', key: 'value_12_weeks', color: '#fdba74', selected: [15, 118, 110] },
        { label: '4 SEM', legend: '3 - 4 SEM', key: 'value_4_weeks', color: '#fbbf24', selected: [20, 184, 166] },
    ];
    const charts = { washer: null, pareto: null };
    let pulseTimer = null;
    let currentMachines = [];

    const valueLabels = {
        id: 'dashboardLefValueLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.fillStyle = '#172554';
            ctx.font = '700 10px sans-serif';
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                chart.getDatasetMeta(datasetIndex).data.forEach((bar, index) => {
                    const value = Number(dataset.data[index]);
                    if (Number.isFinite(value)) ctx.fillText(number.format(value), bar.x, Math.max(12, bar.y - 4));
                });
            });
            ctx.restore();
        },
    };

    function chartOptions(xTitle) {
        const compact = window.matchMedia('(max-width: 640px)').matches;
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion.matches ? false : { duration: 350 },
            plugins: {
                datalabels: { display: false },
                legend: { display: xTitle === 'Máquina', position: compact ? 'bottom' : 'top', labels: { usePointStyle: true, boxWidth: 8, color: '#334155' } },
                tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${number.format(Number(context.raw))}` } },
            },
            layout: { padding: { top: 20, right: 12 } },
            scales: {
                x: {
                    grid: { display: false },
                    title: { display: !compact, text: xTitle, color: '#64748b' },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        color: '#475569',
                        font: { size: compact ? 9 : 10, weight: '700' },
                        callback(value) {
                            const label = this.getLabelForValue(value);
                            if (xTitle !== 'Máquina') return label;
                            return label.match(/.{1,16}(?:\s|$)/g)?.map((part) => part.trim()) || label;
                        },
                    },
                },
                y: {
                    beginAtZero: true,
                    grace: '18%',
                    grid: { color: 'rgba(148, 163, 184, 0.20)' },
                    title: { display: true, text: 'Valor LEF', color: '#64748b' },
                    ticks: { color: '#475569', callback: (value) => number.format(Number(value)) },
                },
            },
        };
    }

    function destroyCharts() {
        window.clearInterval(pulseTimer);
        pulseTimer = null;
        currentMachines = [];
        charts.washer?.destroy();
        charts.pareto?.destroy();
        charts.washer = null;
        charts.pareto = null;
    }

    function setMessage(node, text) {
        node.textContent = text;
        node.hidden = !text;
    }

    function drawWasher(row, name) {
        const canvas = document.getElementById('lefDashboardWasherChart');
        canvas.setAttribute('aria-label', `Valores LEF de ${row.name} en ${name}: ${periods.map((period) => `${period.label} ${number.format(Number(row[period.key]))}`).join(', ')}`);
        charts.washer = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: periods.map((period) => period.label),
                datasets: [{ label: `${row.name} - ${name}`, data: periods.map((period) => Number(row[period.key])), backgroundColor: periods.map((period) => period.color), borderRadius: 8, maxBarThickness: 160 }],
            },
            plugins: [valueLabels],
            options: chartOptions('Periodo'),
        });
    }

    function drawPareto(rows, name) {
        currentMachines = rows;
        paretoWidth.style.width = `${Math.max(paretoWrap.clientWidth, rows.length * 145)}px`;
        const selectedIndex = rows.findIndex((row) => row.highlight);
        position.textContent = selectedIndex >= 0
            ? `${name}: posición ${selectedIndex + 1} de ${rows.length} según LEF de 52 semanas`
            : 'No se identificó la máquina lavadora en esta importación.';

        const canvas = document.getElementById('lefDashboardParetoChart');
        canvas.setAttribute('aria-label', `Pareto LEF de ${name}. ${position.textContent}`);
        charts.pareto = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: rows.map((row) => row.name),
                datasets: periods.map((period) => ({
                    label: period.legend,
                    data: rows.map((row) => Number(row[period.key])),
                    backgroundColor: rows.map((row) => row.highlight ? `rgba(${period.selected.join(',')}, 1)` : period.color),
                    borderRadius: 7,
                    maxBarThickness: 44,
                })),
            },
            plugins: [valueLabels],
            options: chartOptions('Máquina'),
        });

        if (selectedIndex >= 0 && !reducedMotion.matches) {
            pulseTimer = window.setInterval(() => {
                if (document.hidden || !charts.pareto) return;
                const opacity = 0.67 + 0.33 * (1 + Math.sin(Date.now() / 380)) / 2;
                charts.pareto.data.datasets.forEach((dataset, index) => {
                    dataset.backgroundColor[selectedIndex] = `rgba(${periods[index].selected.join(',')}, ${opacity})`;
                });
                charts.pareto.update('none');
            }, 100);
        }
    }

    function render() {
        destroyCharts();
        loading.hidden = true;
        const line = lines.find((item) => String(item.id) === select.value);

        if (!line || !line.import_id) {
            chartsNode.hidden = true;
            setMessage(empty, payload.period
                ? 'Esta lavadora no tiene información cargada para el periodo de análisis.'
                : 'Todavía no hay información 52-12-4 cargada.');
            return;
        }
        if (!window.Chart) {
            chartsNode.hidden = true;
            setMessage(empty, 'No se pudieron cargar las gráficas LEF. Recarga la página.');
            return;
        }

        setMessage(empty, '');
        chartsNode.hidden = false;
        const hasWasher = Boolean(line.washer);
        const hasMachines = Array.isArray(line.machines) && line.machines.length > 0;
        washerWrap.hidden = !hasWasher;
        paretoWrap.hidden = !hasMachines;
        setMessage(washerEmpty, hasWasher ? '' : 'Esta carga no contiene datos de la lavadora.');
        setMessage(paretoEmpty, hasMachines ? '' : 'Esta carga no contiene datos de máquinas.');
        position.hidden = !hasMachines;
        if (hasWasher) drawWasher(line.washer, line.nombre);
        if (hasMachines) drawPareto(line.machines, line.nombre);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const firstWithData = lines.find((line) => line.import_id);
        if (firstWithData) select.value = String(firstWithData.id);
        select.addEventListener('change', render);
        reducedMotion.addEventListener('change', render);
        window.addEventListener('resize', () => {
            if (currentMachines.length && !paretoWrap.hidden) {
                paretoWidth.style.width = `${Math.max(paretoWrap.clientWidth, currentMachines.length * 145)}px`;
            }
        }, { passive: true });
        render();
    });
})();
