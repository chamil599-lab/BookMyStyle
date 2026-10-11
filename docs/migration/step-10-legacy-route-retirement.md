# Step ten: management route retirement and native renewal

Prepared 11 October 2026 (Asia/Colombo). Local source checkpoint only. No production deployment, imported record writes, migrations or seeds.

## Retired contracts

The 56 React management endpoints under /api/admin and /api/owner now return HTTP 410 with code endpoint_retired and a fixed native next_url. The exact methods, paths and replacement routes are in [retired-management-api.json](retired-management-api.json). These endpoints retain authentication, verified-role checks, owner approval where applicable, throttling and private no-store responses. Current roles/approval are checked again against a fresh account before returning a destination. They no longer query business records or run the former management/reporting controllers.

Five duplicate API controllers were removed. This also removes their divergent reporting definitions, weak package/service validation, independent quota checks and destructive rejection path from active management. Existing management GET bookmarks still redirect to authorized native pages. The broken /owner salon/booking writes and nested staff/service routes now return an explicit 410 page or JSON response; they never redirect/replay a write. Three empty resource controllers were removed. Staff remains the salon staff-count setting; this does not introduce individual staff profiles or assume a working staff CRUD feature existed.

**Compatibility change:** the standalone React admin/owner screens and any external clients calling these 56 endpoints can no longer manage records. Before release, confirm deployed frontend domains and any external consumers; ship the Laravel/native route cutover together. There is no compatibility flag that re-enables the removed controllers. Source rollback is to the step-nine tag, followed by rebuilding assets.

## Retained API surfaces

Public catalogue/content, guest booking/availability, reviews, visits, authentication, customer account/favorites/bookings and notifications remain available. The two generic booking management endpoints remain temporary compatibility contracts using BookingService and scoped policies; both now require verified access and have rate limits. Authenticated APIs in the main private group use no-store headers. The administrative image-cycle write remains protected and now also requires verification and throttling. None of these retained contracts is certified by this stage as fully audited or ready for indefinite support. Reconcile/retire the remaining contracts after integration and consumer review.

The booking policy now uses the same verified admin/approved active owner definitions as catalogue management. Shared management booking writes refresh their actor before authorization and after acquiring record locks. Subscription request/payment/trial/approval/cancellation rules likewise refresh the acting account, protecting against a stale component/account object after verification or role/approval revocation. Approval amounts are bounded to the decimal column's maximum. Trial targets may still be unverified owners when an administrator approves onboarding, preserving the existing approval/trial policy; owner self-service requires approved, verified access.

## Native subscription recovery

The subscription URLs are retained because Filament links to this native recovery flow. They now require a verified, approved owner without requiring an active subscription, so expired owners can renew. Customer/admin access is denied. POST requests require CSRF and are throttled. The missing-view package bookmark redirects to /subscription/required; successful requests redirect using an explicit panel route rather than relying on a default panel.

Plan selection and checkout now use the storefront Blade layout and root-built CSS/Livewire assets, replacing CDN Tailwind/Alpine and icon scripts. Plans show LKR and actual duration, monthly request limits and calendar/analytics features. Only currently available packages are offered; inactive/future/ended/deleted checkout packages are rejected. Active plans are labelled active; same-plan renewal is selectable. Pending requests block duplicate submissions, display their quoted package name and link to payment editing/history. Unsupported trial/refund promises and broken support links were removed.

The agreed policy remains unchanged: current paid access continues while a request is reviewed; approved same-plan renewals begin after the current period, while different-plan approvals begin immediately. Submitted owner/status/amount/date fields are ignored. A request is pending with zero recorded payment until administrator review. One Pay retains its existing request-recording option, clearly labelled as lacking automatic processing; no payment or mail is sent by verification.

## Evidence and remaining release gates

See [verification.md](verification.md) for final counts and private artifact locations. Regression tests exercise every retired API method, verify unchanged business records and no notifications/mail, and cover expired renewal, duplicate requests, forged fields, unavailable packages, bank-reference validation, revoked access and bounded amounts. Existing shared-service coverage now tests native Blade requests and the actual Filament approval action rather than the retired API.

Remaining work: PHP/root/retained React dependency and media audit, external consumer review, real mail/payment delivery, account retention rules, MySQL concurrency/load/SSE capacity, unapplied index migrations, complete visual/integration parity and production deployment/rollback rehearsal. No full security certification is claimed.

Checkpoint: migration-step-10-legacy-route-retirement. Private bundle: .local/migration-baseline/step-10-legacy-route-retirement.bundle. Follow [rollback.md](rollback.md) for source/assets; imported records do not require restoration for this source-only stage.
