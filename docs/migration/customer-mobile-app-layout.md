# Customer mobile app layout

At widths below 768px, the customer workspace uses a sticky compact brand/notification/avatar header and fixed five-item bottom navigation: Home, Bookings, Discover, Saved and Account. The desktop sidebar, website header and marketing footer are replaced on these customer pages only. Desktop navigation remains unchanged.

The Account sheet provides profile, password, booking history, notifications, about, privacy, theme and CSRF-protected logout actions. It supports keyboard focus wrapping, Escape and backdrop dismissal. Mobile spacing accounts for display safe areas and leaves padding below the last content item so the bottom navigation does not cover actions. Statistics and welcome cards are more compact on mobile.

This is a responsive Blade interface. It adds no Android package, offline caching or native integration. Existing routes, authentication, ownership checks and customer actions are retained.

Validation: production build; 46 public/customer feature tests (287 assertions); 14 isolated browser checks for desktop layout, 320px fit, fixed navigation, active tabs, Account sheet focus, theme switching and bottom content clearance, with zero JavaScript errors. Screenshots were reviewed. Browser artifacts remain private under `.local/migration-baseline/auth-smoke-20261015_022026_69a1a2/customer-mobile-app-summary.json`.
