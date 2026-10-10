# Integrations and risks

## Integration inventory

| Integration | Evidence | Migration requirement |
| --- | --- | --- |
| Google sign-in | AuthController::googleAuth, GoogleOAuthProvider in main.jsx, config/services.php | Preserve ID-token verification, approved-owner handling, identity linking, and authorized origins. Credentials stay outside Git. |
| Google Maps | client/src/components/Map.jsx | Browser key is hardcoded; replace with configuration and verify API/referrer restrictions. Do not put server secrets in frontend configuration. |
| Mail/SMTP | config/mail.php, mail and notification classes, BookingObserver | Capture mail settings privately; test with a mail sink. Prevent duplicate emails and dispatch side effects after transaction commit. |
| Database notifications and stream | NotificationController, useNotifications.js | Current fetch-based SSE plus 60-second polling uses bearer headers. Preserve read/delete/unread behavior with session auth; verify hosting timeouts. |
| WhatsApp | ContactInformation.jsx, owner/OwnerBookings.jsx, whatsapp Blade views | Browser wa.me handoff and message templates; no verified automated WhatsApp gateway identified. |
| Payment method labels | OwnerSubscriptionController, SubscriptionController | bank_transfer/one_pay are pending manual requests in reviewed code. No verified OnePay settlement/webhook implementation identified. Do not claim automatic payment confirmation. |
| Media and images | spatie/medialibrary, Intervention/Image, Filament media plugin, storage/app/public | Preserve database media references AND uploaded files; validate upload types/limits and access boundaries. |
| Hosting | public/.htaccess, client/public/.htaccess, two cPanel guides | Guides conflict on combined/split topology and panel URL. Confirm actual server document root before cutover. Never regenerate APP_KEY for an existing deployment. |
| Queue/session/cache | Captured environment summary; config files | Local queue/session use database. Preserve jobs/sessions tables and document workers, shared cache, deployment restart behavior. |
| Scheduler | Old app/Console/Kernel and runtime schedule.json | Two intended commands but zero runtime events; register and verify in a later implementation stage. |
| SEO and analytics | SEO.jsx, MainLayout visit tracking, sitemap/robots/static metadata | Preserve canonicals, structured data, tracking semantics, URL query behavior, indexing, and storage URLs. |
| External assets | Google Fonts, CDN icons, external image URLs | Preserve/rehost assets as appropriate; account for availability, content security policy, and privacy. |

Configured Postmark/SES/Resend/Slack options are framework configuration, not evidence of active integrations. No external account access or messages were used during this baseline.

## Risk register

| ID | Priority | Observed issue | Follow-up gate |
| --- | --- | --- | --- |
| SEC-01 | Critical | Public salon booking list and email-based booking lookup return private records | Require authenticated ownership or verified signed guest access; minimal public availability response |
| SEC-02 | Critical | Generic authenticated booking-status route lacks record/role authorization | BookingPolicy across every entry point |
| SEC-03 | High | Review identity trusts caller email; public rating models include email | Verified identity and safe public response fields |
| SEC-04 | High | Panel provider calls Model::unguard globally | Explicit field allowlists and protected privileged fields |
| SEC-05 | High | localStorage bearer tokens, no default expiry, no explicit API login throttling | Session auth, CSRF, session lifecycle, throttling |
| SEC-06 | High | Filament custom actions/forms and Livewire middleware enforcement need audit | Test direct actions, tampered IDs, ownership, revoked approval/subscription |
| SEC-07 | High | Customer booking linking trusts matching email | Verified guest-claim/account-linking process |
| DATA-01 | High | Plan changes overwrite paid active subscription/history | Shared, history-preserving subscription policy |
| DATA-02 | High | Booking hours, concurrency, capacity and allowed states differ across entry points | Shared validated booking service and concurrency checks |
| DATA-03 | High | Expiry/future renewal/amount/rejection semantics differ | One subscription state machine and boundary tests |
| OPS-01 | High | Configured local database initially missing | User requested empty database creation; import real data and recapture backup before migration |
| OPS-02 | Medium | Intended scheduled tasks absent from runtime schedule | Verify registration and hosting cron in later stage |
| OPS-03 | Medium | Deploy docs refer to /admin Filament and include key regeneration | Confirm /filament baseline; preserve existing encryption key |
| UX-01 | Medium | Legacy Blade frontend and subscription packages views missing | Rebuild and verify; do not just enable dormant routes |
| UX-02 | Medium | Hardcoded production links and obsolete fallback API URLs | Map URLs to local routes and test deep links |

Risk priority is based on source review. This is not a penetration test, live production assessment, or dependency vulnerability audit.
