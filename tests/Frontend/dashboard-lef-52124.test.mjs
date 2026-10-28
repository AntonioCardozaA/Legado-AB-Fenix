import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/dashboard-lef-52124.js', import.meta.url), 'utf8');

test('the washer selector updates both LEF charts and the Pareto highlight', () => {
    const callbacks = {};
    const elements = new Map();
    const chartInstances = [];
    const timers = new Map();
    const motion = { matches: false, addEventListener(event, callback) { callbacks.motion = callback; } };
    let nextTimer = 0;

    function element(id) {
        if (!elements.has(id)) {
            elements.set(id, {
                id,
                hidden: false,
                value: '',
                textContent: '',
                clientWidth: 700,
                style: {},
                attributes: {},
                addEventListener(event, callback) { callbacks[`${id}:${event}`] = callback; },
                setAttribute(name, value) { this.attributes[name] = value; },
            });
        }
        return elements.get(id);
    }

    class ChartStub {
        constructor(canvas, config) {
            this.canvas = canvas;
            this.config = config;
            this.data = config.data;
            chartInstances.push(this);
        }
        destroy() { this.destroyed = true; }
        update() { this.updated = true; }
    }

    element('lefDashboardData').textContent = JSON.stringify({
        period: '2026-10',
        lineas: [
            {
                id: 1, nombre: 'L-04', import_id: 10,
                washer: { name: 'LAVADORA', value_52_weeks: 0.38, value_12_weeks: 0.26, value_4_weeks: 0.30 },
                machines: [
                    { name: 'TRANSPORTADOR', value_52_weeks: 0.75, value_12_weeks: 0.50, value_4_weeks: 0.25, highlight: false },
                    { name: 'LAVADORA', value_52_weeks: 0.38, value_12_weeks: 0.26, value_4_weeks: 0.30, highlight: true },
                ],
            },
            { id: 2, nombre: 'L-05', import_id: null, washer: null, machines: [] },
            {
                id: 3, nombre: 'L-06', import_id: 11,
                washer: { name: 'LAVADORA', value_52_weeks: 0.8, value_12_weeks: 0.7, value_4_weeks: 0.6 },
                machines: [{ name: 'LAVADORA', value_52_weeks: 0.8, value_12_weeks: 0.7, value_4_weeks: 0.6, highlight: true }],
            },
        ],
    });

    const document = {
        hidden: false,
        getElementById: element,
        addEventListener(event, callback) { callbacks[event] = callback; },
    };
    const window = {
        Chart: ChartStub,
        matchMedia() { return motion; },
        addEventListener(event, callback) { callbacks[event] = callback; },
        setInterval(callback) { timers.set(++nextTimer, callback); return nextTimer; },
        clearInterval(id) { timers.delete(id); },
    };

    runInNewContext(source, { document, window, Chart: ChartStub, Intl, Date, console });
    callbacks.DOMContentLoaded();

    assert.equal(element('lefDashboardLinea').value, '1');
    assert.deepEqual(Array.from(chartInstances[0].data.datasets[0].data), [0.38, 0.26, 0.3]);
    assert.deepEqual(Array.from(chartInstances[1].data.labels), ['TRANSPORTADOR', 'LAVADORA']);
    assert.match(element('lefDashboardPosition').textContent, /posición 2 de 2/);
    assert.match(chartInstances[1].data.datasets[0].backgroundColor[1], /^rgba\(13,148,136/);
    assert.equal(timers.size, 1);

    timers.values().next().value();
    assert.equal(chartInstances[1].updated, true);

    element('lefDashboardLinea').value = '2';
    callbacks['lefDashboardLinea:change']();
    assert.equal(element('lefDashboardCharts').hidden, true);
    assert.match(element('lefDashboardEmpty').textContent, /no tiene información cargada/);
    assert.equal(timers.size, 0);

    element('lefDashboardLinea').value = '3';
    callbacks['lefDashboardLinea:change']();
    assert.deepEqual(Array.from(chartInstances.at(-2).data.datasets[0].data), [0.8, 0.7, 0.6]);
    assert.match(element('lefDashboardPosition').textContent, /posición 1 de 1/);

    motion.matches = true;
    callbacks.motion();
    assert.equal(timers.size, 0);
    assert.equal(chartInstances.at(-1).config.options.animation, false);
});
