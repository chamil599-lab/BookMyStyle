# Step eleven: upload hardening and dependency audit

This checkpoint tightens native administrative image uploads and audits both JavaScript dependency trees and the locked PHP dependency tree. It does not establish production readiness. The dependency upgrade phase remains open.

## Changes

- Image-cycle API authorization reloads the account before checking verified administrator access, avoiding a cached role granting access after revocation.
- API image uploads must be decoded raster images (JPEG, PNG, GIF or WebP), no larger than 10 MB and no wider or taller than 6,000 pixels. Filament image-cycle, team-member, deal and service-page uploads use the same MIME, size and dimension bounds. SVG is excluded.
- Deal and service-page uploads explicitly use the public disk and separate directories; existing records/files are not relocated.
- External image URLs accept only HTTP(S), with a 2,048-character limit. API descriptions are bounded to 10,000 characters.
- Apache's public rewrite configuration rejects script extensions under /storage. The deployment host must independently disable script execution for the upload directory, including aliases and other web-server configurations. Local PHP/Herd tests do not verify Apache enforcement.
- Compatible React dependency updates were applied without lifecycle scripts. No forced major updates were applied.

## Audit evidence and release gates

Reports are private under .local/migration-baseline/step-11-*.json and build logs under the matching prefix. Audit counts describe advisories returned by the registries, not proven exploitation of this application; one advisory can affect multiple packages.

| Dependency tree | Result |
|---|---|
| Root npm | Zero reported vulnerabilities |
| Retained React client, before | 28 findings: 21 high, 6 moderate, 1 low |
| Retained React client, after compatible fixes | 11 findings: 6 high, 5 moderate; major version changes remain necessary according to npm |
| Locked Composer dependencies | 70 package/advisory entries across 23 packages; not remediated in this checkpoint |

Affected PHP packages include Laravel, Livewire, Filament, Media Library, Guzzle, CommonMark and Symfony components. Some are development dependencies. The full private report retains affected version ranges and advisory links. Composer's audit returned a repository ownership diagnostic while resolving version information but also produced valid advisory JSON; no global Git configuration was changed.

Next: resolve PHP upgrades within supported version constraints (including the pinned calendar plugin), inspect the resulting lockfile, publish the required framework/panel assets deliberately, and repeat service, authorization, media and real-browser checks. Avoid automatic Composer scripts until their side effects have been reviewed. If patched versions require major upgrades, migrate the affected integration and verify behavior before release. For React, either upgrade its remaining Vite/router/Tailwind dependency chains with tests or complete removal of the retained client and its production deployment after confirming remaining consumers.

Do not release while unresolved dependency findings remain without a documented application-specific assessment and mitigation. Do not describe this checkpoint as a clean PHP audit.

## Host verification still required

Before production cutover, verify the document root is public/, HTTPS and secure session cookies, APP_DEBUG=false, restrictive CORS and trusted proxy configuration, private .env/backups/logs, disabled upload execution, and correct storage permissions. Check real mail delivery, OAuth callback domains, queue/scheduler execution and external API consumers. Validate backups/restoration and the two pending index migrations against an isolated copy before applying production schema changes. Imported data and existing uploads are preserved; retention, MySQL concurrency/load and production rollback rehearsal remain open.

## Verification

- Full isolated SQLite suite: 219 tests passed, 1,391 assertions.
- Four new upload tests cover raster storage, SVG/script disguises, excessive dimensions, unsafe URL schemes and revoked cached roles.
- Both root and React production builds passed. Existing empty root app chunk and large React chunk warnings remain.
- Synthetic HTTP checks: 89 successful check flags (including the browser-runner flag). Chrome: 50 successful flags and zero uncaught JavaScript errors.
- Browser artifacts: .local/migration-baseline/auth-smoke-20261011_130947_9ad6d7/.
- Imported database read-only inspection: 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51. No foreign-key orphans or missing media originals/conversions; no nontransactional tables. No imported records, schema or files were changed.
- Changed PHP passes Pint and Git whitespace checks pass. Apache execution blocking still requires verification on the actual deployment host.

No production deployment was performed. Unrelated pre-existing image deletions and untracked files are excluded from this checkpoint.
