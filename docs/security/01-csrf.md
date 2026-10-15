# 01 — CSRF protection

Checked 15 October 2026. Result: no CSRF protection gap found in the reviewed local browser application. This does not certify the deployed host or other security categories.

Reviewed Laravel's web middleware configuration, all authored route definitions, plain POST forms, AJAX mutations, Livewire update/upload routes, Filament panel middleware, authentication/logout, Google sign-in, customer actions and subscription requests. No application CSRF exclusions or middleware bypasses were found. Plain mutation forms include `@csrf`; the card favorite fetch supplies `X-CSRF-TOKEN`. Directory filtering uses GET without account mutations. Livewire actions use its web-protected update endpoint, and Filament explicitly includes CSRF middleware.

Added `tests/Feature/Migration/CsrfProtectionTest.php`. Laravel normally bypasses CSRF during tests, so these checks explicitly enable the real middleware. Sixteen browser mutation endpoints reject both missing and mismatched tokens with HTTP 419, including Livewire update/upload and authorized Filament logout. Matching form and AJAX header tokens successfully log out; a mismatched AJAX token is rejected. Favorite changes cannot use GET. The bearer API is checked separately to ensure its route middleware does not enable browser session/stateful authentication.

Result: **20 tests passed, 42 assertions**. The initial Filament probe used a user without panel access and correctly received HTTP 403; the final probe uses an authorized admin to exercise CSRF itself. No production application changes were needed.

API bearer requests do not use the browser's session-token workflow. Public unauthenticated booking/visit API endpoints remain rate-limited public writes; their abuse resistance belongs to the API/rate-limiting review. Signed email verification links and non-sensitive visit tracking are intentional GET-side effects. Production cookie/HTTPS configuration still belongs to the deployment review.

Reference: [Laravel 12 CSRF protection](https://laravel.com/framework/docs/12.x/csrf). Next category: XSS, not yet audited in this sequence.
