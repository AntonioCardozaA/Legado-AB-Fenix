import test from 'node:test';
import assert from 'node:assert/strict';
import { createVisibleChartRenderer } from '../../resources/js/visible-chart.mjs';

function browser({ observer = true } = {}) {
    const listeners = {}, observed = new Set();
    let callback, observers = 0;
    const environment = {
        innerHeight: 1000, matchMedia: () => ({ matches: false }),
        addEventListener: (type, listener) => { listeners[type] = listener; },
        IntersectionObserver: observer ? class {
            constructor(handler) { callback = handler; observers++; }
            observe(element) { observed.add(element); }
            unobserve(element) { observed.delete(element); }
        } : undefined,
    };
    return { render: createVisibleChartRenderer(environment), environment, observed, listeners,
        get observers() { return observers; }, enter: element => callback([{ target: element, isIntersecting: true }]) };
}
const element = top => ({ getBoundingClientRect: () => ({ top, bottom: top + 200 }) });

test('nearby charts render immediately while distant charts wait until observed', () => {
    const page = browser(), calls = [], far = element(4000);
    page.render(element(900), () => calls.push('near'));
    page.render(far, () => calls.push('far'));
    assert.deepEqual(calls, ['near']);
    assert.equal(page.observed.size, 1);
    page.enter(far); page.enter(far);
    assert.deepEqual(calls, ['near', 'far']);
    assert.equal(page.observed.size, 0);
});

test('rapid filter changes render the latest configuration once and share one observer', () => {
    const page = browser(), calls = [], first = element(4000), second = element(5000);
    page.render(first, () => calls.push('obsolete'));
    page.render(first, () => calls.push('latest'));
    page.render(second, () => calls.push('second'));
    page.enter(first); page.enter(second);
    assert.deepEqual(calls, ['latest', 'second']);
    assert.equal(page.observers, 1);
});

test('print flushes pending charts once, including charts below the screen', () => {
    const page = browser(), calls = [], far = element(4000);
    page.render(far, () => calls.push('print'));
    page.listeners.beforeprint(); page.listeners.beforeprint(); page.enter(far);
    assert.deepEqual(calls, ['print']);
    assert.equal(page.observed.size, 0);
});

test('unsupported browsers and print documents render immediately', () => {
    const page = browser({ observer: false }), calls = [];
    page.render(element(4000), () => calls.push('unsupported'));
    const printPage = browser(); printPage.environment.matchMedia = () => ({ matches: true });
    printPage.render(element(4000), () => calls.push('print'));
    assert.deepEqual(calls, ['unsupported', 'print']);
});

test('an empty filter can cancel a pending chart without resurrecting obsolete data', () => {
    const page = browser(), calls = [], far = element(4000);
    page.render(far, () => calls.push('obsolete'));
    page.render.cancel(far);
    page.enter(far); page.listeners.beforeprint();
    assert.deepEqual(calls, []);
    assert.equal(page.observed.size, 0);
});
