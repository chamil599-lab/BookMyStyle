# Favorites on salon cards

Salon cards now have a 44px heart control above the cover image, independent of the salon link. Saved state is loaded with a correlated favorite lookup in the card query, avoiding per-card queries. Authenticated verified users can save or remove in place; guests are directed to sign in and unverified accounts to email verification.

The web endpoint accepts idempotent POST/save and DELETE/remove requests, requires authentication and verified email, retains CSRF protection and throttles requests. It uses the existing transaction/ownership service, permits saving only publicly visible salons and changes only the requesting user's records. Responses are private and not cached. Button state changes after a successful response; duplicate clicks are disabled while pending and errors are displayed without a false saved state.

Validation: production build and 48 public/customer tests with 304 assertions, including idempotency, ownership, authentication, verification and owner-approval checks. Seven isolated browser checks passed with zero JavaScript errors, covering guest navigation, save persistence, favorite controls after instant filtering and the saved-salons list. Artifacts remain private under `.local/migration-baseline/auth-smoke-20261015_022026_69a1a2/salon-card-favorite-summary.json`.
