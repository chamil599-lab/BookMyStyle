# Step 16: cached native release and staging verification tools

Added BMS_AUTH_SMOKE_CACHED=1 mode to the native HTTP/Chrome runner. It generates real Laravel configuration, route and Blade view caches in a newly created private fixture directory. SQLite, sessions, cache, mail and queue are explicitly isolated before cache generation. HTTP-only fixture response headers prove the server uses cached configuration and routes; compiled views are checked too. The headers exist only in the loopback smoke router, not the application.

The fixture disables Google sign-in and suppresses mail. Its cached configuration and synthetic account files may contain private configuration values and remain under ignored .local. They must never be published or copied into production. The actual source .env, application cache paths and imported database are not changed by this stage.

A real unauthorized API identity regression exposed an exception-response path that skipped route middleware privacy headers. bootstrap/app.php now adds private/no-store and noindex/nofollow headers to exception responses under api/auth/*, while preserving status/body behavior. Authenticated identity and login validation responses retain their existing middleware headers.

## Host checks

The staging URL/access method was requested but has not been supplied. No live staging probe, external email delivery, Google sign-in, payment submission or production deployment has been performed. The CLI host checker is prepared and tested with synthetic responses:

~~~powershell
node scripts/migration/verify-staging-host.mjs https://YOUR-STAGING-HOST
~~~

Use an HTTPS origin only, without embedded credentials, a path, query or fragment. TLS verification must remain enabled; the checker refuses NODE_TLS_REJECT_UNAUTHORIZED=0. It sends unauthenticated GET/OPTIONS requests, follows no redirects and uses timeouts plus a bounded login response body. It checks the native login, customer/panel guest redirects, private unauthorized API identity response, hidden environment/Git/Composer/vendor metadata and denial of an unreviewed CORS origin. Its report contains fixed check identifiers and statuses, not response bodies, cookies, credentials or network exception details.

Exit zero means those public automated checks passed; one means failed checks; two means invalid target/TLS configuration. release_ready always remains false. This is not a penetration test or proof of the server's document-root path, upload execution restrictions, all exposed files, authenticated authorization or integrations. GET/OPTIONS may create normal host access logs. It never submits login/payment/forms or sends messages.

On the actual host, separately run the PHP configuration/file preflight from the isolated release directory. Then review HTTPS/proxy/document-root/upload settings, optimized host PHP dependencies, queue supervision and cron requirements, production backup restoration and load/lock timing. Google ID-token sign-in needs the real authorized browser origin/client configuration. Email verification and password reset need controlled delivery testing with an approved recipient; no real messages are sent by these helpers. Payment labels still represent pending manual requests, not automatic OnePay processing.

The old split-domain React deployment guide now links to the native DEPLOY_CPANEL.md runbook. Scheduler registration remains an open operational gate: intended commands exist in the legacy kernel but were not registered in the captured runtime schedule. This stage does not enable background reminders or execute scheduler commands against imported data.

Final validation results and private artifact location are recorded in verification.md. Live host/integration gates remain pending until staging details and any required test-account/message authorization are supplied.
