# syntax=docker/dockerfile:1

# --------------------------------------------------
# Stage 1: Install PHP dependencies with Composer
# --------------------------------------------------
FROM php:8.4-cli AS vendor

WORKDIR /app

# intl is required by filament/support during composer install
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    unzip \
    pkg-config \
    && docker-php-ext-install intl zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

# --------------------------------------------------
# Stage 2: Build frontend assets with Vite
# --------------------------------------------------
FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# Tailwind scans Blade/PHP sources (including vendor views), so the full
# application plus Composer vendor directory must be present for the build.
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --------------------------------------------------
# Stage 3: Final Apache + PHP runtime image
# --------------------------------------------------
FROM php:8.4-apache

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
    libicu-dev \
    pkg-config \
    zip \
    unzip \
    git \
    curl \
    supervisor \
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
    && docker-php-ext-install opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# phpredis rather than predis: a C extension keeps Redis off the composer
# dependency list and is materially faster on the hot cache path.
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Enable Apache rewrite module and set document root
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf

# Laravel's public/.htaccess rewrite rules require AllowOverride, which the
# base image sets to None; without this every non-root route returns 404.
COPY <<'EOF' /etc/apache2/conf-available/laravel.conf
<Directory /var/www/html/public>
    AllowOverride All
    Require all granted
</Directory>
EOF
RUN a2enconf laravel

WORKDIR /var/www/html

# Copy application code, vendor dependencies, and built frontend assets
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

# Supervisor runs the web server, queue worker, and scheduler in one
# container, which suits a single small VPS deployment.
COPY <<'EOF' /etc/supervisor/conf.d/app.conf
[supervisord]
nodaemon=true
user=root
logfile=/dev/null
logfile_maxbytes=0

[program:apache]
command=apache2-foreground
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true

[program:queue-worker]
command=php /var/www/html/artisan queue:work --tries=3 --max-time=3600 --sleep=3
user=www-data
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
stopwaitsecs=3600

[program:scheduler]
command=sh -c 'while true; do php /var/www/html/artisan schedule:run --no-interaction; sleep 60; done'
user=www-data
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
EOF

COPY <<'EOF' /usr/local/bin/app-entrypoint.sh
#!/bin/sh
set -e

php artisan storage:link --force
php artisan config:cache
php artisan view:cache
php artisan migrate --force

exec supervisord -c /etc/supervisor/supervisord.conf
EOF
RUN chmod +x /usr/local/bin/app-entrypoint.sh

EXPOSE 80

CMD ["app-entrypoint.sh"]
