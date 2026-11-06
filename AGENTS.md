# AGENTS.md

Instructions for AI coding agents and human contributors. Read this fully
before changing code. Details live in `docs/`, this file is the contract.

## Project

Symfony 7.4 LTS JSON API for the Habit Focus mobile app. PHP 8.4 in Docker and
CI (minimum 8.2, so do not use 8.3/8.4-only syntax in new code unless the
`composer.json` PHP constraint is raised too). PostgreSQL 17. No HTML, no
sessions, no cookies: stateless JWT API.

## Commands

Everything goes through `make`; run `make help` for the list.

| Task | Command |
| ---- | ------- |
| Start dev database | `make up` |
| Run all checks CI runs | `make check` |
| Unit tests only (no database) | `make test-unit` |
| Fix code style | `make cs-fix` |
| New migration from entity changes | `make db-diff` |

A change is done only when `make check` is green. Never skip or weaken a
check to make it pass; fix the cause.

## Architecture

Feature-first modules, each split in four layers:

```
src/<Module>/
  Domain/          entities, repository interfaces, domain exceptions
  Application/     use-case handlers, ports (interfaces) to the outside
  Infrastructure/  Doctrine, JWT, rate limiter, console commands
  Ui/Http/         controllers and request DTOs
src/Shared/        cross-cutting: Transaction port, exception listener, headers
```

Dependency rule: `Ui -> Application -> Domain`; `Infrastructure -> Application
/ Domain`. `Domain` and `Application` never import from `Infrastructure` or
`Ui`. `Application` depends on interfaces (repositories, `AccessTokenIssuer`,
`Transaction`), never on Doctrine or Lexik directly. Domain entities may carry
Doctrine mapping attributes (pragmatic choice, see `docs/architecture.md`).

Modules talk through Application classes or Domain interfaces, never through
another module's Infrastructure.

## Coding rules

* Every PHP file starts with `declare(strict_types=1);`.
* Classes are `final` (and `readonly` when they hold only constructor state),
  unless they are Doctrine entities (they must stay extensible for proxies).
* One use case = one invokable handler class in `Application/`
  (`LoginHandler::__invoke`). Controllers stay thin: rate limit, call a
  handler, build the response. No business logic in controllers.
* Controllers are invokable, declared with `#[AsController]` and `#[Route]`.
* Request bodies are mapped with `#[MapRequestPayload]` into a `readonly` DTO in
  `Ui/Http/Request/` with `Assert` constraints. Validate at the edge, trust
  types inside.
* Domain errors are exceptions in `Domain/Exception/`. To expose one over HTTP
  add it to `ApiExceptionListener::DOMAIN_ERRORS`. Never build error JSON by hand
  in a controller.
* Time comes from `Symfony\Component\Clock\ClockInterface`, never `new
  \DateTimeImmutable()` or `time()` in logic that has to be tested.
* Identifiers are UUID v7 (`Symfony\Component\Uid\Uuid::v7()`).
* Interfaces are bound explicitly in `config/services.yaml`.
* Comments explain why, not what. Public classes get a short docblock.

## Database

* Schema changes always go through a **generated** migration:
  change the entity, run `make db-diff`, review the SQL, commit it. Never edit
  an already merged migration, add a new one.
* Never call `doctrine:schema:update` outside throwaway experiments.
* Migrations must be safe to run on a populated table (add nullable column,
  backfill, then constrain).
* Repository interfaces live in `Domain/`; Doctrine implementations in
  `Infrastructure/Doctrine/`. Handlers never see `EntityManager`.

## Security rules (non-negotiable)

* Never log or return passwords, password hashes, access tokens or refresh
  tokens. Tests must not print them either.
* Refresh tokens are stored only as SHA-256 hash and are single use. Do not
  change the rotation or reuse-detection logic without updating
  `docs/adr/0002-refresh-token-rotation.md` and its tests.
* Login must answer identically for unknown email and wrong password (same
  exception, same message, similar timing). Do not add distinguishing
  behaviour.
* Every new public endpoint needs a rate limiter (`config/packages/rate_limiter.yaml`
  and `AuthRateLimiter`), input length limits and a functional test for
  the rejection cases.
* Every new protected endpoint must be reachable only with a valid access
  token (default `access_control` already enforces it) and must scope data to
  the current user (`#[CurrentUser]`); add a test that user A cannot read
  user B's data.
* No secrets in git. `.env` holds only harmless development defaults; real
  values come from the environment.
* Do not add dependencies without a reason; run `composer audit` afterwards.

## Testing

* `tests/Unit`: pure logic with in-memory fakes from `tests/Support`
  (`AuthFixture` wires the handlers). No kernel, no database, must stay fast.
* `tests/Functional`: real HTTP through `WebTestCase` against the Postgres
  test database (`app_test`). Extend `ApiTestCase`, which empties tables
  before each test.
* Every bug fix starts with a failing test. Every endpoint has tests for the
  happy path, validation errors, auth errors and edge cases.
* Test names describe behaviour: `testReusedRefreshTokenRevokesTheSession`.
* Use the cheap password hasher from `when@test` config; never lower hashing
  cost outside the test environment.

## API contract

The mobile app depends on `docs/api.md` and `docs/openapi.yaml`. The login and
refresh responses must keep `accessToken` and `refreshToken` (extra fields are
allowed). Breaking changes need a new version or a coordinated app release.
Update the docs in the same change as the code.

## Git workflow

Small commits, conventional style (`feat:`, `fix:`, `chore:`, `docs:`,
`test:`, `refactor:`). Work on a branch, open a pull request, merge when CI
is green. Describe the why in the PR.

## Adding a feature: checklist

1. Domain entity or change, plus repository interface if new.
2. Application handler (+ port interfaces if it needs the outside world).
3. Infrastructure implementation and binding in `config/services.yaml`.
4. Request DTO, invokable controller, rate limiter if public.
5. `make db-diff` and review the migration.
6. Unit tests with fakes, functional tests over HTTP.
7. Update `docs/api.md`, `docs/openapi.yaml`, and an ADR if a decision was made.
8. `make check`.
