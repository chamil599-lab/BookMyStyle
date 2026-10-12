import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Serve only the retained build and synthetic API responses. No database or external requests.
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const phase = process.argv[2];
if (!['before', 'after'].includes(phase)) throw new Error('Use before or after.');
const directory = path.join(root, '.local/migration-baseline/react-smoke-' + phase);
const dist = path.join(root, 'client/dist');
await mkdir(directory, { recursive: true });
const user = { id: 900, name: 'Migration Customer', email: 'migration@example.test', role: 'customer', email_verified_at: '2026-10-01T00:00:00Z' };
const salon = { id: 1, name: 'Migration Salon', address: 'Synthetic address', location: 'Synthetic town', services: [], rating: 4, review_count: 1 };
const checks = {}, errors = [], metrics = {}, pending = new Map();
let socket, sequence = 0, browser, interceptionCancellations = 0;
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const expect = (value, label) => { if (!value) throw new Error('React browser check failed: ' + label); checks[label] = true; };
const server = createServer(async (request, response) => {
    try {
        const url = new URL(request.url, 'http://127.0.0.1');
        if (url.pathname.startsWith('/api/')) {
            let data = [];
            if (url.pathname === '/api/salons') data = [salon];
            if (url.pathname === '/api/departments') data = [{ id: 1, name: 'Hair' }];
            if (url.pathname === '/api/stats') data = { totalSalons: 1, approvedBookings: 0, averageRating: 4 };
            if (url.pathname === '/api/customer/stats') data = { data: { total_bookings: 0, upcoming_bookings: 0, completed_bookings: 0, cancelled_bookings: 0, favorite_salons: 0 } };
            if (url.pathname === '/api/customer/profile') data = { data: user };
            if (url.pathname === '/api/auth/me') data = { user };
            if (url.pathname === '/api/notifications') data = { notifications: [], unread_count: 0 };
            if (url.pathname === '/api/auth/login') {
                let body = '';
                for await (const chunk of request) { body += chunk; if (body.length > 4096) throw new Error('Oversized fixture request'); }
                const input = JSON.parse(body);
                if (input.email !== user.email || input.password !== 'Migration-Test-Only-123!') {
                    response.writeHead(422, { 'Content-Type': 'application/json' });
                    response.end(JSON.stringify({ message: 'Synthetic invalid credentials.' })); return;
                }
                data = { user, token: 'synthetic-token-never-valid-on-laravel' };
            }
            response.writeHead(200, { 'Content-Type': url.pathname.endsWith('/stream') ? 'text/event-stream' : 'application/json', 'Cache-Control': 'no-store' });
            response.end(url.pathname.endsWith('/stream') ? ': fixture\n\n' : JSON.stringify(data)); return;
        }
        const target = path.resolve(dist, '.' + decodeURIComponent(url.pathname));
        if (target !== dist && !target.startsWith(dist + path.sep)) { response.writeHead(403); response.end(); return; }
        const extensions = { '.js': 'application/javascript', '.css': 'text/css', '.png': 'image/png', '.svg': 'image/svg+xml', '.woff2': 'font/woff2' };
        let contents, type = extensions[path.extname(target)];
        if (type) contents = await readFile(target);
        else { contents = await readFile(path.join(dist, 'index.html')); type = 'text/html'; }
        response.writeHead(200, { 'Content-Type': type, 'Cache-Control': 'no-store' }); response.end(contents);
    } catch { response.writeHead(404); response.end(); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const base = 'http://127.0.0.1:' + server.address().port;
const port = 25000 + Math.floor(Math.random() * 4000);
try {
    browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=' + port, '--user-data-dir=' + path.join(directory, 'browser-profile')], { windowsHide: true, stdio: 'ignore' });
    let target;
    for (let i = 0; i < 80; i++) { try { target = await (await fetch('http://127.0.0.1:' + port + '/json/new?about:blank', { method: 'PUT' })).json(); break; } catch { await sleep(250); } }
    if (!target) throw new Error('Chrome did not start');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
    const call = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error('Timeout: ' + method)); }, 20000);
        pending.set(id, { resolve, reject, timer }); socket.send(JSON.stringify({ id, method, params }));
    });
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
        if (message.method === 'Fetch.requestPaused') {
            const request = message.params;
            const requestUrl = new URL(request.request.url);
            const local = requestUrl.origin === base;
            const stub = requestUrl.hostname === 'accounts.google.com' && requestUrl.pathname === '/gsi/client'
                ? 'window.google={accounts:{id:{initialize(){},renderButton(){},prompt(){},cancel(){},disableAutoSelect(){}}}};' : '';
            // External fonts/images/OAuth scripts are stubbed before any network request occurs.
            call(local ? 'Fetch.continueRequest' : 'Fetch.fulfillRequest', local ? { requestId: request.requestId } : { requestId: request.requestId, responseCode: 200, responseHeaders: [{ name: 'Content-Type', value: request.resourceType === 'Script' ? 'application/javascript' : 'text/plain' }], body: Buffer.from(stub).toString('base64') }).catch(error => {
                if (error.message.includes('Invalid InterceptionId')) interceptionCancellations++;
                else errors.push(error.message);
            });
        }
        if (message.id && pending.has(message.id)) { const entry = pending.get(message.id); clearTimeout(entry.timer); pending.delete(message.id); if (message.error) entry.reject(new Error(JSON.stringify(message.error))); else entry.resolve(message.result); }
    });
    const evaluate = async expression => { const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text); return result.result.value; };
    const until = async expression => { for (let i = 0; i < 80; i++) { if (await evaluate(expression)) return; await sleep(250); } await writeFile(path.join(directory, 'timeout.html'), await evaluate('document.documentElement.outerHTML')); throw new Error('Timed out: ' + expression); };
    const viewport = async width => { await call('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: width < 600 }); await sleep(350); };
    const screenshot = async name => { const result = await call('Page.captureScreenshot', { format: 'png' }); await writeFile(path.join(directory, name + '.png'), Buffer.from(result.data, 'base64')); };
    const navigate = async route => { await call('Page.navigate', { url: base + route }); await until('document.querySelector("#root").children.length > 0 && !!document.querySelector("main, form, h1")'); await sleep(500); };
    await call('Page.enable'); await call('Runtime.enable'); await call('Fetch.enable', { patterns: [{ urlPattern: '*' }] });
    await viewport(1440); await navigate('/');
    await until('document.body.textContent.includes("Migration Salon")'); checks.fixture_salon_rendered = true;
    if (phase === 'after') expect(await evaluate('getComputedStyle(document.querySelector("h1")).fontFamily === "Unbounded"'), 'display_font_family_preserved');
    await screenshot('desktop-home');
    await viewport(390); await screenshot('mobile-home');
    metrics.mobile_home_width = await evaluate('document.documentElement.scrollWidth');
    expect(metrics.mobile_home_width <= 391, 'mobile_home_no_overflow');
    await viewport(1440); await navigate('/login');
    expect(await evaluate('!!document.querySelector("input[type=password]")'), 'login_form');
    metrics.desktop_login_hidden_panel = await evaluate('getComputedStyle([...document.querySelectorAll("div")].find(el => el.classList.contains("lg:flex"))).display');
    expect(metrics.desktop_login_hidden_panel === 'flex', 'desktop_login_responsive_panel'); await screenshot('desktop-login');
    await viewport(390); await screenshot('mobile-login');
    expect(await evaluate('document.documentElement.scrollWidth <= 391'), 'mobile_login_no_overflow');
    await evaluate('[...document.querySelectorAll("a")].find(a => a.getAttribute("href") === "/register").click()');
    await until('location.pathname === "/register" && !!document.querySelector("h1, h2")'); checks.client_side_register_navigation = true;
    await navigate('/forgot-password'); expect(await evaluate('!!document.querySelector("input[type=email]")'), 'forgot_password_route');
    await navigate('/login');
    await evaluate('for (const [name, value] of Object.entries({email:"migration@example.test",password:"wrong-password"})) { const input=document.querySelector("input[name="+name+"]"); Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,"value").set.call(input,value); input.dispatchEvent(new Event("input",{bubbles:true})); } document.querySelector("form").requestSubmit()');
    await until('document.body.textContent.includes("Synthetic invalid credentials.")'); checks.invalid_login_message = true;
    await evaluate('Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,"value").set.call(document.querySelector("input[name=password]"),"Migration-Test-Only-123!"); document.querySelector("input[name=password]").dispatchEvent(new Event("input",{bubbles:true})); document.querySelector("form").requestSubmit()');
    await until('location.pathname === "/dashboard" && document.body.textContent.includes("Total Bookings")'); checks.login_router_navigation = true;
    metrics.mobile_customer_dashboard_width = await evaluate('document.documentElement.scrollWidth');
    metrics.dashboard_surface = await evaluate('getComputedStyle(document.querySelector(".ds-shell")).backgroundColor');
    await screenshot('mobile-customer-dashboard');
    await viewport(1440); await screenshot('desktop-customer-dashboard');
    for (const route of ['/dashboard/upcoming', '/dashboard/history', '/dashboard/favorites', '/dashboard/profile']) { await navigate(route); expect(await evaluate('location.pathname === ' + JSON.stringify(route) + ' && !document.body.textContent.includes("Unexpected Application Error")'), 'customer_route:' + route); }
    expect(errors.length === 0, 'no_uncaught_javascript_errors');
    if (phase === 'after') { const previous = JSON.parse(await readFile(path.join(root, '.local/migration-baseline/react-smoke-before/summary.json'), 'utf8')); expect(JSON.stringify(metrics) === JSON.stringify(previous.metrics), 'baseline_layout_metrics_preserved'); }
    await writeFile(path.join(directory, 'summary.json'), JSON.stringify({ scope: 'Retained React build; synthetic local API only; external requests stubbed', checks, metrics, errors, interceptionCancellations }, null, 2));
    console.log(JSON.stringify({ phase, checks: Object.keys(checks).length, errors: errors.length }));
} catch (error) {
    await writeFile(path.join(directory, 'failure.json'), JSON.stringify({ checks, metrics, errors, error: error.message }, null, 2));
    throw error;
} finally {
    for (const entry of pending.values()) { clearTimeout(entry.timer); entry.reject(new Error('Browser shutting down')); }
    socket?.close(); browser?.kill(); server.closeAllConnections(); server.close();
}
