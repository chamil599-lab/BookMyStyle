# Versioned updates

`VERSION` is the single source of truth for the application version. The initial development baseline is `0.1.0`. Composer derives project versions from Git; do not add a separate Composer version field. The private npm package only builds assets and has no independent release version.

## Prepare an update

1. Create a branch from `main`, for example `git switch -c feature/booking-improvements`.
2. Make the change and add a concise entry under `Unreleased` in `CHANGELOG.md`. Include any configuration or migration requirements.
3. Run the checks below and open a pull request. Review and merge it before preparing a release.

```powershell
php -d memory_limit=512M vendor/bin/pest
php vendor/bin/pint --test
npm run build
```

Use the existing disposable test database configuration. Preserve installation secrets, APP_KEY and uploaded files when updating an installation.

## Prepare a release

1. Choose the next version: PATCH for compatible fixes, MINOR for new functionality, MAJOR for incompatible changes. During `0.x`, document breaking changes explicitly and increase the minor version.
2. Update `VERSION` and move completed `Unreleased` entries into a matching dated section in `CHANGELOG.md`; leave an empty `Unreleased` section for future work.
3. Review deployment and rollback requirements in `DEPLOY_CPANEL.md` and `docs/migration/rollback.md`. Record migrations, required configuration and recovery instructions in the release notes.
4. Run all checks, review the release changes through a pull request, and merge them into `main`.
5. From the clean, reviewed `main` commit, create an annotated tag matching `VERSION`. For example, when `VERSION` contains `0.1.1`:

```powershell
git switch main
git pull --ff-only origin main
git status --short
git tag -a v0.1.1 -m "BookMyStyle 0.1.1"
git push origin main
git push origin v0.1.1
```

Stop if the working tree is dirty or the checks fail. Push only the intended release tag; existing historical checkpoint tags are not application releases. Never move or overwrite a published release tag. Correct a published release with a new version.

Create a GitHub Release for that tag using the matching changelog entry. Mark development releases as prereleases until production-readiness work has been completed. Releases and tags do not deploy automatically; follow the deployment instructions separately.

## Connect the private repository

Create an empty private GitHub repository named `bookmystyle`, without generated README, license or gitignore files, so the existing history remains authoritative. Add its reviewed SSH or HTTPS URL as `origin`, then push `main`. Never embed credentials in the remote URL. Keep credentials, database exports and uploads outside Git as described in the README.

After the first successful GitHub workflow runs, configure branch protection for `main` and require the test and formatting checks. No GitHub repository or release is created by these instructions alone.
