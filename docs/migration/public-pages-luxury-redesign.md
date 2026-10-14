# Remaining public pages: luxury design

The luxury styling now covers the salon directory, trending/popular lists, service collection, deals, about, privacy and terms pages. These eight routes share the home/salon header, typography, ivory surfaces, deep-green actions and gold accents. Dark mode uses black backgrounds and charcoal panels throughout.

Directory/editorial pages gain a structured introductory heading, refined filters and cards. Ranked directories use a three-column filter layout when no sort control is present. The about page has clearer statistics, mission/value blocks, team cards and a salon-owner invitation. Legal pages use a restrained reading surface and the shared typography/buttons; legal wording and dates are unchanged.

Theme application is limited to these public routes. Filter styling is scoped inside main content so authenticated navigation/logout forms retain their intended layout. Existing query validation, filtering/ranking, pagination, content visibility, escaped text, safe links/images, published pricing and actual statistics are retained. No React, dependency, backend service, database or environment changes were introduced. Authentication, account and Filament screens are outside this public-page design update.

Verification:

- Vite build passed. Final public-page tests: 29 passed, 137 assertions.
- Cached synthetic SQLite HTTP/Chrome runner: 94 HTTP checks and 80 browser checks passed, zero uncaught JavaScript errors, 50 screenshots. Each updated route is checked for the luxury theme, 320px horizontal overflow and computed black dark background, alongside the existing homepage/salon and customer/management workflows.
- Private evidence: `.local/migration-baseline/auth-smoke-20261014_175155_098bcf/`. Desktop/mobile/dark previews use the route name in their filenames. Logs: `.local/migration-baseline/public-luxury-build.log` and `public-luxury-tests-final.log`.
- Node syntax and authored Git whitespace checks passed. No production deployment or imported database changes occurred; existing unrelated image deletions remain untouched.

Checkpoint: `public-pages-clean-luxury`. Private rollback bundle: `.local/migration-baseline/public-pages-clean-luxury.bundle`. Revert source/style/test changes without restoring data. Actual host/integration verification remains separate.
