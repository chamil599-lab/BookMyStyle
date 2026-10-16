# 04 - IDOR / broken access control

Checked 16 October 2026. No exploitable cross-account record access was found in the reviewed paths. No production application changes were needed.

Reviewed customer browser/API booking queries and cancellation, account profile targeting, favorites, notification UUID operations, owner booking APIs, Filament salon/service/booking/subscription resource queries, calendar event resolution and shared write-service authorization. Customer and favorite identities come from the authenticated actor. Notification lookups use that actor's notification relationship. Owner resources scope by created_by, salon ownership or subscription user_id; policies also enforce object ownership. Booking cancellation rechecks the customer scope under a transaction lock. Catalogue updates reauthorize locked records. Installed Filament bulk-selection code resolves selected IDs through the configured table query; booking bulk actions additionally authorize individual records.

Verified guest-booking behavior: a verified matching email may access an unclaimed booking only when user_id is null. Matching email does not override another account's user_id. Public salon pages are intentionally accessible; hidden/unapproved salon visibility is separately covered by the public-page tests.

Added three regression tests: forged customer identity in the legacy booking-list body and foreign booking cancellation; foreign notification UUIDs through browser/API read/delete plus bulk actions; and foreign owner booking APIs with booking/subscription resource scoping. Denied mutations preserve the foreign records. Existing tests also cover owner direct salon/service edit URLs, calendar event identifiers, favorite isolation, revoked approval and customer role gates.

Validation: customer and management suites passed **41 tests, 270 assertions**; the added owner API/resource test was then run separately and passed **1 test, 5 assertions**. Total verified: **42 tests, 275 assertions** across those runs. Synthetic SQLite fixtures only; imported MySQL data was untouched. This is a local source review and regression audit, not a claim that every deployed access path has been penetration-tested.

Method follows [OWASP IDOR prevention guidance](https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html): use multiple accounts and attempt foreign record references, checking denial and unchanged state. Next category: privilege escalation.
