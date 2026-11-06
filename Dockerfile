# syntax=docker/dockerfile:1

# Production image of the API, served by FrankenPHP (Caddy + PHP in one binary).
# Build:  docker build -t habit-focus-api .
# The tag below follows the "1-php8.4" line; pin an exact version/digest if you
# need fully reproducible builds.

FROM dunglas/frankenphp:1-php8.4 AS base

WORKDIR /app

RUN install-php-extensions pdo_pgsql intl opcache zip

# Run as an unprivileged user. Port 8080 is above 1024, so no special
# capability is needed and the one set on the binary is removed.
RUN setcap -r /usr/local/bin/frankenphp || true \
    && useradd --uid 1000 --create-home --shell /usr/sbin/nologin app \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R app:app /data /config /app

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# ---- Composer dependencies (production only) ----
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup \
    && chown -R app:app var

# ---- Final image ----
FROM base AS prod

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY --from=vendor --chown=app:app /app /app

USER app
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/health") === false ? 1 : 0);'
