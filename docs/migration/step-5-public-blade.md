# Step five: public Blade pages

Implemented on 11 October 2026 (Asia/Colombo). This is a local source checkpoint; no production deployment was performed.

## What moved

Laravel now serves the homepage, salon directory, salon detail, services, deals, about, trending, popular, terms, and privacy pages. The shared Blade layout provides desktop/mobile navigation, session login/logout links, theme preference, metadata, canonical URLs, and a footer. The existing logo, video, warm palette, and font family are retained. Public CSS is built separately from Filament and account styles: about 24.6 KB, 5.9 KB compressed.

Directory search, department filters, validated sort choices, and pagination use ordinary GET requests. Filters survive pagination and work without client API data loading. Salon detail includes published services, conditional pricing, gallery links, contact/social/map links, opening hours, reviews, favorites, and the Livewire booking form. Managed homepage images rotate when available; the existing video is the fallback. Reduced-motion preferences disable automatic image cycling/video playback.

The booking form shows slots that fit the complete rounded service duration and current staff capacity. Availability uses a bounded pair of booking/duration queries instead of querying each individual slot. Submission still rechecks quota, approval, subscription, service ownership, future time, hours and capacity inside the shared transaction. Guest booking remains supported; signed-in booking uses the authenticated account. Submission requests salon approval rather than claiming instant confirmation.

## Security and corrected behavior

- Approved accounts with the owner role define public salon visibility. Pending, revoked, missing-owner, and non-owner salons are excluded from Blade directory/detail, sitemap, and public salon API endpoints. Expired subscriptions do not hide the salon; online booking is unavailable until the owner has an active subscription.
- Only published, non-deleted services belonging to the salon creator are public. Editing another owner's service does not transfer service ownership. Draft/unpublished services cannot be selected through a forged public booking request. Historical/management booking behavior remains separate.
- `show_price=false` suppresses actual service prices in rendered HTML, Livewire markup, and public salon/service API responses. Public service JSON omits owner audit fields. Generic managed service-page pricing remains public as explicitly published content.
- Search uses bound SQL parameters with escaped wildcard characters. Sort choices and filter sizes/page bounds are validated. Content is escaped as text; external image/social URL schemes are constrained to HTTP(S) and safe local paths. JavaScript/data URLs and traversal image paths are rejected.
- Reviews require a verified signed-in identity with an approved or completed booking. Shared review rules now serve both API and Livewire. Email supplied by a visitor cannot establish review identity. Existing unique constraints and transactional account locking prevent duplicates; public review HTML and JSON omit email addresses.
- Favorite actions use the authenticated account and existing `favorite_salons` table used by React. They require verification, limit requests, and lock the account for mutation. The separate legacy `salon_favorites` table is not repurposed or merged.
- Public visits continue feeding existing analytics, with one session/path visit per 30 minutes. Only public successful GETs are recorded; query strings/referrers are omitted and network/session identity is keyed-hashed. This represents session visitors rather than persistent cross-session tracking. Recording failures do not prevent page delivery.
- Sitemap and robots responses come from Laravel, with current public salon IDs and private routes excluded. Canonical URLs and sitemap locations use configured APP_URL. The old static root files were removed so they cannot shadow these endpoints.

Trending/popular previously displayed sample technology articles. Trending now ranks approved/completed bookings created within the last 30 days, then rating; popular uses all-time approved/completed booking counts, then rating. Ties have stable ID ordering. The definitions appear on their pages. About statistics use real public-catalogue aggregates instead of invented fallback counts/team members.

Existing legal text was transferred from React rather than rewritten or reviewed as legal advice. Its previously generated daily date is now fixed to the migration date. Actual legal/contact accuracy remains a business review item.

## Deployment and remaining stages

Apache must send non-file requests to Laravel's index.php and use it as DirectoryIndex. Both `public/.htaccess` and the React build's source `.htaccess` now accommodate that arrangement. Do not deploy an old React rewrite file over these changes. Build the root assets with `npm run build`; the new `storefront` manifest entry and the copied hero video must be deployed with the Blade source.

Customer `/dashboard/upcoming`, `/history`, `/favorites`, `/profile`, and `/change-password` URLs are the next migration stage. Locally, they still use configured FRONTEND_URL. In the existing same-domain production layout, Laravel can serve the retained root React index.html for those specific paths; retain its existing assets until those pages migrate. If a deployment omits that legacy bundle and points FRONTEND_URL back to itself, Laravel returns to the account dashboard instead of entering a redirect loop. This fallback is not customer-page feature parity.

Blade sessions and React bearer tokens remain separate during this transition. Public navigation points to native `/dashboard` and authentication. Preserve the existing React files only for remaining journeys; do not replace the Laravel public assets with an entire React dist directory. Old `/admin` and `/owner` SPA bookmark mapping and full Filament resource parity are still migration work. The new Apache arrangement should be staged together with those remaining cutover checks before a production release. No deployment is authorized or performed by this step.

No imported schema changes, migrations, seeds, record edits, or delivery to real customers were performed. Synthetic fixture HTTP/browser tests suppress mail. Tests cover visibility, filtering, pagination, price privacy, escaped content, published-content rules, capacity, favorites and review identity. Real Chrome checks exercise mobile menu/theme and Livewire availability, check horizontal overflow/JavaScript errors, and save private desktop/mobile screenshots. Production email/Google delivery and a broader dependency/resource security audit remain open.

Checkpoint: `migration-step-5-public-blade`. Private source bundle: `.local/migration-baseline/step-5-public-blade.bundle`. Use [the rollback runbook](rollback.md) with step four and rebuild root assets to roll back source. Restore the previous Apache/static SEO arrangement only as part of that coordinated source rollback; retain the verified imported-data backup.
