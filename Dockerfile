FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p var/covers storage/framework/cache storage/framework/views storage/logs
ENV SESSION_DRIVER=array CACHE_STORE=array QUEUE_CONNECTION=sync LOG_CHANNEL=stderr
# Let uploads above 2 MB reach the app so it answers 422 itself (CA-03.4, T-15).
RUN echo "upload_max_filesize=8M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=8M" >> /usr/local/etc/php/conf.d/uploads.ini
EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force && php artisan library:create-admin && php artisan serve --host=0.0.0.0 --port=8000"]
