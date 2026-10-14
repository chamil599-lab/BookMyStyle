import { spawn } from 'node:child_process';
import { mkdir, readFile, realpath, writeFile } from 'node:fs/promises';
import path from 'node:path';

const [base, directory, executable] = process.argv.slice(2);
const privateRoot = await realpath('.local/migration-baseline');
if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base)
    || !/^imported-browser-\d{8}_\d{6}_[a-f0-9]{6}$/.test(path.basename(directory))
    || await realpath(path.dirname(directory)) !== privateRoot) throw new Error('Use the private isolated browser fixture.');
const fixture = JSON.parse(await readFile(path.join(directory, 'fixture.json'), 'utf8'));
const port = 23000 + Math.floor(Math.random() * 5000);
await mkdir(path.join(directory, 'browser-profile'), { recursive: true });
const browser = spawn(executable, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--disable-background-networking', '--disable-component-update', '--disable-sync', '--no-proxy-server',
    '--remote-debugging-address=127.0.0.1', `--remote-debugging-port=${port}`,
    `--user-data-dir=${path.join(directory, 'browser-profile')}`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const checks = {};
const errors = [];
let socket;
let sequence = 0;
let screenshots = 0;
let blockedRequests = 0;
let lastDocument;
const pending = new Map();
const expect = (ok, label) => { if (!ok) throw new Error(`Browser check failed: ${label}`); checks[label] = true; };
const save = async () => writeFile(path.join(directory, 'browser-summary.json'), JSON.stringify({ checks,
    screenshots, uncaught_javascript_errors: errors.length, blocked_external_requests: blockedRequests }, null, 2));
try {
    let target;
    for (let i = 0; i < 80; i++) {
        try { target = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json(); break; }
        catch { await sleep(250); }
    }
    if (!target) throw new Error('Browser debugging endpoint did not start.');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
    const call = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`Timed out: ${method}`)); }, 30000);
        pending.set(id, { resolve, reject, timer }); socket.send(JSON.stringify({ id, method, params }));
    });
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails);
        if (message.method === 'Network.responseReceived' && message.params.type === 'Document') lastDocument = message.params.response;
        if (message.method === 'Fetch.requestPaused') {
            const { requestId, request } = message.params;
            const allowed = request.url.startsWith(base + '/') || /^(data:|blob:)/.test(request.url);
            if (!allowed) blockedRequests++;
            call(allowed ? 'Fetch.continueRequest' : 'Fetch.failRequest', allowed ? { requestId } : { requestId, errorReason: 'BlockedByClient' })
                .catch(() => errors.push({ category: 'request_interception_failure' }));
        }
        if (message.id && pending.has(message.id)) {
            const { resolve, reject, timer } = pending.get(message.id); clearTimeout(timer); pending.delete(message.id);
            if (message.error) reject(new Error(`Browser protocol failure: ${message.error.code}`)); else resolve(message.result);
        }
    });
    const evaluate = async expression => {
        const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error('Browser evaluation failed; inspect private artifacts.');
        return result.result.value;
    };
    const until = async (expression, label) => {
        for (let i = 0; i < 100; i++) { if (await evaluate(expression)) return; await sleep(250); }
        await writeFile(path.join(directory, 'browser-timeout.html'), await evaluate('document.documentElement.outerHTML'));
        throw new Error(`Timed out waiting for ${label}`);
    };
    const navigate = async (route, destination = route) => {
        lastDocument = null;
        await call('Page.navigate', { url: base + route });
        await until(`location.pathname === ${JSON.stringify(destination)} && document.readyState === 'complete' && typeof Livewire !== 'undefined'`, 'native page');
        await sleep(250);
        expect(lastDocument?.status === 200, `document-${Object.keys(checks).length}`);
    };
    const viewport = async width => {
        await call('Emulation.setDeviceMetricsOverride', { width, height: width < 600 ? 844 : 1000, deviceScaleFactor: 1, mobile: width < 600 });
        await sleep(500);
    };
    const screenshot = async label => {
        const result = await call('Page.captureScreenshot', { format: 'png' });
        await writeFile(path.join(directory, `${label}.png`), Buffer.from(result.data, 'base64')); screenshots++;
    };
    const fillLogin = async (email, password) => evaluate(`for (const [field, value] of Object.entries(${JSON.stringify({ 'form.email': email, 'form.password': password })})) {
        const input = [...document.querySelectorAll('input')].find(el => el.getAttribute('wire:model') === field);
        input.value = value; input.dispatchEvent(new Event('input', { bubbles: true }));
    }
    document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));`);
    await call('Page.enable'); await call('Runtime.enable'); await call('Network.enable');
    await call('Fetch.enable', { patterns: [{ urlPattern: '*', requestStage: 'Request' }] });
    await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-color-scheme', value: 'light' }] });
    await viewport(1440); await navigate('/');
    await until("[...document.querySelectorAll('img.hero-media')].every(image => image.complete && image.naturalWidth > 0)", 'hero image loading or fallback');
    expect(await evaluate("[...document.querySelectorAll('img.hero-media')].every(image => image.naturalWidth > 0)"), 'hero_images_render_without_broken_icons');
    await screenshot('desktop-home');
    expect(await evaluate("[...document.styleSheets].some(s => s.href?.includes('storefront'))"), 'public_css_loaded');
    await viewport(390);
    expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_home_no_overflow');
    await screenshot('mobile-home');
    for (const [index, route] of fixture.salon_paths.entries()) {
        await viewport(1440); await navigate(route); await screenshot(`salon-${index}`);
        checks[`salon-${index}-rendered`] = true;
    }
    await navigate('/profile', '/login'); checks.guest_profile_denied = true;
    await call('Network.clearBrowserCookies');
    for (const [index, account] of fixture.accounts.entries()) {
        await call('Network.clearBrowserCookies'); await viewport(1440); await navigate('/login');
        if (index === 0) {
            await fillLogin(account.email, 'intentionally-wrong-fixture-password');
            await until("location.pathname === '/login' && document.body.textContent.includes('These credentials do not match our records.')", 'wrong password rejection');
            checks.wrong_password_rejected = true;
        }
        await fillLogin(account.email, account.password);
        await until(`location.pathname === ${JSON.stringify(account.dashboard)} && document.readyState === 'complete' && typeof Livewire !== 'undefined'`, account.label + ' login');
        expect(lastDocument?.status === 200, account.label + '-form-login');
        await sleep(350); await screenshot(account.label + '-desktop-dashboard');
        await viewport(390);
        expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), account.label + '-mobile-dashboard-no-overflow');
        await screenshot(account.label + '-mobile-dashboard');
        await viewport(1440);
        for (const [page, route] of account.pages.entries()) {
            await navigate(route); await screenshot(account.label + '-page-' + page);
            checks[account.label + '-page-' + page] = true;
            if (route === '/profile') {
                expect(await evaluate(`document.querySelector('input[type=email]').value === ${JSON.stringify(account.email)}`), account.label + '-session-identity');
                const headers = Object.fromEntries(Object.entries(lastDocument.headers).map(([key, value]) => [key.toLowerCase(), value]));
                expect(headers['cache-control']?.includes('no-store'), account.label + '-profile-private');
            }
        }
        await navigate('/profile');
        if (index === 0) {
            const cookies = await call('Network.getCookies', { urls: [base] });
            expect(cookies.cookies.some(cookie => cookie.name === 'bms_imported_browser' && cookie.httpOnly && cookie.sameSite === 'Lax'), 'session_cookie_http_only_lax');
            expect(await evaluate("fetch('/logout', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body: '_token=invalid-fixture-token' }).then(response => response.status === 419)"), 'logout_csrf_enforced');
            await navigate('/profile');
        }
        expect(await evaluate("fetch('/logout', { method: 'POST', redirect: 'manual', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _token: document.querySelector('meta[name=csrf-token]').content }) }).then(response => response.type === 'opaqueredirect')"), account.label + '-logout');
        await navigate('/profile', '/login'); checks[account.label + '-logout-protects-profile'] = true;
        await save();
    }
    expect(errors.length === 0, 'no_uncaught_javascript_errors');
    await save();
    console.log(JSON.stringify({ status: 'passed', checks: Object.keys(checks).length, screenshots, uncaught_javascript_errors: errors.length }));
} catch (error) {
    await writeFile(path.join(directory, 'browser-errors-private.json'), JSON.stringify(errors, null, 2));
    await save();
    await writeFile(path.join(directory, 'browser-failure.txt'), error.message);
    console.error('Imported browser checks failed; inspect private artifacts.');
    process.exitCode = 1;
} finally {
    for (const item of pending.values()) clearTimeout(item.timer);
    socket?.close(); browser.kill();
}
