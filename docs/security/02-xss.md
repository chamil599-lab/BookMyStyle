# 02 — Cross-site scripting

Checked 15 October 2026. Result: no exploitable XSS path found in the reviewed local application. This is scoped evidence, not a guarantee against every possible payload or a deployed-host certification.

Reviewed authored Blade output, Alpine/JavaScript expressions and DOM insertion, public URLs/images, salon/service/review text, editorial cards, customer identity, notification views, Filament tables/infolists, calendar content configuration and mail rendering. User data uses escaped Blade output; JavaScript data uses JSON/Js serialization; public links/images are restricted by PublicUrl. No authored `x-html`, untrusted raw HTML output, eval, document.write or innerHTML sink was found. The raw sitemap expression emits only the constant XML declaration.

Instant filtering parses same-origin server-rendered HTML and copies the escaped results section. No untrusted text is reinterpreted as raw HTML. Calendar custom HTML event content is not enabled for the booking widget; descriptions in its details and Filament service infolist use normal text entries. Raw HTML support inside vendor libraries is not itself evidence of an application vulnerability.

Added 12 regression tests covering stored salon/service/department/category/review payloads, reflected search input in HTML and Alpine contexts, deals/services content, customer identity/profile/notifications, Filament salon tables/inbox and seven executable/obfuscated URL variants. These and the five existing upload-security tests passed: **17 tests, 106 assertions**. DOM parsing confirms malicious input creates no probe elements, inline event-handler attributes or executable script nodes.

A headless Chrome probe additionally passed four checks with no JavaScript exceptions: reflected search round-trips as literal text, does not execute, remains safe when instant filtering replaces results, and initializes without script errors. It used only the private synthetic fixture; imported data was not modified. Browser evidence: `.local/migration-baseline/auth-smoke-20261015_022026_69a1a2/xss-browser-summary.json`.

No production application changes were needed. CSP and third-party script/dependency management remain separate checklist items, not certified by this result.

Reference: [OWASP XSS prevention guidance](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html). Next category: SQL injection.
