# Step thirteen: retained React dependency security upgrades

This checkpoint removes the remaining retained-client dependency findings while preserving the Blade/Filament routing arrangement. The retained React management screens still target retired management APIs; this upgrade does not reactivate those endpoints or deploy the client.

## Changes

| Package | New locked version |
|---|---|
| react | 18.2.0 |
| react-dom | 18.2.0 |
| react-router-dom | 7.18.4 |
| vite | 7.3.7 |
| @vitejs/plugin-react | 5.2.0 |
| tailwindcss | 4.3.3 |
| @tailwindcss/postcss | 4.3.3 |

React remains at 18.2.0. Router and Vite are upgraded to patched supported major versions rather than applying npm audit fix --force indiscriminately. Client engines declare Node ^20.19.0 or >=22.12.0, matching the [Vite 7 runtime requirements](https://v7.vite.dev/guide/migration). Local builds/tests use Node 24.19.0.

The [official Tailwind upgrade tool](https://tailwindcss.com/docs/upgrade-guide) migrated the custom palette/fonts from JavaScript configuration into CSS theme variables, replaced the PostCSS plugin, removed the old config and Autoprefixer, and updated utility classes in 58 templates. The existing class-based dark variant and border-color compatibility rules are retained. A parser comparison confirms executable structure is unchanged apart from literal contents; this check excludes string/template-literal contents, which were separately reviewed as class migrations. Browser inspection found a heading-font fallback difference; explicit family-name hints and a base-layer heading rule restore the original utility-class typography precedence.

Tailwind 4 requires modern browsers (Safari 16.4+, Chrome 111+, Firefox 128+); see its [browser compatibility requirements](https://tailwindcss.com/docs/upgrade-guide#browser-requirements). Native root assets already use Tailwind 4. Full cross-browser coverage remains a release gate.

The development proxy now verifies TLS by default for both API and storage traffic. An explicit VITE_DEV_PROXY_INSECURE=true exception is limited to localhost, 127.0.0.1, [::1] or bookmystyle.test. Remote targets cannot disable verification and must use HTTPS; local HTTP development remains available. Non-HTTP(S) targets and embedded credentials are rejected. The example environment documents the local exception; trusting the local CA is preferred. No real environment values were changed.

## Security audit and isolation

The client audit reported 11 findings before this checkpoint (6 high, 5 moderate) and zero afterward. Root npm and installed Composer audits also report zero advisories. Private evidence uses .local/migration-baseline/step-13-*.

The React browser harness serves only client/dist and a small synthetic in-memory API on a loopback server. Chrome requests to all other origins are stubbed before network dispatch. The harness never connects to Laravel, a database, real OAuth, mail, payments or production APIs. Its synthetic token is never valid on Laravel. Browser builds use relative /api paths and a fake OAuth client ID; regenerate deployment builds with the intended production configuration only after release approval.

Before/after artifacts are in .local/migration-baseline/react-smoke-before/ and react-smoke-after/. The baseline registration route begins with role selection rather than a form, so the fixture waits for that existing screen. The retained React dashboard already overflows to 465 px at a 390 px viewport; the migrated build preserves this baseline instead of claiming the legacy dashboard is fully responsive. Native Blade/Filament journeys are independently checked with the migration smoke runner.

The official migration tool emitted a Git ownership diagnostic during its final status check; source/dependency migration completed and was verified independently. No global Git configuration was changed. Package lifecycle scripts were suppressed during dependency installation.

## Remaining release gates

A clean registry audit is not an application or host security certification. External consumer review for the 56 retired API methods, actual OAuth/mail/payment delivery, queue/scheduler setup, upload execution enforcement, retention policy, MySQL concurrency/load, the two pending index migrations, production runtime/optimized-autoloader verification, cross-browser/visual parity and rollback rehearsal remain open. No imported records, schema or existing uploads are changed; no production deployment is performed.

## Final verification

| Check | Result |
|---|---|
| Retained React npm audit | Zero findings, down from 11 |
| Root npm and installed Composer audits | Zero findings; Composer also reports no abandoned packages |
| Builds | Retained React and root Laravel/Filament builds passed; existing large React chunk and empty root app chunk warnings remain |
| Proxy security regressions | Four Node tests passed: strict default/remote TLS, explicit local exception, scheme/credential rejection and HTTPS-only remote proxies |
| Retained React browser | 14 baseline checks; 16 final checks; zero uncaught JavaScript errors; measured layout metrics preserved and explicit display-font family verified |
| Retained visual inspection | Before/after mobile home screenshots inspected; heading fallback correction verified |
| PHP regression suite | 220 passed, 1,396 assertions; isolated SQLite and suppressed mail |
| Native HTTP/Chrome | 89 successful check flags including browser runner; 50 Chrome check flags; zero uncaught JavaScript errors |
| Imported integrity | Read-only checks unchanged: 35 tables, users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51; no FK orphans, missing media originals/conversions or nontransactional tables |
| Source checks | Browser harness syntax and authored-file Git whitespace checks passed |

Native browser artifacts: .local/migration-baseline/auth-smoke-20261012_022023_bb4f3c/. Retained browser artifacts: react-smoke-before/ and react-smoke-after/ under the same private migration directory. The final retained fixture includes a minimal non-networking Google script interface for the fake client ID; it does not verify OAuth. Chrome request-interception cancellations during navigation are tracked separately from JavaScript errors.

An initial PHP process run concurrently with the root build ended after one progress marker without a diagnostic report. The standalone rerun completed successfully; its log is step-13-php-tests-final.log. This is local verification evidence, not production performance evidence.

Checkpoint: migration-step-13-react-security-upgrades. Private full-history rollback bundle: .local/migration-baseline/step-13-react-security-upgrades.bundle. Restore the selected source checkpoint and install its matching package lockfiles, rebuild root/client assets as appropriate, and follow the rollback runbook. No imported-data restoration is needed for this source/dependency stage. Unrelated pre-existing image deletions and untracked files remain outside the checkpoint.
