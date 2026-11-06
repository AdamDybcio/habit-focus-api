# ADR 0001: Own Symfony backend instead of Firebase or Supabase

Status: accepted

## Context

The Flutter app needs accounts, sync and later habit storage. Firebase and
Supabase would deliver auth in an afternoon.

## Decision

Write a Symfony API with its own authentication.

## Consequences

* Full control and understanding of the token flow, security and data model;
  this is the point of the project (portfolio, learning, interview topics).
* We own operations: hosting, backups, patching, rate limiting.
* More code to maintain than a BaaS. Accepted.
* The contract (`/auth/login`, `/auth/refresh`) is simple, so moving to a BaaS
  later would only change the app's data source.
