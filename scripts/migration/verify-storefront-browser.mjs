import { spawn } from 'node:child_process';
import { writeFile, mkdir, readFile } from 'node:fs/promises';
import path from 'node:path';

const [base, directory, executable, appointmentDate, fixtureFile] = process.argv.slice(2);
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
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`Timed out: ${method}`)); }, method === 'Page.navigate' ? 45000 : 15000);
        pending.set(id, { resolve, reject, timer }); socket.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async expression => {
        const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
        return result.result.value;
    };
    const until = async (expression, label) => {
        for (let i = 0; i < 80; i++) { if (await evaluate(expression)) return; await sleep(250); }
        await writeFile(path.join(directory, 'browser-timeout.html'), await evaluate('document.documentElement.outerHTML'));
        throw new Error(`Timed out waiting for ${label}`);
    };
    const navigate = async route => {
        await call('Page.navigate', { url: base + route });
        await until(`location.pathname === ${JSON.stringify(route)} && document.querySelector('main, form') && typeof Alpine !== 'undefined'`, route);
        await sleep(300);
    };
    const viewport = async (width, height) => {
        await call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
        await sleep(500); // Allow Alpine's responsive sidebar state and CSS transitions to settle.
    };
    const screenshot = async (name, full = false) => {
        const metrics = full ? await call('Page.getLayoutMetrics') : null;
        const clip = metrics ? { x: 0, y: 0, width: metrics.cssContentSize.width, height: metrics.cssContentSize.height, scale: 1 } : undefined;
        const result = await call('Page.captureScreenshot', { format: 'png', ...(full ? { captureBeyondViewport: true, clip } : {}) });
        await writeFile(path.join(directory, `${name}.png`), Buffer.from(result.data, 'base64'));
    };
    await call('Page.enable'); await call('Runtime.enable');
    await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-color-scheme', value: 'light' }] });
    await viewport(1440, 1000); await navigate('/');
    expect(await evaluate("document.querySelector('.storefront-brand-mark').naturalWidth > 0 && Math.round(document.querySelector('.storefront-brand-mark').getBoundingClientRect().width) === 52"), 'navbar_brand_loaded_and_sized');
    expect(await evaluate("document.querySelector('nav[aria-label=\"Main navigation\"] a[aria-current=page]').pathname === '/'"), 'navbar_active_home_link');
    if (await evaluate("document.body.classList.contains('home-luxury')")) {
        await until("document.querySelector('video.luxury-portrait').readyState >= 2", 'luxury hero film');
        await evaluate("document.querySelector('video.luxury-portrait').play()");
        await until("document.querySelector('.luxury-film-toggle').textContent.includes('Pause film')", 'film control state');
        await evaluate("document.querySelector('.luxury-film-toggle').click()");
        await until("document.querySelector('video.luxury-portrait').paused", 'film pause control');
        checks.luxury_film_control = true;
    }
    expect(await evaluate("!!document.querySelector('link[href*=storefront]') && [...document.styleSheets].some(s => s.href?.includes('storefront'))"), 'public_css_loaded');
    await screenshot('desktop-home');
    await viewport(390, 844); await navigate('/');
    expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_home_no_overflow');
    await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Menu').click()");
    await sleep(100);
    expect(await evaluate("getComputedStyle(document.querySelector('#mobile-nav')).display !== 'none'"), 'mobile_menu_opens');
    await call('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' });
    await call('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape' });
    expect(await evaluate("getComputedStyle(document.querySelector('#mobile-nav')).display === 'none' && document.activeElement === document.querySelector('[aria-controls=mobile-nav]')"), 'mobile_menu_escape_restores_focus');
    const darkBefore = await evaluate("document.documentElement.classList.contains('dark')");
    await evaluate("document.querySelector('button[aria-label=\"Toggle color theme\"]').click()");
    expect(await evaluate("document.documentElement.classList.contains('dark')") !== darkBefore, 'theme_toggle');
    await evaluate("document.querySelector('button[aria-label=\"Toggle color theme\"]').click()");
    await screenshot('mobile-home');
    if (await evaluate("document.body.classList.contains('home-luxury')")) {
        await viewport(320, 780);
        expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'small_mobile_home_no_overflow');
        await viewport(390, 844);
        await evaluate("document.querySelector('#home-location').value = 'nonexistent-luxury-fixture'; setTimeout(() => document.querySelector('form[role=search]').requestSubmit(), 0)");
        await until("location.pathname === '/salons' && new URLSearchParams(location.search).get('q') === 'nonexistent-luxury-fixture' && document.body.textContent.includes('No salons found')", 'luxury salon discovery');
        checks.luxury_discovery_search = true;
    }
    for (const route of ['/salons', '/trending', '/popular', '/services', '/deals', '/about', '/privacy-policy', '/terms-and-condition']) {
        const label = route.slice(1);
        await viewport(1440, 1000); await navigate(route);
        expect(await evaluate("document.body.classList.contains('public-luxury')"), label + '_luxury_theme');
        await screenshot('desktop-' + label);
        await viewport(390, 844);
        await screenshot('mobile-' + label);
        await viewport(320, 780);
        expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), label + '_small_mobile_no_overflow');
        await evaluate("document.documentElement.classList.add('dark')");
        expect(await evaluate("getComputedStyle(document.body).backgroundColor === 'rgb(11, 11, 11)'"), label + '_dark_black_background');
        await screenshot('dark-' + label);
        await evaluate("document.documentElement.classList.remove('dark')");
    }
    await viewport(1440, 1000); await navigate('/salon/1'); await screenshot('desktop-salon');
    if (await evaluate("document.body.classList.contains('salon-detail')")) {
        await screenshot('desktop-salon-full', true);
        expect(await evaluate("['services', 'booking', 'gallery', 'contact', 'reviews'].every(id => document.getElementById(id))"), 'luxury_salon_sections');
        await evaluate("document.documentElement.classList.add('dark')");
        expect(await evaluate("getComputedStyle(document.body).backgroundColor === 'rgb(11, 11, 11)'"), 'luxury_salon_dark_black_background');
        await screenshot('desktop-salon-dark');
        await evaluate("document.documentElement.classList.remove('dark')");
        await viewport(320, 780);
        expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'small_mobile_salon_no_overflow');
        await viewport(1440, 1000);
    }
    await evaluate(`const date = document.querySelector('input[type=date]'); date.value = ${JSON.stringify(appointmentDate)}; date.dispatchEvent(new Event('input', { bubbles: true })); date.dispatchEvent(new Event('change', { bubbles: true }));`);
    await sleep(500);
    await evaluate("const service = [...document.querySelectorAll('select')].find(el => el.getAttribute('wire:model.live') === 'service_id'); service.value = service.options[1].value; service.dispatchEvent(new Event('change', { bubbles: true }));");
    await until("[...document.querySelectorAll('select')].find(el => el.getAttribute('wire:model') === 'time').options.length > 1", 'Livewire available slots');
    checks.livewire_available_slots = true;
    await viewport(390, 844);
    expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_salon_no_overflow');
    await screenshot('mobile-salon');
    await screenshot('mobile-salon-full', true);
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
    if (fixtureFile) {
        if (path.resolve(fixtureFile) !== path.resolve(directory, 'customer-fixture.json')) throw new Error('Customer credentials must come from the private synthetic fixture.');
        const fixture = JSON.parse(await readFile(fixtureFile, 'utf8'));
        await navigate('/login');
        await evaluate(`for (const [field, value] of Object.entries(${JSON.stringify({ 'form.email': fixture.email, 'form.password': fixture.password })})) {
            const input = [...document.querySelectorAll('input')].find(el => el.getAttribute('wire:model') === field);
            input.value = value; input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));`);
        await until("location.pathname === '/dashboard' && document.body.textContent.includes('Your next appointments')", 'customer native login');
        checks.customer_session_login = true;
        await viewport(1440, 1000); await screenshot('desktop-customer-dashboard');
        await viewport(390, 844);
        expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_customer_dashboard_no_overflow');
        await screenshot('mobile-customer-dashboard');
        await navigate('/dashboard/upcoming');
        expect(await evaluate(`!!document.querySelector('[data-booking-id="${fixture.bookingId}"]')`), 'customer_upcoming_booking');
        await evaluate(`document.querySelector('[data-booking-id="${fixture.bookingId}"] button[type=submit]').click()`);
        await until(`getComputedStyle(document.querySelector('[data-booking-id="${fixture.bookingId}"] form div')).display !== 'none'`, 'cancellation confirmation');
        expect(await evaluate(`getComputedStyle(document.querySelector('[data-booking-id="${fixture.bookingId}"] [x-cloak]') || document.querySelector('[data-booking-id="${fixture.bookingId}"] form div')).display !== 'none'`), 'customer_cancel_confirmation');
        await evaluate(`const buttons = document.querySelector('[data-booking-id="${fixture.bookingId}"]').querySelectorAll('button'); [...buttons].find(b => b.textContent.includes('Yes, cancel')).click()`);
        await until("document.body.textContent.includes('Booking cancelled.')", 'customer cancellation');
        checks.customer_cancel_booking = true;
        await navigate('/dashboard/history');
        expect(await evaluate(`!!document.querySelector('[data-booking-id="${fixture.bookingId}"]')`), 'cancelled_booking_in_history');
        expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'mobile_customer_history_no_overflow');
        await screenshot('mobile-customer-history');
        await navigate('/dashboard/favorites');
        await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.includes('from favorites')).click()");
        await until("document.body.textContent.includes('Salon removed from favorites.') && document.body.textContent.includes('No favorite salons yet.')", 'remove favorite');
        checks.customer_remove_favorite = true;
        await navigate('/dashboard/notifications');
        await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Mark all as read').click()");
        await until("document.body.textContent.includes('Notifications marked as read.')", 'read notifications');
        checks.customer_read_notifications = true;
        const notificationCount = await evaluate("document.querySelectorAll('main article').length");
        await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Delete notification').click()");
        await until(`document.body.textContent.includes('Notification deleted.') && document.querySelectorAll('main article').length === ${notificationCount - 1}`, 'delete notification');
        checks.customer_delete_notification = true;
        await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === 'Clear all notifications').click()");
        await until("document.body.textContent.includes('Notifications cleared.') && document.body.textContent.includes('No notifications yet.')", 'clear notifications');
        checks.customer_clear_notifications = true;
        for (const [role, route] of [['owner', '/filament/owner-dashboard'], ['admin', '/filament/dashboard']]) {
            await evaluate(`fetch('/logout', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _token: document.querySelector('meta[name=csrf-token]').content }) })`);
            await navigate('/login');
            await evaluate(`for (const [field, value] of Object.entries(${JSON.stringify({ 'form.email': fixture[role].email, 'form.password': fixture[role].password })})) {
                const input = [...document.querySelectorAll('input')].find(el => el.getAttribute('wire:model') === field);
                input.value = value; input.dispatchEvent(new Event('input', { bubbles: true }));
            }
            document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));`);
            await until(`location.pathname === ${JSON.stringify(route)} && document.querySelector('main')`, `${role} Filament session handoff`);
            await sleep(700);
            checks[`${role}_filament_session_handoff`] = true;
            await viewport(1440, 1000); await screenshot(`desktop-filament-${role}`);
            expect(await evaluate("document.querySelectorAll('.bms-stat').length === 4 && !!document.querySelector('.bms-welcome')"), role + '_dashboard_overview');
            await evaluate("document.documentElement.classList.add('dark')");
            expect(await evaluate("getComputedStyle(document.body).backgroundColor === 'rgb(11, 11, 11)'"), role + '_dashboard_black_dark_theme');
            await screenshot(`dark-filament-${role}`);
            await viewport(320, 780);
            expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), role + '_dashboard_320_no_overflow');
            expect(await evaluate("[...document.querySelectorAll('.bms-welcome, .bms-stat')].every(el => el.getBoundingClientRect().right <= innerWidth + 1)"), role + '_dashboard_mobile_cards_fit');
            await screenshot(`mobile-filament-${role}`);
            await evaluate("document.documentElement.classList.remove('dark')");
            await viewport(1440, 1000);
            expect(await evaluate("!!document.querySelector('[data-notification-bell]')"), role + '_notification_bell');
            if (role === 'owner') {
                await call('Emulation.setTimezoneOverride', { timezoneId: 'America/Los_Angeles' });
                await navigate('/filament/owner-dashboard');
                await until("!!document.querySelector('.ec-event')", 'owner calendar events');
                await evaluate("document.querySelector('.ec-event').click()");
                await until("[...document.querySelectorAll('[role=dialog]')].some(el => getComputedStyle(el).display !== 'none' && el.textContent.includes('Signature cut'))", 'calendar details popup');
                checks.owner_calendar_popup = true;
                await sleep(700);
                await screenshot('desktop-owner-calendar-popup');
                await call('Emulation.setTimezoneOverride', { timezoneId: 'Asia/Colombo' });
            }
            await navigate('/filament/notification-inbox');
            await until("document.querySelector('article[data-notification-id]') !== null", role + ' inbox');
            await evaluate("[...document.querySelectorAll('main button')].find(b => b.textContent.trim() === 'Mark all as read').click()");
            await until("![...document.querySelectorAll('main article button')].some(b => b.textContent.trim() === 'Mark as read')", role + ' read notifications');
            await until("document.querySelector('[data-notification-bell]').getAttribute('aria-label') === 'Notifications: 0 unread'", role + ' bell refresh');
            checks[role + '_notification_read_all'] = true;
            checks[role + '_notification_bell_refresh'] = true;
            const inboxCount = await evaluate("document.querySelectorAll('main article').length");
            await evaluate("[...document.querySelectorAll('main button')].find(b => b.textContent.trim() === 'Delete notification').click()");
            await until('document.querySelectorAll("main article").length === ' + (inboxCount - 1), role + ' delete notification');
            checks[role + '_notification_delete'] = true;
            await viewport(390, 844);
            expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), role + '_notification_mobile_no_overflow');
            await screenshot('mobile-filament-' + role + '-notifications');
            await navigate('/filament/bookings');
            expect(await evaluate("[...document.querySelectorAll('a')].some(a => a.href.startsWith('https://wa.me/947') && a.target === '_blank' && a.rel.includes('noopener'))"), role + '_whatsapp_safe_link');
            await navigate('/filament/services');
            expect(await evaluate("document.body.textContent.includes('Signature cut')"), `${role}_filament_services_visible`);
            await viewport(390, 844);
            expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), `${role}_filament_mobile_no_overflow`);
            await screenshot('mobile-filament-' + role + '-services');
            await navigate('/filament/analytics-dashboard');
            await until("typeof Chart !== 'undefined' && document.querySelectorAll('canvas').length >= 3 && [...document.querySelectorAll('canvas')].every(c => Chart.getChart(c))", role + ' analytics charts');
            checks[role + '_analytics_charts'] = true;
            expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), role + '_analytics_mobile_no_overflow');
            await screenshot('mobile-filament-' + role + '-analytics');
            await evaluate("[...document.querySelectorAll('button')].find(b => b.textContent.trim() === '7 Days').click()");
            await until("[...document.querySelectorAll('div')].some(el => el.getAttribute('wire:key')?.startsWith('daily-chart-7d')) && [...document.querySelectorAll('canvas')].every(c => Chart.getChart(c))", role + ' analytics filter');
            checks[role + '_analytics_filter_update'] = true;
            await viewport(1440, 1000); await screenshot('desktop-filament-' + role + '-analytics');
            if (role === 'owner') {
                await navigate('/subscription/required');
                expect(await evaluate("document.body.textContent.includes('Your current plan is Fixture') && document.body.textContent.includes('LKR 100.00')"), 'owner_native_plan_state');
                await screenshot('desktop-owner-subscription-plans');
                await viewport(390, 844);
                expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'owner_plans_mobile_no_overflow');
                await screenshot('mobile-owner-subscription-plans');
                await navigate('/subscription/checkout/' + fixture.packageId);
                await until("document.querySelector('#transaction-id').required", 'bank reference required');
                await evaluate("document.querySelector('#payment-method').value = 'one_pay'; document.querySelector('#payment-method').dispatchEvent(new Event('change', { bubbles: true }));");
                await until("!document.querySelector('#transaction-id').required", 'payment method changes');
                await evaluate("document.querySelector('#payment-method').value = 'bank_transfer'; document.querySelector('#payment-method').dispatchEvent(new Event('change', { bubbles: true }));");
                await until("document.querySelector('#transaction-id').required", 'bank reference restored');
                checks.owner_payment_method_fields = true;
                expect(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), 'owner_checkout_mobile_no_overflow');
                await screenshot('mobile-owner-subscription-checkout');
                await evaluate("document.querySelector('#transaction-id').value = 'BROWSER-RENEWAL'; document.querySelector('[data-subscription-request]').requestSubmit();");
                await until("location.pathname === '/filament/user-subscriptions' && document.body?.textContent.includes('Pending') && typeof Alpine !== 'undefined'", 'native renewal submitted');
                checks.owner_native_renewal_request = true;
                await navigate('/subscription/required');
                expect(await evaluate("document.body.textContent.includes('request is pending approval') && ![...document.querySelectorAll('a')].some(a => a.textContent.trim() === 'Request renewal')"), 'owner_pending_plan_state');
                await screenshot('mobile-owner-subscription-pending');
            }
        }
    }
    await writeFile(path.join(directory, 'browser-summary.json'), JSON.stringify({ checks, errors }, null, 2));
    expect(errors.length === 0, 'no_uncaught_javascript_errors');
    await writeFile(path.join(directory, 'browser-summary.json'), JSON.stringify({ checks, errors }, null, 2));
    console.log(JSON.stringify({ checks, errors }, null, 2));
    await call('Browser.close');
} finally {
    socket?.close(); browser.kill();
}
