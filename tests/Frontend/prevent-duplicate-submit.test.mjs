import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/prevent-duplicate-submit.js', import.meta.url), 'utf8');
function fixture({ loading = false } = {}) {
    const listeners = {}, attributes = new Map(), buttons = [{ disabled: false }, { disabled: true }];
    buttons.forEach(button => {
        button.innerHTML = 'Save'; button.className = 'original';
        button.classList = { add: (...classes) => { button.className += ' ' + classes.join(' '); } };
    });
    const form = {
        dataset: { submitLoading: String(loading) },
        matches: () => true, querySelectorAll: () => buttons,
        getAttribute: (name) => attributes.get(name) ?? null,
        setAttribute: (name, value) => attributes.set(name, value), removeAttribute: (name) => attributes.delete(name),
    };
    runInNewContext(source, {
        document: { addEventListener: (type, callback) => { listeners[type] = callback; } },
        window: { addEventListener: (type, callback) => { listeners[type] = callback; } },
    });
    return { buttons, attributes, form, restore: () => listeners.pageshow(), submit(prevented = false, target = form) {
        const event = { target, defaultPrevented: prevented, preventDefault() { this.defaultPrevented = true; } };
        listeners.submit(event);
        return event;
    } };
}

test('two immediate submits admit one request and disable save buttons', () => {
    const f = fixture();
    assert.equal(f.submit().defaultPrevented, false);
    assert.equal(f.submit().defaultPrevented, true);
    assert.equal(f.buttons[0].disabled, true);
    assert.equal(f.attributes.get('aria-busy'), 'true');
});
test('rejected photo/side validation leaves a valid retry available', () => {
    const f = fixture();
    f.submit(true);
    assert.equal(f.buttons[0].disabled, false);
    assert.equal(f.submit().defaultPrevented, false);
});
test('back/forward restores original button and accessibility states', () => {
    const f = fixture();
    f.submit();
    f.restore();
    assert.deepEqual(f.buttons.map((button) => button.disabled), [false, true]);
    assert.equal(f.attributes.has('aria-busy'), false);
    assert.equal(f.submit().defaultPrevented, false);
});
test('other forms remain unaffected', () => {
    const f = fixture();
    const other = { matches: () => false };
    assert.equal(f.submit(false, other).defaultPrevented, false);
});

test('quick pasteurizer loading indicator preserves a retry and restores its text and classes on return', () => {
    const f = fixture({ loading: true });
    f.submit(true);
    assert.equal(f.buttons[0].innerHTML, 'Save');
    f.submit();
    assert.match(f.buttons[0].innerHTML, /Guardando/);
    assert.match(f.buttons[0].className, /cursor-wait/);
    assert.equal(f.submit().defaultPrevented, true);
    f.restore();
    assert.equal(f.buttons[0].innerHTML, 'Save');
    assert.equal(f.buttons[0].className, 'original');
    assert.equal(f.buttons[0].disabled, false);
    assert.equal(f.buttons[1].disabled, true);
});
