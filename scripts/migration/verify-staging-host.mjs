import { fileURLToPath } from 'node:url';
import path from 'node:path';

export function stagingOrigin(value) {
    const url = new URL(value);
    if (url.protocol !== 'https:' || url.username || url.password || url.pathname !== '/' || url.search || url.hash) {
        throw new Error('Provide an HTTPS staging origin without credentials, paths or query parameters.');
    }
    return url.origin;
}

async function boundedText(response) {
    const reader = response.body?.getReader();
    if (!reader) return '';
    const decoder = new TextDecoder();
    let bytes = 0;
    let text = '';
    try {
        while (true) {
            const { value, done } = await reader.read();
            if (done) return text + decoder.decode();
            bytes += value.byteLength;
            if (bytes > 1024 * 1024) throw new Error('Page exceeds inspection limit.');
            text += decoder.decode(value, { stream: true });
        }
    } finally { await reader.cancel(); }
}

export async function inspectStaging(value, fetcher = fetch) {
    if (process.env.NODE_TLS_REJECT_UNAUTHORIZED === '0') throw new Error('TLS verification must remain enabled.');
    const origin = stagingOrigin(value);
    const checks = {};
    const request = async (pathname, inspect) => {
        try {
            const response = await fetcher(origin + pathname, {
                redirect: 'manual', signal: AbortSignal.timeout(15000),
                headers: { Accept: pathname.startsWith('/api/') ? 'application/json' : 'text/html', 'User-Agent': 'BookMyStyle-Staging-ReadOnly-Check' },
            });
            try { return await inspect(response); }
            finally { await response.body?.cancel().catch(() => {}); }
        } catch { return false; }
    };
    checks.native_login = await request('/login', async response => {
        const html = response.status === 200 ? await boundedText(response) : '';
        return html.includes('wire:snapshot') && html.includes('Sign in to BookMyStyle');
    });
    checks.private_api_identity = await request('/api/auth/me', response => {
        const cache = response.headers.get('cache-control') || '';
        return response.status === 401 && cache.includes('private') && cache.includes('no-store');
    });
    for (const pathname of ['/dashboard', '/filament']) {
        checks[pathname === '/dashboard' ? 'customer_guest_redirect' : 'panel_guest_redirect'] = await request(pathname, response => {
            if (![302, 303].includes(response.status)) return false;
            const destination = new URL(response.headers.get('location') || '', origin);
            return destination.origin === origin && /\/login$/.test(destination.pathname);
        });
    }
    for (const [id, pathname] of Object.entries({ environment_file_hidden: '/.env', git_config_hidden: '/.git/config', composer_manifest_hidden: '/composer.json', composer_lock_hidden: '/composer.lock', vendor_metadata_hidden: '/vendor/composer/installed.json' })) {
        checks[id] = await request(pathname, response => [403, 404].includes(response.status));
    }
    try {
        const response = await fetcher(origin + '/api/auth/me', { method: 'OPTIONS', redirect: 'manual', signal: AbortSignal.timeout(15000), headers: { Origin: 'https://migration-check.invalid', 'Access-Control-Request-Method': 'GET' } });
        checks.unreviewed_cors_origin_denied = [200, 204, 403, 405].includes(response.status) && !response.headers.has('access-control-allow-origin');
        await response.body?.cancel();
    } catch { checks.unreviewed_cors_origin_denied = false; }
    return { scope: 'Unauthenticated GET/OPTIONS only; redirects are not followed; strict TLS; no mail, OAuth login, payment submission or database migration', checks, automated_pass: Object.values(checks).every(Boolean), release_ready: false, manual_gates: ['actual_document_root', 'upload_execution_disabled', 'real_oauth_and_email_delivery', 'payment_approval', 'backup_restore_and_load', 'authenticated_role_journeys'] };
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    try {
        const report = await inspectStaging(process.argv[2]);
        console.log(JSON.stringify(report, null, 2));
        process.exitCode = report.automated_pass ? 0 : 1;
    } catch {
        console.error('Staging inspection requires verified TLS and an HTTPS origin without credentials, paths or query parameters.');
        process.exitCode = 2;
    }
}
