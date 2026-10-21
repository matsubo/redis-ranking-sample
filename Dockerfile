# Tailwind scans public/ and php/templates/ for class names and writes
# assets/css/app.css.
FROM node:24-alpine AS css

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY src src
COPY public public
COPY php/templates php/templates
RUN npm run build

FROM php:8.5-apache AS base

# phpredis from PECL, pinned. The tag php:8.5-apache follows the 8.5 patch
# releases, so rebuilding the image picks up PHP security fixes.
RUN pecl install redis-6.3.0 \
 && docker-php-ext-enable redis \
 && rm -rf /tmp/pear

# Composer and the dev tools (php-cs-fixer, PHPStan, PHPUnit). compose.yaml's
# `php` service runs this stage with the repo mounted:
#   docker compose run --rm php install / check / fix
FROM base AS dev

# Current composer images no longer fall back to git when a dist zip cannot be
# extracted, so unzip is required.
RUN apt-get update -q && apt-get install -yq --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

FROM dev AS vendor

WORKDIR /var/www/php
COPY php/composer.json php/composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts --no-progress
COPY php/src src
RUN composer dump-autoload --no-dev --optimize

FROM base AS server

# Links the GHCR package to this repository (and its Actions token).
LABEL org.opencontainers.image.source=https://github.com/matsubo/redis-ranking-sample

# Don't advertise the PHP version in X-Powered-By.
RUN printf 'expose_php = Off\n' > "$PHP_INI_DIR/conf.d/zz-app.ini"

# Cache headers for static files, no version in Server / error pages.
# zz- so it loads after Debian's security.conf, which sets ServerTokens OS.
COPY deploy/apache.conf /etc/apache2/conf-available/zz-app.conf
RUN a2enmod headers && a2enconf zz-app

WORKDIR /var/www/html
COPY php /var/www/php
COPY --from=vendor /var/www/php/vendor /var/www/php/vendor
COPY public /var/www/html
COPY --from=css /app/public/assets/css/app.css /var/www/html/assets/css/app.css
