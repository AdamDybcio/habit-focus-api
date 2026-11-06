# Deployment

Target: a Linux VM with Docker, running one Caddy on the host for TLS and
routing several sites. The API container listens only on `127.0.0.1:8080`,
Postgres is reachable only inside the compose network.

## One-time setup on the server

```bash
git clone https://github.com/<you>/habit-focus-api.git /opt/habit-focus-api
cd /opt/habit-focus-api

# Secrets
cp .env.prod.example .env.prod.local
chmod 600 .env.prod.local
openssl rand -hex 32   # -> APP_SECRET
openssl rand -hex 24   # -> POSTGRES_PASSWORD
# choose JWT_PASSPHRASE, fill TRUSTED_PROXIES (see below)
$EDITOR .env.prod.local

# JWT keys (once; keep a backup of config/jwt outside the server)
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem \
  -aes256 -pass pass:"$JWT_PASSPHRASE" -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -passin pass:"$JWT_PASSPHRASE" \
  -pubout -out config/jwt/public.pem
sudo chown -R 1000:1000 config/jwt && chmod 600 config/jwt/private.pem
```

`JWT_PASSPHRASE` in the commands is the value from `.env.prod.local`
(`set -a; . ./.env.prod.local; set +a` loads it into the shell).

## Start / update

```bash
docker compose -f compose.prod.yaml --env-file .env.prod.local up -d --build
docker compose -f compose.prod.yaml --env-file .env.prod.local exec app \
  php bin/console doctrine:migrations:migrate --no-interaction
curl -s http://127.0.0.1:8080/health
```

Migrations are run explicitly and not at container start: you decide when the
schema changes and a failing migration does not crash-loop the app.

Update = `git pull`, then the same two commands.

## Caddy on the host

```caddyfile
api.example.com {
	encode zstd gzip
	reverse_proxy 127.0.0.1:8080
}
```

Caddy gets the certificate automatically and sets `X-Forwarded-*`. The app
must trust it: `TRUSTED_PROXIES` has to contain the address from which the
container sees Caddy. With Docker's default bridge the host appears as the
bridge gateway (usually `172.x.0.1`); the example file lists the private
ranges. Check with `docker compose ... logs app` or by calling the API
and inspecting the client IP used by the rate limiter. Without a correct value
all clients share one IP, and the IP rate limit would hit everyone at once.

## Housekeeping

Expired refresh tokens are removed by a console command. Run it daily:

```cron
15 3 * * * cd /opt/habit-focus-api && docker compose -f compose.prod.yaml --env-file .env.prod.local exec -T app php bin/console app:refresh-tokens:purge
```

Backups (daily, keep a few weeks, copy off the machine):

```bash
docker compose -f compose.prod.yaml --env-file .env.prod.local exec -T database \
  pg_dump -U app app | gzip > /var/backups/habit-focus-$(date +%F).sql.gz
```

Restore: `gunzip -c backup.sql.gz | docker compose ... exec -T database psql -U app app`.
Test a restore at least once.

## Rollback

Checkout the previous git tag/commit and run `up -d --build`. Migrations are
forward only: for a destructive migration write a new migration that undoes it.

## Checklist before going public

* [ ] HTTPS works, HTTP redirects (Caddy does it by default)
* [ ] `.env.prod.local` mode 600, not in git
* [ ] JWT keys backed up, private key not readable by others
* [ ] `curl https://api.example.com/health` returns ok; `/me` without token returns 401
* [ ] Rate limit sees real client IPs (`TRUSTED_PROXIES`)
* [ ] Daily purge and backup jobs installed and tested
