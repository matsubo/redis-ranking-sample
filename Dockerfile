# Tailwind scans public/ for class names and writes assets/css/app.css.
FROM node:24-alpine AS css

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY src src
COPY public public
RUN npm run build

FROM php:8.5-apache AS lib

# Current composer images no longer fall back to git when a dist zip cannot be
# extracted, so unzip is required.
RUN apt update -q && apt install git unzip -yq \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

COPY ./php/composer.json /tmp
COPY ./php/composer.lock /tmp

WORKDIR /tmp

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install

FROM php:8.5-apache AS server

WORKDIR /var/www/html/

# Links the GHCR package to this repository (and its Actions token).
LABEL org.opencontainers.image.source=https://github.com/matsubo/redis-ranking-sample

# phpredis from PECL, pinned. The tag php:8.5-apache follows the 8.5 patch
# releases, so rebuilding the image picks up PHP security fixes.
RUN pecl install redis-6.3.0 \
 && docker-php-ext-enable redis \
 && rm -rf /tmp/pear

COPY --from=lib /tmp/vendor /var/www/vendor

COPY public /var/www/html
COPY --from=css /app/public/assets/css/app.css /var/www/html/assets/css/app.css
COPY php /var/www/php
