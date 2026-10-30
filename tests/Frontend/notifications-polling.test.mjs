import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const layout = readFileSync(new URL('../../resources/views/layouts/app.blade.php', import.meta.url), 'utf8');
const start = layout.indexOf('function refreshNotifications() {');
const end = layout.indexOf('\n(function() {', start);
assert.ok(start >= 0 && end > start, 'Notification polling script must be present in the layout');
const source = layout.slice(start, end);

function notificationPage({ hasBell = true, respond } = {}) {
    const initialItems = [{ id: 'existing', message: 'Already rendered by Blade' }];
    const badge = { textContent: '1' };
    const list = { items: initialItems };
    const requests = [];
    const errors = [];
    const intervals = [];
    let now = 0;
    const payload = { count: 2, notifications_count: 2, items: [{ id: 'new', message: 'New notification' }] };
    const context = {
        document: {
            getElementById(id) {
                if (!hasBell) return null;
                return id === 'notification-badge' ? badge : id === 'notifications-list' ? list : null;
            },
        },
        notificationsUnreadCountUrl: '/notifications/unread-count',
        fetch(url, options) {
            requests.push({ url, options });
            return respond ? respond(requests.length, payload) : Promise.resolve({ json: () => payload });
        },
        updateNotificationControls(data) { badge.textContent = String(data.count); },
        renderNotifications(items) { list.items = items; },
        setInterval(callback, delay) {
            intervals.push({ callback, delay, next: now + delay });
            return intervals.length;
        },
        console: { error: (...args) => errors.push(args) },
    };
    runInNewContext(source, context);

    return {
        badge, list, requests, errors, initialItems, context,
        async advance(milliseconds) {
            const until = now + milliseconds;
            for (;;) {
                const next = intervals.filter((timer) => timer.next <= until).sort((a, b) => a.next - b.next)[0];
                if (!next) break;
                now = next.next;
                next.next += next.delay;
                next.callback();
                await new Promise((resolve) => setImmediate(resolve));
            }
            now = until;
        },
    };
}

test('initial notification state remains visible without a duplicate request', async () => {
    const page = notificationPage();

    assert.equal(page.requests.length, 0);
    assert.equal(page.badge.textContent, '1');
    assert.equal(page.list.items, page.initialItems);
    await page.advance(29999);
    assert.equal(page.requests.length, 0);
    assert.equal(page.list.items, page.initialItems);

    await page.advance(1);
    assert.equal(page.requests.length, 1);
    assert.equal(page.badge.textContent, '2');
    assert.equal(page.list.items[0].id, 'new');
    assert.equal(page.requests[0].url, '/notifications/unread-count');
    assert.equal(page.requests[0].options.headers.Accept, 'application/json');
});

test('notification refresh continues every 30 seconds', async () => {
    const page = notificationPage();
    await page.advance(90000);
    assert.equal(page.requests.length, 3);
    assert.equal(page.errors.length, 0);
});

test('pages without the notification bell do not fetch notifications', async () => {
    const page = notificationPage({ hasBell: false });
    await page.advance(60000);
    assert.equal(page.requests.length, 0);
});

test('a failed refresh preserves the current items and recovers on the next interval', async () => {
    const page = notificationPage({
        respond: (attempt, payload) => attempt === 1
            ? Promise.reject(new Error('Temporary network failure'))
            : Promise.resolve({ json: () => payload }),
    });
    await page.advance(30000);
    assert.equal(page.list.items, page.initialItems);
    assert.equal(page.badge.textContent, '1');
    assert.equal(page.errors.length, 1);

    await page.advance(30000);
    assert.equal(page.requests.length, 2);
    assert.equal(page.list.items[0].id, 'new');
    assert.equal(page.badge.textContent, '2');
});

test('an explicit refresh can still retrieve notifications immediately', async () => {
    const page = notificationPage();
    page.context.refreshNotifications();
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(page.requests.length, 1);
    assert.equal(page.list.items[0].id, 'new');
});
