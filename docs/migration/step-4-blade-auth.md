# Step four: Blade authentication

Implemented on 11 October 2026 (Asia/Colombo).

Laravel now serves `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, email verification, `/profile`, and the profile/password/appearance settings pages through Blade and Livewire. Login creates a web session instead of exposing a browser API token. Registration supports customers and pending salon owners. Password registration sends email verification; imported users are not automatically verified.

`/dashboard` requires authentication and a verified email. Administrators continue into the Filament admin dashboard; approved owners continue into the Filament owner dashboard. Customers and pending owners see an account page. Filament uses the same web session. Customers cannot access the panel; pending owners retain limited account access and can complete email verification without an approval redirect loop. Administrator and owner accounts cannot use the customer self-deletion action.

## Security changes

- Registration and Google identity resolution use shared account services across Blade, API, and Filament registration. Submitted approval, verification, or administrator privileges are never accepted from public registration.
- Web login regenerates the session. Logout invalidates it and regenerates the CSRF token. Native forms and Livewire requests use CSRF protection. Session cookies are HttpOnly and SameSite=Lax, with secure cookies enabled by default when APP_URL uses HTTPS. HTTPS must be preserved in deployment.
- Login, registration, password reset/confirmation, and verification resend actions have server-side limits, including limits inside Livewire actions. Reset requests give the same public response for existing and unknown addresses.
- Password updates rotate remember tokens, revoke all existing Sanctum tokens, and remove other database-backed sessions. The session authentication middleware rejects stale password hashes for other session stores. Livewire persists authentication and verification middleware checks.
- Signed email verification checks the intended user and current email. An email change clears verification, as established in step two.
- Google sign-in validates the credential on the server using the configured Google client ID. It requires a verified email, rejects conflicting identities, preserves existing roles and approval, and restricts automatic linking of existing password accounts to Google-authoritative Gmail/Workspace claims. Other existing accounts must use their password. Browser credential submission is CSRF protected and does not return a bearer token.
- Proxy configuration is now read after Laravel has booted. An empty trusted-proxy list ignores forwarded identity/scheme headers, including forged cloud-looking hostnames; configured proxies remain supported.
- Legacy owner CRUD routes now require verified, approved owner access in addition to subscription checks.

## Compatibility and remaining migration work

Public pages and `/dashboard/*` still redirect to React. Existing React bearer-token authentication remains operational and is separate from the new web session: signing in on Blade does not create a React localStorage token. Migrating public/customer pages and changing their navigation comes next. Retire bearer-token browser authentication only after those flows move to Blade.

Previously issued `/password/reset?token=...&email=...` and `/reset-password?token=...` links redirect to the Laravel reset view. API registration now sends verification mail; API password changes invalidate previously issued tokens. Existing unverified administrators and owners must verify their email to use the panel; no imported verification flags were changed. Google-only accounts can use the reset flow to establish a password.

Google buttons appear only when a client ID is configured. Configure the deployed origin in Google and test it before release. Identity tests mock the verifier; live Google consent and SMTP delivery were not exercised. Mail was suppressed throughout automated verification. Visual parity with the React pages and production deployment checks remain open. This step is not a complete security audit.

## Validation and rollback

The automated suite covers account privileges, signed verification, rate limits, session rotation, password/token/session invalidation, Google linking, and Filament access. `scripts/migration/verify-blade-auth.php` separately starts a real local HTTP server against a private synthetic SQLite database. It checks real Livewire login, session cookies, CSRF rejection, dashboard/profile access, logout, and registration/reset page rendering. Its mail transport is suppressed; it does not use the imported MySQL database.

The root asset build and Blade compilation pass. Imported-data inspection confirms no schema changes, no change to the captured aggregate counts, and 51 migration records. No migrations or seeds ran against imported data. Two previously pending index migrations remain pending.

Checkpoint: `migration-step-4-blade-auth`; private source bundle: `.local/migration-baseline/step-4-blade-auth.bundle`. To roll back source and assets, follow [the rollback runbook](rollback.md), using the step-three checkpoint and rebuilding root assets. Password/token invalidation and user verification performed by real users cannot be reversed by a source rollback; no such user actions were performed by this verification.
