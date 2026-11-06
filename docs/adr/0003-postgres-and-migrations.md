# ADR 0003: PostgreSQL with Doctrine migrations

Status: accepted

## Context

We need a relational database with transactions and row locks (refresh token
rotation) and a safe way to evolve the schema.

## Decision

* PostgreSQL 17 in all environments, including CI (a service container), so
  tests run on the engine used in production. No SQLite in tests.
* Schema changes only through Doctrine migrations that are **generated**
  (`make db-diff`) from entity mappings, reviewed and committed. CI runs
  `doctrine:migrations:migrate` and `doctrine:schema:validate`, which fails
  when entities and migrations disagree.
* UUID v7 primary keys (time-ordered, good index locality), generated in the
  application.
* Timestamps are `datetime_immutable`; the clock is injected for tests.

## Consequences

* Row locking and unique constraints behave in tests as in production.
* The functional test suite needs a running Postgres (`make up`).
* Migrations are forward only; a rollback is a new migration.
