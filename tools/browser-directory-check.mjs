import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFile, writeFile, stat } from 'node:fs/promises';
import { resolve, join, sep, extname, basename } from 'node:path';
import { tmpdir } from 'node:os';
import { once } from 'node:events';
import { withChrome, delay } from './chrome-local-session.mjs';

const root = resolve(process.argv[2] || '');
assert.ok(basename(root).startsWith('legado-browser-directory-') && [tmpdir(), join(process.env.LOCALAPPDATA || tmpdir(), 'Temp')].some(path => root.toLowerCase().startsWith(resolve(path).toLowerCase() + sep)));
const meta = JSON.parse(await readFile(join(root, 'meta.json'), 'utf8'));
const publicRoot = resolve('public'), requests = [];
let origin;
const server = createServer(async (req, res) => {
    try {
        const url = new URL(req.url, 'http://localhost');
        if (req.method !== 'GET') { res.writeHead(405); res.end(); return; }
        if (url.pathname === meta.route) {
            const partial = req.headers['x-directory-fragment'] === '1';
            const search = url.searchParams.get('search');
            const name = search === 'Fixture 00001' ? 'first' : search === 'Fixture 00002' ? 'second' : search === 'Fixture 00003' ? 'third' : url.searchParams.get('page') === '2' ? 'page2' : 'initial';
            if (search === 'expired') { res.writeHead(302, { Location: '/login' }); res.end(); return; }
            if (partial && search === 'invalid') { res.writeHead(200, { 'Content-Type': 'text/html' }); res.end('<p>Not a directory</p>'); return; }
            const entry = { partial, search, name }; requests.push(entry);
            await delay(search === 'Fixture 00002' ? 400 : partial && name === 'initial' ? 250 : 20);
            const html = (await readFile(join(root, name + (partial ? '-fragment' : '') + '.html'), 'utf8')).replaceAll('http://localhost', origin);
            entry.bytes = Buffer.byteLength(html);
            if (res.destroyed) return;
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'private, no-store' }); res.end(html); return;
        }
        if (url.pathname === '/login') { res.writeHead(200, { 'Content-Type': 'text/html' }); res.end('<h1 id="login">Login required</h1>'); return; }
        if (url.pathname.startsWith('/notifications/') || url.pathname.startsWith('/asistente/')) { res.writeHead(200, { 'Content-Type': 'application/json' }); res.end('{"count":0,"items":[],"conversations":[]}'); return; }
        const file = resolve(publicRoot, '.' + url.pathname);
        if (!file.startsWith(publicRoot + sep) || !(await stat(file)).isFile()) throw new Error('Not public');
        const types = { '.js': 'text/javascript', '.css': 'text/css', '.webp': 'image/webp', '.png': 'image/png', '.svg': 'image/svg+xml' };
        res.writeHead(200, { 'Content-Type': types[extname(file)] || 'application/octet-stream' }); res.end(await readFile(file));
    } catch { res.writeHead(404); res.end(); }
});
server.listen(0, '127.0.0.1'); await once(server, 'listening'); origin = `http://127.0.0.1:${server.address().port}`;
try {
    await withChrome(async ({ output, send, evaluate, waitFor, errors }) => {
        const results = [];
        for (const width of [1440, 390]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 500 });
            await send('Page.navigate', { url: origin + meta.route });
            await waitFor('document.readyState==="complete"&&typeof navigateUserDirectory==="function"');
            await evaluate(`window.initialBody=document.body;document.querySelector('form[method="POST"] input[name="name"]').value='Unsaved creation';document.getElementById('search').focus();`);
            await evaluate(`const search=document.getElementById('search');search.value='Fixture 00001';search.dispatchEvent(new Event('change',{bubbles:true}));`);
            await waitFor('location.search.includes("00001")&&document.querySelector("#userDirectory").getAttribute("aria-busy")!=="true"');
            const filtered = await evaluate(`({sameDocument:initialBody===document.body,focus:document.activeElement.id,unsaved:document.querySelector('form[method="POST"] input[name="name"]').value,rows:document.querySelectorAll('#userDirectory a.group').length})`);
            assert.deepEqual(filtered, { sameDocument: true, focus: 'search', unsaved: 'Unsaved creation', rows: 1 });
            await evaluate('history.back()'); await waitFor('location.search===""&&document.querySelectorAll("#userDirectory a.group").length===25');
            await evaluate('history.forward()'); await waitFor('location.search.includes("00001")&&document.querySelectorAll("#userDirectory a.group").length===1');
            await evaluate('history.back()'); await waitFor('location.search===""');
            await evaluate('history.forward()'); await waitFor('location.search.includes("00001")'); await delay(400);
            assert.equal(await evaluate('document.querySelectorAll("#userDirectory a.group").length'), 1, 'Rapid back/forward must cancel the pending previous page even when URL returns to the committed search.');
            await evaluate(`navigateUserDirectory(${JSON.stringify(origin + meta.route)}+'?search=Fixture+00002');navigateUserDirectory(${JSON.stringify(origin + meta.route)}+'?search=Fixture+00003');`);
            await waitFor('location.search.includes("00003")'); await delay(500);
            assert.equal(await evaluate('document.querySelector("#userDirectory").textContent.includes("Fixture 00003")&&!document.querySelector("#userDirectory").textContent.includes("Fixture 00002")'), true);
            await evaluate(`void navigateUserDirectory(${JSON.stringify(origin + meta.route)}+'?search=Fixture+00002');window.creationInput=document.querySelector('form[method="POST"] input[name="name"]');creationInput.focus();`);
            await waitFor('location.search.includes("00002")');
            assert.equal(await evaluate('document.activeElement===creationInput'), true, 'A pending filter must not steal focus from the creation form.');
            await evaluate(`navigateUserDirectory(${JSON.stringify(origin + meta.route)})`);
            await waitFor('location.search===""&&document.querySelectorAll("#userDirectory a.group").length===25');
            await evaluate(`document.querySelector('#userDirectory [data-directory-pagination] a[href*="page=2"]').click()`);
            await waitFor('location.search.includes("page=2")');
            assert.equal(await evaluate('initialBody===document.body'), true);
            const actions = await evaluate(`(()=>{const link=document.querySelector('#userDirectory [data-directory-pagination] a');const event=new MouseEvent('click',{bubbles:true,cancelable:true,ctrlKey:true});let intercepted;const prevent=e=>{intercepted=e.defaultPrevented;e.preventDefault();};document.addEventListener('click',prevent,{once:true});link.dispatchEvent(event);return {ctrlIntercepted:intercepted};})()`);
            assert.equal(actions.ctrlIntercepted, false);
            await evaluate(`navigateUserDirectory(${JSON.stringify(origin + meta.route)}+'?search=invalid')`);
            await waitFor('typeof window.initialBody==="undefined"&&location.search.includes("invalid")&&document.readyState==="complete"');
            assert.equal(await evaluate('!!document.getElementById("userDirectory")'), true);
            await evaluate(`window.fallbackMarker=true;delete window.navigateUserDirectory;document.getElementById('search').value='Fixture 00001';document.getElementById('search').dispatchEvent(new Event('change',{bubbles:true}));`);
            await waitFor('typeof window.fallbackMarker==="undefined"&&location.search.includes("00001")&&document.readyState==="complete"');
            assert.equal(await evaluate('document.querySelectorAll("#userDirectory a.group").length'), 1);
            await evaluate(`navigateUserDirectory(${JSON.stringify(origin + meta.route)}+'?search=expired')`);
            await waitFor('location.pathname==="/login"&&!!document.getElementById("login")');
            results.push({ width, filtered, backForward: true, rapidBackForward: true, latestResponse: true, creationFocusPreserved: true, pagination: true, ctrlLinkUntouched: true, invalidResponseFullFallback: true, scriptUnavailableFullFallback: true, loginRedirectFullFallback: true });
        }
        assert.equal(errors.length, 0);
        await writeFile(join(output, 'results.json'), JSON.stringify({ results, requests, runtimeErrors: errors.length }, null, 2));
        console.log(JSON.stringify({ output, results, runtimeErrors: errors.length }));
    });
} finally { server.closeAllConnections(); server.close(); }
