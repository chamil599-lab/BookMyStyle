# Step twenty-three: inspect public release files

The read-only deployment preflight now inspects nested public directories instead of checking only a few root filenames. Five fixed statuses cover completion, repository/environment metadata, backup/database/archive/log/key artifacts, unexpected server-script filenames and symlink targets. Root `index.php` remains allowed, while nested `index.php` and compound names such as `picture.PHP.jpg` fail. Normal CSS/JS, SVG, build manifests and Apache rules remain allowed.

The scanner reads filesystem metadata only and reports no filenames, contents or exception messages. Missing/unreadable/unsupported trees, over 50,000 entries or more than 32 directory levels fail inspection. It does not follow arbitrary symlinks; only a root `storage` link resolving exactly to this release's conventional `storage/app/public` is followed and inspected. Other layouts fail pending explicit review. The application performs no deletion, permission change or file move.

This is a filename denylist and prerequisite check, not a content scanner or proof of the server's document root/execution configuration. Unrecognized extensions, disguised content and host-specific alias/reparse behavior still need review. Production upload execution restrictions, secret handling and actual host probes remain mandatory. Strict automated success continues to report `release_ready=false`.

Focused tests cover accepted assets, nested metadata, compressed SQL, Git bundles, private keys, nested/compound scripts, empty repository directories, unavailable trees and report non-disclosure. This Windows host denies directory symlink creation, so the symlink fixture is explicitly skipped rather than claimed as passing; verify it on the release host. Existing workspace public-file statuses pass. Local development configuration still fails production prerequisites as expected; no environment settings were changed.

Results and private logs are recorded in verification.md. The preflight makes no database queries. Regression tests use isolated fixtures; no imported-database queries/migrations, browser requests, actual mail, deployment or source asset changes occur in this stage. Existing image deletions and local files remain outside the checkpoint.

Checkpoint: `migration-step-23-public-release-files`. Private full-history rollback bundle: `.local/migration-baseline/step-23-public-release-files.bundle`. Revert the helper, preflight integration, tests and docs without restoring the imported database.
