# 13 - API security

Checked 16 October 2026. Reviewed API authentication/authorization boundaries passed locally. No production application changes were needed.

Reviewed retained API routes, public versus authenticated operations, Sanctum configuration/token issuance, alternate authorization headers, middleware separation, CORS and private identity responses. APIs use bearer tokens without StartSession or Sanctum stateful SPA middleware. Public catalogue/statistics and anonymous booking submission are intentional public functions; public writes have rate limits. Retained booking management endpoints require verified authorized roles, approval/subscription checks where applicable and record policies. Retired management endpoints do not replay writes.

Tokens have a bounded global TTL (default 1440 minutes, configuration constrained to 1-43200), and new tokens have expires_at. Logout revokes the presented token; password changes/resets revoke all account tokens. Tokens are stored as hashes by Sanctum. Standard Authorization takes precedence over the compatibility X-Authorization header. Query-string token/access_token values are not authentication sources. Identity responses omit passwords/remember credentials and use private/no-store headers.

Added six cases covering URL token rejection; conflicting authorization-header precedence; alternate-header authentication and identity payload hygiene; a real database session cookie failing to authenticate the API; and approved/unapproved CORS preflights without Access-Control-Allow-Credentials. With one allowed origin, the installed CORS implementation may send that fixed origin even for an unapproved caller; it does not reflect the attacker origin or wildcard, so the browser does not grant access to that caller.

Validation: **36 tests passed, 143 assertions**, across ApiBoundarySecurityTest, ApiTokenLifecycleTest, ApiAccessTest, ProductionCorsTest and AuthResponsePrivacyTest. Existing cases cover explicit/legacy token expiry, logout/password revocation, owner/customer/admin boundaries, foreign salon records, verified guest booking claims, profile verification reset and reduced public data. Synthetic SQLite fixtures only; imported MySQL data was untouched.

Limits: issued tokens currently have wildcard abilities and rely on account roles/policies for authorization; this is not a scoped third-party integration token system. CORS is browser response-access policy, not authentication or a restriction on non-browser clients. Tests simulate requests in Laravel and do not certify production HTTPS, proxy/header forwarding, CORS environment overrides, request-size limits, edge throttles or mobile credential storage. Anonymous booking spam/business limits and broader data exposure remain separately reviewed categories.

Reference: [OWASP REST security guidance](https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html). Next category: business-rule security.
