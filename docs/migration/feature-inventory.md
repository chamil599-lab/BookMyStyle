# Feature inventory and parity checklist

Every unchecked item is a migration acceptance criterion, not a claim that the current feature has passed runtime testing. Preserve visual design and meaningful behavior; security defects in the risk register are exceptions that must be fixed.

## Active React pages

All page paths below come from `client/src/main.jsx`; page modules are under `client/src/layouts/`.

| Current URL | Source module | Main behavior | Destination |
| --- | --- | --- | --- |
| `/` | Homepage.jsx | Salons, categories, statistics, hero image cycle | Blade + reusable components |
| `/salons` | Salons.jsx | Salon directory, filtering, search | Blade + Livewire |
| `/salon/:id` | SalonDetail.jsx | Gallery, services, contact/map, hours, reviews, booking | Blade + Livewire |
| `/services` | Services.jsx | Managed service-page content | Blade |
| `/deals` | Deals.jsx | Managed deals content | Blade |
| `/about` | Aboutpage.jsx | About content, statistics, team | Blade |
| `/trending` | Trending.jsx | Trending salon list | Blade |
| `/popular` | MostPopular.jsx | Popular salon list | Blade |
| `/terms-and-condition` | TermsAndConditions.jsx | Terms | Blade |
| `/privacy-policy` | PrivacyPolicy.jsx | Privacy policy | Blade |
| `/login` | LoginPage.jsx | Password and Google login | Session auth + Blade |
| `/register` | RegisterPage.jsx | Account registration | Session auth + Blade |
| `/forgot-password` | Forgotpassword.jsx | Reset-link request | Session auth + Blade |
| `/reset-password` | Resetpassword.jsx | Token/email password reset | Session auth + Blade |
| `/dashboard` | customer/DashboardOverview.jsx | Customer statistics | Blade + Livewire |
| `/dashboard/upcoming` | customer/UpcomingBookings.jsx | Upcoming bookings and cancellation | Blade + Livewire |
| `/dashboard/history` | customer/BookingHistory.jsx | Paginated history | Blade + Livewire |
| `/dashboard/favorites` | customer/FavoriteSalons.jsx | Saved salons | Blade + Livewire |
| `/dashboard/profile` | customer/ProfilePage.jsx | Profile update | Blade + Livewire |
| `/dashboard/change-password` | customer/ChangePassword.jsx | Password update | Blade + Livewire |
| `/admin` | AdminDashboard.jsx | Revenue, visits, subscriptions, packages | Filament admin panel |
| `/admin/bookings` | admin/AdminBookings.jsx | Booking listing/status actions | Filament admin panel |
| `/admin/salons` | admin/AdminSalons.jsx | Salon listing | Filament admin panel |
| `/admin/users` | admin/AdminUsers.jsx | Owner approval/rejection | Filament admin panel |
| `/admin/subscriptions` | admin/AdminSubscriptions.jsx | Subscription approval/status | Filament admin panel |
| `/admin/profile` | admin/AdminProfile.jsx | Profile/password | Filament profile |
| `/admin/packages` | admin/AdminPackages.jsx | Package CRUD | Filament admin panel |
| `/admin/categories` | admin/AdminCategories.jsx | Category CRUD | Filament admin panel |
| `/admin/departments` | admin/AdminDepartments.jsx | Department CRUD | Filament admin panel |
| `/owner` | OwnerDashboard.jsx | Revenue/profit analytics, calendar, bookings | Filament owner panel |
| `/owner/salons` | owner/OwnerSalons.jsx | Salon CRUD, images, hours/contact/social fields | Filament owner panel |
| `/owner/bookings` | owner/OwnerBookings.jsx | Booking CRUD/status, WhatsApp links | Filament owner panel |
| `/owner/services` | owner/OwnerServices.jsx | Service CRUD, departments/categories | Filament owner panel |
| `/owner/subscription` | owner/OwnerSubscription.jsx | Packages, plan requests, history | Filament owner panel |
| `/owner/profile` | owner/OwnerProfile.jsx | Profile/password | Filament profile |

The root Laravel router also contains legacy `/owner` resource routes and session-based `/subscription` routes. Preserve or explicitly redirect existing URLs after choosing final panel paths; React `/admin` and Filament `/filament` are distinct today.

## Existing Filament coverage

Resources under `app/Filament/Admin/Resources`: Bookings, Salons, Services, Users, UserSubscriptions, SubscriptionPackages, Departments, Categories, FeaturedSalons, ImageCycles, ServicePages, DealsPages, TeamMembers. Presence of a resource is not proof of feature parity or correct authorization.

Pages: admin Dashboard, OwnerDashboard, AnalyticsDashboard, PendingApproval, custom Login/Register. Widgets: BookingCalendarWidget, RevenueStats, RevenueMonthlyChart, AdminRevenueStats, OwnerRevenueStat. Validate discovery/visibility and compare calculations, not just their appearance.

Existing Blade includes layouts, account/authentication pages, emails, subscription checkout/required pages, and Filament custom views. `App/Livewire/Frontend/{Home,Salons,SalonDetail}` and `BookingForm` are not production-ready replacements: referenced frontend/booking views are missing and booking rules differ.

## Parity acceptance

- [ ] Public page paths, responsive layouts, navigation, footer, theme preference, loading/error/empty states.
- [ ] Search/filter/sorting, pagination, popular/trending definitions, approved/public salon visibility.
- [ ] Salon images, pricing visibility, service durations, operating hours, staff capacity, map/contact/social links.
- [ ] Guest and signed-in booking, conflict handling, past-time protection, customer account linking.
- [ ] Reviews prove customer identity; private emails are never published.
- [ ] Favorites, customer statistics/history/cancellation/profile/password.
- [ ] Admin and owner listings, filtering, pagination, CRUD, status transitions, exports if present.
- [ ] Owner approval, pending screens, expired-subscription recovery, package quotas and analytics/calendar gates.
- [ ] Renewal/plan-change history and verified amounts/dates follow one agreed policy.
- [ ] Revenue/profit/commission figures match the agreed definitions and date boundaries.
- [ ] Notifications, email delivery, notification read/delete controls, WhatsApp handoff.
- [ ] Canonical links, metadata, JSON-LD, sitemap/robots, deep links, storage URLs, password-reset links.
- [ ] Cross-owner read/write attempts, customer privacy, guest access, revoked approval and expired sessions are tested.

## Visual reference capture

Once a usable database is available, capture desktop and mobile screenshots of public home/list/detail/booking/auth pages and each role's dashboard, including empty and validation-error states. Store any customer-identifying screenshots privately. Use dedicated fixture accounts; do not perform live payments or send customer notifications during review.
