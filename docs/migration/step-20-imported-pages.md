# Step twenty: native pages with imported data

The native GET routes now have repeatable checks against a fresh isolated MySQL copy of the imported database. The original connection is guarded against application writes, and all-table record fingerprints must match before and after verification. No migrations or seeds run against the source.

Run locally with PHP and the installed MySQL client paths:

```powershell
php -d memory_limit=512M scripts/migration/verify-imported-pages.php 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysqldump.exe' 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysql.exe'
```

The tool delegates backup, fresh restore, index migration, rollback and reapplication verification to `verify-index-migrations.php`. The child reports only its private artifact directory name; the target database remains in a private file. Named connection configuration strips the source routing name and verifies the schema resolver points to the copy before rendering. Both tools accept only the local loopback MySQL workspace.

Results: 74 page checks passed; four public salon detail pages rendered. All ten imported accounts were checked: two administrators, five owners, two customers and one without these roles. All ten stored password hashes were recognized as bcrypt; actual passwords were not inspected or exercised. Eleven owner panel responses matched expected verification, approval or subscription gates. An approved owner's inactive subscription requires a 403 for bookings while permitting the recovery dashboard and read access to salons and subscriptions.

Checks cover public lists/information, sitemap/robots/login, profile and role-dependent dashboard routes, customer account pages, and administrator/owner resource lists. Known dashboard and email-verification redirects require the expected destination. Other denied panel responses accept the existing 302/403 gates. The checker resets authentication, session and Livewire redirect state between probes to represent fresh PHP requests; a denied Livewire mount must not contaminate later probes.

Authentication is injected into the isolated application. These checks verify rendering and middleware behavior with existing records, not password login, OAuth, browser JavaScript, visual parity, external delivery, payment integration or every resource mutation. Earlier isolated Livewire/PHP tests and step-sixteen cached browser evidence provide separate coverage. Staging and host release gates remain pending.

Mail/cache/session use array drivers, compiled views and application logs use the private fixture directory, and maintenance flags are disabled. Public visit tracking can write only to the copy. Source record fingerprints matched at successful completion. Application-level source guards do not replace restricted database credentials, and fingerprints cannot establish that an external writer made no transient changes.

Private evidence: `.local/migration-baseline/imported-pages-20261014_130239_0da1d7/summary.json`, rendered HTML and restore reports. HTML/logs can contain personal information and must remain outside version control. Copy databases, backups and failed rehearsal artifacts are retained for review; this tool does not delete them automatically.

Checkpoint: `migration-step-20-imported-pages`. Private rollback bundle: `.local/migration-baseline/step-20-imported-pages.bundle`. Reverting the verification scripts/docs needs no imported database restoration. Unrelated image deletions and local files remain excluded.
