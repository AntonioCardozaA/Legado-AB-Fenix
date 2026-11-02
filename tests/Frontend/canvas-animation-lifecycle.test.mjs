import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/diagram-animator/chain-animator.js', import.meta.url), 'utf8');

function animator() {
    const frames = new Map(), events = {};
    let next = 0, draws = 0;
    const instance = {
        canvas: { width: 100, height: 100 }, ctx: { clearRect() {}, drawImage() { draws++; } }, img: {},
        config: { chainSpeed: 3, chainPathPoints: [], chainLinkSize: 10 }, chainOffset: 0, isPlaying: false, animationSpeed: 1,
    };
    const context = {
        window: { diagramInstances: { fixture: instance } },
        document: { hidden: false, addEventListener(type, callback) { events[type] = callback; } },
        requestAnimationFrame(callback) { frames.set(++next, callback); return next; },
        cancelAnimationFrame(id) { frames.delete(id); },
    };
    runInNewContext(source, context);
    context.getTotalChainLength = () => 100;
    context.drawAnimatedChain = () => {};
    context.drawSprockets = () => {};
    return {
        context, instance, frames, get draws() { return draws; },
        tick(count = 1) { for (let i = 0; i < count; i++) { const scheduled = [...frames.values()]; frames.clear(); scheduled.forEach((callback) => callback()); } },
        visibility(hidden) { context.document.hidden = hidden; events.visibilitychange?.(); },
    };
}

test('a paused canvas draws once and does no further frame work', () => {
    const a = animator();
    a.context.animate('fixture');
    a.tick(60);
    assert.equal(a.draws, 1);
    assert.equal(a.frames.size, 0);
});

test('playing has one loop and pause/resume preserve position', () => {
    const a = animator();
    a.instance.isPlaying = true;
    a.context.animate('fixture');
    a.context.animate('fixture');
    assert.equal(a.frames.size, 1);
    a.tick();
    a.instance.isPlaying = false;
    a.context.animate('fixture');
    const offset = a.instance.chainOffset;
    a.tick(60);
    assert.equal(a.instance.chainOffset, offset);
    assert.equal(a.frames.size, 0);
    a.instance.isPlaying = true;
    a.context.animate('fixture');
    assert.equal(a.instance.chainOffset, offset + 3);
    assert.equal(a.frames.size, 1);
});

test('hidden documents suspend playing frames and resume without resetting the offset', () => {
    const a = animator();
    a.instance.isPlaying = true;
    a.context.animate('fixture');
    a.visibility(true);
    const offset = a.instance.chainOffset, draws = a.draws;
    a.tick(60);
    assert.equal(a.instance.chainOffset, offset);
    assert.equal(a.draws, draws);
    assert.equal(a.frames.size, 0);
    a.visibility(false);
    assert.equal(a.instance.chainOffset, offset + 3);
    assert.equal(a.frames.size, 1);
});

test('a paused canvas initialized while hidden renders its background when visible', () => {
    const a = animator();
    a.context.document.hidden = true;
    a.context.animate('fixture');
    assert.equal(a.draws, 0);
    a.visibility(false);
    assert.equal(a.draws, 1);
    assert.equal(a.frames.size, 0);
});
