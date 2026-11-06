# Habit Focus API

Backend of the [Habit Focus](https://github.com/AdamDybcio/habit_focus) mobile app.
Symfony 7.4 LTS, PHP 8.4, PostgreSQL 17.

Right now it provides authentication: registration, login, short-lived JWT
access tokens and rotating refresh tokens with reuse detection. Habit endpoints
will be added next to match the app's offline-first sync.

## Highlights

* Access token: RS256 JWT, 15 minutes. Refresh token: opaque 256-bit random
  string, 30 days, stored only as SHA-256 hash, single use, rotated on every
  refresh. Replaying a used token revokes the whole session
  ([ADR 0002](docs/adr/0002-refresh-token-rotation.md)).
* Passwords hashed with Symfony's `auto` hasher (bcrypt/argon2 per platform),
  transparently re-hashed when parameters improve.
* Rate limiting on every public endpoint, uniform error responses
  (RFC 9457 `application/problem+json`), security headers, no user enumeration
  on login.
* Feature-first Clean Architecture (`Domain` / `Application` / `Infrastructure`
  / `Ui` per module), Doctrine migrations, PHPStan level 8, PHP-CS-Fixer,
  unit and functional tests, GitHub Actions CI, FrankenPHP Docker image.

## Quick start

See [SETUP.md](SETUP.md). Short version:

```bash
composer install
make up jwt-keys db-create db-migrate
make serve        # http://127.0.0.1:8000
make check        # lint + code style + PHPStan + tests
```

## API

| Method | Path             | Auth    | Description                            |
| ------ | ---------------- | ------- | -------------------------------------- |
| POST   | `/auth/register` | public  | Create account, returns tokens (201)   |
| POST   | `/auth/login`    | public  | Email + password, returns tokens       |
| POST   | `/auth/refresh`  | public  | Exchange refresh token for a new pair  |
| POST   | `/auth/logout`   | public  | Revoke the session (204, idempotent)   |
| GET    | `/me`            | Bearer  | Profile of the current user            |
| GET    | `/health`        | public  | Liveness probe                         |

Details and examples: [docs/api.md](docs/api.md), machine-readable
[docs/openapi.yaml](docs/openapi.yaml).

## Documentation

* [SETUP.md](SETUP.md) - local development
* [AGENTS.md](AGENTS.md) - rules for AI coding agents (and humans)
* [docs/architecture.md](docs/architecture.md) - structure and dependency rules
* [docs/security.md](docs/security.md) - threat model and decisions
* [docs/deployment.md](docs/deployment.md) - Docker, Caddy, backups
* [docs/adr/](docs/adr) - architecture decision records
* [SECURITY.md](SECURITY.md) - how to report a vulnerability
