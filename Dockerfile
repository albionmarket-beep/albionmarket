# Stage 1 - Composer Dependencies
FROM php:8.3-cli AS composer

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev \
    && docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2 - Frontend Build
FROM node:22 AS frontend

WORKDIR /app

COPY . .

# Copy vendor folder from composer stage
COPY --from=composer /app/vendor ./vendor

RUN npm install
RUN npm run build

# Stage 3 - Production
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        exif \
        pcntl \
        gd

WORKDIR /var/www

COPY . .

COPY --from=composer /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN php artisan config:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true

EXPOSE 9000

CMD ["php-fpm"]