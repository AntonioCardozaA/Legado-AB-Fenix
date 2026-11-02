import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const view = readFileSync(new URL('../../resources/views/analisis-52-12-4/index.blade.php', import.meta.url), 'utf8');
const source = view.slice(view.indexOf('    function loadPeriods('), view.indexOf('    function renderDashboard('));
const flush = () => new Promise((resolve) => setImmediate(resolve));

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
