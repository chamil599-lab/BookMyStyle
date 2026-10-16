# Email readiness before the final release

Reviewed 16 October 2026. This extends the security checklist with actual in-memory mail transport/rendering tests; earlier Mail/Notification fakes alone did not exercise complete message delivery/rendering.

Fixed during this review:

- Removed the empty legacy App EventServiceProvider registration from config/app.php. Laravel's current bootstrap already registers the framework event provider; both providers registered SendEmailVerificationNotification, producing two verification emails per AccountService registration. The transport test now requires exactly one message.
- Bounded SMTP timeout to 15 seconds by default, configurable through MAIL_TIMEOUT. Previously timeout was null. Updated .env.example without changing the private environment.
- Booking decision emails resolve soft-deleted service titles and have a fallback if the service is absent. Previously dereferencing the normal relationship could break email rendering after a service was deleted.
- Account-approval emails use the configured application's role-aware dashboard route rather than a hardcoded old domain/admin URL.

| Flow | Current behavior | Evidence / remaining work |
| --- | --- | --- |
| Customer/native owner registration | One verification email | Real in-memory transport rendering, recipient and signed-link verification tested. |
| Verification resend | Throttled; signed account/email-bound links | Existing authentication tests cover resend, expiry, tampering and foreign accounts. Real inbox link still needs an appropriate reachable environment. |
| Password recovery | Reset email with token bound to account | In-memory email link opens the native form and completes password reset; expiry/reuse covered by earlier security tests. |
| Filament owner registration | Pending-account message plus Filament verification | Account-message rendering tested; pending welcome message exists only on this registration path. Full real-inbox panel registration remains a staging check. |
| Owner approval/rejection | Email after committed moderation action | Templates rendered through in-memory transport; approval dashboard URL corrected. Mail failure/retry still needs operational handling. |
| New booking | Owner dashboard/database notification | No booking-request acknowledgement email or owner new-booking email is currently implemented. |
| Booking approved/rejected | Customer email plus account notifications | Existing status-change tests and deleted-service email rendering tested. |
| Customer cancellation | Owner dashboard notification; status becomes rejected | The booking observer sends the generic rejected-status email; distinct cancellation wording is not implemented. |
| Subscription expiry | Email and dashboard notification | Rendering path exists; reminders remain host-disabled until cron/cache/mail setup is verified. |
| Subscription request/approval/cancellation | Management workflow and UI feedback | No dedicated transactional email classes for these events were found. Decide required communications before launch. |

Configured SMTP credentials and a non-placeholder sender are present. The configured host/account differs from the mail.limach.lk:465 / infobookmystyle@limach.lk details supplied in chat. Existing credentials were not transferred to that different server, and no private credentials were printed or changed. Port 465 uses implicit TLS (`MAIL_SCHEME=smtps`) if that mailbox is later configured with its correct password privately.

With the user's supplied test inbox, sent one real, clearly labelled plaintext delivery check through the existing configured SMTP sender to infobookmystyle@limach.lk. SMTP accepted the handoff. This does not prove inbox placement, SPF/DKIM/DMARC, bounce handling or link completion; receipt confirmation was requested. The message contained no customer data or account tokens. No imported account was created/modified and no imported booking/subscription was used.

Current mailables/notifications execute synchronously despite the database queue configuration: Queueable alone does not implement ShouldQueue. Delivery can therefore affect request latency. Reliable delivery needs an agreed retry/outbox approach, monitored failures and careful transaction boundaries. In particular, after-commit mail failures can occur after an account decision has already saved; avoid treating a retry as a second approval or registration.

Before release: confirm inbox/spam placement and sender identity; configure/verify SPF, DKIM and DMARC with the actual provider; test verification/reset/owner flows from a reachable staging site using controlled accounts; review cancellation wording, missing event emails and legacy template styling/support contact; establish monitored retry delivery and reminder scheduling; rebuild reviewed configuration caches with the release. No production rollout or imported database mutation occurred.

Final local verification: **77 tests passed, 296 assertions**, across EmailFlowTest, SharedServicesTest, BladeAuthTest and MaintenanceOperationsTest. The in-memory transport exercises template rendering and actual notification/mail construction without contacting SMTP; the separately authorized SMTP check tests handoff only.
