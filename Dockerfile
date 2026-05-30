# ==========================
# Stage 1 - Composer
# ==========================
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

COPY . .

# ==========================
# Stage 2 - Frontend Build
# ==========================
FROM node:22 AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

# IMPORTANT: copy vendor folder from composer stage
COPY --from=composer /app/vendor ./vendor

RUN npm run build

# ==========================
# Stage 3 - Production
# ==========================
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-configure gd \
       --with-freetype \
       --with-jpeg \
    && docker-php-ext-install \
       pdo_mysql \
       mbstring \
       zip \
       bcmath \
       exif \
       gd

WORKDIR /var/www

COPY --from=composer /app /var/www

COPY --from=frontend /app/public/build /var/www/public/build

RUN chown -R www-data:www-data storage bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]