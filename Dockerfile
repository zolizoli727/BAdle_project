FROM php:8.2-apache

RUN a2enmod rewrite

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libpq-dev \
    libzip-dev \
    zip && \
    docker-php-ext-install pdo pdo_pgsql zip

COPY . .

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN rm -rf vendor/

# composer scripts tiltása
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist

RUN mkdir -p storage/logs storage/framework bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

EXPOSE 8080

CMD ["sh", "-c", "php artisan optimize && php artisan migrate --force && apache2-foreground"]
