import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdtempSync, readdirSync, rmdirSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';

const directory = mkdtempSync(path.resolve('.local/browser-boundary-'));
try {
    test('PHP runner rejects an artifact directory outside the private migration directory without writing', () => {
        const result = spawnSync('php', ['scripts/migration/verify-imported-browser.php'], {
            env: { ...process.env, BMS_IMPORTED_BROWSER_DIRECTORY: directory }, encoding: 'utf8', timeout: 30000,
        });
        assert.equal(result.status, 1);
        assert.match(result.stderr, /^Imported-browser verification failed; inspect private artifacts\./);
        assert.deepEqual(readdirSync(directory), []);
    });
    test('browser runner rejects a non-loopback origin before launching Chrome', () => {
        const result = spawnSync(process.execPath, ['scripts/migration/verify-imported-browser.mjs',
            'https://example.invalid', directory, 'must-not-launch'], { encoding: 'utf8', timeout: 30000 });
        assert.equal(result.status, 1);
        assert.match(result.stderr, /Use the private isolated browser fixture/);
        assert.deepEqual(readdirSync(directory), []);
    });
    test('browser runner rejects an outside artifact directory before loading credentials', () => {
        const result = spawnSync(process.execPath, ['scripts/migration/verify-imported-browser.mjs',
            'http://127.0.0.1:19000', directory, 'must-not-launch'], { encoding: 'utf8', timeout: 30000 });
        assert.equal(result.status, 1);
        assert.match(result.stderr, /Use the private isolated browser fixture/);
        assert.deepEqual(readdirSync(directory), []);
    });
} finally {
    process.on('exit', () => { if (readdirSync(directory).length === 0) rmdirSync(directory); });
}
