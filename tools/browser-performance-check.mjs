import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdtemp, stat } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { resolve, join, extname, sep, dirname, basename } from 'node:path';
import { once } from 'node:events';

const fixtureRoot = resolve(process.argv[2] || '');
const comparisonRoot = process.argv[4] ? resolve(process.argv[4]) : null;
const allowedRoots = [tmpdir(), join(process.env.LOCALAPPDATA || tmpdir(), 'Temp')];
for (const candidate of [fixtureRoot, comparisonRoot].filter(Boolean)) {
    if (!basename(candidate).startsWith('legado-browser-') || !allowedRoots.some((root) => candidate.toLowerCase().startsWith(resolve(root).replace(/[\\/]+$/, '').toLowerCase() + sep))) throw new Error('Expected a synthetic fixture directory under TEMP.');
    for (const page of (process.argv[3] || 'form,history,diagrams,dashboard').split(',')) await stat(join(candidate, page + '.html'));
}
const output = await mkdtemp(join(dirname(fixtureRoot), 'legado-browser-check-'));
const publicRoot = resolve('public');
const mime = { '.js': 'text/javascript', '.css': 'text/css', '.png': 'image/png', '.webp': 'image/webp', '.svg': 'image/svg+xml', '.json': 'application/json' };
let origin;
let activeFixtureRoot = fixtureRoot;
const server = createServer(async (req, res) => {
    try {
        if (req.method !== 'GET') { res.writeHead(405); res.end(); return; }
        const path = new URL(req.url, 'http://localhost').pathname;
        if (path === '/_fixture-assets/panels.js') {
            res.writeHead(200, { 'Content-Type': 'text/javascript', 'Cache-Control': 'no-store' });
            res.end(await readFile(join(activeFixtureRoot, 'assets/panels.js'))); return;
        }
        if (path.startsWith('/fixture/')) {
            const file = resolve(activeFixtureRoot, path.slice('/fixture/'.length));
            if (!file.startsWith(activeFixtureRoot + sep) || !file.endsWith('.html')) throw new Error('Invalid fixture.');
            const html = (await readFile(file, 'utf8')).replaceAll('http://localhost', origin).replaceAll('http:\\/\\/localhost', origin);
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' }); res.end(html); return;
        }
        if (path.startsWith('/notifications/') || path.startsWith('/asistente/') || path.startsWith('/busqueda')) {
            res.writeHead(200, { 'Content-Type': 'application/json' }); res.end(JSON.stringify({ count: 0, items: [], conversations: [] })); return;
        }
        if (path === '/storage/fixture.jpg') {
            res.writeHead(200, { 'Content-Type': 'image/png' });
            res.end(await readFile(join(publicRoot, 'images/icon-192.png'))); return;
        }
        const file = resolve(publicRoot, '.' + path);
        if (!file.startsWith(publicRoot + sep) || !(await stat(file)).isFile()) throw new Error('Not public.');
        res.writeHead(200, { 'Content-Type': mime[extname(file)] || 'application/octet-stream', 'Cache-Control': 'public, max-age=3600' });
        res.end(await readFile(file));
    } catch { res.writeHead(404); res.end(); }
});
server.listen(0, '127.0.0.1'); await once(server, 'listening'); origin = `http://127.0.0.1:${server.address().port}`;
const portServer = createServer(); portServer.listen(0, '127.0.0.1'); await once(portServer, 'listening');
const debugPort = portServer.address().port; await new Promise((done) => portServer.close(done));
const profile = await mkdtemp(join(dirname(fixtureRoot), 'legado-chrome-profile-'));
const browser = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
    '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${debugPort}`, `--user-data-dir=${profile}`, 'about:blank',
], { windowsHide: true, stdio: 'ignore' });
const delay = (ms) => new Promise((done) => setTimeout(done, ms));
let socket, send;
try {
    let target;
    for (let i = 0; i < 100; i++) {
        try { const targets = await (await fetch(`http://127.0.0.1:${debugPort}/json`)).json(); target = targets.find((t) => t.type === 'page'); if (target) break; } catch {}
        await delay(100);
    }
    if (!target) throw new Error('Chrome did not expose a page.');
    socket = new WebSocket(target.webSocketDebuggerUrl); await once(socket, 'open');
    let nextId = 0; const pending = new Map(), errors = [], failed = [];
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.id) {
            const deferred = pending.get(message.id); pending.delete(message.id);
            message.error ? deferred?.reject(new Error(JSON.stringify(message.error))) : deferred?.resolve(message.result);
        }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text + ': ' + (message.params.exceptionDetails.exception?.description || ''));
        if (message.method === 'Network.loadingFailed') failed.push(message.params.errorText);
    });
    send = (method, params = {}) => new Promise((resolve, reject) => { const id = ++nextId; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params })); });
    await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
    if (process.env.LEGADO_CPU_PROFILE === '1') {
        await send('Profiler.enable');
        await send('Page.addScriptToEvaluateOnNewDocument', { source: `window.__legadoLongTasks=[];new PerformanceObserver(list=>window.__legadoLongTasks.push(...list.getEntries().map(e=>({start:e.startTime,duration:e.duration})))).observe({type:'longtask',buffered:true});` });
    }
    if (process.env.LEGADO_PROFILE_WARMUP === '1') {
        const page=(process.argv[3] || 'dashboard').split(',')[0];
        await send('Page.navigate',{url:`${origin}/fixture/${page}.html`});
        for(let i=0;i<100;i++) {
            await delay(100);
            const state=await send('Runtime.evaluate',{expression:'document.readyState',returnByValue:true});
            if(state.result.value==='complete')break;
        }
        await delay(1000);
    }
    const results = [];
    for (const [variant, root] of (comparisonRoot ? [['before', fixtureRoot], ['after', comparisonRoot]] : [['', fixtureRoot]])) {
    activeFixtureRoot = root;
    for (const page of (process.argv[3] || 'form,history,diagrams,dashboard').split(',')) for (const width of [1440, 390]) {
        const stem = `${variant ? variant + '-' : ''}${page}-${width}`;
        errors.length = 0; failed.length = 0;
        await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 500 });
        if (process.env.LEGADO_CPU_PROFILE === '1') await send('Profiler.start');
        await send('Page.navigate', { url: `${origin}/fixture/${page}.html` });
        for (let i = 0; i < 80; i++) {
            await delay(100);
            const state = await send('Runtime.evaluate', { expression: 'document.readyState', returnByValue: true });
            if (state.result.value === 'complete') break;
        }
        await delay(800);
        let cpu;
        if (process.env.LEGADO_CPU_PROFILE === '1') {
            const { profile: cpuProfile } = await send('Profiler.stop');
            await writeFile(join(output, `${stem}.cpuprofile`), JSON.stringify(cpuProfile));
            const urls = new Map(cpuProfile.nodes.map(node => [node.id, node.callFrame.url]));
            const samplesByUrl = {};
            cpuProfile.samples.forEach((node, index) => { const url=urls.get(node)||'(native/idle)';samplesByUrl[url]=(samplesByUrl[url]||0)+(cpuProfile.timeDeltas[index]||0)/1000; });
            cpu = { sampledMsByUrl: samplesByUrl, longTasks: (await send('Runtime.evaluate', { expression: 'window.__legadoLongTasks', returnByValue: true })).result.value };
        }
        const fontState = await send('Runtime.evaluate', {
            expression: 'Promise.race([document.fonts.ready.then(() => true), new Promise(resolve => setTimeout(() => resolve(false), 30000))])',
            awaitPromise: true, returnByValue: true,
        });
        if (fontState.result.value !== true) throw new Error('Fonts did not finish loading; discard this visual comparison.');
        await send('Runtime.evaluate', { expression: `document.querySelectorAll('svg').forEach(svg => svg.pauseAnimations?.()); const freeze = document.createElement('style'); freeze.textContent='*,*::before,*::after { animation: none !important; transition: none !important; }'; document.head.append(freeze); if(window.Chart?.getChart) document.querySelectorAll('canvas').forEach(canvas=>{const chart=Chart.getChart(canvas);if(chart){chart.stop();chart.update('none');}});`, returnByValue: true });
        const metrics = await send('Runtime.evaluate', { expression: `JSON.stringify({title:document.title,scrollWidth:document.documentElement.scrollWidth,viewport:innerWidth,dom:document.getElementsByTagName('*').length,tailwind:!!window.tailwind,charts:typeof Chart,logos:[...document.querySelectorAll('.logo-sequence img')].map(i=>({source:i.currentSrc,width:i.naturalWidth})),canvases:[...document.querySelectorAll('canvas')].map(c=>({id:c.id,width:c.width,height:c.height})),styles:[...document.querySelectorAll('body, header, main, form, button, input, select, textarea, h1, h2, .card, .diagrama-lavadora')].map(e=>{const s=getComputedStyle(e);return {tag:e.tagName,id:e.id,display:s.display,color:s.color,background:s.backgroundColor,font:s.fontFamily,size:s.fontSize,width:s.width,padding:s.padding,margin:s.margin,border:s.borderRadius};}),resources:performance.getEntriesByType('resource').map(r=>({name:r.name.replace(location.origin,''),transfer:r.transferSize,decoded:r.decodedBodySize,duration:r.duration}))})`, returnByValue: true });
        const data = JSON.parse(metrics.result.value);
        if (cpu) data.cpu = cpu;
        await writeFile(join(output, `${stem}.json`), JSON.stringify(data, null, 2));
        const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        await writeFile(join(output, `${stem}.png`), Buffer.from(screenshot.data, 'base64'));
        if (page === 'dashboard' || page === 'pasteurizer' || page === 'hydraulics') {
            const check = await send('Runtime.evaluate', {
                expression: `JSON.stringify((() => {
                    const snapshot = () => [...document.querySelectorAll('canvas')].map(canvas => {
                        const chart = window.Chart?.getChart(canvas);
                        return {id:canvas.id,labels:chart?.data.labels,datasets:chart?.data.datasets.map(d=>({label:d.label,data:d.data,hidden:d.hidden}))};
                    });
                    const initial = snapshot(), filters = [];
                    document.querySelectorAll('#fallasSeverityFilters button').forEach(button => { button.click(); filters.push({filter:button.dataset.filter,charts:snapshot()}); });
                    document.querySelector('#fallasSeverityFilters button')?.click();
                    const modals = [];
                    document.querySelectorAll('[onclick^="showPasteurizadoraDetail"], [onclick^="showCentralHidraulicaDetail"]').forEach(button => {
                        button.click();
                        const modal = document.getElementById('alertModal');
                        modals.push({open:modal.classList.contains('show'),title:modal.querySelector('#modalTitle').textContent,text:modal.querySelector('#modalBody').textContent.replace(/\\s+/g,' ').trim(),links:[...modal.querySelectorAll('a')].map(a=>a.href.replace(location.origin,''))});
                        modal.querySelector('[onclick="closeModal()"]')?.click();
                        modals.at(-1).closed = !modal.classList.contains('show');
                    });
                    return {initial,filters,planLinks:document.querySelectorAll('#planesActions a').length,modals};
                })())`, returnByValue: true,
            });
            data.dashboardChecks = JSON.parse(check.result.value);
            await writeFile(join(output, `${stem}.json`), JSON.stringify(data, null, 2));
        }
        if (page === 'dashboard' && process.env.LEGADO_VISIBLE_CHART_PROBE === '1') {
            const probe = await send('Runtime.evaluate', { awaitPromise: true, returnByValue: true,
                expression: `(async()=>{
                    const wait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
                    const canvas=document.getElementById('historicoChart'),select=document.getElementById('historicoScopeSelect');
                    const config=JSON.parse(document.getElementById('lavadoraPanelsData').textContent);
                    const deferred=!Chart.getChart(canvas), tableReady=document.getElementById('historicoTableBody').children.length>0;
                    canvas.scrollIntoView({block:'center'});await wait(400);
                    const chart=Chart.getChart(canvas);if(!chart)throw new Error('Visible historical chart did not initialize.');
                    const labelsMatch=JSON.stringify(chart.data.labels)===JSON.stringify(config.historico.labels);
                    const valuesMatch=JSON.stringify(chart.data.datasets[0].data)===JSON.stringify(config.historico.series.Todas.map(Number));
                    select.value='L-04';select.dispatchEvent(new Event('change',{bubbles:true}));await wait(100);
                    const filtered=Chart.getChart(canvas).data.datasets[0].label==='L-04';
                    window.scrollTo(0,0);await wait(100);select.value='Todas';select.dispatchEvent(new Event('change',{bubbles:true}));
                    window.dispatchEvent(new Event('beforeprint'));const printed=Chart.getChart(canvas).data.datasets[0].label==='Todas las lavadoras';
                    return JSON.stringify({deferred,tableReady,labelsMatch,valuesMatch,filtered,printed,passed:tableReady&&labelsMatch&&valuesMatch&&filtered&&printed});
                })()` });
            if (probe.exceptionDetails) throw new Error(probe.exceptionDetails.exception?.description || probe.exceptionDetails.text);
            data.visibleChartChecks=JSON.parse(probe.result.value);
            await writeFile(join(output, `${stem}.json`),JSON.stringify(data,null,2));
            if(!data.visibleChartChecks.passed)throw new Error('Visible chart probe failed.');
        }
        if (page === 'pasteur-history' && process.env.LEGADO_PASTEUR_HISTORY_PROBE === '1') {
            const probe = await send('Runtime.evaluate', {
                awaitPromise: true, returnByValue: true,
                expression: `(async()=>{
                    const trigger=document.querySelector('[onclick="openImageModalFromDataset(this)"]');
                    if(!trigger)throw new Error('Missing evidence control.');
                    trigger.click();const modal=document.getElementById('imageModal'),image=document.getElementById('modalImage');
                    await image.decode();
                    const open=!modal.classList.contains('hidden')&&document.body.style.overflow==='hidden';
                    const caption=document.getElementById('modalCaption').textContent;
                    const loaded=image.naturalWidth>0,source=image.src.replace(location.origin,'');
                    document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
                    const closed=modal.classList.contains('hidden')&&document.body.style.overflow!== 'hidden';
                    const links=[...document.querySelectorAll('a[href*="historial_"]')];
                    const linksValid=links.every(link=>Boolean(document.getElementById(new URL(link.href).hash.slice(1))));
                    const groups=[...document.querySelectorAll('h3')].filter(heading=>heading.textContent.includes(' | Módulo ')).length;
                    return JSON.stringify({open,closed,loaded,caption,source,links:links.length,linksValid,groups,passed:open&&closed&&loaded&&linksValid&&groups===3});
                })()`,
            });
            if (probe.exceptionDetails) throw new Error(probe.exceptionDetails.exception?.description || probe.exceptionDetails.text);
            data.pasteurHistoryChecks = JSON.parse(probe.result.value);
            await writeFile(join(output, `${stem}.json`), JSON.stringify(data, null, 2));
            if (!data.pasteurHistoryChecks.passed) throw new Error('Pasteurizer history probe failed; inspect its JSON artifact.');
        }
        if (['diagrams', 'diagram-visibility'].includes(page) && process.env.LEGADO_DIAGRAM_VISIBILITY_PROBE === '1') {
            const probe = await send('Runtime.evaluate', {
                awaitPromise: true, returnByValue: true,
                expression: `(async()=>{
                    const wait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
                    const diagram=document.querySelector('[data-diagrama-lavadora]');
                    const target=diagram?.querySelector('[data-monitor-kind="reductor"][data-monitor-severities*=","]');
                    if(!target||!diagram._syncMonitorAnimation)throw new Error('Missing diagram with mixed-severity monitor data.');
                    diagram.scrollIntoView({block:'start'});await wait(300);
                    await wait(100);
                    diagram._syncMonitorAnimation();const timer=diagram._monitorColorAlternationTimer;
                    diagram._syncMonitorAnimation();const oneLoop=timer===diagram._monitorColorAlternationTimer;
                    diagram.classList.add('is-paused');diagram._syncMonitorAnimation();
                    const snapshot=()=>({visible:diagram._monitorInViewport,timer:!!diagram._monitorColorAlternationTimer,manual:diagram.classList.contains('is-paused'),background:diagram.classList.contains('is-background-paused'),svgCount:diagram.querySelectorAll('svg').length,svgPaused:[...diagram.querySelectorAll('svg')].every(svg=>svg.animationsPaused()),severity:target.getAttribute('class')});
                    const visible=snapshot();const spacer=document.createElement('div');spacer.style.height='2500px';document.body.append(spacer);
                    window.scrollTo(0,document.documentElement.scrollHeight);await wait(300);const offscreen=snapshot();
                    await wait(3200);const idle=snapshot();window.scrollTo(0,0);await wait(300);const resumed=snapshot();
                    await wait(3200);const cycled=snapshot();spacer.remove();
                    return JSON.stringify({oneLoop,visible,offscreen,idle,resumed,cycled,passed:oneLoop&&visible.timer&&visible.svgCount>0&&!offscreen.visible&&!offscreen.timer&&offscreen.background&&offscreen.svgPaused&&offscreen.severity===idle.severity&&resumed.visible&&resumed.timer&&resumed.manual&&resumed.svgPaused&&cycled.severity!==resumed.severity});
                })()`,
            });
            if (probe.exceptionDetails) throw new Error(probe.exceptionDetails.exception?.description || probe.exceptionDetails.text);
            data.diagramChecks = JSON.parse(probe.result.value);
            await writeFile(join(output, `${stem}.json`), JSON.stringify(data, null, 2));
            if (!data.diagramChecks.passed) throw new Error('Diagram visibility probe failed; inspect its JSON artifact.');
        }
        results.push({ variant, page, width, dom: data.dom, scrollWidth: data.scrollWidth, tailwind: data.tailwind, charts: data.charts, errors: [...errors], failed: [...failed] });
    }
    }
    await writeFile(join(output, 'results.json'), JSON.stringify(results, null, 2));
    console.log(JSON.stringify({ output, results }));
} catch (error) {
    console.error(JSON.stringify({ output }));
    throw error;
} finally {
    if (send) { try { await send('Browser.close'); } catch {} }
    socket?.close(); browser.kill(); server.close();
}
