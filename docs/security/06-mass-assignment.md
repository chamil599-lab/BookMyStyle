# 06 - Mass assignment

Checked 16 October 2026. No exploitable assignment of protected fields was found in the reviewed write paths. No production code changes were required.

Reviewed request-to-model writes in API controllers, shared account/booking/catalogue/subscription/review/favorite services, Blade Livewire profile/password forms and Filament management pages. Writes use validated allowlists, explicit field construction or shared services that filter input again. API registration passes all request input into AccountService, but that service validates allowed fields and constructs the User attributes explicitly before persistence. Password-reset forceFill calls construct a fixed password/remember-token array; they do not bind arbitrary input.

Several models intentionally include sensitive attributes in fillable for trusted internal operations. Model fillable alone is therefore not the security boundary: request and service allowlists must remain in place. No direct model create/update/fill using unfiltered request all() was found in the reviewed application paths. User management intentionally allows authorized administrators to edit approval and assign roles; public profile updates do not.

Added three regression tests: public booking API payloads containing forged primary key, user_id, status and timestamps; pending subscription payment edits containing forged owner, package, active status, amount, trial flag, dates and feature snapshot; and catalogue service edits containing forged ownership, primary key, timestamp and deletion fields. Allowed changes persist while protected values remain server-controlled. Existing tests cover registration/profile privilege injection, Filament salon/service ownership, booking actor/status enforcement, and subscription history protection.

Validation: **64 tests passed, 225 assertions** across SharedServicesTest, FilamentManagementTest and PrivilegeEscalationTest. Tests used synthetic SQLite fixtures; imported MySQL data was untouched. This is a local source review and regression check, not production penetration-test certification.

Reference: [OWASP mass assignment guidance](https://cheatsheetseries.owasp.org/cheatsheets/Mass_Assignment_Cheat_Sheet.html). Next category: authentication security.
