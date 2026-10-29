# Changelog

Track user-visible changes here. Versions use MAJOR.MINOR.PATCH; Git release tags use the `v` prefix.

## [Unreleased]

### Added

- Owners can save an optional Map URL; salon pages show a map icon/link and use the saved destination for directions. The API exposes map_url. Requires the add_map_url_to_salons_table migration.

- Salon owners can select full-day closures on a calendar; active appointment conflicts must be resolved first. Closed dates are disabled for website bookings and enforced in shared booking validation. Requires the salon_closed_dates migration; see docs/salon-closed-dates.md.

- Repository version tracking and a documented release process.
- No-Terminal cPanel packaging, private CLI setup, and tested fresh-install administrator/plan initialization without demo accounts.
- Native mobile catalogue, owner/admin APIs, browser-approved mobile login, push-device support, booking reminders, and account deletion information.
- Filament subscription plan/checkout pages and updated booking/reporting views.
- A dated cPanel deployment record with confirmed host paths, PHP CLI, setup evidence and remaining integration checks.

### Changed

- Frontend theme matches Filament blue, with matching blue/white logo and favicons.
- Production configuration template documents current mail, session, reminder and Firebase settings.
- Reporting aggregates and archived-salon booking visibility are updated; salon HTML-stripping expectations are corrected in tests.

### Deployment

- The user uploaded the application to `https://bookmystyle.limach.lk` and confirmed the site loading, configuration/database checks and completed migrations on 21 October 2026.
- This host runs PHP 8.4.26 CLI at `/usr/local/bin/php`; `/usr/bin/php` is CGI/FastCGI.
- Keep existing keys/data/uploads when updating. New push tables require forward migrations; push/reminders require reviewed scheduler, queue and Firebase/mail configuration.
- Mobile production build/device acceptance, live mail/uploads, administrator/preparation verification and backup/restore checks remain open. No version tag or GitHub release is created by this record.

## [0.1.0] - 2026-10-16

### Added

- Initial version-number baseline for the existing Laravel, Blade/Livewire and Filament application.
- Existing booking, salon management and subscription functionality, with repository CI and deployment documentation.

This entry establishes development version tracking. It does not indicate a published release or production-readiness approval. No `v0.1.0` release tag has been created.
