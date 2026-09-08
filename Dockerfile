# =========================================================
# Stage 1: Composer / PHP Dependencies
# =========================================================

FROM composer:latest AS php-deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --optimize-autoloader \
    --no-scripts


# =========================================================
# Stage 2: Frontend Build
# =========================================================

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build


# =========================================================
# Stage 3: Production Image
# =========================================================

FROM dunglas/frankenphp:1-php8.5-alpine AS production

ARG USER_UID=1000
ARG USER_GID=1000

# Gruppe und User mit Host-UID/GID anlegen
RUN addgroup -g ${USER_GID} web && \
    adduser -u ${USER_UID} -G web -s /bin/sh -D web

RUN install-php-extensions \
    pdo_pgsql \
    redis \
    exif \
    pcntl \
    bcmath \
    gd

WORKDIR /app

# Application source
COPY . .

# PHP dependencies
COPY --from=php-deps /app/vendor ./vendor

# Compiled Vite assets
COPY --from=frontend /app/public/build ./public/build

# Laravel runtime directories
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        /data/caddy \
        /config/caddy \
        bootstrap/cache \
    && chown -R web:web storage bootstrap/cache /data/caddy /config/caddy \
    && chmod -R 775 storage bootstrap/cache

COPY .deploy/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

USER web

ENTRYPOINT ["/entrypoint.sh"]
