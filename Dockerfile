# syntax=docker/dockerfile:1

FROM php:8.3-fpm-bookworm AS base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    gosu \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    netcat-openbsd \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package.json ./

RUN npm install

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM base AS production

COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/healthcheck.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx

RUN apk add --no-cache wget

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public

EXPOSE 80
