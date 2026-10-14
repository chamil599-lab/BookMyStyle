# Step 18: retained API token lifecycle

Retained API registration, password login and Google ID-token login now use ApiTokenService to issue tokens with explicit expires_at timestamps. The Sanctum global expiration policy defaults to 1440 minutes (24 hours) and also bounds older tokens that have no explicit expiry. SANCTUM_TOKEN_TTL_MINUTES is clamped to 1..43200 minutes; invalid, zero or negative values become one minute rather than disabling expiration. The actual workspace .env and imported token records are not rewritten by this stage.

The response shape remains compatible with the retained React client. A token older than the effective lifetime is rejected on authentication, with no automatic deletion of its stored record. The retained Axios 401 handler already clears local bearer state and redirects to login. Users holding older API tokens must log in again after this policy is deployed. Native Blade/Filament session lifetime and remember-me behavior are separate and unchanged by token TTL.

The deployment preflight now fails finite_api_token_lifetime if a cached Sanctum setting is null, non-integer or outside the bounds. Deploy with the reviewed environment and regenerate configuration cache; otherwise old cached settings can leave legacy token authentication unbounded. Newly issued tokens still get a finite explicit expiry if an old cache contains null.

Bearer logout deletes only the token used for the request, preserving other device tokens. Session/transient-token callers use the native session logout path rather than invoking delete on a transient token. The controller session branch is tested on a fixture web route; this stage does not enable stateful SPA middleware or change the real API's session-routing topology.

Password change and reset already revoke all bearer tokens through the User password-update hook. Added real-route regressions verify that behavior alongside the current-token logout, valid/expired tokens, age-based legacy rejection, all three issuance paths and TTL configuration bounds. Google verification is mocked with synthetic identity data and notifications are faked; no real sign-in, mail delivery or imported-data mutation is performed.

The bearer ability list remains compatible with existing endpoints; record ownership, verified identity, fresh roles and management retirement remain enforced by existing policies/middleware. This does not introduce automatic refresh tokens or an expired-token pruning job. Any future cleanup of stored tokens needs a reviewed database/operations plan. Expiration reduces bearer exposure but does not eliminate the retained React client's localStorage/XSS risk; native session pages remain the intended frontend.

Official token expiration/revocation behavior: https://laravel.com/docs/12.x/sanctum#token-expiration. Final test results and checkpoint are recorded in verification.md. Live host/OAuth/mail and release approval gates remain open.
