FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
      git \
      unzip \
      libicu-dev \
      libzip-dev \
      libonig-dev \
      libpq-dev \
    && docker-php-ext-install intl pdo zip pdo_pgsql \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Leverage cache for deps
COPY backend/composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress || true

# App code
COPY backend/ ./

# our entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
 && mkdir -p storage/framework/{cache,views,sessions} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

