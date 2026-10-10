# Step seven: secure Filament management foundation

Implemented on 11 October 2026 (Asia/Colombo). This is a local source checkpoint; no production deployment was performed.

## Completed management work

Salons and services now have explicit authorization for listing, viewing, creating, editing and deleting. Approved owners can read their own catalogue; an active subscription is required for mutations. Expired owners retain catalogue and subscription-history access so they can recover their plan. Administrators can manage all catalogue records without having a personal owner subscription. Administrators select an approved owner account when creating a salon; creating a service uses the selected salon's owner.

Filament salon/service creation and editing call `CatalogueManagement`. Creation locks the owner's account before checking quotas; edits lock the account and record and reauthorize against current identity/ownership. Limits cover salon count, owner-wide service count and staff capacity. Service ownership is owner-wide in the existing schema: selecting a salon does not create a new salon/service pivot relationship. Audit fields come from the server, ownership cannot be transferred through form fields, and administrator edits preserve the creator while recording the editor. Validation errors attach to the actual Filament fields rather than raising generic exceptions.

Service validation includes category/department consistency, nonnegative prices, valid status and positive bounded integer duration. Salon validation includes bounded text, staff count, opening/closing times and HTTP(S) social links. Managed social table links also use the safe URL helper for already-imported values. Upload controls retain raster-image MIME/size restrictions and stop preserving client-provided filenames. Media deletion callbacks reauthorize the current record before deleting its media. This step does not claim a complete media-upload security audit.

Explicit policies now protect Users, Departments, Categories, Subscription Packages and managed public-content resources. Hiding a navigation entry no longer supplies their only create/edit protection. Owner access to subscription records is restricted to their own history; subscription creation/editing remains administrative and deletion remains disabled. Administrator accounts and the acting administrator cannot be removed through user deletion; user bulk deletion authorizes each record. Custom user approval/rejection actions recheck the acting account and restrict approval roles to owner/customer. Existing approval mail and rejection behavior are retained and still need broader parity review.

Approval middleware now persists across authenticated Filament Livewire requests. Filament responses disable caching and indexing, and the panel home link uses the role-aware native dashboard handoff.

## Calendar and analytics access

The owner dashboard only mounts the calendar when the active plan enables it. Calendar fetches recheck that feature and role, restrict date windows to 93 days, load only displayed dates, and use the rounded service duration rather than an arbitrary one-hour event. Historical service duration remains available after soft deletion.

Browser-supplied calendar event model/record fields are restricted to Booking and the authorized owner's salons. Arbitrary model resolution and cross-owner record selection are blocked. The popup schema uses the installed Filament 4/Guava API instead of an unused older Infolist method. Analytics access also explicitly requires a verified administrator or approved active owner with the analytics feature, and salon-scoping queries recheck access.

Financial definitions and chart calculations have not been certified by this stage. Existing owner/admin earnings calculations, subscription revenue/status buckets, profit/commission and date boundaries remain the next parity stage. Browser-local calendar timezone behavior also needs comparison before claiming cross-timezone parity.

## Old dashboard links and remaining scope

`/admin` and recognized admin management links redirect to their corresponding Filament pages; `/owner`, `/owner/dashboard`, `/owner/salons`, `/owner/services`, `/owner/bookings` and `/owner/subscription` do the same for owners. Recognized create/view/edit links redirect by record ID and retain authorization at the destination. Profile links enter the native account form. Role and verification gates protect these aliases; pending owners reach the pending-approval page. Redirect destinations are a fixed mapping, not visitor-supplied URLs. Old React query filters are not translated automatically.

Top-level legacy owner salon/booking GET routes now redirect instead of rendering older management views. Legacy write routes and nested salon/staff/service routes remain for compatibility while they are audited; they are not retired in this checkpoint. React source and API endpoints are retained. This stage therefore establishes safe core management and bookmark entry, not complete admin/owner feature or visual parity.

Next-stage gaps include package editing (the current resource only registers create/view), remaining user/subscription action parity, notification/WhatsApp journeys, analytics/commission definitions, calendar interactions/timezone comparison, remaining legacy-route retirement and deployment checks. Preserve the verified imported backup and React assets until these gates pass.

## Verification and rollback

Tests use synthetic SQLite records and suppressed mail. They exercise direct forbidden create/edit URLs, foreign owner records, actual Filament create/save submissions, server-owned audit fields, quota/staff errors, expiry recovery, administrator deletion protection, bookmark routing, calendar model/record forgery, feature/range limits and revoked approval. Real HTTP and Chrome checks cover both roles' native session handoff, resource lists/create screens, owner denial, private headers and responsive layouts. SQLite tests do not independently prove MySQL concurrency under load; transaction lock ordering is implemented, while production load/deadlock checks remain a separate gate.

No imported migrations, seeds, record changes or media changes were performed. The imported counts and integrity checks remain unchanged. No real email delivery or production deployment was performed.

Checkpoint: `migration-step-7-filament-management`. Private source bundle: `.local/migration-baseline/step-7-filament-management.bundle`. Roll back source to step six using [the rollback runbook](rollback.md) and rebuild root assets. No imported database restore is required for this source-only step.
