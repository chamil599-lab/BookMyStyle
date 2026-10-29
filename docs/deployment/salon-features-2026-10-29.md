# Prepared cPanel update: closed dates and map links

Prepared on 29 October 2026. Target: `https://bookmystyle.limach.lk`.

## Git and deployment state

The feature branch `feature/salon-map-url` is pushed to the private GitHub repository. [Draft PR #2](https://github.com/chamil599-lab/BookMyStyle/pull/2) is open against `main`. It includes the prior cPanel checkpoint, both features, reference documentation, upgrade tooling and the targeted dependency security patch. Review the branch base/commit range before merging. No merge, release tag or production update is claimed.

The user selected cPanel File Manager and temporary Cron Jobs for rollout. There is no connected cPanel/SSH deployment session. The hosted `/up` endpoint returned HTTP 200 before rollout; this is a baseline health check, not proof the features are deployed.

## Exact candidate and local artifacts

The upload bundle was built from clean committed source:

```text
536b0ce8317423e1afc5868f6919a4991039f632
```

The base checkpoint for the patch is `3e28f89`, the recorded source of the existing cPanel installation. The clean build checkout is `.worktrees/cpanel-salon-release`; unrelated working-tree edits were not copied into the patch.

Local output: `.local/salon-features-update-2026-10-29/`. Archives remain private local build artifacts and are not committed to Git.

| Archive | Bytes | Entries | Extraction destination |
| --- | ---: | ---: | --- |
| `01-salon-update-prepare.zip` | 9,829 | 4 | `/home/limachlk/bookmystyle` |
| `02-salon-update-application.zip` | 265,677 | 106 | `/home/limachlk/bookmystyle` |
| `03-salon-update-public.zip` | 204,998 | 6 | `/home/limachlk/public_html/bookmystyle.limach.lk` |

```text
8b15b5eb03e4e9154a7962435492bb794f0c77fa5373420e503bbc0c0fbb6f38  01-salon-update-prepare.zip
63085ffed927c6ad0bc5ffb6129996d3e91357352a35406b61671815a0900b68  02-salon-update-application.zip
85b07d1f75fbc693294fc9f22c398f7adb8f8a7edfc224b071325a1f977ebd18  03-salon-update-public.zip
```

The output also contains `READ-ME-FIRST.md`, the file-hash manifest and `SHA256SUMS.txt`. The manifest identifies the exact candidate source and every feature/dependency/public file to verify on the host.

## Included changes

- Full-day closed-date selection and date ranges; active-appointment conflict checks; shared booking enforcement; customer calendar restrictions; API availability metadata.
- Owner-managed Map URL; public map icon/link; address fallback; resolved URL in the public salon API.
- Only the two feature migrations: `salon_closed_dates` and nullable `salons.map_url`.
- Symfony Mime 7.4.19 to 7.4.22 for CVE-2026-45070, discovered in the fresh release audit. Other locked dependency versions are unchanged. The patch includes only that library, the lockfile and matching production Composer metadata/autoload files, rather than replacing the whole dependency directory.
- Private CLI upgrade helper and upload instructions. It preserves existing configuration/key/data and requires maintenance for migrations/preparation.

## Validation

- Final clean-source full Pest suite after the security patch: **491 passed, one Windows symlink test skipped, 2,868 assertions**. The previously reported 492-test run was against the main local folder, which also contained unrelated edits; the release candidate deliberately excludes those edits.
- Focused feature/upgrade tests: **13 passed, 78 assertions**, including repeatable additive migrations preserving existing records and helper path rejection.
- Clean-source repository-wide Pint and Vite production build passed.
- Composer validation passed with the existing exact-version warning for Guava Calendar. Composer and npm audits passed after the Mime patch.
- Local production dependency platform requirements passed. The bundle uses production-only Composer metadata with PSR-4 fallback. Optimized autoload scanning stalled on Windows, so a normal production PSR-4 loader was generated instead; the committed Composer configuration was restored unchanged.
- Guarded in-memory smoke with production-only dependencies passed migrations, owner closure save, public map rendering, working-hours closure metadata, homepage/login/salon/API HTTP checks, and configuration/route/view cache generation.
- All archive entries were reread and verified. Independent exclusion checks found no credentials, database exports, runtime data, Composer temporary downloads or unexpected public scripts.
- An initial test attempt overlapped the asset build and failed two missing-manifest checks; rerunning the full suite after the build and security patch produced the successful result above. The failed intermediate archives were removed and replaced by the verified hashes recorded here.

These are local checks, not evidence of production MySQL migration success, backup restoration, live owner UI interaction or post-update health. The production-target helper itself has not run on the host.

## Next action and rollout sequence

Follow [the exact cPanel update instructions](../../deployment/cpanel/SALON-FEATURES-UPDATE.md). After Git review and a hosted backup, upload only archive 01 first and run the private helper with `--check` through a temporary cron. Read the matching private report before continuing. Then use `--down`, `--migrate`, upload archives 02/03 while in maintenance, and use `--prepare` to verify files, refresh caches and reopen. Remove each temporary cron after success.

Do not use the fresh-install helper or rotate `APP_KEY`. Retain the additive feature schema/data for source rollback. Deployment completion must be recorded only after the user performs the cPanel steps and supplies the corresponding non-secret report/verification results.
