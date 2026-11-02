import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/diagramas-lavadoras.js', import.meta.url), 'utf8');

function classes(initial = []) {
    const values = new Set(initial);
    return {
        contains: (name) => values.has(name), add: (name) => values.add(name), remove: (name) => values.delete(name),
        toggle(name, force = !values.has(name)) { force ? values.add(name) : values.delete(name); return force; },
    };
}

function monitor(script = source) {
    const events = {}, timers = new Map();
    let observer, mutation, next = 0;
    const target = { dataset: { monitorSeverities: 'red,yellow' }, classList: classes(['monitor-severity-red']) };
    const svg = { paused: false, pauseAnimations() { this.paused = true; }, unpauseAnimations() { this.paused = false; } };
    const diagram = {
        classList: classes(), querySelector: () => null,
        querySelectorAll: (selector) => selector === 'svg' ? [svg] : [target],
    };
    const context = {
        document: {
            hidden: false, addEventListener: (type, callback) => { events[type] = callback; },
            querySelectorAll: () => [diagram], body: { appendChild() {} },
            createElement: () => ({ setAttribute() {}, classList: classes() }),
        },
        window: {
            setInterval(callback) { timers.set(++next, callback); return next; }, clearInterval(id) { timers.delete(id); }, addEventListener() {},
        },
        MutationObserver: class { constructor(callback) { mutation = callback; } observe() {} },
        IntersectionObserver: class { constructor(callback) { observer = callback; } observe() {} },
    };
    runInNewContext(script, context);
    events.DOMContentLoaded();
    return {
        target, svg, diagram, timers,
        tick() { [...timers.values()].forEach((callback) => callback()); },
        visibility(hidden) { context.document.hidden = hidden; events.visibilitychange?.(); },
        viewport(visible) { observer?.([{ target: diagram, isIntersecting: visible }]); },
        replace() { mutation([{ type: 'childList' }]); },
    };
}

test('SVG hidden intervals stop and retain the color sequence when returning', () => {
    const m = monitor();
    m.tick();
    assert.equal(m.target.classList.contains('monitor-severity-yellow'), true);
    m.visibility(true);
    assert.equal(m.timers.size, 0);
    assert.equal(m.svg.paused, true);
    m.tick();
    assert.equal(m.target.classList.contains('monitor-severity-yellow'), true);
    m.visibility(false);
    assert.equal(m.timers.size, 1);
    assert.equal(m.svg.paused, false);
    m.tick();
    assert.equal(m.target.classList.contains('monitor-severity-red'), true);
});

test('offscreen diagrams stay suspended during updates and resume without duplicate intervals', () => {
    const m = monitor();
    m.viewport(false);
    assert.equal(m.timers.size, 0);
    assert.equal(m.diagram.classList.contains('is-background-paused'), true);
    m.replace();
    assert.equal(m.timers.size, 0);
    m.viewport(true);
    m.visibility(false);
    m.viewport(true);
    assert.equal(m.timers.size, 1);
    m.diagram.classList.add('is-paused');
    m.visibility(true);
    m.visibility(false);
    assert.equal(m.svg.paused, true, 'Returning to the viewport must preserve the manual pause.');
});
