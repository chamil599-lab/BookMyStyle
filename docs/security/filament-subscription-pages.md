# Filament subscription pages

Subscription plans and checkout now render as discovered Filament pages under
`/filament/subscription-plans` and `/filament/subscription-checkout/{package}`.
The Billing navigation includes Subscription Plans. Dashboard and subscription
history actions open the panel page directly. Previous `/subscription/required`
and `/subscription/checkout/{package}` bookmarks redirect after the existing
authorization and package checks.

Only verified, approved owners can access either page. An active subscription
is intentionally not required, allowing expired owners to request recovery.
Checkout's package ID is locked against Livewire client modification; access
and package availability are checked again when rendering. Requests still use
the existing authenticated, verified, approved, CSRF-protected and throttled
POST endpoint and SubscriptionService validation. No payment is processed by
these pages, and no subscription is automatically approved.

The plans page shows trial or paid status, remaining days, start date and end
date. Pending requests prevent another checkout. Existing renewal scheduling
and plan-change approval rules are preserved. Imported records were not changed.

Validation covers Filament rendering, legacy redirects, expired-owner recovery,
role restrictions, unavailable packages, payment validation errors after redirect,
trial information, and pending-request blocking using synthetic test data.

Validation result: 415 tests passed (2,314 assertions), one existing Windows
symlink test skipped. Run directly with
`php -d memory_limit=512M vendor/pestphp/pest/bin/pest --compact`; the default
128 MB full-suite process exhausted memory. Focused subscription tests: 17
passed (488 assertions). Vite production build and changed-PHP Pint check passed.
