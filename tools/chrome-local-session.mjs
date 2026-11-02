import { spawn } from 'node:child_process';
import { mkdtemp } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { once } from 'node:events';
import { createServer } from 'node:net';

export const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

export async function withChrome(run) {
    const output = await mkdtemp(join(tmpdir(), 'legado-browser-directory-check-'));
    const portServer = createServer(); portServer.listen(0, '127.0.0.1'); await once(portServer, 'listening');
    const port = portServer.address().port; await new Promise(resolve => portServer.close(resolve));
    const browser = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--no-first-run', '--no-default-browser-check',
        `--remote-debugging-port=${port}`, `--user-data-dir=${join(output, 'profile')}`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
    let socket, send;
    try {
        let target;
        for (let i = 0; i < 100 && !target; i++) {
            try { target = (await (await fetch(`http://127.0.0.1:${port}/json`)).json()).find(item => item.type === 'page'); } catch {}
            if (!target) await delay(100);
        }
        if (!target) throw new Error('Chrome did not open.');
        socket = new WebSocket(target.webSocketDebuggerUrl); await once(socket, 'open');
        let next = 0; const pending = new Map(), errors = [];
        socket.addEventListener('message', ({ data }) => {
            const message = JSON.parse(data);
            if (message.id) {
                const item = pending.get(message.id); pending.delete(message.id); clearTimeout(item?.timer);
                message.error ? item?.reject(new Error(JSON.stringify(message.error))) : item?.resolve(message.result);
            }
            if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails);
        });
        send = (method, params = {}) => new Promise((resolve, reject) => {
            const id = ++next, timer = setTimeout(() => { pending.delete(id); reject(new Error('CDP timeout: ' + method)); }, 15000);
            pending.set(id, { resolve, reject, timer }); socket.send(JSON.stringify({ id, method, params }));
        });
        const evaluate = async expression => {
            const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
            if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
            return result.result.value;
        };
        const waitFor = async expression => {
            for (let i = 0; i < 200; i++) { if (await evaluate(expression)) return; await delay(50); }
            throw new Error('Timed out: ' + expression);
        };
        await send('Page.enable'); await send('Runtime.enable');
        await run({ output, send, evaluate, waitFor, errors });
    } catch (error) { console.error(JSON.stringify({ output })); throw error; }
    finally {
        if (send) { try { await send('Browser.close'); } catch {} }
        socket?.close(); browser.kill();
    }
}
