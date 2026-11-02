// Local Chrome integration checks: real production scripts, fetch, abort, visibility and Canvas.
// Endpoints and data are synthetic; no operational DB, provider, credentials or CDN.
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdtemp } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { once } from 'node:events';

const layout = await readFile('resources/views/layouts/app.blade.php', 'utf8');
const view = await readFile('resources/views/analisis-52-12-4/index.blade.php', 'utf8');
const start = layout.indexOf('let notificationRefreshPending = false;');
const end = layout.indexOf('\n(function() {', start);
const lefStart = view.indexOf('    function loadPeriods('), lefEnd = view.indexOf('    function renderDashboard(');
assert.ok(start >= 0 && end > start && lefStart >= 0 && lefEnd > lefStart);
const polling = layout.slice(start, end), lef = view.slice(lefStart, lefEnd);
const animator = await readFile('public/js/diagram-animator/chain-animator.js', 'utf8');
const submitGuard = await readFile('public/js/prevent-duplicate-submit.js', 'utf8');
const requests = [];
let notificationsFail = false;
const server = createServer(async (req, res) => {
    const url = new URL(req.url, 'http://localhost');
    if (req.method !== 'GET') { res.writeHead(405); res.end(); return; }
    if (url.pathname.startsWith('/api/')) {
        const entry = { path: url.pathname, line: url.searchParams.get('linea_id'), start: performance.now() };
        requests.push(entry);
        const isPeriods = url.pathname.endsWith('periods');
        const ms = url.pathname.endsWith('notifications') ? 350 : entry.line === '1' ? (isPeriods ? 650 : 400) : 80;
        await new Promise(resolve => setTimeout(resolve, ms));
        const failed = url.pathname.endsWith('notifications') && notificationsFail;
        entry.end = performance.now(); entry.failed = failed;
        if (!res.destroyed) {
            res.writeHead(failed ? 503 : 200, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
            res.end(JSON.stringify(isPeriods ? { items: [{ data_date: entry.line === '1' ? '2026-01-01' : '2026-10-01' }] }
                : { line: entry.line, count: 2, items: [{ id: 'new', message: 'Updated' }] }));
        }
        return;
    }
    if (url.pathname === '/canvas.js') { res.writeHead(200, { 'Content-Type': 'text/javascript' }); res.end(animator); return; }
    if (url.pathname === '/submit-guard.js') { res.writeHead(200, { 'Content-Type': 'text/javascript' }); res.end(submitGuard); return; }
    if (url.pathname === '/image.svg') { res.writeHead(200, { 'Content-Type': 'image/svg+xml' }); res.end('<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="600"><rect width="1000" height="600" fill="white"/></svg>'); return; }
    const page = url.pathname;
    const body = page === '/submit' ? `<form data-prevent-duplicate-submit data-submit-loading="true"><input required value="Valid"><button type="submit" class="original">Save</button></form>
        ${url.searchParams.get('guard') === '0' ? '' : '<script src="/submit-guard.js"></script>'}
        <script>window.processing=true;window.admitted=0;window.restoredFromCache=false;const form=document.querySelector('form');
        form.addEventListener('submit',event=>{if(processing)event.preventDefault();});
        document.addEventListener('submit',event=>{if(!event.defaultPrevented){admitted++;fetch('/api/submit');}event.preventDefault();});
        window.addEventListener('pageshow',event=>{restoredFromCache=event.persisted;});</script>` : page === '/polling' ? `<span id="notification-badge">1</span><div id="notifications-list">Initial</div><script>
        const notificationsUnreadCountUrl='/api/notifications';
        function updateNotificationControls(data){document.getElementById('notification-badge').textContent=data.count;}
        function renderNotifications(items){document.getElementById('notifications-list').textContent=items[0]?.message||'';}
        ${polling}</script>` : page === '/lef' ? `<input type="date" id="date"><div id="rendered"></div><div id="feedback"></div><script>
        const state={lineaId:1,dataDate:null,period:'all',analysis:'all',periodsRequestId:0,dataRequestId:0};
        const nodes={date:document.getElementById('date')},endpoints={periods:'/api/periods',data:'/api/data'};
        const rendered=[],feedback=[]; let renderedAt;
        function syncStateFromControls(){state.dataDate=nodes.date.value||null;}
        function setClearLoading(){} function hideFeedback(){}
        function renderDashboard(payload){rendered.push(payload);renderedAt=performance.now();document.getElementById('rendered').textContent=payload.line;}
        function renderEmpty(message){feedback.push(message);} function showFeedback(message){feedback.push(message);}
        ${lef}</script>` : page === '/canvas' ? `<style>canvas{width:min(100%,800px);height:auto}</style><canvas id="diagram-canvas-line-04"></canvas><div style="height:3000px"></div>
        <script>window.diagramInstances={};window.draws=0;window.pendingRafs=new Set();const nativeRaf=requestAnimationFrame,nativeCancel=cancelAnimationFrame;
        window.requestAnimationFrame=callback=>{let id=nativeRaf(time=>{pendingRafs.delete(id);callback(time);});pendingRafs.add(id);return id;};window.cancelAnimationFrame=id=>{pendingRafs.delete(id);nativeCancel(id);};
        const originalDraw=CanvasRenderingContext2D.prototype.drawImage;CanvasRenderingContext2D.prototype.drawImage=function(...args){window.draws++;return originalDraw.apply(this,args);};</script>
        <script src="/canvas.js"></script><script>initializeDiagram('line-04','/image.svg');</script>` : '<p>Background tab</p>';
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' }); res.end('<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body>' + body + '</body></html>');
});
server.listen(0, '127.0.0.1'); await once(server, 'listening');
const origin = `http://127.0.0.1:${server.address().port}`;
const portServer = createServer(); portServer.listen(0, '127.0.0.1'); await once(portServer, 'listening');
const debugPort = portServer.address().port; await new Promise(resolve => portServer.close(resolve));
const output = await mkdtemp(join(tmpdir(), 'legado-browser-async-'));
const browser = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--no-first-run', '--no-default-browser-check',
    `--remote-debugging-port=${debugPort}`, `--user-data-dir=${join(output, 'profile')}`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
let socket, send;
try {
    let target;
    for (let i = 0; i < 100 && !target; i++) {
        try { target = (await (await fetch(`http://127.0.0.1:${debugPort}/json`)).json()).find(item => item.type === 'page'); } catch {}
        if (!target) await delay(100);
    }
    assert.ok(target, 'Chrome exposes a page');
    socket = new WebSocket(target.webSocketDebuggerUrl); await once(socket, 'open');
    let next = 0; const pending = new Map(), errors = [];
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.id) {
            const item = pending.get(message.id); pending.delete(message.id);
            message.error ? item?.reject(new Error(JSON.stringify(message.error))) : item?.resolve(message.result);
        }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails);
    });
    send = (method, params = {}) => new Promise((resolve, reject) => { const id = ++next; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params })); });
    const evaluate = async expression => {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
        return result.result.value;
    };
    const waitFor = async expression => {
        for (let i = 0; i < 100; i++) { if (await evaluate(expression)) return; await delay(50); }
        throw new Error('Condition timed out: ' + expression);
    };
    const navigate = async path => { await send('Page.navigate', { url: origin + path }); await waitFor(`document.readyState==='complete'&&location.pathname===${JSON.stringify(new URL(path, origin).pathname)}`); };
    await send('Page.enable'); await send('Runtime.enable');
    await navigate('/submit?guard=0');
    const baseline = await evaluate('processing=false;form.requestSubmit();form.requestSubmit();admitted');
    assert.equal(baseline, 2);
    await navigate('/submit');
    const blocked = await evaluate('form.requestSubmit();({admitted,enabled:!form.querySelector("button").disabled})');
    assert.deepEqual(blocked, { admitted: 0, enabled: true });
    const guarded = await evaluate('processing=false;form.requestSubmit();form.requestSubmit();({admitted,busy:form.getAttribute("aria-busy"),loading:form.querySelector("button").textContent})');
    assert.equal(guarded.admitted, 1); assert.equal(guarded.busy, 'true'); assert.equal(guarded.loading, 'Guardando...');
    await delay(150);
    await navigate('/background'); await evaluate('history.back()');
    await waitFor('location.pathname==="/submit"&&document.readyState==="complete"'); await delay(100);
    const returned = await evaluate('({enabled:!document.querySelector("button").disabled,text:document.querySelector("button").textContent,classes:document.querySelector("button").className,bfcache:restoredFromCache})');
    assert.equal(returned.enabled, true); assert.equal(returned.text, 'Save'); assert.equal(returned.classes, 'original');
    const formResult = { withoutGuardAdmitted: baseline, withGuardAdmitted: guarded.admitted, validatorRejectsWithoutLocking: true, returned };
    requests.length=0;
    let result = { forms: formResult, runtimeErrors: errors.length, syntheticEndpoints: true };
    if (process.env.LEGADO_FORM_ONLY !== '1') {
    await navigate('/polling'); await delay(500);
    assert.equal(requests.length, 0, 'Blade initial state does not cause a duplicate fetch');
    await evaluate('refreshNotifications();refreshNotifications();refreshNotifications();'); await delay(500);
    assert.equal(requests.length, 1, 'Native pending fetch cannot overlap');
    notificationsFail = true;
    await evaluate('refreshNotifications()');
    assert.equal(await evaluate('document.getElementById("notifications-list").textContent'), 'Updated');
    notificationsFail = false; await evaluate('refreshNotifications()');
    assert.equal(requests.length, 3, 'HTTP failure releases the pending guard');
    const other = await send('Target.createTarget', { url: origin + '/background' });
    await send('Target.activateTarget', { targetId: other.targetId });
    await waitFor('document.hidden');
    const hiddenRequests = requests.length;
    await delay(31000); // Cross the real 30-second polling interval while Chrome reports hidden.
    assert.equal(requests.length, hiddenRequests, 'Hidden tab skips its real timer');
    await send('Target.activateTarget', { targetId: target.id });
    await waitFor('!document.hidden'); await delay(500);
    assert.equal(requests.length, hiddenRequests + 1, 'Returning to the tab revalidates immediately');
    await send('Target.closeTarget', { targetId: other.targetId });
    const pollingResult = { initialFetches: 0, overlapPrevented: true, httpFailureRecovery: true, hiddenWaitMs: 31000, hiddenFetches: 0, visibleRevalidation: true };

    await navigate('/lef');
    const serial = await evaluate(`(async()=>{const start=performance.now();await fetch('/api/periods?linea_id=1').then(r=>r.json());await fetch('/api/data?linea_id=1').then(r=>r.json());return performance.now()-start;})()`);
    const requestStart = requests.length;
    const parallel = await evaluate(`(async()=>{const start=performance.now();loadPeriods(true);while(state.loadingPeriods||!rendered.length)await new Promise(r=>setTimeout(r,10));return {totalMs:performance.now()-start,dataMs:renderedAt-start,rendered:rendered.length,dateMax:nodes.date.max};})()`);
    const pair = requests.slice(requestStart);
    assert.equal(pair.length, 2); assert.ok(Math.abs(pair[0].start - pair[1].start) < 100, 'Native fetches begin together');
    assert.equal(parallel.rendered, 1); assert.equal(parallel.dateMax, '2026-01-01');
    assert.ok(parallel.dataMs < serial - 250, 'Data no longer waits for the synthetic periods response');
    await evaluate('rendered.length=0;state.lineaId=1;loadPeriods(true);state.lineaId=2;loadPeriods(true);'); await delay(900);
    const latest = await evaluate('({rendered:rendered.map(p=>p.line),dateMax:nodes.date.max,errors:feedback.length,dateEnabled:!nodes.date.disabled})');
    assert.deepEqual(latest, { rendered: ['2'], dateMax: '2026-10-01', errors: 0, dateEnabled: true });
    const lefResult = { sequentialTransportMs: serial, parallel, startsApartMs: Math.abs(pair[0].start - pair[1].start), latest };

    const canvases = [];
    for (const width of [1440, 390]) {
        await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 500 });
        await navigate('/canvas'); await waitFor('!!window.diagramInstances["line-04"]?.visibilityObserver'); await delay(200);
        const paused = await evaluate('(async()=>{const start=draws;await new Promise(r=>setTimeout(r,250));return {initial:draws,extra:draws-start};})()');
        assert.ok(paused.initial >= 1); assert.equal(paused.extra, 0);
        const playing = await evaluate('(async()=>{const i=diagramInstances["line-04"];i.isPlaying=true;animate("line-04");animate("line-04");const single=pendingRafs.size===1;const start=draws;await new Promise(r=>setTimeout(r,250));return {single,frames:draws-start};})()');
        assert.ok(playing.single); assert.ok(playing.frames > 0);
        await evaluate('window.scrollTo(0,document.documentElement.scrollHeight)'); await waitFor('diagramInstances["line-04"].inViewport===false'); await delay(100);
        const offscreen = await evaluate('(async()=>{const start=draws,offset=diagramInstances["line-04"].chainOffset;await new Promise(r=>setTimeout(r,250));return {extra:draws-start,offsetPreserved:offset===diagramInstances["line-04"].chainOffset,playing:diagramInstances["line-04"].isPlaying};})()');
        assert.deepEqual(offscreen, { extra: 0, offsetPreserved: true, playing: true });
        await evaluate('window.scrollTo(0,0)'); await waitFor('diagramInstances["line-04"].inViewport===true');
        await evaluate('diagramInstances["line-04"].isPlaying=false;animate("line-04")');
        const stopped = await evaluate('(async()=>{const start=draws;await new Promise(r=>setTimeout(r,250));return draws-start;})()'); assert.equal(stopped, 0);
        canvases.push({ width, paused, playing, offscreen, manualPauseExtraFrames: stopped });
    }
    assert.equal(errors.length, 0);
    result = { ...result, polling: pollingResult, lef: lefResult, canvases, runtimeErrors: errors.length };
    }
    await writeFile(join(output, 'results.json'), JSON.stringify(result, null, 2));
    console.log(JSON.stringify({ output, ...result }, null, 2));
} finally {
    if (send) { try { await send('Browser.close'); } catch {} }
    socket?.close(); browser.kill(); server.close();
}
