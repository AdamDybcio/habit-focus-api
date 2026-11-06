# Security

Threat model and decisions, organised by the OWASP API Security Top 10 (2023).
"Status" says what is done and what is a known trade-off.

## Assets

User credentials, refresh tokens (they grant long-lived access), personal data
(email), later: habit data.

## Controls

| Risk | Control | Where |
| ---- | ------- | ----- |
| API1 Broken object level authorization | Every future data endpoint scopes queries to `#[CurrentUser]`; rule in AGENTS.md; a test per endpoint that user A cannot read user B | planned with habit endpoints |
| API2 Broken authentication | Short access JWT (RS256), single-use rotating refresh tokens with reuse detection, password hashing with `auto` hasher and rehash, login rate limits per IP and per email, identical answer and timing for unknown email | `Auth/` |
| API3 Broken object property level authorization | Responses are built by hand from explicit fields, entities are never serialized; request DTOs list allowed fields | controllers |
| API4 Unrestricted resource consumption | Rate limiter on public endpoints, length limits on every input (email 180, password 128, token 512), memory limit | `rate_limiter.yaml`, DTOs |
| API5 Broken function level authorization | Single role today; default `access_control` denies everything except the public firewall | `security.yaml` |
| API6 Unrestricted access to sensitive business flows | Registration limited to 5 per hour per IP | `AuthRateLimiter` |
| API7 SSRF | The API makes no outgoing requests | n/a |
| API8 Security misconfiguration | Security headers on all responses, debug off in prod, problem+json without internals, DB and app bound to localhost, non-root container, `no-new-privileges`, trusted proxies configured explicitly | `SecurityHeadersListener`, `compose.prod.yaml` |
| API9 Improper inventory management | One versionless API, documented in `openapi.yaml`, health endpoint is the only extra | docs |
| API10 Unsafe consumption of APIs | No third-party APIs consumed | n/a |

## Tokens in detail

* Refresh token: 256 random bits from `random_bytes`, URL-safe base64. Stored as
  SHA-256 hash: a database leak does not leak usable tokens. A fast hash is
  correct here because the input is high entropy (unlike passwords).
* Rotation: the old token is marked used and a new one is issued in the same
  *family*. Reuse of a used token revokes the whole family.
* Concurrency: the token row is locked (`SELECT ... FOR UPDATE`) inside a
  transaction, so two parallel refreshes cannot both succeed.
* Revocation after reuse is committed, and the exception is raised after the
  commit. Raising inside the transaction would roll the revocation back.
* Access token: only `sub` (user UUID), `iat`, `exp`. Short life is the
  revocation strategy: a stolen access token is useless after 15 minutes.
  Immediate access-token revocation is intentionally not supported.
* Keys: RSA key pair on disk, private key encrypted with a passphrase, mounted
  read-only into the container, never in git.

## Known trade-offs

1. **Email enumeration on register.** `409` tells that an email exists. A
   neutral response would need email verification. Accepted for a demo,
   mitigated by the register rate limit.
2. **Lost refresh response.** If the server rotated a token but the response
   never reached the app, the retry is treated as reuse and the session ends.
   Safer by default; the user logs in again. A grace window would weaken theft detection.
3. **Rate limiter without locks, state per instance.** `lock_factory: null`
   makes counting slightly inexact under heavy concurrency, and counters are in
   the app's cache (per container). Fine for one instance; use Redis for several.
4. **Access JWTs cannot be revoked** before expiry (see above).
5. **Lexik 401 body** differs from problem+json (see architecture.md).
6. **No email verification, no password reset, no MFA.** Out of scope for now.
7. **Passwords are not checked against breach lists.** Only length rules.

## Operational security

* `composer audit` runs in CI; Dependabot opens weekly update PRs.
* Secrets only in environment/`.env.prod.local` on the server (mode 600).
* Logs must not contain tokens: handlers never log request bodies.
* Back up the database (see deployment.md); rotating `APP_SECRET` is safe, but
  replacing the JWT key pair logs everyone out of access tokens (refresh
  tokens keep working, so sessions survive).
