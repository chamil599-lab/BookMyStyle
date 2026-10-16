# 05 - Privilege escalation

Checked 16 October 2026. No exploitable customer-to-owner/admin or owner-to-admin escalation was found in the reviewed paths. No production application changes were required.

Reviewed public browser/API registration, Google account creation and existing-account sign-in, profile updates, role-assignment call sites, user-management Filament pages/actions, Shield role management policy, panel middleware, and shared moderation/subscription authorization. Public roles are limited to customer/owner; newly registered owners remain pending. Existing Google accounts retain their roles and approval. Profile updates accept defined identity fields, not role or approval fields. Administrative user creation/editing authorizes the current actor on the server; moderation re-fetches the actor before authorization. Administrators may intentionally assign administrator roles through protected user-management pages.

The panel requires an owner/admin role, email verification and owner approval, with persistent authentication/approval middleware on Livewire requests. Shield role policy permits administrators only. Shield's configured super_admin role is not publicly assignable, and define_via_gate is false; the reviewed configuration does not establish a super-admin Gate bypass.

Added ten regression cases: three elevated/malformed public registration roles, forged privilege fields in customer and pending-owner API profile updates, customer/owner direct role-management and Livewire user-creation access, customer/owner self-approval calls, and a cached administrator actor whose role has been revoked. Denied operations leave privileges and approval unchanged. Existing authentication and retired-route suites cover Google role preservation, pending owners, role/approval revocation, unverified actors and protected subscription actions.

Validation: **52 tests, 604 assertions** passed together; the subsequently added stale-administrator test passed separately with **1 test, 3 assertions**. Total verified: **53 tests, 607 assertions** across both runs. Tests used synthetic SQLite fixtures; imported MySQL data was untouched. This local review does not certify all deployed configuration or every possible action sequence.

Reference: [OWASP authorization guidance](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html). Next category: mass assignment.
