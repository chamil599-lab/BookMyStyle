# 17 - Dependencies and deployment security

Checked 16 October 2026. Current lockfile advisory checks passed; production deployment readiness is not established. No dependency updates or deployment were performed.

Executed Composer audit against composer.lock with plugins/scripts disabled: no advisories and no abandoned packages reported. npm audit reported zero vulnerabilities across 148 dependency entries, including development/optional entries. These are registry advisory results at the time of checking, not proof of absence of undisclosed vulnerabilities. Composer validation passed with one non-security warning about the exact guava/calendar 2.0 constraint. Local Composer emitted Git ownership/version-discovery warnings; audit and validation commands nevertheless completed successfully. No global Git ownership configuration was changed.

Composer check-platform-reqs --no-dev passed on local PHP 8.4.26 for the installed production dependencies. This validates the local runtime, not the hosting server or a new optimized production install. Composer lifecycle scripts can publish assets/run Filament upgrades; DEPLOY_CPANEL.md requires isolated locked installation and review before scripts execute. Updated that runbook to require fresh Composer/npm audits and lockfile validation before every release, with dependency fixes tested in isolation.

Reviewed DeploymentReadiness, public release scanning and the deployment runbook. A read-only report using this checkout's current configuration returned release_ready=false and automated_pass=false. Failed IDs: production_environment, debug_disabled, https_application_url and restricted_cors_origins. This checkout remains configured for development; its settings were not changed to production. All ten manual host/integration gates remain unverified. The report checked configuration/local metadata without database queries, mail or deployment and printed no secret values.

DeploymentReadinessTest, ProductionCorsTest and PublicReleaseFilesTest passed **16 tests, 38 assertions; one symlink test skipped** because this Windows host cannot create the required directory link. Guards cover unsafe configuration, unbounded tokens, public artifacts, assets and symlink handling. The actual public-tree scan also passed in the preceding data-exposure check.

Outstanding before release: correct document root/HTTPS, production debug/cookie/CORS/proxy settings, real host PHP/extensions and optimized autoloader, least-privilege database/storage access, uploads unable to execute scripts, isolated migration/concurrency validation, backup restoration/rollback rehearsal, real mail/Google/payment integration, workers/cron and browser/load checks. Strict script CSP remains outstanding from item 16. The imported MySQL database was untouched; no migration, credential rotation, package update, mail or production action occurred.

Next category: monitoring and recovery.
