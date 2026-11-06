# API

Base URL: `https://<host>` (production) or `http://127.0.0.1:8000` (local).
All bodies are JSON (`Content-Type: application/json`). Machine-readable spec:
[openapi.yaml](openapi.yaml).

## Token response

`/auth/register`, `/auth/login` and `/auth/refresh` return:

```json
{
  "accessToken": "eyJ...",
  "refreshToken": "q3u0...",
  "expiresIn": 900
}
```

* `accessToken`: JWT, send as `Authorization: Bearer <token>`. Lives 15 minutes.
* `refreshToken`: opaque, single use, lives 30 days. Store it in secure storage.
* `expiresIn`: access token lifetime in seconds.

## Endpoints

### POST /auth/register

Body: `{ "email": "ada@example.com", "password": "at least 10 chars" }`

| Status | Meaning |
| ------ | ------- |
| 201 | Account created, token response |
| 409 | Email already registered |
| 422 | Validation failed (`violations` lists fields) |
| 429 | Rate limited |

Email is case-insensitive. Password: 10 to 128 characters.

### POST /auth/login

Body: `{ "email": "...", "password": "..." }`

| Status | Meaning |
| ------ | ------- |
| 200 | Token response |
| 401 | Wrong email or password (the same answer for both) |
| 422 | Missing or oversized fields |
| 429 | Rate limited (see below) |

### POST /auth/refresh

Body: `{ "refreshToken": "..." }`. Returns a **new** pair; the sent token is
dead afterwards.

| Status | Meaning |
| ------ | ------- |
| 200 | Token response |
| 401 | Unknown, expired, revoked or already used token. A reused token also ends the whole session |
| 429 | Rate limited |

Client rule: after a 401 here, delete local tokens and show the login screen.
After a network error or 5xx, keep the tokens and retry later: the token was
possibly not consumed. If the response was lost *after* the server rotated the
token, the retry looks like a reuse and ends the session; the user logs in
again (see ADR 0002).

### POST /auth/logout

Body: `{ "refreshToken": "..." }`. Always `204`, also for unknown tokens.

### GET /me

Requires a Bearer access token. Returns `{ "id", "email", "createdAt" }`.
`401` when the token is missing, invalid or expired.

### GET /health

`200 {"status":"ok"}`. Does not touch the database.

## Rate limits

Per client IP unless noted, sliding window:

| Endpoint | Limit |
| -------- | ----- |
| login | 10 / minute per IP, 5 / minute per email |
| refresh, logout | 30 / minute |
| register | 5 / hour |

`429` carries a `Retry-After` header (seconds).

## Error shape

```json
{ "type": "about:blank", "title": "Unprocessable Entity", "status": 422,
  "detail": "...", "violations": [{ "field": "password", "message": "..." }] }
```
