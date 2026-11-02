const dashboardConfig = JSON.parse(document.getElementById('pasteurDashboardData').textContent);
let fallasMechanicalChart, componentesPasteurizadoraChart, planesPasteurizadoraChart, avanceRevisionPasteurizadoraChart, analisis52124PasteurizadoraChart, analisis30147PasteurizadoraChart;
    let fallasCentralHidraulicaChart, planesCentralHidraulicaChart, avanceRevisionCentralHidraulicaChart, analisis52124CentralHidraulicaChart, analisis30147CentralHidraulicaChart;
    const pasteurizadorasData = dashboardConfig.pasteurizadorasData;
    const fallasPorLineaPasteurizadora = dashboardConfig.fallasPorLineaPasteurizadora;
    const componentesDanadosPasteurizadora = dashboardConfig.componentesDanadosPasteurizadora;
    const planesAccionDashboardPasteurizadora = dashboardConfig.planesAccionDashboardPasteurizadora;
    const avanceRevisionPasteurizadora = dashboardConfig.avanceRevisionPasteurizadora;
    const analisis52124Pasteurizadora = dashboardConfig.analisis52124Pasteurizadora;
    const analisis30147Pasteurizadora = dashboardConfig.analisis30147Pasteurizadora;
    const centralesHidraulicasData = dashboardConfig.centralesHidraulicasData;
    const fallasPorLineaCentralHidraulica = dashboardConfig.fallasPorLineaCentralHidraulica;
    const planesAccionDashboardCentralHidraulica = dashboardConfig.planesAccionDashboardCentralHidraulica;
    const avanceRevisionCentralHidraulica = dashboardConfig.avanceRevisionCentralHidraulica;
    const analisis52124CentralHidraulica = dashboardConfig.analisis52124CentralHidraulica;
    const analisis30147CentralHidraulica = dashboardConfig.analisis30147CentralHidraulica;
    const pasteurTrendChartTypes = {
        '52124': 'bar',
        '30147': 'bar',
        'central52124': 'bar',
        'central30147': 'bar'
    };
    const pasteurTrendCards = {
        '52124': {
            key: '52124',
            canvasId: 'analisis52124PasteurizadoraChart',
            selectId: 'analisis52124PasteurizadoraLineaSelect',
            dataset: analisis52124Pasteurizadora,
            statusCardId: 'trend52124StatusCard',
            statusTitleId: 'trend52124StatusTitle',
            statusCopyId: 'trend52124StatusCopy',
            statusNoteId: 'trend52124StatusNote',
            windowsId: 'trend52124WindowCards',
            machineGridId: 'trend52124MachineGrid',
            captionId: 'trend52124Caption',
            title: '52-12-4',
            windowRoles: ['Histórico anual', 'Impacto trimestral', 'Control inmediato'],
            colors: [
                ['rgba(16, 185, 129, 0.88)', '#047857', 'rgba(16, 185, 129, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        },
        '30147': {
            key: '30147',
            canvasId: 'analisis30147PasteurizadoraChart',
            selectId: 'analisis30147PasteurizadoraLineaSelect',
            dataset: analisis30147Pasteurizadora,
            statusCardId: 'trend30147StatusCard',
            statusTitleId: 'trend30147StatusTitle',
            statusCopyId: 'trend30147StatusCopy',
            statusNoteId: 'trend30147StatusNote',
            windowsId: 'trend30147WindowCards',
            machineGridId: 'trend30147MachineGrid',
            captionId: 'trend30147Caption',
            title: '30-14-7',
            windowRoles: ['Ventana amplia', 'Seguimiento intermedio', 'Control inmediato'],
            colors: [
                ['rgba(16, 185, 129, 0.88)', '#047857', 'rgba(16, 185, 129, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        },
        'central52124': {
            key: 'central52124',
            canvasId: 'analisis52124CentralHidraulicaChart',
            selectId: 'analisis52124CentralHidraulicaLineaSelect',
            dataset: analisis52124CentralHidraulica,
            statusCardId: 'trendCentral52124StatusCard',
            statusTitleId: 'trendCentral52124StatusTitle',
            statusCopyId: 'trendCentral52124StatusCopy',
            statusNoteId: 'trendCentral52124StatusNote',
            windowsId: 'trendCentral52124WindowCards',
            machineGridId: 'trendCentral52124MachineGrid',
            captionId: 'trendCentral52124Caption',
            title: 'Central Hidraulica 52-12-4',
            windowRoles: ['Historico anual', 'Impacto trimestral', 'Control inmediato'],
            colors: [
                ['rgba(20, 184, 166, 0.88)', '#0f766e', 'rgba(20, 184, 166, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        },
        'central30147': {
            key: 'central30147',
            canvasId: 'analisis30147CentralHidraulicaChart',
            selectId: 'analisis30147CentralHidraulicaLineaSelect',
            dataset: analisis30147CentralHidraulica,
            statusCardId: 'trendCentral30147StatusCard',
            statusTitleId: 'trendCentral30147StatusTitle',
            statusCopyId: 'trendCentral30147StatusCopy',
            statusNoteId: 'trendCentral30147StatusNote',
            windowsId: 'trendCentral30147WindowCards',
            machineGridId: 'trendCentral30147MachineGrid',
            captionId: 'trendCentral30147Caption',
            title: 'Central Hidraulica 30-14-7',
            windowRoles: ['Ventana amplia', 'Seguimiento intermedio', 'Control inmediato'],
            colors: [
                ['rgba(20, 184, 166, 0.88)', '#0f766e', 'rgba(20, 184, 166, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        initPasteurizadoraTrendSelectors();
        initPasteurizadoraCarousels();
        setAutoRefresh();
    });

    function initCharts() {
        const fallasCanvas = document.getElementById(dashboardConfig.fallasCanvasId);

        if (fallasCanvas) {
            const fallasCtx = fallasCanvas.getContext('2d');
            fallasMechanicalChart = new Chart(fallasCtx, {
            type: 'bar',
            data: {
                labels: fallasPorLineaPasteurizadora.map(item => item.linea),
                datasets: [
                    {
                        label: 'Críticos',
                        data: fallasPorLineaPasteurizadora.map(item => item.criticos || 0),
                        backgroundColor: 'rgba(239, 68, 68, 0.9)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Requiere revisión',
                        data: fallasPorLineaPasteurizadora.map(item => item.requiere_revision || 0),
                        backgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: '#d97706',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Severo / Moderado',
                        data: fallasPorLineaPasteurizadora.map(item => item.desgaste || 0),
                        backgroundColor: 'rgba(249, 115, 22, 0.88)',
                        borderColor: '#ea580c',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', padding: 8 }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 13, weight: 600 }, color: '#334155', padding: 8 }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            },
                            footer: function(items) {
                                const item = fallasPorLineaPasteurizadora[items[0]?.dataIndex];
                                return item ? `Total: ${item.total_fallas || 0}` : '';
                            }
                        }
                    }
                }
            }
            });
        }

        componentesPasteurizadoraChart = buildComponentesPasteurizadoraChart();
        planesPasteurizadoraChart = buildPlanesPasteurizadoraChart();
        avanceRevisionPasteurizadoraChart = buildAvanceRevisionPasteurizadoraChart();
        analisis52124PasteurizadoraChart = buildPasteurizadoraTrendChart(
            'analisis52124PasteurizadoraChart',
            analisis52124Pasteurizadora,
            pasteurTrendCards['52124']
        );
        analisis30147PasteurizadoraChart = buildPasteurizadoraTrendChart(
            'analisis30147PasteurizadoraChart',
            analisis30147Pasteurizadora,
            pasteurTrendCards['30147']
        );
        fallasCentralHidraulicaChart = buildFallasCentralHidraulicaChart();
        planesCentralHidraulicaChart = buildPlanesCentralHidraulicaChart();
        avanceRevisionCentralHidraulicaChart = buildAvanceRevisionCentralHidraulicaChart();
        analisis52124CentralHidraulicaChart = buildPasteurizadoraTrendChart(
            'analisis52124CentralHidraulicaChart',
            analisis52124CentralHidraulica,
            pasteurTrendCards['central52124']
        );
        analisis30147CentralHidraulicaChart = buildPasteurizadoraTrendChart(
            'analisis30147CentralHidraulicaChart',
            analisis30147CentralHidraulica,
            pasteurTrendCards['central30147']
        );
    }

    function buildComponentesPasteurizadoraChart() {
        const canvas = document.getElementById('componentesPasteurizadoraChart');
        if (!canvas) return null;

        const rows = Array.isArray(componentesDanadosPasteurizadora)
            ? componentesDanadosPasteurizadora.filter((item) => Number(item.total_danios || 0) > 0)
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: rows.map((item) => item.componente || 'Sin componente'),
                datasets: [{
                    data: rows.map((item) => Number(item.total_danios || 0)),
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.9)',
                        'rgba(245, 158, 11, 0.9)',
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(139, 92, 246, 0.9)',
                        'rgba(236, 72, 153, 0.9)',
                    ],
                    borderColor: ['#dc2626', '#d97706', '#059669', '#2563eb', '#7c3aed', '#db2777'],
                    borderWidth: 3,
                    borderRadius: 8,
                    hoverBorderWidth: 5,
                    hoverOffset: 12,
                    spacing: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#334155',
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            font: { size: 12, weight: 600, family: "'Inter', sans-serif" },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#f59e0b',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((sum, value) => sum + Number(value || 0), 0);
                                const value = Number(context.raw || 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                return [`Daños: ${value}`, `${percentage}%`];
                            },
                        },
                    },
                },
            },
        });
    }

    function buildPlanesPasteurizadoraChart() {
        const canvas = document.getElementById('planesPasteurizadoraChart');
        if (!canvas) return null;

        const summary = planesAccionDashboardPasteurizadora?.resumen || {};
        return new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pendientes críticos', 'Activos programados', 'Completados'],
                datasets: [{
                    data: [
                        Number(summary.pendientes || 0),
                        Number(summary.programados || 0),
                        Number(summary.completados || 0),
                    ],
                    backgroundColor: ['rgba(239, 68, 68, 0.92)', 'rgba(245, 158, 11, 0.88)', 'rgba(16, 185, 129, 0.88)'],
                    borderColor: ['#dc2626', '#d97706', '#059669'],
                    borderWidth: 2,
                    hoverOffset: 10,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: { label: context => `${context.label}: ${context.raw}` },
                    },
                },
            },
            plugins: [{
                id: 'pasteurPlanesCenterLabel',
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
                },
            }],
        });
    }

    function buildAvanceRevisionPasteurizadoraChart() {
        const canvas = document.getElementById('avanceRevisionPasteurizadoraChart');
        if (!canvas) return null;

        const labels = Array.isArray(avanceRevisionPasteurizadora?.labels)
            ? avanceRevisionPasteurizadora.labels
            : [];
        const values = Array.isArray(avanceRevisionPasteurizadora?.porcentajes)
            ? avanceRevisionPasteurizadora.porcentajes.map(value => Number(value || 0))
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Avance de revisión mecánica',
                    data: values,
                    backgroundColor: values.map(value => value >= 90
                        ? 'rgba(16, 185, 129, 0.88)'
                        : (value >= 60 ? 'rgba(245, 158, 11, 0.88)' : 'rgba(249, 115, 22, 0.88)')),
                    borderColor: values.map(value => value >= 90
                        ? '#059669'
                        : (value >= 60 ? '#d97706' : '#ea580c')),
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: {
                            font: { size: 12, weight: 600 },
                            color: '#64748b',
                            callback: value => `${value}%`
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.raw}% revisado`
                        }
                    }
                }
            }
        });
    }

    function buildFallasCentralHidraulicaChart() {
        const canvas = document.getElementById('fallasCentralHidraulicaChart');
        if (!canvas) return null;

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: fallasPorLineaCentralHidraulica.map(item => item.linea),
                datasets: [
                    {
                        label: 'Criticos',
                        data: fallasPorLineaCentralHidraulica.map(item => item.criticos || 0),
                        backgroundColor: 'rgba(239, 68, 68, 0.9)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Requiere revision',
                        data: fallasPorLineaCentralHidraulica.map(item => item.requiere_revision || 0),
                        backgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: '#d97706',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Severo / Moderado',
                        data: fallasPorLineaCentralHidraulica.map(item => item.desgaste || 0),
                        backgroundColor: 'rgba(20, 184, 166, 0.86)',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', padding: 8 }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 13, weight: 600 }, color: '#334155', padding: 8 }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ccfbf1',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw}`,
                            footer: function(items) {
                                const item = fallasPorLineaCentralHidraulica[items[0]?.dataIndex];
                                return item ? `Total: ${item.total_fallas || 0}` : '';
                            }
                        }
                    }
                }
            }
        });
    }

    function buildPlanesCentralHidraulicaChart() {
        const canvas = document.getElementById('planesCentralHidraulicaChart');
        if (!canvas) return null;

        const rows = Array.isArray(planesAccionDashboardCentralHidraulica?.por_linea)
            ? planesAccionDashboardCentralHidraulica.por_linea
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rows.map(item => item.linea || 'N/A'),
                datasets: [
                    {
                        label: 'Abiertos',
                        data: rows.map(item => Number(item.abiertos || 0)),
                        backgroundColor: 'rgba(239, 68, 68, 0.86)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false
                    },
                    {
                        label: 'Completados',
                        data: rows.map(item => Number(item.completados || 0)),
                        backgroundColor: 'rgba(20, 184, 166, 0.86)',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', precision: 0 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155' }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ccfbf1',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        padding: 14
                    }
                }
            }
        });
    }

    function buildAvanceRevisionCentralHidraulicaChart() {
        const canvas = document.getElementById('avanceRevisionCentralHidraulicaChart');
        if (!canvas) return null;

        const labels = Array.isArray(avanceRevisionCentralHidraulica?.labels)
            ? avanceRevisionCentralHidraulica.labels
            : [];
        const values = Array.isArray(avanceRevisionCentralHidraulica?.porcentajes)
            ? avanceRevisionCentralHidraulica.porcentajes.map(value => Number(value || 0))
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Avance de revision hidraulica',
                    data: values,
                    backgroundColor: values.map(value => value >= 90
                        ? 'rgba(20, 184, 166, 0.88)'
                        : (value >= 60 ? 'rgba(245, 158, 11, 0.88)' : 'rgba(249, 115, 22, 0.88)')),
                    borderColor: values.map(value => value >= 90
                        ? '#0f766e'
                        : (value >= 60 ? '#d97706' : '#ea580c')),
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: {
                            font: { size: 12, weight: 600 },
                            color: '#64748b',
                            callback: value => `${value}%`
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ccfbf1',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.raw}% revisado`
                        }
                    }
                }
            }
        });
    }

    function initPasteurizadoraTrendSelectors() {
        document.querySelectorAll('[data-pasteur-trend-type]').forEach((button) => {
            button.addEventListener('click', () => {
                const cardKey = button.dataset.pasteurTrendCard;
                const chartType = button.dataset.pasteurTrendType || 'bar';
                const config = pasteurTrendCards[cardKey];

                if (!config) {
                    return;
                }

                pasteurTrendChartTypes[cardKey] = chartType;

                document.querySelectorAll(`[data-pasteur-trend-type][data-pasteur-trend-card="${cardKey}"]`).forEach((item) => {
                    item.classList.toggle('active', item.dataset.pasteurTrendType === chartType);
                });

                const chart = buildPasteurizadoraTrendChart(config.canvasId, config.dataset, config);

                if (cardKey === '52124') {
                    analisis52124PasteurizadoraChart = chart;
                } else if (cardKey === '30147') {
                    analisis30147PasteurizadoraChart = chart;
                } else if (cardKey === 'central52124') {
                    analisis52124CentralHidraulicaChart = chart;
                } else if (cardKey === 'central30147') {
                    analisis30147CentralHidraulicaChart = chart;
                }
            });
        });

        Object.values(pasteurTrendCards).forEach((config) => {
            const select = document.getElementById(config.selectId);

            if (!select) {
                return;
            }

            select.addEventListener('change', () => {
                const chart = buildPasteurizadoraTrendChart(config.canvasId, config.dataset, config);

                if (config.key === '52124') {
                    analisis52124PasteurizadoraChart = chart;
                } else if (config.key === '30147') {
                    analisis30147PasteurizadoraChart = chart;
                } else if (config.key === 'central52124') {
                    analisis52124CentralHidraulicaChart = chart;
                } else if (config.key === 'central30147') {
                    analisis30147CentralHidraulicaChart = chart;
                }
            });
        });
    }

    function formatTrendCount(value) {
        return new Intl.NumberFormat('es-MX', {
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function getTrendSerieForRow(row, serieKey) {
        return (Array.isArray(row?.series) ? row.series : []).find((item) => item.key === serieKey) || null;
    }

    function getTrendLatestValue(row, serieKey) {
        const serie = getTrendSerieForRow(row, serieKey);
        const values = Array.isArray(serie?.data) ? serie.data : [];

        return Number(values[values.length - 1] || 0);
    }

    function getTrendPreviousValue(row, serieKey) {
        const serie = getTrendSerieForRow(row, serieKey);
        const values = Array.isArray(serie?.data) ? serie.data : [];

        return Number(values.length > 1 ? values[values.length - 2] : 0);
    }

    function getTrendCurrentTotalForRow(row, sourceSeries) {
        return sourceSeries.reduce((sum, serie) => sum + getTrendLatestValue(row, serie.key), 0);
    }

    function trendDeltaCopy(diff) {
        if (diff < 0) {
            return `Bajó ${formatTrendCount(Math.abs(diff))} vs corte anterior`;
        }

        if (diff > 0) {
            return `Subió ${formatTrendCount(diff)} vs corte anterior`;
        }

        return 'Sin cambio vs corte anterior';
    }

    function buildPasteurizadoraTrendSummaries(rows, sourceSeries, config) {
        return sourceSeries.map((serie, index) => {
            const current = rows.reduce((sum, row) => sum + getTrendLatestValue(row, serie.key), 0);
            const previous = rows.reduce((sum, row) => sum + getTrendPreviousValue(row, serie.key), 0);
            const diff = current - previous;
            const impacted = rows.filter((row) => getTrendLatestValue(row, serie.key) > 0).length;

            return {
                key: serie.key,
                label: String(serie.label || `Ventana ${index + 1}`),
                current,
                previous,
                diff,
                impacted,
                role: config.windowRoles?.[index] || 'Ventana de seguimiento',
                color: config.colors?.[index % (config.colors?.length || 1)]?.[1] || '#3b82f6'
            };
        });
    }

    function renderPasteurizadoraTrendExecutive(rows, sourceSeries, config) {
        const statusCard = document.getElementById(config.statusCardId);
        const statusTitle = document.getElementById(config.statusTitleId);
        const statusCopy = document.getElementById(config.statusCopyId);
        const statusNote = document.getElementById(config.statusNoteId);
        const windowsHost = document.getElementById(config.windowsId);
        const machineHost = document.getElementById(config.machineGridId);
        const captionNode = document.getElementById(config.captionId);
        const summaries = buildPasteurizadoraTrendSummaries(rows, sourceSeries, config);
        const recent = summaries[summaries.length - 1] || null;
        const mid = summaries[1] || null;
        const latestLabel = rows.find((row) => Array.isArray(row?.labels) && row.labels.length)?.labels?.slice(-1)?.[0]
            || config.dataset?.periodo?.label
            || 'periodo actual';

        if (machineHost) {
            machineHost.innerHTML = rows.length
                ? rows.map((row) => {
                    const currentTotal = getTrendCurrentTotalForRow(row, sourceSeries);
                    const state = currentTotal > 0 ? 'active' : 'inactive';
                    const icon = currentTotal > 0 ? 'fa-chart-line' : 'fa-circle-check';

                    return `
                        <span class="dashboard-trend-machine-pill ${state}">
                            <i class="fas ${icon}"></i>
                            ${escapeHtml(row.linea || 'N/A')}
                        </span>
                    `;
                }).join('')
                : `
                    <span class="dashboard-trend-machine-pill inactive">
                        <i class="fas fa-circle-info"></i>
                        Sin pasteurizadoras
                    </span>
                `;
        }

        if (windowsHost) {
            if (!summaries.length) {
                windowsHost.innerHTML = `
                    <article class="dashboard-trend-window-card" style="--window-accent: #64748b">
                        <div class="dashboard-trend-window-label">Sin datos</div>
                        <div class="dashboard-trend-window-value">0</div>
                        <div class="dashboard-trend-window-role">No hay información de ventanas para el periodo filtrado.</div>
                    </article>
                `;
            } else {
                windowsHost.innerHTML = summaries.map((summary) => {
                    const deltaClass = summary.diff < 0 ? 'positive' : (summary.diff > 0 ? 'alert' : 'neutral');
                    const deltaIcon = summary.diff < 0 ? 'fa-arrow-down' : (summary.diff > 0 ? 'fa-arrow-up' : 'fa-minus');

                    return `
                        <article class="dashboard-trend-window-card" style="--window-accent: ${summary.color}">
                            <div class="dashboard-trend-window-label">${escapeHtml(summary.label)}</div>
                            <div class="dashboard-trend-window-value">${formatTrendCount(summary.current)}</div>
                            <div class="dashboard-trend-window-role">${escapeHtml(summary.role)}</div>
                            <div class="dashboard-trend-window-delta ${deltaClass}">
                                <i class="fas ${deltaIcon}"></i>
                                <span>${trendDeltaCopy(summary.diff)}</span>
                            </div>
                        </article>
                    `;
                }).join('');
            }
        }

        if (statusCard && statusTitle && statusCopy) {
            let tone = 'neutral';
            let title = 'Sin danos recientes';
            let copy = 'La ventana reciente no registra danos para esta seleccion.';

            if (!rows.length || !sourceSeries.length) {
                title = 'Sin datos en el periodo';
                copy = 'No hay daños registrados para construir esta tendencia con el filtro actual.';
            } else if ((recent?.current || 0) === 0) {
                tone = 'positive';
            } else if ((recent?.diff || 0) > 0 || (mid?.diff || 0) > 0) {
                tone = 'alert';
                title = 'Repunte reciente';
                copy = `${recent?.label || 'La ventana reciente'} o ${mid?.label || 'la ventana intermedia'} subieron frente al corte anterior. Revisa las pasteurizadoras con mayor incidencia.`;
            } else if ((recent?.diff || 0) <= 0 && (mid?.diff || 0) <= 0) {
                tone = 'positive';
                title = 'Tendencia bajo control';
                copy = `${recent?.label || 'La ventana reciente'} no aumenta frente al corte anterior y el comportamiento general se mantiene estable.`;
            }

            statusCard.className = `dashboard-trend-status dashboard-trend-status--${tone}`;
            statusTitle.textContent = title;
            statusCopy.textContent = copy;
        }

        if (statusNote) {
            const recentText = recent
                ? `${formatTrendCount(recent.impacted)} de ${formatTrendCount(rows.length)} pasteurizadoras con daños en ${recent.label}.`
                : 'Sin pasteurizadoras con daños recientes para esta ventana.';
            statusNote.textContent = recentText;
        }

        if (captionNode) {
            captionNode.textContent = `Corte actual: ${latestLabel}. Comparativo ${config.title} construido solo con análisis de Pasteurizadora.`;
        }
    }

    function buildPasteurizadoraTrendChart(canvasId, dataset, config = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        const rows = Array.isArray(dataset?.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const selectedId = select?.value || dataset?.default_linea_id || rows[0]?.linea_id || '';
        const item = rows.find((row) => Number(row.linea_id) === Number(selectedId)) || rows[0] || null;
        const labels = Array.isArray(item?.labels) ? item.labels : [];
        const sourceSeries = Array.isArray(item?.series) ? item.series : [];

        if (select && item?.linea_id) {
            select.value = String(item.linea_id);
        }

        const colors = config.colors || [
            ['rgba(16, 185, 129, 0.88)', '#047857', 'rgba(16, 185, 129, 0.22)'],
            ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
            ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)'],
        ];
        const chartType = pasteurTrendChartTypes[config.key] || config.chartType || 'bar';
        const isBar = chartType === 'bar';
        const chartSeries = sourceSeries.map((serie, index) => ({
            label: String(serie.label || `Serie ${index + 1}`).replace(/dias/gi, 'días'),
            data: Array.isArray(serie.data) ? serie.data.map(value => Number(value || 0)) : [],
            backgroundColor: isBar ? colors[index % colors.length][0] : (colors[index % colors.length][2] || colors[index % colors.length][0]),
            borderColor: colors[index % colors.length][1],
            borderWidth: isBar ? 0 : (index === sourceSeries.length - 1 ? 4 : 3),
            borderDash: isBar ? [] : (index === 0 ? [8, 6] : []),
            borderRadius: isBar ? 10 : 0,
            borderSkipped: isBar ? false : undefined,
            tension: isBar ? 0 : 0.32,
            pointRadius: isBar ? 0 : 4,
            pointHoverRadius: isBar ? 0 : 7,
            pointBackgroundColor: colors[index % colors.length][1],
            pointBorderColor: '#ffffff',
            pointBorderWidth: isBar ? 0 : 2,
            fill: false,
            maxBarThickness: isBar ? 28 : undefined,
            categoryPercentage: isBar ? 0.72 : undefined,
            barPercentage: isBar ? 0.74 : undefined,
        }));

        renderPasteurizadoraTrendExecutive(item ? [item] : [], sourceSeries, config);

        return new Chart(canvas.getContext('2d'), {
            type: chartType,
            data: {
                labels,
                datasets: chartSeries,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', padding: 8, precision: 0 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155', padding: 8 }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw} daños`
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 14, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    }
                }
            }
        });
    }

    function refreshData() {
        window.location.reload();
    }

    function setAutoRefresh() {
        setInterval(() => {
            refreshData();
        }, 300000);
    }

    function initPasteurizadoraCarousels() {
        document.querySelectorAll('.lavadora-carousel').forEach(carousel => {
            const slides = carousel.querySelectorAll('.carousel-slide');
            const prevButton = carousel.querySelector('.carousel-prev');
            const nextButton = carousel.querySelector('.carousel-next');
            const dots = carousel.querySelectorAll('.carousel-dot');
            let currentIndex = 0;

            function showSlide(index) {
                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('active', slideIndex === index);
                });
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('active', dotIndex === index);
                });
                currentIndex = index;
            }

            function goNext() {
                if (slides.length <= 1) return;
                showSlide((currentIndex + 1) % slides.length);
            }

            function goPrev() {
                if (slides.length <= 1) return;
                showSlide((currentIndex - 1 + slides.length) % slides.length);
            }

            if (nextButton) {
                nextButton.addEventListener('click', goNext);
            }

            if (prevButton) {
                prevButton.addEventListener('click', goPrev);
            }

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    const index = parseInt(dot.dataset.index, 10);
                    if (!isNaN(index)) {
                        showSlide(index);
                    }
                });
            });

            if (slides.length > 1) {
                setInterval(goNext, 6000);
            }
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function(char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function formatModalDate(value) {
        if (!value) {
            return 'Sin fecha';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleDateString('es-MX');
    }

    function pasteurizadoraAnalisisPorEstado(estado) {
        const porEstado = estado.analisis_por_estado || {};

        return {
            critico: Array.isArray(porEstado.critico) ? porEstado.critico : (Array.isArray(estado.analisis_criticos) ? estado.analisis_criticos : []),
            severo: Array.isArray(porEstado.severo) ? porEstado.severo : [],
            moderado: Array.isArray(porEstado.moderado) ? porEstado.moderado : [],
            revision: Array.isArray(porEstado.revision) ? porEstado.revision : (Array.isArray(estado.analisis_revision) ? estado.analisis_revision : [])
        };
    }

    function pasteurizadoraAlertSections(estado) {
        const porEstado = pasteurizadoraAnalisisPorEstado(estado);

        return [
            { key: 'critico', title: 'Requiere cambio', tone: 'critico' },
            { key: 'severo', title: 'Daño severo', tone: 'severo' },
            { key: 'moderado', title: 'Daño moderado', tone: 'moderado' },
            { key: 'revision', title: 'Requiere revision', tone: 'revision' }
        ].filter((section) => Array.isArray(porEstado[section.key]) && porEstado[section.key].length > 0);
    }

    function pasteurizadoraAnalysisLocation(analysis, mode) {
        if (mode === 'central') {
            return [
                analysis.piso ? `Piso: ${analysis.piso}` : null,
                analysis.lado || null
            ].filter(Boolean).join(' · ');
        }

        return [
            analysis.modulo ? `Modulo: ${analysis.modulo}` : null,
            analysis.nivel ? `Nivel: ${analysis.nivel}` : null,
            analysis.lado || null
        ].filter(Boolean).join(' · ');
    }

    function renderPasteurizadoraAnalisisSection(title, tone, analyses, mode = 'mecanica') {
        if (!Array.isArray(analyses) || analyses.length === 0) {
            return '';
        }

        const toneClasses = {
            critico: 'bg-red-100 text-red-700',
            severo: 'bg-orange-100 text-orange-700',
            moderado: 'bg-amber-100 text-amber-700',
            revision: 'bg-yellow-100 text-yellow-700'
        };
        const badgeClass = toneClasses[tone] || 'bg-slate-100 text-slate-700';

        const cards = analyses.map((analysis) => {
            const fallback = mode === 'central' ? '/images/icono_pas.png' : '/images/icono_pas.png';
            const image = analysis.image || analysis.fallback_image || fallback;
            const location = pasteurizadoraAnalysisLocation(analysis, mode);
            const componentName = analysis.componente_nombre || analysis.componente?.nombre || 'Componente';
            const componentNumbersLabel = analysis.componentes_revisados_label || '';
            const dateLabel = analysis.fecha_formateada || formatModalDate(analysis.fecha_analisis);
            const orderLabel = analysis.numero_orden ? `Orden: ${analysis.numero_orden}` : null;

            return `
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="componente-header">
                                <div class="componente-icono">
                                    <img src="${escapeHtml(image)}" class="w-8 h-8 object-contain" onerror="this.src='${escapeHtml(analysis.fallback_image || fallback)}'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="componente-nombre">${escapeHtml(componentName)}</div>
                                    ${componentNumbersLabel ? `<p class="text-sm font-semibold text-gray-700 mt-1">${escapeHtml(componentNumbersLabel)}</p>` : ''}
                                </div>
                            </div>
                            ${location ? `<p class="text-sm text-gray-600 mt-2">${escapeHtml(location)}</p>` : ''}
                            <p class="text-xs text-gray-500 mt-1">
                                Fecha: ${escapeHtml(dateLabel)}${orderLabel ? ` · ${escapeHtml(orderLabel)}` : ''}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${badgeClass}">
                            ${escapeHtml(analysis.estado_label || analysis.estado || title)}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2">
                        ${escapeHtml(analysis.actividad || 'Sin descripcion')}
                    </p>
                </div>
            `;
        }).join('');

        return `
            <div class="mb-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h4 class="font-bold text-gray-800">${escapeHtml(title)}</h4>
                    <span class="severity-pill ${tone}">${analyses.length}</span>
                </div>
                <div class="space-y-3">
                    ${cards}
                </div>
            </div>
        `;
    }

    function renderPasteurizadoraAlertChips(conteo) {
        const total = Object.values(conteo || {}).reduce((sum, value) => sum + Number(value || 0), 0);

        if (total <= 0) {
            return '';
        }

        return `
            <div class="mb-4 flex flex-wrap gap-2">
                ${Number(conteo.critico || 0) > 0 ? `<span class="severity-pill critico">${Number(conteo.critico || 0)} requiere cambio</span>` : ''}
                ${Number(conteo.severo || 0) > 0 ? `<span class="severity-pill severo">${Number(conteo.severo || 0)} severo</span>` : ''}
                ${Number(conteo.moderado || 0) > 0 ? `<span class="severity-pill moderado">${Number(conteo.moderado || 0)} moderado</span>` : ''}
                ${Number(conteo.revision || 0) > 0 ? `<span class="severity-pill revision">${Number(conteo.revision || 0)} revision</span>` : ''}
            </div>
        `;
    }

    function renderPasteurizadoraEmptyDetail(message) {
        return `
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm text-slate-600">
                ${escapeHtml(message)}
            </div>
        `;
    }

    function showPasteurizadoraDetail(pasteurizadora) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const estado = pasteurizadora.estado || {};
        const porEstado = pasteurizadoraAnalisisPorEstado(estado);
        const sections = pasteurizadoraAlertSections(estado);
        const conteoAlertas = estado.conteo_alertas || {};

        modalTitle.innerHTML = `Detalle - ${escapeHtml(pasteurizadora.nombre)}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (estado.nivel === 'riesgo' ? 'bg-orange-50 border-l-4 border-orange-500' : (estado.nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'))}">
                <h4 class="font-bold text-lg mb-2">Estado: ${escapeHtml((estado.nivel || 'bueno').toUpperCase())}</h4>
                <p class="text-gray-700">${escapeHtml(estado.mensaje || 'Sin mensaje de estado')}</p>
            </div>
        `;

        html += renderPasteurizadoraAlertChips(conteoAlertas);

        sections.forEach((section) => {
            html += renderPasteurizadoraAnalisisSection(section.title, section.tone, porEstado[section.key] || [], 'mecanica');
        });

        if (estado.ultimo_analisis) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.fecha || 'Sin registro')}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Modulos configurados: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.modulos || 'N/A')}</span></p>
                    </div>
                </div>
            `;
        }

        if ((estado.acciones_pendientes || 0) > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta pasteurizadora tiene ${escapeHtml(estado.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        ${dashboardConfig.urls.mechanicalPlans ? `
                        <a href="${escapeHtml(dashboardConfig.urls.mechanicalPlans)}?linea_id=${pasteurizadora.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        if (sections.length === 0 && !(estado.acciones_pendientes > 0)) {
            html += renderPasteurizadoraEmptyDetail('No hay componentes con alertas activas para esta pasteurizadora.');
        }

        html += `
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                ${dashboardConfig.urls.mechanicalAnalyses ? `
                <a href="${escapeHtml(dashboardConfig.urls.mechanicalAnalyses)}?linea_id=${pasteurizadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Analisis
                </a>
                ` : ''}
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `;

        modalBody.innerHTML = html;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function showCentralHidraulicaDetail(central) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const estado = central.estado || {};
        const conteoAlertas = estado.conteo_alertas || {};
        const porEstado = pasteurizadoraAnalisisPorEstado(estado);
        const sections = pasteurizadoraAlertSections(estado);
        const pisos = Array.isArray(estado.pisos) ? estado.pisos : [];
        const nivel = estado.nivel || 'bueno';
        const toneClass = nivel === 'critico'
            ? 'bg-red-50 border-l-4 border-red-500'
            : (nivel === 'riesgo'
                ? 'bg-orange-50 border-l-4 border-orange-500'
                : (nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'));

        modalTitle.innerHTML = `Detalle - ${escapeHtml(central.nombre)}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${toneClass}">
                <h4 class="font-bold text-lg mb-2">Estado: ${escapeHtml(nivel.toUpperCase())}</h4>
                <p class="text-gray-700">${escapeHtml(estado.mensaje || 'Central Hidraulica sin mensaje de estado')}</p>
            </div>
        `;

        html += renderPasteurizadoraAlertChips(conteoAlertas);

        sections.forEach((section) => {
            html += renderPasteurizadoraAnalisisSection(section.title, section.tone, porEstado[section.key] || [], 'central');
        });

        if (pisos.length > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Avance por piso</h4>
                    <div class="space-y-2">
            `;

            pisos.forEach((piso) => {
                const porcentaje = Math.min(100, Math.max(0, Number(piso.porcentaje || 0)));

                html += `
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">${escapeHtml(piso.label || 'Piso')}</span>
                            <span class="text-sm font-bold text-blue-600">${porcentaje}%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: ${porcentaje}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Revisados: ${piso.revisados || 0} / ${piso.total || 0} - Alertas: ${piso.alertas || 0}</p>
                    </div>
                `;
            });

            html += `</div></div>`;
        }

        if (estado.ultimo_analisis) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.fecha || 'Sin registro')}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Componente: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.componente || 'N/A')}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Piso: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.piso || 'N/A')}</span></p>
                    </div>
                </div>
            `;
        }

        if ((estado.acciones_pendientes || 0) > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta central tiene ${escapeHtml(estado.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        ${dashboardConfig.urls.centralPlans ? `
                        <a href="${escapeHtml(dashboardConfig.urls.centralPlans)}&linea_id=${central.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        if (sections.length === 0 && !(estado.acciones_pendientes > 0)) {
            html += renderPasteurizadoraEmptyDetail('No hay registros con alertas activas para esta Central Hidraulica.');
        }

        html += `
            <div class="flex flex-wrap justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                ${dashboardConfig.urls.centralAnalyses ? `
                <a href="${escapeHtml(dashboardConfig.urls.centralAnalyses)}?linea_id=${central.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-droplet mr-1"></i> Ver Analisis
                </a>
                <a href="${escapeHtml(dashboardConfig.urls.centralHistory)}?linea_id=${central.id}" class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-800 transition">
                    <i class="fas fa-history mr-1"></i> Historico
                </a>
                ` : ''}
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `;

        modalBody.innerHTML = html;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('alertModal');
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('alertModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
// These names are used by the existing inline controls.
Object.assign(window, { refreshData, showPasteurizadoraDetail, showCentralHidraulicaDetail, closeModal });
