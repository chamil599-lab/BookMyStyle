# Step two: security repairs

Implemented against source and isolated test fixtures on 10 October 2026. The local `book-my-style` database remains empty awaiting the user's import. No application migrations, seeds, customer notifications, or production changes were performed.

## Changes

- Legacy salon-booking lists now require an authenticated admin or approved active owner AND salon ownership authorization.
- Generic booking-status changes require the same role/approval/subscription protections and a BookingPolicy record check. Filament booking pages and custom/bulk actions use the policy; tampered salon/service IDs are rejected and relationship options are owner-scoped.
- Private booking lookup ignores caller-supplied email and uses the authenticated account. Guest email linking requires verified email and never overrides a booking's explicit user_id.
- Any profile email change invalidates verification at the User model level, including API changes. Google login requires a verified provider email and only verifies the account email when it matches the provider email.
- Review eligibility and submission require login and an account-owned approved booking (or verified guest email). Caller email is ignored. Review JSON hides email, and duplicate submissions return conflict, including database unique-constraint races.
- React's existing review form uses a read-only account email and asks guests to sign in. This is an intentional security change; anonymous reviews by typed email are retired. Booking remains available to guests.
- Global Model::unguard was removed. Existing model fillable lists remain in force.
- Homepage image-cycle creation is admin-only, including its FormRequest authorization.
- Login is limited to five attempts per email/IP per minute and thirty per IP; registration/Google/reset endpoints are limited to ten per IP per minute. Public booking/site-visit writes share a ten-per-IP-per-minute limit; review submissions are limited to five per account per minute.
- Forwarded headers are trusted only from explicitly configured TRUSTED_PROXIES. The default is empty. On hosting behind a reverse proxy, configure its actual IP addresses/CIDRs before deployment; do not use a wildcard. Otherwise attackers could spoof client addresses to evade throttles.
- Generic status updates rely on BookingObserver for one email delivery and no longer return SMTP exception details to callers.

## Verification

Security regression tests cover guest/customer/cross-owner denial, authorized owner/admin access, pending/expired owners, authenticated booking lookup, guest linking, email-change verification, review identity/privacy/duplicates, image-cycle permissions, login throttling, mass-assignment protection, and actual Filament Livewire forms. Tests use an in-memory SQLite database and fake outbound mail/notifications.

All 17 security tests pass (77 assertions). The final full suite has 30 passing tests and the same 22 pre-existing starter-kit failures (133 assertions). The React production build passes with its existing large-bundle warning. Detailed logs are ignored under `.local/migration-baseline/`.

## Remaining migration work

Import the real data and recapture/restore-test its backup before schema changes or cutover. Browser-based checks against imported records remain pending. This patch does not make the project fully secure: the next shared-service stage must unify booking capacity/hours/status rules and subscription state transitions. Session/cookie authentication, token retirement, complete Filament resource authorization, upload review, email verification flows, dependency auditing, and deployment configuration are separate migration stages.

Existing starter-kit route/view failures remain recorded in verification.md. Migration parity checks must account for the intentional access restrictions above rather than reproduce the old vulnerabilities.
