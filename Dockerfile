# syntax=docker/dockerfile:1

# --------------------------------------------------
# Stage 1: Build frontend assets with Node
# --------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .
RUN npm run build

# --------------------------------------------------
# Stage 2: Install PHP dependencies with Composer
# --------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

# --------------------------------------------------
# Stage 3: Final Apache + PHP runtime image
# --------------------------------------------------
FROM php:8.3-apache

# Install required system and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module and set document root
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application code and built artifacts
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Ensure Laravel can write to required directories
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Discover packages and prepare Filament assets using a temporary app key.
RUN APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));" | tr -d '\n') \
    php artisan package:discover --ansi \
    && APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));" | tr -d '\n') \
    php artisan filament:upgrade --ansi

EXPOSE 80

# Free-tier Render web services do not support pre-deploy commands, so we
# run migrations as part of the container startup instead.
CMD ["sh", "-c", "php artisan migrate --force && exec apache2-foreground"]
