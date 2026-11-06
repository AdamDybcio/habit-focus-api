# Setup

Guide for running the API locally.

## Requirements

* PHP 8.2 or newer (CI and the Docker image use 8.4) with extensions `intl`,
  `pdo_pgsql`, `zip`, `opcache`
* [Composer](https://getcomposer.org/) 2
* Docker with Compose (for PostgreSQL)
* `make`

## 1. Install dependencies

```bash
composer install
```

## 2. Configure

Defaults in `.env` work for local development, nothing to change. To override
anything, create `.env.local` (git-ignored). Never put real secrets in `.env`.

## 3. Database and keys

```bash
make up          # PostgreSQL 17 in Docker on 127.0.0.1:5432
make jwt-keys    # RSA key pair in config/jwt (git-ignored)
make db-create   # app and app_test databases
make db-migrate  # apply migrations to both
```

## 4. Run

```bash
make serve       # http://127.0.0.1:8000
curl -s http://127.0.0.1:8000/health
```

Try the flow:

```bash
curl -s -X POST http://127.0.0.1:8000/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"email":"ada@example.com","password":"correct horse battery"}'
```

The response contains `accessToken` and `refreshToken`. Use the access token
with `Authorization: Bearer <token>` on `GET /me`.

To use the API from a phone on the same network, bind to all interfaces
(`php -S 0.0.0.0:8000 -t public`) and put `http://<your-computer-ip>:8000` in
the app's `config/dev.json`. Plain HTTP is for local development only.

## 5. Tests and checks

```bash
make test           # unit + functional (needs the database from step 3)
make test-unit      # fast, no database
make check          # everything CI runs
```

## Common problems

| Symptom | Fix |
| ------- | --- |
| `Connection refused` on port 5432 | `make up`, then wait until the container is healthy |
| `database "app_test" does not exist` | `make db-create db-migrate` |
| `Unable to create a signed JWT` | `make jwt-keys`; the passphrase must match `JWT_PASSPHRASE` |
| `could not find driver` | enable the `pdo_pgsql` PHP extension |
| PHPStan: container XML not found | `php bin/console cache:warmup --env=dev` (`make stan` does it) |
