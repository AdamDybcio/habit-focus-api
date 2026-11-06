# ADR 0002: Opaque rotating refresh tokens with reuse detection

Status: accepted

## Context

Mobile apps need long sessions without keeping a long-lived bearer token that
works everywhere. Options: long-lived JWT, JWT refresh tokens, opaque refresh
tokens in the database.

## Decision

* Access token: JWT RS256, 15 minutes, stateless.
* Refresh token: opaque random 256-bit value, 30 days, stored as SHA-256 hash
  with `family_id`, `expires_at`, `used_at`, `revoked_at`.
* Every refresh marks the token used and issues a new one in the same family
  (rotation).
* Presenting a used token revokes the whole family (reuse detection).
* The token row is locked with `SELECT ... FOR UPDATE` while rotating.
* Logout revokes the family.

## Why not the alternatives

* Long-lived JWT: cannot be revoked, a leak is valid for weeks.
* JWT as refresh token: no benefit over a random string, and it still needs
  database state to be revocable.

## Consequences

* A stolen refresh token is detected as soon as either party uses it
  after the other; the session ends.
* If a refresh response is lost after rotation, the app's retry looks like
  reuse and the user must log in again. We accept this to keep detection
  strict. A grace period for the previous token would fix it but lets a thief
  race the user.
* One extra database write per refresh (every 15 minutes per active device).
* Expired tokens are purged daily (`app:refresh-tokens:purge`).
* Revoking access tokens before expiry is not supported.
