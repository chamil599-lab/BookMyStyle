# Current business behavior

This is a code baseline. Differences below require an explicit implementation decision in the shared-services stage.

## Authentication and approval

API registration accepts only customer/owner roles, creates a Sanctum personal token, and initially approves customers but not owners. React stores the token and user payload in localStorage and sends Authorization plus X-Authorization headers. Token expiration is null in `config/sanctum.php`. Google sign-in verifies an ID token through google/apiclient. Password reset uses Laravel's password broker; web reset routes redirect to React.

API admin routes require Sanctum and admin role. Owner routes additionally require owner role, approval, and subscription middleware. Customer routes require authentication but do not require a customer role. React role guards are navigation conveniences, not security boundaries.

Filament uses session authentication and one panel for admin/owner. User::canAccessPanel permits these two roles. The panel includes an approval middleware, email-verification configuration, and Shield. User does not implement MustVerifyEmail; custom middleware is not configured as persistent auth middleware. Only RolePolicy exists in app/Policies. Verify authorization of actions and Livewire calls during migration.

## Booking

Primary public creation: `POST /api/bookings` -> BookingApiController + StoreBookingRequest.

1. Validate salon/service existence, contact fields, date >= today and HH:mm time.
2. Begin transaction and lock the salon record.
3. Accept a service whose created_by OR updated_by matches the salon owner's created_by (not a salon_id relationship).
4. Capacity is max(1, number_of_staff). Duration is at least 30 minutes and rounded up to 30-minute slots.
5. Lock pending/approved bookings on the date. Count existing intervals overlapping the proposed interval; reject at capacity with HTTP 409.
6. Create pending booking, associate user_id if Sanctum authenticates the request, and notify the salon owner.

React BookingForm independently generates slots from hours and checks past times/closing time. The public creation controller does not validate all those browser constraints. Its overlapping-interval count is not a per-instant occupancy calculation; preserve the intended capacity rule after testing adjacent/partially overlapping appointments.

Owner booking CRUD scopes records by owned salons and services, checks package limits, and uses a transaction/conflict helper. It accepts pending/approved/completed/rejected and may create past bookings. Booking model constants and Filament forms only describe pending/approved/rejected. Customer cancellation sets rejected; it checks the customer-scoped query and does not introduce a canceled booking state.

Booking::scopeForCustomer links by user_id OR matching email, without requiring email verification in that scope. Guest bookings therefore need a secure claim/verification policy before account migration.

The old Livewire BookingForm uses a component-held booked-slot list, incorrectly queries services by created_by = salon ID, and has neither equivalent locking nor full date/hour validation. Filament CreateBooking checks subscription quotas but does not call the API capacity validator; its salon/service form relationships need ownership enforcement on submission.

BookingObserver sends status-change emails for approved/rejected. The generic BookingController also sends a status email after saving, so that entry point can duplicate delivery. Some controllers send database notifications separately. Consolidate side effects after successful commit in the shared service.

Public legacy booking read endpoints expose complete records; the generic authenticated status endpoint lacks ownership/role authorization. These are defects to repair, not compatibility requirements.

## Subscriptions

UserSubscription status fields include pending, active, expired, rejected, canceled. Package fields include price/duration, max salons/staff/services/bookings per month, analytics, calendar, priority support, and active/popular ordering flags. A nullable monthly booking maximum means unlimited in User; other quota entry points differ.

API and web subscribe accept bank_transfer/one_pay. Bank transfer requires a transaction reference. Submission creates a pending row with amount 0, null dates, and a package feature snapshot. A plan change overwrites the existing active row and makes it pending, resetting paid amount and dates. Duplicate pending requests are checked without a shared transaction lock.

API admin activation permits pending/canceled/rejected/expired, begins now, ends after package duration, and sets paid amount to package price. Reject permits pending -> rejected; cancel permits active -> canceled.

Filament approval accepts a manually verified amount. Renewal approval can start at the parent's future end date, instead of now. Its reject action writes canceled, unlike the API. Filament renewal creates a separate pending row with renewed_from, unlike active-row plan changes.

User::activeSubscription checks active + ends_at > now and chooses latest; it does not check starts_at <= now. A future approved renewal can therefore be selected before it begins. API EnsureSubscriptionActive additionally requires daysRemaining() > 0, potentially blocking the final fractional day earlier than the model. Keep one agreed rule in the new service.

API subscription endpoints are explicitly exempted from active-subscription enforcement so expired owners can view packages, subscribe, and read history. Approval middleware still runs. Preserve this recovery access.

Limits are applied inconsistently: User helpers use live package values; OwnerManagementController has separate quota checks; legacy CheckSubscription references max_daily_bookings/max_monthly_bookings, which are not current package fields. Decide whether purchases retain features_snapshot or use current package values, and apply one policy everywhere.

## Financial and scheduled behavior

AdminShareCalculator calculates 5% of the current service price for approved bookings and compares it with AdminShare.paid. This is calculated revenue/commission, not confirmed payment settlement. Review dashboard date filters and approved/completed treatment before declaring parity.

The older app/Console/Kernel defines featured:purge-expired hourly and subscriptions:notify-expiring daily at 09:00. The booted Laravel 12 scheduler currently reports zero events. The newer bootstrap uses routes/console.php and does not bind this old kernel. Do not assume reminders/purges are currently scheduled. The configured application timezone is Asia/Colombo.
