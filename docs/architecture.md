# Architecture

## Layout

```
src/
  Shared/
    Application/Transaction.php            port: run code atomically
    Infrastructure/Doctrine/               DoctrineTransaction
    Infrastructure/Http/                   ApiExceptionListener, SecurityHeadersListener, HealthController
  User/
    Domain/                                User entity, UserRepository, EmailAlreadyRegistered
    Application/RegisterUserHandler.php
    Infrastructure/Doctrine/               DoctrineUserRepository
    Infrastructure/Security/               UserProvider (loads user from JWT subject)
    Ui/Http/MeController.php
  Auth/
    Domain/                                RefreshToken entity, RefreshTokenRepository, exceptions
    Application/                           Login/Refresh/Logout/RegisterAccount handlers, TokenPairIssuer,
                                           AccessTokenIssuer (port), TokenPair, OpaqueToken
    Infrastructure/Doctrine/               DoctrineRefreshTokenRepository
    Infrastructure/Jwt/                    LexikAccessTokenIssuer
    Infrastructure/Http/                   AuthRateLimiter
    Infrastructure/Console/                PurgeExpiredRefreshTokensCommand
    Ui/Http/                               controllers + Request DTOs
```

## Dependency rule

```
Ui  ->  Application  ->  Domain
              ^             ^
              |             |
        Infrastructure -----+
```

Application defines what it needs (`UserRepository`, `AccessTokenIssuer`,
`Transaction`), Infrastructure provides it. Swapping Lexik for another JWT
library, or Doctrine for something else, touches only Infrastructure. Unit
tests prove the point: they run the handlers with in-memory fakes, no kernel.

## Pragmatic exceptions

* Entities carry Doctrine attributes and implement Symfony's `UserInterface`.
  A fully separate persistence model would double the classes for no benefit
  in a project of this size. The mapping is declared in `config/packages/doctrine.yaml`
  per module (`src/User/Domain`, `src/Auth/Domain`).
* `RefreshHandler` and `RegisterAccountHandler` use the `Transaction` port, an
  abstraction over Doctrine's `wrapInTransaction`, so the application layer
  stays free of Doctrine.

## Request flow

```
HTTP -> Caddy (TLS) -> FrankenPHP -> Symfony kernel
  -> firewall "public" (auth, health) | firewall "api" (JWT required)
  -> controller: rate limit -> handler -> response
  -> ApiExceptionListener turns exceptions into problem+json
  -> SecurityHeadersListener adds headers
```

## Token lifecycle

```
login/register ──► access JWT (15 min) + refresh R1 (family F)
                        │
   access expires ──► POST /auth/refresh {R1}
                        │   lock R1 row, mark used, issue R2 in family F
                        ▼
                   access JWT + refresh R2
                        │
   R1 presented again ──► reuse detected ──► revoke whole family F ──► 401
   logout {R2} ──► revoke family F ──► 204
```

## Error format

All errors are `application/problem+json`:

```json
{ "type": "about:blank", "title": "Unauthorized", "status": 401, "detail": "Invalid credentials." }
```

Validation errors (422) add `violations: [{field, message}]`. 5xx responses never
contain internal details outside debug mode. One known inconsistency: the 401
for a missing or invalid *access token* is produced by Lexik and has the shape
`{"code":401,"message":"..."}`. The app treats any 401 the same way, so it is
documented rather than patched.
