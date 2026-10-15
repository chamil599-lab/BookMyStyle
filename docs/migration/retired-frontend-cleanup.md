# Retired frontend cleanup

The active application uses Blade/Livewire for public and customer pages, Filament for owner/admin management, and the single root Vite build. The retired `client/` React project (source, installed packages and build output) and unused `frontend/` Tailwind scaffold were removed. Earlier source remains recoverable from Git history.

Also removed: the React-only browser/config verification scripts, obsolete React deployment guide, unused Livewire Home/Salons/SalonDetail/SearchBar prototypes whose views no longer existed, the downloaded Composer installer, and eight empty stray files from the project root. Previously absent retired public/frontend images are recorded as deleted. Active images, video, uploads, backend/API code and migration evidence remain.

Unused root packages Axios, Font Awesome and the unused Tailwind Vite plugin were uninstalled with the lockfile updated, removing 29 packages. Vite, Tailwind's PostCSS integration, Chart.js and the Composer development runner are retained. Native deployment notes and example environment files no longer instruct developers to configure a separate frontend URL. Historical migration documents are identified as historical evidence.

Preserved privately in the ignored `.local/migration-baseline/cleanup-20261015/` directory: the retired React environment file, the existing app.zip backup and the local composer.phar binary. Composer was not available as a global command, so its local binary was preserved. Databases, application credentials, media, private backups and Git history were not removed.

Validation: root production Vite build and authored whitespace checks passed. The full PHP suite reported 273 passing tests, one Windows symlink permission skip, and one outdated homepage assertion looking for the former uppercase logo. That assertion now checks BookMyStyle and its targeted rerun passed. No application failure was found.

The existing Composer class map was inspected and contains no stale entries for the removed prototype classes. An optional autoload regeneration was stopped after it remained slow, without lifecycle scripts or plugins; the existing autoloader successfully ran the full suite. No Composer dependencies changed. No migrations or imported-database writes are part of this cleanup.
