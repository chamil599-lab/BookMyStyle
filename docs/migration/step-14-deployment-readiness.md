# Step 14: production readiness

Added a read-only configuration/file preflight at scripts/migration/check-deployment.php. It checks production environment, debug, HTTPS, key shape, cookies, persistent stores, mail selection, MySQL selection, CORS, explicit proxy ranges, root build files, published Filament assets and accidental public project files. It reports fixed identifiers/statuses; configuration values and exception details are withheld. It performs no database queries, external requests, mail delivery or deployment. It reads effective Laravel configuration, including any existing configuration cache.

Default mode produces a diagnostic report even with failed prerequisites; --strict returns failure when automated checks fail. Manual gates always remain unverified and release_ready always remains false. This tool is an aid for release review, not authorization or a complete security audit. It does not validate every mail transport option, scan all exposed archives, verify every published asset byte or prove host/proxy/DNS behavior. Database state and pending migrations require separate read-only review.

Production CORS now defaults to no external origins; explicit environment settings remain honored. Local defaults continue to support development. The retained API auth group now uses private/no-store and noindex response headers, including identity and login validation responses. Native session authentication is unchanged.

DEPLOY_CPANEL.md replaces obsolete instructions to publish React over native routes. It covers isolated release builds, protected document root, existing-key preservation, server upload execution restrictions, cached release validation, integration tests, backup/restore and explicit release approval. Actual production values are not written into this checkout.

Verification results and checkpoint are recorded in verification.md. Imported MySQL is unchanged; its two pending index migrations remain unapplied. No production deployment has occurred.

The preflight loads only Laravel environment/configuration bootstrappers, without booting providers, to avoid discovery-cache writes. Local config/route/view cache generation passed using private cache paths, SQLite in memory and suppressed mail; the final preflight passed report generation against both cached and uncached settings. This does not validate host behavior or cached-release browser parity.
