# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Étape 2: Image finale pour l'application
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html

COPY --from=composer-build /app/vendor ./vendor
COPY . .

RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# ✅ Fichier .env adapté à Railway
RUN echo "APP_NAME=Laravel" > .env && \
    echo "APP_ENV=local" >> .env && \
    echo "APP_KEY=base64:BmopHdpkEyJ71+zT0RiqryPWH7A4HK9D5T/WCy6UKfQ=" >> .env && \
    echo "APP_DEBUG=true" >> .env && \
    echo "APP_URL=http://localhost" >> .env && \
    echo "" >> .env && \
    echo "LOG_CHANNEL=stack" >> .env && \
    echo "LOG_DEPRECATIONS_CHANNEL=null" >> .env && \
    echo "LOG_LEVEL=debug" >> .env && \
    echo "" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=metro.proxy.rlwy.net" >> .env && \
    echo "DB_PORT=36926" >> .env && \
    echo "DB_DATABASE=railway" >> .env && \
    echo "DB_USERNAME=postgres" >> .env && \
    echo "DB_PASSWORD=zZotXUbMIQJIneqRhcYlTjzksIaCMfeW" >> .env && \
    echo "" >> .env && \
    echo "BROADCAST_DRIVER=log" >> .env && \
    echo "CACHE_DRIVER=file" >> .env && \
    echo "FILESYSTEM_DISK=local" >> .env && \
    echo "QUEUE_CONNECTION=sync" >> .env && \
    echo "SESSION_DRIVER=file" >> .env && \
    echo "SESSION_LIFETIME=120" >> .env && \
    echo "" >> .env && \
    echo "MEMCACHED_HOST=127.0.0.1" >> .env && \
    echo "REDIS_HOST=127.0.0.1" >> .env && \
    echo "REDIS_PASSWORD=null" >> .env && \
    echo "REDIS_PORT=6379" >> .env && \
    echo "" >> .env && \
    echo "MAIL_MAILER=smtp" >> .env && \
    echo "MAIL_HOST=mailpit" >> .env && \
    echo "MAIL_PORT=1025" >> .env && \
    echo "MAIL_USERNAME=null" >> .env && \
    echo "MAIL_PASSWORD=null" >> .env && \
    echo "MAIL_ENCRYPTION=null" >> .env && \
    echo "MAIL_FROM_ADDRESS=hello@example.com" >> .env && \
    echo "MAIL_FROM_NAME=Laravel" >> .env && \
    echo "" >> .env && \
    echo "AWS_ACCESS_KEY_ID=" >> .env && \
    echo "AWS_SECRET_ACCESS_KEY=" >> .env && \
    echo "AWS_DEFAULT_REGION=us-east-1" >> .env && \
    echo "AWS_BUCKET=" >> .env && \
    echo "AWS_USE_PATH_STYLE_ENDPOINT=false" >> .env && \
    echo "" >> .env && \
    echo "PUSHER_APP_ID=" >> .env && \
    echo "PUSHER_APP_KEY=" >> .env && \
    echo "PUSHER_APP_SECRET=" >> .env && \
    echo "PUSHER_HOST=" >> .env && \
    echo "PUSHER_PORT=443" >> .env && \
    echo "PUSHER_SCHEME=https" >> .env && \
    echo "PUSHER_APP_CLUSTER=mt1" >> .env && \
    echo "" >> .env && \
    echo "VITE_APP_NAME=Laravel" >> .env && \
    echo "VITE_PUSHER_APP_KEY=" >> .env && \
    echo "VITE_PUSHER_HOST=" >> .env && \
    echo "VITE_PUSHER_PORT=443" >> .env && \
    echo "VITE_PUSHER_SCHEME=https" >> .env && \
    echo "VITE_PUSHER_APP_CLUSTER=mt1" >> .env

RUN chown laravel:laravel .env

USER laravel
RUN php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan l5-swagger:generate
USER root

# Copier le fichier Swagger généré vers public/storage
RUN mkdir -p public/storage && \
    cp storage/api-docs/api-docs.json public/storage/api-docs.json

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER laravel

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
