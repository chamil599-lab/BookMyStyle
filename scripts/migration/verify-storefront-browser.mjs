import { spawn } from 'node:child_process';
import { writeFile, mkdir } from 'node:fs/promises';
import path from 'node:path';

const [base, directory, executable, appointmentDate] = process.argv.slice(2);
if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base) || !/^auth-smoke-\d{8}_\d{6}_[a-f0-9]{6}$/.test(path.basename(directory))) throw new Error('Use the isolated smoke server and private artifact directory.');
const port = 23000 + Math.floor(Math.random() * 5000);
await mkdir(path.join(directory, 'browser-profile'), { recursive: true });
const browser = spawn(executable, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${port}`, `--user-data-dir=${path.join(directory, 'browser-profile')}`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const checks = {};
let socket;
let sequence = 0;
const pending = new Map();
const errors = [];
const expect = (ok, label) => { if (!ok) throw new Error(`Browser check failed: ${label}`); checks[label] = true; };
try {
    let target;
    for (let i = 0; i < 80; i++) {
        try { target = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json(); break; } catch { await sleep(250); }
    }
    if (!target) throw new Error('Browser debugging endpoint did not start.');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text + ': ' + (message.params.exceptionDetails.exception?.description || ''));
        if (message.id && pending.has(message.id)) {
            const { resolve, reject, timer } = pending.get(message.id); clearTimeout(timer); pending.delete(message.id);
            if (message.error) reject(new Error(JSON.stringify(message.error))); else resolve(message.result);
        }
    });
    const call = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`Timed out: ${method}`)); }, 15000);
        pending.set(id, { resolve, reject, timer }); socket.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async expression => {
        const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
        return result.result.value;
    };
    const until = async (expression, label) => {
        for (let i = 0; i < 80; i++) { if (await evaluate(expression)) return; await sleep(250); }
        throw new Error(`Timed out waiting for ${label}`);
    };
    const navigate = async route => {
        await call('Page.navigate', { url: base + route });
        await until(`location.pathname === ${JSON.stringify(route)} && document.querySelector('main') && typeof Alpine !== 'undefined'`, route);
        await sleep(300);
    };
    const viewport = (width, height) => call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
    const screenshot = async name => {
        const result = await call('Page.captureScreenshot', { format: 'png' });
        await writeFile(path.join(directory, `${name}.png`), Buffer.from(result.data, 'base64'));
    };
    await call('Page.enable'); await call('Runtime.enable');
    await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-color-scheme', value: 'light' }] });
    await viewport(1440, 1000); await navigate('/');
    expect(await evaluate("!!document.querySelector('link[href*=storefront]') && [...document.styleSheets].some(s => s.href?.includes('storefront'))"), 'public_css_loaded');
    await screenshot('desktop-home');
    await viewport(390, 844); await navigate('/');
    expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_home_no_overflow');
    await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Menu').click()");
    await sleep(100);
    expect(await evaluate("getComputedStyle(document.querySelector('#mobile-nav')).display !== 'none'"), 'mobile_menu_opens');
    await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Menu').click()");
    const darkBefore = await evaluate("document.documentElement.classList.contains('dark')");
    await evaluate("document.querySelector('button[aria-label=\"Toggle color theme\"]').click()");
    expect(await evaluate("document.documentElement.classList.contains('dark')") !== darkBefore, 'theme_toggle');
    await evaluate("document.querySelector('button[aria-label=\"Toggle color theme\"]').click()");
    await screenshot('mobile-home');
    await viewport(1440, 1000); await navigate('/salon/1'); await screenshot('desktop-salon');
    await evaluate(`const date = document.querySelector('input[type=date]'); date.value = ${JSON.stringify(appointmentDate)}; date.dispatchEvent(new Event('input', { bubbles: true })); date.dispatchEvent(new Event('change', { bubbles: true }));`);
    await sleep(500);
    await evaluate("const service = [...document.querySelectorAll('select')].find(el => el.getAttribute('wire:model.live') === 'service_id'); service.value = service.options[1].value; service.dispatchEvent(new Event('change', { bubbles: true }));");
    await until("[...document.querySelectorAll('select')].find(el => el.getAttribute('wire:model') === 'time').options.length > 1", 'Livewire available slots');
    checks.livewire_available_slots = true;
    await viewport(390, 844);
    expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_salon_no_overflow');
    await screenshot('mobile-salon');
    await evaluate("document.querySelector('#booking').scrollIntoView()");
    await sleep(300);
    await screenshot('mobile-booking');
    await evaluate(`for (const [field, value] of Object.entries({ name: 'Browser Guest', email: 'browser@example.test', phone: '0700000000' })) {
        const input = [...document.querySelectorAll('input')].find(el => el.getAttribute('wire:model') === field);
        input.value = value; input.dispatchEvent(new Event('input', { bubbles: true }));
    }
    const time = [...document.querySelectorAll('select')].find(el => el.getAttribute('wire:model') === 'time');
    time.value = time.options[1].value; time.dispatchEvent(new Event('change', { bubbles: true }));
    [...document.querySelectorAll('form')].find(el => el.getAttribute('wire:submit') === 'submit').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));`);
    await until("document.querySelector('#booking').textContent.includes('Your booking request has been sent')", 'guest booking submission');
    checks.livewire_guest_booking = true;
    expect(errors.length === 0, 'no_uncaught_javascript_errors');
    await writeFile(path.join(directory, 'browser-summary.json'), JSON.stringify({ checks, errors }, null, 2));
    console.log(JSON.stringify({ checks, errors }, null, 2));
    await call('Browser.close');
} finally {
    socket?.close(); browser.kill();
}
