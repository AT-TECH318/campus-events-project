FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public bootstrap/cache \
    && ln -sfn ../storage/app/public /var/www/html/public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && sed -i 's/\r$//' /var/www/html/docker/entrypoint.sh \
    && chmod 755 /var/www/html/docker/entrypoint.sh

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

ENV APP_ENV=production

EXPOSE 80

CMD ["/var/www/html/docker/entrypoint.sh"]
