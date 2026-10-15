# Customer workspace redesign

The customer overview now shares the public site's editorial typography, green accents and refined surfaces, with a pure white light-mode background and black dark mode. A responsive account sidebar provides route-aware navigation and a customer identity block. The overview adds a welcome panel, five statistics cards, upcoming appointments and shortcuts to saved salons and booking history.

Shared booking cards have clearer salon/service hierarchy, readable status badges, appointment details and booking references. Existing cancellation confirmation and server actions remain intact. The shared workspace layout applies to upcoming bookings, history, favorites and notifications; profile/password destinations retain their existing forms.

All customer data sources, ownership checks, private/no-store response headers, price visibility checks, form methods and CSRF protection are unchanged. No database changes or imported-data writes are involved.

Validation: production Vite build; 46 public/customer feature tests with 287 assertions; 15 isolated customer browser checks covering login, statistics, active navigation, dark mode, 320px bounds, cancellation, history, saved-salon removal and notification actions, with zero JavaScript errors. A final preview also verified the compact mobile header after a spacing adjustment. Desktop, dark and mobile screenshots were reviewed. Browser artifacts remain private under `.local/migration-baseline/auth-smoke-20261015_022026_69a1a2`.

The initial broader smoke run stopped at the existing mobile menu Escape assertion before reaching the customer flow. That assertion now waits for Alpine to apply visibility and focus updates. Customer flows were then exercised directly against the isolated fixture; the broader run is not recorded as passing for this change.
