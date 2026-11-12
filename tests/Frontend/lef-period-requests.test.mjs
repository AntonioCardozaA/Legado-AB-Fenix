import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext, Script } from 'node:vm';

const view = readFileSync(new URL('../../resources/views/analisis-52-12-4/index.blade.php', import.meta.url), 'utf8');
const source = view.slice(view.indexOf('    function loadPeriods('), view.indexOf('    function renderDashboard('));
const flush = () => new Promise((resolve) => setImmediate(resolve));

test('the complete dashboard script parses after Blade values are inserted', () => {
    const script = view.slice(view.indexOf('<script>') + '<script>'.length, view.lastIndexOf('</script>'))
        .replace(/@json\(\$lineasPayload\)/g, '[]')
        .replace(/@json\(\$selectedLineaId\)/g, '"all"')
        .replace(/@json\(route\('[^']+'\)\)/g, '"/test"');
    assert.doesNotThrow(() => new Script(script));
});

test('the general chart shows all lines and restores the individual line chart', () => {
    const chartSource = view.slice(view.indexOf('    function renderWasherChart('), view.indexOf('    function renderComparisonChart('));
    const width = { style: { minWidth: '' } };
    const charts = {};
    const periods = [
        { label: '52 SEM', datasetLabel: '52', valueKey: 'value_52_weeks', color: 'orange' },
        { label: '4 SEM', datasetLabel: '4', valueKey: 'value_4_weeks', color: 'yellow' },
    ];
    const context = {
        document: { getElementById: () => ({}), querySelector: () => width },
        charts, selectedPeriodDefinitions: () => periods, valueLabelPlugin: {},
        dataset: (label, data) => ({ label, data }), baseChartOptions: () => ({}),
        setChartWidth: () => { width.style.minWidth = '900px'; },
        Chart: class {
            constructor(canvas, config) { this.data = config.data; }
            destroy() { this.destroyed = true; }
        },
    };
    runInNewContext(chartSource, context);
    context.renderWasherChart(null, [
        { linea: 'L-04', value_52_weeks: 4, value_4_weeks: 2 },
        { linea: 'L-05', value_52_weeks: null, value_4_weeks: null },
    ]);
    const allChart = charts.washerChart;
    assert.deepEqual(Array.from(allChart.data.labels), ['L-04', 'L-05']);
    assert.deepEqual(Array.from(allChart.data.datasets[0].data), [4, null]);
    assert.equal(width.style.minWidth, '900px');
    context.renderWasherChart({ name: 'LINEA COMPLETA', value_52_weeks: 5, value_4_weeks: 3 });
    assert.equal(allChart.destroyed, true);
    assert.deepEqual(Array.from(charts.washerChart.data.labels), ['52 SEM', '4 SEM']);
    assert.deepEqual(Array.from(charts.washerChart.data.datasets[0].data), [5, 3]);
    assert.equal(width.style.minWidth, '');
});

function page() {
    const calls = [], rendered = [], errors = [];
    const state = { lineaId: 1, dataDate: null, period: 'all', analysis: 'all', periodsRequestId: 0, dataRequestId: 0 };
    const date = { value: '', min: '', max: '', disabled: false };
    const context = {
        state, nodes: { date }, endpoints: { periods: '/periods', data: '/data' },
        AbortController, URLSearchParams, encodeURIComponent,
        syncStateFromControls() { state.dataDate = date.value || null; },
        setClearLoading() {}, hideFeedback() {},
        renderDashboard: (payload) => rendered.push(payload),
        renderEmpty: (message) => errors.push(message),
        showFeedback: (message) => errors.push(message),
        fetch(url, options) {
            let resolve, reject;
            const pending = new Promise((yes, no) => { resolve = yes; reject = no; });
            calls.push({ url, signal: options.signal, finish: (data) => resolve({ ok: true, json: () => data }), reject });
            return pending;
        },
    };
    runInNewContext(source, context);
    return { context, state, date, calls, rendered, errors };
}

test('all lines loads its total trend and late individual failures cannot replace it', async () => {
    const p = page();
    Object.assign(p.state, { lineaId: 'all', trendVisible: true, trendRequestId: 0 });
    Object.assign(p.context, {
        setTrendLoading() {},
        renderTrend: (payload) => p.rendered.push(payload),
        renderTrendEmpty: (message) => p.errors.push(message),
    });
    p.context.endpoints.trend = '/trend';
    runInNewContext(view.slice(view.indexOf('    function loadTrend('), view.indexOf('    function setTrendLoading(')), p.context);
    p.context.loadTrend();
    assert.equal(p.calls[0].url, '/trend?scope=all');
    p.calls[0].finish({ all_lines: true });
    await flush();
    assert.equal(p.rendered[0].all_lines, true);

    p.state.lineaId = 1;
    p.context.loadTrend();
    assert.equal(p.calls[1].url, '/trend?linea_id=1');
    p.state.lineaId = 'all';
    p.context.loadTrend();
    assert.equal(p.calls[1].signal.aborted, true);
    p.calls[2].finish({ all_lines: true, total: 11 });
    p.calls[1].reject(new Error('Stale individual trend failure'));
    await flush();
    assert.equal(p.rendered.at(-1).total, 11);
    assert.equal(p.errors.length, 0);
});

test('the trend chart plots total LEF in all windows for both years and can return to one line', () => {
    const charts = {};
    const nodes = { trendHeading: {}, trendTitle: {}, trendEmpty: {}, trendChartWrap: {} };
    const context = {
        charts, nodes, document: { getElementById: () => ({}) },
        compactQuery: { matches: false }, valueLabelPlugin: {},
        periodDefinitions: ['52', '12', '4'].map((period) => ({ key: period, label: `${period} SEM`, valueKey: `value_${period}_weeks` })),
        currentLineName: () => 'Todas las lineas', trendChartOptions: () => ({}),
        Chart: class {
            constructor(canvas, config) { this.config = config; this.data = config.data; }
            destroy() { this.destroyed = true; }
        },
    };
    runInNewContext(view.slice(view.indexOf('    function renderTrend('), view.indexOf('    function renderTrendEmpty(')), context);
    const payload = {
        has_data: true, all_lines: true, linea: 'Todas las lineas', machine_name: 'LAVADORAS - LEF TOTAL',
        years: [2025, 2026], months: ['Ene', 'Feb'],
        series: {
            2025: [{ value_52_weeks: 15, value_12_weeks: 10, value_4_weeks: 5 }, { value_52_weeks: null, value_12_weeks: null, value_4_weeks: null }],
            2026: [{ value_52_weeks: 11, value_12_weeks: 7, value_4_weeks: 5 }, { value_52_weeks: 0, value_12_weeks: 0, value_4_weeks: 0 }],
        },
    };
    context.renderTrend(payload);
    const totalChart = charts.washerTrendChart;
    assert.equal(totalChart.config.type, 'line');
    assert.equal(totalChart.data.datasets.length, 6);
    assert.deepEqual(Array.from(totalChart.data.datasets[0].data), [15, null]);
    assert.deepEqual(Array.from(totalChart.data.datasets[3].data), [11, 0]);
    assert.deepEqual(Array.from(totalChart.data.datasets[4].data), [7, 0]);
    assert.deepEqual(Array.from(totalChart.data.datasets[5].data), [5, 0]);
    assert.match(nodes.trendHeading.textContent, /LEF total/);
    assert.match(nodes.trendTitle.textContent, /Todas las lineas/);
    assert.equal(nodes.trendChartWrap.hidden, false);
    context.renderTrend({ ...payload, all_lines: false, linea: 'L-04', machine_name: 'LAVADORA' });
    assert.equal(totalChart.destroyed, true);
    assert.match(nodes.trendHeading.textContent, /Maquina LAVADORA/);
    assert.match(nodes.trendTitle.textContent, /L-04/);
});

test('data starts before periods finish and runs only once', async () => {
    const p = page();
    p.context.loadPeriods(true);
    assert.equal(p.calls.length, 2);
    assert.ok(p.calls[0].url.startsWith('/data?'));
    assert.equal(p.date.disabled, true);
    p.calls[0].finish({ line: 1 });
    await flush();
    assert.equal(p.rendered[0].line, 1);
    p.calls[1].finish({ items: [{ data_date: '2026-08-01' }, { data_date: '2026-07-01' }] });
    await flush();
    assert.equal(p.calls.length, 2);
    assert.equal(p.date.min, '2026-07-01');
    assert.equal(p.date.max, '2026-08-01');
    assert.equal(p.date.disabled, false);
});

test('all lines loads data and periods without sending an invalid line ID and can switch back', async () => {
    const p = page();
    p.context.loadPeriods(true);
    p.state.lineaId = 'all';
    p.context.loadPeriods(true);
    assert.equal(p.calls[0].signal.aborted, true);
    assert.equal(p.calls[1].signal.aborted, true);
    assert.equal(new URL(p.calls[2].url, 'http://localhost').searchParams.get('scope'), 'all');
    assert.equal(new URL(p.calls[2].url, 'http://localhost').searchParams.has('linea_id'), false);
    assert.equal(p.calls[3].url, '/periods?scope=all');
    p.calls[2].finish({ all_lines: true });
    p.calls[3].finish({ items: [{ data_date: '2026-10-31' }, { data_date: '2026-09-30' }] });
    await flush();
    assert.equal(p.rendered[0].all_lines, true);
    assert.equal(p.date.min, '2026-09-30');
    assert.equal(p.date.max, '2026-10-31');
    p.state.lineaId = 2;
    p.context.loadPeriods(true);
    assert.match(p.calls[4].url, /linea_id=2/);
    assert.doesNotMatch(p.calls[4].url, /scope=all/);
});

test('rapid line changes cancel old requests and discard late data and periods', async () => {
    const p = page();
    p.context.loadPeriods(true);
    p.state.lineaId = 2;
    p.context.loadPeriods(true);
    assert.equal(p.calls[0].signal.aborted, true);
    assert.equal(p.calls[1].signal.aborted, true);
    assert.match(p.calls[2].url, /linea_id=2/);
    p.calls[2].finish({ line: 2 });
    p.calls[3].finish({ items: [{ data_date: '2026-10-01' }] });
    await flush();
    p.calls[0].finish({ line: 1 });
    p.calls[1].finish({ items: [{ data_date: '2020-01-01' }] });
    await flush();
    assert.equal(p.rendered.length, 1);
    assert.equal(p.rendered[0].line, 2);
    assert.equal(p.date.max, '2026-10-01');
});

test('stale failures cannot overwrite a current dashboard and current period failures recover date input', async () => {
    const p = page();
    p.context.loadPeriods(true);
    p.state.lineaId = 2;
    p.context.loadPeriods(true);
    p.calls[2].finish({ line: 2 });
    p.calls[0].reject(new Error('Stale data failure'));
    p.calls[1].reject(new Error('Stale periods failure'));
    await flush();
    assert.equal(p.errors.length, 0);
    p.calls[3].reject(new Error('Current periods failure'));
    await flush();
    assert.equal(p.errors.length, 1);
    assert.equal(p.date.disabled, false);
    assert.equal(p.state.loadingPeriods, false);
    assert.equal(p.rendered[0].line, 2);
});
