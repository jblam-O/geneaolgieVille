FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json tsconfig.json vite.config.ts ./
COPY frontend ./frontend
RUN npm ci && npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.4-apache
WORKDIR /var/www/html

RUN a2enmod rewrite headers expires
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install intl pdo pdo_mysql opcache zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app/vendor /var/www/html/vendor
COPY . /var/www/html
COPY --from=frontend /app/public/genealogy /var/www/html/public/genealogy
COPY docker/entrypoint.sh /usr/local/bin/genealogie-entrypoint

RUN touch /var/www/html/.env \
    && mkdir -p /var/www/html/var /var/www/html/public/uploads
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads || true

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

RUN chmod +x /usr/local/bin/genealogie-entrypoint
ENTRYPOINT ["/usr/local/bin/genealogie-entrypoint"]

EXPOSE 80
