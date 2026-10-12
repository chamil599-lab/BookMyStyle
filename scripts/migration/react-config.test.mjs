import test from 'node:test';
import assert from 'node:assert/strict';
import { createProxyOptions } from '../../client/vite.config.js';

test('development proxy verifies TLS for default and remote targets', () => {
    assert.equal(createProxyOptions().secure, true);
    const remote = createProxyOptions({ VITE_LARAVEL_URL: 'https://example.test' });
    assert.equal(remote.target, 'https://example.test');
    assert.equal(remote.secure, true);
    assert.equal(remote.changeOrigin, true);
});

test('self-signed certificate exception requires an explicit local opt-in', () => {
    assert.equal(createProxyOptions({ VITE_DEV_PROXY_INSECURE: 'true' }).secure, false);
    assert.equal(createProxyOptions({ VITE_LARAVEL_URL: 'https://127.0.0.1', VITE_DEV_PROXY_INSECURE: 'true' }).secure, false);
    assert.throws(() => createProxyOptions({ VITE_LARAVEL_URL: 'https://remote.example', VITE_DEV_PROXY_INSECURE: 'true' }), /local development/);
});

test('proxy rejects non-HTTP targets and embedded credentials', () => {
    for (const target of ['file:///etc/passwd', 'ftp://example.test', 'https://user:password@example.test']) {
        assert.throws(() => createProxyOptions({ VITE_LARAVEL_URL: target }), /HTTP/);
    }
});

test('remote proxies require HTTPS while local HTTP development remains available', () => {
    assert.throws(() => createProxyOptions({ VITE_LARAVEL_URL: 'http://remote.example' }), /require HTTPS/);
    assert.equal(createProxyOptions({ VITE_LARAVEL_URL: 'http://127.0.0.1:8000' }).target, 'http://127.0.0.1:8000');
});
