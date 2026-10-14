import test from 'node:test';
import assert from 'node:assert/strict';
import { inspectStaging, stagingOrigin } from './verify-staging-host.mjs';

function fixture({ exposedSecret = false, externalRedirect = false, corsWildcard = false, networkFailure = false } = {}) {
    return async (url, options) => {
        assert.equal(options.redirect, 'manual');
        assert.ok(['GET', 'OPTIONS'].includes(options.method || 'GET'));
        assert.equal(options.headers.Authorization, undefined);
        if (new URL(url).pathname.startsWith('/api/') && options.method !== 'OPTIONS') assert.equal(options.headers.Accept, 'application/json');
        if (networkFailure) throw new Error('private-network-diagnostic');
        if (options.method === 'OPTIONS') return new Response(null, { status: 204, headers: corsWildcard ? { 'Access-Control-Allow-Origin': '*' } : {} });
        const pathname = new URL(url).pathname;
        if (pathname === '/login') return new Response('Sign in to BookMyStyle <div wire:snapshot="fixture"></div>');
        if (pathname === '/api/auth/me') return new Response('{}', { status: 401, headers: { 'Cache-Control': 'private, no-store' } });
        if (['/dashboard', '/filament'].includes(pathname)) return new Response(null, { status: 302, headers: { Location: externalRedirect ? 'https://unreviewed.invalid/login' : '/login' } });
        return new Response(exposedSecret ? 'private-password-value' : '', { status: exposedSecret ? 200 : 404 });
    };
}

test('rejects insecure or credential-bearing targets before requesting them', async () => {
    for (const value of ['http://staging.example.com', 'https://user:password@staging.example.com', 'https://staging.example.com/path', 'https://staging.example.com?secret=1']) {
        assert.throws(() => stagingOrigin(value));
        await assert.rejects(inspectStaging(value, () => assert.fail('Must not request invalid target')));
    }
});
test('passing public checks still leave manual release gates open', async () => {
    const report = await inspectStaging('https://staging.example.com', fixture());
    assert.equal(report.automated_pass, true);
    assert.equal(report.release_ready, false);
    assert.equal(Object.keys(report.checks).length, 10);
});
test('detects exposed project files without printing their contents', async () => {
    const report = await inspectStaging('https://staging.example.com', fixture({ exposedSecret: true }));
    assert.equal(report.checks.environment_file_hidden, false);
    assert.equal(report.automated_pass, false);
    assert.ok(!JSON.stringify(report).includes('private-password-value'));
});
test('rejects cross-origin login redirects and wildcard CORS', async () => {
    const report = await inspectStaging('https://staging.example.com', fixture({ externalRedirect: true, corsWildcard: true }));
    assert.equal(report.checks.panel_guest_redirect, false);
    assert.equal(report.checks.customer_guest_redirect, false);
    assert.equal(report.checks.unreviewed_cors_origin_denied, false);
});
test('network failures fail checks without disclosing exception details', async () => {
    const report = await inspectStaging('https://staging.example.com', fixture({ networkFailure: true }));
    assert.equal(report.automated_pass, false);
    assert.ok(Object.values(report.checks).every(value => value === false));
    assert.ok(!JSON.stringify(report).includes('private-network-diagnostic'));
});

test('refuses a process environment that disables TLS verification', async () => {
    const previous = process.env.NODE_TLS_REJECT_UNAUTHORIZED;
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
    try {
        await assert.rejects(inspectStaging('https://staging.example.com', () => assert.fail('Must not request with TLS disabled')), /TLS verification/);
    } finally {
        if (previous === undefined) delete process.env.NODE_TLS_REJECT_UNAUTHORIZED;
        else process.env.NODE_TLS_REJECT_UNAUTHORIZED = previous;
    }
});
